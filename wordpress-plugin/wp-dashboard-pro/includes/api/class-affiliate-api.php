<?php
/**
 * Affiliate API
 * Handles affiliate management endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Dashboard_Pro_Affiliate_API extends WP_Dashboard_Pro_API_Base {
    
    /**
     * Register routes
     */
    public function register_routes() {
        // Get affiliates (admin)
        $this->register_route('/affiliates', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_affiliates'),
            'permission_callback' => $this->check_permission('manage_affiliates'),
        ));
        
        // Get single affiliate
        $this->register_route('/affiliates/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_affiliate'),
            'permission_callback' => array($this, 'can_view_affiliate'),
        ));
        
        // Get current user's affiliate profile
        $this->register_route('/affiliates/me', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_my_affiliate'),
            'permission_callback' => array($this, 'check_affiliate_permission'),
        ));
        
        // Update affiliate
        $this->register_route('/affiliates/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_affiliate'),
            'permission_callback' => array($this, 'can_edit_affiliate'),
        ));
        
        // Approve affiliate
        $this->register_route('/affiliates/(?P<id>\d+)/approve', array(
            'methods' => 'POST',
            'callback' => array($this, 'approve_affiliate'),
            'permission_callback' => $this->check_permission('approve_affiliates'),
        ));
        
        // Reject affiliate
        $this->register_route('/affiliates/(?P<id>\d+)/reject', array(
            'methods' => 'POST',
            'callback' => array($this, 'reject_affiliate'),
            'permission_callback' => $this->check_permission('approve_affiliates'),
        ));
        
        // Get affiliate links
        $this->register_route('/affiliates/(?P<id>\d+)/links', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_affiliate_links'),
            'permission_callback' => array($this, 'can_view_affiliate'),
        ));
        
        // Get affiliate stats
        $this->register_route('/affiliates/(?P<id>\d+)/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_affiliate_stats'),
            'permission_callback' => array($this, 'can_view_affiliate'),
        ));
        
        // Apply to become affiliate
        $this->register_route('/affiliates/apply', array(
            'methods' => 'POST',
            'callback' => array($this, 'apply'),
            'permission_callback' => array($this, 'authenticate_request'),
        ));
    }
    
    /**
     * Get all affiliates
     */
    public function get_affiliates($request) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'dashboard_pro_affiliates';
        $pagination = $this->get_pagination_params($request);
        $status = $request->get_param('status');
        $search = $request->get_param('search');
        
        // Build where clause
        $where = array('1=1');
        
        if ($status) {
            $where[] = $wpdb->prepare('a.status = %s', $status);
        }
        
        if ($search) {
            $where[] = $wpdb->prepare(
                '(u.user_login LIKE %s OR u.user_email LIKE %s OR a.affiliate_code LIKE %s)',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Get total count
        $total = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM $table a
            LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
            WHERE $where_clause
        ");
        
        // Get affiliates
        $offset = ($pagination['page'] - 1) * $pagination['per_page'];
        $affiliates = $wpdb->get_results($wpdb->prepare("
            SELECT a.*, u.user_login, u.user_email, u.display_name
            FROM $table a
            LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
            WHERE $where_clause
            ORDER BY a.{$pagination['orderby']} {$pagination['order']}
            LIMIT %d OFFSET %d
        ", $pagination['per_page'], $offset));
        
        $formatted_affiliates = array_map(array($this, 'format_affiliate'), $affiliates);
        
        return $this->success_response(
            $this->format_pagination_response(
                $formatted_affiliates,
                $total,
                $pagination['page'],
                $pagination['per_page']
            )
        );
    }
    
    /**
     * Get single affiliate
     */
    public function get_affiliate($request) {
        global $wpdb;
        
        $affiliate_id = $request['id'];
        $table = $wpdb->prefix . 'dashboard_pro_affiliates';
        
        $affiliate = $wpdb->get_row($wpdb->prepare("
            SELECT a.*, u.user_login, u.user_email, u.display_name
            FROM $table a
            LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
            WHERE a.id = %d
        ", $affiliate_id));
        
        if (!$affiliate) {
            return $this->error_response('Affiliate not found', 404);
        }
        
        return $this->success_response($this->format_affiliate($affiliate, true));
    }
    
    /**
     * Get current user's affiliate profile
     */
    public function get_my_affiliate($request) {
        global $wpdb;
        
        $user_id = $this->get_current_user_id();
        $table = $wpdb->prefix . 'dashboard_pro_affiliates';
        
        $affiliate = $wpdb->get_row($wpdb->prepare("
            SELECT a.*, u.user_login, u.user_email, u.display_name
            FROM $table a
            LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
            WHERE a.user_id = %d
        ", $user_id));
        
        if (!$affiliate) {
            return $this->error_response('Affiliate profile not found', 404);
        }
        
        return $this->success_response($this->format_affiliate($affiliate, true));
    }
    
    /**
     * Update affiliate
     */
    public function update_affiliate($request) {
        global $wpdb;
        
        $affiliate_id = $request['id'];
        $table = $wpdb->prefix . 'dashboard_pro_affiliates';
        
        $affiliate = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $affiliate_id));
        
        if (!$affiliate) {
            return $this->error_response('Affiliate not found', 404);
        }
        
        // Prepare update data
        $update_data = array();
        
        if ($request->has_param('commission_rate')) {
            $update_data['commission_rate'] = $this->sanitize_decimal($request->get_param('commission_rate'));
        }
        
        if ($request->has_param('payment_email')) {
            $update_data['payment_email'] = $this->sanitize_email($request->get_param('payment_email'));
        }
        
        if ($request->has_param('payment_method')) {
            $update_data['payment_method'] = $this->sanitize_string($request->get_param('payment_method'));
        }
        
        if ($request->has_param('payment_details')) {
            $update_data['payment_details'] = sanitize_textarea_field($request->get_param('payment_details'));
        }
        
        if ($request->has_param('notes')) {
            $update_data['notes'] = sanitize_textarea_field($request->get_param('notes'));
        }
        
        if (empty($update_data)) {
            return $this->error_response('No data to update', 400);
        }
        
        // Update
        $result = $wpdb->update($table, $update_data, array('id' => $affiliate_id));
        
        if ($result === false) {
            return $this->error_response('Failed to update affiliate', 500);
        }
        
        $this->log_activity('update_affiliate', 'Updated affiliate', 'affiliate', $affiliate_id);
        
        return $this->success_response(null, 'Affiliate updated successfully');
    }
    
    /**
     * Approve affiliate
     */
    public function approve_affiliate($request) {
        global $wpdb;
        
        $affiliate_id = $request['id'];
        $table = $wpdb->prefix . 'dashboard_pro_affiliates';
        
        $affiliate = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $affiliate_id));
        
        if (!$affiliate) {
            return $this->error_response('Affiliate not found', 404);
        }
        
        if ($affiliate->status === 'active') {
            return $this->error_response('Affiliate is already approved', 400);
        }
        
        // Update status
        $wpdb->update(
            $table,
            array(
                'status' => 'active',
                'approved_by' => $this->get_current_user_id(),
                'approved_at' => current_time('mysql'),
            ),
            array('id' => $affiliate_id)
        );
        
        $this->log_activity('approve_affiliate', 'Approved affiliate', 'affiliate', $affiliate_id);
        
        // Send notification email if enabled
        if ($this->settings->get('email_notifications.affiliate_approved')) {
            do_action('wp_dashboard_pro_affiliate_approved', $affiliate);
        }
        
        return $this->success_response(null, 'Affiliate approved successfully');
    }
    
    /**
     * Reject affiliate
     */
    public function reject_affiliate($request) {
        global $wpdb;
        
        $affiliate_id = $request['id'];
        $reason = sanitize_textarea_field($request->get_param('reason'));
        $table = $wpdb->prefix . 'dashboard_pro_affiliates';
        
        $affiliate = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $affiliate_id));
        
        if (!$affiliate) {
            return $this->error_response('Affiliate not found', 404);
        }
        
        // Update status
        $wpdb->update(
            $table,
            array(
                'status' => 'rejected',
                'notes' => $reason,
            ),
            array('id' => $affiliate_id)
        );
        
        $this->log_activity('reject_affiliate', 'Rejected affiliate', 'affiliate', $affiliate_id);
        
        // Send notification email if enabled
        if ($this->settings->get('email_notifications.affiliate_rejected')) {
            do_action('wp_dashboard_pro_affiliate_rejected', $affiliate, $reason);
        }
        
        return $this->success_response(null, 'Affiliate rejected');
    }
    
    /**
     * Get affiliate links
     */
    public function get_affiliate_links($request) {
        global $wpdb;
        
        $affiliate_id = $request['id'];
        $table = $wpdb->prefix . 'dashboard_pro_affiliates';
        
        $affiliate = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $affiliate_id));
        
        if (!$affiliate) {
            return $this->error_response('Affiliate not found', 404);
        }
        
        $site_url = get_site_url();
        $affiliate_code = $affiliate->affiliate_code;
        
        // Generate common affiliate links
        $links = array(
            array(
                'name' => 'Homepage',
                'url' => "$site_url?ref=$affiliate_code",
                'description' => 'Main website link',
            ),
            array(
                'name' => 'Shop',
                'url' => "$site_url/shop?ref=$affiliate_code",
                'description' => 'Shop page link',
            ),
        );
        
        // Add product links if WooCommerce is active
        if (class_exists('WooCommerce')) {
            $products = wc_get_products(array('limit' => 10, 'status' => 'publish'));
            
            foreach ($products as $product) {
                $links[] = array(
                    'name' => $product->get_name(),
                    'url' => get_permalink($product->get_id()) . "?ref=$affiliate_code",
                    'description' => 'Product link',
                    'product_id' => $product->get_id(),
                );
            }
        }
        
        return $this->success_response($links);
    }
    
    /**
     * Get affiliate statistics
     */
    public function get_affiliate_stats($request) {
        global $wpdb;
        
        $affiliate_id = $request['id'];
        $period = $request->get_param('period') ?: '30days';
        
        $table = $wpdb->prefix . 'dashboard_pro_affiliates';
        $affiliate = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $affiliate_id));
        
        if (!$affiliate) {
            return $this->error_response('Affiliate not found', 404);
        }
        
        // Calculate date range
        $date_filter = $this->get_date_filter($period);
        
        // Get referrals
        $referral_table = $wpdb->prefix . 'dashboard_pro_referrals';
        $referral_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_visits,
                SUM(converted) as conversions
            FROM $referral_table
            WHERE affiliate_id = %d $date_filter
        ", $affiliate_id));
        
        // Get commissions
        $commission_table = $wpdb->prefix . 'dashboard_pro_commissions';
        $commission_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_commissions,
                COALESCE(SUM(amount), 0) as total_earnings,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as pending,
                COALESCE(SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END), 0) as approved,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) as paid
            FROM $commission_table
            WHERE affiliate_id = %d $date_filter
        ", $affiliate_id));
        
        return $this->success_response(array(
            'period' => $period,
            'visits' => intval($referral_stats->total_visits),
            'conversions' => intval($referral_stats->conversions),
            'conversion_rate' => $referral_stats->total_visits > 0 
                ? round(($referral_stats->conversions / $referral_stats->total_visits) * 100, 2) 
                : 0,
            'total_commissions' => intval($commission_stats->total_commissions),
            'total_earnings' => floatval($commission_stats->total_earnings),
            'pending' => floatval($commission_stats->pending),
            'approved' => floatval($commission_stats->approved),
            'paid' => floatval($commission_stats->paid),
        ));
    }
    
    /**
     * Apply to become affiliate
     */
    public function apply($request) {
        global $wpdb;
        
        $user_id = $this->get_current_user_id();
        $user = get_user_by('ID', $user_id);
        
        // Check if already an affiliate
        $table = $wpdb->prefix . 'dashboard_pro_affiliates';
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE user_id = %d", $user_id));
        
        if ($exists) {
            return $this->error_response('You are already an affiliate', 400);
        }
        
        // Generate affiliate code
        $base_code = strtoupper(substr($user->user_login, 0, 6) . $user_id);
        $code = $base_code;
        $counter = 1;
        
        while ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE affiliate_code = %s", $code)) > 0) {
            $code = $base_code . $counter;
            $counter++;
        }
        
        $status = $this->settings->get('affiliate_approval_required') ? 'pending' : 'active';
        $commission_rate = $this->settings->get('affiliate_default_commission_rate', 10);
        
        // Create affiliate record
        $wpdb->insert(
            $table,
            array(
                'user_id' => $user_id,
                'affiliate_code' => $code,
                'commission_rate' => $commission_rate,
                'status' => $status,
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%f', '%s', '%s')
        );
        
        // Add affiliate role
        $user->add_role('affiliate');
        
        $this->log_activity('apply_affiliate', 'Applied to become affiliate');
        
        $message = $status === 'pending' 
            ? 'Your affiliate application has been submitted and is pending approval' 
            : 'Your affiliate account has been created successfully';
        
        return $this->success_response(array('status' => $status, 'code' => $code), $message);
    }
    
    /**
     * Format affiliate for response
     */
    private function format_affiliate($affiliate, $detailed = false) {
        $data = array(
            'id' => intval($affiliate->id),
            'user_id' => intval($affiliate->user_id),
            'username' => $affiliate->user_login,
            'email' => $affiliate->user_email,
            'display_name' => $affiliate->display_name,
            'affiliate_code' => $affiliate->affiliate_code,
            'commission_rate' => floatval($affiliate->commission_rate),
            'status' => $affiliate->status,
            'total_referrals' => intval($affiliate->total_referrals),
            'total_sales' => floatval($affiliate->total_sales),
            'total_commissions' => floatval($affiliate->total_commissions),
            'pending_commissions' => floatval($affiliate->pending_commissions),
            'paid_commissions' => floatval($affiliate->paid_commissions),
            'created_at' => $affiliate->created_at,
        );
        
        if ($detailed) {
            $data['payment_email'] = $affiliate->payment_email;
            $data['payment_method'] = $affiliate->payment_method;
            $data['payment_details'] = $affiliate->payment_details;
            $data['notes'] = $affiliate->notes;
            $data['approved_at'] = $affiliate->approved_at;
        }
        
        return $data;
    }
    
    /**
     * Check if user can view affiliate
     */
    public function can_view_affiliate($request) {
        $affiliate_id = $request['id'];
        $user = $this->auth->get_current_user();
        
        if (!$user) {
            return new WP_Error('unauthorized', 'You must be logged in', array('status' => 401));
        }
        
        // Admins can view all
        if (user_can($user, 'manage_affiliates')) {
            return true;
        }
        
        // Affiliates can view their own
        global $wpdb;
        $table = $wpdb->prefix . 'dashboard_pro_affiliates';
        $affiliate = $wpdb->get_row($wpdb->prepare("SELECT user_id FROM $table WHERE id = %d", $affiliate_id));
        
        if ($affiliate && $affiliate->user_id == $user->ID) {
            return true;
        }
        
        return new WP_Error('forbidden', 'You do not have permission', array('status' => 403));
    }
    
    /**
     * Check if user can edit affiliate
     */
    public function can_edit_affiliate($request) {
        $affiliate_id = $request['id'];
        $user = $this->auth->get_current_user();
        
        if (!$user) {
            return new WP_Error('unauthorized', 'You must be logged in', array('status' => 401));
        }
        
        // Admins can edit all
        if (user_can($user, 'manage_affiliates')) {
            return true;
        }
        
        // Affiliates can edit their own (limited fields)
        global $wpdb;
        $table = $wpdb->prefix . 'dashboard_pro_affiliates';
        $affiliate = $wpdb->get_row($wpdb->prepare("SELECT user_id FROM $table WHERE id = %d", $affiliate_id));
        
        if ($affiliate && $affiliate->user_id == $user->ID) {
            return true;
        }
        
        return new WP_Error('forbidden', 'You do not have permission', array('status' => 403));
    }
    
    /**
     * Get date filter SQL
     */
    private function get_date_filter($period) {
        switch ($period) {
            case '7days':
                return "AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            case '30days':
                return "AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            case '90days':
                return "AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
            case 'year':
                return "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            case 'month':
                return "AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())";
            default:
                return '';
        }
    }
}
