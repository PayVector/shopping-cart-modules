<?php
/**
 * header_php.php for PayVector 3DS
 */

if (!isset($_SESSION['customer_id'])) {
    zen_redirect(zen_href_link(FILENAME_TIME_OUT));
}


require_once(DIR_WS_MODULES . 'payment/payvector.php');


$payment_lang_file = DIR_WS_LANGUAGES . $_SESSION['language'] . '/modules/payment/payvector.php';
if (file_exists($payment_lang_file)) {
    require_once($payment_lang_file);
}

$payvector_module = new payvector();


$crossReference = isset($_SESSION['payvector_cross_reference']) ? $_SESSION['payvector_cross_reference'] : '';
$order_id = isset($_SESSION['payvector_order_id']) ? $_SESSION['payvector_order_id'] : '';
if ($order_id == '') {
    
    if (isset($_SESSION['order_number_created'])) {
        $order_id = $_SESSION['order_number_created'];
    }
}


$postData = $_POST;
if (!empty($postData) && (isset($postData['cres']) || isset($postData['threeDSMethodData']) || isset($postData['PaRes']) || isset($postData['MD']))) {
    
    if (empty($order_id)) {
        $messageStack->add_session('checkout_payment', 'Session expired during 3DS verification.', 'error');
        zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
    }

    require_once(DIR_WS_CLASSES . 'order.php');
    $order = new order($order_id);
    
    $processor = new TransactionProcessor();
    $processor->setMerchantID(MODULE_PAYMENT_PAYVECTOR_MERCHANT_ID);
    $processor->setMerchantPassword(MODULE_PAYMENT_PAYVECTOR_PASSWORD);
    
    $paymentProcessorDomain = "payvector.net";
    $rgepl = new \net\thepaymentgateway\paymentsystem\RequestGatewayEntryPointList();
    $rgepl->add("https://gw1." . $paymentProcessorDomain, 100, 2);
    $rgepl->add("https://gw2." . $paymentProcessorDomain, 200, 2);
    $rgepl->add("https://gw3." . $paymentProcessorDomain, 300, 2);
    $processor->setRgeplRequestGatewayEntryPointList($rgepl);
    
    $threeDSMethodData = isset($postData['threeDSMethodData']) ? $postData['threeDSMethodData'] : '';
    $cres = isset($postData['cres']) ? $postData['cres'] : '';
    $threeDSSessionData = isset($postData['threeDSSessionData']) ? $postData['threeDSSessionData'] : '';

    if (!empty($cres)) {
        
        $finalCrossReference = '';
        if (!empty($threeDSSessionData)) {
            $szBase64 = strtr($threeDSSessionData, '-_', '+/');
            $nPadding = strlen($szBase64) % 4;
            if ($nPadding) {
                $szBase64 .= str_repeat('=', 4 - $nPadding);
            }
            $finalCrossReference = base64_decode($szBase64);
        }
        $finalCrossReference = $finalCrossReference ? $finalCrossReference : $crossReference;
        
        $tdsa = new \net\thepaymentgateway\paymentsystem\ThreeDSecureAuthentication($rgepl);
        $tdsa->getMerchantAuthentication()->setMerchantID(MODULE_PAYMENT_PAYVECTOR_MERCHANT_ID);
        $tdsa->getMerchantAuthentication()->setPassword(MODULE_PAYMENT_PAYVECTOR_PASSWORD);
        $tdsa->getThreeDSecureInputData()->setCrossReference($finalCrossReference);
        $tdsa->getThreeDSecureInputData()->setCRES($cres);
        
        $authenticationResult = null;
        $outputData = null;
        $boProcessed = $tdsa->processTransaction($authenticationResult, $outputData);
        
        $finalResult = new ThreeDSecureFinalTransactionResult($boProcessed, $tdsa, $authenticationResult, $outputData, $_SESSION);
        $payvector_module->_handleTransactionResult($finalResult, $processor, $order, 'new');
        
        $_SESSION['cart']->reset(true);
        $unrewritten_success_url = (defined('ENABLE_SSL') && ENABLE_SSL == 'true' ? HTTPS_SERVER . DIR_WS_HTTPS_CATALOG : HTTP_SERVER . DIR_WS_CATALOG) . 'index.php?main_page=' . FILENAME_CHECKOUT_SUCCESS;
        if (zen_session_id() != '') $unrewritten_success_url .= '&zenid=' . zen_session_id();
        zen_redirect($unrewritten_success_url);
        
    } elseif (!empty($threeDSMethodData)) {
        
        $tdse = new \net\thepaymentgateway\paymentsystem\ThreeDSecureEnvironment($rgepl);
        $tdse->getMerchantAuthentication()->setMerchantID(MODULE_PAYMENT_PAYVECTOR_MERCHANT_ID);
        $tdse->getMerchantAuthentication()->setPassword(MODULE_PAYMENT_PAYVECTOR_PASSWORD);
        $tdse->getThreeDSecureEnvironmentData()->setCrossReference($crossReference);
        $tdse->getThreeDSecureEnvironmentData()->setMethodData($threeDSMethodData);
        
        $authenticationResult = null;
        $outputData = null;
        $boProcessed = $tdse->processTransaction($authenticationResult, $outputData);
        
        if ($authenticationResult->getStatusCode() === 3) {
            
            $creq = $outputData->getThreeDSecureOutputData()->getCREQ();
            $szBase64 = base64_encode($outputData->getCrossReference());
            $sessionData = rtrim(strtr($szBase64, '+/', '-_'), '=');
            
            $_SESSION['payvector_3ds_data'] = array(
                'acs_url' => $outputData->getThreeDSecureOutputData()->getACSURL(),
                'target'  => 'threeDSecureFrame',
                'params'  => array(
                    'creq' => $creq,
                    'threeDSSessionData' => $sessionData
                ),
                'version' => '2_challenge'
            );
            
            
            $unrewritten_url = (defined('ENABLE_SSL') && ENABLE_SSL == 'true' ? HTTPS_SERVER . DIR_WS_HTTPS_CATALOG : HTTP_SERVER . DIR_WS_CATALOG) . 'index.php?main_page=payvector_3ds';
            if (zen_session_id() != '') $unrewritten_url .= '&zenid=' . zen_session_id();
            zen_redirect($unrewritten_url);
        } else {
            
            $finalResult = new ThreeDSecureFinalTransactionResult($boProcessed, $tdse, $authenticationResult, $outputData, $_SESSION);
            $payvector_module->_handleTransactionResult($finalResult, $processor, $order, 'new');
            
            $_SESSION['cart']->reset(true);
            $unrewritten_success_url = (defined('ENABLE_SSL') && ENABLE_SSL == 'true' ? HTTPS_SERVER . DIR_WS_HTTPS_CATALOG : HTTP_SERVER . DIR_WS_CATALOG) . 'index.php?main_page=' . FILENAME_CHECKOUT_SUCCESS;
            if (zen_session_id() != '') $unrewritten_success_url .= '&zenid=' . zen_session_id();
            zen_redirect($unrewritten_success_url);
        }
    } else {
        
        $md = isset($postData['MD']) ? $postData['MD'] : $crossReference;
        $paRes = isset($postData['PaRes']) ? $postData['PaRes'] : '';
        
        $result = $processor->check3DSecureResult($md, $paRes, $_SESSION);
        $payvector_module->_handleTransactionResult($result, $processor, $order, 'new');
        
        $_SESSION['cart']->reset(true);
        $unrewritten_success_url = (defined('ENABLE_SSL') && ENABLE_SSL == 'true' ? HTTPS_SERVER . DIR_WS_HTTPS_CATALOG : HTTP_SERVER . DIR_WS_CATALOG) . 'index.php?main_page=' . FILENAME_CHECKOUT_SUCCESS;
        if (zen_session_id() != '') $unrewritten_success_url .= '&zenid=' . zen_session_id();
        zen_redirect($unrewritten_success_url);
    }
}


if (!isset($_SESSION['payvector_3ds_data']) || empty($_SESSION['payvector_3ds_data']['acs_url'])) {
    zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', false, false, true));
}

$session_data = $_SESSION['payvector_3ds_data'];
$acs_url = $session_data['acs_url'];
$target = isset($session_data['target']) ? $session_data['target'] : 'threeDSecureFrame';
$form_params = isset($session_data['params']) ? $session_data['params'] : array();


$breadcrumb->add('PayVector 3D Secure Verification');
