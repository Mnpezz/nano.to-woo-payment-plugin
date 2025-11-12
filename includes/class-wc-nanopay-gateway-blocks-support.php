<?php
// File: includes/class-wc-nanoto-gateway-blocks-support.php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Nanoto_Gateway_Blocks_Support extends AbstractPaymentMethodType {
    private $gateway;
    protected $name = 'nanoto';

    public function initialize() {
        $this->settings = get_option("woocommerce_{$this->name}_settings", []);
        $this->gateway = new WC_Nanoto_Gateway();
    }

    public function is_active() {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles() {
        wp_register_script(
            'wc-nanoto-blocks-integration',
            plugins_url('build/index.js', dirname(__FILE__)),
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
        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('wc-nanoto-blocks-integration');
        }
        return ['wc-nanoto-blocks-integration'];
    }

    public function get_payment_method_data() {
        return [
            'title' => $this->gateway->get_title(),
            'description' => $this->gateway->get_description(),
            'supports' => $this->gateway->supports,
            'nano_address' => $this->gateway->get_option('nano_address'),
        ];
    }
}
