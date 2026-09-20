<?php


require (dirname(dirname(dirname(__FILE__))) . "/Config.php");
require_once (dirname(dirname(dirname(__FILE__))) . "/payvector/TransactionProcessor.php");
require_once (dirname(dirname(dirname(__FILE__))) . "/payvector/ISOHelper.php");
require_once (dirname(dirname(dirname(__FILE__))) . "/payvector/PaymentFormHelper.php");

require_once __DIR__ . "/../../../../../init.php";
require_once __DIR__ . "/../../../../../includes/functions.php";
require_once __DIR__ . "/../../../../../includes/clientfunctions.php";
require_once __DIR__ . "/../../../../../includes/gatewayfunctions.php";
require_once __DIR__ . "/../../../../../includes/invoicefunctions.php";
require_once __DIR__ . "/../../../../../includes/ccfunctions.php";

if (!function_exists('getMerchantDetails')) {
    require_once (dirname(dirname(dirname(__FILE__))) . '/Helper.php');
}

$gateway       = getGatewayVariables("payvector");
$MerchantDetails = getMerchantDetails($gateway, "ReceiveTransactionResult");

$nOutputStatusCode = 0;
$szOutputMessage = "RECEIVED";
$validateErrorMessage = "";
$transactionResult = null;

$hash_matches = \PaymentFormHelper::validateTransactionResult_POST(
    $MerchantDetails['MerchantID'],
    $MerchantDetails['Password'],
    $gateway['pskPreSharedKey'],
    $gateway['hpfHashMethod'],
    $_POST,
    $transactionResult,
    $validateErrorMessage
);

        if (!$hash_matches) {
            $nOutputStatusCode = 30;
            $szOutputMessage = "Hash Verification Failed: " . $validateErrorMessage;
        } else {
            require_once(dirname(__FILE__) . '/ServerMethodResponse.php');
            StoreServerMethodResponse($_POST);

            
            try {
                $finalResult = new \HostedPaymentFormFinalTransactionResult($transactionResult);
                $order_id = $finalResult->getOrderID($_SESSION);
                $status_code = $finalResult->getStatusCode();
                $cross_reference = $finalResult->getCrossReference();
                $card_expiry_date = method_exists($finalResult, 'getCardExpiryDate') ? $finalResult->getCardExpiryDate() : null;
                $card_last_four = $finalResult->getCardLastFour($_SESSION);
                if (empty($card_last_four) && isset($_SESSION['payvector_saved_last4'])) {
                    $card_last_four = $_SESSION['payvector_saved_last4'];
                }
                if (empty($card_last_four) && isset($_POST['CardNumberLastFour'])) {
                    $card_last_four = $_POST['CardNumberLastFour'];
                }
                if (empty($card_last_four) && isset($_POST['CardLastFour'])) {
                    $card_last_four = $_POST['CardLastFour'];
                }
                if (empty($card_last_four) && isset($_POST['CardLast4'])) {
                    $card_last_four = $_POST['CardLast4'];
                }
                $card_type = $finalResult->getCardType();

                
                $clean_order_id = current(explode('-', $order_id));
                if (is_numeric($clean_order_id)) {
                    $customer_id = \WHMCS\Database\Capsule::table('tblinvoices')
                        ->where('id', $clean_order_id)
                        ->value('userid');

                    if ($customer_id) {
                        payvectorDatabase::insertCrossReference($customer_id, $cross_reference, $card_type, $card_last_four);
                        payvectorDatabase::saveCardPayMethod($customer_id, $cross_reference, $card_last_four, $card_expiry_date, $card_type);
                    }
                }
            } catch (\Exception $e) {
                logTransaction("Payvector Corporation", ['Error' => $e->getMessage()], "error");
            }
        }

echo("StatusCode=" . $nOutputStatusCode . "&Message=" . print_r($szOutputMessage, 1));
?>