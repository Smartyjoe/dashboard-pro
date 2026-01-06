<?php
/**
 * Dashboard API
 * Handles dashboard statistics and analytics endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Dashboard_Pro_Dashboard_API extends WP_Dashboard_Pro_API_Base {
    
    /**
     * Register routes
     */
    public function register_routes() {
        // Get dashboard stats
        $this->register_route('/dashboard/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_stats'),
            'permission_callback' => array($this, 'authenticate_request'),
        ));
        
        // Get recent activity
        $this->register_route('/dashboard/activity', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_activity'),
            'permission_callback' => array($this, 'authenticate_request'),
        ));
        
        // Get analytics data
        $this->register_route('/dashboard/analytics', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_analytics'),
            'permission_callback' => array($this, 'authenticate_request'),
        ));
        
        // Get sales chart data
        $this->register_route('/dashboard/sales-chart', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_sales_chart'),
            'permission_callback' => array($this, 'authenticate_request'),
        ));
    }
    
    /**
     * Get dashboard statistics
     */
    public function get_stats($request) {
        global $wpdb;
        
        $user = $this->auth->get_current_user();
        $is_admin = user_can($user, 'manage_options');
        $is_affiliate = in_array('affiliate', $user->roles);
        
        $stats = array();
        
        if ($is_admin) {
            // Admin stats
            $stats = $this->get_admin_stats();
        } elseif ($is_affiliate) {
            // Affiliate stats
            $stats = $this->get_affiliate_stats($user->ID);
        } else {
            // Basic user stats
            $stats = $this->get_user_stats($user->ID);
        }
        
        return $this->success_response($stats);
    }
    
    /**
     * Get admin statistics
     */
    private function get_admin_stats() {
        global $wpdb;
        
        $stats = array(
            'total_users' => $this->get_total_users(),
            'total_orders' => $this->get_total_orders(),
            'total_revenue' => $this->get_total_revenue(),
            'pending_orders' => $this->get_pending_orders(),
        );
        
        // Add affiliate stats if module enabled
        if ($this->settings->is_module_enabled('affiliate')) {
            $affiliate_table = $wpdb->prefix . 'dashboard_pro_affiliates';
            $commission_table = $wpdb->prefix . 'dashboard_pro_commissions';
            $withdrawal_table = $wpdb->prefix . 'dashboard_pro_withdrawals';
            
            $stats['total_affiliates'] = $wpdb->get_var("SELECT COUNT(*) FROM $affiliate_table WHERE status = 'active'");
            $stats['pending_affiliates'] = $wpdb->get_var("SELECT COUNT(*) FROM $affiliate_table WHERE status = 'pending'");
            $stats['total_commissions'] = $wpdb->get_var("SELECT COALESCE(SUM(amount), 0) FROM $commission_table");
            $stats['pending_commissions'] = $wpdb->get_var("SELECT COALESCE(SUM(amount), 0) FROM $commission_table WHERE status = 'pending'");
            $stats['paid_commissions'] = $wpdb->get_var("SELECT COALESCE(SUM(amount), 0) FROM $commission_table WHERE status = 'paid'");
            $stats['pending_withdrawals'] = $wpdb->get_var("SELECT COUNT(*) FROM $withdrawal_table WHERE status = 'pending'");
        }
        
        return $stats;
    }
    
    /**
     * Get affiliate statistics
     */
    private function get_affiliate_stats($user_id) {
        global $wpdb;
        
        $affiliate_table = $wpdb->prefix . 'dashboard_pro_affiliates';
        $commission_table = $wpdb->prefix . 'dashboard_pro_commissions';
        $referral_table = $wpdb->prefix . 'dashboard_pro_referrals';
        
        // Get affiliate record
        $affiliate = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $affiliate_table WHERE user_id = %d",
            $user_id
        ));
        
        if (!$affiliate) {
            return array();
        }
        
        // Get commission stats
        $commissions = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_commissions,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as pending_amount,
                COALESCE(SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END), 0) as approved_amount,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) as paid_amount
            FROM $commission_table 
            WHERE affiliate_id = %d",
            $affiliate->id
        ));
        
        // Get referral stats
        $referrals = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_visits,
                SUM(converted) as total_conversions
            FROM $referral_table 
            WHERE affiliate_id = %d",
            $affiliate->id
        ));
        
        // Get this month's stats
        $month_start = date('Y-m-01 00:00:00');
        $month_commissions = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) 
            FROM $commission_table 
            WHERE affiliate_id = %d AND created_at >= %s",
            $affiliate->id,
            $month_start
        ));
        
        return array(
            'affiliate_code' => $affiliate->affiliate_code,
            'commission_rate' => $affiliate->commission_rate,
            'status' => $affiliate->status,
            'total_referrals' => intval($referrals->total_visits),
            'total_conversions' => intval($referrals->total_conversions),
            'conversion_rate' => $referrals->total_visits > 0 ? round(($referrals->total_conversions / $referrals->total_visits) * 100, 2) : 0,
            'total_commissions' => intval($commissions->total_commissions),
            'pending_earnings' => floatval($commissions->pending_amount),
            'approved_earnings' => floatval($commissions->approved_amount),
            'paid_earnings' => floatval($commissions->paid_amount),
            'total_earnings' => floatval($commissions->pending_amount + $commissions->approved_amount + $commissions->paid_amount),
            'this_month_earnings' => floatval($month_commissions),
        );
    }
    
    /**
     * Get user statistics
     */
    private function get_user_stats($user_id) {
        return array(
            'total_orders' => $this->get_user_orders($user_id),
            'total_spent' => $this->get_user_total_spent($user_id),
        );
    }
    
    /**
     * Get recent activity
     */
    public function get_activity($request) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'dashboard_pro_activity';
        $pagination = $this->get_pagination_params($request);
        
        $user = $this->auth->get_current_user();
        $is_admin = user_can($user, 'manage_options');
        
        // Build query
        $where = $is_admin ? '1=1' : $wpdb->prepare('user_id = %d', $user->ID);
        
        // Get total count
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE $where");
        
        // Get activities
        $offset = ($pagination['page'] - 1) * $pagination['per_page'];
        $activities = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, u.display_name as user_name, u.user_email
            FROM $table a
            LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
            WHERE $where
            ORDER BY a.created_at DESC
            LIMIT %d OFFSET %d",
            $pagination['per_page'],
            $offset
        ));
        
        // Format activities
        $formatted_activities = array_map(function($activity) {
            return array(
                'id' => $activity->id,
                'user' => array(
                    'id' => $activity->user_id,
                    'name' => $activity->user_name,
                    'email' => $activity->user_email,
                ),
                'action' => $activity->action,
                'description' => $activity->description,
                'object_type' => $activity->object_type,
                'object_id' => $activity->object_id,
                'metadata' => $activity->metadata ? json_decode($activity->metadata) : null,
                'ip_address' => $activity->ip_address,
                'created_at' => $activity->created_at,
            );
        }, $activities);
        
        return $this->success_response(
            $this->format_pagination_response(
                $formatted_activities,
                $total,
                $pagination['page'],
                $pagination['per_page']
            )
        );
    }
    
    /**
     * Get analytics data
     */
    public function get_analytics($request) {
        $period = $request->get_param('period') ?: '30days';
        $metric = $request->get_param('metric') ?: 'all';
        
        $analytics = array(
            'period' => $period,
            'data' => $this->get_analytics_data($period, $metric),
        );
        
        return $this->success_response($analytics);
    }
    
    /**
     * Get sales chart data
     */
    public function get_sales_chart($request) {
        $days = intval($request->get_param('days') ?: 30);
        
        $chart_data = $this->get_sales_by_day($days);
        
        return $this->success_response($chart_data);
    }
    
    /**
     * Helper: Get total users
     */
    private function get_total_users() {
        return count_users()['total_users'];
    }
    
    /**
     * Helper: Get total orders (WooCommerce)
     */
    private function get_total_orders() {
        if (!class_exists('WooCommerce')) {
            return 0;
        }
        
        $orders = wc_get_orders(array(
            'limit' => -1,
            'status' => array('wc-completed', 'wc-processing', 'wc-on-hold'),
            'return' => 'ids',
        ));
        
        return count($orders);
    }
    
    /**
     * Helper: Get total revenue (WooCommerce)
     */
    private function get_total_revenue() {
        if (!class_exists('WooCommerce')) {
            return 0;
        }
        
        global $wpdb;
        
        $result = $wpdb->get_var("
            SELECT SUM(meta_value) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_order_total' 
            AND post_id IN (
                SELECT ID FROM {$wpdb->posts} 
                WHERE post_type = 'shop_order' 
                AND post_status IN ('wc-completed', 'wc-processing')
            )
        ");
        
        return floatval($result);
    }
    
    /**
     * Helper: Get pending orders
     */
    private function get_pending_orders() {
        if (!class_exists('WooCommerce')) {
            return 0;
        }
        
        $orders = wc_get_orders(array(
            'limit' => -1,
            'status' => 'pending',
            'return' => 'ids',
        ));
        
        return count($orders);
    }
    
    /**
     * Helper: Get user orders
     */
    private function get_user_orders($user_id) {
        if (!class_exists('WooCommerce')) {
            return 0;
        }
        
        $orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'limit' => -1,
            'return' => 'ids',
        ));
        
        return count($orders);
    }
    
    /**
     * Helper: Get user total spent
     */
    private function get_user_total_spent($user_id) {
        if (!class_exists('WooCommerce')) {
            return 0;
        }
        
        $customer = new WC_Customer($user_id);
        return $customer->get_total_spent();
    }
    
    /**
     * Helper: Get analytics data
     */
    private function get_analytics_data($period, $metric) {
        // This would contain more complex analytics logic
        // For now, return basic structure
        return array(
            'visits' => 0,
            'conversions' => 0,
            'revenue' => 0,
            'commissions' => 0,
        );
    }
    
    /**
     * Helper: Get sales by day
     */
    private function get_sales_by_day($days) {
        if (!class_exists('WooCommerce')) {
            return array();
        }
        
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(p.post_date) as date,
                COUNT(p.ID) as orders,
                COALESCE(SUM(pm.meta_value), 0) as total
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_order_total'
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-completed', 'wc-processing')
            AND p.post_date >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(p.post_date)
            ORDER BY date ASC
        ", $days));
        
        return array_map(function($row) {
            return array(
                'date' => $row->date,
                'orders' => intval($row->orders),
                'total' => floatval($row->total),
            );
        }, $results);
    }
}
