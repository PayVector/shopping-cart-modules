<?php

namespace Opencart\Catalog\Controller\Extension\Payvector\Payment;

if (defined('DIR_EXTENSION')) {
	require_once DIR_EXTENSION . 'payvector/system/engine/controller.php';
	require_once DIR_EXTENSION . 'payvector/catalog/controller/payment/lib/include_all.php';
} else {
	require_once DIR_SYSTEM . 'extension/payvector/system/engine/controller.php';
	require_once DIR_SYSTEM . 'extension/payvector/catalog/controller/payment/lib/include_all.php';
}

class Payvector extends \Opencart\System\Engine\Extension\Payvector\Controller
{
	private $paymentProcessorDomain = '';
	private $paymentProcessorHPFDomain = '';
	private $hostedPaymentFormURL = '';
	private $TransparentRedirectURL = '';
	private $hostedPaymentFormHandlerURL = '';

	private function getCurrentOrderId($finalTransactionResult)
	{
		$order_id = 0;

		if (!empty($finalTransactionResult)) {
			$order_id = (int) $finalTransactionResult->getOrderID($this->sessionHandler);
			$this->log->write(['Result OrderId' => $order_id]);
		}


		if (empty($order_id) && !empty($this->session->data['order_id'])) {
			$order_id = (int) $this->session->data['order_id'];
			$this->log->write(['Session OrderId' => $order_id]);
		}

		return $order_id;
	}

	public function __construct($registry)
	{
		// first call the parent so $this->registry is set up
		parent::__construct($registry);

		$this->sessionHandler = new NativeSessionHandler();

		$this->sessionHandler->initialiseSession();

		$this->paymentProcessorDomain = "payvector.net";
		$this->paymentProcessorHPFDomain = "https://mms.".$this->paymentProcessorDomain."/";
		$this->hostedPaymentFormURL = $this->paymentProcessorHPFDomain."Pages/PublicPages/PaymentForm.aspx";
		$this->TransparentRedirectURL = $this->paymentProcessorHPFDomain."Pages/PublicPages/TransparentRedirect.aspx";
		$this->hostedPaymentFormHandlerURL = $this->paymentProcessorHPFDomain."Pages/PublicPages/PaymentFormResultHandler.ashx";
	}

	/**
	 * Gets the data required and then renders the correct template
	 */
	public function index()
	{

		$data = array();
		$this->load->language($this->ePath);

		$data['months'] = array();
		for($i = 1; $i <= 12; $i++)
		{
			$formattedMonthNumber = sprintf('%02d', $i);
			$data['months'][] = array(
				'text' => $formattedMonthNumber,
				'value' => $formattedMonthNumber
			);
		}

		$data['year_valid'] = array();
		$data['year_expire'] = array();
		$yearNow = date('y');
		for ($y = $yearNow - 9; $y <= $yearNow; $y++) {
			$data['year_valid'][] = array(
				'text' => 20 . sprintf('%02d', $y),
				'value' => 20 . sprintf('%02d', $y)
			);
		}
		for ($y = $yearNow; $y < $yearNow + 10; $y++) {
			$data['year_expire'][] = array(
				'text' => 20 . sprintf('%02d', $y),
				'value' => 20 . sprintf('%02d', $y)
			);
		}

		$data['capture_method'] = $this->config->get('payment_payvector_capture_method');
		if($this->config->get('payment_payvector_capture_method') === IntegrationMethod::TransparentRedirect)
		{
			$data['TransparentRedirectURL'] = $this->transparentRedirectURL;
			$this->load->model('checkout/order');
			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

			$isoCurrencyCode = null;
			$isoCountryCode = null;
			$currencyShort = $order_info['currency_code'];
			$cartTotal = $order_info['total'];
			$countryShort = $order_info['payment_iso_code_2'];
			$iclISOCurrencyList = ISOHelper::getISOCurrencyList();
			if(!empty($currencyShort) && $iclISOCurrencyList->getISOCurrency($currencyShort, $icISOCurrency))
			{
				/** @var $icISOCurrency ISOCurrency */
				$isoCurrencyCode = $icISOCurrency->getISOCode();
				//Always check to see if the cart already formats in minor currency
				$cartTotal = (string) $cartTotal;
				$cartTotal = round($cartTotal * ("1" . str_repeat(0, $icISOCurrency->getExponent())));
			}
			$iclISOCountryList = ISOHelper::getISOCountryList();
			if(!empty($countryShort) && $countryShort != "-1" && $iclISOCountryList->getISOCountry($countryShort, $icISOCountry))
			{
				/** @var $icISOCountry ISOCountry */
				$isoCountryCode = $icISOCountry->getISOCode();
			}

			$transactionProcessor = new TransactionProcessor();
			$transactionProcessor->setMerchantID($this->config->get('payment_payvector_mid'));
			$transactionProcessor->setMerchantPassword($this->config->get('payment_payvector_pass'));
			$transactionProcessor->setCurrencyCode($isoCurrencyCode);
			$transactionProcessor->setAmount($cartTotal);
			$transactionProcessor->setOrderID($order_info['order_id']);
			$transactionProcessor->setOrderDescription("OpenCart order " . $order_info['order_id']);
			$transactionProcessor->setCustomerName($order_info['firstname'] . " " . $order_info['lastname']);
			$transactionProcessor->setAddress1($order_info['payment_address_1']);
			$transactionProcessor->setAddress2($order_info['payment_address_2']);
			$transactionProcessor->setCity($order_info['payment_city']);
			$transactionProcessor->setState($order_info['payment_zone']);
			$transactionProcessor->setPostcode($order_info['payment_postcode']);
			$transactionProcessor->setCountryCode($isoCountryCode);
			$transactionProcessor->setEmailAddress($order_info['email']);
			$transactionProcessor->setPhoneNumber($order_info['telephone']);
			$transactionProcessor->setIPAddress($this->request->server['REMOTE_ADDR']);
			$transactionProcessor->setTransactionType($this->config->get('payment_payvector_transaction_type'));

			$data = array_merge(
				$data,
				$transactionProcessor->getTransparentForm(
					str_replace("&amp;", "&", $this->url->link($this->ePath . $this->separator . 'callbackTransparent', '', 'SSL')),
					$this->config->get('payment_payvector_pre_shared_key'),
					$this->config->get('payment_payvector_hash_method'),
					$this->sessionHandler)
			);
		}

		$template = null;
		$crossReferenceResults = PayVectorSQL::SELECT_CrossReference($this->db, $this->customer->getId());
		if($crossReferenceResults  && $this->config->get('payment_payvector_enable_cross_reference') === "1")
		{
			if(isset($crossReferenceResults['card_first_six']) && isset($crossReferenceResults['card_last_four']))
			{
				$data['entry_cc_used_saved'] = sprintf(
					$this->language->get('entry_cc_used_saved'),
					$crossReferenceResults['card_type'],
					": " . substr($crossReferenceResults['card_first_six'], 0, 4) . "-" . substr($crossReferenceResults['card_first_six'], 4, 6) . "XX-XXXX-" . $crossReferenceResults['card_last_four']
				);
			}
			else
			{
				$data['entry_cc_used_saved'] = sprintf($this->language->get('entry_cc_used_saved'), $crossReferenceResults['card_type'], "");
			}
			$data['entry_cc_enter_new'] = $this->language->get('entry_cc_enter_new');

			$template = $this->ePathTemplate . 'payvector_cross_reference';
		}
		else
		{
			$template = $this->ePathTemplate . 'payvector';
		}

		// $data['ajax_url'] = '';
		$data['ajax_url'] = $this->url->link($this->ePath . $this->separator . 'send', '', 'SSL');

		return $this->load->view($template, $data);
	}

