<?php

function StoreServerMethodResponse($postData)
{
    if (!payvectorDatabase::insertHPF_SERVER_Results($postData)) {
        mail($DeveloperEmailAddress, print_r(payvectorDatabase::GetErrorMessage(), 1));
    }
}

function RetrieveServerMethodResponse($CrossReference)
{
    $results = payvectorDatabase::selectHPF_SERVER_Results($CrossReference);
    if (!$results) {
        mail($DeveloperEmailAddress, "RetrieveServerMethodResponse", print_r(payvectorDatabase::GetErrorMessage(), 1));
    }

    return $results;
}

function RetrieveAndProcessTransactionResult($CrossReference)
{
    $results = payvectorDatabase::selectHPF_SERVER_Results($CrossReference);
    if (!$results) {
        mail($DeveloperEmailAddress, "RetrieveAndProcessTransactionResult", print_r(payvectorDatabase::GetErrorMessage(), 1));
        return false;
    }

    $transactionResult = new \HostedPaymentFormFinalTransactionResult($results);
    $order_id = $transactionResult->getOrderID($_SESSION);
    $status_code = $transactionResult->getStatusCode();
    $cross_reference = $transactionResult->getCrossReference();
    $card_last_four = $transactionResult->getCardLastFour($_SESSION);
    if (empty($card_last_four) && isset($_SESSION['payvector_saved_last4'])) {
        $card_last_four = $_SESSION['payvector_saved_last4'];
    }
    if (empty($card_last_four) && isset($results['CardNumberLastFour'])) {
        $card_last_four = $results['CardNumberLastFour'];
    }
    if (empty($card_last_four) && isset($results['CardLastFour'])) {
        $card_last_four = $results['CardLastFour'];
    }
    if (empty($card_last_four) && isset($results['CardLast4'])) {
        $card_last_four = $results['CardLast4'];
    }
    $card_type = $transactionResult->getCardType();

    $card_expiry_date = method_exists($transactionResult, 'getCardExpiryDate') ? $transactionResult->getCardExpiryDate() : null;


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

    return true;
}