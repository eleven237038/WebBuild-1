<?php
class ControllerCatalogProduct extends Controller {
	private $error = array();

	/**
	 * 表单详情页仅保留中文输入 (zh-cn); 保存前把已提交的中文值镜像到所有其他启用语言行,
	 * 避免 editProduct 的 DELETE+重插 丢失 en-gb 等行 (否则非中文前台商品名为空),
	 * 同时保证 addProduct 为每个语言都写入一行。调用时机: validateForm 通过后、add/editProduct 之前。
	 */
	protected function mirrorChineseToAllLanguages() {
		if (empty($this->request->post['product_description'])) {
			return;
		}
		$this->load->model('localisation/language');
		$languages = $this->model_localisation_language->getLanguages();

		// 表单只渲染中文, 故 POST 通常只有一行; 取第一个提交行作为镜像源。
		$src = reset($this->request->post['product_description']);
		if (!is_array($src) || !$src) {
			return;
		}

		foreach ($languages as $lang) {
			$lid = (int)$lang['language_id'];
			// 仅补齐缺失的语言行, 不覆盖用户已显式提交的 (防御性, 当前表单不会提交多语言)。
			if (!isset($this->request->post['product_description'][$lid])) {
				$this->request->post['product_description'][$lid] = $src;
			}
		}
	}

	public function index() {
		$this->load->language('catalog/product');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/product');

		$this->getList();
	}

