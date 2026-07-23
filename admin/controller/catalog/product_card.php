<?php
class ControllerCatalogProductCard extends Controller {
	private $error = array();

	// Code namespace owned entirely by this page. editSetting() clobbers it (safe here).
	private $code = 'product_card';

	// Boolean toggles (still 1/0) - only the two action buttons remain as checkboxes.
	private $checkboxes = array(
		'product_card_show_wishlist',
		'product_card_show_add_button',
	);

	// Field-mapping slots: value is a custom_tag tag_id (which field feeds this slot) or '' (blank = hide).
	private $field_maps = array(
		'product_card_show_image',
		'product_card_show_name',
		'product_card_show_description',
		'product_card_show_price',
		'product_card_show_badges',
	);

	// slot => system_column used to resolve the default field tag_id per product type.
	private $map_defaults = array(
		'product_card_show_image'       => 'image',
		'product_card_show_name'        => 'name',
		'product_card_show_description' => 'description',
		'product_card_show_price'       => 'price',
		'product_card_show_badges'      => '',  // no system default; opt-in
	);

	private $keys = array(
		// field-mapping slots (tag_id or '')
		'product_card_show_image',
		'product_card_show_name',
		'product_card_show_description',
		'product_card_show_price',
		'product_card_show_badges',
		// boolean toggles
		'product_card_show_wishlist',
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

	// JSON repeaters (stored serialized): admin-defined static badges shown on
	// every card of this type, on top of the data-driven badges.
	private $repeaters = array(
		'product_card_badges',  // array of {text, bg, color}
	);

	// Defaults are owned by ModelCatalogProduct::cardDefaults() (single source
	// of truth shared with getCardConfig on the storefront) and loaded in index().

	public function index() {
		if (!$this->user->hasPermission('access', 'catalog/product_card')) {
			$this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}
		$this->load->language('catalog/product_card');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');
		// Defaults: single source of truth lives in the catalog product model
		// (cardDefaults), shared with getCardConfig() on the storefront.
		$this->load->model('catalog/product');
		$defaults = $this->model_catalog_product->cardDefaults();

		// Per product type: type 1 = global default (code 'product_card'); type N>1 = 'product_card_N'.
		$product_type_id = isset($this->request->get['product_type_id']) ? (int)$this->request->get['product_type_id'] : 1;
		if ($product_type_id < 1) { $product_type_id = 1; }
		$code = ($product_type_id > 1) ? $this->code . '_' . $product_type_id : $this->code;

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$save = array();
			foreach ($this->keys as $key) {
				if (in_array($key, $this->checkboxes, true)) {
					$save[$key] = isset($this->request->post[$key]) ? 1 : 0;
				} elseif (in_array($key, $this->field_maps, true)) {
					// field-mapping slot: store tag_id (int) or '' (blank = hide)
					$save[$key] = (isset($this->request->post[$key]) && $this->request->post[$key] !== '') ? (int)$this->request->post[$key] : '';
				} else {
					$save[$key] = isset($this->request->post[$key]) ? $this->request->post[$key] : '';
				}
			}
			// Repeaters: pass as PHP arrays - editSettingCode json_encodes them.
			foreach ($this->collectRepeaters() as $rkey => $rval) {
				$save[$rkey] = $rval;
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

		// Build field-mapping dropdown options from this product type's custom_tag fields.
		$this->load->model('catalog/custom_tag');
		$type_fields = $this->model_catalog_custom_tag->getTagsByType($product_type_id);
		$parent_names = array();
		foreach ($type_fields as $r) { $parent_names[(int)$r['tag_id']] = $r['name']; }
		$tag_ids = array();
		$sysmap = array();  // system_column => tag_id (default resolution)
		$field_options = array();
		foreach ($type_fields as $r) {
			$tid = (int)$r['tag_id'];
			$tag_ids[$tid] = true;
			if ($r['system_column'] !== '') { $sysmap[$r['system_column']] = $tid; }
			if ($r['tag_type'] === 'struct') { continue; }  // structure parents carry no value
			$label = (!empty($r['parent_id']) && isset($parent_names[(int)$r['parent_id']]))
				? $parent_names[(int)$r['parent_id']] . ' / ' . $r['name']
				: $r['name'];
			$field_options[] = array('tag_id' => $tid, 'label' => $label);
		}
		$data['field_options'] = $field_options;

		foreach ($this->keys as $key) {
			if (isset($this->request->post[$key])) {
				$data[$key] = $this->request->post[$key];
			} elseif (array_key_exists($key, $saved)) {
				$v = $saved[$key];
				// field-mapping slot: migrate legacy boolean ('1'/'0') or stale tag_id -> default
				if (in_array($key, $this->field_maps, true)) {
					$iv = (int)$v;
					$v = ($v !== '' && $v !== '0' && isset($tag_ids[$iv])) ? $iv : $this->mapDefault($key, $sysmap);
				}
				$data[$key] = $v;
			} else {
				if (in_array($key, $this->field_maps, true)) {
					$data[$key] = $this->mapDefault($key, $sysmap);
				} else {
					$data[$key] = isset($defaults[$key]) ? $defaults[$key] : '';
				}
			}
		}

		// Repeaters: prefer sanitized POST (re-display on error), else DB (decoded
		// array), else the default array. Always keep >=1 empty row to edit.
		foreach ($this->repeaters as $rkey) {
			if (isset($this->request->post[$rkey])) {
				// collectRepeaters($only) returns array($key => $rows); extract inner.
				$got = $this->collectRepeaters($rkey);
				$data[$rkey] = isset($got[$rkey]) ? $got[$rkey] : array();
			} elseif (array_key_exists($rkey, $saved) && is_array($saved[$rkey])) {
				$data[$rkey] = array_values($saved[$rkey]);
			} else {
				$data[$rkey] = isset($defaults[$rkey]) && is_array($defaults[$rkey]) ? array_values($defaults[$rkey]) : array();
			}
		}
		if (empty($data['product_card_badges'])) {
			$data['product_card_badges'] = array(array('text' => '', 'bg' => '#10B981', 'color' => '#ffffff'));
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
		$data['text_section_map']     = $this->language->get('text_section_map');
		$data['text_none']            = $this->language->get('text_none');
		$data['text_section_size']    = $this->language->get('text_section_size');
		$data['text_section_text']    = $this->language->get('text_section_text');
		$data['text_section_color']   = $this->language->get('text_section_color');
		$data['text_preview']         = $this->language->get('text_preview');
		$data['text_preview_hint']    = $this->language->get('text_preview_hint');

		// Per-field entry labels
		foreach ($this->keys as $key) {
			$data['entry_' . $key] = $this->language->get('entry_' . $key);
		}
		// Repeater labels + button strings.
		$data['entry_product_card_badges'] = $this->language->get('entry_product_card_badges');
		$data['text_badge_text']   = $this->language->get('text_badge_text');
		$data['text_badge_bg']     = $this->language->get('text_badge_bg');
		$data['text_badge_color']  = $this->language->get('text_badge_color');
		$data['button_add_badge']  = $this->language->get('button_add_badge');
		$data['button_remove']     = $this->language->get('button_remove');

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

	/**
	 * Resolve the default tag_id for a field-mapping slot from the product type's
	 * system_column-tag_id map. Returns '' when the type has no matching system field.
	 */
	private function mapDefault($key, $sysmap) {
		$col = isset($this->map_defaults[$key]) ? $this->map_defaults[$key] : '';
		return ($col !== '' && isset($sysmap[$col])) ? $sysmap[$col] : '';
	}

	/**
	 * Collect + sanitize the static-badge repeater rows from POST. Pass a single
	 * key to fetch just that repeater (re-display on error); omit to fetch all
	 * (used on save). Rows with no text are dropped. Returns PHP arrays - the
	 * model's editSettingCode json_encodes them (serialized=1).
	 */
	private function collectRepeaters($only = null) {
		$out = array();
		if ($only === null || $only === 'product_card_badges') {
			$raw = isset($this->request->post['product_card_badges']) && is_array($this->request->post['product_card_badges'])
				? $this->request->post['product_card_badges'] : array();
			$badges = array();
			foreach ($raw as $row) {
				$text  = isset($row['text'])  ? trim((string)$row['text'])  : '';
				if ($text === '') { continue; }  // drop empty rows on save
				$bg    = isset($row['bg'])    ? (string)$row['bg']    : '#10B981';
				$color = isset($row['color']) ? (string)$row['color'] : '#ffffff';
				$badges[] = array('text' => $text, 'bg' => $bg, 'color' => $color);
			}
			$out['product_card_badges'] = $badges;
		}
		return $out;
	}
}
