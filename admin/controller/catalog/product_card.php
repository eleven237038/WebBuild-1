<?php
class ControllerCatalogProductCard extends Controller {
	private $error = array();

	// Code namespace owned entirely by this page. editSetting() clobbers it (safe here).
	private $code = 'product_card';

	private $checkboxes = array(
		'product_card_show_image',
		'product_card_show_name',
		'product_card_show_description',
		'product_card_show_price',
		'product_card_show_wishlist',
		'product_card_show_badges',
		'product_card_show_add_button',
	);

	private $keys = array(
		// toggles
		'product_card_show_image',
		'product_card_show_name',
		'product_card_show_description',
		'product_card_show_price',
		'product_card_show_wishlist',
		'product_card_show_badges',
		'product_card_show_add_button',
		// sizing
		'product_card_image_height',
		'product_card_desc_length',
		'product_card_desc_clamp',
		'product_card_name_font_size',
		'product_card_price_font_size',
		// text
		'product_card_add_btn_text',
		// colors
		'product_card_primary_color',
		'product_card_name_color',
		'product_card_price_color',
	);

	private $defaults = array(
		'product_card_show_image'        => 1,
		'product_card_show_name'         => 1,
		'product_card_show_description'  => 1,
		'product_card_show_price'        => 1,
		'product_card_show_wishlist'     => 1,
		'product_card_show_badges'       => 1,
		'product_card_show_add_button'   => 1,
		'product_card_image_height'      => 200,
		'product_card_desc_length'       => 100,
		'product_card_desc_clamp'        => 2,
		'product_card_name_font_size'    => 15,
		'product_card_price_font_size'   => 22,
		'product_card_add_btn_text'      => '+',
		'product_card_primary_color'     => '#10B981',
		'product_card_name_color'        => '#0F172A',
		'product_card_price_color'       => '#10B981',
	);

	public function index() {
		if (!$this->user->hasPermission('access', 'catalog/product_card')) {
			$this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}
		$this->load->language('catalog/product_card');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');

		// Per product type: type 1 = global default (code 'product_card'); type N>1 = 'product_card_N'.
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
			// editSettingCode (not editSetting): the per-type code 'product_card_N'
			// is not a prefix of the 'product_card_*' keys, so editSetting's key
			// guard would silently write 0 rows. See ModelSettingSetting::editSettingCode.
			$this->model_setting_setting->editSettingCode($code, $save);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('catalog/product_card', 'user_token=' . $this->session->data['user_token'] . '&product_type_id=' . $product_type_id, true));
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
			'href' => $this->url->link('catalog/product_card', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('catalog/product_card', 'user_token=' . $this->session->data['user_token'] . '&product_type_id=' . $product_type_id, true);
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
				$data[$key] = isset($this->defaults[$key]) ? $this->defaults[$key] : '';
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
				'url'             => $this->url->link('catalog/product_card', 'user_token=' . $this->session->data['user_token'] . '&product_type_id=' . $_tid, true),
				'active'          => ($_tid == $product_type_id),
			);
		}
		$data['product_type_id'] = $product_type_id;

		// Shared strings
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

		// Per-field entry labels
		foreach ($this->keys as $key) {
			$data['entry_' . $key] = $this->language->get('entry_' . $key);
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/product_card_form', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'catalog/product_card')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
