<?php
defined( 'ABSPATH' ) || exit;

// Get user IP.
// Parses forwarded IP headers safely and applies proxy-trust rules before falling back to REMOTE_ADDR.
function fhai_first_valid_ip_from_header( $header_value, $public_only = false ) {
	$parts = array_map( 'trim', explode( ',', (string) $header_value ) );
	foreach ( $parts as $part ) {
		$flags = $public_only ? FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE : 0;
		if ( filter_var( $part, FILTER_VALIDATE_IP, $flags ) ) {
			return $part;
		}
	}

	return '';
}

// Resolve client IP from trusted headers, preferring public IPs first.
function fhai_get_ip_from_trusted_headers( $trusted_headers ) {
	foreach ( $trusted_headers as $header_key ) {
		if ( empty( $_SERVER[ $header_key ] ) ) {
			continue;
		}

		$raw_header = sanitize_text_field( wp_unslash( $_SERVER[ $header_key ] ) );
		$public_ip  = fhai_first_valid_ip_from_header( $raw_header, true );
		if ( '' !== $public_ip ) {
			return $public_ip;
		}

		$ip = fhai_first_valid_ip_from_header( $raw_header );
		if ( '' !== $ip ) {
			return $ip;
		}
	}

	return '';
}

// Determine the effective client IP.
// If the request comes from a trusted or private proxy, use forwarded headers; otherwise use REMOTE_ADDR.
function fhai_get_user_ip() {
	if ( empty( $_SERVER['REMOTE_ADDR'] ) ) {
		return '';
	}

	$remote_addr = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
	if ( ! filter_var( $remote_addr, FILTER_VALIDATE_IP ) ) {
		return '';
	}

	$trusted_proxies = apply_filters( 'fhai_trusted_proxies', array() );
	$trusted_headers = apply_filters(
		'fhai_trusted_ip_headers',
		array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_TRUE_CLIENT_IP',
			'HTTP_X_REAL_IP',
			'HTTP_X_FORWARDED_FOR',
		)
	);

	$should_trust_forwarded = in_array( $remote_addr, $trusted_proxies, true );

	// Common hosting pattern: app server sees private/internal proxy IP.
	$is_remote_public = (bool) filter_var( $remote_addr, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
	if ( ! $is_remote_public ) {
		$should_trust_forwarded = true;
	}

	if ( $should_trust_forwarded ) {
		$trusted_header_ip = fhai_get_ip_from_trusted_headers( $trusted_headers );
		if ( '' !== $trusted_header_ip ) {
			return $trusted_header_ip;
		}
	}

	return $remote_addr;
}

//  Currency symbols
function fhai_currency_symbol_combined( $fhai_key ) {
	$fhai_map = array(
		'USD' => '$',
		'INR' => '₹',
		'GBP' => '£',
		'EUR' => '€',
		'CAD' => 'C$',
		'AUD' => 'A$',
		'NZD' => 'NZ$',
		'JPY' => '¥',
		'CNY' => '¥',
		'SGD' => 'S$',
		'HKD' => 'HK$',
		'KRW' => '₩',
		'RUB' => '₽',
		'BRL' => 'R$',
		'ZAR' => 'R',
		'MXN' => 'MX$',
		'CHF' => 'CHF',
		'SEK' => 'kr',
		'NOK' => 'kr',
		'DKK' => 'kr',
		'PLN' => 'zł',
		'CZK' => 'Kč',
		'HUF' => 'Ft',
		'TRY' => '₺',
		'SAR' => '﷼',
		'AED' => 'د.إ',
		'ILS' => '₪',
		'THB' => '฿',
		'IDR' => 'Rp',
		'MYR' => 'RM',
		'PHP' => '₱',
		'VND' => '₫',
		'NGN' => '₦',
	);

	if ( ! $fhai_key ) {
		return '';                  // return empty if no key
	}
	return $fhai_map[ $fhai_key ] ?? $fhai_key;  // fallback to code instead of $
}

//  Indian number format
function fhai_indian_number_format( $fhai_number ) {

	if ( ! class_exists( 'NumberFormatter' ) ) {
		return number_format( $fhai_number, 2 );
	}

	$fhai_formatter = new NumberFormatter( 'en_IN', NumberFormatter::DECIMAL );
	return $fhai_formatter->format( $fhai_number );
}

// Allow only safe CSS color values in inline style attributes.
function fhai_sanitize_css_color( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}

	$hex = sanitize_hex_color( $value );
	if ( $hex ) {
		return $hex;
	}

	return '';
}

//  Shortcode handler

function fhai_calling( $atts ) {

	$fhai_atts = shortcode_atts(
		array(
			'group' => '',
			'class' => '',
			'style' => '',
		),
		$atts
	);

	$fhai_group_id = absint( $fhai_atts['group'] );
	if ( ! $fhai_group_id ) {
		return 'Invalid group ID.';
	}

	$fhai_user_ip = fhai_get_user_ip();
	if ( empty( $fhai_user_ip ) ) {
		return 'Unable to detect user IP.';
	}

	$fhai_api_url = get_option( 'fhai_api_url' );
	if ( empty( $fhai_api_url ) ) {
		return 'API URL is not configured.';
	}

	$fhai_cache_key     = 'fhai_products_' . md5( $fhai_group_id . '|' . $fhai_user_ip );
	$fhai_products_data = get_transient( $fhai_cache_key );

	if ( false === $fhai_products_data ) {
		// Fetch products.
		$fhai_products_url = add_query_arg(
			array(
				'group'     => $fhai_group_id,
				'ipAddress' => $fhai_user_ip,
			),
			$fhai_api_url
		);

		$fhai_response = wp_remote_get( $fhai_products_url, array( 'timeout' => 10 ) );
		if ( is_wp_error( $fhai_response ) ) {
			return 'Unable to fetch products.';
		}

		$fhai_response_code = wp_remote_retrieve_response_code( $fhai_response );
		if ( 200 !== (int) $fhai_response_code ) {
			return 'Unable to fetch products.';
		}

		$fhai_products_data = json_decode( wp_remote_retrieve_body( $fhai_response ), true );
		if ( ! is_array( $fhai_products_data ) ) {
			return 'No products available.';
		}

		$fhai_cache_ttl = (int) apply_filters( 'fhai_products_cache_ttl', 300 );
		set_transient( $fhai_cache_key, $fhai_products_data, $fhai_cache_ttl > 0 ? $fhai_cache_ttl : 300 );
	}

	if ( empty( $fhai_products_data['products'] ) ) {
		return 'No products available.';
	}

	// Determine if all products have status = 1
	$fhai_all_status_one = true;
	foreach ( $fhai_products_data['products'] as $fhai_product ) {
		if ( (string) ( $fhai_product['status'] ?? '' ) !== '1' ) {
			$fhai_all_status_one = false;
			break;
		}
	}

	// Root-level currency code and symbol from API

	$fhai_currency_code   = $fhai_products_data['currency'] ?? null;
	$fhai_currency_symbol =
	$fhai_products_data['currency_symbol']
	?? ( $fhai_currency_code ? fhai_currency_symbol_combined( $fhai_currency_code ) : '' );

	$products        = $fhai_products_data['products'];
	$group_id        = $fhai_group_id;
	$atts            = $fhai_atts;
	$all_status_one  = $fhai_all_status_one;
	$currency_code   = $fhai_currency_code;
	$currency_symbol = $fhai_currency_symbol;

	ob_start();
	include FHAI_DIR . 'templates/products-template.php';
	return ob_get_clean();
}


//  Register shortcode

add_shortcode( 'fhai_pricing', 'fhai_calling' );
