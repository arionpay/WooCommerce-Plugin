<?php
/**
 * Plugin Name: ArionPay Crypto (Universal v18)
 * Description: Fixes Post-Payment UI. Shows "Payment Successful" message instead of reloading the form.
 * Version: 18.0.2
 * Author: ArionPay
 * Author URI: https://arionpay.com
 * Text Domain: arionpay
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ✅ HELPER: Load Logos from Checkout Server
// ✅ HELPER: Load Logos Locally (assets/logos/*.png) with safe fallback
function arionpay_get_logo($chain) {
    $c = strtoupper($chain);
    $ticker = strtolower($c);

    if (strpos($c, 'BTC') !== false) $ticker = 'btc';
    else if (strpos($c, 'ETH') !== false) $ticker = 'eth';
    else if (strpos($c, 'TRX') !== false) $ticker = 'trx';
    else if (strpos($c, 'LTC') !== false) $ticker = 'ltc';
    else if (strpos($c, 'DOGE') !== false) $ticker = 'doge';
    else if (strpos($c, 'BSC') !== false || $c === 'BNB') $ticker = 'bnb';
    else if (strpos($c, 'MATIC') !== false) $ticker = 'matic';
    else if (strpos($c, 'SOL') !== false) $ticker = 'sol';
    else if (strpos($c, 'BCH') !== false) $ticker = 'bch';
    else if (strpos($c, 'DASH') !== false) $ticker = 'dash';
    else if (strpos($c, 'ZEC') !== false) $ticker = 'zec';
    else if (strpos($c, 'XRP') !== false) $ticker = 'xrp';
    else if (strpos($c, 'ADA') !== false) $ticker = 'ada';
    else if (strpos($c, 'ETC') !== false) $ticker = 'etc';
    else if ($c === 'BASE') $ticker = 'base';
    else if (strpos($c, 'AVAX') !== false) $ticker = 'avax';
    else if (strpos($c, 'USDT') !== false) $ticker = 'usdt';
    else if (strpos($c, 'USDC') !== false) $ticker = 'usdc';

    // ✅ Local logos: /wp-content/plugins/arionpay-woocommerce/assets/logos/{ticker}.png
    static $cache = [];
    if (isset($cache[$ticker])) return $cache[$ticker];

    $rel = 'assets/logos/' . $ticker . '.png';
    $abs = plugin_dir_path(__FILE__) . $rel;

    if (file_exists($abs)) {
        $cache[$ticker] = plugins_url($rel, __FILE__);
        return $cache[$ticker];
    }

    // ✅ Fallback (only if missing locally)
    $cache[$ticker] = "https://checkout.arionpay.com/logos/{$ticker}.png";
    return $cache[$ticker];
}

/**
 * ✅ Enqueue checkout UI assets only on ArionPay thank-you page.
 * This does NOT change payment logic—only how CSS/JS is loaded.
 */
function arionpay_enqueue_checkout_assets() {
    if ( ! function_exists('is_order_received_page') || ! is_order_received_page() ) return;

    $order_id = absint( get_query_var('order-received') );
    if ( ! $order_id ) return;

    $order = wc_get_order($order_id);
    if ( ! $order ) return;

    // Only for ArionPay gateway
    if ( $order->get_payment_method() !== 'arionpay' ) return;

    // Only in white-label mode (same as your UI rendering)
    $settings = get_option('woocommerce_arionpay_settings', []);
    if ( empty($settings['white_label']) || $settings['white_label'] !== 'yes' ) return;

    wp_enqueue_style(
        'arionpay-checkout',
        plugins_url('assets/arionpay-checkout.css', __FILE__),
        [],
        '18.0.2'
    );

    wp_enqueue_script(
        'arionpay-checkout',
        plugins_url('assets/arionpay-checkout.js', __FILE__),
        [],
        '18.0.2',
        true
    );

    // Pass dynamic data to JS (invoice id + API base)
    $invoice_json = $order->get_meta('_arionpay_invoice_json');
    $invoice = $invoice_json ? json_decode($invoice_json, true) : null;
    $inv_id = is_array($invoice) && !empty($invoice['id']) ? (string)$invoice['id'] : '';

    wp_localize_script('arionpay-checkout', 'ARIONPAY_UI', [
        'invId' => $inv_id,
        'base'  => 'https://api.arionpay.com',
    ]);

    // FontAwesome (same as you had inline)
    wp_enqueue_style(
        'arionpay-fa',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
        [],
        '6.0.0'
    );
}
add_action('wp_enqueue_scripts', 'arionpay_enqueue_checkout_assets', 20);

