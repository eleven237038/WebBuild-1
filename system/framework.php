<?php
// Registry
$registry = Registry::getSingleton();

// Config
$config = new Config();

// Load the default config
$config->load('default');
$config->load($application_config);
$registry->set('config', $config);

// Log
$log = new Log($config->get('error_filename'));
$registry->set('log', $log);

date_default_timezone_set($config->get('date_timezone'));

set_error_handler(function ($code, $message, $file, $line) use ($log, $config) {
    // error suppressed with @
    if (error_reporting() === 0) {
        return false;
    }

    switch ($code) {
        case E_NOTICE:
        case E_USER_NOTICE:
            $error = 'Notice';
            break;
        case E_WARNING:
        case E_USER_WARNING:
            $error = 'Warning';
            break;
        case E_ERROR:
        case E_USER_ERROR:
            $error = 'Fatal Error';
            break;
        default:
            $error = 'Unknown';
            break;
    }

    if ($config->get('error_display')) {
        echo '<b>' . $error . '</b>: ' . $message . ' in <b>' . $file . '</b> on line <b>' . $line . '</b>';
    }

    if ($config->get('error_log')) {
        $log->write('PHP ' . $error . ':  ' . $message . ' in ' . $file . ' on line ' . $line);
    }

    return true;
});

// Event
$event = new Event($registry);
$registry->set('event', $event);

// Event Register
if ($config->has('action_event')) {
    foreach ($config->get('action_event') as $key => $value) {
        foreach ($value as $priority => $action) {
            $event->register($key, new Action($action), $priority);
        }
    }
}

// Loader
$loader = new Loader($registry);
$registry->set('load', $loader);

// Request
$registry->set('request', new Request());

// Response
$response = new Response();
$response->addHeader('Content-Type: text/html; charset=utf-8');
$response->setCompression($config->get('config_compression'));
$registry->set('response', $response);

// Database
if ($config->get('db_autostart')) {
    $registry->set('db', new DB($config->get('db_engine'), $config->get('db_hostname'), $config->get('db_username'), $config->get('db_password'), $config->get('db_database'), $config->get('db_port')));
}

// DebugBar / Whoops - dev only. Building StandardDebugBar and its collectors (which
// open a second PDO connection) on every production request is pure overhead, so the
// whole block is gated on is_debug().
if (is_debug()) {
    if (class_exists(\DebugBar\StandardDebugBar::class)) {
        $debugBar = new \DebugBar\StandardDebugBar();

        $cap = \Models\Base::ensureCapsule();
        if ($cap) {
            $debugBar->addCollector(new Mpdo\Collector());
            $debugBar->addCollector(new Eloquent\Collector($cap));
        }

        $serverUrl = is_admin() ? HTTPS_CATALOG : HTTPS_SERVER;
        $baseUrl = $serverUrl . 'vendor/maximebf/debugbar/src/DebugBar/Resources';
        $registry->set('debug_bar', $debugBar->getJavascriptRenderer($baseUrl));
    }

    if (class_exists(\Whoops\Run::class)) {
        $whoops = new \Whoops\Run;
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
        $whoops->register();
        $registry->set('whoops', $whoops);
    }
}

// Session
$session = new Session($config->get('session_engine'), $registry);
$registry->set('session', $session);

if ($config->get('session_autostart')) {
    /*
    We are adding the session cookie outside of the session class as I believe
    PHP messed up in a big way handling sessions. Why in the hell is it so hard to
    have more than one concurrent session using cookies!

    Is it not better to have multiple cookies when accessing parts of the system
    that requires different cookie sessions for security reasons.

    Also cookies can be accessed via the URL parameters. So why force only one cookie
    for all sessions!
    */

    if (isset($_COOKIE[$config->get('session_name')])) {
        $session_id = $_COOKIE[$config->get('session_name')];
    } else {
        $session_id = '';
    }

    $session->start($session_id);

    setcookie($config->get('session_name'), $session->getId(), (ini_get('session.cookie_lifetime') ? (time() + ini_get('session.cookie_lifetime')) : 0), ini_get('session.cookie_path'), ini_get('session.cookie_domain'));
}

