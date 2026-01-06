/**
 * WordPress API Service
 * 
 * This service handles all communication with the WordPress REST API.
 * It includes methods for authentication, WooCommerce operations,
 * and custom affiliate system endpoints.
 * 
 * IMPORTANT: This is a template. You'll need to implement the corresponding
 * REST API endpoints in WordPress to make this fully functional.
 */

import { config, getApiUrl } from '@/config/wordpress';
import { 
  User, 
  Affiliate, 
  Commission, 
  Withdrawal, 
  CommissionRule,
  Product,
  Order,
  EmailTemplate,
  ActivityLog,
  ApiResponse,
  PaginatedResponse,
} from '@/types';

class WordPressApiService {
  private authToken: string | null = null;

  /**
   * Set the authentication token for API requests
   */
  setAuthToken(token: string) {
    this.authToken = token;
  }

  /**
   * Clear the authentication token
   */
  clearAuthToken() {
    this.authToken = null;
  }

  /**
   * Make an authenticated API request
   */
  private tokenRefresher: null | (() => Promise<string | null>) = null;

  setTokenRefresher(handler: () => Promise<string | null>) {
    this.tokenRefresher = handler;
  }

  private async request<T>(
    endpoint: string,
    options: RequestInit = {},
    _retry = false
  ): Promise<ApiResponse<T>> {
    const url = getApiUrl(endpoint);
    
    const headers: HeadersInit = {
      'Content-Type': 'application/json',
      ...options.headers,
    };

    if (this.authToken) {
      headers['Authorization'] = `Bearer ${this.authToken}`;
    }

    try {
      const response = await fetch(url, {
        ...options,
        headers,
      });

      const data = await response.json();

      if (!response.ok) {
        // If unauthorized and we have a refresher, try once
        if (response.status === 401 && this.tokenRefresher && !_retry) {
          const newToken = await this.tokenRefresher();
          if (newToken) {
            this.setAuthToken(newToken);
            return this.request<T>(endpoint, options, true);
          }
        }
        return {
          success: false,
          error: data?.message || 'An error occurred',
        };
      }

      return {
        success: true,
        data,
      };
    } catch (error) {
      return {
        success: false,
        error: error instanceof Error ? error.message : 'Network error',
      };
    }
  }

  // ============================================
  // AUTHENTICATION
  // ============================================

  /**
   * Authenticate user and get JWT token
   */
  async login(username: string, password: string): Promise<ApiResponse<{ 
    access_token: string; 
    refresh_token: string;
    user: User;
    settings: any;
  }>> {
    return this.request(config.endpoints.auth.login, {
      method: 'POST',
      body: JSON.stringify({ username, password }),
    });
  }

  /**
   * Refresh access token
   */
  async refreshToken(refreshToken: string): Promise<ApiResponse<{ access_token: string }>> {
    return this.request(config.endpoints.auth.refresh, {
      method: 'POST',
      body: JSON.stringify({ refresh_token: refreshToken }),
    });
  }

  /**
   * Validate the current token
   */
  async validateToken(): Promise<ApiResponse<{ valid: boolean; user_id: number }>> {
    return this.request(config.endpoints.auth.validate, {
      method: 'GET',
    });
  }

  /**
   * Get current user
   */
  async getCurrentUser(): Promise<ApiResponse<User>> {
    return this.request(config.endpoints.auth.me, {
      method: 'GET',
    });
  }

  /**
   * Logout
   */
  async logout(): Promise<ApiResponse<void>> {
    return this.request(config.endpoints.auth.logout, {
      method: 'POST',
    });
  }

  // ============================================
  // USERS
  // ============================================

  /**
   * Get all users with optional filters
   */
  async getUsers(params?: {
    role?: string;
    page?: number;
    perPage?: number;
    search?: string;
  }): Promise<ApiResponse<PaginatedResponse<User>>> {
    const queryParams = new URLSearchParams();
    if (params?.role) queryParams.set('role', params.role);
    if (params?.page) queryParams.set('page', params.page.toString());
    if (params?.perPage) queryParams.set('per_page', params.perPage.toString());
    if (params?.search) queryParams.set('search', params.search);

    return this.request(`${config.endpoints.users}?${queryParams}`);
  }

