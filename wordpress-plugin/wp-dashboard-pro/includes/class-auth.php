<?php
/**
 * Authentication Manager
 * Handles JWT authentication and token management
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Dashboard_Pro_Auth {
    
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
    }
    
    /**
     * Authenticate user with username/email and password
     */
    public function authenticate($username, $password) {
        // Try to authenticate
        $user = wp_authenticate($username, $password);
        
        if (is_wp_error($user)) {
            return array(
                'success' => false,
                'message' => $user->get_error_message(),
            );
        }
        
        // Generate tokens
        $access_token = $this->generate_token($user->ID, 'access');
        $refresh_token = $this->generate_token($user->ID, 'refresh');
        
        // Get user data
        $user_data = $this->get_user_data($user);
        
        // Log activity
        $this->log_activity($user->ID, 'login', 'User logged in');
        
        return array(
            'success' => true,
            'access_token' => $access_token,
            'refresh_token' => $refresh_token,
            'user' => $user_data,
        );
    }
    
    /**
     * Generate JWT token
     */
    public function generate_token($user_id, $type = 'access') {
        $secret = $this->settings->get('jwt_secret_key');
        $issued_at = time();
        
        if ($type === 'refresh') {
            $expiration = $issued_at + $this->settings->get('jwt_refresh_expiration', 604800);
        } else {
            $expiration = $issued_at + $this->settings->get('jwt_expiration', 86400);
        }
        
        $payload = array(
            'iss' => get_site_url(),
            'iat' => $issued_at,
            'exp' => $expiration,
            'user_id' => $user_id,
            'type' => $type,
        );
        
        return $this->encode_jwt($payload, $secret);
    }
    
    /**
     * Validate JWT token
     */
    public function validate_token($token) {
        try {
            $secret = $this->settings->get('jwt_secret_key');
            $payload = $this->decode_jwt($token, $secret);
            
            if (!$payload || !isset($payload->user_id)) {
                return false;
            }
            
            // Check expiration
            if (isset($payload->exp) && $payload->exp < time()) {
                return false;
            }
            
            // Check if user still exists and is active
            $user = get_user_by('ID', $payload->user_id);
            if (!$user) {
                return false;
            }
            
            return $payload;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Refresh access token
     */
    public function refresh_token($refresh_token) {
        $payload = $this->validate_token($refresh_token);
        
        if (!$payload || $payload->type !== 'refresh') {
            return array(
                'success' => false,
                'message' => 'Invalid refresh token',
            );
        }
        
        // Generate new access token
        $access_token = $this->generate_token($payload->user_id, 'access');
        
        return array(
            'success' => true,
            'access_token' => $access_token,
        );
    }
    
    /**
     * Get current user from token
     */
    public function get_current_user() {
        $token = $this->get_token_from_request();
        
        if (!$token) {
            return null;
        }
        
        $payload = $this->validate_token($token);
        
        if (!$payload) {
            return null;
        }
        
        return get_user_by('ID', $payload->user_id);
    }
    
    /**
     * Get token from request
     */
    private function get_token_from_request() {
        $headers = $this->get_authorization_header();
        
        if (!empty($headers)) {
            if (preg_match('/Bearer\s+(.*)$/i', $headers, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    /**
     * Get authorization header
     */
    private function get_authorization_header() {
        $headers = null;
        
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER['Authorization']);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(
                array_map('ucwords', array_keys($requestHeaders)),
                array_values($requestHeaders)
            );
            
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        
        return $headers;
    }
    
    /**
     * Get user data for response
     */
    private function get_user_data($user) {
        $data = array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'roles' => $user->roles,
            'capabilities' => array_keys($user->allcaps),
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
                $data['affiliate'] = array(
                    'id' => $affiliate->id,
                    'code' => $affiliate->affiliate_code,
                    'status' => $affiliate->status,
                    'commission_rate' => $affiliate->commission_rate,
                );
            }
        }
        
        return $data;
    }
    
    /**
     * Encode JWT
     * Simple implementation - for production, consider using firebase/php-jwt library
     */
    private function encode_jwt($payload, $secret) {
        $header = json_encode(array('typ' => 'JWT', 'alg' => 'HS256'));
        $payload = json_encode($payload);
        
        $base64UrlHeader = $this->base64url_encode($header);
        $base64UrlPayload = $this->base64url_encode($payload);
        
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = $this->base64url_encode($signature);
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
    
    /**
     * Decode JWT
     */
    private function decode_jwt($jwt, $secret) {
        $parts = explode('.', $jwt);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $parts;
        
        // Verify signature
        $signature = $this->base64url_decode($base64UrlSignature);
        $expectedSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        
        if (!hash_equals($signature, $expectedSignature)) {
            return false;
        }
        
        $payload = json_decode($this->base64url_decode($base64UrlPayload));
        
        return $payload;
    }
    
    /**
     * Base64 URL encode
     */
    private function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     */
    private function base64url_decode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
    
    /**
     * Check if user has permission
     */
    public function user_can($user_id, $capability) {
        $user = get_user_by('ID', $user_id);
        
        if (!$user) {
            return false;
        }
        
        return user_can($user, $capability);
    }
    
    /**
     * Log activity
     */
    private function log_activity($user_id, $action, $description = '') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'dashboard_pro_activity';
        
        $wpdb->insert(
            $table,
            array(
                'user_id' => $user_id,
                'action' => $action,
                'description' => $description,
                'ip_address' => $this->get_client_ip(),
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return $ip;
    }
}
