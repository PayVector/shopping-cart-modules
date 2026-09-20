<?php

define("CLIENTAREA", true);
require_once __DIR__ . "/../../../../init.php";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/../../../../includes/functions.php";
require_once __DIR__ . "/../../../../includes/clientfunctions.php";
require_once __DIR__ . "/../../../../includes/gatewayfunctions.php";
require_once __DIR__ . "/../../../../includes/invoicefunctions.php";

$gatewayParams = getGatewayVariables("payvector");
if (!$gatewayParams["type"]) die("Module Not Activated");

if (!isset($_SESSION['uid'])) {
    die("Not logged in");
}

$invoiceid = (int)$_POST['invoiceid'];


try {
    $invoice = \WHMCS\Database\Capsule::table('tblinvoices')->where('id', $invoiceid)->where('userid', $_SESSION['uid'])->first();
    if (!$invoice || $invoice->status != 'Unpaid') {
        die("Invalid Invoice or Invoice already paid.");
    }

    $userid = $invoice->userid;
    $client = \WHMCS\Database\Capsule::table('tblclients')->where('id', $userid)->first();

    require_once (dirname(dirname(dirname(__FILE__))) . "/payvector.php");

    $params = $gatewayParams;
    $params['invoiceid'] = $invoiceid;
    $params['amount'] = $invoice->total;
    
    
    $currencyCode = \WHMCS\Database\Capsule::table('tblcurrencies')->where('id', $client->currency)->value('code');
    $params['currency'] = $currencyCode ? $currencyCode : 'GBP';

    if (!function_exists('payvector_getSavedCardDetails')) {
        require_once (dirname(dirname(__FILE__)) . '/Helper.php');
    }
    list($gatewayId, $cardLastFour, $cardType) = payvector_getSavedCardDetails($userid);

    $params['clientdetails'] = array(
        'userid' => $userid,
        'firstname' => $client->firstname,
        'lastname' => $client->lastname,
        'email' => $client->email,
        'address1' => $client->address1,
        'address2' => $client->address2,
        'city' => $client->city,
        'state' => $client->state,
        'postcode' => $client->postcode,
        'country' => $client->country,
        'phonenumber' => $client->phonenumber,
        'gatewayid' => $gatewayId
    );

    $params['gatewayid'] = $gatewayId;
    $params['cardlastfour'] = $cardLastFour;


    $fields = [
        'payvector_saved_card', 'payvector_saved_cc_cvv', 'payvector_cc_owner',
        'ccnumber', 'ccexpirymonth', 'ccexpiryyear', 'cccvv',
        'payvector_browser_java_enabled', 'payvector_browser_language', 
        'payvector_browser_color_depth', 'payvector_browser_screen_height', 
        'payvector_browser_screen_width', 'payvector_browser_tz', 
        'payvector_browser_user_agent'
    ];
    
    foreach ($fields as $f) {
        if (isset($_POST[$f])) {
            $_REQUEST[$f] = $_POST[$f];
        }
    }

    if (isset($_POST['payvector_saved_card'])) {
        $_SESSION['payvector_attempted_card_type'] = $_POST['payvector_saved_card'];
    }

    
    unset($_SESSION['PaymentError']);
    unset($_SESSION['paymenterrormsg']);
    unset($_SESSION['paymenterror']);

    
    $result = payvector_capture($params);

    if (is_array($result)) {
        if (isset($result['status']) && $result['status'] == 'success') {
            unset($_SESSION['payvector_attempted_card_type']);
            session_write_close();
            header("Location: ../../../../viewinvoice.php?id=" . $invoiceid . "&paymentsuccess=true");
        } else {
            $cardType = isset($_POST['payvector_saved_card']) ? $_POST['payvector_saved_card'] : '';
            $errorMsg = isset($_SESSION['PaymentError']) ? $_SESSION['PaymentError'] : (isset($result['rawdata']['ErrorMessage']) ? $result['rawdata']['ErrorMessage'] : '');
            session_write_close();
            header("Location: ../../../../viewinvoice.php?id=" . $invoiceid . "&paymentfailed=true&paymenterrormsg=" . urlencode($errorMsg) . "&payvector_card_type=" . urlencode($cardType));
        }
        exit;
    } 
    
    if ($result === 'success') {
        unset($_SESSION['payvector_attempted_card_type']);
        session_write_close();
        header("Location: ../../../../viewinvoice.php?id=" . $invoiceid . "&paymentsuccess=true");
        exit;
    }
    
    if ($result === 'declined' || $result === 'failed') {
        $cardType = isset($_POST['payvector_saved_card']) ? $_POST['payvector_saved_card'] : '';
        $errorMsg = isset($_SESSION['PaymentError']) ? $_SESSION['PaymentError'] : '';
        session_write_close();
        header("Location: ../../../../viewinvoice.php?id=" . $invoiceid . "&paymentfailed=true&paymenterrormsg=" . urlencode($errorMsg) . "&payvector_card_type=" . urlencode($cardType));
        exit;
    }
    
    if (is_string($result) && strpos($result, '<meta http-equiv="refresh"') !== false) {        
        echo $result;
        exit;
    }

    
    session_write_close();
    
    include_once ROOTDIR . '/includes/header.php';
    echo $result;
    include_once ROOTDIR . '/includes/footer.php';
    
} catch (\Exception $e) {
    die("An error occurred: " . $e->getMessage());
}
exit;
