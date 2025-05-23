<?php

class ControllerExtensionPaymentPayvector extends Controller
{
	private $error = array();

	/**
	 * Called when the plugin is first installed - creates the database tables and inserts placeholder data
	 */
	public function install()
	{
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

	/**
	 * Called when the plugin is removed - drops the database tables
	 */
	public function uninstall()
	{
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "payvector_gateway_entry_points`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "payvector_cross_reference`");
	}

	/**
	 * Shows config page for the PayVector extension
	 */
	public function index()
	{
		$this->load->language('extension/payment/payvector');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');
		if(($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate()))
		{
			$this->model_setting_setting->editSetting('payment_payvector', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', 'SSL'));
		}
		//load all text from the language file into the class
		$data = array();
		$data += $this->language->load('extension/payment/payvector');
		//get order statuses so that we can show them for the "on successful/unsuccessful transaction" setting
		$this->load->model('localisation/order_status');
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		//get success message/errors
		if(isset($this->session->data['success']))
		{
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		}
		else
		{
			$data['success'] = '';
		}
		if(isset($this->error['warning']))
		{
			$data['error_warning'] = $this->error['warning'];
		}
		else
		{
			$data['error_warning'] = '';
		}
		if(isset($this->error['mid']))
		{
			$data['error_mid'] = $this->error['mid'];
		}
		else
		{
			$data['error_mid'] = '';
		}
		if(isset($this->error['pass']))
		{
			$data['error_pass'] = $this->error['pass'];
		}
		else
		{
			$data['error_pass'] = '';
		}

		//show breadcrumbs
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL'),
			'separator' => false
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_payment'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', 'SSL'),
			'separator' => ' :: '
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/payvector', 'user_token=' . $this->session->data['user_token'], 'SSL'),
			'separator' => ' :: '
		);
		$data['action'] = $this->url->link('extension/payment/payvector', 'user_token=' . $this->session->data['user_token'], 'SSL');
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', 'SSL');

		//check if a setting was passed in the POST - if not get it from the config
		$data['payment_payvector_title'] = $this->config->get('payment_payvector_title');
		if(empty($data['payment_payvector_title']))
		{
			$data['payment_payvector_title'] = "Credit/Debit card";
		}
		$data['payment_payvector_status'] = $this->config->get('payment_payvector_status');
		$data['payment_payvector_geo_zone_id'] = $this->config->get('payment_payvector_geo_zone_id');
		
		$defaultSuccessfulOrderStatus = 'Processing';
		$defaultFailedOrderStatus = 'Failed';

		$defaultSuccessfulOrderStatusID = '';
		$defaultFailedOrderStatusID = '';

		foreach ($data['order_statuses'] as $status) {
			if (strtolower($status['name']) === strtolower($defaultSuccessfulOrderStatus)) {
				$defaultSuccessfulOrderStatusID = $status['order_status_id'];
			}
			if (strtolower($status['name']) === strtolower($defaultFailedOrderStatus)) {
				$defaultFailedOrderStatusID = $status['order_status_id'];
			}
		}
		
		$data['payment_payvector_order_status_id'] = $this->config->get('payment_payvector_order_status_id') ? : $defaultSuccessfulOrderStatusID;
		$data['payment_payvector_failed_order_status_id'] = $this->config->get('payment_payvector_failed_order_status_id') ? : $defaultFailedOrderStatusID;

		$data['payment_payvector_capture_method'] = $this->config->get('payment_payvector_capture_method') ? : 'Hosted Payment Form';
		$data['payment_payvector_pre_shared_key'] = $this->config->get('payment_payvector_pre_shared_key');
		$data['payment_payvector_result_delivery_method'] = $this->config->get('payment_payvector_result_delivery_method');
		$data['payment_payvector_hash_method'] = $this->config->get('payment_payvector_hash_method') ? : 'SHA1';
		$data['payment_payvector_mid'] = $this->config->get('payment_payvector_mid');
		$data['payment_payvector_pass'] = $this->config->get('payment_payvector_pass');
		$data['payment_payvector_test'] = $this->config->get('payment_payvector_test');
		$data['payment_payvector_transaction_type'] = $this->config->get('payment_payvector_transaction_type');
		$data['payment_payvector_sort_order'] = $this->config->get('payment_payvector_sort_order');
		$data['payment_payvector_enable_cross_reference'] = $this->config->get('payment_payvector_enable_cross_reference');
		$data['payment_payvector_enable_3ds_cross_reference'] = $this->config->get('payment_payvector_enable_3ds_cross_reference');

		$this->load->model('localisation/geo_zone');
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/payvector', $data));
	}

	private function validate()
	{
		error_log(print_r($this->error, true));

		if(!$this->user->hasPermission('modify', 'extension/payment/payvector'))
		{
			$this->error['warning'] = $this->language->get('error_permission');
		}
		if(!$this->request->post['payment_payvector_mid'])
		{
			$this->error['mid'] = $this->language->get('error_mid');
		}
		if(!$this->request->post['payment_payvector_pass'])
		{
			$this->error['pass'] = $this->language->get('error_pass');
		}
		
		error_log(print_r($this->error, true));
		error_log("Error array is empty: ".empty($this->error));
		error_log("Array count: ".count($this->error));
		error_log("Referrer key exists: ".array_key_exists('referrer', $this->error));
		
		if (empty($this->error))
		{
			error_log("PERMISSION CHECK TRUE");
			return true;
		}
		else
		{
			error_log("PERMISSION CHECK FALSE");
			return false;
		}
	}
}