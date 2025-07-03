<?php
/**
 * Administrador principal para el plugin WooCommerce Carrito Científico
 *
 * @package WC_Scientific_Cart
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del administrador
 */
class WC_Scientific_Cart_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Inicializar hooks del admin
     */
    private function init_hooks() {
        // Menús del admin
        add_action('admin_menu', array($this, 'add_admin_menus'));
        
        // Enqueue scripts y styles del admin
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Hooks de productos
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_scientific_product_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_scientific_product_fields'));
        
        // Metaboxes personalizados
        add_action('add_meta_boxes', array($this, 'add_product_metaboxes'));
        
        // Columnas personalizadas en listados
        add_filter('manage_product_posts_columns', array($this, 'add_product_columns'));
        add_action('manage_product_posts_custom_column', array($this, 'populate_product_columns'), 10, 2);
        
        // Notices del admin
        add_action('admin_notices', array($this, 'display_admin_notices'));
        
        // AJAX del admin
        add_action('wp_ajax_wc_scientific_cart_test_erp_connection', array($this, 'test_erp_connection'));
        add_action('wp_ajax_wc_scientific_cart_export_data', array($this, 'export_data'));
        add_action('wp_ajax_wc_scientific_cart_import_products', array($this, 'import_scientific_products'));
    }
    
    /**
     * Agregar menús del administrador
     */
    public function add_admin_menus() {
        // Menú principal
        add_submenu_page(
            'woocommerce',
            __('Carrito Científico', 'wc-scientific-cart'),
            __('Carrito Científico', 'wc-scientific-cart'),
            'manage_woocommerce',
            'wc-scientific-cart',
            array($this, 'dashboard_page')
        );
        
        // Configuración
        add_submenu_page(
            'woocommerce',
            __('Configuración - Carrito Científico', 'wc-scientific-cart'),
            __('Config. Científico', 'wc-scientific-cart'),
            'manage_woocommerce',
            'wc-scientific-cart-settings',
            array($this, 'settings_page')
        );
        
        // Presupuestos
        add_submenu_page(
            'woocommerce',
            __('Presupuestos - Carrito Científico', 'wc-scientific-cart'),
            __('Presupuestos', 'wc-scientific-cart'),
            'manage_woocommerce',
            'wc-scientific-cart-quotes',
            array($this, 'quotes_page')
        );
        
        // CRM Leads
        add_submenu_page(
            'woocommerce',
            __('CRM Leads - Carrito Científico', 'wc-scientific-cart'),
            __('CRM Leads', 'wc-scientific-cart'),
            'manage_woocommerce',
            'wc-scientific-cart-crm',
            array($this, 'crm_page')
        );
        
        // Reportes
        add_submenu_page(
            'woocommerce',
            __('Reportes - Carrito Científico', 'wc-scientific-cart'),
            __('Reportes', 'wc-scientific-cart'),
            'manage_woocommerce',
            'wc-scientific-cart-reports',
            array($this, 'reports_page')
        );
    }
    
    /**
     * Enqueue assets del admin
     */
    public function enqueue_admin_assets($hook) {
        // Solo cargar en páginas del plugin
        if (strpos($hook, 'wc-scientific-cart') === false && $hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        
        // CSS del admin
        wp_enqueue_style(
            'wc-scientific-cart-admin',
            WC_SCIENTIFIC_CART_PLUGIN_URL . 'admin/assets/css/admin.css',
            array(),
            WC_SCIENTIFIC_CART_VERSION
        );
        
        // JavaScript del admin
        wp_enqueue_script(
            'wc-scientific-cart-admin',
            WC_SCIENTIFIC_CART_PLUGIN_URL . 'admin/assets/js/admin.js',
            array('jquery', 'wp-color-picker'),
            WC_SCIENTIFIC_CART_VERSION,
            true
        );
        
        // Localizar script
        wp_localize_script('wc-scientific-cart-admin', 'wc_scientific_cart_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc_scientific_cart_admin_nonce'),
            'strings' => array(
                'testing_connection' => __('Probando conexión...', 'wc-scientific-cart'),
                'connection_success' => __('Conexión exitosa', 'wc-scientific-cart'),
                'connection_failed' => __('Error de conexión', 'wc-scientific-cart'),
                'confirm_delete' => __('¿Estás seguro de eliminar este elemento?', 'wc-scientific-cart'),
                'saving' => __('Guardando...', 'wc-scientific-cart'),
                'saved' => __('Guardado', 'wc-scientific-cart'),
                'error' => __('Error', 'wc-scientific-cart')
            )
        ));
        
        // Color picker
        wp_enqueue_style('wp-color-picker');
    }
    
    /**
     * Página del dashboard
     */
    public function dashboard_page() {
        $main_instance = WC_Scientific_Cart_Main::get_instance();
        $stats = $main_instance->get_stats();
        $erp_stats = $main_instance->erp_integration->get_erp_stats();
        
        include WC_SCIENTIFIC_CART_PLUGIN_PATH . 'admin/views/dashboard.php';
    }
    
    /**
     * Página de configuración
     */
    public function settings_page() {
        $settings = new WC_Scientific_Cart_Settings();
        $settings->render_page();
    }
    
    /**
     * Página de presupuestos
     */
    public function quotes_page() {
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'view':
                $this->view_quote_page();
                break;
            case 'edit':
                $this->edit_quote_page();
                break;
            default:
                $this->list_quotes_page();
                break;
        }
    }
    
    /**
     * Página de CRM
     */
    public function crm_page() {
        global $wpdb;
        
        $crm_table = $wpdb->prefix . 'scientific_cart_crm_leads';
        
        // Verificar si la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$crm_table'") != $crm_table) {
            echo '<div class="notice notice-info"><p>' . __('La tabla CRM se creará automáticamente cuando recibas tu primer lead.', 'wc-scientific-cart') . '</p></div>';
            return;
        }
        
        $leads = $wpdb->get_results("SELECT * FROM $crm_table ORDER BY created_at DESC LIMIT 50");
        
        include WC_SCIENTIFIC_CART_PLUGIN_PATH . 'admin/views/crm.php';
    }
    
    /**
     * Página de reportes
     */
    public function reports_page() {
        include WC_SCIENTIFIC_CART_PLUGIN_PATH . 'admin/views/reports.php';
    }
    
    /**
     * Listar presupuestos
     */
    private function list_quotes_page() {
        global $wpdb;
        
        $quotes_table = $wpdb->prefix . 'scientific_cart_quotes';
        $per_page = 20;
        $current_page = max(1, intval($_GET['paged'] ?? 1));
        $offset = ($current_page - 1) * $per_page;
        
        // Filtros
        $where_clause = '1=1';
        $where_params = array();
        
        if (!empty($_GET['status'])) {
            $where_clause .= ' AND status = %s';
            $where_params[] = sanitize_text_field($_GET['status']);
        }
        
        if (!empty($_GET['date_from'])) {
            $where_clause .= ' AND created_at >= %s';
            $where_params[] = sanitize_text_field($_GET['date_from']) . ' 00:00:00';
        }
        
        if (!empty($_GET['date_to'])) {
            $where_clause .= ' AND created_at <= %s';
            $where_params[] = sanitize_text_field($_GET['date_to']) . ' 23:59:59';
        }
        
        // Consulta principal
        $query = "SELECT * FROM $quotes_table WHERE $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $where_params[] = $per_page;
        $where_params[] = $offset;
        
        if (!empty($where_params)) {
            $query = $wpdb->prepare($query, $where_params);
        }
        
        $quotes = $wpdb->get_results($query);
        
        // Total para paginación
        $total_query = "SELECT COUNT(*) FROM $quotes_table WHERE $where_clause";
        if (count($where_params) > 2) { // Si hay filtros
            $total_query = $wpdb->prepare($total_query, array_slice($where_params, 0, -2));
        }
        $total_items = $wpdb->get_var($total_query);
        
        include WC_SCIENTIFIC_CART_PLUGIN_PATH . 'admin/views/quotes-list.php';
    }
    
    /**
     * Ver presupuesto individual
     */
    private function view_quote_page() {
        $quote_id = intval($_GET['id'] ?? 0);
        
        if (!$quote_id) {
            wp_die(__('ID de presupuesto inválido.', 'wc-scientific-cart'));
        }
        
        global $wpdb;
        $quotes_table = $wpdb->prefix . 'scientific_cart_quotes';
        
        $quote = $wpdb->get_row($wpdb->prepare("SELECT * FROM $quotes_table WHERE id = %d", $quote_id));
        
        if (!$quote) {
            wp_die(__('Presupuesto no encontrado.', 'wc-scientific-cart'));
        }
        
        $quote_data = json_decode($quote->products_data, true);
        
        include WC_SCIENTIFIC_CART_PLUGIN_PATH . 'admin/views/quote-view.php';
    }
    
    /**
     * Editar presupuesto
     */
    private function edit_quote_page() {
        $quote_id = intval($_GET['id'] ?? 0);
        
        if (!$quote_id) {
            wp_die(__('ID de presupuesto inválido.', 'wc-scientific-cart'));
        }
        
        // Procesar formulario si se envió
        if ($_POST && wp_verify_nonce($_POST['_wpnonce'], 'edit_quote_' . $quote_id)) {
            $this->process_quote_edit($quote_id);
        }
        
        global $wpdb;
        $quotes_table = $wpdb->prefix . 'scientific_cart_quotes';
        
        $quote = $wpdb->get_row($wpdb->prepare("SELECT * FROM $quotes_table WHERE id = %d", $quote_id));
        
        if (!$quote) {
            wp_die(__('Presupuesto no encontrado.', 'wc-scientific-cart'));
        }
        
        $quote_data = json_decode($quote->products_data, true);
        
        include WC_SCIENTIFIC_CART_PLUGIN_PATH . 'admin/views/quote-edit.php';
    }
    
    /**
     * Procesar edición de presupuesto
     */
    private function process_quote_edit($quote_id) {
        global $wpdb;
        
        $quotes_table = $wpdb->prefix . 'scientific_cart_quotes';
        
        $updated_data = array(
            'status' => sanitize_text_field($_POST['status']),
            'priority' => sanitize_text_field($_POST['priority']),
            'notes' => sanitize_textarea_field($_POST['notes'])
        );
        
        $result = $wpdb->update(
            $quotes_table,
            $updated_data,
            array('id' => $quote_id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>' . __('Presupuesto actualizado correctamente.', 'wc-scientific-cart') . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . __('Error al actualizar el presupuesto.', 'wc-scientific-cart') . '</p></div>';
            });
        }
    }
    
    /**
     * Agregar campos científicos a productos
     */
    public function add_scientific_product_fields() {
        global $post;
        
        echo '<div class="options_group scientific-product-fields">';
        
        // Número de catálogo
        woocommerce_wp_text_input(array(
            'id' => '_catalog_number',
            'label' => __('Número de Catálogo', 'wc-scientific-cart'),
            'desc_tip' => true,
            'description' => __('Número de catálogo del producto científico.', 'wc-scientific-cart')
        ));
        
        // Número CAS
        woocommerce_wp_text_input(array(
            'id' => '_cas_number',
            'label' => __('Número CAS', 'wc-scientific-cart'),
            'desc_tip' => true,
            'description' => __('Chemical Abstracts Service Registry Number.', 'wc-scientific-cart')
        ));
        
        // Fórmula molecular
        woocommerce_wp_text_input(array(
            'id' => '_molecular_formula',
            'label' => __('Fórmula Molecular', 'wc-scientific-cart'),
            'desc_tip' => true,
            'description' => __('Fórmula química del compuesto.', 'wc-scientific-cart')
        ));
        
        // Peso molecular
        woocommerce_wp_text_input(array(
            'id' => '_molecular_weight',
            'label' => __('Peso Molecular', 'wc-scientific-cart'),
            'desc_tip' => true,
            'description' => __('Peso molecular en g/mol.', 'wc-scientific-cart'),
            'type' => 'number',
            'custom_attributes' => array('step' => '0.01')
        ));
        
        // Nivel de pureza
        woocommerce_wp_text_input(array(
            'id' => '_purity_level',
            'label' => __('Nivel de Pureza', 'wc-scientific-cart'),
            'desc_tip' => true,
            'description' => __('Porcentaje de pureza (ej: 99.5%).', 'wc-scientific-cart'),
            'placeholder' => '99.5%'
        ));
        
        // Condiciones de almacenamiento
        woocommerce_wp_select(array(
            'id' => '_storage_conditions',
            'label' => __('Condiciones de Almacenamiento', 'wc-scientific-cart'),
            'options' => array(
                '' => __('Seleccionar...', 'wc-scientific-cart'),
                'room_temp' => __('Temperatura ambiente', 'wc-scientific-cart'),
                'refrigerated' => __('Refrigerado (2-8°C)', 'wc-scientific-cart'),
                'frozen' => __('Congelado (-20°C)', 'wc-scientific-cart'),
                'dry_place' => __('Lugar seco', 'wc-scientific-cart'),
                'dark_place' => __('Lugar oscuro', 'wc-scientific-cart'),
                'inert_atmosphere' => __('Atmósfera inerte', 'wc-scientific-cart')
            )
        ));
        
        // Clase de peligro
        woocommerce_wp_select(array(
            'id' => '_hazard_class',
            'label' => __('Clase de Peligro', 'wc-scientific-cart'),
            'options' => array(
                '' => __('Sin clasificar', 'wc-scientific-cart'),
                'explosive' => __('Explosivo', 'wc-scientific-cart'),
                'flammable' => __('Inflamable', 'wc-scientific-cart'),
                'oxidizing' => __('Oxidante', 'wc-scientific-cart'),
                'toxic' => __('Tóxico', 'wc-scientific-cart'),
                'corrosive' => __('Corrosivo', 'wc-scientific-cart'),
                'irritant' => __('Irritante', 'wc-scientific-cart'),
                'environmental' => __('Peligroso para el medio ambiente', 'wc-scientific-cart')
            )
        ));
        
        // Fabricante
        woocommerce_wp_text_input(array(
            'id' => '_manufacturer',
            'label' => __('Fabricante', 'wc-scientific-cart'),
            'desc_tip' => true,
            'description' => __('Empresa fabricante del producto.', 'wc-scientific-cart')
        ));
        
        echo '</div>';
    }
    
    /**
     * Guardar campos científicos del producto
     */
    public function save_scientific_product_fields($post_id) {
        $scientific_fields = array(
            '_catalog_number',
            '_cas_number',
            '_molecular_formula',
            '_molecular_weight',
            '_purity_level',
            '_storage_conditions',
            '_hazard_class',
            '_manufacturer'
        );
        
        foreach ($scientific_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Guardar especificaciones técnicas
        if (isset($_POST['_technical_specifications'])) {
            update_post_meta($post_id, '_technical_specifications', wp_kses_post($_POST['_technical_specifications']));
        }
    }
    
    /**
     * Agregar metaboxes personalizados
     */
    public function add_product_metaboxes() {
        add_meta_box(
            'scientific-product-specs',
            __('Especificaciones Técnicas Científicas', 'wc-scientific-cart'),
            array($this, 'render_technical_specs_metabox'),
            'product',
            'normal',
            'high'
        );
    }
    
    /**
     * Renderizar metabox de especificaciones técnicas
     */
    public function render_technical_specs_metabox($post) {
        $technical_specs = get_post_meta($post->ID, '_technical_specifications', true);
        
        wp_nonce_field('save_technical_specs', 'technical_specs_nonce');
        
        echo '<div class="scientific-specs-editor">';
        echo '<label for="technical_specifications">' . __('Especificaciones Técnicas Detalladas:', 'wc-scientific-cart') . '</label>';
        
        wp_editor($technical_specs, '_technical_specifications', array(
            'textarea_name' => '_technical_specifications',
            'textarea_rows' => 10,
            'media_buttons' => false,
            'teeny' => true,
            'quicktags' => true
        ));
        
        echo '<p class="description">' . __('Información técnica detallada del producto. Puede incluir tablas, listas y formato HTML básico.', 'wc-scientific-cart') . '</p>';
        echo '</div>';
    }
    
    /**
     * Agregar columnas a la lista de productos
     */
    public function add_product_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $column) {
            $new_columns[$key] = $column;
            
            if ($key === 'sku') {
                $new_columns['catalog_number'] = __('Catálogo', 'wc-scientific-cart');
                $new_columns['cas_number'] = __('CAS', 'wc-scientific-cart');
                $new_columns['purity'] = __('Pureza', 'wc-scientific-cart');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Poblar columnas personalizadas
     */
    public function populate_product_columns($column, $post_id) {
        switch ($column) {
            case 'catalog_number':
                $catalog = get_post_meta($post_id, '_catalog_number', true);
                echo $catalog ? esc_html($catalog) : '—';
                break;
                
            case 'cas_number':
                $cas = get_post_meta($post_id, '_cas_number', true);
                echo $cas ? esc_html($cas) : '—';
                break;
                
            case 'purity':
                $purity = get_post_meta($post_id, '_purity_level', true);
                if ($purity) {
                    $purity_num = floatval(str_replace('%', '', $purity));
                    $class = $purity_num >= 99 ? 'high-purity' : ($purity_num >= 95 ? 'medium-purity' : 'low-purity');
                    echo '<span class="purity-badge ' . $class . '">' . esc_html($purity) . '</span>';
                } else {
                    echo '—';
                }
                break;
        }
    }
    
    /**
     * Mostrar notices del admin
     */
    public function display_admin_notices() {
        $screen = get_current_screen();
        
        if (strpos($screen->id, 'wc-scientific-cart') === false) {
            return;
        }
        
        // Verificar configuración
        $erp_endpoint = get_option('wc_scientific_cart_erp_endpoint', '');
        if (empty($erp_endpoint)) {
            echo '<div class="notice notice-warning">';
            echo '<p><strong>' . __('Carrito Científico:', 'wc-scientific-cart') . '</strong> ';
            echo sprintf(
                __('No has configurado la integración ERP. %sConfigurarlo ahora%s', 'wc-scientific-cart'),
                '<a href="' . admin_url('admin.php?page=wc-scientific-cart-settings#erp-config') . '">',
                '</a>'
            );
            echo '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Probar conexión ERP vía AJAX
     */
    public function test_erp_connection() {
        if (!wp_verify_nonce($_POST['nonce'], 'wc_scientific_cart_admin_nonce')) {
            wp_send_json_error(array('message' => __('Error de seguridad.', 'wc-scientific-cart')));
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('No tienes permisos suficientes.', 'wc-scientific-cart')));
        }
        
        $main_instance = WC_Scientific_Cart_Main::get_instance();
        $result = $main_instance->erp_integration->test_connection();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Exportar datos vía AJAX
     */
    public function export_data() {
        if (!wp_verify_nonce($_POST['nonce'], 'wc_scientific_cart_admin_nonce')) {
            wp_send_json_error(array('message' => __('Error de seguridad.', 'wc-scientific-cart')));
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('No tienes permisos suficientes.', 'wc-scientific-cart')));
        }
        
        $ajax_handler = new WC_Scientific_Cart_Ajax_Handler();
        $ajax_handler->export_quotes();
    }
    
    /**
     * Importar productos científicos
     */
    public function import_scientific_products() {
        if (!wp_verify_nonce($_POST['nonce'], 'wc_scientific_cart_admin_nonce')) {
            wp_send_json_error(array('message' => __('Error de seguridad.', 'wc-scientific-cart')));
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('No tienes permisos suficientes.', 'wc-scientific-cart')));
        }
        
        // Aquí iría la lógica de importación
        wp_send_json_success(array('message' => __('Funcionalidad de importación próximamente.', 'wc-scientific-cart')));
    }
}
?>
