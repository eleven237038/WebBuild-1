<?php
class ModelMarketingMailSchedule extends Model {

	public function addCampaign($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "mail_schedule SET
			store_id = '" . (int)($data['store_id'] ?? 0) . "',
			recipient_type = '" . $this->db->escape((string)($data['recipient_type'] ?? 'newsletter')) . "',
			recipient_data = '" . $this->db->escape((string)($data['recipient_data'] ?? '')) . "',
			subject = '" . $this->db->escape((string)($data['subject'] ?? '')) . "',
			message = '" . $this->db->escape((string)($data['message'] ?? '')) . "',
			send_at = '" . $this->db->escape((string)($data['send_at'] ?? date('Y-m-d H:i:s'))) . "',
			status = 'pending',
			sent_count = 0,
			total_count = 0,
			date_added = NOW(),
			date_modified = NOW()");
		return $this->db->getLastId();
	}

	public function editCampaign($schedule_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "mail_schedule SET
			store_id = '" . (int)($data['store_id'] ?? 0) . "',
			recipient_type = '" . $this->db->escape((string)($data['recipient_type'] ?? 'newsletter')) . "',
			recipient_data = '" . $this->db->escape((string)($data['recipient_data'] ?? '')) . "',
			subject = '" . $this->db->escape((string)($data['subject'] ?? '')) . "',
			message = '" . $this->db->escape((string)($data['message'] ?? '')) . "',
			send_at = '" . $this->db->escape((string)($data['send_at'] ?? date('Y-m-d H:i:s'))) . "',
			date_modified = NOW()
			WHERE schedule_id = '" . (int)$schedule_id . "'");
	}

	public function deleteCampaign($schedule_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "mail_schedule WHERE schedule_id = '" . (int)$schedule_id . "'");
	}

	public function getCampaign($schedule_id) {
		return $this->db->query("SELECT * FROM " . DB_PREFIX . "mail_schedule WHERE schedule_id = '" . (int)$schedule_id . "'")->row;
	}

	public function getCampaigns($data = array()) {
		$sort = isset($data['sort']) ? $data['sort'] : 'send_at';
		$order = (isset($data['order']) && $data['order'] == 'ASC') ? 'ASC' : 'DESC';
		$allowed = array('schedule_id', 'subject', 'send_at', 'status', 'date_added');
		if (!in_array($sort, $allowed)) { $sort = 'send_at'; }
		$sql = "SELECT * FROM " . DB_PREFIX . "mail_schedule ORDER BY " . $sort . " " . $order;
		if (isset($data['start']) && isset($data['limit'])) {
			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}
		return $this->db->query($sql)->rows;
	}

	public function getTotalCampaigns() {
		return (int)$this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "mail_schedule")->row['total'];
	}

	public function setStatus($schedule_id, $status) {
		$this->db->query("UPDATE " . DB_PREFIX . "mail_schedule SET status = '" . $this->db->escape($status) . "', date_modified = NOW() WHERE schedule_id = '" . (int)$schedule_id . "'");
	}

	public function getDue() {
		return $this->db->query("SELECT * FROM " . DB_PREFIX . "mail_schedule WHERE status = 'pending' AND send_at <= NOW() ORDER BY send_at ASC")->rows;
	}

	// 解析收件人列表
	public function resolveRecipients($campaign) {
		$emails = array();
		$type = $campaign['recipient_type'];
		$data = $campaign['recipient_data'];

		switch ($type) {
			case 'newsletter':
				$rows = $this->db->query("SELECT email FROM " . DB_PREFIX . "customer WHERE newsletter = 1 AND status = 1 AND email <> ''")->rows;
				foreach ($rows as $r) { $emails[] = $r['email']; }
				break;
			case 'customer_all':
				$rows = $this->db->query("SELECT email FROM " . DB_PREFIX . "customer WHERE status = 1 AND email <> ''")->rows;
				foreach ($rows as $r) { $emails[] = $r['email']; }
				break;
			case 'customer_group':
				$gid = (int)$data;
				$rows = $this->db->query("SELECT email FROM " . DB_PREFIX . "customer WHERE customer_group_id = '" . $gid . "' AND status = 1 AND email <> ''")->rows;
				foreach ($rows as $r) { $emails[] = $r['email']; }
				break;
			case 'emails':
				foreach (preg_split('/[\s,;]+/', (string)$data) as $e) {
					$e = trim($e);
					if ($e && filter_var($e, FILTER_VALIDATE_EMAIL)) { $emails[] = $e; }
				}
				break;
		}
		return array_unique($emails);
	}

	// 发送单条计划:解析收件人,逐条发送,更新计数与状态。返回汇总。
	public function dispatch($schedule_id) {
		$campaign = $this->getCampaign($schedule_id);
		if (!$campaign) { return array('error' => 'campaign not found'); }
		if ($campaign['status'] == 'sent') { return array('info' => 'already sent'); }

		$this->setStatus($schedule_id, 'sending');

		$emails = $this->resolveRecipients($campaign);
		$total = count($emails);
		$this->db->query("UPDATE " . DB_PREFIX . "mail_schedule SET total_count = '" . (int)$total . "' WHERE schedule_id = '" . (int)$schedule_id . "'");

		$store_email = $this->config->get('config_email');
		$store_name  = $this->config->get('config_name');

		$html  = '<html dir="ltr" lang="en"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><title>' . htmlspecialchars($campaign['subject']) . '</title></head><body>' . html_entity_decode($campaign['message'], ENT_QUOTES, 'UTF-8') . '</body></html>';

		$sent = 0;
		foreach ($emails as $email) {
			$mail = new Mail($this->config->get('config_mail_engine'));
			$mail->parameter = $this->config->get('config_mail_parameter');
			$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
			$mail->smtp_username = $this->config->get('config_mail_smtp_username');
			$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
			$mail->smtp_port = $this->config->get('config_mail_smtp_port');
			$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
			$mail->setTo($email);
			$mail->setFrom($store_email);
			$mail->setSender(html_entity_decode($store_name, ENT_QUOTES, 'UTF-8'));
			$mail->setSubject(html_entity_decode($campaign['subject'], ENT_QUOTES, 'UTF-8'));
			$mail->setHtml($html);
			$mail->send();
			$sent++;
			// 每 50 封刷新一次计数,降低 DB 写入
			if ($sent % 50 == 0) {
				$this->db->query("UPDATE " . DB_PREFIX . "mail_schedule SET sent_count = '" . (int)$sent . "' WHERE schedule_id = '" . (int)$schedule_id . "'");
			}
		}

		$this->db->query("UPDATE " . DB_PREFIX . "mail_schedule SET sent_count = '" . (int)$sent . "', status = 'sent', date_modified = NOW() WHERE schedule_id = '" . (int)$schedule_id . "'");

		return array('success' => true, 'sent' => $sent, 'total' => $total);
	}
}
