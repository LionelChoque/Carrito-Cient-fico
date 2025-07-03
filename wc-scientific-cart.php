<?php
/**
 * Plugin Name: WooCommerce Carrito Científico
 * Plugin URI: https://tu-sitio.com
 * Description: Plugin de carrito personalizado para WooCommerce compatible con HPOS, con estética científica y funcionalidad de solicitud de presupuestos.
 * Version: 1.0.0
 * Author: Tu Empresa
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

/**
 * Clase principal del plugin
 */
class WC_Scientific_Cart {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    /**
     * Inicializar el plugin
     */
    public function init() {
        // Verificar que WooCommerce esté activo
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Declarar compatibilidad con HPOS
        add_action('before_woocommerce_init', array($this, 'declare_hpos_compatibility'));
        
        // Cargar archivos del plugin
        $this->load_plugin_textdomain();
        $this->includes();
        
        // Hooks principales
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('woocommerce_before_cart', array($this, 'add_custom_cart_header'));
        add_action('woocommerce_cart_actions', array($this, 'add_quote_button'));
        add_action('wp_ajax_request_quote', array($this, 'handle_quote_request'));
        add_action('wp_ajax_nopriv_request_quote', array($this, 'handle_quote_request_redirect'));
        
        // Filtros para personalizar el carrito
        add_filter('woocommerce_cart_item_name', array($this, 'customize_cart_item_display'), 10, 3);
        add_filter('woocommerce_cart_totals_order_total_html', array($this, 'customize_total_display'));
    }
    
