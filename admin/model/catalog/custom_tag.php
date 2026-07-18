<?php
class ModelCatalogCustomTag extends Model {
	public function addTag($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "custom_tag SET name = '" . $this->db->escape($data['name']) . "', sort_order = '" . (int)$data['sort_order'] . "', status = '" . (int)$data['status'] . "', date_added = NOW(), date_modified = NOW()");
		return $this->db->getLastId();
	}

	public function editTag($tag_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "custom_tag SET name = '" . $this->db->escape($data['name']) . "', sort_order = '" . (int)$data['sort_order'] . "', status = '" . (int)$data['status'] . "', date_modified = NOW() WHERE tag_id = '" . (int)$tag_id . "'");
	}

	public function deleteTag($tag_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "custom_tag WHERE tag_id = '" . (int)$tag_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_custom_tag WHERE tag_id = '" . (int)$tag_id . "'");
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
}
