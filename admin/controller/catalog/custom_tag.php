<?php
class ControllerCatalogCustomTag extends Controller {
	private $error = array();

	public function index() {
		if (!$this->user->hasPermission('access', 'catalog/custom_tag')) {
			$this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token']));
			return;
		}
		$this->load->language('catalog/category');
		$this->document->setTitle('标签管理');
		$this->load->model('catalog/custom_tag');
		$this->getList();
	}

	protected function getList() {
		$data['tag_tree']   = $this->model_catalog_custom_tag->getCustomTagTree();
		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->error['warning'])) { $data['error_warning'] = $this->error['warning']; } else { $data['error_warning'] = ''; }
		if (isset($this->session->data['success'])) { $data['success'] = $this->session->data['success']; unset($this->session->data['success']); } else { $data['success'] = ''; }

		$data['add']    = $this->url->link('catalog/custom_tag/add', 'user_token=' . $this->session->data['user_token']);
		$data['delete'] = $this->url->link('catalog/custom_tag/delete', 'user_token=' . $this->session->data['user_token']);
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
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
			$this->model_catalog_custom_tag->addTag($this->request->post);
			$this->session->data['success'] = '标签已添加';
			// If AJAX (from drag-drop UI), return JSON; else redirect
			if (!empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
				$this->response->addHeader('Content-Type: application/json');
				$this->response->setOutput(json_encode(['success' => true]));
				return;
			}
			$this->response->redirect($this->url->link('catalog/custom_tag', 'user_token=' . $this->session->data['user_token']));
		}
		$this->getForm();
	}

	public function edit() {
		if (!$this->user->hasPermission('modify', 'catalog/custom_tag')) {
			$this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token']));
			return;
		}
		$this->load->language('catalog/category');
		$this->document->setTitle('编辑标签');
		$this->load->model('catalog/custom_tag');
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_catalog_custom_tag->editTag($this->request->get['tag_id'], $this->request->post);
			$this->session->data['success'] = '标签已更新';
			$this->response->redirect($this->url->link('catalog/custom_tag', 'user_token=' . $this->session->data['user_token']));
		}
		$this->getForm();
	}

	protected function getForm() {
		$data['user_token'] = $this->session->data['user_token'];
		if (isset($this->error['warning'])) { $data['error_warning'] = $this->error['warning']; } else { $data['error_warning'] = ''; }
		if (isset($this->error['name']))     { $data['error_name']     = $this->error['name'];     } else { $data['error_name'] = '';     }

		if (!isset($this->request->get['tag_id'])) {
			$data['action'] = $this->url->link('catalog/custom_tag/add', 'user_token=' . $this->session->data['user_token']);
		} else {
			$data['action'] = $this->url->link('catalog/custom_tag/edit', 'user_token=' . $this->session->data['user_token'] . '&tag_id=' . $this->request->get['tag_id']);
		}

		if (isset($this->request->get['tag_id'])) {
			$tag_info = $this->model_catalog_custom_tag->getTag($this->request->get['tag_id']);
		}
		if (isset($this->request->post['name']))       { $data['name']       = $this->request->post['name'];       } elseif (!empty($tag_info)) { $data['name']       = $tag_info['name'];       } else { $data['name'] = ''; }
		if (isset($this->request->post['sort_order'])) { $data['sort_order'] = $this->request->post['sort_order']; } elseif (!empty($tag_info)) { $data['sort_order'] = $tag_info['sort_order']; } else { $data['sort_order'] = 0; }
		if (isset($this->request->post['status']))     { $data['status']     = $this->request->post['status'];     } elseif (!empty($tag_info)) { $data['status']     = $tag_info['status'];     } else { $data['status'] = 1; }

		$data['all_tags']  = $this->model_catalog_custom_tag->getTags();
		$data['parent_id'] = isset($this->request->post['parent_id']) ? (int)$this->request->post['parent_id'] : (!empty($tag_info) ? (int)$tag_info['parent_id'] : 0);

		$data['cancel'] = $this->url->link('catalog/custom_tag', 'user_token=' . $this->session->data['user_token']);
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
			$this->session->data['success'] = '标签已删除';
		}
		$this->response->redirect($this->url->link('catalog/custom_tag', 'user_token=' . $this->session->data['user_token']));
	}

	protected function validateForm() {
		if ((utf8_strlen($this->request->post['name']) < 1) || (utf8_strlen($this->request->post['name']) > 64)) {
			$this->error['name'] = '标签名称必须在 1-64 字符之间';
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
