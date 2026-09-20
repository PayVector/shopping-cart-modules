<?php

require_once __DIR__ . "/../../../../init.php";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/../../../../includes/gatewayfunctions.php";
require_once __DIR__ . "/../../../../includes/invoicefunctions.php";
require_once __DIR__ . "/../../../../includes/ccfunctions.php";

require_once (dirname(dirname(__FILE__)) . '/Config.php');
require_once (dirname(dirname(__FILE__)) . '/payvector/TransactionProcessor.php');
require_once (dirname(dirname(__FILE__)) . '/payvector/ISOHelper.php');
require_once (dirname(dirname(__FILE__)) . '/payvector/PaymentFormHelper.php');
require_once (dirname(dirname(__FILE__)) . '/Helper.php');
require_once (dirname(dirname(__FILE__)) . '/HandleTransactionResults.php');

$gateway = getGatewayVariables("payvector");
$CallingFunction = "3DSecureCallback";

require_once (dirname(dirname(dirname(__FILE__))) . '/payvector.php');

$step = isset($_GET['step']) ? $_GET['step'] : '';
$cardType = isset($_GET['payvector_card_type']) ? $_GET['payvector_card_type'] : '';
$cres = isset($_POST['cres']) ? $_POST['cres'] : (isset($_GET['cres']) ? $_GET['cres'] : '');
$threeDSMethodData = isset($_POST['threeDSMethodData']) ? $_POST['threeDSMethodData'] : (isset($_GET['threeDSMethodData']) ? $_GET['threeDSMethodData'] : '');
$paRes = isset($_POST['PaRes']) ? $_POST['PaRes'] : (isset($_GET['PaRes']) ? $_GET['PaRes'] : '');
$md = isset($_POST['MD']) ? $_POST['MD'] : (isset($_GET['MD']) ? $_GET['MD'] : '');

$invoiceId = isset($_GET['invoiceid']) ? $_GET['invoiceid'] : (isset($_SESSION['payvector_3ds_data']['invoiceid']) ? $_SESSION['payvector_3ds_data']['invoiceid'] : '');
$crossRef = isset($_GET['crossref']) ? $_GET['crossref'] : (isset($_SESSION['payvector_3ds_data']['crossref']) ? $_SESSION['payvector_3ds_data']['crossref'] : '');

if (empty($invoiceId) && !empty($md)) {
    $invoiceId = checkCbInvoiceID($invoiceId, "payvector");
}