	public function send()
	{
		$this->load->language($this->ePath);

		$captureMethod = $this->config->get('payment_payvector_capture_method');
		$enableCrossReference = $this->config->get('payment_payvector_enable_cross_reference');

		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		$rgeplRequestGatewayEntryPointList = $this->getEntryPointList();

		if(isset($this->request->post['CardName']))
		{
			$customerName = $this->request->post['CardName'];
		}
		else
		{
			$customerName = $order_info['firstname'] . " " . $order_info['lastname'];
		}

		$isoCurrencyCode = null;
		$isoCountryCode = null;
		$currencyShort = $order_info['currency_code'];
		$cartTotal = $order_info['total'];
		$countryShort = $order_info['payment_iso_code_2'];
		$iclISOCurrencyList = ISOHelper::getISOCurrencyList();
		if(!empty($currencyShort) && $iclISOCurrencyList->getISOCurrency($currencyShort, $icISOCurrency))
		{
			/** @var $icISOCurrency ISOCurrency */
			$isoCurrencyCode = $icISOCurrency->getISOCode();
			//Always check to see if the cart already formats in minor currency
			$cartTotal = (string) $cartTotal;
			$cartTotal = round($cartTotal * ("1" . str_repeat(0, $icISOCurrency->getExponent())));
		}
		$iclISOCountryList = ISOHelper::getISOCountryList();
		if(!empty($countryShort) && $countryShort != "-1" && $iclISOCountryList->getISOCountry($countryShort, $icISOCountry))
		{
			/** @var $icISOCountry ISOCountry */
			$isoCountryCode = $icISOCountry->getISOCode();
		}

		$transactionProcessor = new TransactionProcessor();
		$transactionProcessor->setMerchantID($this->config->get('payment_payvector_mid'));
		$transactionProcessor->setMerchantPassword($this->config->get('payment_payvector_pass'));
		$transactionProcessor->setRgeplRequestGatewayEntryPointList($rgeplRequestGatewayEntryPointList);
		$transactionProcessor->setCurrencyCode($isoCurrencyCode);
		$transactionProcessor->setAmount($cartTotal);
		$transactionProcessor->setOrderID($order_info['order_id']);
		$transactionProcessor->setOrderDescription("OpenCart order " . $order_info['order_id']);
		$transactionProcessor->setCustomerName($customerName);
		$transactionProcessor->setAddress1($order_info['payment_address_1']);
		$transactionProcessor->setAddress2($order_info['payment_address_2']);
		$transactionProcessor->setCity($order_info['payment_city']);
		$transactionProcessor->setState($order_info['payment_zone']);
		$transactionProcessor->setPostcode($order_info['payment_postcode']);
		$transactionProcessor->setCountryCode($isoCountryCode);
		$transactionProcessor->setEmailAddress($order_info['email']);
		$transactionProcessor->setPhoneNumber($order_info['telephone']);
		$transactionProcessor->setIPAddress($this->request->server['REMOTE_ADDR']);
		$transactionProcessor->setTransactionType($this->config->get('payment_payvector_transaction_type'));

		//3DSv2 Parameters
		if (isset($this->request->post['browserjavaenabled'])) {
			$transactionProcessor->setJavaEnabled($this->request->post['browserjavaenabled']);
			$transactionProcessor->setJavaScriptEnabled('true');
			$transactionProcessor->setScreenWidth($this->request->post['browserscreenwidth']);
			$transactionProcessor->setScreenHeight($this->request->post['browserscreenheight']);
			$transactionProcessor->setScreenColourDepth($this->request->post['browsercolordepth']);
			$transactionProcessor->setTimezoneOffset($this->request->post['browsertz']);
			$transactionProcessor->setLanguage($this->request->post['browserlanguage']);

			$notifyURL = str_replace("&amp;", "&", $this->url->link($this->ePath . $this->separator . 'callback3DSecure', '', 'SSL'));

			$ChURL = htmlspecialchars($notifyURL, ENT_XML1, 'UTF-8');

			$transactionProcessor->setChallengeNotificationURL($ChURL);
			$transactionProcessor->setFingerprintNotificationURL($ChURL);
		}

		if (isset($this->request->post['CV2'])) {
			$transactionProcessor->setCV2($this->request->post['CV2']);
		}

		if($enableCrossReference === "1" && isset($this->request->post['payment_type']) && $this->request->post['payment_type'] === SaleType::CrossReferenceSale)
		{
			$this->session->data['payvector_sale_type'] = SaleType::CrossReferenceSale;
			$crossReferenceResults = PayVectorSQL::SELECT_CrossReference($this->db, $this->customer->getId());
			$finalTransactionResult = $transactionProcessor->doCrossReferenceTransaction(
				$crossReferenceResults['cross_reference'],
				$this->config->get('payment_payvector_enable_3ds_cross_reference') === "1",
				$this->sessionHandler);
		}
		else
		{
			$this->session->data['payvector_sale_type'] = SaleType::NewSale;
			if($captureMethod === IntegrationMethod::HostedPaymentForm)
			{
				$callbackURL = str_replace("&amp;", "&", $this->url->link($this->ePath . $this->separator . 'callbackHPF', '', 'SSL'));
				$json = $transactionProcessor->getHostedPaymentForm(
					$callbackURL,
					$callbackURL,
					$this->config->get('payment_payvector_pre_shared_key'),
					$this->config->get('payment_payvector_hash_method'),
					$this->config->get('payment_payvector_result_delivery_method'),
					false,
					$this->sessionHandler);

				$json['redirectHPF'] = $this->url->link($this->ePath . $this->separator . 'createHPF', '', 'SSL');
			}
			else
			{
				$finalTransactionResult = $transactionProcessor->doCardDetailsTransaction(
					$this->request->post['CardNumber'],
					$this->request->post['ExpiryDateMonth'],
					$this->request->post['ExpiryDateYear'],
					$this->request->post['IssueNumber'],
					$this->sessionHandler);

				// $this->log->write($finalTransactionResult);
			}
		}
		if($captureMethod !== IntegrationMethod::HostedPaymentForm ||
			isset($this->request->post['payment_type']) && $this->request->post['payment_type'] === SaleType::CrossReferenceSale)
		{
			$this->handleTransactionResults($finalTransactionResult);
			// $this->log->write($finalTransactionResult);
		}
		else
		{
			//v1.5.1.2 or earlier
			if (!method_exists($this->tax, 'getRates'))
			{
				$this->load->library('json');
				$this->response->setOutput(Json::encode($json));
			} else {
				$this->response->setOutput(json_encode($json));
			}
		}
	}

