import React from 'react';
import { useAuth } from '@/contexts/AuthContext';
import { StatCard } from '@/components/ui/stat-card';
import { DataTable } from '@/components/ui/data-table';
import { StatusBadge } from '@/components/ui/status-badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { 
  DollarSign, 
  Users, 
  TrendingUp, 
  Wallet,
  ArrowRight,
  ShoppingBag,
  Link2,
  Eye,
} from 'lucide-react';
import { Link } from 'react-router-dom';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, BarChart, Bar } from 'recharts';

// Mock data
const revenueData = [
  { name: 'Jan', revenue: 4000, commissions: 400 },
  { name: 'Feb', revenue: 3000, commissions: 300 },
  { name: 'Mar', revenue: 5000, commissions: 500 },
  { name: 'Apr', revenue: 4500, commissions: 450 },
  { name: 'May', revenue: 6000, commissions: 600 },
  { name: 'Jun', revenue: 5500, commissions: 550 },
  { name: 'Jul', revenue: 7000, commissions: 700 },
];

const topAffiliates = [
  { id: 1, name: 'Mike Partner', earnings: '$2,450', sales: 45, conversion: '8.2%' },
  { id: 2, name: 'Sarah Seller', earnings: '$1,890', sales: 38, conversion: '7.5%' },
  { id: 3, name: 'John Promo', earnings: '$1,650', sales: 32, conversion: '6.8%' },
  { id: 4, name: 'Emma Links', earnings: '$1,420', sales: 28, conversion: '6.2%' },
  { id: 5, name: 'David Referral', earnings: '$1,180', sales: 24, conversion: '5.9%' },
];

const recentOrders = [
  { id: '1001', customer: 'Alice Johnson', total: '$89.99', affiliate: 'Mike Partner', status: 'completed', date: '2 min ago' },
  { id: '1002', customer: 'Bob Smith', total: '$149.00', affiliate: 'Sarah Seller', status: 'processing', date: '15 min ago' },
  { id: '1003', customer: 'Carol Davis', total: '$59.99', affiliate: '-', status: 'completed', date: '1 hour ago' },
  { id: '1004', customer: 'Dan Wilson', total: '$199.99', affiliate: 'John Promo', status: 'on-hold', date: '2 hours ago' },
];

const pendingWithdrawals = [
  { id: 1, affiliate: 'Mike Partner', amount: '$450', requested: '2 days ago', status: 'pending' },
  { id: 2, affiliate: 'Sarah Seller', amount: '$280', requested: '3 days ago', status: 'pending' },
  { id: 3, affiliate: 'Emma Links', amount: '$150', requested: '5 days ago', status: 'pending' },
];

