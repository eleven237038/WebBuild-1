<?php
class ControllerMarketingMailSchedule extends Controller {
	public function index() {
		$this->load->language('marketing/contact');
		$this->document->setTitle('Schedule Sending');
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('marketing/mail_schedule', $data));
	}
}
