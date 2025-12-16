<?php
// User IP
function fhai_get_user_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipList = explode(',', sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR'])));
        $ip = trim($ipList[0]);
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
    } else {
        $ip = '';
    }
    return $ip;
}

// Get country from IP (simple & minimal)
function fhai_get_country_from_ip($ip) {
    $default_country = 'US';

    // Cloudflare header override (fast + reliable)
   if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
    $cf_country = sanitize_text_field(
        wp_unslash($_SERVER['HTTP_CF_IPCOUNTRY'])
    );

    return strtoupper($cf_country);
}
    if (empty($ip)) {
        return $default_country;
    }

    $url = 'http://ip-api.com/json/' . urlencode($ip) . '?fields=countryCode';

    $response = wp_remote_get($url, [
        'timeout' => 5,
    ]);

    if (is_wp_error($response)) {
        return $default_country;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    return !empty($data['countryCode'])
        ? strtoupper($data['countryCode'])
        : $default_country;
}


// Currency and symbols
function fhai_currency_symbol_combined($key) {
    $map = [
        'USD' => '$', 'INR' => '₹', 'GBP' => '£', 'EUR' => '€',
        'CAD' => 'C$', 'AUD' => 'A$', 'NZD' => 'NZ$', 'JPY' => '¥',
        'CNY' => '¥', 'SGD' => 'S$', 'HKD' => 'HK$', 'KRW' => '₩',
        'RUB' => '₽', 'BRL' => 'R$', 'ZAR' => 'R', 'MXN' => 'MX$',
        'CHF' => 'CHF', 'SEK' => 'kr', 'NOK' => 'kr', 'DKK' => 'kr',
        'PLN' => 'zł', 'CZK' => 'Kč', 'HUF' => 'Ft', 'TRY' => '₺',
        'SAR' => '﷼', 'AED' => 'د.إ', 'ILS' => '₪', 'THB' => '฿',
        'IDR' => 'Rp', 'MYR' => 'RM', 'PHP' => '₱', 'VND' => '₫',
        'NGN' => '₦',
        'US' => '$', 'IN' => '₹', 'GB' => '£', 'EU' => '€', 'CA' => 'C$',
        'AU' => 'A$', 'NZ' => 'NZ$', 'JP' => '¥', 'CN' => '¥', 'SG' => 'S$',
        'HK' => 'HK$', 'KR' => '₩', 'RU' => '₽', 'BR' => 'R$', 'ZA' => 'R',
        'MX' => 'MX$', 'CH' => 'CHF', 'SE' => 'kr', 'NO' => 'kr', 'DK' => 'kr',
        'PL' => 'zł', 'CZ' => 'Kč', 'HU' => 'Ft', 'TR' => '₺', 'SA' => '﷼',
        'AE' => 'د.إ', 'IL' => '₪', 'TH' => '฿', 'ID' => 'Rp', 'MY' => 'RM',
        'PH' => '₱', 'VN' => '₫', 'NG' => '₦',
    ];

    return $map[$key] ?? '$';
}

// Indian number format without rounding
function fhai_indian_number_format($number) {
    $fhai_formatter = new \NumberFormatter('en_IN', \NumberFormatter::DECIMAL);
    return $fhai_formatter->format($number);
}

// Template loader
function fhai_get_template($template_name, $args = array()) {
    $template_path = FHAI_DIR . 'templates/' . $template_name;

    if (!file_exists($template_path)) {
        return '<p style="color:red;">Template not found.</p>';
    }

    extract($args);
    ob_start();
    include $template_path;
    return ob_get_clean();
}

// Shortcode
function fhai_calling($atts) {
    $atts = shortcode_atts(array(
        'group' => '',
        'country' => '',
        'days' => '',
        'class' => '',
        'style' => '',
    ), $atts);

    $group_id = absint($atts['group']);
    if ($group_id <= 0) return 'Invalid group ID.';

    $user_ip = fhai_get_user_ip();
    $detected_country = fhai_get_country_from_ip($user_ip);
    $country_code = !empty($atts['country']) ? strtoupper($atts['country']) : $detected_country;

    $api_url = get_option('fhai_api_url');
    if (empty($api_url)) return 'API URL is not set. Please configure it in plugin settings.';

    // Get products
    $products_url = $api_url . '?group=' . $group_id . '&country=' . $country_code;
    $products_response = wp_remote_get($products_url, array('method' => 'GET'));
    $products_data = array();
    if (!is_wp_error($products_response)) {
        $products_data = json_decode(wp_remote_retrieve_body($products_response), true);
    }

    if (empty($products_data['products'])) return 'No products available for this group.';

    // Check if all products are active
    $all_status_one = true;
    foreach ($products_data['products'] as $fhai_product) {
        if ($fhai_product['status'] !== "1") {
            $all_status_one = false;
            break;
        }
    }

    // Send data to template
    return fhai_get_template('products-template.php', array(
        'products'       => $products_data['products'],
        'group_id'       => $group_id,
        'country_code'   => $country_code,
        'atts'           => $atts,
        'all_status_one' => $all_status_one,
    ));
}
