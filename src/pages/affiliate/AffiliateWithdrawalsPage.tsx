import React, { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { DataTable } from '@/components/ui/data-table';
import { StatusBadge } from '@/components/ui/status-badge';
import { StatCard } from '@/components/ui/stat-card';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { 
  Wallet, 
  DollarSign, 
  Clock, 
  CheckCircle,
  AlertCircle,
  ArrowUpRight,
} from 'lucide-react';
import { toast } from 'sonner';

// Mock withdrawal history
const mockWithdrawals = [
  { id: 1, amount: 320, status: 'completed', requestedAt: '2024-03-05', processedAt: '2024-03-07', transactionId: 'TXN-001234' },
  { id: 2, amount: 180, status: 'completed', requestedAt: '2024-02-20', processedAt: '2024-02-22', transactionId: 'TXN-001189' },
  { id: 3, amount: 250, status: 'completed', requestedAt: '2024-02-05', processedAt: '2024-02-08', transactionId: 'TXN-001145' },
  { id: 4, amount: 150, status: 'pending', requestedAt: '2024-03-12', processedAt: null, transactionId: null },
];

export default function AffiliateWithdrawalsPage() {
  const [withdrawAmount, setWithdrawAmount] = useState('');
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  
  const availableBalance = 320;
  const pendingBalance = 150;
  const minimumWithdrawal = 50;

  const handleWithdraw = () => {
    const amount = parseFloat(withdrawAmount);
    
    if (isNaN(amount) || amount < minimumWithdrawal) {
      toast.error(`Minimum withdrawal amount is $${minimumWithdrawal}`);
      return;
    }
    
    if (amount > availableBalance) {
      toast.error('Insufficient balance');
      return;
    }
    
    toast.success(`Withdrawal request for $${amount.toFixed(2)} submitted!`);
    setIsDialogOpen(false);
    setWithdrawAmount('');
  };

  const totalWithdrawn = mockWithdrawals
    .filter(w => w.status === 'completed')
    .reduce((sum, w) => sum + w.amount, 0);

  return (
    <div className="space-y-6 animate-fade-in">
      {/* Header */}
      <div>
        <h1 className="text-2xl md:text-3xl font-bold">Withdrawals</h1>
        <p className="text-muted-foreground">Request payouts and view your withdrawal history</p>
      </div>

      {/* Stats */}
      <div className="dashboard-grid">
        <StatCard
          title="Available Balance"
          value={`$${availableBalance.toFixed(2)}`}
          icon={<Wallet className="h-6 w-6" />}
          variant="accent"
        />
        <StatCard
          title="Pending Balance"
          value={`$${pendingBalance.toFixed(2)}`}
          icon={<Clock className="h-6 w-6" />}
          variant="warning"
        />
        <StatCard
          title="Total Withdrawn"
          value={`$${totalWithdrawn.toFixed(2)}`}
          icon={<DollarSign className="h-6 w-6" />}
          variant="primary"
        />
        <StatCard
          title="Min. Withdrawal"
          value={`$${minimumWithdrawal}`}
          icon={<AlertCircle className="h-6 w-6" />}
        />
      </div>

      {/* Request Withdrawal */}
      <Card className="border-accent/20">
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <ArrowUpRight className="h-5 w-5" />
            Request Withdrawal
          </CardTitle>
          <CardDescription>
            Withdraw your available earnings to your bank account
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="flex flex-col sm:flex-row gap-4 items-start sm:items-end">
            <div className="flex-1 space-y-2 w-full">
              <Label>Amount to Withdraw</Label>
              <div className="relative">
                <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">$</span>
                <Input
                  type="number"
                  placeholder="0.00"
                  value={withdrawAmount}
                  onChange={(e) => setWithdrawAmount(e.target.value)}
                  className="pl-7"
                  min={minimumWithdrawal}
                  max={availableBalance}
                />
              </div>
              <p className="text-sm text-muted-foreground">
                Available: <span className="font-medium text-accent">${availableBalance.toFixed(2)}</span>
              </p>
            </div>
            
            <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
              <DialogTrigger asChild>
                <Button 
                  className="gradient-accent text-accent-foreground w-full sm:w-auto"
                  disabled={!withdrawAmount || parseFloat(withdrawAmount) < minimumWithdrawal}
                >
                  Request Withdrawal
                </Button>
              </DialogTrigger>
              <DialogContent>
                <DialogHeader>
                  <DialogTitle>Confirm Withdrawal</DialogTitle>
                  <DialogDescription>
                    Please review and confirm your withdrawal request
                  </DialogDescription>
                </DialogHeader>
                <div className="space-y-4 py-4">
                  <div className="p-4 rounded-lg bg-muted/50 space-y-3">
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Amount</span>
                      <span className="font-semibold text-lg">${parseFloat(withdrawAmount || '0').toFixed(2)}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Bank Account</span>
                      <span className="font-medium">Chase Bank ****1234</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Processing Time</span>
                      <span className="font-medium">3-5 business days</span>
                    </div>
                  </div>
                  
                  <div className="flex items-start gap-2 p-3 rounded-lg bg-warning/10 text-warning text-sm">
                    <AlertCircle className="h-4 w-4 mt-0.5 flex-shrink-0" />
                    <p>
                      Withdrawals are processed within 3-5 business days. Make sure your bank details are up to date.
                    </p>
                  </div>

                  <div className="flex gap-3">
                    <Button 
                      variant="outline" 
                      onClick={() => setIsDialogOpen(false)}
                      className="flex-1"
                    >
                      Cancel
                    </Button>
                    <Button 
                      onClick={handleWithdraw}
                      className="flex-1 gradient-accent text-accent-foreground"
                    >
                      Confirm Withdrawal
                    </Button>
                  </div>
                </div>
              </DialogContent>
            </Dialog>
          </div>
        </CardContent>
      </Card>

      {/* Withdrawal History */}
      <Card>
        <CardHeader>
          <CardTitle>Withdrawal History</CardTitle>
          <CardDescription>Your past payout requests and their status</CardDescription>
        </CardHeader>
        <CardContent className="p-0">
          <DataTable
            columns={[
              { 
                key: 'amount', 
                header: 'Amount',
                render: (item) => (
                  <span className="font-semibold text-lg">${item.amount.toFixed(2)}</span>
                )
              },
              { 
                key: 'status', 
                header: 'Status',
                render: (item) => <StatusBadge status={item.status} />
              },
              { key: 'requestedAt', header: 'Requested' },
              { 
                key: 'processedAt', 
                header: 'Processed',
                render: (item) => item.processedAt || '-'
              },
              { 
                key: 'transactionId', 
                header: 'Transaction ID',
                render: (item) => item.transactionId ? (
                  <code className="px-2 py-1 bg-muted rounded text-xs">{item.transactionId}</code>
                ) : '-'
              },
            ]}
            data={mockWithdrawals}
            emptyMessage="No withdrawals yet"
          />
        </CardContent>
      </Card>

      {/* Bank Details Notice */}
      <Card className="border-primary/20">
        <CardContent className="p-4 flex items-start gap-4">
          <div className="p-2 rounded-lg bg-primary/10">
            <CheckCircle className="h-5 w-5 text-primary" />
          </div>
          <div>
            <h3 className="font-semibold">Bank Account Verified</h3>
            <p className="text-sm text-muted-foreground mt-1">
              Your bank account (Chase Bank ****1234) is verified and ready to receive payouts.
              You can update your bank details in your profile settings.
            </p>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
