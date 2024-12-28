<?php
/**
 * Plugin Name: Shopino Plugin
 * Plugin URI: http://shopino.app
 * Description: افزونه وردپرس شاپینو
 * Version: 1.0.0
 * Author: Shopino Team
 * Author URI: https://shopino.app
 * Plugin Icon: https://shopino.app/icons/512x512.png
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SHOPINO_API_KEY', 'Gx4dR8aLpM2oNcI5kEeJ7fH6gYb4nM3');
define('SHOPINO_API_NAMESPACE', 'api/v1');

abstract class ShopinoBaseAPI {
    public function check_api_key() {
        
        $api_key = sanitize_text_field($_SERVER['HTTP_X_SHOPINO_API_KEY'] ?? '');
        error_log("API Key: " . $api_key);
        error_log("Request Headers: " . print_r($_SERVER, true));
        if ($api_key !== SHOPINO_API_KEY) {
            error_log("Invalid API Key: " . $api_key);
            return false;
        }
        return true;
    }

    public function check_woocommerce() {
        if (!class_exists('WooCommerce')) {
            return new WP_Error(
                'woocommerce_not_active',
                'WooCommerce is not installed or activated',
                ['status' => 500]
            );
        }
        return true;
    }
}

class CustomAPIEndpoints extends ShopinoBaseAPI {
    public $webhook_option_name = 'shopino_webhook_data';

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_custom_routes']);
    }

    public function register_custom_routes() {
        register_rest_route(SHOPINO_API_NAMESPACE, '/products', [
            'methods' => 'GET',
            'callback' => [$this, 'get_all_products'],
            'permission_callback' => [$this, 'check_api_key'],
        ]);

        register_rest_route(SHOPINO_API_NAMESPACE, '/order', [
            'methods' => 'POST',
            'callback' => [$this, 'create_order'],
            'permission_callback' => [$this, 'check_api_key'],
        ]);

        register_rest_route(SHOPINO_API_NAMESPACE, '/webhook-key', [
            'methods' => 'POST',
            'callback' => [$this, 'get_or_create_webhook'],
            'permission_callback' => [$this, 'check_api_key'],
        ]);
    }

    public function get_all_products() {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $page = isset($_GET['page']) ? absint($_GET['page']) : 1;
        $per_page = isset($_GET['per_page']) ? absint($_GET['per_page']) : 10; 

        $args = [
            'post_type' => 'product',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => 'publish'
        ];

        $products_query = new WP_Query($args);
        $products_data = [];

        if ($products_query->have_posts()) {
            while ($products_query->have_posts()) {
                $products_query->the_post();
                $product = wc_get_product(get_the_ID());

                $product_details = [
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'slug' => urldecode($product->get_slug()),
                    'permalink' => $product->get_permalink(),
                    'type' => $product->get_type(),
                    'price' => $product->get_price(),
                    'regular_price' => $product->get_regular_price(),
                    'sale_price' => $product->get_sale_price(),
                    'on_sale' => $product->is_on_sale(),
                    'stock_quantity' => $product->get_stock_quantity(),
                    'stock_status' => $product->get_stock_status(),
                    'short_description' => $product->get_short_description(),
                    'description' => $product->get_description(),
                    'images' => $this->get_product_images($product),
                    'categories' => $this->get_product_categories($product),
                    'attributes' => $this->get_product_attributes($product),
                ];

                $products_data[] = $product_details;
            }

            wp_reset_postdata();
        }

        return wp_json_encode([
            'total_products' => (int) $products_query->found_posts,
            'total_pages' => (int) ceil($products_query->found_posts / $per_page),
            'current_page' => $page,
            'products' => $products_data
        ], JSON_UNESCAPED_UNICODE);
    }

    private function get_product_images($product){
        $images = [];

        $main_image_id = $product->get_image_id();
        if ($main_image_id) {
            $main_image_data = wp_get_attachment_image_src($main_image_id, 'full');
            $images[] = [
                'id' => $main_image_id,
                'src' => $main_image_data[0],
                'type' => 'main'
            ];
        }
        $gallery_image_ids = $product->get_gallery_image_ids();
        foreach ($gallery_image_ids as $image_id) {
            $image_data = wp_get_attachment_image_src($image_id, 'full');
            $images[] = [
                'id' => $image_id,
                'src' => $image_data[0],
                'type' => 'gallery'
            ];
        }

        return $images;
    }
    private function get_product_categories($product) {
        $categories = [];
        $term_ids = $product->get_category_ids();
        
        foreach ($term_ids as $term_id) {
            $term = get_term($term_id, 'product_cat');
            if ($term) {
                $categories[] = [
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                ];
            }
        }
        return $categories;
    }
    private function get_product_attributes($product) {
        $attributes = [];
        $product_attributes = $product->get_attributes();
        foreach ($product_attributes as $attribute_name => $attribute) {
            $attributes[] = [
                'name' => $attribute_name,
                'options' => $attribute->get_options(),
                'visible' => $attribute->get_visible(),
                'variation' => $attribute->get_variation(),
            ];
        }
        return $attributes;
    }

    public function create_order($request) {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $params = $request->get_json_params();

        $required_fields = ['billing', 'line_items'];
        foreach ($required_fields as $field) {
            if (!isset($params[$field])) {
                return new WP_Error(
                    'missing_required_field',
                    "Missing required field: {$field}",
                    ['status' => 400]
                );
            }
        }

        try {
            $order = wc_create_order();

            foreach ($params['line_items'] as $item) {
                if (!isset($item['product_id'], $item['quantity'])) {
                    continue;
                }

                $product = wc_get_product($item['product_id']);
                if (!$product) {
                    continue;
                }

                $order->add_product(
                    $product,
                    $item['quantity'],
                    [
                        'subtotal' => isset($item['subtotal']) ? $item['subtotal'] : '',
                        'total' => isset($item['total']) ? $item['total'] : '',
                    ]
                );
            }

            $order->set_address($params['billing'], 'billing');

            if (isset($params['shipping'])) {
                $order->set_address($params['shipping'], 'shipping');
            }

            if (isset($params['payment_method'])) {
                $order->set_payment_method($params['payment_method']);
            }

            $status = isset($params['status']) ? $params['status'] : 'pending';
            $order->set_status($status);

            $order->calculate_totals();

            $order->save();

            return [
                'success' => true,
                'order_id' => $order->get_id(),
                'order_number' => $order->get_order_number(),
                'status' => $order->get_status(),
                'total' => $order->get_total()
            ];

        } catch (Exception $e) {
            return new WP_Error(
                'order_creation_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public function get_or_create_webhook() {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $params = json_decode(file_get_contents('php://input'), true);
        $secret = isset($params['secret']) ? sanitize_text_field($params['secret']) : '';

        error_log("Received secret: " . $secret);

        if (empty($secret)) {
            return new WP_Error('invalid_secret', 'Secret cannot be empty', ['status' => 400]);
        }

        try {
            $existing_webhooks = get_option($this->webhook_option_name, []);
            $webhook_ids = [];

            $topics = ['product.created', 'product.updated', 'product.deleted', 'product.restored'];

            foreach ($topics as $topic) {
                if (isset($existing_webhooks[$topic])) {
                    $webhook = wc_get_webhook($existing_webhooks[$topic]['id']);
                    if ($webhook && $webhook->get_status() === 'active') {
                        $webhook_ids[$topic] = [
                            'id' => $existing_webhooks[$topic]['id'],
                            'secret' => $existing_webhooks[$topic]['secret']
                        ];
                        continue;
                    }
                }

                $username = 'shopino_webhook_' . time();
                $user_id = wp_create_user($username, wp_generate_password(), $username . '@example.com');
                
                if (is_wp_error($user_id)) {
                    error_log('Failed to create user: ' . $user_id->get_error_message());
                    $user_id = get_current_user_id(); 
                } else {
                    $user = new WP_User($user_id);
                    $user->add_cap('read');
                    $user->add_cap('manage_woocommerce');
                    $user->add_cap('read_private_products');
                }

                $webhook = new WC_Webhook();
                $webhook->set_name('Shopino Integration Webhook for ' . $topic);
                $webhook->set_status('active');
                $webhook->set_topic($topic);
                $webhook->set_delivery_url('https://search.shopino.app/webhook/api/create-webhook');
                $webhook->set_secret($secret);
                $webhook->set_api_version('wp_api_v2');
                $webhook->set_user_id($user_id);
                
                $webhook->save();
                $webhook_id = $webhook->get_id();

                if (!$webhook_id) {
                    error_log('Failed to create webhook for ' . $topic);
                    throw new Exception('Failed to create webhook for ' . $topic);
                }

                update_post_meta($webhook_id, '_webhook_deliver_async', 'no');
                update_post_meta($webhook_id, '_webhook_pending_delivery', false);
                
                do_action('woocommerce_webhook_updated', $webhook_id, $webhook);

                $domain_name = parse_url(home_url(), PHP_URL_HOST);
                $payload = json_encode([
                    'webhook_id' => $webhook_id,
                    'domain_name' => $domain_name
                ]);
                $delivery_id = time();
                
                $args = [
                    'method' => 'POST',
                    'timeout' => 60,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking' => true,
                    'body' => $payload,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'X-WC-Webhook-Source' => home_url('/'),
                        'X-WC-Webhook-Topic' => $topic,
                        'X-WC-Webhook-Resource' => 'product',
                        'X-WC-Webhook-Event' => 'ping',
                        'X-WC-Webhook-Signature' => base64_encode(hash_hmac('sha256', $payload, $secret, true)),
                        'X-WC-Webhook-ID' => $webhook_id,
                        'X-WC-Webhook-Delivery-ID' => $delivery_id,
                    ],
                ];
                
                $response = wp_remote_post($webhook->get_delivery_url(), $args);
                
                if (is_wp_error($response)) {
                    error_log("Initial ping failed for webhook ID " . $webhook_id . ": " . $response->get_error_message());
                    $webhook->delete(true);
                    continue;
                } 
                
                $response_code = wp_remote_retrieve_response_code($response);
                error_log("Initial ping response for webhook ID " . $webhook_id . ": " . $response_code);
                error_log("Initial ping response body: " . wp_remote_retrieve_body($response));

                if ($response_code !== 200) {
                    error_log("Webhook ping failed with status " . $response_code . ". Not creating webhook.");
                    return new WP_Error(
                        'webhook_creation_failed',
                        'Webhook creation failed due to server error.',
                        ['status' => 502] 
                    );
                }

                $delivery_args = [
                    'webhook_id' => $webhook_id,
                    'arg' => [] 
                ];
                wc_webhook_process_delivery($webhook_id, $delivery_args);
                
                $webhook_ids[$topic] = [
                    'id' => $webhook_id,
                    'secret' => $secret,
                    'user_id' => $user_id,
                    'ping_response' => $response_code
                ];

                error_log("Created webhook ID: " . $webhook_id . " for topic: " . $topic);
                error_log("Webhook user ID: " . $user_id);
                error_log("Webhook full data: " . print_r($webhook->get_data(), true));
            }
            
            update_option($this->webhook_option_name, $webhook_ids);

            $response = [
                'success' => true,
                'webhooks' => $webhook_ids
            ];
            error_log("Response: " . json_encode($response));
            return $response;

        } catch (Exception $e) {
            return new WP_Error(
                'webhook_creation_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
}

new CustomAPIEndpoints();