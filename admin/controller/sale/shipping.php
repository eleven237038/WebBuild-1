<?php
class ControllerSaleShipping extends Controller {
	private $error = array();

	// Code namespace owned entirely by this page. editSetting() clobbers it
	// (safe here: every key below is prefixed with 'shipping_').
	private $code = 'shipping';

	// Boolean toggles (stored as 1/0).
	private $checkboxes = array(
		'shipping_enabled',
	);

	// All persisted keys. Defaults live in shippingDefaults() below.
	private $keys = array(
		// toggle
		'shipping_enabled',
		// fees
		'shipping_flat_rate',
		'shipping_per_item',
		'shipping_free_threshold',
		// text
		'shipping_delivery_text',
		'shipping_free_hint',
	);

	public function index() {
		if (!$this->user->hasPermission('access', 'sale/shipping')) {
			$this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}
		$this->load->language('sale/shipping');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');

		$defaults = $this->shippingDefaults();

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$save = array();
			foreach ($this->keys as $key) {
				if (in_array($key, $this->checkboxes, true)) {
					$save[$key] = isset($this->request->post[$key]) ? 1 : 0;
				} else {
					$save[$key] = isset($this->request->post[$key]) ? $this->request->post[$key] : '';
				}
			}
			$this->model_setting_setting->editSetting($this->code, $save);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('sale/shipping', 'user_token=' . $this->session->data['user_token'], true));
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
			'href' => $this->url->link('sale/shipping', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('sale/shipping', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);

		// Load current values: prefer POST (re-display on error), else DB, else default.
		$saved = $this->model_setting_setting->getSetting($this->code, 0);
		foreach ($this->keys as $key) {
			if (isset($this->request->post[$key])) {
				$data[$key] = $this->request->post[$key];
			} elseif (array_key_exists($key, $saved)) {
				$data[$key] = $saved[$key];
			} else {
				$data[$key] = isset($defaults[$key]) ? $defaults[$key] : '';
			}
		}

		$data['heading_title']        = $this->language->get('heading_title');
		$data['text_form']            = $this->language->get('text_form');
		$data['button_save']          = $this->language->get('button_save');
		$data['button_cancel']        = $this->language->get('button_cancel');
		$data['text_section_toggle']  = $this->language->get('text_section_toggle');
		$data['text_section_fee']     = $this->language->get('text_section_fee');
		$data['text_section_text']    = $this->language->get('text_section_text');

		foreach ($this->keys as $key) {
			$data['entry_' . $key] = $this->language->get('entry_' . $key);
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('sale/shipping_form', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'sale/shipping')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	/**
	 * Initial defaults for the 运费管理 page. Single source of truth for the
	 * form's first-render values; the frontend cart/checkout can read these same
	 * settings (config->get('shipping_*')) once shipping calc is wired in.
	 */
	private function shippingDefaults() {
		return array(
			'shipping_enabled'        => 1,
			'shipping_flat_rate'      => '15.00',
			'shipping_per_item'       => '5.00',
			'shipping_free_threshold' => '200.00',
			'shipping_delivery_text'  => '预计 3-5 个工作日送达',
			'shipping_free_hint'      => '满 ¥200 免运费',
		);
	}
}
