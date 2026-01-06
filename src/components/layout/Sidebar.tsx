import React, { useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { cn } from '@/lib/utils';
import { useAuth } from '@/contexts/AuthContext';
import { useTheme } from '@/contexts/ThemeContext';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
  LayoutDashboard,
  Users,
  ShoppingBag,
  DollarSign,
  Wallet,
  BarChart3,
  Settings,
  Mail,
  Shield,
  Link2,
  LogOut,
  ChevronLeft,
  ChevronRight,
  Moon,
  Sun,
  Package,
  Ticket,
  UserCog,
  Activity,
  Menu,
} from 'lucide-react';

interface NavItem {
  title: string;
  href: string;
  icon: React.ElementType;
  permission?: string;
  roles?: string[];
}

const adminNavItems: NavItem[] = [
  { title: 'Dashboard', href: '/admin', icon: LayoutDashboard },
  { title: 'Users', href: '/admin/users', icon: Users, permission: 'manage_users' },
  { title: 'Affiliates', href: '/admin/affiliates', icon: Link2, permission: 'manage_affiliates' },
  { title: 'Commissions', href: '/admin/commissions', icon: DollarSign, permission: 'manage_commissions' },
  { title: 'Withdrawals', href: '/admin/withdrawals', icon: Wallet, permission: 'manage_withdrawals' },
  { title: 'Products', href: '/admin/products', icon: Package, permission: 'manage_products' },
  { title: 'Orders', href: '/admin/orders', icon: ShoppingBag, permission: 'manage_orders' },
  { title: 'Coupons', href: '/admin/coupons', icon: Ticket, permission: 'manage_products' },
  { title: 'Analytics', href: '/admin/analytics', icon: BarChart3, permission: 'view_analytics' },
  { title: 'Email Templates', href: '/admin/emails', icon: Mail, permission: 'manage_email_templates' },
  { title: 'Role Management', href: '/admin/roles', icon: Shield, roles: ['super_admin'] },
  { title: 'Activity Log', href: '/admin/activity', icon: Activity, roles: ['super_admin', 'administrator'] },
  { title: 'Settings', href: '/admin/settings', icon: Settings },
];

const affiliateNavItems: NavItem[] = [
  { title: 'Dashboard', href: '/affiliate', icon: LayoutDashboard },
  { title: 'My Links', href: '/affiliate/links', icon: Link2 },
  { title: 'Commissions', href: '/affiliate/commissions', icon: DollarSign },
  { title: 'Withdrawals', href: '/affiliate/withdrawals', icon: Wallet },
  { title: 'Analytics', href: '/affiliate/analytics', icon: BarChart3 },
  { title: 'Profile', href: '/affiliate/profile', icon: UserCog },
];

interface SidebarProps {
  collapsed: boolean;
  onCollapse: (collapsed: boolean) => void;
  mobileOpen: boolean;
  onMobileClose: () => void;
}

