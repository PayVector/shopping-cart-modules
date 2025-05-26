<?php
use PrestaShop\PrestaShop\Core\Payment\PaymentOption; 
if (!defined('_PS_VERSION_'))
{
	exit;
}

include 'lib/TransactionProcessor.php';
include 'CrossReference.php';

class PayVector extends PaymentModule
{
	public function __construct()
	{
		$this->name = 'payvector';
		$this->tab = 'payments_gateways';
		$this->version = '2.0.0';
		$this->author = 'Iridium Corporation';
		$this->module_key = '1305374415efa0fad2f0fee2256187b2';
		$this->need_instance = 1;
		$this->ps_versions_compliancy = array(
            'min' => '1.5',
            'max' => _PS_VERSION_
        );
		$this->bootstrap = true;

		parent::__construct();		

		$this->displayName = $this->l('PayVector');
		$this->description = $this->l('PayVector payment extension for PrestaShop. This extension fully supports the processing of 3D secure ('.
			'Verified By Visa and Mastercard SecureCode) transactions.');
		$this->confirmUninstall = $this->l('Are you sure you want to uninstall? Your configuration will not be saved.');
		$this->paymentProcessorDomain = "payvector.net";		

		//$this->shopID = $this->context->shop->id;
	}

	public function install()
    {
        if (!parent::install() || !$this->registerHook('paymentOptions') || !$this->registerHook('adminOrder') || !$this->registerHook('paymentReturn')
        ) {
            return false;
        }

		
		Db::getInstance()->Execute(
			'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payvector_cross_reference`
			(
				`id_cross_reference` INT NOT NULL,
				`id_customer` INT NOT NULL,
				`cross_reference` VARCHAR(24) NULL DEFAULT NULL,
				`card_last_four` VARCHAR(4) NULL DEFAULT NULL,
				`card_type` VARCHAR(45) NULL DEFAULT NULL,
				`last_updated` VARCHAR(255) NOT NULL,
			PRIMARY KEY (`id_cross_reference`)
			);'
		);

        return true;

    }

	public function hookPaymentOptions($params)
    {
        return $this->payvectorPaymentOptions($params);
    }

	public function payvectorPaymentOptions($params)
    {

        if (!$this->active) {
            return;
        }
        
        $payment_options = [
            $this->payvectorExternalPaymentOption(),
        ];
        return $payment_options;
    }
	public function hookAdminOrder($params)
	{
		if (!$this->active) return false;
		$order = new Order((int)$params['id_order']);
		if ($order->module != $this->name)
			return false;
		if (!Validate::isLoadedObject($order))
			return false;

		/* Refund or cancel a transaction */
		if (Tools::isSubmit('payvectorrefund'))		
		{
			$this->payvector_refund($order);
		}
		$order = new Order((int)$params['id_order']);
			
		$logoUrl = $this->context->link->getBaseLink() . 'modules/payvector/img/logo.png';
		$isPaymentAccepted = ((int)$order->current_state === (int)Configuration::get('PS_OS_PAYMENT'));
		$refundedStates = [
      	  (int)Configuration::get('PS_OS_REFUND'),
	        (int)Configuration::get('PS_OS_PARTIAL_REFUND'),
	    ];
		$isRefunded = in_array((int)$order->current_state, $refundedStates);
		
		$this->context->smarty->assign(array(
			'payvector_form' => './index.php?tab=AdminOrders&id_order='.(int)$order->id.'&vieworder&token='.Tools::getAdminTokenLite('AdminOrders'),
			'logoUrl' =>$logoUrl,
			'show_refund_button' => $isPaymentAccepted,
			 'is_refunded' => $isRefunded,
		));

		return $this->display(__FILE__, 'admin-order.tpl');
	
	}
	private function getEntryPointList()
	{
		$configuration = Configuration::getMultiple(
			array(
				'PAYVECTOR_ENTRY_POINTS',
				'PAYVECTOR_ENTRY_POINTS_MODIFIED'
			)
		);

		$rgepl_request_gateway_entry_point_list = new \net\thepaymentgateway\paymentsystem\RequestGatewayEntryPointList();
		

		if (
			strtotime($configuration['PAYVECTOR_ENTRY_POINTS_MODIFIED']) > strtotime('-30 minutes')
			&& $configuration['PAYVECTOR_ENTRY_POINTS'] !== null
		)
		{
			$gepl_gateway_entry_point_list = GatewayEntryPointList::fromXmlString($configuration['PAYVECTOR_ENTRY_POINTS']);
			for($nCount = 0; $nCount < $gepl_gateway_entry_point_list->getCount(); $nCount++)
			{
				$gepl_gateway_entry_point = $gepl_gateway_entry_point_list->getAt($nCount);
				$rgepl_request_gateway_entry_point_list->add($gepl_gateway_entry_point->getEntryPointURL(), $gepl_gateway_entry_point->getMetric(), 1);
			}
		}
		else
		{
			// if we don't have a recent list in the database then just use blind processing
			$rgepl_request_gateway_entry_point_list->add("https://gw1." . $this->paymentProcessorDomain, 100, 2);
			$rgepl_request_gateway_entry_point_list->add("https://gw2." . $this->paymentProcessorDomain, 200, 2);
			$rgepl_request_gateway_entry_point_list->add("https://gw3." . $this->paymentProcessorDomain, 300, 2);
		}

		return $rgepl_request_gateway_entry_point_list;
	}

