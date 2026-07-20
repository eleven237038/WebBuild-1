<?php
class ModelCatalogCustomTag extends Model {
	public function addTag($data) {
		$ft  = $this->db->escape((string)($data['field_type'] ?? 'tag'));
		$tt  = $this->db->escape((string)($data['tag_type'] ?? 'text'));
		$ir  = (int)($data['is_required'] ?? 0);
		$sc  = $this->db->escape((string)($data['system_column'] ?? ''));
		$it  = $this->db->escape((string)($data['input_type'] ?? ''));
		$dl  = $this->db->escape((string)($data['display_label'] ?? ''));
		$ic  = (int)($data['is_core'] ?? 0);
		$ptid = (int)($data['product_type_id'] ?? 0);
		$this->db->query("INSERT INTO " . DB_PREFIX . "custom_tag SET parent_id = '" . (int)($data['parent_id'] ?? 0) . "', name = '" . $this->db->escape($data['name']) . "', field_type = '" . $ft . "', tag_type = '" . $tt . "', is_required = '" . $ir . "', system_column = '" . $sc . "', input_type = '" . $it . "', display_label = '" . $dl . "', is_core = '" . $ic . "', product_type_id = '" . $ptid . "', sort_order = '" . (int)($data['sort_order'] ?? 0) . "', status = '" . (int)($data['status'] ?? 1) . "', show_in_list = '" . (int)($data['show_in_list'] ?? 0) . "', date_added = NOW(), date_modified = NOW()");
		return $this->db->getLastId();
	}

	public function editTag($tag_id, $data) {
		$existing = $this->getTag($tag_id);
		if (!$existing) { return; }
		// Merge: posted value wins, else preserve existing — protects is_core/system_column/input_type/parent_id/sort_order/status which the simplified form does not surface
		$name       = $this->db->escape((string)($data['name'] ?? $existing['name']));
		$ft         = $this->db->escape((string)($data['field_type'] ?? $existing['field_type']));
		$tt         = $this->db->escape((string)($data['tag_type'] ?? $existing['tag_type']));
		$ir         = (int)($data['is_required'] ?? $existing['is_required']);
		$sc         = $this->db->escape((string)($data['system_column'] ?? $existing['system_column']));
		$it         = $this->db->escape((string)($data['input_type'] ?? $existing['input_type']));
		$dl         = $this->db->escape((string)($data['display_label'] ?? $existing['display_label']));
		$ic         = (int)($data['is_core'] ?? $existing['is_core']);
		$ptid       = (int)($data['product_type_id'] ?? $existing['product_type_id']);
		$parent_id  = (int)($data['parent_id'] ?? $existing['parent_id']);
		// 成环防御:父级不能是自身或自身后代
		if ($parent_id && $this->wouldCreateCycle((int)$tag_id, $parent_id)) {
			$parent_id = (int)$existing['parent_id'];
		}
		$sort_order = (int)($data['sort_order'] ?? $existing['sort_order']);
		$status     = (int)($data['status'] ?? $existing['status']);
		$this->db->query("UPDATE " . DB_PREFIX . "custom_tag SET parent_id = '" . $parent_id . "', name = '" . $name . "', field_type = '" . $ft . "', tag_type = '" . $tt . "', is_required = '" . $ir . "', system_column = '" . $sc . "', input_type = '" . $it . "', display_label = '" . $dl . "', is_core = '" . $ic . "', product_type_id = '" . $ptid . "', sort_order = '" . $sort_order . "', status = '" . $status . "', show_in_list = '" . (int)($data['show_in_list'] ?? 0) . "', date_modified = NOW() WHERE tag_id = '" . (int)$tag_id . "'");
	}

	public function deleteTag($tag_id) {
		// 级联删除:连同所有子字段一起删除,避免孤儿
		$ids   = $this->getDescendantIds((int)$tag_id);
		$ids[] = (int)$tag_id;
		$id_list = implode(',', array_map('intval', $ids));
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_custom_tag WHERE tag_id IN (" . $id_list . ")");
		$this->db->query("DELETE FROM " . DB_PREFIX . "custom_tag WHERE tag_id IN (" . $id_list . ")");
	}

	// 递归收集某字段的所有后代 tag_id(含去重与基本防环)
	public function getDescendantIds($tag_id) {
		$ids = array();
		$query = $this->db->query("SELECT tag_id FROM " . DB_PREFIX . "custom_tag WHERE parent_id = '" . (int)$tag_id . "'");
		foreach ($query->rows as $row) {
			$cid = (int)$row['tag_id'];
			if (in_array($cid, $ids) || $cid == (int)$tag_id) { continue; }
			$ids[] = $cid;
			foreach ($this->getDescendantIds($cid) as $gc) {
				if (!in_array($gc, $ids)) { $ids[] = $gc; }
			}
		}
		return $ids;
	}

	// 判断把 parent_id 设为 tag_id 的父级是否成环(父级是自身或自身后代)
	public function wouldCreateCycle($tag_id, $parent_id) {
		if (!$parent_id) { return false; }
		if ((int)$parent_id == (int)$tag_id) { return true; }
		return in_array((int)$parent_id, $this->getDescendantIds((int)$tag_id));
	}

