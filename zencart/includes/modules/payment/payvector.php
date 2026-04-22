<?php
/**
 * PayVector Payment Module for Zen Cart
 *
 * @package paymentMethod
 * @copyright Copyright 2026 PayVector
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

class payvector {
  var $code, $title, $description, $enabled, $sort_order;

  function __construct() {
    global $order;
    
    include_once(DIR_FS_CATALOG . DIR_WS_MODULES . 'payment/payvector/TransactionProcessor.php');   

    $this->code = 'payvector';
    $this->title = MODULE_PAYMENT_PAYVECTOR_TEXT_TITLE;
    $this->description = MODULE_PAYMENT_PAYVECTOR_TEXT_DESCRIPTION;
    $this->sort_order = defined('MODULE_PAYMENT_PAYVECTOR_SORT_ORDER') ? MODULE_PAYMENT_PAYVECTOR_SORT_ORDER : 0;
    $this->enabled = (defined('MODULE_PAYMENT_PAYVECTOR_STATUS') && MODULE_PAYMENT_PAYVECTOR_STATUS == 'True');
    
    $this->mode = defined('MODULE_PAYMENT_PAYVECTOR_MODE') ? MODULE_PAYMENT_PAYVECTOR_MODE : 'Hosted Payment Form';                        
    $this->mid = defined('MODULE_PAYMENT_PAYVECTOR_MERCHANT_ID') ? MODULE_PAYMENT_PAYVECTOR_MERCHANT_ID : '';
    $this->pass = defined('MODULE_PAYMENT_PAYVECTOR_PASSWORD') ? MODULE_PAYMENT_PAYVECTOR_PASSWORD : '';
    $this->hpf_psk = defined('MODULE_PAYMENT_PAYVECTOR_SECRET_KEY') ? MODULE_PAYMENT_PAYVECTOR_SECRET_KEY : '';
    $this->hpf_hash = defined('MODULE_PAYMENT_PAYVECTOR_HASH_METHOD') ? MODULE_PAYMENT_PAYVECTOR_HASH_METHOD : 'SHA1';
    $this->hpf_rdm = defined('MODULE_PAYMENT_PAYVECTOR_HPF_RDM') ? MODULE_PAYMENT_PAYVECTOR_HPF_RDM : 'POST';
    $this->order_id = isset($_GET['order_id']) ? $_GET['order_id'] : '';
    $this->hpf_handler_url = 'https://mms.payvector.net/Pages/PublicPages/PaymentFormResultHandler.ashx';
    $this->hpf_url = 'https://mms.payvector.net/Pages/PublicPages/PaymentForm.aspx';
    
    $this->gateway_url = 'https://mms.payvector.net/Pages/Public/PayVectorHPaymentForm.aspx';
    $this->api_url = 'https://mms.payvector.net/Pages/Public/PaymentGateway.aspx';

    if (null === $order) return false;

    if (is_object($order)) $this->update_status();
  }

  function update_status() {
    global $order, $db;

    if (($this->enabled == true) && ((int)MODULE_PAYMENT_PAYVECTOR_ZONE > 0)) {
      $check_flag = false;
      $check = $db->Execute("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_PAYVECTOR_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
      while (!$check->EOF) {
        if ($check->fields['zone_id'] < 1) {
          $check_flag = true;
          break;
        } elseif ($check->fields['zone_id'] == $order->billing['zone_id']) {
          $check_flag = true;
          break;
        }
        $check->MoveNext();
      }

      if ($check_flag == false) {
        $this->enabled = false;
      }
    }
  }

  function javascript_validation() {
    return false;
  }

  function getSavedCards() {
    global $db;
    $cards = array();
    
    if (isset($_SESSION['customer_id']) && (int)$_SESSION['customer_id'] > 0) {
        $customer_id = (int)$_SESSION['customer_id'];
        $query = $db->Execute("SELECT * FROM payvector_cross_reference WHERE customer_id = '" . $customer_id . "' ORDER BY id DESC");
        while (!$query->EOF) {
            $cards[] = $query->fields;
            $query->MoveNext();
        }
    }
    return $cards;
  }

  function isDirectAPI() {
    return ($this->mode == 'Direct API');
  }

  function isHostedPaymentForm() {
    return ($this->mode == 'Hosted Payment Form');
  }

  function selection() {
    global $db, $customer_id, $order;
    
    $selection = array('id' => $this->code,
                       'module' => $this->title);
                       
    $show_saved_cards = (defined('MODULE_PAYMENT_PAYVECTOR_SHOW_SAVED_CARDS') && MODULE_PAYMENT_PAYVECTOR_SHOW_SAVED_CARDS == 'True');
    $is_direct_api = $this->isDirectAPI();
    $is_hpf = $this->isHostedPaymentForm();
    
    if ($is_direct_api || $is_hpf) {
        $onFocus = ' onfocus="methodSelect(\'pmt-' . $this->code . '\')"';
        
        $saved_cards = $show_saved_cards ? $this->getSavedCards() : array();
        $fields_array = array();
        
        $selected_card = 'new';
        if (count($saved_cards) > 0) {
            $selected_card = (string)$saved_cards[0]['id'];
        }
        
        $session_card = isset($_SESSION['payvector_cc_data']['saved_card']) ? $_SESSION['payvector_cc_data']['saved_card'] : '';
        if ($session_card != '') {
            $selected_card = (string)$session_card;
        }
        
        if (isset($_GET['pvmode'])) {
            $selected_card = (string)$_GET['pvmode'];
            if ($selected_card == 'saved' && count($saved_cards) > 0) {
                 $selected_card = (string)$saved_cards[0]['id'];
            }
        }
        
        if (count($saved_cards) > 0) {
            foreach ($saved_cards as $card) {
                
                $text_part1 = 'Use saved card';
                $is_checked = ($selected_card === (string)$card['id']) ? 'checked="checked"' : '';
                $radio_field = '<input type="radio" name="payvector_saved_card" value="' . $card['id'] . '" class="payvector-card-selector" style="margin-left: 10px;" ' . $is_checked . $onFocus . ' />';
                
                $text_part2 = ' ending in ' . $card['last_four'] . ' (' . $card['card_type'] . ')';                
                
                $card_info_html =  $text_part2;
                
                $fields_array[] = array('title' =>  ' <span style="font-weight: bold; white-space: nowrap;">'. $radio_field. $text_part1 .  $card_info_html.'</span>' ,
                                      'field' => '');
                
                $cvv_display = ($selected_card === (string)$card['id']) ? '' : 'display:none;';
                $cvv_field = '<div id="payvector-cvv-' . $card['id'] . '" class="payvector-saved-cvv" style="margin-top: 5px; margin-left: 50px; ' . $cvv_display . '">' . 
                             MODULE_PAYMENT_PAYVECTOR_TEXT_CVV . ' ' . zen_draw_input_field('payvector_saved_cc_cvv_' . $card['id'], '', 'size="4" maxlength="4" style="width: 50px;"') . '</div>';
                $fields_array[] = array('title' => '', 'field' => $cvv_field);
            }
            
            
            $is_new_checked = ($selected_card === 'new') ? 'checked="checked"' : '';
            $radio_new = '<input type="radio" name="payvector_saved_card" value="new" class="payvector-card-selector" style="margin-left: 10px;" ' . $is_new_checked . $onFocus . ' />';
            $fields_array[] = array('title' => $radio_new . ' <span style="margin-left: 5px;">' . MODULE_PAYMENT_PAYVECTOR_TEXT_NEW_CARD . '</span>', 'field' => '');
        } else {
            
            $fields_array[] = array('title' => '', 'field' => zen_draw_hidden_field('payvector_saved_card', 'new'));
        }

        
        if ($is_direct_api) {
            $new_card_style = ($selected_card != 'new') ? 'display:none;' : '';
            $new_card_wrapper_start = '<div class="payvector-new-card" style="' . $new_card_style . '">';
            $new_card_wrapper_end = '</div>';

            $post_cc_owner = isset($_SESSION['payvector_cc_data']['cc_owner']) && !empty($_SESSION['payvector_cc_data']['cc_owner']) ? $_SESSION['payvector_cc_data']['cc_owner'] : $order->billing['firstname'] . ' ' . $order->billing['lastname'];

            $fields_array[] = array('title' => '<div class="payvector-new-card" style="' . $new_card_style . '">' . MODULE_PAYMENT_PAYVECTOR_TEXT_CREDIT_CARD_OWNER . '</div>',
                  'field' => '<div class="payvector-new-card" style="' . $new_card_style . '">' . zen_draw_input_field('payvector_cc_owner', $post_cc_owner, 'id="' . $this->code . '-cc-owner"' . $onFocus) . '</div>');
                  
            $fields_array[] = array('title' => '<div class="payvector-new-card" style="' . $new_card_style . '">' . MODULE_PAYMENT_PAYVECTOR_TEXT_CREDIT_CARD_NUMBER . '</div>',
                  'field' => '<div class="payvector-new-card" style="' . $new_card_style . '">' . zen_draw_input_field('payvector_cc_number', '', 'id="' . $this->code . '-cc-number"' . $onFocus) . '</div>');
                  
            $fields_array[] = array('title' => '<div class="payvector-new-card" style="' . $new_card_style . '">' . MODULE_PAYMENT_PAYVECTOR_TEXT_CREDIT_CARD_EXPIRES . '</div>',
                  'field' => '<div class="payvector-new-card" style="' . $new_card_style . '">' . zen_draw_pull_down_menu('payvector_cc_expires_month', $this->get_months(), '', 'id="' . $this->code . '-cc-expires-month" style="width: 50px; display: inline-block; padding: 0.3em; height: 35px; box-sizing: border-box; margin-left: 0;"' . $onFocus) . ' / ' . zen_draw_pull_down_menu('payvector_cc_expires_year', $this->get_years(), '', 'id="' . $this->code . '-cc-expires-year" style="width: 65px; display: inline-block; padding: 0.3em; height: 35px; box-sizing: border-box;"' . $onFocus) . '</div>');
                  
            $fields_array[] = array('title' => '<div class="payvector-new-card" style="' . $new_card_style . '">' . MODULE_PAYMENT_PAYVECTOR_TEXT_CVV . '</div>',
                  'field' => '<div class="payvector-new-card" style="' . $new_card_style . '">' . zen_draw_input_field('payvector_cc_cvv', '', 'size="4" maxlength="4" style="width: 50px;" id="' . $this->code . '-cc-cvv"' . $onFocus) . '</div>');
        }

        
        $js = '<script type="text/javascript">
            function togglePayVectorFields() {
                var selected = document.querySelector(\'input[name="payvector_saved_card"]:checked\');
                if (!selected) return;
                
                var isNew = (selected.value === "new");
                var newCardFields = document.querySelectorAll(".payvector-new-card");
                var savedCvvFields = document.querySelectorAll(".payvector-saved-cvv");
                var savedCardRadios = document.querySelectorAll(".payvector-saved-card");
                
                // Toggle New Card Fields
                newCardFields.forEach(function(el) {
                    el.style.display = isNew ? "block" : "none";
                });
                
                // Toggle Saved CVV Fields
                savedCvvFields.forEach(function(el) {
                    el.style.display = "none";
                });
                
                if (!isNew) {
                    var activeCvv = document.getElementById("payvector-cvv-" + selected.value);
                    if (activeCvv) activeCvv.style.display = "block";
                }
            }
            
            function getPayVector3DSv2Params() {
                if (document.getElementById("payvector_browser_java_enabled")) document.getElementById("payvector_browser_java_enabled").value = navigator.javaEnabled();
                if (document.getElementById("payvector_browser_language")) document.getElementById("payvector_browser_language").value = navigator.language || navigator.userLanguage;
                if (document.getElementById("payvector_browser_color_depth")) document.getElementById("payvector_browser_color_depth").value = screen.colorDepth;
                if (document.getElementById("payvector_browser_screen_height")) document.getElementById("payvector_browser_screen_height").value = screen.height;
                if (document.getElementById("payvector_browser_screen_width")) document.getElementById("payvector_browser_screen_width").value = screen.width;
                if (document.getElementById("payvector_browser_tz")) document.getElementById("payvector_browser_tz").value = new Date().getTimezoneOffset();
                if (document.getElementById("payvector_browser_user_agent")) document.getElementById("payvector_browser_user_agent").value = navigator.userAgent;
            }
            
            // Attach event listeners
            document.addEventListener("DOMContentLoaded", function() {
                var selectors = document.querySelectorAll(".payvector-card-selector");
                selectors.forEach(function(radio) {
                    radio.addEventListener("change", togglePayVectorFields);
                });
                // Initialize state
                togglePayVectorFields();
                getPayVector3DSv2Params();
            });
        </script>';

        $hidden_3ds = '<input type="hidden" name="payvector_browser_java_enabled" id="payvector_browser_java_enabled" value="">
        <input type="hidden" name="payvector_browser_language" id="payvector_browser_language" value="">
        <input type="hidden" name="payvector_browser_color_depth" id="payvector_browser_color_depth" value="">
        <input type="hidden" name="payvector_browser_screen_height" id="payvector_browser_screen_height" value="">
        <input type="hidden" name="payvector_browser_screen_width" id="payvector_browser_screen_width" value="">
        <input type="hidden" name="payvector_browser_tz" id="payvector_browser_tz" value="">
        <input type="hidden" name="payvector_browser_user_agent" id="payvector_browser_user_agent" value="">';

        $fields_array[] = array('title' => '', 'field' => $hidden_3ds . $js);
        
        $selection['fields'] = $fields_array;
    }

    return $selection;
  }
  
  function get_months() {
      $months = array();
      for ($i=1; $i<13; $i++) {
          $months[] = array('id' => sprintf('%02d', $i), 'text' => sprintf('%02d', $i));
      }
      return $months;
  }

  function get_years() {
      $years = array();
      $today = getdate();
      for ($i=$today['year']; $i < $today['year']+10; $i++) {
          $years[] = array('id' => date('y',mktime(0,0,0,1,1,$i)), 'text' => date('Y',mktime(0,0,0,1,1,$i)));
      }
      return $years;
  }

  function pre_confirmation_check() {
    $is_direct_api = $this->isDirectAPI();
    $is_hpf = $this->isHostedPaymentForm();
    $show_saved_cards = (defined('MODULE_PAYMENT_PAYVECTOR_SHOW_SAVED_CARDS') && MODULE_PAYMENT_PAYVECTOR_SHOW_SAVED_CARDS == 'True');
    if ($is_direct_api || $is_hpf) {
        $saved_card_id = isset($_POST['payvector_saved_card']) ? $_POST['payvector_saved_card'] : 'new';
        
        $_SESSION['payvector_cc_data'] = array(
            'saved_card' => $saved_card_id,
            'cc_owner' => isset($_POST['payvector_cc_owner']) ? $_POST['payvector_cc_owner'] : '',
            'cc_number' => isset($_POST['payvector_cc_number']) ? $_POST['payvector_cc_number'] : '',
            'cc_expires_month' => isset($_POST['payvector_cc_expires_month']) ? $_POST['payvector_cc_expires_month'] : '',
            'cc_expires_year' => isset($_POST['payvector_cc_expires_year']) ? $_POST['payvector_cc_expires_year'] : '',
            'cc_cvv' => isset($_POST['payvector_cc_cvv']) ? $_POST['payvector_cc_cvv'] : '',
            'browser_java_enabled' => isset($_POST['payvector_browser_java_enabled']) ? $_POST['payvector_browser_java_enabled'] : '',
            'browser_language' => isset($_POST['payvector_browser_language']) ? $_POST['payvector_browser_language'] : '',
            'browser_color_depth' => isset($_POST['payvector_browser_color_depth']) ? $_POST['payvector_browser_color_depth'] : '',
            'browser_screen_height' => isset($_POST['payvector_browser_screen_height']) ? $_POST['payvector_browser_screen_height'] : '',
            'browser_screen_width' => isset($_POST['payvector_browser_screen_width']) ? $_POST['payvector_browser_screen_width'] : '',
            'browser_tz' => isset($_POST['payvector_browser_tz']) ? $_POST['payvector_browser_tz'] : '',
            'browser_user_agent' => isset($_POST['payvector_browser_user_agent']) ? $_POST['payvector_browser_user_agent'] : ''
        );
        
        if ($saved_card_id != 'new' && (int)$saved_card_id > 0) {
            $_SESSION['payvector_cc_data']['cc_cvv'] = isset($_POST['payvector_saved_cc_cvv_' . $saved_card_id]) ? $_POST['payvector_saved_cc_cvv_' . $saved_card_id] : '';
        }
    }
    return false;
  }

  function confirmation() {
    return false;
  }

  function process_button() {
    return false;
  }

  function before_process() {
    return false;
  }

  function after_order_create($zf_insert_id) {
    global $order, $db, $messageStack, $currencies;
    
    $this->__construct();
    
    if (isset($_GET['action']) && $_GET['action'] == '3ds_return') {
        $paRes = $_POST['PaRes'];
        $crossReference = $_SESSION['payvector_cross_reference']; // Need to ensure this is stored
        
        $tp = new \TransactionProcessor();
        $tp->setMerchantID($this->mid);
        $tp->setMerchantPassword($this->pass);
        $tp->setRgeplRequestGatewayEntryPointList($this->getEntryPointList());
        $sessionHandler = $_SESSION;
        
        try {
            $result = $tp->check3DSecureResult($crossReference, $paRes, $sessionHandler);
            
            if ($result->getStatusCode() == 0) {
                
                return false;
            } else {
                 $cc_data = isset($_SESSION['payvector_cc_data']) ? $_SESSION['payvector_cc_data'] : array();
                 $saved_card_id = isset($cc_data['saved_card']) ? $cc_data['saved_card'] : 'new';
                 $messageStack->add_session('checkout_payment', MODULE_PAYMENT_PAYVECTOR_TEXT_DECLINED . ' ' . $result->getMessage(), 'error');
                 zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, 'pvmode=' . ($saved_card_id == 'new' ? 'new' : 'saved'), 'SSL', true, false));
            }
        } catch (Exception $e) {
             $cc_data = isset($_SESSION['payvector_cc_data']) ? $_SESSION['payvector_cc_data'] : array();
             $saved_card_id = isset($cc_data['saved_card']) ? $cc_data['saved_card'] : 'new';
             $messageStack->add_session('checkout_payment', 'Error processing 3DS result: ' . $e->getMessage(), 'error');
             zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, 'pvmode=' . ($saved_card_id == 'new' ? 'new' : 'saved'), 'SSL', true, false));
        }
        return;
    }
    
    $is_hpf = $this->isHostedPaymentForm();
    $is_direct_api = $this->isDirectAPI();
    
    $cc_data = isset($_SESSION['payvector_cc_data']) ? $_SESSION['payvector_cc_data'] : array();
    $saved_card_id = isset($cc_data['saved_card']) ? $cc_data['saved_card'] : 'new';
    
    
    if ($is_direct_api || ($is_hpf && $saved_card_id != 'new')) {
        return $this->processDirectAPI($zf_insert_id);
    }
    
    
    if ($is_hpf) {
            $tp = new \TransactionProcessor();
            $tp->setMerchantID($this->mid);
            $tp->setMerchantPassword($this->pass);
            $tp->setRgeplRequestGatewayEntryPointList($this->getEntryPointList());
            
            list($iso_currency_code, $amount) = $this->setAmountFromCurrency($order->info['currency'], $order->info['total']);
            $tp->setCurrencyCode($iso_currency_code);
            $tp->setAmount($amount);
            
            if ($zf_insert_id != '') {
                $tp->setOrderID($zf_insert_id);
            } else {
                $tp->setOrderID(date('YmdHis') . '-' . $_SESSION['customer_id']);
            }
            $tp->setOrderDescription('Order from ' . STORE_NAME);
            $tp->setCustomerName($order->billing['firstname'] . ' ' . $order->billing['lastname']);
            $tp->setAddress1($order->billing['street_address']);
            if (isset($order->billing['suburb']) && $order->billing['suburb'] != '') {
                $tp->setAddress2($order->billing['suburb']);
            }
            $tp->setCity($order->billing['city']);
            if (isset($order->billing['state']) && $order->billing['state'] != '') {
                $tp->setState($order->billing['state']);
            }
            $tp->setPostcode($order->billing['postcode']);
            $tp->setCountryCode($this->getISOCountryCode($order->billing['country']['iso_code_2']));
            $tp->setEmailAddress($order->customer['email_address']);
            $tp->setPhoneNumber($order->customer['telephone']);
            $ipAddress = zen_get_ip_address();
			if ($ipAddress != "" && $ipAddress != ".")
			{
				$tp->setIPAddress($ipAddress);
			}
            
            $callbackUrl = (defined('ENABLE_SSL') && ENABLE_SSL == 'true' ? HTTPS_SERVER . DIR_WS_HTTPS_CATALOG : HTTP_SERVER . DIR_WS_CATALOG) . 'ext/modules/payment/payvector/call_webhooks.php?zenid=' . zen_session_id();            
            $sessionHandler = $_SESSION;
            
            
            $formFields = $tp->getHostedPaymentForm(
                $callbackUrl,
                $callbackUrl,
                $this->hpf_psk,
                $this->hpf_hash,
                $this->hpf_rdm,
                true,
                $sessionHandler
            );
            
            $html = '<html><head><title>Processing Payment...</title></head><body onload="document.getElementById(\'payvector_form\').submit();">';
            $html .= '<form id="payvector_form" name="payvector_form" action="' . $this->hpf_url . '" method="post">';
            foreach ($formFields as $key => $value) {
                $html .= zen_draw_hidden_field($key, $value) . "\n";
            }
            $html .= '<div style="text-align: center; margin-top: 50px;">';
            $html .= '<p>Please wait, redirecting to payment gateway...</p>';
            $html .= '<noscript><input type="submit" value="Click here if you are not redirected." /></noscript>';
            $html .= '</div></form></body></html>';
            
            echo $html;
            exit;
    }
  }

  function processDirectAPI($zf_insert_id = '') {
      global $order, $db, $messageStack, $currencies;
      
      $cc_data = isset($_SESSION['payvector_cc_data']) ? $_SESSION['payvector_cc_data'] : array();
      
      $saved_card_id = isset($cc_data['saved_card']) ? $cc_data['saved_card'] : 'new';
      
      
      $_SESSION['payvector_order_id'] = $zf_insert_id;
      
      $tp = new \TransactionProcessor();
      $tp->setMerchantID($this->mid);
      $tp->setMerchantPassword($this->pass);
      $tp->setRgeplRequestGatewayEntryPointList($this->getEntryPointList());
      
      list($iso_currency_code, $amount) = $this->setAmountFromCurrency($order->info['currency'], $order->info['total']);
      $tp->setCurrencyCode($iso_currency_code);
      $tp->setAmount($amount);
      
      if ($zf_insert_id != '') {
          $tp->setOrderID($zf_insert_id);
      } else {
          $tp->setOrderID(date('YmdHis') . '-' . $_SESSION['customer_id']);
      }
      $tp->setOrderDescription('Order from ' . STORE_NAME);
      $tp->setCustomerName($order->billing['firstname'] . ' ' . $order->billing['lastname']);
      $tp->setAddress1($order->billing['street_address']);
      if (isset($order->billing['suburb']) && $order->billing['suburb'] != '') {
          $tp->setAddress2($order->billing['suburb']);
      }
      $tp->setCity($order->billing['city']);
      if (isset($order->billing['state']) && $order->billing['state'] != '') {
          $tp->setState($order->billing['state']);
      }
      $tp->setPostcode($order->billing['postcode']);
      $tp->setCountryCode($this->getISOCountryCode($order->billing['country']['iso_code_2']));
      $tp->setEmailAddress($order->customer['email_address']);
      $tp->setPhoneNumber($order->customer['telephone']);
      $ipAddress = zen_get_ip_address();
	  if ($ipAddress != "" && $ipAddress != ".")
      {
          $tp->setIPAddress($ipAddress);
      }     
      
      $tp->setJavaEnabled(isset($cc_data['browser_java_enabled']) ? $cc_data['browser_java_enabled'] : 'false');
      $tp->setJavaScriptEnabled('true');
      $tp->setScreenWidth(isset($cc_data['browser_screen_width']) ? $cc_data['browser_screen_width'] : '');
      $tp->setScreenHeight(isset($cc_data['browser_screen_height']) ? $cc_data['browser_screen_height'] : '');
      $tp->setScreenColourDepth(isset($cc_data['browser_color_depth']) ? $cc_data['browser_color_depth'] : '');
      $tp->setTimezoneOffset(isset($cc_data['browser_tz']) ? $cc_data['browser_tz'] : '');
      $tp->setLanguage(isset($cc_data['browser_language']) ? $cc_data['browser_language'] : '');
      
      $tp->setChallengeNotificationURL((defined('ENABLE_SSL') && ENABLE_SSL == 'true' ? HTTPS_SERVER . DIR_WS_HTTPS_CATALOG : HTTP_SERVER . DIR_WS_CATALOG) . 'ext/modules/payment/payvector/3ds_return.php');
      $tp->setFingerprintNotificationURL((defined('ENABLE_SSL') && ENABLE_SSL == 'true' ? HTTPS_SERVER . DIR_WS_HTTPS_CATALOG : HTTP_SERVER . DIR_WS_CATALOG) . 'ext/modules/payment/payvector/3ds_return.php');
      
      $sessionHandler = $_SESSION;
      
      if ($saved_card_id != 'new' && (int)$saved_card_id > 0) {
         
          $query = $db->Execute("SELECT * FROM payvector_cross_reference WHERE id = '" . (int)$saved_card_id . "' AND customer_id = '" . (int)$_SESSION['customer_id'] . "'");
          if (!$query->EOF) {
              $cross_reference = $query->fields['cross_reference'];
              $cc_cvv = isset($cc_data['cc_cvv']) ? $cc_data['cc_cvv'] : '';
              $tp->setCV2($cc_cvv);
              
              $result = $tp->doCrossReferenceTransaction(
                  $cross_reference,
                  false, 
                  $sessionHandler
              );
          } else {
              $messageStack->add_session('checkout_payment', MODULE_PAYMENT_PAYVECTOR_TEXT_DECLINED . ' Invalid Saved Card.', 'error');
              zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, 'pvmode=' . ($saved_card_id == 'new' ? 'new' : 'saved'), 'SSL', true, false));
              return;
          }
      } else {
          
          $cc_owner = isset($cc_data['cc_owner']) ? $cc_data['cc_owner'] : '';
          $cc_number = isset($cc_data['cc_number']) ? $cc_data['cc_number'] : '';
          $cc_expires_month = isset($cc_data['cc_expires_month']) ? $cc_data['cc_expires_month'] : '';
          $cc_expires_year = isset($cc_data['cc_expires_year']) ? $cc_data['cc_expires_year'] : '';
          $cc_cvv = isset($cc_data['cc_cvv']) ? $cc_data['cc_cvv'] : '';
          
          
          if ($cc_owner != '') {
              $tp->setCustomerName($cc_owner);
          }
          $tp->setCV2($cc_cvv);
          
          $result = $tp->doCardDetailsTransaction(
              $cc_number,
              $cc_expires_month,
              $cc_expires_year,
              '', 
              $sessionHandler
          );
          
          if (isset($_SESSION['payvector_cc_data']['cc_number'])) {
              $_SESSION['payvector_saved_last4'] = substr(str_replace(' ', '', $_SESSION['payvector_cc_data']['cc_number']), -4);
          }
      }
      
      
      $this->_handleTransactionResult($result, $tp, $order, 'api', $saved_card_id);
  }
  
  function _handleTransactionResult($result, $processor, $order, $paytype = null, $saved_card_id = 'new') {
      global $db, $messageStack;
      
      if ($result->transactionProcessed() && $result->transactionSuccessful()) {
          
          if ($saved_card_id == 'new' && isset($_SESSION['customer_id']) && (int)$_SESSION['customer_id'] > 0) {
              $xref = $result->getCrossReference();
              $last4 = isset($_SESSION['payvector_saved_last4']) ? $_SESSION['payvector_saved_last4'] : '';
              $card_type = $result->getCardType();
              $this->updateCrossReference($_SESSION['customer_id'], $xref, $card_type, $last4);
          }
          
          if (isset($_SESSION['payvector_saved_last4'])) {
              unset($_SESSION['payvector_saved_last4']);
          }
          if (isset($_SESSION['payvector_cc_data'])) {
              unset($_SESSION['payvector_cc_data']);
          }
          $order_id = $result->getOrderID($_SESSION);
          $clean_order_id = current(explode('-', $order_id));
          if (empty($clean_order_id) && isset($_SESSION['payvector_order_id'])) {
              $clean_order_id = current(explode('-', $_SESSION['payvector_order_id']));
          }

          $new_status = (defined('MODULE_PAYMENT_PAYVECTOR_ORDER_STATUS_ID') && MODULE_PAYMENT_PAYVECTOR_ORDER_STATUS_ID > 0) ? (int)MODULE_PAYMENT_PAYVECTOR_ORDER_STATUS_ID : 2; // Default to Processing (2)
          
          if (is_numeric($clean_order_id)) {
              $this->updateOrderStatus((int)$clean_order_id, $new_status, 'Success', $result->getCrossReference());
          }
          
          return false;
      } elseif ($result->getStatusCode() == 3) {
        
          $_SESSION['payvector_cross_reference'] = $result->getCrossReference();
          $_SESSION['payvector_order_id'] = isset($_SESSION['payvector_order_id']) ? $_SESSION['payvector_order_id'] : '';
          
          $threeDSecureOutput = $result->getThreeDSecureOutputData();
          if ($threeDSecureOutput && method_exists($threeDSecureOutput, 'getMethodURL') && $threeDSecureOutput->getMethodURL() != '') {
              
              $params = array();
              $acsUrl = $threeDSecureOutput->getMethodURL();
              $params['ThreeDSMethodData'] = $threeDSecureOutput->getMethodData();
              
              $_SESSION['payvector_3ds_data'] = array(
                  'acs_url' => $acsUrl,
                  'target'  => 'threeDSecureFrame',
                  'params'  => $params,
                  'version' => '2'
              );
          } else {
              
              $acsUrl = $result->getThreeDSecureACSURL();
              $params = array();
              $params['PaReq'] = $result->getThreeDSecurePaREQ();
              $params['MD'] = $result->getThreeDSecureMD();
              $params['TermUrl'] = (defined('ENABLE_SSL') && ENABLE_SSL == 'true' ? HTTPS_SERVER . DIR_WS_HTTPS_CATALOG : HTTP_SERVER . DIR_WS_CATALOG) . 'ext/modules/payment/payvector/3ds_return.php';
              
              $_SESSION['payvector_3ds_data'] = array(
                  'acs_url' => $acsUrl,
                  'target'  => '_parent',
                  'params'  => $params,
                  'version' => '1'
              );
          }
          
          $unrewritten_url = (defined('ENABLE_SSL') && ENABLE_SSL == 'true' ? HTTPS_SERVER . DIR_WS_HTTPS_CATALOG : HTTP_SERVER . DIR_WS_CATALOG) . 'index.php?main_page=payvector_3ds';
          if (zen_session_id() != '') $unrewritten_url .= '&zenid=' . zen_session_id();
          zen_redirect($unrewritten_url);
      } else {
          
          $message = $result->getMessage();
          $messageStack->add_session('checkout_payment', MODULE_PAYMENT_PAYVECTOR_TEXT_DECLINED . ' ' . $message, 'error');
          zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, 'pvmode=' . ($saved_card_id == 'new' ? 'new' : 'saved'), 'SSL', true, false));
      }
  }

  function updateCrossReference($customer_id, $cross_reference, $card_type, $last_four) {
      global $db;
      $check = $db->Execute("SELECT id FROM payvector_cross_reference WHERE customer_id = '" . (int)$customer_id . "'LIMIT 1");
      if ($check->RecordCount() > 0) {
           $db->Execute("UPDATE payvector_cross_reference SET cross_reference = '" . zen_db_input($cross_reference) . "', card_type = '" . zen_db_input($card_type) . "', last_four = '" . zen_db_input($last_four) . "', date_added = NOW() WHERE id = '" . (int)$check->fields['id'] . "'");
      } else {
           $db->Execute("INSERT INTO payvector_cross_reference (customer_id, cross_reference, card_type, last_four, date_added) VALUES ('" . (int)$customer_id . "', '" . zen_db_input($cross_reference) . "', '" . zen_db_input($card_type) . "', '" . zen_db_input($last_four) . "', NOW())");
      }
  }

  function updateOrderStatus($order_id, $newstatus, $payment_status, $invoice_id) {
      global $db;      
      $_SESSION['cart']->reset(true);
      $comments = 'payment status :' . $payment_status . ' , Invoice Id: ' . $invoice_id;
      $ordupdatar = array(
          array('fieldName' => 'orders_id', 'value' => $order_id, 'type' => 'integer'),
          array('fieldName' => 'orders_status_id', 'value' => $newstatus, 'type' => 'integer'),
          array('fieldName' => 'date_added', 'value' => 'now()', 'type' => 'noquotestring'),
          array('fieldName' => 'comments', 'value' => $comments, 'type' => 'string'),
          array('fieldName' => 'customer_notified', 'value' => 0, 'type' => 'integer')
      );
      
      $db->perform(TABLE_ORDERS_STATUS_HISTORY, $ordupdatar);
      
      $db->Execute("UPDATE " . TABLE_ORDERS . " SET `orders_status` = '" . (int)$newstatus . "' WHERE `orders_id` = '" . (int)$order_id . "'");

  }

  function call_webhooks() {
      global $db, $messageStack, $order, $cart, $currencies;
      
          $this->__construct();
          
          $hash_matches = false;
          $transaction_result = [];
          $validate_error_message = '';          
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
              $messageStack->add_session('checkout_payment', MODULE_PAYMENT_PAYVECTOR_TEXT_DECLINED . ' Hash Verification Failed: ' . $validate_error_message, 'error');
              zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, 'pvmode=new', 'SSL', true, false));
              exit;
          }
          
          $order_id = $transaction_result->getOrderID();            
          $status_code = $transaction_result->getStatusCode();
          $message = $transaction_result->getMessage();
          $cross_reference = $transaction_result->getCrossReference();
          
          
          if ($status_code == 0) {              
              $clean_order_id = current(explode('-', $order_id));
              $new_status = (defined('MODULE_PAYMENT_PAYVECTOR_ORDER_STATUS_ID') && MODULE_PAYMENT_PAYVECTOR_ORDER_STATUS_ID > 0) ? (int)MODULE_PAYMENT_PAYVECTOR_ORDER_STATUS_ID : 2;
                           
              if (defined('MODULE_PAYMENT_PAYVECTOR_SHOW_SAVED_CARDS') && MODULE_PAYMENT_PAYVECTOR_SHOW_SAVED_CARDS == 'True') {
                  $customer_id_query = $db->Execute("SELECT customers_id FROM " . TABLE_ORDERS . " WHERE orders_id = '" . (int)$clean_order_id . "' LIMIT 1");
                  if (!$customer_id_query->EOF && $customer_id_query->fields['customers_id'] > 0) {
                      $hpf_result =  new \HostedPaymentFormFinalTransactionResult($transaction_result);
                      $card_last_four = $hpf_result->getCardLastFour($_SESSION);                      
                      $card_type = $transaction_result->getCardType();                                          
                      $this->updateCrossReference($customer_id_query->fields['customers_id'], $cross_reference, $card_type, $card_last_four);                
                  }
              }
              if (is_numeric($clean_order_id)) {
                  $this->updateOrderStatus($clean_order_id, $new_status, 'Success', $cross_reference);
              }
              
              $unrewritten_success_url = (defined('ENABLE_SSL') && ENABLE_SSL == 'true' ? HTTPS_SERVER . DIR_WS_HTTPS_CATALOG : HTTP_SERVER . DIR_WS_CATALOG) . 'index.php?main_page=' . FILENAME_CHECKOUT_SUCCESS;
              if (zen_session_id() != '') $unrewritten_success_url .= '&zenid=' . zen_session_id();
              zen_redirect($unrewritten_success_url);
          } else {
              $messageStack->add_session('checkout_payment', MODULE_PAYMENT_PAYVECTOR_TEXT_DECLINED . ' ' . $message, 'error');
              zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, 'pvmode=new', 'SSL', true, false));
          }
          exit;
      
      
      $unrewritten_success_url = (defined('ENABLE_SSL') && ENABLE_SSL == 'true' ? HTTPS_SERVER . DIR_WS_HTTPS_CATALOG : HTTP_SERVER . DIR_WS_CATALOG) . 'index.php?main_page=' . FILENAME_CHECKOUT_SUCCESS;
      if (zen_session_id() != '') $unrewritten_success_url .= '&zenid=' . zen_session_id();
      zen_redirect($unrewritten_success_url);
  }

  function getEntryPointList() {
      $paymentProcessorDomain = "payvector.net";
      $rgepl_request_gateway_entry_point_list = new \net\thepaymentgateway\paymentsystem\RequestGatewayEntryPointList();      
      
      $rgepl_request_gateway_entry_point_list->add("https://gw1." . $paymentProcessorDomain, 100, 2);
      $rgepl_request_gateway_entry_point_list->add("https://gw2." . $paymentProcessorDomain, 200, 2);
      $rgepl_request_gateway_entry_point_list->add("https://gw3." . $paymentProcessorDomain, 300, 2);                

      return $rgepl_request_gateway_entry_point_list;
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
      
      return array($isoCurrencyCode, $amount);
  }

  function check() {
    global $db;
    if (!isset($this->_check)) {
      $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_PAYVECTOR_STATUS'");
      $this->_check = $check_query->RecordCount();
    }
    return $this->_check;
  }

  function install() {
    global $db;
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable PayVector Module', 'MODULE_PAYMENT_PAYVECTOR_STATUS', 'True', 'Do you want to accept PayVector payments?', '6', '1', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Payment Mode', 'MODULE_PAYMENT_PAYVECTOR_MODE', 'Hosted Payment Form', 'Select the payment mode', '6', '2', 'zen_cfg_select_option(array(\'Direct API\', \'Hosted Payment Form\'), ', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Merchant ID', 'MODULE_PAYMENT_PAYVECTOR_MERCHANT_ID', '', 'Your PayVector Merchant ID', '6', '3', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Password', 'MODULE_PAYMENT_PAYVECTOR_PASSWORD', '', 'Your PayVector Password', '6', '4', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Pre-Shared Key', 'MODULE_PAYMENT_PAYVECTOR_SECRET_KEY', '', 'Your PayVector Pre-Shared Key (Hosted Payment Form only)', '6', '5', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Hash Method', 'MODULE_PAYMENT_PAYVECTOR_HASH_METHOD', 'SHA1', 'Hash Method (Hosted Payment Form only)', '6', '6', 'zen_cfg_select_option(array(\'SHA1\', \'MD5\', \'HMACSHA1\', \'HMACMD5\'), ', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Result Delivery Method', 'MODULE_PAYMENT_PAYVECTOR_HPF_RDM', 'POST', 'Result Delivery Method (Hosted Payment Form only)', '6', '7', 'zen_cfg_select_option(array(\'POST\', \'SERVER\', \'SERVER_PULL\'), ', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Saved Cards', 'MODULE_PAYMENT_PAYVECTOR_SHOW_SAVED_CARDS', 'True', 'Do you want to show saved cards?', '6', '8', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order of Display', 'MODULE_PAYMENT_PAYVECTOR_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_PAYVECTOR_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_PAYVECTOR_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");

    
    // Create Cross-reference table
    $db->Execute("CREATE TABLE IF NOT EXISTS payvector_cross_reference (
      id INT AUTO_INCREMENT PRIMARY KEY,
      customer_id INT NOT NULL,
      cross_reference VARCHAR(255) NOT NULL,
      card_type VARCHAR(50),
      last_four VARCHAR(4),      
      date_added DATETIME
    )");
  }

  function remove() {
    global $db;
    $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
  }

  function keys() {
    return array('MODULE_PAYMENT_PAYVECTOR_STATUS', 'MODULE_PAYMENT_PAYVECTOR_MODE', 'MODULE_PAYMENT_PAYVECTOR_MERCHANT_ID', 'MODULE_PAYMENT_PAYVECTOR_PASSWORD', 'MODULE_PAYMENT_PAYVECTOR_SECRET_KEY', 'MODULE_PAYMENT_PAYVECTOR_HASH_METHOD', 'MODULE_PAYMENT_PAYVECTOR_HPF_RDM', 'MODULE_PAYMENT_PAYVECTOR_SHOW_SAVED_CARDS', 'MODULE_PAYMENT_PAYVECTOR_ZONE', 'MODULE_PAYMENT_PAYVECTOR_ORDER_STATUS_ID', 'MODULE_PAYMENT_PAYVECTOR_SORT_ORDER');
  }
}
