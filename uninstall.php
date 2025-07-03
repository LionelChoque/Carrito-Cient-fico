<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Eliminar opciones
$options = array(
    'wc_scientific_cart_erp_endpoint',
    'wc_scientific_cart_erp_api_key',
    'wc_scientific_cart_email_notifications',
    'wc_scientific_cart_admin_email',
    'wc_scientific_cart_min_quote_amount',
    'wc_scientific_cart_auto_create_leads',
    'wc_scientific_cart_header_title',
    'wc_scientific_cart_header_subtitle',
    'wc_scientific_cart_button_text',
    'wc_scientific_cart_success_message',
    'wc_scientific_cart_error_message'
);

foreach ($options as $option) {
    delete_option($option);
}

// Eliminar tablas (opcional - comentar si quieres conservar datos)
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}scientific_cart_quotes");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}scientific_cart_crm_leads");
?>
