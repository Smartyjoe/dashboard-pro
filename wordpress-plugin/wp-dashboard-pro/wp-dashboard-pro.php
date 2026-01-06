<?php
/**
 * Plugin Name: WP Dashboard Pro
 * Plugin URI: https://yourdomain.com/wp-dashboard-pro
 * Description: Professional white-label dashboard and affiliate management system for WooCommerce. Modular, customizable, and production-ready.
 * Version: 1.0.0
 * Author: smatatech Technologies
 * Author URI: https://smatatech.com.ng
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wp-dashboard-pro
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.5
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Plugin version
define('WP_DASHBOARD_PRO_VERSION', '1.0.0');
define('WP_DASHBOARD_PRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_DASHBOARD_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_DASHBOARD_PRO_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Core plugin class
 */
class WP_Dashboard_Pro {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Settings instance
     */
    public $settings;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        // Core classes
        require_once WP_DASHBOARD_PRO_PLUGIN_DIR . 'includes/class-settings.php';
        require_once WP_DASHBOARD_PRO_PLUGIN_DIR . 'includes/class-installer.php';
        require_once WP_DASHBOARD_PRO_PLUGIN_DIR . 'includes/class-auth.php';
        
        // API classes
        require_once WP_DASHBOARD_PRO_PLUGIN_DIR . 'includes/api/class-api-base.php';
        require_once WP_DASHBOARD_PRO_PLUGIN_DIR . 'includes/api/class-auth-api.php';
        require_once WP_DASHBOARD_PRO_PLUGIN_DIR . 'includes/api/class-dashboard-api.php';
        require_once WP_DASHBOARD_PRO_PLUGIN_DIR . 'includes/api/class-users-api.php';
        require_once WP_DASHBOARD_PRO_PLUGIN_DIR . 'includes/api/class-settings-api.php';
        require_once WP_DASHBOARD_PRO_PLUGIN_DIR . 'includes/api/class-products-api.php';
        require_once WP_DASHBOARD_PRO_PLUGIN_DIR . 'includes/api/class-orders-api.php';
        
        // Admin classes
        require_once WP_DASHBOARD_PRO_PLUGIN_DIR . 'includes/admin/class-admin.php';
        require_once WP_DASHBOARD_PRO_PLUGIN_DIR . 'includes/admin/class-admin-settings.php';
        
        // Load modular features based on settings
        $this->settings = new WP_Dashboard_Pro_Settings();
        $this->load_modules();
    }
    
    /**
     * Load enabled modules
     */
    private function load_modules() {
        // Analytics module (core - always loaded)
        require_once WP_DASHBOARD_PRO_PLUGIN_DIR . 'includes/modules/analytics/class-analytics.php';
        
        // Affiliate module (optional)
        if ($this->settings->is_module_enabled('affiliate')) {
            require_once WP_DASHBOARD_PRO_PLUGIN_DIR . 'includes/modules/affiliate/class-affiliate.php';
            require_once WP_DASHBOARD_PRO_PLUGIN_DIR . 'includes/modules/affiliate/class-referral-tracker.php';
            require_once WP_DASHBOARD_PRO_PLUGIN_DIR . 'includes/modules/affiliate/class-tier-system.php';
            require_once WP_DASHBOARD_PRO_PLUGIN_DIR . 'includes/api/class-affiliate-api.php';
        }
        
        // Commission module (optional)
        if ($this->settings->is_module_enabled('commission')) {
            require_once WP_DASHBOARD_PRO_PLUGIN_DIR . 'includes/modules/commission/class-commission.php';
            require_once WP_DASHBOARD_PRO_PLUGIN_DIR . 'includes/modules/commission/class-commission-calculator.php';
            require_once WP_DASHBOARD_PRO_PLUGIN_DIR . 'includes/api/class-commission-api.php';
        }
        
        // Withdrawal module (optional)
        if ($this->settings->is_module_enabled('withdrawal')) {
            require_once WP_DASHBOARD_PRO_PLUGIN_DIR . 'includes/modules/withdrawal/class-withdrawal.php';
            require_once WP_DASHBOARD_PRO_PLUGIN_DIR . 'includes/api/class-withdrawal-api.php';
        }
        
        // Email notifications (optional)
        if ($this->settings->is_module_enabled('email_notifications')) {
            require_once WP_DASHBOARD_PRO_PLUGIN_DIR . 'includes/modules/email/class-email-manager.php';
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array('WP_Dashboard_Pro_Installer', 'activate'));
        register_deactivation_hook(__FILE__, array('WP_Dashboard_Pro_Installer', 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'init'));
        add_action('rest_api_init', array($this, 'register_api_routes'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('wp-dashboard-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize components
        if (is_admin()) {
            new WP_Dashboard_Pro_Admin();
            new WP_Dashboard_Pro_Admin_Settings();
        }
        
        // Initialize modules
        $this->init_modules();
    }
    
    /**
     * Initialize enabled modules
     */
    private function init_modules() {
        // Analytics (core)
        WP_Dashboard_Pro_Analytics::get_instance();
        
        // Affiliate system
        if ($this->settings->is_module_enabled('affiliate')) {
            WP_Dashboard_Pro_Affiliate::get_instance();
            WP_Dashboard_Pro_Referral_Tracker::get_instance();
            WP_Dashboard_Pro_Tier_System::get_instance();
        }
        
        // Commission system
        if ($this->settings->is_module_enabled('commission')) {
            WP_Dashboard_Pro_Commission::get_instance();
        }
        
        // Withdrawal system
        if ($this->settings->is_module_enabled('withdrawal')) {
            WP_Dashboard_Pro_Withdrawal::get_instance();
        }
        
        // Email notifications
        if ($this->settings->is_module_enabled('email_notifications')) {
            WP_Dashboard_Pro_Email_Manager::get_instance();
        }
    }
    
    /**
     * Register REST API routes
     */
    public function register_api_routes() {
        // Auth routes (always available)
        $auth_api = new WP_Dashboard_Pro_Auth_API();
        $auth_api->register_routes();
        
        // Dashboard/Analytics routes (core)
        $dashboard_api = new WP_Dashboard_Pro_Dashboard_API();
        $dashboard_api->register_routes();
        
        // Users routes (core)
        $users_api = new WP_Dashboard_Pro_Users_API();
        $users_api->register_routes();
        
        // Settings routes (core)
        $settings_api = new WP_Dashboard_Pro_Settings_API();
        $settings_api->register_routes();
        
        // Products routes (core - WooCommerce integration)
        if (class_exists('WooCommerce')) {
            $products_api = new WP_Dashboard_Pro_Products_API();
            $products_api->register_routes();
            
            $orders_api = new WP_Dashboard_Pro_Orders_API();
            $orders_api->register_routes();
        }
        
        // Module-specific routes
        if ($this->settings->is_module_enabled('affiliate')) {
            $affiliate_api = new WP_Dashboard_Pro_Affiliate_API();
            $affiliate_api->register_routes();
        }
        
        if ($this->settings->is_module_enabled('commission')) {
            $commission_api = new WP_Dashboard_Pro_Commission_API();
            $commission_api->register_routes();
        }
        
        if ($this->settings->is_module_enabled('withdrawal')) {
            $withdrawal_api = new WP_Dashboard_Pro_Withdrawal_API();
            $withdrawal_api->register_routes();
        }
    }
}

/**
 * Initialize the plugin
 */
function wp_dashboard_pro() {
    return WP_Dashboard_Pro::get_instance();
}

// Start the plugin
wp_dashboard_pro();
