<?php

use PHPUnit\Framework\TestCase;

final class FhaiPricingFunctionsTest extends TestCase {
	// Reset globals between tests to avoid cross-test contamination.
	protected function setUp(): void {
		$_SERVER = array();
		$GLOBALS['fhai_test_filters'] = array();
	}

	// Picks the first public IP from a header list.
	public function test_first_valid_ip_from_header_returns_first_public_ip(): void {
		$header = '10.0.0.1, 8.8.8.8, 1.1.1.1';
		$this->assertSame( '8.8.8.8', fhai_first_valid_ip_from_header( $header, true ) );
	}

	// Returns empty when no valid IP exists.
	public function test_first_valid_ip_from_header_returns_empty_for_invalid_values(): void {
		$header = 'abc, not-an-ip, 300.1.1.1';
		$this->assertSame( '', fhai_first_valid_ip_from_header( $header ) );
	}

	// No proxy: use REMOTE_ADDR.
	public function test_get_user_ip_returns_remote_addr_when_no_proxy(): void {
		$_SERVER['REMOTE_ADDR'] = '203.0.113.7';
		$this->assertSame( '203.0.113.7', fhai_get_user_ip() );
	}

	// Private proxy: trust forwarded header and use public IP.
	public function test_get_user_ip_uses_forwarded_header_for_private_proxy_remote(): void {
		$_SERVER['REMOTE_ADDR']        = '10.0.0.2';
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.10, 10.0.0.2';
		$this->assertSame( '198.51.100.10', fhai_get_user_ip() );
	}

	// Trusted proxy: respect header precedence and filters.
	public function test_get_user_ip_uses_trusted_proxy_filter_and_header_order(): void {
		$_SERVER['REMOTE_ADDR']           = '203.0.113.10';
		$_SERVER['HTTP_TRUE_CLIENT_IP']   = '198.51.100.30';
		$_SERVER['HTTP_X_FORWARDED_FOR']  = '198.51.100.20, 10.1.1.1';
		$GLOBALS['fhai_test_filters']['fhai_trusted_proxies'] = static function( $value ) {
			return array( '203.0.113.10' );
		};

		$this->assertSame( '198.51.100.30', fhai_get_user_ip() );
	}
}
