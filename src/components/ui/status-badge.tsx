import React from 'react';
import { cn } from '@/lib/utils';

interface StatusBadgeProps {
  status: string;
  variant?: 'default' | 'success' | 'warning' | 'destructive' | 'info';
  className?: string;
}

const statusVariantMap: Record<string, 'success' | 'warning' | 'destructive' | 'info' | 'default'> = {
  active: 'success',
  pending: 'warning',
  suspended: 'destructive',
  approved: 'success',
  paid: 'success',
  cancelled: 'destructive',
  completed: 'success',
  processing: 'info',
  rejected: 'destructive',
  'on-hold': 'warning',
  refunded: 'destructive',
  publish: 'success',
  draft: 'warning',
  instock: 'success',
  outofstock: 'destructive',
  onbackorder: 'warning',
};

const variantStyles = {
  default: 'bg-secondary text-secondary-foreground',
  success: 'bg-success/15 text-success border-success/20',
  warning: 'bg-warning/15 text-warning border-warning/20',
  destructive: 'bg-destructive/15 text-destructive border-destructive/20',
  info: 'bg-primary/15 text-primary border-primary/20',
};

export function StatusBadge({ status, variant, className }: StatusBadgeProps) {
  const resolvedVariant = variant || statusVariantMap[status.toLowerCase()] || 'default';
  
  return (
    <span className={cn(
      "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border",
      variantStyles[resolvedVariant],
      className
    )}>
      {status.charAt(0).toUpperCase() + status.slice(1).replace(/-/g, ' ')}
    </span>
  );
}
