<?php
/**
 * Authentication API
 * Handles login, token refresh, and authentication endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Dashboard_Pro_Auth_API extends WP_Dashboard_Pro_API_Base {
    
    /**
     * Register routes
     */
    public function register_routes() {
        // Login
        $this->register_route('/auth/login', array(
            'methods' => 'POST',
            'callback' => array($this, 'login'),
            'permission_callback' => '__return_true',
        ));
        
        // Refresh token
        $this->register_route('/auth/refresh', array(
            'methods' => 'POST',
            'callback' => array($this, 'refresh'),
            'permission_callback' => '__return_true',
        ));
        
        // Validate token
        $this->register_route('/auth/validate', array(
            'methods' => 'GET',
            'callback' => array($this, 'validate'),
            'permission_callback' => array($this, 'authenticate_request'),
        ));
        
        // Logout
        $this->register_route('/auth/logout', array(
            'methods' => 'POST',
            'callback' => array($this, 'logout'),
            'permission_callback' => array($this, 'authenticate_request'),
        ));
        
        // Get current user
        $this->register_route('/auth/me', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_current_user'),
            'permission_callback' => array($this, 'authenticate_request'),
        ));
        
        // Register (if enabled in settings)
        $this->register_route('/auth/register', array(
            'methods' => 'POST',
            'callback' => array($this, 'register'),
            'permission_callback' => '__return_true',
        ));
    }
    
    /**
     * Login endpoint
     */
    public function login($request) {
        $username = $request->get_param('username');
        $password = $request->get_param('password');
        
        // Validate required params
        $validation = $this->validate_required_params($request, array('username', 'password'));
        if (is_wp_error($validation)) {
            return $this->error_response($validation->get_error_message(), 400);
        }
        
        // Authenticate
        $result = $this->auth->authenticate($username, $password);
        
        if (!$result['success']) {
            return $this->error_response($result['message'], 401);
        }
        
        return $this->success_response(
            array(
                'access_token' => $result['access_token'],
                'refresh_token' => $result['refresh_token'],
                'user' => $result['user'],
                'settings' => $this->settings->export_for_frontend(),
            ),
            'Login successful'
        );
    }
    
    /**
     * Refresh token endpoint
     */
    public function refresh($request) {
        $refresh_token = $request->get_param('refresh_token');
        
        if (empty($refresh_token)) {
            return $this->error_response('Refresh token is required', 400);
        }
        
        $result = $this->auth->refresh_token($refresh_token);
        
        if (!$result['success']) {
            return $this->error_response($result['message'], 401);
        }
        
        return $this->success_response(
            array(
                'access_token' => $result['access_token'],
            ),
            'Token refreshed successfully'
        );
    }
    
    /**
     * Validate token endpoint
     */
    public function validate($request) {
        $user = $this->auth->get_current_user();
        
        return $this->success_response(
            array(
                'valid' => true,
                'user_id' => $user->ID,
            ),
            'Token is valid'
        );
    }
    
    /**
     * Logout endpoint
     */
    public function logout($request) {
        // Log activity
        $this->log_activity('logout', 'User logged out');
        
        return $this->success_response(null, 'Logout successful');
    }
    
    /**
     * Get current user endpoint
     */
    public function get_current_user($request) {
        $user = $this->auth->get_current_user();
        
        if (!$user) {
            return $this->error_response('User not found', 404);
        }
        
        $user_data = array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'roles' => $user->roles,
            'capabilities' => array_keys(array_filter($user->allcaps)),
            'avatar_url' => get_avatar_url($user->ID),
        );
        
        // Add affiliate data if user is affiliate
        if (in_array('affiliate', $user->roles)) {
            global $wpdb;
            $table = $wpdb->prefix . 'dashboard_pro_affiliates';
            $affiliate = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE user_id = %d",
                $user->ID
            ));
            
            if ($affiliate) {
                $user_data['affiliate'] = array(
                    'id' => $affiliate->id,
                    'code' => $affiliate->affiliate_code,
                    'status' => $affiliate->status,
                    'commission_rate' => $affiliate->commission_rate,
                    'total_referrals' => $affiliate->total_referrals,
                    'total_sales' => $affiliate->total_sales,
                    'total_commissions' => $affiliate->total_commissions,
                    'pending_commissions' => $affiliate->pending_commissions,
                    'paid_commissions' => $affiliate->paid_commissions,
                );
            }
        }
        
        return $this->success_response($user_data);
    }
    
    /**
     * Register endpoint
     */
    public function register($request) {
        // Check if registration is enabled
        if (!$this->settings->get('enable_registration', false)) {
            return $this->error_response('Registration is currently disabled', 403);
        }
        
        $username = $this->sanitize_string($request->get_param('username'));
        $email = $this->sanitize_email($request->get_param('email'));
        $password = $request->get_param('password');
        $first_name = $this->sanitize_string($request->get_param('first_name'));
        $last_name = $this->sanitize_string($request->get_param('last_name'));
        $register_as_affiliate = $request->get_param('register_as_affiliate');
        
        // Validate required params
        $validation = $this->validate_required_params($request, array('username', 'email', 'password'));
        if (is_wp_error($validation)) {
            return $this->error_response($validation->get_error_message(), 400);
        }
        
        // Validate email
        if (!is_email($email)) {
            return $this->error_response('Invalid email address', 400);
        }
        
        // Check if username exists
        if (username_exists($username)) {
            return $this->error_response('Username already exists', 400);
        }
        
        // Check if email exists
        if (email_exists($email)) {
            return $this->error_response('Email already exists', 400);
        }
        
        // Create user
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            return $this->error_response($user_id->get_error_message(), 400);
        }
        
        // Update user meta
        if ($first_name) {
            update_user_meta($user_id, 'first_name', $first_name);
        }
        if ($last_name) {
            update_user_meta($user_id, 'last_name', $last_name);
        }
        
        // Add affiliate role if requested
        if ($register_as_affiliate && $this->settings->is_module_enabled('affiliate')) {
            $user = new WP_User($user_id);
            $user->add_role('affiliate');
            
            // Create affiliate record
            $this->create_affiliate_record($user_id);
        }
        
        // Log activity
        $this->log_activity('register', 'New user registered', 'user', $user_id);
        
        // Auto-login after registration
        $result = $this->auth->authenticate($username, $password);
        
        return $this->success_response(
            array(
                'access_token' => $result['access_token'],
                'refresh_token' => $result['refresh_token'],
                'user' => $result['user'],
            ),
            'Registration successful'
        );
    }
    
    /**
     * Create affiliate record
     */
    private function create_affiliate_record($user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'dashboard_pro_affiliates';
        
        // Generate unique affiliate code
        $affiliate_code = $this->generate_affiliate_code($user_id);
        
        $status = $this->settings->get('affiliate_approval_required') ? 'pending' : 'active';
        $commission_rate = $this->settings->get('affiliate_default_commission_rate', 10);
        
        $wpdb->insert(
            $table,
            array(
                'user_id' => $user_id,
                'affiliate_code' => $affiliate_code,
                'commission_rate' => $commission_rate,
                'status' => $status,
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%f', '%s', '%s')
        );
    }
    
    /**
     * Generate unique affiliate code
     */
    private function generate_affiliate_code($user_id) {
        $user = get_user_by('ID', $user_id);
        $base_code = strtoupper(substr($user->user_login, 0, 6) . $user_id);
        
        global $wpdb;
        $table = $wpdb->prefix . 'dashboard_pro_affiliates';
        
        $code = $base_code;
        $counter = 1;
        
        while ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE affiliate_code = %s", $code)) > 0) {
            $code = $base_code . $counter;
            $counter++;
        }
        
        return $code;
    }
}
