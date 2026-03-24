<?php
chdir('../../../../');


if (isset($_GET['zenid']) && !empty($_GET['zenid'])) {
    session_id($_GET['zenid']);
}

require_once( 'includes/application_top.php' );

if (defined('MODULE_PAYMENT_PAYVECTOR_STATUS') && MODULE_PAYMENT_PAYVECTOR_STATUS == 'True') {
    require(DIR_WS_CLASSES . 'payment.php');
    $payment_modules = new payment('payvector');
    
    if (isset($GLOBALS['payvector'])) {
        $payvector_module = $GLOBALS['payvector'];        
        $payvector_module->call_webhooks();
    }
} else {
    header('HTTP/1.1 404 Not Found');
    exit;
}
