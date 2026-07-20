<?php
class ControllerCatalogCustomTag extends Controller {
	private $error = array();

	public function index() {
		if (!$this->user->hasPermission('access', 'catalog/custom_tag')) {
			$this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token']));
			return;
		}
		$this->load->language('catalog/category');
		$this->document->setTitle('商品类型管理');
		$this->load->model('catalog/custom_tag');
		$this->getList();
	}

	protected function getList() {
		// jQuery UI sortable drives the WordPress-style flat drag tree.
		$this->document->addScript('view/javascript/jquery/jquery-ui/jquery-ui.min.js');

		// 商品类型列表 + 当前选中类型 (GET 优先, 否则取排序最前的类型)
		$product_types = $this->model_catalog_custom_tag->getProductTypes();
		$product_type_id = isset($this->request->get['product_type_id']) ? (int)$this->request->get['product_type_id'] : 0;
		if (!$product_type_id && $product_types) {
			$product_type_id = (int)$product_types[0]['product_type_id'];
		}
		// 传入的类型不存在则回退到第一个
		if ($product_type_id && !$this->model_catalog_custom_tag->getProductType($product_type_id)) {
			$product_type_id = $product_types ? (int)$product_types[0]['product_type_id'] : 0;
		}

		// 为每个类型附字段计数
		foreach ($product_types as &$pt) {
			$pt['field_count'] = count($this->model_catalog_custom_tag->getTagsByType((int)$pt['product_type_id']));
		}
		unset($pt);

		$data['product_types']   = $product_types;
		$data['product_type_id'] = $product_type_id;
		$data['tag_flat']        = $product_type_id ? $this->model_catalog_custom_tag->getCustomTagFlatByType($product_type_id) : array();
		$data['user_token']      = $this->session->data['user_token'];

		if (isset($this->error['warning'])) { $data['error_warning'] = $this->error['warning']; } else { $data['error_warning'] = ''; }
		if (isset($this->session->data['success'])) { $data['success'] = $this->session->data['success']; unset($this->session->data['success']); } else { $data['success'] = ''; }

		$pt_query = $product_type_id ? '&product_type_id=' . $product_type_id : '';
		$data['add']         = $this->url->link('catalog/custom_tag/add', 'user_token=' . $this->session->data['user_token'] . $pt_query);
		$data['delete']      = $this->url->link('catalog/custom_tag/delete', 'user_token=' . $this->session->data['user_token']);
		$data['add_type']    = $this->url->link('catalog/custom_tag/addType', 'user_token=' . $this->session->data['user_token']);
		$data['rename_type'] = $this->url->link('catalog/custom_tag/renameType', 'user_token=' . $this->session->data['user_token']);
		$data['delete_type'] = $this->url->link('catalog/custom_tag/deleteType', 'user_token=' . $this->session->data['user_token']);
		$data['list_base']   = $this->url->link('catalog/custom_tag', 'user_token=' . $this->session->data['user_token']);
		$data['header']      = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']      = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('catalog/custom_tag_list', $data));
	}

