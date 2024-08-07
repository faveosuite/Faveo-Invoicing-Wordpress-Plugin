<?php
function api_calling($atts) {
    // Set default attributes and merge with passed attributes
    $atts = shortcode_atts(
        array(
            'group' => '',          // Group Id
            'country' => 'IN',      // Default country
            'product_id' => '',     // Product ID
            'days' => '',           // Days filter
        ), 
        $atts
    );

    // Validate group ID
    $group_id = absint($atts['group']);
    if ($group_id <= 0) {
        return 'Invalid group ID provided.';
    }

    // Validate product ID
    $product_id = absint($atts['product_id']);
    if ($product_id <= 0) {
        return 'Invalid product ID provided.';
    }

    // Fetch the API URL from the settings
    $api_url = get_option('agora_invoicing_api_url');
    if (empty($api_url)) {
        return 'API URL is not set. Please configure it in the plugin settings.';
    }

    // Fetch products based on group ID and country
    $products_url = $api_url . '?group=' . $group_id . '&country=' . $atts['country'];
    $products_response = wp_remote_get($products_url, array('method' => 'GET'));

    if (is_wp_error($products_response)) {
        $error_message = $products_response->get_error_message();
        return "Error in displaying products: $error_message";
    }

    $products_data = json_decode(wp_remote_retrieve_body($products_response), true);

    if (empty($products_data['products'])) {
        return 'No products available for the specified group and country.';
    }

    // Find the product with the specified ID and days
    $product_found = false;
    $selected_product = array();

    foreach ($products_data['products'] as $product) {
        if ($product['id'] == $product_id && $product['days'] == $atts['days']) {
            $selected_product = $product;
            $product_found = true;
            break;
        }
    }

    if (!$product_found) {
        return 'Product not found with the specified ID and days.';
    }

    // Get the currency symbol based on the specified country
    $currency_symbol = get_currency_symbol($atts['country']);

  // HTML output for the single product
$html = '<div class="containerr">'; // Add the new container div
$html .= '<div class="packagess">';

$html .= '<div class="product-pricing">';
$html .= '<h1>' . esc_html($selected_product['name']) . '</h1>';

// Check if the product has highlight attribute set to 1
if ($selected_product['highlight'] == 1 && $group_id != 11) {
    $html .= '<h2>Custom Pricing</h2>';
} else {
    // Check group ID and days attribute for pricing calculation for faveo cloud Monthly
    if ($group_id == 11 && $atts['days'] == 366) {
        $price_per_month = floatval($selected_product['add_price']) / 12;
        $price_per_month = floor($price_per_month); // Remove decimal points
        $price_per_month = number_format($price_per_month);
        $html .= '<h2>' . $currency_symbol . esc_html($price_per_month) . ' </h2>';
    } else {
        if (!empty($selected_product['offer_price'])) {
            $offer_percentage = floatval($selected_product['offer_price']);
            $original_price = floatval($selected_product['add_price']);
            $discount_amount = ($original_price * $offer_percentage) / 100;
            $final_price = $original_price - $discount_amount;

            // Price without decimal points
            $final_price = floor($final_price);

            // Price with currency symbol and comma separation
            $final_price = number_format($final_price);

            $html .= '<h2>' . $currency_symbol . esc_html($final_price) . '</h2>';
            $html .= '<p class="strikeeprice"><s>' . $currency_symbol . esc_html(number_format(floatval($selected_product['add_price']))) . '</s></p>';
        } else {
            $html .= '<h2>' . $currency_symbol . esc_html(number_format(floatval($selected_product['add_price']))) . '</h2>';
        }
    }
}
$html .= '<p class="price_descriptionn">' . wp_kses_post($selected_product['price_description']) . '</p>';

$html .= '</div>';

$html .= '<div class="description">' . wp_kses_post($selected_product['description']) . '</div>';

// Display the Price and currency based on country
$price_html = '';

if (isset($selected_product['prices']) && is_array($selected_product['prices'])) {
    foreach ($selected_product['prices'] as $price) {
        if ($price['country'] === $atts['country']) {
            $price_html = '<p><strong>Price:</strong> ' . $currency_symbol . esc_html(number_format(floatval($price['price']))) . ' ' . esc_html($price['currency']) . '</p>';
            break;
        }
    }
}

$html .= $price_html;

$html .= '<a href="' . esc_url($selected_product['shoping_cart_link']) . '" class="button custom medium">Buy Now</a>';
$html .= '</div>'; // Close the packagess div
$html .= '</div>'; // Close the containerr div

return $html;

}

// Display currency symbol based on country code
function get_currency_symbol($country_code) {
    switch ($country_code) {
        case 'US':
            return '$'; // US Dollar
        case 'IN':
            return '₹'; // Indian Rupee
        case 'GB':
            return '£'; // British Pound
        case 'EU':
            return '€'; // Euro
        default:
            return ''; // Default to empty string if symbol is not defined
    }
}

?>
