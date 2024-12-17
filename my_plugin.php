<?php
/*
Plugin Name: Shopino Plugin
Plugin URI: http://shopino.app
Description: افزونه وردپرس شاپینو
Author: Hossein
Author URI: http://github.com/siftman
*/ 

if (!defined('ABSPATH')){
    exit;
}

function my_plugin_footer_text(){
    echo '<p>Shopino WordPress Plugin';
}

add_action('wp_footer', 'my_plugin_footer_text')

function my_plugin_activation_notice(){
    ?>
    <div class="notice notice-succes is-dimissible">
        Thanks for using Shopino
    </div>
    <?php
}
