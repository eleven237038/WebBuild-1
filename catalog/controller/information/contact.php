<?php
class ControllerInformationContact extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('information/contact');

		$this->document->setTitle('Contact Us - ' . $this->config->get('config_name'));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$mail = new Mail($this->config->get('config_mail_engine'));
			$mail->parameter = $this->config->get('config_mail_parameter');
			$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
			$mail->smtp_username = $this->config->get('config_mail_smtp_username');
			$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
			$mail->smtp_port = $this->config->get('config_mail_smtp_port');
			$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

			$mail->setTo($this->config->get('config_email'));
			$mail->setFrom($this->config->get('config_email'));
			$mail->setReplyTo($this->request->post['email']);
			$mail->setSender(html_entity_decode($this->request->post['name'], ENT_QUOTES, 'UTF-8'));

			// Optional Subject field (custom, spartalabs-style). If provided, fold it
			// into the email subject + body; otherwise fall back to the native format.
			$subject_custom = isset($this->request->post['subject']) ? trim($this->request->post['subject']) : '';

			if ($subject_custom !== '') {
				$mail->setSubject(html_entity_decode('[Contact] ' . $subject_custom . ' - ' . $this->request->post['name'], ENT_QUOTES, 'UTF-8'));
				$mail->setText("Subject: " . $subject_custom . "\r\n\r\n" . $this->request->post['enquiry']);
			} else {
				$mail->setSubject(html_entity_decode(sprintf($this->language->get('email_subject'), $this->request->post['name']), ENT_QUOTES, 'UTF-8'));
				$mail->setText($this->request->post['enquiry']);
			}
			$mail->send();

			$this->response->redirect($this->url->link('information/contact/success'));
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('information/contact')
		);

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = '';
		}

		if (isset($this->error['email'])) {
			$data['error_email'] = $this->error['email'];
		} else {
			$data['error_email'] = '';
		}

		if (isset($this->error['enquiry'])) {
			$data['error_enquiry'] = $this->error['enquiry'];
		} else {
			$data['error_enquiry'] = '';
		}

		$data['button_submit'] = $this->language->get('button_submit');

		$data['action'] = $this->url->link('information/contact');
		$data['home'] = $this->url->link('common/home');

		$this->load->model('tool/image');

		$data['image'] = $this->model_tool_image->resize($this->config->get('config_image'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_location_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_location_height'), false);

		$data['store'] = $this->config->get('config_name');
		$data['store_email'] = $this->config->get('config_email');
		$data['address'] = nl2br($this->config->get('config_address'));
		$data['geocode'] = $this->config->get('config_geocode');
		$data['geocode_hl'] = $this->config->get('config_language');
		$data['telephone'] = $this->config->get('config_telephone');
		$data['fax'] = $this->config->get('config_fax');
		$data['open'] = nl2br($this->config->get('config_open'));
		$data['comment'] = $this->config->get('config_comment');

		// Social accounts (admin 联系方式目录 repeater) - decode JSON and attach
		// icon + label per platform so the template can render brand-icon links.
		$social_meta = array(
			'facebook'  => array('Facebook', 'fa-brands fa-facebook'),
			'instagram' => array('Instagram', 'fa-brands fa-instagram'),
			'whatsapp'  => array('WhatsApp', 'fa-brands fa-whatsapp'),
			'youtube'   => array('YouTube', 'fa-brands fa-youtube'),
			'tiktok'    => array('TikTok', 'fa-brands fa-tiktok'),
			'x'         => array('X', 'fa-brands fa-x-twitter'),
			'linkedin'  => array('LinkedIn', 'fa-brands fa-linkedin'),
			'pinterest' => array('Pinterest', 'fa-brands fa-pinterest'),
			'threads'   => array('Threads', 'fa-brands fa-threads'),
			'telegram'  => array('Telegram', 'fa-brands fa-telegram'),
			'snapchat'  => array('Snapchat', 'fa-brands fa-snapchat'),
			'reddit'    => array('Reddit', 'fa-brands fa-reddit'),
			'discord'   => array('Discord', 'fa-brands fa-discord'),
			'tumblr'    => array('Tumblr', 'fa-brands fa-tumblr'),
			'wechat'    => array('WeChat', 'fa-brands fa-weixin'),
			'weibo'     => array('Weibo', 'fa-brands fa-weibo'),
			'medium'    => array('Medium', 'fa-brands fa-medium'),
			'github'    => array('GitHub', 'fa-brands fa-github'),
			'quora'     => array('Quora', 'fa-brands fa-quora'),
			'vimeo'     => array('Vimeo', 'fa-brands fa-vimeo'),
			'twitch'    => array('Twitch', 'fa-brands fa-twitch'),
			'mastodon'  => array('Mastodon', 'fa-brands fa-mastodon'),
			'vk'        => array('VK', 'fa-brands fa-vk'),
			'line'      => array('Line', 'fa-brands fa-line'),
			'messenger' => array('Messenger', 'fa-brands fa-facebook-messenger'),
			'bluesky'   => array('Bluesky', 'fa-brands fa-bluesky'),
			'custom'    => array('Link', 'fa-solid fa-link'),
		);
		$data['socials'] = array();
		$_socials_raw = $this->config->get('config_social_accounts');
		$_socials = $_socials_raw ? json_decode($_socials_raw, true) : array();
		if (is_array($_socials)) {
			foreach ($_socials as $_s) {
				$_p = isset($_s['platform']) ? $_s['platform'] : 'custom';
				$_url = isset($_s['url']) ? trim($_s['url']) : '';
				if ($_url === '') {
					continue;
				}
				// Ensure clickable (prepend https:// if no scheme).
				if (!preg_match('#^https?://#i', $_url)) {
					$_url = 'https://' . ltrim($_url, '/');
				}
				$_meta = isset($social_meta[$_p]) ? $social_meta[$_p] : $social_meta['custom'];
				$data['socials'][] = array(
					'platform' => $_p,
					'url'       => $_url,
					'label'     => $_meta[0],
					'icon'      => $_meta[1],
				);
			}
		}

		$data['locations'] = array();

		$this->load->model('localisation/location');

		foreach((array)$this->config->get('config_location') as $location_id) {
			$location_info = $this->model_localisation_location->getLocation($location_id);

			if ($location_info) {
				$image = $this->model_tool_image->resize($location_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_location_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_location_height'), false);

				$data['locations'][] = array(
					'location_id' => $location_info['location_id'],
					'name'        => $location_info['name'],
					'address'     => nl2br($location_info['address']),
					'geocode'     => $location_info['geocode'],
					'telephone'   => $location_info['telephone'],
					'fax'         => $location_info['fax'],
					'image'       => $image,
					'open'        => nl2br($location_info['open']),
					'comment'     => $location_info['comment']
				);
			}
		}

		if (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} else {
			$data['name'] = get_name($this->customer->getFirstName(), $this->customer->getLastName());
		}

		if (isset($this->request->post['email'])) {
			$data['email'] = $this->request->post['email'];
		} else {
			$data['email'] = $this->customer->getEmail();
		}

		if (isset($this->request->post['enquiry'])) {
			$data['enquiry'] = $this->request->post['enquiry'];
		} else {
			$data['enquiry'] = '';
		}

		if (isset($this->request->post['subject'])) {
			$data['subject'] = $this->request->post['subject'];
		} else {
			$data['subject'] = '';
		}

		// Captcha
		if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('contact', (array)$this->config->get('config_captcha_page'))) {
			$data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'), $this->error);
		} else {
			$data['captcha'] = '';
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('information/contact', $data));
	}

	protected function validate() {
		// English overrides - the redesigned contact page is English-facing, so the
		// validation messages are localised here rather than pulled from the (zh-cn)
		// language file to stay consistent with the page copy.
		if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 32)) {
			$this->error['name'] = 'Name must be between 3 and 32 characters!';
		}

		if (!filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL)) {
			$this->error['email'] = 'E-Mail Address does not appear to be valid!';
		}

		if ((utf8_strlen($this->request->post['enquiry']) < 10) || (utf8_strlen($this->request->post['enquiry']) > 3000)) {
			$this->error['enquiry'] = 'Enquiry must be between 10 and 3000 characters!';
		}

		// Captcha
		if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('contact', (array)$this->config->get('config_captcha_page'))) {
			$captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

			if ($captcha) {
				$this->error['captcha'] = $captcha;
			}
		}

		return !$this->error;
	}

	public function success() {
		$this->load->language('information/contact');

		$this->document->setTitle('Contact Us - ' . $this->config->get('config_name'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('information/contact')
		);

		$data['continue'] = $this->url->link('common/home');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('common/success', $data));
	}
}