  /**
   * Get a single user by ID
   */
  async getUser(userId: number): Promise<ApiResponse<User>> {
    return this.request(`${config.endpoints.users}/${userId}`);
  }

  /**
   * Create a new user
   */
  async createUser(userData: Partial<User>): Promise<ApiResponse<User>> {
    return this.request(config.endpoints.users, {
      method: 'POST',
      body: JSON.stringify(userData),
    });
  }

  /**
   * Update a user
   */
  async updateUser(userId: number, userData: Partial<User>): Promise<ApiResponse<User>> {
    return this.request(`${config.endpoints.users}/${userId}`, {
      method: 'PUT',
      body: JSON.stringify(userData),
    });
  }

  // ============================================
  // AFFILIATES
  // ============================================

  /**
   * Get all affiliates
   */
  async getAffiliates(params?: {
    status?: string;
    tier?: number;
    page?: number;
    perPage?: number;
  }): Promise<ApiResponse<PaginatedResponse<Affiliate>>> {
    const queryParams = new URLSearchParams();
    if (params?.status) queryParams.set('status', params.status);
    if (params?.tier) queryParams.set('tier', params.tier.toString());
    if (params?.page) queryParams.set('page', params.page.toString());
    if (params?.perPage) queryParams.set('per_page', params.perPage.toString());

    return this.request(`${config.endpoints.affiliates}?${queryParams}`);
  }

  /**
   * Get a single affiliate by ID
   */
  async getAffiliate(affiliateId: number): Promise<ApiResponse<Affiliate>> {
    return this.request(`${config.endpoints.affiliates}/${affiliateId}`);
  }

  /**
   * Create a new affiliate (upgrade user to affiliate)
   */
  async createAffiliate(userId: number, data?: { parentAffiliateId?: number }): Promise<ApiResponse<Affiliate>> {
    return this.request(config.endpoints.affiliates, {
      method: 'POST',
      body: JSON.stringify({ userId, ...data }),
    });
  }

  /**
   * Update affiliate status
   */
  async updateAffiliateStatus(affiliateId: number, status: 'active' | 'suspended'): Promise<ApiResponse<Affiliate>> {
    return this.request(`${config.endpoints.affiliates}/${affiliateId}`, {
      method: 'PUT',
      body: JSON.stringify({ status }),
    });
  }

  /**
   * Get affiliate referral link
   */
  async getAffiliateLink(affiliateId: number, productId?: number): Promise<ApiResponse<{ link: string }>> {
    const queryParams = productId ? `?product_id=${productId}` : '';
    return this.request(`${config.endpoints.affiliates}/${affiliateId}/link${queryParams}`);
  }

  // ============================================
  // COMMISSIONS
  // ============================================

  /**
   * Get all commissions
   */
  async getCommissions(params?: {
    affiliateId?: number;
    status?: string;
    tier?: number;
    page?: number;
    perPage?: number;
  }): Promise<ApiResponse<PaginatedResponse<Commission>>> {
    const queryParams = new URLSearchParams();
    if (params?.affiliateId) queryParams.set('affiliate_id', params.affiliateId.toString());
    if (params?.status) queryParams.set('status', params.status);
    if (params?.tier) queryParams.set('tier', params.tier.toString());
    if (params?.page) queryParams.set('page', params.page.toString());
    if (params?.perPage) queryParams.set('per_page', params.perPage.toString());

    return this.request(`${config.endpoints.commissions}?${queryParams}`);
  }

  /**
   * Update commission status
   */
  async updateCommissionStatus(commissionId: number, status: 'approved' | 'paid' | 'cancelled'): Promise<ApiResponse<Commission>> {
    return this.request(`${config.endpoints.commissions}/${commissionId}`, {
      method: 'PUT',
      body: JSON.stringify({ status }),
    });
  }

  /**
   * Get commission rules
   */
  async getCommissionRules(): Promise<ApiResponse<CommissionRule[]>> {
    return this.request(`${config.endpoints.commissions}/rules`);
  }

  /**
   * Create/update commission rule
   */
  async saveCommissionRule(rule: Partial<CommissionRule>): Promise<ApiResponse<CommissionRule>> {
    return this.request(`${config.endpoints.commissions}/rules`, {
      method: 'POST',
      body: JSON.stringify(rule),
    });
  }

