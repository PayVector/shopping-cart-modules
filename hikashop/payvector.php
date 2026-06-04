<?php
/**
 * @package    HikaShop for Joomla!
 * @version    6.4
 * @author     Your Name
 * @copyright  (C) 2026. All rights reserved.
 * @license    GNU/GPLv2
 */

defined('_JEXEC') or die('Restricted access');

class plgHikashoppaymentPayvector extends hikashopPaymentPlugin {

	var $name = 'payvector';
	var $multiple = true;
	var $pluginConfig = array(
		'mode' => array('Payment Mode', 'list', array(
			'Hosted Payment Form' => 'Hosted Payment Form',
			'Direct API' => 'Direct API'
		)),
		'merchant_id' => array('Merchant ID', 'input'),
		'password' => array('Password', 'input'),
		'pre_shared_key' => array('Pre-Shared Key', 'input'),
		'hash_method' => array('Hash Method', 'list', array(
			'SHA1' => 'SHA1',
			'MD5' => 'MD5',
			'HMACSHA1' => 'HMACSHA1',
			'HMACMD5' => 'HMACMD5'
		)),
		'result_delivery_method' => array('Result Delivery Method', 'list', array(
			'POST' => 'POST',			
			'SERVER_PULL' => 'SERVER_PULL'
		)),
		'show_saved_cards' => array('Show Saved Cards', 'boolean'),
		'order_status' => array('Pending Status', 'orderstatus'),
		'verified_status' => array('Verified Status', 'orderstatus'),
		'invalid_status' => array('Invalid Status', 'orderstatus')
	);

	

	function getPaymentDefaultValues(&$element) {
		$element->payment_name='PayVector';
		$element->payment_description='You can pay by credit card using this payment method';
		$element->payment_images='';

		$element->payment_params->mode='Hosted Payment Form';
		$element->payment_params->merchant_id='';
		$element->payment_params->password='';
		$element->payment_params->pre_shared_key='';
		$element->payment_params->hash_method='SHA1';
		$element->payment_params->result_delivery_method='POST';
		$element->payment_params->show_saved_cards='1';
		$element->payment_params->order_status='created';
		$element->payment_params->verified_status='confirmed';
		$element->payment_params->invalid_status='cancelled';
	}
	
