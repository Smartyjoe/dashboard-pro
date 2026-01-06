import React, { useState } from 'react';
import { DataTable } from '@/components/ui/data-table';
import { StatusBadge } from '@/components/ui/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { StatCard } from '@/components/ui/stat-card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Search, Wallet, Clock, CheckCircle, XCircle, Eye, Check, X } from 'lucide-react';

// Mock withdrawal data
const mockWithdrawals = [
  { id: 1, affiliate: 'Mike Partner', email: 'mike@example.com', amount: 450, bank: 'Chase Bank ****1234', status: 'pending', requestedAt: '2024-03-13', processedAt: null },
  { id: 2, affiliate: 'Sarah Seller', email: 'sarah@example.com', amount: 280, bank: 'Bank of America ****5678', status: 'pending', requestedAt: '2024-03-12', processedAt: null },
  { id: 3, affiliate: 'Emma Links', email: 'emma@example.com', amount: 150, bank: 'Wells Fargo ****9012', status: 'pending', requestedAt: '2024-03-10', processedAt: null },
  { id: 4, affiliate: 'John Promo', email: 'john@example.com', amount: 320, bank: 'Chase Bank ****3456', status: 'completed', requestedAt: '2024-03-05', processedAt: '2024-03-07' },
  { id: 5, affiliate: 'David Referral', email: 'david@example.com', amount: 180, bank: 'Citi Bank ****7890', status: 'completed', requestedAt: '2024-03-01', processedAt: '2024-03-03' },
  { id: 6, affiliate: 'Lisa Growth', email: 'lisa@example.com', amount: 95, bank: 'PNC Bank ****2345', status: 'rejected', requestedAt: '2024-02-28', processedAt: '2024-03-01' },
];

