<?php
namespace PayVector\Payment\Model;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\Registry;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use \Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use \Magento\Customer\Model\Session as CustomerSession;
use Magento\Quote\Api\CartRepositoryInterface;

include_once(__DIR__ . DIRECTORY_SEPARATOR ."Common/TransactionProcessor.php");


class Payment extends \Magento\Payment\Model\Method\AbstractMethod {

    const PAYVECTOR_PAYMENT_CODE = 'payvector_payment';	

    protected $_code = self::PAYVECTOR_PAYMENT_CODE;

    /**
     *
     * @var \Magento\Framework\UrlInterface 
     */
    protected $_urlBuilder;
    
    protected $_checkoutSession;
	protected $_order;   
	protected $_orderFactory;	
	protected $endpoint  ;	
	
	protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
	protected $_canVoid = true;
	protected $invoiceService;
	protected $transaction;
	protected $registry;
	protected $invoiceRepository;
	protected $invoiceSender;
    protected $_logger;
	protected $Psrlogger;
	protected $response;
	protected $messageManager;
	protected $_store;
	protected $resolver;
	protected $configWriter;
	protected $_canAuthorize = true;
	protected $_canCapture = true;
	protected $customerSession; 
	protected $orderCollectionFactory;   
	protected $transaction_processor;
	protected $countryFactory;
	protected $isoCurrencyCode;
	protected $quoteRepository;		

