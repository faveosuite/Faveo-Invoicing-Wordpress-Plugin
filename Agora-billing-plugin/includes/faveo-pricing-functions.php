<?php
function faveopricing_calling($atts) {
    // Set default attributes and merge with passed attributes
    $atts = shortcode_atts(
        array(
            'group' => '',          // Group Id
            'country' => 'US',      // Default country
            'days' => '',           // Days filter
            'class' => '',          // Custom class for styling
            'style' => '',          // New style class for specific product
        ), 
        $atts
    );

    // Validate group ID
    $group_id = absint($atts['group']);
    if ($group_id <= 0) {
        return 'Invalid group ID provided.';
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

    // Get the currency symbol based on the specified country
    $currency_symbol = fabwp_currency_symbol($atts['country']);

    // HTML output for all products in the group
    $html = '';

    // Only add JavaScript for toggle functionality if group is 11
    if ($group_id == 11) {
        $html .= '<center>';
        $html .= '<div class="toggle-wrapper">';
        $html .= '<div class="toggle-labels">';
        $html .= '<span class="toggle-label monthly">Monthly</span>';
        $html .= '<label class="toggle-switch">';
        $html .= '<input type="checkbox" id="pricing-toggle">';
        $html .= '<span class="slider"></span>';
        $html .= '</label>';
        $html .= '<span class="toggle-label yearly">Yearly</span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</center>';
    }

    $html .= '<div class="products-wrapper"';

if ($group_id == 11) {
    $html .= ' style="margin-left:255px;"';
} elseif ($group_id == 7) {
    $html .= ' style="margin-left: 20px;" ';
}

$html .= '>'; // Wrapper to apply flexbox

foreach ($products_data['products'] as $product) {
    // Special handling for group 11
    if ($group_id == 11 && ($product['add_price'] == "0")) {
        continue; // Skip freelancer products
    }

    // Use the custom class attribute if provided, else fallback to default class
    $product_class = !empty($atts['class']) ? esc_attr($atts['class']) : 'product-' . esc_attr($product['id']);

    //Classes for Product's background colors-  needs to be removed after recieving the colors from billing
    $product_styles_group1 = in_array($product['name'], ['Helpdesk Freelancer', 'ServiceDesk Freelancer', 'Faveo Cloud HelpDesk', 'Support service', 'Customization', 'Faveo Upgrade', 'Install service']) ? ' product-styles-group1' : '';
    $product_styles_group2 = in_array($product['name'], ['Helpdesk Startup', 'Servicedesk Startup', 'Helpdesk Startup (Recurring)', 'ServiceDesk Startup (Recurring)']) ? ' product-styles-group2' : '';
    $product_styles_group3 = in_array($product['name'], ['Helpdesk SME', 'ServiceDesk SME', 'Helpdesk SME (Recurring)', 'ServiceDesk SME (Recurring)', 'Faveo Cloud ServiceDesk']) ? ' product-styles-group3' : '';
    $product_styles_group4 = in_array($product['name'], ['Helpdesk Enterprise', 'Helpdesk Enterprise (Recurring)', 'ServiceDesk Enterprise', 'ServiceDesk Enterprise (Recurring)']) ? ' product-styles-group4' : '';
    $product_styles_group5 = in_array($product['name'], ['Helpdesk Enterprise Pro', 'ServiceDesk Enterprise Pro']) ? ' product-styles-group5' : '';

    // Determine if the toggle is enabled (monthly or yearly pricing)
   $pricing_type = isset($_GET['pricing']) ? sanitize_text_field(wp_unslash($_GET['pricing'])) : 'monthly';

    // Calculate the monthly price based on pricing type
    if ($pricing_type == 'yearly') {
        // Calculate monthly price from yearly price
        $yearly_price = floatval($product['add_price']);
        $monthly_price = $yearly_price / 12;
    } else {
        // Use the default monthly price
        $monthly_price = floatval($product['add_price']);
    }

    // Set font size for "Custom Pricing" if highlighted and group is not 11
    $custom_pricing_style = ($product['highlight'] == 1 && $group_id != 11) ? 'style="font-size: 2.0rem;"' : '';

    // Apply specific width to packagess based on group
    $packagess_style = '';
    if ($group_id == 11) {
        $packagess_style = 'style="width: 300px;"';
    } elseif ($group_id == 7) {
        $packagess_style = 'style="width: 250px;"';
    }

    // Display the product container with additional class for background colors
    $html .= '<div class="product-container ' . esc_attr($atts['style']) . $product_styles_group1 . $product_styles_group2 . $product_styles_group3 . $product_styles_group4 . $product_styles_group5 . '" data-group-id="' . esc_attr($group_id) . '" data-days="' . esc_attr($product['days']) . '">'; // Add the style class and days attribute here

    // Add a ribbon for group 3 and group 4 products
    if (!empty($product_styles_group3)) {
        // $html .= '<div class="ribbon"><span>Special</span></div>';
         $html .= '<div class="popular-ribbon text-light">Most Popular</div>';
    }

    $html .= '<div class="additional-container">';
    $html .= '<div class="packagess ' . $product_class . '" ' . $packagess_style . '>';

    $html .= '<div class="product-pricing ' . ($product['highlight'] == 1 ? 'highlighted-product' : '') . '">';
    $html .= '<h1>' . esc_html($product['name']) . '</h1>';
        // $html .= '<h1><span class="tooltip" data-tooltip="' . esc_attr($product['add_price']) . '">' . esc_html($product['name']) . '</span></h1>';

    // Determine if "Custom Pricing" should be displayed
    if ($product['highlight'] == 1 && $group_id != 11) {
        $html .= '<h2 ' . $custom_pricing_style . '>Custom Pricing</h2>';
    } else {
        // Store both monthly and yearly prices for JavaScript to use
        $html .= '<h2 data-monthly-price="' . $currency_symbol . esc_html(number_format($monthly_price)) . '"';
        if ($pricing_type == 'yearly' && $group_id != 11) {
            $html .= ' data-yearly-price="' . esc_html($yearly_price) . '">';
            $html .= $currency_symbol . esc_html(number_format($monthly_price));
        } else {
            $html .= '>';
            $html .= $currency_symbol . esc_html(number_format($monthly_price));
        }
        $html .= '</h2>';
    }

    // Display price_description only if the pricing is not "Custom Pricing"
    if (!($product['highlight'] == 1 && $group_id != 11)) {
        $html .= '<p class="price_descriptionn">' . wp_kses_post($product['price_description']) . '</p>';
    }

    $html .= '<a href="' . esc_url($product['shoping_cart_link']) . '" class="button medium color-6">Buy Now</a>';

    $html .= '</div>';

    // $html .= '<div class="description">' . wp_kses_post($product['description']) . '</div>';
     $html .= '<div class="description">';
    // Add tooltips to each list item
    $description_with_tooltips = preg_replace_callback(
    '/<li>(.*?)<\/li>/',
    function ($matches) {
    // Return the list item with a title attribute for tooltip
    return '<li>' . $matches[1] . '</li>';
    //  return '<li title="hello">' . $matches[1] . '</li>';
    },
     $product['description']
    );
    $html .= wp_kses_post($description_with_tooltips);
    $html .= '</div>';

    
    // Display the Price and currency based on country
    $price_html = '';

    if (isset($product['prices']) && is_array($product['prices'])) {
        foreach ($product['prices'] as $price) {
            if ($price['country'] === $atts['country']) {
                $price_html = '<p><strong>Price:</strong> ' . $currency_symbol . esc_html(number_format(floatval($price['price']))) . ' ' . esc_html($price['currency']) . '</p>';
                break;
            }
        }
    }

    $html .= $price_html;

    $html .= '</div>'; // Close the packagess div
    $html .= '</div>'; // Close the additional-container div
    $html .= '</div>'; // Close the product-container div
}

$html .= '</div>'; // Close the products-wrapper div

if ($group_id != 11 && $group_id != 7) {
    $html .= '<style>';
    $html .= '.product-container li { margin-top: 15px; }';
    $html .= '</style>';
}

    return $html;
}

// Display currency symbol based on country code
function fabwp_currency_symbol($country_code) {
    switch ($country_code) {
        case 'US':
            return '$'; // US Dollar
        case 'IN':
            return '₹'; // Indian Rupee
        case 'ZW':
            return 'Z$'; // Zimbabwe Real
         case 'ZW':
            return 'Z$';
         case 'AF':
            return '؋';
         case 'AL':
            return 'L';
         case 'DZ':
            return 'د.ج';
         case 'AD':
            return '€';
         case 'AO':
            return 'Kz';
         case 'AR':
            return '$';
         case 'AM':
            return '֏';
         case 'AU':
            return '$';
         case 'AT':
            return '€';
         case 'AZ':
            return '₼';
         case 'BS':
            return '$';
         case 'BH':
            return '.د.ب';
         case 'BD':
            return '৳';
        case 'BB':
            return '$';
        case 'BY':
            return 'Br';
        case 'BE':
            return '€';
        case 'BZ':
            return '$';
        case 'BJ':
            return 'Fr';
        case 'BT':
            return 'Nu.';
        case 'BO':
            return 'Bs.';
        case 'BA':
            return 'KM';
        case 'BW':
            return 'P';
        case 'BN':
            return '$';
        case 'BG':
            return 'лв';
        case 'BF':
            return 'Fr';
        case 'BI':
            return 'Fr';
        case 'KH':
            return '៛';
        case 'CM':
            return 'Fr';
        case 'CA':
            return '$';
        case 'CV':
            return '$';
        case 'CF':
            return 'Fr';
        case 'TD':
            return '$';
         case 'CL':
            return 'Fr';
         case 'CN':
            return '¥';
         case 'CO':
            return '$';
         case 'KM':
            return 'Fr';
         case 'CG':
            return 'Fr';
         case 'CD':
            return 'Fr';
         case 'CR':
            return '₡';
         case 'HR':
            return 'kn';
         case 'CU':
            return '$';
         case 'CY':
            return '€';
         case 'CZ':
            return 'Kč';
         case 'DK':
            return 'kr';
         case 'DJ':
            return 'Fr';
         case 'DM':
            return '$';
         case 'DO':
            return '$';
         case 'EC':
            return '$';
         case 'EG':
            return '£';
         case 'SV':
            return '$';
         case 'GQ':
            return 'Fr';
        case 'ER':
            return 'Nfk';
            case 'EE':
            return '€';
            case 'SZ':
            return 'L';
            case 'ET':
            return 'Br';
            case 'FJ':
            return '$';
            case 'FI':
            return '€';
            case 'FR':
            return '€';
            case 'GA':
            return 'Fr';
            case 'GM':
            return 'D';
            case 'GE':
            return '₾';
            case 'DE':
            return '€';
            case 'GH':
            return '₵';
            case 'GR':
            return '€';
            case 'GD':
            return '$';
            case 'GT':
            return 'Q';
            case 'GN':
            return 'Fr';
            case 'GW':
            return 'Fr';
            case 'GY':
            return '$';
            case 'HT':
            return 'G';
            case 'HN':
            return 'L';
            case 'HU':
            return 'Ft';
            case 'IS':
            return 'kr';
            case 'ID':
            return 'Rp';
            case 'IR':
            return '﷼';
            case 'IQ':
            return 'ع.د';
            case 'IE':
            return '€';
            case 'IL':
            return '₪';
            case 'IT':
            return '€';
            case 'JM':
            return '$';
            case 'JP':
            return '¥';
            case 'JO':
            return 'د.ا';
            case 'KZ':
            return '₸';
        
        default:
            return '$'; // Default to Dollar
    }
}

?>