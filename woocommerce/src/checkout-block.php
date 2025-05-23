<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Payvector_Blocks extends AbstractPaymentMethodType 
{
    private $gateway;
    protected $name = 'payvector';

    public function initialize() {
		$this->settings = get_option( 'woocommerce_payvector_settings', [] );
		$gateways       = WC()->payment_gateways->payment_gateways();
		$this->gateway  = $gateways[ $this->name ];
	}

    public function is_active() {
		return true;
	}

    public function get_payment_method_script_handles()
    {
        wp_register_script(
            'payvector-blocks',
            plugin_dir_url(__FILE__) . 'checkout_block.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            null,
            true
        );

        if (function_exists('wp_set_script_translations')) 
        {
            wp_set_script_translations('payvector-blocks');
        }

        return ['payvector-blocks'];
    }

    public function get_payment_method_data()
    {
        
        if (class_exists('WC_Gateway_Payvector')) {
            $gateway = new WC_Gateway_Payvector();        
            ob_start();
            $gateway->hash_block = true;
            $gateway->payment_fields();
            $form_html = ob_get_clean();
        } else {
            $form_html = '<div>Payment form unavailable</div>';
        }

        return [
            'title' => $this->get_setting( 'title' ),
            'description' => $this->get_setting( 'description' ),
            'html' => $form_html,
            'cvvImgUrl' => plugins_url( 'img/CVV.jpg', __FILE__ ),
        ]; 
    }
}