	protected function checkDB() {
		$db = JFactory::getDbo();
		$query = "CREATE TABLE IF NOT EXISTS `#__payvector_cross_reference` (
		  `id` INT AUTO_INCREMENT PRIMARY KEY,
		  `customer_id` INT NOT NULL,
		  `cross_reference` VARCHAR(255) NOT NULL,
		  `card_type` VARCHAR(50),
		  `last_four` VARCHAR(4),      
		  `date_added` DATETIME
		)";
		$db->setQuery($query);
		$db->execute();
	}

	protected function getSavedCards() {
		$user = JFactory::getUser();
		if ($user->id == 0) return array();
		
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from('#__payvector_cross_reference')
			->where('customer_id = ' . (int)$user->id)
			->order('id DESC');
		$db->setQuery($query);
		return $db->loadAssocList();
	}

	protected function updateCrossReference($customer_id, $cross_reference, $card_type, $last_four) {
		if ((int)$customer_id == 0) return;
		if (trim($card_type) === '') return;
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('id')
			->from('#__payvector_cross_reference')
			->where('customer_id = ' . (int)$customer_id);
		$db->setQuery($query);
		$existing = $db->loadResult();
		
		if ($existing) {
			$query = $db->getQuery(true)
				->update('#__payvector_cross_reference')
				->set('cross_reference = ' . $db->quote($cross_reference))
				->set('card_type = ' . $db->quote($card_type))
				->set('last_four = ' . $db->quote($last_four))
				->set('date_added = NOW()')
				->where('id = ' . (int)$existing);
			$db->setQuery($query);
			$db->execute();
		} else {
			$query = $db->getQuery(true)
				->insert('#__payvector_cross_reference')
				->columns('customer_id, cross_reference, card_type, last_four, date_added')
				->values((int)$customer_id . ', ' . $db->quote($cross_reference) . ', ' . $db->quote($card_type) . ', ' . $db->quote($last_four) . ', NOW()');
			$db->setQuery($query);
			$db->execute();
		}
	}

	protected function setAmountFromCurrency($currency_code, $amount) {
		$amount = number_format($amount, 2, '.', '');
		require_once(dirname(__FILE__) . '/payvector/ISOHelper.php');
		$iso_currency_list = ISOHelper::getISOCurrencyList();
		$isoCurrencyCode = '';
		
		if (!empty($currency_code) && $iso_currency_list->getISOCurrency($currency_code, $iso_currency)) {
			$isoCurrencyCode = $iso_currency->getISOCode();
			$amount = (string)$amount;
			$amount = round($amount * ("1" . str_repeat(0, $iso_currency->getExponent())));
		}
		
		return array($isoCurrencyCode, $amount);
	}

	protected function getISOCountryCode($country_code) {
		require_once(dirname(__FILE__) . '/payvector/ISOHelper.php');
		$iso_country_list = ISOHelper::getISOCountryList();
		if (!empty($country_code) && $iso_country_list->getISOCountry($country_code, $iso_country)) {
			return $iso_country->getISOCode();
		}
		return '';
	}

	protected function getEntryPointList() {
		require_once(dirname(__FILE__) . '/payvector/TransactionProcessor.php');
		$paymentProcessorDomain = "payvector.net";
		$rgepl_request_gateway_entry_point_list = new \net\thepaymentgateway\paymentsystem\RequestGatewayEntryPointList();
		$rgepl_request_gateway_entry_point_list->add("https://gw1." . $paymentProcessorDomain, 100, 2);
		$rgepl_request_gateway_entry_point_list->add("https://gw2." . $paymentProcessorDomain, 200, 2);
		$rgepl_request_gateway_entry_point_list->add("https://gw3." . $paymentProcessorDomain, 300, 2);
		return $rgepl_request_gateway_entry_point_list;
	}

	public function onPaymentDisplay(&$order, &$methods, &$usable_methods) {
		$app = JFactory::getApplication();
		$payvector_error = $app->input->getString('payvector_error', '');
		if (!empty($payvector_error)) {
			$app->enqueueMessage('Payment processing failed: ' . urldecode($payvector_error), 'error');
			$app->input->set('payvector_error', '');
		}

		parent::onPaymentDisplay($order, $methods, $usable_methods);
		
		$this->checkDB();
		
		
		$method = null;
		foreach($usable_methods as $m) {
			if($m->payment_type == $this->name) {
				$method = $m;
				break;
			}
		}
		if(empty($method)) {
			foreach($methods as $m) {
				if($m->payment_type == $this->name) {
					$method = $m;
					break;
				}
			}
		}
		if(empty($method)) {
			return true;
		}
		
		if (empty($method->payment_params->mode)) {
			$method->payment_params->mode = 'Hosted Payment Form';
		}

		$show_saved_cards = true;
		if (isset($method->payment_params->show_saved_cards) && ($method->payment_params->show_saved_cards === '0' || $method->payment_params->show_saved_cards === 0 || $method->payment_params->show_saved_cards === 'False')) {
			$show_saved_cards = false;
		}
		
		$saved_cards = $show_saved_cards ? $this->getSavedCards() : array();
		
		
		$needs_custom_html = false;
		if ($method->payment_params->mode == 'Direct API') {
			$needs_custom_html = true;
		} elseif ($method->payment_params->mode == 'Hosted Payment Form' && count($saved_cards) > 0) {
			$needs_custom_html = true;
		}
		
		if ($needs_custom_html) {
			$app = JFactory::getApplication();
			$checkout_custom = $app->getUserState('com_hikashop.checkout_custom', null);
			if(is_string($checkout_custom)) {
				$checkout_custom = json_decode(base64_decode($checkout_custom), true);
			}
			$custom_data = array();
			if (!empty($checkout_custom) && isset($checkout_custom[$method->payment_id])) {
				$custom_data = $checkout_custom[$method->payment_id];
			}

			$selected_card = 'new';
			if (count($saved_cards) > 0) {
				$selected_card = (string)$saved_cards[0]['id'];
			}
			
			$session_card = '';
			if (isset($custom_data['payvector_saved_card'])) {
				$session_card = $custom_data['payvector_saved_card'];
			} elseif (isset($_SESSION['payvector_saved_card'])) {
				$session_card = $_SESSION['payvector_saved_card'];
			} elseif (isset($_SESSION['payvector_cc_data']['saved_card'])) {
				$session_card = $_SESSION['payvector_cc_data']['saved_card'];
			}

			$has_error = false;
			$messages = $app->getMessageQueue();
			if (!empty($messages)) {
				foreach ($messages as $msg) {
					if (is_array($msg) && isset($msg['type']) && $msg['type'] == 'error') {
						$has_error = true;
						break;
					} elseif (is_object($msg) && isset($msg->type) && $msg->type == 'error') {
						$has_error = true;
						break;
					}
				}
			}

			if ($session_card != '' && ($session_card != 'new' || $has_error)) {
				$selected_card = (string)$session_card;
			}

			$html = '<div class="payvector_direct_api" style="margin-top:10px;">';
			
			if (count($saved_cards) > 0) {
				foreach ($saved_cards as $card) {
					$is_checked = ($selected_card === (string)$card['id']) ? 'checked="checked"' : '';
					$html .= '<div style="margin-bottom: 5px;"><input type="radio" name="checkout[payment][custom][' . $method->payment_id . '][payvector_saved_card]" value="'.$card['id'].'" class="payvector-card-selector" '.$is_checked.' /> ';
					$html .= 'Use saved card ending in '.$card['last_four'].' ('.$card['card_type'].')';
					
					$cvv_display = ($selected_card === (string)$card['id']) ? '' : 'display:none;';
					$html .= '<div id="payvector-cvv-'.$card['id'].'" class="payvector-saved-cvv" style="'.$cvv_display.' margin-left: 20px; margin-top: 5px;">';
					$html .= 'CVV: <input type="text" name="checkout[payment][custom][' . $method->payment_id . '][payvector_saved_cc_cvv_'.$card['id'].']" value="" size="4" maxlength="4" style="width:60px;" /></div></div>';
				}
				$is_new_checked = ($selected_card === 'new') ? 'checked="checked"' : '';
				$html .= '<div style="margin-bottom: 5px;"><input type="radio" name="checkout[payment][custom][' . $method->payment_id . '][payvector_saved_card]" value="new" class="payvector-card-selector" '.$is_new_checked.' /> Use new card</div>';
			} else {
				$html .= '<input type="hidden" name="checkout[payment][custom][' . $method->payment_id . '][payvector_saved_card]" value="new" />';
			}

			if ($method->payment_params->mode == 'Direct API') {
				$new_card_style = ($selected_card != 'new') ? 'display:none;' : '';
				$html .= '<div class="payvector-new-card" style="'.$new_card_style.'">';
				$html .= '<p><strong>Credit Card Information</strong></p>';
				
				$cc_owner_val = isset($custom_data['payvector_cc_owner']) ? htmlspecialchars($custom_data['payvector_cc_owner']) : '';
				$html .= '<div style="margin-bottom: 5px;"><label style="display:inline-block; width:120px;">Card Owner:</label>';
				$html .= '<input type="text" name="checkout[payment][custom][' . $method->payment_id . '][payvector_cc_owner]" value="' . $cc_owner_val . '" /></div>';
				
				$html .= '<div style="margin-bottom: 5px;"><label style="display:inline-block; width:120px;">Card Number:</label>';
				$html .= '<input type="text" name="checkout[payment][custom][' . $method->payment_id . '][payvector_cc_number]" value="" /></div>';
				
				$html .= '<div style="margin-bottom: 5px;"><label style="display:inline-block; width:120px;">Expiration Date:</label>';
				$html .= '<select name="checkout[payment][custom][' . $method->payment_id . '][payvector_cc_expires_month]" style="width: auto; display: inline-block;">';
				for ($i = 1; $i <= 12; $i++) {
					$val = sprintf('%02d', $i);
					$selected = (isset($custom_data['payvector_cc_expires_month']) && $custom_data['payvector_cc_expires_month'] == $val) ? 'selected="selected"' : '';
					$html .= '<option value="'.$val.'" '.$selected.'>'.$val.'</option>';
				}
				$html .= '</select> / <select name="checkout[payment][custom][' . $method->payment_id . '][payvector_cc_expires_year]" style="width: auto; display: inline-block;">';
				$currentYear = (int)date('Y');
				for ($i = $currentYear; $i <= $currentYear + 10; $i++) {
					$val = substr((string)$i, -2);
					$selected = (isset($custom_data['payvector_cc_expires_year']) && $custom_data['payvector_cc_expires_year'] == $val) ? 'selected="selected"' : '';
					$html .= '<option value="'.$val.'" '.$selected.'>'.$i.'</option>';
				}
				$html .= '</select></div>';
				
				$html .= '<div style="margin-bottom: 5px;"><label style="display:inline-block; width:120px;">CVV:</label>';
				$html .= '<input type="text" name="checkout[payment][custom][' . $method->payment_id . '][payvector_cc_cvv]" value="" size="4" maxlength="4" style="width:60px;" /></div>';
				$html .= '</div>'; 
			}
			
			$html .= '<script type="text/javascript">
				(function() {
					function initPayVector() {
						var selectors = document.querySelectorAll(".payvector-card-selector");
						function togglePayVectorFields() {
							var selected = document.querySelector(\'input[name*="payvector_saved_card"]:checked\');
							if (!selected) return;
							var isNew = (selected.value === "new");
							var newCardFields = document.querySelectorAll(".payvector-new-card");
							var savedCvvFields = document.querySelectorAll(".payvector-saved-cvv");
							newCardFields.forEach(function(el) { el.style.display = isNew ? "block" : "none"; });
							savedCvvFields.forEach(function(el) { el.style.display = "none"; });
							if (!isNew) {
								var activeCvv = document.getElementById("payvector-cvv-" + selected.value);
								if (activeCvv) activeCvv.style.display = "block";
							}
						}
						selectors.forEach(function(radio) { 
							radio.removeEventListener("change", togglePayVectorFields);
							radio.addEventListener("change", togglePayVectorFields); 
						});
						if(selectors.length > 0) togglePayVectorFields();
					}
					if (document.readyState === "loading") {
						document.addEventListener("DOMContentLoaded", initPayVector);
					} else {
						initPayVector();
					}
				})();
			</script>';
			
			$html .= '
			<input type="hidden" name="checkout[payment][custom][' . $method->payment_id . '][payvector_browser_java_enabled]" id="payvector_browser_java_enabled" value="">
			<input type="hidden" name="checkout[payment][custom][' . $method->payment_id . '][payvector_browser_language]" id="payvector_browser_language" value="">
			<input type="hidden" name="checkout[payment][custom][' . $method->payment_id . '][payvector_browser_color_depth]" id="payvector_browser_color_depth" value="">
			<input type="hidden" name="checkout[payment][custom][' . $method->payment_id . '][payvector_browser_screen_height]" id="payvector_browser_screen_height" value="">
			<input type="hidden" name="checkout[payment][custom][' . $method->payment_id . '][payvector_browser_screen_width]" id="payvector_browser_screen_width" value="">
			<input type="hidden" name="checkout[payment][custom][' . $method->payment_id . '][payvector_browser_tz]" id="payvector_browser_tz" value="">
			<input type="hidden" name="checkout[payment][custom][' . $method->payment_id . '][payvector_browser_user_agent]" id="payvector_browser_user_agent" value="">
			<script type="text/javascript">
				(function() {
					function initPayVectorBrowser() {
						if (document.getElementById("payvector_browser_java_enabled")) document.getElementById("payvector_browser_java_enabled").value = navigator.javaEnabled();
						if (document.getElementById("payvector_browser_language")) document.getElementById("payvector_browser_language").value = navigator.language || navigator.userLanguage;
						if (document.getElementById("payvector_browser_color_depth")) document.getElementById("payvector_browser_color_depth").value = screen.colorDepth;
						if (document.getElementById("payvector_browser_screen_height")) document.getElementById("payvector_browser_screen_height").value = screen.height;
						if (document.getElementById("payvector_browser_screen_width")) document.getElementById("payvector_browser_screen_width").value = screen.width;
						if (document.getElementById("payvector_browser_tz")) document.getElementById("payvector_browser_tz").value = new Date().getTimezoneOffset();
						if (document.getElementById("payvector_browser_user_agent")) document.getElementById("payvector_browser_user_agent").value = navigator.userAgent;
					}
					if (document.readyState === "loading") {
						document.addEventListener("DOMContentLoaded", initPayVectorBrowser);
					} else {
						initPayVectorBrowser();
					}
				})();
			</script>';

			$html .= '</div>';
			
			
			foreach($methods as $m) {
				if($m->payment_type == $this->name) {
					$m->custom_html = $html;
					$m->custom_html_no_btn = true;
					$m->custom_html_ignore_cache = true;
				}
			}
			foreach($usable_methods as $m) {
				if($m->payment_type == $this->name) {
					$m->custom_html = $html;
					$m->custom_html_no_btn = true;
					$m->custom_html_ignore_cache = true;
				}
			}
		}

		return true;
	}

	public function onAfterOrderConfirm(&$order, &$methods, $method_id) {		
		if (!isset($this->url_itemid)) $this->url_itemid = '';
		
		parent::onAfterOrderConfirm($order, $methods, $method_id);
		
		$method = null;
		foreach($methods as $m) {
			if($m->payment_type == $this->name && $m->payment_id == $method_id) {
				$method = $m;
				break;
			}
		}

		if(empty($method)) {
			return;
		}

		require_once(dirname(__FILE__) . '/payvector/TransactionProcessor.php');

		$mid = @$method->payment_params->merchant_id;
		$pass = @$method->payment_params->password;
		$mode = @$method->payment_params->mode;
		if(empty($mode)) $mode = 'Hosted Payment Form';
		
		$hpf_psk = @$method->payment_params->pre_shared_key;
		$hpf_hash = @$method->payment_params->hash_method;
		if(empty($hpf_hash)) $hpf_hash = 'SHA1';
		$hpf_rdm = @$method->payment_params->result_delivery_method;
		if(empty($hpf_rdm)) $hpf_rdm = 'POST';
		

		
		$tp = new \TransactionProcessor();
		$tp->setMerchantID($mid);
		$tp->setMerchantPassword($pass);
		$tp->setRgeplRequestGatewayEntryPointList($this->getEntryPointList());
		

		if (isset($this->currency) && !empty($this->currency->currency_code)) {
			$currency_code = $this->currency->currency_code;
		} else {
			$currencyClass = hikashop_get('class.currency');
			$currency_obj = $currencyClass->get($order->order_currency_id);
			$currency_code = @$currency_obj->currency_code;
		}
		
		
		list($iso_currency_code, $amount) = $this->setAmountFromCurrency($currency_code, $order->cart->full_total->prices[0]->price_value_with_tax);
		
		$tp->setCurrencyCode($iso_currency_code);
		$tp->setAmount($amount);
		
		$tp->setOrderID($order->order_id . '-' . time());
		$tp->setOrderDescription('Order from ' . HIKASHOP_LIVE);

		$billing = $order->cart->billing_address;
		if (!empty($billing)) {
			$tp->setCustomerName((string)@$billing->address_firstname . ' ' . (string)@$billing->address_lastname);
			$tp->setAddress1((string)@$billing->address_street);
			$tp->setCity((string)@$billing->address_city);
			
			$state_val = '';
			if (isset($billing->address_state) && is_object($billing->address_state)) {
				$state_val = @$billing->address_state->zone_name;
			} else {
				$state_val = @$billing->address_state;
			}
			$tp->setState((string)$state_val);
			$tp->setPostcode((string)@$billing->address_post_code);
			
			if (!empty($billing->address_country_code_2)) {
				$tp->setCountryCode($this->getISOCountryCode($billing->address_country_code_2));
			}
			if (!empty($billing->address_telephone)) {
				$tp->setPhoneNumber((string)$billing->address_telephone);
			}
		}
		

		$app = JFactory::getApplication();
		$user = JFactory::getUser();

		
		$checkout_custom = $app->getUserState('com_hikashop.checkout_custom', null);
		if(is_string($checkout_custom)) {
			$checkout_custom = json_decode(base64_decode($checkout_custom), true);
		}
		$custom_data = array();
		if (!empty($checkout_custom) && isset($checkout_custom[$method_id])) {
			$custom_data = $checkout_custom[$method_id];
		}

		$saved_card_id = isset($custom_data['payvector_saved_card']) ? $custom_data['payvector_saved_card'] : 'new';
		
		$show_saved_cards = true;
		if (isset($method->payment_params->show_saved_cards) && ($method->payment_params->show_saved_cards === '0' || $method->payment_params->show_saved_cards === 0 || $method->payment_params->show_saved_cards === 'False')) {
			$show_saved_cards = false;
		}

		if ($mode == 'Hosted Payment Form' && $saved_card_id == 'new') {
			$notify_url = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=checkout&task=notify&notif_payment=' . $this->name . '&tmpl=component&lang=' . $this->locale . $this->url_itemid;
			$return_url = $notify_url;
			
			$hpf_url = 'https://mms.payvector.net/Pages/PublicPages/PaymentForm.aspx';
			
			if (!isset($_SESSION['payvector_transaction_is_cross_reference'])) $_SESSION['payvector_transaction_is_cross_reference'] = false;
			if (!isset($_SESSION['payvector_transaction_card_first_six'])) $_SESSION['payvector_transaction_card_first_six'] = '';
			if (!isset($_SESSION['payvector_transaction_card_last_four'])) $_SESSION['payvector_transaction_card_last_four'] = '';
			if (!isset($_SESSION['payvector_transaction_order_id'])) $_SESSION['payvector_transaction_order_id'] = '';
			$sessionHandler = $_SESSION;
			
			$formFields = $tp->getHostedPaymentForm(
				$return_url,
				'',
				$hpf_psk,
				$hpf_hash,
				$hpf_rdm,
				false,
				$sessionHandler
			);
			
			$this->payment_params->formFields = $formFields;
			$this->payment_params->url = $hpf_url;			
			
			return $this->showPage('end');
		} else {
			
			
			if (!empty($order->customer->user_email)) {
				$email_val = is_array($order->customer->user_email) ? reset($order->customer->user_email) : $order->customer->user_email;
				$tp->setEmailAddress((string)$email_val);
			}
			$tp->setIPAddress($_SERVER['REMOTE_ADDR']);
			
			$cc_owner = isset($custom_data['payvector_cc_owner']) ? $custom_data['payvector_cc_owner'] : '';
			$cc_number = isset($custom_data['payvector_cc_number']) ? $custom_data['payvector_cc_number'] : '';
			$cc_expires_month = isset($custom_data['payvector_cc_expires_month']) ? $custom_data['payvector_cc_expires_month'] : '';
			$cc_expires_year = isset($custom_data['payvector_cc_expires_year']) ? $custom_data['payvector_cc_expires_year'] : '';
			$cc_cvv = isset($custom_data['payvector_cc_cvv']) ? $custom_data['payvector_cc_cvv'] : '';

			if ($cc_owner != '') {
				$tp->setCustomerName($cc_owner);
			}
			
			$tp->setJavaEnabled(isset($custom_data['payvector_browser_java_enabled']) ? $custom_data['payvector_browser_java_enabled'] : 'false');
			$tp->setJavaScriptEnabled('true');
			$tp->setScreenWidth(isset($custom_data['payvector_browser_screen_width']) ? $custom_data['payvector_browser_screen_width'] : '');
			$tp->setScreenHeight(isset($custom_data['payvector_browser_screen_height']) ? $custom_data['payvector_browser_screen_height'] : '');
			$tp->setScreenColourDepth(isset($custom_data['payvector_browser_color_depth']) ? $custom_data['payvector_browser_color_depth'] : '');
			$tp->setTimezoneOffset(isset($custom_data['payvector_browser_tz']) ? $custom_data['payvector_browser_tz'] : '');
			$tp->setLanguage(isset($custom_data['payvector_browser_language']) ? $custom_data['payvector_browser_language'] : '');
			
			$termUrl = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=checkout&task=notify&notif_payment=' . $this->name . '&tmpl=component&lang=' . $this->locale . $this->url_itemid . '&is3ds=1&order_id=' . $order->order_id;
			$tp->setChallengeNotificationURL($termUrl);
			$tp->setFingerprintNotificationURL($termUrl);
			
			$sessionHandler = $_SESSION;
			if (!isset($sessionHandler['payvector_transaction_is_cross_reference'])) $sessionHandler['payvector_transaction_is_cross_reference'] = false;
			if (!isset($sessionHandler['payvector_transaction_card_first_six'])) $sessionHandler['payvector_transaction_card_first_six'] = '';
			if (!isset($sessionHandler['payvector_transaction_card_last_four'])) $sessionHandler['payvector_transaction_card_last_four'] = '';
			if (!isset($sessionHandler['payvector_transaction_order_id'])) $sessionHandler['payvector_transaction_order_id'] = '';

			try {
				if ($saved_card_id != 'new' && (int)$saved_card_id > 0) {
					
					$db = JFactory::getDbo();
					$query = $db->getQuery(true)
						->select('cross_reference')
						->from('#__payvector_cross_reference')
						->where('id = ' . (int)$saved_card_id)
						->where('customer_id = ' . (int)$user->id);
					$db->setQuery($query);
					$cross_reference = $db->loadResult();
					
					if (!$cross_reference) {
						$app->enqueueMessage('Invalid Saved Card.', 'error');
						$app->redirect(HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=checkout' . $this->url_itemid);
						exit;
					}
					
					$saved_cc_cvv = isset($custom_data['payvector_saved_cc_cvv_' . $saved_card_id]) ? $custom_data['payvector_saved_cc_cvv_' . $saved_card_id] : '';
					$tp->setCV2($saved_cc_cvv);
					
					$result = $tp->doCrossReferenceTransaction(
						$cross_reference,
						false,
						$sessionHandler
					);
				} else {
					
					$tp->setCV2($cc_cvv);
					
					if (!empty($cc_number)) {
						$session = JFactory::getSession();
						$session->set('payvector_saved_last4', substr(str_replace(' ', '', $cc_number), -4));
					}
					
					$result = $tp->doCardDetailsTransaction(
						$cc_number,
						$cc_expires_month,
						$cc_expires_year,
						'',
						$sessionHandler
					);
				}

				if ($result->transactionProcessed() && $result->transactionSuccessful()) {
					if ($saved_card_id == 'new' && $show_saved_cards && $user->id > 0) {
						$session = JFactory::getSession();
						$xref = $result->getCrossReference();
						$last4 = $session->get('payvector_saved_last4', '');
						$card_type = $result->getCardType();
						if (empty($card_type)) {
							$card_type = 'Card';
						}
						$this->updateCrossReference($user->id, $xref, $card_type, $last4);
						$session->clear('payvector_saved_last4');
					}
					
					$order_status = $method->payment_params->verified_status;
					$history = new stdClass();
					$history->notified = 1;
					$history->amount = isset($order->cart->full_total->prices[0]->price_value_with_tax) ? $order->cart->full_total->prices[0]->price_value_with_tax : $order->order_full_price;
					$history->data = 'PayVector Payment Successful. Trans ID: ' . $result->getCrossReference();									
					
					$email = new stdClass();
					$url = HIKASHOP_LIVE.'administrator/index.php?option=com_hikashop&ctrl=order&task=edit&order_id='.$order->order_id;
					$order_text = "\r\n".JText::sprintf('NOTIFICATION_OF_ORDER_ON_WEBSITE',$order->order_number,HIKASHOP_LIVE);
					$order_text .= "\r\n".str_replace('<br/>',"\r\n",JText::sprintf('ACCESS_ORDER_WITH_LINK',$url));
					
					$email->subject = JText::sprintf('PAYMENT_NOTIFICATION_FOR_ORDER','PayVector','SUCCESS',$order->order_number);
					$email->body = str_replace('<br/>',"\r\n",JText::sprintf('PAYMENT_NOTIFICATION_STATUS','PayVector','SUCCESS')).' '.JText::sprintf('ORDER_STATUS_CHANGED',$order_status)."\r\n\r\n".$order_text;

					$order_id = $order->order_id;
					$this->modifyOrder($order_id, $order_status, $history, $email);
					
					
					$cartClass = hikashop_get('class.cart');
					$cartClass->cleanCartFromSession();
					$app->setUserState(HIKASHOP_COMPONENT.'.order_id', $order->order_id);
					if ($user->guest) {
						$app->setUserState(HIKASHOP_COMPONENT.'.user_id', 0);
					}
					
					$app->enqueueMessage('Payment successful');
					$return_url = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=checkout&task=after_end&order_id=' . $order->order_id . $this->url_itemid;
					$app->redirect($return_url);
					exit;
				} elseif ($result->getStatusCode() == 3) {
					
					$session = JFactory::getSession();
					$session->set('payvector_cross_reference', $result->getCrossReference());
					
					$threeDSecureOutput = $result->getThreeDSecureOutputData();
					$hasV2 = ($threeDSecureOutput && method_exists($threeDSecureOutput, 'getMethodURL') && $threeDSecureOutput->getMethodURL() != '');
					
					$this->payment_params->hasV2 = $hasV2;
					if ($hasV2) {
						$this->payment_params->methodUrl = $threeDSecureOutput->getMethodURL();
						$this->payment_params->ThreeDSMethodData = $threeDSecureOutput->getMethodData();
					}
					
					
					if ($threeDSecureOutput && method_exists($threeDSecureOutput, 'getCREQ') && $threeDSecureOutput->getCREQ() != '') {
						$this->payment_params->url = $threeDSecureOutput->getACSURL();
						$this->payment_params->creq = $threeDSecureOutput->getCREQ();
						$this->payment_params->isV2Challenge = true;
					} else {
						$this->payment_params->url = $threeDSecureOutput ? $threeDSecureOutput->getACSURL() : '';
						$this->payment_params->PaReq = $threeDSecureOutput ? $threeDSecureOutput->getPaREQ() : '';
						$this->payment_params->MD = $result->getCrossReference();
						$this->payment_params->TermUrl = $termUrl;
						$this->payment_params->isV2Challenge = false;
					}
					
					return $this->showPage('3ds');
				} else {
					$invalid_status = @$method->payment_params->invalid_status;
					if (empty($invalid_status)) {
						$invalid_status = 'cancelled';
					}
					$history = new stdClass();
					$history->notified = 0;
					$history->amount = isset($order->cart->full_total->prices[0]->price_value_with_tax) ? $order->cart->full_total->prices[0]->price_value_with_tax : $order->order_full_price;
					$history->data = 'PayVector Payment Failed: ' . $result->getMessage();
					$this->modifyOrder($order->order_id, $invalid_status, $history, false);

					$app->enqueueMessage('Payment processing failed: ' . $result->getMessage(), 'error');
					$return_url = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=checkout' . $this->url_itemid;
					$app->redirect($return_url);
					exit;
				}
			} catch (Exception $e) {
				$invalid_status = @$method->payment_params->invalid_status;
				if (empty($invalid_status)) {
					$invalid_status = 'cancelled';
				}
				$history = new stdClass();
				$history->notified = 0;
				$history->amount = isset($order->cart->full_total->prices[0]->price_value_with_tax) ? $order->cart->full_total->prices[0]->price_value_with_tax : $order->order_full_price;
				$history->data = 'PayVector Payment Error: ' . $e->getMessage();
				$this->modifyOrder($order->order_id, $invalid_status, $history, false);

				$app->enqueueMessage('Payment processing error: ' . $e->getMessage(), 'error');
				$return_url = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=checkout' . $this->url_itemid;
				$app->redirect($return_url);
				exit;
			}
		}
	}

	public function onPaymentNotification(&$statuses) {
		if (!isset($this->url_itemid)) $this->url_itemid = '';
		
		$app = JFactory::getApplication();
		
		require_once(dirname(__FILE__) . '/payvector/TransactionProcessor.php');
		require_once(dirname(__FILE__) . '/payvector/PaymentFormHelper.php');

		$dbOrder = hikashop_get('class.order');	

		
		$is_3ds = $app->input->getInt('is3ds', 0);
		if ($is_3ds) {
			$order_id = $app->input->getInt('order_id', 0);
		} else {
			$order_ref = $app->input->getString('OrderID', '');
			if (empty($order_ref) && isset($_REQUEST['OrderID'])) {
				$order_ref = $_REQUEST['OrderID'];
			}
			$order_id_parts = explode('-', $order_ref);
			$order_id = (int)$order_id_parts[0];
		}
		if (empty($order_id)) {
			echo 'Order ID not found in request.';
			exit;
		}

		$order = $dbOrder->loadFullOrder((int)$order_id);
		if (empty($order)) {
			echo 'Order not found.';
			exit;
		}

		$this->loadPaymentParams($order);
		

		if (empty($this->payment_params)) {
			echo 'Payment parameters not found.';
			exit;
		}

		
		$method = $this;

		$mid = @$method->payment_params->merchant_id;
		$pass = @$method->payment_params->password;
		$hpf_psk = @$method->payment_params->pre_shared_key;
		$hpf_hash = @$method->payment_params->hash_method;
		if (empty($hpf_hash)) $hpf_hash = 'SHA1';
		$hpf_rdm = @$method->payment_params->result_delivery_method;
		if (empty($hpf_rdm)) $hpf_rdm = 'POST';

		$mode = @$method->payment_params->mode;
		if (empty($mode)) $mode = 'Hosted Payment Form';
		
		if ($mode == 'Direct API') {
			$is_3ds = $app->input->getInt('is3ds', 0);
			if ($is_3ds) {
				$this->processDirectPaymentNotification($method, $mid, $pass, $dbOrder);
			}
		} elseif ($mode == 'Hosted Payment Form') {			
			$this->processHostedPaymentNotification($method, $mid, $pass, $hpf_psk, $hpf_hash, $hpf_rdm, $order);
		}
	}

	protected function processDirectPaymentNotification($method, $mid, $pass, $dbOrder) {
		$app = JFactory::getApplication();
		$order_id = $app->input->getInt('order_id', 0);
		
		$threeDSMethodData = $app->input->post->getString('threeDSMethodData', '');
		$cres = $app->input->post->getString('cres', '');
		$threeDSSessionData = $app->input->post->getString('threeDSSessionData', '');
		$paRes = $app->input->post->getString('PaRes', '');
		$md = $app->input->post->getString('MD', '');
		
		$session = JFactory::getSession();
		$crossReference = $session->get('payvector_cross_reference', '');
		
		$rgepl = $this->getEntryPointList();
		$sessionHandler = $_SESSION;
		if (!isset($sessionHandler['payvector_transaction_is_cross_reference'])) $sessionHandler['payvector_transaction_is_cross_reference'] = false;
		if (!isset($sessionHandler['payvector_transaction_card_first_six'])) $sessionHandler['payvector_transaction_card_first_six'] = '';
		if (!isset($sessionHandler['payvector_transaction_card_last_four'])) $sessionHandler['payvector_transaction_card_last_four'] = '';
		if (!isset($sessionHandler['payvector_transaction_order_id'])) $sessionHandler['payvector_transaction_order_id'] = '';

		try {
			if (!empty($cres)) {
				
				$finalCrossReference = '';
				if (!empty($threeDSSessionData)) {
					$szBase64 = strtr($threeDSSessionData, '-_', '+/');
					$nPadding = strlen($szBase64) % 4;
					if ($nPadding) {
						$szBase64 .= str_repeat('=', 4 - $nPadding);
					}
					$finalCrossReference = base64_decode($szBase64);
				}
				$finalCrossReference = $finalCrossReference ? $finalCrossReference : $crossReference;
				
				$tdsa = new \net\thepaymentgateway\paymentsystem\ThreeDSecureAuthentication($rgepl);
				$tdsa->getMerchantAuthentication()->setMerchantID($mid);
				$tdsa->getMerchantAuthentication()->setPassword($pass);
				$tdsa->getThreeDSecureInputData()->setCrossReference($finalCrossReference);
				$tdsa->getThreeDSecureInputData()->setCRES($cres);
				
				$authenticationResult = null;
				$outputData = null;
				$boProcessed = $tdsa->processTransaction($authenticationResult, $outputData);
				
				$result = new ThreeDSecureFinalTransactionResult($boProcessed, $tdsa, $authenticationResult, $outputData, $sessionHandler);
				
			} elseif (!empty($threeDSMethodData)) {
				
				$tdse = new \net\thepaymentgateway\paymentsystem\ThreeDSecureEnvironment($rgepl);
				$tdse->getMerchantAuthentication()->setMerchantID($mid);
				$tdse->getMerchantAuthentication()->setPassword($pass);
				$tdse->getThreeDSecureEnvironmentData()->setCrossReference($crossReference);
				$tdse->getThreeDSecureEnvironmentData()->setMethodData($threeDSMethodData);
				
				$authenticationResult = null;
				$outputData = null;
				$boProcessed = $tdse->processTransaction($authenticationResult, $outputData);
				
				if ($authenticationResult->getStatusCode() === 3) {
					
					$creq = $outputData->getThreeDSecureOutputData()->getCREQ();
					$szBase64 = base64_encode($outputData->getCrossReference());
					$sessionData = rtrim(strtr($szBase64, '+/', '-_'), '=');
					$acsUrl = $outputData->getThreeDSecureOutputData()->getACSURL();
					
					
					echo '
					<!DOCTYPE html>
					<html>
					<head>
						<title>3D Secure Challenge</title>
					</head>
					<body>
						<form id="challenge_form" action="' . htmlspecialchars($acsUrl) . '" method="POST" target="_self">
							<input type="hidden" name="creq" value="' . htmlspecialchars($creq) . '" />
							<input type="hidden" name="threeDSSessionData" value="' . htmlspecialchars($sessionData) . '" />
						</form>
						<script>
							
							var iframe = window.parent.document.getElementById("threeDSecureFrame");
							if (iframe) {
								iframe.style.display = "block";
								iframe.style.width = "100%";
								iframe.style.height = "600px";
								iframe.style.border = "none";
							}
							
							var spinner = window.parent.document.getElementById("hikashop_payvector_3ds_spinner");
							if (spinner) spinner.style.display = "none";
							var msg = window.parent.document.getElementById("hikashop_payvector_3ds_message");
							if (msg) msg.style.display = "none";
							
							document.getElementById("challenge_form").submit();
						</script>
					</body>
					</html>
					';
					exit;
				} else {
					$result = new ThreeDSecureFinalTransactionResult($boProcessed, $tdse, $authenticationResult, $outputData, $sessionHandler);
				}
				
			} else {
				
				$paRes = empty($paRes) ? $cres : $paRes;
				$finalMD = empty($md) ? $crossReference : $md;
				
				$tp = new \TransactionProcessor();
				$tp->setMerchantID($mid);
				$tp->setMerchantPassword($pass);
				$tp->setRgeplRequestGatewayEntryPointList($rgepl);
				
				$result = $tp->check3DSecureResult($finalMD, $paRes, $sessionHandler);
			}
			
			
			if ($result->getStatusCode() == 0) {
				$order = $dbOrder->loadFullOrder((int)$order_id);
				$show_saved_cards = true;
				if (isset($method->payment_params->show_saved_cards) && ($method->payment_params->show_saved_cards === '0' || $method->payment_params->show_saved_cards === 0 || $method->payment_params->show_saved_cards === 'False')) {
					$show_saved_cards = false;
				}
				
				$customer_id = 0;
				if (!empty($order->customer->user_cms_id)) {
					$customer_id = (int)$order->customer->user_cms_id;
				}
				
				if ($show_saved_cards && $customer_id > 0) {
					$last4 = $session->get('payvector_saved_last4', '');
					if (!empty($last4)) {
						$card_type = $result->getCardType();
						if (empty($card_type)) {
							$card_type = 'Card';
						}
						$this->updateCrossReference($customer_id, $result->getCrossReference(), $card_type, $last4);
						$session->clear('payvector_saved_last4');
					}
				}
				
				$order_status = @$method->payment_params->verified_status;
				if (empty($order_status)) {
					$order_status = 'confirmed';
				}
				$history = new stdClass();
				$history->notified = 1;
				$history->amount = $order->order_full_price;
				$history->data = 'PayVector Payment Successful. Trans ID: ' . $result->getCrossReference();
				
				$email = new stdClass();
				$url = HIKASHOP_LIVE.'administrator/index.php?option=com_hikashop&ctrl=order&task=edit&order_id='.$order_id;
				$order_text = "\r\n".JText::sprintf('NOTIFICATION_OF_ORDER_ON_WEBSITE',$order->order_number,HIKASHOP_LIVE);
				$order_text .= "\r\n".str_replace('<br/>',"\r\n",JText::sprintf('ACCESS_ORDER_WITH_LINK',$url));
				
				$email->subject = JText::sprintf('PAYMENT_NOTIFICATION_FOR_ORDER','PayVector','SUCCESS',$order->order_number);
				$email->body = str_replace('<br/>',"\r\n",JText::sprintf('PAYMENT_NOTIFICATION_STATUS','PayVector','SUCCESS')).' '.JText::sprintf('ORDER_STATUS_CHANGED',$order_status)."\r\n\r\n".$order_text;
				
				$this->modifyOrder($order_id, $order_status, $history, $email);
				
				$app->enqueueMessage('Payment confirmed via PayVector 3D Secure.');
				
				$successUrl = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=checkout&task=after_end&order_id=' . $order_id . $this->url_itemid;
				JFactory::getSession()->close();
				echo '
				<script>
					if (window.parent && window.parent !== window) {
						window.parent.location.href = "' . $successUrl . '";
					} else {
						window.location.href = "' . $successUrl . '";
					}
				</script>
				';
				exit;
			} else {
				$order = $dbOrder->loadFullOrder((int)$order_id);
				$email = new stdClass();
				$url = HIKASHOP_LIVE.'administrator/index.php?option=com_hikashop&ctrl=order&task=edit&order_id='.$order_id;
				$order_text = "\r\n".JText::sprintf('NOTIFICATION_OF_ORDER_ON_WEBSITE',$order->order_number,HIKASHOP_LIVE);
				$order_text .= "\r\n".str_replace('<br/>',"\r\n",JText::sprintf('ACCESS_ORDER_WITH_LINK',$url));
				
				$email->subject = JText::sprintf('NOTIFICATION_REFUSED_FOR_THE_ORDER','PayVector').' - Payment Failed';
				$email->body = "Hello,\r\n A PayVector 3DS payment was failed. Reason: " . $result->getMessage() . "\r\n\r\n" . $order_text;

				$invalid_status = @$method->payment_params->invalid_status;
				if (empty($invalid_status)) {
					$invalid_status = 'cancelled';
				}
				$this->modifyOrder($order_id, $invalid_status, false, $email);
				
				$app->enqueueMessage('Payment processing failed: ' . $result->getMessage(), 'error');
				
				$failUrl = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=checkout' . $this->url_itemid . '&payvector_error=' . urlencode($result->getMessage());
				JFactory::getSession()->close();
				echo '
				<script>
					if (window.parent && window.parent !== window) {
						window.parent.location.href = "' . $failUrl . '";
					} else {
						window.location.href = "' . $failUrl . '";
					}
				</script>
				';
				exit;
			}
		} catch (Exception $e) {
			$app->enqueueMessage('3D Secure processing error: ' . $e->getMessage(), 'error');
			$failUrl = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=checkout' . $this->url_itemid . '&payvector_error=' . urlencode($e->getMessage());
			JFactory::getSession()->close();
			echo '
			<script>
				if (window.parent && window.parent !== window) {
					window.parent.location.href = "' . $failUrl . '";
				} else {
					window.location.href = "' . $failUrl . '";
				}
			</script>
			';
			exit;
		}
	}

	protected function processHostedPaymentNotification($method, $mid, $pass, $hpf_psk, $hpf_hash, $hpf_rdm, $order) {
		$app = JFactory::getApplication();
		$dbOrder = hikashop_get('class.order');
		
		$hash_matches = false;
		$transaction_result = null;
		$validate_error_message = '';
		
		if ($hpf_rdm == 'POST') {
			$hash_matches = \PaymentFormHelper::validateTransactionResult_POST(
				$mid, $pass, $hpf_psk, $hpf_hash, $_POST, $transaction_result, $validate_error_message
			);
		} elseif ($hpf_rdm == 'SERVER_PULL') {
			$szPaymentFormResultHandlerURL = 'https://mms.payvector.net/Pages/PublicPages/PaymentFormResultHandler.ashx';
			$hash_matches = \PaymentFormHelper::validateTransactionResult_SERVER_PULL(
				$mid, $pass, $hpf_psk, $hpf_hash, $_REQUEST, $szPaymentFormResultHandlerURL, $transaction_result, $validate_error_message
			);
		} else {
			echo 'Invalid delivery method.';
			exit;
		}

		if (!$hash_matches) {
			echo 'Verification Failed: ' . $validate_error_message;
			exit;
		}

		$status_code = $transaction_result->getStatusCode();
		$message = $transaction_result->getMessage();
		$cross_reference = $transaction_result->getCrossReference();

		$clean_order_id = (int)$order->order_id;


		if ($status_code == 0) {
			
			$show_saved_cards = true;
			if (isset($method->payment_params->show_saved_cards) && ($method->payment_params->show_saved_cards === '0' || $method->payment_params->show_saved_cards === 0 || $method->payment_params->show_saved_cards === 'False')) {
				$show_saved_cards = false;
			}
			if ($show_saved_cards) {
				$customer_id = 0;
				if (!empty($order->customer->user_cms_id)) {
					$customer_id = (int)$order->customer->user_cms_id;
				}
				if ($customer_id > 0) {					
					$hpf_result = new \HostedPaymentFormFinalTransactionResult($transaction_result);
					$card_last_four = $hpf_result->getCardLastFour($_SESSION);                      
					$card_type = $transaction_result->getCardType();                                          
					$this->updateCrossReference($customer_id, $cross_reference, $card_type, $card_last_four);
				}
			}

			$order_status = $this->payment_params->verified_status;
						
			$history = new stdClass();
			$history->notified = 1;
			$history->amount = $order->order_full_price;
			$history->data = 'PayVector Payment Successful. Trans ID: ' . $cross_reference;
			
			$email = new stdClass();
			$url = HIKASHOP_LIVE.'administrator/index.php?option=com_hikashop&ctrl=order&task=edit&order_id='.$clean_order_id;
			$order_text = "\r\n".JText::sprintf('NOTIFICATION_OF_ORDER_ON_WEBSITE',$order->order_number,HIKASHOP_LIVE);
			$order_text .= "\r\n".str_replace('<br/>',"\r\n",JText::sprintf('ACCESS_ORDER_WITH_LINK',$url));
			
			$email->subject = JText::sprintf('PAYMENT_NOTIFICATION_FOR_ORDER','PayVector','SUCCESS',$order->order_number);
			$email->body = str_replace('<br/>',"\r\n",JText::sprintf('PAYMENT_NOTIFICATION_STATUS','PayVector','SUCCESS')).' '.JText::sprintf('ORDER_STATUS_CHANGED',$order_status)."\r\n\r\n".$order_text;
			
			$this->modifyOrder($clean_order_id, $order_status, $history, $email);
			
			$return_url = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=checkout&task=after_end&order_id=' . $clean_order_id . $this->url_itemid;
			$app->redirect($return_url);
			exit;
		} else {
			$email = new stdClass();
			$url = HIKASHOP_LIVE.'administrator/index.php?option=com_hikashop&ctrl=order&task=edit&order_id='.$clean_order_id;
			$order_text = "\r\n".JText::sprintf('NOTIFICATION_OF_ORDER_ON_WEBSITE',$order->order_number,HIKASHOP_LIVE);
			$order_text .= "\r\n".str_replace('<br/>',"\r\n",JText::sprintf('ACCESS_ORDER_WITH_LINK',$url));
			
			$email->subject = JText::sprintf('NOTIFICATION_REFUSED_FOR_THE_ORDER','PayVector').' - Payment Failed';
			$email->body = "Hello,\r\n A PayVector payment was failed. Trans ID: " . $cross_reference . " / Message: " . $message . "\r\n\r\n" . $order_text;

			$invalid_status = @$method->payment_params->invalid_status;
			if (empty($invalid_status)) {
				$invalid_status = 'cancelled';
			}
			$this->modifyOrder($clean_order_id, $invalid_status, false, $email);
			
			$return_url = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=checkout' . $this->url_itemid;
			$app->enqueueMessage('Payment processing failed: ' . $message, 'error');
			$app->redirect($return_url);
			exit;
		}
	}
	
}

if (!class_exists('plgHikashoppaymentPayvectorStubDocument')) {
	if (class_exists('JDocument')) {
		class plgHikashoppaymentPayvectorStubDocument extends JDocument {
			public function render($cache = false, $params = array()) {
				return '';
			}
		}
	} else {
		class plgHikashoppaymentPayvectorStubDocument {
			public function getType() {
				return 'html';
			}
			public function __call($name, $arguments) {
				return null;
			}
		}
	}
}

