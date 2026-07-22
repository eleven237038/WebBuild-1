<?php
namespace Cart;
class Weight {
	private $weights = array();

	public function __construct($registry) {
		$this->db = $registry->get('db');
		$this->config = $registry->get('config');

		// Weight classes rarely change; cache the lookup in APCu to skip the
		// per-request query. Cleared by apcu_clear_cache() (opcache-reset.php).
		$__ck = 'ocweight:' . $this->config->get('config_language_id');
		$__hit = false;
		if (function_exists('apcu_fetch')) {
			$this->weights = apcu_fetch($__ck, $__hit);
		}

		if (!$__hit) {
			$weight_class_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "weight_class wc LEFT JOIN " . DB_PREFIX . "weight_class_description wcd ON (wc.weight_class_id = wcd.weight_class_id) WHERE wcd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

			foreach ($weight_class_query->rows as $result) {
				$this->weights[$result['weight_class_id']] = array(
					'weight_class_id' => $result['weight_class_id'],
					'title'           => $result['title'],
					'unit'            => $result['unit'],
					'value'           => $result['value']
				);
			}

			if (function_exists('apcu_store')) {
				apcu_store($__ck, $this->weights, 3600);
			}
		}
	}

	public function convert($value, $from, $to) {
		if ($from == $to) {
			return $value;
		}

		if (isset($this->weights[$from])) {
			$from = $this->weights[$from]['value'];
		} else {
			$from = 1;
		}

		if (isset($this->weights[$to])) {
			$to = $this->weights[$to]['value'];
		} else {
			$to = 1;
		}

		return $value * ($to / $from);
	}

	public function format($value, $weight_class_id, $decimal_point = '.', $thousand_point = ',') {
		if (isset($this->weights[$weight_class_id])) {
			return number_format($value, 2, $decimal_point, $thousand_point) . $this->weights[$weight_class_id]['unit'];
		} else {
			return number_format($value, 2, $decimal_point, $thousand_point);
		}
	}

	public function getUnit($weight_class_id) {
		if (isset($this->weights[$weight_class_id])) {
			return $this->weights[$weight_class_id]['unit'];
		} else {
			return '';
		}
	}
}