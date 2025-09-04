/**
 * JavaScript para el plugin WooCommerce Carrito Científico
 * Maneja la funcionalidad AJAX para solicitar presupuestos
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Variables globales
    var isProcessing = false;
    
    /**
     * Manejar click del botón solicitar presupuesto
     */
    $('#request-quote-btn').on('click', function(e) {
        e.preventDefault();
        
        if (isProcessing) {
            return;
        }
        
        var $button = $(this);
        var $buttonText = $button.find('.btn-text');
        var originalText = $button.html();
        
        // Verificar si hay productos en el carrito
        if ($('.woocommerce-cart-form .cart_item').length === 0) {
            showMessage('Tu carrito está vacío. Agrega productos antes de solicitar un presupuesto.', 'error');
            return;
        }
        
        isProcessing = true;
        
        // Actualizar estado del botón
        $button.prop('disabled', true);
        $button.html('<span class="btn-icon"><svg class="animate-spin" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 3V6M10 14V17M17 10H14M6 10H3M15.364 4.636L13.95 6.05M6.05 13.95L4.636 15.364M15.364 15.364L13.95 13.95M6.05 6.05L4.636 4.636" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>' + wc_scientific_cart_ajax.requesting_quote);
        
        // Realizar petición AJAX
        $.ajax({
            url: wc_scientific_cart_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wc_scientific_cart_request_quote',
                nonce: wc_scientific_cart_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    
                    // Opcionalmente limpiar el carrito después de enviar la solicitud
                    // window.location.href = wc_add_to_cart_params.cart_url;
                    
                    // Animar éxito
                    animateSuccess($button);
                    
                } else {
                    showMessage(response.data.message, 'error');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX Error:', textStatus, errorThrown);
                showMessage(wc_scientific_cart_ajax.quote_error, 'error');
            },
            complete: function() {
                isProcessing = false;
                
                // Restaurar botón después de un delay
                setTimeout(function() {
                    $button.prop('disabled', false);
                    $button.html(originalText);
                }, 2000);
            }
        });
    });
    
    /**
     * Mostrar mensaje al usuario
     */
    function showMessage(message, type) {
        // Remover mensajes existentes
        $('.quote-message').remove();
        
        var messageClass = type === 'success' ? 'quote-success-message' : 'quote-error-message';
        var messageIcon = type === 'success' ? '✓' : '⚠';
        
        var $message = $('<div class="quote-message ' + messageClass + '">')
            .html('<strong>' + messageIcon + '</strong> ' + message);
        
        // Insertar el mensaje
        $('.scientific-quote-section').after($message);
        
        // Scroll suave hacia el mensaje
        $('html, body').animate({
            scrollTop: $message.offset().top - 20
        }, 500);
        
        // Auto-remove success messages after 5 seconds
        if (type === 'success') {
            setTimeout(function() {
                $message.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    }
    
    /**
     * Animación de éxito para el botón
     */
    function animateSuccess($button) {
        $button.addClass('success-animation');
        
        // Efecto de pulso de éxito
        $button.html('<span class="btn-icon">✓</span>¡Enviado!');
        
        setTimeout(function() {
            $button.removeClass('success-animation');
        }, 2000);
    }
    
    /**
     * Validar campos de usuario antes de enviar
     */
    function validateUserData() {
        var errors = [];
        
        // Verificar información del usuario (esto se puede expandir)
        if ($('.customer-info').length === 0) {
            errors.push('Información de usuario no encontrada');
        }
        
        return errors;
    }
    
    /**
     * Efectos visuales adicionales
     */
    
    // Efecto hover para productos en el carrito
    $('.woocommerce-cart-form .cart_item').hover(
        function() {
            $(this).addClass('hover-effect');
        },
        function() {
            $(this).removeClass('hover-effect');
        }
    );
    
    // Animación de entrada para la sección de presupuesto
    function animateQuoteSection() {
        $('.scientific-quote-section').css({
            'opacity': '0',
            'transform': 'translateY(20px)'
        }).animate({
            'opacity': '1'
        }, 500).css('transform', 'translateY(0px)');
    }
    
    // Ejecutar animación si la sección existe
    if ($('.scientific-quote-section').length > 0) {
        setTimeout(animateQuoteSection, 300);
    }
    
    /**
     * Mejorar la experiencia del carrito
     */
    
    // Actualización automática del carrito cuando cambian las cantidades
    $(document).on('change', '.cart .qty', function() {
        var $form = $('.woocommerce-cart-form');
        
        // Mostrar indicador de carga
        $form.append('<div class="cart-updating-overlay"><div class="spinner"></div></div>');
        
        // Simular un pequeño delay para mejor UX
        setTimeout(function() {
            $('[name="update_cart"]').trigger('click');
        }, 500);
    });
    
    // Remover overlay cuando se complete la actualización
    $(document).on('updated_cart_totals', function() {
        $('.cart-updating-overlay').remove();
    });
    
    /**
     * Funcionalidad de confirmación para remover productos
     */
    $(document).on('click', '.remove', function(e) {
        if (!confirm('¿Estás seguro de que quieres remover este producto del carrito?')) {
            e.preventDefault();
            return false;
        }
    });
    
    /**
     * Tooltips informativos
     */
    function initTooltips() {
        // Agregar tooltips a elementos importantes
        $('.scientific-quote-btn').attr('title', 'Solicita un presupuesto personalizado con precios preferenciales');
        $('.product-sku').attr('title', 'Código SKU del producto');
        
        // Implementar tooltips simples
        $('[title]').hover(
            function() {
                var title = $(this).attr('title');
                $(this).data('title', title).removeAttr('title');
                $('<div class="tooltip">' + title + '</div>').appendTo('body').fadeIn(200);
            },
            function() {
                $(this).attr('title', $(this).data('title'));
                $('.tooltip').remove();
            }
        ).mousemove(function(e) {
            $('.tooltip').css({
                'top': e.pageY + 10,
                'left': e.pageX + 10
            });
        });
    }
    
    initTooltips();
    
    /**
     * Función para agregar estilos dinámicos de CSS
     */
    function addDynamicStyles() {
        var styles = `
            <style id="scientific-cart-dynamic-styles">
                .success-animation {
                    background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%) !important;
                    transform: scale(1.05);
                    transition: all 0.3s ease;
                }
                
                .animate-spin {
                    animation: spin 1s linear infinite;
                }
                
                @keyframes spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
                
                .cart-updating-overlay {
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(255, 255, 255, 0.8);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 1000;
                }
                
                .spinner {
                    width: 40px;
                    height: 40px;
                    border: 4px solid #f3f3f3;
                    border-top: 4px solid #2196F3;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                }
                
                .hover-effect {
                    background-color: #F3E5F5 !important;
                    transition: background-color 0.3s ease;
                }
                
                .tooltip {
                    position: absolute;
                    background: #333;
                    color: white;
                    padding: 5px 10px;
                    border-radius: 4px;
                    font-size: 12px;
                    z-index: 1000;
                    max-width: 200px;
                    word-wrap: break-word;
                }
                
                .woocommerce-cart-form {
                    position: relative;
                }
                
                @media (max-width: 768px) {
                    .tooltip {
                        display: none;
                    }
                    
                    .scientific-quote-btn {
                        font-size: 14px;
                        padding: 12px 20px;
                    }
                }
            </style>
        `;
        
        if ($('#scientific-cart-dynamic-styles').length === 0) {
            $('head').append(styles);
        }
    }
    
    addDynamicStyles();
    
    /**
     * Función de inicialización que se ejecuta cuando el DOM está listo
     */
    function initScientificCart() {
        console.log('🧪 Scientific Cart initialized successfully!');
        
        // Agregar clase para identificar que el JS está cargado
        $('body').addClass('scientific-cart-loaded');
        
        // Verificar configuración
        if (typeof wc_scientific_cart_ajax === 'undefined') {
            console.warn('⚠️ Scientific Cart: AJAX configuration not found');
            return;
        }
        
        // Log de debug (solo en desarrollo)
        if (window.location.hostname === 'localhost' || window.location.hostname.includes('dev')) {
            console.log('🔧 Scientific Cart Debug Info:', {
                'AJAX URL': wc_scientific_cart_ajax.ajax_url,
                'Nonce': wc_scientific_cart_ajax.nonce,
                'Cart Items': $('.cart_item').length
            });
        }
    }
    
    // Ejecutar inicialización
    initScientificCart();
    
    /**
     * Manejar actualizaciones del carrito vía AJAX
     */
    $(document.body).on('updated_wc_div', function() {
        // Re-inicializar elementos después de actualizaciones AJAX
        initTooltips();
        
        // Re-aplicar efectos hover
        $('.woocommerce-cart-form .cart_item').hover(
            function() {
                $(this).addClass('hover-effect');
            },
            function() {
                $(this).removeClass('hover-effect');
            }
        );
    });
    
});
