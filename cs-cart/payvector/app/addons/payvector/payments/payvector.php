<?php
use Tygh\Registry;
if (!defined('BOOTSTRAP')) { die('Access denied'); }
if ( !defined('AREA') ) { die('Access denied'); }
if (!defined('PAYMENT_NOTIFICATION')) {
$pvparam = $processor_data['processor_params'];	
$merchant_id = $pvparam['merchant_id'];
	$merchant_password = $pvparam['merchant_password'];

	$transaction_processor = new \TransactionProcessor();
	$transaction_processor->setMerchantID($merchant_id);
	$transaction_processor->setMerchantPassword($merchant_password);
	
	$amount = fn_format_price_by_currency($order_info['total']);		
	$amount = number_format($amount, 2, '.', '');
    $currency = CART_SECONDARY_CURRENCY;

    $order_description = "";

    $customer_name =  $order_info['b_firstname'] . " " . $order_info['b_lastname'];

    $add1       = $order_info['b_address'];
    $add2       = $order_info['b_address_2'];    
    $city       =  $order_info['b_city'];
    $state      =  $_b_state = fn_get_state_name($order_info['b_state'], $order_info['b_country']);;
    $postcode   =  $order_info['b_zipcode'];
	$email 		=  $order_info['email'];
	$phone		=  $order_info['phone'];
	
    $country_code    =  $order_info['b_country'];	

	$iso_country_code = '';				
	$iso_country_list = \ISOHelper::getISOCountryList();

	if(!empty($country_code) && $iso_country_list->getISOCountry($country_code, $iso_country))
	{				
		$iso_country_code = $iso_country->getISOCode();
	}
    

	list($iso_currency_code, $amount) = fn_payvector_set_amount($currency, $amount);	
	$transaction_processor->setCurrencyCode($iso_currency_code);
	$transaction_processor->setAmount($amount);
	$transaction_processor->setOrderID($order_id);
	$transaction_processor->setOrderDescription('Order ID ' . $order_id);
	$transaction_processor->setCustomerName($customer_name);
	$transaction_processor->setAddress1($add1);
	$transaction_processor->setAddress2($add2);
	$transaction_processor->setCity($city);
	$transaction_processor->setState($state );
	$transaction_processor->setPostcode($postcode);
	$transaction_processor->setCountryCode($iso_country_code);
	$transaction_processor->setEmailAddress($email);
	$transaction_processor->setPhoneNumber($phone);
	$transaction_processor->setIPAddress($_SERVER['REMOTE_ADDR']);

	$payment_info = $_POST['payment_info'] ?? [];
	$pv_info = $_POST['pv_info'];
	//3DSv2 Parameters							
	$transaction_processor->setJavaEnabled($pv_info['browserJavaEnabled']);
	$transaction_processor->setJavaScriptEnabled('true');
	$transaction_processor->setScreenWidth($pv_info['browserScreenWidth']);
	$transaction_processor->setScreenHeight($pv_info['browserScreenHeight']);
	$transaction_processor->setScreenColourDepth($pv_info['browserColorDepth']);
	$transaction_processor->setTimezoneOffset($pv_info['browserTZ']);				
	$transaction_processor->setLanguage($pv_info['browserLanguage']);
	$ChURL = fn_url("payvector.payment?order_id={$order_id}&action=threedsecurelanding");
	$ChURL = htmlspecialchars($ChURL, ENT_XML1, 'UTF-8');
	$transaction_processor->setChallengeNotificationURL($ChURL);				
	$transaction_processor->setFingerprintNotificationURL($ChURL);	
	$transaction_processor->setRgeplRequestGatewayEntryPointList(fn_payvector_get_entry_pointList());	
	$sessionHandler = Tygh::$app['session'];
	//check cross ref
	if ($pv_info['payvector_payment_type'] == 'stored_card'){
		
		$transaction_processor->setCV2($payment_info['cvv2']);
		$user_id = Tygh::$app['session']['auth']['user_id'];
		$cr_ar = 	fn_payvector_get_cross_reference($user_id);        

        if (is_array($cr_ar)) {
               
               $cross_reference = $cr_ar['cross_reference'];               
               $final_transaction_result = $transaction_processor->doCrossReferenceTransaction(
						$cross_reference,"true", $sessionHandler);   						
               
        }
        else {
			fn_set_notification('E', fn_get_lang_var('error'), 'Saved Card Not Found');
			fn_redirect('checkout.cart');            
            return false;
        }
	}
	else {
		//direct
		if ($pvparam['capture_method'] == 'direct'){
			$transaction_processor->setCV2($payment_info['cvv2']);
			$final_transaction_result = $transaction_processor->doCardDetailsTransaction(
						$payment_info['card_number'],
						$payment_info['expiry_month'],
						$payment_info['expiry_year'],
						$payment_info['payvector_issue_number'] ?? '',
			$sessionHandler );

		}
		else {
			//hosted
			$session_id = Tygh::$app['session']->getID();
			$hostedPaymentFormURL = 'https://mms.payvector.net/Pages/PublicPages/PaymentForm.aspx';
			$CallbackUrl = fn_url("payvector.payment?order_id={$order_id}&action=callback&security_hash=".fn_generate_security_hash(). '&sid=' . base64_encode($session_id) );				
			
 			$view_array = $transaction_processor->getHostedPaymentForm(
				    $CallbackUrl,
				    $CallbackUrl,
				    $pvparam['presharedkey'],
				    $pvparam['hash_method'],
				    $pvparam['result_delivery_method'],		
				    false,
				    $sessionHandler
                );
                $redirectUrl = $hostedPaymentFormURL . '?' . http_build_query($view_array);
                header('Location: ' . $redirectUrl);
				exit;

		}

	}
	fn_payvector_handleTransactionResults($final_transaction_result);
	exit;
}
?>