import React from 'react';
import { cn } from '@/lib/utils';
import { TrendingUp, TrendingDown, Minus } from 'lucide-react';

interface StatCardProps {
  title: string;
  value: string | number;
  change?: number;
  changeLabel?: string;
  icon?: React.ReactNode;
  variant?: 'default' | 'primary' | 'accent' | 'warning';
  className?: string;
}

const variantStyles = {
  default: '',
  primary: 'border-primary/20 hover:border-primary/40',
  accent: 'border-accent/20 hover:border-accent/40',
  warning: 'border-warning/20 hover:border-warning/40',
};

const iconBgStyles = {
  default: 'bg-secondary text-secondary-foreground',
  primary: 'bg-primary/10 text-primary',
  accent: 'bg-accent/10 text-accent',
  warning: 'bg-warning/10 text-warning',
};

export function StatCard({ 
  title, 
  value, 
  change, 
  changeLabel,
  icon,
  variant = 'default',
  className 
}: StatCardProps) {
  const isPositive = change && change > 0;
  const isNegative = change && change < 0;

  return (
    <div className={cn('stat-card', variantStyles[variant], className)}>
      <div className="flex items-start justify-between gap-4">
        <div className="flex-1 min-w-0">
          <p className="text-sm font-medium text-muted-foreground">{title}</p>
          <h3 className="text-2xl md:text-3xl font-bold mt-2 tracking-tight">{value}</h3>
          
          {change !== undefined && (
            <div className="flex items-center gap-1.5 mt-2">
              {isPositive && (
                <>
                  <TrendingUp className="h-4 w-4 text-success" />
                  <span className="text-sm font-medium text-success">+{change}%</span>
                </>
              )}
              {isNegative && (
                <>
                  <TrendingDown className="h-4 w-4 text-destructive" />
                  <span className="text-sm font-medium text-destructive">{change}%</span>
                </>
              )}
              {!isPositive && !isNegative && (
                <>
                  <Minus className="h-4 w-4 text-muted-foreground" />
                  <span className="text-sm font-medium text-muted-foreground">0%</span>
                </>
              )}
              {changeLabel && (
                <span className="text-sm text-muted-foreground">{changeLabel}</span>
              )}
            </div>
          )}
        </div>

        {icon && (
          <div className={cn(
            'flex-shrink-0 p-3 rounded-xl',
            iconBgStyles[variant]
          )}>
            {icon}
          </div>
        )}
      </div>
    </div>
  );
}
