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
		// text: section titles
		'product_detail_related_title',
		'product_detail_research_title',
		// colors
		'product_detail_primary_color',
		'product_detail_bg_navy',
	);

	// JSON repeaters (stored serialized): each is one setting row holding an
	// array. The admin form manages N rows via JS (add/remove/reorder) - no
	// longer capped at 3 trust items / 4 research links / 3 fixed tabs.
	private $repeaters = array(
		'product_detail_trust_items',    // array of strings
		'product_detail_tabs',           // array of {label, body, is_details}
		'product_detail_research_links', // array of {label, url}
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
			// Repeaters: pass as PHP arrays - editSettingCode json_encodes them
			// (serialized=1). Sanitize + drop empty rows so the store stays clean.
			foreach ($this->collectRepeaters() as $rkey => $rval) {
				$save[$rkey] = $rval;
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

		// Repeaters: prefer sanitized POST (re-display on error), else DB (already
		// decoded to an array by getSetting), else the default array. Always keep
		// at least one empty row so the admin can start adding.
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
		// Guarantee a non-empty editor on first load.
		if (empty($data['product_detail_trust_items'])) { $data['product_detail_trust_items'] = array(''); }
		if (empty($data['product_detail_tabs'])) { $data['product_detail_tabs'] = array(array('label' => '', 'body' => '', 'is_details' => 0)); }
		if (empty($data['product_detail_research_links'])) { $data['product_detail_research_links'] = array(array('label' => '', 'url' => '')); }

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
		// Repeater labels + button strings.
		$data['entry_product_detail_trust_items']    = $this->language->get('entry_product_detail_trust_items');
		$data['entry_product_detail_tabs']           = $this->language->get('entry_product_detail_tabs');
		$data['entry_product_detail_research_links'] = $this->language->get('entry_product_detail_research_links');
		$data['text_tab_label']    = $this->language->get('text_tab_label');
		$data['text_tab_body']     = $this->language->get('text_tab_body');
		$data['text_tab_is_details'] = $this->language->get('text_tab_is_details');
		$data['help_tab_is_details'] = $this->language->get('help_tab_is_details');
		$data['text_rl_label']     = $this->language->get('text_rl_label');
		$data['text_rl_url']       = $this->language->get('text_rl_url');
		$data['button_add_trust']  = $this->language->get('button_add_trust');
		$data['button_add_tab']    = $this->language->get('button_add_tab');
		$data['button_add_link']   = $this->language->get('button_add_link');
		$data['button_remove']     = $this->language->get('button_remove');

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

	/**
	 * Collect + sanitize the repeater rows from POST. Pass a single key to fetch
	 * just that repeater (used for re-display on validation error); omit it to
	 * fetch all three (used on save). Empty rows are dropped so the store stays
	 * clean. Arrays are returned (not JSON) - editSettingCode serializes them.
	 */
	private function collectRepeaters($only = null) {
		$out = array();

		// trust_items: flat array of strings.
		if ($only === null || $only === 'product_detail_trust_items') {
			$raw = isset($this->request->post['product_detail_trust_items']) && is_array($this->request->post['product_detail_trust_items'])
				? $this->request->post['product_detail_trust_items'] : array();
			$items = array();
			foreach ($raw as $v) {
				$v = trim((string)$v);
				if ($v !== '') { $items[] = $v; }
			}
			$out['product_detail_trust_items'] = $items;
		}

		// tabs: array of {label, body, is_details}.
		if ($only === null || $only === 'product_detail_tabs') {
			$raw = isset($this->request->post['product_detail_tabs']) && is_array($this->request->post['product_detail_tabs'])
				? $this->request->post['product_detail_tabs'] : array();
			$tabs = array();
			$seen_details = false;
			foreach ($raw as $row) {
				$label = isset($row['label']) ? trim((string)$row['label']) : '';
				$body  = isset($row['body'])  ? (string)$row['body'] : '';
				if ($label === '' && $body === '') { continue; }
				$is_details = !empty($row['is_details']) ? 1 : 0;
				// Only the first details-flagged tab keeps the flag (so description
				// + specifications render once, not duplicated across tabs).
				if ($is_details && $seen_details) { $is_details = 0; }
				if ($is_details) { $seen_details = true; }
				$tabs[] = array('label' => $label, 'body' => $body, 'is_details' => $is_details);
			}
			$out['product_detail_tabs'] = $tabs;
		}

		// research_links: array of {label, url}.
		if ($only === null || $only === 'product_detail_research_links') {
			$raw = isset($this->request->post['product_detail_research_links']) && is_array($this->request->post['product_detail_research_links'])
				? $this->request->post['product_detail_research_links'] : array();
			$links = array();
			foreach ($raw as $row) {
				$label = isset($row['label']) ? trim((string)$row['label']) : '';
				$url   = isset($row['url'])   ? trim((string)$row['url'])   : '';
				if ($label === '' && $url === '') { continue; }
				$links[] = array('label' => $label, 'url' => $url);
			}
			$out['product_detail_research_links'] = $links;
		}

		return $out;
	}
}
