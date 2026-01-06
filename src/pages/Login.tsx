import React, { useState } from 'react';
import { useNavigate, Navigate } from 'react-router-dom';
import { useAuth } from '@/contexts/AuthContext';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useTheme } from '@/contexts/ThemeContext';
import { Moon, Sun, Eye, EyeOff, AlertCircle } from 'lucide-react';
import { cn } from '@/lib/utils';

export default function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  
  const { login, isAuthenticated, user } = useAuth();
  const isAdminLogin = window.location.pathname === '/admin';
  const { resolvedTheme, toggleTheme } = useTheme();
  const navigate = useNavigate();

  if (isAuthenticated && user) {
    const redirectPath = user.role === 'affiliate' ? '/affiliate' : '/dashboard';
    return <Navigate to={redirectPath} replace />;
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setIsSubmitting(true);

    try {
      const success = await login(email, password);
      if (success) {
        // Redirect handled by the component re-render
      } else {
        setError('Invalid email or password. Try demo credentials below.');
      }
    } catch {
      setError('An error occurred. Please try again.');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-background p-4">
      {/* Background decoration */}
      <div className="fixed inset-0 overflow-hidden pointer-events-none">
        <div className="absolute -top-1/2 -right-1/2 w-full h-full rounded-full bg-primary/5 blur-3xl" />
        <div className="absolute -bottom-1/2 -left-1/2 w-full h-full rounded-full bg-accent/5 blur-3xl" />
      </div>

      {/* Theme toggle */}
      <Button
        variant="ghost"
        size="icon"
        onClick={toggleTheme}
        className="fixed top-4 right-4 z-50"
      >
        {resolvedTheme === 'dark' ? <Sun className="h-5 w-5" /> : <Moon className="h-5 w-5" />}
      </Button>

      <div className="w-full max-w-md relative z-10 animate-slide-up">
        {/* Logo */}
        <div className="flex flex-col items-center mb-8">
          <div className="w-16 h-16 rounded-2xl gradient-primary flex items-center justify-center shadow-glow mb-4">
            <span className="text-3xl font-bold text-primary-foreground">W</span>
          </div>
          <h1 className="text-2xl font-bold">WP Dashboard</h1>
          <p className="text-muted-foreground mt-1">Affiliate Management System</p>
        </div>

        <Card className="border-border/50 shadow-xl">
          <CardHeader className="text-center pb-4">
            <CardTitle className="text-xl">Welcome back</CardTitle>
            <CardDescription>Sign in to your account to continue</CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-4">
              {error && (
                <div className="flex items-center gap-2 p-3 rounded-lg bg-destructive/10 text-destructive text-sm">
                  <AlertCircle className="h-4 w-4 flex-shrink-0" />
                  <span>{error}</span>
                </div>
              )}

              <div className="space-y-2">
                <Label htmlFor="email">Email</Label>
                <Input
                  id="email"
                  type="email"
                  placeholder="you@example.com"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  required
                  className="h-11"
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="password">Password</Label>
                <div className="relative">
                  <Input
                    id="password"
                    type={showPassword ? 'text' : 'password'}
                    placeholder="••••••••"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    required
                    className="h-11 pr-10"
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                  >
                    {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                  </button>
                </div>
              </div>

              <Button 
                type="submit" 
                className="w-full h-11 gradient-primary text-primary-foreground font-medium"
                disabled={isSubmitting}
              >
                {isSubmitting ? (
                  <span className="flex items-center gap-2">
                    <span className="h-4 w-4 border-2 border-current border-t-transparent rounded-full animate-spin" />
                    Signing in...
                  </span>
                ) : (
                  'Sign in'
                )}
              </Button>
            </form>

            {/* Demo credentials */}
            <div className="mt-6 pt-6 border-t border-border">
              <p className="text-sm text-muted-foreground text-center mb-3">
                Demo credentials (password: <code className="px-1.5 py-0.5 bg-muted rounded text-xs">demo123</code>)
              </p>
              <div className="grid gap-2 text-sm">
                <button
                  type="button"
                  onClick={() => { setEmail('admin@example.com'); setPassword('demo123'); }}
                  className="flex items-center justify-between p-2.5 rounded-lg bg-muted/50 hover:bg-muted transition-colors text-left"
                >
                  <span className="font-medium">Super Admin</span>
                  <span className="text-muted-foreground text-xs">admin@example.com</span>
                </button>
                <button
                  type="button"
                  onClick={() => { setEmail('manager@example.com'); setPassword('demo123'); }}
                  className="flex items-center justify-between p-2.5 rounded-lg bg-muted/50 hover:bg-muted transition-colors text-left"
                >
                  <span className="font-medium">Store Manager</span>
                  <span className="text-muted-foreground text-xs">manager@example.com</span>
                </button>
                <button
                  type="button"
                  onClick={() => { setEmail('affiliate@example.com'); setPassword('demo123'); }}
                  className="flex items-center justify-between p-2.5 rounded-lg bg-muted/50 hover:bg-muted transition-colors text-left"
                >
                  <span className="font-medium">Affiliate User</span>
                  <span className="text-muted-foreground text-xs">affiliate@example.com</span>
                </button>
              </div>
            </div>
          </CardContent>
        </Card>

        {isAdminLogin ? null : (
        <p className="text-center text-sm text-muted-foreground mt-6">
          Powered by WordPress & WooCommerce
        </p>
        )}
      </div>
    </div>
  );
}
