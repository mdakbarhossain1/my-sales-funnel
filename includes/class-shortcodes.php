<?php
class MSF_Shortcodes {
    
    public static function init() {
        add_shortcode('sales_funnel', array(__CLASS__, 'sales_funnel_shortcode'));
        add_shortcode('msf_product', array(__CLASS__, 'product_shortcode'));
        add_shortcode('msf_checkout', array(__CLASS__, 'checkout_shortcode'));
        add_shortcode('msf_order_summary', array(__CLASS__, 'order_summary_shortcode'));
    }
    
    public static function sales_funnel_shortcode($atts) {
        $atts = shortcode_atts(array(
            'product_id' => '',
            'template' => 'default'
        ), $atts);
        
        ob_start();
        ?>
        <div class="msf-sales-funnel" data-product-id="<?php echo esc_attr($atts['product_id']); ?>">
            <div class="msf-container">
                <!-- Product Section -->
                <div class="msf-product-section">
                    <?php echo do_shortcode('[msf_product product_id="' . $atts['product_id'] . '"]'); ?>
                </div>
                
                <!-- Checkout Section -->
                <div class="msf-checkout-section">
                    <?php echo do_shortcode('[msf_checkout]'); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public static function product_shortcode($atts) {
        $atts = shortcode_atts(array(
            'product_id' => ''
        ), $atts);
        
        if (empty($atts['product_id'])) {
            return '<p>Please specify a product ID</p>';
        }
        
        $product = wc_get_product($atts['product_id']);
        
        if (!$product) {
            return '<p>Product not found</p>';
        }
        
        ob_start();
        ?>
        <div class="msf-product-display">
            <h2 class="msf-product-title"><?php echo $product->get_name(); ?></h2>
            
            <div class="msf-product-image">
                <?php echo $product->get_image('large'); ?>
            </div>
            
            <div class="msf-product-description">
                <?php echo wpautop($product->get_description()); ?>
            </div>
            
            <div class="msf-product-price">
                <?php echo $product->get_price_html(); ?>
                <div class="msf-selected-variation-price" style="display: none;"></div>
            </div>
            
            <div class="msf-variations-container">
                <?php 
                // Use our custom variations shortcode instead of the action hook
                echo do_shortcode('[msf_product_variations product_id="' . $atts['product_id'] . '"]'); 
                ?>
            </div>
            
            <div class="msf-quantity">
                <label>Quantity:</label>
                <input type="number" name="quantity" value="1" min="1" class="msf-quantity-input">
            </div>
            
            <div class="msf-add-to-cart">
                <button type="button" class="msf-add-to-cart-btn">Add to Cart</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public static function checkout_shortcode() {
        // REMOVED the condition - always show checkout form
        ob_start();
        ?>
        <div class="msf-checkout-form">
            <h3>Complete Your Order</h3>
            
            <!-- Order Summary Display -->
            <div class="msf-order-summary-display">
                <?php echo do_shortcode('[msf_order_summary]'); ?>
            </div>
            
            <!-- Customer Information Form -->
            <form id="msf-checkout-form">
                <div class="msf-form-section">
                    <h4>Customer Information</h4>
                    
                    <div class="msf-form-row">
                        <div class="msf-form-group">
                            <label>First Name *</label>
                            <input type="text" name="billing_first_name" required>
                        </div>
                        <div class="msf-form-group">
                            <label>Last Name *</label>
                            <input type="text" name="billing_last_name" required>
                        </div>
                    </div>
                    
                    <div class="msf-form-row">
                        <div class="msf-form-group">
                            <label>Email Address *</label>
                            <input type="email" name="billing_email" required>
                        </div>
                        <div class="msf-form-group">
                            <label>Phone Number *</label>
                            <input type="tel" name="billing_phone" required>
                        </div>
                    </div>
                </div>
                
                <div class="msf-form-section">
                    <h4>Shipping Address</h4>
                    
                    <div class="msf-form-group">
                        <label>Address Line 1 *</label>
                        <input type="text" name="billing_address_1" required>
                    </div>
                    
                    <div class="msf-form-group">
                        <label>City *</label>
                        <input type="text" name="billing_city" required>
                    </div>
                    
                    <div class="msf-form-row">
                        <div class="msf-form-group">
                            <label>Country *</label>
                            <select name="billing_country" class="msf-country-select" required>
                                <option value="">Select Country</option>
                                <?php 
                                $countries = WC()->countries->get_countries();
                                foreach ($countries as $code => $name) { 
                                ?>
                                    <option value="<?php echo esc_attr($code); ?>">
                                        <?php echo esc_html($name); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        
                        <div class="msf-form-group">
                            <label>State/Province *</label>
                            <input type="text" name="billing_state" required>
                        </div>
                    </div>
                    
                    <div class="msf-form-row">
                        <div class="msf-form-group">
                            <label>Postal/ZIP Code *</label>
                            <input type="text" name="billing_postcode" required>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Method -->
                <div class="msf-form-section">
                    <h4>Payment Method</h4>
                    <div class="msf-payment-methods">
                        <?php if (WC()->payment_gateways->get_available_payment_gateways()) : ?>
                            <?php foreach (WC()->payment_gateways->get_available_payment_gateways() as $gateway) : ?>
                                <div class="msf-payment-method">
                                    <input type="radio" 
                                           name="payment_method" 
                                           value="<?php echo esc_attr($gateway->id); ?>" 
                                           id="payment_<?php echo esc_attr($gateway->id); ?>"
                                           <?php echo $gateway->id === 'cod' ? 'checked' : ''; ?>>
                                    <label for="payment_<?php echo esc_attr($gateway->id); ?>">
                                        <?php echo esc_html($gateway->get_title()); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p>No payment methods available.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Terms and Conditions -->
                <div class="msf-form-section">
                    <div class="msf-form-group">
                        <label class="msf-checkbox-label">
                            <input type="checkbox" name="terms" required>
                            I agree to the <a href="<?php echo wc_get_page_permalink('terms'); ?>" target="_blank">terms and conditions</a> *
                        </label>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="msf-form-submit">
                    <button type="submit" class="msf-submit-order">
                        <span class="msf-order-total-amount">Complete Order - <?php echo WC()->cart->get_total(); ?></span>
                    </button>
                </div>
                
                <!-- Messages -->
                <div class="msf-message"></div>
                
                <!-- Security Notice -->
                <div class="msf-security-notice">
                    <p><i class="dashicons dashicons-lock"></i> Your payment information is secure and encrypted.</p>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public static function order_summary_shortcode() {
        ob_start();
        ?>
        <div class="msf-order-summary">
            <h4>Order Summary</h4>
            <div class="msf-order-items">
                <div class="msf-order-item">
                    <span class="msf-item-name">Product</span>
                    <span class="msf-item-price">Price</span>
                </div>
                <div class="msf-order-contents">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
            
            <div class="msf-order-totals">
                <div class="msf-total-row">
                    <span>Subtotal:</span>
                    <span class="msf-subtotal"><?php echo WC()->cart->get_cart_subtotal(); ?></span>
                </div>
                <div class="msf-total-row">
                    <span>Shipping:</span>
                    <span class="msf-shipping"><?php echo WC()->cart->get_cart_shipping_total(); ?></span>
                </div>
                <div class="msf-total-row">
                    <span>Tax:</span>
                    <span class="msf-tax"><?php echo WC()->cart->get_total_tax(); ?></span>
                </div>
                <div class="msf-total-row msf-grand-total">
                    <span>Total:</span>
                    <span class="msf-total"><?php echo WC()->cart->get_total(); ?></span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}