    /**
     * 
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
      public function __construct(
        \Magento\Framework\Model\Context $context,        
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
 		Registry $registry, 
  	    \Magento\Sales\Model\OrderFactory $orderFactory,		  
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \PayVector\Payment\Helper\Payment $helper,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
        \Magento\Checkout\Model\Session $checkoutSession ,
     	InvoiceService $invoiceService,
		InvoiceSender $invoiceSender,
		InvoiceRepositoryInterface $invoiceRepository,
	    \Magento\Framework\DB\Transaction $transaction,
		\Psr\Log\LoggerInterface $Psrlogger ,
	  	\Magento\Framework\App\Response\Http $response,
		\Magento\Framework\Message\ManagerInterface  $messageManager,
		\Magento\Store\Api\Data\StoreInterface $store,
		\Magento\Framework\Locale\Resolver $resolver,		
		WriterInterface $configWriter,
		CartRepositoryInterface $quoteRepository
    ) {
        

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,			
            $paymentData,
            $scopeConfig,
            $logger			
        );
			
        
	    $this->helper = $helper;
        $this->orderSender = $orderSender;
        $this->httpClientFactory = $httpClientFactory;
        $this->_checkoutSession = $checkoutSession;
		$this->_orderFactory = $orderFactory;		    
	    $this->invoiceService = $invoiceService;
		$this->transaction = $transaction;  
	    $this->registry = $registry;
		$this->invoiceRepository = $invoiceRepository;
		$this->invoiceSender = $invoiceSender;  
	  	$this->_logger = $Psrlogger;
		$this->response = $response;  
	    $this->_store = $store;
		$this->messageManager = $messageManager;  
		$this->resolver = $resolver; 
		$this->configWriter = $configWriter;
		$this->paymentProcessorDomain = "payvector.net";
		$this->paymentProcessorHPFDomain = "https://mms.".$this->paymentProcessorDomain."/";
		$this->hostedPaymentFormURL = $this->paymentProcessorHPFDomain."Pages/PublicPages/PaymentForm.aspx";
		$this->hostedPaymentFormHandlerURL = $this->paymentProcessorHPFDomain."Pages/PublicPages/PaymentFormResultHandler.ashx";
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$this->customerSession = $objectManager->get(CustomerSession::class);
		$this->orderCollectionFactory = $objectManager->get(OrderCollectionFactory::class);  
		$this->transaction_processor = new \TransactionProcessor();   
		$this->isoCurrencyCode = '';		
    }

	
	protected function _getOrder()
    {
        if (!$this->_order) {
            $incrementId =  $this->_checkoutSession->getLastRealOrderId();
			
            $this->_order = $this->_orderFactory->create()->loadByIncrementId($incrementId);
        }
        return $this->_order;
    }
	public function canInvoice(){
		return true;
	}
    public function getRedirectUrl() {
        return $this->helper->getUrl($this->getConfigData('redirect_url'));
    }
	
	public function getThreedsUrl() {
        return $this->helper->getUrl($this->getConfigData('threeds_url'));
    }

	public function getCallbackUrl() {
        return $this->helper->getUrl($this->getConfigData('callback_url'));
    }
	public function getHashMethod() {
        return strtoupper($this->getConfigData('hashmethod'));
    }
	public function getPresharedKey() {
        return $this->getConfigData('presharedkey');
    }
	public function getResultDeliveryMethod() {
        //return $this->getConfigData('resultdeliverymethod');
		return 'SERVER_PULL';
    }
	
	public function getMerchantId() {
        return $this->getConfigData('merchantid');
    }
	
	public function getPassword() {
        return $this->getConfigData('password');
    }	
	public function getPaymentAction() {
        return $this->getConfigData('payment_action');
    }	
	
	public function getPaymentMode() {
        return $this->getConfigData('mode');
    }	
	

	public function setConfigData($field, $value)
    {        
        $path = 'payment/' . $this->_code . '/' . $field;
        return $this->configWriter->save($path, $value);
    }

	

	public function assignData(\Magento\Framework\DataObject $data)
	{
    	parent::assignData($data);

	    $additionalData = $data->getData('additional_data');
	    if (!is_array($additionalData)) {
        	return $this;
    	}

    	$info = $this->getInfoInstance();
		$info->setAdditionalInformation('cc_cid', $additionalData['cc_cid'] ?? null);
		$info->setAdditionalInformation('use_saved_card', $additionalData['use_saved_card'] ?? null);	
		if ($this->getPaymentMode() == 'hosted') return $this;

    // Card data
		$info->setAdditionalInformation('cc_owner', $additionalData['cc_owner'] ?? null);
    	$info->setAdditionalInformation('cc_number', $additionalData['cc_number'] ?? null);
    	$info->setAdditionalInformation('cc_exp_month', $additionalData['cc_exp_month'] ?? null);
    	$info->setAdditionalInformation('cc_exp_year', $additionalData['cc_exp_year'] ?? null);
    	
			
		

    // 3DS2 fields
    	$info->setAdditionalInformation('browser_user_agent', $additionalData['browser_user_agent'] ?? null);
    	$info->setAdditionalInformation('accept_header', $additionalData['accept_header'] ?? null);
    	$info->setAdditionalInformation('browser_language', $additionalData['browser_language'] ?? null);
    	$info->setAdditionalInformation('screen_width', $additionalData['screen_width'] ?? null);
    	$info->setAdditionalInformation('screen_height', $additionalData['screen_height'] ?? null);
    	$info->setAdditionalInformation('color_depth', $additionalData['color_depth'] ?? null);
    	$info->setAdditionalInformation('java_enabled', $additionalData['java_enabled'] ?? null);
    	$info->setAdditionalInformation('timezone_offset', $additionalData['timezone_offset'] ?? null);

    	return $this;
	}
	

	public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {		
		return $this->_DoTransaction($payment, $amount);    
    }
	public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
		return $this->_DoTransaction($payment, $amount);    
    }
	private function _DoTransaction(\Magento\Payment\Model\InfoInterface $payment, $amount)
	{
		$use_saved_card = $payment->getAdditionalInformation('use_saved_card');	
		if (($this->getPaymentMode() == 'hosted') && (!$use_saved_card) ) {
			$payment->setIsTransactionPending(true); 
			$payment->setIsTransactionClosed(0);
			return  $this;
		}
			
		if (!$use_saved_card) {
			$cardData = [			        			
				'cardNumber' => $payment->getAdditionalInformation('cc_number'),
	       		'cardOwner' =>$payment->getAdditionalInformation('cc_owner'),
        		'expMonth' =>$payment->getAdditionalInformation('cc_exp_month'),
        		'expYear' =>$payment->getAdditionalInformation('cc_exp_year'),
				'cvv' =>$payment->getAdditionalInformation('cc_cid'),
        		'issue_number' =>''
			];		
		}

			$threeDSData = [
        		'user_agent'       => $payment->getAdditionalInformation('browser_user_agent'),
        		'accept_header'    => $payment->getAdditionalInformation('accept_header'),
        		'language'         => $payment->getAdditionalInformation('browser_language'),
        		'screen_width'     => $payment->getAdditionalInformation('screen_width'),
        		'screen_height'    => $payment->getAdditionalInformation('screen_height'),
        		'color_depth'      => $payment->getAdditionalInformation('color_depth'),
        		'java_enabled'     => $payment->getAdditionalInformation('java_enabled'),
        		'timezone_offset'  => $payment->getAdditionalInformation('timezone_offset'),
    		];
		

			$order = $payment->getOrder();
			$this->_setTransaction($order, $amount);
			//3DSv2 Parameters							
			$this->transaction_processor->setJavaEnabled($threeDSData['java_enabled']);
			$this->transaction_processor->setJavaScriptEnabled('true');
			$this->transaction_processor->setScreenWidth($threeDSData['screen_width']);
			$this->transaction_processor->setScreenHeight($threeDSData['screen_height']);
			$this->transaction_processor->setScreenColourDepth($threeDSData['color_depth']);
			$this->transaction_processor->setTimezoneOffset($threeDSData['timezone_offset']);				
			$this->transaction_processor->setLanguage($threeDSData['language']);

			$ChURL = $this->getThreedsUrl();
			$ChURL = htmlspecialchars($ChURL, ENT_XML1, 'UTF-8');
			
			$this->transaction_processor->setChallengeNotificationURL($ChURL);				
			$this->transaction_processor->setFingerprintNotificationURL($ChURL);
			$this->transaction_processor->setRgeplRequestGatewayEntryPointList($this->getEntryPointList());
			
			$paymentAction = $this->getPaymentAction();
			$szTransactionType = '';
			if($paymentAction == \PayVector\Payment\Model\Source\PaymentAction::ACTION_AUTHORIZE_CAPTURE)
			{
				$szTransactionType = "SALE";
			}
			else if($paymentAction == \PayVector\Payment\Model\Source\PaymentAction::ACTION_AUTHORIZE)
			{
				$szTransactionType = "PREAUTH";
			}
			$this->transaction_processor->setTransactionType($szTransactionType);

			if ($use_saved_card) {
				## saved card 
					$cvv2 = $payment->getAdditionalInformation('cc_cid');
					$this->transaction_processor->setCV2($cvv2);
					$savedcard = $this->getLastOrderCardData();					
					$cross_reference = $savedcard['cross_reference'];
					$final_transaction_result = $this->transaction_processor->doCrossReferenceTransaction(
						$cross_reference,
						"true",
						$this->_checkoutSession);
				

			}
			else {
				##direct api
				$this->transaction_processor->setCV2($cardData['cvv']);

				$final_transaction_result = $this->transaction_processor->doCardDetailsTransaction(
						$cardData['cardOwner'],
						$cardData['cardNumber'],
						$cardData['expMonth'],
						$cardData['expYear'],
						($cardData['issue_number'] ?? ''),
						$this->_checkoutSession );
			}
		$payment->unsAdditionalInformation('cc_number');
		$payment->unsAdditionalInformation('cc_owner');
		$payment->unsAdditionalInformation('cc_exp_month');
		$payment->unsAdditionalInformation('cc_exp_year');
		$payment->unsAdditionalInformation('cc_cid');

		$payment->unsAdditionalInformation('browser_user_agent');
		$payment->unsAdditionalInformation('accept_header');
		$payment->unsAdditionalInformation('browser_language');
		$payment->unsAdditionalInformation('screen_width');
		$payment->unsAdditionalInformation('screen_height');
		$payment->unsAdditionalInformation('color_depth');
		$payment->unsAdditionalInformation('java_enabled');
		$payment->unsAdditionalInformation('timezone_offset');			

		return $this->_handleTransactionResults($payment, $final_transaction_result,$amount);	
	}
		
	private function _setTransaction($order, $amount = 0)	
	{
		$billing = $order->getBillingAddress();							
		$order_id = $order->getIncrementId();
			
		$merchant_id = $this->getMerchantId();
		$merchant_password = $this->getPassword();
		$this->transaction_processor->setMerchantID($merchant_id);
		$this->transaction_processor->setMerchantPassword($merchant_password);
			
		$currency_code = $order->getBaseCurrencyCode();	
		if ($amount == 0) $amount = number_format(($order->getGrandTotal()), 2,'.', '');	
		
		$iso_country_code = '';
		$country_code = $billing->getCountryId();			
		$iso_country_list = \ISOHelper::getISOCountryList();

		if(!empty($country_code) && $iso_country_list->getISOCountry($country_code, $iso_country))
		{				
			$iso_country_code = $iso_country->getISOCode();
		}

		$amount = $this->_SetAmount($currency_code, $amount);
						

		$this->transaction_processor->setCurrencyCode($this->isoCurrencyCode);
		$this->transaction_processor->setAmount($amount);
		$this->transaction_processor->setOrderID($order_id);
		$this->transaction_processor->setOrderDescription('Order No: ' . $order_id);
		$this->transaction_processor->setCustomerName($billing->getFirstname() . " " . $billing->getLastname());		
		$this->transaction_processor->setAddress1($billing->getStreetLine(1));
		$this->transaction_processor->setAddress2($billing->getStreetLine(2));
		$this->transaction_processor->setCity($billing->getCity());
		$this->transaction_processor->setState($billing->getRegion());
		$this->transaction_processor->setPostcode($billing->getPostcode());
		$this->transaction_processor->setCountryCode($iso_country_code);
		$this->transaction_processor->setEmailAddress($order->getCustomerEmail());
		$this->transaction_processor->setPhoneNumber($billing->getTelephone());
		$this->transaction_processor->setIPAddress($_SERVER['REMOTE_ADDR']);

			
	}
	
	public function getLastOrderCardData()
    {
        if (!$this->customerSession->isLoggedIn()) {
            return null;
        }

        $customerId = $this->customerSession->getCustomerId();
		$orderCollection = $this->orderCollectionFactory->create();
		 $orderCollection->getSelect()->join(
        ['payment' => $orderCollection->getTable('sales_order_payment')],
        'main_table.entity_id = payment.parent_id',
        ['method', 'cc_last_4', 'cc_type', 'last_trans_id']
    )->where('payment.method = ?', 'payvector_payment')
     ->where('payment.cc_last_4 IS NOT NULL AND payment.cc_last_4 != ?', '');


        
		$orderCollection->addFieldToFilter('customer_id', $customerId)
    	->setOrder('entity_id', 'DESC')
    	->setPageSize(5)
    	->load();

        foreach ($orderCollection as $order) {
            $payment = $order->getPayment();

            if ($payment && $payment->getCcLast4()) {                
                return [
                    'cross_reference' => $payment->getLastTransId(),
                    'last4' => $payment->getCcLast4(),
                    'card_type'      => $payment->getCcType(),
                ];
            }
        }

        return null;
    }
	
	private function _handleTransactionResults($payment, $final_transaction_result,$amount )
	{		
		
		$order = $payment->getOrder();
		$szOrderID = $order->getIncrementId();
		$transCardData = [];
		if($final_transaction_result->transactionProcessed() && $final_transaction_result->transactionSuccessful())
		{			
			$trans_id = $final_transaction_result->getCrossReference();
			
			$card_type = $final_transaction_result->getCardType();
			$card_last_four = $final_transaction_result->getCardLastFour($this->_checkoutSession);			
    		$payment->setCcType($card_type);
    		$payment->setCcLast4($card_last_four);	
			$payment->setStatus('APPROVED');
			$payment->setTransactionId($trans_id)->setIsTransactionClosed(0);
			

			$amount_received = $final_transaction_result->getAmountReceived();
			$currency_code = $order->getBaseCurrencyCode();
			$amount_received = $this->_GetAmount($currency_code,$amount_received);
			
			$order->setTotalPaid($amount_received);
			$order->addStatusHistoryComment(__('Payment successful. Transaction ID: %1', $trans_id));			
			return $this;		
		}
		else
		{
			
			//run 3DSecure if required
			if($final_transaction_result->getStatusCode() === 3)
			{				
				$this->_checkoutSession->setData('payvector_md', $final_transaction_result->getCrossReference() );
				$this->_checkoutSession->setData('payvector_MethodURL', $final_transaction_result->getThreeDSecureOutputData()->getMethodURL() );
				$this->_checkoutSession->setData('payvector_ThreeDSMethodData', $final_transaction_result->getThreeDSecureOutputData()->getMethodData());
				
				 $payment->setAdditionalInformation('three_ds_status', 'awaiting');
				 $payment->setIsTransactionPending(true); 
				 $payment->setIsTransactionClosed(0);
				 return $this;	
			}	
			{
					throw new \Magento\Framework\Exception\LocalizedException(__(
           			 json_encode([
              			'message' => 'Card Declined',
						'type' => 'error'
            		])));				
			}		
		}	
		
	}
	public function handleThreeDSResults($postData){		
		$blockdata = [];
		$template = '';
		
		$cres = $postData['cres'] ?? '';
		$FormMode = $postData['FormMode'] ?? 'step1';
		$ThreeDSMethodData = $postData['threeDSMethodData'] ?? '';
		if (empty($postData)){
			$order = $this->_getOrder();
			$payment = $order->getPayment();
			if ($payment->getAdditionalInformation('three_ds_status') == 'awaiting') $FormMode = 'step0';
			else {				
				$blockdata['FormAction'] = $this->helper->getUrl('checkout/onepage/success');				
				$template = 'finalform';
				$data = [];
				$data['blockdata'] = $blockdata;
    			$data['template'] = $template;
				return $data;
			}


		}
		
		if (empty($cres)){
			switch ($FormMode){
				case "step0":
					$ThreeDSMethodData = $this->_checkoutSession->getData('payvector_ThreeDSMethodData');
					$blockdata['FormAttributes'] = " target=\"threeDSecureFrame\"";
					$blockdata['params'] =  ["ThreeDSMethodData"=>$ThreeDSMethodData];
					$blockdata['FormAction'] = $this->_checkoutSession->getData('payvector_MethodURL');
					$template = 'landingform';
				break;
				case "step1":
					$blockdata['FormAttributes'] = " target=\"_parent\"";
					$blockdata['FormAction'] = "";
					$blockdata['params']  = ["threeDSMethodData"=>$ThreeDSMethodData, "FormMode"=> "step2"];	
					$template = 'innerform';										
				break;
				case "step2":
					$crossReference = $this->_checkoutSession->getData('payvector_md');
					$tdseThreeDSecureEnvironment = new \net\thepaymentgateway\paymentsystem\ThreeDSecureEnvironment($this->getEntryPointList());
					$tdseThreeDSecureEnvironment->getMerchantAuthentication()->setMerchantID($this->getMerchantId());
					$tdseThreeDSecureEnvironment->getMerchantAuthentication()->setPassword($this->getPassword());
					$tdseThreeDSecureEnvironment->getThreeDSecureEnvironmentData()->setCrossReference($crossReference);
					$tdseThreeDSecureEnvironment->getThreeDSecureEnvironmentData()->setMethodData($ThreeDSMethodData);
					$boTransactionProcessed = $tdseThreeDSecureEnvironment->processTransaction($tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData);
					if($tdsarThreeDSecureAuthenticationResult->getStatusCode() === 3){
						$CREQ = $todTransactionOutputData->getThreeDSecureOutputData()->getCREQ();
						$ThreeDSSessionData = \PaymentFormHelper::base64UrlEncode($todTransactionOutputData->getCrossReference());
						$blockdata['FormAttributes']  = " target=\"threeDSecureFrame\"";
						$blockdata['FormAction'] = $todTransactionOutputData->getThreeDSecureOutputData()->getACSURL();
						$blockdata['params'] = ['creq' => $CREQ, 'threeDSSessionData' => $ThreeDSSessionData];
						$template = 'landingform';		
					}
					else {	
						$finalTransactionResult = new \ThreeDSecureFinalTransactionResult($boTransactionProcessed, $tdseThreeDSecureEnvironment, $tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData, $this->_checkoutSession);
						$PayAction  = $this->_handleHostedTransactionResults($finalTransactionResult);
						$blockdata['FormAttributes']  = " target=\"_parent\"";
						if ($PayAction) $blockdata['FormAction'] = $this->helper->getUrl('checkout/onepage/success');
						else $blockdata['FormAction'] = $this->helper->getUrl('checkout/onepage/failure');
						$blockdata['PayAction'] = $PayAction;
						$blockdata['params'] = [];
						$template = 'finalform';
					}
																
				break;
			}
		}
		else {
			$threeDSSessionData =$postData['threeDSSessionData'] ?? ''; 
			switch ($FormMode)
			{
				case "step1":
					$blockdata['FormAttributes']= " target=\"_parent\"";
					$blockdata['FormAction'] = "";
					$blockdata['params'] = ["cres"=>$cres,"threeDSSessionData"=>$threeDSSessionData, "FormMode"=> "step3"];
					$template = 'innerform';
				break;
				case "step3":
					$CrossReference = \PaymentFormHelper::base64UrlDecode($threeDSSessionData);						
					$tdsaThreeDSecureAuthentication = new \net\thepaymentgateway\paymentsystem\ThreeDSecureAuthentication($this->getEntryPointList());
					$tdsaThreeDSecureAuthentication->getMerchantAuthentication()->setMerchantID($this->getMerchantId());
					$tdsaThreeDSecureAuthentication->getMerchantAuthentication()->setPassword($this->getPassword());
					$tdsaThreeDSecureAuthentication->getThreeDSecureInputData()->setCrossReference($CrossReference);
					$tdsaThreeDSecureAuthentication->getThreeDSecureInputData()->setCRES($cres);
					$boTransactionProcessed = $tdsaThreeDSecureAuthentication->processTransaction($tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData);	
					$finalTransactionResult = new \ThreeDSecureFinalTransactionResult($boTransactionProcessed, $tdsaThreeDSecureAuthentication, $tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData, $this->_checkoutSession);							
					$PayAction  = $this->_handleHostedTransactionResults($finalTransactionResult);
					$blockdata['FormAttributes']  = " target=\"_parent\"";
					if ($PayAction) $blockdata['FormAction'] = $this->helper->getUrl('checkout/onepage/success');
					else $blockdata['FormAction'] = $this->helper->getUrl('checkout/onepage/failure');
					$blockdata['PayAction'] = $PayAction;
					$blockdata['params'] = [];
					$template = 'finalform';
						
											
				break;

			}
		}
		
		$data = [];
		$data['blockdata'] = $blockdata;
    	$data['template'] = $template;
		return $data;
	}
	

	private function getEntryPointList()
	{		
		$rgepl_request_gateway_entry_point_list = new \net\thepaymentgateway\paymentsystem\RequestGatewayEntryPointList();		
		
		$rgepl_request_gateway_entry_point_list->add("https://gw1." . $this->paymentProcessorDomain, 100, 2);
		$rgepl_request_gateway_entry_point_list->add("https://gw2." . $this->paymentProcessorDomain, 200, 2);
		$rgepl_request_gateway_entry_point_list->add("https://gw3." . $this->paymentProcessorDomain, 300, 2);
		

		return $rgepl_request_gateway_entry_point_list;
	}
		
    /**
     * Return url according to environment
     * @return string
     */
	//get payment page url
    public function getPayVectorUrl() {		
		$order = $this->_getOrder();
		$this->_setTransaction($order);				
		$CallbackUrl = $this->getCallbackUrl();				
		$view_array = $this->transaction_processor->getHostedPaymentForm(
					$CallbackUrl,
					$CallbackUrl,
					$this->getPresharedKey(),
					$this->getHashMethod(),
					$this->getResultDeliveryMethod(),		
					false,
					$this->_checkoutSession);
		
		$redirectUrl = $this->hostedPaymentFormURL . '?' . http_build_query($view_array);;
		
		$this->response->setRedirect($redirectUrl);
    }
	
