import React, { useState } from 'react';
import { DataTable } from '@/components/ui/data-table';
import { StatusBadge } from '@/components/ui/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { StatCard } from '@/components/ui/stat-card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Search, Settings, DollarSign, Percent, TrendingUp, Package } from 'lucide-react';

// Mock commission data
const mockCommissions = [
  { id: 1, affiliate: 'Mike Partner', orderId: 1001, product: 'Premium Theme', orderTotal: 89.99, commission: 8.99, rate: 10, tier: 1, status: 'approved', date: '2024-03-15' },
  { id: 2, affiliate: 'Sarah Seller', orderId: 1002, product: 'Plugin Bundle', orderTotal: 149.00, commission: 14.90, rate: 10, tier: 1, status: 'pending', date: '2024-03-14' },
  { id: 3, affiliate: 'John Promo', orderId: 1003, product: 'Starter Pack', orderTotal: 59.99, commission: 5.99, rate: 10, tier: 1, status: 'paid', date: '2024-03-13' },
  { id: 4, affiliate: 'Emma Links', orderId: 1004, product: 'Premium Theme', orderTotal: 89.99, commission: 4.49, rate: 5, tier: 2, status: 'approved', date: '2024-03-12' },
  { id: 5, affiliate: 'Mike Partner', orderId: 1005, product: 'Pro Membership', orderTotal: 199.99, commission: 29.99, rate: 15, tier: 1, status: 'pending', date: '2024-03-11' },
];

// Mock commission rules
const mockRules = [
  { id: 1, type: 'global', name: 'Default Commission', tier1Rate: 10, tier2Rate: 5, isActive: true },
  { id: 2, type: 'product', name: 'Premium Theme', tier1Rate: 15, tier2Rate: 7, isActive: true },
  { id: 3, type: 'product', name: 'Pro Membership', tier1Rate: 20, tier2Rate: 10, isActive: true },
  { id: 4, type: 'category', name: 'Plugins', tier1Rate: 12, tier2Rate: 6, isActive: false },
];