	public function payvector_refund($order)
	{
		$configuration = Configuration::getMultiple(
				array(
					'PAYVECTOR_TEST_MODE',
					'PAYVECTOR_TEST_MERCHANT_ID',
					'PAYVECTOR_TEST_MERCHANT_PASSWORD',
					'PAYVECTOR_MERCHANT_ID',
					'PAYVECTOR_MERCHANT_PASSWORD',
					'PAYVECTOR_CAPTURE_METHOD',
					'PAYVECTOR_PRE_SHARED_KEY',
					'PAYVECTOR_HASH_METHOD',
					'PAYVECTOR_RESULT_DELIVERY_METHOD',
					'PAYVECTOR_3DS_ON_CROSS_REFERENCE'
				)
			);
			if ($configuration['PAYVECTOR_TEST_MODE'] === "true")
			{
				$merchant_id = $configuration['PAYVECTOR_TEST_MERCHANT_ID'];
				$merchant_password = $configuration['PAYVECTOR_TEST_MERCHANT_PASSWORD'];
			}
			else
			{
				$merchant_id = $configuration['PAYVECTOR_MERCHANT_ID'];
				$merchant_password = $configuration['PAYVECTOR_MERCHANT_PASSWORD'];
			}

		$payments = $order->getOrderPaymentCollection();
		if ($payments && count($payments)) {
			 $payment = $payments[0];
			 $CrossReference = $payment->transaction_id;

			$isoCurrencyCode = null;			
			
			$currencyShort = Currency::getCurrencyInstance((int)$order->id_currency)->iso_code;
			$order_id = $order->id;
			$reason = 'Refund';
			
			$iclISOCurrencyList = ISOHelper::getISOCurrencyList();
			if (!empty($currencyShort) && $iclISOCurrencyList->getISOCurrency($currencyShort, $icISOCurrency)) {			
				$isoCurrencyCode = $icISOCurrency->getISOCode();
			}			
			
			$amount = (float)$order->total_paid;
			

			$amount = intval($amount * pow(10, $icISOCurrency->getExponent()));
			
			
			
			
			$crtCrossReferenceTransaction = new \net\thepaymentgateway\paymentsystem\CrossReferenceTransaction($this->getEntryPointList());

			$crtCrossReferenceTransaction->getMerchantAuthentication()->setMerchantID($merchant_id);
			$crtCrossReferenceTransaction->getMerchantAuthentication()->setPassword($merchant_password);

			$crtCrossReferenceTransaction->getTransactionDetails()->getMessageDetails()->setTransactionType("REFUND");
			$crtCrossReferenceTransaction->getTransactionDetails()->getMessageDetails()->setCrossReference($CrossReference);

			$crtCrossReferenceTransaction->getTransactionDetails()->getAmount()->setValue((string) $amount);
			$crtCrossReferenceTransaction->getTransactionDetails()->getCurrencyCode()->setValue($isoCurrencyCode);

			$crtCrossReferenceTransaction->getTransactionDetails()->setOrderID((string) $order_id);
			$crtCrossReferenceTransaction->getTransactionDetails()->setOrderDescription((string) $reason);

			$boTransactionProcessed = $crtCrossReferenceTransaction->processTransaction($crtrCrossReferenceTransactionResult, $todTransactionOutputData);

			if ($boTransactionProcessed == false)
			{				
				return false;
			}
			else
			{
				$orderHistory = new OrderHistory();
			    $orderHistory->id_order = $order->id;
				$refundedStatusId = (int)Configuration::get('PS_OS_REFUND');
				$orderHistory->changeIdOrderState($refundedStatusId, $order->id);
				$orderHistory->addWithemail(true);
				return true;
				
			}	
		}
		
		
	}

