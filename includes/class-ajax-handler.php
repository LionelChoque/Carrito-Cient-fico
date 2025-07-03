<?php
/**
 * Manejador AJAX para el plugin WooCommerce Carrito Científico
 *
 * @package WC_Scientific_Cart
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para manejar todas las peticiones AJAX del plugin
 */
class WC_Scientific_Cart_Ajax_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_ajax_hooks();
    }
    
    /**
     * Inicializar hooks AJAX
     */
    private function init_ajax_hooks() {
        // Hooks para usuarios logueados
        add_action('wp_ajax_wc_scientific_cart_request_quote', array($this, 'handle_quote_request'));
        add_action('wp_ajax_wc_scientific_cart_get_cart_summary', array($this, 'get_cart_summary'));
        add_action('wp_ajax_wc_scientific_cart_validate_cart', array($this, 'validate_cart'));
        
        // Hooks para usuarios no logueados
        add_action('wp_ajax_nopriv_wc_scientific_cart_request_quote', array($this, 'handle_quote_request_redirect'));
        add_action('wp_ajax_nopriv_wc_scientific_cart_get_cart_summary', array($this, 'handle_not_logged_in'));
        add_action('wp_ajax_nopriv_wc_scientific_cart_validate_cart', array($this, 'handle_not_logged_in'));
        
        // Hooks administrativos
        add_action('wp_ajax_wc_scientific_cart_update_quote_status', array($this, 'update_quote_status'));
        add_action('wp_ajax_wc_scientific_cart_get_quote_details', array($this, 'get_quote_details'));
        add_action('wp_ajax_wc_scientific_cart_export_quotes', array($this, 'export_quotes'));
    }
    
    /**
     * Manejar solicitud de presupuesto
     */
    public function handle_quote_request() {
        try {
            // Verificar nonce
            if (!wp_verify_nonce($_POST['nonce'], 'wc_scientific_cart_nonce')) {
                throw new Exception(__('Error de seguridad. Recarga la página e inténtalo nuevamente.', 'wc-scientific-cart'));
            }
            
            // Verificar que el usuario esté logueado
            if (!is_user_logged_in()) {
                throw new Exception(__('Debes iniciar sesión para solicitar un presupuesto.', 'wc-scientific-cart'));
            }
            
            // Validar requisitos del carrito
            $validation = wc_scientific_cart_validate_quote_requirements();
            if (is_wp_error($validation)) {
                throw new Exception($validation->get_error_message());
            }
            
            // Obtener datos del usuario y carrito
            $user_id = get_current_user_id();
            $quote_data = $this->prepare_quote_data($user_id);
            
            // Validar datos requeridos
            $this->validate_quote_data($quote_data);
            
            // Aplicar filtros a los datos
            $quote_data = apply_filters('wc_scientific_cart_quote_data', $quote_data, $user_id);
            
            // Hook antes de enviar
            do_action('wc_scientific_cart_before_quote_send', $quote_data, $user_id);
            
            // Guardar en base de datos
            $quote_id = $this->save_quote_request($quote_data);
            
            if (!$quote_id) {
                throw new Exception(__('Error al guardar la solicitud. Inténtalo nuevamente.', 'wc-scientific-cart'));
            }
            
            // Enviar al ERP
            $erp_integration = new WC_Scientific_Cart_ERP_Integration();
            $erp_result = $erp_integration->send_quote_request($quote_data);
            
            // Actualizar estado según el resultado del ERP
            $this->update_quote_erp_status($quote_id, $erp_result);
            
            // Hook después de enviar exitosamente
            do_action('wc_scientific_cart_quote_sent', $quote_data, $user_id);
            
            // Log de éxito
            wc_scientific_cart_log(sprintf(
                'Quote request sent successfully. Quote ID: %d, User ID: %d, Total: %s',
                $quote_id,
                $user_id,
                $quote_data['cart_total']
            ), 'info', array('quote_id' => $quote_id, 'user_id' => $user_id));
            
            wp_send_json_success(array(
                'message' => get_option('wc_scientific_cart_success_message', __('¡Presupuesto solicitado exitosamente!', 'wc-scientific-cart')),
                'quote_id' => $quote_id,
                'redirect' => apply_filters('wc_scientific_cart_success_redirect', '')
            ));
            
        } catch (Exception $e) {
            // Log del error
            wc_scientific_cart_log('Quote request failed: ' . $e->getMessage(), 'error', array(
                'user_id' => get_current_user_id(),
                'error' => $e->getMessage()
            ));
            
            // Hook para errores
            do_action('wc_scientific_cart_quote_failed', $e->getMessage(), get_current_user_id());
            
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Preparar datos del presupuesto
     */
    private function prepare_quote_data($user_id) {
        $user = get_user_by('ID', $user_id);
        $cart = WC()->cart;
        $cart_data = wc_scientific_cart_get_cart_data();
        
        $quote_data = array(
            'customer_name' => $user->display_name,
            'customer_email' => $user->user_email,
            'company_name' => get_user_meta($user_id, 'billing_company', true),
            'cuit' => get_user_meta($user_id, 'cuit', true),
            'phone' => get_user_meta($user_id, 'billing_phone', true),
            'industry_type' => get_user_meta($user_id, 'industry_type', true),
            'lab_size' => get_user_meta($user_id, 'lab_size', true),
            'annual_budget' => get_user_meta($user_id, 'annual_budget', true),
            'products' => $cart_data['items'],
            'cart_total' => $cart_data['totals']['total'],
            'cart_subtotal' => $cart_data['totals']['subtotal'],
            'cart_tax' => $cart_data['totals']['tax'],
            'item_count' => $cart_data['item_count'],
            'timestamp' => current_time('mysql'),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip_address' => $this->get_client_ip(),
            'billing_address' => array(
                'first_name' => get_user_meta($user_id, 'billing_first_name', true),
                'last_name' => get_user_meta($user_id, 'billing_last_name', true),
                'address_1' => get_user_meta($user_id, 'billing_address_1', true),
                'address_2' => get_user_meta($user_id, 'billing_address_2', true),
                'city' => get_user_meta($user_id, 'billing_city', true),
                'state' => get_user_meta($user_id, 'billing_state', true),
                'postcode' => get_user_meta($user_id, 'billing_postcode', true),
                'country' => get_user_meta($user_id, 'billing_country', true)
            )
        );
        
        return $quote_data;
    }
    
    /**
     * Validar datos del presupuesto
     */
    private function validate_quote_data($quote_data) {
        $errors = array();
        
        // Validar campos requeridos
        if (empty($quote_data['customer_name'])) {
            $errors[] = __('El nombre del cliente es requerido.', 'wc-scientific-cart');
        }
        
        if (empty($quote_data['customer_email']) || !is_email($quote_data['customer_email'])) {
            $errors[] = __('Un email válido es requerido.', 'wc-scientific-cart');
        }
        
        // Validar CUIT si es requerido
        if ('yes' === get_option('wc_scientific_cart_require_cuit', 'yes')) {
            if (empty($quote_data['cuit'])) {
                $errors[] = __('El CUIT es requerido para solicitar presupuestos.', 'wc-scientific-cart');
            } elseif (!wc_scientific_cart_validate_cuit($quote_data['cuit'])) {
                $errors[] = __('El CUIT ingresado no es válido.', 'wc-scientific-cart');
            }
        }
        
        // Validar empresa si es requerida
        if ('yes' === get_option('wc_scientific_cart_require_company', 'yes') && empty($quote_data['company_name'])) {
            $errors[] = __('El nombre de la empresa es requerido.', 'wc-scientific-cart');
        }
        
        // Validar productos
        if (empty($quote_data['products'])) {
            $errors[] = __('No hay productos en el carrito.', 'wc-scientific-cart');
        }
        
        // Aplicar filtros de validación personalizada
        $custom_errors = apply_filters('wc_scientific_cart_validate_quote_data', array(), $quote_data);
        if (!empty($custom_errors)) {
            $errors = array_merge($errors, $custom_errors);
        }
        
        if (!empty($errors)) {
            throw new Exception(implode(' ', $errors));
        }
    }
    
    /**
     * Guardar solicitud de presupuesto en base de datos
     */
    private function save_quote_request($quote_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'scientific_cart_quotes';
        
        $data = array(
            'customer_name' => $quote_data['customer_name'],
            'customer_email' => $quote_data['customer_email'],
            'company_name' => $quote_data['company_name'],
            'cuit' => $quote_data['cuit'],
            'phone' => $quote_data['phone'],
            'products_data' => wp_json_encode($quote_data),
            'cart_total' => $quote_data['cart_total'],
            'status' => 'pending',
            'priority' => $this->calculate_priority($quote_data['cart_total'])
        );
        
        $result = $wpdb->insert($table_name, $data);
        
        if (false === $result) {
            wc_scientific_cart_log('Failed to save quote request: ' . $wpdb->last_error, 'error');
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Calcular prioridad basada en el monto
     */
    private function calculate_priority($total) {
        if ($total >= 10000) {
            return 'high';
        } elseif ($total >= 5000) {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    /**
     * Actualizar estado del presupuesto según resultado del ERP
     */
    private function update_quote_erp_status($quote_id, $erp_result) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'scientific_cart_quotes';
        $status = $erp_result['success'] ? 'sent_to_erp' : 'erp_failed';
        
        $wpdb->update(
            $table_name,
            array('status' => $status),
            array('id' => $quote_id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * Obtener IP del cliente
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }
    
    /**
     * Obtener resumen del carrito
     */
    public function get_cart_summary() {
        if (!wp_verify_nonce($_POST['nonce'], 'wc_scientific_cart_nonce')) {
            wp_send_json_error(array('message' => __('Error de seguridad.', 'wc-scientific-cart')));
        }
        
        $cart_data = wc_scientific_cart_get_cart_data();
        
        wp_send_json_success(array(
            'cart_data' => $cart_data,
            'formatted_total' => wc_scientific_cart_format_price($cart_data['totals']['total']),
            'can_request_quote' => wc_scientific_cart_user_can_request_quote()
        ));
    }
    
    /**
     * Validar carrito
     */
    public function validate_cart() {
        if (!wp_verify_nonce($_POST['nonce'], 'wc_scientific_cart_nonce')) {
            wp_send_json_error(array('message' => __('Error de seguridad.', 'wc-scientific-cart')));
        }
        
        $validation = wc_scientific_cart_validate_quote_requirements();
        
        if (is_wp_error($validation)) {
            wp_send_json_error(array(
                'message' => $validation->get_error_message(),
                'code' => $validation->get_error_code()
            ));
        }
        
        wp_send_json_success(array('message' => __('Carrito válido para presupuesto.', 'wc-scientific-cart')));
    }
    
    /**
     * Manejar redirección para usuarios no logueados
     */
    public function handle_quote_request_redirect() {
        wp_send_json_error(array(
            'message' => __('Debes iniciar sesión para solicitar un presupuesto.', 'wc-scientific-cart'),
            'redirect' => wp_login_url(wc_get_cart_url()),
            'login_required' => true
        ));
    }
    
    /**
     * Manejar peticiones de usuarios no logueados
     */
    public function handle_not_logged_in() {
        wp_send_json_error(array(
            'message' => __('Debes iniciar sesión para acceder a esta funcionalidad.', 'wc-scientific-cart'),
            'login_required' => true
        ));
    }
    
    /**
     * Actualizar estado del presupuesto (Admin)
     */
    public function update_quote_status() {
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('No tienes permisos suficientes.', 'wc-scientific-cart')));
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'wc_scientific_cart_admin_nonce')) {
            wp_send_json_error(array('message' => __('Error de seguridad.', 'wc-scientific-cart')));
        }
        
        $quote_id = intval($_POST['quote_id']);
        $new_status = sanitize_text_field($_POST['status']);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'scientific_cart_quotes';
        
        $result = $wpdb->update(
            $table_name,
            array(
                'status' => $new_status,
                'notes' => $notes
            ),
            array('id' => $quote_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if (false === $result) {
            wp_send_json_error(array('message' => __('Error al actualizar el estado.', 'wc-scientific-cart')));
        }
        
        wp_send_json_success(array('message' => __('Estado actualizado correctamente.', 'wc-scientific-cart')));
    }
    
    /**
     * Obtener detalles del presupuesto (Admin)
     */
    public function get_quote_details() {
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('No tienes permisos suficientes.', 'wc-scientific-cart')));
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'wc_scientific_cart_admin_nonce')) {
            wp_send_json_error(array('message' => __('Error de seguridad.', 'wc-scientific-cart')));
        }
        
        $quote_id = intval($_POST['quote_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'scientific_cart_quotes';
        
        $quote = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $quote_id));
        
        if (!$quote) {
            wp_send_json_error(array('message' => __('Presupuesto no encontrado.', 'wc-scientific-cart')));
        }
        
        $quote_data = json_decode($quote->products_data, true);
        
        wp_send_json_success(array(
            'quote' => $quote,
            'quote_data' => $quote_data,
            'formatted_total' => wc_scientific_cart_format_price($quote->cart_total)
        ));
    }
    
    /**
     * Exportar presupuestos (Admin)
     */
    public function export_quotes() {
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('No tienes permisos suficientes.', 'wc-scientific-cart')));
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'wc_scientific_cart_admin_nonce')) {
            wp_send_json_error(array('message' => __('Error de seguridad.', 'wc-scientific-cart')));
        }
        
        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        $date_from = sanitize_text_field($_POST['date_from'] ?? '');
        $date_to = sanitize_text_field($_POST['date_to'] ?? '');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'scientific_cart_quotes';
        
        $where_clause = '1=1';
        $where_args = array();
        
        if (!empty($date_from)) {
            $where_clause .= ' AND created_at >= %s';
            $where_args[] = $date_from . ' 00:00:00';
        }
        
        if (!empty($date_to)) {
            $where_clause .= ' AND created_at <= %s';
            $where_args[] = $date_to . ' 23:59:59';
        }
        
        $query = "SELECT * FROM $table_name WHERE $where_clause ORDER BY created_at DESC";
        
        if (!empty($where_args)) {
            $query = $wpdb->prepare($query, $where_args);
        }
        
        $quotes = $wpdb->get_results($query);
        
        if (empty($quotes)) {
            wp_send_json_error(array('message' => __('No hay datos para exportar.', 'wc-scientific-cart')));
        }
        
        $export_url = $this->generate_export_file($quotes, $format);
        
        wp_send_json_success(array(
            'download_url' => $export_url,
            'message' => __('Archivo generado correctamente.', 'wc-scientific-cart')
        ));
    }
    
    /**
     * Generar archivo de exportación
     */
    private function generate_export_file($quotes, $format) {
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/wc-scientific-cart-exports/';
        
        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
        }
        
        $filename = 'quotes-export-' . date('Y-m-d-H-i-s') . '.' . $format;
        $filepath = $export_dir . $filename;
        
        if ($format === 'csv') {
            $this->generate_csv_export($quotes, $filepath);
        } else {
            $this->generate_json_export($quotes, $filepath);
        }
        
        return $upload_dir['baseurl'] . '/wc-scientific-cart-exports/' . $filename;
    }
    
    /**
     * Generar exportación CSV
     */
    private function generate_csv_export($quotes, $filepath) {
        $file = fopen($filepath, 'w');
        
        // Headers
        fputcsv($file, array(
            'ID', 'Fecha', 'Cliente', 'Email', 'Empresa', 'CUIT', 'Teléfono',
            'Total', 'Estado', 'Prioridad', 'Productos', 'Notas'
        ));
        
        foreach ($quotes as $quote) {
            $quote_data = json_decode($quote->products_data, true);
            $products_count = count($quote_data['products'] ?? array());
            
            fputcsv($file, array(
                $quote->id,
                $quote->created_at,
                $quote->customer_name,
                $quote->customer_email,
                $quote->company_name,
                $quote->cuit,
                $quote->phone,
                $quote->cart_total,
                $quote->status,
                $quote->priority,
                $products_count,
                $quote->notes
            ));
        }
        
        fclose($file);
    }
    
    /**
     * Generar exportación JSON
     */
    private function generate_json_export($quotes, $filepath) {
        $export_data = array();
        
        foreach ($quotes as $quote) {
            $quote_data = json_decode($quote->products_data, true);
            $export_data[] = array(
                'id' => $quote->id,
                'created_at' => $quote->created_at,
                'customer' => array(
                    'name' => $quote->customer_name,
                    'email' => $quote->customer_email,
                    'company' => $quote->company_name,
                    'cuit' => $quote->cuit,
                    'phone' => $quote->phone
                ),
                'cart_total' => $quote->cart_total,
                'status' => $quote->status,
                'priority' => $quote->priority,
                'notes' => $quote->notes,
                'full_data' => $quote_data
            );
        }
        
        file_put_contents($filepath, wp_json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
?>
