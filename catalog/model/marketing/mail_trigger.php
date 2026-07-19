<?php
class ModelMarketingMailTrigger extends Model {
	// 取某事件下所有启用的触发模板 (catalog 端只读访问)
	public function getTriggersByEvent($event) {
		return $this->db->query("SELECT * FROM " . DB_PREFIX . "mail_trigger WHERE event = '" . $this->db->escape($event) . "' AND status = 1")->rows;
	}
}
