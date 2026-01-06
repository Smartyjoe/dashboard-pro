# Installation Guide - WP Dashboard Pro

## Prerequisites

Before installing WP Dashboard Pro, ensure your server meets these requirements:

- **WordPress**: 5.8 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher
- **WooCommerce**: 5.0+ (optional but recommended)

## Step-by-Step Installation

### 1. Upload Plugin Files

#### Option A: Via WordPress Admin
1. Download the plugin ZIP file
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin"
4. Choose the ZIP file and click "Install Now"
5. Click "Activate Plugin"

#### Option B: Via FTP
1. Extract the plugin ZIP file
2. Upload the `wp-dashboard-pro` folder to `/wp-content/plugins/`
3. Go to WordPress Admin → Plugins
4. Find "WP Dashboard Pro" and click "Activate"

### 2. Initial Setup

After activation, the plugin will automatically:
- ✅ Create required database tables
- ✅ Set up default settings
- ✅ Create "Affiliate" user role
- ✅ Add custom capabilities to admin roles

### 3. Configure Settings

#### Access Settings
1. Go to WordPress Admin
2. Click "Dashboard Pro" in the left menu
3. Click "Settings"

#### General Settings
1. **Plugin Name**: Enter your desired plugin name
2. **Dashboard URL**: Enter the URL where your React dashboard is hosted
   - Example: `https://dashboard.yourdomain.com`
3. **Enable CORS**: Check this if your dashboard is on a different domain

#### Branding Settings (White-Label)
1. **Brand Name**: Your company or brand name
   - This appears in the dashboard and emails
2. **Logo URL**: Full URL to your logo
   - Example: `https://yourdomain.com/logo.png`
3. **Primary Color**: Main brand color (hex code)
4. **Secondary Color**: Secondary brand color
5. **Accent Color**: Accent color for highlights

#### Module Settings
Enable only the features you need:
- ✅ **Affiliate System**: For affiliate tracking
- ✅ **Commission System**: For commission management
- ✅ **Withdrawal System**: For payout requests
- ✅ **Email Notifications**: For automated emails
- ✅ **WooCommerce Tracking**: For order tracking

#### Affiliate Settings
1. **Require Approval**: Check if new affiliates need admin approval
2. **Default Commission Rate**: Set default commission percentage (e.g., 10)
3. **Cookie Duration**: How long referral cookies last (default: 30 days)
4. **Minimum Withdrawal**: Minimum amount for withdrawal requests (e.g., 50)
5. **Currency**: Your currency code (e.g., USD, EUR, GBP)

#### Commission Settings
1. **Calculation Method**: 
   - Percentage: Commission as % of order
   - Fixed: Fixed amount per order
2. **Apply To**:
   - Subtotal: Before shipping and tax
   - Total: Including shipping and tax
   - Profit: Revenue minus cost
3. **Status Trigger**: Order status that creates commission
   - Completed (recommended)
   - Processing
4. **Hold Period**: Days to hold commission before withdrawal (0 = no hold)

#### Email Settings
1. **From Name**: Sender name for emails (e.g., Your Company)
2. **From Email**: Sender email address
3. **Enable/Disable Notifications**: Check boxes for emails you want to send

### 4. Connect React Dashboard

#### Update React Configuration

Edit your React dashboard's `src/config/wordpress.ts`:

```typescript
export const WORDPRESS_CONFIG = {
  apiUrl: 'https://your-site.com/wp-json/dashboard-pro/v1',
  siteUrl: 'https://your-site.com',
};
```

#### Test API Connection

1. Try logging in from your React dashboard
2. Use any existing WordPress admin user
3. If successful, you'll receive JWT tokens

### 5. Create Test Affiliate (Optional)

To test the affiliate system:

1. **Create User**:
   - Go to Users → Add New
   - Fill in details
   - Select Role: "Affiliate"
   - Click "Add New User"

2. **Verify Affiliate Record**:
   - The plugin automatically creates affiliate profile
   - Go to Dashboard Pro to view affiliate details
   - Note the unique affiliate code

3. **Test Referral Link**:
   - Use format: `https://your-site.com/?ref=AFFILIATE_CODE`
   - Visit the link (use incognito mode)
   - Check that cookie is set
   - Make a test order

## CORS Configuration (If Needed)

If your React dashboard is on a different domain:

### Method 1: Via Plugin Settings
1. Go to Dashboard Pro → Settings → General
2. Check "Enable CORS"
3. Save settings

### Method 2: Via WordPress Code
Add to your theme's `functions.php`:

```php
add_filter('rest_pre_serve_request', function($served, $result, $request) {
    if (strpos($request->get_route(), '/dashboard-pro/') !== false) {
        header('Access-Control-Allow-Origin: https://your-dashboard-domain.com');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Credentials: true');
    }
    return $served;
}, 10, 3);
```

## Troubleshooting

### Issue: Cannot Login / 401 Error
**Solution:**
1. Check WordPress Admin → Settings → Permalinks
2. Click "Save Changes" to flush rewrite rules
3. Verify JWT secret exists in database
4. Check that API endpoint is accessible

### Issue: CORS Errors
**Solution:**
1. Enable CORS in plugin settings
2. Add your dashboard URL to allowed origins
3. Check server .htaccess file
4. Verify headers in browser console

### Issue: Commissions Not Created
**Solution:**
1. Verify WooCommerce Tracking module is enabled
2. Check commission status trigger matches order status
3. Ensure affiliate cookie is set
4. Review order meta for `_referred_by` field

### Issue: Emails Not Sending
**Solution:**
1. Verify Email Notifications module is enabled
2. Check individual notification settings
3. Test with WP Mail SMTP plugin
4. Check server email configuration

### Issue: Database Tables Not Created
**Solution:**
1. Deactivate and reactivate the plugin
2. Check database user has CREATE TABLE permission
3. Review WordPress error logs
4. Manually run activation hook

## Security Recommendations

### 1. Secure JWT Secret
The plugin generates a secure JWT secret automatically. Never share this key.

### 2. HTTPS Only
Always use HTTPS for:
- WordPress site
- React dashboard
- API requests

### 3. Regular Backups
Backup your database regularly, especially these tables:
- `wp_dashboard_pro_affiliates`
- `wp_dashboard_pro_commissions`
- `wp_dashboard_pro_withdrawals`

### 4. Limit Admin Access
Only trusted users should have:
- `manage_options`
- `manage_affiliates`
- `process_withdrawals`

### 5. Monitor Activity
Regularly review the activity log:
- Dashboard Pro → Activity

## Next Steps

After successful installation:

1. ✅ Create test affiliate account
2. ✅ Test referral tracking
3. ✅ Place test order
4. ✅ Verify commission creation
5. ✅ Test withdrawal request
6. ✅ Customize branding
7. ✅ Configure email templates
8. ✅ Set up payment methods

## Need Help?

If you encounter any issues:
1. Check this documentation
2. Review WordPress error logs
3. Test with default WordPress theme
4. Disable other plugins temporarily

## Updating

When updating the plugin:
1. Backup your database first
2. Deactivate the plugin
3. Replace plugin files
4. Reactivate the plugin
5. Visit Dashboard Pro page to run any database updates

---

**Congratulations! Your WP Dashboard Pro installation is complete.** 🎉
