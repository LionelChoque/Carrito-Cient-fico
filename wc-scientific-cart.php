<?php
/**
 * Plugin Name: WooCommerce Carrito Científico
 * Plugin URI: https://bairesanalitica.com
 * Description: Plugin de carrito personalizado para WooCommerce compatible con HPOS, con estética científica y funcionalidad de solicitud de presupuestos.
 * Version: 1.0.0
 * Author: Baires Analítica
 * Requires at least: 5.0
 * Tested up to: 6.5
 * WC requires at least: 6.0
 * WC tested up to: 9.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wc-scientific-cart
 * Domain Path: /languages
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('WC_SCIENTIFIC_CART_VERSION', '1.0.0');
define('WC_SCIENTIFIC_CART_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_SCIENTIFIC_CART_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WC_SCIENTIFIC_CART_PLUGIN_FILE', __FILE__);

/**
 * Función principal de inicialización del plugin
 */
function wc_scientific_cart_init() {
    // Verificar que WooCommerce esté activo
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'wc_scientific_cart_woocommerce_missing_notice');
        return;
    }

    // Declarar compatibilidad con HPOS
    add_action('before_woocommerce_init', 'wc_scientific_cart_declare_hpos_compatibility');

    // Cargar archivos del plugin
    wc_scientific_cart_includes();

    // Inicializar el plugin principal
    WC_Scientific_Cart_Main::get_instance();
}

/**
 * Declarar compatibilidad con HPOS
 */
function wc_scientific_cart_declare_hpos_compatibility() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
    }
}

/**
 * Cargar todos los archivos necesarios
 */
function wc_scientific_cart_includes() {
    // Funciones auxiliares
    require_once WC_SCIENTIFIC_CART_PLUGIN_PATH . 'includes/functions.php';
    
    // Clases principales
    // 
    require_once WC_SCIENTIFIC_CART_PLUGIN_PATH . 'includes/class-erp-integration.php';
    require_once WC_SCIENTIFIC_CART_PLUGIN_PATH . 'includes/class-ajax-handler.php';    
    require_once WC_SCIENTIFIC_CART_PLUGIN_PATH . 'includes/class-cart-customizer.php';
    require_once WC_SCIENTIFIC_CART_PLUGIN_PATH . 'includes/class-main.php';
    
    // Admin (solo en admin)
    if (is_admin()) {
        require_once WC_SCIENTIFIC_CART_PLUGIN_PATH . 'admin/class-admin.php';
        require_once WC_SCIENTIFIC_CART_PLUGIN_PATH . 'admin/class-settings.php';
    }
}

/**
 * Aviso si WooCommerce no está activo
 */
function wc_scientific_cart_woocommerce_missing_notice() {
    echo '<div class="error"><p><strong>' . 
         esc_html__('WooCommerce Carrito Científico', 'wc-scientific-cart') . 
         '</strong> ' . 
         esc_html__('requiere que WooCommerce esté instalado y activo.', 'wc-scientific-cart') . 
         '</p></div>';
}

/**
 * Activación del plugin
 */
function wc_scientific_cart_activate() {
    // Verificar versión de PHP
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        wp_die(__('Este plugin requiere PHP 7.4 o superior.', 'wc-scientific-cart'));
    }

    // Verificar que WooCommerce esté activo
    if (!class_exists('WooCommerce')) {
        wp_die(__('Este plugin requiere WooCommerce.', 'wc-scientific-cart'));
    }

    // Crear tablas necesarias
    wc_scientific_cart_create_tables();

    // Agregar opciones por defecto
    wc_scientific_cart_add_default_options();

    // Limpiar rewrite rules
    flush_rewrite_rules();
}

/**
 * Desactivación del plugin
 */
function wc_scientific_cart_deactivate() {
    // Limpiar rewrite rules
    flush_rewrite_rules();
}

/**
 * Crear tablas de base de datos
 */
