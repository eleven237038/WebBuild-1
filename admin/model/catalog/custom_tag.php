<?php
class ModelCatalogCustomTag extends Model {
	public function addTag($data) {
		$ft  = $this->db->escape((string)($data['field_type'] ?? 'tag'));
		$sc  = $this->db->escape((string)($data['system_column'] ?? ''));
		$it  = $this->db->escape((string)($data['input_type'] ?? ''));
		$dl  = $this->db->escape((string)($data['display_label'] ?? ''));
		$ic  = (int)($data['is_core'] ?? 0);
		$this->db->query("INSERT INTO " . DB_PREFIX . "custom_tag SET parent_id = '" . (int)($data['parent_id'] ?? 0) . "', name = '" . $this->db->escape($data['name']) . "', field_type = '" . $ft . "', system_column = '" . $sc . "', input_type = '" . $it . "', display_label = '" . $dl . "', is_core = '" . $ic . "', sort_order = '" . (int)$data['sort_order'] . "', status = '" . (int)$data['status'] . "', date_added = NOW(), date_modified = NOW()");
		return $this->db->getLastId();
	}

	public function editTag($tag_id, $data) {
		$ft  = $this->db->escape((string)($data['field_type'] ?? 'tag'));
		$sc  = $this->db->escape((string)($data['system_column'] ?? ''));
		$it  = $this->db->escape((string)($data['input_type'] ?? ''));
		$dl  = $this->db->escape((string)($data['display_label'] ?? ''));
		$ic  = (int)($data['is_core'] ?? 0);
		$this->db->query("UPDATE " . DB_PREFIX . "custom_tag SET parent_id = '" . (int)($data['parent_id'] ?? 0) . "', name = '" . $this->db->escape($data['name']) . "', field_type = '" . $ft . "', system_column = '" . $sc . "', input_type = '" . $it . "', display_label = '" . $dl . "', is_core = '" . $ic . "', sort_order = '" . (int)$data['sort_order'] . "', status = '" . (int)$data['status'] . "', date_modified = NOW() WHERE tag_id = '" . (int)$tag_id . "'");
	}

	public function deleteTag($tag_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "custom_tag WHERE tag_id = '" . (int)$tag_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_custom_tag WHERE tag_id = '" . (int)$tag_id . "'");
	}

	// Batch save tree from Nestable2 serialize output
	public function saveTree($tree, $parent_id = 0) {
		$sort = 0;
		foreach ($tree as $node) {
			$tag_id = (int)$node['id'];
			$this->db->query("UPDATE " . DB_PREFIX . "custom_tag SET parent_id = '" . (int)$parent_id . "', sort_order = '" . (int)$sort . "' WHERE tag_id = '" . $tag_id . "'");
			$sort++;
			if (!empty($node['children'])) {
				$this->saveTree($node['children'], $tag_id);
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

	// ── Product-tag associations ──
	public function getProductTags($product_id) {
		$query = $this->db->query("SELECT t.tag_id, t.name FROM " . DB_PREFIX . "custom_tag t INNER JOIN " . DB_PREFIX . "product_to_custom_tag pt ON t.tag_id = pt.tag_id WHERE pt.product_id = '" . (int)$product_id . "' AND t.status = 1 ORDER BY t.sort_order ASC");
		return $query->rows;
	}

	public function setProductTags($product_id, $tag_ids) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_custom_tag WHERE product_id = '" . (int)$product_id . "'");
		if (!empty($tag_ids)) {
			foreach ($tag_ids as $tag_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_custom_tag SET product_id = '" . (int)$product_id . "', tag_id = '" . (int)$tag_id . "'");
			}
		}
	}

	// Hierarchical tree with full recursion (supports infinite levels)
	public function getCustomTagTree() {
		$all = $this->getTags();
		return $this->_buildTree($all, 0);
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
	// Legacy: kept for backward compat
	public function getTagsTree_old() {
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
