<?php
/**
 * Users API
 * Handles user management endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Dashboard_Pro_Users_API extends WP_Dashboard_Pro_API_Base {
    
    /**
     * Register routes
     */
    public function register_routes() {
        // Get users
        $this->register_route('/users', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_users'),
            'permission_callback' => $this->check_permission('list_users'),
        ));
        
        // Get single user
        $this->register_route('/users/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user'),
            'permission_callback' => array($this, 'authenticate_request'),
        ));
        
        // Create user
        $this->register_route('/users', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_user'),
            'permission_callback' => $this->check_permission('create_users'),
        ));
        
        // Update user
        $this->register_route('/users/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_user'),
            'permission_callback' => array($this, 'can_edit_user'),
        ));
        
        // Delete user
        $this->register_route('/users/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_user'),
            'permission_callback' => $this->check_permission('delete_users'),
        ));
        
        // Update user role
        $this->register_route('/users/(?P<id>\d+)/role', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_user_role'),
            'permission_callback' => $this->check_permission('promote_users'),
        ));
    }
    
    /**
     * Get users
     */
    public function get_users($request) {
        $pagination = $this->get_pagination_params($request);
        $role = $request->get_param('role');
        $search = $request->get_param('search');
        
        $args = array(
            'number' => $pagination['per_page'],
            'offset' => ($pagination['page'] - 1) * $pagination['per_page'],
            'orderby' => $pagination['orderby'] === 'created_at' ? 'registered' : $pagination['orderby'],
            'order' => $pagination['order'],
        );
        
        if ($role) {
            $args['role'] = $role;
        }
        
        if ($search) {
            $args['search'] = '*' . $search . '*';
            $args['search_columns'] = array('user_login', 'user_email', 'display_name');
        }
        
        // Get users
        $user_query = new WP_User_Query($args);
        $users = $user_query->get_results();
        $total = $user_query->get_total();
        
        // Format users
        $formatted_users = array_map(array($this, 'format_user'), $users);
        
        return $this->success_response(
            $this->format_pagination_response(
                $formatted_users,
                $total,
                $pagination['page'],
                $pagination['per_page']
            )
        );
    }
    
    /**
     * Get single user
     */
    public function get_user($request) {
        $user_id = $request['id'];
        $current_user = $this->auth->get_current_user();
        
        // Users can only view their own profile unless they're admin
        if ($user_id != $current_user->ID && !user_can($current_user, 'list_users')) {
            return $this->error_response('You do not have permission to view this user', 403);
        }
        
        $user = get_user_by('ID', $user_id);
        
        if (!$user) {
            return $this->error_response('User not found', 404);
        }
        
        return $this->success_response($this->format_user($user, true));
    }
    
    /**
     * Create user
     */
    public function create_user($request) {
        $username = $this->sanitize_string($request->get_param('username'));
        $email = $this->sanitize_email($request->get_param('email'));
        $password = $request->get_param('password');
        $first_name = $this->sanitize_string($request->get_param('first_name'));
        $last_name = $this->sanitize_string($request->get_param('last_name'));
        $role = $this->sanitize_string($request->get_param('role')) ?: 'customer';
        
        // Validate
        $validation = $this->validate_required_params($request, array('username', 'email', 'password'));
        if (is_wp_error($validation)) {
            return $this->error_response($validation->get_error_message(), 400);
        }
        
        if (!is_email($email)) {
            return $this->error_response('Invalid email address', 400);
        }
        
        if (username_exists($username)) {
            return $this->error_response('Username already exists', 400);
        }
        
        if (email_exists($email)) {
            return $this->error_response('Email already exists', 400);
        }
        
        // Create user
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            return $this->error_response($user_id->get_error_message(), 400);
        }
        
        // Update user meta
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'role' => $role,
        ));
        
        // Create affiliate record if role is affiliate
        if ($role === 'affiliate' && $this->settings->is_module_enabled('affiliate')) {
            $this->create_affiliate_record($user_id);
        }
        
        // Log activity
        $this->log_activity('create_user', "Created user: $username", 'user', $user_id);
        
        $user = get_user_by('ID', $user_id);
        
        return $this->success_response($this->format_user($user), 'User created successfully', 201);
    }
    
    /**
     * Update user
     */
    public function update_user($request) {
        $user_id = $request['id'];
        $current_user = $this->auth->get_current_user();
        
        // Check permissions
        if ($user_id != $current_user->ID && !user_can($current_user, 'edit_users')) {
            return $this->error_response('You do not have permission to edit this user', 403);
        }
        
        $user = get_user_by('ID', $user_id);
        
        if (!$user) {
            return $this->error_response('User not found', 404);
        }
        
        // Prepare update data
        $update_data = array('ID' => $user_id);
        
        if ($request->has_param('email')) {
            $email = $this->sanitize_email($request->get_param('email'));
            if (!is_email($email)) {
                return $this->error_response('Invalid email address', 400);
            }
            $update_data['user_email'] = $email;
        }
        
        if ($request->has_param('first_name')) {
            $update_data['first_name'] = $this->sanitize_string($request->get_param('first_name'));
        }
        
        if ($request->has_param('last_name')) {
            $update_data['last_name'] = $this->sanitize_string($request->get_param('last_name'));
        }
        
        if ($request->has_param('display_name')) {
            $update_data['display_name'] = $this->sanitize_string($request->get_param('display_name'));
        }
        
        if ($request->has_param('password') && !empty($request->get_param('password'))) {
            $update_data['user_pass'] = $request->get_param('password');
        }
        
        // Update user
        $result = wp_update_user($update_data);
        
        if (is_wp_error($result)) {
            return $this->error_response($result->get_error_message(), 400);
        }
        
        // Log activity
        $this->log_activity('update_user', "Updated user: {$user->user_login}", 'user', $user_id);
        
        $updated_user = get_user_by('ID', $user_id);
        
        return $this->success_response($this->format_user($updated_user), 'User updated successfully');
    }
    
    /**
     * Delete user
     */
    public function delete_user($request) {
        $user_id = $request['id'];
        $current_user = $this->auth->get_current_user();
        
        // Can't delete yourself
        if ($user_id == $current_user->ID) {
            return $this->error_response('You cannot delete your own account', 400);
        }
        
        $user = get_user_by('ID', $user_id);
        
        if (!$user) {
            return $this->error_response('User not found', 404);
        }
        
        // Delete user
        require_once(ABSPATH . 'wp-admin/includes/user.php');
        $result = wp_delete_user($user_id);
        
        if (!$result) {
            return $this->error_response('Failed to delete user', 500);
        }
        
        // Log activity
        $this->log_activity('delete_user', "Deleted user: {$user->user_login}", 'user', $user_id);
        
        return $this->success_response(null, 'User deleted successfully');
    }
    
    /**
     * Update user role
     */
    public function update_user_role($request) {
        $user_id = $request['id'];
        $new_role = $this->sanitize_string($request->get_param('role'));
        
        if (empty($new_role)) {
            return $this->error_response('Role is required', 400);
        }
        
        $user = get_user_by('ID', $user_id);
        
        if (!$user) {
            return $this->error_response('User not found', 404);
        }
        
        // Update role
        $user->set_role($new_role);
        
        // Create affiliate record if changing to affiliate role
        if ($new_role === 'affiliate' && $this->settings->is_module_enabled('affiliate')) {
            global $wpdb;
            $table = $wpdb->prefix . 'dashboard_pro_affiliates';
            $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE user_id = %d", $user_id));
            
            if (!$exists) {
                $this->create_affiliate_record($user_id);
            }
        }
        
        // Log activity
        $this->log_activity('update_user_role', "Changed role to: $new_role", 'user', $user_id);
        
        return $this->success_response($this->format_user($user), 'User role updated successfully');
    }
    
    /**
     * Format user for response
     */
    private function format_user($user, $detailed = false) {
        $data = array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'roles' => $user->roles,
            'avatar_url' => get_avatar_url($user->ID),
            'registered' => $user->user_registered,
        );
        
        if ($detailed) {
            $data['capabilities'] = array_keys(array_filter($user->allcaps));
            
            // Add affiliate data if applicable
            if (in_array('affiliate', $user->roles) && $this->settings->is_module_enabled('affiliate')) {
                global $wpdb;
                $table = $wpdb->prefix . 'dashboard_pro_affiliates';
                $affiliate = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table WHERE user_id = %d",
                    $user->ID
                ));
                
                if ($affiliate) {
                    $data['affiliate'] = array(
                        'id' => $affiliate->id,
                        'code' => $affiliate->affiliate_code,
                        'status' => $affiliate->status,
                        'commission_rate' => $affiliate->commission_rate,
                        'total_referrals' => $affiliate->total_referrals,
                        'total_sales' => $affiliate->total_sales,
                        'total_commissions' => $affiliate->total_commissions,
                    );
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Check if current user can edit specified user
     */
    public function can_edit_user($request) {
        $user_id = $request['id'];
        $current_user = $this->auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('unauthorized', 'You must be logged in', array('status' => 401));
        }
        
        // Users can edit themselves, or admins can edit anyone
        if ($user_id == $current_user->ID || user_can($current_user, 'edit_users')) {
            return true;
        }
        
        return new WP_Error('forbidden', 'You do not have permission to edit this user', array('status' => 403));
    }
    
    /**
     * Create affiliate record
     */
    private function create_affiliate_record($user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'dashboard_pro_affiliates';
        
        // Generate unique affiliate code
        $user = get_user_by('ID', $user_id);
        $base_code = strtoupper(substr($user->user_login, 0, 6) . $user_id);
        
        $code = $base_code;
        $counter = 1;
        
        while ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE affiliate_code = %s", $code)) > 0) {
            $code = $base_code . $counter;
            $counter++;
        }
        
        $status = $this->settings->get('affiliate_approval_required') ? 'pending' : 'active';
        $commission_rate = $this->settings->get('affiliate_default_commission_rate', 10);
        
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
    }
}
