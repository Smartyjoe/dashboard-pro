<?php
/**
 * Settings Manager
 * Handles all plugin settings and configurations
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Dashboard_Pro_Settings {
    
    /**
     * Option name for settings
     */
    const OPTION_NAME = 'wp_dashboard_pro_settings';
    
    /**
     * Settings cache
     */
    private $settings;
    
    /**
     * Default settings
     */
    private $defaults = array(
        // General Settings
        'plugin_name' => 'Dashboard Pro',
        'plugin_enabled' => true,
        
        // Branding
        'brand_name' => 'Dashboard Pro',
        'brand_logo_url' => '',
        'brand_favicon_url' => '',
        'brand_primary_color' => '#3b82f6',
        'brand_secondary_color' => '#8b5cf6',
        'brand_accent_color' => '#10b981',
        
        // Module Toggles
        'modules' => array(
            'analytics' => true,          // Core - always enabled
            'user_management' => true,     // Core - always enabled
            'affiliate' => true,
            'commission' => true,
            'withdrawal' => true,
            'email_notifications' => true,
            'woocommerce_tracking' => true,
        ),
        
        // JWT Settings
        'jwt_secret_key' => '',
        'jwt_expiration' => 86400,        // 24 hours
        'jwt_refresh_expiration' => 604800, // 7 days
        
        // Dashboard Settings
        'dashboard_url' => '',
        'allowed_origins' => array(),
        'enable_cors' => true,
        
        // Affiliate Settings
        'affiliate_approval_required' => true,
        'affiliate_default_commission_rate' => 10,
        'affiliate_cookie_duration' => 30, // days
        'affiliate_minimum_withdrawal' => 50,
        'affiliate_currency' => 'USD',
        
        // Commission Settings
        'commission_calculation' => 'percentage', // percentage or fixed
        'commission_apply_to' => 'subtotal',      // subtotal, total, or profit
        'commission_status_trigger' => 'completed', // Order status that triggers commission
        'commission_hold_period' => 0,             // Days to hold commission
        
        // Email Settings
        'email_from_name' => '',
        'email_from_address' => '',
        'email_notifications' => array(
            'affiliate_approved' => true,
            'affiliate_rejected' => true,
            'commission_earned' => true,
            'withdrawal_requested' => true,
            'withdrawal_approved' => true,
            'withdrawal_rejected' => true,
        ),
        
        // Role Permissions
        'role_permissions' => array(
            'administrator' => array('all'),
            'shop_manager' => array('view_analytics', 'manage_affiliates', 'manage_commissions'),
            'affiliate' => array('view_own_dashboard', 'request_withdrawal'),
        ),
        
        // Advanced Settings
        'enable_logging' => true,
        'enable_debug_mode' => false,
        'api_rate_limit' => 100, // requests per minute
        'cache_duration' => 300, // 5 minutes
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->load_settings();
    }
    
    /**
     * Load settings from database
     */
    private function load_settings() {
        $saved_settings = get_option(self::OPTION_NAME, array());
        $this->settings = wp_parse_args($saved_settings, $this->defaults);
        
        // Generate JWT secret if not exists
        if (empty($this->settings['jwt_secret_key'])) {
            $this->settings['jwt_secret_key'] = $this->generate_jwt_secret();
            $this->save_settings();
        }
    }
    
    /**
     * Get all settings
     */
    public function get_all() {
        return $this->settings;
    }
    
    /**
     * Get specific setting
     */
    public function get($key, $default = null) {
        if (strpos($key, '.') !== false) {
            // Handle nested keys like 'modules.affiliate'
            $keys = explode('.', $key);
            $value = $this->settings;
            
            foreach ($keys as $k) {
                if (isset($value[$k])) {
                    $value = $value[$k];
                } else {
                    return $default;
                }
            }
            
            return $value;
        }
        
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }
    
    /**
     * Update setting
     */
    public function update($key, $value) {
        if (strpos($key, '.') !== false) {
            // Handle nested keys
            $keys = explode('.', $key);
            $temp = &$this->settings;
            
            foreach ($keys as $i => $k) {
                if ($i === count($keys) - 1) {
                    $temp[$k] = $value;
                } else {
                    if (!isset($temp[$k]) || !is_array($temp[$k])) {
                        $temp[$k] = array();
                    }
                    $temp = &$temp[$k];
                }
            }
        } else {
            $this->settings[$key] = $value;
        }
        
        return $this->save_settings();
    }
    
    /**
     * Update multiple settings
     */
    public function update_multiple($settings) {
        $this->settings = array_merge($this->settings, $settings);
        return $this->save_settings();
    }
    
    /**
     * Save settings to database
     */
    private function save_settings() {
        return update_option(self::OPTION_NAME, $this->settings);
    }
    
    /**
     * Reset settings to defaults
     */
    public function reset() {
        $this->settings = $this->defaults;
        $this->settings['jwt_secret_key'] = $this->generate_jwt_secret();
        return $this->save_settings();
    }
    
    /**
     * Check if module is enabled
     */
    public function is_module_enabled($module) {
        // Analytics and user management are always enabled (core features)
        if (in_array($module, array('analytics', 'user_management'))) {
            return true;
        }
        
        return !empty($this->settings['modules'][$module]);
    }
    
    /**
     * Enable module
     */
    public function enable_module($module) {
        $this->settings['modules'][$module] = true;
        return $this->save_settings();
    }
    
    /**
     * Disable module
     */
    public function disable_module($module) {
        // Prevent disabling core modules
        if (in_array($module, array('analytics', 'user_management'))) {
            return false;
        }
        
        $this->settings['modules'][$module] = false;
        return $this->save_settings();
    }
    
    /**
     * Get branding settings
     */
    public function get_branding() {
        return array(
            'name' => $this->get('brand_name'),
            'logo_url' => $this->get('brand_logo_url'),
            'favicon_url' => $this->get('brand_favicon_url'),
            'colors' => array(
                'primary' => $this->get('brand_primary_color'),
                'secondary' => $this->get('brand_secondary_color'),
                'accent' => $this->get('brand_accent_color'),
            ),
        );
    }
    
    /**
     * Get module configuration
     */
    public function get_module_config() {
        return array(
            'enabled_modules' => array_keys(array_filter($this->settings['modules'])),
            'affiliate' => array(
                'approval_required' => $this->get('affiliate_approval_required'),
                'default_commission_rate' => $this->get('affiliate_default_commission_rate'),
                'minimum_withdrawal' => $this->get('affiliate_minimum_withdrawal'),
                'currency' => $this->get('affiliate_currency'),
            ),
            'commission' => array(
                'calculation' => $this->get('commission_calculation'),
                'apply_to' => $this->get('commission_apply_to'),
                'status_trigger' => $this->get('commission_status_trigger'),
            ),
        );
    }
    
    /**
     * Generate secure JWT secret key
     */
    private function generate_jwt_secret() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Export settings for frontend
     */
    public function export_for_frontend() {
        return array(
            'branding' => $this->get_branding(),
            'modules' => array_keys(array_filter($this->settings['modules'])),
            'affiliate_config' => array(
                'approval_required' => $this->get('affiliate_approval_required'),
                'minimum_withdrawal' => $this->get('affiliate_minimum_withdrawal'),
                'currency' => $this->get('affiliate_currency'),
            ),
        );
    }
}
