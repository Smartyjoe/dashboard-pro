import React, { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { useAuth } from '@/contexts/AuthContext';
import { 
  User, 
  Key, 
  Bell, 
  Globe, 
  Wallet,
  Shield,
  Link2,
  CheckCircle,
  AlertCircle,
} from 'lucide-react';

export default function SettingsPage() {
  const { user } = useAuth();
  const [isConnected, setIsConnected] = useState(false);

  return (
    <div className="space-y-6 animate-fade-in">
      {/* Header */}
      <div>
        <h1 className="text-2xl md:text-3xl font-bold">Settings</h1>
        <p className="text-muted-foreground">Manage your account and system configuration</p>
      </div>

      <Tabs defaultValue="profile" className="space-y-6">
        <TabsList className="flex-wrap h-auto gap-2">
          <TabsTrigger value="profile" className="flex items-center gap-2">
            <User className="h-4 w-4" />
            Profile
          </TabsTrigger>
          <TabsTrigger value="security" className="flex items-center gap-2">
            <Key className="h-4 w-4" />
            Security
          </TabsTrigger>
          <TabsTrigger value="notifications" className="flex items-center gap-2">
            <Bell className="h-4 w-4" />
            Notifications
          </TabsTrigger>
          <TabsTrigger value="wordpress" className="flex items-center gap-2">
            <Globe className="h-4 w-4" />
            WordPress
          </TabsTrigger>
          <TabsTrigger value="withdrawals" className="flex items-center gap-2">
            <Wallet className="h-4 w-4" />
            Withdrawals
          </TabsTrigger>
        </TabsList>

        {/* Profile Settings */}
        <TabsContent value="profile">
          <Card>
            <CardHeader>
              <CardTitle>Profile Information</CardTitle>
              <CardDescription>Update your personal details</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="flex items-center gap-6">
                <div className="w-20 h-20 rounded-full gradient-primary flex items-center justify-center">
                  <span className="text-2xl font-bold text-primary-foreground">
                    {user?.firstName[0]}{user?.lastName[0]}
                  </span>
                </div>
                <div>
                  <Button variant="outline" size="sm">Change Photo</Button>
                  <p className="text-sm text-muted-foreground mt-1">JPG, PNG. Max 2MB</p>
                </div>
              </div>

              <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                  <Label>First Name</Label>
                  <Input defaultValue={user?.firstName} />
                </div>
                <div className="space-y-2">
                  <Label>Last Name</Label>
                  <Input defaultValue={user?.lastName} />
                </div>
                <div className="space-y-2">
                  <Label>Email</Label>
                  <Input type="email" defaultValue={user?.email} />
                </div>
                <div className="space-y-2">
                  <Label>Username</Label>
                  <Input defaultValue={user?.username} />
                </div>
              </div>

              <Button className="gradient-primary text-primary-foreground">
                Save Changes
              </Button>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Security Settings */}
        <TabsContent value="security">
          <div className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>Change Password</CardTitle>
                <CardDescription>Update your password regularly for security</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-2">
                  <Label>Current Password</Label>
                  <Input type="password" />
                </div>
                <div className="space-y-2">
                  <Label>New Password</Label>
                  <Input type="password" />
                </div>
                <div className="space-y-2">
                  <Label>Confirm New Password</Label>
                  <Input type="password" />
                </div>
                <Button className="gradient-primary text-primary-foreground">
                  Update Password
                </Button>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Two-Factor Authentication</CardTitle>
                <CardDescription>Add an extra layer of security to your account</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <div className="p-2 rounded-lg bg-warning/10">
                      <Shield className="h-5 w-5 text-warning" />
                    </div>
                    <div>
                      <p className="font-medium">Two-Factor Auth</p>
                      <p className="text-sm text-muted-foreground">Currently disabled</p>
                    </div>
                  </div>
                  <Button variant="outline">Enable</Button>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        {/* Notification Settings */}
        <TabsContent value="notifications">
          <Card>
            <CardHeader>
              <CardTitle>Email Notifications</CardTitle>
              <CardDescription>Choose what updates you want to receive</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              {[
                { id: 'new_sale', label: 'New Sale', description: 'Get notified when a new order is placed' },
                { id: 'affiliate_signup', label: 'Affiliate Sign Up', description: 'When a new affiliate joins your program' },
                { id: 'withdrawal_request', label: 'Withdrawal Request', description: 'When an affiliate requests a payout' },
                { id: 'commission_approved', label: 'Commission Approved', description: 'When commissions are approved' },
                { id: 'weekly_report', label: 'Weekly Report', description: 'Summary of your affiliate program performance' },
              ].map(notification => (
                <div key={notification.id} className="flex items-center justify-between">
                  <div>
                    <p className="font-medium">{notification.label}</p>
                    <p className="text-sm text-muted-foreground">{notification.description}</p>
                  </div>
                  <Switch defaultChecked />
                </div>
              ))}

              <Button className="gradient-primary text-primary-foreground">
                Save Preferences
              </Button>
            </CardContent>
          </Card>
        </TabsContent>

        {/* WordPress Connection */}
        <TabsContent value="wordpress">
          <div className="space-y-6">
            <Card className={isConnected ? 'border-success/20' : 'border-warning/20'}>
              <CardHeader>
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <Globe className="h-6 w-6" />
                    <div>
                      <CardTitle>WordPress Connection</CardTitle>
                      <CardDescription>Connect to your WordPress site</CardDescription>
                    </div>
                  </div>
                  <Badge variant={isConnected ? 'default' : 'secondary'} className={isConnected ? 'bg-success' : ''}>
                    {isConnected ? (
                      <><CheckCircle className="h-3 w-3 mr-1" /> Connected</>
                    ) : (
                      <><AlertCircle className="h-3 w-3 mr-1" /> Not Connected</>
                    )}
                  </Badge>
                </div>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-2">
                  <Label>WordPress REST API URL</Label>
                  <Input placeholder="https://your-site.com/wp-json" />
                  <p className="text-sm text-muted-foreground">
                    Enter your WordPress site URL with /wp-json endpoint
                  </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                  <div className="space-y-2">
                    <Label>WooCommerce Consumer Key</Label>
                    <Input placeholder="ck_..." type="password" />
                  </div>
                  <div className="space-y-2">
                    <Label>WooCommerce Consumer Secret</Label>
                    <Input placeholder="cs_..." type="password" />
                  </div>
                </div>

                <div className="space-y-2">
                  <Label>Authentication Key (JWT/App Password)</Label>
                  <Input placeholder="Your authentication key" type="password" />
                </div>

                <div className="flex gap-3">
                  <Button 
                    variant="outline" 
                    onClick={() => setIsConnected(!isConnected)}
                  >
                    Test Connection
                  </Button>
                  <Button className="gradient-primary text-primary-foreground">
                    Save Configuration
                  </Button>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>API Endpoints</CardTitle>
                <CardDescription>
                  Custom REST API endpoints required in WordPress
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div className="space-y-3 font-mono text-sm">
                  {[
                    '/affiliate/v1/affiliates',
                    '/affiliate/v1/commissions',
                    '/affiliate/v1/withdrawals',
                    '/affiliate/v1/tiers',
                    '/affiliate/v1/settings',
                    '/affiliate/v1/email-templates',
                  ].map(endpoint => (
                    <div key={endpoint} className="flex items-center gap-2 p-2 rounded bg-muted">
                      <Link2 className="h-4 w-4 text-muted-foreground" />
                      <code>{endpoint}</code>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        {/* Withdrawal Settings */}
        <TabsContent value="withdrawals">
          <Card>
            <CardHeader>
              <CardTitle>Withdrawal Configuration</CardTitle>
              <CardDescription>Set rules for affiliate payouts</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                  <Label>Minimum Withdrawal Amount</Label>
                  <div className="relative">
                    <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">$</span>
                    <Input type="number" defaultValue="50" className="pl-7" />
                  </div>
                </div>
                <div className="space-y-2">
                  <Label>Maximum Withdrawal Amount</Label>
                  <div className="relative">
                    <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">$</span>
                    <Input type="number" defaultValue="10000" className="pl-7" />
                  </div>
                </div>
              </div>

              <div className="space-y-2">
                <Label>Processing Time</Label>
                <Select defaultValue="3">
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="1">1 business day</SelectItem>
                    <SelectItem value="3">3 business days</SelectItem>
                    <SelectItem value="5">5 business days</SelectItem>
                    <SelectItem value="7">7 business days</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="flex items-center justify-between">
                <div>
                  <p className="font-medium">Require Bank Verification</p>
                  <p className="text-sm text-muted-foreground">
                    Affiliates must verify their bank details before withdrawing
                  </p>
                </div>
                <Switch defaultChecked />
              </div>

              <div className="flex items-center justify-between">
                <div>
                  <p className="font-medium">Auto-Approve Small Withdrawals</p>
                  <p className="text-sm text-muted-foreground">
                    Automatically approve withdrawals under $100
                  </p>
                </div>
                <Switch />
              </div>

              <Button className="gradient-primary text-primary-foreground">
                Save Settings
              </Button>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
