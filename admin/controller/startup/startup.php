<?php
class ControllerStartupStartup extends Controller {
	public function index() {
		// Settings (APCu-cached; invalidated by a file-version stamp bumped on
		// editSetting - see admin/model/setting/setting.php). Same approach as the
		// catalog startup; ~80ms query avoided per request.
		$ver_file = DIR_CACHE . 'settings.ver';
		$ver = @filemtime($ver_file);
		if (!$ver) { $ver = 1; @touch($ver_file); }
		$apcu_key = 'oc_settings:' . $ver . ':0';
		$config_data = null;
		$hit = false;
		if (function_exists('apcu_fetch')) {
			$config_data = apcu_fetch($apcu_key, $hit);
		}
		if (!$hit) {
			$config_data = array();
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '0'");

			foreach ($query->rows as $setting) {
				if (!$setting['serialized']) {
					$config_data[$setting['key']] = $setting['value'];
				} else {
					$config_data[$setting['key']] = json_decode($setting['value'], true);
				}
			}
			if (function_exists('apcu_store')) {
				apcu_store($apcu_key, $config_data, 3600);
			}
		}

		foreach ($config_data as $__k => $__v) {
			$this->config->set($__k, $__v);
		}

		// Theme
		$this->config->set('template_cache', $this->config->get('developer_theme'));
				
		// Language
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "language` WHERE code = '" . $this->db->escape($this->config->get('config_admin_language')) . "'");
		
		if ($query->num_rows) {
			$this->config->set('config_language_id', $query->row['language_id']);
		}
		
		// Language
		$language = new Language($this->config->get('config_admin_language'));
		$language->load($this->config->get('config_admin_language'));
		$this->registry->set('language', $language);
		
		// Customer
		$this->registry->set('customer', new Cart\Customer($this->registry));

		// Currency
		$this->registry->set('currency', new Cart\Currency($this->registry));
	
		// Tax
		$this->registry->set('tax', new Cart\Tax($this->registry));
		
		if ($this->config->get('config_tax_default') == 'shipping') {
			$this->tax->setShippingAddress($this->config->get('config_country_id'), $this->config->get('config_zone_id'));
		}

		if ($this->config->get('config_tax_default') == 'payment') {
			$this->tax->setPaymentAddress($this->config->get('config_country_id'), $this->config->get('config_zone_id'));
		}

		$this->tax->setStoreAddress($this->config->get('config_country_id'), $this->config->get('config_zone_id'));

		// Weight
		$this->registry->set('weight', new Cart\Weight($this->registry));
		
		// Length
		$this->registry->set('length', new Cart\Length($this->registry));
		
		// Cart
		$this->registry->set('cart', new Cart\Cart($this->registry));
		
		// Encryption
		$this->registry->set('encryption', new Encryption($this->config->get('config_encryption')));
	}
}