function wc_scientific_cart_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Tabla para solicitudes de presupuesto
    $quotes_table = $wpdb->prefix . 'scientific_cart_quotes';
    $quotes_sql = "CREATE TABLE $quotes_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        customer_name varchar(255) NOT NULL,
        customer_email varchar(255) NOT NULL,
        company_name varchar(255),
        cuit varchar(20),
        phone varchar(50),
        products_data longtext NOT NULL,
        cart_total decimal(10,2) NOT NULL,
        status varchar(20) DEFAULT 'pending',
        priority varchar(10) DEFAULT 'medium',
        notes text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY customer_email (customer_email),
        KEY status (status),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    // Tabla para CRM leads
    $crm_table = $wpdb->prefix . 'scientific_cart_crm_leads';
    $crm_sql = "CREATE TABLE $crm_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        quote_id mediumint(9),
        customer_name varchar(255) NOT NULL,
        customer_email varchar(255) NOT NULL,
        company_name varchar(255),
        cuit varchar(20),
        phone varchar(50),
        industry_type varchar(50),
        lab_size varchar(20),
        annual_budget varchar(20),
        products_count int DEFAULT 0,
        estimated_value decimal(10,2) DEFAULT 0,
        lead_source varchar(50) DEFAULT 'cart_quote',
        status varchar(20) DEFAULT 'new',
        priority varchar(10) DEFAULT 'medium',
        assigned_to bigint(20),
        last_contact datetime,
        next_followup datetime,
        conversion_probability int DEFAULT 0,
        notes text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY quote_id (quote_id),
        KEY customer_email (customer_email),
        KEY status (status),
        KEY priority (priority),
        KEY assigned_to (assigned_to),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($quotes_sql);
    dbDelta($crm_sql);
}

/**
 * Agregar opciones por defecto
 */
function wc_scientific_cart_add_default_options() {
    $default_options = array(
        'wc_scientific_cart_erp_endpoint' => '',
        'wc_scientific_cart_erp_api_key' => '',
        'wc_scientific_cart_email_notifications' => 'yes',
        'wc_scientific_cart_admin_email' => get_option('admin_email'),
        'wc_scientific_cart_min_quote_amount' => '0',
        'wc_scientific_cart_auto_create_leads' => 'yes',
        'wc_scientific_cart_header_title' => __('Carrito de Soluciones Analíticas', 'wc-scientific-cart'),
        'wc_scientific_cart_header_subtitle' => __('Revisa tus productos seleccionados y solicita tu presupuesto personalizado', 'wc-scientific-cart'),
        'wc_scientific_cart_button_text' => __('Solicitar Presupuesto', 'wc-scientific-cart'),
        'wc_scientific_cart_success_message' => __('¡Presupuesto solicitado exitosamente! Nos pondremos en contacto contigo pronto.', 'wc-scientific-cart'),
        'wc_scientific_cart_error_message' => __('Error al solicitar presupuesto. Por favor, inténtalo nuevamente.', 'wc-scientific-cart')
    );

    foreach ($default_options as $option_name => $option_value) {
        if (false === get_option($option_name)) {
            add_option($option_name, $option_value);
        }
    }
}

/**
 * Cargar traducciones
 */
function wc_scientific_cart_load_textdomain() {
    load_plugin_textdomain(
        'wc-scientific-cart',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}

// Hooks de WordPress
add_action('plugins_loaded', 'wc_scientific_cart_init');
add_action('plugins_loaded', 'wc_scientific_cart_load_textdomain');

// Hooks de activación/desactivación
register_activation_hook(__FILE__, 'wc_scientific_cart_activate');
register_deactivation_hook(__FILE__, 'wc_scientific_cart_deactivate');

/**
 * Agregar enlaces en la página de plugins
 */
function wc_scientific_cart_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=wc-scientific-cart-settings') . '">' . __('Configuración', 'wc-scientific-cart') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wc_scientific_cart_plugin_action_links');
?>
