<?php
class ControllerCatalogActivity extends Controller {
	private $error = array();

	// Activity/Promo block for the homepage. Stored under the 'config' code
	// (store 0) so the frontend reads it via $this->config->get('activity_*')
	// from the startup-loaded config cache — zero extra queries on the homepage.
	// editSettingValue = surgical UPDATE, never clobbers other config rows.
	private $keys = array(
		'activity_enabled',
		'activity_tag',
		'activity_title',
		'activity_subtitle',
		'activity_badge',
		'activity_cta_label',
		'activity_cta_url',
		'activity_bg_color',
		'activity_text_color',
		'activity_accent_color',
	);

	// Sensible defaults seeded on first load (green-on-navy, matches the brand).
	private $defaults = array(
		'activity_enabled'     => '0',
		'activity_tag'         => '// LIMITED OFFER',
		'activity_title'       => 'Summer Research Sale',
		'activity_subtitle'    => 'Save on selected reagents and kits. Stock is limited — once it is gone, it is gone.',
		'activity_badge'       => '-20%',
		'activity_cta_label'   => 'Shop the sale',
		'activity_cta_url'     => '',
		'activity_bg_color'    => '#0F172A',
		'activity_text_color'  => '#F8FAFC',
		'activity_accent_color'=> '#10B981',
	);

	public function index() {
		if (!$this->user->hasPermission('access', 'catalog/activity')) {
			$this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}
		$this->load->language('catalog/activity');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			foreach ($this->keys as $key) {
				$value = isset($this->request->post[$key]) ? $this->request->post[$key] : '';
				// Checkboxes are only POSTed when ticked; coerce to '1'/'0'.
				if ($key == 'activity_enabled') {
					$value = $value ? '1' : '0';
				}
				$this->upsertActivity($key, $value);
			}

			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('catalog/activity', 'user_token=' . $this->session->data['user_token'], true));
		}

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->error['warning'])) { $data['error_warning'] = $this->error['warning']; } else { $data['error_warning'] = ''; }
		if (isset($this->session->data['success'])) { $data['success'] = $this->session->data['success']; unset($this->session->data['success']); } else { $data['success'] = ''; }

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('catalog/activity', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('catalog/activity', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);

		// Load current values: prefer POST (re-display on validation error), else DB
		// (falling back to defaults so first visit shows sensible sample copy).
		foreach ($this->keys as $key) {
			if (isset($this->request->post[$key])) {
				$data[$key] = $this->request->post[$key];
			} else {
				$db_val = $this->model_setting_setting->getSettingValue($key, 0);
				$data[$key] = ($db_val !== null && $db_val !== '') ? $db_val : $this->defaults[$key];
			}
		}
		// Checkbox re-display: '1' if ticked in POST, else the stored value.
		if (isset($this->request->post['activity_enabled'])) {
			$data['activity_enabled'] = '1';
		}

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_form']     = $this->language->get('text_form');
		$data['text_enabled']  = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['button_save']   = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['entry_status']     = $this->language->get('entry_status');
		$data['entry_tag']        = $this->language->get('entry_tag');
		$data['entry_title']      = $this->language->get('entry_title');
		$data['entry_subtitle']   = $this->language->get('entry_subtitle');
		$data['entry_badge']      = $this->language->get('entry_badge');
		$data['entry_cta_label']  = $this->language->get('entry_cta_label');
		$data['entry_cta_url']    = $this->language->get('entry_cta_url');
		$data['entry_bg_color']   = $this->language->get('entry_bg_color');
		$data['entry_text_color'] = $this->language->get('entry_text_color');
		$data['entry_accent_color'] = $this->language->get('entry_accent_color');
		$data['help_tag']         = $this->language->get('help_tag');
		$data['help_badge']       = $this->language->get('help_badge');
		$data['help_cta_url']     = $this->language->get('help_cta_url');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/activity_form', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'catalog/activity')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		return !$this->error;
	}

	/**
	 * Upsert a config setting under code='config' store 0. editSettingValue only
	 * UPDATEs (it won't INSERT new keys), so for the first save of activity_* we
	 * INSERT a placeholder row first, then editSettingValue updates it AND bumps
	 * settings.ver - which invalidates the catalog startup's APCu config cache
	 * (keyed oc_settings:<ver>:<store>), so the homepage sees the new value
	 * immediately instead of up to 3600s later.
	 */
	private function upsertActivity($key, $value) {
		$exists = $this->db->query("SELECT setting_id FROM " . DB_PREFIX . "setting WHERE `code` = 'config' AND `key` = '" . $this->db->escape($key) . "' AND store_id = '0'");
		if (!$exists->num_rows) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '0', `code` = 'config', `key` = '" . $this->db->escape($key) . "', `value` = '', serialized = '0'");
		}
		$this->model_setting_setting->editSettingValue('config', $key, $value, 0);
	}
}