// Early full-page cache for guest visitors on the main storefront pages.
// Serves cached HTML before the startup controllers / dispatch run, cutting
// the per-request bootstrap for the most common pages. Applies only to GET
// requests from guests with an empty cart AND empty wishlist (the only
// session-dependent parts of the header). Invalidated by the content.ver /
// settings.ver stamps (bumped on content/setting saves via oc_event).
$__page_fw_key = null;
if (!is_admin() && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'GET' && $config->get('session_autostart')) {
	$__fw_route = isset($_GET['route']) ? $_GET['route'] : '';
	if ($__fw_route === '') {
		// No route param: only treat as home when the URL path is the site root.
		// (If SEO URLs are ever enabled, a non-root path is a clean URL whose
		// real route is only resolved later by startup/seo_url, so we skip it.)
		$__fw_path = isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
		if ($__fw_path === '/' || $__fw_path === '' || $__fw_path === '/index.php') {
			$__fw_route = 'common/home';
		}
	}
	$__fw_cacheable = ($__fw_route === 'common/home' || $__fw_route === 'product/category' || $__fw_route === 'product/product');
	// Dev/test bypass: ?nocache=1 skips the full-page cache so changes are seen
	// immediately without fighting APCu per-worker staleness on Docker mounts.
	// Restricted to private/local IPs (same gate as opcache-reset.php) so it can't
	// be abused to force cache misses from the outside.
	if (!empty($_GET['nocache'])) {
		$__rip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
		if ($__rip === '127.0.0.1' || $__rip === '::1' || preg_match('/^(10\.|172\.(1[6-9]|2\d|3[01])\.|192\.168\.)/', $__rip)) {
			$__fw_cacheable = false;
		}
	}
	if ($__fw_cacheable) {
		$__fw_cust = !empty($session->data['customer_id']) ? (int)$session->data['customer_id'] : 0;
		$__fw_cart = isset($session->data['cart']) ? count($session->data['cart']) : 0;
		$__fw_wish = isset($session->data['wishlist']) ? count($session->data['wishlist']) : 0;
		if ($__fw_cust === 0 && $__fw_cart === 0 && $__fw_wish === 0) {
			$__fw_params = '';
			if ($__fw_route === 'product/category' && isset($_GET['path'])) {
				$__fw_params = (string)$_GET['path'];
			} elseif ($__fw_route === 'product/product' && isset($_GET['product_id'])) {
				$__fw_params = 'p' . (int)$_GET['product_id'];
			}
			$__cver = (int)@file_get_contents(DIR_CACHE . 'content.ver') ?: 1;
			$__sver = (int)@file_get_contents(DIR_CACHE . 'settings.ver') ?: 1;
			$__page_fw_key = 'page_fw:' . $__fw_route . ':' . $__cver . ':' . $__sver . ':' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . ':' . (isset($session->data['language']) ? $session->data['language'] : '') . ':' . (isset($session->data['currency']) ? $session->data['currency'] : '') . ':' . $__fw_params;
			if (function_exists('apcu_fetch')) {
				$__fw_hit = false;
				$__fw_cached = apcu_fetch($__page_fw_key, $__fw_hit);
				if ($__fw_hit) {
					$response->setOutput($__fw_cached);
					$response->output();
					exit;
				}
			}
		}
	}
}

// Cache
$registry->set('cache', new Cache($config->get('cache_engine'), $config->get('cache_expire')));

// Url
if ($config->get('url_autostart')) {
    $registry->set('url', new Url($config->get('site_url')));
}

if (is_admin()) {
    $registry->set('front_url', new Url(HTTP_CATALOG));
}

// Language
$language = new Language($config->get('language_directory'));
$registry->set('language', $language);

// Document
$registry->set('document', new Document());

// Config Autoload
if ($config->has('config_autoload')) {
    foreach ($config->get('config_autoload') as $value) {
        $loader->config($value);
    }
}

// Language Autoload
if ($config->has('language_autoload')) {
    foreach ($config->get('language_autoload') as $value) {
        $loader->language($value);
    }
}

// Library Autoload
if ($config->has('library_autoload')) {
    foreach ($config->get('library_autoload') as $value) {
        $loader->library($value);
    }
}

// Model Autoload
if ($config->has('model_autoload')) {
    foreach ($config->get('model_autoload') as $value) {
        $loader->model($value);
    }
}

// Route
$route = new Router($registry);

// Pre Actions
if ($config->has('action_pre_action')) {
    foreach ($config->get('action_pre_action') as $value) {
        $route->addPreAction(new Action($value));
    }
}

// Dispatch
$route->dispatch(new Action($config->get('action_router')), new Action($config->get('action_error')));

// Populate the early page full-page cache from the rendered output.
if ($__page_fw_key !== null && function_exists('apcu_store')) {
	apcu_store($__page_fw_key, $response->getOutput(), 120);
}

// Output
$response->output();
