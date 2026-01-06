<?php
/**
 * Commission Module
 * Handles WooCommerce order tracking and commission creation
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Dashboard_Pro_Commission {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Settings instance
     */
    private $settings;
    
    /**
     * Calculator instance
     */
    private $calculator;
    
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
        $this->calculator = new WP_Dashboard_Pro_Commission_Calculator();
        
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // WooCommerce order status changed
        add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'), 10, 4);
        
        // Order completed (backup hook)
        add_action('woocommerce_order_status_completed', array($this, 'handle_order_completed'));
        
        // Order refunded
        add_action('woocommerce_order_refunded', array($this, 'handle_order_refunded'), 10, 2);
    }
    
    /**
     * Handle order status change
     */
    public function handle_order_status_change($order_id, $old_status, $new_status, $order) {
        $trigger_status = $this->settings->get('commission_status_trigger', 'completed');
        
        // Remove 'wc-' prefix if present
        $trigger_status = str_replace('wc-', '', $trigger_status);
        $new_status = str_replace('wc-', '', $new_status);
        
        // Create commission when order reaches trigger status
        if ($new_status === $trigger_status) {
            $this->process_order_commission($order_id);
        }
        
        // Handle refunds
        if ($new_status === 'refunded' || $new_status === 'cancelled') {
            $this->handle_order_refunded($order_id);
        }
    }
    
    /**
     * Handle order completed
     */
    public function handle_order_completed($order_id) {
        $trigger_status = $this->settings->get('commission_status_trigger', 'completed');
        
        if ($trigger_status === 'completed') {
            $this->process_order_commission($order_id);
        }
    }
    
    /**
     * Process order commission
     */
    private function process_order_commission($order_id) {
        global $wpdb;
        
        // Get order
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        // Find referral for this order
        $referral = $this->find_referral_for_order($order);
        
        if (!$referral) {
            return;
        }
        
        // Create commission
        $this->calculator->create_commission($order_id, $referral->affiliate_id, $referral->id);
    }
    
    /**
     * Find referral for order
     */
    private function find_referral_for_order($order) {
        global $wpdb;
        
        $customer_id = $order->get_customer_id();
        
        if (!$customer_id) {
            // Try to find by email
            $email = $order->get_billing_email();
            $user = get_user_by('email', $email);
            if ($user) {
                $customer_id = $user->ID;
            }
        }
        
        if (!$customer_id) {
            return null;
        }
        
        // Get referral from user meta (stored when user registered via referral)
        $affiliate_code = get_user_meta($customer_id, '_referred_by', true);
        
        if (!$affiliate_code) {
            // Try to get from order meta
            $affiliate_code = $order->get_meta('_referred_by');
        }
        
        if (!$affiliate_code) {
            return null;
        }
        
        // Get affiliate by code
        $affiliate_table = $wpdb->prefix . 'dashboard_pro_affiliates';
        $affiliate = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $affiliate_table WHERE affiliate_code = %s",
            $affiliate_code
        ));
        
        if (!$affiliate) {
            return null;
        }
        
        // Get or create referral record
        $referral_table = $wpdb->prefix . 'dashboard_pro_referrals';
        $referral = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $referral_table 
            WHERE affiliate_id = %d 
            AND order_id = %d",
            $affiliate->id,
            $order->get_id()
        ));
        
        if (!$referral) {
            // Create referral record
            $wpdb->insert(
                $referral_table,
                array(
                    'affiliate_id' => $affiliate->id,
                    'order_id' => $order->get_id(),
                    'converted' => 0,
                    'created_at' => current_time('mysql'),
                ),
                array('%d', '%d', '%d', '%s')
            );
            
            $referral = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $referral_table WHERE id = %d",
                $wpdb->insert_id
            ));
        }
        
        return $referral;
    }
    
    /**
     * Handle order refunded
     */
    public function handle_order_refunded($order_id, $refund_id = null) {
        global $wpdb;
        
        $commission_table = $wpdb->prefix . 'dashboard_pro_commissions';
        
        // Get commission for this order
        $commission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $commission_table WHERE order_id = %d",
            $order_id
        ));
        
        if (!$commission) {
            return;
        }
        
        // If commission is unpaid, delete it
        if ($commission->status === 'pending' || $commission->status === 'approved') {
            $wpdb->delete($commission_table, array('id' => $commission->id));
            
            // Update affiliate totals
            $this->update_affiliate_totals($commission->affiliate_id);
            
            do_action('wp_dashboard_pro_commission_revoked', $commission->id, $order_id);
        } elseif ($commission->status === 'paid') {
            // If already paid, create a negative commission
            $wpdb->insert(
                $commission_table,
                array(
                    'affiliate_id' => $commission->affiliate_id,
                    'order_id' => $order_id,
                    'amount' => -abs($commission->amount),
                    'status' => 'approved',
                    'type' => 'refund',
                    'description' => "Refund for Order #{$order_id}",
                    'created_at' => current_time('mysql'),
                ),
                array('%d', '%d', '%f', '%s', '%s', '%s', '%s')
            );
            
            // Update affiliate totals
            $this->update_affiliate_totals($commission->affiliate_id);
        }
    }
    
    /**
     * Update affiliate totals
     */
    private function update_affiliate_totals($affiliate_id) {
        global $wpdb;
        
        $commission_table = $wpdb->prefix . 'dashboard_pro_commissions';
        $affiliate_table = $wpdb->prefix . 'dashboard_pro_affiliates';
        
        $totals = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COALESCE(SUM(amount), 0) as total_commissions,
                COALESCE(SUM(CASE WHEN status = 'pending' OR status = 'approved' THEN amount ELSE 0 END), 0) as pending,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) as paid
            FROM $commission_table
            WHERE affiliate_id = %d
        ", $affiliate_id));
        
        $wpdb->update(
            $affiliate_table,
            array(
                'total_commissions' => $totals->total_commissions,
                'pending_commissions' => $totals->pending,
                'paid_commissions' => $totals->paid,
            ),
            array('id' => $affiliate_id)
        );
    }
}
