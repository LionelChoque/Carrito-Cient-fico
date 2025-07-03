<?php
/**
 * Funciones auxiliares para el plugin WooCommerce Carrito Científico
 *
 * @package WC_Scientific_Cart
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Obtener template del plugin
 *
 * @param string $template_name Nombre del template
 * @param array $args Argumentos para pasar al template
 * @param string $template_path Ruta del template (opcional)
 * @param string $default_path Ruta por defecto (opcional)
 */
function wc_scientific_cart_get_template($template_name, $args = array(), $template_path = '', $default_path = '') {
    if (!empty($args) && is_array($args)) {
        extract($args);
    }

    $located = wc_scientific_cart_locate_template($template_name, $template_path, $default_path);

    if (!file_exists($located)) {
        wc_doing_it_wrong(__FUNCTION__, sprintf(__('%s no existe.', 'wc-scientific-cart'), '<code>' . $located . '</code>'), WC_SCIENTIFIC_CART_VERSION);
        return;
    }

    // Permitir filtrar los argumentos del template
    $args = apply_filters('wc_scientific_cart_get_template_args', $args, $template_name);

    do_action('wc_scientific_cart_before_template_part', $template_name, $template_path, $located, $args);

    include $located;

    do_action('wc_scientific_cart_after_template_part', $template_name, $template_path, $located, $args);
}

/**
 * Obtener template HTML como string
 *
 * @param string $template_name Nombre del template
 * @param array $args Argumentos para pasar al template
 * @param string $template_path Ruta del template (opcional)
 * @param string $default_path Ruta por defecto (opcional)
 * @return string
 */
function wc_scientific_cart_get_template_html($template_name, $args = array(), $template_path = '', $default_path = '') {
    ob_start();
    wc_scientific_cart_get_template($template_name, $args, $template_path, $default_path);
    return ob_get_clean();
}

/**
 * Localizar template del plugin
 *
 * @param string $template_name Nombre del template
 * @param string $template_path Ruta del template
 * @param string $default_path Ruta por defecto
 * @return string
 */
function wc_scientific_cart_locate_template($template_name, $template_path = '', $default_path = '') {
    if (!$template_path) {
        $template_path = 'wc-scientific-cart/';
    }

    if (!$default_path) {
        $default_path = WC_SCIENTIFIC_CART_PLUGIN_PATH . 'templates/';
    }

    // Buscar en el tema activo
    $template = locate_template(array(
        trailingslashit($template_path) . $template_name,
        $template_name,
    ));

    // Si no existe en el tema, usar el del plugin
    if (!$template) {
        $template = $default_path . $template_name;
    }

    // Permitir filtrar la localización del template
    return apply_filters('wc_scientific_cart_locate_template', $template, $template_name, $template_path);
}

/**
 * Verificar si el usuario actual puede solicitar presupuestos
 *
 * @param int $user_id ID del usuario (opcional)
 * @return bool
 */
function wc_scientific_cart_user_can_request_quote($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    // Verificar si el usuario está logueado
    if (!is_user_logged_in()) {
        return false;
    }

    // Verificar roles permitidos
    $allowed_roles = apply_filters('wc_scientific_cart_allowed_roles', array('customer', 'administrator', 'shop_manager'));
    $user = get_user_by('ID', $user_id);
    
    if (!$user) {
        return false;
    }

    $user_roles = $user->roles;
    $has_allowed_role = array_intersect($user_roles, $allowed_roles);

    if (empty($has_allowed_role)) {
        return false;
    }

    // Hook para filtros adicionales
    return apply_filters('wc_scientific_cart_user_can_request_quote', true, $user_id);
}

/**
 * Obtener datos del carrito para presupuesto
 *
 * @return array
 */
function wc_scientific_cart_get_cart_data() {
    $cart = WC()->cart;
    
    if ($cart->is_empty()) {
        return array();
    }

    $cart_data = array(
        'items' => array(),
        'totals' => array(
            'subtotal' => $cart->get_subtotal(),
            'tax' => $cart->get_total_tax(),
            'total' => $cart->get_total('raw')
        ),
        'item_count' => $cart->get_cart_contents_count()
    );

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        
        $cart_data['items'][] = array(
            'key' => $cart_item_key,
            'product_id' => $product->get_id(),
            'variation_id' => $cart_item['variation_id'] ?? 0,
            'name' => $product->get_name(),
            'sku' => $product->get_sku(),
            'quantity' => $cart_item['quantity'],
            'price' => $product->get_price(),
            'line_total' => $cart_item['line_total'],
            'line_tax' => $cart_item['line_tax'],
            'product_data' => wc_scientific_cart_get_product_scientific_data($product)
        );
    }

    return apply_filters('wc_scientific_cart_get_cart_data', $cart_data);
}

/**
 * Obtener datos científicos del producto
 *
 * @param WC_Product $product Producto
 * @return array
 */
