<?php
// Heading
$_['heading_title']    = 'PayVector';

// Text
$_['text_extension']    = 'Extensions';
$_['text_edit']         = 'Settings';

// Error
$_['heading_title'] = 'PayVector';
$_['heading_version']  = 'v2.0.4';
$_['heading_builddate'] = '15 April 2025';

// Text
$_['text_payvector'] = '<img src="' . HTTP_CATALOG . 'extension/payvector/admin/view/image/payment/payvector.png" alt="PayVector" title="PayVector" style="border: 1px solid #EEEEEE; padding: 3px;" />';
$_['text_capture_method_direct'] = 'Direct API';
$_['text_capture_method_hpf'] = 'Hosted Payment Form';
$_['text_capture_method_transparent'] = 'Transparent Redirect';
$_['text_payment'] = 'Payment';
$_['text_success'] = 'Settings updated successfully';
$_['text_development'] = '<span style="color: green;">Ready</span>';
$_['text_transaction_type_sale'] = 'SALE';
$_['text_transaction_type_preauth'] = 'PREAUTH';
$_['text_result_delivery_method_post'] = "POST";
$_['text_result_delivery_method_server_pull'] = "SERVER_PULL";

// Entry
$_['entry_status'] = 'Module Status';
$_['entry_title'] = 'Title';
$_['entry_geo_zone'] = 'Geo Zone';
$_['entry_order_status'] = 'Successful Transaction Order Status';
$_['entry_failed_order_status'] = 'Failed Transaction Order Status';
$_['entry_capture_method'] = 'Capture Method';
$_['entry_mid'] = 'Merchant ID';
$_['entry_pass'] = 'Merchant Password';
$_['entry_pre_shared_key'] = 'Pre Shared Key';
$_['entry_result_delivery_method'] = "Result Delivery Method";
$_['entry_hash_method'] = "Hash Method";
$_['entry_test'] = 'Test Mode';
$_['entry_sort_order'] = 'Sort Order';
$_['entry_transaction_type'] = 'Transaction Type';
$_['entry_enable_cross_reference'] = 'Enable Saved Card Functionality';
$_['entry_enable_3ds_cross_reference'] = 'Enable 3DSecure on Cross Reference Transactions';

// Help
$_['help_title'] = 'Title which the user sees during checkout';
$_['help_capture_method'] = 'Method of capturing card details.';
$_['help_mid'] = 'Enter your PayVector Merchant ID.';
$_['help_pass'] = 'Enter your PayVector Merchant password.';
$_['help_pre_shared_key'] = 'The Pre Shared Key can be found in the PayVector MMS.';
$_['help_result_delivery_method'] = "Method used to get the results of the transaction - please consult the getting started guide available in the MMS to choose the correct setting for your server setup.";
$_['help_hash_method'] = "<em>Must</em> be the same as the value set in the MMS";
$_['help_transaction_type'] = 'Select SALE to capture payment immediately. Select PREAUTH if you want to manually collect payment after authorisation.';
$_['help_enable_cross_reference'] = 'The saved card functionality requires that the customer\'s CVV code be passed back to your server before being forwarded to the PayVector ' .
	'gateway. Please note that enabling this functionality while using the Hosted Payment Form or Transparent Redirect capture method likely increases your PCI compliance obligations to ' .
	'the same level as using the Direct/API capture method.';
$_['help_enable_3ds_cross_reference'] = 'By default 3DSecure is disabled on cross reference transactions (Use saved card transactions), by enabling this setting 3DSecure will be ' .
	'performed on all cross reference transactions. Subscriptions are not effected, 3DSecure is never performed on CA transactions as the customer is not present.';
$_['help_order_status'] = 'Select the desired status for a successful transaction made through the PayVector gateway.';
$_['help_failed_order_status'] = 'Select the desired status for a failed transaction made through the PayVector gateway.';
$_['help_geo_zone'] = 'Select the zone which can use this payment method.';

// Error
$_['error_permission'] = 'Warning: You do not have permission to modify this payment module';
$_['error_mid'] = 'Merchant ID required';
$_['error_pass'] = 'Merchant Password required!';
$_['error_key'] = 'Pre Shared Key required';
