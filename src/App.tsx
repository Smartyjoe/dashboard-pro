import { Toaster } from "@/components/ui/toaster";
import { Toaster as Sonner } from "@/components/ui/sonner";
import { TooltipProvider } from "@/components/ui/tooltip";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";
import { AuthProvider } from "@/contexts/AuthContext";
import { ThemeProvider } from "@/contexts/ThemeContext";

// Layouts
import DashboardLayout from "@/components/layout/DashboardLayout";

// Pages
import Index from "./pages/Index";
import Login from "./pages/Login";
import NotFound from "./pages/NotFound";

// Admin Pages
import AdminDashboard from "./pages/admin/AdminDashboard";
import UsersPage from "./pages/admin/UsersPage";
import AffiliatesPage from "./pages/admin/AffiliatesPage";
import CommissionsPage from "./pages/admin/CommissionsPage";
import WithdrawalsPage from "./pages/admin/WithdrawalsPage";
import ProductsPage from "./pages/admin/ProductsPage";
import OrdersPage from "./pages/admin/OrdersPage";
import RoleManagementPage from "./pages/admin/RoleManagementPage";
import SettingsPage from "./pages/admin/SettingsPage";

// Affiliate Pages
import AffiliateDashboard from "./pages/affiliate/AffiliateDashboard";
import AffiliateLinksPage from "./pages/affiliate/AffiliateLinksPage";
import AffiliateWithdrawalsPage from "./pages/affiliate/AffiliateWithdrawalsPage";

const queryClient = new QueryClient();

const App = () => (
  <QueryClientProvider client={queryClient}>
    <ThemeProvider>
      <AuthProvider>
        <TooltipProvider>
          <Toaster />
          <Sonner />
          <BrowserRouter>
            <Routes>
              {/* Public Routes */}
              <Route path="/" element={<Index />} />
              <Route path="/login" element={<Login />} />

              {/* Admin Login */}
              <Route path="/admin" element={<Login key="admin-login" />} />

              {/* Admin Dashboard Routes */}
              <Route path="/dashboard" element={<DashboardLayout requiredRole="admin" />}>
                <Route index element={<AdminDashboard />} />
                <Route path="users" element={<UsersPage />} />
                <Route path="affiliates" element={<AffiliatesPage />} />
                <Route path="commissions" element={<CommissionsPage />} />
                <Route path="withdrawals" element={<WithdrawalsPage />} />
                <Route path="products" element={<ProductsPage />} />
                <Route path="orders" element={<OrdersPage />} />
                <Route path="coupons" element={<AdminDashboard />} />
                <Route path="analytics" element={<AdminDashboard />} />
                <Route path="emails" element={<AdminDashboard />} />
                <Route path="roles" element={<RoleManagementPage />} />
                <Route path="activity" element={<AdminDashboard />} />
                <Route path="settings" element={<SettingsPage />} />
              </Route>

              {/* Affiliate Dashboard Routes */}
              <Route path="/affiliate" element={<DashboardLayout requiredRole="affiliate" />}>
                <Route index element={<AffiliateDashboard />} />
                <Route path="links" element={<AffiliateLinksPage />} />
                <Route path="commissions" element={<AffiliateDashboard />} />
                <Route path="withdrawals" element={<AffiliateWithdrawalsPage />} />
                <Route path="analytics" element={<AffiliateDashboard />} />
                <Route path="profile" element={<SettingsPage />} />
              </Route>

              {/* Catch-all */}
              <Route path="*" element={<NotFound />} />
            </Routes>
          </BrowserRouter>
        </TooltipProvider>
      </AuthProvider>
    </ThemeProvider>
  </QueryClientProvider>
);

export default App;
