<?php
/**
 * Plugin Name: Agora Invoicing Plugin
 * Description: This plugin pulls product pricing, Description/Details, currency, and order URL from the Agora invoicing web application.
 * Version: 1.2.8
 * Author: Ladybird Web Solution Pvt Ltd
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Icon: /wp-content/plugins/faveo-agora-invoicing/assets/plugin-icon.png
 */

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin directory paths
define('FHAI_DIR', plugin_dir_path(__FILE__));
define('FHAI_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once FHAI_DIR . 'includes/admin-settings.php';
require_once FHAI_DIR . 'includes/faveo-pricing-functions.php';

// Enqueue styles and scripts
add_action('wp_enqueue_scripts', 'fhai_data_enqueue_scripts');

function fhai_data_enqueue_scripts() {
    wp_enqueue_style('api-data-style', FHAI_URL . 'css/style.css', array(), '1.0'); // Set version to 1.0
    wp_enqueue_script('api-data-script', FHAI_URL . 'js/faveo-pricing-script.js', array('jquery'), '1.0', true);
}

// Shortcode to display product data
add_shortcode('fhai', 'fhai_calling');
