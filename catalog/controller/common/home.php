<?php
class ControllerCommonHome extends Controller {
	public function index() {
		$this->document->setTitle($this->config->get('config_meta_title'));
		$this->document->setDescription($this->config->get('config_meta_description'));
		$this->document->setKeywords($this->config->get('config_meta_keyword'));

		if (isset($this->request->get['route'])) {
			$this->document->addLink($this->config->get('config_url'), 'canonical');
		}

		// ----- Dynamic Products for Homepage (real DB, same builder as shop) -----
		// Mirrors product/category: handleSingleProduct() + getProductCustomTags()
		// so homepage cards are byte-identical to shop cards (markup, CSS, data).
		// Surfaces show_on_homepage=1 rows first, then fills with other active
		// products, so the grid never empties as long as >=1 product is active.
		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$data['products'] = array();

		$limit = 8;

		$query = $this->db->query("SELECT p.product_id FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY p.product_id ORDER BY p.show_on_homepage DESC, p.sort_order ASC, p.date_added DESC LIMIT " . (int)$limit);

		$thumb_w = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width') ?: 228;
		$thumb_h = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height') ?: 200;

		// Batch the product + custom-tag fetches (was N+1: one getProduct() and one
		// getProductCustomTags() per product = 16 queries; now 2 queries total).
		$pids = array();
		foreach ($query->rows as $row) {
			$pids[] = (int)$row['product_id'];
		}
		$products_map = $this->model_catalog_product->getProductsByIds($pids);
		$tags_map = $this->model_catalog_product->getProductsCustomTags($pids);

		foreach ($query->rows as $row) {
			$pid = (int)$row['product_id'];

			if (isset($products_map[$pid])) {
				$href = $this->url->link('product/product', 'product_id=' . $pid);
				$ct = isset($tags_map[$pid]) ? $tags_map[$pid] : array();
				$data['products'][] = $this->model_catalog_product->handleSingleProduct($products_map[$pid], $thumb_w, $thumb_h, $href, $ct);
			}
		}

		// ----- Activity / promo block (configurable via 前端内容 > 活动设置) -----
		// Stored under code='config' store 0, so it rides the startup config cache
		// (no extra query). Hidden entirely when activity_enabled is off.
		$data['activity'] = array(
			'enabled'      => (bool)$this->config->get('activity_enabled'),
			'tag'          => $this->config->get('activity_tag'),
			'title'        => $this->config->get('activity_title'),
			'subtitle'     => $this->config->get('activity_subtitle'),
			'badge'        => $this->config->get('activity_badge'),
			'cta_label'    => $this->config->get('activity_cta_label'),
			'cta_url'      => $this->config->get('activity_cta_url'),
			'bg_color'     => $this->config->get('activity_bg_color') ?: '#0F172A',
			'text_color'   => $this->config->get('activity_text_color') ?: '#F8FAFC',
			'accent_color' => $this->config->get('activity_accent_color') ?: '#10B981',
		);

		$data['column_left']    = $this->load->controller('common/column_left');
		$data['column_right']   = $this->load->controller('common/column_right');
		$data['content_top']    = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer']         = $this->load->controller('common/footer');
		$data['header']         = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('common/home', $data));
	}
}