    /**
     * Declarar compatibilidad con HPOS
     */
    public function declare_hpos_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                __FILE__,
                true
            );
        }
    }
    
    /**
     * Cargar archivos necesarios
     */
    private function includes() {
        require_once WC_SCIENTIFIC_CART_PLUGIN_PATH . 'includes/class-erp-integration.php';
        require_once WC_SCIENTIFIC_CART_PLUGIN_PATH . 'includes/class-cart-customizer.php';
    }
    
    /**
     * Cargar traducciones
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain('wc-scientific-cart', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Aviso si WooCommerce no está activo
     */
    public function woocommerce_missing_notice() {
        echo '<div class="error"><p><strong>' . 
             esc_html__('WooCommerce Carrito Científico', 'wc-scientific-cart') . 
             '</strong> ' . 
             esc_html__('requiere que WooCommerce esté instalado y activo.', 'wc-scientific-cart') . 
             '</p></div>';
    }
    
    /**
     * Encolar scripts y estilos
     */
    public function enqueue_scripts() {
        if (is_cart()) {
            wp_enqueue_style(
                'wc-scientific-cart-style',
                WC_SCIENTIFIC_CART_PLUGIN_URL . 'assets/css/scientific-cart.css',
                array(),
                WC_SCIENTIFIC_CART_VERSION
            );
            
            wp_enqueue_script(
                'wc-scientific-cart-script',
                WC_SCIENTIFIC_CART_PLUGIN_URL . 'assets/js/scientific-cart.js',
                array('jquery'),
                WC_SCIENTIFIC_CART_VERSION,
                true
            );
            
            wp_localize_script('wc-scientific-cart-script', 'wc_scientific_cart_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wc_scientific_cart_nonce'),
                'requesting_quote' => __('Solicitando presupuesto...', 'wc-scientific-cart'),
                'quote_success' => __('¡Presupuesto solicitado exitosamente! Nos pondremos en contacto contigo pronto.', 'wc-scientific-cart'),
                'quote_error' => __('Error al solicitar presupuesto. Por favor, inténtalo nuevamente.', 'wc-scientific-cart'),
                'login_required' => __('Debes iniciar sesión para solicitar un presupuesto.', 'wc-scientific-cart')
            ));
        }
    }
    
    /**
     * Agregar header personalizado al carrito
     */
    public function add_custom_cart_header() {
        ?>
        <div class="scientific-cart-header">
            <div class="scientific-icon">
                <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 5C19.17 5 18.5 5.67 18.5 6.5V8H21.5V6.5C21.5 5.67 20.83 5 20 5Z" fill="#2196F3"/>
                    <path d="M16 8V6.5C16 4.29 17.79 2.5 20 2.5C22.21 2.5 24 4.29 24 6.5V8H28C29.1 8 30 8.9 30 10V32C30 33.1 29.1 34 28 34H12C10.9 34 10 33.1 10 32V10C10 8.9 10.9 8 12 8H16Z" fill="#4FC3F7"/>
                    <circle cx="20" cy="18" r="3" fill="#E3F2FD"/>
                    <path d="M17 25H23V27H17V25Z" fill="#1976D2"/>
                    <path d="M15 29H25V31H15V29Z" fill="#1976D2"/>
                </svg>
            </div>
            <h2 class="scientific-title"><?php _e('Carrito de Soluciones Analíticas', 'wc-scientific-cart'); ?></h2>
            <p class="scientific-subtitle"><?php _e('Revisa tus productos seleccionados y solicita tu presupuesto personalizado', 'wc-scientific-cart'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Agregar botón de solicitar presupuesto
     */
    public function add_quote_button() {
        if (!is_user_logged_in()) {
            return;
        }
        
        $current_user = wp_get_current_user();
        $company_name = get_user_meta($current_user->ID, 'billing_company', true);
        $cuit = get_user_meta($current_user->ID, 'cuit', true); // Campo personalizado para CUIT
        
        ?>
        <div class="scientific-quote-section">
            <div class="quote-info">
                <h3><?php _e('Solicitar Presupuesto Personalizado', 'wc-scientific-cart'); ?></h3>
                <div class="customer-info">
                    <p><strong><?php _e('Cliente:', 'wc-scientific-cart'); ?></strong> <?php echo esc_html($current_user->display_name); ?></p>
                    <?php if ($company_name): ?>
                        <p><strong><?php _e('Empresa:', 'wc-scientific-cart'); ?></strong> <?php echo esc_html($company_name); ?></p>
                    <?php endif; ?>
                    <?php if ($cuit): ?>
                        <p><strong><?php _e('CUIT:', 'wc-scientific-cart'); ?></strong> <?php echo esc_html($cuit); ?></p>
                    <?php endif; ?>
                </div>
                <p class="quote-description">
                    <?php _e('Nuestro equipo de expertos analizará tus necesidades y te proporcionará una cotización detallada con precios preferenciales y condiciones especiales.', 'wc-scientific-cart'); ?>
                </p>
            </div>
            <button type="button" id="request-quote-btn" class="button scientific-quote-btn">
                <span class="btn-icon">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 5.5H2C1.45 5.5 1 5.95 1 6.5V15.5C1 16.05 1.45 16.5 2 16.5H18C18.55 16.5 19 16.05 19 15.5V6.5C19 5.95 18.55 5.5 18 5.5Z" fill="currentColor"/>
                        <path d="M18 7.5L10 12.5L2 7.5" stroke="white" stroke-width="1.5"/>
                    </svg>
                </span>
                <?php _e('Solicitar Presupuesto', 'wc-scientific-cart'); ?>
            </button>
        </div>
        <?php
    }
    
    /**
     * Manejar solicitud de presupuesto AJAX
     */
    public function handle_quote_request() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wc_scientific_cart_nonce')) {
            wp_die(__('Error de seguridad', 'wc-scientific-cart'));
        }
        
        // Verificar que el usuario esté loggeado
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('Debes iniciar sesión para solicitar un presupuesto.', 'wc-scientific-cart')
            ));
        }
        
        $current_user = wp_get_current_user();
        $cart = WC()->cart;
        
        if ($cart->is_empty()) {
            wp_send_json_error(array(
                'message' => __('Tu carrito está vacío.', 'wc-scientific-cart')
            ));
        }
        
        // Preparar datos para el ERP
        $quote_data = array(
            'customer_name' => $current_user->display_name,
            'customer_email' => $current_user->user_email,
            'company_name' => get_user_meta($current_user->ID, 'billing_company', true),
            'cuit' => get_user_meta($current_user->ID, 'cuit', true),
            'phone' => get_user_meta($current_user->ID, 'billing_phone', true),
            'products' => array(),
            'cart_total' => $cart->get_cart_contents_total(),
            'timestamp' => current_time('mysql')
        );
        
        // Obtener productos del carrito
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $quote_data['products'][] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'sku' => $product->get_sku(),
                'quantity' => $cart_item['quantity'],
                'price' => $product->get_price(),
                'total' => $cart_item['line_total']
            );
        }
        
        // Enviar al ERP
        $erp_integration = new WC_Scientific_Cart_ERP_Integration();
        $result = $erp_integration->send_quote_request($quote_data);
        
        if ($result['success']) {
            // Guardar la solicitud en la base de datos para seguimiento
            $this->save_quote_request($quote_data);
            
            wp_send_json_success(array(
                'message' => __('¡Presupuesto solicitado exitosamente! Nos pondremos en contacto contigo pronto.', 'wc-scientific-cart')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Error al solicitar presupuesto. Por favor, inténtalo nuevamente.', 'wc-scientific-cart')
            ));
        }
    }
    
    /**
     * Redirigir usuarios no loggeados
     */
    public function handle_quote_request_redirect() {
        wp_send_json_error(array(
            'message' => __('Debes iniciar sesión para solicitar un presupuesto.', 'wc-scientific-cart'),
            'redirect' => wp_login_url(wc_get_cart_url())
        ));
    }
    
    /**
     * Personalizar visualización de productos en carrito
     */
    public function customize_cart_item_display($product_name, $cart_item, $cart_item_key) {
        $product = $cart_item['data'];
        $sku = $product->get_sku();
        
        $custom_name = '<div class="scientific-product-info">';
        $custom_name .= '<span class="product-name">' . $product_name . '</span>';
        if ($sku) {
            $custom_name .= '<span class="product-sku">SKU: ' . esc_html($sku) . '</span>';
        }
        $custom_name .= '</div>';
        
        return $custom_name;
    }
    
    /**
     * Personalizar visualización del total
     */
    public function customize_total_display($total_html) {
        return '<div class="scientific-total">' . $total_html . '</div>';
    }
    
    /**
     * Guardar solicitud de presupuesto
     */
    private function save_quote_request($quote_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'scientific_cart_quotes';
        
        $wpdb->insert(
            $table_name,
            array(
                'customer_name' => $quote_data['customer_name'],
                'customer_email' => $quote_data['customer_email'],
                'company_name' => $quote_data['company_name'],
                'cuit' => $quote_data['cuit'],
                'products_data' => json_encode($quote_data['products']),
                'cart_total' => $quote_data['cart_total'],
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s')
        );
    }
}

