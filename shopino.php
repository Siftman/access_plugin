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

// Include the logo file
// require_once plugin_dir_path(__FILE__) . 'includes/logo.php';

if (!defined('ABSPATH')) {
    exit;
}

define('shopino_api_key', 'skljfwlkfjbfek2843#@$$(Ywkfb');

class CustomAPIEndpoints {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_custom_routes']);
    }

    public function register_custom_routes() {
        register_rest_route('api/v1', '/products', [
            'methods' => 'GET',
            'callback' => [$this, 'get_all_products'],
            'permission_callback' => [$this, 'check_api_key'],
        ]);

        register_rest_route('api/v1', '/order', [
            'methods' => 'POST',
            'callback' => [$this, 'create_order'],
            'permission_callback' => [$this, 'check_api_key'],
        ]);
    }

    public function get_all_products() {
        if (!class_exists('WooCommerce')) {
            return new WP_Error(
                'woocommerce_not_active',
                'WooCommerce is not installed or activated',
                ['status' => 500]
            );
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

    public function check_api_key() {
        $api_key = isset($_SERVER['HTTP_X_SHOPINO_API_KEY']) ? sanitize_text_field($_SERVER['HTTP_X_SHOPINO_API_KEY']) : '';
        return $api_key === shopino_api_key;
    }

    public function create_order($request) {
        if (!class_exists('WooCommerce')) {
            return new WP_Error(
                'woocommerce_not_active',
                'WooCommerce is not installed or activated',
                ['status' => 500]
            );
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
}

new CustomAPIEndpoints();