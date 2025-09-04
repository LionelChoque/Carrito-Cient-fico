
<?php if (!defined('ABSPATH')) exit; ?>

<h3><?php _e('Información Científica', 'wc-scientific-cart'); ?></h3>
<table class="form-table">
    <tr>
        <th><label for="cuit"><?php _e('CUIT', 'wc-scientific-cart'); ?></label></th>
        <td>
            <input type="text" name="cuit" id="cuit" value="<?php echo esc_attr($fields_data['cuit']); ?>" class="regular-text" pattern="[0-9\-]*" />
            <br><span class="description"><?php _e('Formato: 20-12345678-9', 'wc-scientific-cart'); ?></span>
        </td>
    </tr>
    <tr>
        <th><label for="industry_type"><?php _e('Tipo de Industria', 'wc-scientific-cart'); ?></label></th>
        <td>
            <select name="industry_type" id="industry_type">
                <?php foreach (wc_scientific_cart_get_industry_options() as $value => $label): ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($fields_data['industry_type'], $value); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <th><label for="lab_size"><?php _e('Tamaño del Laboratorio', 'wc-scientific-cart'); ?></label></th>
        <td>
            <select name="lab_size" id="lab_size">
                <?php foreach (wc_scientific_cart_get_lab_size_options() as $value => $label): ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($fields_data['lab_size'], $value); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <th><label for="annual_budget"><?php _e('Presupuesto Anual Estimado', 'wc-scientific-cart'); ?></label></th>
        <td>
            <select name="annual_budget" id="annual_budget">
                <?php foreach (wc_scientific_cart_get_budget_options() as $value => $label): ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($fields_data['annual_budget'], $value); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
</table>

