<?php

/*
 * Product: PayVector Payment Gateway for VirtueMart
 * Version: 1.0.0
 * Release Date: 2014.02.03
 *
 * Copyright (C) 2014 PayVector <support@payvector.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('_VALID_MOS') && !defined('_JEXEC'))
{
	die('Direct Access to ' . basename(__FILE__) . ' is not allowed.');
}

/**
 * @version $Id: payvector.php,v 1.0 2014.01.28
 */
jimport('joomla.error.log');
if (!class_exists('Creditcard'))
{
	require_once (JPATH_VM_ADMINISTRATOR . '/helpers/creditcard.php');
}
if (!class_exists('vmPSPlugin'))
{
	require (JPATH_VM_PLUGINS . '/vmpsplugin.php');
}
require __DIR__ . '/PayVector-Library/TransactionProcessor.php';


class plgVmPaymentPayVector extends vmPSPlugin
{

	/**
	 * Public instance of the class (used by Joomla/VirtueMart)
	 * @var boolean
	 */
	public static $_this = false;

	/**
	 * Version Number
	 * @var float
	 */
	public static $versionNumber = "1.0.0";

	/**
	 * Object containing the credit card data stored in the session
	 * @var stdClass
	 */
	private $creditCard;

	/**
	 * Object containing the credit card data stored in the session
	 * @var CardDetails
	 */
	private $cardDetails;

	/**
	 * Gateway Token
	 * @var string
	 */
	private $crossReference;

	/**
	 * Last four digits of the card number that was originally used to generate the crossReference
	 * @var string
	 */
	private $cardLastFour;

	/**
	 * Type of card (VISA/MASTERCARD etc.)
	 * @var string
	 */
	private $cardType;

	/**
	 * Whether the user is entering a used card or a saved card
	 * @var SaleType
	 */
	private $paymentType;

	/**
	 * Object containing the credit card data stored in the session
	 * @var VirtueMartCart
	 */
	private $lastCart;

	/**
	 * Display name of the module for use in the admin section of the site
	 * @var string
	 */
	public static $adminDisplayName = 'PayVector Payment Module';

	/*
	 * Display name of the module for use in the frontend section of the site
	 * @var string
	 */
	private $displayName = "PayVector";

	/**
	 * Name of the module
	 * @var string
	 */
	private $paymentElement = 'payvector';
	
	/**
	 * NAME OF THIS MODULE
	 * @var string
	 */
	private $paymentElementUppercase = 'PAYVECTOR';

	/**
	 * URL for SERVER_PULL method
	 * @var string
	 */
	private $paymentFormResultHandlerURL = "https://mms.payvector.net/Pages/PublicPages/PaymentFormResultHandler.ashx";

	public function __construct(&$subject, $config)
	{
		if (func_num_args() == 0)
		{
			return;
		}
		parent::__construct($subject, $config);

		$this->_loggable = true;
		$this->tableFields = array_keys($this->getTableSQLFields());
		$varsToPush = $this->getVarsToPush();
		$this->setConfigParameterable($this->_configTableFieldName, $varsToPush);
	}

	protected function getVmPluginCreateTableSQL()
	{
		return $this->createTableSQL('Payment Iridium Table');
	}

	/**
	 * Fields to create the payment table
	 * @return string SQL Fields
	 */
	public function getTableSQLFields()
	{
		$SQLfields = array('id' => 'int(11) UNSIGNED NOT NULL AUTO_INCREMENT', 'virtuemart_order_id' => 'int(11) UNSIGNED DEFAULT NULL', 'order_number' => 'char(32) DEFAULT NULL', 'virtuemart_paymentmethod_id' => 'mediumint(1) UNSIGNED DEFAULT NULL', 'payment_name' => 'varchar(1000) NOT NULL DEFAULT \'\' ', 'payment_order_total' => 'decimal(15,5) NOT NULL DEFAULT \'0.00000\' ', 'payment_currency' => 'char(3) ', 'cost_per_transaction' => ' decimal(10,2) DEFAULT NULL ', 'cost_percent_total' => ' decimal(10,2) DEFAULT NULL ', 'tax_id' => 'smallint(11) DEFAULT NULL', 'result' => 'varchar(255) DEFAULT NULL', 'card_last_four' => 'varchar(4) DEFAULT NULL', 'cross_reference' => 'varchar(24) DEFAULT NULL', 'status_code' => 'int(3) UNSIGNED DEFAULT NULL', 'message' => 'varchar(255) DEFAULT NULL', 'error_details' => 'varchar(512) DEFAULT NULL');

		return $SQLfields;
	}

	
	public function plgVmOnStoreInstallPaymentPluginTable($jplugin_id)
	{
		return $this->onStoreInstallPluginTable($jplugin_id);
	}

