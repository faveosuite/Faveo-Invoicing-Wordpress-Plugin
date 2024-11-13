<?php
/**
 * Plugin Name: Faveo Agora Invoicing
 * Description: This plugin pulls product pricing, Description/Details, currency, and order URL from the Agora invoicing web application.
 * Version: 1.0
 * Author: Ladybird Web Solution Pvt Ltd
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
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
require_once AGORA_INVOICING_DIR . 'includes/faveo-pricing-functions.php';

// Enqueue styles and scripts
add_action('wp_enqueue_scripts', 'api_data_enqueue_scripts');

function api_data_enqueue_scripts() {
    wp_enqueue_style('api-data-style', AGORA_INVOICING_URL . 'css/style.css', array(), '1.0'); // Set version to 1.0
    wp_enqueue_script('api-data-script', AGORA_INVOICING_URL . 'js/faveo-pricing-script.js', array('jquery'), '1.0', true);
}

// Shortcode to display product data
add_shortcode('faveopricing', 'faveopricing_calling');
