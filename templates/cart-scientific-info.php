?>
<?php if (!defined('ABSPATH')) exit; ?>

<div class="scientific-info-section">
    <h4><?php _e('Información del Carrito', 'wc-scientific-cart'); ?></h4>
    <div class="scientific-stats">
        <div class="scientific-stat">
            <span class="stat-value"><?php echo $stats['total_products']; ?></span>
            <span class="stat-label"><?php _e('Productos', 'wc-scientific-cart'); ?></span>
        </div>
        <?php if ($stats['unique_suppliers'] > 0): ?>
        <div class="scientific-stat">
            <span class="stat-value"><?php echo $stats['unique_suppliers']; ?></span>
            <span class="stat-label"><?php _e('Proveedores', 'wc-scientific-cart'); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($stats['hazardous_items'] > 0): ?>
        <div class="scientific-stat">
            <span class="stat-value"><?php echo $stats['hazardous_items']; ?></span>
            <span class="stat-label"><?php _e('Ítems Peligrosos', 'wc-scientific-cart'); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($stats['high_purity_items'] > 0): ?>
        <div class="scientific-stat">
            <span class="stat-value"><?php echo $stats['high_purity_items']; ?></span>
            <span class="stat-label"><?php _e('Alta Pureza', 'wc-scientific-cart'); ?></span>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
