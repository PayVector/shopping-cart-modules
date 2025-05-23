<?php
namespace Opencart\Admin\Controller\Extension\Payvector\Payment;

if (defined('DIR_EXTENSION')) {
	require_once DIR_EXTENSION . 'payvector/system/engine/controller.php';
} else {
	require_once DIR_SYSTEM . 'extension/payvector/system/engine/controller.php';
}

class Payvector extends \Opencart\System\Engine\Extension\Payvector\Controller
{
	private const HostedPaymentForm = 'Hosted Payment Form';
	public function install()
	{
		$this->load->model('setting/setting');
		$this->load->model('localisation/language');

		$value = [
			'status' => true,
			'title' => 'Credit/Debit card',
			'geo_zone_id' => '',
			'order_status_id' => '',
			'failed_order_status_id' => '',
			'capture_method' => '',
			'pre_shared_key' => '',
			'result_delivery_method' => '',
			'hash_method',
			'mid' => '',
			'pass' => '',
			'test' => '',
			'transaction_type' => '',
			'sort_order' => '',
			'enable_cross_reference' => false,
			'enable_3ds_cross_reference' => false,
		];

		$configs = [];
		foreach ($value as $key => $v) {
			$configs[$this->eName . '_' . $key] = $v;
		}

		$this->model_setting_setting->editSetting($this->eName, $configs);

		$this->db->query(
			"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "payvector_gateway_entry_points` (
				gateway_entry_point_object LONGTEXT NOT NULL,
				date_time_processed TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
			);"
		);
		$this->db->query(
			"INSERT INTO `" . DB_PREFIX . "payvector_gateway_entry_points` VALUES(
			'PlaceHolder',
			0
		);"
		);
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "payvector_cross_reference` (
				customer_id INT(11),
				cross_reference VARCHAR(24) DEFAULT NULL,
				card_first_six CHAR(6) DEFAULT NULL,
				card_last_four CHAR(4) DEFAULT NULL,
				card_type VARCHAR(45) DEFAULT NULL,
				last_updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (customer_id)
			);"
		);
	}

	public function uninstall()
	{
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "payvector_gateway_entry_points`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "payvector_cross_reference`");
	}

	public function index()
	{
		$data = [];

		$this->load->language($this->ePath);
		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		$data = array_merge($data, $this->commonLangValues());

		if (isset($this->error['warning'])) {
			$data['text_error_msg'] = $this->error['warning'];
		} else {
			$data['text_error_msg'] = '';
		}

		if(isset($this->error['mid'])) {
			$data['error_mid'] = $this->error['mid'];
		} else {
			$data['error_mid'] = '';
		}
		if(isset($this->error['pass'])) {
			$data['error_pass'] = $this->error['pass'];
		} else {
			$data['error_pass'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true),
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link($this->ePath, 'user_token=' . $this->session->data['user_token'], true),
		);

		//get order statuses so that we can show them for the "on successful/unsuccessful transaction" setting
		$this->load->model('localisation/order_status');
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();


		$data['save'] = $this->url->link('extension/payvector/payment/payvector' . _SEPARATOR_ . 'save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment');

		foreach ($this->config_keys as $key) {
			$full_key = $this->eName . '_' . $key;
			if (isset($this->request->post[$full_key])) {
				$data[$full_key] = $this->request->post[$full_key];
			} else {
				$data[$full_key] = $this->config->get($full_key);
			}
		}

		if(empty($data['payment_payvector_title']))
		{
			$data['payment_payvector_title'] = "Credit/Debit card";
		}

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($this->ePath, $data));
	}

	/**
	 * @return void
	 */
	public function save()
	{
		$this->load->language($this->ePath);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->ePath)) {
			$json['error'] = $this->language->get('error_permission');
		}

		error_log(print_r($this->error, true));

		if (empty($this->request->post['payment_payvector_mid'])) {
			$json['error']['mid'] = $this->language->get('error_mid');
		}

		if (empty($this->request->post['payment_payvector_pass'])) {
			$json['error']['pass'] = $this->language->get('error_pass');
		}

		error_log(print_r($this->error, true));
		error_log("Error array is empty: ".empty($this->error));
		error_log("Array count: ".count($this->error));
		error_log("Referrer key exists: " . array_key_exists('referrer', $this->error));

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting($this->eName, $this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
 
	protected function validate()
	{
		if (!$this->user->hasPermission('modify', $this->ePath)) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}


