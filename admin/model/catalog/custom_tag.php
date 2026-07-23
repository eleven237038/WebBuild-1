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
		// 结构体现可嵌套子结构体 (最多 3 层); 父级须为结构体, 深度由 validateForm/saveTree 兜底
		$parent_id = (int)($data['parent_id'] ?? 0);
		// 类型专属配置 (number/text/textarea/image_multi): JSON 文本列
		$cfg = $this->db->escape((string)($data['config'] ?? ''));
		$this->db->query("INSERT INTO " . DB_PREFIX . "custom_tag SET parent_id = '" . $parent_id . "', name = '" . $this->db->escape($data['name']) . "', field_type = '" . $ft . "', tag_type = '" . $tt . "', is_required = '" . $ir . "', system_column = '" . $sc . "', input_type = '" . $it . "', display_label = '" . $dl . "', is_core = '" . $ic . "', product_type_id = '" . $ptid . "', sort_order = '" . (int)($data['sort_order'] ?? 0) . "', status = '" . (int)($data['status'] ?? 1) . "', show_in_list = '" . (int)($data['show_in_list'] ?? 0) . "', config = '" . $cfg . "', date_added = NOW(), date_modified = NOW()");
		$tag_id = $this->db->getLastId();
		// select 选项持久化
		if (!empty($data['options']) && is_array($data['options'])) {
			$this->setTagOptions($tag_id, $data['options']);
		}
		return $tag_id;
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
		// 类型专属配置: 优先用提交值, 否则保留旧值
		if (array_key_exists('config', $data)) {
			$cfg = $this->db->escape((string)$data['config']);
		} else {
			$cfg = $this->db->escape((string)($existing['config'] ?? ''));
		}
		$this->db->query("UPDATE " . DB_PREFIX . "custom_tag SET parent_id = '" . $parent_id . "', name = '" . $name . "', field_type = '" . $ft . "', tag_type = '" . $tt . "', is_required = '" . $ir . "', system_column = '" . $sc . "', input_type = '" . $it . "', display_label = '" . $dl . "', is_core = '" . $ic . "', product_type_id = '" . $ptid . "', sort_order = '" . $sort_order . "', status = '" . $status . "', show_in_list = '" . (int)($data['show_in_list'] ?? $existing['show_in_list']) . "', config = '" . $cfg . "', date_modified = NOW() WHERE tag_id = '" . (int)$tag_id . "'");
		// select 选项: 总是用提交值覆盖 (控制器永远会传 options, 即使为空)
		if (array_key_exists('options', $data)) {
			$this->setTagOptions((int)$tag_id, is_array($data['options']) ? $data['options'] : array());
		}
	}

	public function deleteTag($tag_id) {
		// 级联删除:连同所有子字段一起删除,避免孤儿
		$ids   = $this->getDescendantIds((int)$tag_id);
		$ids[] = (int)$tag_id;
		$id_list = implode(',', array_map('intval', $ids));
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_custom_tag WHERE tag_id IN (" . $id_list . ")");
		$this->db->query("DELETE FROM " . DB_PREFIX . "custom_tag_option WHERE tag_id IN (" . $id_list . ")");
		$this->db->query("DELETE FROM " . DB_PREFIX . "custom_tag WHERE tag_id IN (" . $id_list . ")");
	}

	// ── 类型专属配置 / 选项 (select) ──
	// 读 select 选项; 返回 [{value,text}], 与 system_column 注入的 options 形状一致。
	public function getTagOptions($tag_id) {
		$q = $this->db->query("SELECT * FROM " . DB_PREFIX . "custom_tag_option WHERE tag_id = '" . (int)$tag_id . "' ORDER BY sort_order ASC, option_id ASC");
		$out = array();
		foreach ($q->rows as $r) {
			$out[] = array('value' => $r['value'], 'text' => $r['label']);
		}
		return $out;
	}

	// 写 select 选项: $options = [{value,text}, ...] 或并行数组形式。
	public function setTagOptions($tag_id, $options) {
		$tag_id = (int)$tag_id;
		$this->db->query("DELETE FROM " . DB_PREFIX . "custom_tag_option WHERE tag_id = '" . $tag_id . "'");
		$sort = 0;
		foreach ($options as $opt) {
			$value = '';
			$text  = '';
			if (is_array($opt)) {
				$value = (string)($opt['value'] ?? '');
				$text  = (string)($opt['text'] ?? ($opt['label'] ?? ''));
			} else {
				$value = (string)$opt;
				$text  = (string)$opt;
			}
			// 跳过完全空行
			if ($value === '' && $text === '') { continue; }
			$this->db->query("INSERT INTO " . DB_PREFIX . "custom_tag_option SET tag_id = '" . $tag_id . "', value = '" . $this->db->escape($value) . "', label = '" . $this->db->escape($text) . "', sort_order = '" . (int)$sort . "'");
			$sort++;
		}
	}

	// 读类型专属配置 JSON (number 的 unit/min/max, text 的 placeholder,
	// textarea 的 placeholder, image_multi 的 max_count); 失败/空返 []。
	public function getTagConfig($tag_id) {
		$tag = $this->getTag($tag_id);
		if (!$tag || empty($tag['config'])) { return array(); }
		$cfg = json_decode($tag['config'], true);
		return is_array($cfg) ? $cfg : array();
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

	// 供编辑表单父字段下拉。规则: 仅结构体 (tag_type='struct') 可作父字段; 结构体可嵌套
	// 子结构体 (最多 3 层), 故列出全部结构体 (任意深度), 按深度缩进显示; 排除自身及其所有
	// 后代 (成环防御)。$product_type_id 非零时仅返回同类型结构体。返回值附 depth 供前端按
	// 类型门控 (结构体作子时父级深度须 <= 1, 即子结构体 depth <= 2)。
	public function getParentOptions($exclude_tag_id = 0, $product_type_id = 0) {
		$exclude = $exclude_tag_id ? $this->getDescendantIds((int)$exclude_tag_id) : array();
		$exclude[] = (int)$exclude_tag_id;
		$flat = $product_type_id ? $this->getCustomTagFlatByType((int)$product_type_id) : $this->getCustomTagFlat();
		$options = array();
		foreach ($flat as $row) {
			if ($row['tag_type'] !== 'struct') { continue; }
			if (in_array((int)$row['tag_id'], $exclude, true)) { continue; }
			$options[] = array(
				'tag_id' => $row['tag_id'],
				'name'   => str_repeat('　', (int)$row['depth']) . $row['name'],
				'depth'  => (int)$row['depth'],
			);
		}
		return $options;
	}

	// 计算某结构体的嵌套深度 (祖先链中结构体个数 = 其 depth; 仅结构体可作父)。
	// 用于 validateForm 校验结构体作子时父级深度 <= 1 (子结构体 depth <= 2, 即最多 3 层)。
	public function getStructDepth($tag_id) {
		$depth = 0;
		$seen  = array((int)$tag_id);
		$current = $this->getTag((int)$tag_id);
		while ($current && (int)$current['parent_id']) {
			$pid = (int)$current['parent_id'];
			if (in_array($pid, $seen, true)) { break; } // 防环
			$seen[] = $pid;
			$parent = $this->getTag($pid);
			if (!$parent) { break; }
			$depth++;
			$current = $parent;
		}
		return $depth;
	}

	// Batch save tree. Accepts the WordPress-style flat payload [{id, depth}, ...]
	// in display order: depth encodes the hierarchy and the parent is reconstructed
	// via a struct-only stack (mirrors WP's wp_save_nav_menu_items). Falls back to the
	// legacy nested [{id, children:[...]}] shape (Nestable2) for backward compat.
	//
	// 结构体规则: 仅 tag_type='struct' 可作父; 结构体可嵌套子结构体最多 3 层 (depth 0/1/2);
	// 普通字段可挂任意结构体下 (最深 depth 3)。struct_stack[i] = 当前作为第 i 层父级的结构体
	// tag_id (仅结构体入栈; 字段不入栈故不能作父)。客户端拖拽引擎已限制, 此处服务端兜底校正。
	public function saveTree($tree, $parent_id = 0) {
		if (empty($tree) || !is_array($tree)) { return; }

		$is_flat = isset($tree[0]) && array_key_exists('depth', $tree[0]);
		if ($is_flat) {
			// 预读所有节点的 tag_type
			$type_map = array();
			foreach ($tree as $node) {
				$tid = (int)($node['id'] ?? 0);
				if ($tid) {
					$tag = $this->getTag($tid);
					$type_map[$tid] = $tag ? $tag['tag_type'] : '';
				}
			}
			$struct_stack = array(); // depth => 结构体 tag_id (仅结构体入栈)
			$sort = 0;
			foreach ($tree as $node) {
				$tag_id = (int)($node['id'] ?? 0);
				$depth  = (int)($node['depth'] ?? 0);
				if (!$tag_id) { continue; }
				$tt = $type_map[$tag_id] ?? '';
				// 按类型钳制最大深度: 结构体 2 (3 层), 普通字段 3
				$cap = ($tt === 'struct') ? 2 : 3;
				if ($depth > $cap) { $depth = $cap; }
				if ($depth < 0) { $depth = 0; }
				// depth=0 -> 顶级; depth>0 -> 父级须是 struct_stack[depth-1] 处的结构体
				if ($depth === 0) {
					$parent = 0;
					$struct_stack = array();
				} elseif (isset($struct_stack[$depth - 1])) {
					$parent = $struct_stack[$depth - 1];
					// 截断栈: 丢弃更深的结构体引用 (已离开其子树, 防止字段后误挂旧父)
					$struct_stack = array_slice($struct_stack, 0, $depth, true);
				} else {
					// 缺少 depth-1 处的结构体父级 -> 降回顶级
					$depth = 0;
					$parent = 0;
					$struct_stack = array();
				}
				$this->db->query("UPDATE " . DB_PREFIX . "custom_tag SET parent_id = '" . (int)$parent . "', sort_order = '" . (int)$sort . "', date_modified = NOW() WHERE tag_id = '" . $tag_id . "'");
				// 仅结构体入栈 (成为下一层子字段的父级候选); 字段不入栈 (不能作父)
				if ($tt === 'struct') {
					$struct_stack[$depth] = $tag_id;
					$struct_stack = array_slice($struct_stack, 0, $depth + 1, true);
				}
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
	// 结构体无值, 不进规格; select 把存储的 value 解析成选项 label 显示
	// (前台显示"启用"而非"1")。
	public function getProductTags($product_id) {
		$query = $this->db->query("SELECT t.tag_id, t.name, t.tag_type, t.is_required, t.display_label, pt.`value` FROM " . DB_PREFIX . "custom_tag t INNER JOIN " . DB_PREFIX . "product_to_custom_tag pt ON t.tag_id = pt.tag_id WHERE pt.product_id = '" . (int)$product_id . "' AND t.status = 1 AND t.tag_type <> 'struct' ORDER BY t.sort_order ASC");
		$rows = $query->rows;
		// select: 把 value 映射为选项 label
		foreach ($rows as &$r) {
			if ($r['tag_type'] === 'select' && $r['value'] !== '') {
				foreach ($this->getTagOptions((int)$r['tag_id']) as $opt) {
					if ((string)$opt['value'] === (string)$r['value']) {
						$r['value'] = $opt['text'];
						break;
					}
				}
			}
		}
		unset($r);
		return $rows;
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
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_custom_tag SET product_id = '" . (int)$product_id . "', tag_id = '" . $tag_id . "', `value` = '" . $this->db->escape($value) . "' ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
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
