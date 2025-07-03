?>
<?php if (!defined('ABSPATH')) exit; ?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _e('Confirmación de Solicitud de Presupuesto', 'wc-scientific-cart'); ?></title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #E3F2FD 0%, #B3E5FC 100%); padding: 30px; border-radius: 12px; text-align: center; margin-bottom: 30px;">
        <h1 style="color: #1976D2; margin: 0;">¡Solicitud Recibida!</h1>
        <p style="margin: 10px 0 0 0; font-size: 16px;"><?php echo esc_html($site_name); ?></p>
    </div>
    
    <div style="background: white; padding: 30px; border-radius: 8px; border: 1px solid #E0E0E0;">
        <p>Estimado/a <strong><?php echo esc_html($user->display_name); ?></strong>,</p>
        
        <p>Hemos recibido tu solicitud de presupuesto para productos científicos. Nuestro equipo de especialistas la revisará y se pondrá en contacto contigo dentro de las próximas <strong>24 horas</strong>.</p>
        
        <div style="background: #F8F9FA; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2196F3;">
            <h3 style="color: #1976D2; margin-top: 0;">Resumen de tu solicitud:</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; border-bottom: 1px solid #E0E0E0;"><strong>Cliente:</strong></td>
                    <td style="padding: 8px 0; border-bottom: 1px solid #E0E0E0;"><?php echo esc_html($quote_data['customer_name']); ?></td>
                </tr>
                <?php if (!empty($quote_data['company_name'])): ?>
                <tr>
                    <td style="padding: 8px 0; border-bottom: 1px solid #E0E0E0;"><strong>Empresa:</strong></td>
                    <td style="padding: 8px 0; border-bottom: 1px solid #E0E0E0;"><?php echo esc_html($quote_data['company_name']); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td style="padding: 8px 0; border-bottom: 1px solid #E0E0E0;"><strong>Total estimado:</strong></td>
                    <td style="padding: 8px 0; border-bottom: 1px solid #E0E0E0;"><?php echo wc_scientific_cart_format_price($quote_data['cart_total']); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0;"><strong>Productos solicitados:</strong></td>
                    <td style="padding: 8px 0;"><?php echo count($quote_data['products']); ?></td>
                </tr>
            </table>
        </div>
        
        <h4 style="color: #1976D2;">En breve recibirás una propuesta personalizada con:</h4>
        <ul style="margin-left: 20px;">
            <li>Precios preferenciales</li>
            <li>Condiciones de pago flexibles</li>
            <li>Asesoramiento técnico especializado</li>
            <li>Garantía de calidad certificada</li>
            <li>Soporte post-venta</li>
        </ul>
        
        <div style="background: #E8F5E8; padding: 15px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #4CAF50;">
            <p style="margin: 0;"><strong>💡 Mientras tanto:</strong> Puedes seguir navegando nuestro catálogo en <a href="<?php echo esc_url($site_url); ?>" style="color: #2196F3;"><?php echo esc_html($site_name); ?></a></p>
        </div>
        
        <p style="margin-top: 30px;">
            Atentamente,<br>
            <strong>Equipo de Soluciones Analíticas</strong><br>
            <?php echo esc_html($site_name); ?>
        </p>
    </div>
    
    <div style="text-align: center; margin-top: 30px; color: #666; font-size: 12px;">
        <p>Este email fue generado automáticamente, por favor no respondas a esta dirección.</p>
        <p>Si tienes preguntas, contacta con nosotros a través de nuestro sitio web.</p>
    </div>
</body>
</html>

<?php
