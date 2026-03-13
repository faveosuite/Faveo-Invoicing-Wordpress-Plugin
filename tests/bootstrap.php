<?php
// Minimal WordPress function stubs for unit tests.
define( 'ABSPATH', __DIR__ . '/../' );

if ( ! function_exists( 'sanitize_text_field' ) ) {
	// Basic sanitization for test context.
	function sanitize_text_field( $text ) {
		return is_string( $text ) ? trim( $text ) : $text;
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	// Mirror WP unslash behavior for strings/arrays in tests.
	function wp_unslash( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'wp_unslash', $value );
		}
		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	// Simple filter hook emulator used by tests.
	function apply_filters( $hook_name, $value ) {
		global $fhai_test_filters;
		if ( isset( $fhai_test_filters[ $hook_name ] ) && is_callable( $fhai_test_filters[ $hook_name ] ) ) {
			return call_user_func( $fhai_test_filters[ $hook_name ], $value );
		}
		return $value;
	}
}

if ( ! function_exists( 'add_shortcode' ) ) {
	// Stub for plugin bootstrap compatibility.
	function add_shortcode( $tag, $callback ) {
		return true;
	}
}

// Load plugin functions under test.
require_once __DIR__ . '/../includes/faveo-pricing-functions.php';