	/**
	 * Called once the user confirms their order, checks to make sure the order is not a duplicate then processes the payment
	 * @param  VirtueMartCart $cart  VirtueMartCart object containing the cart details
	 * @param  array          $order Array of stdClass objects containing details of the order
	 * @return bool                  TRUE on success, FALSE on failure, NULL when this plugin was not selected
	 */
	public function plgVmConfirmedOrder(VirtueMartCart $cart, array $order)
	{
		if (!($method = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id)))
		{
			return null;
			// Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($method->payment_element))
		{
			return false;
		}

		$db = JFactory::getDBO();

		// get the card
		$this->getCardFromSession();
		$this->retrieveCardDetails($order['details']['BT']->virtuemart_paymentmethod_id);
		$this->setCardIntoSession();

		


		$merchantAuthentication = $this->getMerchantAuthentication($method);
		$transactionProcessor = new TransactionProcessor();
		$transactionProcessor->setMerchantID($merchantAuthentication->getMerchantID());
		$transactionProcessor->setMerchantPassword($merchantAuthentication->getPassword());

		// convert
		$converted = $this->getConverted($method, $order);

		// Store data to the plugin table
		$this->_virtuemart_paymentmethod_id = $order['details']['BT']->virtuemart_paymentmethod_id;
		$dbValues['payment_name'] = $this->renderPluginName($method);
		$dbValues['order_number'] = $order['details']['BT']->order_number;
		$dbValues['virtuemart_paymentmethod_id'] = $this->_virtuemart_paymentmethod_id;
		$dbValues['cost_per_transaction'] = $method->cost_per_transaction;
		$dbValues['cost_percent_total'] = $method->cost_percent_total;
		$dbValues['payment_currency'] = $converted['code3'];
		$dbValues['payment_order_total'] = $converted['total'];
		$dbValues['tax_id'] = $method->tax_id;
		$this->storePSPluginInternalData($dbValues);

		$isoCurrencyCode = null;
		$isoCountryCode = null;
		$cartTotal = $converted['total'];
		$orderNumber = $order['details']['BT']->order_number;
		$orderID = $order['details']['BT']->virtuemart_order_id;
		$orderDescription = "[VM] Order Number: " . $orderNumber;
		$addressBT = $order['details']['BT'];

		//$billingDetails = $this->retrieveBillingDetails($order);
		//$this->retrieveCardDetails($order);

		//convert cart total into base currency and change currency code/country short into ISO codes
		$iclISOCurrencyList = ISOHelper::getISOCurrencyList();
		/** @var $icISOCurrency ISOCurrency */
		if($iclISOCurrencyList->getISOCurrency($converted['code3'], $icISOCurrency))
		{
			$isoCurrencyCode = $icISOCurrency->getISOCode();
			//Always check to see if the cart already formats in minor currency
			$cartTotal = round($converted['total'] * ("1" . str_repeat(0, $icISOCurrency->getExponent())));
		}
		//$iclISOCountryList = ISOHelper::getISOCountryList();
		/** @var $icISOCountry ISOCountry */
		//if($iclISOCountryList->getISOCountry($addressBT->billingcountry, $icISOCountry))
		//{
		//	$isoCountryCode = $icISOCountry->getISOCode();
		//}

		$billingCountryISOCode = ShopFunctions::getCountryByID($addressBT->virtuemart_country_id, 'country_num_code');		
		
		// State code for US customers only
		if (isset($addressBT->virtuemart_state_id))
		{
			$billingState = ShopFunctions::getStateByID($addressBT->virtuemart_state_id);
		}
		else
		{
			$billingState = '';
		}

		$transactionProcessor->setCurrencyCode($isoCurrencyCode);
		$transactionProcessor->setAmount($cartTotal);
		$transactionProcessor->setOrderID($orderID);
		$transactionProcessor->setOrderDescription($orderDescription);
		$transactionProcessor->setCustomerName($this->cardDetails->getCardName());
		$transactionProcessor->setAddress1($addressBT->address_1);
		$transactionProcessor->setAddress2($addressBT->address_2);
		$transactionProcessor->setCity($addressBT->city);
		$transactionProcessor->setState($billingState);
		$transactionProcessor->setPostcode($addressBT->zip);
		$transactionProcessor->setCountryCode($billingCountryISOCode);
		$transactionProcessor->setEmailAddress($addressBT->email);
		$transactionProcessor->setPhoneNumber($addressBT->phone_1);
		$transactionProcessor->setIPAddress($_SERVER['REMOTE_ADDR']);
		$transactionProcessor->setTransactionType(TransactionType::Sale);
		

		if($method->capture_method === IntegrationMethod::HostedPaymentForm && $this->paymentType === SaleType::NewSale)
		{
			$callbackURL = JURI::base() . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginresponsereceived&pm=';
			$callbackURL .= $order['details']['BT']->virtuemart_paymentmethod_id;
			$callbackURL .= '&oid=' . $order['details']['BT']->order_number;
			$callbackURL .= '&result_delivery_method=' . $method->result_delivery_method;

			$preSharedKey = $method->pre_shared_key;
			$hashMethod = $method->hash_method;
			$resultDeliveryMethod = $method->result_delivery_method;

			$viewArray = $transactionProcessor->getHostedPaymentForm(
				$callbackURL,
				$callbackURL,
				$preSharedKey,
				$hashMethod,
				$resultDeliveryMethod
			);

			include __DIR__ . "/PayVector-Library/Config.php";
			//print HPF form using values from the viewArray			
			require __DIR__ . "/Templates/HPF.tpl";
			exit;
		}
		//
		else
		{
			$transactionProcessor->setRgeplRequestGatewayEntryPointList($this->getEntryPointList());
			$transactionProcessor->setCV2($this->cardDetails->getCV2());
			
			//3DSv2 Parameters
			$jInput = JFactory::getApplication()->input;				
			$transactionProcessor->setJavaEnabled($jInput->get('browserJavaEnabled', '','STR'));
			$transactionProcessor->setJavaScriptEnabled('true');
			$transactionProcessor->setScreenWidth($jInput->get('browserScreenWidth', '','STR'));
			$transactionProcessor->setScreenHeight($jInput->get('browserScreenHeight', '','STR'));
			$transactionProcessor->setScreenColourDepth($jInput->get('browserColorDepth', '','STR'));
			$transactionProcessor->setTimezoneOffset($jInput->get('browserTZ', '','STR'));				
			$transactionProcessor->setLanguage($jInput->get('browserLanguage', '','STR'));				

			
			$ChURL = JURI::root(false);
			$ChURL .= 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginresponsereceived';
			$ChURL .= '&pm=' . $method->virtuemart_paymentmethod_id;
			$ChURL .= '&oid=' . $orderNumber.'&PayVector3DV2=Y';
			$ChURL = htmlspecialchars($ChURL, ENT_XML1, 'UTF-8');
			$transactionProcessor->setChallengeNotificationURL($ChURL);				
			$transactionProcessor->setFingerprintNotificationURL($ChURL);

			if($this->paymentType === SaleType::NewSale)
			{
				$finalTransactionResult = $transactionProcessor->doCardDetailsTransaction(
					$this->cardDetails->getCardNumber(),
					$this->cardDetails->getExpiryDate()->getMonth()->getValue(),
					$this->cardDetails->getExpiryDate()->getYear()->getValue(),				
					$this->cardDetails->getIssueNumber()
				);
				
				
			}
			else
			{
				$finalTransactionResult = $transactionProcessor->doCrossReferenceTransaction($this->crossReference);
			}
		}
		

		$html = $this->handleTransactionResults($method, $order, $finalTransactionResult);
		vRequest::setVar ('html', $html);

		return true;
	}

