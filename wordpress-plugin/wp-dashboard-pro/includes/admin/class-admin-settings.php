<?php
/**
 * Admin Settings
 * Handles WordPress admin settings interface
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Dashboard_Pro_Admin_Settings {
    
    /**
     * Settings instance
     */
    private $settings;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = new WP_Dashboard_Pro_Settings();
        
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_dashboard_pro_render_settings', array($this, 'render_settings_page'));
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('wp_dashboard_pro_settings', WP_Dashboard_Pro_Settings::OPTION_NAME);
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle form submission
        if (isset($_POST['wp_dashboard_pro_settings_submit'])) {
            check_admin_referer('wp_dashboard_pro_settings');
            $this->save_settings();
        }
        
        $settings = $this->settings->get_all();
        ?>
        <div class="wrap">
            <h1><?php _e('Dashboard Pro Settings', 'wp-dashboard-pro'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('wp_dashboard_pro_settings'); ?>
                
                <h2 class="nav-tab-wrapper">
                    <a href="#general" class="nav-tab nav-tab-active"><?php _e('General', 'wp-dashboard-pro'); ?></a>
                    <a href="#branding" class="nav-tab"><?php _e('Branding', 'wp-dashboard-pro'); ?></a>
                    <a href="#modules" class="nav-tab"><?php _e('Modules', 'wp-dashboard-pro'); ?></a>
                    <a href="#affiliate" class="nav-tab"><?php _e('Affiliate Settings', 'wp-dashboard-pro'); ?></a>
                    <a href="#commission" class="nav-tab"><?php _e('Commission Settings', 'wp-dashboard-pro'); ?></a>
                    <a href="#email" class="nav-tab"><?php _e('Email Settings', 'wp-dashboard-pro'); ?></a>
                </h2>
                
                <!-- General Settings -->
                <div id="general" class="tab-content">
                    <h2><?php _e('General Settings', 'wp-dashboard-pro'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="plugin_name"><?php _e('Plugin Name', 'wp-dashboard-pro'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="plugin_name" name="plugin_name" value="<?php echo esc_attr($settings['plugin_name']); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="dashboard_url"><?php _e('Dashboard URL', 'wp-dashboard-pro'); ?></label>
                            </th>
                            <td>
                                <input type="url" id="dashboard_url" name="dashboard_url" value="<?php echo esc_attr($settings['dashboard_url']); ?>" class="regular-text" />
                                <p class="description"><?php _e('The URL where your React dashboard is hosted.', 'wp-dashboard-pro'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="enable_cors"><?php _e('Enable CORS', 'wp-dashboard-pro'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="enable_cors" name="enable_cors" value="1" <?php checked($settings['enable_cors']); ?> />
                                <label for="enable_cors"><?php _e('Allow cross-origin requests', 'wp-dashboard-pro'); ?></label>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Branding Settings -->
                <div id="branding" class="tab-content" style="display:none;">
                    <h2><?php _e('Branding & White Label', 'wp-dashboard-pro'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="brand_name"><?php _e('Brand Name', 'wp-dashboard-pro'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="brand_name" name="brand_name" value="<?php echo esc_attr($settings['brand_name']); ?>" class="regular-text" />
                                <p class="description"><?php _e('Your company or brand name displayed in the dashboard.', 'wp-dashboard-pro'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="brand_logo_url"><?php _e('Logo URL', 'wp-dashboard-pro'); ?></label>
                            </th>
                            <td>
                                <input type="url" id="brand_logo_url" name="brand_logo_url" value="<?php echo esc_attr($settings['brand_logo_url']); ?>" class="regular-text" />
                                <p class="description"><?php _e('Full URL to your logo image.', 'wp-dashboard-pro'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="brand_primary_color"><?php _e('Primary Color', 'wp-dashboard-pro'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="brand_primary_color" name="brand_primary_color" value="<?php echo esc_attr($settings['brand_primary_color']); ?>" class="color-picker" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="brand_secondary_color"><?php _e('Secondary Color', 'wp-dashboard-pro'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="brand_secondary_color" name="brand_secondary_color" value="<?php echo esc_attr($settings['brand_secondary_color']); ?>" class="color-picker" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="brand_accent_color"><?php _e('Accent Color', 'wp-dashboard-pro'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="brand_accent_color" name="brand_accent_color" value="<?php echo esc_attr($settings['brand_accent_color']); ?>" class="color-picker" />
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Module Settings -->
                <div id="modules" class="tab-content" style="display:none;">
                    <h2><?php _e('Feature Modules', 'wp-dashboard-pro'); ?></h2>
                    <p><?php _e('Enable or disable features based on your needs. Core features (Analytics, User Management) cannot be disabled.', 'wp-dashboard-pro'); ?></p>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Affiliate System', 'wp-dashboard-pro'); ?></th>
                            <td>
                                <input type="checkbox" id="module_affiliate" name="modules[affiliate]" value="1" <?php checked(!empty($settings['modules']['affiliate'])); ?> />
                                <label for="module_affiliate"><?php _e('Enable affiliate tracking and management', 'wp-dashboard-pro'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Commission System', 'wp-dashboard-pro'); ?></th>
                            <td>
                                <input type="checkbox" id="module_commission" name="modules[commission]" value="1" <?php checked(!empty($settings['modules']['commission'])); ?> />
                                <label for="module_commission"><?php _e('Enable commission tracking and payments', 'wp-dashboard-pro'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Withdrawal System', 'wp-dashboard-pro'); ?></th>
                            <td>
                                <input type="checkbox" id="module_withdrawal" name="modules[withdrawal]" value="1" <?php checked(!empty($settings['modules']['withdrawal'])); ?> />
                                <label for="module_withdrawal"><?php _e('Enable withdrawal requests', 'wp-dashboard-pro'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Email Notifications', 'wp-dashboard-pro'); ?></th>
                            <td>
                                <input type="checkbox" id="module_email_notifications" name="modules[email_notifications]" value="1" <?php checked(!empty($settings['modules']['email_notifications'])); ?> />
                                <label for="module_email_notifications"><?php _e('Enable automated email notifications', 'wp-dashboard-pro'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('WooCommerce Tracking', 'wp-dashboard-pro'); ?></th>
                            <td>
                                <input type="checkbox" id="module_woocommerce_tracking" name="modules[woocommerce_tracking]" value="1" <?php checked(!empty($settings['modules']['woocommerce_tracking'])); ?> />
                                <label for="module_woocommerce_tracking"><?php _e('Track WooCommerce orders for commissions', 'wp-dashboard-pro'); ?></label>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Affiliate Settings -->
                <div id="affiliate" class="tab-content" style="display:none;">
                    <h2><?php _e('Affiliate Settings', 'wp-dashboard-pro'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="affiliate_approval_required"><?php _e('Require Approval', 'wp-dashboard-pro'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="affiliate_approval_required" name="affiliate_approval_required" value="1" <?php checked($settings['affiliate_approval_required']); ?> />
                                <label for="affiliate_approval_required"><?php _e('New affiliates must be approved by admin', 'wp-dashboard-pro'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="affiliate_default_commission_rate"><?php _e('Default Commission Rate (%)', 'wp-dashboard-pro'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="affiliate_default_commission_rate" name="affiliate_default_commission_rate" value="<?php echo esc_attr($settings['affiliate_default_commission_rate']); ?>" min="0" max="100" step="0.01" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="affiliate_cookie_duration"><?php _e('Cookie Duration (days)', 'wp-dashboard-pro'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="affiliate_cookie_duration" name="affiliate_cookie_duration" value="<?php echo esc_attr($settings['affiliate_cookie_duration']); ?>" min="1" />
                                <p class="description"><?php _e('How long referral cookies last.', 'wp-dashboard-pro'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="affiliate_minimum_withdrawal"><?php _e('Minimum Withdrawal Amount', 'wp-dashboard-pro'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="affiliate_minimum_withdrawal" name="affiliate_minimum_withdrawal" value="<?php echo esc_attr($settings['affiliate_minimum_withdrawal']); ?>" min="0" step="0.01" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="affiliate_currency"><?php _e('Currency', 'wp-dashboard-pro'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="affiliate_currency" name="affiliate_currency" value="<?php echo esc_attr($settings['affiliate_currency']); ?>" maxlength="3" />
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Commission Settings -->
                <div id="commission" class="tab-content" style="display:none;">
                    <h2><?php _e('Commission Settings', 'wp-dashboard-pro'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="commission_calculation"><?php _e('Calculation Method', 'wp-dashboard-pro'); ?></label>
                            </th>
                            <td>
                                <select id="commission_calculation" name="commission_calculation">
                                    <option value="percentage" <?php selected($settings['commission_calculation'], 'percentage'); ?>><?php _e('Percentage', 'wp-dashboard-pro'); ?></option>
                                    <option value="fixed" <?php selected($settings['commission_calculation'], 'fixed'); ?>><?php _e('Fixed Amount', 'wp-dashboard-pro'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="commission_apply_to"><?php _e('Apply To', 'wp-dashboard-pro'); ?></label>
                            </th>
                            <td>
                                <select id="commission_apply_to" name="commission_apply_to">
                                    <option value="subtotal" <?php selected($settings['commission_apply_to'], 'subtotal'); ?>><?php _e('Subtotal', 'wp-dashboard-pro'); ?></option>
                                    <option value="total" <?php selected($settings['commission_apply_to'], 'total'); ?>><?php _e('Order Total', 'wp-dashboard-pro'); ?></option>
                                    <option value="profit" <?php selected($settings['commission_apply_to'], 'profit'); ?>><?php _e('Profit', 'wp-dashboard-pro'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="commission_status_trigger"><?php _e('Status Trigger', 'wp-dashboard-pro'); ?></label>
                            </th>
                            <td>
                                <select id="commission_status_trigger" name="commission_status_trigger">
                                    <option value="completed" <?php selected($settings['commission_status_trigger'], 'completed'); ?>><?php _e('Completed', 'wp-dashboard-pro'); ?></option>
                                    <option value="processing" <?php selected($settings['commission_status_trigger'], 'processing'); ?>><?php _e('Processing', 'wp-dashboard-pro'); ?></option>
                                </select>
                                <p class="description"><?php _e('Order status that triggers commission creation.', 'wp-dashboard-pro'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="commission_hold_period"><?php _e('Hold Period (days)', 'wp-dashboard-pro'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="commission_hold_period" name="commission_hold_period" value="<?php echo esc_attr($settings['commission_hold_period']); ?>" min="0" />
                                <p class="description"><?php _e('Days to hold commission before it can be withdrawn (0 = no hold).', 'wp-dashboard-pro'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Email Settings -->
                <div id="email" class="tab-content" style="display:none;">
                    <h2><?php _e('Email Settings', 'wp-dashboard-pro'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="email_from_name"><?php _e('From Name', 'wp-dashboard-pro'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="email_from_name" name="email_from_name" value="<?php echo esc_attr($settings['email_from_name']); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="email_from_address"><?php _e('From Email', 'wp-dashboard-pro'); ?></label>
                            </th>
                            <td>
                                <input type="email" id="email_from_address" name="email_from_address" value="<?php echo esc_attr($settings['email_from_address']); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Email Notifications', 'wp-dashboard-pro'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="email_notifications[affiliate_approved]" value="1" <?php checked(!empty($settings['email_notifications']['affiliate_approved'])); ?> />
                                    <?php _e('Affiliate Approved', 'wp-dashboard-pro'); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="email_notifications[affiliate_rejected]" value="1" <?php checked(!empty($settings['email_notifications']['affiliate_rejected'])); ?> />
                                    <?php _e('Affiliate Rejected', 'wp-dashboard-pro'); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="email_notifications[commission_earned]" value="1" <?php checked(!empty($settings['email_notifications']['commission_earned'])); ?> />
                                    <?php _e('Commission Earned', 'wp-dashboard-pro'); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="email_notifications[withdrawal_requested]" value="1" <?php checked(!empty($settings['email_notifications']['withdrawal_requested'])); ?> />
                                    <?php _e('Withdrawal Requested', 'wp-dashboard-pro'); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="email_notifications[withdrawal_approved]" value="1" <?php checked(!empty($settings['email_notifications']['withdrawal_approved'])); ?> />
                                    <?php _e('Withdrawal Approved', 'wp-dashboard-pro'); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="email_notifications[withdrawal_rejected]" value="1" <?php checked(!empty($settings['email_notifications']['withdrawal_rejected'])); ?> />
                                    <?php _e('Withdrawal Rejected', 'wp-dashboard-pro'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <?php submit_button(__('Save Settings', 'wp-dashboard-pro'), 'primary', 'wp_dashboard_pro_settings_submit'); ?>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab switching
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                $('.tab-content').hide();
                $(target).show();
            });
            
            // Color picker
            if ($.fn.wpColorPicker) {
                $('.color-picker').wpColorPicker();
            }
        });
        </script>
        <?php
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        $new_settings = array();
        
        // General settings
        $new_settings['plugin_name'] = sanitize_text_field($_POST['plugin_name'] ?? '');
        $new_settings['dashboard_url'] = esc_url_raw($_POST['dashboard_url'] ?? '');
        $new_settings['enable_cors'] = !empty($_POST['enable_cors']);
        
        // Branding settings
        $new_settings['brand_name'] = sanitize_text_field($_POST['brand_name'] ?? '');
        $new_settings['brand_logo_url'] = esc_url_raw($_POST['brand_logo_url'] ?? '');
        $new_settings['brand_primary_color'] = sanitize_hex_color($_POST['brand_primary_color'] ?? '');
        $new_settings['brand_secondary_color'] = sanitize_hex_color($_POST['brand_secondary_color'] ?? '');
        $new_settings['brand_accent_color'] = sanitize_hex_color($_POST['brand_accent_color'] ?? '');
        
        // Module settings
        $new_settings['modules'] = isset($_POST['modules']) ? array_map('boolval', $_POST['modules']) : array();
        
        // Affiliate settings
        $new_settings['affiliate_approval_required'] = !empty($_POST['affiliate_approval_required']);
        $new_settings['affiliate_default_commission_rate'] = floatval($_POST['affiliate_default_commission_rate'] ?? 10);
        $new_settings['affiliate_cookie_duration'] = absint($_POST['affiliate_cookie_duration'] ?? 30);
        $new_settings['affiliate_minimum_withdrawal'] = floatval($_POST['affiliate_minimum_withdrawal'] ?? 50);
        $new_settings['affiliate_currency'] = sanitize_text_field($_POST['affiliate_currency'] ?? 'USD');
        
        // Commission settings
        $new_settings['commission_calculation'] = sanitize_text_field($_POST['commission_calculation'] ?? 'percentage');
        $new_settings['commission_apply_to'] = sanitize_text_field($_POST['commission_apply_to'] ?? 'subtotal');
        $new_settings['commission_status_trigger'] = sanitize_text_field($_POST['commission_status_trigger'] ?? 'completed');
        $new_settings['commission_hold_period'] = absint($_POST['commission_hold_period'] ?? 0);
        
        // Email settings
        $new_settings['email_from_name'] = sanitize_text_field($_POST['email_from_name'] ?? '');
        $new_settings['email_from_address'] = sanitize_email($_POST['email_from_address'] ?? '');
        $new_settings['email_notifications'] = isset($_POST['email_notifications']) ? array_map('boolval', $_POST['email_notifications']) : array();
        
        // Update settings
        $this->settings->update_multiple($new_settings);
        
        // Show success message
        add_settings_error(
            'wp_dashboard_pro_settings',
            'settings_updated',
            __('Settings saved successfully.', 'wp-dashboard-pro'),
            'updated'
        );
        
        settings_errors('wp_dashboard_pro_settings');
    }
}
