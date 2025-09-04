<?php
/**
 * Clase principal del plugin WooCommerce Carrito Científico
 *
 * @package WC_Scientific_Cart
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del plugin
 */
class WC_Scientific_Cart_Main {
    
    /**
     * Instancia única de la clase
     * @var WC_Scientific_Cart_Main
     */
    private static $instance = null;
    
    /**
     * Instancia de la integración ERP
     * @var WC_Scientific_Cart_ERP_Integration
     */
    public $erp_integration;
    
    /**
     * Instancia del customizador del carrito
     * @var WC_Scientific_Cart_Customizer
     */
    public $cart_customizer;
    
    /**
     * Instancia del manejador AJAX
     * @var WC_Scientific_Cart_Ajax_Handler
     */
    public $ajax_handler;
    
    /**
     * Obtener instancia única de la clase
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_classes();
        $this->init_hooks();
    }
    
    /**
     * Inicializar clases
     */
    private function init_classes() {
        // Inicializar integración ERP
        $this->erp_integration = new WC_Scientific_Cart_ERP_Integration();
        
        // Inicializar customizador del carrito
        $this->cart_customizer = new WC_Scientific_Cart_Customizer();
        
        // Inicializar manejador AJAX
        $this->ajax_handler = new WC_Scientific_Cart_Ajax_Handler();
        
        // Inicializar admin si estamos en el admin
        if (is_admin()) {
            new WC_Scientific_Cart_Admin();
        }
    }
    
    /**
     * Inicializar hooks principales
     */
    private function init_hooks() {
        // Agregar sección de presupuesto después del carrito
        add_action('woocommerce_after_cart', array($this, 'add_quote_section'));
        
        // Enqueue scripts y styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Agregar header personalizado antes del carrito
        add_action('woocommerce_before_cart', array($this, 'add_cart_header'));
         
        // Agregar campos personalizados al perfil de usuario
        add_action('show_user_profile', array($this, 'add_user_fields'));
        add_action('edit_user_profile', array($this, 'add_user_fields'));
        add_action('personal_options_update', array($this, 'save_user_fields'));
        add_action('edit_user_profile_update', array($this, 'save_user_fields'));
    }
    
    /**
     * Enqueue scripts y styles
     */
    public function enqueue_scripts() {
        if (!is_cart()) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'wc-scientific-cart',
            WC_SCIENTIFIC_CART_PLUGIN_URL . 'assets/css/scientific-cart.css',
            array(),
            WC_SCIENTIFIC_CART_VERSION
        );
        
        // JavaScript
        wp_enqueue_script(
            'wc-scientific-cart',
            WC_SCIENTIFIC_CART_PLUGIN_URL . 'assets/js/scientific-cart.js',
            array('jquery'),
            WC_SCIENTIFIC_CART_VERSION,
            true
        );
        
        // Localizar script
        wp_localize_script('wc-scientific-cart', 'wc_scientific_cart_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc_scientific_cart_nonce'),
            'requesting_quote' => __('Solicitando presupuesto...', 'wc-scientific-cart'),
            'quote_error' => __('Error al solicitar presupuesto. Por favor, inténtalo nuevamente.', 'wc-scientific-cart'),
            'confirm_remove' => __('¿Estás seguro de que quieres eliminar este producto?', 'wc-scientific-cart'),
            'cart_empty' => __('Tu carrito está vacío.', 'wc-scientific-cart'),
            'login_required' => __('Debes iniciar sesión para solicitar un presupuesto.', 'wc-scientific-cart')
        ));
    }
    
    /**
     * Agregar header del carrito
     */
    public function add_cart_header() {
        $title = get_option('wc_scientific_cart_header_title', __('Carrito de Soluciones Analíticas', 'wc-scientific-cart'));
        $subtitle = get_option('wc_scientific_cart_header_subtitle', __('Revisa tus productos seleccionados y solicita tu presupuesto personalizado', 'wc-scientific-cart'));
        
        $icon = '<svg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
            <circle cx="30" cy="30" r="28" fill="#E3F2FD" stroke="#2196F3" stroke-width="2"/>
            <path d="M30 15v10m0 10v10m10-15H20" stroke="#2196F3" stroke-width="2" stroke-linecap="round"/>
            <circle cx="22" cy="25" r="3" fill="#4FC3F7"/>
            <circle cx="38" cy="25" r="3" fill="#4FC3F7"/>
            <circle cx="30" cy="40" r="3" fill="#4FC3F7"/>
        </svg>';
        
        wc_scientific_cart_get_template('cart-header.php', array(
            'title' => $title,
            'subtitle' => $subtitle,
            'icon' => $icon
        ));
    }
    
    /**
     * Agregar sección de solicitud de presupuesto
     */
    public function add_quote_section() {
        if (!is_user_logged_in()) {
            wc_scientific_cart_get_template('login-message.php', array(
                'login_url' => wp_login_url(wc_get_cart_url())
            ));
            return;
        }
        
        $user = wp_get_current_user();
        $user_id = $user->ID;
        
        $user_data = array(
            'name' => $user->display_name,
            'email' => $user->user_email,
            'company' => get_user_meta($user_id, 'billing_company', true),
            'cuit' => get_user_meta($user_id, 'cuit', true),
            'phone' => get_user_meta($user_id, 'billing_phone', true)
        );
        
        $button_text = get_option('wc_scientific_cart_button_text', __('Solicitar Presupuesto', 'wc-scientific-cart'));
        
        wc_scientific_cart_get_template('quote-section.php', array(
            'user_data' => $user_data,
            'button_text' => $button_text
        ));
    }
    
    /**
     * Agregar campos personalizados al perfil de usuario
     */
    public function add_user_fields($user) {
        $fields_data = array(
            'cuit' => get_user_meta($user->ID, 'cuit', true),
            'industry_type' => get_user_meta($user->ID, 'industry_type', true),
            'lab_size' => get_user_meta($user->ID, 'lab_size', true),
            'annual_budget' => get_user_meta($user->ID, 'annual_budget', true)
        );
        
        include WC_SCIENTIFIC_CART_PLUGIN_PATH . 'admin/views/user-fields.php';
    }
    
    /**
     * Guardar campos personalizados del usuario
     */
    public function save_user_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }
        
        $fields = array('cuit', 'industry_type', 'lab_size', 'annual_budget');
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_user_meta($user_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }
    
    /**
     * Obtener estadísticas del plugin
     */
    public function get_stats() {
        global $wpdb;
        
        $quotes_table = $wpdb->prefix . 'scientific_cart_quotes';
        
        return array(
            'total_quotes' => $wpdb->get_var("SELECT COUNT(*) FROM $quotes_table"),
            'pending_quotes' => $wpdb->get_var("SELECT COUNT(*) FROM $quotes_table WHERE status = 'pending'"),
            'processed_quotes' => $wpdb->get_var("SELECT COUNT(*) FROM $quotes_table WHERE status = 'processed'"),
            'total_value' => $wpdb->get_var("SELECT SUM(cart_total) FROM $quotes_table"),
            'last_quote_date' => $wpdb->get_var("SELECT created_at FROM $quotes_table ORDER BY created_at DESC LIMIT 1")
        );
    }
}