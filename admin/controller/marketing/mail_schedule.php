<?php
class ControllerMarketingMailSchedule extends Controller {

	public function index() {
		if (!$this->user->hasPermission('access', 'marketing/mail_schedule')) {
			$this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token']));
			return;
		}
		$this->load->language('marketing/mail_schedule');
		$this->document->setTitle($this->language->get('heading_title'));

		// 面包屑
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
		$data['breadcrumbs'][] = array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('marketing/mail_schedule', 'user_token=' . $this->session->data['user_token'], true));

		$data['user_token'] = $this->session->data['user_token'];

		$data['add']   = $this->url->link('marketing/mail_schedule/add', 'user_token=' . $this->session->data['user_token'], true);
		$data['save']  = $this->url->link('marketing/mail_schedule/save', 'user_token=' . $this->session->data['user_token'], true);
		$data['delete'] = $this->url->link('marketing/mail_schedule/delete', 'user_token=' . $this->session->data['user_token'], true);
		$data['send']  = $this->url->link('marketing/mail_schedule/send', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketing/mail_schedule', 'user_token=' . $this->session->data['user_token'], true);

		// 商店与客户组下拉数据
		$this->load->model('setting/store');
		$data['stores'] = array(0 => $this->language->get('text_default'));
		foreach ($this->model_setting_store->getStores() as $s) {
			$data['stores'][$s['store_id']] = $s['name'];
		}

		$this->load->model('customer/customer_group');
		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		// 收件人类型
		$data['recipient_types'] = array(
			'newsletter'     => $this->language->get('text_recipient_newsletter'),
			'customer_all'   => $this->language->get('text_recipient_all'),
			'customer_group' => $this->language->get('text_recipient_group'),
			'emails'         => $this->language->get('text_recipient_emails'),
		);

		// 列表数据
		$this->load->model('marketing/mail_schedule');
		$filter = array(
			'sort'  => isset($this->request->get['sort']) ? $this->request->get['sort'] : 'send_at',
			'order' => isset($this->request->get['order']) ? $this->request->get['order'] : 'DESC',
			'start' => 0,
			'limit' => 1000,
		);
		$campaigns = $this->model_marketing_mail_schedule->getCampaigns($filter);
		$data['campaigns'] = array();
		foreach ($campaigns as $c) {
			$data['campaigns'][] = array(
				'schedule_id'    => $c['schedule_id'],
				'subject'        => $c['subject'],
				'recipient_type' => isset($data['recipient_types'][$c['recipient_type']]) ? $data['recipient_types'][$c['recipient_type']] : $c['recipient_type'],
				'send_at'        => $c['send_at'],
				'status'         => $c['status'],
				'status_text'    => $this->language->get('text_status_' . $c['status']) ? $this->language->get('text_status_' . $c['status']) : $c['status'],
				'sent_count'     => $c['sent_count'],
				'total_count'    => $c['total_count'],
				'edit'           => $this->url->link('marketing/mail_schedule/edit', 'user_token=' . $this->session->data['user_token'] . '&schedule_id=' . $c['schedule_id'], true),
				'delete'         => $this->url->link('marketing/mail_schedule/delete', 'user_token=' . $this->session->data['user_token'] . '&schedule_id=' . $c['schedule_id'], true),
				'send'           => $this->url->link('marketing/mail_schedule/send', 'user_token=' . $this->session->data['user_token'] . '&schedule_id=' . $c['schedule_id'], true),
			);
		}

		// 文本变量
		$data['heading_title']    = $this->language->get('heading_title');
		$data['text_list']        = $this->language->get('text_list');
		$data['text_no_results']  = $this->language->get('text_no_results');
		$data['text_confirm']     = $this->language->get('text_confirm');
		$data['text_send_now']    = $this->language->get('text_send_now');
		$data['column_subject']   = $this->language->get('column_subject');
		$data['column_recipient'] = $this->language->get('column_recipient');
		$data['column_send_at']   = $this->language->get('column_send_at');
		$data['column_status']    = $this->language->get('column_status');
		$data['column_progress']  = $this->language->get('column_progress');
		$data['column_action']    = $this->language->get('column_action');
		$data['button_add']       = $this->language->get('button_add');
		$data['button_edit']      = $this->language->get('button_edit');
		$data['button_delete']    = $this->language->get('button_delete');
		$data['button_send']      = $this->language->get('button_send');
		$data['button_save']      = $this->language->get('button_save');
		$data['button_cancel']    = $this->language->get('button_cancel');

		// 表单字段标签
		$data['entry_store']          = $this->language->get('entry_store');
		$data['entry_recipient']      = $this->language->get('entry_recipient');
		$data['entry_customer_group'] = $this->language->get('entry_customer_group');
		$data['entry_emails']         = $this->language->get('entry_emails');
		$data['entry_subject']        = $this->language->get('entry_subject');
		$data['entry_message']        = $this->language->get('entry_message');
		$data['entry_send_at']        = $this->language->get('entry_send_at');

		$data['error_permission'] = !$this->user->hasPermission('modify', 'marketing/mail_schedule');

		$data['text_loading'] = $this->language->get('text_loading');
		$data['summernote']   = $this->config->get('config_admin_language') == 'en-gb' ? 'en-US' : 'zh-CN';

		// summernote / 日期选择器脚本
		$this->document->addStyle('view/javascript/summernote/summernote.css');
		$this->document->addScript('view/javascript/summernote/summernote.js');
		$this->document->addScript('view/javascript/summernote/opencart.js');
		$this->document->addStyle('view/javascript/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css');
		$this->document->addScript('view/javascript/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js');
		$this->document->addScript('view/javascript/jquery/datetimepicker/moment.min.js');

		$data['header']      = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']      = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('marketing/mail_schedule', $data));
	}

