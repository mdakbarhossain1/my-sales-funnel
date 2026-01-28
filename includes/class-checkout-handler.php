<?php
class MSF_Checkout_Handler {
    
    public static function init() {
        add_action('wp_ajax_msf_process_checkout', array(__CLASS__, 'process_checkout'));
        add_action('wp_ajax_nopriv_msf_process_checkout', array(__CLASS__, 'process_checkout'));
        add_action('woocommerce_checkout_fields', array(__CLASS__, 'simplify_checkout_fields'));
        add_action('woocommerce_cart_calculate_fees', array(__CLASS__, 'calculate_shipping_on_checkout'));
    }
    
    public static function simplify_checkout_fields($fields) {
        // Remove unnecessary fields for one-page checkout
        unset($fields['billing']['billing_company']);
        unset($fields['billing']['billing_address_2']);
        
        // Make fields required
        $fields['billing']['billing_first_name']['required'] = true;
        $fields['billing']['billing_last_name']['required'] = true;
        $fields['billing']['billing_email']['required'] = true;
        $fields['billing']['billing_phone']['required'] = true;
        $fields['billing']['billing_address_1']['required'] = true;
        $fields['billing']['billing_city']['required'] = true;
        $fields['billing']['billing_state']['required'] = true;
        $fields['billing']['billing_postcode']['required'] = true;
        $fields['billing']['billing_country']['required'] = true;
        
        return $fields;
    }
    
    public static function calculate_shipping_on_checkout() {
        // Ensure shipping is calculated when customer enters address
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        // If we have customer address data, set it for shipping calculation
        if (isset($_POST['billing_country']) || isset($_POST['shipping_country'])) {
            $country = isset($_POST['billing_country']) ? $_POST['billing_country'] : 
                      (isset($_POST['shipping_country']) ? $_POST['shipping_country'] : null);
            
            $state = isset($_POST['billing_state']) ? $_POST['billing_state'] : 
                    (isset($_POST['shipping_state']) ? $_POST['shipping_state'] : null);
            
            $postcode = isset($_POST['billing_postcode']) ? $_POST['billing_postcode'] : 
                       (isset($_POST['shipping_postcode']) ? $_POST['shipping_postcode'] : null);
            
            $city = isset($_POST['billing_city']) ? $_POST['billing_city'] : 
                   (isset($_POST['shipping_city']) ? $_POST['shipping_city'] : null);
            
            if ($country && $state) {
                // Set customer location for shipping calculation
                WC()->customer->set_shipping_location($country, $state, $postcode, $city);
                WC()->customer->set_billing_location($country, $state, $postcode, $city);
            }
        }
    }
    
    public static function process_checkout() {
        check_ajax_referer('msf_nonce', 'nonce');
        
        // Add product to cart
        WC()->cart->empty_cart();
        
        $product_id = intval($_POST['product_id']);
        $variation_id = isset($_POST['variation_id']) ? intval($_POST['variation_id']) : 0;
        $quantity = intval($_POST['quantity']);
        
        // Get variation data if available
        $variation_data = array();
        if (isset($_POST['variation_data']) && is_array($_POST['variation_data'])) {
            $variation_data = $_POST['variation_data'];
        }
        
        // Add product to cart
        if ($variation_id) {
            $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation_data);
        } else {
            $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity);
        }
        
        if (!$cart_item_key) {
            wp_send_json_error(array(
                'message' => 'Failed to add product to cart.'
            ));
        }
        
        // Set customer address data for shipping calculation
        $country = sanitize_text_field($_POST['billing_country']);
        $state = sanitize_text_field($_POST['billing_state']);
        $postcode = sanitize_text_field($_POST['billing_postcode']);
        $city = sanitize_text_field($_POST['billing_city']);
        $address = sanitize_text_field($_POST['billing_address_1']);
        
        // Set customer location
        WC()->customer->set_billing_location($country, $state, $postcode, $city);
        WC()->customer->set_shipping_location($country, $state, $postcode, $city);
        
        // Set checkout fields
        WC()->customer->set_billing_first_name(sanitize_text_field($_POST['billing_first_name']));
        WC()->customer->set_billing_last_name(sanitize_text_field($_POST['billing_last_name']));
        WC()->customer->set_billing_email(sanitize_text_field($_POST['billing_email']));
        WC()->customer->set_billing_phone(sanitize_text_field($_POST['billing_phone']));
        WC()->customer->set_billing_address_1($address);
        WC()->customer->set_billing_city($city);
        WC()->customer->set_billing_state($state);
        WC()->customer->set_billing_postcode($postcode);
        WC()->customer->set_billing_country($country);
        
        // Copy billing to shipping
        WC()->customer->set_shipping_first_name(sanitize_text_field($_POST['billing_first_name']));
        WC()->customer->set_shipping_last_name(sanitize_text_field($_POST['billing_last_name']));
        WC()->customer->set_shipping_address_1($address);
        WC()->customer->set_shipping_city($city);
        WC()->customer->set_shipping_state($state);
        WC()->customer->set_shipping_postcode($postcode);
        WC()->customer->set_shipping_country($country);
        
        // Calculate shipping and totals
        WC()->cart->calculate_shipping();
        WC()->cart->calculate_totals();
        
        // Process payment
        try {
            // Get payment method
            $payment_method = sanitize_text_field($_POST['payment_method']);
            
            // Create order
            $order = wc_create_order();
            
            // Add products to order
            foreach (WC()->cart->get_cart() as $cart_item) {
                $order->add_product(
                    $cart_item['data'],
                    $cart_item['quantity'],
                    array(
                        'variation' => $cart_item['variation'],
                        'totals' => array(
                            'subtotal' => $cart_item['line_subtotal'],
                            'total' => $cart_item['line_total']
                        )
                    )
                );
            }
            
            // Set addresses
            $order->set_address(array(
                'first_name' => sanitize_text_field($_POST['billing_first_name']),
                'last_name'  => sanitize_text_field($_POST['billing_last_name']),
                'email'      => sanitize_text_field($_POST['billing_email']),
                'phone'      => sanitize_text_field($_POST['billing_phone']),
                'address_1'  => $address,
                'city'       => $city,
                'state'      => $state,
                'postcode'   => $postcode,
                'country'    => $country
            ), 'billing');
            
            $order->set_address(array(
                'first_name' => sanitize_text_field($_POST['billing_first_name']),
                'last_name'  => sanitize_text_field($_POST['billing_last_name']),
                'address_1'  => $address,
                'city'       => $city,
                'state'      => $state,
                'postcode'   => $postcode,
                'country'    => $country
            ), 'shipping');
            
            // Set payment method
            $order->set_payment_method($payment_method);
            
            // Calculate totals
            $order->calculate_totals();
            
            // Save order
            $order_id = $order->save();
            
            // Process payment
            $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
            
            if (isset($available_gateways[$payment_method])) {
                $result = $available_gateways[$payment_method]->process_payment($order_id);
                
                // Clear cart
                WC()->cart->empty_cart();
                
                wp_send_json_success(array(
                    'message' => 'Order placed successfully!',
                    'order_id' => $order_id,
                    'total' => $order->get_formatted_order_total(),
                    'redirect' => $result['redirect']
                ));
            } else {
                throw new Exception('Payment method not available.');
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }
}