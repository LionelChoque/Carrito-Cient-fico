?>
<?php if (!defined('ABSPATH')) exit; ?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php _e('Nueva Solicitud de Presupuesto', 'wc-scientific-cart'); ?></title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 800px; margin: 0 auto; padding: 20px;">
        <div style="background: #1976D2; color: white; padding: 20px; border-radius: 8px 8px 0 0;">
            <h1 style="margin: 0;">🧪 Nueva Solicitud de Presupuesto</h1>
            <p style="margin: 5px 0 0 0;"><?php echo esc_html($site_name); ?></p>
        </div>
        
        <div style="background: white; padding: 30px; border: 1px solid #E0E0E0; border-top: none; border-radius: 0 0 8px 8px;">
            <h2>Información del Cliente</h2>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
                <tr>
                    <td style="padding: 10px; border: 1px solid #E0E0E0; background: #F8F9FA; font-weight: bold;">Nombre:</td>
                    <td style="padding: 10px; border: 1px solid #E0E0E0;"><?php echo esc_html($quote_data['customer_name']); ?></td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #E0E0E0; background: #F8F9FA; font-weight: bold;">Email:</td>
                    <td style="padding: 10px; border: 1px solid #E0E0E0;"><a href="mailto:<?php echo esc_attr($quote_data['customer_email']); ?>"><?php echo esc_html($quote_data['customer_email']); ?></a></td>
                </tr>
                <?php if (!empty($quote_data['company_name'])): ?>
                <tr>
                    <td style="padding: 10px; border: 1px solid #E0E0E0; background: #F8F9FA; font-weight: bold;">Empresa:</td>
                    <td style="padding: 10px; border: 1px solid #E0E0E0;"><?php echo esc_html($quote_data['company_name']); ?></td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($quote_data['cuit'])): ?>
                <tr>
                    <td style="padding: 10px; border: 1px solid #E0E0E0; background: #F8F9FA; font-weight: bold;">CUIT:</td>
                    <td style="padding: 10px; border: 1px solid #E0E0E0;"><?php echo esc_html($quote_data['cuit']); ?></td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($quote_data['phone'])): ?>
                <tr>
                    <td style="padding: 10px; border: 1px solid #E0E0E0; background: #F8F9FA; font-weight: bold;">Teléfono:</td>
                    <td style="padding: 10px; border: 1px solid #E0E0E0;"><?php echo esc_html($quote_data['phone']); ?></td>
                </tr>
                <?php endif; ?>
            </table>
            
            <h2>Productos Solicitados</h2>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
                <thead>
                    <tr style="background: #1976D2; color: white;">
                        <th style="padding: 12px; text-align: left; border: 1px solid #1976D2;">Producto</th>
                        <th style="padding: 12px; text-align: left; border: 1px solid #1976D2;">SKU</th>
                        <th style="padding: 12px; text-align: center; border: 1px solid #1976D2;">Cantidad</th>
                        <th style="padding: 12px; text-align: right; border: 1px solid #1976D2;">Precio Unit.</th>
                        <th style="padding: 12px; text-align: right; border: 1px solid #1976D2;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($quote_data['products'] as $product): ?>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #E0E0E0;"><?php echo esc_html($product['name']); ?></td>
                        <td style="padding: 10px; border: 1px solid #E0E0E0;"><?php echo esc_html($product['sku']); ?></td>
                        <td style="padding: 10px; border: 1px solid #E0E0E0; text-align: center;"><?php echo esc_html($product['quantity']); ?></td>
                        <td style="padding: 10px; border: 1px solid #E0E0E0; text-align: right;"><?php echo wc_scientific_cart_format_price($product['price']); ?></td>
                        <td style="padding: 10px; border: 1px solid #E0E0E0; text-align: right;"><?php echo wc_scientific_cart_format_price($product['line_total']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #F8F9FA; font-weight: bold;">
                        <td colspan="4" style="padding: 12px; border: 1px solid #E0E0E0; text-align: right;">TOTAL:</td>
                        <td style="padding: 12px; border: 1px solid #E0E0E0; text-align: right; font-size: 18px; color: #1976D2;"><?php echo wc_scientific_cart_format_price($quote_data['cart_total']); ?></td>
                    </tr>
                </tfoot>
            </table>
            
            <div style="background: #E3F2FD; padding: 20px; border-radius: 8px; text-align: center;">
                <h3 style="margin-top: 0; color: #1976D2;">Acciones Rápidas</h3>
                <a href="<?php echo esc_url($admin_url); ?>" style="background: #2196F3; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 5px;">
                    Ver en Admin
                </a>
                <a href="mailto:<?php echo esc_attr($quote_data['customer_email']); ?>" style="background: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 5px;">
                    Responder por Email
                </a>
            </div>
            
            <p style="margin-top: 30px; color: #666; font-size: 12px;">
                Solicitud recibida el <?php echo date('d/m/Y H:i:s'); ?> desde <?php echo esc_html($quote_data['ip_address'] ?? 'IP no disponible'); ?>
            </p>
        </div>
    </div>
</body>
</html>

<?php
