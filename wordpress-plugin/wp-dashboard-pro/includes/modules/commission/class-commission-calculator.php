<?php
/**
 * Commission Calculator
 * Handles commission calculation logic
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Dashboard_Pro_Commission_Calculator {
    
    /**
     * Settings instance
     */
    private $settings;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = new WP_Dashboard_Pro_Settings();
    }
    
    /**
     * Calculate commission for an order
     */
    public function calculate_commission($order, $affiliate_id) {
        global $wpdb;
        
        if (!class_exists('WooCommerce')) {
            return 0;
        }
        
        // Get affiliate
        $affiliate_table = $wpdb->prefix . 'dashboard_pro_affiliates';
        $affiliate = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $affiliate_table WHERE id = %d",
            $affiliate_id
        ));
        
        if (!$affiliate || $affiliate->status !== 'active') {
            return 0;
        }
        
        $commission_rate = floatval($affiliate->commission_rate);
        $calculation_method = $this->settings->get('commission_calculation', 'percentage');
        $apply_to = $this->settings->get('commission_apply_to', 'subtotal');
        
        // Get order object if ID is passed
        if (is_numeric($order)) {
            $order = wc_get_order($order);
        }
        
        if (!$order) {
            return 0;
        }
        
        // Determine base amount
        $base_amount = 0;
        
        switch ($apply_to) {
            case 'subtotal':
                $base_amount = $order->get_subtotal();
                break;
            case 'total':
                $base_amount = $order->get_total();
                break;
            case 'profit':
                // Calculate profit (total - cost of goods)
                $base_amount = $this->calculate_profit($order);
                break;
            default:
                $base_amount = $order->get_subtotal();
        }
        
        // Calculate commission
        if ($calculation_method === 'fixed') {
            $commission = $commission_rate;
        } else {
            $commission = ($base_amount * $commission_rate) / 100;
        }
        
        // Apply filters for custom calculation
        $commission = apply_filters('wp_dashboard_pro_calculate_commission', $commission, $order, $affiliate);
        
        return round($commission, 2);
    }
    
    /**
     * Calculate profit for order
     */
    private function calculate_profit($order) {
        $total = $order->get_total();
        $cost = 0;
        
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product) {
                $product_cost = get_post_meta($product->get_id(), '_cost', true);
                if ($product_cost) {
                    $cost += floatval($product_cost) * $item->get_quantity();
                }
            }
        }
        
        return max(0, $total - $cost);
    }
    
    /**
     * Create commission record
     */
    public function create_commission($order_id, $affiliate_id, $referral_id = null) {
        global $wpdb;
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return false;
        }
        
        // Calculate commission
        $commission_amount = $this->calculate_commission($order, $affiliate_id);
        
        if ($commission_amount <= 0) {
            return false;
        }
        
        // Get affiliate
        $affiliate_table = $wpdb->prefix . 'dashboard_pro_affiliates';
        $affiliate = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $affiliate_table WHERE id = %d",
            $affiliate_id
        ));
        
        // Check if commission already exists for this order
        $commission_table = $wpdb->prefix . 'dashboard_pro_commissions';
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $commission_table WHERE order_id = %d",
            $order_id
        ));
        
        if ($exists > 0) {
            return false;
        }
        
        // Calculate due date if hold period is set
        $hold_period = $this->settings->get('commission_hold_period', 0);
        $due_date = null;
        if ($hold_period > 0) {
            $due_date = date('Y-m-d H:i:s', strtotime("+{$hold_period} days"));
        }
        
        // Create commission
        $result = $wpdb->insert(
            $commission_table,
            array(
                'affiliate_id' => $affiliate_id,
                'referral_id' => $referral_id,
                'order_id' => $order_id,
                'amount' => $commission_amount,
                'rate' => $affiliate->commission_rate,
                'order_total' => $order->get_total(),
                'status' => 'pending',
                'type' => 'sale',
                'description' => "Order #{$order_id}",
                'due_date' => $due_date,
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%d', '%f', '%f', '%f', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            $commission_id = $wpdb->insert_id;
            
            // Update affiliate totals
            $this->update_affiliate_totals($affiliate_id);
            
            // Update referral as converted
            if ($referral_id) {
                $referral_table = $wpdb->prefix . 'dashboard_pro_referrals';
                $wpdb->update(
                    $referral_table,
                    array(
                        'converted' => 1,
                        'conversion_date' => current_time('mysql'),
                        'order_id' => $order_id,
                    ),
                    array('id' => $referral_id)
                );
            }
            
            // Send notification
            do_action('wp_dashboard_pro_commission_created', $commission_id, $order, $affiliate);
            
            return $commission_id;
        }
        
        return false;
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
                COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) as paid,
                COALESCE(SUM(order_total), 0) as total_sales
            FROM $commission_table
            WHERE affiliate_id = %d
        ", $affiliate_id));
        
        $wpdb->update(
            $affiliate_table,
            array(
                'total_commissions' => $totals->total_commissions,
                'pending_commissions' => $totals->pending,
                'paid_commissions' => $totals->paid,
                'total_sales' => $totals->total_sales,
            ),
            array('id' => $affiliate_id)
        );
    }
}