/**
 * Clase para integración con ERP
 */
class WC_Scientific_Cart_ERP_Integration {
    
    /**
     * Enviar solicitud de presupuesto al ERP
     */
    public function send_quote_request($quote_data) {
        // URL de tu ERP (configurar según tu sistema)
        $erp_endpoint = get_option('wc_scientific_cart_erp_endpoint', '');
        $erp_api_key = get_option('wc_scientific_cart_erp_api_key', '');
        
        if (empty($erp_endpoint)) {
            // Si no hay configuración de ERP, usar webhook local o email
            return $this->send_email_notification($quote_data);
        }
        
        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $erp_api_key
        );
        
        $body = json_encode(array(
            'type' => 'quote_request',
            'data' => $quote_data
        ));
        
        $response = wp_remote_post($erp_endpoint, array(
            'method' => 'POST',
            'headers' => $headers,
            'body' => $body,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('ERP Integration Error: ' . $response->get_error_message());
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code === 200) {
            return array('success' => true, 'message' => 'Quote request sent successfully');
        } else {
            return array('success' => false, 'message' => 'ERP returned error code: ' . $response_code);
        }
    }
    
    /**
     * Enviar notificación por email como fallback
     */
    private function send_email_notification($quote_data) {
        $admin_email = get_option('admin_email');
        $subject = 'Nueva Solicitud de Presupuesto - ' . $quote_data['customer_name'];
        
        $message = "Nueva solicitud de presupuesto recibida:\n\n";
        $message .= "Cliente: " . $quote_data['customer_name'] . "\n";
        $message .= "Email: " . $quote_data['customer_email'] . "\n";
        $message .= "Empresa: " . $quote_data['company_name'] . "\n";
        $message .= "CUIT: " . $quote_data['cuit'] . "\n";
        $message .= "Total del carrito: $" . $quote_data['cart_total'] . "\n\n";
        
        $message .= "Productos:\n";
        foreach ($quote_data['products'] as $product) {
            $message .= "- " . $product['name'] . " (SKU: " . $product['sku'] . ") - Cantidad: " . $product['quantity'] . " - Total: $" . $product['total'] . "\n";
        }
        
        $sent = wp_mail($admin_email, $subject, $message);
        
        return array('success' => $sent, 'message' => $sent ? 'Email sent' : 'Email failed');
    }
}

