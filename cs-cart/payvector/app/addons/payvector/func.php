<?php
use Tygh\Database\Connection;
use Tygh\Registry;
include_once (__DIR__ .'/includes/TransactionProcessor.php');

function fn_payvector_init_templater(&$params)
{	
    fn_add_script('js/addons/payvector/payvector.js');
}

function fn_payvector_checkout_select_default_payment_method(&$cart, &$auth, &$params)
{    
    if (defined('AREA') && AREA === 'C') {
        if (!empty($cart['payment_id'])) {
            $payment_id = $cart['payment_id'];
            $payment_data = fn_get_payment_method_data($payment_id);
			

            $firstname =  $cart['user_data']['firstname'] ?? '';
            $lastname  = $cart['user_data']['lastname'] ?? '';
            $cardholder = trim($firstname . ' ' . $lastname);
			$cross_reference = '';
            $card_type = '';
            $card_last_four = '';        
			$user_id = Tygh::$app['session']['auth']['user_id'];
			if (isset($user_id)){
				$cr_ar = 	fn_payvector_get_cross_reference($user_id);				
				if (isset($cr_ar)){
					$cross_reference = $cr_ar['cross_reference'] ?? '';
            		$card_type = $cr_ar['card_type'] ?? '';
            		$card_last_four = $cr_ar['card_last_four'] ?? '';    
				}				
			}
			
			            
            if (!empty($payment_data['processor']) && $payment_data['processor'] == 'PayVector') {
                Tygh::$app['view']->assign('pv_cc', [
                    'cardholder' => $cardholder ?? '',
                    'payment_data' => $payment_data,
                    'cross_reference' => $cross_reference,
                    'card_type' => $card_type,
                    'card_last_four' => $card_last_four,
					'capture_method' => $payment_data['processor_params']['capture_method'],
                ]            
            );
            }
        }     
    }
}


function fn_payvector_handleTransactionResults($final_transaction_result)
{				
	$transCardData = [];
    $sessionHandler = Tygh::$app['session'];
	
	$order_id = $final_transaction_result->getOrderID($sessionHandler);	
	
	if (empty($order_id)) return false;
	$pp_response = array();
	

	if($final_transaction_result->transactionProcessed() && $final_transaction_result->transactionSuccessful())
	{				
		
		$gatewayEntryPointList = $final_transaction_result->getGatewayEntryPointList();
		if(isset($gatewayEntryPointList))
		{
			Configuration::updateGlobalValue('PAYVECTOR_ENTRY_POINTS', $gatewayEntryPointList->toXmlString(), true);
			Configuration::updateGlobalValue('PAYVECTOR_ENTRY_POINTS_MODIFIED', date('Y-m-d H:i:s P'));
		}

		
		$amount_received = $final_transaction_result->getAmountReceived();
			
        $currency_code = CART_SECONDARY_CURRENCY;
		$amount_received = fn_payvector_get_amount($currency_code,$amount_received);		
		//update Cross Reference
		$user_id = Tygh::$app['session']['auth']['user_id'];
		$CrossReference = $final_transaction_result->getCrossReference();
		$CardType = $final_transaction_result->getCardType();
		$card_last_four = $final_transaction_result->getCardLastFour($sessionHandler);
		$last_updated = date('Y-m-d H:i:s P');		
		fn_payvector_update_cross_reference($user_id, $CrossReference,$card_last_four, $CardType,  $last_updated);
		
		$pp_response['order_status'] = 'P';           						
		$pp_response['transaction_id'] = $CrossReference; 
		$pp_response['reason_text'] = 'Payment Successful';
		
		unset($sessionHandler['payvector_md']);
		unset($sessionHandler['payvector_MethodURL']);		
		
		
		fn_finish_payment($order_id, $pp_response, false );		
		fn_order_placement_routines( 'route', $order_id );	
	}
	else
	{			
		//run 3DSecure if required
		if($final_transaction_result->getStatusCode() === 3)
		{	
			$sessionHandler['payvector_md'] = $final_transaction_result->getCrossReference();
			$sessionHandler['payvector_MethodURL'] = $final_transaction_result->getThreeDSecureOutputData()->getMethodURL() ;
			$sessionHandler['payvector_ThreeDSMethodData'] = $final_transaction_result->getThreeDSecureOutputData()->getMethodData();
            //$redirect_to = $GLOBALS['storeURL'].'/index.php?_a=gateway&gateway=payvector&mode=threedsecurelanding&step=0';                
            $redirect_to = fn_url("payvector.payment?order_id={$order_id}&action=threedsecure&step=0");
            header('Location: ' . $redirect_to);
			return;
		}

		$amount_received = 0;
		
		$pp_response['order_status'] = 'F';           								
		$pp_response['reason_text'] = 'Payment Fail, '.$final_transaction_result -> getMessage();
		fn_set_notification('E', fn_get_lang_var('error'), $pp_response['reason_text']);
		fn_redirect('checkout.cart');
		
	
	}			

}