	// 供编辑表单父字段下拉。最大层级 4: 父字段可为任意非自身后代字段,
	// 返回全部字段 (排除自身及其后代防成环), 按 depth 缩进前缀体现层级。
	// $product_type_id 非零时仅返回同类型字段 (父字段必须同属一个商品类型)。
	public function getParentOptions($exclude_tag_id = 0, $product_type_id = 0) {
		$exclude = $exclude_tag_id ? $this->getDescendantIds((int)$exclude_tag_id) : array();
		$exclude[] = (int)$exclude_tag_id;
		$options = array();
		$flat = $product_type_id ? $this->getCustomTagFlatByType((int)$product_type_id) : $this->getCustomTagFlat();
		foreach ($flat as $row) {
			if (in_array((int)$row['tag_id'], $exclude)) { continue; }
			$options[] = array(
				'tag_id' => $row['tag_id'],
				'name'   => str_repeat('　', (int)$row['depth']) . $row['name'],
			);
		}
		return $options;
	}

	// Batch save tree. Accepts the WordPress-style flat payload [{id, depth}, ...]
	// in display order: depth encodes the hierarchy and the parent is reconstructed
	// via a depth stack (mirrors WP's wp_save_nav_menu_items). Falls back to the
	// legacy nested [{id, children:[...]}] shape (Nestable2) for backward compat.
	public function saveTree($tree, $parent_id = 0) {
		if (empty($tree) || !is_array($tree)) { return; }

		$is_flat = isset($tree[0]) && array_key_exists('depth', $tree[0]);
		if ($is_flat) {
			$stack = array(0 => 0); // depth => parent_id (depth 0 -> parent 0)
			$sort  = 0;
			foreach ($tree as $node) {
				$tag_id = (int)($node['id'] ?? 0);
				$depth  = (int)($node['depth'] ?? 0);
				if (!$tag_id) { continue; }
				if ($depth < 0) { $depth = 0; }
				if ($depth > 4) { $depth = 4; }   // 全局上限 4 级: 最多 4 层子字段
				$parent = ($depth <= 0) ? 0 : (isset($stack[$depth - 1]) ? $stack[$depth - 1] : 0);
				$this->db->query("UPDATE " . DB_PREFIX . "custom_tag SET parent_id = '" . (int)$parent . "', sort_order = '" . (int)$sort . "', date_modified = NOW() WHERE tag_id = '" . $tag_id . "'");
				$stack[$depth] = $tag_id;
				$sort++;
			}
		} else {
			$this->_saveTreeNested($tree, (int)$parent_id);
		}
	}

	// Legacy nested-format persister (Nestable2 serialize shape).
	private function _saveTreeNested($tree, $parent_id) {
		$sort = 0;
		foreach ($tree as $node) {
			$tag_id = (int)$node['id'];
			$this->db->query("UPDATE " . DB_PREFIX . "custom_tag SET parent_id = '" . (int)$parent_id . "', sort_order = '" . (int)$sort . "', date_modified = NOW() WHERE tag_id = '" . $tag_id . "'");
			$sort++;
			if (!empty($node['children'])) {
				$this->_saveTreeNested($node['children'], $tag_id);
			}
		}
	}

