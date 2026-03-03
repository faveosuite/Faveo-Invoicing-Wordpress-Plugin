<?php
defined( 'ABSPATH' ) || exit;

/**
 * Add admin menu
 */
add_action( 'admin_menu', 'fhai_register_admin_menu' );
function fhai_register_admin_menu() {
	add_menu_page(
		__( 'Faveo Invoicing Settings', 'faveo-agora-invoicing' ),
		__( 'Faveo Invoicing', 'faveo-agora-invoicing' ),
		'manage_options',
		'fhai-invoicing-settings',
		'fhai_render_settings_page',
		'dashicons-admin-generic'
	);
}

/**
 * Render settings page
 */
function fhai_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Faveo Invoicing Settings', 'faveo-agora-invoicing' ); ?></h1>
		<?php settings_errors(); ?>

		<form method="post" action="options.php">
			<?php
			settings_fields( 'fhai_settings_group' );
			do_settings_sections( 'fhai-invoicing-settings' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * Register settings
 */
add_action( 'admin_init', 'fhai_register_settings' );
function fhai_register_settings() {

	register_setting(
		'fhai_settings_group',
		'fhai_api_url',
		'fhai_validate_api_url'
	);

	register_setting(
		'fhai_settings_group',
		'fhai_custom_sales_url',
		'fhai_validate_custom_sales_url'
	);

	add_settings_section(
		'fhai_api_section',
		__( 'API Settings', 'faveo-agora-invoicing' ),
		'fhai_api_section_callback',
		'fhai-invoicing-settings'
	);

	add_settings_field(
		'fhai_api_url',
		__( 'Faveo Invoicing API Base URL', 'faveo-agora-invoicing' ),
		'fhai_api_url_field',
		'fhai-invoicing-settings',
		'fhai_api_section'
	);

	add_settings_field(
		'fhai_custom_sales_url',
		__( 'Custom Sales URL', 'faveo-agora-invoicing' ),
		'fhai_custom_sales_url_field',
		'fhai-invoicing-settings',
		'fhai_api_section'
	);
}

/**
 * Section description
 */
function fhai_api_section_callback() {
	echo esc_html__(
		'Enter the base API URL.',
		'faveo-agora-invoicing'
	);
}

/**
 * API URL field
 */
function fhai_api_url_field() {
	$value = esc_url( get_option( 'fhai_api_url', '' ) );
	echo '<input type="url" class="regular-text" name="fhai_api_url" value="' . esc_attr( $value ) . '" />';
}

/**
 * Custom sales URL field
 */
function fhai_custom_sales_url_field() {
	$value = esc_url( get_option( 'fhai_custom_sales_url', '' ) );
	echo '<input type="url" class="regular-text" name="fhai_custom_sales_url" value="' . esc_attr( $value ) . '" />';
}

/**
 * Validate API URL
 */
function fhai_validate_api_url( $input ) {
	$input = esc_url_raw( trim( $input ) );

	if ( empty( $input ) || ! filter_var( $input, FILTER_VALIDATE_URL ) ) {
		add_settings_error(
			'fhai_api_url',
			'fhai_invalid_api_url',
			__( 'Please enter a valid API URL.', 'faveo-agora-invoicing' ),
			'error'
		);
		return get_option( 'fhai_api_url' );
	}

	return $input;
}

/**
 * Validate Custom Sales URL
 */
function fhai_validate_custom_sales_url( $input ) {
	$input = esc_url_raw( trim( $input ) );

	if ( empty( $input ) || ! filter_var( $input, FILTER_VALIDATE_URL ) ) {
		add_settings_error(
			'fhai_custom_sales_url',
			'fhai_invalid_sales_url',
			__( 'Please enter a valid Custom Sales URL.', 'faveo-agora-invoicing' ),
			'error'
		);
		return get_option( 'fhai_custom_sales_url' );
	}

	return $input;
}
