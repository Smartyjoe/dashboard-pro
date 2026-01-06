<?php
/**
 * Base API Class
 * All API endpoints extend this class
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class WP_Dashboard_Pro_API_Base {
    
    /**
     * Namespace for API routes
     */
    protected $namespace = 'dashboard-pro/v1';
    
    /**
     * Settings instance
     */
    protected $settings;
    
    /**
     * Auth instance
     */
    protected $auth;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = new WP_Dashboard_Pro_Settings();
        $this->auth = WP_Dashboard_Pro_Auth::get_instance();
    }
    
    /**
     * Register routes - must be implemented by child classes
     */
    abstract public function register_routes();
    
    /**
     * Register a route
     */
    protected function register_route($route, $args) {
        register_rest_route($this->namespace, $route, $args);
    }
    
    /**
     * Check if user is authenticated
     */
    public function authenticate_request($request) {
        $user = $this->auth->get_current_user();
        
        if (!$user) {
            return new WP_Error(
                'unauthorized',
                'You must be logged in to access this resource.',
                array('status' => 401)
            );
        }
        
        return true;
    }
    
    /**
     * Check if user has specific capability
     */
    public function check_permission($capability) {
        return function($request) use ($capability) {
            $user = $this->auth->get_current_user();
            
            if (!$user) {
                return new WP_Error(
                    'unauthorized',
                    'You must be logged in to access this resource.',
                    array('status' => 401)
                );
            }
            
            if (!user_can($user, $capability) && !user_can($user, 'administrator')) {
                return new WP_Error(
                    'forbidden',
                    'You do not have permission to access this resource.',
                    array('status' => 403)
                );
            }
            
            return true;
        };
    }
    
    /**
     * Check if user is admin
     */
    public function check_admin_permission($request) {
        return $this->check_permission('manage_options')($request);
    }
    
    /**
     * Check if user is affiliate
     */
    public function check_affiliate_permission($request) {
        $user = $this->auth->get_current_user();
        
        if (!$user) {
            return new WP_Error(
                'unauthorized',
                'You must be logged in to access this resource.',
                array('status' => 401)
            );
        }
        
        if (!in_array('affiliate', $user->roles) && !user_can($user, 'administrator')) {
            return new WP_Error(
                'forbidden',
                'You must be an affiliate to access this resource.',
                array('status' => 403)
            );
        }
        
        return true;
    }
    
    /**
     * Success response
     */
    protected function success_response($data, $message = '', $code = 200) {
        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => $message,
                'data' => $data,
            ),
            $code
        );
    }
    
    /**
     * Error response
     */
    protected function error_response($message, $code = 400, $data = null) {
        return new WP_REST_Response(
            array(
                'success' => false,
                'message' => $message,
                'data' => $data,
            ),
            $code
        );
    }
    
    /**
     * Validate required parameters
     */
    protected function validate_required_params($request, $required_params) {
        $missing = array();
        
        foreach ($required_params as $param) {
            if (!$request->has_param($param) || empty($request->get_param($param))) {
                $missing[] = $param;
            }
        }
        
        if (!empty($missing)) {
            return new WP_Error(
                'missing_parameters',
                sprintf('Missing required parameters: %s', implode(', ', $missing)),
                array('status' => 400)
            );
        }
        
        return true;
    }
    
    /**
     * Sanitize string
     */
    protected function sanitize_string($value) {
        return sanitize_text_field($value);
    }
    
    /**
     * Sanitize email
     */
    protected function sanitize_email($value) {
        return sanitize_email($value);
    }
    
    /**
     * Sanitize integer
     */
    protected function sanitize_integer($value) {
        return absint($value);
    }
    
    /**
     * Sanitize decimal
     */
    protected function sanitize_decimal($value) {
        return floatval($value);
    }
    
    /**
     * Sanitize array
     */
    protected function sanitize_array($value) {
        if (!is_array($value)) {
            return array();
        }
        
        return array_map('sanitize_text_field', $value);
    }
    
    /**
     * Get pagination params
     */
    protected function get_pagination_params($request) {
        return array(
            'page' => max(1, $request->get_param('page') ?: 1),
            'per_page' => min(100, max(1, $request->get_param('per_page') ?: 20)),
            'orderby' => $this->sanitize_string($request->get_param('orderby') ?: 'created_at'),
            'order' => strtoupper($request->get_param('order') ?: 'DESC'),
        );
    }
    
    /**
     * Format pagination response
     */
    protected function format_pagination_response($items, $total, $page, $per_page) {
        return array(
            'items' => $items,
            'pagination' => array(
                'total' => $total,
                'count' => count($items),
                'per_page' => $per_page,
                'current_page' => $page,
                'total_pages' => ceil($total / $per_page),
            ),
        );
    }
    
    /**
     * Get current user ID
     */
    protected function get_current_user_id() {
        $user = $this->auth->get_current_user();
        return $user ? $user->ID : 0;
    }
    
    /**
     * Log activity
     */
    protected function log_activity($action, $description = '', $object_type = null, $object_id = null, $metadata = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'dashboard_pro_activity';
        
        $data = array(
            'user_id' => $this->get_current_user_id(),
            'action' => $action,
            'description' => $description,
            'object_type' => $object_type,
            'object_id' => $object_id,
            'metadata' => $metadata ? json_encode($metadata) : null,
            'ip_address' => $this->get_client_ip(),
            'created_at' => current_time('mysql'),
        );
        
        $wpdb->insert($table, $data);
    }
    
    /**
     * Get client IP
     */
    protected function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        return $ip;
    }
    
    /**
     * Enable CORS if configured
     */
    protected function enable_cors() {
        if ($this->settings->get('enable_cors')) {
            $allowed_origins = $this->settings->get('allowed_origins', array());
            
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            
            if (in_array($origin, $allowed_origins) || in_array('*', $allowed_origins)) {
                header("Access-Control-Allow-Origin: $origin");
                header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
                header("Access-Control-Allow-Headers: Content-Type, Authorization");
                header("Access-Control-Allow-Credentials: true");
            }
        }
    }
}
