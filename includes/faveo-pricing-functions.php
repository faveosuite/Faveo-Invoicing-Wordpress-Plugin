<?php
function fhai_calling($atts) {
    // Set default attributes and merge with passed attributes
    $atts = shortcode_atts(
        array(
            'group' => '',        
            'country' => 'US',     
            'days' => '',         
            'class' => '',        
            'style' => '',  
        ), 
        $atts
    );

    $group_id = absint($atts['group']);
    if ($group_id <= 0) {
        return 'Invalid group ID provided.';
    }

    $api_url = get_option('fhai_api_url');
    if (empty($api_url)) {
        return 'API URL is not set. Please configure it in the plugin settings.';
    }

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

    $currency_symbol = fhai_currency_symbol($atts['country']);

    $html = '';

    $all_status_one = true;
    foreach ($products_data['products'] as $product) {
        if ($product['status'] !== "1") {
            $all_status_one = false;
            break;
        }
    }
    
    if ($all_status_one) {
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
    
    // $scrollable_class = count($products_data['products']) > 5 ? 'scrollable' : '';
    // $html .= '<div class="products-wrapper ' . $scrollable_class . '"';
    $html .= '<div class="products-wrapper" ';
    $html .= ($group_id == 11) ? ' style="margin-left:255px;"' : '';
    $html .= '>';

    foreach ($products_data['products'] as $product) {
 
    if ($all_status_one && $product['add_price'] == "0") {
        continue;
    }

    // Use the custom class attribute if provided, else fallback to default class
    $product_class = !empty($atts['class']) ? esc_attr($atts['class']) : 'product-' . esc_attr($product['id']);

    $background_color_style = '';
    if (!empty($product['pricing-background-color'])) {
        
        $background_color_style = 'style="background-color: ' . esc_attr($product['pricing-background-color']) . ';"';
    } else {
        
        $product_styles_group1 = in_array($product['name'], ['Helpdesk Freelancer', 'ServiceDesk Freelancer', 'Faveo Cloud HelpDesk', 'Support service', 'Customization', 'Faveo Upgrade', 'Install service']) ? ' product-styles-group1' : '';
        $product_styles_group2 = in_array($product['name'], ['Helpdesk Startup', 'Servicedesk Startup', 'Helpdesk Startup (Recurring)', 'ServiceDesk Startup (Recurring)']) ? ' product-styles-group2' : '';
        $product_styles_group3 = in_array($product['name'], ['Helpdesk SME', 'ServiceDesk SME', 'Helpdesk SME (Recurring)', 'ServiceDesk SME (Recurring)', 'Faveo Cloud ServiceDesk']) ? ' product-styles-group3' : '';
        $product_styles_group4 = in_array($product['name'], ['Helpdesk Enterprise', 'Helpdesk Enterprise (Recurring)', 'ServiceDesk Enterprise', 'ServiceDesk Enterprise (Recurring)']) ? ' product-styles-group4' : '';
        $product_styles_group5 = in_array($product['name'], ['Helpdesk Enterprise Pro', 'ServiceDesk Enterprise Pro']) ? ' product-styles-group5' : '';
    }

    // Determine if the toggle is enabled (monthly or yearly pricing)
     $pricing_type = isset($_GET['pricing']) ? sanitize_text_field(wp_unslash($_GET['pricing'])) : 'monthly';

     $monthly_price = ($pricing_type == 'yearly') ? floatval($product['add_price']) / 12 : floatval($product['add_price']);

    // Apply offer price if available
        $offer_price = isset($product['offer_price']) ? floatval($product['offer_price']) : 0;
        $final_price = $offer_price > 0 ? $monthly_price - ($monthly_price * ($offer_price / 100)) : $monthly_price;
        
    // Set font size for "Custom Pricing" if highlighted and group is not 11
    $custom_pricing_style = ($product['status'] == 0 ) ? 'style="font-size: 1.8rem;"' : '';

    // Apply specific width to packagess based on group
     $packagess_style = $group_id == 11 ? 'style="width: 300px;"' : ($group_id == 7 ? 'style="width: 250px;"' : '');

   $html .= '<div class="product-container ' . esc_attr($atts['style']) . $product_styles_group1 . $product_styles_group2 . $product_styles_group3 . $product_styles_group4 . $product_styles_group5 . '" data-group-id="' . esc_attr($group_id) . '" data-days="' . esc_attr($product['days']) . '"' . $product_container_style . '>'; // Add the style class and days attribute here
    
    if ($product['highlight'] == 1) {
         $html .= '<div class="popular-ribbon text-light">Most Popular</div>';
    }

    $html .= '<div class="additional-container">';
    $html .= '<div class="packagess ' . $product_class . '" ' . $packagess_style . '>';

    $html .= '<div class="product-pricing ' . ($product['highlight'] == 1 ? 'highlighted-product' : '') . '">';
    $html .= '<h1>' . esc_html($product['name']) . '</h1>';

    // Determine if "Custom Pricing" should be displayed
   if ($product['add_to_contact'] == 1 && $product['status'] == 0) {
   
    // Display "Custom Pricing" for add_to_contact is enabled (except for group 11)
    $html .= '<h2 ' . $custom_pricing_style . '>Custom Pricing</h2>';

    $custom_sales_url = get_option('fhai_custom_sales_url', 'https://www.example.com/');
     $html .= '<a href="' . esc_url($custom_sales_url) . '" class="button medium color-6">Custom Sales</a>';
} else {
    if ($offer_price > 0 && $all_status_one) {
    // Display discounted price (final_price) and original price (monthly_price) without decimals
    $html .= '<h2 data-monthly-price="' . esc_html($currency_symbol . ($atts['country'] === 'IN' ? indian_number_format($final_price) : number_format($final_price, 0))) . '"';
    if ($pricing_type == 'yearly') {
        $html .= ' data-yearly-price="' . esc_html($yearly_price) . '">';
    } else {
        $html .= '>';
    }
    $html .= esc_html($currency_symbol . ($atts['country'] === 'IN' ? indian_number_format($final_price) : number_format($final_price, 0))) . '</h2>';
    $html .= '<p class="original-price">' . esc_html($currency_symbol . ($atts['country'] === 'IN' ? indian_number_format($monthly_price) : number_format($monthly_price, 0))) . '</p>';
} else {
    // Default behavior for group 11 and products without offer_price
    $html .= '<h2 data-monthly-price="' . esc_html($currency_symbol . ($atts['country'] === 'IN' ? indian_number_format($monthly_price) : number_format($monthly_price, 0))) . '"';
    if ($pricing_type == 'yearly' && $group_id != 11) {
        $html .= ' data-yearly-price="' . esc_html($yearly_price) . '">';
        $html .= esc_html($currency_symbol . ($atts['country'] === 'IN' ? indian_number_format($yearly_price) : number_format($yearly_price, 0)));

    } else {
        $html .= '>';
        $html .= esc_html($currency_symbol . ($atts['country'] === 'IN' ? indian_number_format($monthly_price) : number_format($monthly_price, 0)));
    }
    $html .= '</h2>';
    $html .= '<p class="price_descriptionn">' . wp_kses_post($product['price_description']) . '</p>';
    $html .= '<a href="' . esc_url($product['shoping_cart_link']) . '" class="button medium color-6">Buy Now</a>';
}
}

    $html .= '</div>';

     $html .= '<div class="description">';
    // Add tooltips to each list item
    $description_with_tooltips = preg_replace_callback(
    '/<li>(.*?)<\/li>/',
    function ($matches) {
    // Return the list item with a title attribute for tooltip
    return '<li>' . $matches[1] . '</li>';
   
    },
     $product['description']
    );
    $html .= wp_kses_post($description_with_tooltips);
    $html .= '</div>';

    $price_html = '';

    if (isset($product['prices']) && is_array($product['prices'])) {
        foreach ($product['prices'] as $price) {
            if ($price['country'] === $atts['country']) {
               $price_html = '<p><strong>Price:</strong> ' . $currency_symbol . esc_html($atts['country'] === 'IN' ? indian_number_format(floatval($price['price'])) : number_format(floatval($price['price']), 0)) . ' ' . esc_html($price['currency']) . '</p>';
                break;
            }
        }
    }

    $html .= $price_html;

    $html .= '</div>'; // packagess
    $html .= '</div>'; // additional-container
    $html .= '</div>'; // product-container
}

$html .= '</div>'; // products-wrapper

    return $html;
}

// Display currency symbol based on country code
function fhai_currency_symbol($country_code) {
    switch ($country_code) {
        case 'US':
            return '$';
        case 'IN':
            return '₹';
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

function indian_number_format($number) {
    $number = (string) round($number); // Ensure rounding first
    $len = strlen($number);
    if ($len <= 3) {
        return $number;
    }
    $num = substr($number, -3); // Last 3 digits
    $remainder = substr($number, 0, $len - 3); // Remaining digits
    $formatted = '';
    while (strlen($remainder) > 2) {
        $formatted = ',' . substr($remainder, -2) . $formatted;
        $remainder = substr($remainder, 0, -2);
    }
    return $remainder . $formatted . ',' . $num;
}

