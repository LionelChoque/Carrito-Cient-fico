?>
<?php if (!defined('ABSPATH')) exit; ?>

<div class="scientific-quote-section">
    <div class="quote-info">
        <h3><?php _e('Solicitar Presupuesto Personalizado', 'wc-scientific-cart'); ?></h3>
        <div class="customer-info">
            <p><span class="info-label"><?php _e('Cliente:', 'wc-scientific-cart'); ?></span> <?php echo esc_html($user_data['name']); ?></p>
            <?php if (!empty($user_data['company'])): ?>
                <p><span class="info-label"><?php _e('Empresa:', 'wc-scientific-cart'); ?></span> <?php echo esc_html($user_data['company']); ?></p>
            <?php endif; ?>
            <?php if (!empty($user_data['cuit'])): ?>
                <p><span class="info-label"><?php _e('CUIT:', 'wc-scientific-cart'); ?></span> <?php echo esc_html(wc_scientific_cart_format_cuit($user_data['cuit'])); ?></p>
            <?php endif; ?>
            <?php if (!empty($user_data['phone'])): ?>
                <p><span class="info-label"><?php _e('Teléfono:', 'wc-scientific-cart'); ?></span> <?php echo esc_html($user_data['phone']); ?></p>
            <?php endif; ?>
        </div>
        <p class="quote-description">
            <?php _e('Nuestro equipo de especialistas en soluciones analíticas analizará tus necesidades y te proporcionará una cotización detallada con precios preferenciales y condiciones especiales.', 'wc-scientific-cart'); ?>
        </p>
    </div>
    <button type="button" id="request-quote-btn" class="button scientific-quote-btn">
        <span class="btn-icon">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M18 5.5H2C1.45 5.5 1 5.95 1 6.5V15.5C1 16.05 1.45 16.5 2 16.5H18C18.55 16.5 19 16.05 19 15.5V6.5C19 5.95 18.55 5.5 18 5.5Z" fill="currentColor"/>
                <path d="M18 7.5L10 12.5L2 7.5" stroke="white" stroke-width="1.5"/>
            </svg>
        </span>
        <?php echo esc_html($button_text); ?>
    </button>
</div>

<?php
