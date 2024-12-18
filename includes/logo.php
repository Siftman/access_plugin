<?php
function shopino_plugin_icon() {
    // Path to the logo image
    $logo_url = plugin_dir_url(__FILE__) . '../assets/logo.png';

    // Add the logo to the plugin icon
    echo '<style>
        .plugin-icon {
            background-image: url(' . esc_url($logo_url) . ');
            background-size: contain;
            background-repeat: no-repeat;
            width: 100px; /* Adjust width as needed */
            height: 100px; /* Adjust height as needed */
        }
    </style>';
}
add_action('admin_head', 'shopino_plugin_icon'); 