function wc_scientific_cart_get_product_scientific_data($product) {
    $product_id = $product->get_id();
    
    return array(
        'catalog_number' => get_post_meta($product_id, '_catalog_number', true),
        'cas_number' => get_post_meta($product_id, '_cas_number', true),
        'molecular_formula' => get_post_meta($product_id, '_molecular_formula', true),
        'molecular_weight' => get_post_meta($product_id, '_molecular_weight', true),
        'purity_level' => get_post_meta($product_id, '_purity_level', true),
        'storage_conditions' => get_post_meta($product_id, '_storage_conditions', true),
        'hazard_class' => get_post_meta($product_id, '_hazard_class', true),
        'manufacturer' => get_post_meta($product_id, '_manufacturer', true),
        'technical_specifications' => get_post_meta($product_id, '_technical_specifications', true)
    );
}

/**
 * Formatear precio para mostrar
 *
 * @param float $price Precio
 * @param array $args Argumentos de formato
 * @return string
 */
function wc_scientific_cart_format_price($price, $args = array()) {
    $args = wp_parse_args($args, array(
        'currency' => get_woocommerce_currency(),
        'decimal_separator' => wc_get_price_decimal_separator(),
        'thousand_separator' => wc_get_price_thousand_separator(),
        'decimals' => wc_get_price_decimals(),
        'price_format' => get_woocommerce_price_format()
    ));

    $formatted_price = number_format(
        $price,
        $args['decimals'],
        $args['decimal_separator'],
        $args['thousand_separator']
    );

    $formatted_price = sprintf($args['price_format'], get_woocommerce_currency_symbol($args['currency']), $formatted_price);

    return apply_filters('wc_scientific_cart_format_price', $formatted_price, $price, $args);
}

/**
 * Validar CUIT argentino
 *
 * @param string $cuit CUIT a validar
 * @return bool
 */
function wc_scientific_cart_validate_cuit($cuit) {
    // Remover guiones y espacios
    $cuit = preg_replace('/[^0-9]/', '', $cuit);
    
    // Verificar longitud
    if (strlen($cuit) !== 11) {
        return false;
    }

    // Algoritmo de validación CUIT
    $multiplicadores = array(5, 4, 3, 2, 7, 6, 5, 4, 3, 2);
    $suma = 0;

    for ($i = 0; $i < 10; $i++) {
        $suma += $cuit[$i] * $multiplicadores[$i];
    }

    $resto = $suma % 11;
    $digito_verificador = $resto < 2 ? $resto : 11 - $resto;

    return $digito_verificador == $cuit[10];
}

/**
 * Formatear CUIT para mostrar
 *
 * @param string $cuit CUIT sin formato
 * @return string CUIT formateado
 */
function wc_scientific_cart_format_cuit($cuit) {
    $cuit = preg_replace('/[^0-9]/', '', $cuit);
    
    if (strlen($cuit) === 11) {
        return substr($cuit, 0, 2) . '-' . substr($cuit, 2, 8) . '-' . substr($cuit, 10, 1);
    }
    
    return $cuit;
}

/**
 * Obtener opciones de industria
 *
 * @return array
 */
function wc_scientific_cart_get_industry_options() {
    return apply_filters('wc_scientific_cart_industry_options', array(
        '' => __('Seleccionar...', 'wc-scientific-cart'),
        'pharmaceutical' => __('Farmacéutica', 'wc-scientific-cart'),
        'food' => __('Alimentaria', 'wc-scientific-cart'),
        'environmental' => __('Ambiental', 'wc-scientific-cart'),
        'chemical' => __('Química', 'wc-scientific-cart'),
        'petrochemical' => __('Petroquímica', 'wc-scientific-cart'),
        'cosmetic' => __('Cosmética', 'wc-scientific-cart'),
        'academic' => __('Académico/Investigación', 'wc-scientific-cart'),
        'quality_control' => __('Control de Calidad', 'wc-scientific-cart'),
        'mining' => __('Minería', 'wc-scientific-cart'),
        'agriculture' => __('Agricultura', 'wc-scientific-cart'),
        'textile' => __('Textil', 'wc-scientific-cart'),
        'water_treatment' => __('Tratamiento de Agua', 'wc-scientific-cart'),
        'other' => __('Otro', 'wc-scientific-cart')
    ));
}

/**
 * Obtener opciones de tamaño de laboratorio
 *
 * @return array
 */
function wc_scientific_cart_get_lab_size_options() {
    return apply_filters('wc_scientific_cart_lab_size_options', array(
        '' => __('Seleccionar...', 'wc-scientific-cart'),
        'micro' => __('Micro (1 analista)', 'wc-scientific-cart'),
        'small' => __('Pequeño (2-5 analistas)', 'wc-scientific-cart'),
        'medium' => __('Mediano (6-20 analistas)', 'wc-scientific-cart'),
        'large' => __('Grande (21-50 analistas)', 'wc-scientific-cart'),
        'enterprise' => __('Corporativo (50+ analistas)', 'wc-scientific-cart')
    ));
}

/**
 * Obtener opciones de presupuesto anual
 *
 * @return array
 */
