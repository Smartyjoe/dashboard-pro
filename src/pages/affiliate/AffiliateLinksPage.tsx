import React, { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { DataTable } from '@/components/ui/data-table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { 
  Link2, 
  Copy, 
  ExternalLink, 
  Plus,
  Search,
  Eye,
  MousePointer,
  ShoppingCart,
} from 'lucide-react';
import { toast } from 'sonner';

// Mock product data
const mockProducts = [
  { id: 1, name: 'Premium Theme', price: 89.99, image: '/placeholder.svg' },
  { id: 2, name: 'Plugin Bundle', price: 149.00, image: '/placeholder.svg' },
  { id: 3, name: 'Starter Pack', price: 59.99, image: '/placeholder.svg' },
  { id: 4, name: 'Pro Membership', price: 199.99, image: '/placeholder.svg' },
  { id: 5, name: 'Developer Tools', price: 299.99, image: '/placeholder.svg' },
];

// Mock generated links
const mockLinks = [
  { id: 1, name: 'Global Link', url: 'https://store.com/?ref=MIKE2024', clicks: 456, conversions: 23, created: '2024-02-01' },
  { id: 2, name: 'Premium Theme', url: 'https://store.com/premium-theme?ref=MIKE2024', clicks: 234, conversions: 12, created: '2024-02-15' },
  { id: 3, name: 'Plugin Bundle', url: 'https://store.com/plugin-bundle?ref=MIKE2024', clicks: 189, conversions: 8, created: '2024-03-01' },
  { id: 4, name: 'Pro Membership', url: 'https://store.com/pro-membership?ref=MIKE2024', clicks: 156, conversions: 5, created: '2024-03-10' },
];

export default function AffiliateLinksPage() {
  const [search, setSearch] = useState('');
  const [selectedProduct, setSelectedProduct] = useState('');
  const affiliateCode = 'MIKE2024';

  const copyLink = (url: string) => {
    navigator.clipboard.writeText(url);
    toast.success('Link copied to clipboard!');
  };

  const generateLink = () => {
    if (selectedProduct) {
      const product = mockProducts.find(p => p.id.toString() === selectedProduct);
      if (product) {
        const link = `https://store.com/${product.name.toLowerCase().replace(/\s+/g, '-')}?ref=${affiliateCode}`;
        copyLink(link);
        toast.success(`Link generated for ${product.name}!`);
      }
    }
  };

  const filteredLinks = mockLinks.filter(link =>
    link.name.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <div className="space-y-6 animate-fade-in">
      {/* Header */}
      <div>
        <h1 className="text-2xl md:text-3xl font-bold">Affiliate Links</h1>
        <p className="text-muted-foreground">Generate and manage your referral links</p>
      </div>

      {/* Global Referral Link */}
      <Card className="border-primary/20 bg-gradient-to-r from-primary/5 to-transparent">
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Link2 className="h-5 w-5" />
            Your Global Referral Link
          </CardTitle>
          <CardDescription>
            This link works for any product. When visitors click and purchase anything, you earn commission.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="flex gap-2">
            <Input 
              value={`https://store.com/?ref=${affiliateCode}`}
              readOnly 
              className="bg-background font-mono"
            />
            <Button 
              variant="outline" 
              size="icon"
              onClick={() => copyLink(`https://store.com/?ref=${affiliateCode}`)}
            >
              <Copy className="h-4 w-4" />
            </Button>
            <Button variant="outline" size="icon" asChild>
              <a href={`https://store.com/?ref=${affiliateCode}`} target="_blank" rel="noopener noreferrer">
                <ExternalLink className="h-4 w-4" />
              </a>
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Generate Product Link */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Plus className="h-5 w-5" />
            Generate Product Link
          </CardTitle>
          <CardDescription>
            Create a direct link to a specific product
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="flex-1 space-y-2">
              <Label>Select Product</Label>
              <Select value={selectedProduct} onValueChange={setSelectedProduct}>
                <SelectTrigger>
                  <SelectValue placeholder="Choose a product..." />
                </SelectTrigger>
                <SelectContent>
                  {mockProducts.map(product => (
                    <SelectItem key={product.id} value={product.id.toString()}>
                      {product.name} - ${product.price}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="flex items-end">
              <Button 
                onClick={generateLink}
                disabled={!selectedProduct}
                className="gradient-primary text-primary-foreground w-full sm:w-auto"
              >
                Generate & Copy Link
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Your Links */}
      <Card>
        <CardHeader>
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <CardTitle>Your Links</CardTitle>
              <CardDescription>Track performance of your affiliate links</CardDescription>
            </div>
            <div className="relative w-full sm:w-64">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Search links..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="pl-9"
              />
            </div>
          </div>
        </CardHeader>
        <CardContent className="p-0">
          <DataTable
            columns={[
              { 
                key: 'name', 
                header: 'Link Name',
                render: (item) => (
                  <div className="flex items-center gap-3">
                    <div className="p-2 rounded-lg bg-primary/10">
                      <Link2 className="h-4 w-4 text-primary" />
                    </div>
                    <span className="font-medium">{item.name}</span>
                  </div>
                )
              },
              { 
                key: 'clicks', 
                header: 'Clicks',
                render: (item) => (
                  <div className="flex items-center gap-2">
                    <MousePointer className="h-4 w-4 text-muted-foreground" />
                    <span>{item.clicks}</span>
                  </div>
                )
              },
              { 
                key: 'conversions', 
                header: 'Conversions',
                render: (item) => (
                  <div className="flex items-center gap-2">
                    <ShoppingCart className="h-4 w-4 text-muted-foreground" />
                    <span>{item.conversions}</span>
                    <span className="text-xs text-muted-foreground">
                      ({((item.conversions / item.clicks) * 100).toFixed(1)}%)
                    </span>
                  </div>
                )
              },
              { key: 'created', header: 'Created' },
              {
                key: 'actions',
                header: '',
                render: (item) => (
                  <div className="flex items-center gap-1">
                    <Button 
                      variant="ghost" 
                      size="sm"
                      onClick={() => copyLink(item.url)}
                    >
                      <Copy className="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="sm" asChild>
                      <a href={item.url} target="_blank" rel="noopener noreferrer">
                        <ExternalLink className="h-4 w-4" />
                      </a>
                    </Button>
                  </div>
                )
              },
            ]}
            data={filteredLinks}
            emptyMessage="No links found"
          />
        </CardContent>
      </Card>

      {/* Tips */}
      <Card>
        <CardHeader>
          <CardTitle className="text-lg">Tips for Maximizing Conversions</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div className="p-4 rounded-lg bg-muted/30">
              <h4 className="font-medium mb-2">Use Product Links</h4>
              <p className="text-sm text-muted-foreground">
                Direct links to specific products convert better than generic links.
              </p>
            </div>
            <div className="p-4 rounded-lg bg-muted/30">
              <h4 className="font-medium mb-2">Share on Social Media</h4>
              <p className="text-sm text-muted-foreground">
                Post your links on platforms where your audience is most active.
              </p>
            </div>
            <div className="p-4 rounded-lg bg-muted/30">
              <h4 className="font-medium mb-2">Write Reviews</h4>
              <p className="text-sm text-muted-foreground">
                Create honest product reviews with your affiliate links embedded.
              </p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
