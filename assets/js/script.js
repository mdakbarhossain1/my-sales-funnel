jQuery(document).ready(function($) {
    
    // Product variations selection
    $('.msf-option').on('click', function() {
        var $this = $(this);
        var attribute = $this.data('attribute');
        var value = $this.data('value');
        
        // Update active state
        $this.siblings().removeClass('active');
        $this.addClass('active');
        
        // Update hidden input
        $this.closest('.msf-variation-group').find('.msf-variation-input').val(value);
        
        // Get variation data
        updateVariationPrice();
    });
    
    // Initialize first variation as selected
    if ($('.msf-options:first .msf-option:first').length) {
        $('.msf-options:first .msf-option:first').trigger('click');
    }
    
    // Quantity change
    $('.msf-quantity-input').on('change input', function() {
        addToCart();
    });
    
    // Add to cart button click
    $('.msf-add-to-cart-btn').on('click', function() {
        addToCart();
    });

    // Update variation price
    function updateVariationPrice() {
        var productId = $('.msf-sales-funnel').data('product-id');
        var variationData = {};
        
        $('.msf-variation-input').each(function() {
            var attr = $(this).attr('name');
            var val = $(this).val();
            if (val) {
                variationData[attr] = val;
            }
        });
        
        // If all variation attributes are selected
        var $variationWrapper = $('.msf-variations-wrapper');
        var expectedAttributes = $variationWrapper.find('.msf-variation-group').length;
        var selectedAttributes = Object.keys(variationData).length;
        
        if (selectedAttributes === expectedAttributes) {
            $.ajax({
                url: msf_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'msf_get_variation_price',
                    nonce: msf_ajax.nonce,
                    product_id: productId,
                    variation_data: variationData
                },
                success: function(response) {
                    if (response.success) {
                        // Store variation ID in data attribute
                        $('.msf-product-price').data('variation-id', response.data.variation_id);
                        
                        // Update price display
                        $('.msf-product-price').html(response.data.display_price);
                        
                        // Update selected variation price display
                        $('.msf-selected-variation-price').html(response.data.display_price).show();
                        
                        // Update cart
                        addToCart();
                        updateOrderSummary();
                    } else {
                        $('.msf-selected-variation-price').hide();
                    }
                },
                error: function() {
                    $('.msf-selected-variation-price').hide();
                }
            });
        }
    }
    
    // Add to cart function
    function addToCart() {
        var productId = $('.msf-sales-funnel').data('product-id');
        var quantity = $('.msf-quantity-input').val();
        var variationData = {};
        var variationId = 0;
        
        // Get selected variations
        $('.msf-variation-input').each(function() {
            var attr = $(this).attr('name');
            var val = $(this).val();
            if (val) {
                variationData[attr] = val;
            }
        });
        
        // For variable products, check if all attributes are selected
        var $variationWrapper = $('.msf-variations-wrapper');
        if ($variationWrapper.length) {
            var expectedAttributes = $variationWrapper.find('.msf-variation-group').length;
            var selectedAttributes = Object.keys(variationData).length;
            
            if (selectedAttributes < expectedAttributes) {
                showMessage('Please select all product options', 'error');
                return;
            }
            
            // Get variation ID from stored data or via AJAX
            variationId = $('.msf-product-price').data('variation-id') || 0;
            
            if (!variationId && Object.keys(variationData).length > 0) {
                // Get variation ID via AJAX
                $.ajax({
                    url: msf_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'msf_get_variation_price',
                        nonce: msf_ajax.nonce,
                        product_id: productId,
                        variation_data: variationData
                    },
                    success: function(response) {
                        if (response.success && response.data.variation_id) {
                            variationId = response.data.variation_id;
                            $('.msf-product-price').data('variation-id', variationId);
                            // Now update cart with the variation
                            updateCart(productId, variationId, quantity, variationData, true);
                        }
                    }
                });
                return; // Exit and wait for AJAX response
            }
        }
        
        // Update cart with product
        updateCart(productId, variationId, quantity, variationData, true);
    }
    
    // Update cart totals
    function updateCart(productId, variationId, quantity, variationData, showMessageFlag = false) {
        $.ajax({
            url: msf_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'msf_update_cart',
                nonce: msf_ajax.nonce,
                product_id: productId,
                variation_id: variationId,
                variation_data: variationData,
                quantity: quantity
            },
            success: function(response) {
                if (response.success) {
                    // Update all price displays
                    $('.msf-subtotal').html(response.data.subtotal);
                    $('.msf-shipping').html(response.data.shipping);
                    $('.msf-tax').html(response.data.tax);
                    $('.msf-total').html(response.data.total);
                    
                    // Update order total in submit button
                    $('.msf-order-total-amount').html('Complete Order - ' + response.data.total);
                    
                    if (showMessageFlag) {
                        showMessage('✓ Product added to cart!', 'success');
                    }
                    
                    // Update order summary
                    updateOrderSummary();
                } else {
                    if (showMessageFlag) {
                        showMessage(response.data.message || 'Failed to add to cart', 'error');
                    }
                }
            },
            error: function() {
                if (showMessageFlag) {
                    showMessage('An error occurred. Please try again.', 'error');
                }
            }
        });
    }
    
    // Update order summary display
    function updateOrderSummary() {
        var productName = $('.msf-product-title').text();
        var quantity = $('.msf-quantity-input').val();
        
        // Get selected variations
        var variations = [];
        $('.msf-option.active').each(function() {
            variations.push($(this).data('value'));
        });
        
        var variationText = variations.length > 0 ? ' (' + variations.join(', ') + ')' : '';
        
        // Get price
        var priceElement = $('.msf-selected-variation-price:visible').length ? 
                          $('.msf-selected-variation-price') : 
                          $('.msf-product-price .price');
        
        var price = priceElement.text() || $('.msf-product-price').text();
        
        var summaryHtml = `
            <div class="msf-order-line-item">
                <span>${productName}${variationText} × ${quantity}</span>
                <span>${price}</span>
            </div>
        `;
        
        $('.msf-order-contents').html(summaryHtml);
    }
    
    // Show message
    function showMessage(message, type) {
        var $message = $('.msf-message');
        $message.removeClass('success error').addClass(type).text(message).show();
        
        // Scroll to message
        $('html, body').animate({
            scrollTop: $message.offset().top - 100
        }, 500);
        
        setTimeout(function() {
            $message.fadeOut();
        }, 3000);
    }
    
    // Validate email
    function isValidEmail(email) {
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Validate phone
    function isValidPhone(phone) {
        var re = /^[\+]?[1-9][\d]{0,15}$/;
        return re.test(phone.replace(/[\s\-\(\)]/g, ''));
    }
    
    // Process checkout
    $('#msf-checkout-form').on('submit', function(e) {
        e.preventDefault();
        
        // Validate terms and conditions
        if (!$('input[name="terms"]').is(':checked')) {
            showMessage('Please agree to the terms and conditions', 'error');
            return;
        }
        
        // Validate payment method
        if (!$('input[name="payment_method"]').is(':checked')) {
            showMessage('Please select a payment method', 'error');
            return;
        }
        
        // Validate required fields
        var errors = [];
        var formData = {};
        
        // Get all form fields
        $(this).find('[name]').each(function() {
            var $field = $(this);
            var name = $field.attr('name');
            var value = $field.val();
            var isRequired = $field.prop('required');
            
            formData[name] = value;
            
            if (isRequired && !value.trim()) {
                var fieldLabel = $field.closest('.msf-form-group').find('label').text().replace('*', '').trim();
                errors.push(fieldLabel + ' is required');
                $field.addClass('error');
            } else {
                $field.removeClass('error');
            }
            
            // Email validation
            if (name === 'billing_email' && value.trim()) {
                if (!isValidEmail(value)) {
                    errors.push('Please enter a valid email address');
                    $field.addClass('error');
                }
            }
            
            // Phone validation
            if (name === 'billing_phone' && value.trim()) {
                if (!isValidPhone(value)) {
                    errors.push('Please enter a valid phone number');
                    $field.addClass('error');
                }
            }
        });
        
        // Show errors if any
        if (errors.length > 0) {
            showMessage(errors[0], 'error');
            return;
        }
        
        // Get product data
        var productId = $('.msf-sales-funnel').data('product-id');
        var quantity = $('.msf-quantity-input').val();
        var variationData = {};
        var variationId = $('.msf-product-price').data('variation-id') || 0;
        
        // Get variation data
        $('.msf-variation-input').each(function() {
            var attr = $(this).attr('name');
            var val = $(this).val();
            if (val) {
                variationData[attr] = val;
            }
        });
        
        // Prepare checkout data
        var checkoutData = {
            action: 'msf_process_checkout',
            nonce: msf_ajax.nonce,
            product_id: productId,
            variation_id: variationId,
            quantity: quantity,
            variation_data: variationData
        };
        
        // Add form data
        $.extend(checkoutData, formData);
        
        // Process checkout
        processCheckout(checkoutData);
    });
    
    // Process checkout function
    function processCheckout(data) {
        $.ajax({
            url: msf_ajax.ajax_url,
            type: 'POST',
            data: data,
            beforeSend: function() {
                $('.msf-submit-order').prop('disabled', true).html('<span>Processing Order...</span>');
                $('.msf-message').hide();
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $('.msf-message').removeClass('error').addClass('success')
                        .html('<strong>Success!</strong> ' + response.data.message).show();
                    
                    // Show order confirmation
                    $('.msf-checkout-form').html(`
                        <div class="msf-order-confirmation">
                            <div class="msf-confirmation-icon">✓</div>
                            <h3>Order Confirmed!</h3>
                            <p class="msf-confirmation-text">Thank you for your purchase!</p>
                            
                            <div class="msf-order-details">
                                <div class="msf-order-detail">
                                    <span class="msf-detail-label">Order Number:</span>
                                    <span class="msf-detail-value">#${response.data.order_id || '0000'}</span>
                                </div>
                                <div class="msf-order-detail">
                                    <span class="msf-detail-label">Total Amount:</span>
                                    <span class="msf-detail-value">${response.data.total || $('.msf-total').text()}</span>
                                </div>
                                <div class="msf-order-detail">
                                    <span class="msf-detail-label">Payment Method:</span>
                                    <span class="msf-detail-value">${$('input[name="payment_method"]:checked').next('label').text()}</span>
                                </div>
                            </div>
                            
                            <p class="msf-confirmation-note">
                                You will receive an order confirmation email shortly.
                                Please check your spam folder if you don't see it in your inbox.
                            </p>
                            
                            <div class="msf-confirmation-actions">
                                <a href="${window.location.href}" class="msf-btn-primary">Continue Shopping</a>
                                <a href="${msf_ajax.ajax_url.replace('admin-ajax.php', 'my-account/orders/')}" class="msf-btn-secondary">View Orders</a>
                            </div>
                        </div>
                    `);
                    
                    // Scroll to confirmation
                    $('html, body').animate({
                        scrollTop: $('.msf-order-confirmation').offset().top - 100
                    }, 500);
                    
                    // Redirect if specified
                    if (response.data.redirect) {
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 3000);
                    }
                    
                } else {
                    // Show error message
                    $('.msf-message').removeClass('success').addClass('error')
                        .html('<strong>Error:</strong> ' + (response.data.message || 'Something went wrong. Please try again.')).show();
                    
                    $('.msf-submit-order').prop('disabled', false).html('<span>Complete Order</span>');
                    
                    // Scroll to error
                    $('html, body').animate({
                        scrollTop: $('.msf-message').offset().top - 100
                    }, 500);
                }
            },
            error: function(xhr, status, error) {
                // Show error message
                $('.msf-message').removeClass('success').addClass('error')
                    .html('<strong>Error:</strong> An error occurred while processing your order. Please try again.').show();
                
                $('.msf-submit-order').prop('disabled', false).html('<span>Complete Order</span>');
                
                // Scroll to error
                $('html, body').animate({
                    scrollTop: $('.msf-message').offset().top - 100
                }, 500);
                
                console.error('Checkout error:', error);
            }
        });
    }
    
    // Update cart totals when address fields change
    $('.msf-country-select, input[name="billing_state"], input[name="billing_postcode"]').on('change', function() {
        // Wait a moment for all fields to be updated
        setTimeout(function() {
            updateCartTotals();
        }, 500);
    });
    
    // Function to update cart totals (for shipping calculation)
    function updateCartTotals() {
        var country = $('.msf-country-select').val();
        var state = $('input[name="billing_state"]').val();
        var postcode = $('input[name="billing_postcode"]').val();
        var city = $('input[name="billing_city"]').val();
        
        if (country && state) {
            $.ajax({
                url: msf_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'msf_get_cart_totals',
                    nonce: msf_ajax.nonce,
                    country: country,
                    state: state,
                    postcode: postcode,
                    city: city
                },
                success: function(response) {
                    if (response.success) {
                        $('.msf-subtotal').html(response.data.subtotal);
                        $('.msf-shipping').html(response.data.shipping);
                        $('.msf-tax').html(response.data.tax);
                        $('.msf-total').html(response.data.total);
                        $('.msf-order-total-amount').html('Complete Order - ' + response.data.total);
                    }
                }
            });
        }
    }
    
    // Initialize cart on page load
    setTimeout(function() {
        addToCart();
    }, 1000);
    
    // Real-time validation for fields
    $('#msf-checkout-form input, #msf-checkout-form select').on('blur', function() {
        var $field = $(this);
        var name = $field.attr('name');
        var value = $field.val();
        
        if ($field.prop('required') && !value.trim()) {
            $field.addClass('error');
        } else {
            $field.removeClass('error');
            
            // Email validation
            if (name === 'billing_email' && value.trim()) {
                if (!isValidEmail(value)) {
                    $field.addClass('error');
                }
            }
            
            // Phone validation
            if (name === 'billing_phone' && value.trim()) {
                if (!isValidPhone(value)) {
                    $field.addClass('error');
                }
            }
        }
    });
    
    // Auto-format phone number
    $('input[name="billing_phone"]').on('input', function() {
        var value = $(this).val().replace(/\D/g, '');
        if (value.length > 0) {
            value = '+' + value;
        }
        $(this).val(value);
    });
});