export default function AdminDashboard() {
  const { user } = useAuth();

  return (
    <div className="space-y-6 animate-fade-in">
      {/* Header */}
      <div className="flex flex-col gap-1">
        <h1 className="text-2xl md:text-3xl font-bold">
          Welcome back, {user?.firstName}!
        </h1>
        <p className="text-muted-foreground">
          Here's what's happening with your affiliate program today.
        </p>
      </div>

      {/* Stats Grid */}
      <div className="dashboard-grid">
        <StatCard
          title="Total Revenue"
          value="$45,231"
          change={12.5}
          changeLabel="from last month"
          icon={<DollarSign className="h-6 w-6" />}
          variant="primary"
        />
        <StatCard
          title="Active Affiliates"
          value="248"
          change={8.2}
          changeLabel="from last month"
          icon={<Users className="h-6 w-6" />}
          variant="accent"
        />
        <StatCard
          title="Total Commissions"
          value="$4,523"
          change={-2.4}
          changeLabel="from last month"
          icon={<TrendingUp className="h-6 w-6" />}
          variant="warning"
        />
        <StatCard
          title="Pending Withdrawals"
          value="$1,280"
          change={0}
          changeLabel="3 requests"
          icon={<Wallet className="h-6 w-6" />}
        />
      </div>

      {/* Charts Row */}
      <div className="grid gap-6 lg:grid-cols-2">
        {/* Revenue Chart */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <div>
              <CardTitle className="text-lg font-semibold">Revenue Overview</CardTitle>
              <CardDescription>Monthly revenue and commission trends</CardDescription>
            </div>
            <Button variant="ghost" size="sm" asChild>
              <Link to="/admin/analytics" className="flex items-center gap-1">
                View Details <ArrowRight className="h-4 w-4" />
              </Link>
            </Button>
          </CardHeader>
          <CardContent>
            <div className="h-[280px]">
              <ResponsiveContainer width="100%" height="100%">
                <AreaChart data={revenueData}>
                  <defs>
                    <linearGradient id="colorRevenue" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor="hsl(var(--primary))" stopOpacity={0.3}/>
                      <stop offset="95%" stopColor="hsl(var(--primary))" stopOpacity={0}/>
                    </linearGradient>
                    <linearGradient id="colorCommissions" x1="0" y1="0" x2="0" y2="1">
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
                  />
                  <Area 
                    type="monotone" 
                    dataKey="revenue" 
                    stroke="hsl(var(--primary))" 
                    fillOpacity={1} 
                    fill="url(#colorRevenue)" 
                    strokeWidth={2}
                  />
                  <Area 
                    type="monotone" 
                    dataKey="commissions" 
                    stroke="hsl(var(--accent))" 
                    fillOpacity={1} 
                    fill="url(#colorCommissions)" 
                    strokeWidth={2}
                  />
                </AreaChart>
              </ResponsiveContainer>
            </div>
          </CardContent>
        </Card>

        {/* Top Affiliates */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <div>
              <CardTitle className="text-lg font-semibold">Top Affiliates</CardTitle>
              <CardDescription>Best performing partners this month</CardDescription>
            </div>
            <Button variant="ghost" size="sm" asChild>
              <Link to="/admin/affiliates" className="flex items-center gap-1">
                View All <ArrowRight className="h-4 w-4" />
              </Link>
            </Button>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {topAffiliates.map((affiliate, index) => (
                <div 
                  key={affiliate.id}
                  className="flex items-center gap-4 p-3 rounded-lg bg-muted/30 hover:bg-muted/50 transition-colors"
                >
                  <div className="w-8 h-8 rounded-full gradient-primary flex items-center justify-center text-sm font-bold text-primary-foreground">
                    {index + 1}
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="font-medium truncate">{affiliate.name}</p>
                    <p className="text-sm text-muted-foreground">{affiliate.sales} sales</p>
                  </div>
                  <div className="text-right">
                    <p className="font-semibold text-accent">{affiliate.earnings}</p>
                    <p className="text-sm text-muted-foreground">{affiliate.conversion}</p>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Tables Row */}
      <div className="grid gap-6 lg:grid-cols-2">
        {/* Recent Orders */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-4">
            <div>
              <CardTitle className="text-lg font-semibold">Recent Orders</CardTitle>
              <CardDescription>Latest purchases from your store</CardDescription>
            </div>
            <Button variant="ghost" size="sm" asChild>
              <Link to="/admin/orders" className="flex items-center gap-1">
                View All <ArrowRight className="h-4 w-4" />
              </Link>
            </Button>
          </CardHeader>
          <CardContent className="p-0">
            <DataTable
              columns={[
                { key: 'id', header: 'Order', render: (item) => <span className="font-medium">#{item.id}</span> },
                { key: 'customer', header: 'Customer' },
                { key: 'total', header: 'Total' },
                { key: 'status', header: 'Status', render: (item) => <StatusBadge status={item.status} /> },
              ]}
              data={recentOrders}
              className="border-0 rounded-none"
            />
          </CardContent>
        </Card>

        {/* Pending Withdrawals */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-4">
            <div>
              <CardTitle className="text-lg font-semibold">Pending Withdrawals</CardTitle>
              <CardDescription>Requests awaiting approval</CardDescription>
            </div>
            <Button variant="ghost" size="sm" asChild>
              <Link to="/admin/withdrawals" className="flex items-center gap-1">
                View All <ArrowRight className="h-4 w-4" />
              </Link>
            </Button>
          </CardHeader>
          <CardContent className="p-0">
            <DataTable
              columns={[
                { key: 'affiliate', header: 'Affiliate' },
                { key: 'amount', header: 'Amount', render: (item) => <span className="font-semibold text-warning">{item.amount}</span> },
                { key: 'requested', header: 'Requested' },
                { key: 'actions', header: '', render: () => (
                  <div className="flex gap-2">
                    <Button size="sm" variant="outline" className="h-7 px-2">
                      Approve
                    </Button>
                  </div>
                )},
              ]}
              data={pendingWithdrawals}
              className="border-0 rounded-none"
            />
          </CardContent>
        </Card>
      </div>

      {/* Quick Actions */}
      <Card>
        <CardHeader>
          <CardTitle className="text-lg font-semibold">Quick Actions</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <Button variant="outline" className="h-auto py-4 flex-col gap-2" asChild>
              <Link to="/admin/affiliates">
                <Users className="h-5 w-5" />
                <span>Manage Affiliates</span>
              </Link>
            </Button>
            <Button variant="outline" className="h-auto py-4 flex-col gap-2" asChild>
              <Link to="/admin/products">
                <ShoppingBag className="h-5 w-5" />
                <span>View Products</span>
              </Link>
            </Button>
            <Button variant="outline" className="h-auto py-4 flex-col gap-2" asChild>
              <Link to="/admin/commissions">
                <DollarSign className="h-5 w-5" />
                <span>Commission Rules</span>
              </Link>
            </Button>
            <Button variant="outline" className="h-auto py-4 flex-col gap-2" asChild>
              <Link to="/admin/analytics">
                <TrendingUp className="h-5 w-5" />
                <span>View Analytics</span>
              </Link>
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
