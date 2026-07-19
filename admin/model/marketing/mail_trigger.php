<?php
class ModelMarketingMailTrigger extends Model {

	public function addTrigger($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "mail_trigger SET
			code = '" . $this->db->escape((string)($data['code'] ?? '')) . "',
			event = '" . $this->db->escape((string)($data['event'] ?? '')) . "',
			subject = '" . $this->db->escape((string)($data['subject'] ?? '')) . "',
			message = '" . $this->db->escape((string)($data['message'] ?? '')) . "',
			status = '" . (int)($data['status'] ?? 1) . "',
			date_added = NOW(),
			date_modified = NOW()");
		return $this->db->getLastId();
	}

	public function editTrigger($trigger_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "mail_trigger SET
			code = '" . $this->db->escape((string)($data['code'] ?? '')) . "',
			event = '" . $this->db->escape((string)($data['event'] ?? '')) . "',
			subject = '" . $this->db->escape((string)($data['subject'] ?? '')) . "',
			message = '" . $this->db->escape((string)($data['message'] ?? '')) . "',
			status = '" . (int)($data['status'] ?? 1) . "',
			date_modified = NOW()
			WHERE trigger_id = '" . (int)$trigger_id . "'");
	}

	public function deleteTrigger($trigger_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "mail_trigger WHERE trigger_id = '" . (int)$trigger_id . "'");
	}

	public function getTrigger($trigger_id) {
		return $this->db->query("SELECT * FROM " . DB_PREFIX . "mail_trigger WHERE trigger_id = '" . (int)$trigger_id . "'")->row;
	}

	public function getTriggers($data = array()) {
		$sort = isset($data['sort']) ? $data['sort'] : 'event';
		$order = (isset($data['order']) && $data['order'] == 'DESC') ? 'DESC' : 'ASC';
		$allowed = array('trigger_id', 'code', 'event', 'subject', 'status', 'date_added');
		if (!in_array($sort, $allowed)) { $sort = 'event'; }
		$sql = "SELECT * FROM " . DB_PREFIX . "mail_trigger ORDER BY " . $sort . " " . $order;
		if (isset($data['start']) && isset($data['limit'])) {
			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}
		return $this->db->query($sql)->rows;
	}

	public function getTotalTriggers() {
		return (int)$this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "mail_trigger")->row['total'];
	}

	// 取某事件下所有启用的模板
	public function getTriggersByEvent($event) {
		return $this->db->query("SELECT * FROM " . DB_PREFIX . "mail_trigger WHERE event = '" . $this->db->escape($event) . "' AND status = 1")->rows;
	}

	public function setStatus($trigger_id, $status) {
		$this->db->query("UPDATE " . DB_PREFIX . "mail_trigger SET status = '" . (int)$status . "', date_modified = NOW() WHERE trigger_id = '" . (int)$trigger_id . "'");
	}
}
