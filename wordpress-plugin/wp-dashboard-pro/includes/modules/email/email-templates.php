<?php
/**
 * Email Templates
 * HTML email templates for notifications
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Dashboard_Pro_Email_Templates {
    
    /**
     * Get email header
     */
    public static function get_header($title = '') {
        $settings = new WP_Dashboard_Pro_Settings();
        $brand_name = $settings->get('brand_name') ?: get_bloginfo('name');
        $brand_color = $settings->get('brand_color') ?: '#3b82f6';
        
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . esc_html($title) . '</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f3f4f6;
            color: #111827;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .header {
            background: linear-gradient(135deg, ' . esc_attr($brand_color) . ' 0%, ' . esc_attr($brand_color) . 'dd 100%);
            color: #ffffff;
            padding: 40px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #111827;
        }
        .message {
            font-size: 16px;
            line-height: 1.6;
            color: #4b5563;
            margin-bottom: 30px;
        }
        .info-box {
            background-color: #f9fafb;
            border-left: 4px solid ' . esc_attr($brand_color) . ';
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box .label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        .info-box .value {
            color: #1f2937;
            font-size: 18px;
        }
        .button {
            display: inline-block;
            background-color: ' . esc_attr($brand_color) . ';
            color: #ffffff !important;
            padding: 14px 28px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
            text-align: center;
        }
        .footer {
            background-color: #f9fafb;
            padding: 30px;
            text-align: center;
            font-size: 14px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }
        .footer a {
            color: ' . esc_attr($brand_color) . ';
            text-decoration: none;
        }
        .divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 30px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>' . esc_html($brand_name) . '</h1>
        </div>
        <div class="content">';
    }
    
    /**
     * Get email footer
     */
    public static function get_footer() {
        $settings = new WP_Dashboard_Pro_Settings();
        $brand_name = $settings->get('brand_name') ?: get_bloginfo('name');
        $site_url = home_url();
        
        return '    </div>
        <div class="footer">
            <p>© ' . date('Y') . ' ' . esc_html($brand_name) . '. All rights reserved.</p>
            <p>
                <a href="' . esc_url($site_url) . '">Visit our website</a> | 
                <a href="' . esc_url($site_url . '/contact') . '">Contact Support</a>
            </p>
        </div>
    </div>
</body>
</html>';
    }
    
    /**
     * Affiliate Approved Template
     */
    public static function affiliate_approved($affiliate, $user) {
        $header = self::get_header('Affiliate Application Approved');
        $footer = self::get_footer();
        
        $login_url = wp_login_url();
        
        $content = '<div class="greeting">Hi ' . esc_html($user->display_name) . ',</div>
        
        <div class="message">
            <p>Congratulations! Your affiliate application has been approved. You can now start promoting our products and earning commissions.</p>
        </div>
        
        <div class="info-box">
            <div class="label">Your Affiliate Code:</div>
            <div class="value" style="font-family: monospace; font-size: 20px;">' . esc_html($affiliate->affiliate_code) . '</div>
        </div>
        
        <div class="info-box">
            <div class="label">Commission Rate:</div>
            <div class="value">' . esc_html($affiliate->commission_rate) . '%</div>
        </div>
        
        <div class="message">
            <p>Use your unique affiliate code to track your referrals and earn commissions on every sale.</p>
        </div>
        
        <a href="' . esc_url($login_url) . '" class="button">Access Your Dashboard</a>
        
        <div class="divider"></div>
        
        <div class="message" style="font-size: 14px;">
            <p><strong>Getting Started:</strong></p>
            <ul>
                <li>Log in to your affiliate dashboard</li>
                <li>Generate your referral links</li>
                <li>Share on social media, blogs, or email</li>
                <li>Track your performance in real-time</li>
            </ul>
        </div>';
        
        return $header . $content . $footer;
    }
    
    /**
     * Affiliate Rejected Template
     */
    public static function affiliate_rejected($affiliate, $user, $reason = '') {
        $header = self::get_header('Affiliate Application Status');
        $footer = self::get_footer();
        
        $content = '<div class="greeting">Hi ' . esc_html($user->display_name) . ',</div>
        
        <div class="message">
            <p>Thank you for your interest in our affiliate program.</p>
            <p>After careful review, we are unable to approve your application at this time.</p>
        </div>';
        
        if ($reason) {
            $content .= '<div class="info-box">
                <div class="label">Reason:</div>
                <div class="value">' . esc_html($reason) . '</div>
            </div>';
        }
        
        $content .= '<div class="message">
            <p>You may reapply after addressing the concerns mentioned above.</p>
            <p>If you have any questions, please don\'t hesitate to contact our support team.</p>
        </div>';
        
        return $header . $content . $footer;
    }
    
    /**
     * Commission Earned Template
     */
    public static function commission_earned($commission, $user, $order) {
        $header = self::get_header('New Commission Earned');
        $footer = self::get_footer();
        
        $settings = new WP_Dashboard_Pro_Settings();
        $currency = $settings->get('affiliate_currency', 'USD');
        
        $content = '<div class="greeting">Hi ' . esc_html($user->display_name) . ',</div>
        
        <div class="message">
            <p>Great news! You\'ve earned a new commission from a successful referral.</p>
        </div>
        
        <div class="info-box">
            <div class="label">Commission Amount:</div>
            <div class="value" style="color: #10b981; font-size: 24px; font-weight: bold;">
                ' . esc_html($currency) . ' ' . number_format($commission->amount, 2) . '
            </div>
        </div>
        
        <div class="info-box">
            <div class="label">Order Number:</div>
            <div class="value">#' . esc_html($commission->order_id) . '</div>
        </div>
        
        <div class="info-box">
            <div class="label">Status:</div>
            <div class="value">' . esc_html(ucfirst($commission->status)) . '</div>
        </div>
        
        <div class="message">
            <p>Keep up the excellent work! Your commission will be available for withdrawal once it\'s approved.</p>
        </div>
        
        <a href="' . esc_url(home_url('/affiliate/commissions')) . '" class="button">View All Commissions</a>';
        
        return $header . $content . $footer;
    }
    
    /**
     * Withdrawal Requested Template (Affiliate)
     */
    public static function withdrawal_requested_affiliate($withdrawal, $user) {
        $header = self::get_header('Withdrawal Request Received');
        $footer = self::get_footer();
        
        $settings = new WP_Dashboard_Pro_Settings();
        $currency = $settings->get('affiliate_currency', 'USD');
        
        $content = '<div class="greeting">Hi ' . esc_html($user->display_name) . ',</div>
        
        <div class="message">
            <p>We have received your withdrawal request and it is being processed.</p>
        </div>
        
        <div class="info-box">
            <div class="label">Withdrawal Amount:</div>
            <div class="value" style="font-size: 24px; font-weight: bold;">
                ' . esc_html($currency) . ' ' . number_format($withdrawal->amount, 2) . '
            </div>
        </div>
        
        <div class="info-box">
            <div class="label">Payment Method:</div>
            <div class="value">' . esc_html(ucfirst($withdrawal->method)) . '</div>
        </div>
        
        <div class="info-box">
            <div class="label">Status:</div>
            <div class="value" style="color: #f59e0b;">Pending Review</div>
        </div>
        
        <div class="message">
            <p>We will review your request and process it within 1-3 business days. You will receive another email once your withdrawal is approved.</p>
        </div>
        
        <a href="' . esc_url(home_url('/affiliate/withdrawals')) . '" class="button">View Withdrawal Status</a>';
        
        return $header . $content . $footer;
    }
    
    /**
     * Withdrawal Requested Template (Admin)
     */
    public static function withdrawal_requested_admin($withdrawal, $affiliate) {
        $header = self::get_header('New Withdrawal Request');
        $footer = self::get_footer();
        
        $settings = new WP_Dashboard_Pro_Settings();
        $currency = $settings->get('affiliate_currency', 'USD');
        
        $content = '<div class="greeting">New Withdrawal Request</div>
        
        <div class="message">
            <p>A withdrawal request has been submitted and requires your attention.</p>
        </div>
        
        <div class="info-box">
            <div class="label">Amount:</div>
            <div class="value" style="font-size: 24px; font-weight: bold;">
                ' . esc_html($currency) . ' ' . number_format($withdrawal->amount, 2) . '
            </div>
        </div>
        
        <div class="info-box">
            <div class="label">Affiliate:</div>
            <div class="value">' . esc_html($affiliate->affiliate_code) . ' (ID: ' . esc_html($affiliate->id) . ')</div>
        </div>
        
        <div class="info-box">
            <div class="label">Payment Method:</div>
            <div class="value">' . esc_html(ucfirst($withdrawal->method)) . '</div>
        </div>
        
        <div class="message">
            <p>Please review and process this withdrawal request in the admin dashboard.</p>
        </div>
        
        <a href="' . esc_url(admin_url('admin.php?page=dashboard-pro-withdrawals')) . '" class="button">Review Request</a>';
        
        return $header . $content . $footer;
    }
    
    /**
     * Withdrawal Approved Template
     */
    public static function withdrawal_approved($withdrawal, $user) {
        $header = self::get_header('Withdrawal Approved');
        $footer = self::get_footer();
        
        $settings = new WP_Dashboard_Pro_Settings();
        $currency = $settings->get('affiliate_currency', 'USD');
        
        $content = '<div class="greeting">Hi ' . esc_html($user->display_name) . ',</div>
        
        <div class="message">
            <p>Good news! Your withdrawal request has been approved and is being processed.</p>
        </div>
        
        <div class="info-box">
            <div class="label">Withdrawal Amount:</div>
            <div class="value" style="font-size: 24px; font-weight: bold; color: #10b981;">
                ' . esc_html($currency) . ' ' . number_format($withdrawal->amount, 2) . '
            </div>
        </div>
        
        <div class="info-box">
            <div class="label">Payment Method:</div>
            <div class="value">' . esc_html(ucfirst($withdrawal->method)) . '</div>
        </div>
        
        <div class="info-box">
            <div class="label">Status:</div>
            <div class="value" style="color: #10b981;">Approved - Processing</div>
        </div>
        
        <div class="message">
            <p>You should receive your payment within 2-5 business days, depending on your payment method.</p>
        </div>
        
        <a href="' . esc_url(home_url('/affiliate/withdrawals')) . '" class="button">View Withdrawal History</a>';
        
        return $header . $content . $footer;
    }
    
    /**
     * Withdrawal Rejected Template
     */
    public static function withdrawal_rejected($withdrawal, $user, $reason = '') {
        $header = self::get_header('Withdrawal Request Status');
        $footer = self::get_footer();
        
        $settings = new WP_Dashboard_Pro_Settings();
        $currency = $settings->get('affiliate_currency', 'USD');
        
        $content = '<div class="greeting">Hi ' . esc_html($user->display_name) . ',</div>
        
        <div class="message">
            <p>We have reviewed your withdrawal request.</p>
        </div>
        
        <div class="info-box">
            <div class="label">Withdrawal Amount:</div>
            <div class="value" style="font-size: 24px; font-weight: bold;">
                ' . esc_html($currency) . ' ' . number_format($withdrawal->amount, 2) . '
            </div>
        </div>
        
        <div class="info-box">
            <div class="label">Status:</div>
            <div class="value" style="color: #ef4444;">Rejected</div>
        </div>';
        
        if ($reason) {
            $content .= '<div class="info-box">
                <div class="label">Reason:</div>
                <div class="value">' . esc_html($reason) . '</div>
            </div>';
        }
        
        $content .= '<div class="message">
            <p>Your funds remain in your account balance. You may submit a new withdrawal request after addressing the issue mentioned above.</p>
            <p>If you have any questions, please contact our support team.</p>
        </div>
        
        <a href="' . esc_url(home_url('/affiliate/withdrawals')) . '" class="button">View Your Account</a>';
        
        return $header . $content . $footer;
    }
    
    /**
     * Order Notification Template
     */
    public static function order_notification($order, $user) {
        $header = self::get_header('New Order Received');
        $footer = self::get_footer();
        
        $content = '<div class="greeting">Hi ' . esc_html($user->display_name) . ',</div>
        
        <div class="message">
            <p>Thank you for your order! We have received your order and it is being processed.</p>
        </div>
        
        <div class="info-box">
            <div class="label">Order Number:</div>
            <div class="value">#' . esc_html($order->get_order_number()) . '</div>
        </div>
        
        <div class="info-box">
            <div class="label">Total Amount:</div>
            <div class="value" style="font-size: 24px; font-weight: bold;">
                ' . esc_html($order->get_currency()) . ' ' . number_format($order->get_total(), 2) . '
            </div>
        </div>
        
        <div class="message">
            <p>We will send you another email once your order has been shipped.</p>
        </div>
        
        <a href="' . esc_url($order->get_view_order_url()) . '" class="button">View Order Details</a>';
        
        return $header . $content . $footer;
    }
}