if ($step === 'show_challenge') {
    $sessionData = $_SESSION['payvector_3ds_data'];
    $acsUrl = $sessionData['acs_url'];
    $target = $sessionData['target'];
    $paramsList = $sessionData['params'];
    ?>
    <div style="text-align:center; padding: 20px; font-family: sans-serif;">
        <h3>3D Secure Verification</h3>
        <p>Please complete the verification window below. Do not close or refresh this page.</p>
        <iframe name="<?php echo htmlspecialchars($target, ENT_QUOTES, 'UTF-8'); ?>" width="100%" height="700" frameborder="0"></iframe>
        <form id="threeds_form" action="<?php echo htmlspecialchars($acsUrl, ENT_QUOTES, 'UTF-8'); ?>" method="POST" target="<?php echo htmlspecialchars($target, ENT_QUOTES, 'UTF-8'); ?>">
            <?php foreach ($paramsList as $key => $value) { ?>
                <input type="hidden" name="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>" />
            <?php } ?>
        </form>
        <script type="text/javascript">
            var form = document.getElementById('threeds_form');
            if(form) form.submit();
        </script>
    </div>
    <?php
    exit;
} elseif (!empty($cres) || $step === 'challenge') {

    $threeDSSessionData = isset($_POST['threeDSSessionData']) ? $_POST['threeDSSessionData'] : '';
    if (!empty($threeDSSessionData)) {
        $szBase64 = strtr($threeDSSessionData, '-_', '+/');
        $nPadding = strlen($szBase64) % 4;
        if ($nPadding) {
            $szBase64 .= str_repeat('=', 4 - $nPadding);
        }
        $finalCrossRef = base64_decode($szBase64);
    } else {
        $finalCrossRef = $crossRef;
    }

    $UserID = payvectorDatabase::select3DS_UserID($finalCrossRef);
    $MerchantDetails = getMerchantDetails($gateway, $CallingFunction);

    $tp = new \TransactionProcessor();
    $tp->setMerchantID($MerchantDetails['MerchantID']);
    $tp->setMerchantPassword($MerchantDetails['Password']);

    $rgepl = new \net\thepaymentgateway\paymentsystem\RequestGatewayEntryPointList();
    $rgepl->add("https://gw1.payvector.net", 100, 2);
    $rgepl->add("https://gw2.payvector.net", 200, 2);
    $rgepl->add("https://gw3.payvector.net", 300, 2);
    $tp->setRgeplRequestGatewayEntryPointList($rgepl);

    $tdsa = new \net\thepaymentgateway\paymentsystem\ThreeDSecureAuthentication($rgepl);
    $tdsa->getMerchantAuthentication()->setMerchantID($MerchantDetails['MerchantID']);
    $tdsa->getMerchantAuthentication()->setPassword($MerchantDetails['Password']);
    $tdsa->getThreeDSecureInputData()->setCrossReference($finalCrossRef);
    $tdsa->getThreeDSecureInputData()->setCRES($cres);

    $authenticationResult = null;
    $outputData = null;
    $boProcessed = $tdsa->processTransaction($authenticationResult, $outputData);

    $finalResult = new \ThreeDSecureFinalTransactionResult($boProcessed, $tdsa, $authenticationResult, $outputData, $_SESSION);
    
    payvectorDatabase::delete3DS_Transaction($finalCrossRef, $UserID);

    $amount = payvectorDatabase::select3DS_Amount($finalCrossRef);
    $currency = payvectorDatabase::select3DS_ISOCurrencyCode($finalCrossRef);

    $storeGatewayID = ($cardType === 'new');
    $storeCardLastFour = ($cardType === 'new');
    $res = HandleTransactionResults($finalResult, $tp, array('clientdetails' => array('userid' => $UserID), 'invoiceid' => $invoiceId, 'amount' => $amount, 'currency' => $currency, 'showSavedCards' => isset($gateway['showSavedCards']) ? $gateway['showSavedCards'] : 'True'), $CallingFunction, $storeGatewayID, $storeCardLastFour);
    session_write_close();
    echo $res;
    exit;
} elseif (!empty($threeDSMethodData) || $step === 'fingerprint') {

    $UserID = payvectorDatabase::select3DS_UserID($crossRef);
    $MerchantDetails = getMerchantDetails($gateway, $CallingFunction);

    $tp = new \TransactionProcessor();
    $tp->setMerchantID($MerchantDetails['MerchantID']);
    $tp->setMerchantPassword($MerchantDetails['Password']);

    $rgepl = new \net\thepaymentgateway\paymentsystem\RequestGatewayEntryPointList();
    $rgepl->add("https://gw1.payvector.net", 100, 2);
    $rgepl->add("https://gw2.payvector.net", 200, 2);
    $rgepl->add("https://gw3.payvector.net", 300, 2);
    $tp->setRgeplRequestGatewayEntryPointList($rgepl);

    $tdse = new \net\thepaymentgateway\paymentsystem\ThreeDSecureEnvironment($rgepl);
    $tdse->getMerchantAuthentication()->setMerchantID($MerchantDetails['MerchantID']);
    $tdse->getMerchantAuthentication()->setPassword($MerchantDetails['Password']);
    $tdse->getThreeDSecureEnvironmentData()->setCrossReference($crossRef);
    $tdse->getThreeDSecureEnvironmentData()->setMethodData($threeDSMethodData);

    $authenticationResult = null;
    $outputData = null;
    $boProcessed = $tdse->processTransaction($authenticationResult, $outputData);

    if ($authenticationResult->getStatusCode() === 3) {

        $creq = $outputData->getThreeDSecureOutputData()->getCREQ();
        $acsUrl = $outputData->getThreeDSecureOutputData()->getACSURL();
        $szBase64 = base64_encode($outputData->getCrossReference());
        $sessionData = rtrim(strtr($szBase64, '+/', '-_'), '=');

        $_SESSION['payvector_3ds_data'] = array(
            'acs_url' => $acsUrl,
            'target'  => 'threeDSecureFrame',
            'params'  => array(
                'creq' => $creq,
                'threeDSSessionData' => $sessionData
            ),
            'version' => '2_challenge',
            'invoiceid' => $invoiceId,
            'crossref' => $crossRef
        );

        $showChallengeUrl = rtrim($gateway['systemurl'], '/') . '/modules/gateways/payvector/Callback/3DSecureCallback.php?step=show_challenge&payvector_card_type=' . urlencode($cardType);
        ?>
        <div style="text-align: center; font-family: sans-serif;">
            <p>Verification fingerprinting complete. Loading challenge...</p>
            <form action="<?php echo htmlspecialchars($showChallengeUrl, ENT_QUOTES, 'UTF-8'); ?>" method="post" id="three_ds_return_form" target="_parent">
            </form>
            <script>
                document.getElementById('three_ds_return_form').submit();
            </script>
        </div>
        <?php
    } else {

        $finalResult = new \ThreeDSecureFinalTransactionResult($boProcessed, $tdse, $authenticationResult, $outputData, $_SESSION);
        payvectorDatabase::delete3DS_Transaction($crossRef, $UserID);

        $amount = payvectorDatabase::select3DS_Amount($crossRef);
        $currency = payvectorDatabase::select3DS_ISOCurrencyCode($crossRef);

        $storeGatewayID = ($cardType === 'new');
        $storeCardLastFour = ($cardType === 'new');
        $res = HandleTransactionResults($finalResult, $tp, array('clientdetails' => array('userid' => $UserID), 'invoiceid' => $invoiceId, 'amount' => $amount, 'currency' => $currency, 'showSavedCards' => isset($gateway['showSavedCards']) ? $gateway['showSavedCards'] : 'True'), $CallingFunction, $storeGatewayID, $storeCardLastFour);
        session_write_close();
        echo $res;
        exit;
    }
    exit;
} else {

    $finalCrossRef = !empty($md) ? $md : $crossRef;
    $UserID = payvectorDatabase::select3DS_UserID($finalCrossRef);
    $MerchantDetails = getMerchantDetails($gateway, $CallingFunction);

    $tp = new \TransactionProcessor();
    $tp->setMerchantID($MerchantDetails['MerchantID']);
    $tp->setMerchantPassword($MerchantDetails['Password']);

    $rgepl = new \net\thepaymentgateway\paymentsystem\RequestGatewayEntryPointList();
    $rgepl->add("https://gw1.payvector.net", 100, 2);
    $rgepl->add("https://gw2.payvector.net", 200, 2);
    $rgepl->add("https://gw3.payvector.net", 300, 2);
    $tp->setRgeplRequestGatewayEntryPointList($rgepl);

    $result = $tp->check3DSecureResult($finalCrossRef, $paRes, $_SESSION);

    payvectorDatabase::delete3DS_Transaction($finalCrossRef, $UserID);

    $amount = payvectorDatabase::select3DS_Amount($finalCrossRef);
    $currency = payvectorDatabase::select3DS_ISOCurrencyCode($finalCrossRef);

    $storeGatewayID = ($cardType === 'new');
    $storeCardLastFour = ($cardType === 'new');
    $res = HandleTransactionResults($result, $tp, array('clientdetails' => array('userid' => $UserID), 'invoiceid' => $invoiceId, 'amount' => $amount, 'currency' => $currency, 'showSavedCards' => isset($gateway['showSavedCards']) ? $gateway['showSavedCards'] : 'True'), $CallingFunction, $storeGatewayID, $storeCardLastFour);
    session_write_close();
    echo $res;
    exit;
}