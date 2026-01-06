<?php
/**
 * Referral Tracker
 * Tracks affiliate referrals via cookies and URL parameters
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Dashboard_Pro_Referral_Tracker {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Settings instance
     */
    private $settings;
    
    /**
     * Cookie name
     */
    private $cookie_name = 'wp_dashboard_pro_ref';
    
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
        $this->settings = new WP_Dashboard_Pro_Settings();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'track_referral'));
        add_action('user_register', array($this, 'save_referral_to_user'));
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_referral_to_order'));
    }
    
    /**
     * Track referral from URL parameter
     */
    public function track_referral() {
        // Check for referral parameter
        $ref_code = null;
        
        if (isset($_GET['ref'])) {
            $ref_code = sanitize_text_field($_GET['ref']);
        } elseif (isset($_GET['affiliate'])) {
            $ref_code = sanitize_text_field($_GET['affiliate']);
        } elseif (isset($_GET['aff'])) {
            $ref_code = sanitize_text_field($_GET['aff']);
        }
        
        if (!$ref_code) {
            return;
        }
        
        // Validate affiliate code
        global $wpdb;
        $affiliate_table = $wpdb->prefix . 'dashboard_pro_affiliates';
        $affiliate = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $affiliate_table WHERE affiliate_code = %s AND status = 'active'",
            $ref_code
        ));
        
        if (!$affiliate) {
            return;
        }
        
        // Set cookie
        $cookie_duration = $this->settings->get('affiliate_cookie_duration', 30);
        $expiry = time() + (DAY_IN_SECONDS * $cookie_duration);
        
        setcookie(
            $this->cookie_name,
            $ref_code,
            $expiry,
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(),
            true
        );
        
        // Store in session as backup
        if (!session_id()) {
            session_start();
        }
        $_SESSION['wp_dashboard_pro_ref'] = $ref_code;
        
        // Track visit
        $this->track_visit($affiliate->id);
    }
    
    /**
     * Track affiliate visit
     */
    private function track_visit($affiliate_id) {
        global $wpdb;
        
        $referral_table = $wpdb->prefix . 'dashboard_pro_referrals';
        
        // Generate visitor ID
        $visitor_id = $this->get_visitor_id();
        
        // Check if visit already tracked today
        $exists = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $referral_table 
            WHERE affiliate_id = %d 
            AND visitor_id = %s 
            AND DATE(created_at) = CURDATE()
        ", $affiliate_id, $visitor_id));
        
        if ($exists > 0) {
            return;
        }
        
        // Track visit
        $wpdb->insert(
            $referral_table,
            array(
                'affiliate_id' => $affiliate_id,
                'visitor_id' => $visitor_id,
                'ip_address' => $this->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'referrer_url' => $_SERVER['HTTP_REFERER'] ?? '',
                'landing_page' => $_SERVER['REQUEST_URI'] ?? '',
                'converted' => 0,
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
        );
        
        // Update affiliate total referrals
        $affiliate_table = $wpdb->prefix . 'dashboard_pro_affiliates';
        $wpdb->query($wpdb->prepare(
            "UPDATE $affiliate_table SET total_referrals = total_referrals + 1 WHERE id = %d",
            $affiliate_id
        ));
    }
    
    /**
     * Save referral to user on registration
     */
    public function save_referral_to_user($user_id) {
        $ref_code = $this->get_referral_code();
        
        if ($ref_code) {
            update_user_meta($user_id, '_referred_by', $ref_code);
        }
    }
    
    /**
     * Save referral to order
     */
    public function save_referral_to_order($order_id) {
        $ref_code = $this->get_referral_code();
        
        if ($ref_code) {
            $order = wc_get_order($order_id);
            if ($order) {
                $order->update_meta_data('_referred_by', $ref_code);
                $order->save();
            }
        }
    }
    
    /**
     * Get referral code from cookie or session
     */
    public function get_referral_code() {
        // Check cookie
        if (isset($_COOKIE[$this->cookie_name])) {
            return sanitize_text_field($_COOKIE[$this->cookie_name]);
        }
        
        // Check session
        if (!session_id()) {
            session_start();
        }
        
        if (isset($_SESSION['wp_dashboard_pro_ref'])) {
            return sanitize_text_field($_SESSION['wp_dashboard_pro_ref']);
        }
        
        return null;
    }
    
    /**
     * Get or generate visitor ID
     */
    private function get_visitor_id() {
        $visitor_id_cookie = 'wp_dashboard_pro_visitor';
        
        if (isset($_COOKIE[$visitor_id_cookie])) {
            return $_COOKIE[$visitor_id_cookie];
        }
        
        // Generate new visitor ID
        $visitor_id = wp_generate_password(32, false);
        
        setcookie(
            $visitor_id_cookie,
            $visitor_id,
            time() + YEAR_IN_SECONDS,
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(),
            true
        );
        
        return $visitor_id;
    }
    
    /**
     * Get client IP
     */
    private function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        return $ip;
    }
}
