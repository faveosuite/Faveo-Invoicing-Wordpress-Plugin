<?php
// Detect user IP
function fhai_get_user_ip() {
    $ip = '';

    if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) && ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
        $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
    } elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        $ipList = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
        $ip     = trim( $ipList[0] );
    } elseif ( isset( $_SERVER['REMOTE_ADDR'] ) && ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
        $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
    }

    return $ip;
}


// Get country from IP using ip-api.com
function fhai_get_country_from_ip($ip) {
    $response = wp_remote_get("http://ip-api.com/json/{$ip}?fields=countryCode");
    if (is_wp_error($response)) {
        return 'US'; // fallback
    }
    $data = json_decode(wp_remote_retrieve_body($response), true);
    return !empty($data['countryCode']) ? strtoupper($data['countryCode']) : 'US';
}

// Currency symbol mapping by code
function fhai_currency_symbol_by_code($currency_code) {
    $map = array(
        'USD' => '$', 'INR' => '₹', 'GBP' => '£', 'EUR' => '€',
        'CAD' => 'C$', 'AUD' => 'A$', 'NZD' => 'NZ$', 'JPY' => '¥',
        'CNY' => '¥', 'SGD' => 'S$', 'HKD' => 'HK$', 'KRW' => '₩',
        'RUB' => '₽', 'BRL' => 'R$', 'ZAR' => 'R', 'MXN' => 'MX$',
        'CHF' => 'CHF', 'SEK' => 'kr', 'NOK' => 'kr', 'DKK' => 'kr',
        'PLN' => 'zł', 'CZK' => 'Kč', 'HUF' => 'Ft', 'TRY' => '₺',
        'SAR' => '﷼', 'AED' => 'د.إ', 'ILS' => '₪', 'THB' => '฿',
        'IDR' => 'Rp', 'MYR' => 'RM', 'PHP' => '₱', 'VND' => '₫',
        'NGN' => '₦'
    );
    return isset($map[$currency_code]) ? $map[$currency_code] : '$';
}

// Currency symbol mapping by country
function fhai_currency_symbol($country_code) {
    $map = array(
        'US' => '$', 'IN' => '₹', 'GB' => '£', 'EU' => '€', 'CA' => 'C$',
        'AU' => 'A$', 'NZ' => 'NZ$', 'JP' => '¥', 'CN' => '¥', 'SG' => 'S$',
        'HK' => 'HK$', 'KR' => '₩', 'RU' => '₽', 'BR' => 'R$', 'ZA' => 'R',
        'MX' => 'MX$', 'CH' => 'CHF', 'SE' => 'kr', 'NO' => 'kr', 'DK' => 'kr',
        'PL' => 'zł', 'CZ' => 'Kč', 'HU' => 'Ft', 'TR' => '₺', 'SA' => '﷼',
        'AE' => 'د.إ', 'IL' => '₪', 'TH' => '฿', 'ID' => 'Rp', 'MY' => 'RM',
        'PH' => '₱', 'VN' => '₫', 'NG' => '₦'
    );
    return isset($map[$country_code]) ? $map[$country_code] : '$';
}

// Format Indian number
function indian_number_format($number) {
    $number = (string) round($number);
    $len = strlen($number);
    if ($len <= 3) return $number;
    $num = substr($number, -3);
    $remainder = substr($number, 0, $len - 3);
    $formatted = '';
    while (strlen($remainder) > 2) {
        $formatted = ',' . substr($remainder, -2) . $formatted;
        $remainder = substr($remainder, 0, -2);
    }
    return $remainder . $formatted . ',' . $num;
}