	public function add() {
		$this->load->language('catalog/product');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/product');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			// Defensive defaults for removed UI tabs
		if (!isset($this->request->post['product_seo_url'])) { $this->request->post['product_seo_url'] = array(); }
		if (!isset($this->request->post['product_option'])) { $this->request->post['product_option'] = array(); }
		if (!isset($this->request->post['product_discount'])) { $this->request->post['product_discount'] = array(); }
		if (!isset($this->request->post['product_special'])) { $this->request->post['product_special'] = array(); }
		if (!isset($this->request->post['product_reward'])) { $this->request->post['product_reward'] = array(); }
		if (!isset($this->request->post['product_download'])) { $this->request->post['product_download'] = array(); }
		if (!isset($this->request->post['product_filter'])) { $this->request->post['product_filter'] = array(); }
		if (!isset($this->request->post['product_layout'])) { $this->request->post['product_layout'] = array(); }
		$this->mirrorChineseToAllLanguages();
		$this->model_catalog_product->addProduct($this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

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

			$this->response->redirect($this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('catalog/product');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/product');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			// Defensive defaults for removed UI tabs
		if (!isset($this->request->post['product_seo_url'])) { $this->request->post['product_seo_url'] = array(); }
		if (!isset($this->request->post['product_option'])) { $this->request->post['product_option'] = array(); }
		if (!isset($this->request->post['product_discount'])) { $this->request->post['product_discount'] = array(); }
		if (!isset($this->request->post['product_special'])) { $this->request->post['product_special'] = array(); }
		if (!isset($this->request->post['product_reward'])) { $this->request->post['product_reward'] = array(); }
		if (!isset($this->request->post['product_download'])) { $this->request->post['product_download'] = array(); }
		if (!isset($this->request->post['product_filter'])) { $this->request->post['product_filter'] = array(); }
		if (!isset($this->request->post['product_layout'])) { $this->request->post['product_layout'] = array(); }
		$this->mirrorChineseToAllLanguages();
		// Preserve scalar system columns absent from POST (form is custom_tag-driven; fields without a custom_tag
		// would otherwise be wiped to 0/'' by editProduct's whitelist SET). status is the critical one (auto-delist bug).
		$existing = $this->model_catalog_product->getProduct($this->request->get['product_id']);
		if ($existing) {
			$preserve = array('model','sku','upc','ean','jan','isbn','mpn','location','quantity','minimum','subtract','stock_status_id','date_available','manufacturer_id','shipping','price','points','weight','weight_class_id','length','width','height','length_class_id','status','show_on_homepage','tax_class_id','sort_order');
			foreach ($preserve as $pf) {
				if (!isset($this->request->post[$pf]) && isset($existing[$pf])) {
					$this->request->post[$pf] = $existing[$pf];
				}
			}
		}
		$this->model_catalog_product->editProduct($this->request->get['product_id'], $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

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

			$this->response->redirect($this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('catalog/product');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/product');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $product_id) {
				$this->model_catalog_product->deleteProduct($product_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

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

			$this->response->redirect($this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url));
		}

		$this->getList();
	}

	public function copy() {
		$this->load->language('catalog/product');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/product');

		if (isset($this->request->post['selected']) && $this->validateCopy()) {
			foreach ($this->request->post['selected'] as $product_id) {
				$this->model_catalog_product->copyProduct($product_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

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

			$this->response->redirect($this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url));
		}

		$this->getList();
	}

	protected function getList() {
		$this->document->addScript('view/javascript/jquery/switch/bootstrap-switch.min.js');
		$this->document->addScript('view/javascript/jquery/jquery-ui/jquery-ui.min.js');
		$this->document->addStyle('view/javascript/jquery/jquery-ui/jquery-ui.min.css');

		// ── 自定义字段筛选 (filter_cf): session 持久化, cf_apply 时同步, 全新进入则清空 ──
		$fresh_nav = !isset($this->request->get['sort'])
			&& !isset($this->request->get['order'])
			&& !isset($this->request->get['page'])
			&& !isset($this->request->get['cf_apply']);

		if (isset($this->request->get['cf_apply'])) {
			if (isset($this->request->get['filter_cf']) && is_array($this->request->get['filter_cf'])) {
				$this->session->data['product_filter_cf'] = $this->request->get['filter_cf'];
			} else {
				unset($this->session->data['product_filter_cf']);
			}
		} elseif ($fresh_nav) {
			unset($this->session->data['product_filter_cf']);
		}

		$filter_cf = array();
		if (!empty($this->session->data['product_filter_cf']) && is_array($this->session->data['product_filter_cf'])) {
			foreach ($this->session->data['product_filter_cf'] as $tid => $val) {
				$tid = (int)$tid;
				if ($tid > 0 && (string)$val !== '') {
					$filter_cf[$tid] = (string)$val;
				}
			}
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'pd.name';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

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

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url)
		);

		$data['add'] = $this->url->link('catalog/product/add', 'user_token=' . $this->session->data['user_token'] . $url);
		$data['copy'] = $this->url->link('catalog/product/copy', 'user_token=' . $this->session->data['user_token'] . $url);
		$data['delete'] = $this->url->link('catalog/product/delete', 'user_token=' . $this->session->data['user_token'] . $url);

		$this->load->model('catalog/category');
		$data['categories'] = array();

		$filter_data = array(
			'sort'  => 'name',
			'order' => 'ASC',
		);

		$category_total = $this->model_catalog_category->getTotalCategories();

		$results = $this->model_catalog_category->getCategories($filter_data);

		foreach ($results as $result) {
			$data['categories'][] = array(
				'category_id' => $result['category_id'],
				'name'        => $result['name'],
			);
		}

		// 加载自定义字段定义, 构建 filter_cf 模型输入与模板行
		$this->load->model('catalog/custom_tag');
		$all_cf_tags = $this->model_catalog_custom_tag->getTags();
		$cf_tag_map = array();
		$cf_tags_for_dropdown = array();
		foreach ($all_cf_tags as $t) {
			if (empty($t['status'])) {
				continue;
			}
			$cf_tag_map[(int)$t['tag_id']] = $t;
			$cf_tags_for_dropdown[] = array(
				'tag_id'        => (int)$t['tag_id'],
				'label'         => !empty($t['display_label']) ? $t['display_label'] : $t['name'],
				'system_column' => $t['system_column'],
			);
		}

		// 可配置列表列: show_in_list=1 的字段 (排除已在表格硬编码的 name/model/price/quantity/status)
		$hardcoded_cols = array('name', 'model', 'price', 'quantity', 'status');
		$list_tags = array();
		foreach ($cf_tag_map as $tid => $t) {
			if (empty($t['show_in_list']) || empty($t['status'])) { continue; }
			if (in_array($t['system_column'], $hardcoded_cols, true)) { continue; }
			$list_tags[$tid] = $t;
		}
		$data['list_columns'] = array();
		foreach ($list_tags as $tid => $t) {
			$data['list_columns'][] = array(
				'tag_id'        => $tid,
				'system_column' => $t['system_column'],
				'name'          => !empty($t['display_label']) ? $t['display_label'] : $t['name'],
			);
		}

		$filter_cf_model = array();
		$filter_cf_rows  = array();
		foreach ($filter_cf as $tid => $val) {
			if (!isset($cf_tag_map[$tid])) {
				continue;
			}
			$t = $cf_tag_map[$tid];
			$filter_cf_model[] = array(
				'tag_id'        => $tid,
				'system_column' => $t['system_column'],
				'field_type'    => $t['field_type'],
				'value'         => $val,
			);
			$filter_cf_rows[] = array(
				'tag_id'  => $tid,
				'label'   => !empty($t['display_label']) ? $t['display_label'] : $t['name'],
				'value'   => $val,
				'options' => $this->model_catalog_product->getCustomTagValues($t),
			);
		}

		$data['products'] = array();

		$filter_data = array(
			'filter_cf'       => $filter_cf_model,
			'sort'            => $sort,
			'order'           => $order,
			'start'           => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'           => $this->config->get('config_limit_admin')
		);

		$this->load->model('tool/image');

		$product_total = $this->model_catalog_product->getTotalProducts($filter_data);

		$results = $this->model_catalog_product->getProducts($filter_data);

		// 批量取 EAV 列表列的值; 预载 stock_status 名称映射 (system_column=stock_status_id 时用)
		$list_eav_tag_ids = array();
		foreach ($list_tags as $tid => $t) {
			if ($t['system_column'] === '') { $list_eav_tag_ids[] = $tid; }
		}
		$cf_values_map = array();
		if ($results && $list_eav_tag_ids) {
			$_pids = array();
			foreach ($results as $r) { $_pids[] = (int)$r['product_id']; }
			$cf_values_map = $this->model_catalog_product->getProductsTagValues($_pids, $list_eav_tag_ids);
		}
		$stock_status_map = array();
		$need_stock = false;
		foreach ($list_tags as $t) { if ($t['system_column'] == 'stock_status_id') { $need_stock = true; break; } }
		if ($need_stock) {
			$this->load->model('localisation/stock_status');
			foreach ($this->model_localisation_stock_status->getStockStatuses() as $ss) {
				$stock_status_map[(int)$ss['stock_status_id']] = $ss['name'];
			}
		}

		foreach ($results as $result) {
			if (is_file(DIR_IMAGE . $result['image'])) {
				$image = $this->model_tool_image->resize($result['image'], 40, 40);
			} else {
				$image = $this->model_tool_image->resize('no_image.png', 40, 40);
			}

			$special = false;

			$product_specials = $this->model_catalog_product->getProductSpecials($result['product_id']);

			foreach ($product_specials  as $product_special) {
				if (($product_special['date_start'] == '0000-00-00' || strtotime($product_special['date_start']) < time()) && ($product_special['date_end'] == '0000-00-00' || strtotime($product_special['date_end']) > time())) {
					$special = $this->currency->format($product_special['price'], $this->config->get('config_currency'));

					break;
				}
			}

			$cf_cols = array();
			foreach ($list_tags as $tid => $t) {
				$col = $t['system_column'];
				if ($col === '') {
					$cf_cols[$tid] = isset($cf_values_map[(int)$result['product_id']][$tid]) ? $cf_values_map[(int)$result['product_id']][$tid] : '';
				} elseif ($col == 'stock_status_id') {
					$cf_cols[$tid] = isset($stock_status_map[(int)$result['stock_status_id']]) ? $stock_status_map[(int)$result['stock_status_id']] : '';
				} elseif (in_array($col, array('subtract', 'shipping'), true)) {
					$cf_cols[$tid] = $result[$col] ? '是' : '否';
				} else {
					$cf_cols[$tid] = isset($result[$col]) ? $result[$col] : '';
				}
			}

			$data['products'][] = array(
				'product_id' => $result['product_id'],
				'image'      => $image,
				'name'       => $result['name'],
				'model'      => $result['model'],
				'price'      => $this->currency->format($result['price'], $this->config->get('config_currency')),
				'special'    => $special,
				'quantity'   => $result['quantity'],
				'status'     => $result['status'],
				'view'       => $this->front_url->link('product/product', "product_id={$result['product_id']}"),
				'edit'       => $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'] . $url),
				'cf_cols'    => $cf_cols
			);
		}

		$data['user_token'] = $this->session->data['user_token'];

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

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_id'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.product_id' . $url);
		$data['sort_name'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . '&sort=pd.name' . $url);
		$data['sort_model'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.model' . $url);
		$data['sort_price'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.price' . $url);
		$data['sort_quantity'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.quantity' . $url);
		$data['sort_status'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.status' . $url);
		$data['sort_order'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.sort_order' . $url);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $product_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}');

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($product_total - $this->config->get('config_limit_admin'))) ? $product_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $product_total, ceil($product_total / $this->config->get('config_limit_admin')));

		$data['cf_tags']   = $cf_tags_for_dropdown;
		$data['filter_cf'] = $filter_cf_rows;

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/product_list', $data));
	}

	protected function getForm() {
		$this->document->addStyle('view/javascript/codemirror/lib/codemirror.css');
		$this->document->addStyle('view/javascript/codemirror/theme/monokai.css');
		$this->document->addStyle('view/javascript/summernote/summernote.css');

		$this->document->addScript('view/javascript/codemirror/lib/codemirror.js');
		$this->document->addScript('view/javascript/codemirror/lib/xml.js');
		$this->document->addScript('view/javascript/codemirror/lib/formatting.js');
		$this->document->addScript('view/javascript/summernote/summernote.js');
		$this->document->addScript('view/javascript/summernote/summernote-image-attributes.js');
		$this->document->addScript('view/javascript/summernote/opencart.js');

		$data['text_form'] = !isset($this->request->get['product_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = array();
		}

		if (isset($this->error['meta_title'])) {
			$data['error_meta_title'] = $this->error['meta_title'];
		} else {
			$data['error_meta_title'] = array();
		}

		if (isset($this->error['model'])) {
			$data['error_model'] = $this->error['model'];
		} else {
			$data['error_model'] = '';
		}

		if (isset($this->error['keyword'])) {
			$data['error_keyword'] = $this->error['keyword'];
		} else {
			$data['error_keyword'] = '';
		}

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

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url)
		);

		if (!isset($this->request->get['product_id'])) {
			$data['action'] = $this->url->link('catalog/product/add', 'user_token=' . $this->session->data['user_token'] . $url);
		} else {
			$data['action'] = $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $this->request->get['product_id'] . $url);
		}

		$data['cancel'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url);

		if (isset($this->request->get['product_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$product_info = $this->model_catalog_product->getProduct($this->request->get['product_id']);
		}

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->request->get['product_id'])) {
			$data['product_id'] = $this->request->get['product_id'];
		} else {
			$data['product_id'] = 0;
		}

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		if (isset($this->request->post['product_description'])) {
			$data['product_description'] = $this->request->post['product_description'];
		} elseif (isset($this->request->get['product_id'])) {
			$data['product_description'] = $this->model_catalog_product->getProductDescriptions($this->request->get['product_id']);
		} else {
			$data['product_description'] = array();
		}

		if (isset($this->request->post['model'])) {
			$data['model'] = $this->request->post['model'];
		} elseif (!empty($product_info)) {
			$data['model'] = $product_info['model'];
		} else {
			$data['model'] = '';
		}

		if (isset($this->request->post['sku'])) {
			$data['sku'] = $this->request->post['sku'];
		} elseif (!empty($product_info)) {
			$data['sku'] = $product_info['sku'];
		} else {
			$data['sku'] = '';
		}

		if (isset($this->request->post['upc'])) {
			$data['upc'] = $this->request->post['upc'];
		} elseif (!empty($product_info)) {
			$data['upc'] = $product_info['upc'];
		} else {
			$data['upc'] = '';
		}

		if (isset($this->request->post['ean'])) {
			$data['ean'] = $this->request->post['ean'];
		} elseif (!empty($product_info)) {
			$data['ean'] = $product_info['ean'];
		} else {
			$data['ean'] = '';
		}

		if (isset($this->request->post['jan'])) {
			$data['jan'] = $this->request->post['jan'];
		} elseif (!empty($product_info)) {
			$data['jan'] = $product_info['jan'];
		} else {
			$data['jan'] = '';
		}

		if (isset($this->request->post['isbn'])) {
			$data['isbn'] = $this->request->post['isbn'];
		} elseif (!empty($product_info)) {
			$data['isbn'] = $product_info['isbn'];
		} else {
			$data['isbn'] = '';
		}

		if (isset($this->request->post['mpn'])) {
			$data['mpn'] = $this->request->post['mpn'];
		} elseif (!empty($product_info)) {
			$data['mpn'] = $product_info['mpn'];
		} else {
			$data['mpn'] = '';
		}

		if (isset($this->request->post['location'])) {
			$data['location'] = $this->request->post['location'];
		} elseif (!empty($product_info)) {
			$data['location'] = $product_info['location'];
		} else {
			$data['location'] = '';
		}

		$this->load->model('setting/store');

		$data['stores'] = array();

		$data['stores'][] = array(
			'store_id' => 0,
			'name'     => $this->language->get('text_default')
		);

		$stores = $this->model_setting_store->getStores();

		foreach ($stores as $store) {
			$data['stores'][] = array(
				'store_id' => $store['store_id'],
				'name'     => $store['name']
			);
		}

		if (isset($this->request->post['product_store'])) {
			$data['product_store'] = $this->request->post['product_store'];
		} elseif (isset($this->request->get['product_id'])) {
			$data['product_store'] = $this->model_catalog_product->getProductStores($this->request->get['product_id']);
		} else {
			$data['product_store'] = array(0);
		}

		if (isset($this->request->post['shipping'])) {
			$data['shipping'] = $this->request->post['shipping'];
		} elseif (!empty($product_info)) {
			$data['shipping'] = $product_info['shipping'];
		} else {
			$data['shipping'] = 1;
		}

		if (isset($this->request->post['price'])) {
			$data['price'] = $this->request->post['price'];
		} elseif (!empty($product_info)) {
			$data['price'] = $product_info['price'];
		} else {
			$data['price'] = '';
		}

		$this->load->model('localisation/tax_class');

		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

		if (isset($this->request->post['tax_class_id'])) {
			$data['tax_class_id'] = $this->request->post['tax_class_id'];
		} elseif (!empty($product_info)) {
			$data['tax_class_id'] = $product_info['tax_class_id'];
		} else {
			$data['tax_class_id'] = 0;
		}

		if (isset($this->request->post['date_available'])) {
			$data['date_available'] = $this->request->post['date_available'];
		} elseif (!empty($product_info)) {
			$data['date_available'] = ($product_info['date_available'] != '0000-00-00') ? $product_info['date_available'] : '';
		} else {
			$data['date_available'] = date('Y-m-d');
		}

		if (isset($this->request->post['quantity'])) {
			$data['quantity'] = $this->request->post['quantity'];
		} elseif (!empty($product_info)) {
			$data['quantity'] = $product_info['quantity'];
		} else {
			$data['quantity'] = 1;
		}

		if (isset($this->request->post['minimum'])) {
			$data['minimum'] = $this->request->post['minimum'];
		} elseif (!empty($product_info)) {
			$data['minimum'] = $product_info['minimum'];
		} else {
			$data['minimum'] = 1;
		}

		if (isset($this->request->post['subtract'])) {
			$data['subtract'] = $this->request->post['subtract'];
		} elseif (!empty($product_info)) {
			$data['subtract'] = $product_info['subtract'];
		} else {
			$data['subtract'] = 1;
		}

		if (isset($this->request->post['sort_order'])) {
			$data['sort_order'] = $this->request->post['sort_order'];
		} elseif (!empty($product_info)) {
			$data['sort_order'] = $product_info['sort_order'];
		} else {
			$data['sort_order'] = 1;
		}

		$this->load->model('localisation/stock_status');

		$data['stock_statuses'] = $this->model_localisation_stock_status->getStockStatuses();

		if (isset($this->request->post['stock_status_id'])) {
			$data['stock_status_id'] = $this->request->post['stock_status_id'];
		} elseif (!empty($product_info)) {
			$data['stock_status_id'] = $product_info['stock_status_id'];
		} else {
			$data['stock_status_id'] = 0;
		}

		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($product_info)) {
			$data['status'] = $product_info['status'];
		} else {
			$data['status'] = true;
		}
		// Show on Homepage toggle
		if (isset($this->request->post['show_on_homepage'])) {
			$data['show_on_homepage'] = $this->request->post['show_on_homepage'];
		} elseif (!empty($product_info)) {
			$data['show_on_homepage'] = $product_info['show_on_homepage'];
		} else {
			$data['show_on_homepage'] = 0;
		}

		// Custom Tags — hierarchical tree + core field config
		$this->load->model('catalog/custom_tag');
		$data['tag_tree'] = $this->model_catalog_custom_tag->getCustomTagTree();
		// Build core_fields lookup: name => display_label for system_column records
		$all_tags = $this->model_catalog_custom_tag->getTags();
		// Core field labels for General tab + system fields for dynamic Data tab + custom (non-core) fields
		$data['core_fields']   = array();
		$data['system_fields'] = array();
		$data['custom_fields'] = array();
		// 预序重排: 顶级(parent_id=0)按 sort_order, 每个结构体(struct)后紧跟其子字段
		// (parent_id=该struct), 保证结构体与子字段在表单中连续, 便于 fieldset 分组渲染。
		$top_level   = array();
		$children_of = array();   // parent_id => [tag, ...]
		foreach ($all_tags as $t) {
			$pid = (int)$t['parent_id'];
			if ($pid === 0) {
				$top_level[] = $t;
			} else {
				if (!isset($children_of[$pid])) { $children_of[$pid] = array(); }
				$children_of[$pid][] = $t;
			}
		}
		$ordered = array();
		foreach ($top_level as $t) {
			$ordered[] = $t;
			$tid = (int)$t['tag_id'];
			if (!empty($children_of[$tid])) {
				foreach ($children_of[$tid] as $child) {
					$ordered[] = $child;
				}
			}
		}
		$data['form_fields'] = array();
		foreach ($ordered as $t) {
			if (!empty($t['system_column'])) {
				// Attach picker options for known system select columns
				if ($t['system_column'] == 'stock_status_id' && !empty($data['stock_statuses'])) {
					$t['options'] = array();
					foreach ($data['stock_statuses'] as $ss) {
						$t['options'][] = array('value' => $ss['stock_status_id'], 'text' => $ss['name']);
					}
				}
				// 状态: 启用/禁用 单选 (修复编辑商品后自动下架 bug - status 必须始终提交)
				if ($t['system_column'] == 'status') {
					$t['options'] = array(
						array('value' => 1, 'text' => $this->language->get('text_enabled')),
						array('value' => 0, 'text' => $this->language->get('text_disabled'))
					);
				}
				$data['system_fields'][] = $t;
				$data['core_fields'][$t['name']] = !empty($t['display_label']) ? $t['display_label'] : $t['name'];
			} else {
				$data['custom_fields'][] = $t;
			}
			// 非 system_column 的 select/radio: 从 DB 载入选项; number/text/textarea/image_multi: 载入 config
			if (empty($t['system_column']) && in_array($t['tag_type'], array('select', 'radio'), true)) {
				$t['options'] = $this->model_catalog_custom_tag->getTagOptions((int)$t['tag_id']);
			}
			if (empty($t['system_column']) && in_array($t['tag_type'], array('number', 'text', 'textarea', 'image_multi'), true)) {
				$t['config'] = $this->model_catalog_custom_tag->getTagConfig((int)$t['tag_id']);
			}
			// 统一字段列表 (预序: 结构体紧跟子字段), 供表单统一渲染
			$data['form_fields'][] = $t;
		}
		if (isset($this->request->post['product_custom_tag'])) {
			$data['product_custom_tag'] = $this->request->post['product_custom_tag'];
		} elseif (!empty($product_info)) {
			$data['product_custom_tag'] = $this->model_catalog_product->getProductCustomTags($product_info['product_id']);
		} else {
			$data['product_custom_tag'] = array();
		}

		if (isset($this->request->post['weight'])) {
			$data['weight'] = $this->request->post['weight'];
		} elseif (!empty($product_info)) {
			$data['weight'] = $product_info['weight'];
		} else {
			$data['weight'] = '';
		}

		$this->load->model('localisation/weight_class');

		$data['weight_classes'] = $this->model_localisation_weight_class->getWeightClasses();

		if (isset($this->request->post['weight_class_id'])) {
			$data['weight_class_id'] = $this->request->post['weight_class_id'];
		} elseif (!empty($product_info)) {
			$data['weight_class_id'] = $product_info['weight_class_id'];
		} else {
			$data['weight_class_id'] = $this->config->get('config_weight_class_id');
		}

		if (isset($this->request->post['length'])) {
			$data['length'] = $this->request->post['length'];
		} elseif (!empty($product_info)) {
			$data['length'] = $product_info['length'];
		} else {
			$data['length'] = '';
		}

		if (isset($this->request->post['width'])) {
			$data['width'] = $this->request->post['width'];
		} elseif (!empty($product_info)) {
			$data['width'] = $product_info['width'];
		} else {
			$data['width'] = '';
		}

		if (isset($this->request->post['height'])) {
			$data['height'] = $this->request->post['height'];
		} elseif (!empty($product_info)) {
			$data['height'] = $product_info['height'];
		} else {
			$data['height'] = '';
		}

		$this->load->model('localisation/length_class');

		$data['length_classes'] = $this->model_localisation_length_class->getLengthClasses();

		if (isset($this->request->post['length_class_id'])) {
			$data['length_class_id'] = $this->request->post['length_class_id'];
		} elseif (!empty($product_info)) {
			$data['length_class_id'] = $product_info['length_class_id'];
		} else {
			$data['length_class_id'] = $this->config->get('config_length_class_id');
		}

		$this->load->model('catalog/manufacturer');

		if (isset($this->request->post['manufacturer_id'])) {
			$data['manufacturer_id'] = $this->request->post['manufacturer_id'];
		} elseif (!empty($product_info)) {
			$data['manufacturer_id'] = $product_info['manufacturer_id'];
		} else {
			$data['manufacturer_id'] = 0;
		}

		if (isset($this->request->post['manufacturer'])) {
			$data['manufacturer'] = $this->request->post['manufacturer'];
		} elseif (!empty($product_info)) {
			$manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($product_info['manufacturer_id']);

			if ($manufacturer_info) {
				$data['manufacturer'] = $manufacturer_info['name'];
			} else {
				$data['manufacturer'] = '';
			}
		} else {
			$data['manufacturer'] = '';
		}

		// Categories
		$this->load->model('catalog/category');

		if (isset($this->request->post['product_category'])) {
			$categories = $this->request->post['product_category'];
		} elseif (isset($this->request->get['product_id'])) {
			$categories = $this->model_catalog_product->getProductCategories($this->request->get['product_id']);
		} else {
			$categories = array();
		}

		$data['product_categories'] = array();

		foreach ($categories as $category_id) {
			$category_info = $this->model_catalog_category->getCategory($category_id);

			if ($category_info) {
				$data['product_categories'][] = array(
					'category_id' => $category_info['category_id'],
					'name'        => ($category_info['path']) ? $category_info['path'] . ' &gt; ' . $category_info['name'] : $category_info['name']
				);
			}
		}

		// Filters
		$this->load->model('catalog/filter');

		if (isset($this->request->post['product_filter'])) {
			$filters = $this->request->post['product_filter'];
		} elseif (isset($this->request->get['product_id'])) {
			$filters = $this->model_catalog_product->getProductFilters($this->request->get['product_id']);
		} else {
			$filters = array();
		}

		$data['product_filters'] = array();

		foreach ($filters as $filter_id) {
			$filter_info = $this->model_catalog_filter->getFilter($filter_id);

			if ($filter_info) {
				$data['product_filters'][] = array(
					'filter_id' => $filter_info['filter_id'],
					'name'      => $filter_info['group'] . ' &gt; ' . $filter_info['name']
				);
			}
		}

        // Options
        $this->load->model('catalog/option');

        if (isset($this->request->post['product_option'])) {
            $product_options = $this->request->post['product_option'];
        } elseif (isset($this->request->get['product_id'])) {
            $product_options = $this->model_catalog_product->getProductOptions($this->request->get['product_id']);
        } else {
            $product_options = array();
        }

        $data['product_options'] = array();

        foreach ($product_options as $product_option) {
            $product_option_value_data = array();

            if (isset($product_option['product_option_value'])) {
                foreach ($product_option['product_option_value'] as $product_option_value) {
                    $product_option_value_data[] = array(
                        'product_option_value_id' => $product_option_value['product_option_value_id'],
                        'option_value_id'         => $product_option_value['option_value_id'],
                        'quantity'                => $product_option_value['quantity'],
                        'subtract'                => $product_option_value['subtract'],
                        'price'                   => $product_option_value['price'],
                        'price_prefix'            => $product_option_value['price_prefix'],
                        'points'                  => $product_option_value['points'],
                        'points_prefix'           => $product_option_value['points_prefix'],
                        'weight'                  => $product_option_value['weight'],
                        'weight_prefix'           => $product_option_value['weight_prefix']
                    );
                }
            }

            $data['product_options'][] = array(
                'product_option_id'    => $product_option['product_option_id'],
                'product_option_value' => $product_option_value_data,
                'option_id'            => $product_option['option_id'],
                'name'                 => $product_option['name'],
                'type'                 => $product_option['type'],
                'value'                => isset($product_option['value']) ? $product_option['value'] : '',
                'required'             => $product_option['required']
            );
        }

        $data['option_values'] = array();

        foreach ($data['product_options'] as $product_option) {
            if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
                if (!isset($data['option_values'][$product_option['option_id']])) {
                    $data['option_values'][$product_option['option_id']] = $this->model_catalog_option->getOptionValues($product_option['option_id']);
                }
            }
        }

		$this->load->model('customer/customer_group');

		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		if (isset($this->request->post['product_discount'])) {
			$product_discounts = $this->request->post['product_discount'];
		} elseif (isset($this->request->get['product_id'])) {
			$product_discounts = $this->model_catalog_product->getProductDiscounts($this->request->get['product_id']);
		} else {
			$product_discounts = array();
		}

		$data['product_discounts'] = array();

		foreach ($product_discounts as $product_discount) {
			$data['product_discounts'][] = array(
				'customer_group_id' => $product_discount['customer_group_id'],
				'quantity'          => $product_discount['quantity'],
				'priority'          => $product_discount['priority'],
				'price'             => $product_discount['price'],
				'date_start'        => ($product_discount['date_start'] != '0000-00-00') ? $product_discount['date_start'] : '',
				'date_end'          => ($product_discount['date_end'] != '0000-00-00') ? $product_discount['date_end'] : ''
			);
		}

		if (isset($this->request->post['product_special'])) {
			$product_specials = $this->request->post['product_special'];
		} elseif (isset($this->request->get['product_id'])) {
			$product_specials = $this->model_catalog_product->getProductSpecials($this->request->get['product_id']);
		} else {
			$product_specials = array();
		}

		$data['product_specials'] = array();

		foreach ($product_specials as $product_special) {
			$data['product_specials'][] = array(
				'customer_group_id' => $product_special['customer_group_id'],
				'priority'          => $product_special['priority'],
				'price'             => $product_special['price'],
				'date_start'        => ($product_special['date_start'] != '0000-00-00') ? $product_special['date_start'] : '',
				'date_end'          => ($product_special['date_end'] != '0000-00-00') ? $product_special['date_end'] :  ''
			);
		}

		// Image
		if (isset($this->request->post['image'])) {
			$data['image'] = $this->request->post['image'];
		} elseif (!empty($product_info)) {
			$data['image'] = $product_info['image'];
		} else {
			$data['image'] = '';
		}

		$this->load->model('tool/image');

		if (isset($this->request->post['image']) && is_file(DIR_IMAGE . $this->request->post['image'])) {
			$data['thumb'] = $this->model_tool_image->resize($this->request->post['image'], 100, 100);
		} elseif (!empty($product_info) && is_file(DIR_IMAGE . $product_info['image'])) {
			$data['thumb'] = $this->model_tool_image->resize($product_info['image'], 100, 100);
		} else {
			$data['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		}

		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

		// 图片字段缩略图显示用的原始图 URL 前缀 (单张/多张图片 EAV 字段)
		$data['image_base'] = HTTP_CATALOG . 'image/';

		// Images
		if (isset($this->request->post['product_image'])) {
			$product_images = $this->request->post['product_image'];
		} elseif (isset($this->request->get['product_id'])) {
			$product_images = $this->model_catalog_product->getProductImages($this->request->get['product_id']);
		} else {
			$product_images = array();
		}

		$data['product_images'] = array();

		foreach ($product_images as $product_image) {
			if (is_file(DIR_IMAGE . $product_image['image'])) {
				$image = $product_image['image'];
				$thumb = $product_image['image'];
			} else {
				$image = '';
				$thumb = 'no_image.png';
			}

			$data['product_images'][] = array(
				'image'      => $image,
				'thumb'      => $this->model_tool_image->resize($thumb, 100, 100),
				'sort_order' => $product_image['sort_order']
			);
		}

		// Options
        if (isset($this->request->get['product_id'])) {
            $data['option_list'] = $this->url->link('catalog/product_option', 'user_token=' . $this->session->data['user_token'] . '&filter_product_id=' . $this->request->get['product_id']);
            $data['option_add'] = $this->url->link('catalog/product_option/add', 'user_token=' . $this->session->data['user_token'] . '&filter_product_id=' . $this->request->get['product_id']);
        }

		// Downloads
		$this->load->model('catalog/download');

		if (isset($this->request->post['product_download'])) {
			$product_downloads = $this->request->post['product_download'];
		} elseif (isset($this->request->get['product_id'])) {
			$product_downloads = $this->model_catalog_product->getProductDownloads($this->request->get['product_id']);
		} else {
			$product_downloads = array();
		}

		$data['product_downloads'] = array();

		foreach ($product_downloads as $download_id) {
			$download_info = $this->model_catalog_download->getDownload($download_id);

			if ($download_info) {
				$data['product_downloads'][] = array(
					'download_id' => $download_info['download_id'],
					'name'        => $download_info['name']
				);
			}
		}

		if (isset($this->request->post['product_related'])) {
			$products = $this->request->post['product_related'];
		} elseif (isset($this->request->get['product_id'])) {
			$products = $this->model_catalog_product->getProductRelated($this->request->get['product_id']);
		} else {
			$products = array();
		}

		$data['product_relateds'] = array();

		foreach ($products as $product_id) {
			$related_info = $this->model_catalog_product->getProduct($product_id);

			if ($related_info) {
				$data['product_relateds'][] = array(
					'product_id' => $related_info['product_id'],
					'name'       => $related_info['name']
				);
			}
		}

		if (isset($this->request->post['points'])) {
			$data['points'] = $this->request->post['points'];
		} elseif (!empty($product_info)) {
			$data['points'] = $product_info['points'];
		} else {
			$data['points'] = '';
		}

		if (isset($this->request->post['product_reward'])) {
			$data['product_reward'] = $this->request->post['product_reward'];
		} elseif (isset($this->request->get['product_id'])) {
			$data['product_reward'] = $this->model_catalog_product->getProductRewards($this->request->get['product_id']);
		} else {
			$data['product_reward'] = array();
		}

		if (isset($this->request->post['product_seo_url'])) {
			$data['product_seo_url'] = $this->request->post['product_seo_url'];
		} elseif (isset($this->request->get['product_id'])) {
			$data['product_seo_url'] = $this->model_catalog_product->getProductSeoUrls($this->request->get['product_id']);
		} else {
			$data['product_seo_url'] = array();
		}

		if (isset($this->request->post['product_layout'])) {
			$data['product_layout'] = $this->request->post['product_layout'];
		} elseif (isset($this->request->get['product_id'])) {
			$data['product_layout'] = $this->model_catalog_product->getProductLayouts($this->request->get['product_id']);
		} else {
			$data['product_layout'] = array();
		}

		$this->load->model('design/layout');

		$data['layouts'] = $this->model_design_layout->getLayouts();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/product_form', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'catalog/product')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		foreach ($this->request->post['product_description'] as $language_id => $value) {
			if ((utf8_strlen($value['name']) < 1) || (utf8_strlen($value['name']) > 255)) {
				$this->error['name'][$language_id] = $this->language->get('error_name');
			}

			if ((utf8_strlen($value['meta_title']) < 1) || (utf8_strlen($value['meta_title']) > 255)) {
				$this->error['meta_title'][$language_id] = $this->language->get('error_meta_title');
			}
		}

		if ($this->request->post['product_seo_url']) {
			$this->load->model('design/seo_url');

			foreach ($this->request->post['product_seo_url'] as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!empty($keyword)) {
						if (count(array_keys($language, $keyword)) > 1) {
							$this->error['keyword'][$store_id][$language_id] = $this->language->get('error_unique');
						}

						$seo_urls = $this->model_design_seo_url->getSeoUrlsByKeyword($keyword);

						foreach ($seo_urls as $seo_url) {
							if (($seo_url['store_id'] == $store_id) && (!isset($this->request->get['product_id']) || (($seo_url['query'] != 'product_id=' . $this->request->get['product_id'])))) {
								$this->error['keyword'][$store_id][$language_id] = $this->language->get('error_keyword');

								break;
							}
						}
					}
				}
			}
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'catalog/product')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	protected function validateCopy() {
		if (!$this->user->hasPermission('modify', 'catalog/product')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function autocomplete() {
		$json = array();

		if (isset($this->request->get['filter_name']) || isset($this->request->get['filter_model'])) {
			$this->load->model('catalog/product');
			$this->load->model('catalog/option');
			$this->load->model('catalog/product_option');

			if (isset($this->request->get['filter_name'])) {
				$filter_name = $this->request->get['filter_name'];
			} else {
				$filter_name = '';
			}

			if (isset($this->request->get['filter_model'])) {
				$filter_model = $this->request->get['filter_model'];
			} else {
				$filter_model = '';
			}

			if (isset($this->request->get['limit'])) {
				$limit = $this->request->get['limit'];
			} else {
				$limit = 5;
			}

			$filter_data = array(
				'filter_name'  => $filter_name,
				'filter_model' => $filter_model,
				'start'        => 0,
				'limit'        => $limit
			);

			$results = $this->model_catalog_product->getProducts($filter_data);

			foreach ($results as $result) {
				$option_data = array();

				$product_options = $this->model_catalog_product_option->getProductOptionsByProductId($result['product_id']);

				foreach ($product_options as $product_option) {
					$option_info = $this->model_catalog_option->getOption($product_option['option_id']);

					if ($option_info) {
						$product_option_value_data = array();

						foreach ($product_option['product_option_value'] as $product_option_value) {
							$option_value_info = $this->model_catalog_option->getOptionValue($product_option_value['option_value_id']);

							if ($option_value_info) {
								$product_option_value_data[] = array(
									'product_option_value_id' => $product_option_value['product_option_value_id'],
									'option_value_id'         => $product_option_value['option_value_id'],
									'name'                    => $option_value_info['name'],
									'price'                   => (float)$product_option_value['price'] ? $this->currency->format($product_option_value['price'], $this->config->get('config_currency')) : false,
									'price_prefix'            => $product_option_value['price_prefix']
								);
							}
						}

						$option_data[] = array(
							'product_option_id'    => $product_option['product_option_id'],
							'product_option_value' => $product_option_value_data,
							'option_id'            => $product_option['option_id'],
							'name'                 => $option_info['name'],
							'type'                 => $option_info['type'],
							'value'                => $product_option['value'],
							'required'             => $product_option['required']
						);
					}
				}

				$json[] = array(
					'product_id' => $result['product_id'],
					'name'       => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
					'model'      => $result['model'],
					'option'     => $option_data,
					'price'      => $result['price']
				);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function status() {
		$json = array();
		if (!$this->permission()) {
			$json['status'] = 0;
			$json['message'] = $this->language->get('error_permission');
			$json['data'] = null;

			$this->json_output($json);
			return;
		}

		if (!isset($this->request->get['product_id']) || (int)$this->request->get['product_id'] < 1) {
			$json['status'] = 0;
			$json['message'] = 'Missing: product_id';
			$json['data'] = null;

			$this->json_output($json);
			return;
		}

		if (!isset($this->request->get['status'])) {
			$json['status'] = 0;
			$json['message'] = 'Missing: status';
			$json['data'] = null;

			$this->json_output($json);
			return;
		}

		$product_id = (int)$this->request->get['product_id'];
		$status = (int)$this->request->get['status'] > 0 ? 1 : 0;

		$this->load->model('catalog/product');
		$this->model_catalog_product->editProductStatus($product_id, $status);

		$json['status'] = 1;
		$json['message'] = 'Done.';
		$json['data'] = null;

		$this->json_output($json);
	}

	/**
	 * 自定义字段筛选取值: GET tag_id -> JSON [{value,text},...] (该字段在现有商品数据中的可选值)。
	 * 供商品管理筛选面板 "添加筛选字段" 后 AJAX 填充新行的值下拉。
	 */
	public function cfValues() {
		$this->load->model('catalog/custom_tag');
		$this->load->model('catalog/product');

		$json = array();

		if (isset($this->request->get['tag_id']) && (int)$this->request->get['tag_id'] > 0) {
			$tag = $this->model_catalog_custom_tag->getTag((int)$this->request->get['tag_id']);
			if ($tag) {
				$json = $this->model_catalog_product->getCustomTagValues($tag);
			}
		}

		$this->json_output($json);
	}

	protected function permission() {
		return $this->user->hasPermission('modify', 'catalog/product');
	}
}
