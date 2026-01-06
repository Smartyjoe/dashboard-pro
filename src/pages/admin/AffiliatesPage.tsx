import React, { useState } from 'react';
import { DataTable } from '@/components/ui/data-table';
import { StatusBadge } from '@/components/ui/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { StatCard } from '@/components/ui/stat-card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Search, MoreHorizontal, Users, DollarSign, TrendingUp, Link2, Eye, Ban, CheckCircle } from 'lucide-react';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
  DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';

// Mock data
const mockAffiliates = [
  { id: 1, name: 'Mike Partner', email: 'mike@example.com', referralCode: 'MIKE2024', tier: 1, status: 'active', earnings: 2450, pending: 320, sales: 45, tier2Referrals: 3, createdAt: '2024-02-01' },
  { id: 2, name: 'Sarah Seller', email: 'sarah@example.com', referralCode: 'SARAH24', tier: 1, status: 'active', earnings: 1890, pending: 180, sales: 38, tier2Referrals: 2, createdAt: '2024-02-10' },
  { id: 3, name: 'John Promo', email: 'john@example.com', referralCode: 'JOHN123', tier: 1, status: 'active', earnings: 1650, pending: 0, sales: 32, tier2Referrals: 5, createdAt: '2024-02-15' },
  { id: 4, name: 'Emma Links', email: 'emma@example.com', referralCode: 'EMMA22', tier: 2, status: 'active', earnings: 1420, pending: 150, sales: 28, tier2Referrals: 0, createdAt: '2024-03-01' },
  { id: 5, name: 'David Referral', email: 'david@example.com', referralCode: 'DAVID99', tier: 1, status: 'pending', earnings: 0, pending: 0, sales: 0, tier2Referrals: 0, createdAt: '2024-03-10' },
  { id: 6, name: 'Lisa Growth', email: 'lisa@example.com', referralCode: 'LISA888', tier: 2, status: 'suspended', earnings: 890, pending: 0, sales: 15, tier2Referrals: 0, createdAt: '2024-02-20' },
];

