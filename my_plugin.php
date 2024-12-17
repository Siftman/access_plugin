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
        register_rest_route('custome/v1', '/hello', [
            'methods' => 'GET',
            'callback' => [$this, 'hello_endpoint'],
            'permission_callback' => '__return_true'
        ]);
    }

    public function hello_endpoint() {
        return [
            'message' => 'Hello from Hossein',
            'timestamp' => current_time('mysql')
        ];
    }

    public function check_api_key() {
        $api_key = isset($_SERVER['HTTP_X_API_KEY']) ? 
                   sanitize_text_field($_SERVER['HTTP_X_API_KEY']) : 
                   '';
        return $api_key === '###@@@123abc';
    }
}

new CustomAPIEndpoints();