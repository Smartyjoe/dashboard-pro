# WP Dashboard Pro - WordPress Plugin

A professional, white-label dashboard and affiliate management system for WordPress & WooCommerce.

## Features

### Core Features (Always Enabled)
- ✅ **JWT Authentication** - Secure token-based authentication
- ✅ **User Management** - Complete user CRUD operations
- ✅ **Analytics Dashboard** - Sales, orders, and revenue tracking
- ✅ **Activity Logging** - Track all user actions

### Optional Modules
- 🎯 **Affiliate System** - Track and manage affiliates
- 💰 **Commission System** - Automatic commission calculation
- 💳 **Withdrawal System** - Handle payout requests
- 📧 **Email Notifications** - Automated email alerts
- 🛒 **WooCommerce Integration** - Order tracking and commissions

### White-Label & Branding
- 🎨 Custom branding (logo, colors, name)
- 🔧 Flexible module system (enable/disable features)
- ⚙️ Comprehensive settings interface
- 🌐 Multi-client ready

## Installation

### Requirements
- WordPress 5.8 or higher
- PHP 7.4 or higher
- WooCommerce 5.0+ (optional, but recommended for affiliate features)

### Steps

1. **Upload Plugin**
   ```
   wp-content/plugins/wp-dashboard-pro/
   ```

2. **Activate Plugin**
   - Go to WordPress Admin → Plugins
   - Find "WP Dashboard Pro"
   - Click "Activate"

3. **Configure Settings**
   - Go to Dashboard Pro → Settings
   - Configure branding, modules, and options

## API Endpoints

All endpoints use the base URL: `https://your-site.com/wp-json/dashboard-pro/v1`

### Authentication Endpoints

#### POST `/auth/login`
Login and receive JWT tokens.

**Request:**
```json
{
  "username": "user@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "user": {
      "id": 1,
      "username": "johndoe",
      "email": "user@example.com",
      "roles": ["administrator"]
    },
    "settings": {
      "branding": {...},
      "modules": ["affiliate", "commission"]
    }
  }
}
```

#### POST `/auth/refresh`
Refresh access token using refresh token.

#### GET `/auth/me`
Get current authenticated user info.

#### POST `/auth/logout`
Logout current user.

### Dashboard Endpoints

#### GET `/dashboard/stats`
Get dashboard statistics (admin or affiliate-specific).

#### GET `/dashboard/activity`
Get recent activity log with pagination.

#### GET `/dashboard/sales-chart?days=30`
Get sales chart data for specified period.

### User Management Endpoints

#### GET `/users`
List all users with pagination and filters.

**Query Parameters:**
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 20)
- `role` - Filter by role
- `search` - Search users

#### GET `/users/{id}`
Get single user details.

#### POST `/users`
Create new user.

#### PUT `/users/{id}`
Update user details.

#### DELETE `/users/{id}`
Delete user.

#### PUT `/users/{id}/role`
Change user role.

### Affiliate Endpoints

#### GET `/affiliates`
List all affiliates (admin only).

#### GET `/affiliates/me`
Get current user's affiliate profile.

#### GET `/affiliates/{id}`
Get specific affiliate details.

#### PUT `/affiliates/{id}`
Update affiliate information.

#### POST `/affiliates/{id}/approve`
Approve affiliate (admin only).

#### POST `/affiliates/{id}/reject`
Reject affiliate (admin only).

#### GET `/affiliates/{id}/links`
Get affiliate referral links.

#### GET `/affiliates/{id}/stats`
Get affiliate statistics.

#### POST `/affiliates/apply`
Apply to become an affiliate.

### Commission Endpoints

#### GET `/commissions`
List commissions with pagination.

**Query Parameters:**
- `status` - Filter by status (pending, approved, paid)
- `affiliate_id` - Filter by affiliate

#### GET `/commissions/{id}`
Get commission details.

#### POST `/commissions`
Create manual commission (admin only).

#### PUT `/commissions/{id}`
Update commission.

#### POST `/commissions/{id}/approve`
Approve commission (admin only).

