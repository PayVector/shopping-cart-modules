<?php
use Tygh\Registry;
fn_delete_notification('text_transaction_cancelled');


$order_id = intval($_REQUEST['order_id']);
$mode = $_REQUEST['action'];
if ($mode == 'callback')
{
    $order_info = fn_get_order_info($order_id);
    $payment_id = $order_info['payment_id'];
	$processor_data = fn_get_payment_method_data($payment_id);
    $pvparam = $processor_data['processor_params'];
    
    
    if ($pvparam['result_delivery_method'] === 'POST'){
		$session_id = base64_decode($_REQUEST['sid']);
        Tygh::$app['session']->resetID($session_id);
		
        $hash_matches = PaymentFormHelper::validateTransactionResult_POST(
					$pvparam['merchant_id'],
					$pvparam['merchant_password'],
					$pvparam['presharedkey'],
					$pvparam['hash_method'],
					$_POST,
					$transaction_result,
					$validate_error_message
				);

    }
    else if($pvparam['result_delivery_method'] === 'SERVER_PULL')
    {               
        $hostedPaymentFormHandlerURL = 'https://mms.payvector.net/Pages/PublicPages/PaymentFormResultHandler.ashx';
		$hash_matches = PaymentFormHelper::validateTransactionResult_SERVER_PULL(
            $pvparam['merchant_id'],
            $pvparam['merchant_password'],
            $pvparam['presharedkey'],
            $pvparam['hash_method'],
            $_GET,
            $hostedPaymentFormHandlerURL,
            $transaction_result,
            $validate_error_message
        );
               
    }   
    
    if(!$hash_matches)
    {		                
        echo $validate_error_message;
        exit;        
    }
    
    fn_payvector_handleTransactionResults(new \HostedPaymentFormFinalTransactionResult($transaction_result));
    exit;
}
else if ($mode == 'threedsecurelanding') {
    $postData = $_POST ?? [];
    $cres = $postData['cres'] ?? '';    
    $order_id = $_GET['order_id'] ?? '';
    $step = 2;

    $params = [];
    if (!empty($cres)) {
	    $params['threeDSSessionData'] = $postData['threeDSSessionData'] ?? '';
	    $params['cres'] = $postData['cres'] ?? '';	
	    $step = 3;	
    }
    else {
	    $params['threeDSMethodData'] = $postData['threeDSMethodData'] ?? '';
    }
    $formurl = fn_url("payvector.payment?order_id={$order_id}&action=threedsecure&step=".$step);
     header('Content-Type: text/html; charset=utf-8');    
    echo '<html>';
    echo '<head>';        
    echo '<script>';
    echo 'function onLoadHandler(){ document.forms["processform"].submit(); }';
    echo '</script>';
    echo '</head>';
    echo '<body onload="onLoadHandler();">';        
    echo '<form name="processform" method="POST" action="' . htmlspecialchars($formurl) . '" target="_parent">';
    foreach ($params as $key => $value) {
        echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">' . "\n";
    }
    echo '</form>';
    echo '</body>';
    echo '</html>';       
    exit;
}

else if ($mode == 'threedsecure') {
    $order_info = fn_get_order_info($order_id);
    $payment_id = $order_info['payment_id'];
	$processor_data = fn_get_payment_method_data($payment_id);
    $pvparam = $processor_data['processor_params'];    
    $step = $_GET['step'] ?? '';   
    $sessionHandler = Tygh::$app['session']; 
    if ($step == '0'){                   
        $MethodURL = $sessionHandler['payvector_MethodURL'];
        $ThreeDSMethodData = $sessionHandler['payvector_ThreeDSMethodData'];
        $params =  ["ThreeDSMethodData"=>$ThreeDSMethodData];
        $FormAttributes = " target=\"threeDSecureFrame\"";               
        fn_payvector_threedsecure($FormAttributes, $params,$MethodURL);
    }
    else if ($step == '2'){
        
        $ThreeDSMethodData = $_POST['threeDSMethodData'] ?? '';                          
        $crossReference = $sessionHandler['payvector_md'];               		

        $tdseThreeDSecureEnvironment = new \net\thepaymentgateway\paymentsystem\ThreeDSecureEnvironment(fn_payvector_get_entry_pointList());
        $tdseThreeDSecureEnvironment->getMerchantAuthentication()->setMerchantID($pvparam['merchant_id']);
        $tdseThreeDSecureEnvironment->getMerchantAuthentication()->setPassword($pvparam['merchant_password']);
        $tdseThreeDSecureEnvironment->getThreeDSecureEnvironmentData()->setCrossReference($crossReference);
        $tdseThreeDSecureEnvironment->getThreeDSecureEnvironmentData()->setMethodData($ThreeDSMethodData);
        $boTransactionProcessed = $tdseThreeDSecureEnvironment->processTransaction($tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData);
        if($tdsarThreeDSecureAuthenticationResult->getStatusCode() === 3){
            $CREQ = $todTransactionOutputData->getThreeDSecureOutputData()->getCREQ();
            $ThreeDSSessionData = \PaymentFormHelper::base64UrlEncode($todTransactionOutputData->getCrossReference());
            $FormAttributes  = " target=\"threeDSecureFrame\"";
            $MethodURL = $todTransactionOutputData->getThreeDSecureOutputData()->getACSURL();
            $params = ['creq' => $CREQ, 'threeDSSessionData' => $ThreeDSSessionData];
            fn_payvector_threedsecure($FormAttributes, $params,$MethodURL);
        }
        else {	
            $finalTransactionResult = new \ThreeDSecureFinalTransactionResult($boTransactionProcessed, $tdseThreeDSecureEnvironment, $tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData, $sessionHandler);
            fn_payvector_handleTransactionResults($finalTransactionResult);
        }       
     }
     else if ($step == '3'){
            $cres = $_POST['cres'] ?? ''; 
            $threeDSSessionData = $_POST['threeDSSessionData'] ?? '';                 
            $CrossReference = \PaymentFormHelper::base64UrlDecode($threeDSSessionData);						
            $tdsaThreeDSecureAuthentication = new \net\thepaymentgateway\paymentsystem\ThreeDSecureAuthentication(fn_payvector_get_entry_pointList());
            $tdsaThreeDSecureAuthentication->getMerchantAuthentication()->setMerchantID($pvparam['merchant_id']);
            $tdsaThreeDSecureAuthentication->getMerchantAuthentication()->setPassword($pvparam['merchant_password']);
            $tdsaThreeDSecureAuthentication->getThreeDSecureInputData()->setCrossReference($CrossReference);
            $tdsaThreeDSecureAuthentication->getThreeDSecureInputData()->setCRES($cres);
            $boTransactionProcessed = $tdsaThreeDSecureAuthentication->processTransaction($tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData);	
            $finalTransactionResult = new \ThreeDSecureFinalTransactionResult($boTransactionProcessed, $tdsaThreeDSecureAuthentication, $tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData, $sessionHandler);
            fn_payvector_handleTransactionResults($finalTransactionResult);
        }
}