export default function WithdrawalsPage() {
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [selectedWithdrawal, setSelectedWithdrawal] = useState<typeof mockWithdrawals[0] | null>(null);
  const [dialogMode, setDialogMode] = useState<'view' | 'approve' | 'reject'>('view');

  const filteredWithdrawals = mockWithdrawals.filter(withdrawal => {
    const matchesSearch = withdrawal.affiliate.toLowerCase().includes(search.toLowerCase()) ||
                         withdrawal.email.toLowerCase().includes(search.toLowerCase());
    const matchesStatus = statusFilter === 'all' || withdrawal.status === statusFilter;
    return matchesSearch && matchesStatus;
  });

  const pendingCount = mockWithdrawals.filter(w => w.status === 'pending').length;
  const pendingAmount = mockWithdrawals.filter(w => w.status === 'pending').reduce((sum, w) => sum + w.amount, 0);
  const completedCount = mockWithdrawals.filter(w => w.status === 'completed').length;
  const completedAmount = mockWithdrawals.filter(w => w.status === 'completed').reduce((sum, w) => sum + w.amount, 0);

  const handleAction = (withdrawal: typeof mockWithdrawals[0], mode: 'view' | 'approve' | 'reject') => {
    setSelectedWithdrawal(withdrawal);
    setDialogMode(mode);
  };

  return (
    <div className="space-y-6 animate-fade-in">
      {/* Header */}
      <div>
        <h1 className="text-2xl md:text-3xl font-bold">Withdrawals</h1>
        <p className="text-muted-foreground">Process and manage affiliate payout requests</p>
      </div>

      {/* Stats */}
      <div className="dashboard-grid">
        <StatCard
          title="Pending Requests"
          value={pendingCount}
          icon={<Clock className="h-6 w-6" />}
          variant="warning"
        />
        <StatCard
          title="Pending Amount"
          value={`$${pendingAmount.toLocaleString()}`}
          icon={<Wallet className="h-6 w-6" />}
          variant="primary"
        />
        <StatCard
          title="Completed This Month"
          value={completedCount}
          icon={<CheckCircle className="h-6 w-6" />}
          variant="accent"
        />
        <StatCard
          title="Total Paid Out"
          value={`$${completedAmount.toLocaleString()}`}
          icon={<Wallet className="h-6 w-6" />}
        />
      </div>

      {/* Filters */}
      <Card>
        <CardContent className="p-4">
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Search by affiliate..."
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
                <SelectItem value="processing">Processing</SelectItem>
                <SelectItem value="completed">Completed</SelectItem>
                <SelectItem value="rejected">Rejected</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      {/* Withdrawals Table */}
      <DataTable
        columns={[
          { 
            key: 'affiliate', 
            header: 'Affiliate',
            render: (item) => (
              <div>
                <p className="font-medium">{item.affiliate}</p>
                <p className="text-sm text-muted-foreground">{item.email}</p>
              </div>
            )
          },
          { 
            key: 'amount', 
            header: 'Amount',
            render: (item) => (
              <span className="font-semibold text-lg">${item.amount.toLocaleString()}</span>
            )
          },
          { key: 'bank', header: 'Bank Account' },
          { key: 'status', header: 'Status', render: (item) => <StatusBadge status={item.status} /> },
          { key: 'requestedAt', header: 'Requested' },
          { key: 'processedAt', header: 'Processed', render: (item) => item.processedAt || '-' },
          {
            key: 'actions',
            header: '',
            render: (item) => (
              <div className="flex items-center gap-2">
                <Button 
                  variant="ghost" 
                  size="sm"
                  onClick={() => handleAction(item, 'view')}
                >
                  <Eye className="h-4 w-4" />
                </Button>
                {item.status === 'pending' && (
                  <>
                    <Button 
                      variant="ghost" 
                      size="sm" 
                      className="text-success hover:text-success"
                      onClick={() => handleAction(item, 'approve')}
                    >
                      <Check className="h-4 w-4" />
                    </Button>
                    <Button 
                      variant="ghost" 
                      size="sm" 
                      className="text-destructive hover:text-destructive"
                      onClick={() => handleAction(item, 'reject')}
                    >
                      <X className="h-4 w-4" />
                    </Button>
                  </>
                )}
              </div>
            )
          },
        ]}
        data={filteredWithdrawals}
        emptyMessage="No withdrawals found"
      />

      {/* Action Dialog */}
      <Dialog open={!!selectedWithdrawal} onOpenChange={() => setSelectedWithdrawal(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>
              {dialogMode === 'view' && 'Withdrawal Details'}
              {dialogMode === 'approve' && 'Approve Withdrawal'}
              {dialogMode === 'reject' && 'Reject Withdrawal'}
            </DialogTitle>
            <DialogDescription>
              {dialogMode === 'view' && 'View the details of this withdrawal request'}
              {dialogMode === 'approve' && 'Confirm that you want to approve this payout'}
              {dialogMode === 'reject' && 'Provide a reason for rejecting this request'}
            </DialogDescription>
          </DialogHeader>

          {selectedWithdrawal && (
            <div className="space-y-4 py-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <Label className="text-muted-foreground">Affiliate</Label>
                  <p className="font-medium">{selectedWithdrawal.affiliate}</p>
                </div>
                <div>
                  <Label className="text-muted-foreground">Amount</Label>
                  <p className="font-semibold text-xl">${selectedWithdrawal.amount}</p>
                </div>
                <div>
                  <Label className="text-muted-foreground">Bank Account</Label>
                  <p className="font-medium">{selectedWithdrawal.bank}</p>
                </div>
                <div>
                  <Label className="text-muted-foreground">Requested</Label>
                  <p className="font-medium">{selectedWithdrawal.requestedAt}</p>
                </div>
              </div>

              {dialogMode === 'reject' && (
                <div className="space-y-2">
                  <Label>Rejection Reason</Label>
                  <Textarea placeholder="Please provide a reason for rejecting this withdrawal..." />
                </div>
              )}

              {dialogMode === 'approve' && (
                <div className="space-y-2">
                  <Label>Transaction ID (optional)</Label>
                  <Input placeholder="Enter payment reference number..." />
                </div>
              )}

              <div className="flex gap-2 justify-end pt-4">
                <Button variant="outline" onClick={() => setSelectedWithdrawal(null)}>
                  Cancel
                </Button>
                {dialogMode === 'approve' && (
                  <Button className="bg-success hover:bg-success/90 text-success-foreground">
                    <CheckCircle className="h-4 w-4 mr-2" />
                    Approve Payout
                  </Button>
                )}
                {dialogMode === 'reject' && (
                  <Button variant="destructive">
                    <XCircle className="h-4 w-4 mr-2" />
                    Reject Request
                  </Button>
                )}
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}
