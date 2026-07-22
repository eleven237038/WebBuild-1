<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* Language class
*/
class Language {
	private $default = 'en-gb';
	private $directory;
	public $data = array();

	/**
	 * Constructor
	 *
	 * @param	string	$file
	 *
 	*/
	public function __construct($directory = '') {
		$this->directory = $directory;
	}

	/**
     *
     *
     * @param	string	$key
	 *
	 * @return	string
     */
	public function get($key) {
		return (isset($this->data[$key]) ? $this->data[$key] : $key);
	}

	/**
     *
     *
     * @param	string	$key
	 * @param	string	$value
     */
	public function set($key, $value) {
		$this->data[$key] = $value;
	}

	/**
     *
     *
	 * @return	array
     */
	public function all() {
		return $this->data;
	}

	/**
     *
     *
     * @param	string	$filename
	 * @param	string	$key
	 *
	 * @return	array
     */
	public function load($filename, $key = '') {
		if (!$key) {
			$_ = array();

			// APCu-cache the merged language array. Each load otherwise does two
			// is_file()+require() on the slow Windows Docker mount (~3ms/stat),
			// and load() fires once per controller (header, column_left, footer,
			// the page...). Cleared by apcu_clear_cache() (opcache-reset.php).
			$__ck = 'oclang:' . $this->default . ':' . $this->directory . ':' . $filename;
			$__hit = false;
			if (function_exists('apcu_fetch')) {
				$__cached = apcu_fetch($__ck, $__hit);
				if ($__hit) {
					$_ = $__cached;
				}
			}

			if (!$__hit) {
				$file = DIR_LANGUAGE . $this->default . '/' . $filename . '.php';

				if (is_file($file)) {
					require($file);
				}

				$file = DIR_LANGUAGE . $this->directory . '/' . $filename . '.php';

				if (is_file($file)) {
					require($file);
				}

				if (function_exists('apcu_store')) {
					apcu_store($__ck, $_, 3600);
				}
			}

			$this->data = array_merge($this->data, $_);
		} else {
			// Put the language into a sub key
			$this->data[$key] = new Language($this->directory);
			$this->data[$key]->load($filename);
		}

		return $this->data;
	}
}
