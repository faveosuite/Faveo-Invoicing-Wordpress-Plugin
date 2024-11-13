<?php
// Add a menu item to the admin panel
add_action('admin_menu', 'agora_invoicing_menu');

function agora_invoicing_menu() {
    add_menu_page(
        ' Agora Invoicing Settings',
        ' Agora Invoicing',
        'manage_options',
        'agora-invoicing-settings',
        'agora_invoicing_settings_page',
        'dashicons-admin-generic'
    );
}

function agora_invoicing_settings_page() {
    ?>
    <div class="wrap">
        <h1>Agora Invoicing Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('agora_invoicing_settings_group');
            do_settings_sections('agora-invoicing-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register the settings
add_action('admin_init', 'agora_invoicing_settings_init');

function agora_invoicing_settings_init() {
    register_setting('agora_invoicing_settings_group', 'agora_invoicing_api_url');

    add_settings_section(
        'agora_invoicing_settings_section',
        ' API Settings',
        'agora_invoicing_settings_section_callback',
        'agora-invoicing-settings'
    );

    add_settings_field(
        'agora_invoicing_api_url',
        ' API URL',
        'agora_invoicing_api_url_callback',
        'agora-invoicing-settings',
        'agora_invoicing_settings_section'
    );
}

function agora_invoicing_settings_section_callback() {
    echo 'Enter the API URL for Agora Invoicing.';
}

function agora_invoicing_api_url_callback() {
    $api_url = get_option('agora_invoicing_api_url');
    echo '<input type="text" id="agora_invoicing_api_url" name="agora_invoicing_api_url" value="' . esc_attr($api_url) . '" size="50" />';
}
?>
