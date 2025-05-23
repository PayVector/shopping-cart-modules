<?php

namespace Opencart\Catalog\Model\Extension\Payvector\Payment;

if (defined('DIR_EXTENSION')) {
	require_once DIR_EXTENSION . 'payvector/system/engine/model.php';
} else {
	require_once DIR_SYSTEM . 'extension/payvector/system/engine/model.php';
}

class Payvector extends \Opencart\System\Engine\Extension\Payvector\Model
{
/**
	 * @param array $address
	 *
	 * @return array
	 */
	public function getMethods(array $address = []): array {

		$this->load->language($this->ePath);

		if(!$this->config->get('payment_payvector_geo_zone_id')) {
			$status = true;
		} elseif($query->num_rows) {
			$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '" . (int)$this->config->get('payment_payvector_geo_zone_id') . "' AND `country_id` = '" . (int)$address['country_id'] . "' AND (`zone_id` = '" . (int)$address['zone_id'] . "' OR `zone_id` = '0')");
			$status = true;
		} else {
			$status = false;
		}

		$method_data = array();

		if($status) {
			$option_data['payvector'] = [
				'code' => 'payvector.payvector',
				'name' => $this->config->get('payment_payvector_title')
			];

			$method_data = [
				'code'       => 'payvector',
				'name'       => $this->config->get('payment_payvector_title'),
				'option'     => $option_data,
				'sort_order' => $this->config->get('payment_payvector_sort_order')
			];
		}

		return $method_data;
	}
}
