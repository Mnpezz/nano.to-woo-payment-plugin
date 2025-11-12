<?php
/*
Plugin Name: Nanoto Woo Payment Gateway
Description: Adds Nano.to payment method for WooCommerce using NanoPay.js
Version: 2.4
Author: mnpezz
Plugin URI: http://github.com/mnpezz
Requires at least: 5.0
Requires PHP: 7.0
WC requires at least: 3.0
WC tested up to: 8.3
*/

if (!defined('ABSPATH')) {
    exit;
}

add_filter('woocommerce_payment_gateways', 'add_nanoto_gateway');
function add_nanoto_gateway($gateways) {
    $gateways[] = 'WC_Nanoto_Gateway';
    return $gateways;
}

// CRITICAL: Enqueue scripts OUTSIDE the class so they always load
add_action('wp_enqueue_scripts', 'nanoto_enqueue_scripts', 20);
function nanoto_enqueue_scripts() {
    // Load on checkout, cart, and order-pay pages
    if (!is_cart() && !is_checkout() && !is_wc_endpoint_url('order-pay')) {
        return;
    }

    // Use the OFFICIAL CDN from nano.to documentation
    wp_enqueue_script('nanopay', 'https://cdn.nano.to/pay.js', array(), '2.0.0', true);
    wp_enqueue_style('nanoto-custom-styles', plugin_dir_url(__FILE__) . 'css/nano-woo-payment.css', array(), '1.0.0');
}

add_action('plugins_loaded', 'init_nanoto_gateway', 11);
function init_nanoto_gateway() {
    if (!class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>WooCommerce is not active. The WooCommerce Nanoto Gateway plugin requires WooCommerce to be active.</p></div>';
        });
        return;
    }

    class WC_Nanoto_Gateway extends WC_Payment_Gateway {
        public function __construct() {
            $this->id = 'nanoto';
            $this->icon = plugin_dir_url(__FILE__) . 'assets/images/nanoto-icon.png';
            $this->has_fields = false;
            $this->method_title = 'Nanoto';
            $this->method_description = 'Pay with Nano using Nano.to (NanoPay.js)';
            $this->supports = array('products');

            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->nano_address = $this->get_option('nano_address');
            $this->notify_email = $this->get_option('notify_email');

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => 'Enable/Disable',
                    'type' => 'checkbox',
                    'label' => 'Enable Nanoto Payment',
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => 'Title',
                    'type' => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default' => 'Pay with Nano',
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => 'Description',
                    'type' => 'textarea',
                    'description' => 'Payment method description that the customer will see on your checkout.',
                    'default' => 'Pay with Nano cryptocurrency',
                ),
                'nano_address' => array(
                    'title' => 'Nano Address',
                    'type' => 'text',
                    'description' => 'Enter your Nano address (starts with nano_)',
                    'default' => '',
                ),
                'notify_email' => array(
                    'title' => 'Notification Email',
                    'type' => 'email',
                    'description' => 'Enter an email address to receive payment notifications (optional).',
                    'default' => '',
                ),
            );
        }

        public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true)
            );
        }

        public function receipt_page($order_id) {
            $order = wc_get_order($order_id);
            
            // Prevent duplicate rendering
            static $rendered = false;
            if ($rendered) {
                return;
            }
            $rendered = true;
            
            echo '<p>' . __('Please complete your payment using Nano.', 'woocommerce') . '</p>';
            $this->generate_nanoto_form($order);
        }

        public function generate_nanoto_form($order) {
            $amount = $order->get_total();
            $currency = $order->get_currency();
            $order_id = $order->get_id();
            $order_number = $order->get_order_number();
            $site_name = get_bloginfo('name');
            ?>
            <div id="nanoto-payment-container"></div>
            <script>
                (function() {
                    'use strict';
                    
                    function initNanoPayment() {
                        // Check if NanoPay library is loaded
                        if (typeof window.NanoPay === 'undefined') {
                            console.log('Waiting for NanoPay library...');
                            setTimeout(initNanoPayment, 100);
                            return;
                        }
                        
                        console.log('NanoPay library loaded! Version:', window.NanoPay.version || '2.0.0');
                        
                        try {
                            // Open the NanoPay modal
                            window.NanoPay.open({
                                title: "<?php echo esc_js($site_name . ' - Order #' . $order_number); ?>",
                                address: '<?php echo esc_js($this->nano_address); ?>',
                                amount: <?php echo esc_js($amount); ?>,
                                currency: '<?php echo esc_js($currency); ?>',
                                <?php if (!empty($this->notify_email)): ?>
                                notify: '<?php echo esc_js($this->notify_email); ?>',
                                <?php endif; ?>
                                position: 'center',
                                description: '<?php echo esc_js('Order #' . $order_number); ?>',
                                success: function(block) {
                                    console.log('Payment successful!', block);
                                    
                                    // Send AJAX to mark order as paid
                                    jQuery.post(
                                        '<?php echo admin_url('admin-ajax.php'); ?>',
                                        {
                                            action: 'nanoto_payment_complete',
                                            order_id: <?php echo $order_id; ?>,
                                            block_hash: block.block ? block.block.hash : '',
                                            nonce: '<?php echo wp_create_nonce('nanoto-payment-complete'); ?>'
                                        },
                                        function(response) {
                                            if (response.success) {
                                                window.location.href = '<?php echo esc_js($this->get_return_url($order)); ?>';
                                            } else {
                                                console.error('Failed to update order:', response.data);
                                                alert('Payment received, but there was an issue. Please contact support with your payment hash.');
                                            }
                                        }
                                    ).fail(function(xhr, status, error) {
                                        console.error('AJAX error:', error);
                                        alert('Payment received, but connection failed. Please contact support.');
                                    });
                                },
                                cancel: function() {
                                    console.log('Payment cancelled by user');
                                    if (confirm('Are you sure you want to cancel this payment?')) {
                                        window.location.href = '<?php echo esc_js(wc_get_cart_url()); ?>';
                                    }
                                },
                                expired: function() {
                                    console.log('Payment expired');
                                    alert('Payment request expired. Please try again.');
                                }
                            });
                            
                        } catch (error) {
                            console.error('Error initializing NanoPay:', error);
                            alert('Error loading payment system. Please refresh the page and try again.');
                        }
                    }
                    
                    // Start initialization
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', initNanoPayment);
                    } else {
                        initNanoPayment();
                    }
                })();
            </script>
            <?php
        }
    }
}