  // ============================================
  // WITHDRAWALS
  // ============================================

  /**
   * Get all withdrawals
   */
  async getWithdrawals(params?: {
    affiliateId?: number;
    status?: string;
    page?: number;
    perPage?: number;
  }): Promise<ApiResponse<PaginatedResponse<Withdrawal>>> {
    const queryParams = new URLSearchParams();
    if (params?.affiliateId) queryParams.set('affiliate_id', params.affiliateId.toString());
    if (params?.status) queryParams.set('status', params.status);
    if (params?.page) queryParams.set('page', params.page.toString());
    if (params?.perPage) queryParams.set('per_page', params.perPage.toString());

    return this.request(`${config.endpoints.withdrawals}?${queryParams}`);
  }

  /**
   * Request a withdrawal (affiliate)
   */
  async requestWithdrawal(affiliateId: number, amount: number): Promise<ApiResponse<Withdrawal>> {
    return this.request(config.endpoints.withdrawals, {
      method: 'POST',
      body: JSON.stringify({ affiliateId, amount }),
    });
  }

  /**
   * Process withdrawal (admin)
   */
  async processWithdrawal(
    withdrawalId: number, 
    action: 'approve' | 'reject', 
    data?: { transactionId?: string; notes?: string }
  ): Promise<ApiResponse<Withdrawal>> {
    return this.request(`${config.endpoints.withdrawals}/${withdrawalId}`, {
      method: 'PUT',
      body: JSON.stringify({ action, ...data }),
    });
  }

  // ============================================
  // WOOCOMMERCE - PRODUCTS
  // ============================================

  /**
   * Get WooCommerce products
   */
  async getProducts(params?: {
    page?: number;
    perPage?: number;
    search?: string;
    status?: string;
  }): Promise<ApiResponse<PaginatedResponse<Product>>> {
    const queryParams = new URLSearchParams();
    if (params?.page) queryParams.set('page', params.page.toString());
    if (params?.perPage) queryParams.set('per_page', params.perPage.toString());
    if (params?.search) queryParams.set('search', params.search);
    if (params?.status) queryParams.set('status', params.status);

    const qs = queryParams.toString();
    const url = qs ? `${config.endpoints.products}?${qs}` : config.endpoints.products;
    return this.request(url);
  }

