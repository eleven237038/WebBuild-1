<?php
/**
 * @package        OpenCart
 * @author        Daniel Kerr
 * @copyright    Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license        https://opensource.org/licenses/GPL-3.0
 * @link        https://www.opencart.com
 */

/**
 * Action class
 */
class Action {
	private $id;
	private $route;
	private $method = 'index';

	/**
	 * Constructor
	 *
	 * @param    string $route
	 */
	public function __construct($route) {
		$this->id = $route;

		// APCu-cached route resolution. The is_file() loop below stats the
		// Windows Docker volume mount (~3ms each); event registration alone
		// creates dozens of Actions per request. Cache the resolved
		// route/method per (context, route) and skip the loop on hit.
		// Cleared by apcu_clear_cache() (opcache-reset.php) after code changes.
		static $__ctx = '';
		if ($__ctx === '') { $__ctx = (is_admin() ? 'a' : 'c'); }
		$__ck = 'actr:' . $__ctx . ':' . $route;
		if ($__ctx !== '' && function_exists('apcu_fetch')) {
			$__cached = apcu_fetch($__ck, $__hit);
			if ($__hit) {
				$this->route = $__cached['r'];
				$this->method = $__cached['m'];
				return;
			}
		}

		$this->method = 'index';
		$parts = explode('/', preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route));

		// Break apart the route
		while ($parts) {
			$file = DIR_APPLICATION . 'controller/' . implode('/', $parts) . '.php';

			if (is_file($file)) {
				$this->route = implode('/', $parts);

				break;
			} else {
				$this->method = array_pop($parts);
			}
		}

		if (function_exists('apcu_store')) {
			apcu_store($__ck, array('r' => isset($this->route) ? $this->route : null, 'm' => $this->method), 3600);
		}
	}

	/**
	 *
	 *
	 * @return    string
	 *
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 *
	 *
	 * @param    object $registry
	 * @param    array $args
	 */
	public function execute($registry, array $args = array()) {
		// Stop any magical methods being called
		if (substr($this->method, 0, 2) == '__') {
			return new \Exception('Error: Calls to magic methods are not allowed!');
		}

		$file = DIR_APPLICATION . 'controller/' . $this->route . '.php';
		$class = 'Controller' . preg_replace('/[^a-zA-Z0-9]/', '', $this->route);

		// Initialize the class
		if (is_file($file)) {
			include_once($file);

			$controller = new $class($registry);
		} else {
			return new \Exception('Error: Could not call ' . $this->route . '/' . $this->method . '!');
		}

		$reflection = new ReflectionClass($class);

		if ($reflection->hasMethod($this->method) && $reflection->getMethod($this->method)->getNumberOfRequiredParameters() <= count($args)) {
			return call_user_func_array(array($controller, $this->method), $args);
		} else {
			return new \Exception('Error: Could not call ' . $this->route . '/' . $this->method . '!');
		}
	}
}
