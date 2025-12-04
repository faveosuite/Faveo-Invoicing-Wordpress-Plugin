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

<?php $visible_count = 0; ?>
<div class="products-wrapper" data-count="0">

<?php foreach ($products as $product) :
$is_hidden = false;
    // Product styles
$product_styles_group1 = $product_styles_group2 = $product_styles_group3 = $product_styles_group4 = $product_styles_group5 = $product_styles_default = '';

if (!empty($product['pricing-background-color'])) {
    $background_color_style = 'style="background-color: ' . esc_attr($product['pricing-background-color']) . ';"';
} else {
    if (in_array($product['name'], ['Helpdesk Freelancer','ServiceDesk Freelancer','Faveo Cloud HelpDesk','Support service','Customization','Faveo Upgrade','Install service'])) {
        $product_styles_group1 = ' product-styles-group1';
    } elseif (in_array($product['name'], ['Helpdesk Startup','Servicedesk Startup','Helpdesk Startup (Recurring)','ServiceDesk Startup (Recurring)','Faveo Cloud Helpdesk Startup','Faveo Cloud ServiceDesk  Startup'])) {
        $product_styles_group2 = ' product-styles-group2';
    } elseif (in_array($product['name'], ['Helpdesk SME ','ServiceDesk SME','Helpdesk SME (Recurring)','ServiceDesk SME (Recurring)','Faveo Cloud Helpdesk SME','Faveo Cloud ServiceDesk SME'])) {
        $product_styles_group3 = ' product-styles-group3';
    } elseif (in_array($product['name'], ['Helpdesk Enterprise','Helpdesk Enterprise (Recurring)','ServiceDesk Enterprise','ServiceDesk Enterprise (Recurring)','Faveo Cloud Helpdesk Enterprise','Faveo Cloud ServiceDesk Enterprise'])) {
        $product_styles_group4 = ' product-styles-group4';
    } elseif (in_array($product['name'], ['Helpdesk Enterprise Pro','ServiceDesk Enterprise Pro'])) {
        $product_styles_group5 = ' product-styles-group5';
    } else {
        // ✅ Default style if product doesn't match any group
        $product_styles_default = ' product-styles-default';
    }
}


    // Currency
    $currency_code = !empty($product['currency']) ? strtoupper($product['currency']) : '';
    $currency_symbol = fhai_currency_symbol_combined($currency_code ?: $country_code);

    // Prices
    $monthly_price = floatval($product['add_price']);
    $yearly_price = floatval($product['add_price']);
    $offer_price = floatval($product['offer_price'] ?? 0);

    $effective_price = $offer_price > 0 ? $monthly_price - ($monthly_price * ($offer_price / 100)) : $monthly_price;

    // Format display price
    if (class_exists('NumberFormatter')) {
        $formatter = new NumberFormatter('en', NumberFormatter::CURRENCY);
        $display_price = $formatter->formatCurrency($effective_price, $currency_code ?: 'USD');
    } else {
        $display_price = ($currency_code === 'INR' || $country_code === 'IN') ? indian_number_format($effective_price) : number_format($effective_price, 2);
    }

    // product key (prefer an ID if available, else sanitized name)
    $product_key = isset($product['id']) ? 'p-' . intval($product['id']) : sanitize_title($product['name']);

 $visible_count++;
?>
    
    <div class="product-container <?php echo esc_attr($atts['style'] . ($product['highlight']==1?' highlighted-product-container':'') . $product_styles_group1 . $product_styles_group2 . $product_styles_group3 . $product_styles_group4 . $product_styles_group5 . $product_styles_default); ?>"
         data-product-key="<?php echo esc_attr($product_key); ?>"
         data-group="<?php echo esc_attr($group_id); ?>"
         data-days="<?php echo esc_attr($product['days']); ?>"
         data-monthly="<?php echo esc_attr($monthly_price); ?>"
         data-yearly="<?php echo esc_attr($yearly_price); ?>"
         data-offer="<?php echo esc_attr($offer_price); ?>"
         data-currency="<?php echo esc_attr($currency_symbol); ?>"
         data-has-toggle="<?php echo $all_status_one ? '1' : '0'; ?>"
         data-add-to-contact="<?php echo !empty($product['add_to_contact']) ? '1' : '0'; ?>"
    >

        <div class="additional-container">
            <div class="packagess" <?php echo $background_color_style ?? ''; ?>>
                <div class="product-pricing <?php echo $product['highlight']==1?'highlighted-product':''; ?>">

                    <?php if ($product['highlight']==1) : ?>
                        <div class="popular-ribbon text-light">Most Popular</div>
                    <?php endif; ?>

                    <h1><?php echo esc_html($product['name']); ?></h1>

                    <?php if (!empty($product['short_description'])) : ?>
                        <div class="short-description"><?php echo wp_kses_post($product['short_description']); ?></div>
                    <?php endif; ?>

                    <?php if (!empty($product['add_to_contact'])) : ?>
                        <!-- keep data attributes so JS can still read if needed -->
                        <h2 data-monthly-price="<?php echo esc_attr($monthly_price); ?>" data-yearly-price="<?php echo esc_attr($yearly_price); ?>"
                            style="font-size:30px !important; height:100px !important; line-height:50px; margin-top:30px;">
                            Custom Pricing
                        </h2>
                        <?php $custom_sales_url = get_option('fhai_custom_sales_url','https://www.example.com/'); ?>
                        <a href="<?php echo esc_url($custom_sales_url); ?>" class="purchase-btn">Custom Sales</a>
                    <?php else : ?>
                        <h2 data-monthly-price="<?php echo esc_attr($monthly_price); ?>" data-yearly-price="<?php echo esc_attr($yearly_price); ?>">
                            <?php echo esc_html($display_price); ?>
                        </h2>

                        <?php if ($offer_price>0) :
                            $formatted_orig = ($currency_code === 'INR' || $country_code==='IN') ? indian_number_format($monthly_price) : number_format($monthly_price, 2);
                        ?>
                            <p class="original-price"
                               data-monthly-orig="<?php echo esc_attr($monthly_price); ?>"
                               data-yearly-orig="<?php echo esc_attr($yearly_price); ?>">
                               <s><?php echo esc_html($currency_symbol.$formatted_orig); ?></s>
                            </p>
                        <?php endif; ?>

                        <p class="price-description"><?php echo wp_kses_post($product['price_description']); ?></p>
                        <a href="<?php echo esc_url($product['shoping_cart_link']); ?>" class="purchase-btn">Buy Now</a>
                    <?php endif; ?>

                </div> <!-- product-pricing -->

                <?php
                // Keep description with tooltips
                $description_with_tooltips = preg_replace_callback(
                    '/<li([^>]*)>(.*?)<\/li>/i',
                    function ($matches) {
                        $attributes = $matches[1];
                        $inner_html = $matches[2];
                        preg_match('/title="([^"]*)"/i', $attributes, $title_match);
                        $tooltip = $title_match[1] ?? wp_strip_all_tags($inner_html);
                        return '<li data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top" data-bs-content="' 
                                . esc_attr($tooltip) . '">' . $inner_html . '</li>';
                    },
                    $product['description']
                );
                ?>
                <div class="description"><?php echo wp_kses_post($description_with_tooltips); ?></div>

            </div> <!-- packagess -->
        </div> <!-- additional-container -->

    </div> <!-- product-container -->

<?php endforeach; ?>
</div> <!-- products-wrapper -->