	public function getCampaign() {
		$json = array();
		if ($this->user->hasPermission('access', 'marketing/mail_schedule')) {
			$this->load->model('marketing/mail_schedule');
			$schedule_id = (int)($this->request->get['schedule_id'] ?? 0);
			$json = $this->model_marketing_mail_schedule->getCampaign($schedule_id);
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	// AJAX 保存 (新增/编辑)
	public function save() {
		$this->load->language('marketing/mail_schedule');
		$json = array();

		if (!$this->user->hasPermission('modify', 'marketing/mail_schedule')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (empty($this->request->post['subject'])) {
			$json['error'] = $this->language->get('error_subject');
		}
		if (empty($this->request->post['send_at'])) {
			$json['error'] = $this->language->get('error_send_at');
		}
		if (($this->request->post['recipient_type'] ?? '') == 'emails' && empty($this->request->post['recipient_data'])) {
			$json['error'] = $this->language->get('error_emails');
		}

		if (!$json) {
			$this->load->model('marketing/mail_schedule');
			$schedule_id = (int)($this->request->post['schedule_id'] ?? 0);

			$data = array(
				'store_id'       => (int)($this->request->post['store_id'] ?? 0),
				'recipient_type' => $this->request->post['recipient_type'] ?? 'newsletter',
				'recipient_data' => $this->request->post['recipient_data'] ?? '',
				'subject'        => $this->request->post['subject'] ?? '',
				'message'        => $this->request->post['message'] ?? '',
				'send_at'        => $this->request->post['send_at'] ?? date('Y-m-d H:i:s'),
			);

			if ($schedule_id) {
				$this->model_marketing_mail_schedule->editCampaign($schedule_id, $data);
			} else {
				$schedule_id = $this->model_marketing_mail_schedule->addCampaign($data);
			}
			$json['success'] = $this->language->get('text_success');
			$json['schedule_id'] = $schedule_id;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	// AJAX 删除
	public function delete() {
		$this->load->language('marketing/mail_schedule');
		$json = array();
		if (!$this->user->hasPermission('modify', 'marketing/mail_schedule')) {
			$json['error'] = $this->language->get('error_permission');
		}
		if (!$json) {
			$this->load->model('marketing/mail_schedule');
			$schedule_id = (int)($this->request->get['schedule_id'] ?? $this->request->post['schedule_id'] ?? 0);
			$this->model_marketing_mail_schedule->deleteCampaign($schedule_id);
			$json['success'] = $this->language->get('text_deleted');
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	// 立即发送 (AJAX)
	public function send() {
		$this->load->language('marketing/mail_schedule');
		$json = array();
		if (!$this->user->hasPermission('modify', 'marketing/mail_schedule')) {
			$json['error'] = $this->language->get('error_permission');
		}
		if (!$json) {
			$this->load->model('marketing/mail_schedule');
			$schedule_id = (int)($this->request->get['schedule_id'] ?? $this->request->post['schedule_id'] ?? 0);
			@set_time_limit(0);
			$result = $this->model_marketing_mail_schedule->dispatch($schedule_id);
			if (isset($result['error'])) {
				$json['error'] = $result['error'];
			} else {
				$json['success'] = sprintf($this->language->get('text_sent'), $result['sent'] ?? 0, $result['total'] ?? 0);
			}
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
