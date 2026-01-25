<?php
/**
 * PayVector Payment Module for osCommerce 4
 * 
 * @package PayVector
 * @version 1.0.0
 * @author PayVector
 */

namespace common\modules\orderPayment;

require_once dirname(__FILE__) . '/lib/payvector/TransactionProcessor.php';

use common\classes\modules\ModulePayment;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;

class Payvector extends ModulePayment {

    var $code, $title, $description, $enabled, $form_action_url, $processing_status;
    const AS_TEMP_ORDER = false;

    /**
     * default values for translation
     */
    protected $defaultTranslationArray = [
        'MODULE_PAYMENT_PAYVECTOR_TEXT_TITLE' => 'PayVector',
        'MODULE_PAYMENT_PAYVECTOR_TEXT_DESCRIPTION' => 'PayVector Payment Gateway - Accept credit and debit card payments',
        'MODULE_PAYMENT_PAYVECTOR_ERROR' => 'There has been an error processing your payment',
    ];

    /**
     * class constructor
     */
    function __construct($order_id = -1) {
        parent::__construct();
        
        $this->code = 'payvector';
        $this->title = MODULE_PAYMENT_PAYVECTOR_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_PAYVECTOR_TEXT_DESCRIPTION;
        
        if (!defined('MODULE_PAYMENT_PAYVECTOR_STATUS')) {
            $this->enabled = false;
            return false;
        }
        
        $this->sort_order = MODULE_PAYMENT_PAYVECTOR_SORT_ORDER;
        $this->enabled = ((MODULE_PAYMENT_PAYVECTOR_STATUS == 'True') ? true : false);
        $this->online = false;
        
        $this->update_status();
        
        $this->mode = defined('MODULE_PAYMENT_PAYVECTOR_MODE') ? MODULE_PAYMENT_PAYVECTOR_MODE : 'Hosted Payment Form';                        
        $this->mid = defined('MODULE_PAYMENT_PAYVECTOR_MID') ? MODULE_PAYMENT_PAYVECTOR_MID : '';
        $this->pass = defined('MODULE_PAYMENT_PAYVECTOR_PASS') ? MODULE_PAYMENT_PAYVECTOR_PASS : '';
        $this->hpf_psk = defined('MODULE_PAYMENT_PAYVECTOR_HPF_PSK') ? MODULE_PAYMENT_PAYVECTOR_HPF_PSK : '';
        $this->hpf_hash = defined('MODULE_PAYMENT_PAYVECTOR_HPF_HASH') ? MODULE_PAYMENT_PAYVECTOR_HPF_HASH : 'SHA1';
        $this->hpf_rdm = defined('MODULE_PAYMENT_PAYVECTOR_HPF_RDM') ? MODULE_PAYMENT_PAYVECTOR_HPF_RDM : 'POST';
        $this->order_id = $order_id;
        $this->hpf_handler_url = 'https://mms.payvector.net/Pages/PublicPages/PaymentFormResultHandler.ashx';
        $this->hpf_url = 'https://mms.payvector.net/Pages/PublicPages/PaymentForm.aspx';
        
    }