export default function CommissionsPage() {
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [tier2Enabled, setTier2Enabled] = useState(true);

  const filteredCommissions = mockCommissions.filter(commission => {
    const matchesSearch = commission.affiliate.toLowerCase().includes(search.toLowerCase()) ||
                         commission.product.toLowerCase().includes(search.toLowerCase());
    const matchesStatus = statusFilter === 'all' || commission.status === statusFilter;
    return matchesSearch && matchesStatus;
  });

  const totalCommissions = mockCommissions.reduce((sum, c) => sum + c.commission, 0);
  const pendingCommissions = mockCommissions.filter(c => c.status === 'pending').reduce((sum, c) => sum + c.commission, 0);
  const paidCommissions = mockCommissions.filter(c => c.status === 'paid').reduce((sum, c) => sum + c.commission, 0);

  return (
    <div className="space-y-6 animate-fade-in">
      {/* Header */}
      <div>
        <h1 className="text-2xl md:text-3xl font-bold">Commissions</h1>
        <p className="text-muted-foreground">Manage commission rules and view transaction history</p>
      </div>

      {/* Stats */}
      <div className="dashboard-grid">
        <StatCard
          title="Total Commissions"
          value={`$${totalCommissions.toFixed(2)}`}
          icon={<DollarSign className="h-6 w-6" />}
          variant="primary"
        />
        <StatCard
          title="Pending"
          value={`$${pendingCommissions.toFixed(2)}`}
          icon={<TrendingUp className="h-6 w-6" />}
          variant="warning"
        />
        <StatCard
          title="Paid Out"
          value={`$${paidCommissions.toFixed(2)}`}
          icon={<DollarSign className="h-6 w-6" />}
          variant="accent"
        />
        <StatCard
          title="Avg. Rate"
          value="10%"
          icon={<Percent className="h-6 w-6" />}
        />
      </div>

      <Tabs defaultValue="transactions" className="space-y-6">
        <TabsList>
          <TabsTrigger value="transactions">Transactions</TabsTrigger>
          <TabsTrigger value="rules">Commission Rules</TabsTrigger>
          <TabsTrigger value="settings">Settings</TabsTrigger>
        </TabsList>

        <TabsContent value="transactions" className="space-y-4">
          {/* Filters */}
          <Card>
            <CardContent className="p-4">
              <div className="flex flex-col sm:flex-row gap-4">
                <div className="relative flex-1">
                  <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                  <Input
                    placeholder="Search by affiliate or product..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="pl-9"
                  />
                </div>
                <Select value={statusFilter} onValueChange={setStatusFilter}>
                  <SelectTrigger className="w-full sm:w-40">
                    <SelectValue placeholder="Status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All Status</SelectItem>
                    <SelectItem value="pending">Pending</SelectItem>
                    <SelectItem value="approved">Approved</SelectItem>
                    <SelectItem value="paid">Paid</SelectItem>
                    <SelectItem value="cancelled">Cancelled</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </CardContent>
          </Card>

          {/* Commissions Table */}
          <DataTable
            columns={[
              { key: 'orderId', header: 'Order', render: (item) => <span className="font-medium">#{item.orderId}</span> },
              { key: 'affiliate', header: 'Affiliate' },
              { key: 'product', header: 'Product' },
              { key: 'orderTotal', header: 'Order Total', render: (item) => `$${item.orderTotal.toFixed(2)}` },
              { 
                key: 'commission', 
                header: 'Commission',
                render: (item) => (
                  <span className="font-semibold text-accent">${item.commission.toFixed(2)}</span>
                )
              },
              { key: 'rate', header: 'Rate', render: (item) => `${item.rate}%` },
              { 
                key: 'tier', 
                header: 'Tier',
                render: (item) => (
                  <span className={`px-2 py-0.5 rounded text-xs font-medium ${
                    item.tier === 1 ? 'bg-primary/10 text-primary' : 'bg-secondary text-secondary-foreground'
                  }`}>
                    T{item.tier}
                  </span>
                )
              },
              { key: 'status', header: 'Status', render: (item) => <StatusBadge status={item.status} /> },
              { key: 'date', header: 'Date' },
            ]}
            data={filteredCommissions}
            emptyMessage="No commissions found"
          />
        </TabsContent>

        <TabsContent value="rules" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Commission Rules</CardTitle>
              <CardDescription>
                Configure default and product-specific commission rates
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {mockRules.map((rule) => (
                  <div 
                    key={rule.id}
                    className="flex items-center justify-between p-4 rounded-lg border border-border"
                  >
                    <div className="flex items-center gap-4">
                      <div className={`w-10 h-10 rounded-lg flex items-center justify-center ${
                        rule.type === 'global' ? 'bg-primary/10 text-primary' :
                        rule.type === 'product' ? 'bg-accent/10 text-accent' :
                        'bg-warning/10 text-warning'
                      }`}>
                        {rule.type === 'global' ? <Settings className="h-5 w-5" /> :
                         rule.type === 'product' ? <Package className="h-5 w-5" /> :
                         <Package className="h-5 w-5" />}
                      </div>
                      <div>
                        <p className="font-medium">{rule.name}</p>
                        <p className="text-sm text-muted-foreground capitalize">{rule.type} rule</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-6">
                      <div className="text-right">
                        <p className="text-sm text-muted-foreground">Tier 1</p>
                        <p className="font-semibold">{rule.tier1Rate}%</p>
                      </div>
                      <div className="text-right">
                        <p className="text-sm text-muted-foreground">Tier 2</p>
                        <p className="font-semibold">{rule.tier2Rate}%</p>
                      </div>
                      <Switch checked={rule.isActive} />
                    </div>
                  </div>
                ))}
              </div>
              <Button className="mt-4 gradient-primary text-primary-foreground">
                Add Commission Rule
              </Button>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="settings" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Commission Settings</CardTitle>
              <CardDescription>
                Configure global commission behavior
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="flex items-center justify-between">
                <div className="space-y-1">
                  <Label className="text-base">Two-Tier Commission</Label>
                  <p className="text-sm text-muted-foreground">
                    Enable tier-2 commissions for affiliate referrals
                  </p>
                </div>
                <Switch 
                  checked={tier2Enabled} 
                  onCheckedChange={setTier2Enabled}
                />
              </div>

              <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                  <Label>Default Tier 1 Rate</Label>
                  <div className="relative">
                    <Input type="number" defaultValue="10" className="pr-8" />
                    <span className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground">%</span>
                  </div>
                </div>
                <div className="space-y-2">
                  <Label>Default Tier 2 Rate</Label>
                  <div className="relative">
                    <Input type="number" defaultValue="5" className="pr-8" disabled={!tier2Enabled} />
                    <span className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground">%</span>
                  </div>
                </div>
              </div>

              <div className="space-y-2">
                <Label>Cookie Duration (days)</Label>
                <Input type="number" defaultValue="30" className="max-w-xs" />
                <p className="text-sm text-muted-foreground">
                  How long affiliate referral tracking remains active
                </p>
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
