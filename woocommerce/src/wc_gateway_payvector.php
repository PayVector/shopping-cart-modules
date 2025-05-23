<?php
/**
 * Plugin Name: PayVector WooCommerce Integration
 * Description: Allows taking payments through the PayVector Payment Gateway with a WooCommerce installation.
 * Version: 2.0.10
 * Author: PayVector
 * Author URI: http://www.payvector.co.uk
 * License: GPLv2 or later
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}
use Automattic\WooCommerce\Utilities\OrderUtil;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

// check that woocommerce is an active plugin before initializing payvector payment gateway
if (in_array('woocommerce/woocommerce.php', (array) get_option('active_plugins'))) {
	add_action('plugins_loaded', 'payvector_init', 0);
	add_filter('woocommerce_payment_gateways', 'ir_payvector_add_gateway');
	add_action('woocommerce_blocks_loaded', 'woocommerce_payvector_blocks');
}

add_action('before_woocommerce_init', function() {
	if (class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class))
    {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
	}
});

add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil'))
    {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});


add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'payvector_action_links' );
function payvector_action_links( $links ) {
	$links[] = '<a href="'. esc_url( get_admin_url(null, 'admin.php?page=wc-settings&tab=checkout&section=payvector') ) .'">Settings</a>';	
	return $links;
 }

function woocommerce_payvector_blocks() {	

	if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType'))
    {
        require_once dirname( __FILE__ ) . '/checkout-block.php';

        add_action(
          'woocommerce_blocks_payment_method_type_registration',
          function(PaymentMethodRegistry $payment_method_registry) {
            $payment_method_registry->register(new WC_Payvector_Blocks());
          },
          10
        );
    }
}    

/**
 * Add PayVector to woocommerce methods array for payment gateways
 * @param  array  $methods Array of payment methods available in woocommerce
 * @return array           Updated array of methods now containing the PayVector gateway
 */
function ir_payvector_add_gateway($methods)
{
	$methods[] = 'WC_Gateway_PayVector';

	return $methods;
}

/**
 * Only load the class when woocommerce is found
 * @return void
 */
