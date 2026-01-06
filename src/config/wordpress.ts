/**
 * WordPress & WooCommerce Configuration
 * 
 * This configuration file centralizes all WordPress API settings.
 * Values are loaded from environment variables (.env file).
 * 
 * For production, ensure all environment variables are properly set.
 */

export const config = {
  // WordPress REST API base URL - Dashboard Pro Plugin
  wordpressApiUrl: import.meta.env.VITE_WORDPRESS_API_URL || 'http://localhost/wordpress/wp-json',
  
  // WooCommerce API credentials are NOT needed on the client; all Woo operations are proxied via the plugin
  woocommerce: {
    consumerKey: '',
    consumerSecret: '',
  },
  
  // Debug mode
  debugMode: import.meta.env.VITE_DEBUG_MODE === 'true',
  
  // API endpoints - Dashboard Pro Plugin
  endpoints: {
    // Authentication
    auth: {
      login: '/dashboard-pro/v1/auth/login',
      refresh: '/dashboard-pro/v1/auth/refresh',
      validate: '/dashboard-pro/v1/auth/validate',
      logout: '/dashboard-pro/v1/auth/logout',
      me: '/dashboard-pro/v1/auth/me',
    },
    
    // Dashboard
    dashboard: {
      stats: '/dashboard-pro/v1/dashboard/stats',
      activity: '/dashboard-pro/v1/dashboard/activity',
      analytics: '/dashboard-pro/v1/dashboard/analytics',
      salesChart: '/dashboard-pro/v1/dashboard/sales-chart',
    },
    
    // Users
    users: '/dashboard-pro/v1/users',
    
    // Settings
    settings: '/dashboard-pro/v1/settings',
    branding: '/dashboard-pro/v1/settings/branding',
    modules: '/dashboard-pro/v1/settings/modules',
    emailTemplates: '/dashboard-pro/v1/settings/email-templates',
    permissions: '/dashboard-pro/v1/settings/permissions',
    
    // Affiliates
    affiliates: '/dashboard-pro/v1/affiliates',
    
    // Commissions
    commissions: '/dashboard-pro/v1/commissions',
    
    // Withdrawals
    withdrawals: '/dashboard-pro/v1/withdrawals',
    
    // WooCommerce (proxied by plugin)
    products: '/dashboard-pro/v1/products',
    productCategories: '/dashboard-pro/v1/products/categories',
    productMedia: '/dashboard-pro/v1/products/media',
    orders: '/dashboard-pro/v1/orders',
  },
} as const;

/**
 * Check if the WordPress configuration is valid
 */
export const isConfigured = (): boolean => {
  return !!(
    config.wordpressApiUrl
  );
};

/**
 * Get full API URL for an endpoint
 */
export const getApiUrl = (endpoint: string): string => {
  return `${config.wordpressApiUrl}${endpoint}`;
};

export default config;
