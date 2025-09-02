<?php
require_once __DIR__.'/lib/TransactionProcessor.php';
require_once __DIR__.'/util.php';

class Gateway 
{
    private $_config;
    private $_basket;
    private $_result_message;
    private $_url;
    private $_path;
    
    private $sessionHandler;
    private $isoCurrencyCode = '';
    protected $transaction_processor;
    private $PaymentProcessorFullDomain  = '';
    private $paymentProcessorDomain = '';
    private $hostedPaymentFormURL = '';
    private $hostedPaymentFormHandlerURL = '';

    public $module;

    public function __construct($module = false, $basket = false) 
	{
        $this -> _db = &$GLOBALS['db'];

        $this -> module = $module;
        $this -> _basket = &$GLOBALS['cart'] -> basket;
        
        $this->paymentProcessorDomain = "payvector.net";
        $PaymentProcessorPort   = 443;
        $this->PaymentProcessorFullDomain = $this->paymentProcessorDomain . "/";
		
		$paymentProcessorHPFDomain = "https://mms.".$this->paymentProcessorDomain."/";
		$this->hostedPaymentFormURL = $paymentProcessorHPFDomain."Pages/PublicPages/PaymentForm.aspx";
        $this->hostedPaymentFormHandlerURL = $paymentProcessorHPFDomain."Pages/PublicPages/PaymentFormResultHandler.ashx";

        $this->sessionHandler = $GLOBALS['session'];   
        $this->transaction_processor = new \TransactionProcessor();
    }

    ##################################################
    public function transfer() 
	{
        switch($this -> module['mode']) 
		{
            case 'hpf-1' :
                $transfer = array(
                    'action' => "https://mms." . $this->PaymentProcessorFullDomain . "Pages/PublicPages/PaymentForm.aspx",
                    'method' => 'post',
                    'target' => '_self',
                    'submit' => '');
                break;
            case 'tr' :
                $transfer = array(
                    'action' => "https://mms." . $this->PaymentProcessorFullDomain . "Pages/PublicPages/TransparentRedirect.aspx",
                    'method' => 'post',
                    'target' => '_self',
                    'submit' => '');
                break;
            default :
                $transfer = array(
                    'action' => "",
                    'method' => 'post',
                    'target' => '_self',
                    'submit' => '');
                break;
        }

        return $transfer;
    }

    ##################################################
    public function repeatVariables() 
	{
        return (isset($hidden)) ? $hidden : false;
    }

    public function fixedVariables() 
	{
        $hidden['gateway'] = basename(dirname(__FILE__));

        switch($this -> module['mode'])
		{
            case "tr" :
                break;

            default :
                break;
        }

        return (isset($hidden)) ? $hidden : false;
    }
    
    private function getEntryPointList()
	{		
		$rgepl_request_gateway_entry_point_list = new \net\thepaymentgateway\paymentsystem\RequestGatewayEntryPointList();		
		
		$rgepl_request_gateway_entry_point_list->add("https://gw1." . $this->paymentProcessorDomain, 100, 2);
		$rgepl_request_gateway_entry_point_list->add("https://gw2." . $this->paymentProcessorDomain, 200, 2);
		$rgepl_request_gateway_entry_point_list->add("https://gw3." . $this->paymentProcessorDomain, 300, 2);				

		return $rgepl_request_gateway_entry_point_list;
	}
    