function payvector_init()
{
	//add javascript for the admin page
	function payvector_enqueue($hook)
	{
		if ($hook !== 'woocommerce_page_wc-settings') {
			return;
		}
		wp_enqueue_script('jquery');
		wp_register_script(
			'payvector_admin',
			plugin_dir_url(__FILE__) . '/js/admin.js',
			array('jquery')
		);
		wp_enqueue_script('payvector_admin');
	}
	add_action('admin_enqueue_scripts', 'payvector_enqueue');

	//require the payvector library files
	require __DIR__ . '/lib/TransactionProcessor.php';

	class WCSessionHandler implements PayVectorSessionHandler
	{
		public function initialiseSession()
		{
		}
		public function setSessionValue($key, $value)
		{
			WC()->session->set($key, $value);
		}
		public function getSessionValue($key)
		{
			return WC()->session->get($key);
		}
		public function unsetSessionValue($key)
		{
			WC()->session->__unset($key);
		}
	}

	/**
	 * PayVector WooCommerce Payment Gateway
	 *
	 * Allows processing of payments using the Direct/API and Hosted Payment Form integration methods
	 *
	 * @class          WC_Gateway_PayVector
	 * @extends        WC_Payment_Gateway
	 * @version        1.0.0
	 * @package        WooCommerce/Classes/Payment
	 * @author         Iridium Corporation
	 */
	class WC_Gateway_PayVector extends WC_Payment_Gateway
	{
		public $id = 'payvector';
		/**
		 * URL the gateway should use to run callback functions
		 * @var string
		 */
		private $notifyURL;
		/**
		 * Array containing error messages relating to incorrectly input card details
		 * @var array
		 */
		private $inputErrors;
		/**
		 * Woocommerce order object relating to this transaction
		 * @var WC_Order
		 */
		private $order;
		/**
		 * Key used to store the entry point list with update_options()
		 * @var string
		 */
		private $gatewayEntryPointListXMLKey = '_gateway_entry_point_list_xml_string';
		/**
		 * Key used to store the cross reference with update_user_meta()
		 * @var string
		 */
		private $crossReferenceKey = '_cross_reference';
		/**
		 * Key used to store the cross type with update_user_meta()
		 * @var string
		 */
		private $cardTypeKey = '_card_type';
		/**
		 * Key used to store the last four digits of the card number with update_user_meta()
		 * @var string
		 */
		private $cardLastFourKey = '_card_last_four';
		/**
		 * Key used to store the cross reference with update_post_meta()
		 * @var string
		 */
		private $subscriptionCrossReferenceKey = "_subscription_cross_reference";
		/**
		 * Key used to store the cross type with update_post_meta()
		 * @var string
		 */
		private $subscriptionCardTypeKey = '_subscription_card_type';
		/**
		 * Key used to store the last four digits of the card number with update_post_meta()
		 * @var string
		 */
		private $subscriptionCardLastFourKey = '_subscription_card_last_four';

		private $sessionHandler;

		public function __construct()
		{			
			$this->paymentProcessorDomain = "payvector.net";
			$this->paymentProcessorHPFDomain = "https://mms.".$this->paymentProcessorDomain."/";
			$this->hostedPaymentFormURL = $this->paymentProcessorHPFDomain."Pages/PublicPages/PaymentForm.aspx";
			$this->hostedPaymentFormHandlerURL = $this->paymentProcessorHPFDomain."Pages/PublicPages/PaymentFormResultHandler.ashx";
			
			$this->gatewayEntryPointListXMLKey = $this->id . $this->gatewayEntryPointListXMLKey;
			$this->crossReferenceKey = $this->id . $this->crossReferenceKey;
			$this->cardTypeKey = $this->id . $this->cardTypeKey;
			$this->cardLastFourKey = $this->id . $this->cardLastFourKey;
			$this->subscriptionCrossReferenceKey = '_' . $this->id . $this->subscriptionCrossReferenceKey;
			$this->subscriptionCardTypeKey = '_' . $this->id . $this->subscriptionCardTypeKey;
			$this->subscriptionCardLastFourKey = '_' . $this->id . $this->subscriptionCardLastFourKey;
			$this->icon = '';
			$this->supports = array(
				'products',
				'subscriptions',
				'refunds',
				'subscription_cancellation',
				'subscription_suspension',
				'subscription_reactivation',
				'subscription_amount_changes',
				'subscription_date_changes',
				'subscription_payment_method_change'
			);
			$this->orderButtonText = __('Proceed to PayVector', 'woocommerce');			
			$this->methodTitle = __('PayVector', 'woocommerce');
			$this->notifyURL = WC()->api_request_url('WC_Gateway_PayVector');
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();
			// Define user set variables
			$this->title = $this->get_option('title');
			$this->description = $this->get_option('description');
			$this->captureMethod = $this->get_option('capture_method');
			$this->preSharedKey = $this->get_option('pre_shared_key');
			$this->hashMethod = $this->get_option('hash_method');
			$this->resultDeliveryMethod = $this->get_option('result_delivery_method');
			$this->transactionType = $this->get_option('transaction_type');
			if ($this->get_option('test_mode') === "yes") {
				$this->mid = $this->get_option('test_mid');
				$this->password = $this->get_option('test_password');
			} else {
				$this->mid = $this->get_option('live_mid');
				$this->password = $this->get_option('live_password');
			}
			$this->has_fields = true;
			$this->hash_block = false;
			// Actions
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
			add_action('woocommerce_api_wc_gateway_payvector', array($this, 'callback'));
			if (!has_action('scheduled_subscription_payment_payvector')) {
				add_action('scheduled_subscription_payment_payvector', array($this, 'scheduledSubscriptionPayment'), 10, 3);
			}
			add_action('woocommerce_subscriptions_changed_failing_payment_method_wc_gateway_payvector', array($this, 'subscriptionMethodChanged'), 10, 2);
			$this->enabled = $this->get_option("enabled") === "yes" && $this->is_valid_for_use();
			
			$this->sessionHandler = new WCSessionHandler();
		}

		/**
		 * Overrides super method to avoid using strings just to return a boolean anyway
		 *
		 * @return bool
		 */
		public function is_available()
		{
			return $this->enabled;
		}

		public function can_refund_order( $order ) {		
			return $order && $order->get_transaction_id();
		}

		public function process_refund( $order_id, $amount = null, $reason = '' ) {
			$order = wc_get_order( $order_id );
	
			if ( ! $this->can_refund_order( $order ) ) {
				return new WP_Error( 'error', __( 'Refund failed.', 'woocommerce' ) );
			}

			$CrossReference = $order->get_transaction_id();

			$isoCurrencyCode = null;			
			$currencyShort = $order->get_order_currency();
			
			
			$iclISOCurrencyList = ISOHelper::getISOCurrencyList();
			if (!empty($currencyShort) && $iclISOCurrencyList->getISOCurrency($currencyShort, $icISOCurrency)) {			
				$isoCurrencyCode = $icISOCurrency->getISOCode();
			}			
			
			$amount = intval($amount * pow(10, $icISOCurrency->getExponent()));
			
			
			$crtCrossReferenceTransaction = new \net\thepaymentgateway\paymentsystem\CrossReferenceTransaction($this->getEntryPointList());

			$crtCrossReferenceTransaction->getMerchantAuthentication()->setMerchantID($this->mid);
			$crtCrossReferenceTransaction->getMerchantAuthentication()->setPassword($this->password);

			$crtCrossReferenceTransaction->getTransactionDetails()->getMessageDetails()->setTransactionType("REFUND");
			$crtCrossReferenceTransaction->getTransactionDetails()->getMessageDetails()->setCrossReference($CrossReference);

			$crtCrossReferenceTransaction->getTransactionDetails()->getAmount()->setValue((string) $amount);
			$crtCrossReferenceTransaction->getTransactionDetails()->getCurrencyCode()->setValue($isoCurrencyCode);

			$crtCrossReferenceTransaction->getTransactionDetails()->setOrderID((string) $order_id);
			$crtCrossReferenceTransaction->getTransactionDetails()->setOrderDescription((string) $reason);

			$boTransactionProcessed = $crtCrossReferenceTransaction->processTransaction($crtrCrossReferenceTransactionResult, $todTransactionOutputData);

			if ($boTransactionProcessed == false)
			{				
				return new WP_Error( 'error', __( "Refund failed. - Couldn't communicate with payment gateway", 'woocommerce' ) );				
			}
			else
			{
				$StatusCode = $crtrCrossReferenceTransactionResult->getStatusCode();
				if ($StatusCode ==  0) return true;				
				else return new WP_Error( 'error', __( "Refund failed. - ".$crtrCrossReferenceTransactionResult->getMessage(), 'woocommerce' ) );
				
			}	
		}


		/**
		 * Check if this gateway is enabled and supports the store currency
		 *
		 * @access public
		 * @return bool
		 */
		public function is_valid_for_use()
		{
			$iclISOCurrencyList = ISOHelper::getISOCurrencyList();
			$supportedCurrencies = array();
			for ($i = 0; $i < $iclISOCurrencyList->getCount(); $i++) {
				$supportedCurrencies[] = $iclISOCurrencyList->getAt($i)->getCurrencyShort();
			}
			if (!in_array(get_woocommerce_currency(), apply_filters('woocommerce_payvector_supported_currencies', $supportedCurrencies))) {
				return false;
			}

			return true;
		}

		/**
		 * Prints options available for this gateway implementation
		 */
		public function admin_options()
		{
			?>
			<h3><?php __('PayVector Payment Gateway', 'woocommerce'); ?></h3>

			<?php if (!$this->enabled): ?>
				<div class="inline error">
					<p><strong><?php _e('Gateway Disabled', 'woocommerce'); ?></strong>: <?php __(
					   	'PayVector does not support your store currency.',
					   	'woocommerce'
					   ); ?></p>
				</div>
				<?php
			endif;

			?>
			<table class="form-table">
				<?php
				// Generate the HTML For the settings form.
				$this->generate_settings_html();
				?>
			</table>
			<?php
		}

		/**
		 * Initialise Gateway Settings Form Fields
		 *
		 * @access public
		 * @return void
		 */
		public function init_form_fields()
		{
			$this->form_fields = array(
				'enabled' => array(
					'title' => __('Enable/Disable', 'woocommerce'),
					'type' => 'checkbox',
					'label' => __('Enable PayVector Gateway', 'woocommerce'),
					'default' => 'yes'
				),
				'test_mode' => array(
					'title' => __('Test Mode', 'woocommerce'),
					'type' => 'checkbox',
					'default' => 'yes',
					'description' => __('Test mode transactions can only use credit cards in the Test Card document available in the MMS.', 'woocommerce'),
				),
				'title' => array(
					'title' => __('Title', 'woocommerce'),
					'type' => 'text',
					'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
					'default' => __('PayVector', 'woocommerce'),
					'desc_tip' => true
				),
				'description' => array(
					'title' => __('Description', 'woocommerce'),
					'type' => 'textarea',
					'description' => __('This controls the description which the user sees during checkout.', 'woocommerce'),
					'default' => __('Pay with credit or debit card via PayVector', 'woocommerce')
				),
				'capture_method' => array(
					'title' => __('Capture Method', 'woocommerce'),
					'type' => 'select',
					'description' => 'This controls how the plugin captures the credit card details. Please note that the Direct method requires an SSL certificate and may require a level of PCI
				compliance - for more details please consult the Getting Started guide available in the PayVector MMS.',
					'options' => array(
						IntegrationMethod::DirectAPI => __('Direct/API', 'woocommerce'),
						IntegrationMethod::HostedPaymentForm => __('Hosted Payment Form', 'woocommerce')
					)
				),
				'live_mid' => array(
					'title' => __('<strong>Live</strong> Merchant ID', 'woocommerce'),
					'type' => 'text',
					'description' => __('Please enter your PayVector Merchant ID.', 'woocommerce'),
					'default' => '',
					'desc_tip' => true
				),
				'live_password' => array(
					'title' => __('<strong>Live</strong> Merchant Password', 'woocommerce'),
					'type' => 'password',
					'description' => __('Please enter your PayVector Merchant Password.', 'woocommerce'),
					'default' => '',
					'desc_tip' => true
				),
				'test_mid' => array(
					'title' => __('<strong>Test</strong> Merchant ID', 'woocommerce'),
					'type' => 'text',
					'description' => __('Please enter your PayVector <strong>TEST</strong> Merchant ID.', 'woocommerce'),
					'default' => '',
					'desc_tip' => true
				),
				'test_password' => array(
					'title' => __('<strong>Test</strong> Merchant Password', 'woocommerce'),
					'type' => 'password',
					'description' => __('Please enter your PayVector <strong>TEST</strong> Merchant ID.', 'woocommerce'),
					'default' => '',
					'desc_tip' => true
				)
			);
			if (class_exists("WC_Subscriptions_Order")) {
				$this->form_fields['ca_mid'] = array(
					'title' => __('<strong>CA</strong> Merchant ID', 'woocommerce'),
					'type' => 'text',
					'label' => 'Subscriptions plugin detected, please enter your CA ',
					'description' => __('Please enter your PayVector Continuous Authority Merchant ID.', 'woocommerce'),
					'default' => '',
					'desc_tip' => true
				);
				$this->form_fields['ca_password'] = array(
					'title' => __('<strong>CA</strong> Merchant Password', 'woocommerce'),
					'type' => 'password',
					'description' => __('Please enter your PayVector Continuous Authority Merchant Password.', 'woocommerce'),
					'default' => '',
					'desc_tip' => true
				);
			}
			$this->form_fields['transaction_type'] = array(
				'title' => __('Transaction Type', 'woocommerce'),
				'type' => 'select',
				'description' => __('Choose whether you wish to capture funds immediately or authorize payment only.', 'woocommerce'),
				'default' => 'sale',
				'options' => array(
					TransactionType::Sale => __('SALE', 'woocommerce'),
					TransactionType::PreAuth => __('AUTH Only', 'woocommerce')
				)
			);
			$this->form_fields['pre_shared_key'] = array(
				'title' => __('Pre Shared Key', 'woocommerce'),
				'type' => 'password',
				'description' => __('Please enter your Pre Shared Key which can be found in the PayVector MMS.', 'woocommerce'),
				'default' => ''
			);
			$this->form_fields['hash_method'] = array(
				'title' => __('Hash Method', 'woocommerce'),
				'type' => 'select',
				'description' => __(
					'Hash method to use when passing variables to the Hosted Payment Form - this must be the identical to the value in the PayVector MMS.',
					'woocommerce'
				),
				'default' => 'SHA1',
				'options' => array(
					'MD5' => __('MD5', 'woocommerce'),
					'HMACMD5' => __('HMACMD5', 'woocommerce'),
					'SHA1' => __('SHA1', 'woocommerce'),
					'HMACSHA1' => __('HMACSHA1', 'woocommerce')
				)
			);
			$this->form_fields['result_delivery_method'] = array(
				'title' => __('Result Delivery Method', 'woocommerce'),
				'type' => 'select',
				'description' => 'Method the gateway should use when reporting results of the transaction. Please consult the getting started guide for information on which ' .
					'option to pick',
				'options' => array(
					'POST' => __('POST', 'woocommerce'),
					'SERVER_PULL' => __('SERVER_PULL', 'woocommerce')
				)
			);
			$this->form_fields['enable_saved_card'] = array(
				'title' => __('Enable Saved Card Functionality'),
				'type' => 'checkbox',
				'description' => 'The saved card functionality requires that the customer\'s CVV code be passed back to your server before being forwarded to the PayVector ' .
					'gateway. Please note that enabling this functionality while using the Hosted Payment Form capture method likely increases your PCI compliance obligations to ' .
					'the same level as using the Direct/API capture method.'
			);
			$this->form_fields['enable_3ds_cross_reference'] = array(
				'title' => __('Enable 3DSecure on Cross Reference Transactions'),
				'type' => 'checkbox',
				'description' => 'By default 3DSecure is disabled on cross reference transactions ("Use saved card" transactions), by checking this setting 3DSecure will be ' .
					'performed on all cross reference transactions. Subscriptions are not effected, 3DSecure is never performed on CA transactions as the customer is not present.'
			);
		}

		/**
		 * Payment fields for PayVector integration - dynamically hides fields not applicable with javascript
		 **/
		public function payment_fields()
		{
			$userID = get_current_user_id();			
			$crossReference = get_user_meta($userID, $this->crossReferenceKey, true);
			$cardType = get_user_meta($userID, $this->cardTypeKey, true);
			$cardLastFour = get_user_meta($userID, $this->cardLastFourKey, true);			
			
			$pluginURL = plugin_dir_url(__FILE__);
			$yearNow = date('y');
			$startYear = array();
			$startYear[] = "";
			$expiryYear = array();
			$expiryYear[] = "";
			for ($y = $yearNow - 9; $y <= $yearNow; $y++) {
				$startYear[] = sprintf('%02d', $y);
			}
			for ($y = $yearNow; $y < $yearNow + 10; $y++) {
				$expiryYear[] = sprintf('%02d', $y);
			}
			ob_start();
			include __DIR__ . "/templates/PaymentForm.tpl";
			echo ob_get_clean();
		}

		/**
		 * Process the payment and return the result
		 *
		 * @access public
		 * @param  int $orderID
		 * @return array
		 */
		public function process_payment($orderID)
		{
			
			$this->order = new WC_Order($orderID);
			$data = $this->getCardData($_POST['payment_type'] === SaleType::CrossReferenceSale);
								
			
			if (($this->captureMethod === IntegrationMethod::DirectAPI || $_POST['payment_type'] === SaleType::CrossReferenceSale) && !$data) {
				foreach ($this->inputErrors as $inputError) {
					wc_add_notice($inputError, "error");					
				}

				return ['result' => 'failure', 'redirect' => ''];				
			}
			//Convert cart total to minor currency and convert country/currency shorts to ISO codes
			$isoCurrencyCode = null;
			$isoCountryCode = null;
			$currencyShort = $this->order->get_order_currency();
			$cartTotal = $this->order->get_total();
			$countryShort = $this->order->billing_country;
			$iclISOCurrencyList = ISOHelper::getISOCurrencyList();
			if (!empty($currencyShort) && $iclISOCurrencyList->getISOCurrency($currencyShort, $icISOCurrency)) {
				/** @var $icISOCurrency ISOCurrency */
				$isoCurrencyCode = $icISOCurrency->getISOCode();
				//Always check to see if the cart already formats in minor currency
				$cartTotal = (string) $cartTotal;
				$cartTotal = round($cartTotal * ("1" . str_repeat(0, $icISOCurrency->getExponent())));
			}
			$iclISOCountryList = ISOHelper::getISOCountryList();
			if (!empty($countryShort) && $countryShort != "-1" && $iclISOCountryList->getISOCountry($countryShort, $icISOCountry)) {
				/** @var $icISOCountry ISOCountry */
				$isoCountryCode = $icISOCountry->getISOCode();				
			}
			

			$transactionProcessor = new TransactionProcessor();
			$transactionProcessor->setMerchantID($this->mid);
			$transactionProcessor->setMerchantPassword($this->password);
			$transactionProcessor->setRgeplRequestGatewayEntryPointList($this->getEntryPointList());
			$transactionProcessor->setCurrencyCode($isoCurrencyCode);
			$transactionProcessor->setAmount($cartTotal);
			$transactionProcessor->setOrderID($orderID);
			$transactionProcessor->setOrderDescription("WooCommerce Order number " . $orderID);
			$transactionProcessor->setCustomerName($this->order->billing_first_name . " " . $this->order->billing_last_name);
			$transactionProcessor->setAddress1($this->order->billing_address_1);
			$transactionProcessor->setAddress2($this->order->billing_address_2);
			$transactionProcessor->setCity($this->order->billing_city);
			$transactionProcessor->setState($this->order->billing_state);
			$transactionProcessor->setPostcode($this->order->billing_postcode);
			$transactionProcessor->setCountryCode($isoCountryCode);
			$transactionProcessor->setEmailAddress($this->order->billing_email);
			$transactionProcessor->setPhoneNumber($this->order->billing_phone);
			$transactionProcessor->setIPAddress($_SERVER['REMOTE_ADDR']);
			$transactionProcessor->setTransactionType($this->transactionType);
			

			//3DSv2 Parameters				
			$transactionProcessor->setJavaEnabled($data['browserJavaEnabled']);
			$transactionProcessor->setJavaScriptEnabled('true');
			$transactionProcessor->setScreenWidth($data['browserScreenWidth']);
			$transactionProcessor->setScreenHeight($data['browserScreenHeight']);
			$transactionProcessor->setScreenColourDepth($data['browserColorDepth']);
			$transactionProcessor->setTimezoneOffset($data['browserTZ']);				
			$transactionProcessor->setLanguage($data['browserLanguage']);

			$isRecurringInitial = class_exists("WC_Subscriptions_Order") && WC_Subscriptions_Order::order_contains_subscription($this->order);
			
			
			$ChURL = add_query_arg( [				
				'action' => '3DSecureV2',
				'orderID' => $orderID,
				'isRecurringInitial' => $isRecurringInitial ? 'true': 'false',
			], $this->notifyURL );
			

			$ChURL = htmlspecialchars($ChURL, ENT_XML1, 'UTF-8');
			
			$transactionProcessor->setChallengeNotificationURL($ChURL);				
			$transactionProcessor->setFingerprintNotificationURL($ChURL);

			if (isset($data['cv2'])) {
				$transactionProcessor->setCV2($data['cv2']);
			}
			//if the subscriptions plugin is enabled then check to see if this order contains a subscription
			
			//if this order contains a subscription check if it's a free trial and run a preauth for 1.01 if so
			if ($isRecurringInitial) {
				$transactionProcessor = $this->getSubscriptionProcessor($transactionProcessor, WC_Subscriptions_Order::get_total_initial_payment($this->order));
			}
			//handle new transaction
			if ($_POST['payment_type'] !== SaleType::CrossReferenceSale || $this->get_option('enable_saved_card') !== "yes") {
				
				WC()->session->set( 'payvector_transaction_is_cross_reference', false );
				if ($this->captureMethod === IntegrationMethod::DirectAPI) {
					$finalTransactionResult = $transactionProcessor->doCardDetailsTransaction(
						$data['cardNumber'],
						$data['expiryMonth'],
						$data['expiryYear'],
						$data['issueNumber'],
						$this->sessionHandler);
				} else {
					$urlparms = [						
						'orderID' => $orderID,
						'isRecurringInitial' => $isRecurringInitial ? 'true': 'false',
					];
					
					$urlparms['hpfReturn'] = 'true';
					$callbackURL1 = add_query_arg($urlparms, $this->notifyURL );
					unset($urlparms['hpfReturn']);
					$urlparms['serverResult'] = 'true';
					$callbackURL2 = add_query_arg($urlparms, $this->notifyURL );

					
					$viewArray = $transactionProcessor->getHostedPaymentForm(
						$callbackURL1,
						$callbackURL2,
						$this->preSharedKey,
						$this->hashMethod,
						$this->resultDeliveryMethod,
						false,
						$this->sessionHandler);
					$redirectUrl = $this->hostedPaymentFormURL."?";
					$redirectUrl .= http_build_query($viewArray);
					

					return array(
						'result' => 'success',
						'redirect' => $redirectUrl
					);
				}
			}
			//handle cross reference transaction
			else {
				
				WC()->session->set( 'payvector_transaction_is_cross_reference', true );
				$crossReference = get_user_meta(get_current_user_id(), $this->crossReferenceKey, true);
				if (!empty($crossReference)) {
					if (!$isRecurringInitial && $this->get_option('enable_3ds_cross_reference') !== "yes") {
						$finalTransactionResult = $transactionProcessor->doCrossReferenceTransaction($crossReference, false, $this->sessionHandler);
					} else {
						$finalTransactionResult = $transactionProcessor->doCrossReferenceTransaction($crossReference, true, $this->sessionHandler);
					}
				}
				else {
					//crossReference not found - either a database error or the session has ended
					wc_add_notice(__('Session expired - please try again', 'woothemes'), "error");
					wp_redirect($this->order->get_checkout_payment_url());
					exit;
				}
			}
			return $this->handleTransactionResults($finalTransactionResult, $isRecurringInitial);
		}

		/**
		 * @param string   $amountToCharge
		 * @param WC_Order $order
		 * @param int      $productID
		 */
		public function scheduledSubscriptionPayment($amountToCharge, $order, $productID)
		{
			$this->order = $order;
			$CAMid = $this->get_option('ca_mid');
			$CAPassword = $this->get_option('ca_password');
			if (empty($CAMid) || empty($CAPassword)) {
				$CAMid = $this->mid;
				$CAPassword = $this->password;
			}
			$transactionProcessor = new TransactionProcessor(
				$CAMid,
				$CAPassword,
				$this->getEntryPointList(),
				$order->get_order_currency(),
				$amountToCharge,
				$order->id,
				"WooCommerce Order number " . $order->id
			);
			$transactionProcessor = $this->getSubscriptionProcessor($transactionProcessor, $amountToCharge);
			//get the cross reference from the database and run the transaction
			$crossReference = get_post_meta($order->id, $this->subscriptionCrossReferenceKey, true);
			if (!empty($crossReference)) {
				$finalTransactionResult = $transactionProcessor->doCrossReferenceTransaction(
					$crossReference,
					false, 
					$this->sessionHandler);
			} else {
				$order->add_order_note(__("Cross reference not found for subscription", "woothemes"));
				WC_Subscriptions_Manager::process_subscription_payment_failure_on_order($order, $productID);

				return;
			}
			if ($finalTransactionResult->transactionProcessed() && $finalTransactionResult->transactionSuccessful()) {
				//Save cross reference
				$crossReference = $finalTransactionResult->getCrossReference();
				update_post_meta($order->id, $this->subscriptionCrossReferenceKey, $crossReference);
				WC_Subscriptions_Manager::process_subscription_payments_on_order($order, $productID);
			} else {
				wc_add_notice($finalTransactionResult->getErrorMessage(), "error");
				WC_Subscriptions_Manager::process_subscription_payment_failure_on_order($order, $productID);
			}
		}

		/**
		 * @param  TransactionProcessor $transactionProcessor
		 * @param  float|string         $amount
		 * @return TransactionProcessor
		 */
		private function getSubscriptionProcessor($transactionProcessor, $amount)
		{
			$iclISOCurrencyList = ISOHelper::getISOCurrencyList();
			if ($iclISOCurrencyList->getISOCurrency($this->order->get_order_currency(), $icISOCurrency)) {
				/** @var $icISOCurrency ISOCurrency */
				$exponent = $icISOCurrency->getExponent();
				$amount = (string) $amount;
				if (strpos($amount, '.') != 0) {
					$amount = round($amount * ("1" . str_repeat(0, $exponent)));
				}
			}
			$transactionType = $this->transactionType;
			if ($amount == 0) {
				//if this is a free trial then run a preauth for £1.01
				$transactionType = TransactionType::PreAuth;
				$amount = 101;
			}
			$transactionProcessor->setAmount($amount);
			$transactionProcessor->setTransactionType($transactionType);

			return $transactionProcessor;
		}

		//TODO look over
		public function subscriptionMethodChanged($originalOrder, $newRenewalOrder)
		{
			update_post_meta($originalOrder->id, $this->subscriptionCrossReferenceKey, get_post_meta($newRenewalOrder->id, $this->subscriptionCrossReferenceKey, true));
		}

		private function create3d()
		{
			$FormAttributes = " target=\"threeDSecureFrame\"";			
			$FormAction = WC()->session->get( 'payvector_MethodURL' );
			$prams =  ["ThreeDSMethodData"=>WC()->session->get( 'payvector_ThreeDSMethodData')];
			
			$this->showThreeDSV2IFrame($FormAttributes, $FormAction, $prams);
			
		}

		private function showThreeDSV2($FormAttributes, $FormAction, $prams){
			include(__DIR__ . '/templates/3DSecure.tpl');
			exit;			
		}

		private function showThreeDSV2IFrame($FormAttributes, $FormAction, $prams)
		{
			include(__DIR__ . '/templates/3DSecureLandingForm.tpl');
			exit;
		}

		public function callbackHPF()
		{
			/* @var TransactionResult $trTransactionResult */
			$hashMatches = false;
			$szValidateErrorMessage = "";
			if ($this->resultDeliveryMethod === "POST") {
				$hashMatches = PaymentFormHelper::validateTransactionResult_POST(
					$this->mid,
					$this->password,
					$this->preSharedKey,
					$this->hashMethod,
					$_POST,
					$trTransactionResult,
					$szValidateErrorMessage
				);
			} else if ($this->resultDeliveryMethod === "SERVER_PULL") {
				$hashMatches = PaymentFormHelper::validateTransactionResult_SERVER_PULL(
					$this->mid,
					$this->password,
					$this->preSharedKey,
					$this->hashMethod,
					$_GET,
					$this->hostedPaymentFormHandlerURL,
					$trTransactionResult,
					$szValidateErrorMessage
				);
			}
			if (!$hashMatches) {
				echo $szValidateErrorMessage;
				exit;
			}
			$this->handleTransactionResults(
				new HostedPaymentFormFinalTransactionResult($trTransactionResult),
				(isset($_GET['isRecurringInitial']) && $_GET['isRecurringInitial'] === "true")
			);
		}

		/**
		 * Handles the returned results from the 3DSecure form
		 *
		 * @return array
		 * @throws Exception
		 */
		public function callback3DSecure()
		{

			$cres = $_REQUEST['cres'] ?? '';
			$FormMode = $_REQUEST['FormMode'] ?? 'NEW';
			$ThreeDSMethodData = $_REQUEST['threeDSMethodData'] ?? '';

			if (empty($cres)){
				switch ($FormMode)
				{
					case "NEW":						
						$FormAttributes = " target=\"_parent\"";
						$FormAction = "";
						$prams = ["threeDSMethodData"=>$ThreeDSMethodData, "FormMode"=> "STEP2"];						
						$this->showThreeDSV2($FormAttributes, $FormAction, $prams );							
						exit;						
					break;
					case "STEP2":						
						$crossReference = WC()->session->get( 'payvector_md');
						
						$tdseThreeDSecureEnvironment = new \net\thepaymentgateway\paymentsystem\ThreeDSecureEnvironment($this->getEntryPointList());
						$tdseThreeDSecureEnvironment->getMerchantAuthentication()->setMerchantID($this->mid);
						$tdseThreeDSecureEnvironment->getMerchantAuthentication()->setPassword($this->password);
						$tdseThreeDSecureEnvironment->getThreeDSecureEnvironmentData()->setCrossReference($crossReference);
						$tdseThreeDSecureEnvironment->getThreeDSecureEnvironmentData()->setMethodData($ThreeDSMethodData);
						$boTransactionProcessed = $tdseThreeDSecureEnvironment->processTransaction($tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData);
						
						if($tdsarThreeDSecureAuthenticationResult->getStatusCode() === 3){			
							$CREQ = $todTransactionOutputData->getThreeDSecureOutputData()->getCREQ();
							$ThreeDSSessionData = PaymentFormHelper::base64UrlEncode($todTransactionOutputData->getCrossReference());
							$FormAttributes = " target=\"threeDSecureFrame\"";
							$FormAction = $todTransactionOutputData->getThreeDSecureOutputData()->getACSURL();
							$parms = ['creq' => $CREQ, 'threeDSSessionData' => $ThreeDSSessionData];
							
							$this->showThreeDSV2IFrame($FormAttributes, $FormAction, $parms );
							exit;
						}
						else {							
							$finalTransactionResult = new ThreeDSecureFinalTransactionResult($boTransactionProcessed, $tdseThreeDSecureEnvironment, $tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData, $this->sessionHandler);
							//$this->handleTransactionResults($method, $order, $finalTransactionResult);
							$this->handleTransactionResults($finalTransactionResult, (isset($_GET['recurring_initial']) && $_GET['recurring_initial'] === "true"));
							exit;

						}
										
						exit;
					break;
				}
			}
			else {		
				$threeDSSessionData =$_REQUEST['threeDSSessionData'] ?? ''; 
				
				switch ($FormMode)
				{
					case "NEW":		
						$FormAttributes = " target=\"_parent\"";
						$FormAction = "";
						$prams = ["cres"=>$cres,"threeDSSessionData"=>$threeDSSessionData, "FormMode"=> "STEP3"];
						$this->showThreeDSV2($FormAttributes, $FormAction, $prams );
					break;
					case "STEP3":
						
						$CrossReference = PaymentFormHelper::base64UrlDecode($threeDSSessionData);
						
						$tdsaThreeDSecureAuthentication = new \net\thepaymentgateway\paymentsystem\ThreeDSecureAuthentication($this->getEntryPointList());
						$tdsaThreeDSecureAuthentication->getMerchantAuthentication()->setMerchantID($this->mid);
						$tdsaThreeDSecureAuthentication->getMerchantAuthentication()->setPassword($this->password);
						$tdsaThreeDSecureAuthentication->getThreeDSecureInputData()->setCrossReference($CrossReference);
						$tdsaThreeDSecureAuthentication->getThreeDSecureInputData()->setCRES($cres);
						$boTransactionProcessed = $tdsaThreeDSecureAuthentication->processTransaction($tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData);	
						$finalTransactionResult = new ThreeDSecureFinalTransactionResult($boTransactionProcessed, $tdsaThreeDSecureAuthentication, $tdsarThreeDSecureAuthenticationResult, $todTransactionOutputData, $this->sessionHandler);							
						//$html = $this->handleTransactionResults($method, $order, $finalTransactionResult);						
						$this->handleTransactionResults($finalTransactionResult, (isset($_GET['recurring_initial']) && $_GET['recurring_initial'] === "true"));

						WC()->session->__unset('payvector_md');
						WC()->session->__unset('payvector_MethodURL');						
								
						return true;
					break;

				}
			}
			exit;
		}

		/**
		 * Routes callbacks to the correct function
		 *
		 * @return array|null
		 * @throws Exception
		 */
		public function callback()
		{
			if (isset($_GET['action'])) {
				if ($_GET['action'] === "3DSecureV2") {
					return $this->callback3DSecure();
				} else if ($_GET['action'] === "create3D") {
					$this->create3d();
				}
			} else if ($_GET['hpfReturn'] === "true") {
				$this->callbackHPF();
			}

			return null;
		}

		/**
		 * Checks the database for a recent gateway entry point list and returns it if found, otherwise returns a blind list
		 *
		 * @throws Exception
		 * @return RequestGatewayEntryPointList
		 */
		private function getEntryPointList()
		{
			$rgeplRequestGatewayEntryPointList = new \net\thepaymentgateway\paymentsystem\RequestGatewayEntryPointList();
			$geplGatewayEntryPointListXML = get_option($this->gatewayEntryPointListXMLKey, null);
			
			if ($geplGatewayEntryPointListXML !== null) {
				$geplGatewayEntryPointList = GatewayEntryPointList::fromXmlString($geplGatewayEntryPointListXML);
				for ($nCount = 0; $nCount < $geplGatewayEntryPointList->getCount(); $nCount++) {
					$geplGatewayEntryPoint = $geplGatewayEntryPointList->getAt($nCount);
					$rgeplRequestGatewayEntryPointList->add($geplGatewayEntryPoint->getEntryPointURL(), $geplGatewayEntryPoint->getMetric(), 1);
				}
			} else {
				// if we don't have a recent list in the database then just use blind processing
				$rgeplRequestGatewayEntryPointList->add("https://gw1." . $this->paymentProcessorDomain, 100, 2);
				$rgeplRequestGatewayEntryPointList->add("https://gw2." . $this->paymentProcessorDomain, 200, 2);
				$rgeplRequestGatewayEntryPointList->add("https://gw3." . $this->paymentProcessorDomain, 300, 2);
			}

			return $rgeplRequestGatewayEntryPointList;
		}

		/**
		 * Validates credit/debit card data and returns it in a formatted array
		 *
		 * @param  bool $crossReferenceTransaction True if this is a cross reference transaction and only needs the CVV verifying
		 * @return array|bool                            Array containing the card data if it's valid, false otherwise
		 */
		private function getCardData($crossReferenceTransaction = false)
		{
			$this->inputErrors = array();
			$data = array();
			//only get card data if this is a direct transaction
			if ($this->captureMethod === IntegrationMethod::DirectAPI || $crossReferenceTransaction) {
				
				if (!empty($_POST['card_code'])) $data['cv2'] = $_POST['card_code'];
				if (!empty($_POST['card_code_sc'])) $data['cv2'] = $_POST['card_code_sc'];
				//3DSv2
				$data['browserJavaEnabled'] = sanitize_text_field($_POST['browserjavaenabled']);
				$data['browserScreenWidth'] = sanitize_text_field($_POST['browserscreenwidth']);
				$data['browserScreenHeight'] = sanitize_text_field($_POST['browserscreenheight']);
				$data['browserColorDepth'] = sanitize_text_field($_POST['browsercolordepth']);
				$data['browserTZ'] = sanitize_text_field($_POST['browsertz']);
				$data['browserLanguage'] = sanitize_text_field($_POST['browserlanguage']);
				if (!is_numeric($data['cv2'])) {
					$this->inputErrors[] = "Invalid cvv code";
				}
				//only get the CV2 on cross reference transactions
				if (!$crossReferenceTransaction) {
					$data['cardNumber'] = str_replace(array(' ', '-'), '', $_POST['card_number']);
					$inputLength = strlen($data['cardNumber']);
					if (!is_numeric($data['cardNumber']) || $inputLength < 8 || $inputLength > 20) {
						$this->inputErrors[] = "Invalid card number";
					}
					$data['expiryMonth'] = $_POST['expirymonth'];
					if (!is_numeric($data['expiryMonth'])) {
						$this->inputErrors[] = "Invalid expiry month";
					}
					$data['expiryYear'] = $_POST['expiryyear'];
					if (!is_numeric($data['expiryYear'])) {
						$this->inputErrors[] = "Invalid expiry year";
					}					
					$data['issueNumber'] = $_POST['issue_number'];
					if (isset($data['issueNumber']) && $data['issueNumber'] !== "" && !is_numeric($data['issueNumber'])) {
						$this->inputErrors[] = "Invalid issue number";
					}
				}
			}
			if (count($this->inputErrors) === 0) {
				return $data;
			} else {
				return false;
			}
		}

		/**
		 * @param  FinalTransactionResult $finalTransactionResult An implementation of FinalTransactionResult
		 * @param  bool                   $isRecurringInitial     (optional) True if this is the initial payment of a recurring transaction
		 * @param  int|string             $orderID                (optional) ID number of this order
		 * @return array                                          If still on the checkout page then an array is returned, otherwise causes a redirect
		 */
		public function handleTransactionResults(FinalTransactionResult $finalTransactionResult, $isRecurringInitial = false)
		{			
			global $woocommerce;
			$orderID = $finalTransactionResult->getOrderID($this->sessionHandler);
			if (empty($orderID)) $orderID = $_GET['orderID'];
			
			if (!isset($this->order)) {
				$this->order = new WC_Order($orderID );			
			}
			
			if ($finalTransactionResult->transactionProcessed() && $finalTransactionResult->transactionSuccessful()) {
				//save gateway entry point list to the database (Direct/API only)
				$gatewayEntryPointList = $finalTransactionResult->getGatewayEntryPointList();
				if (isset($gatewayEntryPointList)) {
					update_option($this->gatewayEntryPointListXMLKey, $gatewayEntryPointList->toXmlString());
				}
				$userID = $this->order->get_user_id();
								
				//Save cross reference
				$crossReference = $finalTransactionResult->getCrossReference();
				$cardType = $finalTransactionResult->getCardType();
				$cardLastFour = $finalTransactionResult->getCardLastFour($this->sessionHandler);
				
				
				if ($isRecurringInitial) {
					update_post_meta($finalTransactionResult->getOrderID($this->sessionHandler), $this->subscriptionCrossReferenceKey, $crossReference);
					update_post_meta($finalTransactionResult->getOrderID($this->sessionHandler), $this->subscriptionCardTypeKey, $cardType);
					if (isset($cardLastFour)) {
						update_post_meta($finalTransactionResult->getOrderID($this->sessionHandler), $this->subscriptionCardLastFourKey, $cardLastFour);
					}
				
					} else if (isset($userID) && isset($crossReference)) {				
					//$userID = get_current_user_id();
					
					
					update_user_meta($userID, $this->crossReferenceKey, $crossReference);
					update_user_meta($userID, $this->cardTypeKey, $cardType);
					$is_cross_reference = WC()->session->get( 'payvector_transaction_is_cross_reference' );
					$cTransactionMethod = $finalTransactionResult->getTransactionMethod();
					//don't run a database call for a cross reference transaction as the card last four won't have changed
					if (
						(!$is_cross_reference)
						&& ($cTransactionMethod !== TransactionMethod::CrossReferenceTransaction)
					) {
						
						if (isset($cardLastFour)) {
							update_user_meta($userID, $this->cardLastFourKey, $cardLastFour);
						} else {
							update_user_meta($userID, $this->cardLastFourKey, "");
						}
					}
					
					WC()->session->__unset('payvector_transaction_is_cross_reference');
				}
				//don't add duplicates to the database
				if (
					!$finalTransactionResult->duplicateTransaction()
					|| ($woocommerce->session->order_awaiting_payment == $finalTransactionResult->getOrderID($this->sessionHandler))
				) {
					$this->order->add_order_note("Payment complete: " . $finalTransactionResult->getMessage());
					$this->order->set_transaction_id($crossReference);
					$this->order->payment_complete();
				}
				$woocommerce->cart->empty_cart();
				//if using the hosted payment form method or if this is a 3DS transaction then redirect manually
				if (
					$finalTransactionResult->getIntegrationMethod() === IntegrationMethod::HostedPaymentForm
					|| $finalTransactionResult->getTransactionMethod() === TransactionMethod::ThreeDSecureTransaction
				) {
					$this->redirectToPaymentComplete();
				}
			} else {
				//run 3DSecure if required
				if ($finalTransactionResult->getStatusCode() === 3) {					

					
					WC()->session->set( 'payvector_md', $finalTransactionResult->getCrossReference() );
					WC()->session->set( 'payvector_MethodURL', $finalTransactionResult->getThreeDSecureOutputData()->getMethodURL() );
					WC()->session->set( 'payvector_ThreeDSMethodData', $finalTransactionResult->getThreeDSecureOutputData()->getMethodData() );

					$redirectUrl= add_query_arg( [						
						'action' => 'create3D',
						'orderID' => $this->order->id,
						'isRecurringInitial' => $isRecurringInitial,
					], $this->notifyURL );					

					return array(
						'result' => 'success',
						'redirect' => $redirectUrl
					);
				}
				//otherwise report the error
				
				wc_add_notice(__('Payment error: ', 'woothemes') . $finalTransactionResult->getUserFriendlyMessage(), "error");
				$this->order->update_status('failed', $finalTransactionResult->getMessage());
				if ($finalTransactionResult instanceof HostedPaymentFormFinalTransactionResult || $finalTransactionResult instanceof ThreeDSecureFinalTransactionResult) {
					$this->redirectToPaymentComplete();
				}
			}

			//if using direct then let the ajax handle redirection
			return array(
				'result' => 'success',
				'redirect' => $this->get_return_url($this->order)
			);
		}

		private function redirectToPaymentComplete()
		{
			$link =
				'<noscript>You have javascript disabled, please click to continue: <a target="_top" href="' .
				$this->order->get_checkout_order_received_url() .
				'">' .
				$this->order->get_checkout_order_received_url() .
				'</a></noscript>';
			$js = "window.parent.location.href='" . $this->order->get_checkout_order_received_url() . "'";
			echo '<p>Please wait</p>';
			echo $link;
			echo '<script>';
			echo $js;
			echo '</script>';
			exit;
		}
	}

	if (is_admin()) {
		new WC_Gateway_PayVector();
	}
}
