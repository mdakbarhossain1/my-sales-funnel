<?php
class MSF_Product_Handler {
    
    public static function init() {
        add_shortcode('msf_product_variations', array(__CLASS__, 'variations_shortcode'));
        add_filter('woocommerce_add_to_cart_validation', array(__CLASS__, 'validate_variations'), 10, 3);
    }
    
    public static function variations_shortcode($atts) {
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
        
        if ($product->is_type('variable')) {
            echo '<div class="msf-variations-wrapper" data-product-id="' . esc_attr($atts['product_id']) . '">';
            
            // Get available variations
            $variations = $product->get_available_variations();
            $attributes = $product->get_variation_attributes();
            
            foreach ($attributes as $attribute_name => $options) {
                $label = wc_attribute_label($attribute_name);
                ?>
                <div class="msf-variation-group">
                    <label><?php echo esc_html($label); ?>:</label>
                    <div class="msf-options">
                        <?php foreach ($options as $option) { ?>
                            <button type="button" class="msf-option" 
                                    data-attribute="<?php echo esc_attr($attribute_name); ?>"
                                    data-value="<?php echo esc_attr($option); ?>">
                                <?php echo esc_html($option); ?>
                            </button>
                        <?php } ?>
                    </div>
                    <input type="hidden" 
                           name="<?php echo esc_attr($attribute_name); ?>" 
                           value=""
                           class="msf-variation-input">
                </div>
                <?php
            }
            
            echo '</div>';
            
            // Add variation data script
            self::output_variation_data($product);
            
        } else {
            echo '<p>This product has no variations.</p>';
        }
        
        return ob_get_clean();
    }
    
    private static function output_variation_data($product) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var variations = <?php echo json_encode($product->get_available_variations()); ?>;
            var variation_attributes = <?php echo json_encode($product->get_variation_attributes()); ?>;
            
            window.msfProductData = {
                variations: variations,
                attributes: variation_attributes,
                productId: <?php echo $product->get_id(); ?>
            };
        });
        </script>
        <?php
    }
    
    public static function get_selected_variation_data($product_id, $variation_data) {
        $product = wc_get_product($product_id);
        
        if ($product->is_type('variable')) {
            $data_store = WC_Data_Store::load('product');
            $variation_id = $data_store->find_matching_product_variation($product, $variation_data);
            
            if ($variation_id) {
                $variation = wc_get_product($variation_id);
                return array(
                    'variation_id' => $variation_id,
                    'price' => $variation->get_price(),
                    'display_price' => wc_price($variation->get_price()),
                    'stock_quantity' => $variation->get_stock_quantity(),
                    'in_stock' => $variation->is_in_stock(),
                    'variation' => $variation
                );
            }
        }
        
        return false;
    }
}