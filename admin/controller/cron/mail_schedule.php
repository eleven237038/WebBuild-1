<?php
// 定时群发邮件 - cron 调度器
// 由 oc_cron 表注册 (action=cron/mail_schedule, cycle=hourly)。
// 运行在 ADMIN 上下文 (marketplace/cron::run 通过 load->controller 调用本控制器)。
class ControllerCronMailSchedule extends Controller {
	public function index($cron_id = 0, $code = '', $cycle = '', $date_added = '', $date_modified = '') {
		@set_time_limit(0);
		@ini_set('memory_limit', '512M');

		$this->load->model('marketing/mail_schedule');

		$due = $this->model_marketing_mail_schedule->getDue();
		foreach ($due as $campaign) {
			$this->model_marketing_mail_schedule->dispatch($campaign['schedule_id']);
		}
	}
}
