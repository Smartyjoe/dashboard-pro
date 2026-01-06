<?php
/**
 * Email Manager
 * Handles all email notifications
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Dashboard_Pro_Email_Manager {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Settings instance
     */
    private $settings;
    
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
        
        // Load email templates
        require_once WP_DASHBOARD_PRO_PLUGIN_DIR . 'includes/modules/email/email-templates.php';
        
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Affiliate notifications
        add_action('wp_dashboard_pro_affiliate_approved', array($this, 'send_affiliate_approved'));
        add_action('wp_dashboard_pro_affiliate_rejected', array($this, 'send_affiliate_rejected'), 10, 2);
        
        // Commission notifications
        add_action('wp_dashboard_pro_commission_created', array($this, 'send_commission_earned'), 10, 3);
        add_action('wp_dashboard_pro_commission_approved', array($this, 'send_commission_approved'));
        
        // Withdrawal notifications
        add_action('wp_dashboard_pro_withdrawal_requested', array($this, 'send_withdrawal_requested'));
        add_action('wp_dashboard_pro_withdrawal_approved', array($this, 'send_withdrawal_approved'));
        add_action('wp_dashboard_pro_withdrawal_rejected', array($this, 'send_withdrawal_rejected'), 10, 2);
    }
    
    /**
     * Send affiliate approved email
     */
    public function send_affiliate_approved($affiliate) {
        if (!$this->settings->get('email_notifications.affiliate_approved')) {
            return;
        }
        
        $user = get_user_by('ID', $affiliate->user_id);
        
        if (!$user) {
            return;
        }
        
        $subject = sprintf('[%s] Affiliate Application Approved', $this->get_site_name());
        $message = WP_Dashboard_Pro_Email_Templates::affiliate_approved($affiliate, $user);
        
        $this->send_email($user->user_email, $subject, $message, true);
    }
    
    /**
     * Send affiliate rejected email
     */
    public function send_affiliate_rejected($affiliate, $reason) {
        if (!$this->settings->get('email_notifications.affiliate_rejected')) {
            return;
        }
        
        $user = get_user_by('ID', $affiliate->user_id);
        
        if (!$user) {
            return;
        }
        
        $subject = sprintf('[%s] Affiliate Application Status', $this->get_site_name());
        $message = WP_Dashboard_Pro_Email_Templates::affiliate_rejected($affiliate, $user, $reason);
        
        $this->send_email($user->user_email, $subject, $message, true);
    }
    
    /**
     * Send commission earned email
     */
    public function send_commission_earned($commission_id, $order, $affiliate) {
        if (!$this->settings->get('email_notifications.commission_earned')) {
            return;
        }
        
        global $wpdb;
        
        $commission_table = $wpdb->prefix . 'dashboard_pro_commissions';
        $commission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $commission_table WHERE id = %d",
            $commission_id
        ));
        
        if (!$commission) {
            return;
        }
        
        $user = get_user_by('ID', $affiliate->user_id);
        
        if (!$user) {
            return;
        }
        
        $subject = sprintf('[%s] New Commission Earned', $this->get_site_name());
        $message = WP_Dashboard_Pro_Email_Templates::commission_earned($commission, $user, $order);
        
        $this->send_email($user->user_email, $subject, $message, true);
    }
    
    /**
     * Send commission approved email
     */
    public function send_commission_approved($commission) {
        // Implementation similar to above
    }
    
    /**
     * Send withdrawal requested email
     */
    public function send_withdrawal_requested($withdrawal_id) {
        if (!$this->settings->get('email_notifications.withdrawal_requested')) {
            return;
        }
        
        global $wpdb;
        
        $withdrawal_table = $wpdb->prefix . 'dashboard_pro_withdrawals';
        $affiliate_table = $wpdb->prefix . 'dashboard_pro_affiliates';
        
        $withdrawal = $wpdb->get_row($wpdb->prepare(
            "SELECT w.*, a.user_id, a.affiliate_code FROM $withdrawal_table w
            LEFT JOIN $affiliate_table a ON w.affiliate_id = a.id
            WHERE w.id = %d",
            $withdrawal_id
        ));
        
        if (!$withdrawal) {
            return;
        }
        
        $affiliate = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $affiliate_table WHERE id = %d",
            $withdrawal->affiliate_id
        ));
        
        // Send to admin
        $admin_email = get_option('admin_email');
        $subject = sprintf('[%s] New Withdrawal Request', $this->get_site_name());
        $message = WP_Dashboard_Pro_Email_Templates::withdrawal_requested_admin($withdrawal, $affiliate);
        $this->send_email($admin_email, $subject, $message, true);
        
        // Send to affiliate
        $user = get_user_by('ID', $withdrawal->user_id);
        
        if ($user) {
            $subject = sprintf('[%s] Withdrawal Request Received', $this->get_site_name());
            $message = WP_Dashboard_Pro_Email_Templates::withdrawal_requested_affiliate($withdrawal, $user);
            $this->send_email($user->user_email, $subject, $message, true);
        }
    }
    
    /**
     * Send withdrawal approved email
     */
    public function send_withdrawal_approved($withdrawal) {
        if (!$this->settings->get('email_notifications.withdrawal_approved')) {
            return;
        }
        
        global $wpdb;
        
        $affiliate_table = $wpdb->prefix . 'dashboard_pro_affiliates';
        $affiliate = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $affiliate_table WHERE id = %d",
            $withdrawal->affiliate_id
        ));
        
        if (!$affiliate) {
            return;
        }
        
        $user = get_user_by('ID', $affiliate->user_id);
        
        if (!$user) {
            return;
        }
        
        $subject = sprintf('[%s] Withdrawal Approved', $this->get_site_name());
        $message = WP_Dashboard_Pro_Email_Templates::withdrawal_approved($withdrawal, $user);
        
        $this->send_email($user->user_email, $subject, $message, true);
    }
    
    /**
     * Send withdrawal rejected email
     */
    public function send_withdrawal_rejected($withdrawal, $reason) {
        if (!$this->settings->get('email_notifications.withdrawal_rejected')) {
            return;
        }
        
        global $wpdb;
        
        $affiliate_table = $wpdb->prefix . 'dashboard_pro_affiliates';
        $affiliate = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $affiliate_table WHERE id = %d",
            $withdrawal->affiliate_id
        ));
        
        if (!$affiliate) {
            return;
        }
        
        $user = get_user_by('ID', $affiliate->user_id);
        
        if (!$user) {
            return;
        }
        
        $subject = sprintf('[%s] Withdrawal Request Status', $this->get_site_name());
        $message = WP_Dashboard_Pro_Email_Templates::withdrawal_rejected($withdrawal, $user, $reason);
        
        $this->send_email($user->user_email, $subject, $message, true);
    }
    
    /**
     * Send email
     */
    private function send_email($to, $subject, $message, $is_html = false) {
        $from_name = $this->settings->get('email_from_name') ?: $this->get_site_name();
        $from_email = $this->settings->get('email_from_address') ?: get_option('admin_email');
        
        $content_type = $is_html ? 'text/html' : 'text/plain';
        
        $headers = array(
            "Content-Type: $content_type; charset=UTF-8",
            "From: $from_name <$from_email>",
        );
        
        wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Get site name
     */
    private function get_site_name() {
        return $this->settings->get('brand_name') ?: get_bloginfo('name');
    }
}