	public function payvectorExternalPaymentOption()
    {
        $lang = Tools::strtolower($this->context->language->iso_code);
		$url = $this->context->link->getModuleLink('payvector', 'payment');
		$errmsg = null;

		$year_now = date('y');
		$start_year = array();
		$start_year[] = "";
		$expiry_year = array();
		$expiry_year[] = "";
		for($y = $year_now - 9; $y <= $year_now; $y++)
		{
			$start_year[] = sprintf('%02d', $y);
		}
		for($y = $year_now; $y < $year_now + 10; $y++)
		{
			$expiry_year[] = sprintf('%02d', $y);
		}

		if(Configuration::get('PAYVECTOR_SAVED_CARD') === "true")
		{
			$cross_reference = new CrossReference();
			$cross_reference->loadFromCustomerID($this->context->cart->id_customer);
			if(isset($cross_reference->last_updated) && strtotime($cross_reference->last_updated) > strtotime('-365 days'))
			{
				$this->context->smarty->assign(array(
					'cross_reference' => $cross_reference->cross_reference,
					'card_last_four' => $cross_reference->card_last_four,
					'card_type' => $cross_reference->card_type,
				));
			}
		}

		
		if (isset($_GET['payvectorerror'])) $errmsg = $_GET['payvectorerror'];
        $this->context->smarty->assign(array(
			'module_dir' => __PS_BASE_URI__ . 'modules/' . $this->name . '/',
            'module_name' => $this->name,            
			'action_url' => $url,						            
            'errmsg' => $errmsg,
			'capture_method' => Configuration::get('PAYVECTOR_CAPTURE_METHOD'),
			'start_year' => $start_year,
			'expiry_year' => $expiry_year
        ));
		
        $newOption = new PaymentOption();
        $newOption->setCallToActionText($this->l('Pay with PayVector'))
            ->setForm($this->context->smarty->fetch('module:payvector/views/templates/hook/payment.tpl'));

        return $newOption;
    }

	
	public function uninstall()
	{
		if (!parent::uninstall())
		{
			return false;
		}

		Db::getInstance()->Execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'payvector_cross_reference`;');

		/* Clean configuration table */
		Configuration::deleteFromContext('PAYVECTOR_TEST_MODE');
		Configuration::deleteFromContext('PAYVECTOR_TEST_MERCHANT_ID');
		Configuration::deleteFromContext('PAYVECTOR_TEST_MERCHANT_PASSWORD');
		Configuration::deleteFromContext('PAYVECTOR_MERCHANT_ID');
		Configuration::deleteFromContext('PAYVECTOR_MERCHANT_PASSWORD');
		Configuration::deleteFromContext('PAYVECTOR_CAPTURE_METHOD');
		Configuration::deleteFromContext('PAYVECTOR_PRE_SHARED_KEY');
		Configuration::deleteFromContext('PAYVECTOR_HASH_METHOD');
		Configuration::deleteFromContext('PAYVECTOR_RESULT_DELIVERY_METHOD');
		Configuration::deleteFromContext('PAYVECTOR_SAVED_CARD');
		Configuration::deleteFromContext('PAYVECTOR_3DS_ON_CROSS_REFERENCE');
		Configuration::deleteByName('PAYVECTOR_ENTRY_POINTS');
		Configuration::deleteByName('PAYVECTOR_ENTRY_POINTS_MODIFIED');

