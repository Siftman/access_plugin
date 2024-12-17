<?php
/**
 * Plugin Name: Shopino Plugin
 * Plugin URI: http://shopino.app
 * Description: افزونه وردپرس شاپینو
 * Version: 1.0.0
 * Author: Hossein
 * Author URI: http://github.com/siftman
 */


if (!defined('ABSPATH')) {
    exit;
}

class CustomAPIEndpoints {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_custom_routes']);
    }

    public function register_custom_routes() {
        register_rest_route('custome/v1', '/products', [
            'methods' => 'GET',
            'callback' => [$this, 'get_all_products'],
            'permission_callback' => '__return_true'
        ]);
    }

    public function get_all_products() {
        if (!class_exists('WooCommerce')){
            return new WP_Error(
                'woocommerce_not_active',
                'woocommerce is not installed or activated',
                ['status' => 500]
            );
        }

        $args = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'status' => 'publish'
        ];

        $products_query = new WP_Query($args);
        $products_data = [];

        if ($products_query->have_posts()){
            while ($products_query->have_posts()){

                $products_query->the_post();
                $product = wc_get_product(get_the_ID());

                $product_details = [
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'slug' => $product->get_slug(),
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
                    'attributes' => $this->get_product_attributes($product),];

                $products_data[] = $product_details;
            }

            wp_reset_postdata();
        }

        return [
            'total_products' => count($products_data),
            'products' => $products_data
        ];
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
        $api_key = isset($_SERVER['HTTP_X_API_KEY']) ? 
                   sanitize_text_field($_SERVER['HTTP_X_API_KEY']) : 
                   '';
        return $api_key === '###@@@123abc';
    }
}

new CustomAPIEndpoints();