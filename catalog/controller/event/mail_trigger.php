<?php
class ControllerEventMailTrigger extends Controller {

	// model/account/customer/addCustomer/after
	// $output = 新建的 customer_id ; $args[0] = 提交的客户数据
	public function addCustomer(&$route, &$args, &$output) {
		if (!$output) { return; }

		$this->load->model('marketing/mail_trigger');
		$triggers = $this->model_marketing_mail_trigger->getTriggersByEvent('customer_register');
		if (!$triggers) { return; }

		$customer_id = (int)$output;

		// 客户资料 (catalog model),fallback 到 $args[0]
		$this->load->model('account/customer');
		$customer_info = $this->model_account_customer->getCustomer($customer_id);
		if (!$customer_info) {
			$customer_info = array(
				'firstname' => $args[0]['firstname'] ?? '',
				'lastname'  => $args[0]['lastname'] ?? '',
				'email'     => $args[0]['email'] ?? '',
			);
		}

		$store_name  = $this->config->get('config_name');
		$store_email = $this->config->get('config_email');

		$placeholders = array(
			'{firstname}'  => $customer_info['firstname'],
			'{lastname}'   => $customer_info['lastname'],
			'{email}'      => $customer_info['email'],
			'{store_name}' => $store_name,
		);

		foreach ($triggers as $trigger) {
			$subject = strtr($trigger['subject'], $placeholders);
			$message = strtr($trigger['message'], $placeholders);

			$html = '<html dir="ltr" lang="en"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><title>' . htmlspecialchars($subject) . '</title></head><body>' . html_entity_decode($message, ENT_QUOTES, 'UTF-8') . '</body></html>';

			$mail = new Mail($this->config->get('config_mail_engine'));
			$mail->parameter = $this->config->get('config_mail_parameter');
			$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
			$mail->smtp_username = $this->config->get('config_mail_smtp_username');
			$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
			$mail->smtp_port = $this->config->get('config_mail_smtp_port');
			$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
			$mail->setTo($customer_info['email']);
			$mail->setFrom($store_email);
			$mail->setSender(html_entity_decode($store_name, ENT_QUOTES, 'UTF-8'));
			$mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
			$mail->setHtml($html);
			$mail->send();
		}
	}
}
