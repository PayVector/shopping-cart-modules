<?php

if (defined('DIR_EXTENSION')) {
	require_once DIR_EXTENSION . 'payvector/catalog/controller/payment/lib/TransactionProcessor.php';
	require_once DIR_EXTENSION . 'payvector/catalog/controller/payment/lib/ISOHelper.php';
	require_once DIR_EXTENSION . 'payvector/catalog/controller/payment/lib/PaymentFormHelper.php';
	require_once DIR_EXTENSION . 'payvector/catalog/controller/payment/lib/ThePaymentGateway/PaymentSystem.php';
	require_once DIR_EXTENSION . 'payvector/catalog/controller/payment/lib/ThePaymentGateway/SOAP.php';
	require_once DIR_EXTENSION . 'payvector/catalog/controller/payment/lib/ThePaymentGateway/TPG_Common.php';
} else {
	require_once DIR_SYSTEM . 'extension/payvector/catalog/controller/payment/lib/TransactionProcessor.php';
	require_once DIR_SYSTEM . 'extension/payvector/catalog/controller/payment/lib/ISOHelper.php';
	require_once DIR_SYSTEM . 'extension/payvector/catalog/controller/payment/lib/PaymentFormHelper.php';
	require_once DIR_SYSTEM . 'extension/payvector/catalog/controller/payment/lib/ThePaymentGateway/PaymentSystem.php';
	require_once DIR_SYSTEM . 'extension/payvector/catalog/controller/payment/lib/ThePaymentGateway/SOAP.php';
	require_once DIR_SYSTEM . 'extension/payvector/catalog/controller/payment/lib/ThePaymentGateway/TPG_Common.php';
}
