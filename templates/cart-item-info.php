?>
<?php if (!defined('ABSPATH')) exit; ?>

<div class="scientific-product-info">
    <span class="product-name"><?php echo $product_name; ?></span>
    <?php if (!empty($product_data['sku'])): ?>
        <span class="product-sku">SKU: <?php echo esc_html($product_data['sku']); ?></span>
    <?php endif; ?>
</div>

<?php
