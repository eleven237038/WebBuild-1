<?php
class ModelToolImage extends Model {
	// Per-request memo + APCu cross-request cache for resize() results. Each resize()
	// call otherwise does ~5 stat/realpath/filemtime syscalls; with 40+ images per
	// page that is a large chunk of PHP time on Docker Desktop's slow FS. The resized
	// file path is deterministic from (filename, w, h), so the result is safe to cache.
	private static $memo = array();

	public function resize($filename, $width = 100, $height = 100, $placeholder = true) {
		$width = (int)$width;
		$height = (int)$height;
		$mkey = $filename . '|' . $width . 'x' . $height;

		// 1) per-request memo
		if (isset(self::$memo[$mkey])) {
			return self::$memo[$mkey];
		}

		// 2) APCu cross-request cache (5 min TTL; cleared image cache just causes a
		//    short stale window until TTL expiry / opcache-reset, acceptable for dev).
		$apcu_key = 'oc_img:' . $mkey;
		if (function_exists('apcu_fetch') && false !== ($cached = apcu_fetch($apcu_key, $ok)) && $ok) {
			return self::$memo[$mkey] = $cached;
		}

		if (!is_file(DIR_IMAGE . $filename) || substr(str_replace('\\', '/', realpath(DIR_IMAGE . $filename)), 0, strlen(DIR_IMAGE)) != str_replace('\\', '/', DIR_IMAGE)) {
			if ($placeholder) {
				$filename = 'placeholder.png';
			} else {
				return;
			}
		}

		$extension = pathinfo($filename, PATHINFO_EXTENSION);

		$image_old = $filename;
		$image_new = 'cache/' . utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '-' . $width . 'x' . $height . '.' . $extension;

		if (!is_file(DIR_IMAGE . $image_new) || (filemtime(DIR_IMAGE . $image_old) > filemtime(DIR_IMAGE . $image_new))) {
			list($width_orig, $height_orig, $image_type) = getimagesize(DIR_IMAGE . $image_old);

			if (!in_array($image_type, array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF))) {
				return DIR_IMAGE . $image_old;
			}

			$path = '';

			$directories = explode('/', dirname($image_new));

			foreach ($directories as $directory) {
				$path = $path . '/' . $directory;

				if (!is_dir(DIR_IMAGE . $path)) {
					@mkdir(DIR_IMAGE . $path, 0777);
				}
			}

			if ($width_orig != $width || $height_orig != $height) {
				$image = new Image(DIR_IMAGE . $image_old);
				$image->resize($width, $height);
				$image->save(DIR_IMAGE . $image_new);
			} else {
				copy(DIR_IMAGE . $image_old, DIR_IMAGE . $image_new);
			}
		}

		$image_new = str_replace(' ', '%20', $image_new);  // fix bug when attach image on email (gmail.com). it is automatic changing space " " to +

		$url = $this->config->get('config_url') . 'image/' . $image_new;

		self::$memo[$mkey] = $url;
		if (function_exists('apcu_store')) {
			apcu_store($apcu_key, $url, 300);
		}

		return $url;
	}
}
