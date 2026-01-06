<?php
/**
 * 2-Tier Affiliate System
 * Handles multi-level affiliate commissions
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Dashboard_Pro_Tier_System {
    
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
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Hook into commission creation
        add_action('wp_dashboard_pro_commission_created', array($this, 'create_tier2_commission'), 10, 3);
        
        // Hook into affiliate registration
        add_action('wp_dashboard_pro_affiliate_created', array($this, 'track_parent_affiliate'), 10, 2);
    }
    
    /**
     * Track parent affiliate when new affiliate signs up
     */
    public function track_parent_affiliate($affiliate_id, $user_id) {
        global $wpdb;
        
        // Check if there's a referral cookie
        $parent_code = isset($_COOKIE['affiliate_ref']) ? sanitize_text_field($_COOKIE['affiliate_ref']) : '';
        
        if (empty($parent_code)) {
            return;
        }
        
        $affiliate_table = $wpdb->prefix . 'dashboard_pro_affiliates';
        
        // Find parent affiliate by code
        $parent = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $affiliate_table WHERE affiliate_code = %s AND status = 'active'",
            $parent_code
        ));
        
        if (!$parent) {
            return;
        }
        
        // Update new affiliate with parent
        $wpdb->update(
            $affiliate_table,
            array('parent_affiliate_id' => $parent->id),
            array('id' => $affiliate_id),
            array('%d'),
            array('%d')
        );
        
        // Increment parent's sub-affiliate count
        $wpdb->query($wpdb->prepare(
            "UPDATE $affiliate_table SET total_sub_affiliates = total_sub_affiliates + 1 WHERE id = %d",
            $parent->id
        ));
        
        // Log activity
        $this->log_activity($parent->id, 'sub_affiliate_recruited', array(
            'sub_affiliate_id' => $affiliate_id,
            'user_id' => $user_id,
        ));
    }
    
    /**
     * Create tier 2 commission when tier 1 commission is created
     */
    public function create_tier2_commission($commission_id, $order, $affiliate) {
        // Check if 2-tier is enabled
        if (!$this->settings->get('affiliate.enable_2tier', false)) {
            return;
        }
        
        // Check if affiliate has a parent
        if (empty($affiliate->parent_affiliate_id)) {
            return;
        }
        
        global $wpdb;
        
        $affiliate_table = $wpdb->prefix . 'dashboard_pro_affiliates';
        $commission_table = $wpdb->prefix . 'dashboard_pro_commissions';
        
        // Get parent affiliate
        $parent = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $affiliate_table WHERE id = %d AND status = 'active'",
            $affiliate->parent_affiliate_id
        ));
        
        if (!$parent) {
            return;
        }
        
        // Get the original commission details
        $original_commission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $commission_table WHERE id = %d",
            $commission_id
        ));
        
        if (!$original_commission) {
            return;
        }
        
        // Calculate tier 2 commission
        $tier2_rate = $parent->tier2_commission_rate;
        $order_total = $original_commission->order_total;
        $tier2_amount = ($order_total * $tier2_rate) / 100;
        
        // Apply minimum commission if set
        $min_commission = $this->settings->get('affiliate.min_commission', 0);
        if ($tier2_amount < $min_commission) {
            return;
        }
        
        // Create tier 2 commission
        $wpdb->insert(
            $commission_table,
            array(
                'affiliate_id' => $parent->id,
                'parent_affiliate_id' => null, // Parent doesn't have a parent in this system
                'referral_id' => $original_commission->referral_id,
                'order_id' => $original_commission->order_id,
                'amount' => $tier2_amount,
                'rate' => $tier2_rate,
                'order_total' => $order_total,
                'tier' => 2,
                'status' => 'pending',
                'type' => 'tier2_sale',
                'description' => sprintf(
                    'Tier 2 commission from sub-affiliate %s (Order #%d)',
                    $affiliate->affiliate_code,
                    $original_commission->order_id
                ),
            ),
            array('%d', '%d', '%d', '%d', '%f', '%f', '%f', '%d', '%s', '%s', '%s')
        );
        
        $tier2_commission_id = $wpdb->insert_id;
        
        // Update parent's stats
        $wpdb->query($wpdb->prepare(
            "UPDATE $affiliate_table 
            SET total_commissions = total_commissions + %f,
                pending_commissions = pending_commissions + %f
            WHERE id = %d",
            $tier2_amount,
            $tier2_amount,
            $parent->id
        ));
        
        // Send notification
        do_action('wp_dashboard_pro_tier2_commission_created', $tier2_commission_id, $parent, $affiliate);
        
        // Log activity
        $this->log_activity($parent->id, 'tier2_commission_earned', array(
            'commission_id' => $tier2_commission_id,
            'amount' => $tier2_amount,
            'sub_affiliate_id' => $affiliate->id,
            'order_id' => $original_commission->order_id,
        ));
    }
    
    /**
     * Get affiliate's sub-affiliates
     */
    public function get_sub_affiliates($affiliate_id, $status = 'active') {
        global $wpdb;
        
        $affiliate_table = $wpdb->prefix . 'dashboard_pro_affiliates';
        
        $where = array($wpdb->prepare('parent_affiliate_id = %d', $affiliate_id));
        
        if ($status) {
            $where[] = $wpdb->prepare('status = %s', $status);
        }
        
        $where_clause = implode(' AND ', $where);
        
        return $wpdb->get_results("SELECT * FROM $affiliate_table WHERE $where_clause ORDER BY created_at DESC");
    }
    
    /**
     * Get sub-affiliate statistics
     */
    public function get_sub_affiliate_stats($affiliate_id) {
        global $wpdb;
        
        $affiliate_table = $wpdb->prefix . 'dashboard_pro_affiliates';
        $commission_table = $wpdb->prefix . 'dashboard_pro_commissions';
        
        // Get total sub-affiliates
        $total_subs = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $affiliate_table WHERE parent_affiliate_id = %d",
            $affiliate_id
        ));
        
        // Get active sub-affiliates
        $active_subs = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $affiliate_table WHERE parent_affiliate_id = %d AND status = 'active'",
            $affiliate_id
        ));
        
        // Get tier 2 commission totals
        $tier2_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_commissions,
                COALESCE(SUM(amount), 0) as total_earned,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as pending,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) as paid
            FROM $commission_table 
            WHERE affiliate_id = %d AND tier = 2",
            $affiliate_id
        ));
        
        return array(
            'total_sub_affiliates' => intval($total_subs),
            'active_sub_affiliates' => intval($active_subs),
            'tier2_commissions' => intval($tier2_stats->total_commissions),
            'tier2_total_earned' => floatval($tier2_stats->total_earned),
            'tier2_pending' => floatval($tier2_stats->pending),
            'tier2_paid' => floatval($tier2_stats->paid),
        );
    }
    
    /**
     * Get affiliate hierarchy (parent chain)
     */
    public function get_affiliate_hierarchy($affiliate_id, $max_depth = 10) {
        global $wpdb;
        
        $affiliate_table = $wpdb->prefix . 'dashboard_pro_affiliates';
        $hierarchy = array();
        $current_id = $affiliate_id;
        $depth = 0;
        
        while ($current_id && $depth < $max_depth) {
            $affiliate = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $affiliate_table WHERE id = %d",
                $current_id
            ));
            
            if (!$affiliate) {
                break;
            }
            
            $hierarchy[] = array(
                'id' => $affiliate->id,
                'user_id' => $affiliate->user_id,
                'affiliate_code' => $affiliate->affiliate_code,
                'level' => $depth + 1,
            );
            
            $current_id = $affiliate->parent_affiliate_id;
            $depth++;
        }
        
        return $hierarchy;
    }
    
    /**
     * Generate affiliate recruitment link
     */
    public function get_recruitment_link($affiliate_code) {
        $base_url = $this->settings->get('dashboard_url') ?: home_url();
        return add_query_arg('ref', $affiliate_code, trailingslashit($base_url) . 'affiliate/signup');
    }
    
    /**
     * Log activity
     */
    private function log_activity($affiliate_id, $action, $data = array()) {
        global $wpdb;
        
        $activity_table = $wpdb->prefix . 'dashboard_pro_activity';
        
        // Get affiliate user
        $affiliate_table = $wpdb->prefix . 'dashboard_pro_affiliates';
        $affiliate = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id FROM $affiliate_table WHERE id = %d",
            $affiliate_id
        ));
        
        if (!$affiliate) {
            return;
        }
        
        $wpdb->insert(
            $activity_table,
            array(
                'user_id' => $affiliate->user_id,
                'action' => $action,
                'object_type' => 'tier_system',
                'object_id' => $affiliate_id,
                'description' => $this->get_activity_description($action, $data),
                'metadata' => json_encode($data),
                'ip_address' => $this->get_ip_address(),
            ),
            array('%d', '%s', '%s', '%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get activity description
     */
    private function get_activity_description($action, $data) {
        switch ($action) {
            case 'sub_affiliate_recruited':
                return 'Recruited a new sub-affiliate';
            case 'tier2_commission_earned':
                return sprintf('Earned tier 2 commission: $%s', number_format($data['amount'], 2));
            default:
                return ucfirst(str_replace('_', ' ', $action));
        }
    }
    
    /**
     * Get IP address
     */
    private function get_ip_address() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
        } else {
            return sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }
    }
}