	/**
	 * @param FinalTransactionResult $finalTransactionResult
	 */
	public function handleTransactionResults(FinalTransactionResult $finalTransactionResult)
	{
		// $this->log->write('=== inside transactionSuccessful');
		// $this->log->write([
		// 	'transactionSuccessful' => $finalTransactionResult->transactionSuccessful(),
		// 	'transactionProcessed' => $finalTransactionResult->transactionProcessed(),
		// 	'getErrorMessage' => $finalTransactionResult->getErrorMessage(),
		// 	'getStatusCode' => $finalTransactionResult->getStatusCode(),
		// ]);

		if($finalTransactionResult->transactionProcessed() && $finalTransactionResult->transactionSuccessful())
		{
			//Direct/API only
			if($finalTransactionResult instanceof DirectFinalTransactionResult)
			{
				//save gateway entry point list to the database
				$gatewayEntryPointListXML = "";//$finalTransactionResult->getGatewayEntryPointList()->toXmlString();
				PayVectorSQL::UPDATE_GatewayEntryPoints($this->db, $gatewayEntryPointListXML);
			}
			//Save cross reference
			$crossReference = $finalTransactionResult->getCrossReference();
			$cardType = $finalTransactionResult->getCardType();
			$cardFirstSix = $finalTransactionResult->getCardFirstSix($this->sessionHandler);
			$cardLastFour = $finalTransactionResult->getCardLastFour($this->sessionHandler);
			$overwriteCardLastFour = (isset($_SESSION['payvector_transaction_is_cross_reference']) && !$_SESSION['payvector_transaction_is_cross_reference'])
				&& $finalTransactionResult->getTransactionMethod() !== TransactionMethod::CrossReferenceTransaction;

			PayVectorSQL::UPDATE_CrossReference($this->db, $this->customer->getID(), $crossReference, $cardType, $cardFirstSix, $cardLastFour, $overwriteCardLastFour);

			//report transaction success
			if(
				$finalTransactionResult->getIntegrationMethod() === IntegrationMethod::HostedPaymentForm
				|| $finalTransactionResult->getIntegrationMethod() === IntegrationMethod::TransparentRedirect
				|| $finalTransactionResult->getTransactionMethod() === TransactionMethod::ThreeDSecureTransaction
			)
			{
				$this->load->model('checkout/order');
				$successMessage = "Transaction ID: $crossReference";
				$order_id = $this->getCurrentOrderId($finalTransactionResult);

				$this->model_checkout_order->addHistory($order_id, $this->config->get('payment_payvector_order_status_id'), $successMessage, false);
				$this->response->redirect($this->url->link('checkout/success', '', 'SSL'));
			}
			else
			{
				$successMessage = 'AuthCode: ' . $finalTransactionResult->getAuthCode() . " <br> " . "Transaction ID: $crossReference";
				$order_id = $this->getCurrentOrderId($finalTransactionResult);
				$this->model_checkout_order->addHistory($order_id, $this->config->get('payment_payvector_order_status_id'), $successMessage, false);
				$json['success'] = $this->url->link('checkout/success', '', 'SSL');
			}
		}
		else
		{
			//run 3DSecure if required
			if($finalTransactionResult->getStatusCode() === 3)
			{
				$threeDSecureOutputData = $finalTransactionResult->getThreeDSecureOutputData();
				$json['redirect'] = $this->url->link($this->ePath . $this->separator . 'create3d', '', 'SSL');
				// var_dump($threeDSecureOutputData->getPaREQ());die;
				$json['ACSURL'] = $threeDSecureOutputData->getACSURL();
				$json['PaREQ'] = $threeDSecureOutputData->getPaREQ();
				$this->session->data['payvector_MD'] = $json['MD'] = $finalTransactionResult->getCrossReference();
				$json['TermUrl'] = $this->url->link($this->ePath . $this->separator . 'callback3DSecure', '', 'SSL');

				$this->session->data['payvector_MethodURL'] = $threeDSecureOutputData->getMethodURL();

				$this->session->data['payvector_ThreeDSMethodData'] = $threeDSecureOutputData->getMethodData();

			}
			//otherwise report transaction failure
			else
			{
				$this->load->model("checkout/order");
				$order_id = $this->getCurrentOrderId($finalTransactionResult);

				$this->model_checkout_order->addHistory($order_id, $this->config->get('payment_payvector_failed_order_status_id'), $finalTransactionResult->getErrorMessage(), false);

				$this->log->write([$finalTransactionResult->getIntegrationMethod()]);

				if(
					$finalTransactionResult->getTransactionMethod() === TransactionMethod::ThreeDSecureTransaction
					|| $finalTransactionResult->getIntegrationMethod() === IntegrationMethod::HostedPaymentForm
					|| $finalTransactionResult->getIntegrationMethod() === IntegrationMethod::TransparentRedirect
				)
				{
					$this->session->data['payvector_error'] = $finalTransactionResult->getUserFriendlyMessage();
					$this->response->redirect($this->url->link($this->ePath . $this->separator . 'failure', '', 'SSL'));
				}
				else
				{
					$json['error'] = "Transaction failed with message: " . $finalTransactionResult->getUserFriendlyMessage() .
						". Please check your card details";
				}
			}
		}

		//v1.5.1.2 or earlier
		if (!method_exists($this->tax, 'getRates'))
		{
			$this->load->library('json');
			$this->response->setOutput(Json::encode($json));
		} else
		{
			$this->response->setOutput(json_encode($json));
		}
	}

