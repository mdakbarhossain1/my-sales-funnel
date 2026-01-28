<?php
class MSF_Ajax_Handler {
    
    public static function init() {
        add_action('wp_ajax_msf_get_variation_price', array(__CLASS__, 'get_variation_price'));
        add_action('wp_ajax_nopriv_msf_get_variation_price', array(__CLASS__, 'get_variation_price'));
        add_action('wp_ajax_msf_update_cart', array(__CLASS__, 'update_cart'));
        add_action('wp_ajax_nopriv_msf_update_cart', array(__CLASS__, 'update_cart'));
        add_action('wp_ajax_msf_get_cart_totals', array(__CLASS__, 'get_cart_totals'));
        add_action('wp_ajax_nopriv_msf_get_cart_totals', array(__CLASS__, 'get_cart_totals'));
    }
    
    public static function get_variation_price() {
        check_ajax_referer('msf_nonce', 'nonce');
        
        $product_id = intval($_POST['product_id']);
        
        // Parse variation data properly
        $variation_data = array();
        if (isset($_POST['variation_data']) && is_array($_POST['variation_data'])) {
            $variation_data = $_POST['variation_data'];
        }
        
        $variation_info = MSF_Product_Handler::get_selected_variation_data($product_id, $variation_data);
        
        if ($variation_info) {
            wp_send_json_success($variation_info);
        } else {
            wp_send_json_error(array('message' => 'Variation not available'));
        }
    }
    
    public static function update_cart() {
        check_ajax_referer('msf_nonce', 'nonce');
        
        $product_id = intval($_POST['product_id']);
        $variation_id = isset($_POST['variation_id']) ? intval($_POST['variation_id']) : 0;
        $quantity = intval($_POST['quantity']);
        
        // Parse variation data for validation
        $variation_data = array();
        if (isset($_POST['variation_data']) && is_array($_POST['variation_data'])) {
            $variation_data = $_POST['variation_data'];
        }
        
        // Empty cart first
        WC()->cart->empty_cart();
        
        // Add product to cart
        if ($variation_id > 0) {
            $added = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation_data);
        } else {
            $added = WC()->cart->add_to_cart($product_id, $quantity);
        }
        
        if ($added) {
            // Calculate shipping to ensure it's available
            WC()->cart->calculate_shipping();
            WC()->cart->calculate_totals();
            
            wp_send_json_success(array(
                'success' => true,
                'subtotal' => WC()->cart->get_cart_subtotal(),
                'shipping' => WC()->cart->get_cart_shipping_total(),
                'tax' => WC()->cart->get_total_tax(),
                'total' => WC()->cart->get_total(),
                'cart_contents_count' => WC()->cart->get_cart_contents_count()
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Failed to add to cart. Please try again.'
            ));
        }
    }
    
    public static function get_cart_totals() {
        check_ajax_referer('msf_nonce', 'nonce');
        
        // Recalculate totals
        WC()->cart->calculate_shipping();
        WC()->cart->calculate_totals();
        
        wp_send_json_success(array(
            'subtotal' => WC()->cart->get_cart_subtotal(),
            'shipping' => WC()->cart->get_cart_shipping_total(),
            'tax' => WC()->cart->get_total_tax(),
            'total' => WC()->cart->get_total(),
            'cart_contents_count' => WC()->cart->get_cart_contents_count()
        ));
    }
}