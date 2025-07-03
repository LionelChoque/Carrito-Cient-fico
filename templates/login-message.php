?>
<?php if (!defined('ABSPATH')) exit; ?>

<div class="login-message">
    <h3><?php _e('Inicia Sesión para Solicitar Presupuesto', 'wc-scientific-cart'); ?></h3>
    <p><?php _e('Para solicitar un presupuesto personalizado necesitas tener una cuenta en nuestro sitio.', 'wc-scientific-cart'); ?></p>
    <a href="<?php echo esc_url($login_url); ?>" class="login-btn">
        <?php _e('Iniciar Sesión', 'wc-scientific-cart'); ?>
    </a>
    <p class="register-link">
        <?php printf(
            __('¿No tienes cuenta? %sRegístrate aquí%s', 'wc-scientific-cart'),
            '<a href="' . esc_url(wp_registration_url()) . '">',
            '</a>'
        ); ?>
    </p>
</div>

<?php