	public function failure()
	{
		$this->load->language('checkout/success');
		$this->load->language($this->ePath);

		$this->document->setTitle($this->language->get('payment_failed_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language'))
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_basket'),
			'href' => $this->url->link('checkout/cart', 'language=' . $this->config->get('config_language'))
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_checkout'),
			'href' => $this->url->link('checkout/checkout', 'language=' . $this->config->get('config_language'))
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('payment_failed_title'),
			'href' => $this->url->link($this->ePath . $this->separator . 'failure', 'language=' . $this->config->get('config_language'))
		];

		$data['heading_title'] = $this->language->get('heading_title');

		$data['payment_failed_title'] = $this->language->get('payment_failed_title');
		$data['text_payment_failed_error'] = sprintf($this->language->get('text_payment_failed_error'), isset($this->session->data['payvector_error']) ? $this->session->data['payvector_error'] : $this->language->get('text_something_went_wrong'));
		$data['text_payment_failed_redirect'] = sprintf($this->language->get('text_payment_failed_redirect'), $this->url->link('checkout/checkout', '', 'SSL'));

		$data['header'] = $this->load->controller('common/header');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($this->ePathTemplate . 'payvector_checkout_failure', $data));

	}

	/**
	 * Renders the 3DSecure form from the template
	 * @param null|string $termURL
	 */
	public function create3d($termURL = null)
	{
		$data = array();
		$this->document->setTitle("3DSecure Authentication");

		$data['ACSURL'] = $this->request->post['ACSURL'];
		$data['PaReq'] = $this->request->post['PaREQ'];
		if(isset($this->request->post['MD']))
		{
			$data['MD'] = $this->request->post['MD'];
		}
		else
		{
			$data['MD'] = $this->request->post['CrossReference'];
		}
		if(isset($this->request->post['TermUrl']))
		{
			$data['termURL'] = $this->request->post['TermUrl'];
		}
		else
		{
			$data['termURL'] = $termURL;
		}

		if (isset($this->session->data['payvector_MD'])) {
			$data['MD'] = $this->session->data['payvector_MD'];
		}

		if (isset($this->session->data['payvector_MethodURL'])) {
			$data['ACSURL'] = $this->session->data['payvector_MethodURL'];
			$data['ThreeDSMethodData'] = $this->session->data['payvector_ThreeDSMethodData'];
		}

		$template = $this->ePathTemplate . 'payvector_3dsecure';

		$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

		if ($is_ajax) {
			$data['header'] = '';
			$data['footer'] = '';
		} else {
		    $data['header'] = $this->load->controller('common/header');
			$data['footer'] = $this->load->controller('common/footer');
		}


		$this->response->setOutput($this->load->view($template, $data));
	}

	private function getTemplateTheme()
	{
		return $this->config->get('config_template') ?? 'default/template';
	}
	/**
	 * Renders the Hosted Payment Form from the template
	 */
	public function createHPF()
	{
		$data = array();
		foreach($this->request->post as $key => $value)
		{
			$data[$key] = $value;
		}

		$data['HostedPaymentFormURL'] = $this->hostedPaymentFormURL;

		$data['themePath'] = "default";
		$template = $this->ePathTemplate . 'payvector_hpf';

		$data['header'] = $this->load->controller('common/header');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($template, $data));
	}

	private function showThreeDSV2($FormAttributes, $FormAction, $prams)
	{

		$data = [];
		$data['FormAttributes'] = $FormAttributes;
		$data['FormAction'] = $FormAction;
		$data['prams'] = $prams;

		$template = $this->ePathTemplate . 'payvector_3dsecure_v2';

		echo $this->load->view($template, $data);
		exit;
	}

	private function showThreeDSV2IFrame($FormAttributes, $FormAction, $prams)
	{
		$data = [];
		$data['FormAttributes'] = $FormAttributes;
		$data['FormAction'] = $FormAction;
		$data['ACSURL'] = $FormAction;
		$data['prams'] = $prams;

		$template = $this->ePathTemplate . 'payvector_3dsecure_v2_landing';

		$data['header'] = $this->load->controller('common/header');
		$data['footer'] = $this->load->controller('common/footer');

		echo $this->load->view($template, $data);
		exit;
	}


	/**
	 * Handle 3DSecure callback
	 */
	public function callback3DSecure()
	{
		$cres = $_REQUEST['cres'] ?? '';
		$FormMode = $_REQUEST['FormMode'] ?? 'NEW';
		$ThreeDSMethodData = $_REQUEST['threeDSMethodData'] ?? '';
		if (empty($cres)){
			switch ($FormMode)
			{
				case "NEW":
					$FormAttributes = " target=\"_parent\"";
					$FormAction = "";
					$prams = ["threeDSMethodData"=>$ThreeDSMethodData, "FormMode"=> "STEP2"];

					$this->showThreeDSV2($FormAttributes, $FormAction, $prams );
					exit;
				break;
				case "STEP2":
					$crossReference = $this->session->data['payvector_MD'];

					$tdseThreeDSecureEnvironment = new \net\thepaymentgateway\paymentsystem\ThreeDSecureEnvironment($this->getEntryPointList());
					$tdseThreeDSecureEnvironment->getMerchantAuthentication()->setMerchantID($this->config->get('payment_payvector_mid'));
					$tdseThreeDSecureEnvironment->getMerchantAuthentication()->setPassword($this->config->get('payment_payvector_pass'));
					$tdseThreeDSecureEnvironment->getThreeDSecureEnvironmentData()->setCrossReference($crossReference);
					$tdseThreeDSecureEnvironment->getThreeDSecureEnvironmentData()->setMethodData($ThreeDSMethodData);
					$boTransactionProcessed = $tdseThreeDSecureEnvironment->processTransaction($tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData);

					// $this->log->write('=== inside crossReference');
					// $this->log->write($crossReference);

					// $this->log->write('=== inside tdsarThreeDSecureAuthenticationResult');
					// $this->log->write($tdsarThreeDSecureAuthenticationResult);
					// $this->log->write('=== inside $tdsarThreeDSecureAuthenticationResult->getStatusCode() ');
					// $this->log->write($tdsarThreeDSecureAuthenticationResult->getStatusCode());

					if($tdsarThreeDSecureAuthenticationResult->getStatusCode() === 3){
						$CREQ = $todTransactionOutputData->getThreeDSecureOutputData()->getCREQ();
						$ThreeDSSessionData = PaymentFormHelper::base64UrlEncode($todTransactionOutputData->getCrossReference());
						$FormAttributes = " target=\"threeDSecureFrame\"";
						$FormAction = $todTransactionOutputData->getThreeDSecureOutputData()->getACSURL();
						$parms = ['creq' => $CREQ, 'threeDSSessionData' => $ThreeDSSessionData];
						// $this->log->write(['$FormAction' => $FormAction]);

						$this->showThreeDSV2IFrame($FormAttributes, $FormAction, $parms );
						exit;
					}
					else {
						// $this->log->write('=== inside finalTransactionResult');

						$finalTransactionResult = new ThreeDSecureFinalTransactionResult($boTransactionProcessed, $tdseThreeDSecureEnvironment, $tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData, $this->sessionHandler);

						// $this->log->write($finalTransactionResult);

						//$this->handleTransactionResults($method, $order, $finalTransactionResult);
						$this->handleTransactionResults($finalTransactionResult);
						exit;

					}

					exit;
				break;
			}
		}else {
			$threeDSSessionData =$_REQUEST['threeDSSessionData'] ?? '';

			switch ($FormMode)
			{
				case "NEW":
					$FormAttributes = " target=\"_parent\"";
					$FormAction = "";
					$prams = ["cres"=>$cres,"threeDSSessionData"=>$threeDSSessionData, "FormMode"=> "STEP3"];
					$this->showThreeDSV2($FormAttributes, $FormAction, $prams );
				break;
				case "STEP3":

					$CrossReference = PaymentFormHelper::base64UrlDecode($threeDSSessionData);

					$tdsaThreeDSecureAuthentication = new \net\thepaymentgateway\paymentsystem\ThreeDSecureAuthentication($this->getEntryPointList());
					$tdsaThreeDSecureAuthentication->getMerchantAuthentication()->setMerchantID($this->config->get('payment_payvector_mid'));
					$tdsaThreeDSecureAuthentication->getMerchantAuthentication()->setPassword($this->config->get('payment_payvector_pass'));
					$tdsaThreeDSecureAuthentication->getThreeDSecureInputData()->setCrossReference($CrossReference);
					$tdsaThreeDSecureAuthentication->getThreeDSecureInputData()->setCRES($cres);
					$boTransactionProcessed = $tdsaThreeDSecureAuthentication->processTransaction($tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData);
					$finalTransactionResult = new ThreeDSecureFinalTransactionResult($boTransactionProcessed, $tdsaThreeDSecureAuthentication, $tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData, $this->sessionHandler);
					//$html = $this->handleTransactionResults($method, $order, $finalTransactionResult);
					$this->handleTransactionResults($finalTransactionResult);

					unset($this->session->data['payvector_MD']);
					unset($this->session->data['payvector_MethodURL']);

					return true;
				break;

			}
		}
		exit;
	}

	/**
	 * Handle Hosted Payment Form callback
	 */
	public function callbackHPF()
	{
		$this->load->model('checkout/order');
		$hashMatches = false;
		$szValidateErrorMessage = "";
		/* @var TransactionResult $trTransactionResult */
		if($this->config->get('payment_payvector_result_delivery_method') === ResultDeliveryMethod::POST)
		{
			$hashMatches = PaymentFormHelper::validateTransactionResult_POST(
				$this->config->get('payment_payvector_mid'),
				$this->config->get('payment_payvector_pass'),
				$this->config->get('payment_payvector_pre_shared_key'),
				$this->config->get('payment_payvector_hash_method'),
				$this->request->post,
				$trTransactionResult,
				$szValidateErrorMessage
			);
		}
		else if($this->config->get('payment_payvector_result_delivery_method') === ResultDeliveryMethod::SERVER_PULL)
		{
			$hashMatches = PaymentFormHelper::validateTransactionResult_SERVER_PULL(
				$this->config->get('payment_payvector_mid'),
				$this->config->get('payment_payvector_pass'),
				$this->config->get('payment_payvector_pre_shared_key'),
				$this->config->get('payment_payvector_hash_method'),
				$this->request->get,
				$this->hostedPaymentFormHandlerURL,
				$trTransactionResult,
				$szValidateErrorMessage
			);
		}

		if(!$hashMatches)
		{
			$this->load->model("checkout/order");
			$order_id = $this->getCurrentOrderId($trTransactionResult);
			$this->model_checkout_order->addHistory($order_id, $this->config->get('payment_payvector_failed_order_status_id'), "Variable tampering detected", false);
			$this->session->data['payvector_error'] = "Variable tampering detected";
			$this->response->redirect($this->url->link($this->ePath . $this->separator . 'failure', '', 'SSL'));
		}
		else
		{
			$this->handleTransactionResults(new HostedPaymentFormFinalTransactionResult($trTransactionResult));
		}
	}

	public function callbackTransparent()
	{
		//Handle 3DSecure Authentication Required
		if(isset($this->request->post['ACSURL']))
		{
			$integrityVerified = PaymentFormHelper::checkIntegrityOfIncomingVariables(
				INCOMING_VARIABLE_SOURCE::THREE_D_SECURE_AUTHENTICATION_REQUIRED,
				$this->request->post,
				$this->config->get('payment_payvector_hash_method'),
				$this->config->get('payment_payvector_pre_shared_key'),
				$this->config->get('payment_payvector_mid'),
				$this->config->get('payment_payvector_pass')
			);
			if($integrityVerified)
			{
				$this->create3d( str_replace("&amp;", "&", $this->url->link($this->ePath . $this->separator . 'callbackTransparent', '', 'SSL')) );
			}
			else
			{
				$this->load->model("checkout/order");
				$this->model_checkout_order->addHistory($this->session->data['order_id'], $this->config->get('payment_payvector_failed_order_status_id'), "Variable tampering detected", false);
				$this->session->data['payvector_error'] = "Variable tampering detected";
				$this->response->redirect($this->url->link($this->ePath . $this->separator . 'failure', '', 'SSL'));
			}
		}
		//Break out of 3DSecure iframe
		else if(isset($this->request->post['PaRes']))
		{
			$this->load->model('checkout/order');
			$crossReference = $this->request->post['MD'];
			$PaRES = $this->request->post['PaRes'];
			if(empty($crossReference) && empty($this->session->data['payvector_MD']))
			{
				echo 'No MD received';
				exit;
			}
			if(empty($PaRES) && empty($this->session->data['payvector_PaRes']))
			{
				echo 'No PaRes received';
				exit;
			}
			if(empty($this->session->data['order_id']))
			{
				$this->response->redirect($this->url->link('account/login', '', 'SSL'));
			}
			if(!isset($this->request->get['breakout']) || $this->request->get['breakout'] !== "true")
			{
				$this->session->data['payvector_PaRes'] = $PaRES;
				$this->session->data['payvector_MD'] = $crossReference;
				$notifyURL = $this->url->link($this->ePath . $this->separator . 'callbackTransparent', "", 'SSL');

				$link = '<noscript>You have javascript disabled, please click to continue: <a target="_top" href="' . $notifyURL . '&breakout=true">' . $notifyURL . '</a></noscript>';
				$js = "window.parent.location.href='" . $notifyURL . "&breakout=true';";
				echo '<p>Please wait</p>';
				echo $link;
				echo '<script language="JavaScript">';
				echo $js;
				echo '</script>';
				exit;
			}
		}
		//after breaking out of iframe
		else if(isset($this->session->data['payvector_PaRes']))
		{
			//generate hash and create from to send back to TransparentRedirect form
			$PaRES = $this->session->data['payvector_PaRes'];
			$crossReference = $this->session->data['payvector_MD'];
			unset($this->session->data['payvector_PaRes']);
			unset($this->session->data['payvector_MD']);

			$merchantID = $this->config->get('payment_payvector_mid');
			$transactionDateTime = date('Y-m-d H:i:s P');
			$callbackURL = str_replace("&amp;", "&", $this->url->link($this->ePath . $this->separator . 'callbackTransparent', '', 'SSL'));
			$preSharedKey = $this->config->get('payment_payvector_pre_shared_key');
			$hashMethod = $this->config->get('payment_payvector_hash_method');

			$stringToHash = PaymentFormHelper::generateStringToHash3DSecurePostAuthentication(
				$hashMethod,
				$preSharedKey,
				$merchantID,
				$this->config->get('payment_payvector_pass'),
				$crossReference,
				$transactionDateTime,
				$callbackURL,
				$PaRES
			);

			$data = array();

			$data['TransparentRedirectURL'] = $this->transparentRedirectURL;
			$data['HashDigest'] = PaymentFormHelper::calculateHashDigest($stringToHash, $preSharedKey, $hashMethod);
			$data['MerchantID'] = $merchantID;
			$data['CrossReference'] = $crossReference;
			$data['TransactionDateTime'] = $transactionDateTime;
			$data['CallbackURL'] = $callbackURL;
			$data['PaRes'] = $PaRES;

			$data['themePath'] = "default";
			$template = $this->ePathTemplate . 'payvector_3dsecure';

			$data['header'] = $this->load->controller('common/header');
			$data['footer'] = $this->load->controller('common/footer');

			$this->response->setOutput($this->load->view($template, $data));
		}
		//Handle Payment Complete
		else
		{
			$integrityVerified = PaymentFormHelper::checkIntegrityOfIncomingVariables(
				INCOMING_VARIABLE_SOURCE::RESULTS,
				$this->request->post,
				$this->config->get('payment_payvector_hash_method'),
				$this->config->get('payment_payvector_pre_shared_key'),
				$this->config->get('payment_payvector_mid'),
				$this->config->get('payment_payvector_pass')
			);

			if($integrityVerified)
			{
				$this->handleTransactionResults(new TransparentRedirectFinalTransactionResult(PaymentFormHelper::$trTransactionResult));
			}
			else
			{
				$this->load->model("checkout/order");

				$order_id = $this->getCurrentOrderId(PaymentFormHelper::$trTransactionResult);

				$this->model_checkout_order->addHistory($order_id, $this->config->get('payment_payvector_failed_order_status_id'), "Variable tampering detected", false);
				$this->session->data['payvector_error'] = "Variable tampering detected";
				$this->response->redirect($this->url->link($this->ePath . $this->separator . 'failure', '', 'SSL'));
			}
		}
	}

	/**
	 * @return RequestGatewayEntryPointList
	 * @throws Exception
	 */
	private function getEntryPointList()
	{
		$rgeplRequestGatewayEntryPointList = new \net\thepaymentgateway\paymentsystem\RequestGatewayEntryPointList();
		$geplGatewayEntryPointListXML = null;
		$result = PayVectorSQL::SELECT_GatewayEntryPoints($this->db);
		if($result->num_rows > 0)
		{
			$geplGatewayEntryPointListXML = $result->row['gateway_entry_point_object'];
		}
		if($geplGatewayEntryPointListXML != null)
		{
			$geplGatewayEntryPointList = GatewayEntryPointList::fromXmlString($geplGatewayEntryPointListXML);
			for($nCount = 0; $nCount < $geplGatewayEntryPointList->getCount(); $nCount++)
			{
				$geplGatewayEntryPoint = $geplGatewayEntryPointList->getAt($nCount);
				$rgeplRequestGatewayEntryPointList->add($geplGatewayEntryPoint->getEntryPointURL(), $geplGatewayEntryPoint->getMetric(), 1);
			}
		}
		else
		{
			// if we don't have a recent list in the database then just use blind processing
			/** @var $PaymentProcessorDomain string */
			$rgeplRequestGatewayEntryPointList->add("https://gw1." . $this->paymentProcessorDomain, 100, 2);
			$rgeplRequestGatewayEntryPointList->add("https://gw2." . $this->paymentProcessorDomain, 200, 2);
			$rgeplRequestGatewayEntryPointList->add("https://gw3." . $this->paymentProcessorDomain, 300, 2);
		}
		return $rgeplRequestGatewayEntryPointList;
	}
}