add_action('plugins_loaded', 'init_arionpay_gateway');
function init_arionpay_gateway() {
    if (!class_exists('WC_Payment_Gateway')) return;

    class WC_ArionPay_Gateway extends WC_Payment_Gateway {
        public function __construct() {
            $this->id = 'arionpay';
            $this->method_title = 'ArionPay Crypto';
            $this->method_description = 'Accept Crypto via ArionPay.';
            $this->has_fields = false;

            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->api_key = $this->get_option('api_key');
            $this->api_secret = $this->get_option('api_secret');
            $this->store_id = $this->get_option('store_id');
            $this->white_label = $this->get_option('white_label');

            $this->api_url = 'https://api.arionpay.com';

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
            add_action('woocommerce_api_arionpay_webhook', [$this, 'webhook_handler']);
            add_action('woocommerce_thankyou_' . $this->id, [$this, 'thankyou_page_display']);
        }

        public function init_form_fields() {
            $this->form_fields = [
                'enabled' => ['title' => 'Enable', 'type' => 'checkbox', 'label' => 'Yes', 'default' => 'yes'],
                'title' => ['title' => 'Title', 'type' => 'text', 'default' => 'Pay with Crypto'],
                'description' => ['title' => 'Desc', 'type' => 'textarea', 'default' => 'Secure Crypto Payment'],
                'api_key' => ['title' => 'API Key', 'type' => 'text'],
                'api_secret' => ['title' => 'Secret Key', 'type' => 'password'],
                'store_id' => ['title' => 'Store ID', 'type' => 'text'],
                'white_label' => [
                    'title'   => 'Mode Selection',
                    'type'    => 'checkbox',
                    'label'   => 'Enable White Label (On-Site Payment)',
                    'default' => 'yes',
                    'description' => 'CHECKED = Customer stays on your site. UNCHECKED = Redirects to ArionPay Checkout.'
                ],
            ];
        }

        public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            $is_white_label = ($this->white_label === 'yes');

            $returnUrl = $this->get_return_url($order);
            $callbackUrl = WC()->api_request_url('arionpay_webhook');

            $payload = [
                'storeId' => $this->store_id,
                'amount' => $order->get_total(),
                'currency' => $order->get_currency(),
                'orderId' => (string)$order_id,
                'email' => $order->get_billing_email(),
                'whiteLabel' => $is_white_label,
                'returnUrl' => $returnUrl,
                'callbackUrl' => $callbackUrl
            ];

            $response = wp_remote_post($this->api_url . '/api/v1/invoices', [
                'method' => 'POST',
                'body' => json_encode($payload),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-api-key' => $this->api_key,
                    'x-signature' => hash_hmac('sha256', json_encode($payload), $this->api_secret)
                ],
                'timeout' => 45,
                'sslverify' => false
            ]);

            if (is_wp_error($response)) {
                wc_add_notice('Connection Error: ' . $response->get_error_message(), 'error');
                return;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            $invoiceData = $body['data'] ?? $body['invoice'] ?? null;

            if (!$invoiceData) {
                $err = $body['error'] ?? 'Unknown Error';
                wc_add_notice('API Error: ' . $err, 'error');
                return;
            }

            $order->update_meta_data('_arionpay_invoice_json', json_encode($invoiceData));
            $order->save();

            // MODE A: WHITE LABEL
            if ($is_white_label) {
                return [
                    'result' => 'success',
                    'redirect' => $returnUrl
                ];
            }

            // MODE B: REDIRECT
            $invId = $invoiceData['id'];
            $redirectUrl = "https://checkout.arionpay.com/pay/{$invId}";

            return [
                'result' => 'success',
                'redirect' => $redirectUrl
            ];
        }

        public function webhook_handler() {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            $sig = $_SERVER['HTTP_X_SIGNATURE'] ?? '';

            if (hash_equals(hash_hmac('sha256', $json, $this->api_secret), $sig)) {
                $order = wc_get_order($data['orderId']);

                if ($order) {
                    if ($data['status'] === 'paid' || $data['status'] === 'confirmed') {
                        $order->payment_complete();
                        $order->add_order_note('✅ ArionPay: Payment Confirmed. Amount: ' . $data['amountCrypto'] . ' ' . $data['asset']);
                    } elseif ($data['status'] === 'paid_partial') {
                        $order->add_order_note('⚠️ ArionPay: Partial Payment Detected.');
                    }
                }
            }
            status_header(200); exit('OK');
        }

        public function thankyou_page_display($order_id) {
            if ($this->white_label !== 'yes') return;

            $order = wc_get_order($order_id);

            // ✅ FIX: If order is already PAID, show Success Message instead of Form!
            if ($order->has_status(array('processing', 'completed'))) {
                echo '
                <div class="arionpay-success-box">
                    <div class="arionpay-success-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <h3 class="arionpay-success-title">Payment Successful!</h3>
                    <p class="arionpay-success-text">
                        Your crypto payment has been confirmed.<br>Thank you for your order.
                    </p>
                </div>';
                return;
            }

            $json = $order->get_meta('_arionpay_invoice_json');
            if (!$json) return;

            $data = json_decode($json, true);

            $avail = $data['available_currencies'] ?? [];
            if (empty($avail) || (count($avail) === 1 && $avail[0] === 'USD')) {
                $avail = ['BTC', 'ETH', 'LTC', 'TRX', 'SOL', 'USDT_TRC20', 'USDC_SOL'];
            }
            $avail = array_filter($avail, function($c) { return !in_array(strtoupper($c), ['USD', 'EUR', 'GBP']); });
            $avail = array_values($avail);

            $listHtml = '';
            foreach ($avail as $c) {
                $logo = arionpay_get_logo($c);
                $listHtml .= "<div class='pdex-option' data-chain='$c' data-logo='$logo'><img src='$logo' alt='$c'><span>$c</span></div>";
            }

            $trigger = '<span>-- Select Coin --</span><i class="fas fa-chevron-down"></i>';

            echo "
            <div id='pdex-box'>
                <div class='pdex-head'><i class='fas fa-lock' style='color:#10b981; margin-right:8px;'></i> Secure Payment</div>
                <div class='pdex-body'>
                    <div class='pdex-sel' id='pdex-sel-btn'>
                        <div id='pdex-trig'>$trigger</div>
                    </div>
                    <div class='pdex-opts' id='pdex-menu'>$listHtml</div>

                    <div id='pdex-loader' style='display:none; text-align:center;'>
                        <i class='fas fa-circle-notch fa-spin fa-3x' style='color:#0ea5e9'></i><br>
                        <span style='color:#64748b; font-size:13px; margin-top:10px; display:block; font-weight:600;'>Fetching Address...</span>
                    </div>

                    <div id='pdex-prompt' style='display:block; text-align:center; padding:30px 10px; color:#64748b;'>
                        <i class='fas fa-hand-pointer' style='font-size:24px; margin-bottom:10px; opacity:0.5;'></i><br>
                        Please select a cryptocurrency above to generate a wallet address.
                    </div>

                    <div id='pdex-content' style='display:none;'>
                        <div style='text-align:center; margin:20px 0;'>
                            <div style='display:inline-block; padding:10px; border:1px solid #e2e8f0; border-radius:12px;'>
                                <img id='pdex-qr' src='' style='width:180px; height:180px; border-radius:4px;'>
                            </div>
                        </div>
                        <div style='font-size:22px; font-weight:800; text-align:center; color:#1e293b;'>
                            <span id='pdex-amt'>---</span> <span id='pdex-cur' style='color:#64748b; font-size:16px;'></span>
                        </div>
                        <div style='margin-top:20px;'>
                            <small style='color:#64748b; font-weight:700; font-size:11px; text-transform:uppercase; letter-spacing:0.5px;'>Send to Address:</small>
                            <div class='pdex-copy' id='pdex-copy-btn'>
                                <span id='pdex-addr'>---</span> <i class='fas fa-copy' style='color:#94a3b8;'></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='pdex-stat' id='pdex-stat'>
                    <i class='fas fa-sync fa-spin' style='margin-right:5px; color:#0ea5e9;'></i> Awaiting payment...
                </div>
            </div>
            ";
        }
    }
}

add_filter('woocommerce_payment_gateways', function($methods) { $methods[] = 'WC_ArionPay_Gateway'; return $methods; });

add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

add_action('woocommerce_blocks_loaded', function() {
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) return;
    class WC_ArionPay_Blocks extends Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType {
        protected $name = 'arionpay';
        public function initialize() { $this->settings = get_option('woocommerce_arionpay_settings', []); }
        public function is_active() { return !empty($this->settings['enabled']) && 'yes' === $this->settings['enabled']; }
        public function get_payment_method_script_handles() {
            // Keep your existing file
            wp_register_script('arionpay-blocks', plugins_url('assets/block.js', __FILE__), ['wc-blocks-registry', 'wc-settings', 'wp-element'], null, true);
            return ['arionpay-blocks'];
        }
        public function get_payment_method_data() {
            return ['title' => $this->settings['title'] ?? 'ArionPay', 'description' => $this->settings['description'] ?? 'Pay with Crypto'];
        }
    }
    add_action('woocommerce_blocks_payment_method_type_registration', function($registry) {
        $registry->register(new WC_ArionPay_Blocks());
    });
});