	/**
	 * Called after 3D Secure has completed to redirect the page so that plgVmOnPaymentResponseReceived is called to interpret the response
	 * @return string True on successful 3D auth, false when unsuccessful
	 */
	function plgVmOnPaymentNotification()
	{
		$jInput = JFactory::getApplication()->input;

		// the payment itself should send the parameter needed.
		$virtuemartPaymentMethodID = $jInput->get('pm', 0, 'INT');

		if (!($method = $this->getVmPluginMethod($virtuemartPaymentMethodID)))
		{
			return null;
			// Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($method->payment_element))
		{
			return false;
		}

		// have we received 3d authentication results?
		$orderNumber = $jInput->get('oid', null, 'ALNUM');
		$crossReference = $jInput->get('MD', null, 'ALNUM');
		$PaRES = $jInput->get('PaRes', null, 'BASE64');

		if (empty($orderNumber))
		{
			echo 'No order number received';
			return false;
		}
		if (empty($crossReference))
		{
			echo 'No MD received';
			return false;
		}
		if (empty($PaRES))
		{
			echo 'No PaRes received';
			return false;
		}

		$_SESSION['payvector_MD'] = $crossReference;
		$_SESSION['payvector_PaRes'] = $PaRES;

		// redirect url
		$responseURL = JURI::root(false);
		$responseURL .= 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginresponsereceived';
		$responseURL .= '&pm=' . $method->virtuemart_paymentmethod_id;
		$responseURL .= '&oid=' . $orderNumber;

		// break out of the iframe
		$js = '';
		$link = '';
		if ($method->debug)
		{
			$link = 'Click to continue: <a target="_top" href="' . $responseURL . '">' . $responseURL . '</a>';
		}
		else
		{
			$js = "window.parent.location.href='" . $responseURL . "';";
		}

		echo '<p>' . JText::_('VMPAYMENT_' . $this->paymentElementUppercase . '_3D_PLEASEWAIT') . '</p>';
		echo $link;
		echo '<script language="JavaScript">';
		echo $js;
		echo '</script>';

		return true;
	}

	/**
	 * [handleHPFCallback description]
	 * @param  string $html                           HTML string to be displayed to the user, passed by reference
	 * @param  stdClass $method                       Object containing the method details
	 * @param  Array $order                           Array containing the order details
	 * @return bool                                   True on success, false on failure
	 */
	public function handleHPFCallback(&$html, $method, $order)
	{
		/* @var TransactionResult $trTransactionResult */
		$hashMatches = false;
		$szValidateErrorMessage = "";
		$merchantAuthentication = $this->getMerchantAuthentication($method);

		if($method->result_delivery_method === ResultDeliveryMethod::POST)
		{
			$hashMatches = PaymentFormHelper::validateTransactionResult_POST(
				$merchantAuthentication->getMerchantID(),
				$merchantAuthentication->getPassword(),
				$method->pre_shared_key,
				$method->hash_method,
				$_POST,
				$trTransactionResult,
				$szValidateErrorMessage
			);
		}
		else if($method->result_delivery_method === ResultDeliveryMethod::SERVER_PULL)
		{
			$hashMatches = PaymentFormHelper::validateTransactionResult_SERVER_PULL(
				$merchantAuthentication->getMerchantID(),
				$merchantAuthentication->getPassword(),
				$method->pre_shared_key,
				$method->hash_method,
				$_GET,
				$this->paymentFormResultHandlerURL,
				$trTransactionResult,
				$szValidateErrorMessage
			);
		}
		if(!$hashMatches)
		{
			$html = $szValidateErrorMessage;
			return false;
		}
		$html = $this->handleTransactionResults($method, $order, new HostedPaymentFormFinalTransactionResult($trTransactionResult));

		return true;
	}

	/**
	 * Interprets the result of the 3DSecure Authentication and formats a response OR does the same for the HPF callback
	 * @param  string $html Output HTML N.B. this paramater is passed as a reference
	 * @return bool         True on successful 3DS Auth, false on failure
	 */
	function plgVmOnPaymentResponseReceived(&$html)
	{
		

		$jInput = JFactory::getApplication()->input;
		// the payment itself should send the parameter needed
		$virtuemartPaymentMethodID = $jInput->get('pm', 0, 'INT');

		if(!($method = $this->getVmPluginMethod($virtuemartPaymentMethodID)))
		{
			return null;
			// Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($method->payment_element))
		{
			return false;
		}

		$html = '';
		$orderNumber = $jInput->get('oid', '', 'ALNUM');
		if ($orderNumber == '')
		{
			$html .= 'No order number received';
			return false;
		}
		// get order details
		if (!class_exists('VirtueMartModelOrders'))
		{
			require (JPATH_VM_ADMINISTRATOR . '/models/orders.php');
		}
		$orderModel = new VirtueMartModelOrders();
		$virtuemartOrderID = $orderModel->getOrderIdByOrderNumber($orderNumber);
		$order = $orderModel->getOrder($virtuemartOrderID);

		$this->getCardFromSession();

		//handle HPF
		if($method->capture_method === IntegrationMethod::HostedPaymentForm && $this->paymentType !== SaleType::CrossReferenceSale)
		{
			if (!$this->handleHPFCallback($html, $method, $order))
			{
				return false;
			}
			return true;
		}
		$merchantAuthentication = $this->getMerchantAuthentication($method);
		
		$PayVectorV2 = $jInput->get('PayVector3DV2', '', 'STR');
		$cres = $jInput->get('cres', '', 'STR');
		$FormMode = $jInput->get('FormMode', 'NEW', 'STR');
		$ThreeDSMethodData = $jInput->get('threeDSMethodData', '', 'STR');
		
		if (!empty($PayVectorV2)){
			if (empty($cres)){
				switch ($FormMode)
				{
					case "NEW":						
						$FormAttributes = " target=\"_parent\"";
						$FormAction = "";
						$prams = ["threeDSMethodData"=>$ThreeDSMethodData, "FormMode"=> "STEP2"];
						$html = $this->showThreeDSV2($FormAttributes, $FormAction, $prams );	
						echo $html;
						exit;						
					break;
					case "STEP2":
						$crossReference = $_SESSION['payvector_MD'];												

						$tdseThreeDSecureEnvironment = new ThreeDSecureEnvironment($this->getEntryPointList());
						$tdseThreeDSecureEnvironment->getMerchantAuthentication()->setMerchantID($merchantAuthentication->getMerchantID());
						$tdseThreeDSecureEnvironment->getMerchantAuthentication()->setPassword($merchantAuthentication->getPassword());
						$tdseThreeDSecureEnvironment->getThreeDSecureEnvironmentData()->setCrossReference($crossReference);
						$tdseThreeDSecureEnvironment->getThreeDSecureEnvironmentData()->setMethodData($ThreeDSMethodData);
						$boTransactionProcessed = $tdseThreeDSecureEnvironment->processTransaction($tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData);	

						//$finalTransactionResult = new ThreeDSecureFinalTransactionResult($boTransactionProcessed, $tdseThreeDSecureEnvironment, $tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData);
						
						
						if($tdsarThreeDSecureAuthenticationResult->getStatusCode() === 3){			
							$CREQ = $todTransactionOutputData->getThreeDSecureOutputData()->getCREQ();
							$ThreeDSSessionData = PaymentFormHelper::base64UrlEncode($todTransactionOutputData->getCrossReference());
							$FormAttributes = " target=\"threeDSecureFrame\"";
							$FormAction = $todTransactionOutputData->getThreeDSecureOutputData()->getACSURL();
							$parms = ['creq' => $CREQ, 'threeDSSessionData' => $ThreeDSSessionData];							
							$html = $this->showThreeDSV2IFrame($FormAttributes, $FormAction, $parms );
							return true;	
						}
						else {
							$finalTransactionResult = new ThreeDSecureFinalTransactionResult($boTransactionProcessed, $tdseThreeDSecureEnvironment, $tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData);														
							$html = $this->handleTransactionResults($method, $order, $finalTransactionResult);								
							return true;	
						}
						
						
						//exit;
						//$html = $this->handleTransactionResults($method, $order, $finalTransactionResult);	
						return true;				
					break;
				}
			}
			else {		
				$threeDSSessionData = $jInput->get('threeDSSessionData', '', 'STR');
				
				switch ($FormMode)
				{
					case "NEW":		
						$FormAttributes = " target=\"_parent\"";
						$FormAction = "";
						$prams = ["cres"=>$cres,"threeDSSessionData"=>$threeDSSessionData, "FormMode"=> "STEP3"];
						$html = $this->showThreeDSV2($FormAttributes, $FormAction, $prams );	
						echo $html;
						exit;					
					break;
					case "STEP3":
						
						$CrossReference = PaymentFormHelper::base64UrlDecode($threeDSSessionData);
						$tdsaThreeDSecureAuthentication = new ThreeDSecureAuthentication($this->getEntryPointList());
						$tdsaThreeDSecureAuthentication->getMerchantAuthentication()->setMerchantID($merchantAuthentication->getMerchantID());
						$tdsaThreeDSecureAuthentication->getMerchantAuthentication()->setPassword($merchantAuthentication->getPassword());
						$tdsaThreeDSecureAuthentication->getThreeDSecureInputData()->setCrossReference($CrossReference);
						$tdsaThreeDSecureAuthentication->getThreeDSecureInputData()->setCRES($cres);
						$boTransactionProcessed = $tdsaThreeDSecureAuthentication->processTransaction($tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData);	
						$finalTransactionResult = new ThreeDSecureFinalTransactionResult($boTransactionProcessed, $tdsaThreeDSecureAuthentication, $tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData);							
						$html = $this->handleTransactionResults($method, $order, $finalTransactionResult);
						unset($_SESSION['payvector_MD']);
						unset($_SESSION['payvector_PaRes']);
								
						return true;
					break;

				}
			}
			exit;
			
		}

		
		return true;
	}

	/**
	 * @param  stdClass               $method
	 * @param  array                  $order
	 * @param  FinalTransactionResult $finalTransactionResult
	 * @return string
	 */
	private function handleTransactionResults($method, $order, $finalTransactionResult)
	{
		$db = JFactory::getDbo();
		$html = '';

		//Load language file based on joomla settings
		$language = JFactory::getLanguage();
		$language->load('com_virtuemart', JPATH_ADMINISTRATOR);

		if (!class_exists('VirtueMartModelCurrency'))
		{
			require (JPATH_VM_ADMINISTRATOR . '/helpers/currencydisplay.php');
		}
		$currency = CurrencyDisplay::getInstance('', $order['details']['BT']->virtuemart_vendor_id);


		if($finalTransactionResult->transactionProcessed() && $finalTransactionResult->transactionSuccessful())
		{
			$userID = JFactory::getUser()->get('id');

			//save gateway entry point list to the database (Direct/API only)
			$gatewayEntryPointList = $finalTransactionResult->getGatewayEntryPointList();
			if(isset($gatewayEntryPointList))
			{
				$gatewayEntryPointQuery = SQL_Queries::UPDATE_GatewayEntryPointObject($gatewayEntryPointList->toXmlString());
				$db->setQuery($gatewayEntryPointQuery);
				$db->execute(); 
			}

			// Checks if a cross reference exists in the database to see if we should perform an update or insert
			$crossReferenceResults = $this->crossReferenceExists($userID);
			

			//Save cross reference
			$crossReference = $finalTransactionResult->getCrossReference();
			$cardType = $finalTransactionResult->getCardType();
			$cardLastFour = $finalTransactionResult->getCardLastFour();

			if($crossReferenceResults !== false)
			{
				$crossReferenceQuery = SQL_Queries::UPDATE_CrossReference($userID, $crossReference, $cardLastFour, $cardType);
			}
			else
			{
				$crossReferenceQuery = SQL_Queries::INSERT_CrossReference($userID, $crossReference, $cardLastFour, $cardType);
			}
			$db->setQuery($crossReferenceQuery);
			$db->execute();

			if (!class_exists('VirtueMartCart'))
			{
				require (JPATH_VM_SITE . '/helpers/cart.php');
			}
			$cart = VirtueMartCart::getCart();

			$session = JFactory::getSession();
			$session->set('cardExpiryMonth_iridium', '', 'vm');
			$session->set('cardExpiryYear_iridium', '', 'vm');	
			// Get result table to display to the user
			ob_start();
			include (__DIR__ . '/Templates/TransactionSuccessful.tpl');
			$html = ob_get_clean();

			$this->processConfirmedOrderPaymentResponse(
				1,
				$cart,
				$order,
				$html,
				$this->renderPluginName($method),
				$method->payment_approved_status
			);
		}
		else
		{
			//run 3DSecure if required			
			if($finalTransactionResult->getStatusCode() === 3)
			{
				// 3d processing and redirect
				$_SESSION['payvector_MD'] = $finalTransactionResult->getCrossReference();	
				//$session = JFactory::getSession();	
				//$session->set('payvector_MD', $finalTransactionResult->getCrossReference(), 'vm');
				$session = JFactory::getSession();
				$session->set('cardExpiryMonth_iridium', '', 'vm');
				$session->set('cardExpiryYear_iridium', '', 'vm');	
				
				$html .= $this->showAuthenticationFrame($order['details']['BT'], $finalTransactionResult);
				return $html;
			}

			// Get result table to display to the user
			ob_start();
			include (JPATH_ROOT . '/plugins/vmpayment/payvector/Templates/TransactionUnsuccessful.tpl');
			$html = ob_get_clean();

			// Enqueue error message
			$errorMessage = JText::_('VMPAYMENT_' . $this->paymentElementUppercase . '_RESULT_DECLINED');
			$app = JFactory::getApplication();
			$app->enqueueMessage($errorMessage);
		}

		//clear out card details from the session
		$this->cardDetails = new CardDetails();
		$this->setCardIntoSession();

		return $html;
	}

	/*
	 * Format stored payment data for response to the user
	 * @param  int|string $virtuemart_order_id   VirtueMart order_id
	 * @param  int|string $virtuemart_payment_id VirtueMart payment_id
	 * @return string                            HTML string for user response on success, null if this module wasn't selected, false on failure
	 */
	function plgVmOnShowOrderBEPayment($virtuemartOrderID, $virtuemartPaymentID)
	{
		if (!$this->selectedThisByMethodId($virtuemartPaymentID))
		{
			return null;
			// Another method was selected, do nothing
		}

		if (!($paymentTable = $this->getDataByOrderId($virtuemartOrderID)))
		{
			return null;
		}

		$html = '<table class="adminlist">' . "\n";
		$html .= $this->getHtmlHeaderBE();
		$html .= $this->getHtmlRowBE('STANDARD_PAYMENT_NAME', $paymentTable->payment_name);
		$html .= $this->getHtmlRowBE('STANDARD_PAYMENT_TOTAL_CURRENCY', $paymentTable->payment_order_total . ' ' . $paymentTable->payment_currency);
		$html .= '</table>' . "\n";
		return $html;
	}

	/**
	 * Called when the payment method has been selected - creates a credit card object containing either card details or a CrossReference
	 * then saves it to the session
	 * @param  VirtueMartCart $cart VirtueMartCart object containing the cart details
	 * @return bool                 null if this module wasn't selected, true if the credit card data is valid, false if not
	 */
	function plgVmOnSelectCheckPayment(VirtueMartCart $cart)
	{
		if (!$this->selectedThisByMethodId($cart->virtuemart_paymentmethod_id))
		{
			return null;
			// Another method was selected, do nothing
		}
		if (!($method = $this->getVmPluginMethod($cart->virtuemart_paymentmethod_id)))
		{
			return FALSE;
		}

		$this->retrieveCardDetails($cart->virtuemart_paymentmethod_id);

		if(
			($method->capture_method === IntegrationMethod::HostedPaymentForm && $this->paymentType === SaleType::CrossReferenceSale && $this->validateCreditCard(false))
			|| $this->validateCreditCard(false)
		)
		{
			$this->setCardIntoSession();
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Called when the payment methods are shown in the cart, generates the HTML form showing stored details or taking in cc details
	 * @param  VirtueMartCart $cart     VirtueMartCart object containing the cart details
	 * @param  integer        $selected Payment method currently selected
	 * @param  array          $htmlIn   Array of html output, each value corresponds to a single plugin
	 * @return bool                     null if this module wasn't selected, true on success, false on failure
	 */
	function plgVmDisplayListFEPayment(VirtueMartCart $cart, $selected, &$htmlIn)
	{
		
		JHtml::_('bootstrap.tooltip');
		
		if ($this->getPluginMethods($cart->vendorId) === 0)
		{
			
			if (empty($this->_name))
			{
				$app = JFactory::getApplication();
				$app->enqueueMessage(JText::_('COM_VIRTUEMART_CART_NO_' . strtoupper($this->_psType)));
				return false;
			}
			else
			{
				return false;
			}
		}

		$method_name = $this->_psType . '_name';
		$user = JFactory::getUser();
		$htmla = array();

		foreach($this->methods as $method)
		{
			if($this->checkConditionsPv($cart, $method, $cart->pricesUnformatted))
			{				

				$methodSalesPrice = $this->calculateSalesPrice($cart, $method, $cart->pricesUnformatted);
				$method->$method_name = $this->renderPluginName($method);
				$html = $this->getPluginHtml($method, $selected, $methodSalesPrice);
				$jInput = JFactory::getApplication()->input;
				$PvCardName = $this->cardDetails->getCardName() ?? '';
				$PvExpMonth = '';
				$PvExpYear = '';
				$expiryDate = $this->cardDetails->getExpiryDate();
				
				
								
				if ($expiryDate->getMonth()->getHasValue()) $PvExpMonth = $expiryDate->getMonth()->getValue();
				if ($expiryDate->getYear()->getHasValue())  $PvExpYear = $expiryDate->getYear()->getValue();

				if($selected == $method->virtuemart_paymentmethod_id && $jInput->get('payment_type', null, 'ALNUM') !== null)
				{
					$this->retrieveCardDetails($method->virtuemart_paymentmethod_id);
				}
				else
				{
					$this->getCardFromSession();
				}

				// show message if in testmode
				$sandboxMessage = "";
				if($method->testmode != '0')
				{
					$sandboxMessage .= '<b>' . JText::_('VMPAYMENT_' . $this->paymentElementUppercase . '_SANDBOX_TEST_MSG') . '</b>';
				}

				// cardholdername
				$cardName = $this->cardDetails->getCardName();
				if (empty($cardName))
				{
					$this->cardDetails->setCardName($user->name);
				}

				$userID = $user->get('id');
				$crossReferenceResult = $this->crossReferenceExists($userID);
				$cvvImages = "<img src='" . JURI::root(false) . 'plugins/vmpayment/' . $this->paymentElement . "/assets/CVV.jpg' style='width:100%' />";

				ob_start();
				if($this->paymentType === SaleType::CrossReferenceSale && $method->capture_method !== IntegrationMethod::HostedPaymentForm)
				{
					include (JPATH_ROOT . '/plugins/vmpayment/payvector/Templates/CrossReferenceFound.tpl');
				}
				else if($crossReferenceResult != false)
				{
					$this->crossReference = $crossReferenceResult['cross_reference'];
					$this->cardLastFour = $crossReferenceResult['card_last_four'];
					$this->cardType = $crossReferenceResult['card_type'];
					if(empty($this->paymentType))
					{
						$this->paymentType = SaleType::CrossReferenceSale;
					}

					if ($method->capture_method === IntegrationMethod::DirectAPI)
					{
						include (JPATH_ROOT . '/plugins/vmpayment/payvector/Templates/CrossReferenceFound.tpl');
					}
					else if ($method->capture_method === IntegrationMethod::HostedPaymentForm)
					{
						include (JPATH_ROOT . '/plugins/vmpayment/payvector/Templates/CrossReferenceFoundHPF.tpl');
					}
				}
				else
				{
					if ($method->capture_method === IntegrationMethod::DirectAPI)
					{
						include (JPATH_ROOT . '/plugins/vmpayment/payvector/Templates/CrossReferenceNotFound.tpl');
					}
					else if ($method->capture_method === IntegrationMethod::HostedPaymentForm)
					{
						echo "<br />" . JText::_('VMPAYMENT_' . $this->paymentElementUppercase . '_NEW_CARD_HPF') . ' ' . $sandboxMessage . '<input id="HPF" type="hidden" name="capture_method" value="HPF">';
					}
				}
				$html .= ob_get_clean();

				$htmla[] = $html;

				if($selected == $method->virtuemart_paymentmethod_id && $this->validateCreditCard(true))
				{
					$this->setCardIntoSession();
				}

			} // if
		}// foreach
		$htmlIn[] = $htmla;


		return true;
	}

	/*
	 * plgVmonSelectedCalculatePricePayment
	 * Calculate the price (value, tax_id) of the selected method
	 * It is called by the calculator
	 * This function does NOT to be reimplemented. If not reimplemented, then the default values from this function are taken.
	 * @author Valerie Isaksen
	 * @cart: VirtueMartCart the current cart
	 * @cart_prices: array the new cart prices
	 * @return null if the method was not selected, false if the shiiping rate is not valid any more, true otherwise
	 *
	 *
	 */
	public function plgVmonSelectedCalculatePricePayment(VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name)
	{
		return $this->onSelectedCalculatePrice($cart, $cart_prices, $cart_prices_name);
	}

	function plgVmgetPaymentCurrency($virtuemartPaymentMethodID, &$paymentCurrencyId)
	{

		if (!($method = $this->getVmPluginMethod($virtuemartPaymentMethodID)))
		{
			return null;
			// Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($method->payment_element))
		{
			return false;
		}
		$this->getPaymentCurrency($method);

		$paymentCurrencyId = $method->payment_currency;
		//! $method->payment_currency might not be correct
	}

	/**
	 * plgVmOnCheckAutomaticSelectedPayment
	 * Checks how many plugins are available. If only one, the user will not have the choice. Enter edit_xxx page
	 * The plugin must check first if it is the correct type
	 * @author Valerie Isaksen
	 * @param VirtueMartCart cart: the cart object
	 * @return null if no plugin was found, 0 if more then one plugin was found,  virtuemart_xxx_id if only one plugin is found
	 *
	 */
	function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = array())
	{
		//return $this->onCheckAutomaticSelected($cart, $cart_prices);
		//return $this->onCheckAutomaticSelected($cart, $cart_prices, $paymentCounter);
		$return = $this->onCheckAutomaticSelected($cart, $cart_prices);
		//return $return;
		if (isset($return))
		{
			return 0;
		}
		else
		{
			return NULL;
		}
	}

	/**
	 * This method is fired when showing the order details in the frontend.
	 * It displays the method-specific data.
	 *
	 * @param integer $order_id The order ID
	 * @return mixed Null for methods that aren't active, text (HTML) otherwise
	 * @author Max Milbers
	 * @author Valerie Isaksen
	 */
	public function plgVmOnShowOrderFEPayment($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name)
	{
		$this->onShowOrderFE($virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name);
	}

	/**
	 * This method is fired when showing when priting an Order
	 * It displays the the payment method-specific data.
	 *
	 * @param integer $_virtuemart_order_id The order ID
	 * @param integer $method_id  method used for this order
	 * @return mixed Null when for payment methods that were not selected, text (HTML) otherwise
	 * @author Valerie Isaksen
	 */
	function plgVmonShowOrderPrintPayment($order_number, $method_id)
	{
		return $this->onShowOrderPrint($order_number, $method_id);
	}

	function plgVmDeclarePluginParamsPaymentVM3( &$data) {
		return $this->declarePluginParams('payment', $data);
	}

	function plgVmSetOnTablePluginParamsPayment($name, $id, &$table)
	{
		return $this->setOnTablePluginParams($name, $id, $table);
	}

	// -----------------------------------------------------------------------------
	// Method copied from example payment gateway file
	public function getCosts(VirtueMartCart $cart, $method, $cart_prices)
	{
		if (preg_match('/%$/', $method->cost_percent_total))
		{
			$cost_percent_total = substr($method->cost_percent_total, 0, -1);
		}
		else
		{
			$cost_percent_total = $method->cost_percent_total;
		}

		return ($method->cost_per_transaction + ($cart_prices['salesPrice'] * $cost_percent_total * 0.01));
	}

	/**
	 * Check if the payment conditions are fulfilled for this payment method
	 * @param  VirtueMartCart               $cart       VirtueMartCart object containing the cart details
	 * @param  TablePaymentmethods|stdClass $method     Object containing information on the order
	 * @param  array                        $cartPrices Array containing price details for the order
	 * @return bool                                     TRUE if the payment conditions are fulfilled, otherwise FALSE
	 */
	protected function checkConditionsPv(VirtueMartCart $cart, $method, array $cartPrices = null)
	{
		if (empty($cart->ST))
		{
			$address = $cart->BT;
		}
		else
		{
			$address = $cart->ST;
		}

		$amount = $cartPrices['salesPrice'];

		// If amount is less than the minimum or greater than the maximum (unless the maximum is 0 or unlimited) then the cart fails the payment conditions
		if ($amount < $method->min_amount || ($amount > $method->max_amount && $method->max_amount != 0))
		{
			if ($amount < $method->min_amount)
			{
				$app = JFactory::getApplication();
				$app->enqueueMessage(JText::sprintf('VMPAYMENT_' . $this->paymentElementUppercase . '_LESS_MIN_VALUE', $method->payment_name));
			}
			else
			{
				$app = JFactory::getApplication();
				$app->enqueueMessage(JText::sprintf('VMPAYMENT_' . $this->paymentElementUppercase . '_GREATER_MAX_VALUE', $method->payment_name));
			}

			return false;
		}

		$countries = array();
		if (!empty($method->countries))
		{
			if (!is_array($method->countries))
			{
				$countries[0] = $method->countries;
			}
			else
			{
				$countries = $method->countries;
			}
		}

		// probably did not gave BT:ST address
		if (!is_array($address))
		{
			$address = array();
			$address['virtuemart_country_id'] = 0;
		}

		if (!isset($address['virtuemart_country_id']))
		{
			$address['virtuemart_country_id'] = 0;
		}
		
		if (count($countries ?? []) == 0 || in_array($address['virtuemart_country_id'], $countries))
		{
			return true;
		}
		else
		{
			$app = JFactory::getApplication();
			$app->enqueueMessage(JText::sprintf('VMPAYMENT_' . $this->paymentElementUppercase . '_UNSUPPORTED_COUNTRY', $method->payment_name));
		}

		return false;
	}

	/**
	 * Returns a HTML string containing the plugin name and the last stored card details (if set)
	 * @param  TablePaymentmethods $plugin Object containing information on the chosen payment method
	 * @return string                      HTML string containing the payment gateway name and
	 */
	protected function renderPluginName($plugin)
	{
		$return = '';
		$img = '';
		$plugin_name = $this->_psType . '_name';
		$plugin_desc = $this->_psType . '_desc';
		$description = '';
		$logosFieldName = $this->_psType . '_logos';
		$logos = $plugin->$logosFieldName;
		if (!empty($logos))
		{
			$url = JURI::root() . 'plugins/vmpayment/payvector/images/';
			if (!is_array($logos))
			{
				$logos = (array)$logos;
			}
			foreach ($logos as $logo)
			{
				$altText = substr($logo, 0, strpos($logo, '.'));
				$img .= '<span class="vmCartPaymentLogo" ><img align="middle" src="' . $url . $logo . '"  alt="' . $altText . '" /></span> ';
			}

			$return = $img . ' ';
		}
		if (!empty($plugin->$plugin_desc))
		{
			$description = '<span class="' . $this->_type . '_description">' . $plugin->$plugin_desc . '</span>';
		}

		$UserID = JFactory::getUser()->get('id');

		//$extrainfo = $this->getExtraPluginNameInfo();
		$extrainfo = '';
		if(!isset($this->cardDetails))
		{
			$this->getCardFromSession();
		}

		if(!empty($this->crossReference))
		{
			if(!empty($this->cardLastFour))
			{
				$description = '<span class="' . $this->_type . '_description">XXXX-' . $this->cardLastFour . '</span>';
			}
			else
			{
				$description = '<span class="' . $this->_type . '_description"></span>';
			}
		}
		else if(!empty($this->cardType))
		{
			$description = $this->cardDetails->getCardName();
			$description .= ' ';
			$description .= $this->cardType;
			$description .= ' XXXX-';
			$description .= substr($this->cardDetails->getCardNumber(), -4, 4);
			$description = '<span class="' . $this->_type . '_description">' . $description . '</span>';
		}

		$pluginName = '<span class="' . $this->_type . '_name">' . $plugin->$plugin_name . '</span>
			<span class=' . $this->_type . '_description">' . $description . '</span><br>' . $return . '<br>';
		$pluginName .= $extrainfo;
		return $pluginName;
	}

	/**
	 * @param  stdClass               $method
	 * @return MerchantAuthentication
	 */
	private function getMerchantAuthentication($method)
	{
		// Get merchant credentials from the plugin configuration
		$merchantAuthentication = new MerchantAuthentication();
		if($method->testmode == 1)
		{
			$merchantAuthentication->setMerchantID($method->test_mid);
			$merchantAuthentication->setPassword($method->test_pass);
		}
		else
		{
			$merchantAuthentication->setMerchantID($method->live_mid);
			$merchantAuthentication->setPassword($method->live_pass);
		}
		return $merchantAuthentication;
	}

	/**
	 * Checks if the chosen credit card is valid using the VirtueMart CreditCard object
	 * @param  bool $enqueueMessage Whether the error message should be queued for display to the user
	 * @return bool                 True on valid credit card, false on invalid
	 */
	private function validateCreditCard($enqueueMessage = true)
	{
	
		$html = '';
		$cardName = $this->cardDetails->getCardName();
		$cardNumber = $this->cardDetails->getCardNumber();
		//$cvv = $this->cardDetails->getCV2();
		$cardNumberLength = strlen($cardNumber);
		//$cvvLength = strlen($cvv);
		$creditCardErrors = array();

		//if (empty($cvv))
		//{
		//	$creditCardErrors[] = 'VMPAYMENT_' . $this->paymentElementUppercase . '_CV2_MISSING';
		//}
		//else if (!is_numeric($cvv))
		//{
		//	$creditCardErrors[] = 'VMPAYMENT_' . $this->paymentElementUppercase . '_CV2_NUMERICAL_ONLY';
		//}
		//else if ($cvvLength < 3 || $cvvLength > 4)
		//{
		//	$creditCardErrors[] = 'VMPAYMENT_' . $this->paymentElementUppercase . '_CV2_INVALID_LENGTH';
		//}

		if($this->paymentType === SaleType::NewSale)
		{
			if (empty($cardNumber))
			{
				$creditCardErrors[] = 'VMPAYMENT_' . $this->paymentElementUppercase . '_CARD_NUMBER_MISSING';
			}
			else if (!is_numeric($cardNumber))
			{
				$creditCardErrors[] = 'VMPAYMENT_' . $this->paymentElementUppercase . '_CARD_NUMBER_NUMERICAL_ONLY';
			}
			else if ($cardNumberLength < 10 || $cardNumberLength > 22)
			{
				$creditCardErrors[] = 'VMPAYMENT_' . $this->paymentElementUppercase . '_CARD_NUMBER_INVALID_LENGTH';
			}
			else if (!Creditcard::validate_credit_card_number(null, $cardNumber))
			{
				$creditCardErrors[] = 'VMPAYMENT_' . $this->paymentElementUppercase . '_CARD_NUMBER_INVALID';
			}

			if (!$this->cardDetails->getExpiryDate()->getMonth()->getHasValue())
			{
				$creditCardErrors[] = 'VMPAYMENT_' . $this->paymentElementUppercase . '_EXPIRY_MONTH_MISSING';
			}
			else if (strlen($this->cardDetails->getExpiryDate()->getMonth()->getValue()) !== 2)
			{
				$creditCardErrors[] = 'VMPAYMENT_' . $this->paymentElementUppercase . '_EXPIRY_MONTH_INVALID_LENGTH';
			}
			if (!$this->cardDetails->getExpiryDate()->getYear()->getHasValue())
			{
				$creditCardErrors[] = 'VMPAYMENT_' . $this->paymentElementUppercase . '_EXPIRY_YEAR_MISSING';
			}
			else if (strlen($this->cardDetails->getExpiryDate()->getYear()->getValue()) !== 2)
			{
				$creditCardErrors[] = 'VMPAYMENT_' . $this->paymentElementUppercase . '_EXPIRY_YEAR_INVALID_LENGTH';
			}
		}


		$valid = (count($creditCardErrors ?? []) === 0);
		

		if (!$valid)
		{
			foreach ($creditCardErrors as $message)
			{
				$html .= Jtext::_($message) . "<br/>";
			}
		}
		if (!$valid && $enqueueMessage)
		{
			$app = JFactory::getApplication();
			$app->enqueueMessage($html);
		}
		
		return $valid;
	}

	/**
	 * Sets the credit card variable into the session
	 */
	private function setCardIntoSession()
	{
		$session = JFactory::getSession();

		$session->set('cardName_iridium', $this->cardDetails->getCardName(), 'vm');
		$session->set('cardNumber_iridium', $this->cardDetails->getCardNumber(), 'vm');
		//$session->set('cardCV2_iridium', $this->cardDetails->getCV2(), 'vm');
		$session->set('cardIssueNumber_iridium', $this->cardDetails->getIssueNumber(), 'vm');
		if($this->cardDetails->getExpiryDate()->getMonth()->getHasValue())
		{
			$session->set('cardExpiryMonth_iridium', $this->cardDetails->getExpiryDate()->getMonth()->getValue(), 'vm');
		}
		if($this->cardDetails->getExpiryDate()->getYear()->getHasValue())
		{
			$session->set('cardExpiryYear_iridium', $this->cardDetails->getExpiryDate()->getYear()->getValue(), 'vm');
		}

		$session->set('crossReference_iridium', $this->crossReference, 'vm');
		$session->set('cardLastFour_iridium', $this->cardLastFour, 'vm');
		$session->set('cardType_iridium', $this->cardType, 'vm');
		$session->set('paymentType_iridium', $this->paymentType, 'vm');
	}

	/**
	 * Retrieves the credit card variable from the session
	 */
	private function getCardFromSession()
	{
		$session = JFactory::getSession();

		$this->cardDetails = new CardDetails();
		
		$this->cardDetails->setCardName($session->get('cardName_iridium', '', 'vm'));
		$this->cardDetails->setCardNumber($session->get('cardNumber_iridium', '', 'vm'));
		//$this->cardDetails->setCV2($session->get('cardCV2_iridium', '', 'vm'));
		$this->cardDetails->setIssueNumber($session->get('cardIssueNumber_iridium', '', 'vm'));
		$this->cardDetails->getExpiryDate()->getMonth()->setValue($session->get('cardExpiryMonth_iridium', null, 'vm'));
		$this->cardDetails->getExpiryDate()->getYear()->setValue($session->get('cardExpiryYear_iridium', null, 'vm'));

		$this->crossReference = $session->get('crossReference_iridium', null, 'vm');
		$this->cardLastFour = $session->get('cardLastFour_iridium', null, 'vm');
		$this->cardType = $session->get('cardType_iridium', null, 'vm');
		$this->paymentType = $session->get('paymentType_iridium', null, 'vm');
	}

	/**
	 * Gets the customer's billing details from the VirtueMart Order array and the credit card object and returns them in an array
	 * @param int      $paymentMethodID Array of stdClass objects containing details of the order
	 */
	private function retrieveCardDetails($paymentMethodID)
	{
		$jInput = JFactory::getApplication()->input;

		if(!isset($this->cardDetails))
		{
			$this->cardDetails = new CardDetails();
		}
		$this->cardDetails->setCardName($jInput->get('cc_cardholdername_' . $paymentMethodID, '', 'STR'));
		$this->cardDetails->setCardNumber($jInput->get('cc_number_' . $paymentMethodID, null, 'ALNUM'));
		$this->cardDetails->getExpiryDate()->getMonth()->setValue($jInput->get('cc_expire_month_' . $paymentMethodID, null, 'ALNUM'));
		$this->cardDetails->getExpiryDate()->getYear()->setValue(substr($jInput->get('cc_expire_year_' . $paymentMethodID, '', 'ALNUM'), -2, 2));
		

		$this->cardDetails->setIssueNumber((string) $jInput->get('cc_issuenum_' . $paymentMethodID, null, 'ALNUM'));
		$this->paymentType = $jInput->get('payment_type', SaleType::NewSale, 'STR');
		if($this->paymentType === SaleType::CrossReferenceSale)
		{
			$this->cardDetails->setCV2((string) $jInput->get('cc_cvv_saved_' . $paymentMethodID, null, 'ALNUM'));

			$userID = JFactory::getUser()->get('id');
			$crossReferenceResult = $this->crossReferenceExists($userID);
			$this->crossReference = $crossReferenceResult['cross_reference'];
			$this->cardLastFour = $crossReferenceResult['card_last_four'];
			$this->cardType = $crossReferenceResult['card_type'];
		}
		else
		{
			$this->cardDetails->setCV2((string) $jInput->get('cc_cvv_' . $paymentMethodID, null, 'ALNUM'));
		}
	}

	/**
	 * Gets the customer's billing details from the VirtueMart Order array and the credit card object and returns them in an array
	 * @param  array  $order Array of stdClass objects containing details of the order
	 * @return array         Billing details formatted into an array
	 */
	private function retrieveBillingDetails(array $order)
	{
		$jInput = JFactory::getApplication()->input;
		$billingDetails = array();

		$paymentMethodID = $order['details']['BT']->virtuemart_paymentmethod_id;

		// Card Details
		$billingDetails['cardName'] = $jInput->get('cc_cardholdername_' . $paymentMethodID, '', 'STR');
		$billingDetails['cardNumber'] = $jInput->get('cc_number_' . $paymentMethodID, null, 'ALNUM');
		$billingDetails['expiryDateMonth'] = $jInput->get('cc_expire_month_' . $paymentMethodID, null, 'ALNUM');
		$billingDetails['expiryDateYear'] = substr($jInput->get('cc_expire_year_' . $paymentMethodID, '', 'ALNUM'), -2, 2);
		$billingDetails['cv2'] = (string) $jInput->get('cc_cvv_' . $paymentMethodID, null, 'INT');
		$billingDetails['issueNumber'] = (string) $jInput->get('cc_issuenum_' . $paymentMethodID, null, 'INT');

		// Billing
		$addressBT = $order['details']['BT'];
		$addressST = ((isset($order['details']['ST'])) ? $order['details']['ST'] : $order['details']['BT']);
		//$addressBT->billingcountry = ShopFunctions::getCountryByID($addressBT->virtuemart_country_id, 'country_2_code');
		//$addressBT->billingstate = isset($addressBT->virtuemart_state_id) ? ShopFunctions::getStateByID($addressBT->virtuemart_state_id) : '';
		//$addressST->deliverycountry = ShopFunctions::getCountryByID($addressST->virtuemart_country_id, 'country_2_code');
		//$addressST->deliverystate = isset($addressST->virtuemart_state_id) ? ShopFunctions::getStateByID($addressST->virtuemart_state_id) : '';
		//if(isset($addressBT->company))
		//  $formdata['BUSINESS'] = $addressBT->company;
		if (isset($addressBT->address_1))
		{
			$billingDetails['address1'] = $addressBT->address_1;
		}
		else
		{
			$billingDetails['address1'] = "";
		}
		if (isset($addressBT->address_2))
		{
			$billingDetails['address2'] = $addressBT->address_2;
		}
		else
		{
			$billingDetails['address2'] = "";
		}
		if (isset($addressBT->city))
		{
			$billingDetails['city'] = $addressBT->city;
		}
		if (isset($addressBT->zip))
		{
			$billingDetails['postcode'] = $addressBT->zip;
		}
		
		$billingDetails['countryISOCode'] = ShopFunctions::getCountryByID($addressBT->virtuemart_country_id, 'country_num_code');
		
		// State code for US customers only
		if (isset($addressBT->virtuemart_state_id))
		{
			$billingDetails['state'] = ShopFunctions::getStateByID($addressBT->virtuemart_state_id);
		}

		// Misc details
		if (isset($addressBT->phone_1))
		{
			$billingDetails['phoneNumber'] = $addressBT->phone_1;
		}
		$billingDetails['emailAddress'] = $addressBT->email;

		return $billingDetails;
	}

	//TODO still needs to be tested with currencies with an exponent other than 2
	/**
	 * Converts currency and amount to values that the payment gateway can accept
	 * @param  TablePaymentmethods $method     TablePaymentsmethod object containing details of the chosen payment method
	 * @param  array               $order      (optional) Array containing order details
	 * @return array                           Array containing converted currencies
	 */
	private function getConverted($method, array $order = null)
	{
		// get the vendor currency
		$paymentCurrency = CurrencyDisplay::getInstance('');
		$vendorCurrency = $paymentCurrency->_vendorCurrency;

		// get the payment method currency
		$methodPaymentCurrency = $this->getPaymentMethodCurrency($method);
		if ($methodPaymentCurrency == 0)
		{
			$methodPaymentCurrency = $vendorCurrency;
		}

		// convert
		$converted['code3'] = $this->getVirtueMartCurrencyCode($methodPaymentCurrency);
		$converted['codenumeric'] = $this->getVirtueMartCurrencyCode($methodPaymentCurrency, true);

		$exponent = 2;
		$iclISOCurrencyList = ISOHelper::getISOCurrencyList();
		/** @var  ISOCurrency $icISOCurrency */
		if ($iclISOCurrencyList->getISOCurrency($converted['codenumeric'], $icISOCurrency))
		{
			$exponent = $icISOCurrency->getExponent();
		}

		if ($order)
		{
			// format
			$converted['total'] = sprintf("%0." . $exponent . "f", $paymentCurrency->convertCurrencyTo($methodPaymentCurrency, $order['details']['BT']->order_total, false));
			// convert other fields
			$converted['order_subtotal'] = sprintf("%0." . $exponent . "f", $paymentCurrency->convertCurrencyTo($methodPaymentCurrency, $order['details']['BT']->order_subtotal, false));
			$converted['order_tax'] = sprintf("%0." . $exponent . "f", $paymentCurrency->convertCurrencyTo($methodPaymentCurrency, $order['details']['BT']->order_tax, false));
			$converted['order_shipment'] = sprintf("%0." . $exponent . "f", $paymentCurrency->convertCurrencyTo($methodPaymentCurrency, $order['details']['BT']->order_shipment, false));
			$converted['order_discount'] = sprintf("%0." . $exponent . "f", $paymentCurrency->convertCurrencyTo($methodPaymentCurrency, $order['details']['BT']->order_discount, false));
		}

		return $converted;
	}

	/**
	 * Selects the numeric or 3 digit currency code from a virtuemart currency ID
	 * @param  string|int  $virtuemartCurrencyID ID of the currency as given by Virtuemart
	 * @param  boolean     $numericCode          (optional) True to get the numeric code, false for the 3 digit code (default false)
	 * @return string                            The numeric or 3 digit code
	 */
	private function getVirtueMartCurrencyCode($virtuemartCurrencyID, $numericCode = false)
	{
		if($numericCode)
		{
			$field = 'currency_numeric_code';
		}
		else
		{
			$field = 'currency_code_3';
		}

		$query = 'SELECT `' . $field . '` FROM `#__virtuemart_currencies` WHERE `virtuemart_currency_id`="' . $virtuemartCurrencyID . '" ';
		$db = JFactory::getDBO();
		$db->setQuery($query);
		$result = $db->loadResult();
		return $result;
	}

	/**
	 * Gets the currency chosen with this payment method
	 * @param  TablePaymentmethods $method TablePaymentsmethod object containing details of the chosen payment method
	 * @return string|int                  VirtueMart currency code used by the payment method
	 */
	private function getPaymentMethodCurrency($method)
	{
		$paymentParameters = explode('|', $method->payment_params);
		$paymentCurrencyKey = $this->arrayFind("payment_currency=", $paymentParameters);
		$currency = explode("=", $paymentParameters[$paymentCurrencyKey]);
		$currency = str_replace('"', '', $currency[1]);
		return $currency;
	}

	/**
	 * Finds the first occurrence of a partial string in the given array
	 * @param  string      $needle   String to search for
	 * @param  array       $haystack Array to search
	 * @return string|bool           Array key of the first match or false on failure
	 */
	private function arrayFind($needle, array $haystack)
	{
		foreach ($haystack as $key => $item)
		{
			if (strpos($item, $needle) !== false)
			{
				return $key;
			}
		}
		return false;
	}

	/**
	 * Check if a CrossReference exists in the database and if so return it
	 * @param  string|int $UserID VirtueMart UserID
	 * @param  JDatabase  $db     (optional) JDatabase object, if one isn't passed it initializes it's own
	 * @return array|bool         Returns an associative array if a CrossReference exists for that user or FALSE if not
	 */
	private function crossReferenceExists($userID, JDatabase $db = null)
{
    if ($userID == 0) {
        return false;
    }

    if (is_null($db)) {
        $db = JFactory::getDbo(); // Corrected method name
    }

    $query = SQL_Queries::SELECT_CrossReference($userID);
    $db->setQuery($query);

    // Use loadAssoc() to fetch a single associative array result
	//$db->query();
    $result = $db->loadAssoc();

    return !empty($result) ? $result : false;
}


	/**
	 * Checks to see if a recent GatewayEntryPointList is saved in the database, returns it if one exists otherwise builds a list blind
	 * @return RequestGatewayEntryPointList List of gateway entry points
	 */
	private function getEntryPointList()
	{
		$rgeplRequestGatewayEntryPointList = new RequestGatewayEntryPointList();

		$db = JFactory::getDBO();

		//This query selects the latest record for Entry Points for high availability
		$query = SQL_Queries::SELECT_GatewayEntryPointObject();

		$db->setQuery($query);
		$row = $db->loadObject();

		$geplGatewayEntryPointListXML = $row->GatewayEntryPointObject;

		if ($geplGatewayEntryPointListXML != null)
		{
			$geplGatewayEntryPointList = GatewayEntryPointList::fromXmlString($geplGatewayEntryPointListXML);

			for ($nCount = 0; $nCount < $geplGatewayEntryPointList->getCount(); $nCount++)
			{
				$geplGatewayEntryPoint = $geplGatewayEntryPointList->getAt($nCount);
				$rgeplRequestGatewayEntryPointList->add($geplGatewayEntryPoint->getEntryPointURL(), $geplGatewayEntryPoint->getMetric(), 1);
			}
		}
		else
		{
			// if we don't have a recent list in the database then just use blind processing
			include __DIR__ . '/PayVector-Library/Config.php';
			/** @var string $PaymentProcessorFullDomain */
			$rgeplRequestGatewayEntryPointList->add("https://gw1." . $PaymentProcessorFullDomain, 100, 2);
			$rgeplRequestGatewayEntryPointList->add("https://gw2." . $PaymentProcessorFullDomain, 200, 2);
			$rgeplRequestGatewayEntryPointList->add("https://gw3." . $PaymentProcessorFullDomain, 300, 2);
		}

		return $rgeplRequestGatewayEntryPointList;
	}

	/**
	 * Shows 3DSecure iframe from a template
	 * @param  object                 $addressBT              Object containing address details for the order
	 * @param  FinalTransactionResult $finalTransactionResult TransactionOutputData object containing the detailed response from the gateway inlcuding the CrossReference
	 * @return string                                         HTML containing the 3DSecure iframe
	 */
	private function showAuthenticationFrame($addressBT, FinalTransactionResult $finalTransactionResult)
	{		
		// create invisible form and post it to the 3d v2 frame
		$FormAttributes = " target=\"threeDSecureFrame\"";
		$FormAction = $finalTransactionResult->getThreeDSecureOutputData()->getMethodURL();
		$prams = ["ThreeDSMethodData"=>$finalTransactionResult->getThreeDSecureOutputData()->getMethodData()];
		return $this->showThreeDSV2IFrame($FormAttributes, $FormAction,$prams);		
	}

	private function showThreeDSV2($FormAttributes, $FormAction, $prams){
		ob_start();		
		include (__DIR__ . '/Templates/3DSEnvironmentLandingForm.tpl');
		$html = ob_get_clean();
		return $html;
	}

	private function showThreeDSV2IFrame($FormAttributes, $FormAction, $prams){
		$loading_page = JURI::root() . 'plugins/vmpayment/payvector/Loading.htm';		
		ob_start();				
		include (__DIR__ . '/Templates/3DSecure.tpl');
		$html = ob_get_clean();
		$app = JFactory::getApplication();
		$app->setUserState('com_virtuemart.payment.3dsecure.html', $html);
		// show message
		$message = JText::_('VMPAYMENT_' . $this->paymentElementUppercase . '_3D_PLEASEAUTH');

		$app = JFactory::getApplication();
		$app->enqueueMessage($message);

		return $html;
	}

}


class SQL_Queries
{

	public static function DELETE_VM_PreviousOrders($orderID)
	{
		$sql[0] = "DELETE FROM #__virtuemart_orders
                    WHERE virtuemart_order_id = '$orderID';";

		$sql[1] = "DELETE FROM #__virtuemart_order_userinfos
                    WHERE virtuemart_order_id = '$orderID';";

		$sql[2] = "DELETE FROM #__virtuemart_order_histories
                    WHERE virtuemart_order_id = '$orderID';";

		$sql[3] = "DELETE FROM #__virtuemart_order_items
                    WHERE virtuemart_order_id = '$orderID';";

		$sql[4] = "DELETE FROM #__virtuemart_order_calc_rules
                    WHERE virtuemart_order_id = '$orderID';";

		return $sql;
	}

	public static function SELECT_Iridium($cols, $orderID)
	{
		$sql = "SELECT";
		foreach ($cols as $col)
		{
			$sql .= " " . $col . ",";
		}
		$sql = substr($sql, 0, -1);
		$sql .= " FROM #__virtuemart_payment_plg_payvector
					 WHERE virtuemart_order_id = $orderID";

		return $sql;
	}

	public static function SELECT_GatewayEntryPointObject()
	{
		$sql = "SELECT `GatewayEntryPointObject`, MAX(`DateTimeProcessed`)
                    FROM #__virtuemart_payment_plg_payvector_gateway_entry_points
                    WHERE `DateTimeProcessed` >= NOW() - interval 10 minute;";

		return $sql;
	}

	public static function UPDATE_GatewayEntryPointObject($gatewayEntryPointListXMLString)
	{
		$sql = "UPDATE #__virtuemart_payment_plg_payvector_gateway_entry_points
                    SET GatewayEntryPointObject = '$gatewayEntryPointListXMLString', DateTimeProcessed = CURRENT_TIMESTAMP;";
		return $sql;
	}

	public static function SELECT_CrossReference($userID)
	{
		$sql = "SELECT `cross_reference`, `card_last_four`, `card_type`, `last_updated`
                    FROM `#__virtuemart_payment_plg_payvector_cross_reference`
                    WHERE `user_id`= '$userID' AND  `last_updated` >= NOW() - interval 400 day;";
		return $sql;
	}

	public static function INSERT_CrossReference($userID, $crossReference, $cardLastFour, $cardType)
	{
		$sql = "INSERT INTO `#__virtuemart_payment_plg_payvector_cross_reference`
						(`user_id`, `cross_reference`, `card_last_four`, `card_type`)
			        VALUES
			        	('$userID', '$crossReference', '$cardLastFour', '$cardType');";
		return $sql;
	}

	public static function UPDATE_CrossReference($userID, $crossReference, $cardLastFour = null, $cardType = null)
	{
		$sql = "UPDATE `#__virtuemart_payment_plg_payvector_cross_reference`
			        SET `cross_reference` = '$crossReference',";
		if ($cardLastFour !== null)
		{
			$sql .= "`card_last_four` = '$cardLastFour',";
		}
		if ($cardType !== null)
		{
			$sql .= "`card_type` = '$cardType',";
		}
		$sql .= "`last_updated` = NOW() WHERE `user_id` = '$userID';";
		return $sql;
	}

}
