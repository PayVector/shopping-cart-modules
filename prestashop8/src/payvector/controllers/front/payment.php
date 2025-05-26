<?php

class PayVectorPaymentModuleFrontController extends ModuleFrontController
{
	
	public function __construct()
	{
		parent::__construct();		
		$this->sessionHandler = new NativeSessionHandler();
		
		$this->sessionHandler->initialiseSession();
		
		$this->paymentProcessorDomain = "payvector.net";
		$this->paymentProcessorHPFDomain = "https://mms.".$this->paymentProcessorDomain."/";
		$this->hostedPaymentFormURL = $this->paymentProcessorHPFDomain."Pages/PublicPages/PaymentForm.aspx";
		$this->transparentRedirectURL = $this->paymentProcessorHPFDomain."Pages/PublicPages/TransparentRedirect.aspx";
		$this->hostedPaymentFormHandlerURL = $this->paymentProcessorHPFDomain."Pages/PublicPages/PaymentFormResultHandler.ashx";
	}
	
	public function postProcess()
	{
	
		if (Tools::isSubmit('payvector_cc_number'))
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

			$transaction_processor = new TransactionProcessor();
			$transaction_processor->setMerchantID($merchant_id);
			$transaction_processor->setMerchantPassword($merchant_password);

			$cart = $this->context->cart;
			$billing_address = new Address((int)$cart->id_address_invoice);
			$billing_state = new State((int) $billing_address->id_state);
			$billing_country = new Country((int) $billing_address->id_country);
			

			//Convert cart total to minor currency and convert country/currency shorts to ISO codes
			$iso_currency_code = null;
			$iso_country_code = null;
			$currency_short = Currency::getCurrencyInstance((int)$cart->id_currency)->iso_code;
			$cart_total = $cart->getOrderTotal();
			$country_short = $billing_country->iso_code;
			$iso_currency_list = ISOHelper::getISOCurrencyList();
			if(!empty($currency_short) && $iso_currency_list->getISOCurrency($currency_short, $iso_currency))
			{
				/** @var $iso_currency ISOCurrency */
				$iso_currency_code = $iso_currency->getISOCode();
				//Always check to see if the cart already formats in minor currency
				$cart_total = (string) $cart_total;
				$cart_total = round($cart_total * ("1" . str_repeat(0, $iso_currency->getExponent())));
			}
			$iso_country_list = ISOHelper::getISOCountryList();
			if(!empty($country_short) && $iso_country_list->getISOCountry($country_short, $iso_country))
			{
				/** @var $iso_country ISOCountry */
				$iso_country_code = $iso_country->getISOCode();
			}

			$transaction_processor->setCurrencyCode($iso_currency_code);
			$transaction_processor->setAmount($cart_total);
			$transaction_processor->setOrderID($cart->id);
			$transaction_processor->setOrderDescription('PrestaShop cart id: ' . $cart->id);
			$transaction_processor->setCustomerName($billing_address->firstname . " " . $billing_address->lastname);
			$transaction_processor->setAddress1($billing_address->address1);
			$transaction_processor->setAddress2($billing_address->address2);
			$transaction_processor->setCity($billing_address->city);
			$transaction_processor->setState($billing_state->name);
			$transaction_processor->setPostcode($billing_address->postcode);
			$transaction_processor->setCountryCode($iso_country_code);
			$transaction_processor->setEmailAddress($this->context->customer->email);
			$transaction_processor->setPhoneNumber($billing_address->phone);
			$transaction_processor->setIPAddress($_SERVER['REMOTE_ADDR']);

			//3DSv2 Parameters							
			$transaction_processor->setJavaEnabled($_POST['browserjavaenabled']);
			$transaction_processor->setJavaScriptEnabled('true');
			$transaction_processor->setScreenWidth($_POST['browserscreenwidth']);
			$transaction_processor->setScreenHeight($_POST['browserscreenheight']);
			$transaction_processor->setScreenColourDepth($_POST['browsercolordepth']);
			$transaction_processor->setTimezoneOffset($_POST['browsertz']);				
			$transaction_processor->setLanguage($_POST['browserlanguage']);

			$ChURL = $this->context->link->getModuleLink('payvector', 'payment', array('action' => '3DSecureV2'));
			$ChURL = htmlspecialchars($ChURL, ENT_XML1, 'UTF-8');
			
			$transaction_processor->setChallengeNotificationURL($ChURL);				
			$transaction_processor->setFingerprintNotificationURL($ChURL);
			

			if(
				Tools::getValue('payvector_payment_type') === SaleType::CrossReferenceSale
				|| $configuration['PAYVECTOR_CAPTURE_METHOD'] === IntegrationMethod::DirectAPI
			)
			{
				$transaction_processor->setRgeplRequestGatewayEntryPointList($this->getEntryPointList());
				

				if(Tools::getValue('payvector_payment_type') === SaleType::NewSale)
				{
					$transaction_processor->setCV2(Tools::safeOutput($_POST['payvector_cc_cvv']));
					$final_transaction_result = $transaction_processor->doCardDetailsTransaction(
						Tools::safeOutput($_POST['payvector_cc_number']),
						Tools::safeOutput($_POST['payvector_cc_expiry']['month']),
						Tools::safeOutput($_POST['payvector_cc_expiry']['year']),
						Tools::safeOutput($_POST['payvector_issue_number'] ?? ''),
						$this->sessionHandler );
						
				}
				else
				{
					$transaction_processor->setCV2(Tools::safeOutput($_POST['payvector_cc_cvv2']));
					$cross_reference = new CrossReference();
					$cross_reference->loadFromCustomerID($this->context->cart->id_customer);										

					$final_transaction_result = $transaction_processor->doCrossReferenceTransaction(
						$cross_reference->cross_reference,
						$configuration['PAYVECTOR_3DS_ON_CROSS_REFERENCE'] === "true",
						$this->sessionHandler);
				}
			}
			else
			{
				
				//run HPF
				$callback_url = $this->context->link->getModuleLink('payvector', 'payment', array('IntegrationMethod' => IntegrationMethod::HostedPaymentForm));
				$pre_shared_key = $configuration['PAYVECTOR_PRE_SHARED_KEY'];
				$hash_method = $configuration['PAYVECTOR_HASH_METHOD'];
				$result_delivery_method = $configuration['PAYVECTOR_RESULT_DELIVERY_METHOD'];

				$view_array = $transaction_processor->getHostedPaymentForm(
					$callback_url,
					$callback_url,
					$pre_shared_key,
					$hash_method,
					$result_delivery_method,
					false,
					$this->sessionHandler);

					
				/** @var string $PaymentProcessorDomain */
				$this->context->smarty->assign(array(
					'hosted_payment_form_url' => $this->hostedPaymentFormURL,
					'view_array' => $view_array
				));

				if (_PS_VERSION_ < '1.6')
				{
					$this->setTemplate('HPF_1_5.tpl');
				}
				else
				{
					$this->setTemplate('module:payvector/views/templates/front/HPF.tpl');
				}
				return;
			}
			
			return $this->handleTransactionResults($final_transaction_result);
		}
		else if(Tools::getValue("IntegrationMethod", null) === IntegrationMethod::HostedPaymentForm)
		{
			$hash_matches = false;
			$validate_error_message = "";

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
					'PAYVECTOR_RESULT_DELIVERY_METHOD'
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

			if($configuration['PAYVECTOR_RESULT_DELIVERY_METHOD'] === ResultDeliveryMethod::POST)
			{
				$hash_matches = PaymentFormHelper::validateTransactionResult_POST(
					$merchant_id,
					$merchant_password,
					$configuration['PAYVECTOR_PRE_SHARED_KEY'],
					$configuration['PAYVECTOR_HASH_METHOD'],
					$_POST,
					$transaction_result,
					$validate_error_message
				);
			}
			else if($configuration['PAYVECTOR_RESULT_DELIVERY_METHOD'] === ResultDeliveryMethod::SERVER_PULL)
			{
				$hash_matches = PaymentFormHelper::validateTransactionResult_SERVER_PULL(
					$merchant_id,
					$merchant_password,
					$configuration['PAYVECTOR_PRE_SHARED_KEY'],
					$configuration['PAYVECTOR_HASH_METHOD'],
					$_GET,
					$this->hostedPaymentFormHandlerURL,
					$transaction_result,
					$validate_error_message
				);
			}
			if(!$hash_matches)
			{
				echo $validate_error_message;
				exit;
			}
			
			return $this->handleTransactionResults(new HostedPaymentFormFinalTransactionResult($transaction_result));
		}
		else if(Tools::getValue("TransactionMethod", null) === TransactionMethod::ThreeDSecureTransaction)
		{
			$cross_reference = Tools::getValue("MD", null);
			$PaRES = Tools::getValue("PaRes", null);

			if (empty($cross_reference))
			{
				echo 'No MD received';
				return;
			}
			if (empty($PaRES))
			{
				echo 'No PaRes received';
				return;
			}
			
			$this->sessionHandler->setSessionValue ('payvector_PaRes',$PaRES );

			// redirect url
			$response_URL = $this->context->link->getModuleLink('payvector', 'payment');

			echo $this->module->l('Please wait while your payment is processed...');
			echo '<br><br>';
			echo '<form method="POST" id="form_3d_breakout" name="form_auth3d" target="_top" action="' . $response_URL . '">';
			echo '<input type="hidden" name="3DSecure_breakout" value="true" />';
			echo '<input type="hidden" name="MD" value="' . $cross_reference . '" />';
			echo '<noscript>';
			echo '<input type="submit" name="' . $this->module->l('Javascript is not enabled on your browser, please click to continue') . '">';
			echo '</noscript>';
			echo '</form>';

			echo '<script>';
			echo 'var frm = document.getElementById("form_3d_breakout");';
			echo 'frm.submit();';
			echo '</script>';

			exit;
		}
		else if(Tools::getValue('3DSecure_breakout', null) === "true")
		{
			$cross_reference = Tools::getValue('MD', null);
			if(!isset($cross_reference))
			{
				echo 'No MD received';
				return false;
			}
			$payvector_PaRes = $this->sessionHandler->getSessionValue('payvector_PaRes');

			if(!isset($payvector_PaRes))
			{
				echo 'No PaRes received';
				return false;
			}

			$configuration = Configuration::getMultiple(
				array(
					'PAYVECTOR_TEST_MODE',
					'PAYVECTOR_TEST_MERCHANT_ID',
					'PAYVECTOR_TEST_MERCHANT_PASSWORD',
					'PAYVECTOR_MERCHANT_ID',
					'PAYVECTOR_MERCHANT_PASSWORD',
					'PAYVECTOR_ENTRY_POINTS',
					'PAYVECTOR_ENTRY_POINTS_MODIFIED'
				));
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

			$transactionProcessor = new TransactionProcessor();
			$transactionProcessor->setMerchantID($merchant_id);
			$transactionProcessor->setMerchantPassword($merchant_password);
			$transactionProcessor->setRgeplRequestGatewayEntryPointList($this->getEntryPointList());
			$payvector_PaRes = $this->sessionHandler->getSessionValue('payvector_PaRes');

			$final_transaction_result = $transactionProcessor->check3DSecureResult($cross_reference, $payvector_PaRes);

			$this->handleTransactionResults($final_transaction_result);
			$sessionHandler->unsetSessionValue('payvector_PaRes');
		}
		else if(Tools::getValue("action", null) === "3DSecureV2"){
			
				$cres = $_REQUEST['cres'] ?? '';
				$FormMode = $_REQUEST['FormMode'] ?? 'NEW';
				$ThreeDSMethodData = $_REQUEST['threeDSMethodData'] ?? '';

				$configuration = Configuration::getMultiple(
				array(
					'PAYVECTOR_TEST_MODE',
					'PAYVECTOR_TEST_MERCHANT_ID',
					'PAYVECTOR_TEST_MERCHANT_PASSWORD',
					'PAYVECTOR_MERCHANT_ID',
					'PAYVECTOR_MERCHANT_PASSWORD',
					'PAYVECTOR_ENTRY_POINTS',
					'PAYVECTOR_ENTRY_POINTS_MODIFIED'
				));
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

				if (empty($cres)){
				switch ($FormMode)
				{
					case "NEW":										
						$FormAttributes = " target=\"_parent\"";
						$FormAction = "";
						$params = ["threeDSMethodData"=>$ThreeDSMethodData, "FormMode"=> "STEP2"];	
						$this->context->smarty->assign(array(	
							'FormAttributes' => $FormAttributes,					
							'params' => $params,
							'FormAction' => $FormAction
					
						));
				
						$this->setTemplate('module:payvector/views/templates/front/3DSecure.tpl');	
						return;							
						exit;						
					break;
					case "STEP2":			
						
						$crossReference = $this->sessionHandler->getSessionValue('payvector_md');
						
						$tdseThreeDSecureEnvironment = new \net\thepaymentgateway\paymentsystem\ThreeDSecureEnvironment($this->getEntryPointList());
						$tdseThreeDSecureEnvironment->getMerchantAuthentication()->setMerchantID($merchant_id);
						$tdseThreeDSecureEnvironment->getMerchantAuthentication()->setPassword($merchant_password);
						$tdseThreeDSecureEnvironment->getThreeDSecureEnvironmentData()->setCrossReference($crossReference);
						$tdseThreeDSecureEnvironment->getThreeDSecureEnvironmentData()->setMethodData($ThreeDSMethodData);
						$boTransactionProcessed = $tdseThreeDSecureEnvironment->processTransaction($tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData);
						
						if($tdsarThreeDSecureAuthenticationResult->getStatusCode() === 3){			
							$CREQ = $todTransactionOutputData->getThreeDSecureOutputData()->getCREQ();
							$ThreeDSSessionData = PaymentFormHelper::base64UrlEncode($todTransactionOutputData->getCrossReference());
							$FormAttributes = " target=\"threeDSecureFrame\"";
							$FormAction = $todTransactionOutputData->getThreeDSecureOutputData()->getACSURL();
							$params = ['creq' => $CREQ, 'threeDSSessionData' => $ThreeDSSessionData];
							
							$this->context->smarty->assign(array(	
							'FormAttributes' => $FormAttributes,					
							'params' => $params,
							'FormAction' => $FormAction
					
						));
				
							$this->setTemplate('module:payvector/views/templates/front/3DSecureLandingForm.tpl');	
							return;	
							exit;
						}
						else {							
							$finalTransactionResult = new ThreeDSecureFinalTransactionResult($boTransactionProcessed, $tdseThreeDSecureEnvironment, $tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData, $this->sessionHandler);
							return $this->handleTransactionResults($finalTransactionResult);
							exit;

						}
										
						exit;
					break;
				}
			}
			else {		
				$threeDSSessionData =$_REQUEST['threeDSSessionData'] ?? ''; 
				
				switch ($FormMode)
				{
					case "NEW":		
						$FormAttributes = " target=\"_parent\"";
						$FormAction = "";
						$params = ["cres"=>$cres,"threeDSSessionData"=>$threeDSSessionData, "FormMode"=> "STEP3"];
						$this->context->smarty->assign(array(	
							'FormAttributes' => $FormAttributes,					
							'params' => $params,
							'FormAction' => $FormAction
					
						));
				
						$this->setTemplate('module:payvector/views/templates/front/3DSecure.tpl');	
						return;						
					break;
					case "STEP3":
						
						$CrossReference = PaymentFormHelper::base64UrlDecode($threeDSSessionData);
						
						$tdsaThreeDSecureAuthentication = new \net\thepaymentgateway\paymentsystem\ThreeDSecureAuthentication($this->getEntryPointList());
						$tdsaThreeDSecureAuthentication->getMerchantAuthentication()->setMerchantID($merchant_id);
						$tdsaThreeDSecureAuthentication->getMerchantAuthentication()->setPassword($merchant_password);
						$tdsaThreeDSecureAuthentication->getThreeDSecureInputData()->setCrossReference($CrossReference);
						$tdsaThreeDSecureAuthentication->getThreeDSecureInputData()->setCRES($cres);
						$boTransactionProcessed = $tdsaThreeDSecureAuthentication->processTransaction($tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData);	
						$finalTransactionResult = new ThreeDSecureFinalTransactionResult($boTransactionProcessed, $tdsaThreeDSecureAuthentication, $tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData, $this->sessionHandler);							
						
											

						return $this->handleTransactionResults($finalTransactionResult);						
					break;

				}
			}
			exit;
		}
	}

	/**
	 * @param  FinalTransactionResult $final_transaction_result An implementation of FinalTransactionResult
	 * @return array                                            If still on the checkout page then an array is returned, otherwise causes a redirect
	 */
	private function handleTransactionResults($final_transaction_result)
	{		
		$transCardData = [];
		if($final_transaction_result->transactionProcessed() && $final_transaction_result->transactionSuccessful())
		{
			
			//save gateway entry point list to the database (Direct/API only)
			$gatewayEntryPointList = $final_transaction_result->getGatewayEntryPointList();
			if(isset($gatewayEntryPointList))
			{
				Configuration::updateGlobalValue('PAYVECTOR_ENTRY_POINTS', $gatewayEntryPointList->toXmlString(), true);
				Configuration::updateGlobalValue('PAYVECTOR_ENTRY_POINTS_MODIFIED', date('Y-m-d H:i:s P'));
			}

			//save cross reference against the user
			$cross_reference = new CrossReference();
			$cross_reference->loadFromCustomerID($this->context->cart->id_customer);
			$cross_reference->id_customer = $this->context->cart->id_customer;
			$cross_reference->cross_reference = $final_transaction_result->getCrossReference();
			$cross_reference->card_type = $final_transaction_result->getCardType();
			$card_last_four = $final_transaction_result->getCardLastFour($this->sessionHandler);
			$cross_reference->card_last_four = $final_transaction_result->getCardLastFour($this->sessionHandler);
			$cross_reference->last_updated = date('Y-m-d H:i:s P');
			if($cross_reference->id !== null)
			{
				$cross_reference->update();
			}
			else
			{
				$cross_reference->add();
			}

			$crossReference = $final_transaction_result->getCrossReference();
			$transCardData =  [
        		'transaction_id'   => $crossReference,
        		'card_number'      => $card_last_four,
        		'card_brand'       => $final_transaction_result->getCardType(),
   			];

			$amount_received = $final_transaction_result->getAmountReceived()->getValue();
			
			$currency_short = Currency::getCurrencyInstance((int)$this->context->cart->id_currency)->iso_code;
			$iso_currency_list = ISOHelper::getISOCurrencyList();
			if(!empty($currency_short) && $iso_currency_list->getISOCurrency($currency_short, $iso_currency))
			{
				/** @var $iso_currency ISOCurrency */
				$iso_currency_code = $iso_currency->getISOCode();
				//Always check to see if the cart already formats in minor currency
				if($iso_currency->getExponent() != 0 && isset($amount_received))
				{
					$amount_received = round($amount_received / pow(10, $iso_currency->getExponent()), $iso_currency->getExponent());
				}
			}
			
			$order_state = Configuration::get('PS_OS_PAYMENT');

		}
		else
		{
			
			//run 3DSecure if required
			if($final_transaction_result->getStatusCode() === 3)
			{
				
				$this->sessionHandler->setSessionValue ('payvector_md', $final_transaction_result->getCrossReference() );
				$this->sessionHandler->setSessionValue('payvector_MethodURL', $final_transaction_result->getThreeDSecureOutputData()->getMethodURL() );
				$this->sessionHandler->setSessionValue('payvector_ThreeDSMethodData', $final_transaction_result->getThreeDSecureOutputData()->getMethodData());

				$FormAttributes = " target=\"threeDSecureFrame\"";
				$params =  ["ThreeDSMethodData"=>$final_transaction_result->getThreeDSecureOutputData()->getMethodData()];

				$this->context->smarty->assign(array(	
					'FormAttributes' => $FormAttributes,					
					'params' => $params,
					'FormAction' => $final_transaction_result->getThreeDSecureOutputData()->getMethodURL()					
				));

				//$this->setTemplate('3DSecure.tpl');
				$this->setTemplate('module:payvector/views/templates/front/3DSecureLandingForm.tpl');
				
				return;
			}

			$amount_received = 0;
			$order_state = Configuration::get('PS_OS_ERROR');
		}
		
		$this->module->validateOrder(
			$this->context->cart->id,
			$order_state,
			$amount_received,
			$this->module->displayName,
			$final_transaction_result->getMessage(),
			$transCardData,
			(int)$this->context->cart->id_currency,
			false,
			$this->context->customer->secure_key
		);

		$url = 'index.php?controller=order-confirmation&';
		if (_PS_VERSION_ < '1.5')
		{
			$url = 'order-confirmation.php?';
		}

		$url .= 'id_module='.(int)$this->module->id.
			'&id_cart='.(int)$this->context->cart->id.
			'&key='.$this->context->customer->secure_key.
			'&id_order='.$this->module->currentOrder.
			'&payment_message='.urlencode('Transaction Result: ' . $final_transaction_result->getUserFriendlyMessage());

			$this->sessionHandler->unsetSessionValue('payvector_md');
			$this->sessionHandler->unsetSessionValue('payvector_MethodURL');	
		Tools::redirect($url);
	}


	/**
	 * Checks the database for a recent gateway entry point list and returns it if found, otherwise returns a blind list
	 * @return RequestGatewayEntryPointList
	 */
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
}