  /**
   * Create a WooCommerce product (Simple products for first pass)
   */
  async createProduct(payload: any): Promise<ApiResponse<Product>> {
    return this.request(config.endpoints.products, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
  }

  /**
   * Update a WooCommerce product
   */
  async updateProduct(id: number, payload: any): Promise<ApiResponse<Product>> {
    return this.request(`${config.endpoints.products}/${id}`, {
      method: 'PUT',
      body: JSON.stringify(payload),
    });
  }

  /**
   * Delete a WooCommerce product
   */
  async deleteProduct(id: number): Promise<ApiResponse<{ deleted: boolean }>> {
    return this.request(`${config.endpoints.products}/${id}`, {
      method: 'DELETE',
    });
  }

  /**
   * Upload product image (multipart/form-data)
   */
  async uploadProductImage(productId: number, file: File, isFeatured = false): Promise<ApiResponse<{ id: number; url: string; is_featured: boolean }>> {
    const form = new FormData();
    form.append('image', file);
    form.append('is_featured', String(isFeatured));
    const endpoint = `${config.endpoints.products}/${productId}/image`;
    // Note: Do not set Content-Type; browser will set multipart boundary
    return this.request(endpoint, {
      method: 'POST',
      body: form as any,
      // Remove default JSON header for multipart
      headers: { 'Content-Type': undefined as any },
    } as any);
  }

  /**
   * Delete a product image
   */
  async deleteProductImage(productId: number, imageId: number): Promise<ApiResponse<{ deleted: boolean }>> {
    const endpoint = `${config.endpoints.products}/${productId}/image/${imageId}`;
    return this.request(endpoint, { method: 'DELETE' });
  }

  /** Get product categories */
  async getProductCategories(): Promise<ApiResponse<Array<{ id: number; name: string }>>> {
    return this.request(config.endpoints.productCategories);
  }

  /** Get media library (images only) */
  async getProductMedia(params?: { page?: number; perPage?: number; search?: string }): Promise<ApiResponse<PaginatedResponse<{ id: number; url: string; title: string }>>> {
    const query = new URLSearchParams();
    if (params?.page) query.set('page', String(params.page));
    if (params?.perPage) query.set('per_page', String(params.perPage));
    if (params?.search) query.set('search', params.search);
    const url = query.toString() ? `${config.endpoints.productMedia}?${query}` : config.endpoints.productMedia;
    return this.request(url);
  }

  // ============================================
  // WOOCOMMERCE - ORDERS
  // ============================================

  /**
   * Get WooCommerce orders
   */
  async getOrders(params?: {
    page?: number;
    perPage?: number;
    status?: string;
    search?: string;
  }): Promise<ApiResponse<PaginatedResponse<Order>>> {
    const queryParams = new URLSearchParams();
    if (params?.page) queryParams.set('page', params.page.toString());
    if (params?.perPage) queryParams.set('per_page', params.perPage.toString());
    if (params?.status) queryParams.set('status', params.status);
    if (params?.search) queryParams.set('search', params.search);

    const qs = queryParams.toString();
    const url = qs ? `${config.endpoints.orders}?${qs}` : config.endpoints.orders;
    return this.request(url);
  }

  /** Get a single order */
  async getOrder(id: number): Promise<ApiResponse<Order>> {
    return this.request(`${config.endpoints.orders}/${id}`);
  }

  /** Update order status */
  async updateOrderStatus(id: number, status: string, note?: string): Promise<ApiResponse<{ id: number; status: string }>> {
    return this.request(`${config.endpoints.orders}/${id}/status`, {
      method: 'PUT',
      body: JSON.stringify({ status, note }),
    });
  }

  // ============================================
  // EMAIL TEMPLATES
  // ============================================

  /**
   * Get email templates
   */
  async getEmailTemplates(): Promise<ApiResponse<EmailTemplate[]>> {
    return this.request(config.endpoints.emailTemplates);
  }

  /**
   * Update email template
   */
  async updateEmailTemplate(templateId: string, data: Partial<EmailTemplate>): Promise<ApiResponse<EmailTemplate>> {
    return this.request(`${config.endpoints.emailTemplates}/${templateId}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  }

  // ============================================
  // ROLE PERMISSIONS
  // ============================================

  /**
   * Get role permissions
   */
  async getRolePermissions(role: string): Promise<ApiResponse<Record<string, boolean>>> {
    return this.request(`${config.endpoints.permissions}/${role}`);
  }

  /**
   * Update role permissions
   */
  async updateRolePermissions(role: string, permissions: Record<string, boolean>): Promise<ApiResponse<void>> {
    return this.request(`${config.endpoints.permissions}/${role}`, {
      method: 'PUT',
      body: JSON.stringify(permissions),
    });
  }

  // ============================================
  // ACTIVITY LOG
  // ============================================

  /**
   * Get activity logs
   */
  async getActivityLogs(params?: {
    userId?: number;
    page?: number;
    perPage?: number;
  }): Promise<ApiResponse<PaginatedResponse<ActivityLog>>> {
    const queryParams = new URLSearchParams();
    if (params?.userId) queryParams.set('user_id', params.userId.toString());
    if (params?.page) queryParams.set('page', params.page.toString());
    if (params?.perPage) queryParams.set('per_page', params.perPage.toString());

    return this.request(`${config.endpoints.settings}/activity-log?${queryParams}`);
  }

  // ============================================
  // ANALYTICS
  // ============================================

  /**
   * Get analytics summary
   */
  async getAnalyticsSummary(period: 'day' | 'week' | 'month' | 'year' = 'month'): Promise<ApiResponse<{
    totalSales: number;
    totalCommissions: number;
    totalAffiliates: number;
    conversionRate: number;
  }>> {
    return this.request(`${config.endpoints.dashboard.analytics}?period=${period}`);
  }

  /**
   * Get chart data
   */
  async getChartData(metric: 'sales' | 'commissions' | 'clicks', period: 'week' | 'month' | 'year'): Promise<ApiResponse<{
    labels: string[];
    values: number[];
  }>> {
    return this.request(`${config.endpoints.dashboard.analytics}/chart?metric=${metric}&period=${period}`);
  }
}

// Export singleton instance
export const wordpressApi = new WordPressApiService();
export default wordpressApi;
