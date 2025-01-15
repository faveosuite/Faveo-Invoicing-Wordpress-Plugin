<?php
// Add a menu item to the admin panel
add_action('admin_menu', 'fhai_menu');

function fhai_menu() {
    add_menu_page(
        ' Agora Invoicing Settings',
        ' Agora Invoicing',
        'manage_options',
        'agora-invoicing-settings',
        'fhai_settings_page',
        'dashicons-admin-generic'
    );
}

function fhai_settings_page() {
    ?>
    <div class="wrap">
        <h1>Agora Invoicing Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('fhai_settings_group');
            do_settings_sections('agora-invoicing-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register the settings
add_action('admin_init', 'fhai_settings_init');

function fhai_settings_init() {
    // Register the API URL setting with sanitization
    register_setting('fhai_settings_group', 'fhai_api_url', 'sanitize_text_field');

    add_settings_section(
        'fhai_settings_section',
        ' API Settings',
        'fhai_settings_section_callback',
        'agora-invoicing-settings'
    );

    add_settings_field(
        'fhai_api_url',
        ' API URL',
        'fhai_api_url_callback',
        'agora-invoicing-settings',
        'fhai_settings_section'
    );
}

function fhai_settings_section_callback() {
    echo 'Enter the API URL for Agora Invoicing.';
}

function fhai_api_url_callback() {
    $api_url = get_option('fhai_api_url');
    echo '<input type="text" id="fhai_api_url" name="fhai_api_url" value="' . esc_attr($api_url) . '" size="50" />';
}
?>
