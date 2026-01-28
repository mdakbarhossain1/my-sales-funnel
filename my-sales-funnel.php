<?php
/**
 * Plugin Name: My Sales Funnel
 * Plugin URI: https://yourwebsite.com
 * Description: Single-page sales funnel with product selection and checkout
 * Version: 1.0.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MSF_VERSION', '1.0.0');
define('MSF_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MSF_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once MSF_PLUGIN_PATH . 'includes/class-product-handler.php';
require_once MSF_PLUGIN_PATH . 'includes/class-checkout-handler.php';
require_once MSF_PLUGIN_PATH . 'includes/class-shortcodes.php';
require_once MSF_PLUGIN_PATH . 'includes/class-ajax-handler.php';

// Initialize plugin
// Initialize plugin
class MySalesFunnel
{

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->init_hooks();
    }

    private function init_hooks()
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('init', array($this, 'init'));
        add_action('wp_loaded', array($this, 'handle_ajax_requests'));
    }

    public function init()
    {
        // Initialize components
        MSF_Product_Handler::init();
        MSF_Checkout_Handler::init();
        MSF_Shortcodes::init();
        MSF_Ajax_Handler::init();
    }

    public function handle_ajax_requests()
    {
        // Handle non-ajax form submissions
        if (isset($_POST['msf_checkout_submit'])) {
            // Process checkout here
        }
    }

    public function enqueue_scripts()
    {
        global $post;

        // Only enqueue on pages with our shortcode
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'sales_funnel')) {
            wp_enqueue_style('msf-style', MSF_PLUGIN_URL . 'assets/css/style.css', array(), MSF_VERSION);
            wp_enqueue_script('msf-script', MSF_PLUGIN_URL . 'assets/js/script.js', array('jquery'), MSF_VERSION, true);

            wp_localize_script('msf-script', 'msf_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('msf_nonce')
            ));
        }
    }
}


// Initialize plugin
MySalesFunnel::get_instance();