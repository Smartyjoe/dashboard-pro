import React from 'react';
import { useAuth } from '@/contexts/AuthContext';
import { StatCard } from '@/components/ui/stat-card';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { DataTable } from '@/components/ui/data-table';
import { StatusBadge } from '@/components/ui/status-badge';
import { 
  DollarSign, 
  Link2, 
  TrendingUp, 
  Users,
  Copy,
  ExternalLink,
  ArrowRight,
  Wallet,
} from 'lucide-react';
import { Link } from 'react-router-dom';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { toast } from 'sonner';

// Mock data
const earningsData = [
  { name: 'Week 1', earnings: 120 },
  { name: 'Week 2', earnings: 180 },
  { name: 'Week 3', earnings: 150 },
  { name: 'Week 4', earnings: 220 },
  { name: 'Week 5', earnings: 280 },
  { name: 'Week 6', earnings: 310 },
];

const recentCommissions = [
  { id: 1, product: 'Premium Theme', amount: 8.99, status: 'approved', date: '2024-03-15' },
  { id: 2, product: 'Plugin Bundle', amount: 14.90, status: 'pending', date: '2024-03-14' },
  { id: 3, product: 'Pro Membership', amount: 29.99, status: 'approved', date: '2024-03-12' },
  { id: 4, product: 'Starter Pack', amount: 5.99, status: 'paid', date: '2024-03-10' },
];

const tier2Referrals = [
  { id: 1, name: 'Emma Links', status: 'active', earnings: 45.50, joinedAt: '2024-02-20' },
  { id: 2, name: 'David Referral', status: 'active', earnings: 32.00, joinedAt: '2024-03-01' },
  { id: 3, name: 'Lisa Growth', status: 'pending', earnings: 0, joinedAt: '2024-03-10' },
];