    protected function getOrderById($order_id)
    {
		return $this->_orderFactory->create()->loadByIncrementId($order_id);        
    }
	private function _handleHostedTransactionResults($final_transaction_result){
		$OrderID = $final_transaction_result->getOrderID($this->_checkoutSession);
		$order = $this->_getOrder();				
		if (!$order->getId()){
			$order = $this->getOrderById($OrderID);
		}
		$payment = $order->getPayment();
		if($final_transaction_result->transactionProcessed() && $final_transaction_result->transactionSuccessful())
		{
			$trans_id = $final_transaction_result->getCrossReference();
			$card_type = $final_transaction_result->getCardType();
			$card_last_four = $final_transaction_result->getCardLastFour($this->_checkoutSession);
			$amount_received = $final_transaction_result->getAmountReceived();	
			
			$currency_code = $order->getBaseCurrencyCode();				
			$amount_received = $this->_GetAmount($currency_code,$amount_received);			
			$payment->setTransactionId($trans_id); 											
			$payment->setLastTransId($trans_id);
    		$payment->setCcType($card_type);
    		$payment->setCcLast4($card_last_four);	
			$payment->setStatus('APPROVED');					
			$payment->setIsTransactionClosed(true);	
			$transaction = $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);	
			$transaction->setIsClosed(true);					
			$order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
			$order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
			$order->setTotalPaid($amount_received);
			$order->addStatusHistoryComment(__('Payment successful. Transaction ID: %1', $trans_id));
        	$order->save();
			return true;
		}
		else {					
			$this->messageManager->addErrorMessage( __('Your payment was unsuccessful. Please try again. -'.$final_transaction_result->getMessage()));			
		 	$payment->setIsTransactionDenied(true);
		    $payment->setIsTransactionClosed(0);
			$order->addStatusHistoryComment(__('Payment failed'));
			$order->save();
			return false;
		}

	}

	
	public function validateResponse(){		
		
		$hash_matches = false;
		$validate_error_message = "";		
			
			$hash_matches = \PaymentFormHelper::validateTransactionResult_SERVER_PULL(
					$this->getMerchantId(),
					$this->getPassword(),					
					$this->getPresharedKey(),
					$this->getHashMethod(),
					$_GET,			
					$this->hostedPaymentFormHandlerURL,
					$transaction_result,
					$validate_error_message
				);
		
		if(!$hash_matches)
		{			
			$this->messageManager->addErrorMessage( __($validate_error_message));
			return false;
			
		}
		return $this->_handleHostedTransactionResults(new \HostedPaymentFormFinalTransactionResult($transaction_result));	
			
	}

	public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
	{
		$trans_id = $payment->getParentTransactionId();
		
		$order = $payment->getOrder();
		$currency_code = $order->getBaseCurrencyCode();			
		
		$amount = $this->_SetAmount($currency_code, $amount);
		$order_id = $order->getId();
		$reason = 'Refund';		
		try {
			$crtCrossReferenceTransaction = new \net\thepaymentgateway\paymentsystem\CrossReferenceTransaction($this->getEntryPointList());

			$crtCrossReferenceTransaction->getMerchantAuthentication()->setMerchantID($this->getMerchantId());
			$crtCrossReferenceTransaction->getMerchantAuthentication()->setPassword($this->getPassword());

			$crtCrossReferenceTransaction->getTransactionDetails()->getMessageDetails()->setTransactionType("REFUND");
			$crtCrossReferenceTransaction->getTransactionDetails()->getMessageDetails()->setCrossReference($trans_id);

			$crtCrossReferenceTransaction->getTransactionDetails()->getAmount()->setValue((string) $amount);
			$crtCrossReferenceTransaction->getTransactionDetails()->getCurrencyCode()->setValue($this->isoCurrencyCode);

			$crtCrossReferenceTransaction->getTransactionDetails()->setOrderID((string) $order_id);
			$crtCrossReferenceTransaction->getTransactionDetails()->setOrderDescription((string) $reason);

			$boTransactionProcessed = $crtCrossReferenceTransaction->processTransaction($crtrCrossReferenceTransactionResult, $todTransactionOutputData);
			if ($boTransactionProcessed) return $this;
			else throw new \Magento\Framework\Exception\LocalizedException(__('Refund failed'));

	  	} catch (\Exception $e) 
	  	{
        	throw new \Magento\Framework\Exception\LocalizedException(__('Refund failed: %1', $e->getMessage()));
		}    
	
		return false;
	}
	private function _SetAmount($currency_code, $amount)
	{		
		$amount = number_format($amount, 2,'.', '');	
		$iso_currency_list = \ISOHelper::getISOCurrencyList();
		if(!empty($currency_code) && $iso_currency_list->getISOCurrency($currency_code, $iso_currency))
		{				
			$this->isoCurrencyCode = $iso_currency->getISOCode();	
						
			$amount = (string) $amount;
			$amount = round($amount * ("1" . str_repeat(0, $iso_currency->getExponent())));			
		}
		return $amount;
	}

	private function _GetAmount($currency_code, $amount)
	{		
		$amount = number_format($amount, 2,'.', '');	
		$iso_currency_list = \ISOHelper::getISOCurrencyList();
		if(!empty($currency_code) && $iso_currency_list->getISOCurrency($currency_code, $iso_currency))
		{				
			$this->isoCurrencyCode = $iso_currency->getISOCode();			
			if($iso_currency->getExponent() != 0 && isset($amount))
			{
				$amount = round($amount / pow(10, $iso_currency->getExponent()), $iso_currency->getExponent());
			}					
		}
		return $amount;
	}	
	
	protected function createInvoice($order, $trans_id)
    {
        
        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->register();
		$invoice->setTransactionId($trans_id);
        $invoice = $this->invoiceRepository->save($invoice);
		
        $this->registry->register('current_invoice', $invoice);

        $transactionSave = 
			$this->transaction->addObject($invoice)
			->addObject($invoice->getOrder());

        $transactionSave->save();
        $this->invoiceSender->send($invoice);

    }
}
