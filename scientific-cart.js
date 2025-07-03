/**
 * JavaScript para el plugin WooCommerce Carrito Científico
 * Versión modular compatible con la nueva estructura del plugin
 * 
 * @package WC_Scientific_Cart
 * @version 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Clase principal del plugin JavaScript
     */
    class WCScientificCart {
        constructor() {
            this.isProcessing = false;
            this.settings = window.wc_scientific_cart_ajax || {};
            this.debug = this.settings.debug_mode || false;
            
            this.init();
        }

        /**
         * Inicializar el plugin
         */
        init() {
            if (this.debug) {
                console.log('🧪 WC Scientific Cart: Initializing...');
            }

            // Verificar dependencias
            if (!this.checkDependencies()) {
                return;
            }

            // Inicializar componentes
            this.initEventHandlers();
            this.initUIComponents();
            this.initValidation();
            this.initAnimations();

            // Hook para desarrolladores
            $(document).trigger('wc_scientific_cart_initialized', [this]);

            if (this.debug) {
                console.log('🧪 WC Scientific Cart: Initialization complete');
            }
        }

        /**
         * Verificar dependencias necesarias
         */
        checkDependencies() {
            if (typeof jQuery === 'undefined') {
                console.error('WC Scientific Cart: jQuery not found');
                return false;
            }

            if (!this.settings.ajax_url) {
                console.error('WC Scientific Cart: AJAX URL not configured');
                return false;
            }

            if (!this.settings.nonce) {
                console.error('WC Scientific Cart: Security nonce not found');
                return false;
            }

            return true;
        }

        /**
         * Inicializar manejadores de eventos
         */
        initEventHandlers() {
            // Botón principal de solicitar presupuesto
            $(document).on('click', '#request-quote-btn', this.handleQuoteRequest.bind(this));

            // Actualización automática del carrito
            this.initCartAutoUpdate();

            // Confirmación para eliminar productos
            this.initRemoveConfirmation();

            // Validación en tiempo real
            this.initRealTimeValidation();

            // Manejo de errores AJAX globales
            this.initErrorHandling();
        }

        /**
         * Manejar solicitud de presupuesto
         */
        async handleQuoteRequest(e) {
            e.preventDefault();

            if (this.isProcessing) {
                return;
            }

            const $button = $(e.currentTarget);

            try {
                // Validar antes de enviar
                await this.validateQuoteRequest();

                // Preparar UI
                this.setLoadingState($button, true);
                this.isProcessing = true;

                // Realizar petición
                const response = await this.sendQuoteRequest();

                // Manejar respuesta exitosa
                this.handleQuoteSuccess(response, $button);

            } catch (error) {
                // Manejar errores
                this.handleQuoteError(error, $button);
            } finally {
                // Limpiar estado
                this.setLoadingState($button, false);
                this.isProcessing = false;
            }
        }

        /**
         * Validar solicitud de presupuesto
         */
        async validateQuoteRequest() {
            // Verificar que hay productos en el carrito
            if ($('.woocommerce-cart-form .cart_item').length === 0) {
                throw new Error(this.settings.cart_empty || 'El carrito está vacío');
            }

            // Validar usuario logueado
            if (!$('body').hasClass('logged-in')) {
                throw new Error(this.settings.login_required || 'Debes iniciar sesión');
            }

            // Validación AJAX adicional
            try {
                const validation = await this.ajaxRequest('wc_scientific_cart_validate_cart');
                if (!validation.success) {
                    throw new Error(validation.data.message);
                }
            } catch (error) {
                if (this.debug) {
                    console.warn('Validation AJAX failed, proceeding anyway:', error);
                }
            }

            return true;
        }

        /**
         * Enviar solicitud de presupuesto
         */
        async sendQuoteRequest() {
            return await this.ajaxRequest('wc_scientific_cart_request_quote');
        }

        /**
         * Manejar respuesta exitosa
         */
        handleQuoteSuccess(response, $button) {
            // Mostrar mensaje de éxito
            this.showMessage(response.data.message, 'success');

            // Animación de éxito en el botón
            this.animateButtonSuccess($button);

            // Limpiar carrito si está configurado
            if (response.data.clear_cart) {
                this.clearCart();
            }

            // Redireccionar si está configurado
            if (response.data.redirect) {
                setTimeout(() => {
                    window.location.href = response.data.redirect;
                }, 2000);
            }

            // Hook para desarrolladores
            $(document).trigger('wc_scientific_cart_quote_success', [response.data]);

            // Analytics/tracking
            this.trackEvent('quote_requested', {
                quote_id: response.data.quote_id,
                success: true
            });
        }

        /**
         * Manejar errores
         */
        handleQuoteError(error, $button) {
            const message = error.message || this.settings.quote_error || 'Error desconocido';
            
            // Mostrar mensaje de error
            this.showMessage(message, 'error');

            // Animación de error en el botón
            this.animateButtonError($button);

            // Log del error
            if (this.debug) {
                console.error('WC Scientific Cart: Quote request failed', error);
            }

            // Hook para desarrolladores
            $(document).trigger('wc_scientific_cart_quote_error', [error]);

            // Analytics/tracking
            this.trackEvent('quote_requested', {
                success: false,
                error: message
            });
        }

        /**
         * Realizar petición AJAX
         */
        async ajaxRequest(action, data = {}) {
            const requestData = {
                action: action,
                nonce: this.settings.nonce,
                ...data
            };

            return new Promise((resolve, reject) => {
                $.ajax({
                    url: this.settings.ajax_url,
                    type: 'POST',
                    data: requestData,
                    timeout: 30000,
                    success: function(response) {
                        if (response.success) {
                            resolve(response);
                        } else {
                            reject(new Error(response.data?.message || 'Error en la respuesta del servidor'));
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        const errorMsg = textStatus === 'timeout' 
                            ? 'Tiempo de espera agotado'
                            : `Error de conexión: ${errorThrown}`;
                        reject(new Error(errorMsg));
                    }
                });
            });
        }

        /**
         * Mostrar mensaje al usuario
         */
        showMessage(message, type) {
            // Remover mensajes existentes
            $('.quote-message').remove();

            const messageClass = type === 'success' ? 'quote-success-message' : 'quote-error-message';
            const messageIcon = type === 'success' ? '✓' : '⚠';

            const $message = $(`<div class="quote-message ${messageClass}">`)
                .html(`<strong>${messageIcon}</strong> ${message}`);

            // Insertar el mensaje
            $('.scientific-quote-section').after($message);

            // Scroll suave hacia el mensaje
            this.scrollToElement($message);

            // Auto-remove success messages
            if (type === 'success') {
                setTimeout(() => {
                    $message.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        }

        /**
         * Establecer estado de carga del botón
         */
        setLoadingState($button, loading) {
            if (loading) {
                $button.prop('disabled', true);
                $button.data('original-html', $button.html());
                $button.html(`
                    <span class="btn-icon">
                        <svg class="animate-spin" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10 3V6M10 14V17M17 10H14M6 10H3M15.364 4.636L13.95 6.05M6.05 13.95L4.636 15.364M15.364 15.364L13.95 13.95M6.05 6.05L4.636 4.636" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>
                    ${this.settings.requesting_quote || 'Procesando...'}
                `);
            } else {
                $button.prop('disabled', false);
                const originalHtml = $button.data('original-html');
                if (originalHtml) {
                    setTimeout(() => {
                        $button.html(originalHtml);
                    }, 1000);
                }
            }
        }

        /**
         * Animar éxito del botón
         */
        animateButtonSuccess($button) {
            $button.addClass('success-animation');
            $button.html('<span class="btn-icon">✓</span>¡Enviado!');

            setTimeout(() => {
                $button.removeClass('success-animation');
            }, 2000);
        }

        /**
         * Animar error del botón
         */
        animateButtonError($button) {
            $button.addClass('error-animation');
            
            setTimeout(() => {
                $button.removeClass('error-animation');
            }, 1000);
        }

        /**
         * Inicializar componentes de UI
         */
        initUIComponents() {
            // Tooltips
            this.initTooltips();

            // Efectos hover en productos
            this.initProductHoverEffects();

            // Expandir/colapsar detalles
            this.initExpandableDetails();

            // Color pickers (si están disponibles)
            if (typeof $.fn.wpColorPicker === 'function') {
                $('.color-picker').wpColorPicker();
            }
        }

        /**
         * Inicializar tooltips
         */
        initTooltips() {
            // Agregar tooltips a elementos importantes
            $('.scientific-quote-btn').attr('title', 'Solicita un presupuesto personalizado con precios preferenciales');
            $('.scientific-badge').each(function() {
                $(this).attr('title', $(this).text());
            });

            // Implementar tooltips simples
            $('[title]').hover(
                function(e) {
                    const title = $(this).attr('title');
                    if (!title) return;
                    
                    $(this).data('title', title).removeAttr('title');
                    $('<div class="wc-scientific-tooltip">' + title + '</div>')
                        .appendTo('body')
                        .fadeIn(200);
                },
                function() {
                    $(this).attr('title', $(this).data('title'));
                    $('.wc-scientific-tooltip').remove();
                }
            ).mousemove(function(e) {
                $('.wc-scientific-tooltip').css({
                    top: e.pageY + 10,
                    left: e.pageX + 10
                });
            });
        }

        /**
         * Inicializar efectos hover en productos
         */
        initProductHoverEffects() {
            $('.woocommerce-cart-form .cart_item').hover(
                function() {
                    $(this).addClass('hover-effect');
                },
                function() {
                    $(this).removeClass('hover-effect');
                }
            );
        }

        /**
         * Inicializar detalles expandibles
         */
        initExpandableDetails() {
            $(document).on('click', '.product-scientific-details summary', function() {
                $(this).parent().toggleClass('expanded');
            });
        }

        /**
         * Inicializar actualización automática del carrito
         */
        initCartAutoUpdate() {
            let updateTimeout;

            $(document).on('change', '.cart .qty', function() {
                clearTimeout(updateTimeout);
                
                const $form = $('.woocommerce-cart-form');
                const $updateButton = $form.find('[name="update_cart"]');

                // Mostrar indicador de carga
                this.showCartUpdating();

                // Delay para evitar múltiples actualizaciones
                updateTimeout = setTimeout(() => {
                    $updateButton.prop('disabled', false).trigger('click');
                }, 800);
            });

            // Limpiar overlay cuando se complete la actualización
            $(document).on('updated_cart_totals', () => {
                this.hideCartUpdating();
            });
        }

        /**
         * Mostrar indicador de actualización del carrito
         */
        showCartUpdating() {
            const $form = $('.woocommerce-cart-form');
            if ($form.find('.cart-updating-overlay').length === 0) {
                $form.append(`
                    <div class="cart-updating-overlay">
                        <div class="updating-spinner"></div>
                        <p>Actualizando carrito...</p>
                    </div>
                `);
            }
        }

        /**
         * Ocultar indicador de actualización del carrito
         */
        hideCartUpdating() {
            $('.cart-updating-overlay').fadeOut(300, function() {
                $(this).remove();
            });
        }

        /**
         * Inicializar confirmación para eliminar productos
         */
        initRemoveConfirmation() {
            $(document).on('click', '.product-remove a', function(e) {
                const confirmMessage = this.settings.confirm_remove || '¿Estás seguro de que quieres remover este producto?';
                if (!confirm(confirmMessage)) {
                    e.preventDefault();
                    return false;
                }
            });
        }

        /**
         * Inicializar validación en tiempo real
         */
        initRealTimeValidation() {
            // Validar CUIT si está presente
            $(document).on('input', 'input[name="cuit"]', function() {
                const cuit = $(this).val().replace(/[^0-9]/g, '');
                const formatted = this.formatCUIT(cuit);
                $(this).val(formatted);
                
                if (cuit.length === 11) {
                    this.validateCUIT(cuit);
                }
            }.bind(this));

            // Validar email
            $(document).on('blur', 'input[type="email"]', function() {
                this.validateEmail($(this));
            }.bind(this));
        }

        /**
         * Formatear CUIT
         */
        formatCUIT(cuit) {
            if (cuit.length <= 2) return cuit;
            if (cuit.length <= 10) return cuit.substring(0, 2) + '-' + cuit.substring(2);
            return cuit.substring(0, 2) + '-' + cuit.substring(2, 10) + '-' + cuit.substring(10, 11);
        }

        /**
         * Validar CUIT
         */
        validateCUIT(cuit) {
            // Algoritmo de validación CUIT argentino
            const multiplicadores = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
            let suma = 0;

            for (let i = 0; i < 10; i++) {
                suma += parseInt(cuit[i]) * multiplicadores[i];
            }

            const resto = suma % 11;
            const digitoVerificador = resto < 2 ? resto : 11 - resto;
            const isValid = digitoVerificador == parseInt(cuit[10]);

            // Mostrar feedback visual
            const $field = $('input[name="cuit"]');
            $field.toggleClass('valid', isValid).toggleClass('invalid', !isValid);

            return isValid;
        }

        /**
         * Validar email
         */
        validateEmail($field) {
            const email = $field.val();
            const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            
            $field.toggleClass('valid', isValid).toggleClass('invalid', !isValid && email !== '');
            
            return isValid;
        }

        /**
         * Inicializar manejo de errores
         */
        initErrorHandling() {
            // Manejar errores AJAX globales
            $(document).ajaxError((event, jqXHR, ajaxSettings, thrownError) => {
                if (ajaxSettings.url === this.settings.ajax_url) {
                    if (this.debug) {
                        console.error('WC Scientific Cart AJAX Error:', {
                            status: jqXHR.status,
                            error: thrownError,
                            response: jqXHR.responseText
                        });
                    }
                }
            });
        }

        /**
         * Inicializar animaciones
         */
        initAnimations() {
            // Animación de entrada para elementos
            this.animateOnScroll();

            // Animación para la sección de presupuesto
            setTimeout(() => {
                $('.scientific-quote-section').addClass('animated fadeInUp');
            }, 300);
        }

        /**
         * Animar elementos al hacer scroll
         */
        animateOnScroll() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        $(entry.target).addClass('animate-in');
                    }
                });
            }, { threshold: 0.1 });

            $('.scientific-info-section, .scientific-quote-section').each(function() {
                observer.observe(this);
            });
        }

        /**
         * Scroll suave hacia elemento
         */
        scrollToElement($element, offset = 20) {
            if ($element.length) {
                $('html, body').animate({
                    scrollTop: $element.offset().top - offset
                }, 500);
            }
        }

        /**
         * Limpiar carrito
         */
        clearCart() {
            // Implementar si es necesario
            if (this.debug) {
                console.log('Clear cart functionality not implemented');
            }
        }

        /**
         * Tracking de eventos
         */
        trackEvent(eventName, data = {}) {
            // Google Analytics
            if (typeof gtag !== 'undefined') {
                gtag('event', eventName, {
                    event_category: 'WC Scientific Cart',
                    ...data
                });
            }

            // Google Tag Manager
            if (typeof dataLayer !== 'undefined') {
                dataLayer.push({
                    event: 'wc_scientific_cart_' + eventName,
                    ...data
                });
            }

            // Hook para desarrolladores
            $(document).trigger('wc_scientific_cart_track', [eventName, data]);
        }

        /**
         * Actualizar fragmentos del carrito
         */
        updateCartFragments() {
            if (typeof wc_cart_fragments_params !== 'undefined') {
                // Trigger WooCommerce cart fragments update
                $(document.body).trigger('wc_fragment_refresh');
            }
        }

        /**
         * Obtener resumen del carrito
         */
        async getCartSummary() {
            try {
                const response = await this.ajaxRequest('wc_scientific_cart_get_cart_summary');
                return response.data;
            } catch (error) {
                if (this.debug) {
                    console.warn('Failed to get cart summary:', error);
                }
                return null;
            }
        }

        /**
         * Destruir instancia
         */
        destroy() {
            // Remover event listeners
            $(document).off('.wc-scientific-cart');
            
            // Limpiar timers
            if (this.updateTimeout) {
                clearTimeout(this.updateTimeout);
            }

            // Hook para desarrolladores
            $(document).trigger('wc_scientific_cart_destroyed', [this]);
        }
    }

    /**
     * Plugin utilities
     */
    const WCScientificCartUtils = {
        /**
         * Formatear precio
         */
        formatPrice(price, currency = '') {
            // Usar la función de WooCommerce si está disponible
            if (typeof accounting !== 'undefined' && window.wc_cart_params) {
                return accounting.formatMoney(price, {
                    symbol: currency || window.wc_cart_params.currency_format_symbol,
                    format: window.wc_cart_params.currency_format,
                    thousand: window.wc_cart_params.currency_format_thousand_sep,
                    decimal: window.wc_cart_params.currency_format_decimal_sep,
                    precision: window.wc_cart_params.currency_format_num_decimals
                });
            }

            // Fallback básico
            return currency + parseFloat(price).toFixed(2);
        },

        /**
         * Debounce function
         */
        debounce(func, wait, immediate = false) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    timeout = null;
                    if (!immediate) func.apply(this, args);
                };
                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(this, args);
            };
        },

        /**
         * Throttle function
         */
        throttle(func, limit) {
            let inThrottle;
            return function(...args) {
                if (!inThrottle) {
                    func.apply(this, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        }
    };

    /**
     * Inicialización cuando el DOM esté listo
     */
    $(document).ready(function() {
        // Verificar si estamos en la página del carrito
        if (!$('body').hasClass('woocommerce-cart')) {
            return;
        }

        // Inicializar plugin
        window.WCScientificCart = new WCScientificCart();
        window.WCScientificCartUtils = WCScientificCartUtils;

        // Exponer para otros scripts
        $.fn.wcScientificCart = function(options) {
            return this.each(function() {
                if (!$(this).data('wc-scientific-cart')) {
                    $(this).data('wc-scientific-cart', new WCScientificCart(options));
                }
            });
        };
    });

    /**
     * Re-inicializar después de actualizaciones AJAX del carrito
     */
    $(document.body).on('updated_wc_div', function() {
        if (window.WCScientificCart && typeof window.WCScientificCart.initUIComponents === 'function') {
            window.WCScientificCart.initUIComponents();
        }
    });

})(jQuery);
