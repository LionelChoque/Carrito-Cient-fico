<?php
/**
 * Configuración del plugin WooCommerce Carrito Científico
 *
 * @package WC_Scientific_Cart
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para manejar la configuración del plugin
 */
class WC_Scientific_Cart_Settings {
    
    /**
     * Configuraciones del plugin
     * @var array
     */
    private $settings;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = $this->get_default_settings();
        $this->init_hooks();
    }
    
    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Registrar configuraciones
     */
    public function register_settings() {
        // Sección ERP
        add_settings_section(
            'wc_scientific_cart_erp',
            __('Configuración ERP', 'wc-scientific-cart'),
            array($this, 'erp_section_callback'),
            'wc_scientific_cart_settings'
        );
        
        // Sección de apariencia
        add_settings_section(
            'wc_scientific_cart_appearance',
            __('Configuración de Apariencia', 'wc-scientific-cart'),
            array($this, 'appearance_section_callback'),
            'wc_scientific_cart_settings'
        );
        
        // Sección de notificaciones
        add_settings_section(
            'wc_scientific_cart_notifications',
            __('Configuración de Notificaciones', 'wc-scientific-cart'),
            array($this, 'notifications_section_callback'),
            'wc_scientific_cart_settings'
        );
        
        // Sección avanzada
        add_settings_section(
            'wc_scientific_cart_advanced',
            __('Configuración Avanzada', 'wc-scientific-cart'),
            array($this, 'advanced_section_callback'),
            'wc_scientific_cart_settings'
        );
        
        // Registrar campos
        $this->register_setting_fields();
    }
    
    /**
     * Registrar campos de configuración
     */
    private function register_setting_fields() {
        $fields = array(
            // Campos ERP
            'erp_endpoint' => array(
                'section' => 'wc_scientific_cart_erp',
                'title' => __('URL del ERP', 'wc-scientific-cart'),
                'type' => 'url',
                'description' => __('URL del endpoint de tu ERP para recibir solicitudes de presupuesto.', 'wc-scientific-cart')
            ),
            'erp_api_key' => array(
                'section' => 'wc_scientific_cart_erp',
                'title' => __('API Key del ERP', 'wc-scientific-cart'),
                'type' => 'password',
                'description' => __('Clave de autenticación para el ERP.', 'wc-scientific-cart')
            ),
            'webhook_url' => array(
                'section' => 'wc_scientific_cart_erp',
                'title' => __('URL de Webhook (Fallback)', 'wc-scientific-cart'),
                'type' => 'url',
                'description' => __('URL alternativa para recibir notificaciones si el ERP no está disponible.', 'wc-scientific-cart')
            ),
            
            // Campos de apariencia
            'header_title' => array(
                'section' => 'wc_scientific_cart_appearance',
                'title' => __('Título del Header', 'wc-scientific-cart'),
                'type' => 'text',
                'description' => __('Título principal que aparece en el carrito.', 'wc-scientific-cart')
            ),
            'header_subtitle' => array(
                'section' => 'wc_scientific_cart_appearance',
                'title' => __('Subtítulo del Header', 'wc-scientific-cart'),
                'type' => 'textarea',
                'description' => __('Subtítulo descriptivo en el carrito.', 'wc-scientific-cart')
            ),
            'button_text' => array(
                'section' => 'wc_scientific_cart_appearance',
                'title' => __('Texto del Botón', 'wc-scientific-cart'),
                'type' => 'text',
                'description' => __('Texto del botón de solicitar presupuesto.', 'wc-scientific-cart')
            ),
            'primary_color' => array(
                'section' => 'wc_scientific_cart_appearance',
                'title' => __('Color Primario', 'wc-scientific-cart'),
                'type' => 'color',
                'description' => __('Color principal del tema científico.', 'wc-scientific-cart')
            ),
            'secondary_color' => array(
                'section' => 'wc_scientific_cart_appearance',
                'title' => __('Color Secundario', 'wc-scientific-cart'),
                'type' => 'color',
                'description' => __('Color secundario para acentos.', 'wc-scientific-cart')
            ),
            
            // Campos de notificaciones
            'email_notifications' => array(
                'section' => 'wc_scientific_cart_notifications',
                'title' => __('Notificaciones por Email', 'wc-scientific-cart'),
                'type' => 'checkbox',
                'description' => __('Enviar notificaciones por email cuando se reciban presupuestos.', 'wc-scientific-cart')
            ),
            'admin_email' => array(
                'section' => 'wc_scientific_cart_notifications',
                'title' => __('Email del Administrador', 'wc-scientific-cart'),
                'type' => 'email',
                'description' => __('Email donde recibir las notificaciones de presupuestos.', 'wc-scientific-cart')
            ),
            'success_message' => array(
                'section' => 'wc_scientific_cart_notifications',
                'title' => __('Mensaje de Éxito', 'wc-scientific-cart'),
                'type' => 'textarea',
                'description' => __('Mensaje mostrado cuando se envía un presupuesto exitosamente.', 'wc-scientific-cart')
            ),
            'error_message' => array(
                'section' => 'wc_scientific_cart_notifications',
                'title' => __('Mensaje de Error', 'wc-scientific-cart'),
                'type' => 'textarea',
                'description' => __('Mensaje mostrado cuando hay un error al enviar el presupuesto.', 'wc-scientific-cart')
            ),
            
            // Campos avanzados
            'min_quote_amount' => array(
                'section' => 'wc_scientific_cart_advanced',
                'title' => __('Monto Mínimo para Presupuesto', 'wc-scientific-cart'),
                'type' => 'number',
                'description' => __('Monto mínimo del carrito para poder solicitar presupuesto (0 = sin límite).', 'wc-scientific-cart')
            ),
            'auto_create_leads' => array(
                'section' => 'wc_scientific_cart_advanced',
                'title' => __('Crear Leads Automáticamente', 'wc-scientific-cart'),
                'type' => 'checkbox',
                'description' => __('Crear automáticamente leads en el CRM interno.', 'wc-scientific-cart')
            ),
            'require_cuit' => array(
                'section' => 'wc_scientific_cart_advanced',
                'title' => __('CUIT Requerido', 'wc-scientific-cart'),
                'type' => 'checkbox',
                'description' => __('Hacer obligatorio el CUIT para solicitar presupuestos.', 'wc-scientific-cart')
            ),
            'require_company' => array(
                'section' => 'wc_scientific_cart_advanced',
                'title' => __('Empresa Requerida', 'wc-scientific-cart'),
                'type' => 'checkbox',
                'description' => __('Hacer obligatorio el nombre de empresa para solicitar presupuestos.', 'wc-scientific-cart')
            ),
            'debug_mode' => array(
                'section' => 'wc_scientific_cart_advanced',
                'title' => __('Modo Debug', 'wc-scientific-cart'),
                'type' => 'checkbox',
                'description' => __('Activar logs detallados para diagnóstico.', 'wc-scientific-cart')
            )
        );
        
        foreach ($fields as $field_id => $field_config) {
            register_setting('wc_scientific_cart_settings', 'wc_scientific_cart_' . $field_id);
            
            add_settings_field(
                'wc_scientific_cart_' . $field_id,
                $field_config['title'],
                array($this, 'render_field'),
                'wc_scientific_cart_settings',
                $field_config['section'],
                array(
                    'field_id' => $field_id,
                    'type' => $field_config['type'],
                    'description' => $field_config['description']
                )
            );
        }
    }
    
    /**
     * Renderizar página de configuración
     */
    public function render_page() {
        // Procesar formulario si se envió
        if ($_POST && wp_verify_nonce($_POST['_wpnonce'], 'wc_scientific_cart_settings')) {
            $this->process_settings_form();
        }
        
        ?>
        <div class="wrap wc-scientific-cart-settings">
            <h1>
                <span class="dashicons dashicons-admin-tools"></span>
                <?php _e('Configuración - Carrito Científico', 'wc-scientific-cart'); ?>
            </h1>
            
            <div class="wc-scientific-cart-settings-wrapper">
                <nav class="nav-tab-wrapper">
                    <a href="#erp-config" class="nav-tab nav-tab-active" data-tab="erp-config">
                        <span class="dashicons dashicons-cloud"></span>
                        <?php _e('ERP', 'wc-scientific-cart'); ?>
                    </a>
                    <a href="#appearance" class="nav-tab" data-tab="appearance">
                        <span class="dashicons dashicons-admin-appearance"></span>
                        <?php _e('Apariencia', 'wc-scientific-cart'); ?>
                    </a>
                    <a href="#notifications" class="nav-tab" data-tab="notifications">
                        <span class="dashicons dashicons-email-alt"></span>
                        <?php _e('Notificaciones', 'wc-scientific-cart'); ?>
                    </a>
                    <a href="#advanced" class="nav-tab" data-tab="advanced">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php _e('Avanzado', 'wc-scientific-cart'); ?>
                    </a>
                </nav>
                
                <form method="post" action="">
                    <?php wp_nonce_field('wc_scientific_cart_settings'); ?>
                    
                    <div id="erp-config" class="tab-content active">
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2><?php _e('Configuración de Integración ERP', 'wc-scientific-cart'); ?></h2>
                            </div>
                            <div class="inside">
                                <?php $this->render_erp_section(); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div id="appearance" class="tab-content">
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2><?php _e('Personalización Visual', 'wc-scientific-cart'); ?></h2>
                            </div>
                            <div class="inside">
                                <?php $this->render_appearance_section(); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div id="notifications" class="tab-content">
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2><?php _e('Configuración de Notificaciones', 'wc-scientific-cart'); ?></h2>
                            </div>
                            <div class="inside">
                                <?php $this->render_notifications_section(); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div id="advanced" class="tab-content">
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2><?php _e('Configuración Avanzada', 'wc-scientific-cart'); ?></h2>
                            </div>
                            <div class="inside">
                                <?php $this->render_advanced_section(); ?>
                            </div>
                        </div>
                    </div>
                    
                    <p class="submit">
                        <input type="submit" name="submit" class="button-primary" value="<?php _e('Guardar Configuración', 'wc-scientific-cart'); ?>">
                        <button type="button" id="test-erp-connection" class="button" style="margin-left: 10px;">
                            <span class="dashicons dashicons-cloud"></span>
                            <?php _e('Probar Conexión ERP', 'wc-scientific-cart'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Renderizar sección ERP
     */
    private function render_erp_section() {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('URL del ERP', 'wc-scientific-cart'); ?></th>
                <td>
                    <input type="url" name="wc_scientific_cart_erp_endpoint" 
                           value="<?php echo esc_attr(get_option('wc_scientific_cart_erp_endpoint', '')); ?>" 
                           class="regular-text" placeholder="https://tu-erp.com/api/quotes" />
                    <p class="description"><?php _e('URL del endpoint de tu ERP para recibir solicitudes de presupuesto.', 'wc-scientific-cart'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('API Key del ERP', 'wc-scientific-cart'); ?></th>
                <td>
                    <input type="password" name="wc_scientific_cart_erp_api_key" 
                           value="<?php echo esc_attr(get_option('wc_scientific_cart_erp_api_key', '')); ?>" 
                           class="regular-text" />
                    <p class="description"><?php _e('Clave de autenticación para el ERP.', 'wc-scientific-cart'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('URL de Webhook (Fallback)', 'wc-scientific-cart'); ?></th>
                <td>
                    <input type="url" name="wc_scientific_cart_webhook_url" 
                           value="<?php echo esc_attr(get_option('wc_scientific_cart_webhook_url', '')); ?>" 
                           class="regular-text" />
                    <p class="description"><?php _e('URL alternativa para recibir notificaciones si el ERP no está disponible.', 'wc-scientific-cart'); ?></p>
                </td>
            </tr>
        </table>
        
        <div class="erp-status-panel">
            <h4><?php _e('Estado de la Conexión ERP', 'wc-scientific-cart'); ?></h4>
            <div id="erp-status-indicator" class="status-indicator">
                <span class="status-icon"></span>
                <span class="status-text"><?php _e('Sin configurar', 'wc-scientific-cart'); ?></span>
            </div>
        </div>
        <?php
    }
    
    /**
     * Renderizar sección de apariencia
     */
    private function render_appearance_section() {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Título del Header', 'wc-scientific-cart'); ?></th>
                <td>
                    <input type="text" name="wc_scientific_cart_header_title" 
                           value="<?php echo esc_attr(get_option('wc_scientific_cart_header_title', __('Carrito de Soluciones Analíticas', 'wc-scientific-cart'))); ?>" 
                           class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Subtítulo del Header', 'wc-scientific-cart'); ?></th>
                <td>
                    <textarea name="wc_scientific_cart_header_subtitle" rows="3" class="large-text"><?php echo esc_textarea(get_option('wc_scientific_cart_header_subtitle', __('Revisa tus productos seleccionados y solicita tu presupuesto personalizado', 'wc-scientific-cart'))); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Texto del Botón', 'wc-scientific-cart'); ?></th>
                <td>
                    <input type="text" name="wc_scientific_cart_button_text" 
                           value="<?php echo esc_attr(get_option('wc_scientific_cart_button_text', __('Solicitar Presupuesto', 'wc-scientific-cart'))); ?>" 
                           class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Colores del Tema', 'wc-scientific-cart'); ?></th>
                <td>
                    <div class="color-picker-group">
                        <div class="color-picker-item">
                            <label><?php _e('Color Primario:', 'wc-scientific-cart'); ?></label>
                            <input type="text" name="wc_scientific_cart_primary_color" 
                                   value="<?php echo esc_attr(get_option('wc_scientific_cart_primary_color', '#2196F3')); ?>" 
                                   class="color-picker" />
                        </div>
                        <div class="color-picker-item">
                            <label><?php _e('Color Secundario:', 'wc-scientific-cart'); ?></label>
                            <input type="text" name="wc_scientific_cart_secondary_color" 
                                   value="<?php echo esc_attr(get_option('wc_scientific_cart_secondary_color', '#4FC3F7')); ?>" 
                                   class="color-picker" />
                        </div>
                        <div class="color-picker-item">
                            <label><?php _e('Color de Acento:', 'wc-scientific-cart'); ?></label>
                            <input type="text" name="wc_scientific_cart_accent_color" 
                                   value="<?php echo esc_attr(get_option('wc_scientific_cart_accent_color', '#1976D2')); ?>" 
                                   class="color-picker" />
                        </div>
                    </div>
                    <p class="description"><?php _e('Personaliza los colores del tema científico.', 'wc-scientific-cart'); ?></p>
                </td>
            </tr>
        </table>
        
        <div class="appearance-preview">
            <h4><?php _e('Vista Previa', 'wc-scientific-cart'); ?></h4>
            <div class="preview-panel" id="appearance-preview">
                <!-- Preview será generado por JavaScript -->
            </div>
        </div>
        <?php
    }
    
    /**
     * Renderizar sección de notificaciones
     */
    private function render_notifications_section() {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Notificaciones por Email', 'wc-scientific-cart'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wc_scientific_cart_email_notifications" value="yes" 
                               <?php checked('yes', get_option('wc_scientific_cart_email_notifications', 'yes')); ?> />
                        <?php _e('Enviar notificaciones por email cuando se reciban presupuestos', 'wc-scientific-cart'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Email del Administrador', 'wc-scientific-cart'); ?></th>
                <td>
                    <input type="email" name="wc_scientific_cart_admin_email" 
                           value="<?php echo esc_attr(get_option('wc_scientific_cart_admin_email', get_option('admin_email'))); ?>" 
                           class="regular-text" />
                    <p class="description"><?php _e('Email donde recibir las notificaciones de presupuestos.', 'wc-scientific-cart'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Mensaje de Éxito', 'wc-scientific-cart'); ?></th>
                <td>
                    <textarea name="wc_scientific_cart_success_message" rows="3" class="large-text"><?php echo esc_textarea(get_option('wc_scientific_cart_success_message', __('¡Presupuesto solicitado exitosamente! Nos pondremos en contacto contigo pronto.', 'wc-scientific-cart'))); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Mensaje de Error', 'wc-scientific-cart'); ?></th>
                <td>
                    <textarea name="wc_scientific_cart_error_message" rows="3" class="large-text"><?php echo esc_textarea(get_option('wc_scientific_cart_error_message', __('Error al solicitar presupuesto. Por favor, inténtalo nuevamente.', 'wc-scientific-cart'))); ?></textarea>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Renderizar sección avanzada
     */
    private function render_advanced_section() {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Monto Mínimo para Presupuesto', 'wc-scientific-cart'); ?></th>
                <td>
                    <input type="number" name="wc_scientific_cart_min_quote_amount" 
                           value="<?php echo esc_attr(get_option('wc_scientific_cart_min_quote_amount', '0')); ?>" 
                           min="0" step="0.01" class="small-text" />
                    <span class="description"><?php echo get_woocommerce_currency_symbol(); ?> (0 = sin límite)</span>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Opciones de Validación', 'wc-scientific-cart'); ?></th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="wc_scientific_cart_require_cuit" value="yes" 
                                   <?php checked('yes', get_option('wc_scientific_cart_require_cuit', 'yes')); ?> />
                            <?php _e('CUIT requerido para solicitar presupuestos', 'wc-scientific-cart'); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="wc_scientific_cart_require_company" value="yes" 
                                   <?php checked('yes', get_option('wc_scientific_cart_require_company', 'yes')); ?> />
                            <?php _e('Nombre de empresa requerido', 'wc-scientific-cart'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('CRM Interno', 'wc-scientific-cart'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wc_scientific_cart_auto_create_leads" value="yes" 
                               <?php checked('yes', get_option('wc_scientific_cart_auto_create_leads', 'yes')); ?> />
                        <?php _e('Crear automáticamente leads en el CRM interno', 'wc-scientific-cart'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Modo Debug', 'wc-scientific-cart'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wc_scientific_cart_debug_mode" value="yes" 
                               <?php checked('yes', get_option('wc_scientific_cart_debug_mode', 'no')); ?> />
                        <?php _e('Activar logs detallados para diagnóstico', 'wc-scientific-cart'); ?>
                    </label>
                    <p class="description"><?php _e('Solo activar durante la resolución de problemas.', 'wc-scientific-cart'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Procesar formulario de configuración
     */
    private function process_settings_form() {
        $settings_fields = array(
            'erp_endpoint', 'erp_api_key', 'webhook_url',
            'header_title', 'header_subtitle', 'button_text',
            'primary_color', 'secondary_color', 'accent_color',
            'email_notifications', 'admin_email', 'success_message', 'error_message',
            'min_quote_amount', 'auto_create_leads', 'require_cuit', 'require_company', 'debug_mode'
        );
        
        $updated = false;
        
        foreach ($settings_fields as $field) {
            $option_name = 'wc_scientific_cart_' . $field;
            $value = $_POST[$option_name] ?? '';
            
            // Sanitizar según el tipo de campo
            switch ($field) {
                case 'erp_endpoint':
                case 'webhook_url':
                    $value = esc_url_raw($value);
                    break;
                case 'admin_email':
                    $value = sanitize_email($value);
                    break;
                case 'primary_color':
                case 'secondary_color':
                case 'accent_color':
                    $value = sanitize_hex_color($value);
                    break;
                case 'min_quote_amount':
                    $value = floatval($value);
                    break;
                case 'email_notifications':
                case 'auto_create_leads':
                case 'require_cuit':
                case 'require_company':
                case 'debug_mode':
                    $value = isset($_POST[$option_name]) ? 'yes' : 'no';
                    break;
                case 'success_message':
                case 'error_message':
                case 'header_subtitle':
                    $value = sanitize_textarea_field($value);
                    break;
                default:
                    $value = sanitize_text_field($value);
                    break;
            }
            
            if (update_option($option_name, $value)) {
                $updated = true;
            }
        }
        
        if ($updated) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>' . __('Configuración guardada correctamente.', 'wc-scientific-cart') . '</p></div>';
            });
        }
    }
    
    /**
     * Obtener configuraciones por defecto
     */
    private function get_default_settings() {
        return wc_scientific_cart_get_default_settings();
    }
    
    /**
     * Callbacks para secciones
     */
    public function erp_section_callback() {
        echo '<p>' . __('Configure la integración con su sistema ERP para automatizar el procesamiento de presupuestos.', 'wc-scientific-cart') . '</p>';
    }
    
    public function appearance_section_callback() {
        echo '<p>' . __('Personalice la apariencia del carrito científico para que coincida con su marca.', 'wc-scientific-cart') . '</p>';
    }
    
    public function notifications_section_callback() {
        echo '<p>' . __('Configure cómo y cuándo recibir notificaciones de nuevos presupuestos.', 'wc-scientific-cart') . '</p>';
    }
    
    public function advanced_section_callback() {
        echo '<p>' . __('Configuraciones avanzadas para personalizar el comportamiento del plugin.', 'wc-scientific-cart') . '</p>';
    }
    
    /**
     * Renderizar campo individual
     */
    public function render_field($args) {
        $field_id = $args['field_id'];
        $type = $args['type'];
        $description = $args['description'];
        $option_name = 'wc_scientific_cart_' . $field_id;
        $value = get_option($option_name, '');
        
        switch ($type) {
            case 'text':
                echo '<input type="text" name="' . $option_name . '" value="' . esc_attr($value) . '" class="regular-text" />';
                break;
            case 'url':
                echo '<input type="url" name="' . $option_name . '" value="' . esc_attr($value) . '" class="regular-text" />';
                break;
            case 'email':
                echo '<input type="email" name="' . $option_name . '" value="' . esc_attr($value) . '" class="regular-text" />';
                break;
            case 'password':
                echo '<input type="password" name="' . $option_name . '" value="' . esc_attr($value) . '" class="regular-text" />';
                break;
            case 'number':
                echo '<input type="number" name="' . $option_name . '" value="' . esc_attr($value) . '" class="small-text" step="0.01" min="0" />';
                break;
            case 'textarea':
                echo '<textarea name="' . $option_name . '" rows="3" class="large-text">' . esc_textarea($value) . '</textarea>';
                break;
            case 'checkbox':
                echo '<label><input type="checkbox" name="' . $option_name . '" value="yes" ' . checked('yes', $value, false) . ' /> ' . $description . '</label>';
                break;
            case 'color':
                echo '<input type="text" name="' . $option_name . '" value="' . esc_attr($value) . '" class="color-picker" />';
                break;
        }
        
        if ($type !== 'checkbox' && !empty($description)) {
            echo '<p class="description">' . $description . '</p>';
        }
    }
}
?>
