<?php
class ControllerMarketingMailSchedule extends Controller {
	public function index() {
		if (!$this->user->hasPermission('access', 'marketing/mail_schedule')) {
			$this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token']));
			return;
		}
		$this->load->language('marketing/contact');
		$this->document->setTitle('Schedule Sending');
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('marketing/mail_schedule', $data));
	}
}
