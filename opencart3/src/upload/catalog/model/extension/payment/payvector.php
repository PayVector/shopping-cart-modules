<?php

class ModelExtensionPaymentPayvector extends Model
{
	public function getMethod($address, $total)
	{
		$this->load->language('extension/payment/payvector');
		$query = $this->db->query(
			"SELECT zone_to_geo_zone_id FROM " . DB_PREFIX . "zone_to_geo_zone
			WHERE geo_zone_id = '" . (int) $this->config->get('payment_payvector_geo_zone_id') . "'
			AND country_id = '" . (int) $address['country_id'] . "'
			AND (zone_id = '" . (int) $address['zone_id'] . "' OR zone_id = '0')"
		);
		if(!$this->config->get('payment_payvector_geo_zone_id'))
		{
			$status = true;
		}
		elseif($query->num_rows)
		{
			$status = true;
		}
		else
		{
			$status = false;
		}
		$method_data = array();
		if($status)
		{
			$method_data = array(
				'code' => 'payvector',
				'title' => $this->config->get('payment_payvector_title'),
				'terms' => '',
				'sort_order' => $this->config->get('payment_payvector_sort_order')
			);
		}

		return $method_data;
	}
}