function fn_payvector_get_amount($currency_code, $amount)
{		
        
		$amount = number_format($amount, 2,'.', '');	        
		$iso_currency_list = \ISOHelper::getISOCurrencyList();		
        
		if(!empty($currency_code) && $iso_currency_list->getISOCurrency($currency_code, $iso_currency))
		{        
			if($iso_currency->getExponent() != 0 && isset($amount))
			{		
				$amount = round($amount / pow(10, $iso_currency->getExponent()), $iso_currency->getExponent());
			}					
		}		
        
		return $amount;
	}
    
    function fn_payvector_set_amount($currency_code, $amount)
	{		
		$amount = number_format($amount, 2,'.', '');			
		$iso_currency_list = \ISOHelper::getISOCurrencyList();		
		if(!empty($currency_code) && $iso_currency_list->getISOCurrency($currency_code, $iso_currency))
		{				
			$isoCurrencyCode = $iso_currency->getISOCode();
			$amount = (string) $amount;
			$amount = round($amount * ("1" . str_repeat(0, $iso_currency->getExponent())));			
		}
		$ret_ar= [];
        $ret_ar[] = $isoCurrencyCode;
        $ret_ar[] = $amount;        
		return $ret_ar;
	}
    function fn_payvector_get_entry_pointList()
	{		
		$paymentProcessorDomain = "payvector.net";
		$rgepl_request_gateway_entry_point_list = new \net\thepaymentgateway\paymentsystem\RequestGatewayEntryPointList();		
		
		$rgepl_request_gateway_entry_point_list->add("https://gw1." . $paymentProcessorDomain, 100, 2);
		$rgepl_request_gateway_entry_point_list->add("https://gw2." . $paymentProcessorDomain, 200, 2);
		$rgepl_request_gateway_entry_point_list->add("https://gw3." . $paymentProcessorDomain, 300, 2);				

		return $rgepl_request_gateway_entry_point_list;
	}


function fn_payvector_get_cross_reference($user_id)
{
    return db_get_row(
        "SELECT * FROM ?:payvector_cross_reference WHERE user_id = ?i",
        $user_id
    );
}

function fn_payvector_update_cross_reference($user_id, $cross_reference, $card_last_four = '', $card_type = '', $last_updated = null)
{
    $exists = db_get_field(
        "SELECT id_cross_reference FROM ?:payvector_cross_reference WHERE user_id = ?i",
        $user_id
    );

    $data = [
        'user_id' => (int) $user_id,
        'cross_reference' => $cross_reference,
        'card_last_four' => $card_last_four,
        'card_type' => $card_type,
        'last_updated' => $last_updated ?: date('Y-m-d H:i:s'),
    ];

    if ($exists) {
        db_query("UPDATE ?:payvector_cross_reference SET ?u WHERE id_cross_reference = ?i", $data, $exists);
    } else {
        db_query("INSERT INTO ?:payvector_cross_reference ?e", $data);
    }
}


function fn_payvector_delete_cross_reference($user_id)
{
    db_query("DELETE FROM ?:payvector_cross_reference WHERE user_id = ?i", $user_id);
}

function fn_payvector_get_url($type){
  //if $type === 'test')
}

function fn_payvector_threedsecure($FormAttributes, $params,$MethodURL) {       
    Tygh::$app['view']->assign('FormAttributes',  $FormAttributes);
    Tygh::$app['view']->assign('params',  $params);
    Tygh::$app['view']->assign('FormAction',  $MethodURL);    
    Tygh::$app['view']->assign('content_tpl', 'addons/payvector/views/threedsecurelandingform.tpl');  
}
?>