import React, { useEffect, useState } from 'react';
import { DataTable } from '@/components/ui/data-table';
import { StatusBadge } from '@/components/ui/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { 
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Search, MoreHorizontal, Eye, Printer, Package, XCircle, CheckCircle } from 'lucide-react';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
  DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { toast } from 'sonner';
import { Textarea } from '@/components/ui/textarea';

import wordpressApi from '@/services/wordpress-api';

// Real orders will be fetched from API
const mockOrders: any[] = [];
/*  { 
    id: 1001, 
    order_number: '#1001',
    customer: { name: 'Alice Johnson', email: 'alice@example.com' },
    status: 'completed',
    total: 149.00,
    items: 2,
    payment_method: 'Credit Card',
    date_created: '2024-03-10 14:32:00',
    billing: {
      first_name: 'Alice',
      last_name: 'Johnson',
      email: 'alice@example.com',
      phone: '+1234567890',
      address_1: '123 Main St',
      city: 'New York',
      state: 'NY',
      postcode: '10001',
      country: 'US',
    },
    line_items: [
      { id: 1, name: 'Premium WordPress Theme', quantity: 1, total: 99.00 },
      { id: 2, name: 'SEO Plugin Pro', quantity: 1, total: 50.00 },
    ]
  },
/*  { 
    id: 1002, 
    order_number: '#1002',
    customer: { name: 'Bob Smith', email: 'bob@example.com' },
    status: 'processing',
    total: 299.00,
    items: 1,
    payment_method: 'PayPal',
    date_created: '2024-03-11 09:15:00',
    billing: {
      first_name: 'Bob',
      last_name: 'Smith',
      email: 'bob@example.com',
      phone: '+1234567891',
      address_1: '456 Oak Ave',
      city: 'Los Angeles',
      state: 'CA',
      postcode: '90001',
      country: 'US',
    },
    line_items: [
      { id: 1, name: 'Affiliate Marketing Suite', quantity: 1, total: 299.00 },
    ]
  },
/*  { 
    id: 1003, 
    order_number: '#1003',
    customer: { name: 'Carol Davis', email: 'carol@example.com' },
    status: 'pending',
    total: 79.00,
    items: 1,
    payment_method: 'Bank Transfer',
    date_created: '2024-03-11 16:45:00',
    billing: {
      first_name: 'Carol',
      last_name: 'Davis',
      email: 'carol@example.com',
      phone: '+1234567892',
      address_1: '789 Pine Rd',
      city: 'Chicago',
      state: 'IL',
      postcode: '60601',
      country: 'US',
    },
    line_items: [
      { id: 1, name: 'SEO Plugin Pro', quantity: 1, total: 79.00 },
    ]
  },
/*  { 
    id: 1004, 
    order_number: '#1004',
    customer: { name: 'Dan Wilson', email: 'dan@example.com' },
    status: 'on-hold',
    total: 198.00,
    items: 2,
    payment_method: 'Credit Card',
    date_created: '2024-03-12 11:20:00',
    billing: {
      first_name: 'Dan',
      last_name: 'Wilson',
      email: 'dan@example.com',
      phone: '+1234567893',
      address_1: '321 Elm St',
      city: 'Houston',
      state: 'TX',
      postcode: '77001',
      country: 'US',
    },
    line_items: [
      { id: 1, name: 'Premium WordPress Theme', quantity: 2, total: 198.00 },
    ]
  },
/*  { 
    id: 1005, 
    order_number: '#1005',
    customer: { name: 'Eve Martinez', email: 'eve@example.com' },
    status: 'failed',
    total: 149.00,
    items: 1,
    payment_method: 'Credit Card',
    date_created: '2024-03-12 15:10:00',
    billing: {
      first_name: 'Eve',
      last_name: 'Martinez',
      email: 'eve@example.com',
      phone: '+1234567894',
      address_1: '654 Maple Dr',
      city: 'Phoenix',
      state: 'AZ',
      postcode: '85001',
      country: 'US',
    },
    line_items: [
      { id: 1, name: 'Landing Page Builder', quantity: 1, total: 149.00 },
    ]
  },
*/

