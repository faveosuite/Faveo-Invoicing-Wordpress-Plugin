<?php
/**
 * Products Template
 *
 * Variables available:
 * @var array $products
 * @var int $group_id
 * @var string $country_code
 * @var array $atts
 * @var bool $all_status_one
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

<?php $fhai_visible_count = 0; ?>
<div class="products-wrapper" data-count="0">

<?php foreach ($products as $fhai_product) :
$fhai_is_hidden = false;
    // Product styles
$fhai_product_styles_group1 = $fhai_product_styles_group2 = $fhai_product_styles_group3 = $fhai_product_styles_group4 = $fhai_product_styles_group5 = $fhai_product_styles_default = '';

$fhai_background_color = '';

if (!empty($fhai_product['pricing-background-color'])) {
    $fhai_background_color = $fhai_product['pricing-background-color'];
}
else {
    if (in_array($fhai_product['name'], ['Helpdesk Freelancer','ServiceDesk Freelancer','Faveo Cloud HelpDesk','Support service','Customization','Faveo Upgrade','Install service'])) {
        $fhai_product_styles_group1 = ' product-styles-group1';
    } elseif (in_array($fhai_product['name'], ['Helpdesk Startup','Servicedesk Startup','Helpdesk Startup (Recurring)','ServiceDesk Startup (Recurring)','Faveo Cloud Helpdesk Startup','Faveo Cloud ServiceDesk  Startup'])) {
        $fhai_product_styles_group2 = ' product-styles-group2';
    } elseif (in_array($fhai_product['name'], ['Helpdesk SME ','ServiceDesk SME','Helpdesk SME (Recurring)','ServiceDesk SME (Recurring)','Faveo Cloud Helpdesk SME','Faveo Cloud ServiceDesk SME'])) {
        $fhai_product_styles_group3 = ' product-styles-group3';
    } elseif (in_array($fhai_product['name'], ['Helpdesk Enterprise','Helpdesk Enterprise (Recurring)','ServiceDesk Enterprise','ServiceDesk Enterprise (Recurring)','Faveo Cloud Helpdesk Enterprise','Faveo Cloud ServiceDesk Enterprise'])) {
        $fhai_product_styles_group4 = ' product-styles-group4';
    } elseif (in_array($fhai_product['name'], ['Helpdesk Enterprise Pro','ServiceDesk Enterprise Pro'])) {
        $fhai_product_styles_group5 = ' product-styles-group5';
    } else {
        // ✅ Default style if product doesn't match any group
        $fhai_product_styles_default = ' product-styles-default';
    }
}


    // Currency
    $fhai_currency_code = !empty($fhai_product['currency']) ? strtoupper($fhai_product['currency']) : '';
    $fhai_currency_symbol = fhai_currency_symbol_combined($fhai_currency_code ?: $country_code);

    // Prices
    $fhai_monthly_price = floatval($fhai_product['add_price']);
    $fhai_yearly_price = floatval($fhai_product['add_price']);
    $fhai_offer_price = floatval($fhai_product['offer_price'] ?? 0);

    $fhai_effective_price = $fhai_offer_price > 0 ? $fhai_monthly_price - ($fhai_monthly_price * ($fhai_offer_price / 100)) : $fhai_monthly_price;

    // Format display price
    if (class_exists('NumberFormatter')) {
        $fhai_formatter = new NumberFormatter('en', NumberFormatter::CURRENCY);
        $fhai_display_price = $fhai_formatter->formatCurrency($fhai_effective_price, $fhai_currency_code ?: 'USD');
    } else {
        $fhai_display_price = ($fhai_currency_code === 'INR' || $country_code === 'IN') ? fhai_indian_number_format($fhai_effective_price) : number_format($fhai_effective_price, 2);
    }

    $fhai_product_key = isset($fhai_product['id']) ? 'p-' . intval($fhai_product['id']) : sanitize_title($fhai_product['name']);

 $fhai_visible_count++;
?>
    
    <div class="product-container <?php echo esc_attr($atts['style'] . ($fhai_product['highlight']==1?' highlighted-product-container':'') . $fhai_product_styles_group1 . $fhai_product_styles_group2 . $fhai_product_styles_group3 . $fhai_product_styles_group4 . $fhai_product_styles_group5 . $fhai_product_styles_default); ?>"
         data-product-key="<?php echo esc_attr($fhai_product_key); ?>"
         data-group="<?php echo esc_attr($group_id); ?>"
         data-days="<?php echo esc_attr($fhai_product['days']); ?>"
         data-monthly="<?php echo esc_attr($fhai_monthly_price); ?>"
         data-yearly="<?php echo esc_attr($fhai_yearly_price); ?>"
         data-offer="<?php echo esc_attr($fhai_offer_price); ?>"
         data-currency="<?php echo esc_attr($fhai_currency_symbol); ?>"
         data-has-toggle="<?php echo $all_status_one ? '1' : '0'; ?>"
         data-add-to-contact="<?php echo !empty($fhai_product['add_to_contact']) ? '1' : '0'; ?>"
    >

        <div class="additional-container">
            <div class="packagess"
     <?php if ($fhai_background_color) : ?>
         style="background-color: <?php echo esc_attr($fhai_background_color); ?>;"
     <?php endif; ?>
>
                <div class="product-pricing <?php echo $fhai_product['highlight']==1?'highlighted-product':''; ?>">

                    <?php if ($fhai_product['highlight']==1) : ?>
                        <div class="popular-ribbon text-light">Most Popular</div>
                    <?php endif; ?>

                    <h1><?php echo esc_html($fhai_product['name']); ?></h1>

                    <?php if (!empty($fhai_product['short_description'])) : ?>
                        <div class="short-description"><?php echo wp_kses_post($fhai_product['short_description']); ?></div>
                    <?php endif; ?>

                    <?php if (!empty($fhai_product['add_to_contact'])) : ?>
                       
                        <h2 data-monthly-price="<?php echo esc_attr($fhai_monthly_price); ?>" data-yearly-price="<?php echo esc_attr($fhai_yearly_price); ?>"
                            style="font-size:30px !important; height:100px !important; line-height:50px; margin-top:30px;">
                            Custom Pricing
                        </h2>
                        <?php $fhai_custom_sales_url = get_option('fhai_custom_sales_url','https://www.example.com/'); ?>
                        <a href="<?php echo esc_url($fhai_custom_sales_url); ?>" class="purchase-btn">Custom Sales</a>
                    <?php else : ?>
                        <h2 data-monthly-price="<?php echo esc_attr($fhai_monthly_price); ?>" data-yearly-price="<?php echo esc_attr($fhai_yearly_price); ?>">
                            <?php echo esc_html($fhai_display_price); ?>
                        </h2>

                        <?php if ($fhai_offer_price>0) :
                            $fhai_formatted_orig = ($fhai_currency_code === 'INR' || $country_code==='IN') ? fhai_indian_number_format($fhai_monthly_price) : number_format($fhai_monthly_price, 2);
                        ?>
                            <p class="original-price"
                               data-monthly-orig="<?php echo esc_attr($fhai_monthly_price); ?>"
                               data-yearly-orig="<?php echo esc_attr($fhai_yearly_price); ?>">
                               <s><?php echo esc_html($fhai_currency_symbol.$fhai_formatted_orig); ?></s>
                            </p>
                        <?php endif; ?>

                        <p class="price-description"><?php echo wp_kses_post($fhai_product['price_description']); ?></p>
                        <a href="<?php echo esc_url($fhai_product['shoping_cart_link']); ?>" class="purchase-btn">Buy Now</a>
                    <?php endif; ?>

                </div> <!-- product-pricing -->

                <?php
             
                $fhai_description_with_tooltips = preg_replace_callback(
                    '/<li([^>]*)>(.*?)<\/li>/i',
                    function ($matches) {
                        $attributes = $matches[1];
                        $inner_html = $matches[2];
                        preg_match('/title="([^"]*)"/i', $attributes, $title_match);
                        $tooltip = $title_match[1] ?? wp_strip_all_tags($inner_html);
                        return '<li data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top" data-bs-content="' 
                                . esc_attr($tooltip) . '">' . $inner_html . '</li>';
                    },
                    $fhai_product['description']
                );
                ?>
                <div class="description"><?php echo wp_kses_post($fhai_description_with_tooltips); ?></div>

            </div> <!-- packagess -->
        </div> <!-- additional-container -->

    </div> <!-- product-container -->

<?php endforeach; ?>
</div> <!-- products-wrapper -->