    function update_status() {
        $this->enabled = true;
        
        if (($this->enabled == true) && ((int) MODULE_PAYMENT_PAYVECTOR_ZONE > 0)) {
            $check_flag = false;
            
            $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_PAYVECTOR_ZONE . "' and zone_country_id = '" . $this->delivery['country']['id'] . "' order by zone_id");
            
            while ($check = tep_db_fetch_array($check_query)) {
                if ($check['zone_id'] < 1) {
                    $check_flag = true;
                    break;
                } elseif ($check['zone_id'] == $this->delivery['zone_id']) {
                    $check_flag = true;
                    break;
                }
            }

            if ($check_flag == false) {
                $this->enabled = false;
            }
            $this->enabled = true;
        }
    }

    function selection() {
        $selection = [
            'id' => $this->code,
            'module' => $this->title,
            'fields' => []
        ];

        
        if (defined('MODULE_PAYMENT_PAYVECTOR_SHOW_SAVED_CARDS') && MODULE_PAYMENT_PAYVECTOR_SHOW_SAVED_CARDS == 'True') {
            $saved_cards = $this->getSavedCards();
            
            foreach ($saved_cards as $card) {
                $cards_array[] = [
                    'id' => $card['id'], 
                    'text' => $card['card_type'] . ' ****' . $card['card_last_four']
                ];
            }
        }

        
        ob_start();
        $months = $this->getMonths();
        $years = $this->getYears();
        
        
        $billing_name = '';
        $billing_address = $this->manager->getBillingAddress();
        if ($billing_address) {
            $billing_name = ($billing_address['firstname'] ?? '') . ' ' . ($billing_address['lastname'] ?? '');
            $billing_name = trim($billing_name);
        }        
        
        
        $is_direct_api = ($this->mode == 'Direct API');
        
        include(dirname(__FILE__) . '/templates/payvector/payvector_selection.php');
        $selection_html = ob_get_clean();

        $selection['fields'][] = [
            'title' => '',
            'field' => $selection_html
        ];
        
        return $selection;
    }

    function pre_confirmation_check() {
        
        $keys_to_save = [
            'payvector_saved_card',
            'payvector_saved_cc_cvv',
            'payvector_new_cc_cvv', 
            'payvector_cc_cvv',
            'payvector_cc_owner',
            'payvector_cc_number',
            'payvector_cc_expires_month',
            'payvector_cc_expires_year',
            'payvector_browser_java_enabled',
            'payvector_browser_language',
            'payvector_browser_color_depth',
            'payvector_browser_screen_height',
            'payvector_browser_screen_width',
            'payvector_browser_tz',
            'payvector_browser_user_agent'
        ];
        
        foreach ($keys_to_save as $key) {
            if (isset($_POST[$key])) {
                $this->manager->set($key, $_POST[$key]);
            }
        }
        
        
        $saved_card_id = \Yii::$app->request->post('payvector_saved_card') ?: ($_POST['payvector_saved_card'] ?? null);
        if (!empty($saved_card_id) && (string)$saved_card_id !== 'new') {
            $cvv = \Yii::$app->request->post('payvector_saved_cc_cvv') ?: ($_POST['payvector_saved_cc_cvv'] ?? null);
            if (empty($cvv) || strlen($cvv) < 3) {
                tep_redirect(\Yii::$app->urlManager->createAbsoluteUrl(['checkout/index', 'payment_error' => $this->code, 'error' => 'CVV must be at least 3 characters']));
            }
        } else {
            if ($this->mode == 'Direct API') {
                 
                  $owner = \Yii::$app->request->post('payvector_cc_owner') ?: ($_POST['payvector_cc_owner'] ?? null);
                 
                 if (empty($owner)) {
                     tep_redirect(\Yii::$app->urlManager->createAbsoluteUrl(['checkout/index', 'payment_error' => $this->code, 'error' => 'Cardholder Name is required']));
                 }

                 
                  $cc_curr = \Yii::$app->request->post('payvector_cc_number') ?: ($_POST['payvector_cc_number'] ?? null);
                 if (empty($cc_curr) || strlen($cc_curr) < 10) { // Basic length check
                      tep_redirect(\Yii::$app->urlManager->createAbsoluteUrl(['checkout/index', 'payment_error' => $this->code, 'error' => 'Valid Card Number is required']));
                 }

                  $cvv = \Yii::$app->request->post('payvector_new_cc_cvv') ?: ($_POST['payvector_new_cc_cvv'] ?? null);
                 
                 if (empty($cvv)) {
                      $cvv = \Yii::$app->request->post('payvector_cc_cvv') ?: ($_POST['payvector_cc_cvv'] ?? null);
                 }
                 
                 if (empty($cvv) || strlen($cvv) < 3) {
                    tep_redirect(\Yii::$app->urlManager->createAbsoluteUrl(['checkout/index', 'payment_error' => $this->code, 'error' => 'CVV must be at least 3 characters']));
                 }
            }
        }
        
        return false;
    }

    protected function getSavedCards() {
        $cards = [];
        if (\Yii::$app->user->isGuest) {
            return $cards;
        }
        $customer_id = \Yii::$app->user->getId();
        
        $query = tep_db_query("SELECT * FROM payvector_cross_reference WHERE customer_id = '" . (int)$customer_id . "' ORDER BY id DESC");
        while ($row = tep_db_fetch_array($query)) {
            $cards[] = $row;
        }
        return $cards;
    }

    
    protected function get_payment_input($key) {
        $val = \Yii::$app->request->post($key) ?: ($_POST[$key] ?? null);
        if (empty($val) && $this->manager->has($key)) {
            $val = $this->manager->get($key);
        }
        return $val;
    }

    function before_process() {
        $this->_save_order();
        $saved_card_id = $this->get_payment_input('payvector_saved_card');        
        if (!empty($saved_card_id) && $saved_card_id != 'new') {
             $this->processDirectAPI();
             return;
        }

        if ($this->mode == 'Hosted Payment Form') {            
            tep_redirect($this->get_hpf_url());
        } else {            
            $this->processDirectAPI();
        }
    }

    protected function _save_order()
{
    if (!empty($this->order_id) && $this->order_id > 0) {
                return $this->order_id;
            }

    $order = $this->manager->getOrderInstance();

    $order->info['order_status'] = $this->getDefaultOrderStatusId();

    $order->save_order();
    $order->save_details();
    $order->save_products(false);

    $_SESSION['payvector_order_id'] = $order->order_id;

    $this->order_id = $order->order_id;
    return $this->order_id; 
}

    function get_hpf_url() {        
                
        $payvector_order_id = $this->_save_order();       
        $order = new \common\classes\Order($payvector_order_id);        
        
        $processor = new \TransactionProcessor();
        
        $processor->setMerchantID($this->mid);
        $processor->setMerchantPassword($this->pass);        
        
        $currencies = \Yii::$container->get('currencies');        
        $total = $order->info['total_inc_tax'] * $order->info['currency_value'];
        
        $currency_code = $order->info['currency'];
        
        
        list($iso_currency_code, $amount) = $this->setAmountFromCurrency($currency_code, $total);
        $processor->setCurrencyCode($iso_currency_code);
        $processor->setAmount($amount);
        $processor->setOrderID($order->order_id);
        $processor->setOrderDescription('Order number ' . $order->order_id);
        
        
        $processor->setCustomerName($order->billing['firstname'] . ' ' . $order->billing['lastname']);
        $processor->setAddress1($order->billing['street_address']);
        $processor->setAddress2('');
        $processor->setCity($order->billing['city']);
        $processor->setState($order->billing['state']);
        $processor->setPostcode($order->billing['postcode']);
        $processor->setCountryCode($this->getISOCountryCode($order->billing['country']['iso_code_2']));
        $processor->setEmailAddress($order->customer['email_address']);
        $processor->setPhoneNumber($order->customer['telephone']);
        
        
        $shop_url = \Yii::$app->urlManager->createAbsoluteUrl(['/']);
        $url_ssl = (stripos($shop_url, 'https') === 0) ? 'https' : 'http';
        $returnURL = \Yii::$app->urlManager->createAbsoluteUrl(['callback/webhooks', 'set' => 'payment', 'module' => $this->code, 'order_id' => $order->order_id, 'action' => 'callback'], $url_ssl);        
        
        $form_data = $processor->getHostedPaymentForm(
            $returnURL,
            $returnURL,
            $this->hpf_psk,
            $this->hpf_hash,
            $this->hpf_rdm,
            false,
            $_SESSION
        );
        
        
        $redirect_url = $this->hpf_url . '?' . http_build_query($form_data);
        return $redirect_url;
    }


    public function call_webhooks() {
        global $cart;
        $action = \Yii::$app->request->get('action', '');        
        
        if ($action == 'callback') {
            
            $hash_matches = false;
            $transaction_result = [];
            $validate_error_message = '';
            
            // Validate transaction result
            if ($this->hpf_rdm === 'POST') {
                $hash_matches = \PaymentFormHelper::validateTransactionResult_POST(
                    $this->mid,
                    $this->pass,
                    $this->hpf_psk,
                    $this->hpf_hash,
                    $_POST,
                    $transaction_result,
                    $validate_error_message
                );
            } elseif ($this->hpf_rdm === 'SERVER_PULL') {
               
                $hash_matches = \PaymentFormHelper::validateTransactionResult_SERVER_PULL(
                    $this->mid,
                    $this->pass,
                    $this->hpf_psk,
                    $this->hpf_hash,
                    $_GET,
                    $this->hpf_handler_url,
                    $transaction_result,
                    $validate_error_message
                );
            }
            
            if (!$hash_matches) {
                tep_redirect(\Yii::$app->urlManager->createAbsoluteUrl(['checkout/index', 'payment_error' => $this->code, 'error' => $validate_error_message]));
                exit;
            }
            
            
            $order_id = $transaction_result->getOrderID();            
            $status_code = $transaction_result->getStatusCode();
            $message = $transaction_result->getMessage();
            $cross_reference = $transaction_result->getCrossReference();
            $amount = $transaction_result->getAmount();
            
            $this->transactionInfo['order_id'] = $order_id;
            $this->transactionInfo['transaction_id'] = $cross_reference;
            $this->transactionInfo['transaction_details'] = json_encode(['Result' => 'Success', 'CrossReference' => $cross_reference]); // We can't json_encode the object directly easily if it has private props 
            $this->transactionInfo['amountPaid'] = $amount / 100; // Convert from minor units
            $this->transactionInfo['currencyCode'] = $transaction_result->getCurrencyCode();
            $this->transactionInfo['silent'] = true;
            $this->transactionInfo['transaction_status'] = $status_code;
            
            if ($status_code == 0) {
                
                $this->transactionInfo['status'] = 2;
                
                if (defined('MODULE_PAYMENT_PAYVECTOR_SHOW_SAVED_CARDS') && MODULE_PAYMENT_PAYVECTOR_SHOW_SAVED_CARDS == 'True') {
                    $this->saveCrossReference($order_id, new \HostedPaymentFormFinalTransactionResult($transaction_result));                
                }
                
                if (is_object($cart)) {
                    $cart->reset(true);
                }
                
                parent::processPaymentNotification(true);
                tep_redirect(\Yii::$app->urlManager->createAbsoluteUrl(['checkout/success']));
            } else {
                
                $this->transactionInfo['status'] = 5;
                parent::processPaymentCancellation();
                tep_redirect(\Yii::$app->urlManager->createAbsoluteUrl(['checkout/index', 'payment_error' => $this->code, 'error' => 'Payment Failed']));
            }
            exit;
        }
        
        
        tep_redirect(\Yii::$app->urlManager->createAbsoluteUrl(['checkout/success']));
    }

    public function saveCrossReference($order_id, $transaction_result) {
        $order = new \common\classes\Order($order_id);
        $customer_id = $order->customer['id'];
        
        
        
        if ($customer_id > 0) {
            
            $cross_reference = '';
            $card_last_four = '';
            $card_type = '';

            
            $cross_reference = $transaction_result->getCrossReference();
            $card_last_four = $transaction_result->getCardLastFour($_SESSION);            

            $card_type = $transaction_result->getCardType();            
            if (empty($card_last_four)) $card_last_four = $_SESSION['payvector_last4_cc_number'];
            if (!empty($card_last_four)) {
                
                $check_query = tep_db_query("SELECT id FROM payvector_cross_reference WHERE customer_id = '" . (int)$customer_id . "'");
                
                if (tep_db_num_rows($check_query) > 0) {
                
                    tep_db_query("UPDATE payvector_cross_reference SET 
                        cross_reference = '" . tep_db_input($cross_reference) . "',
                        card_last_four = '" . tep_db_input($card_last_four) . "',
                        card_type = '" . tep_db_input($card_type) . "',
                        transaction_datetime = NOW()
                        WHERE customer_id = '" . (int)$customer_id . "'");
                } else {
                
                    tep_db_query("INSERT INTO payvector_cross_reference 
                        (customer_id, cross_reference, card_last_four, card_type, transaction_datetime) 
                        VALUES (
                            '" . (int)$customer_id . "',
                            '" . tep_db_input($cross_reference) . "',
                            '" . tep_db_input($card_last_four) . "',
                            '" . tep_db_input($card_type) . "',
                            NOW()
                        )");
                }
            }
        }
    }

    private function getMonths() {
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[] = ['id' => sprintf('%02d', $i), 'text' => sprintf('%02d', $i)];
        }
        return $months;
    }

    private function getYears() {
        $years = [];
        $currentYear = date('Y');
        for ($i = 0; $i < 15; $i++) {
            $years[] = ['id' => $currentYear + $i, 'text' => $currentYear + $i];
        }
        return $years;
    }

    public function getEntryPointList() {
        $paymentProcessorDomain = "payvector.net";
        $rgepl_request_gateway_entry_point_list = new \net\thepaymentgateway\paymentsystem\RequestGatewayEntryPointList();      
        
        $rgepl_request_gateway_entry_point_list->add("https://gw1." . $paymentProcessorDomain, 100, 2);
        $rgepl_request_gateway_entry_point_list->add("https://gw2." . $paymentProcessorDomain, 200, 2);
        $rgepl_request_gateway_entry_point_list->add("https://gw3." . $paymentProcessorDomain, 300, 2);                

        return $rgepl_request_gateway_entry_point_list;
    }

    protected function processDirectAPI() {
        
        $payvector_order_id = $this->_save_order();
        $order = new \common\classes\Order($payvector_order_id);
        
        $processor = new \TransactionProcessor();
        
        $merchant_id = $this->mid;
        $merchant_password = $this->pass;
        
        $processor->setMerchantID($merchant_id);
        $processor->setMerchantPassword($merchant_password);
        
        
        $currencies = \Yii::$container->get('currencies');        
        $total = $order->info['total_inc_tax'] * $order->info['currency_value'];
        $currency_code = $order->info['currency'];
        
        // Set transaction details
        list($iso_currency_code, $amount) = $this->setAmountFromCurrency($currency_code, $total);
        $processor->setCurrencyCode($iso_currency_code);
        $processor->setAmount($amount);
        $processor->setOrderID($order->order_id);
        $processor->setOrderDescription('Order #' . $order->order_id);
        
        // Set customer details
        $processor->setCustomerName($order->billing['firstname'] . ' ' . $order->billing['lastname']);
        $processor->setAddress1($order->billing['street_address']);
        $processor->setCity($order->billing['city']);
        $processor->setState($order->billing['state']);
        $processor->setPostcode($order->billing['postcode']);
        $processor->setCountryCode($this->getISOCountryCode($order->billing['country']['iso_code_2']));
        $processor->setEmailAddress($order->customer['email_address']);
        $processor->setPhoneNumber($order->customer['telephone']);
        $processor->setIPAddress(\Yii::$app->request->userIP);
        $processor->setRgeplRequestGatewayEntryPointList($this->getEntryPointList());

        // 3DSv2 Browser Parameters
        $processor->setJavaEnabled($this->get_payment_input('payvector_browser_java_enabled'));
        $processor->setJavaScriptEnabled('true');
        $processor->setScreenWidth($this->get_payment_input('payvector_browser_screen_width'));
        $processor->setScreenHeight($this->get_payment_input('payvector_browser_screen_height'));
        $processor->setScreenColourDepth($this->get_payment_input('payvector_browser_color_depth'));
        $processor->setTimezoneOffset($this->get_payment_input('payvector_browser_tz'));
        $processor->setLanguage($this->get_payment_input('payvector_browser_language'));
        $processor->setChallengeNotificationURL(\Yii::$app->urlManager->createAbsoluteUrl(['payvector/threedsecure-return', 'order_id' => $order->order_id]));
        $processor->setFingerprintNotificationURL(\Yii::$app->urlManager->createAbsoluteUrl(['payvector/threedsecure-return', 'order_id' => $order->order_id]));
        $_SESSION['payvector_last4_cc_number']  = substr($this->get_payment_input('payvector_cc_number'), -4, 4);
        
        $saved_card_id = $this->get_payment_input('payvector_saved_card');        
        
        if (!empty($saved_card_id) && (string)$saved_card_id !== 'new') {        
             $cc_cvv = $this->get_payment_input('payvector_saved_cc_cvv');
        } else {        
             $cc_cvv = $this->get_payment_input('payvector_new_cc_cvv');
             if (empty($cc_cvv)) {
                 $cc_cvv = $this->get_payment_input('payvector_cc_cvv');
             }
        }
        
       
        $processor->setCV2($cc_cvv);

        $result = null;

        if (!empty($saved_card_id) && $saved_card_id != 'new') {
            
            $card_query = tep_db_query("SELECT cross_reference FROM payvector_cross_reference WHERE id = '" . (int)$saved_card_id . "' AND customer_id = '" . (int)$order->customer['id'] . "'");
            if (tep_db_num_rows($card_query) > 0) {
                $card_data = tep_db_fetch_array($card_query);
                $cross_reference = $card_data['cross_reference'];
                
                
                $result = $processor->doCrossReferenceTransaction(
                    $cross_reference,
                    false, 
                    $_SESSION
                );
                
            } else {                
                 tep_redirect(\Yii::$app->urlManager->createAbsoluteUrl(['checkout/index', 'payment_error' => $this->code, 'error' => 'Invalid Saved Card Selected']));
            }

        } else {
            
            $cc_number = $this->get_payment_input('payvector_cc_number');
            $cc_expiry_month = $this->get_payment_input('payvector_cc_expires_month');
            $cc_expiry_year = $this->get_payment_input('payvector_cc_expires_year');                       
            $cc_number = str_replace(' ', '', $cc_number);                        
            if (strlen($cc_expiry_year) == 2) {
                $cc_expiry_year = '20' . $cc_expiry_year;
            }
            $result = $processor->doCardDetailsTransaction(
                $cc_number,
                $cc_expiry_month,
                $cc_expiry_year,
                '', 
                $_SESSION
            );
        }
        
        // Cleanup sensitive data
        $keys_to_remove = [
            'payvector_saved_cc_cvv',
            'payvector_new_cc_cvv', 
            'payvector_cc_cvv',
            'payvector_cc_owner',
            'payvector_cc_number',
            'payvector_cc_expires_month',
            'payvector_cc_expires_year'
        ];
        
        foreach ($keys_to_remove as $key) {
             if ($this->manager->has($key)) {
                 $this->manager->remove($key);
             }
        }
        
        $this->_handleTransactionResult($result, $processor, $order);
    }

    public function _handleTransactionResult($result, $processor, $order) {
        
        if ($result->transactionProcessed() && $result->transactionSuccessful()) {                          
             $this->transactionInfo['order_id'] = $order->order_id;             
             $this->transactionInfo['transaction_id'] = $result->getCrossReference();
             $this->transactionInfo['amountPaid'] = $result->getAmountReceived() / 100;           
             
             $this->transactionInfo['status'] = 2;              
             $this->transactionInfo['transaction_details'] = json_encode(['Result' => 'Success', 'CrossReference' => $result->getCrossReference()]);             
             
             if (defined('MODULE_PAYMENT_PAYVECTOR_SHOW_SAVED_CARDS') && MODULE_PAYMENT_PAYVECTOR_SHOW_SAVED_CARDS == 'True') {
                 $this->saveCrossReference($order->order_id, $result);                        
             }
             
             parent::processPaymentNotification(true);
             
        } elseif ($result->getStatusCode() === 3) {     
            $threeDSecureOutput = $result->getThreeDSecureOutputData();
            
            $_SESSION['payvector_md'] = $result->getCrossReference();
            $_SESSION['payvector_order_id'] = $order->order_id;

            $params = [];
            $acsUrl = $threeDSecureOutput->getMethodURL();
            $params['ThreeDSMethodData'] = $threeDSecureOutput->getMethodData();
            
            // Store 3DS data in session
            $_SESSION['payvector_3ds_data'] = [
                'acs_url' => $acsUrl,
                'target' => 'threeDSecureFrame',
                'params' => $params
            ];
            
            tep_redirect(\Yii::$app->urlManager->createAbsoluteUrl(['payvector/three-ds', 'order_id' => $order->order_id]));
            exit;
            
        } else {             
             $message = $result->getMessage();             
             tep_redirect(\Yii::$app->urlManager->createAbsoluteUrl(['checkout/index', 'payment_error' => $this->code, 'error' => 'Payment Failed: ' . $message]));
        }
    }

    function process_button() {
        $payparam = array(
            "order_id" => $this->order_id
        );
        foreach ($payparam as $key => $value) {
            $process_button_string .= tep_draw_hidden_field($key, $value);
        }
        return $process_button_string;
    }

    function isOnline() {
        return true;
    }

    protected function getISOCountryCode($country_code) {
        $iso_country_list = \ISOHelper::getISOCountryList();
        if (!empty($country_code) && $iso_country_list->getISOCountry($country_code, $iso_country)) {
            return $iso_country->getISOCode();
        }
        return '';
    }



    protected function setAmountFromCurrency($currency_code, $amount) {
        $amount = number_format($amount, 2, '.', '');
        $iso_currency_list = \ISOHelper::getISOCurrencyList();
        $isoCurrencyCode = '';
        
        if (!empty($currency_code) && $iso_currency_list->getISOCurrency($currency_code, $iso_currency)) {
            $isoCurrencyCode = $iso_currency->getISOCode();
            $amount = (string) $amount;
            $amount = round($amount * ("1" . str_repeat(0, $iso_currency->getExponent())));
        }
        
        return [$isoCurrencyCode, $amount];
    }

    public function configure_keys() {
        return array(
            'MODULE_PAYMENT_PAYVECTOR_STATUS' => array(
                'title' => 'Enable PayVector Module',
                'value' => 'True',
                'description' => 'Do you want to accept PayVector payments?',
                'sort_order' => '1',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
            ),
            'MODULE_PAYMENT_PAYVECTOR_MODE' => array(
                'title' => 'Payment Mode',
                'value' => 'Hosted Payment Form',
                'description' => 'Select payment mode: ',
                'sort_order' => '2',
                'set_function' => 'tep_cfg_select_option(array(\'Direct API\', \'Hosted Payment Form\'), ',
            ),
            
            'MODULE_PAYMENT_PAYVECTOR_MID' => array(
                'title' => 'Merchant ID',
                'value' => '',
                'description' => 'Your PayVector merchant ID',
                'sort_order' => '6',
            ),
            'MODULE_PAYMENT_PAYVECTOR_PASS' => array(
                'title' => 'Password',
                'value' => '',
                'description' => 'Your PayVector  password',
                'sort_order' => '7',
            ),
            'MODULE_PAYMENT_PAYVECTOR_HPF_PSK' => array(
                'title' => 'Pre-Shared Key (HPF)',
                'value' => '',
                'description' => 'Pre-shared key for Hosted Payment Form',
                'sort_order' => '8',
            ),
            'MODULE_PAYMENT_PAYVECTOR_HPF_HASH' => array(
                'title' => 'Hash Method (HPF)',
                'value' => 'SHA1',
                'description' => 'Hash method for HPF',
                'sort_order' => '9',
                'set_function' => 'tep_cfg_select_option(array(\'SHA1\', \'MD5\', \'HMACSHA1\', \'HMACMD5\'), ',
            ),
            'MODULE_PAYMENT_PAYVECTOR_HPF_RDM' => array(
                'title' => 'Result Delivery Method (HPF)',
                'value' => 'POST',
                'description' => 'How results are delivered',
                'sort_order' => '10',
                'set_function' => 'tep_cfg_select_option(array(\'POST\', \'SERVER_PULL\'), ',
            ),
            
            'MODULE_PAYMENT_PAYVECTOR_ZONE' => array(
                'title' => 'Payment Zone',
                'value' => '0',
                'description' => 'If a zone is selected, only enable this payment method for that zone.',
                'sort_order' => '12',
                'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
                'set_function' => 'tep_cfg_pull_down_zone_classes(',
            ),
            'MODULE_PAYMENT_PAYVECTOR_SHOW_SAVED_CARDS' => array(
                'title' => 'Saved Cards',
                'value' => 'True',
                'description' => 'Do you want to show saved cards?',
                'sort_order' => '12',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
            ),
            'MODULE_PAYMENT_PAYVECTOR_SORT_ORDER' => array(
                'title' => 'Sort order of display',
                'value' => '0',
                'description' => 'Sort order of PayVector display. Lowest is displayed first.',
                'sort_order' => '13',
            ),
        );
    }

    public function describe_status_key() {
        return new ModuleStatus('MODULE_PAYMENT_PAYVECTOR_STATUS', 'True', 'False');
    }

    public function describe_sort_key() {
        return new ModuleSortOrder('MODULE_PAYMENT_PAYVECTOR_SORT_ORDER');
    }

    function install($platform_id) {
        tep_db_query("CREATE TABLE IF NOT EXISTS payvector_cross_reference (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            cross_reference VARCHAR(255) NOT NULL,
            card_last_four VARCHAR(4) NOT NULL,
            card_type VARCHAR(50) NOT NULL,
            transaction_datetime DATETIME NOT NULL,
            INDEX idx_customer (customer_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        
        parent::install($platform_id);
    }

    function remove($platform_id) {
        tep_db_query("DROP TABLE IF EXISTS payvector_cross_reference");
        parent::remove($platform_id);
    }
}