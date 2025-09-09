<?php
// Add a menu item to the admin panel
add_action('admin_menu', 'fhai_menu');

function fhai_menu() {
    add_menu_page(
        'Faveo Invoicing Settings',
        'Faveo Invoicing',
        'manage_options',
        'faveo-invoicing-settings',
        'fhai_settings_page',
        'dashicons-admin-generic'
    );
}

function fhai_settings_page() {
    ?>
    <div class="wrap">
        <h1>Faveo Invoicing Settings</h1>
        <?php settings_errors(); ?>
        <form method="post" action="options.php">
            <?php
            settings_fields('fhai_settings_group');
            do_settings_sections('faveo-invoicing-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register the settings
add_action('admin_init', 'fhai_settings_init');

function fhai_settings_init() {
    // Register the API URL setting with a custom validation function
    register_setting('fhai_settings_group', 'fhai_api_url', 'fhai_validate_api_url');
        register_setting('fhai_settings_group', 'fhai_custom_sales_url', 'fhai_validate_custom_sales_url');

    add_settings_section(
        'fhai_settings_section',
        'API Settings',
        'fhai_settings_section_callback',
        'faveo-invoicing-settings'
    );

    add_settings_field(
        'fhai_api_url',
        'API URL',
        'fhai_api_url_callback',
        'faveo-invoicing-settings',
        'fhai_settings_section'
    );
    
     add_settings_field(
        'fhai_custom_sales_url',
        'Custom Sales URL',
        'fhai_custom_sales_url_callback',
        'faveo-invoicing-settings',
        'fhai_settings_section'
    );
}

function fhai_settings_section_callback() {
    echo 'Enter the API URL for Faveo Invoicing.';
}

function fhai_api_url_callback() {
    $api_url = get_option('fhai_api_url');
    echo '<input type="text" id="fhai_api_url" name="fhai_api_url" value="' . esc_attr($api_url) . '" size="50" />';
}

function fhai_custom_sales_url_callback() {
    $custom_sales_url = get_option('fhai_custom_sales_url');
    echo '<input type="text" id="fhai_custom_sales_url" name="fhai_custom_sales_url" value="' . esc_attr($custom_sales_url) . '" size="50" />';
}


// Custom validation function for the API URL
function fhai_validate_api_url($input) {
    if (empty($input) || !filter_var($input, FILTER_VALIDATE_URL)) {
        add_settings_error(
            'fhai_api_url',
            'fhai_invalid_url',
            'Please add a valid URL',
            'error'
        );
        return get_option('fhai_api_url');
    }
    return $input;
}

// Custom validation function for the Custom Sales URL
function fhai_validate_custom_sales_url($input) {
    if (empty($input) || !filter_var($input, FILTER_VALIDATE_URL)) {
        add_settings_error(
            'fhai_custom_sales_url',
            'fhai_invalid_sales_url',
            'Please add a valid Custom Sales URL',
            'error'
        );
        return get_option('fhai_custom_sales_url');
    }
    return $input;
}
?>