export default function Sidebar({ collapsed, onCollapse, mobileOpen, onMobileClose }: SidebarProps) {
  const location = useLocation();
  const { user, logout, hasPermission, hasRole } = useAuth();
  const { resolvedTheme, toggleTheme } = useTheme();
  
  const isAffiliate = user?.role === 'affiliate';
  const navItems = isAffiliate ? affiliateNavItems : adminNavItems;

  const filteredNavItems = navItems.filter(item => {
    if (item.roles && !hasRole(item.roles as any)) return false;
    if (item.permission && !hasPermission(item.permission)) return false;
    return true;
  });

  const NavContent = () => (
    <>
      {/* Logo */}
      <div className={cn(
        "flex items-center gap-3 px-4 py-6 border-b border-sidebar-border",
        collapsed && "justify-center px-2"
      )}>
        <div className="w-10 h-10 rounded-xl gradient-primary flex items-center justify-center shadow-glow-sm">
          <span className="text-xl font-bold text-primary-foreground">W</span>
        </div>
        {!collapsed && (
          <div className="animate-fade-in">
            <h1 className="font-bold text-lg text-foreground">WP Dashboard</h1>
            <p className="text-xs text-muted-foreground">Affiliate System</p>
          </div>
        )}
      </div>

      {/* Navigation */}
      <ScrollArea className="flex-1 px-3 py-4">
        <nav className="space-y-1">
          {filteredNavItems.map((item) => {
            const isActive = location.pathname === item.href;
            const Icon = item.icon;
            
            return (
              <Link
                key={item.href}
                to={item.href}
                onClick={onMobileClose}
                className={cn(
                  "nav-link",
                  isActive && "active",
                  collapsed && "justify-center px-2"
                )}
                title={collapsed ? item.title : undefined}
              >
                <Icon className="h-5 w-5 flex-shrink-0" />
                {!collapsed && (
                  <span className="animate-fade-in">{item.title}</span>
                )}
              </Link>
            );
          })}
        </nav>
      </ScrollArea>

      {/* Footer */}
      <div className="border-t border-sidebar-border p-3 space-y-2">
        {/* Theme Toggle */}
        <Button
          variant="ghost"
          size="sm"
          onClick={toggleTheme}
          className={cn(
            "w-full justify-start gap-3 text-muted-foreground hover:text-foreground",
            collapsed && "justify-center px-2"
          )}
        >
          {resolvedTheme === 'dark' ? (
            <Sun className="h-5 w-5" />
          ) : (
            <Moon className="h-5 w-5" />
          )}
          {!collapsed && (
            <span>{resolvedTheme === 'dark' ? 'Light Mode' : 'Dark Mode'}</span>
          )}
        </Button>

        {/* User Info */}
        {!collapsed && user && (
          <div className="flex items-center gap-3 px-3 py-2 rounded-lg bg-secondary/50 animate-fade-in">
            <div className="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center">
              <span className="text-sm font-medium text-primary">
                {user.firstName[0]}{user.lastName[0]}
              </span>
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium truncate">{user.displayName}</p>
              <p className="text-xs text-muted-foreground capitalize truncate">
                {user.role.replace('_', ' ')}
              </p>
            </div>
          </div>
        )}

        {/* Logout */}
        <Button
          variant="ghost"
          size="sm"
          onClick={logout}
          className={cn(
            "w-full justify-start gap-3 text-muted-foreground hover:text-destructive",
            collapsed && "justify-center px-2"
          )}
        >
          <LogOut className="h-5 w-5" />
          {!collapsed && <span>Log out</span>}
        </Button>

        {/* Collapse Button (Desktop only) */}
        <Button
          variant="ghost"
          size="sm"
          onClick={() => onCollapse(!collapsed)}
          className="w-full justify-center text-muted-foreground hover:text-foreground hidden md:flex"
        >
          {collapsed ? (
            <ChevronRight className="h-5 w-5" />
          ) : (
            <ChevronLeft className="h-5 w-5" />
          )}
        </Button>
      </div>
    </>
  );

  return (
    <>
      {/* Mobile Overlay */}
      {mobileOpen && (
        <div 
          className="fixed inset-0 bg-background/80 backdrop-blur-sm z-40 md:hidden"
          onClick={onMobileClose}
        />
      )}

      {/* Mobile Sidebar */}
      <aside
        className={cn(
          "fixed inset-y-0 left-0 z-50 w-72 bg-sidebar border-r border-sidebar-border flex flex-col transition-transform duration-300 md:hidden",
          mobileOpen ? "translate-x-0" : "-translate-x-full"
        )}
      >
        <NavContent />
      </aside>

      {/* Desktop Sidebar */}
      <aside
        className={cn(
          "hidden md:flex fixed inset-y-0 left-0 z-40 bg-sidebar border-r border-sidebar-border flex-col transition-all duration-300",
          collapsed ? "w-[72px]" : "w-64"
        )}
      >
        <NavContent />
      </aside>
    </>
  );
}
