import React, { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import { Shield, Users, ShoppingBag, DollarSign, BarChart3, Mail, Settings, Eye, Edit, Trash2 } from 'lucide-react';

interface Permission {
  id: string;
  name: string;
  description: string;
  category: string;
}

interface RolePermissions {
  [key: string]: boolean;
}

const allPermissions: Permission[] = [
  // Dashboard & Analytics
  { id: 'view_dashboard', name: 'View Dashboard', description: 'Access the main dashboard', category: 'Dashboard' },
  { id: 'view_analytics', name: 'View Analytics', description: 'Access detailed analytics and reports', category: 'Dashboard' },
  
  // User Management
  { id: 'view_users', name: 'View Users', description: 'See user list and profiles', category: 'Users' },
  { id: 'manage_users', name: 'Manage Users', description: 'Create, edit, and delete users', category: 'Users' },
  { id: 'reset_passwords', name: 'Reset Passwords', description: 'Reset user passwords', category: 'Users' },
  
  // Affiliate Management
  { id: 'view_affiliates', name: 'View Affiliates', description: 'See affiliate list and details', category: 'Affiliates' },
  { id: 'manage_affiliates', name: 'Manage Affiliates', description: 'Approve, suspend, or modify affiliates', category: 'Affiliates' },
  { id: 'view_affiliate_links', name: 'View Affiliate Links', description: 'See affiliate referral links', category: 'Affiliates' },
  
  // Commission Management
  { id: 'view_commissions', name: 'View Commissions', description: 'See commission transactions', category: 'Commissions' },
  { id: 'manage_commissions', name: 'Manage Commissions', description: 'Modify commission rates and rules', category: 'Commissions' },
  { id: 'manage_tier2', name: 'Manage Tier 2 Settings', description: 'Configure two-tier commission system', category: 'Commissions' },
  
  // Withdrawals
  { id: 'view_withdrawals', name: 'View Withdrawals', description: 'See withdrawal requests', category: 'Withdrawals' },
  { id: 'approve_withdrawals', name: 'Approve Withdrawals', description: 'Process payout requests', category: 'Withdrawals' },
  
  // Products & Orders
  { id: 'view_products', name: 'View Products', description: 'See product catalog', category: 'Store' },
  { id: 'manage_products', name: 'Manage Products', description: 'Create, edit, and delete products', category: 'Store' },
  { id: 'view_orders', name: 'View Orders', description: 'See order list and details', category: 'Store' },
  { id: 'manage_orders', name: 'Manage Orders', description: 'Process and update orders', category: 'Store' },
  { id: 'manage_coupons', name: 'Manage Coupons', description: 'Create and manage discount codes', category: 'Store' },
  
  // Communication
  { id: 'view_email_templates', name: 'View Email Templates', description: 'See email template list', category: 'Communication' },
  { id: 'manage_email_templates', name: 'Manage Email Templates', description: 'Edit email templates', category: 'Communication' },
  
  // System
  { id: 'view_activity_log', name: 'View Activity Log', description: 'See system activity history', category: 'System' },
  { id: 'manage_settings', name: 'Manage Settings', description: 'Modify system configuration', category: 'System' },
];

const defaultRolePermissions: Record<string, RolePermissions> = {
  administrator: {
    view_dashboard: true,
    view_analytics: true,
    view_users: true,
    manage_users: true,
    reset_passwords: false,
    view_affiliates: true,
    manage_affiliates: true,
    view_affiliate_links: true,
    view_commissions: true,
    manage_commissions: true,
    manage_tier2: false,
    view_withdrawals: true,
    approve_withdrawals: true,
    view_products: true,
    manage_products: true,
    view_orders: true,
    manage_orders: true,
    manage_coupons: true,
    view_email_templates: true,
    manage_email_templates: true,
    view_activity_log: true,
    manage_settings: false,
  },
  store_manager: {
    view_dashboard: true,
    view_analytics: true,
    view_users: false,
    manage_users: false,
    reset_passwords: false,
    view_affiliates: true,
    manage_affiliates: false,
    view_affiliate_links: true,
    view_commissions: true,
    manage_commissions: false,
    manage_tier2: false,
    view_withdrawals: false,
    approve_withdrawals: false,
    view_products: true,
    manage_products: true,
    view_orders: true,
    manage_orders: true,
    manage_coupons: true,
    view_email_templates: false,
    manage_email_templates: false,
    view_activity_log: false,
    manage_settings: false,
  },
};

const categoryIcons: Record<string, React.ElementType> = {
  Dashboard: BarChart3,
  Users: Users,
  Affiliates: Users,
  Commissions: DollarSign,
  Withdrawals: DollarSign,
  Store: ShoppingBag,
  Communication: Mail,
  System: Settings,
};

export default function RoleManagementPage() {
  const [permissions, setPermissions] = useState(defaultRolePermissions);
  const [activeRole, setActiveRole] = useState('administrator');

  const handlePermissionToggle = (permissionId: string) => {
    setPermissions(prev => ({
      ...prev,
      [activeRole]: {
        ...prev[activeRole],
        [permissionId]: !prev[activeRole][permissionId],
      },
    }));
  };

  const categories = [...new Set(allPermissions.map(p => p.category))];

  const getEnabledCount = (role: string) => {
    return Object.values(permissions[role] || {}).filter(Boolean).length;
  };

  return (
    <div className="space-y-6 animate-fade-in">
      {/* Header */}
      <div>
        <h1 className="text-2xl md:text-3xl font-bold">Role Management</h1>
        <p className="text-muted-foreground">Configure permissions for each user role</p>
      </div>

      {/* Info Card */}
      <Card className="border-primary/20 bg-primary/5">
        <CardContent className="p-4 flex items-start gap-4">
          <div className="p-2 rounded-lg bg-primary/10">
            <Shield className="h-5 w-5 text-primary" />
          </div>
          <div>
            <h3 className="font-semibold">Super Admin Privileges</h3>
            <p className="text-sm text-muted-foreground mt-1">
              As a Super Admin, you have full access to all features. Use this page to configure 
              which features are available to other admin roles. Changes apply immediately.
            </p>
          </div>
        </CardContent>
      </Card>

      <Tabs value={activeRole} onValueChange={setActiveRole}>
        <TabsList className="grid w-full grid-cols-2">
          <TabsTrigger value="administrator" className="flex items-center gap-2">
            Administrator
            <Badge variant="secondary" className="ml-1">
              {getEnabledCount('administrator')}/{allPermissions.length}
            </Badge>
          </TabsTrigger>
          <TabsTrigger value="store_manager" className="flex items-center gap-2">
            Store Manager
            <Badge variant="secondary" className="ml-1">
              {getEnabledCount('store_manager')}/{allPermissions.length}
            </Badge>
          </TabsTrigger>
        </TabsList>

        {['administrator', 'store_manager'].map(role => (
          <TabsContent key={role} value={role} className="space-y-6 mt-6">
            {categories.map(category => {
              const Icon = categoryIcons[category] || Settings;
              const categoryPermissions = allPermissions.filter(p => p.category === category);
              
              return (
                <Card key={category}>
                  <CardHeader className="pb-4">
                    <div className="flex items-center gap-3">
                      <div className="p-2 rounded-lg bg-secondary">
                        <Icon className="h-5 w-5" />
                      </div>
                      <div>
                        <CardTitle className="text-lg">{category}</CardTitle>
                        <CardDescription>
                          {categoryPermissions.filter(p => permissions[role]?.[p.id]).length} of {categoryPermissions.length} enabled
                        </CardDescription>
                      </div>
                    </div>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-4">
                      {categoryPermissions.map(permission => (
                        <div 
                          key={permission.id}
                          className="flex items-center justify-between p-3 rounded-lg bg-muted/30 hover:bg-muted/50 transition-colors"
                        >
                          <div className="flex-1">
                            <Label className="font-medium cursor-pointer" htmlFor={`${role}-${permission.id}`}>
                              {permission.name}
                            </Label>
                            <p className="text-sm text-muted-foreground mt-0.5">
                              {permission.description}
                            </p>
                          </div>
                          <Switch
                            id={`${role}-${permission.id}`}
                            checked={permissions[role]?.[permission.id] ?? false}
                            onCheckedChange={() => handlePermissionToggle(permission.id)}
                          />
                        </div>
                      ))}
                    </div>
                  </CardContent>
                </Card>
              );
            })}

            <div className="flex justify-end gap-3">
              <Button variant="outline">Reset to Default</Button>
              <Button className="gradient-primary text-primary-foreground">
                Save Changes
              </Button>
            </div>
          </TabsContent>
        ))}
      </Tabs>
    </div>
  );
}
