<?php
class ControllerCommonHome extends Controller {
	public function index() {

		$this->document->setTitle($this->config->get('config_meta_title'));
		$this->document->setDescription($this->config->get('config_meta_description'));
		$this->document->setKeywords($this->config->get('config_meta_keyword'));

		if (isset($this->request->get['route'])) {
			$this->document->addLink($this->config->get('config_url'), 'canonical');
		}

		// ----- Dynamic Products for Homepage Service Cards -----
		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$data['products'] = array();

		// Direct query: pick ONE language row per product (first available)
		$query = $this->db->query("
			SELECT p.product_id, pd.name, pd.description, p.image
			FROM " . DB_PREFIX . "product p
			JOIN " . DB_PREFIX . "product_description pd
				ON (p.product_id = pd.product_id)
			WHERE p.status = '1'
				  AND p.show_on_homepage = 1
				  AND p.date_available <= NOW()
			  AND pd.description IS NOT NULL AND pd.description != ''
			
			ORDER BY p.sort_order ASC
			LIMIT 8
		");

		foreach ($query->rows as $result) {
			// Aggressively strip all HTML — strip_tags + regex fallback
			$desc = $result['description'];
			$desc = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $desc);
			$desc = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $desc);
			$desc = strip_tags($desc);
			$desc = preg_replace('/&nbsp;|&amp;|&lt;|&gt;|&quot;|&#?\w+;/', ' ', $desc);
			$desc = html_entity_decode($desc, ENT_QUOTES | ENT_HTML5, 'UTF-8');
			// Normalize whitespace
			$desc = preg_replace('/\s+/', ' ', trim($desc));

			// Truncate to ~150 chars without cutting words
			if (mb_strlen($desc) > 150) {
				$desc = mb_substr($desc, 0, 147) . '...';
			}

			$data['products'][] = array(
				'product_id'  => $result['product_id'],
				'name'        => $result['name'],
				'description' => $desc,
				'image'       => $result['image']
					? $this->model_tool_image->resize($result['image'], 80, 80)
					: '',
				'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id']),
			);
		}

		$data['column_left']    = $this->load->controller('common/column_left');
		$data['column_right']   = $this->load->controller('common/column_right');
		$data['content_top']    = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer']         = $this->load->controller('common/footer');
		$data['header']         = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('common/home', $data));
	}
}