export default function AffiliateDashboard() {
  const { user } = useAuth();
  
  const affiliateLink = `https://yourstore.com/?ref=MIKE2024`;
  
  const copyLink = () => {
    navigator.clipboard.writeText(affiliateLink);
    toast.success('Affiliate link copied to clipboard!');
  };

  return (
    <div className="space-y-6 animate-fade-in">
      {/* Header */}
      <div className="flex flex-col gap-1">
        <h1 className="text-2xl md:text-3xl font-bold">
          Welcome back, {user?.firstName}!
        </h1>
        <p className="text-muted-foreground">
          Here's your affiliate performance overview.
        </p>
      </div>

      {/* Referral Link Card */}
      <Card className="border-primary/20 bg-gradient-to-r from-primary/5 to-transparent">
        <CardContent className="p-6">
          <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div className="flex items-center gap-4">
              <div className="p-3 rounded-xl gradient-primary">
                <Link2 className="h-6 w-6 text-primary-foreground" />
              </div>
              <div>
                <h3 className="font-semibold">Your Referral Link</h3>
                <p className="text-sm text-muted-foreground">Share this link to earn commissions</p>
              </div>
            </div>
            <div className="flex flex-1 max-w-xl gap-2">
              <Input 
                value={affiliateLink} 
                readOnly 
                className="bg-background font-mono text-sm"
              />
              <Button variant="outline" size="icon" onClick={copyLink}>
                <Copy className="h-4 w-4" />
              </Button>
              <Button variant="outline" size="icon" asChild>
                <a href={affiliateLink} target="_blank" rel="noopener noreferrer">
                  <ExternalLink className="h-4 w-4" />
                </a>
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Stats Grid */}
      <div className="dashboard-grid">
        <StatCard
          title="Total Earnings"
          value="$2,450"
          change={18.5}
          changeLabel="this month"
          icon={<DollarSign className="h-6 w-6" />}
          variant="primary"
        />
        <StatCard
          title="Available Balance"
          value="$320"
          icon={<Wallet className="h-6 w-6" />}
          variant="accent"
        />
        <StatCard
          title="Total Clicks"
          value="1,284"
          change={12.3}
          changeLabel="this month"
          icon={<TrendingUp className="h-6 w-6" />}
          variant="warning"
        />
        <StatCard
          title="Tier 2 Referrals"
          value="3"
          icon={<Users className="h-6 w-6" />}
        />
      </div>

      {/* Charts & Tables Row */}
      <div className="grid gap-6 lg:grid-cols-2">
        {/* Earnings Chart */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <div>
              <CardTitle className="text-lg font-semibold">Earnings Overview</CardTitle>
              <CardDescription>Your commission earnings over time</CardDescription>
            </div>
          </CardHeader>
          <CardContent>
            <div className="h-[280px]">
              <ResponsiveContainer width="100%" height="100%">
                <AreaChart data={earningsData}>
                  <defs>
                    <linearGradient id="colorEarnings" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor="hsl(var(--accent))" stopOpacity={0.3}/>
                      <stop offset="95%" stopColor="hsl(var(--accent))" stopOpacity={0}/>
                    </linearGradient>
                  </defs>
                  <CartesianGrid strokeDasharray="3 3" className="stroke-border" />
                  <XAxis dataKey="name" className="text-xs fill-muted-foreground" />
                  <YAxis className="text-xs fill-muted-foreground" />
                  <Tooltip 
                    contentStyle={{ 
                      backgroundColor: 'hsl(var(--card))', 
                      border: '1px solid hsl(var(--border))',
                      borderRadius: '8px',
                    }}
                    formatter={(value) => [`$${value}`, 'Earnings']}
                  />
                  <Area 
                    type="monotone" 
                    dataKey="earnings" 
                    stroke="hsl(var(--accent))" 
                    fillOpacity={1} 
                    fill="url(#colorEarnings)" 
                    strokeWidth={2}
                  />
                </AreaChart>
              </ResponsiveContainer>
            </div>
          </CardContent>
        </Card>

        {/* Recent Commissions */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-4">
            <div>
              <CardTitle className="text-lg font-semibold">Recent Commissions</CardTitle>
              <CardDescription>Your latest earnings</CardDescription>
            </div>
            <Button variant="ghost" size="sm" asChild>
              <Link to="/affiliate/commissions" className="flex items-center gap-1">
                View All <ArrowRight className="h-4 w-4" />
              </Link>
            </Button>
          </CardHeader>
          <CardContent className="p-0">
            <DataTable
              columns={[
                { key: 'product', header: 'Product' },
                { 
                  key: 'amount', 
                  header: 'Amount',
                  render: (item) => (
                    <span className="font-semibold text-accent">${item.amount.toFixed(2)}</span>
                  )
                },
                { key: 'status', header: 'Status', render: (item) => <StatusBadge status={item.status} /> },
                { key: 'date', header: 'Date' },
              ]}
              data={recentCommissions}
              className="border-0 rounded-none"
            />
          </CardContent>
        </Card>
      </div>

      {/* Tier 2 Referrals */}
      <Card>
        <CardHeader>
          <CardTitle className="text-lg font-semibold">Your Tier 2 Network</CardTitle>
          <CardDescription>
            Affiliates who joined through your referral link. You earn 5% on their sales.
          </CardDescription>
        </CardHeader>
        <CardContent>
          {tier2Referrals.length > 0 ? (
            <div className="space-y-4">
              {tier2Referrals.map((referral) => (
                <div 
                  key={referral.id}
                  className="flex items-center justify-between p-4 rounded-lg bg-muted/30"
                >
                  <div className="flex items-center gap-4">
                    <div className="w-10 h-10 rounded-full bg-secondary flex items-center justify-center">
                      <span className="text-sm font-medium">
                        {referral.name.split(' ').map(n => n[0]).join('')}
                      </span>
                    </div>
                    <div>
                      <p className="font-medium">{referral.name}</p>
                      <p className="text-sm text-muted-foreground">Joined {referral.joinedAt}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-4">
                    <StatusBadge status={referral.status} />
                    <div className="text-right">
                      <p className="font-semibold text-accent">${referral.earnings.toFixed(2)}</p>
                      <p className="text-xs text-muted-foreground">Your T2 earnings</p>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="text-center py-8">
              <Users className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
              <h3 className="font-medium mb-2">No Tier 2 Referrals Yet</h3>
              <p className="text-sm text-muted-foreground max-w-md mx-auto">
                When someone signs up as an affiliate through your link, they'll appear here and you'll earn commission on their sales!
              </p>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Quick Actions */}
      <Card>
        <CardHeader>
          <CardTitle className="text-lg font-semibold">Quick Actions</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <Button variant="outline" className="h-auto py-4 flex-col gap-2" asChild>
              <Link to="/affiliate/links">
                <Link2 className="h-5 w-5" />
                <span>Generate Links</span>
              </Link>
            </Button>
            <Button variant="outline" className="h-auto py-4 flex-col gap-2" asChild>
              <Link to="/affiliate/withdrawals">
                <Wallet className="h-5 w-5" />
                <span>Request Payout</span>
              </Link>
            </Button>
            <Button variant="outline" className="h-auto py-4 flex-col gap-2" asChild>
              <Link to="/affiliate/analytics">
                <TrendingUp className="h-5 w-5" />
                <span>View Analytics</span>
              </Link>
            </Button>
            <Button variant="outline" className="h-auto py-4 flex-col gap-2" asChild>
              <Link to="/affiliate/profile">
                <DollarSign className="h-5 w-5" />
                <span>Bank Details</span>
              </Link>
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
