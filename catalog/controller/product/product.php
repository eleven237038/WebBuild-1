<?php
class ControllerProductProduct extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('product/product');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$this->load->model('catalog/category');

		if (isset($this->request->get['path'])) {
			$path = '';

			$parts = explode('_', (string)$this->request->get['path']);

			$category_id = (int)array_pop($parts);

			foreach ($parts as $path_id) {
				if (!$path) {
					$path = $path_id;
				} else {
					$path .= '_' . $path_id;
				}

				$category_info = $this->model_catalog_category->getCategory($path_id);

				if ($category_info) {
					$data['breadcrumbs'][] = array(
						'text' => $category_info['name'],
						'href' => $this->url->link('product/category', 'path=' . $path)
					);
				}
			}

			// Set the last category breadcrumb
			$category_info = $this->model_catalog_category->getCategory($category_id);

			if ($category_info) {
				$url = '';

				if (isset($this->request->get['sort'])) {
					$url .= '&sort=' . $this->request->get['sort'];
				}

				if (isset($this->request->get['order'])) {
					$url .= '&order=' . $this->request->get['order'];
				}

				if (isset($this->request->get['page'])) {
					$url .= '&page=' . $this->request->get['page'];
				}

				if (isset($this->request->get['limit'])) {
					$url .= '&limit=' . $this->request->get['limit'];
				}

				$data['breadcrumbs'][] = array(
					'text' => $category_info['name'],
					'href' => $this->url->link('product/category', 'path=' . $this->request->get['path'] . $url)
				);
			}
		}

		$this->load->model('catalog/manufacturer');

		if (isset($this->request->get['manufacturer_id'])) {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_brand'),
				'href' => $this->url->link('product/manufacturer')
			);

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($this->request->get['manufacturer_id']);

			if ($manufacturer_info) {
				$data['breadcrumbs'][] = array(
					'text' => $manufacturer_info['name'],
					'href' => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $this->request->get['manufacturer_id'] . $url)
				);
			}
		}

		if (isset($this->request->get['search']) || isset($this->request->get['tag'])) {
			$url = '';

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . $this->request->get['search'];
			}

			if (isset($this->request->get['tag'])) {
				$url .= '&tag=' . $this->request->get['tag'];
			}

			if (isset($this->request->get['description'])) {
				$url .= '&description=' . $this->request->get['description'];
			}

			if (isset($this->request->get['category_id'])) {
				$url .= '&category_id=' . $this->request->get['category_id'];
			}

			if (isset($this->request->get['sub_category'])) {
				$url .= '&sub_category=' . $this->request->get['sub_category'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_search'),
				'href' => $this->url->link('product/search', $url)
			);
		}

		if (isset($this->request->get['product_id'])) {
			$product_id = (int)$this->request->get['product_id'];
		} else {
			$product_id = 0;
		}

		$this->load->model('catalog/product');

		$product_info = $this->model_catalog_product->getProduct($product_id);

		if ($product_info) {
			$url = '';

			if (isset($this->request->get['path'])) {
				$url .= '&path=' . $this->request->get['path'];
			}

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['manufacturer_id'])) {
				$url .= '&manufacturer_id=' . $this->request->get['manufacturer_id'];
			}

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . $this->request->get['search'];
			}

			if (isset($this->request->get['tag'])) {
				$url .= '&tag=' . $this->request->get['tag'];
			}

			if (isset($this->request->get['description'])) {
				$url .= '&description=' . $this->request->get['description'];
			}

			if (isset($this->request->get['category_id'])) {
				$url .= '&category_id=' . $this->request->get['category_id'];
			}

			if (isset($this->request->get['sub_category'])) {
				$url .= '&sub_category=' . $this->request->get['sub_category'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['breadcrumbs'][] = array(
				'text' => $product_info['name'],
				'href' => $this->url->link('product/product', $url . '&product_id=' . $this->request->get['product_id'])
			);

			$this->document->setTitle($product_info['meta_title']);
			$this->document->setDescription($product_info['meta_description']);
			$this->document->setKeywords($product_info['meta_keyword']);
			$this->document->setType('product');
			$this->document->setImage($product_info['image']);
			$this->document->setPrice($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')));
			$this->document->addLink($this->url->link('product/product', 'product_id=' . $this->request->get['product_id']), 'canonical');
			$this->document->addScript('catalog/view/javascript/jquery/magnific/jquery.magnific-popup.min.js');
			$this->document->addStyle('catalog/view/javascript/jquery/magnific/magnific-popup.css');
			$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
			$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js');
			$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
			$this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');
			$this->document->addScript('catalog/view/javascript/jquery/elevatezoom/jquery.elevateZoom.min.js');
			$this->document->addStyle('catalog/view/javascript/jquery/fancybox/jquery.fancybox.css');
			$this->document->addScript('catalog/view/javascript/jquery/fancybox/jquery.fancybox.pack.js');

			$data['heading_title'] = $product_info['name'];

			$data['text_minimum'] = sprintf($this->language->get('text_minimum'), $product_info['minimum']);
			$data['text_login'] = sprintf($this->language->get('text_login'), $this->url->link('account/login'), $this->url->link('account/register'));

			$this->load->model('catalog/review');

			$data['tab_review'] = sprintf($this->language->get('tab_review'), $product_info['reviews']);

			$data['product_id'] = (int)$this->request->get['product_id'];
			$data['manufacturer'] = $product_info['manufacturer'];
			$data['manufacturers'] = $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $product_info['manufacturer_id']);
			$data['model'] = $product_info['model'];
			$data['reward'] = $product_info['reward'];
			$data['points'] = $product_info['points'];
			$data['description'] = html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8');

			if ($product_info['quantity'] <= 0) {
				$data['stock'] = $product_info['stock_status'];
			} elseif ($this->config->get('config_stock_display')) {
				$data['stock'] = $product_info['quantity'];
			} else {
				$data['stock'] = $this->language->get('text_instock');
			}

			$preview_width = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_preview_width') ?: 400;
			$preview_height = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_preview_height') ?: 400;

			$this->load->model('tool/image');
			$data['images'] = array();

			if ($product_info['image']) {
				$data['images'][] = array(
					'thumb' => $this->model_tool_image->resize($product_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height')),
					'preview' => $this->model_tool_image->resize($product_info['image'], $preview_width, $preview_height),
					'popup' => $this->model_tool_image->resize($product_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height')),
				);
			}

			$results = $this->model_catalog_product->getProductImages($this->request->get['product_id']);

			foreach ($results as $result) {
				$data['images'][] = array(
					'thumb' => $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height')),
					'preview' => $this->model_tool_image->resize($result['image'], $preview_width, $preview_height),
					'popup' => $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height')),
				);
			}

			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$data['price'] = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
			} else {
				$data['price'] = false;
			}

			if ((float)$product_info['special']) {
				$data['special'] = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
			} else {
				$data['special'] = false;
			}

			if ($this->config->get('config_tax')) {
				$data['tax'] = $this->currency->format((float)$product_info['special'] ? $product_info['special'] : $product_info['price'], $this->session->data['currency']);
			} else {
				$data['tax'] = false;
			}

			$discounts = $this->model_catalog_product->getProductDiscounts($this->request->get['product_id']);

			$data['discounts'] = array();

			foreach ($discounts as $discount) {
				$data['discounts'][] = array(
					'quantity' => $discount['quantity'],
					'price'    => $this->currency->format($this->tax->calculate($discount['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'])
				);
			}

			$data['options'] = array();

			foreach ($this->model_catalog_product->getProductOptions($this->request->get['product_id']) as $option) {
				$product_option_value_data = array();

				foreach ($option['product_option_value'] as $option_value) {
					if (!$option_value['subtract'] || ($option_value['quantity'] > 0)) {
						if ((($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) && (float)$option_value['price']) {
							$price = $this->currency->format($this->tax->calculate($option_value['price'], $product_info['tax_class_id'], $this->config->get('config_tax') ? 'P' : false), $this->session->data['currency']);
						} else {
							$price = false;
						}

						$product_option_value_data[] = array(
							'product_option_value_id' => $option_value['product_option_value_id'],
							'option_value_id'         => $option_value['option_value_id'],
							'name'                    => $option_value['name'],
							'image'                   => $this->model_tool_image->resize($option_value['image'], 50, 50, false),
							'price'                   => $price,
							'price_prefix'            => $option_value['price_prefix']
						);
					}
				}

				$data['options'][] = array(
					'product_option_id'    => $option['product_option_id'],
					'product_option_value' => $product_option_value_data,
					'option_id'            => $option['option_id'],
					'name'                 => $option['name'],
					'type'                 => $option['type'],
					'value'                => $option['value'],
					'required'             => $option['required']
				);
			}

			if ($product_info['minimum']) {
				$data['minimum'] = $product_info['minimum'];
			} else {
				$data['minimum'] = 1;
			}

			$data['review_status'] = $this->config->get('config_review_status');

			if ($this->config->get('config_review_guest') || $this->customer->isLogged()) {
				$data['review_guest'] = true;
			} else {
				$data['review_guest'] = false;
			}

			if ($this->customer->isLogged()) {
				$data['customer_name'] = get_name($this->customer->getFirstName(), $this->customer->getLastName());
			} else {
				$data['customer_name'] = '';
			}

			$data['reviews'] = sprintf($this->language->get('text_reviews'), (int)$product_info['reviews']);
			$data['rating'] = (int)$product_info['rating'];

			// Captcha
			if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('review', (array)$this->config->get('config_captcha_page'))) {
				$data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'));
			} else {
				$data['captcha'] = '';
			}

			$data['share'] = $this->url->link('product/product', 'product_id=' . (int)$this->request->get['product_id']);

			$data['custom_tags'] = $this->model_catalog_product->getProductCustomTags($this->request->get['product_id']);

			// ----- Specifications: fully DB-driven, mirrors 商品管理 fields -----
			// Combines real product columns (system_column fields managed in
			// 商品管理) with EAV custom_tag values, so the spec table updates
			// automatically as 商品管理 content changes.
			$data['specifications'] = array();

			$spec_defs = array(
				array('key' => 'model',        'label' => 'Model'),
				array('key' => 'sku',         'label' => 'SKU'),
				array('key' => 'upc',         'label' => 'UPC'),
				array('key' => 'ean',         'label' => 'EAN'),
				array('key' => 'jan',         'label' => 'JAN'),
				array('key' => 'isbn',        'label' => 'ISBN'),
				array('key' => 'mpn',         'label' => 'MPN'),
				array('key' => 'location',    'label' => 'Location'),
				array('key' => 'stock_status','label' => 'Stock Status'),
				array('key' => 'manufacturer','label' => 'Manufacturer'),
			);

			foreach ($spec_defs as $def) {
				if (isset($product_info[$def['key']]) && $product_info[$def['key']] !== '' && $product_info[$def['key']] !== null) {
					$data['specifications'][] = array('label' => $def['label'], 'value' => $product_info[$def['key']]);
				}
			}

			if (isset($product_info['weight']) && (float)$product_info['weight'] > 0) {
				$data['specifications'][] = array('label' => 'Weight', 'value' => $product_info['weight'] . ' ' . (isset($product_info['weight_class']) ? $product_info['weight_class'] : ''));
			}

			if (isset($product_info['length']) && ((float)$product_info['length'] > 0 || (float)$product_info['width'] > 0 || (float)$product_info['height'] > 0)) {
				$data['specifications'][] = array('label' => 'Dimensions (L×W×H)', 'value' => $product_info['length'] . ' × ' . $product_info['width'] . ' × ' . $product_info['height'] . ' ' . (isset($product_info['length_class']) ? $product_info['length_class'] : ''));
			}

			// EAV custom_tag values (admin 字段管理 custom fields with a saved value)
			foreach ($data['custom_tags'] as $ct) {
				if (isset($ct['value']) && $ct['value'] !== '') {
					$label = (!empty($ct['parent_name'])) ? $ct['parent_name'] . ': ' . $ct['name'] : $ct['name'];
					$data['specifications'][] = array('label' => $label, 'value' => $ct['value']);
				}
			}

			// Product's primary category name (replaces hardcoded // PEPTIDES label)
			$data['product_category'] = '';
			$product_categories = $this->model_catalog_product->getCategories($this->request->get['product_id']);
			if ($product_categories) {
				$cat_info = $this->model_catalog_category->getCategory($product_categories[0]['category_id']);
				if ($cat_info) {
					$data['product_category'] = $cat_info['name'];
				}
			}


			// ----- PDP appearance config (admin > 商品目录 > 商品详情页管理) -----
			// Per product type: type 1 = global default ('product_detail'); type N>1 = 'product_detail_N'
			// (falls back to global if that type was never customized).
			$this->load->model('setting/setting');
			$_pdp_type_id = isset($product_info['product_type_id']) ? (int)$product_info['product_type_id'] : 0;
			$_pdp_code = ($_pdp_type_id > 1) ? 'product_detail_' . $_pdp_type_id : 'product_detail';
			$_pdp_saved = $this->model_setting_setting->getSetting($_pdp_code, 0);
			if ($_pdp_type_id > 1 && empty($_pdp_saved)) {
				$_pdp_saved = $this->model_setting_setting->getSetting('product_detail', 0);
			}
			$_pdp_def = array(
				'product_detail_show_breadcrumb'       => 1,
				'product_detail_show_gallery'          => 1,
				'product_detail_show_badges'           => 1,
				'product_detail_show_trust_box'        => 1,
				'product_detail_show_tabs'             => 1,
				'product_detail_show_related'          => 1,
				'product_detail_show_research'         => 1,
				'product_detail_title_font_size'       => 38,
				'product_detail_body_font_size'        => 15,
				'product_detail_coa_badge_text'        => '★ COA ON FILE',
				'product_detail_batch_verified_text'   => 'BATCH-VERIFIED',
				'product_detail_tab_details_label'     => 'DETAILS',
				'product_detail_tab_coa_label'         => 'CERTIFICATE OF ANALYSIS',
				'product_detail_tab_shipping_label'    => 'SHIPPING & RETURNS',
				'product_detail_tab_details_body'      => 'Premium research-grade compound. Third-party HPLC tested with batch-specific Certificate of Analysis. Each order includes full documentation for complete traceability.',
				'product_detail_tab_coa_body'          => "Every batch of this compound undergoes independent third-party High-Performance Liquid Chromatography (HPLC) and Mass Spectrometry (MS) verification. A Certificate of Analysis (COA) is included with every order, documenting batch-specific purity data, molecular weight confirmation, and analytical methodology.\n\nTo request a specific batch COA, contact our support team with your order number.",
				'product_detail_tab_shipping_body'     => "Orders placed before 2 PM EST ship same business day via express courier with temperature-controlled packaging. Domestic delivery typically arrives within 2-3 business days. International orders ship within 24 hours and arrive in 5-10 business days depending on customs clearance.\n\nReturns accepted within 30 days for unopened products. If a product does not meet stated purity specifications, a full refund is issued upon verification.",
				'product_detail_trust_item_1'          => '30-day lab-verified guarantee',
				'product_detail_trust_item_2'          => 'Free shipping over $150',
				'product_detail_trust_item_3'          => 'Certificate of Analysis included',
				'product_detail_related_title'         => 'MORE COMPOUNDS',
				'product_detail_research_title'        => 'RESEARCH LIBRARY',
				'product_detail_research_link_1_label' => 'The Complete Guide to HPLC Testing for Research Peptides',
				'product_detail_research_link_1_url'   => '#',
				'product_detail_research_link_2_label' => 'Understanding Mass Spectrometry for Peptide Verification',
				'product_detail_research_link_2_url'   => '#',
				'product_detail_research_link_3_label' => 'Cold-Chain Logistics: Best Practices for Peptide Storage',
				'product_detail_research_link_3_url'   => '#',
				'product_detail_research_link_4_label' => 'Endotoxin Testing in Peptide Research: Why It Matters',
				'product_detail_research_link_4_url'   => '#',
				'product_detail_primary_color'         => '#10B981',
				'product_detail_bg_navy'               => '#0F172A',
			);
			$_pdp = array_merge($_pdp_def, $_pdp_saved);
			$data['pdp'] = array(
				'show_breadcrumb'     => $_pdp['product_detail_show_breadcrumb'],
				'show_gallery'        => $_pdp['product_detail_show_gallery'],
				'show_badges'         => $_pdp['product_detail_show_badges'],
				'show_trust_box'      => $_pdp['product_detail_show_trust_box'],
				'show_tabs'           => $_pdp['product_detail_show_tabs'],
				'show_related'        => $_pdp['product_detail_show_related'],
				'show_research'       => $_pdp['product_detail_show_research'],
				'title_font_size'     => $_pdp['product_detail_title_font_size'],
				'body_font_size'      => $_pdp['product_detail_body_font_size'],
				'coa_badge_text'      => $_pdp['product_detail_coa_badge_text'],
				'batch_verified_text' => $_pdp['product_detail_batch_verified_text'],
				'tab_details_label'   => $_pdp['product_detail_tab_details_label'],
				'tab_coa_label'       => $_pdp['product_detail_tab_coa_label'],
				'tab_shipping_label'  => $_pdp['product_detail_tab_shipping_label'],
				'tab_details_body'    => $_pdp['product_detail_tab_details_body'],
				'tab_coa_body'        => $_pdp['product_detail_tab_coa_body'],
				'tab_shipping_body'   => $_pdp['product_detail_tab_shipping_body'],
				'trust_item_1'        => $_pdp['product_detail_trust_item_1'],
				'trust_item_2'        => $_pdp['product_detail_trust_item_2'],
				'trust_item_3'        => $_pdp['product_detail_trust_item_3'],
				'related_title'       => $_pdp['product_detail_related_title'],
				'research_title'      => $_pdp['product_detail_research_title'],
				'research_links'      => array(
					array('label' => $_pdp['product_detail_research_link_1_label'], 'url' => $_pdp['product_detail_research_link_1_url']),
					array('label' => $_pdp['product_detail_research_link_2_label'], 'url' => $_pdp['product_detail_research_link_2_url']),
					array('label' => $_pdp['product_detail_research_link_3_label'], 'url' => $_pdp['product_detail_research_link_3_url']),
					array('label' => $_pdp['product_detail_research_link_4_label'], 'url' => $_pdp['product_detail_research_link_4_url']),
				),
				'primary_color'       => $_pdp['product_detail_primary_color'],
				'bg_navy'             => $_pdp['product_detail_bg_navy'],
			);

$data['products'] = array();

			$results = $this->model_catalog_product->getProductRelated($this->request->get['product_id']);

			foreach ($results as $result) {
				$image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_height'));

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price = false;
				}

				if ((float)$result['special']) {
					$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$special = false;
				}

				if ($this->config->get('config_tax')) {
					$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
				} else {
					$tax = false;
				}

				if ($this->config->get('config_review_status')) {
					$rating = (int)$result['rating'];
				} else {
					$rating = false;
				}

				$data['products'][] = array(
					'product_id'  => $result['product_id'],
					'thumb'       => $image,
					'name'        => $result['name'],
					'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
					'price'       => $price,
					'special'     => $special,
					'tax'         => $tax,
					'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
					'rating'      => $rating,
					'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id'])
				);
			}

			$data['tags'] = array();

			if ($product_info['tag']) {
				$tags = explode(',', $product_info['tag']);

				foreach ($tags as $tag) {
					$data['tags'][] = array(
						'tag'  => trim($tag),
						'href' => $this->url->link('product/search', 'tag=' . trim($tag))
					);
				}
			}

			$data['recurrings'] = $this->model_catalog_product->getProfiles($this->request->get['product_id']);

			$this->model_catalog_product->updateViewed($this->request->get['product_id']);

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('product/product', $data));
		} else {
			$url = '';

			if (isset($this->request->get['path'])) {
				$url .= '&path=' . $this->request->get['path'];
			}

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['manufacturer_id'])) {
				$url .= '&manufacturer_id=' . $this->request->get['manufacturer_id'];
			}

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . $this->request->get['search'];
			}

			if (isset($this->request->get['tag'])) {
				$url .= '&tag=' . $this->request->get['tag'];
			}

			if (isset($this->request->get['description'])) {
				$url .= '&description=' . $this->request->get['description'];
			}

			if (isset($this->request->get['category_id'])) {
				$url .= '&category_id=' . $this->request->get['category_id'];
			}

			if (isset($this->request->get['sub_category'])) {
				$url .= '&sub_category=' . $this->request->get['sub_category'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link('product/product', $url . '&product_id=' . $product_id)
			);

			$this->document->setTitle($this->language->get('text_error'));

			$data['continue'] = $this->url->link('common/home');

			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('error/not_found', $data));
		}
	}

	public function review() {
		$this->load->language('product/product');

		$this->load->model('catalog/review');

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$data['reviews'] = array();

		$review_total = $this->model_catalog_review->getTotalReviewsByProductId($this->request->get['product_id']);

		$results = $this->model_catalog_review->getReviewsByProductId($this->request->get['product_id'], ($page - 1) * 5, 5);

		foreach ($results as $result) {
			$data['reviews'][] = array(
				'author'     => $result['author'],
				'text'       => nl2br($result['text']),
				'rating'     => (int)$result['rating'],
				'date_added' => date($this->language->get('datetime_format'), strtotime($result['date_added']))
			);
		}

		$pagination = new Pagination();
		$pagination->total = $review_total;
		$pagination->page = $page;
		$pagination->limit = 5;
		$pagination->url = $this->url->link('product/product/review', 'product_id=' . $this->request->get['product_id'] . '&page={page}');

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($review_total) ? (($page - 1) * 5) + 1 : 0, ((($page - 1) * 5) > ($review_total - 5)) ? $review_total : ((($page - 1) * 5) + 5), $review_total, ceil($review_total / 5));

		$this->response->setOutput($this->load->view('product/review', $data));
	}

	public function write() {
		$this->load->language('product/product');

		$json = array();

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			if ((utf8_strlen($this->request->post['name']) < 2) || (utf8_strlen($this->request->post['name']) > 25)) {
				$json['error'] = $this->language->get('error_name');
			}

			if ((utf8_strlen($this->request->post['text']) < 5) || (utf8_strlen($this->request->post['text']) > 1000)) {
				$json['error'] = $this->language->get('error_text');
			}

			if (empty($this->request->post['rating']) || $this->request->post['rating'] < 0 || $this->request->post['rating'] > 5) {
				$json['error'] = $this->language->get('error_rating');
			}

			// Captcha
			if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('review', (array)$this->config->get('config_captcha_page'))) {
				$captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

				if ($captcha) {
					$json['error'] = $captcha;
				}
			}

			if (!isset($json['error'])) {
				$this->load->model('catalog/review');

				$this->model_catalog_review->addReview($this->request->get['product_id'], $this->request->post);

				$json['success'] = $this->language->get('text_success');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function getRecurringDescription() {
		$this->load->language('product/product');
		$this->load->model('catalog/product');

		if (isset($this->request->post['product_id'])) {
			$product_id = $this->request->post['product_id'];
		} else {
			$product_id = 0;
		}

		if (isset($this->request->post['recurring_id'])) {
			$recurring_id = $this->request->post['recurring_id'];
		} else {
			$recurring_id = 0;
		}

		if (isset($this->request->post['quantity'])) {
			$quantity = $this->request->post['quantity'];
		} else {
			$quantity = 1;
		}

		$product_info = $this->model_catalog_product->getProduct($product_id);

		$recurring_info = $this->model_catalog_product->getProfile($product_id, $recurring_id);

		$json = array();

		if ($product_info && $recurring_info) {
			if (!$json) {
				$frequencies = array(
					'day'        => $this->language->get('text_day'),
					'week'       => $this->language->get('text_week'),
					'semi_month' => $this->language->get('text_semi_month'),
					'month'      => $this->language->get('text_month'),
					'year'       => $this->language->get('text_year'),
				);

				if ($recurring_info['trial_status'] == 1) {
					$price = $this->currency->format($this->tax->calculate($recurring_info['trial_price'] * $quantity, $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					$trial_text = sprintf($this->language->get('text_trial_description'), $price, $recurring_info['trial_cycle'], $frequencies[$recurring_info['trial_frequency']], $recurring_info['trial_duration']) . ' ';
				} else {
					$trial_text = '';
				}

				$price = $this->currency->format($this->tax->calculate($recurring_info['price'] * $quantity, $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

				if ($recurring_info['duration']) {
					$text = $trial_text . sprintf($this->language->get('text_payment_description'), $price, $recurring_info['cycle'], $frequencies[$recurring_info['frequency']], $recurring_info['duration']);
				} else {
					$text = $trial_text . sprintf($this->language->get('text_payment_cancel'), $price, $recurring_info['cycle'], $frequencies[$recurring_info['frequency']], $recurring_info['duration']);
				}

				$json['success'] = $text;
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
