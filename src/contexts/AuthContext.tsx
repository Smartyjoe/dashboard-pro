import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { User, UserRole } from '@/types';

interface AuthContextType {
  user: User | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  login: (email: string, password: string) => Promise<boolean>;
  logout: () => void;
  hasPermission: (permission: string) => boolean;
  hasRole: (roles: UserRole | UserRole[]) => boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

// Mock user data for demonstration
const mockUsers: Record<string, User> = {
  'admin@example.com': {
    id: 1,
    username: 'superadmin',
    email: 'admin@example.com',
    firstName: 'John',
    lastName: 'Admin',
    displayName: 'John Admin',
    avatar: '',
    role: 'super_admin',
    createdAt: '2024-01-01T00:00:00Z',
    isAffiliate: false,
  },
  'manager@example.com': {
    id: 2,
    username: 'storemanager',
    email: 'manager@example.com',
    firstName: 'Sarah',
    lastName: 'Manager',
    displayName: 'Sarah Manager',
    avatar: '',
    role: 'store_manager',
    createdAt: '2024-01-15T00:00:00Z',
    isAffiliate: false,
  },
  'affiliate@example.com': {
    id: 3,
    username: 'affiliate1',
    email: 'affiliate@example.com',
    firstName: 'Mike',
    lastName: 'Partner',
    displayName: 'Mike Partner',
    avatar: '',
    role: 'affiliate',
    createdAt: '2024-02-01T00:00:00Z',
    isAffiliate: true,
    affiliateId: 1,
  },
};

// Role-based permissions map
const rolePermissions: Record<UserRole, string[]> = {
  super_admin: ['*'], // All permissions
  administrator: [
    'view_dashboard',
    'manage_users',
    'manage_affiliates',
    'manage_commissions',
    'manage_withdrawals',
    'view_analytics',
    'manage_products',
    'manage_orders',
    'manage_email_templates',
  ],
  store_manager: [
    'view_dashboard',
    'manage_products',
    'manage_orders',
    'view_analytics',
  ],
  customer: [
    'view_products',
    'manage_profile',
  ],
  affiliate: [
    'view_affiliate_dashboard',
    'manage_profile',
    'view_commissions',
    'request_withdrawal',
    'generate_links',
  ],
};

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const init = async () => {
      const access = localStorage.getItem('wp_access_token');
      const refresh = localStorage.getItem('wp_refresh_token');

      const { wordpressApi } = await import('@/services/wordpress-api');

      // Hook token refresher for auto-retry
      wordpressApi.setTokenRefresher(async () => {
        const storedRefresh = localStorage.getItem('wp_refresh_token');
        if (!storedRefresh) return null;
        const res = await wordpressApi.refreshToken(storedRefresh);
        if (res.success && res.data) {
          localStorage.setItem('wp_access_token', res.data.access_token);
          return res.data.access_token;
        }
        return null;
      });

      if (access) {
        wordpressApi.setAuthToken(access);
        // Try to fetch current user from backend
        const me = await wordpressApi.getCurrentUser();
        if (me.success && me.data) {
          const wpUser: any = me.data as any;
          const primaryRole: string = (wpUser.roles && wpUser.roles[0]) || 'customer';
          const mapRole = (r: string): UserRole => {
            if (r === 'shop_manager') return 'store_manager';
            if (r === 'administrator') return 'administrator';
            if (r === 'affiliate') return 'affiliate';
            return 'customer';
          };
          const mapped: User = {
            id: wpUser.id,
            username: wpUser.username || wpUser.user_login,
            email: wpUser.email || wpUser.user_email,
            firstName: wpUser.first_name || '',
            lastName: wpUser.last_name || '',
            displayName: wpUser.display_name || wpUser.username,
            avatar: wpUser.avatar_url || '',
            role: mapRole(primaryRole),
            createdAt: new Date().toISOString(),
            isAffiliate: Array.isArray(wpUser.roles) && wpUser.roles.includes('affiliate'),
            affiliateId: wpUser.affiliate?.id,
          };
          setUser(mapped);
          localStorage.setItem('wp_dashboard_user', JSON.stringify(mapped));
        } else if (refresh) {
          // Try refresh flow then retry user fetch
          const res = await wordpressApi.refreshToken(refresh);
          if (res.success && res.data) {
            const newAccess = res.data.access_token;
            localStorage.setItem('wp_access_token', newAccess);
            wordpressApi.setAuthToken(newAccess);
            const me2 = await wordpressApi.getCurrentUser();
            if (me2.success && me2.data) {
              const wpUser: any = me2.data as any;
              const primaryRole: string = (wpUser.roles && wpUser.roles[0]) || 'customer';
              const mapRole = (r: string): UserRole => {
                if (r === 'shop_manager') return 'store_manager';
                if (r === 'administrator') return 'administrator';
                if (r === 'affiliate') return 'affiliate';
                return 'customer';
              };
              const mapped: User = {
                id: wpUser.id,
                username: wpUser.username || wpUser.user_login,
                email: wpUser.email || wpUser.user_email,
                firstName: wpUser.first_name || '',
                lastName: wpUser.last_name || '',
                displayName: wpUser.display_name || wpUser.username,
                avatar: wpUser.avatar_url || '',
                role: mapRole(primaryRole),
                createdAt: new Date().toISOString(),
                isAffiliate: Array.isArray(wpUser.roles) && wpUser.roles.includes('affiliate'),
                affiliateId: wpUser.affiliate?.id,
              };
              setUser(mapped);
              localStorage.setItem('wp_dashboard_user', JSON.stringify(mapped));
            }
          }
        }
      }
      setIsLoading(false);
    };
    init();
  }, []);

  const login = async (email: string, password: string): Promise<boolean> => {
    setIsLoading(true);
    
    try {
      // Connect to WordPress Dashboard Pro API
      const { wordpressApi } = await import('@/services/wordpress-api');
      const response = await wordpressApi.login(email, password);
      
      if (response.success && response.data) {
        // Store tokens
        localStorage.setItem('wp_access_token', response.data.access_token);
        localStorage.setItem('wp_refresh_token', response.data.refresh_token);
        
        // Set auth token and refresher in API service
        wordpressApi.setAuthToken(response.data.access_token);
        wordpressApi.setTokenRefresher(async () => {
          const storedRefresh = localStorage.getItem('wp_refresh_token');
          if (!storedRefresh) return null;
          const res = await wordpressApi.refreshToken(storedRefresh);
          if (res.success && res.data) {
            localStorage.setItem('wp_access_token', res.data.access_token);
            return res.data.access_token;
          }
          return null;
        });
        
        // Map WordPress user to our User type
        const wpUser = response.data.user;
        const primaryRole: string = (wpUser.roles && wpUser.roles[0]) || 'customer';
        const mapRole = (r: string): UserRole => {
          if (r === 'shop_manager') return 'store_manager';
          if (r === 'administrator') return 'administrator';
          if (r === 'affiliate') return 'affiliate';
          return 'customer';
        };
        const user: User = {
          id: wpUser.id,
          username: wpUser.username,
          email: wpUser.email,
          firstName: wpUser.first_name || '',
          lastName: wpUser.last_name || '',
          displayName: wpUser.display_name,
          avatar: wpUser.avatar_url || '',
          role: mapRole(primaryRole),
          createdAt: new Date().toISOString(),
          isAffiliate: Array.isArray(wpUser.roles) && wpUser.roles.includes('affiliate'),
          affiliateId: wpUser.affiliate?.id,
        };
        
        setUser(user);
        localStorage.setItem('wp_dashboard_user', JSON.stringify(user));
        setIsLoading(false);
        return true;
      }
      
      setIsLoading(false);
      return false;
    } catch (error) {
      console.error('Login error:', error);
      setIsLoading(false);
      return false;
    }
  };

  const logout = async () => {
    try {
      const { wordpressApi } = await import('@/services/wordpress-api');
      await wordpressApi.logout();
      wordpressApi.clearAuthToken();
    } catch (error) {
      console.error('Logout error:', error);
    }
    
    setUser(null);
    localStorage.removeItem('wp_dashboard_user');
    localStorage.removeItem('wp_access_token');
    localStorage.removeItem('wp_refresh_token');
  };

  const hasPermission = (permission: string): boolean => {
    if (!user) return false;
    
    const permissions = rolePermissions[user.role];
    return permissions.includes('*') || permissions.includes(permission);
  };

  const hasRole = (roles: UserRole | UserRole[]): boolean => {
    if (!user) return false;
    
    const roleArray = Array.isArray(roles) ? roles : [roles];
    return roleArray.includes(user.role);
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        isLoading,
        isAuthenticated: !!user,
        login,
        logout,
        hasPermission,
        hasRole,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}
