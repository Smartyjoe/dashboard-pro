<?php
/**
 * Settings API
 * Handles plugin settings and configuration endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Dashboard_Pro_Settings_API extends WP_Dashboard_Pro_API_Base {
    
    /**
     * Register routes
     */
    public function register_routes() {
        // Get all settings
        $this->register_route('/settings', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_settings'),
            'permission_callback' => array($this, 'authenticate_request'),
        ));
        
        // Update settings
        $this->register_route('/settings', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_settings'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));
        
        // Get branding settings (public)
        $this->register_route('/settings/branding', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_branding'),
            'permission_callback' => '__return_true',
        ));
        
        // Get module config (public)
        $this->register_route('/settings/modules', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_modules'),
            'permission_callback' => '__return_true',
        ));
        
        // Toggle module
        $this->register_route('/settings/modules/(?P<module>[a-z_]+)/toggle', array(
            'methods' => 'POST',
            'callback' => array($this, 'toggle_module'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));
    }
    
    /**
     * Get all settings
     */
    public function get_settings($request) {
        $user = $this->auth->get_current_user();
        
        if (user_can($user, 'manage_options')) {
            // Admins get all settings
            $settings = $this->settings->get_all();
            
            // Remove sensitive data
            unset($settings['jwt_secret_key']);
            
            return $this->success_response($settings);
        } else {
            // Regular users get limited settings
            return $this->success_response($this->settings->export_for_frontend());
        }
    }
    
    /**
     * Update settings
     */
    public function update_settings($request) {
        $new_settings = $request->get_json_params();
        
        if (empty($new_settings)) {
            return $this->error_response('No settings provided', 400);
        }
        
        // Don't allow updating JWT secret through API
        if (isset($new_settings['jwt_secret_key'])) {
            unset($new_settings['jwt_secret_key']);
        }
        
        // Update settings
        $result = $this->settings->update_multiple($new_settings);
        
        if ($result) {
            $this->log_activity('update_settings', 'Updated plugin settings');
            return $this->success_response(null, 'Settings updated successfully');
        } else {
            return $this->error_response('Failed to update settings', 500);
        }
    }
    
    /**
     * Get branding settings
     */
    public function get_branding($request) {
        return $this->success_response($this->settings->get_branding());
    }
    
    /**
     * Get module configuration
     */
    public function get_modules($request) {
        return $this->success_response($this->settings->get_module_config());
    }
    
    /**
     * Toggle module
     */
    public function toggle_module($request) {
        $module = $request['module'];
        $enable = $request->get_param('enable');
        
        if ($enable) {
            $result = $this->settings->enable_module($module);
        } else {
            $result = $this->settings->disable_module($module);
        }
        
        if ($result === false) {
            return $this->error_response('Cannot disable core modules', 400);
        }
        
        $action = $enable ? 'enabled' : 'disabled';
        $this->log_activity('toggle_module', "Module $module $action", 'module', null, array('module' => $module, 'enabled' => $enable));
        
        return $this->success_response(null, "Module $action successfully");
    }
}
