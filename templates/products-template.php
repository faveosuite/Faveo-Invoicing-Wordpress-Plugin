<?php
defined('ABSPATH') || exit;

/**
 * Products Template
 *
 * Variables available:
 * @var array $products
 * @var int   $group_id
 * @var array $atts
 * @var bool  $all_status_one
 * @var array $fhai_products_data  // Full API response with 'currency'
 */
?>

<?php if ($all_status_one) : ?>
    <center>
        <div class="toggle-wrapper">
            <div class="toggle-labels">
                <span class="toggle-label monthly">Monthly</span>
                <label class="toggle-switch">
                    <input type="checkbox" class="pricing-toggle" data-group="<?php echo esc_attr($group_id); ?>">
                    <span class="slider"></span>
                </label>
                <span class="toggle-label yearly">Yearly</span>
            </div>
        </div>
    </center>
<?php endif; ?>

<div class="products-wrapper" data-count="0">

<?php 
$fhai_currency_code = $fhai_products_data['currency'] ?? '';
$fhai_currency_symbol = fhai_currency_symbol_combined($fhai_currency_code);

foreach ($products as $fhai_product) :

    // -------------------------------------------------
    // Product styles
    // -------------------------------------------------
    $fhai_product_styles_group1 = $fhai_product_styles_group2 = $fhai_product_styles_group3 = '';
    $fhai_product_styles_group4 = $fhai_product_styles_group5 = $fhai_product_styles_default = '';
    $fhai_background_color = $fhai_product['pricing-background-color'] ?? '';

    if (!$fhai_background_color) {
        switch ($fhai_product['name']) {
            case in_array($fhai_product['name'], ['Helpdesk Freelancer','ServiceDesk Freelancer','Faveo Cloud HelpDesk','Support service','Customization','Faveo Upgrade','Install service']):
                $fhai_product_styles_group1 = ' product-styles-group1'; break;
            case in_array($fhai_product['name'], ['Helpdesk Startup (Perpetual)','Servicedesk Startup (Perpetual)','Helpdesk Startup (Recurring)','ServiceDesk Startup (Recurring)','Helpdesk Startup (Cloud)','ServiceDesk Startup (Cloud)']):
                $fhai_product_styles_group2 = ' product-styles-group2'; break;
            case in_array($fhai_product['name'], ['Helpdesk SME (Perpetual)','ServiceDesk SME (Perpetual)','Helpdesk SME (Recurring)','ServiceDesk SME (Recurring)','Helpdesk SME (Cloud)','ServiceDesk SME (Cloud)']):
                $fhai_product_styles_group3 = ' product-styles-group3'; break;
            case in_array($fhai_product['name'], ['Helpdesk Enterprise (Perpetual)','Helpdesk Enterprise (Recurring)','ServiceDesk Enterprise (Perpetual)','ServiceDesk Enterprise (Recurring)','Helpdesk Enterprise (Cloud)','ServiceDesk Enterprise (Cloud)']):
                $fhai_product_styles_group4 = ' product-styles-group4'; break;
            case in_array($fhai_product['name'], ['Helpdesk Enterprise Pro (Perpetual)','ServiceDesk Enterprise Pro (Perpetual)']):
                $fhai_product_styles_group5 = ' product-styles-group5'; break;
            default:
                $fhai_product_styles_default = ' product-styles-default';
        }
    }

    // -------------------------------------------------
    // Prices
    // -------------------------------------------------
    $fhai_monthly_price = floatval($fhai_product['add_price'] ?? 0);
    $fhai_yearly_price  = floatval($fhai_product['add_price'] ?? 0);
    $fhai_offer_price   = floatval($fhai_product['offer_price'] ?? 0);
    $fhai_is_free_product = (!$all_status_one && $fhai_monthly_price <= 0);
    $fhai_effective_price = $fhai_offer_price > 0
        ? $fhai_monthly_price - ($fhai_monthly_price * ($fhai_offer_price / 100))
        : $fhai_monthly_price;

    // -------------------------------------------------
    // Display price formatting
    // -------------------------------------------------
    if (class_exists('NumberFormatter') && $currency_code) {
        $fhai_formatter = new NumberFormatter('en', NumberFormatter::CURRENCY);
        $fhai_display_price  = $fhai_formatter->formatCurrency($fhai_effective_price, $currency_code);
        $fhai_original_price = $fhai_formatter->formatCurrency($fhai_monthly_price, $currency_code);
    } else {
        if ($currency_code === 'INR') {
            $fhai_display_price  = $currency_symbol . fhai_indian_number_format($fhai_effective_price);
            $fhai_original_price = $currency_symbol . fhai_indian_number_format($fhai_monthly_price);
        } else {
            $fhai_display_price  = $currency_symbol . number_format($fhai_effective_price, 2);
            $fhai_original_price = $currency_symbol . number_format($fhai_monthly_price, 2);
        }
    }

    $fhai_product_key = isset($fhai_product['id'])
        ? 'p-' . intval($fhai_product['id'])
        : sanitize_title($fhai_product['name']);
