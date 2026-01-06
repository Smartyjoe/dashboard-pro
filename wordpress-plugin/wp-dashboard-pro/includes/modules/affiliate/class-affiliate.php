<?php
/**
 * Affiliate Module
 * Core affiliate functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Dashboard_Pro_Affiliate {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
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
        // Add any affiliate-specific hooks here
    }
}
