<?php
class ControllerCatalogProductDetail extends Controller {
	private $error = array();

	private $code = 'product_detail';

	private $checkboxes = array(
		'product_detail_show_breadcrumb',
		'product_detail_show_gallery',
		'product_detail_show_badges',
		'product_detail_show_trust_box',
		'product_detail_show_tabs',
		'product_detail_show_related',
		'product_detail_show_research',
	);

	private $keys = array(
		// toggles
		'product_detail_show_breadcrumb',
		'product_detail_show_gallery',
		'product_detail_show_badges',
		'product_detail_show_trust_box',
		'product_detail_show_tabs',
		'product_detail_show_related',
		'product_detail_show_research',
		// sizing
		'product_detail_title_font_size',
		'product_detail_body_font_size',
		// text: badges
		'product_detail_coa_badge_text',
		'product_detail_batch_verified_text',
		// text: tab labels
		'product_detail_tab_details_label',
		'product_detail_tab_coa_label',
		'product_detail_tab_shipping_label',
		// text: tab bodies
		'product_detail_tab_details_body',
		'product_detail_tab_coa_body',
		'product_detail_tab_shipping_body',
		// text: trust box
		'product_detail_trust_item_1',
		'product_detail_trust_item_2',
		'product_detail_trust_item_3',
		// text: section titles
		'product_detail_related_title',
		'product_detail_research_title',
		// text: research links
		'product_detail_research_link_1_label',
		'product_detail_research_link_1_url',
		'product_detail_research_link_2_label',
		'product_detail_research_link_2_url',
		'product_detail_research_link_3_label',
		'product_detail_research_link_3_url',
		'product_detail_research_link_4_label',
		'product_detail_research_link_4_url',
		// colors
		'product_detail_primary_color',
		'product_detail_bg_navy',
	);

	// Defaults are owned by ModelCatalogProduct::detailDefaults() (single source
	// of truth shared with the frontend PDP) and loaded in index().

	public function index() {
		if (!$this->user->hasPermission('access', 'catalog/product_detail')) {
			$this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}
		$this->load->language('catalog/product_detail');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');
		// Defaults: single source of truth lives in the catalog product model
		// (detailDefaults), shared with the frontend PDP so admin form and
		// storefront never diverge.
		$this->load->model('catalog/product');
		$defaults = $this->model_catalog_product->detailDefaults();

		// Per product type: type 1 = global default (code 'product_detail'); type N>1 = 'product_detail_N'.
		$product_type_id = isset($this->request->get['product_type_id']) ? (int)$this->request->get['product_type_id'] : 1;
		if ($product_type_id < 1) { $product_type_id = 1; }
		$code = ($product_type_id > 1) ? $this->code . '_' . $product_type_id : $this->code;

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$save = array();
			foreach ($this->keys as $key) {
				if (in_array($key, $this->checkboxes, true)) {
					$save[$key] = isset($this->request->post[$key]) ? 1 : 0;
				} else {
					$save[$key] = isset($this->request->post[$key]) ? $this->request->post[$key] : '';
				}
			}
			// editSettingCode (not editSetting): the per-type code 'product_detail_N'
			// is not a prefix of the 'product_detail_*' keys, so editSetting's key
			// guard would silently write 0 rows. See ModelSettingSetting::editSettingCode.
			$this->model_setting_setting->editSettingCode($code, $save);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('catalog/product_detail', 'user_token=' . $this->session->data['user_token'] . '&product_type_id=' . $product_type_id, true));
		}

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->error['warning'])) { $data['error_warning'] = $this->error['warning']; } else { $data['error_warning'] = ''; }
		if (isset($this->session->data['success'])) { $data['success'] = $this->session->data['success']; unset($this->session->data['success']); } else { $data['success'] = ''; }

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('catalog/product_detail', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('catalog/product_detail', 'user_token=' . $this->session->data['user_token'] . '&product_type_id=' . $product_type_id, true);
		$data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);

		// Load current values: prefer POST (re-display on error), else DB, else default.
		// Unsaved type N>1 inherits the global (type 1) settings until customized.
		$saved = $this->model_setting_setting->getSetting($code, 0);
		if ($product_type_id > 1 && empty($saved)) {
			$saved = $this->model_setting_setting->getSetting($this->code, 0);
		}
		foreach ($this->keys as $key) {
			if (isset($this->request->post[$key])) {
				$data[$key] = $this->request->post[$key];
			} elseif (array_key_exists($key, $saved)) {
				$data[$key] = $saved[$key];
			} else {
				$data[$key] = isset($defaults[$key]) ? $defaults[$key] : '';
			}
		}

		// 商品类型 chip bar (per-type editing)
		$this->load->model('catalog/custom_tag');
		$data['product_types'] = array();
		foreach ($this->model_catalog_custom_tag->getProductTypes() as $_pt) {
			$_tid = (int)$_pt['product_type_id'];
			$data['product_types'][] = array(
				'product_type_id' => $_tid,
				'name'            => $_pt['name'],
				'url'             => $this->url->link('catalog/product_detail', 'user_token=' . $this->session->data['user_token'] . '&product_type_id=' . $_tid, true),
				'active'          => ($_tid == $product_type_id),
			);
		}
		$data['product_type_id'] = $product_type_id;

		$data['heading_title']        = $this->language->get('heading_title');
		$data['text_form']            = $this->language->get('text_form');
		$data['button_save']          = $this->language->get('button_save');
		$data['button_cancel']        = $this->language->get('button_cancel');
		$data['text_section_toggle']  = $this->language->get('text_section_toggle');
		$data['text_section_size']    = $this->language->get('text_section_size');
		$data['text_section_text']    = $this->language->get('text_section_text');
		$data['text_section_color']   = $this->language->get('text_section_color');
		$data['text_preview']         = $this->language->get('text_preview');
		$data['text_preview_hint']    = $this->language->get('text_preview_hint');

		foreach ($this->keys as $key) {
			$data['entry_' . $key] = $this->language->get('entry_' . $key);
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/product_detail_form', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'catalog/product_detail')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