		return true;
	}

	public function getContent()
	{
		$output = '';

		if (Tools::isSubmit('submit'.$this->name))
		{
			Configuration::updateValue('PAYVECTOR_TEST_MODE', Tools::getValue('PAYVECTOR_TEST_MODE'));
			Configuration::updateValue('PAYVECTOR_TEST_MERCHANT_ID', Tools::getValue('PAYVECTOR_TEST_MERCHANT_ID'));
			Configuration::updateValue('PAYVECTOR_TEST_MERCHANT_PASSWORD', Tools::getValue('PAYVECTOR_TEST_MERCHANT_PASSWORD'));
			Configuration::updateValue('PAYVECTOR_MERCHANT_ID', Tools::getValue('PAYVECTOR_MERCHANT_ID'));
			Configuration::updateValue('PAYVECTOR_MERCHANT_PASSWORD', Tools::getValue('PAYVECTOR_MERCHANT_PASSWORD'));
			Configuration::updateValue('PAYVECTOR_CAPTURE_METHOD', Tools::getValue('PAYVECTOR_CAPTURE_METHOD'));
			Configuration::updateValue('PAYVECTOR_PRE_SHARED_KEY', Tools::getValue('PAYVECTOR_PRE_SHARED_KEY'));
			Configuration::updateValue('PAYVECTOR_HASH_METHOD', Tools::getValue('PAYVECTOR_HASH_METHOD'));
			Configuration::updateValue('PAYVECTOR_RESULT_DELIVERY_METHOD', Tools::getValue('PAYVECTOR_RESULT_DELIVERY_METHOD'));
			Configuration::updateValue('PAYVECTOR_SAVED_CARD', Tools::getValue('PAYVECTOR_SAVED_CARD'));
			Configuration::updateValue('PAYVECTOR_3DS_ON_CROSS_REFERENCE', Tools::getValue('PAYVECTOR_3DS_ON_CROSS_REFERENCE'));
			$output .= $this->displayConfirmation($this->l('Settings updated'));
		}

		return $output.$this->renderForm();
	}

	public function renderForm()
	{
		$default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

		$fields_form[0] = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Settings'),
					'icon' => 'icon-cogs'
				),
				'input' => array(
					array(
						'type' => 'select',
						'label' => $this->l('Operating Mode'),
						'name' => 'PAYVECTOR_TEST_MODE',
						'required' => true,
						'options' => array (
							'query' => array(
								array(
									'id_option' => 'true',
									'name' => $this->l('Test Mode')
								),
								array(
									'id_option' => 'false',
									'name' => $this->l('Live Mode')
								)
							),
							'id' => 'id_option',
							'name' => 'name'
						)
					),
					array(
						'type' => 'text',
						'label' => $this->l('Test Merchant ID'),
						'name' => 'PAYVECTOR_TEST_MERCHANT_ID',
						'required' => false
					),
					array(
						'type' => 'text',
						'label' => $this->l('Test Merchant Password'),
						'name' => 'PAYVECTOR_TEST_MERCHANT_PASSWORD',
						'required' => false
					),
					array(
						'type' => 'text',
						'label' => $this->l('Live Merchant ID'),
						'name' => 'PAYVECTOR_MERCHANT_ID',
						'required' => false
					),
					array(
						'type' => 'text',
						'label' => $this->l('Live Merchant Password'),
						'name' => 'PAYVECTOR_MERCHANT_PASSWORD',
						'required' => false
					),
					array(
						'type' => 'select',
						'label' => $this->l('Capture Method'),
						'name' => 'PAYVECTOR_CAPTURE_METHOD',
						'required' => true,
						'options' => array (
							'query' => array(
								array(
									'id_option' => 'Direct API',
									'name' => $this->l('Direct API')
								),
								array(
									'id_option' => 'Hosted Payment Form',
									'name' => $this->l('Hosted Payment Form')
								)
							),
							'id' => 'id_option',
							'name' => 'name'
						)
					),
					array(
						'type' => 'text',
						'label' => $this->l('Pre Shared Key'),
						'name' => 'PAYVECTOR_PRE_SHARED_KEY',
						'required' => false
					),
					array(
						'type' => 'select',
						'label' => $this->l('Hash Method'),
						'name' => 'PAYVECTOR_HASH_METHOD',
						'required' => true,
						'options' => array (
							'query' => array(
								array(
									'id_option' => 'MD5',
									'name' => $this->l('MD5')
								),
								array(
									'id_option' => 'HMACMD5',
									'name' => $this->l('HMACMD5')
								),
								array(
									'id_option' => 'SHA1',
									'name' => $this->l('SHA1')
								),
								array(
									'id_option' => 'HMACSHA1',
									'name' => $this->l('HMACSHA1')
								)
							),
							'id' => 'id_option',
							'name' => 'name'
						)
					),
					array(
						'type' => 'select',
						'label' => $this->l('Result Delivery Method'),
						'name' => 'PAYVECTOR_RESULT_DELIVERY_METHOD',
						'required' => true,
						'options' => array (
							'query' => array(
								array(
									'id_option' => 'POST',
									'name' => $this->l('POST')
								),
								array(
									'id_option' => 'SERVER_PULL',
									'name' => $this->l('SERVER_PULL')
								)
							),
							'id' => 'id_option',
							'name' => 'name'
						)
					),
					array(
						'type' => 'select',
						'label' => $this->l('Saved Card Functionality'),
						'name' => 'PAYVECTOR_SAVED_CARD',
						'required' => true,
						'options' => array (
							'query' => array(
								array(
									'id_option' => 'true',
									'name' => $this->l('Enable')
								),
								array(
									'id_option' => 'false',
									'name' => $this->l('Disable')
								)
							),
							'id' => 'id_option',
							'name' => 'name'
						)
					),
					array(
						'type' => 'select',
						'label' => $this->l('3DSecure on Cross Reference Transactions'),
						'name' => 'PAYVECTOR_3DS_ON_CROSS_REFERENCE',
						'required' => true,
						'options' => array (
							'query' => array(
								array(
									'id_option' => 'true',
									'name' => $this->l('Enable')
								),
								array(
									'id_option' => 'false',
									'name' => $this->l('Disable')
								)
							),
							'id' => 'id_option',
							'name' => 'name'
						)
					)
				),
				'submit' => array(
					'title' => $this->l('Save'),
					'class' => 'button'
				)
			)
		);

		$helper = new HelperForm();

		// Module, token and currentIndex
		$helper->module = $this;
		$helper->name_controller = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

		// Language
		$helper->default_form_language = $default_lang;
		$helper->allow_employee_form_lang = $default_lang;

		// Title and toolbar
		$helper->title = $this->displayName;
		$helper->show_toolbar = true;        // false -> remove toolbar
		$helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
		$helper->submit_action = 'submit'.$this->name;
		$helper->toolbar_btn = array(
			'save' =>
				array(
					'desc' => $this->l('Save'),
					'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
						'&token='.Tools::getAdminTokenLite('AdminModules'),
				),
			'back' => array(
				'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
				'desc' => $this->l('Back to list')
			)
		);

		// Load current value
		$helper->fields_value['PAYVECTOR_TEST_MODE'] = Configuration::get('PAYVECTOR_TEST_MODE');
		$helper->fields_value['PAYVECTOR_TEST_MERCHANT_ID'] = Configuration::get('PAYVECTOR_TEST_MERCHANT_ID');
		$helper->fields_value['PAYVECTOR_TEST_MERCHANT_PASSWORD'] = Configuration::get('PAYVECTOR_TEST_MERCHANT_PASSWORD');
		$helper->fields_value['PAYVECTOR_MERCHANT_ID'] = Configuration::get('PAYVECTOR_MERCHANT_ID');
		$helper->fields_value['PAYVECTOR_MERCHANT_PASSWORD'] = Configuration::get('PAYVECTOR_MERCHANT_PASSWORD');
		$helper->fields_value['PAYVECTOR_CAPTURE_METHOD'] = Configuration::get('PAYVECTOR_CAPTURE_METHOD');
		$helper->fields_value['PAYVECTOR_PRE_SHARED_KEY'] = Configuration::get('PAYVECTOR_PRE_SHARED_KEY');
		$helper->fields_value['PAYVECTOR_HASH_METHOD'] = Configuration::get('PAYVECTOR_HASH_METHOD');
		$helper->fields_value['PAYVECTOR_RESULT_DELIVERY_METHOD'] = Configuration::get('PAYVECTOR_RESULT_DELIVERY_METHOD');
		$helper->fields_value['PAYVECTOR_SAVED_CARD'] = Configuration::get('PAYVECTOR_SAVED_CARD');
		$helper->fields_value['PAYVECTOR_3DS_ON_CROSS_REFERENCE'] = Configuration::get('PAYVECTOR_3DS_ON_CROSS_REFERENCE');

		return $helper->generateForm($fields_form);
	}

	public function hookPayment($params)
	{
		$test_mode = Configuration::get('PAYVECTOR_TEST_MODE');

		//check if the test mode variable has been set, otherwise return early as the method is not configured
		if (empty($test_mode))
		{
			return;
		}
		else if ($test_mode === "true")
		{
			$merchant_id = Configuration::get('PAYVECTOR_TEST_MERCHANT_ID');
			$merchant_password = Configuration::get('PAYVECTOR_TEST_MERCHANT_PASSWORD');
		}
		else
		{
			$merchant_id = Configuration::get('PAYVECTOR_MERCHANT_ID');
			$merchant_password = Configuration::get('PAYVECTOR_MERCHANT_PASSWORD');
		}

		$year_now = date('y');
		$start_year = array();
		$start_year[] = "";
		$expiry_year = array();
		$expiry_year[] = "";
		for($y = $year_now - 9; $y <= $year_now; $y++)
		{
			$start_year[] = sprintf('%02d', $y);
		}
		for($y = $year_now; $y < $year_now + 10; $y++)
		{
			$expiry_year[] = sprintf('%02d', $y);
		}

		if(Configuration::get('PAYVECTOR_SAVED_CARD') === "true")
		{
			$cross_reference = new CrossReference();
			$cross_reference->loadFromCustomerID($this->context->cart->id_customer);
			if(isset($cross_reference->last_updated) && strtotime($cross_reference->last_updated) > strtotime('-365 days'))
			{
				$this->smarty->assign(array(
					'cross_reference' => $cross_reference->cross_reference,
					'card_last_four' => $cross_reference->card_last_four,
					'card_type' => $cross_reference->card_type,
				));
			}
		}

		$this->smarty->assign(array(
			'capture_method' => Configuration::get('PAYVECTOR_CAPTURE_METHOD'),
			'start_year' => $start_year,
			'expiry_year' => $expiry_year
		));

		if (_PS_VERSION_ < '1.6')
		{
			return $this->display(__FILE__, 'payment_1_5.tpl');
		}
		else
		{
			return $this->display(__FILE__, 'payment.tpl');
		}
	}

	

	public function hookPaymentReturn($params)
	{
		
		try
		{
			//get order details
			$_GET['ajax'] = 'true';
			/* @var OrderDetailControllerCore|FrontControllerCore $controller */
			$controller = Controller::getController('OrderDetailController');
			$controller::$initialized = false;
			ob_start();
			$controller->run();
			$order_details = ob_get_clean();

		}
		catch (PrestaShopException $e)
		{
			$e->displayMessage();
		}

		$this->smarty->assign(array(
			'order_details' => $order_details,
			'payment_message' => Tools::getValue('payment_message', '')
		));

		if (_PS_VERSION_ < '1.6')
		{
			return $this->display(__FILE__, 'payment_complete_1_5.tpl');
		}
		else
		{
			return $this->display(__FILE__, 'payment_complete.tpl');
		}
	}
}