const orderStatusLabels: Record<string, { label: string; variant: 'success' | 'warning' | 'destructive' | 'default' }> = {
  pending: { label: 'Pending Payment', variant: 'warning' },
  processing: { label: 'Processing', variant: 'default' },
  'on-hold': { label: 'On Hold', variant: 'warning' },
  completed: { label: 'Completed', variant: 'success' },
  cancelled: { label: 'Cancelled', variant: 'destructive' },
  refunded: { label: 'Refunded', variant: 'destructive' },
  failed: { label: 'Failed', variant: 'destructive' },
};

export default function OrdersPage() {
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [isViewDialogOpen, setIsViewDialogOpen] = useState(false);
  const [loading, setLoading] = useState(false);
  const [orders, setOrders] = useState<any[]>([]);
  const [page, setPage] = useState(1);
  const [perPage] = useState(20);
  const [total, setTotal] = useState(0);
  const [isStatusDialogOpen, setIsStatusDialogOpen] = useState(false);
  const [selectedOrder, setSelectedOrder] = useState<any>(null);
  const [newStatus, setNewStatus] = useState('');
  const [statusNote, setStatusNote] = useState('');

  const filteredOrders = orders.filter((order: any) => {
    const matchesSearch = 
      order.order_number.toLowerCase().includes(search.toLowerCase()) ||
      order.customer.name.toLowerCase().includes(search.toLowerCase()) ||
      order.customer.email.toLowerCase().includes(search.toLowerCase());
    const matchesStatus = statusFilter === 'all' || order.status === statusFilter;
    return matchesSearch && matchesStatus;
  });

  const handleViewOrder = async (order: any) => {
    // Fetch detailed order
    const res = await wordpressApi.getOrder(order.id);
    if (res.success && res.data) {
      setSelectedOrder(res.data);
      setIsViewDialogOpen(true);
    } else {
      setSelectedOrder(order);
      setIsViewDialogOpen(true);
    }
  };

  const handleChangeStatus = async (order: any) => {
    setSelectedOrder(order);
    setNewStatus(order.status);
    setStatusNote('');
    setIsStatusDialogOpen(true);
  };

  const handleUpdateStatus = async () => {
    if (!selectedOrder) return;
    const res = await wordpressApi.updateOrderStatus(selectedOrder.id, newStatus, statusNote);
    if (res.success) {
      toast.success(`Order status updated to ${orderStatusLabels[newStatus].label}`);
      setIsStatusDialogOpen(false);
      // refresh orders list
      fetchOrders();
    } else {
      toast.error(res.error || 'Failed to update order status');
    }
  };

  const handlePrintOrder = (order: any) => {
    toast.info('Printing order...');
  };

  const fetchOrders = async () => {
    setLoading(true);
    const res = await wordpressApi.getOrders({ page, perPage, status: statusFilter !== 'all' ? statusFilter : undefined, search });
    if (res.success && res.data) {
      setOrders(res.data.data);
      setTotal(res.data.total);
    } else {
      toast.error(res.error || 'Failed to load orders');
    }
    setLoading(false);
  };

  useEffect(() => {
    fetchOrders();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [page, perPage, statusFilter, search]);

  const columns = [
    {
      key: 'order_number',
      header: 'Order',
      render: (order: any) => (
        <div className="font-medium">{order.order_number}</div>
      ),
    },
    {
      key: 'customer',
      header: 'Customer',
      render: (order: any) => {
        const name = `${order.customer.first_name || ''} ${order.customer.last_name || ''}`.trim();
        return (
          <div>
            <div className="font-medium">{name || order.customer.email || '—'}</div>
            <div className="text-sm text-muted-foreground">{order.customer.email}</div>
          </div>
        );
      },
    },
    {
      key: 'status',
      header: 'Status',
      render: (order: any) => (
        <StatusBadge 
          status={orderStatusLabels[order.status].label} 
          variant={orderStatusLabels[order.status].variant} 
        />
      ),
    },
    {
      key: 'date',
      header: 'Date',
      render: (order: any) => (
        <div className="text-sm">
          {new Date(order.date_created).toLocaleDateString()}
          <div className="text-muted-foreground">
            {new Date(order.date_created).toLocaleTimeString()}
          </div>
        </div>
      ),
    },
    {
      key: 'total',
      header: 'Total',
      render: (order: any) => (
        <div className="font-medium">${order.total.toFixed(2)}</div>
      ),
    },
    // Items count is not available in list payload; omit or fetch per-row if needed
    // {
    //   key: 'items',
    //   header: 'Items',
    //   render: (order: any) => '—',
    // },
    {
      key: 'payment',
      header: 'Payment',
      render: (order: any) => (
        <Badge variant="outline">{order.payment_method}</Badge>
      ),
    },
    {
      key: 'actions',
      header: '',
      render: (order: any) => (
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="icon">
              <MoreHorizontal className="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem onClick={() => handleViewOrder(order)}>
              <Eye className="h-4 w-4 mr-2" />
              View Details
            </DropdownMenuItem>
            <DropdownMenuItem onClick={() => handleChangeStatus(order)}>
              <Package className="h-4 w-4 mr-2" />
              Change Status
            </DropdownMenuItem>
            <DropdownMenuItem onClick={() => handlePrintOrder(order)}>
              <Printer className="h-4 w-4 mr-2" />
              Print Order
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            {order.status === 'completed' && (
              <DropdownMenuItem className="text-orange-600">
                <XCircle className="h-4 w-4 mr-2" />
                Refund
              </DropdownMenuItem>
            )}
            {order.status !== 'cancelled' && order.status !== 'completed' && (
              <DropdownMenuItem className="text-destructive">
                <XCircle className="h-4 w-4 mr-2" />
                Cancel Order
              </DropdownMenuItem>
            )}
          </DropdownMenuContent>
        </DropdownMenu>
      ),
    },
  ];

  return (
    <div className="space-y-6 animate-fade-in">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl md:text-3xl font-bold">Orders</h1>
          <p className="text-muted-foreground">Manage and process customer orders</p>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card>
          <CardHeader className="pb-2">
            <CardDescription>Total Orders</CardDescription>
            <CardTitle className="text-2xl">{mockOrders.length}</CardTitle>
          </CardHeader>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardDescription>Processing</CardDescription>
            <CardTitle className="text-2xl">
              {orders.filter((o: any) => o.status === 'processing').length}
            </CardTitle>
          </CardHeader>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardDescription>Completed</CardDescription>
            <CardTitle className="text-2xl text-success">
              {orders.filter((o: any) => o.status === 'completed').length}
            </CardTitle>
          </CardHeader>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardDescription>Total Revenue</CardDescription>
            <CardTitle className="text-2xl">
              ${orders.reduce((sum: number, o: any) => sum + Number(o.total || 0), 0).toFixed(2)}
            </CardTitle>
          </CardHeader>
        </Card>
      </div>

      {/* Filters and Table */}
      <Card>
        <CardHeader>
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="flex-1 relative">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Search orders..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="pl-10"
              />
            </div>
            <Select value={statusFilter} onValueChange={setStatusFilter}>
              <SelectTrigger className="w-full sm:w-[200px]">
                <SelectValue placeholder="Order Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Statuses</SelectItem>
                <SelectItem value="pending">Pending Payment</SelectItem>
                <SelectItem value="processing">Processing</SelectItem>
                <SelectItem value="on-hold">On Hold</SelectItem>
                <SelectItem value="completed">Completed</SelectItem>
                <SelectItem value="cancelled">Cancelled</SelectItem>
                <SelectItem value="refunded">Refunded</SelectItem>
                <SelectItem value="failed">Failed</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="py-10 text-center text-muted-foreground">Loading orders...</div>
          ) : (
            <>
              <DataTable columns={columns} data={filteredOrders} />
              <div className="flex items-center justify-between mt-4">
                <div className="text-sm text-muted-foreground">
                  Page {page} of {Math.max(1, Math.ceil(total / perPage))} • {total} total
                </div>
                <div className="space-x-2">
                  <Button variant="outline" disabled={page <= 1} onClick={() => setPage((p) => Math.max(1, p - 1))}>Previous</Button>
                  <Button variant="outline" disabled={page >= Math.ceil(total / perPage)} onClick={() => setPage((p) => p + 1)}>Next</Button>
                </div>
              </div>
            </>
          )}
        </CardContent>
      </Card>

      {/* View Order Dialog */}
      <Dialog open={isViewDialogOpen} onOpenChange={setIsViewDialogOpen}>
        <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Order Details {selectedOrder?.order_number}</DialogTitle>
            <DialogDescription>
              Order placed on {selectedOrder && new Date(selectedOrder.date_created).toLocaleString()}
            </DialogDescription>
          </DialogHeader>
          {selectedOrder && (
            <div className="space-y-6 py-4">
              {/* Order Status */}
              <div>
                <Label className="text-base font-semibold">Order Status</Label>
                <div className="mt-2">
                  <StatusBadge 
                    status={orderStatusLabels[selectedOrder.status].label} 
                    variant={orderStatusLabels[selectedOrder.status].variant} 
                  />
                </div>
              </div>

              <Separator />

              {/* Customer Information */}
              <div>
                <Label className="text-base font-semibold">Customer Information</Label>
                <div className="mt-2 space-y-1 text-sm">
                  <div><span className="font-medium">Name:</span> {selectedOrder.billing.first_name} {selectedOrder.billing.last_name}</div>
                  <div><span className="font-medium">Email:</span> {selectedOrder.billing.email}</div>
                  <div><span className="font-medium">Phone:</span> {selectedOrder.billing.phone}</div>
                </div>
              </div>

              <Separator />

              {/* Billing Address */}
              <div>
                <Label className="text-base font-semibold">Billing Address</Label>
                <div className="mt-2 text-sm">
                  <div>{selectedOrder.billing.address_1}</div>
                  <div>{selectedOrder.billing.city}, {selectedOrder.billing.state} {selectedOrder.billing.postcode}</div>
                  <div>{selectedOrder.billing.country}</div>
                </div>
              </div>

              <Separator />

              {/* Order Items */}
              <div>
                <Label className="text-base font-semibold">Order Items</Label>
                <div className="mt-2 space-y-2">
                  {selectedOrder.line_items.map((item: any) => (
                    <div key={item.id} className="flex justify-between items-center p-3 bg-muted/50 rounded-lg">
                      <div>
                        <div className="font-medium">{item.name}</div>
                        <div className="text-sm text-muted-foreground">Quantity: {item.quantity}</div>
                      </div>
                      <div className="font-medium">${item.total.toFixed(2)}</div>
                    </div>
                  ))}
                </div>
              </div>

              <Separator />

              {/* Order Total */}
              <div className="flex justify-between items-center text-lg font-bold">
                <span>Total</span>
                <span>${selectedOrder.total.toFixed(2)}</span>
              </div>

              {/* Payment Method */}
              <div className="flex justify-between items-center text-sm">
                <span className="text-muted-foreground">Payment Method</span>
                <Badge variant="outline">{selectedOrder.payment_method}</Badge>
              </div>
            </div>
          )}
          <DialogFooter>
            <Button variant="outline" onClick={() => setIsViewDialogOpen(false)}>
              Close
            </Button>
            <Button onClick={() => handlePrintOrder(selectedOrder)}>
              <Printer className="h-4 w-4 mr-2" />
              Print
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Change Status Dialog */}
      <Dialog open={isStatusDialogOpen} onOpenChange={setIsStatusDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Change Order Status</DialogTitle>
            <DialogDescription>
              Update the status for order {selectedOrder?.order_number}
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4 py-4">
            <div className="space-y-2">
              <Label htmlFor="status">New Status</Label>
              <Select value={newStatus} onValueChange={setNewStatus}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="pending">Pending Payment</SelectItem>
                  <SelectItem value="processing">Processing</SelectItem>
                  <SelectItem value="on-hold">On Hold</SelectItem>
                  <SelectItem value="completed">Completed</SelectItem>
                  <SelectItem value="cancelled">Cancelled</SelectItem>
                  <SelectItem value="refunded">Refunded</SelectItem>
                  <SelectItem value="failed">Failed</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="note">Order Note (Optional)</Label>
              <Textarea 
                id="note"
                placeholder="Add a note about this status change..."
                value={statusNote}
                onChange={(e) => setStatusNote(e.target.value)}
                rows={3}
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setIsStatusDialogOpen(false)}>
              Cancel
            </Button>
            <Button onClick={handleUpdateStatus}>
              Update Status
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
