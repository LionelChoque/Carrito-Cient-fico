<?php
/**
 * Integración con ERP para el plugin WooCommerce Carrito Científico
 *
 * @package WC_Scientific_Cart
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para manejar la integración con sistemas ERP
 */
class WC_Scientific_Cart_ERP_Integration {
    
    /**
     * Endpoint del ERP
     * @var string
     */
    private $erp_endpoint;
    
    /**
     * API Key del ERP
     * @var string
     */
    private $api_key;
    
    /**
     * Timeout para peticiones HTTP
     * @var int
     */
    private $timeout;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->erp_endpoint = get_option('wc_scientific_cart_erp_endpoint', '');
        $this->api_key = get_option('wc_scientific_cart_erp_api_key', '');
        $this->timeout = apply_filters('wc_scientific_cart_erp_timeout', 30);
    }
    
    /**
     * Enviar solicitud de presupuesto al ERP
     *
     * @param array $quote_data Datos del presupuesto
     * @return array Resultado de la operación
     */
    public function send_quote_request($quote_data) {
        try {
            // Validar configuración
            if (empty($this->erp_endpoint)) {
                return $this->fallback_notification($quote_data);
            }
            
            // Preparar datos para el ERP
            $erp_payload = $this->prepare_erp_payload($quote_data);
            
            // Aplicar filtros al payload
            $erp_payload = apply_filters('wc_scientific_cart_erp_payload', $erp_payload, $quote_data);
            
            // Realizar petición HTTP
            $response = $this->make_http_request($erp_payload);
            
            // Procesar respuesta
            $result = $this->process_erp_response($response);
            
            // Log de la operación
            $this->log_erp_operation($quote_data, $result, $erp_payload);
            
            return $result;
            
        } catch (Exception $e) {
            wc_scientific_cart_log('ERP Integration Error: ' . $e->getMessage(), 'error', array(
                'quote_data' => $quote_data,
                'endpoint' => $this->erp_endpoint
            ));
            
            // Intentar fallback en caso de error
            return $this->fallback_notification($quote_data);
        }
    }
    
    /**
     * Preparar payload para el ERP
     *
     * @param array $quote_data Datos del presupuesto
     * @return array
     */
    private function prepare_erp_payload($quote_data) {
        $payload = array(
            'type' => 'quote_request',
            'source' => 'woocommerce_scientific_cart',
            'timestamp' => current_time('c'),
            'site_info' => array(
                'site_name' => get_bloginfo('name'),
                'site_url' => home_url(),
                'admin_email' => get_option('admin_email')
            ),
            'customer' => array(
                'name' => $quote_data['customer_name'],
                'email' => $quote_data['customer_email'],
                'company' => $quote_data['company_name'],
                'cuit' => $quote_data['cuit'],
                'phone' => $quote_data['phone'],
                'industry_type' => $quote_data['industry_type'] ?? '',
                'lab_size' => $quote_data['lab_size'] ?? '',
                'annual_budget' => $quote_data['annual_budget'] ?? ''
            ),
            'billing_address' => $quote_data['billing_address'] ?? array(),
            'cart' => array(
                'total' => $quote_data['cart_total'],
                'subtotal' => $quote_data['cart_subtotal'],
                'tax' => $quote_data['cart_tax'],
                'item_count' => $quote_data['item_count'],
                'currency' => get_woocommerce_currency()
            ),
            'products' => array(),
            'metadata' => array(
                'user_agent' => $quote_data['user_agent'] ?? '',
                'ip_address' => $quote_data['ip_address'] ?? '',
                'request_time' => $quote_data['timestamp']
            )
        );
        
        // Procesar productos
        foreach ($quote_data['products'] as $product) {
            $payload['products'][] = array(
                'id' => $product['product_id'],
                'variation_id' => $product['variation_id'] ?? 0,
                'name' => $product['name'],
                'sku' => $product['sku'],
                'quantity' => $product['quantity'],
                'unit_price' => $product['price'],
                'line_total' => $product['line_total'],
                'line_tax' => $product['line_tax'],
                'scientific_data' => array(
                    'catalog_number' => $product['product_data']['catalog_number'] ?? '',
                    'cas_number' => $product['product_data']['cas_number'] ?? '',
                    'molecular_formula' => $product['product_data']['molecular_formula'] ?? '',
                    'molecular_weight' => $product['product_data']['molecular_weight'] ?? '',
                    'purity_level' => $product['product_data']['purity_level'] ?? '',
                    'storage_conditions' => $product['product_data']['storage_conditions'] ?? '',
                    'hazard_class' => $product['product_data']['hazard_class'] ?? '',
                    'manufacturer' => $product['product_data']['manufacturer'] ?? '',
                    'technical_specifications' => $product['product_data']['technical_specifications'] ?? ''
                )
            );
        }
        
        return $payload;
    }
    
    /**
     * Realizar petición HTTP al ERP
     *
     * @param array $payload Datos a enviar
     * @return array|WP_Error
     */
    private function make_http_request($payload) {
        $headers = array(
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'WooCommerce-Scientific-Cart/' . WC_SCIENTIFIC_CART_VERSION
        );
        
        // Agregar autenticación si hay API key
        if (!empty($this->api_key)) {
            $headers['Authorization'] = 'Bearer ' . $this->api_key;
        }
        
        // Permitir personalizar headers
        $headers = apply_filters('wc_scientific_cart_erp_headers', $headers, $payload);
        
        $args = array(
            'method' => 'POST',
            'headers' => $headers,
            'body' => wp_json_encode($payload),
            'timeout' => $this->timeout,
            'httpversion' => '1.1',
            'sslverify' => true,
            'data_format' => 'body'
        );
        
        // Permitir personalizar argumentos de la petición
        $args = apply_filters('wc_scientific_cart_erp_request_args', $args, $payload);
        
        return wp_remote_post($this->erp_endpoint, $args);
    }
    
    /**
     * Procesar respuesta del ERP
     *
     * @param array|WP_Error $response Respuesta HTTP
     * @return array
     */
    private function process_erp_response($response) {
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'HTTP Error: ' . $response->get_error_message(),
                'error_code' => $response->get_error_code()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        // Log de respuesta si está en modo debug
        if (wc_scientific_cart_is_debug_mode()) {
            wc_scientific_cart_log('ERP Response: ' . $response_body, 'debug', array(
                'response_code' => $response_code
            ));
        }
        
        // Procesar según código de respuesta
        if ($response_code >= 200 && $response_code < 300) {
            $decoded_response = json_decode($response_body, true);
            
            return array(
                'success' => true,
                'message' => 'Quote sent to ERP successfully',
                'response_code' => $response_code,
                'erp_response' => $decoded_response,
                'erp_quote_id' => $decoded_response['quote_id'] ?? null
            );
            
        } else {
            return array(
                'success' => false,
                'message' => 'ERP returned error code: ' . $response_code,
                'response_code' => $response_code,
                'response_body' => $response_body
            );
        }
    }
    
    /**
     * Registrar operación en logs
     */
    private function log_erp_operation($quote_data, $result, $payload) {
        $log_data = array(
            'customer_email' => $quote_data['customer_email'],
            'cart_total' => $quote_data['cart_total'],
            'erp_endpoint' => $this->erp_endpoint,
            'success' => $result['success'],
            'response_code' => $result['response_code'] ?? null
        );
        
        if ($result['success']) {
            wc_scientific_cart_log('Quote sent to ERP successfully', 'info', $log_data);
        } else {
            wc_scientific_cart_log('Failed to send quote to ERP: ' . $result['message'], 'warning', $log_data);
        }
    }
    
    /**
     * Notificación de fallback cuando el ERP no está disponible
     *
     * @param array $quote_data Datos del presupuesto
     * @return array
     */
    private function fallback_notification($quote_data) {
        // Enviar por email como fallback
        $email_sent = $this->send_email_notification($quote_data);
        
        // Crear webhook local si está configurado
        $webhook_sent = $this->send_webhook_notification($quote_data);
        
        if ($email_sent || $webhook_sent) {
            return array(
                'success' => true,
                'message' => 'Quote sent via fallback method',
                'fallback_used' => true,
                'email_sent' => $email_sent,
                'webhook_sent' => $webhook_sent
            );
        } else {
            return array(
                'success' => false,
                'message' => 'All delivery methods failed',
                'fallback_attempted' => true
            );
        }
    }
    
    /**
     * Enviar notificación por email
     *
     * @param array $quote_data Datos del presupuesto
     * @return bool
     */
    private function send_email_notification($quote_data) {
        $admin_email = get_option('wc_scientific_cart_admin_email', get_option('admin_email'));
        
        if (empty($admin_email)) {
            return false;
        }
        
        $subject = sprintf(
            __('Nueva Solicitud de Presupuesto - %s', 'wc-scientific-cart'),
            $quote_data['customer_name']
        );
        
        $template_data = array(
            'quote_data' => $quote_data,
            'site_name' => get_bloginfo('name'),
            'admin_url' => admin_url('admin.php?page=wc-scientific-cart-quotes')
        );
        
        $message = wc_scientific_cart_get_template_html('emails/admin-quote-notification.php', $template_data);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
            'Reply-To: ' . $quote_data['customer_email']
        );
        
        // Aplicar filtros
        $subject = apply_filters('wc_scientific_cart_admin_email_subject', $subject, $quote_data);
        $message = apply_filters('wc_scientific_cart_admin_email_message', $message, $quote_data);
        $headers = apply_filters('wc_scientific_cart_admin_email_headers', $headers, $quote_data);
        
        return wp_mail($admin_email, $subject, $message, $headers);
    }
    
    /**
     * Enviar webhook local
     *
     * @param array $quote_data Datos del presupuesto
     * @return bool
     */
    private function send_webhook_notification($quote_data) {
        $webhook_url = get_option('wc_scientific_cart_webhook_url', '');
        
        if (empty($webhook_url)) {
            return false;
        }
        
        $payload = array(
            'event' => 'quote_request',
            'data' => $quote_data,
            'timestamp' => current_time('c'),
            'source' => home_url()
        );
        
        $response = wp_remote_post($webhook_url, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($payload),
            'timeout' => 15
        ));
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    /**
     * Verificar conectividad con el ERP
     *
     * @return array
     */
    public function test_connection() {
        if (empty($this->erp_endpoint)) {
            return array(
                'success' => false,
                'message' => __('No hay endpoint de ERP configurado.', 'wc-scientific-cart')
            );
        }
        
        $test_payload = array(
            'type' => 'connection_test',
            'timestamp' => current_time('c'),
            'source' => home_url()
        );
        
        $response = $this->make_http_request($test_payload);
        $result = $this->process_erp_response($response);
        
        if ($result['success']) {
            return array(
                'success' => true,
                'message' => __('Conexión exitosa con el ERP.', 'wc-scientific-cart'),
                'response_time' => $this->calculate_response_time($response)
            );
        } else {
            return array(
                'success' => false,
                'message' => sprintf(__('Error de conexión: %s', 'wc-scientific-cart'), $result['message'])
            );
        }
    }
    
    /**
     * Calcular tiempo de respuesta
     */
    private function calculate_response_time($response) {
        if (is_wp_error($response)) {
            return null;
        }
        
        $headers = wp_remote_retrieve_headers($response);
        return $headers['x-response-time'] ?? null;
    }
    
    /**
     * Obtener estadísticas de integración ERP
     *
     * @return array
     */
    public function get_erp_stats() {
        global $wpdb;
        
        $quotes_table = $wpdb->prefix . 'scientific_cart_quotes';
        
        return array(
            'total_sent' => $wpdb->get_var("SELECT COUNT(*) FROM $quotes_table WHERE status IN ('sent_to_erp', 'processed')"),
            'failed_sends' => $wpdb->get_var("SELECT COUNT(*) FROM $quotes_table WHERE status = 'erp_failed'"),
            'success_rate' => $this->calculate_success_rate(),
            'last_successful_send' => $wpdb->get_var("SELECT created_at FROM $quotes_table WHERE status = 'sent_to_erp' ORDER BY created_at DESC LIMIT 1"),
            'is_configured' => !empty($this->erp_endpoint)
        );
    }
    
    /**
     * Calcular tasa de éxito
     */
    private function calculate_success_rate() {
        global $wpdb;
        
        $quotes_table = $wpdb->prefix . 'scientific_cart_quotes';
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $quotes_table");
        $successful = $wpdb->get_var("SELECT COUNT(*) FROM $quotes_table WHERE status IN ('sent_to_erp', 'processed')");
        
        if ($total == 0) {
            return 0;
        }
        
        return round(($successful / $total) * 100, 2);
    }
    
    /**
     * Reenviar presupuesto fallido
     *
     * @param int $quote_id ID del presupuesto
     * @return array
     */
    public function resend_failed_quote($quote_id) {
        global $wpdb;
        
        $quotes_table = $wpdb->prefix . 'scientific_cart_quotes';
        $quote = $wpdb->get_row($wpdb->prepare("SELECT * FROM $quotes_table WHERE id = %d", $quote_id));
        
        if (!$quote) {
            return array(
                'success' => false,
                'message' => __('Presupuesto no encontrado.', 'wc-scientific-cart')
            );
        }
        
        $quote_data = json_decode($quote->products_data, true);
        $result = $this->send_quote_request($quote_data);
        
        // Actualizar estado
        $new_status = $result['success'] ? 'sent_to_erp' : 'erp_failed';
        $wpdb->update(
            $quotes_table,
            array('status' => $new_status),
            array('id' => $quote_id),
            array('%s'),
            array('%d')
        );
        
        return $result;
    }
    
    /**
     * Actualizar configuración del ERP
     *
     * @param array $config Nueva configuración
     * @return bool
     */
    public function update_config($config) {
        $updated = true;
        
        if (isset($config['endpoint'])) {
            $updated = $updated && update_option('wc_scientific_cart_erp_endpoint', esc_url_raw($config['endpoint']));
            $this->erp_endpoint = $config['endpoint'];
        }
        
        if (isset($config['api_key'])) {
            $updated = $updated && update_option('wc_scientific_cart_erp_api_key', sanitize_text_field($config['api_key']));
            $this->api_key = $config['api_key'];
        }
        
        if (isset($config['webhook_url'])) {
            $updated = $updated && update_option('wc_scientific_cart_webhook_url', esc_url_raw($config['webhook_url']));
        }
        
        return $updated;
    }
}
?>
