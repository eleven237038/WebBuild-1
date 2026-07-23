<?php
class ControllerCatalogContact extends Controller {
	private $error = array();

	// Contact info is stored in oc_setting under the 'config' code using these keys.
	// We use editSettingValue (surgical UPDATE) so we never clobber other config rows.
	//
	// Per the 联系方式目录 reconstruct: keep telephone + email, DROP fax/address/
	// geocode/open/comment, and ADD social accounts (all major global platforms) as
	// a JSON-encoded repeater in config_social_accounts.
	private $keys = array(
		'config_telephone',
		'config_email',
	);

	// Comprehensive list of major global social platforms (2026). Drives the
	// platform <select> in the repeater. 'custom' lets the admin paste any URL.
	private $social_platforms = array(
		'facebook'  => 'Facebook',
		'instagram' => 'Instagram',
		'whatsapp'  => 'WhatsApp',
		'youtube'   => 'YouTube',
		'tiktok'    => 'TikTok',
		'x'         => 'X (Twitter)',
		'linkedin'  => 'LinkedIn',
		'pinterest' => 'Pinterest',
		'threads'   => 'Threads',
		'telegram'  => 'Telegram',
		'snapchat'  => 'Snapchat',
		'reddit'    => 'Reddit',
		'discord'   => 'Discord',
		'tumblr'    => 'Tumblr',
		'wechat'    => 'WeChat',
		'weibo'     => 'Weibo',
		'medium'    => 'Medium',
		'github'    => 'GitHub',
		'quora'     => 'Quora',
		'vimeo'     => 'Vimeo',
		'twitch'    => 'Twitch',
		'mastodon'  => 'Mastodon',
		'vk'        => 'VK',
		'line'      => 'Line',
		'messenger' => 'Messenger',
		'bluesky'   => 'Bluesky',
		'custom'    => 'Custom link',
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

			// Social accounts repeater: POST['socials'] is an array of
			// {platform, url} rows. Sanitize, drop empties, store as JSON.
			$socials = $this->collectSocials();
			$this->model_setting_setting->editSettingValue('config', 'config_social_accounts', json_encode($socials), 0);

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

		// Social accounts: prefer POST (re-display on error), else decode DB JSON.
		if (isset($this->request->post['socials']) && is_array($this->request->post['socials'])) {
			$data['socials'] = $this->collectSocials();
		} else {
			$raw = $this->model_setting_setting->getSettingValue('config_social_accounts', 0);
			$decoded = $raw ? json_decode($raw, true) : array();
			$data['socials'] = is_array($decoded) ? $decoded : array();
		}
		// Always show at least one empty row so the admin can start adding.
		if (!is_array($data['socials']) || count($data['socials']) === 0) {
			$data['socials'] = array(array('platform' => 'facebook', 'url' => ''));
		}

		$data['social_platforms'] = $this->social_platforms;

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_form']     = $this->language->get('text_form');
		$data['button_save']   = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['entry_telephone'] = $this->language->get('entry_telephone');
		$data['entry_email']     = $this->language->get('entry_email');
		$data['entry_socials']   = $this->language->get('entry_socials');
		$data['help_socials']    = $this->language->get('help_socials');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/contact_form', $data));
	}

	/**
	 * Pull social rows from POST, sanitize, and drop rows with no URL.
	 * Returns a flat array of {platform, url} assoc arrays.
	 */
	private function collectSocials() {
		$out = array();
		$rows = isset($this->request->post['socials']) && is_array($this->request->post['socials'])
			? $this->request->post['socials'] : array();
		foreach ($rows as $row) {
			$platform = isset($row['platform']) ? (string)$row['platform'] : 'custom';
			if (!isset($this->social_platforms[$platform])) {
				$platform = 'custom';
			}
			$url = isset($row['url']) ? trim((string)$row['url']) : '';
			if ($url === '') {
				continue;  // drop empty rows on save
			}
			$out[] = array('platform' => $platform, 'url' => $url);
		}
		return $out;
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