	public function getTag($tag_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "custom_tag WHERE tag_id = '" . (int)$tag_id . "'");
		return $query->row;
	}

	public function getTags($data = array()) {
		$sql = "SELECT * FROM " . DB_PREFIX . "custom_tag";
		if (!empty($data['filter_name'])) {
			$sql .= " WHERE name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}
		$sql .= " ORDER BY sort_order ASC";
		if (isset($data['start']) || isset($data['limit'])) {
			$sql .= " LIMIT " . (int)$data['start'] . ", " . (int)$data['limit'];
		}
		return $this->db->query($sql)->rows;
	}

	public function getTotalTags() {
		return $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "custom_tag")->row['total'];
	}

	// ── 商品类型 (Product Type) CRUD ──
	// 一个商品类型是一组字段的结构容器; 字段通过 product_type_id 归属某类型。
	public function getProductTypes() {
		return $this->db->query("SELECT * FROM " . DB_PREFIX . "product_type ORDER BY sort_order ASC, product_type_id ASC")->rows;
	}

	public function getProductType($product_type_id) {
		return $this->db->query("SELECT * FROM " . DB_PREFIX . "product_type WHERE product_type_id = '" . (int)$product_type_id . "'")->row;
	}

	public function addProductType($name) {
		$next = $this->db->query("SELECT COALESCE(MAX(sort_order), 0) + 1 AS s FROM " . DB_PREFIX . "product_type")->row['s'];
		$this->db->query("INSERT INTO " . DB_PREFIX . "product_type SET name = '" . $this->db->escape((string)$name) . "', sort_order = '" . (int)$next . "', status = 1, date_added = NOW(), date_modified = NOW()");
		return $this->db->getLastId();
	}

	public function editProductType($product_type_id, $name) {
		$this->db->query("UPDATE " . DB_PREFIX . "product_type SET name = '" . $this->db->escape((string)$name) . "', date_modified = NOW() WHERE product_type_id = '" . (int)$product_type_id . "'");
	}

	// 删除商品类型:级联删除其下所有字段 (deleteTag 再级联 product_to_custom_tag)。
	public function deleteProductType($product_type_id) {
		$rows = $this->db->query("SELECT tag_id FROM " . DB_PREFIX . "custom_tag WHERE product_type_id = '" . (int)$product_type_id . "'")->rows;
		foreach ($rows as $row) {
			$this->deleteTag((int)$row['tag_id']);
		}
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_type WHERE product_type_id = '" . (int)$product_type_id . "'");
	}

	// ── Product-tag associations (EAV: stores per-product value) ──
	public function getProductTags($product_id) {
		$query = $this->db->query("SELECT t.tag_id, t.name, t.tag_type, t.is_required, t.display_label, pt.`value` FROM " . DB_PREFIX . "custom_tag t INNER JOIN " . DB_PREFIX . "product_to_custom_tag pt ON t.tag_id = pt.tag_id WHERE pt.product_id = '" . (int)$product_id . "' AND t.status = 1 ORDER BY t.sort_order ASC");
		return $query->rows;
	}

	/**
	 * Persist product <-> custom tag associations with values.
	 * Accepts any of:
	 *   - [tag_id => ['value' => 'x'], ...]   (form POST shape)
	 *   - [['tag_id' => 1, 'value' => 'x'], ...]
	 *   - [1, 2, 3]                            (legacy flat id list, value='')
	 */
	public function setProductTags($product_id, $tag_data) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_custom_tag WHERE product_id = '" . (int)$product_id . "'");
		if (!empty($tag_data)) {
			foreach ($tag_data as $key => $item) {
				if (is_array($item)) {
					$tag_id = isset($item['tag_id']) ? (int)$item['tag_id'] : (int)$key;
					$value  = isset($item['value']) ? (string)$item['value'] : '';
				} else {
					$tag_id = (int)$item;
					$value  = '';
				}
				if ($tag_id) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_custom_tag SET product_id = '" . (int)$product_id . "', tag_id = '" . $tag_id . "', `value` = '" . $this->db->escape($value) . "'");
				}
			}
		}
	}

	// Hierarchical tree with full recursion (supports infinite levels)
	public function getCustomTagTree() {
		$all = $this->getTags();
		return $this->_buildTree($all, 0);
	}

	// Flat preorder traversal of the tree; each row is augmented with its `depth`.
	// Feeds the WordPress-style flat drag UI where depth is encoded as margin-left.
	public function getCustomTagFlat() {
		return $this->_flatFromRows($this->getTags());
	}

	// 同 getCustomTagFlat, 但仅返回某商品类型下的字段 (列表页右侧字段树用)。
	public function getCustomTagFlatByType($product_type_id) {
		return $this->_flatFromRows($this->getTagsByType($product_type_id));
	}

	public function getTagsByType($product_type_id) {
		$sql = "SELECT * FROM " . DB_PREFIX . "custom_tag WHERE product_type_id = '" . (int)$product_type_id . "' ORDER BY sort_order ASC";
		return $this->db->query($sql)->rows;
	}

	private function _flatFromRows($all) {
		$tree = $this->_buildTree($all, 0);
		$flat = array();
		$walk = function($nodes, $depth) use (&$walk, &$flat) {
			foreach ($nodes as $node) {
				$kids = !empty($node['children']) ? $node['children'] : array();
				unset($node['children']);
				$node['depth'] = $depth;
				$flat[] = $node;
				if ($kids) {
					$walk($kids, $depth + 1);
				}
			}
		};
		$walk($tree, 0);
		return $flat;
	}
	private function _buildTree(&$all, $parent_id) {
		$branch = array();
		foreach ($all as $key => $tag) {
			if ((int)$tag['parent_id'] == $parent_id) {
				$children = $this->_buildTree($all, (int)$tag['tag_id']);
				if ($children) { $tag['children'] = $children; }
				$branch[] = $tag;
			}
		}
		return $branch;
	}

	public function getTagsTree() {
		$tags = $this->getTags();
		$tree = array();
		foreach ($tags as $tag) {
			if ($tag['parent_id'] == 0) {
				$tree[$tag['tag_id']] = array('tag' => $tag, 'children' => array());
			}
		}
		foreach ($tags as $tag) {
			if ($tag['parent_id'] > 0 && isset($tree[$tag['parent_id']])) {
				$tree[$tag['parent_id']]['children'][] = $tag;
			}
		}
		return $tree;
	}

	public function getTagsFlatTree() {
		$tree = $this->getTagsTree();
		$flat = array();
		foreach ($tree as $parent) {
			$flat[] = $parent['tag'];
			foreach ($parent['children'] as $child) {
				$child['name'] = '--- ' . $child['name'];
				$flat[] = $child;
			}
		}
		return $flat;
	}
}
