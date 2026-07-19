<?php
class ControllerMarketingMailTrigger extends Controller {

	public function index() {
		if (!$this->user->hasPermission('access', 'marketing/mail_trigger')) {
			$this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token']));
			return;
		}
		$this->load->language('marketing/mail_trigger');
		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
		$data['breadcrumbs'][] = array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('marketing/mail_trigger', 'user_token=' . $this->session->data['user_token'], true));

		$data['user_token'] = $this->session->data['user_token'];

		$data['save']   = $this->url->link('marketing/mail_trigger/save', 'user_token=' . $this->session->data['user_token'], true);
		$data['delete'] = $this->url->link('marketing/mail_trigger/delete', 'user_token=' . $this->session->data['user_token'], true);
		$data['enable'] = $this->url->link('marketing/mail_trigger/enable', 'user_token=' . $this->session->data['user_token'], true);
		$data['disable']= $this->url->link('marketing/mail_trigger/disable', 'user_token=' . $this->session->data['user_token'], true);
		$data['get']    = $this->url->link('marketing/mail_trigger/getTrigger', 'user_token=' . $this->session->data['user_token'], true);

		// 可用事件清单 (事件 => 显示名)
		$data['events'] = array(
			'customer_register'      => $this->language->get('event_customer_register'),
		);

		// 列表数据
		$this->load->model('marketing/mail_trigger');
		$triggers = $this->model_marketing_mail_trigger->getTriggers();
		$data['triggers'] = array();
		foreach ($triggers as $t) {
			$data['triggers'][] = array(
				'trigger_id' => $t['trigger_id'],
				'code'       => $t['code'],
				'event'      => isset($data['events'][$t['event']]) ? $data['events'][$t['event']] : $t['event'],
				'subject'    => $t['subject'],
				'status'     => $t['status'],
				'date_added' => $t['date_added'],
			);
		}

		// 文本
		$data['heading_title']    = $this->language->get('heading_title');
		$data['text_list']        = $this->language->get('text_list');
		$data['text_no_results']  = $this->language->get('text_no_results');
		$data['text_confirm']     = $this->language->get('text_confirm');
		$data['text_enabled']     = $this->language->get('text_enabled');
		$data['text_disabled']    = $this->language->get('text_disabled');
		$data['column_code']      = $this->language->get('column_code');
		$data['column_event']     = $this->language->get('column_event');
		$data['column_subject']   = $this->language->get('column_subject');
		$data['column_status']    = $this->language->get('column_status');
		$data['column_date_added']= $this->language->get('column_date_added');
		$data['column_action']    = $this->language->get('column_action');
		$data['button_add']       = $this->language->get('button_add');
		$data['button_edit']      = $this->language->get('button_edit');
		$data['button_delete']    = $this->language->get('button_delete');
		$data['button_save']      = $this->language->get('button_save');
		$data['button_cancel']    = $this->language->get('button_cancel');
		$data['button_enable']    = $this->language->get('button_enable');
		$data['button_disable']   = $this->language->get('button_disable');

		$data['entry_code']       = $this->language->get('entry_code');
		$data['entry_event']      = $this->language->get('entry_event');
		$data['entry_subject']    = $this->language->get('entry_subject');
		$data['entry_message']    = $this->language->get('entry_message');
		$data['entry_status']     = $this->language->get('entry_status');
		$data['help_code']        = $this->language->get('help_code');
		$data['help_message']     = $this->language->get('help_message');

		$data['error_permission'] = !$this->user->hasPermission('modify', 'marketing/mail_trigger');
		$data['text_loading']     = $this->language->get('text_loading');
		$data['summernote']       = $this->config->get('config_admin_language') == 'en-gb' ? 'en-US' : 'zh-CN';

		$this->document->addStyle('view/javascript/summernote/summernote.css');
		$this->document->addScript('view/javascript/summernote/summernote.js');
		$this->document->addScript('view/javascript/summernote/opencart.js');

		$data['header']      = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']      = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('marketing/mail_trigger', $data));
	}

	// AJAX 取单条
	public function getTrigger() {
		$json = array();
		if ($this->user->hasPermission('access', 'marketing/mail_trigger')) {
			$this->load->model('marketing/mail_trigger');
			$trigger_id = (int)($this->request->get['trigger_id'] ?? 0);
			$json = $this->model_marketing_mail_trigger->getTrigger($trigger_id);
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	// AJAX 保存
	public function save() {
		$this->load->language('marketing/mail_trigger');
		$json = array();

		if (!$this->user->hasPermission('modify', 'marketing/mail_trigger')) {
			$json['error'] = $this->language->get('error_permission');
		}
		if (empty($this->request->post['subject'])) {
			$json['error'] = $this->language->get('error_subject');
		}
		if (empty($this->request->post['event'])) {
			$json['error'] = $this->language->get('error_event');
		}

		if (!$json) {
			$this->load->model('marketing/mail_trigger');
			$trigger_id = (int)($this->request->post['trigger_id'] ?? 0);

			$data = array(
				'code'    => $this->request->post['code'] ?? '',
				'event'   => $this->request->post['event'] ?? '',
				'subject' => $this->request->post['subject'] ?? '',
				'message' => $this->request->post['message'] ?? '',
				'status'  => isset($this->request->post['status']) ? 1 : 0,
			);

			if ($trigger_id) {
				$this->model_marketing_mail_trigger->editTrigger($trigger_id, $data);
			} else {
				$trigger_id = $this->model_marketing_mail_trigger->addTrigger($data);
			}
			$json['success'] = $this->language->get('text_success');
			$json['trigger_id'] = $trigger_id;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	// AJAX 删除
	public function delete() {
		$this->load->language('marketing/mail_trigger');
		$json = array();
		if (!$this->user->hasPermission('modify', 'marketing/mail_trigger')) {
			$json['error'] = $this->language->get('error_permission');
		}
		if (!$json) {
			$this->load->model('marketing/mail_trigger');
			$trigger_id = (int)($this->request->get['trigger_id'] ?? $this->request->post['trigger_id'] ?? 0);
			$this->model_marketing_mail_trigger->deleteTrigger($trigger_id);
			$json['success'] = $this->language->get('text_deleted');
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function enable() {
		$this->load->language('marketing/mail_trigger');
		$json = array();
		if (!$this->user->hasPermission('modify', 'marketing/mail_trigger')) {
			$json['error'] = $this->language->get('error_permission');
		}
		if (!$json) {
			$this->load->model('marketing/mail_trigger');
			$trigger_id = (int)($this->request->get['trigger_id'] ?? 0);
			$this->model_marketing_mail_trigger->setStatus($trigger_id, 1);
			$json['success'] = $this->language->get('text_success');
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function disable() {
		$this->load->language('marketing/mail_trigger');
		$json = array();
		if (!$this->user->hasPermission('modify', 'marketing/mail_trigger')) {
			$json['error'] = $this->language->get('error_permission');
		}
		if (!$json) {
			$this->load->model('marketing/mail_trigger');
			$trigger_id = (int)($this->request->get['trigger_id'] ?? 0);
			$this->model_marketing_mail_trigger->setStatus($trigger_id, 0);
			$json['success'] = $this->language->get('text_success');
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
