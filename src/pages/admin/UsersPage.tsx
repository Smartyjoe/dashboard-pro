import React, { useState } from 'react';
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
  DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Search, Plus, MoreHorizontal, UserCog, Mail, Trash2 } from 'lucide-react';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

// Mock data
const mockUsers = [
  { id: 1, name: 'John Admin', email: 'admin@example.com', role: 'super_admin', status: 'active', createdAt: '2024-01-01', orders: 0 },
  { id: 2, name: 'Sarah Manager', email: 'manager@example.com', role: 'store_manager', status: 'active', createdAt: '2024-01-15', orders: 0 },
  { id: 3, name: 'Mike Partner', email: 'affiliate@example.com', role: 'affiliate', status: 'active', createdAt: '2024-02-01', orders: 45 },
  { id: 4, name: 'Alice Customer', email: 'alice@example.com', role: 'customer', status: 'active', createdAt: '2024-02-10', orders: 12 },
  { id: 5, name: 'Bob Buyer', email: 'bob@example.com', role: 'customer', status: 'active', createdAt: '2024-02-15', orders: 8 },
  { id: 6, name: 'Carol Davis', email: 'carol@example.com', role: 'customer', status: 'pending', createdAt: '2024-03-01', orders: 0 },
  { id: 7, name: 'Dan Wilson', email: 'dan@example.com', role: 'affiliate', status: 'suspended', createdAt: '2024-03-05', orders: 23 },
];

const roleLabels: Record<string, string> = {
  super_admin: 'Super Admin',
  administrator: 'Administrator',
  store_manager: 'Store Manager',
  customer: 'Customer',
  affiliate: 'Affiliate',
};

export default function UsersPage() {
  const [search, setSearch] = useState('');
  const [roleFilter, setRoleFilter] = useState('all');
  const [isAddDialogOpen, setIsAddDialogOpen] = useState(false);

  const filteredUsers = mockUsers.filter(user => {
    const matchesSearch = user.name.toLowerCase().includes(search.toLowerCase()) ||
                         user.email.toLowerCase().includes(search.toLowerCase());
    const matchesRole = roleFilter === 'all' || user.role === roleFilter;
    return matchesSearch && matchesRole;
  });

  return (
    <div className="space-y-6 animate-fade-in">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl md:text-3xl font-bold">Users</h1>
          <p className="text-muted-foreground">Manage all user accounts and roles</p>
        </div>
        <Dialog open={isAddDialogOpen} onOpenChange={setIsAddDialogOpen}>
          <DialogTrigger asChild>
            <Button className="gradient-primary text-primary-foreground">
              <Plus className="h-4 w-4 mr-2" />
              Add User
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Add New User</DialogTitle>
              <DialogDescription>
                Create a new user account. An invitation will be sent to their email.
              </DialogDescription>
            </DialogHeader>
            <div className="space-y-4 py-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="firstName">First Name</Label>
                  <Input id="firstName" placeholder="John" />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="lastName">Last Name</Label>
                  <Input id="lastName" placeholder="Doe" />
                </div>
              </div>
              <div className="space-y-2">
                <Label htmlFor="email">Email</Label>
                <Input id="email" type="email" placeholder="john@example.com" />
              </div>
              <div className="space-y-2">
                <Label htmlFor="role">Role</Label>
                <Select>
                  <SelectTrigger>
                    <SelectValue placeholder="Select role" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="administrator">Administrator</SelectItem>
                    <SelectItem value="store_manager">Store Manager</SelectItem>
                    <SelectItem value="customer">Customer</SelectItem>
                    <SelectItem value="affiliate">Affiliate</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <Button className="w-full gradient-primary text-primary-foreground">
                Create User
              </Button>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      {/* Filters */}
      <Card>
        <CardContent className="p-4">
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Search users..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="pl-9"
              />
            </div>
            <Select value={roleFilter} onValueChange={setRoleFilter}>
              <SelectTrigger className="w-full sm:w-48">
                <SelectValue placeholder="Filter by role" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Roles</SelectItem>
                <SelectItem value="super_admin">Super Admin</SelectItem>
                <SelectItem value="administrator">Administrator</SelectItem>
                <SelectItem value="store_manager">Store Manager</SelectItem>
                <SelectItem value="customer">Customer</SelectItem>
                <SelectItem value="affiliate">Affiliate</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      {/* Users Table */}
      <DataTable
        columns={[
          { 
            key: 'name', 
            header: 'User',
            render: (item) => (
              <div className="flex items-center gap-3">
                <div className="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center">
                  <span className="text-sm font-medium text-primary">
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
            key: 'role', 
            header: 'Role',
            render: (item) => (
              <span className="px-2.5 py-1 rounded-md bg-secondary text-secondary-foreground text-sm font-medium">
                {roleLabels[item.role]}
              </span>
            )
          },
          { 
            key: 'status', 
            header: 'Status',
            render: (item) => <StatusBadge status={item.status} />
          },
          { key: 'orders', header: 'Orders' },
          { key: 'createdAt', header: 'Joined' },
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
                    <UserCog className="h-4 w-4 mr-2" />
                    Edit User
                  </DropdownMenuItem>
                  <DropdownMenuItem>
                    <Mail className="h-4 w-4 mr-2" />
                    Send Email
                  </DropdownMenuItem>
                  <DropdownMenuItem className="text-destructive">
                    <Trash2 className="h-4 w-4 mr-2" />
                    Delete User
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            )
          },
        ]}
        data={filteredUsers}
        emptyMessage="No users found"
      />
    </div>
  );
}
