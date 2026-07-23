<?php
/**
 * sale/payment - 支付管理 (顶级菜单, 与前端内容同级).
 *
 * 完整移植自 插件管理 > 支付模块 (extension/extension/payment). 原页面是 AJAX
 * 片段 (无 header/column_left/footer, 设计成嵌进 marketplace/extension 的 tab),
 * 这里重建为独立全页: 复用 extension/extension/payment 语言串 + extension/payment/*
 * 各支付方式编辑页 (它们本身是全页), 自带 install/uninstall 动作并回渲染本全页。
 *
 * install/uninstall 仍走 model_setting_extension (与原版一致) 并给当前用户组授予
 * extension/payment/{code} 的 access+modify 权限, 保证"编辑"链接可用。
 */
class ControllerSalePayment extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/extension/payment');
		$this->load->model('setting/extension');
		$this->getList();
	}

	public function install() {
		$this->load->language('extension/extension/payment');
		$this->load->model('setting/extension');

		if ($this->validate()) {
			$this->model_setting_extension->install('payment', $this->request->get['extension']);

			$this->load->model('user/user_group');
			$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/payment/' . $this->request->get['extension']);
			$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/payment/' . $this->request->get['extension']);

			// Call install method if it exists
			$this->load->controller('extension/payment/' . $this->request->get['extension'] . '/install');

			$this->session->data['success'] = $this->language->get('text_success');
		}

		$this->getList();
	}

	public function uninstall() {
		$this->load->language('extension/extension/payment');
		$this->load->model('setting/extension');

		if ($this->validate()) {
			$this->model_setting_extension->uninstall('payment', $this->request->get['extension']);

			// Call uninstall method if it exists
			$this->load->controller('extension/payment/' . $this->request->get['extension'] . '/uninstall');

			$this->session->data['success'] = $this->language->get('text_success');
		}

		$this->getList();
	}

	protected function getList() {
		$this->document->setTitle($this->language->get('heading_title'));

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('sale/payment', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['user_token'] = $this->session->data['user_token'];

		$this->load->model('setting/extension');
		$extensions = $this->model_setting_extension->getInstalled('payment');

		foreach ($extensions as $key => $value) {
			if (!is_file(DIR_APPLICATION . 'controller/extension/payment/' . $value . '.php') && !is_file(DIR_APPLICATION . 'controller/payment/' . $value . '.php')) {
				$this->model_setting_extension->uninstall('payment', $value);
				unset($extensions[$key]);
			}
		}

		$data['extensions'] = array();

		// Compatibility code for old extension folders
		$files = glob(DIR_APPLICATION . 'controller/extension/payment/*.php');

		if ($files) {
			foreach ($files as $file) {
				$extension = basename($file, '.php');

				$this->load->language('extension/payment/' . $extension, 'extension');

				$text_link = $this->language->get('extension')->get('text_' . $extension);

				if ($text_link != 'text_' . $extension) {
					$link = $text_link;
				} else {
					$link = '';
				}

				$data['extensions'][] = array(
					'name'       => $this->language->get('extension')->get('heading_title'),
					'link'       => $link,
					'status'     => $this->config->get('payment_' . $extension . '_status') ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
					'sort_order' => $this->config->get('payment_' . $extension . '_sort_order'),
					'install'    => $this->url->link('sale/payment/install', 'user_token=' . $this->session->data['user_token'] . '&extension=' . $extension, true),
					'uninstall'  => $this->url->link('sale/payment/uninstall', 'user_token=' . $this->session->data['user_token'] . '&extension=' . $extension, true),
					'installed'  => in_array($extension, $extensions),
					'edit'       => $this->url->link('extension/payment/' . $extension, 'user_token=' . $this->session->data['user_token'], true)
				);
			}
		}

		// heading_title/text_list/columns/error_permission 来自 extension/extension/payment 语言;
		// button_*/text_no_results/text_home/text_enabled/text_disabled 来自基础 zh-cn.php (自动加载)。
		$data['heading_title']     = $this->language->get('heading_title');
		$data['text_list']         = $this->language->get('text_list');
		$data['text_no_results']   = $this->language->get('text_no_results');
		$data['button_edit']       = $this->language->get('button_edit');
		$data['button_install']    = $this->language->get('button_install');
		$data['button_uninstall']  = $this->language->get('button_uninstall');
		$data['column_name']       = $this->language->get('column_name');
		$data['column_status']     = $this->language->get('column_status');
		$data['column_sort_order'] = $this->language->get('column_sort_order');
		$data['column_action']     = $this->language->get('column_action');

		$data['header']      = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']      = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('sale/payment', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'sale/payment')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
