<?php
/*
Plugin Name: Woo Blocks NanoPay Gateway
Description: Adds NanoPay as a payment method for WooCommerce
Version: 1.6
Author: mnpezz
Github: https://github.com/Mnpezz/woo-blocks-plugin
Donations: https://nano.to/mnpezz
Requires at least: 5.0
Requires PHP: 7.0
WC requires at least: 3.0
WC tested up to: 8.3
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_filter('woocommerce_payment_gateways', 'add_nanopay_gateway');
function add_nanopay_gateway($gateways) {
    $gateways[] = 'WC_NanoPay_Gateway';
    return $gateways;
}

add_action('plugins_loaded', 'init_nanopay_gateway', 11);
function init_nanopay_gateway() {
    if (!class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>WooCommerce is not active. The WooCommerce NanoPay Gateway plugin requires WooCommerce to be active.</p></div>';
        });
        return;
    }

    class WC_NanoPay_Gateway extends WC_Payment_Gateway {
        public function __construct() {
            $this->id = 'nanopay';
            $this->icon = plugin_dir_url(__FILE__) . 'assets/nanopay-icon.png';
            $this->has_fields = false;
            $this->method_title = 'NanoPay';
            $this->method_description = 'Pay with Nano using NanoPay';

            $this->supports = array('products');

            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->nano_address = $this->get_option('nano_address');
            $this->notify_email = $this->get_option('notify_email');

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
            add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => 'Enable/Disable',
                    'type' => 'checkbox',
                    'label' => 'Enable NanoPay Payment',
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => 'Title',
                    'type' => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default' => 'NanoPay',
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => 'Description',
                    'type' => 'textarea',
                    'description' => 'Payment method description that the customer will see on your checkout.',
                    'default' => 'Pay with Nano using NanoPay',
                ),
                'nano_address' => array(
                    'title' => 'Nano Address or Username',
                    'type' => 'text',
                    'description' => 'Enter your Nano address or Nano.to @Username here.',
                    'default' => '',
                ),
                'notify_email' => array(
                    'title' => 'Notification Email',
                    'type' => 'email',
                    'description' => 'Enter an email address to receive payment notifications.',
                    'default' => '',
                ),
            );
        }

        public function payment_scripts() {
            if (!is_cart() && !is_checkout() && !isset($_GET['pay_for_order'])) {
                return;
            }

            wp_enqueue_script('nanopay', 'https://pay.nano.to/latest.js', array(), null, true);
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
            echo '<p>' . __('Please complete your payment using NanoPay.', 'woocommerce') . '</p>';
            $this->generate_nanopay_form($order);
        }

        public function generate_nanopay_form($order) {
            $amount = $order->get_total();
            $currency = $order->get_currency();
            $order_id = $order->get_id(); // Get the order ID
            ?>
            <div id="nanopay-button"></div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    NanoPay.open({
                        title: "<?php echo esc_js(get_bloginfo('name')); ?> - Order #<?php echo $order->get_order_number(); ?>",
                        address: '<?php echo esc_js($this->nano_address); ?>',
                        amount: <?php echo esc_js($amount); ?>,
                        currency: '<?php echo esc_js($currency); ?>',
                        notify: '<?php echo esc_js($this->notify_email); ?>',
                        success: function(block) {
                            console.log('Payment successful:', block);
                            // Send AJAX request to update order status
                            jQuery.post(
                                '<?php echo admin_url('admin-ajax.php'); ?>',
                                {
                                    action: 'nanopay_payment_complete',
                                    order_id: <?php echo $order_id; ?>,
                                    nonce: '<?php echo wp_create_nonce('nanopay-payment-complete'); ?>'
                                },
                                function(response) {
                                    if (response.success) {
                                        window.location.href = '<?php echo esc_js($this->get_return_url($order)); ?>';
                                    } else {
                                        console.error('Failed to update order status:', response.data);
                                        alert('Payment was received, but there was an issue updating your order. Please contact us for assistance.');
                                    }
                                }
                            );
                        },
                        cancel: function() {
                            console.log('Payment cancelled');
                            alert('Payment was cancelled. Please try again.');
                        }
                    });
                });
            </script>
            <?php
        }
    }
}


// Add this outside of the class definition
add_action('wp_ajax_nanopay_payment_complete', 'nanopay_payment_complete');
add_action('wp_ajax_nopriv_nanopay_payment_complete', 'nanopay_payment_complete');

function nanopay_payment_complete() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'nanopay-payment-complete')) {
        wp_send_json_error('Invalid nonce');
    }

    $order_id = intval($_POST['order_id']);
    $order = wc_get_order($order_id);

    if (!$order) {
        wp_send_json_error('Invalid order ID');
    }

    // Update order status
    $order->update_status('processing', __('Payment received via NanoPay.', 'woocommerce'));

    // Add order note
    $order->add_order_note(__('NanoPay payment completed.', 'woocommerce'));

    wp_send_json_success();
}

// Block support
add_action('woocommerce_blocks_loaded', 'nanopay_register_payment_method_type');

function nanopay_register_payment_method_type() {
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    require_once plugin_dir_path(__FILE__) . 'includes/class-wc-nanopay-gateway-blocks-support.php';

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function(Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
            $payment_method_registry->register(new WC_NanoPay_Gateway_Blocks_Support());
        }
    );
}

// Declare compatibility
add_action('before_woocommerce_init', 'nanopay_cart_checkout_blocks_compatibility');
function nanopay_cart_checkout_blocks_compatibility() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}