class PayVectorSQL
{
	/**
	 * @param  DB       $db OpenCart database object
	 * @return stdClass     Returns an stdClass on success, false on failure
	 */
	public static function SELECT_GatewayEntryPoints($db)
	{
		return $db->query("SELECT gateway_entry_point_object FROM `" . DB_PREFIX . "payvector_gateway_entry_points`
			WHERE `date_time_processed` >= NOW() - interval 10 minute
		;");
	}

	/**
	 * @param DB     $db                    OpenCart database object
	 * @param string $gatewayEntryPointList
	 */
	public static function UPDATE_GatewayEntryPoints($db, $gatewayEntryPointList)
	{
		$db->query("UPDATE `" . DB_PREFIX . "payvector_gateway_entry_points`
			SET gateway_entry_point_object = '$gatewayEntryPointList', date_time_processed = CURRENT_TIMESTAMP;
		");
	}

	/**
	 * @param  DB         $db         OpenCart database object
	 * @param  int        $customerID OpenCart customer id
	 * @return array|bool             Returns an associative array on success, false on failure
	 */
	public static function SELECT_CrossReference($db, $customerID)
	{
		if(!isset($customerID))
		{
			return;
		}
		else
		{
			return $db->query(
					"SELECT cross_reference, card_first_six, card_last_four, card_type, last_updated
					FROM `" . DB_PREFIX . "payvector_cross_reference`
					WHERE `customer_id` = $customerID AND `last_updated` >= NOW() - interval 400 day;"
				)->row;
		}

	}

	/**
	 * @param DB     $db                    OpenCart database object
	 * @param int    $customerID            OpenCart customer id
	 * @param string $crossReference        Cross reference of the latest successful transaction for this customer
	 * @param string $cardType              Type of the customers card
	 * @param string $cardLastFour          Last four digits of the customers card number
	 * @param bool   $overwriteCardNumber True if the $cardLastFour field should be updated even if it's null
	 */
	public static function UPDATE_CrossReference($db, $customerID, $crossReference, $cardType = null, $cardFirstSix = null, $cardLastFour = null, $overwriteCardNumber = false)
	{
		if(!isset($customerID))
		{
			return null;
		}
		if($db->query("SELECT customer_id FROM `" . DB_PREFIX . "payvector_cross_reference` WHERE customer_id = " . $customerID)->num_rows == 0)
		{
			$query = "INSERT INTO `" . DB_PREFIX . "payvector_cross_reference` (customer_id, cross_reference";
			if(isset($cardType))
			{
				$query .= ", card_type";
			}
			if(isset($cardFirstSix) || $overwriteCardNumber)
			{
				$query .= ", card_first_six";
			}
			if(isset($cardLastFour) || $overwriteCardNumber)
			{
				$query .= ", card_last_four";
			}
			$query .= ") VALUES('$customerID', '$crossReference'";
			if(isset($cardType))
			{
				$query .= ", '$cardType'";
			}
			if(isset($cardFirstSix))
			{
				$query .= ", '$cardFirstSix'";
			}
			if(isset($cardLastFour))
			{
				$query .= ", '$cardLastFour'";
			}
			else if($overwriteCardNumber)
			{
				$query .= ", NULL";
			}
			$query .= ");";
		}
		else
		{
			$query = "UPDATE `" . DB_PREFIX . "payvector_cross_reference` SET cross_reference = '$crossReference'";
			if(isset($cardType))
			{
				$query .= ", card_type = '$cardType'";
			}
			if(isset($cardFirstSix))
			{
				$query .= ", card_first_six = '$cardFirstSix'";
			}
			if(isset($cardLastFour))
			{
				$query .= ", card_last_four = '$cardLastFour'";
			}
			else if($overwriteCardNumber)
			{
				$query .= ", card_last_four = NULL";
			}
			$query .= ", last_updated = CURRENT_TIMESTAMP WHERE customer_id = '$customerID';";
		}
		$db->query($query);
	}
}