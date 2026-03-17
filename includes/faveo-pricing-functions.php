<?php
defined('ABSPATH') || exit;

// Get user IP

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

//  Currency symbols
function fhai_currency_symbol_combined($fhai_key) {
    $fhai_map = [
        'USD'=>'$', 'INR'=>'₹', 'GBP'=>'£', 'EUR'=>'€',
        'CAD'=>'C$', 'AUD'=>'A$', 'NZD'=>'NZ$', 'JPY'=>'¥',
        'CNY'=>'¥', 'SGD'=>'S$', 'HKD'=>'HK$', 'KRW'=>'₩',
        'RUB'=>'₽', 'BRL'=>'R$', 'ZAR'=>'R', 'MXN'=>'MX$',
        'CHF'=>'CHF', 'SEK'=>'kr', 'NOK'=>'kr', 'DKK'=>'kr',
        'PLN'=>'zł', 'CZK'=>'Kč', 'HUF'=>'Ft', 'TRY'=>'₺',
        'SAR'=>'﷼', 'AED'=>'د.إ', 'ILS'=>'₪', 'THB'=>'฿',
        'IDR'=>'Rp', 'MYR'=>'RM', 'PHP'=>'₱', 'VND'=>'₫',
        'NGN'=>'₦',
    ];

    if (!$fhai_key) return '';                  // return empty if no key
    return $fhai_map[$fhai_key] ?? $fhai_key;  // fallback to code instead of $
}

//  Indian number format
function fhai_indian_number_format($fhai_number) {

    if (!class_exists('NumberFormatter')) {
        return number_format($fhai_number, 2);
    }

    $fhai_formatter = new NumberFormatter('en_IN', NumberFormatter::DECIMAL);
    return $fhai_formatter->format($fhai_number);
}

//  Template loader
 
function fhai_get_template($fhai_template_name, $fhai_args = []) {

    $fhai_template_path = FHAI_DIR . 'templates/' . $fhai_template_name;

    if (!file_exists($fhai_template_path)) {
        return '<p style="color:red;">Template not found.</p>';
    }

    extract($fhai_args, EXTR_SKIP);

    ob_start();
    include $fhai_template_path;
    return ob_get_clean();
}

//  Shortcode handler
 
function fhai_calling($atts) {

    $fhai_atts = shortcode_atts([
        'group' => '',
        'class' => '',
        'style' => '',
    ], $atts);

    $fhai_group_id = absint($fhai_atts['group']);
    if (!$fhai_group_id) return 'Invalid group ID.';

    $fhai_user_ip = fhai_get_user_ip();
    if (empty($fhai_user_ip)) return 'Unable to detect user IP.';

    $fhai_api_url = get_option('fhai_api_url');
    if (empty($fhai_api_url)) return 'API URL is not configured.';

    // Fetch products
    $fhai_products_url = add_query_arg([
        'group'     => $fhai_group_id,
        'ipAddress' => $fhai_user_ip,
    ], $fhai_api_url);

    $fhai_response = wp_remote_get($fhai_products_url, ['timeout' => 10]);
    if (is_wp_error($fhai_response)) return 'Unable to fetch products.';

    $fhai_products_data = json_decode(wp_remote_retrieve_body($fhai_response), true);
    if (empty($fhai_products_data['products'])) return 'No products available.';

    // Determine if all products have status = 1
    $fhai_all_status_one = true;
    foreach ($fhai_products_data['products'] as $fhai_product) {
        if ((string) ($fhai_product['status'] ?? '') !== '1') {
            $fhai_all_status_one = false;
            break;
        }
    }

    // Root-level currency code and symbol from API
  
    
    
    $fhai_currency_code = $fhai_products_data['currency'] ?? null;
    $fhai_currency_symbol =
    $fhai_products_data['currency_symbol']
    ?? ($fhai_currency_code ? fhai_currency_symbol_combined($fhai_currency_code) : '');


    return fhai_get_template('products-template.php', [
        'products'        => $fhai_products_data['products'],
        'group_id'        => $fhai_group_id,
        'atts'            => $fhai_atts,
        'all_status_one'  => $fhai_all_status_one,
        'currency_code'   => $fhai_currency_code,
        'currency_symbol' => $fhai_currency_symbol,
    ]);
}


//  Register shortcode
 
add_shortcode('fhai_pricing', 'fhai_calling');
