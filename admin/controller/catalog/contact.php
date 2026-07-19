<?php
class ControllerCatalogContact extends Controller {
	private $error = array();

	// Contact info is stored in oc_setting under the 'config' code using these keys.
	// We use editSettingValue (surgical UPDATE) so we never clobber other config rows.
	private $keys = array(
		'config_telephone',
		'config_fax',
		'config_email',
		'config_address',
		'config_geocode',
		'config_open',
		'config_comment'
	);

	public function index() {
		if (!$this->user->hasPermission('access', 'catalog/contact')) {
			$this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}
		$this->load->language('catalog/contact');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			foreach ($this->keys as $key) {
				$value = isset($this->request->post[$key]) ? $this->request->post[$key] : '';
				$this->model_setting_setting->editSettingValue('config', $key, $value, 0);
			}
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('catalog/contact', 'user_token=' . $this->session->data['user_token'], true));
		}

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->error['warning'])) { $data['error_warning'] = $this->error['warning']; } else { $data['error_warning'] = ''; }
		if (isset($this->error['email'])) { $data['error_email'] = $this->error['email']; } else { $data['error_email'] = ''; }
		if (isset($this->session->data['success'])) { $data['success'] = $this->session->data['success']; unset($this->session->data['success']); } else { $data['success'] = ''; }

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('catalog/contact', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('catalog/contact', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);

		// Load current values: prefer POST (on validation error re-display), else DB
		foreach ($this->keys as $key) {
			if (isset($this->request->post[$key])) {
				$data[$key] = $this->request->post[$key];
			} else {
				$data[$key] = $this->model_setting_setting->getSettingValue($key, 0);
			}
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/contact_form', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'catalog/contact')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (isset($this->request->post['config_email']) && $this->request->post['config_email'] != '' && !filter_var($this->request->post['config_email'], FILTER_VALIDATE_EMAIL)) {
			$this->error['email'] = $this->language->get('error_email');
		}

		return !$this->error;
	}
}