    public function call() 
	{     
        $this->hpfcallback();
        return true;
    }
    public function hpfcallback() 
	{     
        if ($this -> module['testMode']) 
		{
            $merchant_id = $this -> module['mid_test'];
            $merchant_password = $this -> module['pass_test'];
        } 
		else 
		{            
            $merchant_id = $this -> module['mid_prod'];
            $merchant_password = $this -> module['pass_prod'];
        }	        
        	
        if($this -> module['hpfResultDeliveryMethod'] === 'POST')
		{                
			$hash_matches = PaymentFormHelper::validateTransactionResult_POST(
				$merchant_id,
				$merchant_password,
				$this->module['hpfPreSharedKey'],
				$this->module['hpfHashMethod'],
				$_POST,
				$transaction_result,
				$validate_error_message);
		}
		else if($this -> module['hpfResultDeliveryMethod'] === 'SERVER_PULL')
		{                
		    $hash_matches = PaymentFormHelper::validateTransactionResult_SERVER_PULL(
			    $merchant_id,
			    $merchant_password,
			    $this->module['hpfPreSharedKey'],
			    $this->module['hpfHashMethod'],
			    $_GET,
			    $this->hostedPaymentFormHandlerURL,
			    $transaction_result,
			    $validate_error_message);
               
		}   

        if(!$hash_matches)
	    {		                
            $this->_fail($validate_error_message);
			return false;			
		}
            
		return $this->handleTransactionResults(new \HostedPaymentFormFinalTransactionResult($transaction_result));
    }
    public function process() 
	{
        $mode = $_GET['mode'] ?? '';   
        if ($mode == 'callback') 
		{            
            return $this->hpfcallback();
        } 
        $order = Order::getInstance();
        $cart_order_id = $this -> _basket['cart_order_id'];
        $order_summary = $order -> getSummary($cart_order_id);                
        $this->_setTransaction();
        
        //3DSv2 Parameters
        
        $this->transaction_processor->setJavaEnabled('false');
		$this->transaction_processor->setJavaScriptEnabled('true');
		$this->transaction_processor->setScreenWidth($_POST['browserscreenwidth']);
		$this->transaction_processor->setScreenHeight($_POST['browserscreenheight']);
		$this->transaction_processor->setScreenColourDepth($_POST['browsercolordepth']);
		$this->transaction_processor->setTimezoneOffset($_POST['browsertz']);				
		$this->transaction_processor->setLanguage($_POST['browserlanguage']);
        $this->transaction_processor->setRgeplRequestGatewayEntryPointList($this->getEntryPointList());	

        if ($_POST['CurrentTransactionType'] == 'cdt') 
		{        
			switch ($this -> module['mode']) 
			{            
				case 'hpf' :
					if($this -> module['hpfResultDeliveryMethod'] === 'SERVER_PULL')
						$CallbackUrl = $GLOBALS['storeURL'].'/index.php?_g=remote&type=gateway&cmd=process&module=PayVector&mode=callback&token='.$this->sessionHandler->getToken();
					else 
						$CallbackUrl = $GLOBALS['storeURL'].'/modules/gateway/PayVector/callback.php?token='.$this->sessionHandler->getToken();
                
					$view_array = $this->transaction_processor->getHostedPaymentForm(
						$CallbackUrl,
						$CallbackUrl,
						$this->module['hpfPreSharedKey'],
						$this->module['hpfHashMethod'],
						$this->module['hpfResultDeliveryMethod'],		
						false,
						$this->sessionHandler);
					$redirectUrl = $this->hostedPaymentFormURL . '?' . http_build_query($view_array);
					header('Location: ' . $redirectUrl);
					
					break;
				case 'api' :           
					$ChURL = $GLOBALS['storeURL'].'/modules/gateway/PayVector/3DSecure.php?token='.$this->sessionHandler->getToken();                
					$ChURL = htmlspecialchars($ChURL, ENT_XML1, 'UTF-8');
					$this->transaction_processor->setChallengeNotificationURL($ChURL);				
					$this->transaction_processor->setFingerprintNotificationURL($ChURL);			    		                
					$this->transaction_processor->setCV2($_POST['CV2']);

					$final_transaction_result = $this->transaction_processor->doCardDetailsTransaction(
						$_POST['CardName'],
						$_POST['CardNumber'],
						substr($_POST['ExpiryDateMonth'], 0, 2),
						substr($_POST['ExpiryDateYear'], -2),
						($_POST['issue_number'] ?? ''),
						$this->sessionHandler);

					return $this->handleTransactionResults($final_transaction_result);                                
					break;
				default :
					break;
			}
		}
		else 
		{
			$this->transaction_processor->setCV2($_POST['CV2_CTR']);
			$crtDetails = $GLOBALS['db'] -> misc(PayVectorSQL::selectCRT_CrossReferenceDetails($order_summary['customer_id']));

			if (is_array($crtDetails)) 
			{
               $crtDetails = $crtDetails[0];
               $cross_reference = $crtDetails['CrossReference'];               
               $final_transaction_result = $this->transaction_processor->doCrossReferenceTransaction(
						$cross_reference,"true", $this->sessionHandler);
               
               return $this->handleTransactionResults($final_transaction_result);
			}
			else 
			{
				$this->_fail("CrossReference Not Found");
				return false;
			}
		}

        return false;
    }