// AJAX handlers
add_action('wp_ajax_nanoto_payment_complete', 'nanoto_payment_complete');
add_action('wp_ajax_nopriv_nanoto_payment_complete', 'nanoto_payment_complete');

function nanoto_payment_complete() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'nanoto-payment-complete')) {
        wp_send_json_error('Invalid nonce');
    }

    $order_id = intval($_POST['order_id']);
    $block_hash = sanitize_text_field($_POST['block_hash']);
    $order = wc_get_order($order_id);

    if (!$order) {
        wp_send_json_error('Invalid order ID');
    }

    // Mark order as complete
    $order->payment_complete();
    $order->add_order_note(sprintf(__('Nano payment completed via Nano.to. Block hash: %s', 'woocommerce'), $block_hash));

    wp_send_json_success();
}

// Block support
add_action('woocommerce_blocks_loaded', 'nanoto_register_payment_method_type');

function nanoto_register_payment_method_type() {
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    require_once plugin_dir_path(__FILE__) . 'includes/class-wc-nanoto-gateway-blocks-support.php';

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function(Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
            $payment_method_registry->register(new WC_Nanoto_Gateway_Blocks_Support());
        }
    );
}

// Pass payment method data to frontend
add_filter('woocommerce_blocks_payment_method_data_registration', 'nanoto_add_payment_method_data');

function nanoto_add_payment_method_data($payment_method_data) {
    $gateway_settings = get_option('woocommerce_nanoto_settings', array());
    
    if (!empty($gateway_settings['enabled']) && $gateway_settings['enabled'] === 'yes') {
        $payment_method_data['nanoto_data'] = array(
            'title' => isset($gateway_settings['title']) ? $gateway_settings['title'] : 'Pay with Nano',
            'description' => isset($gateway_settings['description']) ? $gateway_settings['description'] : 'Pay with Nano cryptocurrency',
            'supports' => array('products'),
            'nano_address' => isset($gateway_settings['nano_address']) ? $gateway_settings['nano_address'] : '',
        );
    }
    
    return $payment_method_data;
}

// Declare compatibility
add_action('before_woocommerce_init', 'nanoto_cart_checkout_blocks_compatibility');
function nanoto_cart_checkout_blocks_compatibility() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}