export default function AffiliatesPage() {
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [tierFilter, setTierFilter] = useState('all');

  const filteredAffiliates = mockAffiliates.filter(affiliate => {
    const matchesSearch = affiliate.name.toLowerCase().includes(search.toLowerCase()) ||
                         affiliate.email.toLowerCase().includes(search.toLowerCase()) ||
                         affiliate.referralCode.toLowerCase().includes(search.toLowerCase());
    const matchesStatus = statusFilter === 'all' || affiliate.status === statusFilter;
    const matchesTier = tierFilter === 'all' || affiliate.tier.toString() === tierFilter;
    return matchesSearch && matchesStatus && matchesTier;
  });

  const totalAffiliates = mockAffiliates.length;
  const activeAffiliates = mockAffiliates.filter(a => a.status === 'active').length;
  const totalEarnings = mockAffiliates.reduce((sum, a) => sum + a.earnings, 0);
  const totalPending = mockAffiliates.reduce((sum, a) => sum + a.pending, 0);

  return (
    <div className="space-y-6 animate-fade-in">
      {/* Header */}
      <div>
        <h1 className="text-2xl md:text-3xl font-bold">Affiliates</h1>
        <p className="text-muted-foreground">Manage your affiliate partners and their performance</p>
      </div>

      {/* Stats */}
      <div className="dashboard-grid">
        <StatCard
          title="Total Affiliates"
          value={totalAffiliates}
          icon={<Users className="h-6 w-6" />}
          variant="primary"
        />
        <StatCard
          title="Active Affiliates"
          value={activeAffiliates}
          icon={<CheckCircle className="h-6 w-6" />}
          variant="accent"
        />
        <StatCard
          title="Total Paid Out"
          value={`$${totalEarnings.toLocaleString()}`}
          icon={<DollarSign className="h-6 w-6" />}
          variant="warning"
        />
        <StatCard
          title="Pending Payouts"
          value={`$${totalPending.toLocaleString()}`}
          icon={<TrendingUp className="h-6 w-6" />}
        />
      </div>

      {/* Filters */}
      <Card>
        <CardContent className="p-4">
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Search affiliates..."
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
                <SelectItem value="active">Active</SelectItem>
                <SelectItem value="pending">Pending</SelectItem>
                <SelectItem value="suspended">Suspended</SelectItem>
              </SelectContent>
            </Select>
            <Select value={tierFilter} onValueChange={setTierFilter}>
              <SelectTrigger className="w-full sm:w-40">
                <SelectValue placeholder="Tier" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Tiers</SelectItem>
                <SelectItem value="1">Tier 1</SelectItem>
                <SelectItem value="2">Tier 2</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      {/* Affiliates Table */}
      <DataTable
        columns={[
          { 
            key: 'name', 
            header: 'Affiliate',
            render: (item) => (
              <div className="flex items-center gap-3">
                <div className="w-9 h-9 rounded-full gradient-accent flex items-center justify-center">
                  <span className="text-sm font-medium text-accent-foreground">
                    {item.name.split(' ').map((n: string) => n[0]).join('')}
                  </span>
                </div>
                <div>
                  <p className="font-medium">{item.name}</p>
                  <p className="text-sm text-muted-foreground">{item.email}</p>
                </div>
              </div>
            )
          },
          { 
            key: 'referralCode', 
            header: 'Referral Code',
            render: (item) => (
              <code className="px-2 py-1 bg-muted rounded text-sm font-mono">
                {item.referralCode}
              </code>
            )
          },
          { 
            key: 'tier', 
            header: 'Tier',
            render: (item) => (
              <span className={`px-2.5 py-1 rounded-md text-sm font-medium ${
                item.tier === 1 
                  ? 'bg-primary/10 text-primary' 
                  : 'bg-secondary text-secondary-foreground'
              }`}>
                Tier {item.tier}
              </span>
            )
          },
          { 
            key: 'status', 
            header: 'Status',
            render: (item) => <StatusBadge status={item.status} />
          },
          { 
            key: 'earnings', 
            header: 'Earnings',
            render: (item) => (
              <span className="font-semibold text-accent">${item.earnings.toLocaleString()}</span>
            )
          },
          { 
            key: 'pending', 
            header: 'Pending',
            render: (item) => (
              <span className="text-warning">${item.pending.toLocaleString()}</span>
            )
          },
          { key: 'sales', header: 'Sales' },
          { key: 'tier2Referrals', header: 'T2 Refs' },
          {
            key: 'actions',
            header: '',
            render: (item) => (
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" size="icon" className="h-8 w-8">
                    <MoreHorizontal className="h-4 w-4" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <DropdownMenuItem>
                    <Eye className="h-4 w-4 mr-2" />
                    View Details
                  </DropdownMenuItem>
                  <DropdownMenuItem>
                    <Link2 className="h-4 w-4 mr-2" />
                    View Links
                  </DropdownMenuItem>
                  <DropdownMenuSeparator />
                  {item.status === 'active' ? (
                    <DropdownMenuItem className="text-destructive">
                      <Ban className="h-4 w-4 mr-2" />
                      Suspend
                    </DropdownMenuItem>
                  ) : item.status === 'pending' ? (
                    <DropdownMenuItem className="text-success">
                      <CheckCircle className="h-4 w-4 mr-2" />
                      Approve
                    </DropdownMenuItem>
                  ) : (
                    <DropdownMenuItem className="text-success">
                      <CheckCircle className="h-4 w-4 mr-2" />
                      Reactivate
                    </DropdownMenuItem>
                  )}
                </DropdownMenuContent>
              </DropdownMenu>
            )
          },
        ]}
        data={filteredAffiliates}
        emptyMessage="No affiliates found"
      />
    </div>
  );
}
