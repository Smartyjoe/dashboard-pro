// User & Role Types
export type UserRole = 'super_admin' | 'administrator' | 'store_manager' | 'customer' | 'affiliate';

export interface User {
  id: number;
  username: string;
  email: string;
  firstName: string;
  lastName: string;
  displayName: string;
  avatar: string;
  role: UserRole;
  createdAt: string;
  isAffiliate: boolean;
  affiliateId?: number;
}

export interface RolePermission {
  id: string;
  name: string;
  description: string;
  enabled: boolean;
}

export interface RoleConfig {
  role: UserRole;
  displayName: string;
  permissions: RolePermission[];
}

// Affiliate Types
export interface Affiliate {
  id: number;
  userId: number;
  user: User;
  referralCode: string;
  parentAffiliateId?: number;
  tier: 1 | 2;
  status: 'pending' | 'active' | 'suspended';
  totalEarnings: number;
  pendingBalance: number;
  availableBalance: number;
  totalReferrals: number;
  tier2Referrals: number;
  conversionRate: number;
  createdAt: string;
  bankDetails?: BankDetails;
}

export interface BankDetails {
  bankName: string;
  accountName: string;
  accountNumber: string;
  routingNumber?: string;
  swiftCode?: string;
}

// Commission Types
export interface Commission {
  id: number;
  affiliateId: number;
  orderId: number;
  productId?: number;
  productName?: string;
  orderTotal: number;
  commissionAmount: number;
  commissionRate: number;
  tier: 1 | 2;
  status: 'pending' | 'approved' | 'paid' | 'cancelled';
  createdAt: string;
  paidAt?: string;
}

export interface CommissionRule {
  id: number;
  type: 'global' | 'product' | 'category';
  targetId?: number;
  targetName?: string;
  commissionType: 'percentage' | 'fixed';
  tier1Rate: number;
  tier2Rate: number;
  isActive: boolean;
}

// Withdrawal Types
export interface Withdrawal {
  id: number;
  affiliateId: number;
  affiliate: Affiliate;
  amount: number;
  status: 'pending' | 'processing' | 'completed' | 'rejected';
  requestedAt: string;
  processedAt?: string;
  processedBy?: number;
  notes?: string;
  transactionId?: string;
}

// Analytics Types
export interface AnalyticsSummary {
  totalSales: number;
  totalCommissions: number;
  totalAffiliates: number;
  activeAffiliates: number;
  pendingWithdrawals: number;
  pendingWithdrawalAmount: number;
  conversionRate: number;
  totalClicks: number;
  periodComparison: {
    salesChange: number;
    commissionsChange: number;
    affiliatesChange: number;
  };
}

export interface ChartDataPoint {
  date: string;
  value: number;
  label?: string;
}

// Email Template Types
export interface EmailTemplate {
  id: string;
  name: string;
  subject: string;
  body: string;
  variables: string[];
  isActive: boolean;
  lastModified: string;
}

// Activity Log Types
export interface ActivityLog {
  id: number;
  userId: number;
  userName: string;
  action: string;
  details: string;
  ipAddress?: string;
  createdAt: string;
}

// WooCommerce Types
export interface Product {
  id: number;
  name: string;
  slug: string;
  price: string;
  regularPrice: string;
  salePrice?: string;
  image: string;
  status: 'publish' | 'draft' | 'pending';
  stockStatus: 'instock' | 'outofstock' | 'onbackorder';
  totalSales: number;
}

export interface Order {
  id: number;
  status: string;
  total: string;
  customerName: string;
  customerEmail: string;
  items: OrderItem[];
  createdAt: string;
  affiliateId?: number;
}

export interface OrderItem {
  productId: number;
  productName: string;
  quantity: number;
  total: string;
}

// API Response Types
export interface ApiResponse<T> {
  success: boolean;
  data?: T;
  error?: string;
  message?: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  total: number;
  page: number;
  perPage: number;
  totalPages: number;
}
