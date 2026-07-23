<?php
/**
 * sale/payment_api - 支付接口 (支付管理 > 支付接口).
 *
 * 实时调试详情页. 横向 chip 栏列出 "支付模块" (sale/payment) 中的全部支付方式
 * (glob admin/controller/extension/payment/*.php + oc_extension installed 标记),
 * 选中一个后展示该支付方式接口的调试详情:
 *   - 基本信息: 名称 / 是否安装 / 启用状态 / 排序 / 后台编辑链接
 *   - 文件存在性: admin(catalog) 的 controller / model / language / view
 *   - 路由: 后台编辑 / 安装 / 前端路由
 *   - 全部 oc_setting 原始行 (code=payment_<code>, 含 serialized 标记, 实时直查 DB)
 *
 * 只读页 - 安装/卸载/编辑请走 支付模块 (sale/payment) 或 extension/payment/<code>.
 * 每次请求都重新 glob + 重查 DB, 故为实时. 无 APCu 缓存.
 */
class ControllerSalePaymentApi extends Controller {

	public function index() {
		$this->load->language('sale/payment_api');
		$this->load->model('setting/extension');

		// 先把本页语言串固化进 $data —— 后面循环加载各支付方式语言文件会覆盖
		// heading_title 等共享 key, 所以必须在此之前取走.
		$data['heading_title'] = $this->language->get('heading_title');
		$this->document->setTitle($data['heading_title']);
		$data['text_list']         = $this->language->get('text_list');
		$data['text_realtime']     = $this->language->get('text_realtime');
		$data['text_no_method']    = $this->language->get('text_no_method');
		$data['text_select_hint']  = $this->language->get('text_select_hint');
		$data['text_section_info'] = $this->language->get('text_section_info');
		$data['text_section_files']= $this->language->get('text_section_files');
		$data['text_section_routes']= $this->language->get('text_section_routes');
		$data['text_section_settings']= $this->language->get('text_section_settings');
		$data['text_no_settings']  = $this->language->get('text_no_settings');
		$data['text_installed']    = $this->language->get('text_installed');
		$data['text_not_installed']= $this->language->get('text_not_installed');
		$data['text_enabled']      = $this->language->get('text_enabled');
		$data['text_disabled']     = $this->language->get('text_disabled');
		$data['text_yes']          = $this->language->get('text_yes');
		$data['text_no']           = $this->language->get('text_no');
		$data['text_found']        = $this->language->get('text_found');
		$data['text_missing']      = $this->language->get('text_missing');
		$data['text_edit']         = $this->language->get('text_edit');
		$data['column_key']        = $this->language->get('column_key');
		$data['column_value']      = $this->language->get('column_value');
		$data['column_serialized'] = $this->language->get('column_serialized');
		$data['column_file']       = $this->language->get('column_file');
		$data['column_state']      = $this->language->get('column_state');
		$data['button_edit']       = $this->language->get('button_edit');

		$data['user_token'] = $this->session->data['user_token'];

		// ---- 1. 支付方式列表 (与 sale/payment 支付模块 同源) ----
		$extensions = $this->model_setting_extension->getInstalled('payment');

		// 清理已安装但文件缺失的 (与 sale/payment 一致)
		foreach ($extensions as $k => $value) {
			if (!is_file(DIR_APPLICATION . 'controller/extension/payment/' . $value . '.php') && !is_file(DIR_APPLICATION . 'controller/payment/' . $value . '.php')) {
				$this->model_setting_extension->uninstall('payment', $value);
				unset($extensions[$k]);
			}
		}

		$files = glob(DIR_APPLICATION . 'controller/extension/payment/*.php');
		$data['methods'] = array();

		if ($files) {
			foreach ($files as $file) {
				$code = basename($file, '.php');

				// 各支付方式名称 (heading_title); 缺语言文件则回退为 code
				$this->load->language('extension/payment/' . $code);
				$name = $this->language->get('heading_title');
				if ($name === 'heading_title') {
					$name = $code;
				}

				$status = $this->config->get('payment_' . $code . '_status') ? 1 : 0;
				$installed = in_array($code, $extensions);

				$data['methods'][] = array(
					'code'      => $code,
					'name'      => $name,
					'installed' => $installed,
					'status'    => $status,
					'href'      => $this->url->link('sale/payment_api', 'user_token=' . $this->session->data['user_token'] . '&code=' . $code, true),
				);
			}
		}

		// 排序: 已安装且启用 > 已安装 > 未安装; 同级按名称
		usort($data['methods'], function ($a, $b) {
			$sa = ($a['installed'] ? 2 : 0) + $a['status'];
			$sb = ($b['installed'] ? 2 : 0) + $b['status'];
			if ($sa !== $sb) {
				return $sb - $sa;
			}
			return strcmp($a['name'], $b['name']);
		});

		// ---- 2. 当前选中的支付方式 ----
		$selected = isset($this->request->get['code']) ? preg_replace('/[^a-zA-Z0-9_]/', '', (string)$this->request->get['code']) : '';

		if ($selected === '' && $data['methods']) {
			// 默认选第一个已安装+启用; 否则第一个已安装; 否则第一个
			foreach ($data['methods'] as $m) {
				if ($m['installed'] && $m['status']) { $selected = $m['code']; break; }
			}
			if ($selected === '') {
				foreach ($data['methods'] as $m) {
					if ($m['installed']) { $selected = $m['code']; break; }
				}
			}
			if ($selected === '') {
				$selected = $data['methods'][0]['code'];
			}
		}

		$data['selected'] = $selected;

		// ---- 3. 调试详情 ----
		$data['detail'] = $this->buildDetail($selected, $extensions);

		// ---- 4. 面包屑 + chrome ----
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $data['heading_title'],
			'href' => $this->url->link('sale/payment_api', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['header']      = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']      = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('sale/payment_api', $data));
	}

	private function buildDetail($code, $extensions) {
		if ($code === '') {
			return null;
		}

		$detail = array('code' => $code);

		// 名称 + 安装 + 启用 + 排序
		$this->load->language('extension/payment/' . $code);
		$name = $this->language->get('heading_title');
		$detail['name']       = ($name !== 'heading_title') ? $name : $code;
		$detail['installed']  = in_array($code, $extensions);
		$detail['status']     = $this->config->get('payment_' . $code . '_status') ? 1 : 0;
		$detail['sort_order'] = $this->config->get('payment_' . $code . '_sort_order');
		$detail['edit_href']  = $this->url->link('extension/payment/' . $code, 'user_token=' . $this->session->data['user_token'], true);

		// 文件存在性
		$admin_lang_dir = DIR_LANGUAGE . $this->config->get('config_admin_language') . '/';
		$cat_lang_dir   = DIR_CATALOG . 'language/' . $this->config->get('config_language') . '/';
		$paths = array(
			'admin_controller'        => DIR_APPLICATION . 'controller/extension/payment/' . $code . '.php',
			'admin_controller_legacy' => DIR_APPLICATION . 'controller/payment/' . $code . '.php',
			'admin_model'             => DIR_APPLICATION . 'model/extension/payment/' . $code . '.php',
			'admin_language'          => $admin_lang_dir . 'extension/payment/' . $code . '.php',
			'admin_view'              => DIR_TEMPLATE . 'extension/payment/' . $code . '.twig',
			'catalog_controller'      => DIR_CATALOG . 'controller/extension/payment/' . $code . '.php',
			'catalog_model'           => DIR_CATALOG . 'model/extension/payment/' . $code . '.php',
			'catalog_language'        => $cat_lang_dir . 'extension/payment/' . $code . '.php',
			'catalog_view'            => DIR_CATALOG . 'view/theme/default/template/extension/payment/' . $code . '.twig',
		);
		$detail['files'] = array();
		foreach ($paths as $label => $p) {
			$detail['files'][] = array(
				'label'  => $label,
				'path'   => $p,
				'exists' => is_file($p),
			);
		}

		// 路由
		$detail['routes'] = array(
			array('label' => 'admin_edit',    'route' => 'extension/payment/' . $code, 'href' => $detail['edit_href']),
			array('label' => 'admin_install', 'route' => 'sale/payment/install?extension=' . $code, 'href' => $this->url->link('sale/payment/install', 'user_token=' . $this->session->data['user_token'] . '&extension=' . $code, true)),
			array('label' => 'admin_uninstall', 'route' => 'sale/payment/uninstall?extension=' . $code, 'href' => $this->url->link('sale/payment/uninstall', 'user_token=' . $this->session->data['user_token'] . '&extension=' . $code, true)),
			array('label' => 'catalog_route', 'route' => 'extension/payment/' . $code, 'href' => ''),
		);

		// 实时原始 settings (直查 DB, 含 serialized 标记)
		$q = $this->db->query("SELECT `key`, value, serialized FROM " . DB_PREFIX . "setting WHERE code = '" . $this->db->escape('payment_' . $code) . "' AND store_id = 0 ORDER BY `key`");
		$detail['settings'] = array();
		foreach ($q->rows as $row) {
			$val = $row['value'];
			if ($row['serialized']) {
				$decoded = json_decode($val, true);
				if (is_array($decoded)) {
					$val = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
				}
			}
			// 截断超长值便于展示
			if (strlen($val) > 500) {
				$val = substr($val, 0, 497) . '...';
			}
			$detail['settings'][] = array(
				'key'        => $row['key'],
				'value'      => $val,
				'serialized' => (int)$row['serialized'],
			);
		}
		$detail['settings_count'] = count($detail['settings']);

		return $detail;
	}
}
