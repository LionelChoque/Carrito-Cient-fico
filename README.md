# Plugin WooCommerce Carrito Científico

## 📁 Estructura de Archivos

Crea la siguiente estructura de carpetas en `/wp-content/plugins/wc-scientific-cart/`:

```
wc-scientific-cart/
├── wc-scientific-cart.php (archivo principal del plugin)
├── README.md
├── assets/
│   ├── css/
│   │   └── scientific-cart.css
│   └── js/
│       └── scientific-cart.js
├── includes/
│   ├── class-erp-integration.php
│   └── class-cart-customizer.php
└── languages/
    └── (archivos de traducción)
```

## 🚀 Instalación

### Paso 1: Crear los archivos

1. **Archivo principal** (`wc-scientific-cart.php`): Ya creado en el primer artefacto
2. **JavaScript** (`assets/js/scientific-cart.js`): Ya creado en el segundo artefacto

### Paso 2: Crear archivos CSS

**Archivo: `assets/css/scientific-cart.css`**

```css
/* Estilos base ya incluidos en el PHP principal */
/* Este archivo puede estar vacío o contener estilos adicionales */

.wc-scientific-cart-loading {
    opacity: 0.6;
    pointer-events: none;
}

.scientific-cart-animation {
    animation: fadeInUp 0.5s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
```

### Paso 3: Crear clases auxiliares

**Archivo: `includes/class-erp-integration.php`**

```php
<?php
/**
 * Clase de integración con ERP
 * (El código principal ya está incluido en el archivo principal)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Esta clase ya está definida en el archivo principal
// Este archivo se puede usar para extensiones futuras de la integración ERP
```

**Archivo: `includes/class-cart-customizer.php`**

```php
<?php
/**
 * Clase de personalización del carrito
 * (El código principal ya está incluido en el archivo principal)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Esta clase ya está definida en el archivo principal
// Este archivo se puede usar para personalizaciones adicionales
```

## ⚙️ Configuración

### 1. Activar el Plugin

1. Sube la carpeta `wc-scientific-cart` a `/wp-content/plugins/`
2. Ve a **WordPress Admin → Plugins**
3. Activa "WooCommerce Carrito Científico"

### 2. Configurar ERP (Opcional)

1. Ve a **WooCommerce → Carrito Científico**
2. Introduce la URL de tu ERP
3. Introduce tu API Key
4. Guarda la configuración

### 3. Configurar Campo CUIT

Agrega este código a `functions.php` de tu tema para crear el campo CUIT:

```php
// Agregar campo CUIT al registro y perfil de usuario
add_action('show_user_profile', 'add_cuit_field');
add_action('edit_user_profile', 'add_cuit_field');
add_action('user_new_form', 'add_cuit_field');

function add_cuit_field($user) {
    $cuit = get_user_meta($user->ID, 'cuit', true);
    ?>
    <h3>Información Empresarial</h3>
    <table class="form-table">
        <tr>
            <th><label for="cuit">CUIT</label></th>
            <td>
                <input type="text" name="cuit" id="cuit" value="<?php echo esc_attr($cuit); ?>" class="regular-text" />
                <br /><span class="description">Ingrese el CUIT de la empresa</span>
            </td>
        </tr>
    </table>
    <?php
}

// Guardar campo CUIT
add_action('personal_options_update', 'save_cuit_field');
add_action('edit_user_profile_update', 'save_cuit_field');
add_action('user_register', 'save_cuit_field');

function save_cuit_field($user_id) {
    if (current_user_can('edit_user', $user_id)) {
        update_user_meta($user_id, 'cuit', sanitize_text_field($_POST['cuit']));
    }
}
```

## 🔧 Personalización

### Colores y Estilo

Los colores están basados en tu diseño web:
- **Azul Principal**: `#2196F3`
- **Azul Oscuro**: `#1976D2` 
- **Azul Claro**: `#4FC3F7`
- **Fondo**: `#E3F2FD`

### Modificar Textos

Para cambiar los textos, edita las funciones `_e()` y `__()` en el archivo principal.

## 🔌 Integración con ERP

### Formato de Datos Enviados

El plugin envía datos al ERP en este formato JSON:

```json
{
    "type": "quote_request",
    "data": {
        "customer_name": "Nombre del Cliente",
        "customer_email": "email@cliente.com",
        "company_name": "Empresa SRL",
        "cuit": "20-12345678-9",
        "phone": "+5411234567",
        "products": [
            {
                "id": 123,
                "name": "Producto Analítico",
                "sku": "PROD-001",
                "quantity": 2,
                "price": 150.00,
                "total": 300.00
            }
        ],
        "cart_total": 300.00,
        "timestamp": "2025-07-02 14:30:00"
    }
}
```

### Endpoint del ERP

Tu ERP debe proporcionar un endpoint que:
1. Acepte peticiones POST
2. Requiera autenticación via Bearer token
3. Procese los datos del presupuesto
4. Responda con código 200 para éxito

Ejemplo de endpoint en tu ERP:
```
POST https://tu-erp.com/api/quotes
Authorization: Bearer TU_API_KEY
Content-Type: application/json
```

## 🛡️ Compatibilidad HPOS

El plugin es totalmente compatible con **High-Performance Order Storage (HPOS)** de WooCommerce, siguiendo las mejores prácticas de 2025.

## 📱 Responsive Design

El diseño es completamente responsive y se adapta a dispositivos móviles.

## 🐛 Debugging

Para activar logs de debug, agrega a `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Los logs se guardarán en `/wp-content/debug.log`

## 🔄 Actualizaciones

Para actualizar el plugin:
1. Desactiva el plugin actual
2. Reemplaza los archivos
3. Reactiva el plugin

**Nota**: Las configuraciones y datos de presupuestos se mantienen intactos.

## 📧 Fallback por Email

Si no configuras un ERP, el plugin enviará las solicitudes por email al administrador del sitio.

## 🎨 Iconografía

El plugin utiliza iconos SVG personalizados que combinan con la estética científica de tu sitio web.

## ⚡ Rendimiento

- **Compatible con HPOS**: Optimizado para alto rendimiento
- **AJAX**: Sin recargas de página
- **CSS optimizado**: Estilos inline para mejor rendimiento
- **JavaScript minificado**: Código optimizado para carga rápida

---

¡Tu plugin está listo para usar! 🧪⚗️