// Shortcode
function fhai_calling($atts) {
    $atts = shortcode_atts(
        array(
            'group'   => '',
            'country' => '',
            'days'    => '',
            'class'   => '',
            'style'   => '',
        ),
        $atts
    );

    $group_id = absint($atts['group']);
    if ($group_id <= 0) return 'Invalid group ID.';

    // Detect country
    $user_ip = fhai_get_user_ip();
    $detected_country = fhai_get_country_from_ip($user_ip);
    $country_code = !empty($atts['country']) ? strtoupper($atts['country']) : $detected_country;

    $api_url = get_option('fhai_api_url');
    if (empty($api_url)) return 'API URL is not set. Please configure it in the plugin settings.';

    // Fetch products
    $products_url = $api_url . '?group=' . $group_id . '&country=' . $country_code;
    $products_response = wp_remote_get($products_url, array('method' => 'GET'));
    $products_data = array();
    if (!is_wp_error($products_response)) {
        $products_data = json_decode(wp_remote_retrieve_body($products_response), true);
    }

    if (empty($products_data['products'])) return 'No products available for this group.';

    $html = '';

    // Show toggle if all products have status 1
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
        $html .= '<input type="checkbox" class="pricing-toggle" data-group="' . esc_attr($group_id) . '">';
        $html .= '<span class="slider"></span>';
        $html .= '</label>';
        $html .= '<span class="toggle-label yearly">Yearly</span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</center>';
    }

    // Wrapper (no carousel)
    $products_count = count($products_data['products']);
    $html .= '<div class="products-wrapper" data-count="' . esc_attr($products_count) . '">';

    foreach ($products_data['products'] as $product) {
        // Styles by product name
        $product_styles_group1 = $product_styles_group2 = $product_styles_group3 = $product_styles_group4 = $product_styles_group5 = '';
        $default_style_class = '';

        if (!empty($product['pricing-background-color'])) {
            $background_color_style = 'style="background-color: ' . esc_attr($product['pricing-background-color']) . ';"';
        } else {
            $product_styles_group1 = in_array($product['name'], ['Helpdesk Freelancer', 'ServiceDesk Freelancer', 'Faveo Cloud HelpDesk', 'Support service', 'Customization', 'Faveo Upgrade', 'Install service']) ? ' product-styles-group1' : '';
            $product_styles_group2 = in_array($product['name'], ['Helpdesk Startup', 'Servicedesk Startup', 'Helpdesk Startup (Recurring)', 'ServiceDesk Startup (Recurring)', 'Faveo Cloud Helpdesk Startup', 'Faveo Cloud ServiceDesk  Startup']) ? ' product-styles-group2' : '';
            $product_styles_group3 = in_array($product['name'], ['Helpdesk SME ', 'ServiceDesk SME', 'Helpdesk SME (Recurring)', 'ServiceDesk SME (Recurring)', 'Faveo Cloud Helpdesk SME', 'Faveo Cloud ServiceDesk SME']) ? ' product-styles-group3' : '';
            $product_styles_group4 = in_array($product['name'], ['Helpdesk Enterprise', 'Helpdesk Enterprise (Recurring)', 'ServiceDesk Enterprise', 'ServiceDesk Enterprise (Recurring)', 'Faveo Cloud Helpdesk Enterprise', 'Faveo Cloud ServiceDesk Enterprise']) ? ' product-styles-group4' : '';
            $product_styles_group5 = in_array($product['name'], ['Helpdesk Enterprise Pro', 'ServiceDesk Enterprise Pro']) ? ' product-styles-group5' : '';

            // ✅ Apply default style if no group styles matched
            if (!$product_styles_group1 && !$product_styles_group2 && !$product_styles_group3 && !$product_styles_group4 && !$product_styles_group5) {
                $default_style_class = ' product-styles-default';
            }
        }

        $monthly_price = floatval($product['add_price']);
        $yearly_price  = floatval($product['add_price']); // Adjust if API gives yearly separately
        $offer_price   = isset($product['offer_price']) ? floatval($product['offer_price']) : 0;

        $effective_price = ($offer_price > 0)
            ? $monthly_price - ($monthly_price * ($offer_price / 100))
            : $monthly_price;

        $currency_code = !empty($product['currency']) ? strtoupper($product['currency']) : '';
        $currency_symbol = !empty($currency_code) ? fhai_currency_symbol_by_code($currency_code) : fhai_currency_symbol($country_code);

        $display_price = ($currency_code === 'INR' || $country_code === 'IN')
            ? indian_number_format($effective_price)
            : number_format($effective_price, 0);

        // Product container
        $html .= '<div class="product-container ' . esc_attr($atts['style']) . 
            ($product['highlight'] == 1 ? ' highlighted-product-container' : '') 
            . $product_styles_group1 . $product_styles_group2 . $product_styles_group3 
            . $product_styles_group4 . $product_styles_group5 . $default_style_class . '" 
            data-group="' . esc_attr($group_id) . '" 
            data-days="' . esc_attr($product['days']) . '" 
            data-monthly="' . esc_attr($monthly_price) . '" 
            data-yearly="' . esc_attr($yearly_price) . '" 
            data-offer="' . esc_attr($offer_price) . '" 
            data-currency="' . esc_attr($currency_symbol) . '">';

        $html .= '<div class="additional-container">';
        $html .= '<div class="packagess">';
        $html .= '<div class="product-pricing ' . ($product['highlight'] == 1 ? 'highlighted-product' : '') . '">';

        if ($product['highlight'] == 1) {
            $html .= '<div class="popular-ribbon text-light">Most Popular</div>';
        }

        $html .= '<h1>' . esc_html($product['name']) . '</h1>';

        if (!empty($product['short_description'])) {
            $html .= '<div class="short-description">' . wp_kses_post($product['short_description']) . '</div>';
        }

        if ($product['add_to_contact'] == 1) {
            $html .= '<h2 style="font-size:28px !important; height:82px !important;line-height: 42px; margin-top:30px;">Custom Pricing</h2>';
            $custom_sales_url = get_option('fhai_custom_sales_url', 'https://www.example.com/');
            $html .= '<a href="' . esc_url($custom_sales_url) . '" class="purchase-btn">Custom Sales</a>';
        } else {
            $html .= '<h2 data-monthly-price="' . esc_attr($monthly_price) . '" data-yearly-price="' . esc_attr($yearly_price) . '">';
            $html .= esc_html($currency_symbol . $display_price) . '</h2>';

            // ✅ Show original/strike-through pricing
            if ($offer_price > 0 && $all_status_one) {
                $html .= '<p class="original-price" 
                            data-monthly-orig="' . esc_attr($monthly_price) . '" 
                            data-yearly-orig="' . esc_attr($yearly_price) . '"></p>';
            } elseif ($offer_price > 0 && !$all_status_one) {
                $original_price = $monthly_price;
                $formatted_orig = ($currency_code === 'INR' || $country_code === 'IN')
                    ? indian_number_format($original_price)
                    : number_format($original_price, 0);
                $html .= '<p class="original-price"><s>' . esc_html($currency_symbol . $formatted_orig) . '</s></p>';
            }

            $html .= '<p class="price-description">' . wp_kses_post($product['price_description']) . '</p>';
            $html .= '<a href="' . esc_url($product['shoping_cart_link']) . '" class="purchase-btn">Buy Now</a>';
        }

        $html .= '</div>'; // product-pricing

        // ✅ Description with tooltips
        $description_with_tooltips = preg_replace_callback(
            '/<li([^>]*)>(.*?)<\/li>/i',
            function($matches) {
                $attributes = $matches[1];
                $inner_html = $matches[2];

                preg_match('/title="([^"]*)"/i', $attributes, $title_match);
                $tooltip = $title_match[1] ?? wp_strip_all_tags($inner_html);

                return '<li data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top" data-bs-content="' 
                        . esc_attr($tooltip) . '">' . $inner_html . '</li>';
            },
            $product['description']
        );

        $html .= '<div class="description">' . wp_kses_post($description_with_tooltips) . '</div>';

        $html .= '</div>'; // packagess
        $html .= '</div>'; // additional-container
        $html .= '</div>'; // product-container
    }

    $html .= '</div>'; // products-wrapper

    return $html;
}
