<?php
/**
 * Plugin Name: Agora Invoicing WordPress Plugin
 * Description: This plugin pulls product pricing, Description/Details, currency and order URL from the Agora invoicing web application.
 * Version: 1.0
 * Author: Ladybird Web Solution Pvt Ltd
 */

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin directory paths
define('AGORA_INVOICING_DIR', plugin_dir_path(__FILE__));
define('AGORA_INVOICING_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once AGORA_INVOICING_DIR . 'includes/admin-settings.php';
require_once AGORA_INVOICING_DIR . 'includes/api-functions.php';

// Enqueue styles and scripts
add_action('wp_enqueue_scripts', 'api_data_enqueue_scripts');

function api_data_enqueue_scripts() {
    wp_enqueue_style('api-data-style', AGORA_INVOICING_URL . 'css/style.css');
    wp_enqueue_script('api-data-script', AGORA_INVOICING_URL . 'js/api-data-script.js', array('jquery'), '1.0', true);
}

// Shortcode to display product data
add_shortcode('api-data', 'api_calling');