#### POST `/commissions/{id}/pay`
Mark commission as paid (admin only).

#### DELETE `/commissions/{id}`
Delete commission (admin only).

### Withdrawal Endpoints

#### GET `/withdrawals`
List withdrawal requests.

#### GET `/withdrawals/{id}`
Get withdrawal details.

#### POST `/withdrawals`
Request withdrawal (affiliate only).

#### POST `/withdrawals/{id}/approve`
Approve withdrawal (admin only).

#### POST `/withdrawals/{id}/reject`
Reject withdrawal (admin only).

#### POST `/withdrawals/{id}/pay`
Mark withdrawal as paid (admin only).

### Settings Endpoints

#### GET `/settings`
Get plugin settings (filtered by user role).

#### PUT `/settings`
Update plugin settings (admin only).

#### GET `/settings/branding`
Get branding settings (public).

#### GET `/settings/modules`
Get enabled modules configuration (public).

## Authentication

All protected endpoints require a JWT token in the Authorization header:

```
Authorization: Bearer {access_token}
```

Access tokens expire after 24 hours (configurable). Use the refresh token to get a new access token.

## Configuration

### Branding Settings
Configure your white-label branding:
- **Brand Name** - Your company name
- **Logo URL** - Full URL to logo image
- **Colors** - Primary, secondary, and accent colors

### Module Settings
Enable/disable features:
- Affiliate System
- Commission System
- Withdrawal System
- Email Notifications
- WooCommerce Tracking

### Affiliate Settings
- Approval required (yes/no)
- Default commission rate (%)
- Cookie duration (days)
- Minimum withdrawal amount
- Currency

### Commission Settings
- Calculation method (percentage/fixed)
- Apply to (subtotal/total/profit)
- Status trigger (completed/processing)
- Hold period (days)

### Email Settings
- From name and email
- Enable/disable specific notifications

## Database Tables

The plugin creates the following tables:

- `wp_dashboard_pro_affiliates` - Affiliate records
- `wp_dashboard_pro_referrals` - Referral tracking
- `wp_dashboard_pro_commissions` - Commission records
- `wp_dashboard_pro_withdrawals` - Withdrawal requests
- `wp_dashboard_pro_analytics` - Analytics events
- `wp_dashboard_pro_activity` - Activity log

## WooCommerce Integration

### Order Tracking
When WooCommerce tracking is enabled:
1. Referral cookies are set when users visit via affiliate links
2. Orders are tracked and linked to affiliates
3. Commissions are automatically created based on settings
4. Affiliates are credited when orders reach the trigger status

### Referral Links
Affiliates can use their unique code in URLs:
```
https://your-site.com/?ref=AFFILIATE123
https://your-site.com/product/item/?ref=AFFILIATE123
```

### Commission Calculation
Commissions are calculated based on:
- **Percentage**: Commission rate × order amount
- **Fixed**: Fixed amount per order
- **Apply to**: Subtotal, total, or profit

### Refunds
When orders are refunded:
- Pending/approved commissions are deleted
- Paid commissions generate negative adjustments

## Filters & Actions

### Filters
```php
// Modify commission calculation
apply_filters('wp_dashboard_pro_calculate_commission', $commission, $order, $affiliate);
```

### Actions
```php
// Commission created
do_action('wp_dashboard_pro_commission_created', $commission_id, $order, $affiliate);

// Commission approved
do_action('wp_dashboard_pro_commission_approved', $commission);

// Affiliate approved
do_action('wp_dashboard_pro_affiliate_approved', $affiliate);

// Withdrawal requested
do_action('wp_dashboard_pro_withdrawal_requested', $withdrawal_id);
```

## Security

- ✅ JWT token-based authentication
- ✅ CORS configuration
- ✅ Role-based permissions
- ✅ SQL injection prevention
- ✅ Input sanitization
- ✅ Nonce verification
- ✅ Secure cookie handling

## Support & Documentation

For detailed documentation and support, visit your plugin settings page in WordPress admin.

## License

GPL-2.0+

## Version

1.0.0
