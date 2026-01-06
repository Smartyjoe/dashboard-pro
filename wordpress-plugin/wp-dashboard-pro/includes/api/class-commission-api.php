<?php
/**
 * Commission API
 * Handles commission management endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Dashboard_Pro_Commission_API extends WP_Dashboard_Pro_API_Base {
    
    /**
     * Register routes
     */
    public function register_routes() {
        // Get commissions
        $this->register_route('/commissions', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_commissions'),
            'permission_callback' => array($this, 'authenticate_request'),
        ));
        
        // Get single commission
        $this->register_route('/commissions/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_commission'),
            'permission_callback' => array($this, 'can_view_commission'),
        ));
        
        // Create manual commission
        $this->register_route('/commissions', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_commission'),
            'permission_callback' => $this->check_permission('manage_commissions'),
        ));
        
        // Update commission
        $this->register_route('/commissions/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_commission'),
            'permission_callback' => $this->check_permission('manage_commissions'),
        ));
        
        // Approve commission
        $this->register_route('/commissions/(?P<id>\d+)/approve', array(
            'methods' => 'POST',
            'callback' => array($this, 'approve_commission'),
            'permission_callback' => $this->check_permission('manage_commissions'),
        ));
        
        // Mark as paid
        $this->register_route('/commissions/(?P<id>\d+)/pay', array(
            'methods' => 'POST',
            'callback' => array($this, 'mark_as_paid'),
            'permission_callback' => $this->check_permission('manage_commissions'),
        ));
        
        // Delete commission
        $this->register_route('/commissions/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_commission'),
            'permission_callback' => $this->check_permission('manage_commissions'),
        ));
    }
    
    /**
     * Get commissions
     */
    public function get_commissions($request) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'dashboard_pro_commissions';
        $pagination = $this->get_pagination_params($request);
        $user = $this->auth->get_current_user();
        
        $status = $request->get_param('status');
        $affiliate_id = $request->get_param('affiliate_id');
        
        // Build where clause
        $where = array('1=1');
        
        // Non-admins can only see their own commissions
        if (!user_can($user, 'manage_commissions')) {
            $affiliate_table = $wpdb->prefix . 'dashboard_pro_affiliates';
            $my_affiliate = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $affiliate_table WHERE user_id = %d",
                $user->ID
            ));
            
            if (!$my_affiliate) {
                return $this->success_response($this->format_pagination_response(array(), 0, 1, 20));
            }
            
            $where[] = $wpdb->prepare('c.affiliate_id = %d', $my_affiliate->id);
        } elseif ($affiliate_id) {
            $where[] = $wpdb->prepare('c.affiliate_id = %d', $affiliate_id);
        }
        
        if ($status) {
            $where[] = $wpdb->prepare('c.status = %s', $status);
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Get total count
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table c WHERE $where_clause");
        
        // Get commissions
        $offset = ($pagination['page'] - 1) * $pagination['per_page'];
        $commissions = $wpdb->get_results($wpdb->prepare("
            SELECT c.*, a.affiliate_code, u.display_name as affiliate_name
            FROM $table c
            LEFT JOIN {$wpdb->prefix}dashboard_pro_affiliates a ON c.affiliate_id = a.id
            LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
            WHERE $where_clause
            ORDER BY c.{$pagination['orderby']} {$pagination['order']}
            LIMIT %d OFFSET %d
        ", $pagination['per_page'], $offset));
        
        $formatted_commissions = array_map(array($this, 'format_commission'), $commissions);
        
        return $this->success_response(
            $this->format_pagination_response(
                $formatted_commissions,
                $total,
                $pagination['page'],
                $pagination['per_page']
            )
        );
    }
    
    /**
     * Get single commission
     */
    public function get_commission($request) {
        global $wpdb;
        
        $commission_id = $request['id'];
        $table = $wpdb->prefix . 'dashboard_pro_commissions';
        
        $commission = $wpdb->get_row($wpdb->prepare("
            SELECT c.*, a.affiliate_code, u.display_name as affiliate_name, u.user_email as affiliate_email
            FROM $table c
            LEFT JOIN {$wpdb->prefix}dashboard_pro_affiliates a ON c.affiliate_id = a.id
            LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
            WHERE c.id = %d
        ", $commission_id));
        
        if (!$commission) {
            return $this->error_response('Commission not found', 404);
        }
        
        return $this->success_response($this->format_commission($commission, true));
    }
    
    /**
     * Create manual commission
     */
    public function create_commission($request) {
        global $wpdb;
        
        $affiliate_id = $this->sanitize_integer($request->get_param('affiliate_id'));
        $amount = $this->sanitize_decimal($request->get_param('amount'));
        $type = $this->sanitize_string($request->get_param('type')) ?: 'manual';
        $description = sanitize_textarea_field($request->get_param('description'));
        
        // Validate
        $validation = $this->validate_required_params($request, array('affiliate_id', 'amount'));
        if (is_wp_error($validation)) {
            return $this->error_response($validation->get_error_message(), 400);
        }
        
        // Verify affiliate exists
        $affiliate_table = $wpdb->prefix . 'dashboard_pro_affiliates';
        $affiliate = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $affiliate_table WHERE id = %d",
            $affiliate_id
        ));
        
        if (!$affiliate) {
            return $this->error_response('Affiliate not found', 404);
        }
        
        // Create commission
        $table = $wpdb->prefix . 'dashboard_pro_commissions';
        $result = $wpdb->insert(
            $table,
            array(
                'affiliate_id' => $affiliate_id,
                'amount' => $amount,
                'type' => $type,
                'description' => $description,
                'status' => 'approved',
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%f', '%s', '%s', '%s', '%s')
        );
        
        if (!$result) {
            return $this->error_response('Failed to create commission', 500);
        }
        
        $commission_id = $wpdb->insert_id;
        
        // Update affiliate totals
        $this->update_affiliate_totals($affiliate_id);
        
        $this->log_activity('create_commission', 'Created manual commission', 'commission', $commission_id);
        
        return $this->success_response(
            array('id' => $commission_id),
            'Commission created successfully',
            201
        );
    }
    
    /**
     * Update commission
     */
    public function update_commission($request) {
        global $wpdb;
        
        $commission_id = $request['id'];
        $table = $wpdb->prefix . 'dashboard_pro_commissions';
        
        $commission = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $commission_id));
        
        if (!$commission) {
            return $this->error_response('Commission not found', 404);
        }
        
        // Prepare update data
        $update_data = array();
        
        if ($request->has_param('amount')) {
            $update_data['amount'] = $this->sanitize_decimal($request->get_param('amount'));
        }
        
        if ($request->has_param('status')) {
            $update_data['status'] = $this->sanitize_string($request->get_param('status'));
        }
        
        if ($request->has_param('description')) {
            $update_data['description'] = sanitize_textarea_field($request->get_param('description'));
        }
        
        if ($request->has_param('notes')) {
            $update_data['notes'] = sanitize_textarea_field($request->get_param('notes'));
        }
        
        if (empty($update_data)) {
            return $this->error_response('No data to update', 400);
        }
        
        $result = $wpdb->update($table, $update_data, array('id' => $commission_id));
        
        if ($result === false) {
            return $this->error_response('Failed to update commission', 500);
        }
        
        // Update affiliate totals
        $this->update_affiliate_totals($commission->affiliate_id);
        
        $this->log_activity('update_commission', 'Updated commission', 'commission', $commission_id);
        
        return $this->success_response(null, 'Commission updated successfully');
    }
    
    /**
     * Approve commission
     */
    public function approve_commission($request) {
        global $wpdb;
        
        $commission_id = $request['id'];
        $table = $wpdb->prefix . 'dashboard_pro_commissions';
        
        $commission = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $commission_id));
        
        if (!$commission) {
            return $this->error_response('Commission not found', 404);
        }
        
        if ($commission->status === 'approved' || $commission->status === 'paid') {
            return $this->error_response('Commission is already approved/paid', 400);
        }
        
        $wpdb->update(
            $table,
            array('status' => 'approved'),
            array('id' => $commission_id)
        );
        
        // Update affiliate totals
        $this->update_affiliate_totals($commission->affiliate_id);
        
        $this->log_activity('approve_commission', 'Approved commission', 'commission', $commission_id);
        
        // Send notification
        if ($this->settings->get('email_notifications.commission_earned')) {
            do_action('wp_dashboard_pro_commission_approved', $commission);
        }
        
        return $this->success_response(null, 'Commission approved successfully');
    }
    
    /**
     * Mark commission as paid
     */
    public function mark_as_paid($request) {
        global $wpdb;
        
        $commission_id = $request['id'];
        $table = $wpdb->prefix . 'dashboard_pro_commissions';
        
        $commission = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $commission_id));
        
        if (!$commission) {
            return $this->error_response('Commission not found', 404);
        }
        
        if ($commission->status === 'paid') {
            return $this->error_response('Commission is already paid', 400);
        }
        
        $wpdb->update(
            $table,
            array(
                'status' => 'paid',
                'paid_date' => current_time('mysql'),
            ),
            array('id' => $commission_id)
        );
        
        // Update affiliate totals
        $this->update_affiliate_totals($commission->affiliate_id);
        
        $this->log_activity('pay_commission', 'Marked commission as paid', 'commission', $commission_id);
        
        return $this->success_response(null, 'Commission marked as paid');
    }
    
    /**
     * Delete commission
     */
    public function delete_commission($request) {
        global $wpdb;
        
        $commission_id = $request['id'];
        $table = $wpdb->prefix . 'dashboard_pro_commissions';
        
        $commission = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $commission_id));
        
        if (!$commission) {
            return $this->error_response('Commission not found', 404);
        }
        
        $result = $wpdb->delete($table, array('id' => $commission_id));
        
        if (!$result) {
            return $this->error_response('Failed to delete commission', 500);
        }
        
        // Update affiliate totals
        $this->update_affiliate_totals($commission->affiliate_id);
        
        $this->log_activity('delete_commission', 'Deleted commission', 'commission', $commission_id);
        
        return $this->success_response(null, 'Commission deleted successfully');
    }
    
    /**
     * Format commission for response
     */
    private function format_commission($commission, $detailed = false) {
        $data = array(
            'id' => intval($commission->id),
            'affiliate_id' => intval($commission->affiliate_id),
            'affiliate_code' => $commission->affiliate_code,
            'affiliate_name' => $commission->affiliate_name,
            'amount' => floatval($commission->amount),
            'rate' => floatval($commission->rate),
            'order_total' => floatval($commission->order_total),
            'status' => $commission->status,
            'type' => $commission->type,
            'description' => $commission->description,
            'order_id' => $commission->order_id,
            'created_at' => $commission->created_at,
        );
        
        if ($detailed) {
            $data['notes'] = $commission->notes;
            $data['paid_date'] = $commission->paid_date;
            $data['due_date'] = $commission->due_date;
            $data['affiliate_email'] = $commission->affiliate_email ?? null;
        }
        
        return $data;
    }
    
    /**
     * Check if user can view commission
     */
    public function can_view_commission($request) {
        $commission_id = $request['id'];
        $user = $this->auth->get_current_user();
        
        if (!$user) {
            return new WP_Error('unauthorized', 'You must be logged in', array('status' => 401));
        }
        
        // Admins can view all
        if (user_can($user, 'manage_commissions')) {
            return true;
        }
        
        // Affiliates can view their own
        global $wpdb;
        $table = $wpdb->prefix . 'dashboard_pro_commissions';
        $affiliate_table = $wpdb->prefix . 'dashboard_pro_affiliates';
        
        $commission = $wpdb->get_row($wpdb->prepare(
            "SELECT c.affiliate_id, a.user_id 
            FROM $table c
            LEFT JOIN $affiliate_table a ON c.affiliate_id = a.id
            WHERE c.id = %d",
            $commission_id
        ));
        
        if ($commission && $commission->user_id == $user->ID) {
            return true;
        }
        
        return new WP_Error('forbidden', 'You do not have permission', array('status' => 403));
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
                COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as pending,
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
