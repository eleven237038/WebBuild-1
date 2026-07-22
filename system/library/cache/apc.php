<?php
namespace Cache;
class APC {
	private $expire;
	private $active = false;
	private $prefix;

	public function __construct($expire) {
		$this->expire = $expire;
		$this->prefix = defined('CACHE_PREFIX') ? CACHE_PREFIX : 'oc_';
		$this->active = function_exists('apcu_fetch') && (ini_get('apc.enabled') || PHP_SAPI === 'cli');
	}

	public function get($key) {
		if (!$this->active) {
			return false;
		}

		$success = false;
		$value = apcu_fetch($this->prefix . $key, $success);

		return $success ? $value : false;
	}

	public function set($key, $value) {
		if (!$this->active) {
			return false;
		}

		return apcu_store($this->prefix . $key, $value, $this->expire);
	}

	public function delete($key) {
		if (!$this->active) {
			return false;
		}

		// OpenCart deletes by key prefix (e.g. delete('product') removes product.1, product.latest, ...).
		// APCUIterator matches all keys beginning with the prefix in one call.
		if (class_exists('APCUIterator')) {
			apcu_delete(new \APCUIterator('/^' . preg_quote($this->prefix . $key, '/') . '/'));
		} else {
			apcu_delete($this->prefix . $key);
		}
	}
}
