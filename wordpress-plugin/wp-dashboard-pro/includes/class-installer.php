<?php
/**
 * Plugin Installer
 * Handles plugin activation, deactivation, and database setup
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Dashboard_Pro_Installer {
    
    /**
     * Plugin activation
     */
    public static function activate() {
        global $wpdb;
        
        // Check requirements
        self::check_requirements();
        
        // Create database tables
        self::create_tables();
        
        // Set default options
        self::set_default_options();
        
        // Create affiliate role
        self::create_affiliate_role();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation flag
        set_transient('wp_dashboard_pro_activated', true, 60);
    }
    
    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Clear scheduled events
        wp_clear_scheduled_hook('wp_dashboard_pro_daily_cleanup');
        wp_clear_scheduled_hook('wp_dashboard_pro_process_commissions');
    }
    
    /**
     * Check plugin requirements
     */
    private static function check_requirements() {
        $errors = array();
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $errors[] = 'PHP 7.4 or higher is required.';
        }
        
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.8', '<')) {
            $errors[] = 'WordPress 5.8 or higher is required.';
        }
        
        // Check if WooCommerce is active (optional but recommended)
        if (!class_exists('WooCommerce')) {
            // Just a notice, not a blocking error
            set_transient('wp_dashboard_pro_woocommerce_notice', true, 300);
        }
        
        if (!empty($errors)) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(implode('<br>', $errors));
        }
    }
    
    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Affiliates table
        $table_affiliates = $wpdb->prefix . 'dashboard_pro_affiliates';
        $sql_affiliates = "CREATE TABLE IF NOT EXISTS $table_affiliates (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            affiliate_code varchar(50) NOT NULL UNIQUE,
            parent_affiliate_id bigint(20) UNSIGNED DEFAULT NULL,
            commission_rate decimal(5,2) DEFAULT 10.00,
            tier2_commission_rate decimal(5,2) DEFAULT 5.00,
            status varchar(20) DEFAULT 'pending',
            payment_email varchar(100),
            payment_method varchar(50),
            payment_details text,
            total_referrals int(11) DEFAULT 0,
            total_sales decimal(10,2) DEFAULT 0.00,
            total_commissions decimal(10,2) DEFAULT 0.00,
            pending_commissions decimal(10,2) DEFAULT 0.00,
            paid_commissions decimal(10,2) DEFAULT 0.00,
            total_sub_affiliates int(11) DEFAULT 0,
            notes text,
            approved_by bigint(20) UNSIGNED,
            approved_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY affiliate_code (affiliate_code),
            KEY parent_affiliate_id (parent_affiliate_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_affiliates);
        
        // Referrals table
        $table_referrals = $wpdb->prefix . 'dashboard_pro_referrals';
        $sql_referrals = "CREATE TABLE IF NOT EXISTS $table_referrals (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            affiliate_id bigint(20) UNSIGNED NOT NULL,
            visitor_id varchar(100),
            ip_address varchar(45),
            user_agent text,
            referrer_url text,
            landing_page text,
            converted tinyint(1) DEFAULT 0,
            conversion_date datetime,
            order_id bigint(20) UNSIGNED,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY visitor_id (visitor_id),
            KEY converted (converted),
            KEY created_at (created_at)
        ) $charset_collate;";
        dbDelta($sql_referrals);
        
        // Commissions table
        $table_commissions = $wpdb->prefix . 'dashboard_pro_commissions';
        $sql_commissions = "CREATE TABLE IF NOT EXISTS $table_commissions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            affiliate_id bigint(20) UNSIGNED NOT NULL,
            parent_affiliate_id bigint(20) UNSIGNED DEFAULT NULL,
            referral_id bigint(20) UNSIGNED,
            order_id bigint(20) UNSIGNED,
            amount decimal(10,2) NOT NULL,
            rate decimal(5,2),
            order_total decimal(10,2),
            tier int(1) DEFAULT 1,
            status varchar(20) DEFAULT 'pending',
            type varchar(20) DEFAULT 'sale',
            description text,
            notes text,
            paid_date datetime,
            due_date datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY parent_affiliate_id (parent_affiliate_id),
            KEY order_id (order_id),
            KEY tier (tier),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        dbDelta($sql_commissions);
        
        // Withdrawals table
        $table_withdrawals = $wpdb->prefix . 'dashboard_pro_withdrawals';
        $sql_withdrawals = "CREATE TABLE IF NOT EXISTS $table_withdrawals (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            affiliate_id bigint(20) UNSIGNED NOT NULL,
            amount decimal(10,2) NOT NULL,
            method varchar(50),
            status varchar(20) DEFAULT 'pending',
            payment_details text,
            transaction_id varchar(100),
            notes text,
            requested_at datetime DEFAULT CURRENT_TIMESTAMP,
            processed_at datetime,
            processed_by bigint(20) UNSIGNED,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY status (status),
            KEY requested_at (requested_at)
        ) $charset_collate;";
        dbDelta($sql_withdrawals);
        
        // Analytics table (for tracking dashboard visits, clicks, etc.)
        $table_analytics = $wpdb->prefix . 'dashboard_pro_analytics';
        $sql_analytics = "CREATE TABLE IF NOT EXISTS $table_analytics (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED,
            event_type varchar(50) NOT NULL,
            event_data text,
            ip_address varchar(45),
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        dbDelta($sql_analytics);
        
        // Activity log table
        $table_activity = $wpdb->prefix . 'dashboard_pro_activity';
        $sql_activity = "CREATE TABLE IF NOT EXISTS $table_activity (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            action varchar(100) NOT NULL,
            object_type varchar(50),
            object_id bigint(20) UNSIGNED,
            description text,
            metadata text,
            ip_address varchar(45),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY action (action),
            KEY created_at (created_at)
        ) $charset_collate;";
        dbDelta($sql_activity);
        
        // Update database version
        update_option('wp_dashboard_pro_db_version', WP_DASHBOARD_PRO_VERSION);
    }
    
    /**
     * Set default options
     */
    private static function set_default_options() {
        // Initialize settings with defaults
        $settings = new WP_Dashboard_Pro_Settings();
        
        // Set site-specific defaults
        $site_url = get_site_url();
        $admin_email = get_option('admin_email');
        $site_name = get_option('blogname');
        
        $settings->update_multiple(array(
            'brand_name' => $site_name,
            'email_from_name' => $site_name,
            'email_from_address' => $admin_email,
            'dashboard_url' => $site_url,
            'allowed_origins' => array($site_url),
        ));
    }
    
    /**
     * Create affiliate role
     */
    private static function create_affiliate_role() {
        // Remove role if exists
        remove_role('affiliate');
        
        // Add affiliate role with basic capabilities
        add_role(
            'affiliate',
            __('Affiliate', 'wp-dashboard-pro'),
            array(
                'read' => true,
                'view_affiliate_dashboard' => true,
                'manage_affiliate_links' => true,
                'request_withdrawal' => true,
            )
        );
        
        // Add custom capabilities to admin and shop manager
        $admin = get_role('administrator');
        $shop_manager = get_role('shop_manager');
        
        $capabilities = array(
            'manage_affiliates',
            'approve_affiliates',
            'manage_commissions',
            'process_withdrawals',
            'view_analytics',
            'manage_dashboard_settings',
        );
        
        foreach ($capabilities as $cap) {
            if ($admin) {
                $admin->add_cap($cap);
            }
            if ($shop_manager) {
                $shop_manager->add_cap($cap);
            }
        }
    }
    
    /**
     * Check and update database if needed
     */
    public static function maybe_update_db() {
        $current_version = get_option('wp_dashboard_pro_db_version', '0');
        
        if (version_compare($current_version, WP_DASHBOARD_PRO_VERSION, '<')) {
            self::create_tables();
            update_option('wp_dashboard_pro_db_version', WP_DASHBOARD_PRO_VERSION);
        }
    }
}
