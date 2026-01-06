<?php
/**
 * Admin Class
 * Handles admin-specific functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Dashboard_Pro_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Dashboard Pro', 'wp-dashboard-pro'),
            __('Dashboard Pro', 'wp-dashboard-pro'),
            'manage_options',
            'wp-dashboard-pro',
            array($this, 'render_admin_page'),
            'dashicons-chart-area',
            30
        );
        
        add_submenu_page(
            'wp-dashboard-pro',
            __('Settings', 'wp-dashboard-pro'),
            __('Settings', 'wp-dashboard-pro'),
            'manage_options',
            'wp-dashboard-pro-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <div class="card">
                <h2><?php _e('Welcome to Dashboard Pro', 'wp-dashboard-pro'); ?></h2>
                <p><?php _e('Your professional dashboard and affiliate management system is ready to use.', 'wp-dashboard-pro'); ?></p>
                
                <h3><?php _e('Quick Links', 'wp-dashboard-pro'); ?></h3>
                <ul>
                    <li><a href="<?php echo admin_url('admin.php?page=wp-dashboard-pro-settings'); ?>"><?php _e('Configure Settings', 'wp-dashboard-pro'); ?></a></li>
                    <li><a href="<?php echo admin_url('users.php?role=affiliate'); ?>"><?php _e('Manage Affiliates', 'wp-dashboard-pro'); ?></a></li>
                </ul>
                
                <h3><?php _e('API Information', 'wp-dashboard-pro'); ?></h3>
                <p><strong><?php _e('API Base URL:', 'wp-dashboard-pro'); ?></strong> <code><?php echo rest_url('dashboard-pro/v1'); ?></code></p>
                <p><?php _e('Use this URL in your React dashboard to connect to the WordPress backend.', 'wp-dashboard-pro'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Settings page is handled by class-admin-settings.php
        do_action('wp_dashboard_pro_render_settings');
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'wp-dashboard-pro') === false) {
            return;
        }
        
        wp_enqueue_style('wp-dashboard-pro-admin', WP_DASHBOARD_PRO_PLUGIN_URL . 'assets/css/admin.css', array(), WP_DASHBOARD_PRO_VERSION);
    }
}