    private function _setTransaction()	
	{
        $tax = Tax::getInstance();        
        $currency_code = $tax->_currency_vars['code'];
        $amount = round(($this->_basket['total'] * $tax->_currency_vars['value']), 2) ;
        
        $amount = number_format($amount, 2, '.', '');
        $order_id = $this->_basket['cart_order_id'];

        if ($this -> module['testMode']) 
		{
            $merchant_id = $this -> module['mid_test'];
            $merchant_password = $this -> module['pass_test'];
        } 
		else 
		{
            $merchant_id = $this -> module['mid_prod'];
            $merchant_password = $this -> module['pass_prod'];
        }			
		
		$this->transaction_processor->setMerchantID($merchant_id);
		$this->transaction_processor->setMerchantPassword($merchant_password);
			
        $country_code = $this -> _basket['billing_address']['country_iso'];

		$iso_country_code = '';				
		$iso_country_list = \ISOHelper::getISOCountryList();

		if(!empty($country_code) && $iso_country_list->getISOCountry($country_code, $iso_country))
		{				
			$iso_country_code = $iso_country->getISOCode();
		}
        if ($this -> module['mode'] == 'api')
		{
			$iso_country_code  = $_POST['CountryCode'];
		}

		$amount = $this->_SetAmount($currency_code, $amount);
						
		$this->transaction_processor->setCurrencyCode($this->isoCurrencyCode);
		$this->transaction_processor->setAmount($amount);
		$this->transaction_processor->setOrderID($order_id);
		$this->transaction_processor->setOrderDescription('Order No: ' . $order_id);
        $this->transaction_processor->setCustomerName($this -> _basket['billing_address']['first_name'] . " " . $this -> _basket['billing_address']['last_name']);		
        
		if ($this -> module['mode'] == 'api') 
		{
		    $this->transaction_processor->setAddress1($_POST['Address1']);
		    $this->transaction_processor->setAddress2($_POST['Address2']);
            $this->transaction_processor->setAddress3($_POST['Address3']);
            $this->transaction_processor->setAddress4($_POST['Address4']);            
		    $this->transaction_processor->setCity($_POST['City']);
		    $this->transaction_processor->setState($_POST['State']);
		    $this->transaction_processor->setPostcode($_POST['Postcode']);
		    $this->transaction_processor->setCountryCode($iso_country_code);
		    $this->transaction_processor->setEmailAddress($_POST['EmailAddress']);
		    $this->transaction_processor->setPhoneNumber($_POST['PhoneNumber']);
        } 
		else 
		{
            $this->transaction_processor->setCustomerName($this -> _basket['billing_address']['first_name'] . " " . $this -> _basket['billing_address']['last_name']);		
		    $this->transaction_processor->setAddress1($this -> _basket['billing_address']['line1']);
		    $this->transaction_processor->setAddress2($this -> _basket['billing_address']['line2']);
		    $this->transaction_processor->setCity($this -> _basket['billing_address']['town']);
		    $this->transaction_processor->setState($this -> _basket['billing_address']['state']);
		    $this->transaction_processor->setPostcode($this -> _basket['billing_address']['postcode']);
		    $this->transaction_processor->setCountryCode($iso_country_code);
		    $this->transaction_processor->setEmailAddress($this -> _basket['billing_address']['email']);
		    $this->transaction_processor->setPhoneNumber($this -> _basket['billing_address']['phone']);    
        }   
		
		$this->transaction_processor->setIPAddress($_SERVER['REMOTE_ADDR']);			
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

    ##################################################

    private function formatMonth(int $val): string
    {
        $date = DateTime::createFromFormat('!m', (string)$val);
        return $val . " - " . $date->format('M'); // "M" gives short month name (Jan, Feb, etc.)
    }
    private function _fail($szNotify) 
	{    
        $GLOBALS['gui'] -> setError($szNotify);
        $redirect_to = 'checkout';
        $redirect_to = $GLOBALS['storeURL'].'/index.php?_a='.$redirect_to;
        httpredir($redirect_to);
    }

    public function threedsecure($FormAttributes, $params,$MethodURL)
	{
        $file_name = 'threedsecurelandingform.tpl';
        $form_file = $GLOBALS['gui'] -> getCustomModuleSkin('gateway', dirname(__FILE__), $file_name);
        $GLOBALS['gui'] -> changeTemplateDir($form_file);
        $GLOBALS['smarty'] -> assign('FormAttributes', $FormAttributes);
        $GLOBALS['smarty'] -> assign('params', $params);
        $GLOBALS['smarty'] -> assign('FormAction', $MethodURL);
        $return = $GLOBALS['smarty'] -> fetch($file_name);
        $GLOBALS['gui'] -> changeTemplateDir();
        return $return;         
    }

    public function form() 
	{
        $return = '';    
        $mode = $_GET['mode'] ?? '';    
        if ($mode == 'threeds') 
		{      
            if ($this -> module['testMode']) 
			{
                $merchant_id = $this -> module['mid_test'];
                $merchant_password = $this -> module['pass_test'];
            } 
			else 
			{
				$merchant_id = $this -> module['mid_prod'];
                $merchant_password = $this -> module['pass_prod'];
            }	      
            $step = $_GET['step'] ?? '';    
            if ($step == '0')
			{                   
				$MethodURL = $this->sessionHandler->get('payvector_MethodURL',  'payvector');                
				$ThreeDSMethodData = $this->sessionHandler->get('payvector_ThreeDSMethodData', 'payvector');                
                $params =  ["ThreeDSMethodData"=>$ThreeDSMethodData];
                $FormAttributes = " target=\"threeDSecureFrame\"";               
                return $this->threedsecure($FormAttributes, $params,$MethodURL);
            }
            else if ($step == '2')
			{
                $ThreeDSMethodData = $_POST['threeDSMethodData'] ?? '';                          
                $crossReference = $this->sessionHandler->get('payvector_md', 'payvector');

				$tdseThreeDSecureEnvironment = new \net\thepaymentgateway\paymentsystem\ThreeDSecureEnvironment($this->getEntryPointList());
				$tdseThreeDSecureEnvironment->getMerchantAuthentication()->setMerchantID($merchant_id);
				$tdseThreeDSecureEnvironment->getMerchantAuthentication()->setPassword($merchant_password);
				$tdseThreeDSecureEnvironment->getThreeDSecureEnvironmentData()->setCrossReference($crossReference);
				$tdseThreeDSecureEnvironment->getThreeDSecureEnvironmentData()->setMethodData($ThreeDSMethodData);
				$boTransactionProcessed = $tdseThreeDSecureEnvironment->processTransaction($tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData);
				if($tdsarThreeDSecureAuthenticationResult->getStatusCode() === 3)
				{
					$CREQ = $todTransactionOutputData->getThreeDSecureOutputData()->getCREQ();
					$ThreeDSSessionData = \PaymentFormHelper::base64UrlEncode($todTransactionOutputData->getCrossReference());
					$FormAttributes  = " target=\"threeDSecureFrame\"";
					$MethodURL = $todTransactionOutputData->getThreeDSecureOutputData()->getACSURL();
					$params = ['creq' => $CREQ, 'threeDSSessionData' => $ThreeDSSessionData];
                    return $this->threedsecure($FormAttributes, $params,$MethodURL);
				}
				else
				{
					$finalTransactionResult = new \ThreeDSecureFinalTransactionResult($boTransactionProcessed, $tdseThreeDSecureEnvironment, $tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData, $this->sessionHandler);
					$this->handleTransactionResults($finalTransactionResult);					
				}
            }
            else if ($step == '3')
			{
                $cres = $_POST['cres'] ?? ''; 
                $threeDSSessionData = $_POST['threeDSSessionData'] ?? '';                 
                $CrossReference = \PaymentFormHelper::base64UrlDecode($threeDSSessionData);						
				$tdsaThreeDSecureAuthentication = new \net\thepaymentgateway\paymentsystem\ThreeDSecureAuthentication($this->getEntryPointList());
				$tdsaThreeDSecureAuthentication->getMerchantAuthentication()->setMerchantID($merchant_id);
				$tdsaThreeDSecureAuthentication->getMerchantAuthentication()->setPassword($merchant_password);
				$tdsaThreeDSecureAuthentication->getThreeDSecureInputData()->setCrossReference($CrossReference);
				$tdsaThreeDSecureAuthentication->getThreeDSecureInputData()->setCRES($cres);
				$boTransactionProcessed = $tdsaThreeDSecureAuthentication->processTransaction($tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData);	
				$finalTransactionResult = new \ThreeDSecureFinalTransactionResult($boTransactionProcessed, $tdsaThreeDSecureAuthentication, $tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData, $this->sessionHandler);
                $this->handleTransactionResults($finalTransactionResult);		                
            }
        }

        $order = Order::getInstance();
        $cart_order_id = $this -> _basket['cart_order_id'];
        $order_summary = $order -> getSummary($cart_order_id);

        if (isset($_POST['CurrentTransactionType'])) 
		{
			return $this -> process();
		}
              
        if ($this -> module['crt']) 
		{
            $crtDetails = $GLOBALS['db'] -> misc(PayVectorSQL::selectCRT_CrossReferenceDetails($order_summary['customer_id']));
            if (is_array($crtDetails)) 
			{
                for ($i = 0; $i < count($crtDetails); $i++) 
				{
                    $date1 = new DateTime('now');
                    $date2 = new DateTime($crtDetails[$i]['TransactionDateTime']);
                    $date2 = $date2 -> add(new DateInterval("P1Y"));
                    if ($date1 < $date2) 
					{
                        $GLOBALS['smarty'] -> assign('DISPLAY_CRT', true);
                        $smarty_data['CRT']['LastFour'] = $crtDetails[$i]['CardLastFour'];
                        $smarty_data['CRT']['CrossReference'] = $crtDetails[$i]['CrossReference'];
                        $smarty_data['CRT']['CardType'] = $crtDetails[$i]['CardType'];
                    } 
					else 
					{
                        $GLOBALS['db'] -> misc(PayVectorSQL::deleteCRT_CardDetails($order_summary['customer_id'], $crtDetails[$i]['CardLastFour']));
                    }
                }
                if (is_array($smarty_data['CRT'])) 
				{
                    $GLOBALS['smarty'] -> assign('CRT', $smarty_data['CRT']);
                }
            }
        }
        
        $GLOBALS['smarty'] -> assign('mode', $this -> module['mode'] );            
            // Display payment result message
        if (!empty($this -> _result_message)) 
		{
            foreach ($this-> _result_message as $error) 
			{
                $GLOBALS['gui'] -> setError($error);
            }
        }

        //Show Expire Months
        $selectedMonth = (isset($_POST['ExpiryDateMonth'])) ? $_POST['ExpiryDateMonth'] : date('m');
        for ($i = 1; $i <= 12; ++$i) {
            $val = sprintf('%02d', $i);
            $smarty_data['card']['expiry']['months'][] = array(
                'selected' => ($val == $selectedMonth) ? 'selected="selected"' : '',
                'value' => $val,
                'display' => $this -> formatMonth($val),
            );
        }

        ## Show Expire Years
        $thisYear = date("Y");
        $maxYear = $thisYear + 10;
        $selectedYear = isset($_POST['ExpiryDateYear']) ? $_POST['ExpiryDateYear'] : ($thisYear + 2);
        for ($i = $thisYear; $i <= $maxYear; ++$i) {
            $smarty_data['card']['expiry']['years'][] = array(
                'selected' => ($i == $selectedYear) ? 'selected="selected"' : '',
                'value' => $i,
            );
        }

        $GLOBALS['smarty'] -> assign('CARD', $smarty_data['card']);

        $smarty_data['customer'] = array(
            'name_on_card' => isset($_POST['cardName']) ? $_POST['cardName'] : $this -> _basket['billing_address']['first_name'] . " " . $this -> _basket['billing_address']['last_name'],
            'first_name' => isset($_POST['firstName']) ? $_POST['firstName'] : $this -> _basket['billing_address']['first_name'],
            'last_name' => isset($_POST['lastName']) ? $_POST['lastName'] : $this -> _basket['billing_address']['last_name'],
            'email' => isset($_POST['emailAddress']) ? $_POST['emailAddress'] : $this -> _basket['billing_address']['email'],
            'add1' => isset($_POST['addr1']) ? $_POST['addr1'] : $this -> _basket['billing_address']['line1'],
            'add2' => isset($_POST['addr2']) ? $_POST['addr2'] : $this -> _basket['billing_address']['line2'],
            'city' => isset($_POST['city']) ? $_POST['city'] : $this -> _basket['billing_address']['town'],
            'state' => isset($_POST['state']) ? $_POST['state'] : $this -> _basket['billing_address']['state'],
            'postcode' => isset($_POST['postcode']) ? $_POST['postcode'] : $this -> _basket['billing_address']['postcode']
        );

        $GLOBALS['smarty'] -> assign('CUSTOMER', $smarty_data['customer']);

        ## Country list
        $countries = $GLOBALS['db'] -> select('CubeCart_geo_country', false, false, array('name' => 'ASC'));
        if ($countries) {
            $currentIso = isset($_POST['country']) ? $_POST['country'] : $this -> _basket['billing_address']['country_iso'];
            foreach ($countries as $country) {
                $country['selected'] = ($country['iso'] == $currentIso) ? 'selected="selected"' : '';
                $smarty_data['countries'][] = $country;
            }
            $GLOBALS['smarty'] -> assign('COUNTRIES', $smarty_data['countries']);
        }

        // Include module language strings - use Language class
        $GLOBALS['language'] -> loadDefinitions("payvector", dirname(__FILE__) . CC_DS . 'language', 'module.definitions.xml');
        // Load other lang either customized ones
        $GLOBALS['language'] -> loadLanguageXML("payvector", '', dirname(__FILE__) . CC_DS . 'language');

        ## Check for custom template for module in skin folder
        $file_name = 'form.tpl';
        $form_file = $GLOBALS['gui'] -> getCustomModuleSkin('gateway', dirname(__FILE__), $file_name);
        $GLOBALS['gui'] -> changeTemplateDir($form_file);
        $return .= $GLOBALS['smarty'] -> fetch($file_name);

        $GLOBALS['gui'] -> changeTemplateDir();
        
        return $return;
    }

    private function handleTransactionResults($final_transaction_result)
	{			
        $order = Order::getInstance();        
        $OrderID = $final_transaction_result->getOrderID($this->sessionHandler);        
        $order_summary = $order -> getSummary($OrderID);   
        $redirect_to = null; 
        $return = false;
		if($final_transaction_result->transactionProcessed() && $final_transaction_result->transactionSuccessful())
		{
            $CrossReference = $final_transaction_result->getCrossReference();
            $card_last_four = trim($final_transaction_result->getCardLastFour($this->sessionHandler));
            $card_type = $final_transaction_result->getCardType();
            
            $OriginCrossReference = $GLOBALS['db'] -> misc(PayVectorSQL::selectCRT_CrossReference($order_summary['customer_id']));
            $TransactionDateTime =  date('Y-m-d H:i:s P');
            if (!$OriginCrossReference) 
			{
                if (!empty($card_last_four))
				{
                    $results = $GLOBALS['db'] -> misc(PayVectorSQL::insertCRT_NewCardDetailsTransaction($order_summary['customer_id'], $CrossReference, $card_last_four, $card_type, $TransactionDateTime));                             
                    $GLOBALS['cache']->clear();
                }
                 
            }
			else 
			{
                if (!empty($card_last_four))
				{   
                    $results = $GLOBALS['db'] -> misc(PayVectorSQL::updateCRT_CardDetails($order_summary['customer_id'], $CrossReference, $card_last_four, $card_type, $TransactionDateTime));
                    $GLOBALS['cache']->clear();
                }
            }
						
			$amount_received = $final_transaction_result->getAmountReceived();            
            $currency_code = $GLOBALS['config'] -> get('config', 'default_currency');	
			$amount_received = $this->_GetAmount($currency_code,$amount_received);

            $status = 'Approved';
            $szNotify = "Payment Processed Successfully  =>  {$final_transaction_result->getMessage()}";            
            $return = true;
		}
		else
		{		
			//run 3DSecure if required
			if($final_transaction_result->getStatusCode() === 3)
			{				
				$this->sessionHandler->set('payvector_md', $final_transaction_result->getCrossReference() , 'payvector');
				$this->sessionHandler->set('payvector_MethodURL', $final_transaction_result->getThreeDSecureOutputData()->getMethodURL() , 'payvector');
				$this->sessionHandler->set('payvector_ThreeDSMethodData', $final_transaction_result->getThreeDSecureOutputData()->getMethodData(), 'payvector');
                $redirect_to = $GLOBALS['storeURL'].'/index.php?_a=gateway&gateway=payvector&mode=threeds&step=0';                
                header('Location: ' . $redirect_to);
				return;
			}
			$amount_received = 0;
            $status = 'Error';
            $szNotify = "Payment Processing Failed  => ";
            $szNotify .= " " . $final_transaction_result -> getMessage();
		}

        $transData['gateway'] = 'PayVector';
        $transData['status'] = $status;
        $transData['notes'] = $szNotify;
        $transData['trans_id'] = $CrossReference;
        $transData['order_id'] = $order_summary['cart_order_id'];
        $transData['amount'] = $amount_received;
        $transData['customer_id'] = $order_summary['customer_id'];
        $transData['extra'] = '';
        $order -> logTransaction($transData);

        switch ($status) 
		{
            case "Approved" :
                $order->paymentStatus(Order::PAYMENT_SUCCESS, $transData['order_id']);
                $order->orderStatus(Order::ORDER_PROCESS, $transData['order_id']);                
                $redirect_to = 'complete';
                $GLOBALS['gui'] -> setNotify($szNotify);
                break;
            case "Declined" :
            case "Error" :                
                $order -> orderStatus(Order::ORDER_PROCESS, $transData['order_id']);
                $order -> paymentStatus(Order::PAYMENT_DECLINE, $transData['order_id']);

                $redirect_to = 'checkout';
                $GLOBALS['gui'] -> setError($szNotify);
                break;
            case "Pending" :
                $order -> orderStatus(Order::ORDER_PENDING, $transData['order_id']);
                $order -> paymentStatus(Order::PAYMENT_PENDING, $transData['order_id']);

                break;
        }           		

		if ($redirect_to == null) 
		{
			return $return;
		} 
		else 
		{
			$redirect_to = $GLOBALS['storeURL'].'/index.php?_a='.$redirect_to;
			$GLOBALS['session'] -> delete('', 'payvector');
			httpredir($redirect_to);
			return $return;
		}
	}
}