?>

    <div class="product-container <?php
        echo esc_attr(
            $atts['style'] .
            ($fhai_product['highlight'] == 1 ? ' highlighted-product-container' : '') .
            $fhai_product_styles_group1 .
            $fhai_product_styles_group2 .
            $fhai_product_styles_group3 .
            $fhai_product_styles_group4 .
            $fhai_product_styles_group5 .
            $fhai_product_styles_default
        );
    ?>"
        data-product-key="<?php echo esc_attr($fhai_product_key); ?>"
        data-group="<?php echo esc_attr($group_id); ?>"
        data-days="<?php echo esc_attr($fhai_product['days']); ?>"
        data-monthly="<?php echo esc_attr($fhai_monthly_price); ?>"
        data-yearly="<?php echo esc_attr($fhai_yearly_price); ?>"
        data-offer="<?php echo esc_attr($fhai_offer_price); ?>"
        data-currency-code="<?php echo esc_attr($currency_code); ?>"
        data-currency-symbol="<?php echo esc_attr($currency_symbol); ?>"
        data-has-toggle="<?php echo $all_status_one ? '1' : '0'; ?>"
        data-add-to-contact="<?php echo !empty($fhai_product['add_to_contact']) ? '1' : '0'; ?>"
        data-is-free="<?php echo $fhai_is_free_product ? '1' : '0'; ?>"
    >

        <div class="additional-container">
            <div class="packagess" <?php if ($fhai_background_color) : ?>
                style="background-color: <?php echo esc_attr($fhai_background_color); ?>;"
            <?php endif; ?>>

                <div class="product-pricing <?php echo $fhai_product['highlight']==1 ? 'highlighted-product' : ''; ?>">

                    <?php if ($fhai_product['highlight']==1) : ?>
                        <div class="popular-ribbon text-light">Most Popular</div>
                    <?php endif; ?>

                    <h1><?php echo esc_html($fhai_product['name']); ?></h1>

                    <?php if (!empty($fhai_product['short_description'])) : ?>
                        <div class="short-description"><?php echo wp_kses_post($fhai_product['short_description']); ?></div>
                    <?php endif; ?>

                    <?php if (!empty($fhai_product['add_to_contact'])) : ?>
                        <h2 class="custom-pricing">
                            Custom Pricing
                        </h2>
                        <p class="price-description"><?php echo wp_kses_post($fhai_product['price_description']); ?></p>
                        <a href="<?php echo esc_url(get_option('fhai_custom_sales_url', 'https://www.example.com/')); ?>" class="purchase-btn">Contact Sales</a>
                    <?php else : ?>
                       <h2 
    data-monthly-price="<?php echo esc_attr($fhai_monthly_price); ?>"
    data-yearly-price="<?php echo esc_attr($fhai_yearly_price); ?>"
    data-currency-symbol="<?php echo esc_attr($currency_symbol); ?>"
    data-currency-code="<?php echo esc_attr($currency_code); ?>"
>
    <?php echo $fhai_is_free_product ? 'Free' : esc_html($fhai_display_price); ?>
</h2>


                        <?php if ($fhai_offer_price > 0) : ?>
                            <p class="original-price"><s><?php echo esc_html($fhai_original_price); ?></s></p>
                        <?php endif; ?>

                        <p class="price-description"><?php echo wp_kses_post($fhai_product['price_description']); ?></p>
                        <a href="<?php echo esc_url($fhai_product['shoping_cart_link']); ?>" class="purchase-btn">Buy Now</a>
                    <?php endif; ?>

                </div>

                <div class="description">
                    <?php
                    $fhai_description_with_tooltips = preg_replace_callback(
                        '/<li([^>]*)>(.*?)<\/li>/i',
                        function ($matches) {
                            $tooltip = wp_strip_all_tags($matches[2]);
                            return '<li data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top" data-bs-content="' .
                                esc_attr($tooltip) . '">' . $matches[2] . '</li>';
                        },
                        $fhai_product['description']
                    );
                    echo wp_kses_post($fhai_description_with_tooltips);
                    ?>
                </div>

            </div>
        </div>
    </div>

<?php endforeach; ?>
</div>
