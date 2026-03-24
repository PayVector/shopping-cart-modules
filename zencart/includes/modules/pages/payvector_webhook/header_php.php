<?php

if (defined('MODULE_PAYMENT_PAYVECTOR_STATUS') && MODULE_PAYMENT_PAYVECTOR_STATUS == 'True') {
    require_once(DIR_WS_CLASSES . 'payment.php');
    $payment_modules = new payment('payvector');
    
    
    if (isset($GLOBALS['payvector'])) {
        $payvector_module = $GLOBALS['payvector'];
        
        
        $_GET['action'] = 'callback';
        $payvector_module->call_webhooks();
    }
}
exit;