function wc_scientific_cart_get_budget_options() {
    return apply_filters('wc_scientific_cart_budget_options', array(
        '' => __('Seleccionar...', 'wc-scientific-cart'),
        'under_10k' => __('Menos de $10,000', 'wc-scientific-cart'),
        '10k_25k' => __('$10,000 - $25,000', 'wc-scientific-cart'),
        '25k_50k' => __('$25,000 - $50,000', 'wc-scientific-cart'),
        '50k_100k' => __('$50,000 - $100,000', 'wc-scientific-cart'),
        '100k_250k' => __('$100,000 - $250,000', 'wc-scientific-cart'),
        '250k_500k' => __('$250,000 - $500,000', 'wc-scientific-cart'),
        'over_500k' => __('Más de $500,000', 'wc-scientific-cart')
    ));
}

/**
 * Registrar log del plugin
 *
 * @param string $message Mensaje a registrar
 * @param string $level Nivel del log (info, warning, error)
 * @param array $context Contexto adicional
 */
function wc_scientific_cart_log($message, $level = 'info', $context = array()) {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }

    $logger = wc_get_logger();
    $logger->log($level, $message, array(
        'source' => 'wc-scientific-cart',
        'context' => $context
    ));
}

/**
 * Verificar si el carrito cumple con los requisitos mínimos para presupuesto
 *
 * @return bool|WP_Error
 */
function wc_scientific_cart_validate_quote_requirements() {
    $cart = WC()->cart;
    
    // Verificar que el carrito no esté vacío
    if ($cart->is_empty()) {
        return new WP_Error('empty_cart', __('El carrito está vacío.', 'wc-scientific-cart'));
    }

    // Verificar monto mínimo
    $min_amount = get_option('wc_scientific_cart_min_quote_amount', 0);
    if ($min_amount > 0 && $cart->get_total('raw') < $min_amount) {
        return new WP_Error('min_amount', sprintf(
            __('El monto mínimo para solicitar presupuesto es %s.', 'wc-scientific-cart'),
            wc_scientific_cart_format_price($min_amount)
        ));
    }

    // Verificar usuario logueado
    if (!is_user_logged_in()) {
        return new WP_Error('not_logged_in', __('Debes iniciar sesión para solicitar un presupuesto.', 'wc-scientific-cart'));
    }

    // Verificar permisos del usuario
    if (!wc_scientific_cart_user_can_request_quote()) {
        return new WP_Error('no_permission', __('No tienes permisos para solicitar presupuestos.', 'wc-scientific-cart'));
    }

    // Hook para validaciones adicionales
    $custom_validation = apply_filters('wc_scientific_cart_validate_quote_requirements', true);
    if (is_wp_error($custom_validation)) {
        return $custom_validation;
    }

    return true;
}

/**
 * Obtener información de contacto del sitio
 *
 * @return array
 */
function wc_scientific_cart_get_contact_info() {
    return apply_filters('wc_scientific_cart_contact_info', array(
        'company_name' => get_option('wc_scientific_cart_company_name', get_bloginfo('name')),
        'email' => get_option('wc_scientific_cart_admin_email', get_option('admin_email')),
        'phone' => get_option('wc_scientific_cart_phone', ''),
        'address' => get_option('wc_scientific_cart_address', ''),
        'website' => home_url()
    ));
}

/**
 * Sanitizar datos de entrada
 *
 * @param mixed $data Datos a sanitizar
 * @param string $type Tipo de sanitización
 * @return mixed
 */
function wc_scientific_cart_sanitize_data($data, $type = 'text') {
    switch ($type) {
        case 'email':
            return sanitize_email($data);
        case 'url':
            return esc_url_raw($data);
        case 'int':
            return intval($data);
        case 'float':
            return floatval($data);
        case 'textarea':
            return sanitize_textarea_field($data);
        case 'html':
            return wp_kses_post($data);
        case 'cuit':
            return preg_replace('/[^0-9\-]/', '', $data);
        default:
            return sanitize_text_field($data);
    }
}

/**
 * Verificar si el plugin está en modo debug
 *
 * @return bool
 */
function wc_scientific_cart_is_debug_mode() {
    return defined('WP_DEBUG') && WP_DEBUG && get_option('wc_scientific_cart_debug_mode', 'no') === 'yes';
}

/**
 * Obtener configuraciones por defecto del plugin
 *
 * @return array
 */
function wc_scientific_cart_get_default_settings() {
    return array(
        'erp_endpoint' => '',
        'erp_api_key' => '',
        'email_notifications' => 'yes',
        'admin_email' => get_option('admin_email'),
        'min_quote_amount' => '0',
        'auto_create_leads' => 'yes',
        'header_title' => __('Carrito de Soluciones Analíticas', 'wc-scientific-cart'),
        'header_subtitle' => __('Revisa tus productos seleccionados y solicita tu presupuesto personalizado', 'wc-scientific-cart'),
        'button_text' => __('Solicitar Presupuesto', 'wc-scientific-cart'),
        'success_message' => __('¡Presupuesto solicitado exitosamente! Nos pondremos en contacto contigo pronto.', 'wc-scientific-cart'),
        'error_message' => __('Error al solicitar presupuesto. Por favor, inténtalo nuevamente.', 'wc-scientific-cart'),
        'require_cuit' => 'yes',
        'require_company' => 'yes',
        'debug_mode' => 'no'
    );
}
?>
