<?php
/**
 * Withdrawal API
 * Handles withdrawal request endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Dashboard_Pro_Withdrawal_API extends WP_Dashboard_Pro_API_Base {
    
    /**
     * Register routes
     */
    public function register_routes() {
        // Get withdrawals
        $this->register_route('/withdrawals', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_withdrawals'),
            'permission_callback' => array($this, 'authenticate_request'),
        ));
        
        // Get single withdrawal
        $this->register_route('/withdrawals/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_withdrawal'),
            'permission_callback' => array($this, 'can_view_withdrawal'),
        ));
        
        // Request withdrawal
        $this->register_route('/withdrawals', array(
            'methods' => 'POST',
            'callback' => array($this, 'request_withdrawal'),
            'permission_callback' => array($this, 'check_affiliate_permission'),
        ));
        
        // Update withdrawal
        $this->register_route('/withdrawals/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_withdrawal'),
            'permission_callback' => $this->check_permission('process_withdrawals'),
        ));
        
        // Approve withdrawal
        $this->register_route('/withdrawals/(?P<id>\d+)/approve', array(
            'methods' => 'POST',
            'callback' => array($this, 'approve_withdrawal'),
            'permission_callback' => $this->check_permission('process_withdrawals'),
        ));
        
        // Reject withdrawal
        $this->register_route('/withdrawals/(?P<id>\d+)/reject', array(
            'methods' => 'POST',
            'callback' => array($this, 'reject_withdrawal'),
            'permission_callback' => $this->check_permission('process_withdrawals'),
        ));
        
        // Mark as paid
        $this->register_route('/withdrawals/(?P<id>\d+)/pay', array(
            'methods' => 'POST',
            'callback' => array($this, 'mark_as_paid'),
            'permission_callback' => $this->check_permission('process_withdrawals'),
        ));
    }
    
    /**
     * Get withdrawals
     */
    public function get_withdrawals($request) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'dashboard_pro_withdrawals';
        $pagination = $this->get_pagination_params($request);
        $user = $this->auth->get_current_user();
        
        $status = $request->get_param('status');
        $affiliate_id = $request->get_param('affiliate_id');
        
        // Build where clause
        $where = array('1=1');
        
        // Non-admins can only see their own withdrawals
        if (!user_can($user, 'process_withdrawals')) {
            $affiliate_table = $wpdb->prefix . 'dashboard_pro_affiliates';
            $my_affiliate = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $affiliate_table WHERE user_id = %d",
                $user->ID
            ));
            
            if (!$my_affiliate) {
                return $this->success_response($this->format_pagination_response(array(), 0, 1, 20));
            }
            
            $where[] = $wpdb->prepare('w.affiliate_id = %d', $my_affiliate->id);
        } elseif ($affiliate_id) {
            $where[] = $wpdb->prepare('w.affiliate_id = %d', $affiliate_id);
        }
        
        if ($status) {
            $where[] = $wpdb->prepare('w.status = %s', $status);
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Get total count
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table w WHERE $where_clause");
        
        // Get withdrawals
        $offset = ($pagination['page'] - 1) * $pagination['per_page'];
        $withdrawals = $wpdb->get_results($wpdb->prepare("
            SELECT w.*, a.affiliate_code, u.display_name as affiliate_name
            FROM $table w
            LEFT JOIN {$wpdb->prefix}dashboard_pro_affiliates a ON w.affiliate_id = a.id
            LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
            WHERE $where_clause
            ORDER BY w.requested_at DESC
            LIMIT %d OFFSET %d
        ", $pagination['per_page'], $offset));
        
        $formatted_withdrawals = array_map(array($this, 'format_withdrawal'), $withdrawals);
        
        return $this->success_response(
            $this->format_pagination_response(
                $formatted_withdrawals,
                $total,
                $pagination['page'],
                $pagination['per_page']
            )
        );
    }
    
    /**
     * Get single withdrawal
     */
    public function get_withdrawal($request) {
        global $wpdb;
        
        $withdrawal_id = $request['id'];
        $table = $wpdb->prefix . 'dashboard_pro_withdrawals';
        
        $withdrawal = $wpdb->get_row($wpdb->prepare("
            SELECT w.*, a.affiliate_code, u.display_name as affiliate_name, u.user_email as affiliate_email
            FROM $table w
            LEFT JOIN {$wpdb->prefix}dashboard_pro_affiliates a ON w.affiliate_id = a.id
            LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
            WHERE w.id = %d
        ", $withdrawal_id));
        
        if (!$withdrawal) {
            return $this->error_response('Withdrawal not found', 404);
        }
        
        return $this->success_response($this->format_withdrawal($withdrawal, true));
    }
    
    /**
     * Request withdrawal
     */
    public function request_withdrawal($request) {
        global $wpdb;
        
        $amount = $this->sanitize_decimal($request->get_param('amount'));
        $method = $this->sanitize_string($request->get_param('method'));
        $payment_details = sanitize_textarea_field($request->get_param('payment_details'));
        
        // Validate
        $validation = $this->validate_required_params($request, array('amount', 'method'));
        if (is_wp_error($validation)) {
            return $this->error_response($validation->get_error_message(), 400);
        }
        
        // Get affiliate
        $user_id = $this->get_current_user_id();
        $affiliate_table = $wpdb->prefix . 'dashboard_pro_affiliates';
        $affiliate = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $affiliate_table WHERE user_id = %d",
            $user_id
        ));
        
        if (!$affiliate) {
            return $this->error_response('Affiliate profile not found', 404);
        }
        
        // Check minimum withdrawal amount
        $min_withdrawal = $this->settings->get('affiliate_minimum_withdrawal', 50);
        if ($amount < $min_withdrawal) {
            return $this->error_response("Minimum withdrawal amount is $min_withdrawal", 400);
        }
        
        // Check available balance
        if ($amount > $affiliate->pending_commissions) {
            return $this->error_response('Insufficient balance', 400);
        }
        
        // Create withdrawal request
        $table = $wpdb->prefix . 'dashboard_pro_withdrawals';
        $result = $wpdb->insert(
            $table,
            array(
                'affiliate_id' => $affiliate->id,
                'amount' => $amount,
                'method' => $method,
                'payment_details' => $payment_details,
                'status' => 'pending',
                'requested_at' => current_time('mysql'),
            ),
            array('%d', '%f', '%s', '%s', '%s', '%s')
        );
        
        if (!$result) {
            return $this->error_response('Failed to create withdrawal request', 500);
        }
        
        $withdrawal_id = $wpdb->insert_id;
        
        $this->log_activity('request_withdrawal', "Requested withdrawal of $amount", 'withdrawal', $withdrawal_id);
        
        // Send notification
        if ($this->settings->get('email_notifications.withdrawal_requested')) {
            do_action('wp_dashboard_pro_withdrawal_requested', $withdrawal_id);
        }
        
        return $this->success_response(
            array('id' => $withdrawal_id),
            'Withdrawal request submitted successfully',
            201
        );
    }
    
    /**
     * Update withdrawal
     */
    public function update_withdrawal($request) {
        global $wpdb;
        
        $withdrawal_id = $request['id'];
        $table = $wpdb->prefix . 'dashboard_pro_withdrawals';
        
        $withdrawal = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $withdrawal_id));
        
        if (!$withdrawal) {
            return $this->error_response('Withdrawal not found', 404);
        }
        
        // Prepare update data
        $update_data = array();
        
        if ($request->has_param('notes')) {
            $update_data['notes'] = sanitize_textarea_field($request->get_param('notes'));
        }
        
        if ($request->has_param('transaction_id')) {
            $update_data['transaction_id'] = $this->sanitize_string($request->get_param('transaction_id'));
        }
        
        if (empty($update_data)) {
            return $this->error_response('No data to update', 400);
        }
        
        $result = $wpdb->update($table, $update_data, array('id' => $withdrawal_id));
        
        if ($result === false) {
            return $this->error_response('Failed to update withdrawal', 500);
        }
        
        $this->log_activity('update_withdrawal', 'Updated withdrawal', 'withdrawal', $withdrawal_id);
        
        return $this->success_response(null, 'Withdrawal updated successfully');
    }
    
    /**
     * Approve withdrawal
     */
    public function approve_withdrawal($request) {
        global $wpdb;
        
        $withdrawal_id = $request['id'];
        $table = $wpdb->prefix . 'dashboard_pro_withdrawals';
        
        $withdrawal = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $withdrawal_id));
        
        if (!$withdrawal) {
            return $this->error_response('Withdrawal not found', 404);
        }
        
        if ($withdrawal->status !== 'pending') {
            return $this->error_response('Withdrawal is not pending', 400);
        }
        
        $wpdb->update(
            $table,
            array('status' => 'approved'),
            array('id' => $withdrawal_id)
        );
        
        $this->log_activity('approve_withdrawal', 'Approved withdrawal', 'withdrawal', $withdrawal_id);
        
        // Send notification
        if ($this->settings->get('email_notifications.withdrawal_approved')) {
            do_action('wp_dashboard_pro_withdrawal_approved', $withdrawal);
        }
        
        return $this->success_response(null, 'Withdrawal approved successfully');
    }
    
    /**
     * Reject withdrawal
     */
    public function reject_withdrawal($request) {
        global $wpdb;
        
        $withdrawal_id = $request['id'];
        $reason = sanitize_textarea_field($request->get_param('reason'));
        $table = $wpdb->prefix . 'dashboard_pro_withdrawals';
        
        $withdrawal = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $withdrawal_id));
        
        if (!$withdrawal) {
            return $this->error_response('Withdrawal not found', 404);
        }
        
        $wpdb->update(
            $table,
            array(
                'status' => 'rejected',
                'notes' => $reason,
            ),
            array('id' => $withdrawal_id)
        );
        
        $this->log_activity('reject_withdrawal', 'Rejected withdrawal', 'withdrawal', $withdrawal_id);
        
        // Send notification
        if ($this->settings->get('email_notifications.withdrawal_rejected')) {
            do_action('wp_dashboard_pro_withdrawal_rejected', $withdrawal, $reason);
        }
        
        return $this->success_response(null, 'Withdrawal rejected');
    }
    
    /**
     * Mark withdrawal as paid
     */
    public function mark_as_paid($request) {
        global $wpdb;
        
        $withdrawal_id = $request['id'];
        $transaction_id = $this->sanitize_string($request->get_param('transaction_id'));
        $table = $wpdb->prefix . 'dashboard_pro_withdrawals';
        
        $withdrawal = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $withdrawal_id));
        
        if (!$withdrawal) {
            return $this->error_response('Withdrawal not found', 404);
        }
        
        // Update withdrawal
        $wpdb->update(
            $table,
            array(
                'status' => 'paid',
                'transaction_id' => $transaction_id,
                'processed_at' => current_time('mysql'),
                'processed_by' => $this->get_current_user_id(),
            ),
            array('id' => $withdrawal_id)
        );
        
        // Update related commissions to paid
        $commission_table = $wpdb->prefix . 'dashboard_pro_commissions';
        $wpdb->query($wpdb->prepare("
            UPDATE $commission_table 
            SET status = 'paid', paid_date = NOW()
            WHERE affiliate_id = %d 
            AND status = 'approved'
            AND amount <= %f
        ", $withdrawal->affiliate_id, $withdrawal->amount));
        
        // Update affiliate balance
        $affiliate_table = $wpdb->prefix . 'dashboard_pro_affiliates';
        $wpdb->query($wpdb->prepare("
            UPDATE $affiliate_table
            SET pending_commissions = pending_commissions - %f,
                paid_commissions = paid_commissions + %f
            WHERE id = %d
        ", $withdrawal->amount, $withdrawal->amount, $withdrawal->affiliate_id));
        
        $this->log_activity('pay_withdrawal', 'Marked withdrawal as paid', 'withdrawal', $withdrawal_id);
        
        return $this->success_response(null, 'Withdrawal marked as paid');
    }
    
    /**
     * Format withdrawal for response
     */
    private function format_withdrawal($withdrawal, $detailed = false) {
        $data = array(
            'id' => intval($withdrawal->id),
            'affiliate_id' => intval($withdrawal->affiliate_id),
            'affiliate_code' => $withdrawal->affiliate_code,
            'affiliate_name' => $withdrawal->affiliate_name,
            'amount' => floatval($withdrawal->amount),
            'method' => $withdrawal->method,
            'status' => $withdrawal->status,
            'requested_at' => $withdrawal->requested_at,
        );
        
        if ($detailed) {
            $data['payment_details'] = $withdrawal->payment_details;
            $data['transaction_id'] = $withdrawal->transaction_id;
            $data['notes'] = $withdrawal->notes;
            $data['processed_at'] = $withdrawal->processed_at;
            $data['affiliate_email'] = $withdrawal->affiliate_email ?? null;
        }
        
        return $data;
    }
    
    /**
     * Check if user can view withdrawal
     */
    public function can_view_withdrawal($request) {
        $withdrawal_id = $request['id'];
        $user = $this->auth->get_current_user();
        
        if (!$user) {
            return new WP_Error('unauthorized', 'You must be logged in', array('status' => 401));
        }
        
        // Admins can view all
        if (user_can($user, 'process_withdrawals')) {
            return true;
        }
        
        // Affiliates can view their own
        global $wpdb;
        $table = $wpdb->prefix . 'dashboard_pro_withdrawals';
        $affiliate_table = $wpdb->prefix . 'dashboard_pro_affiliates';
        
        $withdrawal = $wpdb->get_row($wpdb->prepare(
            "SELECT w.affiliate_id, a.user_id 
            FROM $table w
            LEFT JOIN $affiliate_table a ON w.affiliate_id = a.id
            WHERE w.id = %d",
            $withdrawal_id
        ));
        
        if ($withdrawal && $withdrawal->user_id == $user->ID) {
            return true;
        }
        
        return new WP_Error('forbidden', 'You do not have permission', array('status' => 403));
    }
}