	public function add() {
		if (!$this->user->hasPermission('modify', 'catalog/custom_tag')) {
			$this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token']));
			return;
		}
		$this->load->language('catalog/category');
		$this->load->model('catalog/custom_tag');
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->request->post['is_required'] = isset($this->request->post['is_required']) ? 1 : 0;
			$this->request->post['status']      = isset($this->request->post['status']) ? 1 : 0;
			$this->request->post['parent_id']   = (int)($this->request->post['parent_id'] ?? 0);
			$this->request->post['product_type_id'] = (int)($this->request->post['product_type_id'] ?? $this->request->get['product_type_id'] ?? 0);
			$this->model_catalog_custom_tag->addTag($this->request->post);
			$this->session->data['success'] = '字段已添加';
			// If AJAX (from drag-drop UI), return JSON; else redirect
			if (!empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
				$this->response->addHeader('Content-Type: application/json');
				$this->response->setOutput(json_encode(['success' => true]));
				return;
			}
			$pt = (int)$this->request->post['product_type_id'];
			$pt_query = $pt ? '&product_type_id=' . $pt : '';
			$this->response->redirect($this->url->link('catalog/custom_tag', 'user_token=' . $this->session->data['user_token'] . $pt_query));
		}
		$this->getForm();
	}

	public function edit() {
		if (!$this->user->hasPermission('modify', 'catalog/custom_tag')) {
			$this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token']));
			return;
		}
		$this->load->language('catalog/category');
		$this->document->setTitle('编辑字段');
		$this->load->model('catalog/custom_tag');
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->request->post['is_required'] = isset($this->request->post['is_required']) ? 1 : 0;
			$this->request->post['status']      = isset($this->request->post['status']) ? 1 : 0;
			$this->request->post['parent_id']   = (int)($this->request->post['parent_id'] ?? 0);
			$this->model_catalog_custom_tag->editTag($this->request->get['tag_id'], $this->request->post);
			$this->session->data['success'] = '字段已更新';
			// 回列表时保留所属商品类型
			$tag_info = $this->model_catalog_custom_tag->getTag((int)$this->request->get['tag_id']);
			$pt = (int)($tag_info['product_type_id'] ?? 0);
			$pt_query = $pt ? '&product_type_id=' . $pt : '';
			$this->response->redirect($this->url->link('catalog/custom_tag', 'user_token=' . $this->session->data['user_token'] . $pt_query));
		}
		$this->getForm();
	}

	protected function getForm() {
		$data['user_token'] = $this->session->data['user_token'];
		if (isset($this->error['warning'])) { $data['error_warning'] = $this->error['warning']; } else { $data['error_warning'] = ''; }
		if (isset($this->error['name']))     { $data['error_name']     = $this->error['name'];     } else { $data['error_name'] = '';     }
		if (isset($this->error['parent']))   { $data['error_parent']   = $this->error['parent'];   } else { $data['error_parent'] = '';  }

		$tag_id = isset($this->request->get['tag_id']) ? (int)$this->request->get['tag_id'] : 0;
		$data['heading'] = $tag_id ? '编辑字段' : '添加字段';

		if ($tag_id) {
			$data['action'] = $this->url->link('catalog/custom_tag/edit', 'user_token=' . $this->session->data['user_token'] . '&tag_id=' . $tag_id);
			$tag_info = $this->model_catalog_custom_tag->getTag($tag_id);
		} else {
			$data['action'] = $this->url->link('catalog/custom_tag/add', 'user_token=' . $this->session->data['user_token']);
			$tag_info = array();
		}

		if (isset($this->request->post['name']))        { $data['name']        = $this->request->post['name'];        } elseif (!empty($tag_info)) { $data['name']        = $tag_info['name'];        } else { $data['name'] = ''; }
		if (isset($this->request->post['tag_type']))    { $data['tag_type']    = $this->request->post['tag_type'];    } elseif (!empty($tag_info)) { $data['tag_type']    = $tag_info['tag_type'];    } else { $data['tag_type'] = 'text'; }
		if (isset($this->request->post['is_required'])) { $data['is_required'] = $this->request->post['is_required']; } elseif (!empty($tag_info)) { $data['is_required'] = $tag_info['is_required']; } else { $data['is_required'] = 0; }
		if (isset($this->request->post['display_label'])) { $data['display_label'] = $this->request->post['display_label']; } elseif (!empty($tag_info)) { $data['display_label'] = $tag_info['display_label']; } else { $data['display_label'] = ''; }
		if (isset($this->request->post['status']))      { $data['status']      = $this->request->post['status'];      } elseif (!empty($tag_info)) { $data['status']      = $tag_info['status'];      } else { $data['status'] = 1; }
		if (isset($this->request->post['show_in_list'])) { $data['show_in_list'] = $this->request->post['show_in_list']; } elseif (!empty($tag_info)) { $data['show_in_list'] = $tag_info['show_in_list']; } else { $data['show_in_list'] = 0; }
		if (isset($this->request->post['parent_id']))   { $data['parent_id']   = $this->request->post['parent_id'];   } elseif (!empty($tag_info)) { $data['parent_id']   = $tag_info['parent_id'];   } else { $data['parent_id'] = 0; }

		// 所属商品类型:POST 优先, 其次现有字段, 最后 URL (新增字段时由列表页带入)
		if (isset($this->request->post['product_type_id'])) {
			$product_type_id = (int)$this->request->post['product_type_id'];
		} elseif (!empty($tag_info)) {
			$product_type_id = (int)$tag_info['product_type_id'];
		} elseif (isset($this->request->get['product_type_id'])) {
			$product_type_id = (int)$this->request->get['product_type_id'];
		} else {
			$product_type_id = 0;
		}
		$data['product_type_id'] = $product_type_id;
		$data['parent_options'] = $this->model_catalog_custom_tag->getParentOptions($tag_id, $product_type_id);

		// 只读系统字段(供表单展示)
		if (!empty($tag_info)) {
			$data['is_core']       = $tag_info['is_core'];
			$data['field_type']    = $tag_info['field_type'];
			$data['system_column'] = $tag_info['system_column'];
		} else {
			$data['is_core'] = 0;
			$data['field_type'] = 'tag';
			$data['system_column'] = '';
		}

		$pt_query = $product_type_id ? '&product_type_id=' . $product_type_id : '';
		$data['cancel'] = $this->url->link('catalog/custom_tag', 'user_token=' . $this->session->data['user_token'] . $pt_query);
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('catalog/custom_tag_form', $data));
	}

	public function delete() {
		if (!$this->user->hasPermission('modify', 'catalog/custom_tag')) {
			$this->session->data['error'] = '没有删除权限';
			$this->response->redirect($this->url->link('catalog/custom_tag', 'user_token=' . $this->session->data['user_token']));
			return;
		}
		$this->load->model('catalog/custom_tag');
		if (isset($this->request->post['selected'])) {
			foreach ($this->request->post['selected'] as $tag_id) {
				$this->model_catalog_custom_tag->deleteTag($tag_id);
			}
			$this->session->data['success'] = '字段已删除';
		}
		$this->response->redirect($this->url->link('catalog/custom_tag', 'user_token=' . $this->session->data['user_token']));
	}

	// 新增商品类型 (AJAX)
	public function addType() {
		$this->response->addHeader('Content-Type: application/json');
		if (!$this->user->hasPermission('modify', 'catalog/custom_tag')) {
			$this->response->setOutput(json_encode(['error' => '没有权限']));
			return;
		}
		if (!isset($this->request->get['user_token']) || $this->request->get['user_token'] !== $this->session->data['user_token']) {
			$this->response->setOutput(json_encode(['error' => 'Invalid CSRF token']));
			return;
		}
		$this->load->model('catalog/custom_tag');
		$name = trim((string)($this->request->post['name'] ?? ''));
		if ($name === '') {
			$this->response->setOutput(json_encode(['error' => '名称不能为空']));
			return;
		}
		$id = $this->model_catalog_custom_tag->addProductType($name);
		$this->response->setOutput(json_encode(['success' => true, 'product_type_id' => $id]));
	}

	// 重命名商品类型 (AJAX)
	public function renameType() {
		$this->response->addHeader('Content-Type: application/json');
		if (!$this->user->hasPermission('modify', 'catalog/custom_tag')) {
			$this->response->setOutput(json_encode(['error' => '没有权限']));
			return;
		}
		if (!isset($this->request->get['user_token']) || $this->request->get['user_token'] !== $this->session->data['user_token']) {
			$this->response->setOutput(json_encode(['error' => 'Invalid CSRF token']));
			return;
		}
		$this->load->model('catalog/custom_tag');
		$id = (int)($this->request->post['product_type_id'] ?? 0);
		$name = trim((string)($this->request->post['name'] ?? ''));
		if (!$id || $name === '') {
			$this->response->setOutput(json_encode(['error' => '参数不完整']));
			return;
		}
		$this->model_catalog_custom_tag->editProductType($id, $name);
		$this->response->setOutput(json_encode(['success' => true]));
	}

	// 删除商品类型 (AJAX) - 级联删除其下所有字段
	public function deleteType() {
		$this->response->addHeader('Content-Type: application/json');
		if (!$this->user->hasPermission('modify', 'catalog/custom_tag')) {
			$this->response->setOutput(json_encode(['error' => '没有权限']));
			return;
		}
		if (!isset($this->request->get['user_token']) || $this->request->get['user_token'] !== $this->session->data['user_token']) {
			$this->response->setOutput(json_encode(['error' => 'Invalid CSRF token']));
			return;
		}
		$this->load->model('catalog/custom_tag');
		$id = (int)($this->request->post['product_type_id'] ?? $this->request->get['product_type_id'] ?? 0);
		if (!$id) {
			$this->response->setOutput(json_encode(['error' => '缺少类型 ID']));
			return;
		}
		$this->model_catalog_custom_tag->deleteProductType($id);
		$this->response->setOutput(json_encode(['success' => true]));
	}

	protected function validateForm() {
		if ((utf8_strlen($this->request->post['name']) < 1) || (utf8_strlen($this->request->post['name']) > 64)) {
			$this->error['name'] = '字段名称必须在 1-64 字符之间';
		}
		// 成环校验(仅编辑已有字段时)
		$tag_id    = isset($this->request->get['tag_id']) ? (int)$this->request->get['tag_id'] : 0;
		$parent_id = (int)($this->request->post['parent_id'] ?? 0);
		if ($tag_id && $parent_id && $this->model_catalog_custom_tag->wouldCreateCycle($tag_id, $parent_id)) {
			$this->error['parent'] = '不能将父字段设为它自己或它的子字段';
		}
		return !$this->error;
	}

	public function saveTree() {
		if (!$this->user->hasPermission('modify', 'catalog/custom_tag')) {
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode(['error' => 'Permission denied']));
			return;
		}
		if (!isset($this->request->get['user_token']) || $this->request->get['user_token'] !== $this->session->data['user_token']) {
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode(['error' => 'Invalid CSRF token']));
			return;
		}
		$this->load->model('catalog/custom_tag');
		$json = file_get_contents('php://input');
		$data = json_decode($json, true);
		if (empty($data['tree'])) {
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode(['error' => 'No tree data']));
			return;
		}
		$this->model_catalog_custom_tag->saveTree($data['tree']);
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode(['success' => true]));
	}
}
