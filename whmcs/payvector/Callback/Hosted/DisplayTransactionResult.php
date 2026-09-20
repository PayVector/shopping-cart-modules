<?php

require_once __DIR__ . "/../../../../../init.php";
require_once __DIR__ . "/../../../../../includes/functions.php";
require_once __DIR__ . "/../../../../../includes/clientfunctions.php";
require_once __DIR__ . "/../../../../../includes/gatewayfunctions.php";
require_once __DIR__ . "/../../../../../includes/invoicefunctions.php";
require_once __DIR__ . "/../../../../../includes/ccfunctions.php";

require_once (dirname(dirname(dirname(__FILE__))) . "/Config.php");
require_once (dirname(dirname(dirname(__FILE__))) . "/payvector/TransactionProcessor.php");
require_once (dirname(dirname(dirname(__FILE__))) . "/payvector/ISOHelper.php");
require_once (dirname(dirname(dirname(__FILE__))) . "/payvector/PaymentFormHelper.php");

if (!function_exists('getMerchantDetails')) {
    require_once (dirname(dirname(dirname(__FILE__))) . '/Helper.php');
}

$gateway       = getGatewayVariables("payvector");
$MerchantDetails = getMerchantDetails($gateway, 'DisplayTransactionResult');

$hash_matches = false;
$transactionResult = null;
$validateErrorMessage = '';

if (isset($_REQUEST['HashDigest']) || isset($_REQUEST['CrossReference'])) {
    
    $method = $gateway['hpfResultDeliveryMethod'];
    
    if ($method === 'POST') {
        $hash_matches = \PaymentFormHelper::validateTransactionResult_POST(
            $MerchantDetails['MerchantID'],
            $MerchantDetails['Password'],
            $gateway['pskPreSharedKey'],
            $gateway['hpfHashMethod'],
            $_POST,
            $transactionResult,
            $validateErrorMessage
        );
    } elseif ($method === 'SERVER_PULL') {
        $hash_matches = \PaymentFormHelper::validateTransactionResult_SERVER_PULL(
            $MerchantDetails['MerchantID'],
            $MerchantDetails['Password'],
            $gateway['pskPreSharedKey'],
            $gateway['hpfHashMethod'],
            $_GET,
            'https://mms.payvector.net/Pages/PublicPages/PaymentFormResultHandler.ashx',
            $transactionResult,
            $validateErrorMessage
        );
    } elseif ($method === 'SERVER') {
        require_once(dirname(__FILE__) . '/ServerMethodResponse.php');
        $db_results = RetrieveServerMethodResponse($_REQUEST['CrossReference']);
        if ($db_results) {
            $hash_matches = \PaymentFormHelper::validateTransactionResult_POST(
                $MerchantDetails['MerchantID'],
                $MerchantDetails['Password'],
                $gateway['pskPreSharedKey'],
                $gateway['hpfHashMethod'],
                $db_results,
                $transactionResult,
                $validateErrorMessage
            );
        } else {
            $validateErrorMessage = "No transaction result found in database for CrossReference.";
        }
    }

    if ($hash_matches && $transactionResult) {
        $finalResult = new \HostedPaymentFormFinalTransactionResult($transactionResult);
        
        require_once (dirname(dirname(dirname(__FILE__))) . "/HandleTransactionResults.php");
        
        
        $isoCurrencyList = \ISOHelper::getISOCurrencyList();
        $exponent = 2;
        if ($isoCurrencyList->getISOCurrency($gateway['currency'], $isoCurrency)) {
            $exponent = $isoCurrency->getExponent();
        }
        $amountReceived = $finalResult->getAmountReceived() / pow(10, $exponent);

        $redirectHtml = HandleTransactionResults($finalResult, null, array(
            'clientdetails' => array('userid' => $_SESSION['uid']),
            'invoiceid' => $finalResult->getOrderID($_SESSION),
            'amount' => $amountReceived,
            'paymentmethod' => 'payvector',
            'showSavedCards' => isset($gateway['showSavedCards']) ? $gateway['showSavedCards'] : 'True'
        ), 'DisplayTransactionResult');
        
        echo $redirectHtml;
    } else {
        $invoiceid = 0;
        if (isset($_REQUEST['OrderID'])) {
            $parts = explode('-', $_REQUEST['OrderID']);
            $invoiceid = (int)$parts[0];
        }
        
        if ($invoiceid > 0) {
            $systemUrl = preg_replace('/^https?:/', '', rtrim($gateway['systemurl'], '/'));
            $redirecturl = $systemUrl . '/viewinvoice.php?id=' . $invoiceid . '&paymentfailed=true&paymenterrormsg=' . urlencode($validateErrorMessage);
            echo '<html>
                    <head>
                        <script type="text/javascript">top.location="' . $redirecturl . '";</script>
                    </head>
                    <body>
                        <p>Payment Processing Error. Please wait while you are redirected back to the invoice...</p>
                    </body>
                  </html>';
        } else {
            echo "Payment verification failed: " . htmlspecialchars($validateErrorMessage, ENT_QUOTES, 'UTF-8');
        }
    }
} else {
    echo "No transaction variables received.";
}
?>