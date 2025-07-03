<?php
/**
 * Personalizador del carrito para el plugin WooCommerce Carrito Científico
 *
 * @package WC_Scientific_Cart
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para personalizar la apariencia y funcionalidad del carrito
 */
class WC_Scientific_Cart_Customizer {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // Hooks de personalización visual
        add_action('wp_head', array($this, 'add_custom_styles'));
        add_action('wp_footer', array($this, 'add_custom_scripts'));
        
        // Hooks del carrito
        add_filter('woocommerce_cart_item_remove_link', array($this, 'customize_remove_link'), 10, 2);
        add_filter('woocommerce_cart_item_quantity', array($this, 'customize_quantity_input'), 10, 3);
        add_action('woocommerce_cart_coupon', array($this, 'customize_coupon_form()));
        add_action('woocommerce_cart_contents', array($this, 'add_cart_enhancement_features'));
        
        // Hooks de productos
        add_filter('woocommerce_cart_item_thumbnail', array($this, 'customize_product_thumbnail'), 10, 3);
        add_action('woocommerce_cart_item_name', array($this, 'add_product_badges'), 20, 3);
        
        // Hooks de totales
        add_action('woocommerce_cart_totals_before_shipping', array($this, 'add_scientific_info_section'));
        add_action('woocommerce_review_order_before_submit', array($this, 'add_quote_info_notice'));
        
        // Hooks de funcionalidad
        add_action('woocommerce_cart_updated', array($this, 'update_cart_session_data'));
        add_filter('woocommerce_add_to_cart_fragments', array($this, 'update_cart_fragments'));
    }
    
    /**
     * Agregar estilos personalizados
     */
    public function add_custom_styles() {
        if (!is_cart() && !is_checkout()) {
            return;
        }
        
        $primary_color = get_option('wc_scientific_cart_primary_color', '#2196F3');
        $secondary_color = get_option('wc_scientific_cart_secondary_color', '#4FC3F7');
        $accent_color = get_option('wc_scientific_cart_accent_color', '#1976D2');
        
        ?>
        <style id="wc-scientific-cart-styles">
        :root {
            --wc-sci-primary: <?php echo esc_attr($primary_color); ?>;
            --wc-sci-secondary: <?php echo esc_attr($secondary_color); ?>;
            --wc-sci-accent: <?php echo esc_attr($accent_color); ?>;
            --wc-sci-light: #E3F2FD;
            --wc-sci-gradient: linear-gradient(135deg, var(--wc-sci-light) 0%, #B3E5FC 100%);
        }
        
        /* Header personalizado del carrito */
        .scientific-cart-header {
            background: var(--wc-sci-gradient);
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
            border: 1px solid var(--wc-sci-secondary);
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
            z-index: 0;
        }
        
        @keyframes float {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
        
        .scientific-cart-header .scientific-icon {
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }
        
        .scientific-cart-header .scientific-title {
            color: var(--wc-sci-accent);
            font-size: 28px;
            font-weight: 600;
            margin: 0 0 10px 0;
            position: relative;
            z-index: 1;
        }
        
        .scientific-cart-header .scientific-subtitle {
            color: #37474F;
            font-size: 16px;
            margin: 0;
            position: relative;
            z-index: 1;
        }
        
        /* Sección de presupuesto */
        .scientific-quote-section {
            background: #F8F9FA;
            border: 2px solid var(--wc-sci-secondary);
            border-radius: 12px;
            padding: 25px;
            margin: 20px 0;
            animation: slideInUp 0.5s ease-out;
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .scientific-quote-section h3 {
            color: var(--wc-sci-accent);
            font-size: 22px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .scientific-quote-section h3::before {
            content: '📋';
            font-size: 20px;
        }
        
        .customer-info {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid var(--wc-sci-primary);
            margin-bottom: 15px;
        }
        
        .customer-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        
        .customer-info .info-label {
            font-weight: 600;
            color: var(--wc-sci-accent);
        }
        
        /* Botón de presupuesto */
        .scientific-quote-btn {
            background: linear-gradient(135deg, var(--wc-sci-primary) 0%, var(--wc-sci-accent) 100%);
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
            text-decoration: none;
            justify-content: center;
            min-height: 50px;
        }
        
        .scientific-quote-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(33, 150, 243, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .scientific-quote-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .scientific-quote-btn .btn-icon {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Información de productos científicos */
        .scientific-product-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .scientific-product-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 8px;
        }
        
        .scientific-badge {
            background: var(--wc-sci-light);
            color: var(--wc-sci-accent);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            border: 1px solid var(--wc-sci-secondary);
        }
        
        .scientific-badge.sku-badge {
            background: #E8F5E8;
            color: #2E7D32;
            border-color: #A5D6A7;
        }
        
        .scientific-badge.catalog-badge {
            background: #FFF3E0;
            color: #F57C00;
            border-color: #FFCC02;
        }
        
        .scientific-badge.purity-badge {
            background: #F3E5F5;
            color: #7B1FA2;
            border-color: #CE93D8;
        }
        
        .product-scientific-details {
            margin-top: 10px;
            padding: 10px;
            background: #FAFAFA;
            border-radius: 6px;
            border-left: 3px solid var(--wc-sci-primary);
        }
        
        .product-scientific-details summary {
            cursor: pointer;
            color: var(--wc-sci-primary);
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .product-scientific-details .specs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .spec-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #E0E0E0;
        }
        
        .spec-label {
            font-weight: 500;
            color: #666;
        }
        
        .spec-value {
            color: #333;
        }
        
        /* Tabla del carrito */
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
            background: linear-gradient(135deg, var(--wc-sci-accent) 0%, #1565C0 100%);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        
        .woocommerce table.shop_table td {
            border-bottom: 1px solid var(--wc-sci-light);
            vertical-align: middle;
        }
        
        .woocommerce table.shop_table tr:hover td {
            background-color: #F8F9FA;
            transition: background-color 0.2s ease;
        }
        
        /* Personalización de cantidad */
        .quantity .qty {
            border: 2px solid var(--wc-sci-secondary);
            border-radius: 6px;
            padding: 8px 12px;
            text-align: center;
            font-weight: 600;
            transition: border-color 0.2s ease;
        }
        
        .quantity .qty:focus {
            border-color: var(--wc-sci-primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
        }
        
        /* Botón de eliminar personalizado */
        .product-remove a {
            background: #FFEBEE;
            color: #C62828;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .product-remove a:hover {
            background: #C62828;
            color: white;
            transform: scale(1.1);
        }
        
        /* Totales del carrito */
        .cart_totals {
            background: var(--wc-sci-light);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--wc-sci-secondary);
        }
        
        .cart_totals h2 {
            color: var(--wc-sci-accent);
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        .cart_totals .shop_table {
            background: white;
            border-radius: 8px;
        }
        
        .cart_totals .order-total {
            background: var(--wc-sci-gradient);
            font-weight: bold;
            font-size: 18px;
        }
        
        /* Cupones */
        .coupon {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .coupon input[type="text"] {
            flex: 1;
            padding: 10px;
            border: 2px solid var(--wc-sci-secondary);
            border-radius: 6px;
        }
        
        .coupon .button {
            background: var(--wc-sci-primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .coupon .button:hover {
            background: var(--wc-sci-accent);
        }
        
        /* Mensajes */
        .quote-success-message {
            background: #E8F5E8;
            color: #2E7D32;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #4CAF50;
            margin: 15px 0;
            animation: fadeIn 0.5s ease-out;
        }
        
        .quote-error-message {
            background: #FFEBEE;
            color: #C62828;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #F44336;
            margin: 15px 0;
            animation: fadeIn 0.5s ease-out;
        }
        
        .login-message {
            background: #FFF3E0;
            color: #F57C00;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #FF9800;
            margin: 20px 0;
            text-align: center;
        }
        
        .login-message .login-btn {
            background: var(--wc-sci-primary);
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
            transition: background-color 0.2s ease;
        }
        
        .login-message .login-btn:hover {
            background: var(--wc-sci-accent);
            text-decoration: none;
            color: white;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Información científica adicional */
        .scientific-info-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid var(--wc-sci-secondary);
            margin-bottom: 20px;
        }
        
        .scientific-info-section h4 {
            color: var(--wc-sci-accent);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .scientific-info-section h4::before {
            content: '🧪';
        }
        
        .scientific-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        
        .scientific-stat {
            text-align: center;
            padding: 10px;
            background: var(--wc-sci-light);
            border-radius: 6px;
        }
        
        .scientific-stat .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: var(--wc-sci-accent);
            display: block;
        }
        
        .scientific-stat .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .scientific-cart-header {
                padding: 20px;
            }
            
            .scientific-cart-header .scientific-title {
                font-size: 24px;
            }
            
            .scientific-quote-section {
                padding: 20px;
            }
            
            .scientific-quote-btn {
                width: 100%;
                justify-content: center;
            }
            
            .scientific-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .product-scientific-details .specs-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .scientific-cart-header .scientific-title {
                font-size: 20px;
            }
            
            .scientific-cart-header .scientific-subtitle {
                font-size: 14px;
            }
            
            .scientific-stats {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
    }
    
    /**
     * Agregar scripts personalizados
     */
    public function add_custom_scripts() {
        if (!is_cart()) {
            return;
        }
        
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Animaciones de entrada
            $('.scientific-quote-section').css('opacity', '0').animate({opacity: 1}, 500);
            
            // Confirmación para eliminar productos
            $('.product-remove a').on('click', function(e) {
                if (!confirm(wc_scientific_cart_ajax.confirm_remove || '¿Estás seguro?')) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Actualización automática del carrito
            $('.qty').on('change', function() {
                var $form = $('.woocommerce-cart-form');
                $form.find('[name="update_cart"]').prop('disabled', false);
                
                // Auto-submit después de un delay
                setTimeout(function() {
                    $form.find('[name="update_cart"]').trigger('click');
                }, 500);
            });
            
            // Tooltips para badges
            $('.scientific-badge').each(function() {
                $(this).attr('title', $(this).text());
            });
            
            // Expandir/colapsar detalles científicos
            $('.product-scientific-details summary').on('click', function() {
                $(this).parent().toggleClass('expanded');
            });
        });
        </script>
        <?php
    }
    
    /**
     * Personalizar enlace de eliminar producto
     */
    public function customize_remove_link($link, $cart_item_key) {
        return sprintf(
            '<a href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s" title="%s">×</a>',
            esc_url(wc_get_cart_remove_url($cart_item_key)),
            esc_attr__('Eliminar este artículo', 'wc-scientific-cart'),
            esc_attr(WC()->cart->cart_contents[$cart_item_key]['product_id']),
            esc_attr(WC()->cart->cart_contents[$cart_item_key]['data']->get_sku()),
            esc_attr__('Eliminar este artículo', 'wc-scientific-cart')
        );
    }
    
    /**
     * Personalizar input de cantidad
     */
    public function customize_quantity_input($product_quantity, $cart_item_key, $cart_item) {
        $product = $cart_item['data'];
        
        if ($product->is_sold_individually()) {
            return '<span class="quantity-individual">1</span>';
        }
        
        $quantity_html = woocommerce_quantity_input(array(
            'input_name'   => "cart[{$cart_item_key}][qty]",
            'input_value'  => $cart_item['quantity'],
            'max_value'    => $product->get_max_purchase_quantity(),
            'min_value'    => '0',
            'product_name' => $product->get_name(),
        ), $product, false);
        
        return $quantity_html;
    }
    
    /**
     * Personalizar thumbnail del producto
     */
    public function customize_product_thumbnail($thumbnail, $cart_item, $cart_item_key) {
        $product = $cart_item['data'];
        
        // Agregar overlay con información científica
        $scientific_data = wc_scientific_cart_get_product_scientific_data($product);
        
        $overlay = '';
        if (!empty($scientific_data['hazard_class'])) {
            $overlay = '<div class="product-hazard-overlay" title="' . esc_attr($scientific_data['hazard_class']) . '">⚠️</div>';
        }
        
        return '<div class="scientific-product-thumbnail">' . $thumbnail . $overlay . '</div>';
    }
    
    /**
     * Agregar badges de producto
     */
    public function add_product_badges($cart_item, $cart_item_key) {
        $product = WC()->cart->cart_contents[$cart_item_key]['data'];
        $scientific_data = wc_scientific_cart_get_product_scientific_data($product);
        
        $badges = array();
        
        if (!empty($product->get_sku())) {
            $badges[] = '<span class="scientific-badge sku-badge">SKU: ' . esc_html($product->get_sku()) . '</span>';
        }
        
        if (!empty($scientific_data['catalog_number'])) {
            $badges[] = '<span class="scientific-badge catalog-badge">Cat: ' . esc_html($scientific_data['catalog_number']) . '</span>';
        }
        
        if (!empty($scientific_data['purity_level'])) {
            $badges[] = '<span class="scientific-badge purity-badge">Pureza: ' . esc_html($scientific_data['purity_level']) . '</span>';
        }
        
        if (!empty($scientific_data['cas_number'])) {
            $badges[] = '<span class="scientific-badge">CAS: ' . esc_html($scientific_data['cas_number']) . '</span>';
        }
        
        if (!empty($badges)) {
            echo '<div class="scientific-product-badges">' . implode('', $badges) . '</div>';
        }
        
        // Agregar detalles expandibles
        $this->add_expandable_product_details($scientific_data);
    }
    
    /**
     * Agregar detalles expandibles del producto
     */
    private function add_expandable_product_details($scientific_data) {
        $details = array_filter($scientific_data);
        
        if (empty($details)) {
            return;
        }
        
        echo '<details class="product-scientific-details">';
        echo '<summary>' . __('Especificaciones Técnicas', 'wc-scientific-cart') . '</summary>';
        echo '<div class="specs-grid">';
        
        $labels = array(
            'molecular_formula' => __('Fórmula Molecular', 'wc-scientific-cart'),
            'molecular_weight' => __('Peso Molecular', 'wc-scientific-cart'),
            'storage_conditions' => __('Condiciones de Almacenamiento', 'wc-scientific-cart'),
            'hazard_class' => __('Clase de Peligro', 'wc-scientific-cart'),
            'manufacturer' => __('Fabricante', 'wc-scientific-cart')
        );
        
        foreach ($labels as $key => $label) {
            if (!empty($details[$key])) {
                echo '<div class="spec-item">';
                echo '<span class="spec-label">' . esc_html($label) . ':</span>';
                echo '<span class="spec-value">' . esc_html($details[$key]) . '</span>';
                echo '</div>';
            }
        }
        
        echo '</div>';
        echo '</details>';
    }
    
    /**
     * Agregar sección de información científica
     */
    public function add_scientific_info_section() {
        $cart = WC()->cart;
        $cart_data = wc_scientific_cart_get_cart_data();
        
        // Calcular estadísticas científicas
        $stats = $this->calculate_cart_scientific_stats($cart_data);
        
        wc_scientific_cart_get_template('cart-scientific-info.php', array(
            'stats' => $stats,
            'cart_data' => $cart_data
        ));
    }
    
    /**
     * Calcular estadísticas científicas del carrito
     */
    private function calculate_cart_scientific_stats($cart_data) {
        $stats = array(
            'total_products' => count($cart_data['items']),
            'unique_suppliers' => 0,
            'hazardous_items' => 0,
            'high_purity_items' => 0,
            'total_weight' => 0
        );
        
        $suppliers = array();
        
        foreach ($cart_data['items'] as $item) {
            $scientific_data = $item['product_data'];
            
            // Contar proveedores únicos
            if (!empty($scientific_data['manufacturer'])) {
                $suppliers[] = $scientific_data['manufacturer'];
            }
            
            // Contar ítems peligrosos
            if (!empty($scientific_data['hazard_class'])) {
                $stats['hazardous_items']++;
            }
            
            // Contar ítems de alta pureza (>95%)
            if (!empty($scientific_data['purity_level'])) {
                $purity = floatval(str_replace('%', '', $scientific_data['purity_level']));
                if ($purity >= 95) {
                    $stats['high_purity_items']++;
                }
            }
        }
        
        $stats['unique_suppliers'] = count(array_unique($suppliers));
        
        return $stats;
    }
    
    /**
     * Agregar aviso sobre presupuestos
     */
    public function add_quote_info_notice() {
        if (!is_user_logged_in()) {
            return;
        }
        
        echo '<div class="scientific-quote-info-notice">';
        echo '<p><strong>💡 ' . __('Consejo:', 'wc-scientific-cart') . '</strong> ';
        echo __('Solicita un presupuesto personalizado para obtener precios preferenciales y condiciones especiales.', 'wc-scientific-cart');
        echo '</p>';
        echo '</div>';
    }
    
    /**
     * Actualizar datos de sesión del carrito
     */
    public function update_cart_session_data() {
        $cart_data = wc_scientific_cart_get_cart_data();
        WC()->session->set('scientific_cart_data', $cart_data);
        
        // Hook para desarrolladores
        do_action('wc_scientific_cart_cart_updated', $cart_data);
    }
    
    /**
     * Actualizar fragmentos del carrito para AJAX
     */
    public function update_cart_fragments($fragments) {
        $cart_data = wc_scientific_cart_get_cart_data();
        
        // Agregar fragmento del contador de productos científicos
        ob_start();
        $stats = $this->calculate_cart_scientific_stats($cart_data);
        echo '<span class="scientific-cart-count">' . $stats['total_products'] . '</span>';
        $fragments['.scientific-cart-count'] = ob_get_clean();
        
        // Agregar fragmento del total con formato científico
        ob_start();
        echo '<span class="scientific-cart-total">' . wc_scientific_cart_format_price($cart_data['totals']['total']) . '</span>';
        $fragments['.scientific-cart-total'] = ob_get_clean();
        
        return $fragments;
    }
    
    /**
     * Agregar mejoras al contenido del carrito
     */
    public function add_cart_enhancement_features() {
        // Agregar indicador de carga para actualizaciones AJAX
        echo '<div id="cart-loading-overlay" style="display: none;">';
        echo '<div class="loading-spinner"></div>';
        echo '<p>' . __('Actualizando carrito...', 'wc-scientific-cart') . '</p>';
        echo '</div>';
    }
    
    /**
     * Obtener configuración de colores
     */
    public function get_color_settings() {
        return array(
            'primary' => get_option('wc_scientific_cart_primary_color', '#2196F3'),
            'secondary' => get_option('wc_scientific_cart_secondary_color', '#4FC3F7'),
            'accent' => get_option('wc_scientific_cart_accent_color', '#1976D2'),
            'light' => get_option('wc_scientific_cart_light_color', '#E3F2FD')
        );
    }
    
    /**
     * Actualizar configuración de colores
     */
    public function update_color_settings($colors) {
        $updated = true;
        
        foreach ($colors as $key => $color) {
            $option_name = 'wc_scientific_cart_' . $key . '_color';
            $updated = $updated && update_option($option_name, sanitize_hex_color($color));
        }
        
        return $updated;
    }
}
?>