/**
 * Clase para personalización adicional del carrito
 */
class WC_Scientific_Cart_Customizer {
    
    public function __construct() {
        add_action('wp_head', array($this, 'add_custom_styles'));
    }
    
    /**
     * Agregar estilos personalizados
     */
    public function add_custom_styles() {
        if (!is_cart()) return;
        
        ?>
        <style>
        .scientific-cart-header {
            background: linear-gradient(135deg, #E3F2FD 0%, #B3E5FC 100%);
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
            border: 1px solid #81D4FA;
            position: relative;
            overflow: hidden;
        }
        
        .scientific-cart-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%234FC3F7' fill-opacity='0.1'%3E%3Ccircle cx='30' cy='30' r='4'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
            animation: float 20s infinite linear;
        }
        
        @keyframes float {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
        
        .scientific-icon {
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }
        
        .scientific-title {
            color: #1976D2;
            font-size: 28px;
            font-weight: 600;
            margin: 0 0 10px 0;
            position: relative;
            z-index: 1;
        }
        
        .scientific-subtitle {
            color: #37474F;
            font-size: 16px;
            margin: 0;
            position: relative;
            z-index: 1;
        }
        
        .scientific-quote-section {
            background: #F8F9FA;
            border: 2px solid #4FC3F7;
            border-radius: 12px;
            padding: 25px;
            margin: 20px 0;
        }
        
        .quote-info h3 {
            color: #1976D2;
            font-size: 22px;
            margin-bottom: 15px;
        }
        
        .customer-info {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #2196F3;
            margin-bottom: 15px;
        }
        
        .customer-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        
        .scientific-quote-btn {
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
        }
        
        .scientific-quote-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(33, 150, 243, 0.4);
        }
        
        .scientific-quote-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .scientific-product-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .product-sku {
            font-size: 12px;
            color: #666;
            font-style: italic;
        }
        
        .scientific-total {
            background: linear-gradient(135deg, #E8F5E8 0%, #C8E6C9 100%);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #A5D6A7;
        }
        
        .woocommerce-cart-form {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .woocommerce table.shop_table {
            border-radius: 12px;
            overflow: hidden;
        }
        
        .woocommerce table.shop_table th {
            background: linear-gradient(135deg, #1976D2 0%, #1565C0 100%);
            color: white;
            font-weight: 600;
        }
        
        .woocommerce table.shop_table td {
            border-bottom: 1px solid #E3F2FD;
        }
        
        .woocommerce table.shop_table tr:hover td {
            background-color: #F3E5F5;
        }
        
        .quote-success-message {
            background: #E8F5E8;
            color: #2E7D32;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #4CAF50;
            margin: 15px 0;
        }
        
        .quote-error-message {
            background: #FFEBEE;
            color: #C62828;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #F44336;
            margin: 15px 0;
        }
        
        @media (max-width: 768px) {
            .scientific-cart-header {
                padding: 20px;
            }
            
            .scientific-title {
                font-size: 24px;
            }
            
            .scientific-quote-section {
                padding: 20px;
            }
            
            .scientific-quote-btn {
                width: 100%;
                justify-content: center;
            }
        }
        </style>
        <?php
    }
}

/**
 * Activación del plugin - crear tablas necesarias
 */
function wc_scientific_cart_activate() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'scientific_cart_quotes';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        customer_name varchar(255) NOT NULL,
        customer_email varchar(255) NOT NULL,
        company_name varchar(255),
        cuit varchar(20),
        products_data longtext NOT NULL,
        cart_total decimal(10,2) NOT NULL,
        status varchar(20) DEFAULT 'pending',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Agregar opciones por defecto
    add_option('wc_scientific_cart_erp_endpoint', '');
    add_option('wc_scientific_cart_erp_api_key', '');
}
register_activation_hook(__FILE__, 'wc_scientific_cart_activate');

/**
 * Agregar página de configuración en admin
 */
function wc_scientific_cart_admin_menu() {
    add_submenu_page(
        'woocommerce',
        __('Carrito Científico', 'wc-scientific-cart'),
        __('Carrito Científico', 'wc-scientific-cart'),
        'manage_woocommerce',
        'wc-scientific-cart',
        'wc_scientific_cart_admin_page'
    );
}
add_action('admin_menu', 'wc_scientific_cart_admin_menu');

/**
 * Página de configuración del admin
 */
function wc_scientific_cart_admin_page() {
    if (isset($_POST['submit'])) {
        update_option('wc_scientific_cart_erp_endpoint', sanitize_text_field($_POST['erp_endpoint']));
        update_option('wc_scientific_cart_erp_api_key', sanitize_text_field($_POST['erp_api_key']));
        echo '<div class="notice notice-success"><p>' . __('Configuración guardada.', 'wc-scientific-cart') . '</p></div>';
    }
    
    $erp_endpoint = get_option('wc_scientific_cart_erp_endpoint', '');
    $erp_api_key = get_option('wc_scientific_cart_erp_api_key', '');
    ?>
    <div class="wrap">
        <h1><?php _e('Configuración Carrito Científico', 'wc-scientific-cart'); ?></h1>
        
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('URL del ERP', 'wc-scientific-cart'); ?></th>
                    <td>
                        <input type="url" name="erp_endpoint" value="<?php echo esc_attr($erp_endpoint); ?>" class="regular-text" />
                        <p class="description"><?php _e('URL del endpoint de tu ERP para recibir solicitudes de presupuesto.', 'wc-scientific-cart'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('API Key del ERP', 'wc-scientific-cart'); ?></th>
                    <td>
                        <input type="password" name="erp_api_key" value="<?php echo esc_attr($erp_api_key); ?>" class="regular-text" />
                        <p class="description"><?php _e('Clave de autenticación para el ERP.', 'wc-scientific-cart'); ?></p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
        
        <h2><?php _e('Solicitudes de Presupuesto', 'wc-scientific-cart'); ?></h2>
        
        <?php
        global $wpdb;
        $table_name = $wpdb->prefix . 'scientific_cart_quotes';
        $quotes = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 20");
        
        if ($quotes) {
            ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Fecha', 'wc-scientific-cart'); ?></th>
                        <th><?php _e('Cliente', 'wc-scientific-cart'); ?></th>
                        <th><?php _e('Empresa', 'wc-scientific-cart'); ?></th>
                        <th><?php _e('CUIT', 'wc-scientific-cart'); ?></th>
                        <th><?php _e('Total', 'wc-scientific-cart'); ?></th>
                        <th><?php _e('Estado', 'wc-scientific-cart'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($quotes as $quote): ?>
                    <tr>
                        <td><?php echo esc_html($quote->created_at); ?></td>
                        <td><?php echo esc_html($quote->customer_name); ?></td>
                        <td><?php echo esc_html($quote->company_name); ?></td>
                        <td><?php echo esc_html($quote->cuit); ?></td>
                        <td>$<?php echo esc_html(number_format($quote->cart_total, 2)); ?></td>
                        <td><?php echo esc_html($quote->status); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        } else {
            echo '<p>' . __('No hay solicitudes de presupuesto aún.', 'wc-scientific-cart') . '</p>';
        }
        ?>
    </div>
    <?php
}

// Inicializar el plugin
WC_Scientific_Cart::get_instance();

// Inicializar personalizador
new WC_Scientific_Cart_Customizer();
?>
