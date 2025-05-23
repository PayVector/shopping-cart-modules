<?php

namespace Opencart\System\Library\Extension\Payvector;

define('_SEPARATOR_', (version_compare(VERSION, '4.0.2.0', '<')) ? '/' : '.');

trait PayvectorLib {

	protected $mName      = 'payvector';
	protected $eName      = 'payment_payvector';
    protected $ePath      = 'extension/payvector/payment/payvector';
    protected $ePathEvent = 'extension/payvector/event/payvector';
    protected $ePathTemplate = 'extension/payvector/payment/';
    protected $eVersion   = '1.0.0';
    protected $separator   = _SEPARATOR_;

    protected $config_keys = array(
		'status',
		'title',
		'geo_zone_id',
		'order_status_id',
		'failed_order_status_id',
		'capture_method',
		'pre_shared_key',
		'result_delivery_method',
		'hash_method',
		'mid',
		'pass',
		'test',
		'transaction_type',
		'sort_order',
		'enable_cross_reference',
		'enable_3ds_cross_reference',
	);

	public function getConfigSettingValues() {
		$data = array();
		foreach ($this->config_keys as $key) {
			$data[$key] = $this->config->get($this->eName . '_' . $key);
		}
		return $data;
	}

	public function commonLangValues($data = []) {

		// Global Setting
		return $data;
	}
}
