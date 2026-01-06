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
  DialogFooter,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Search, Plus, MoreHorizontal, Edit, Trash2, Package, Image as ImageIcon, Copy } from 'lucide-react';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
  DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';
import { toast } from 'sonner';
import { ImageUpload } from '@/components/ui/image-upload';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

import { useEffect } from 'react';
import wordpressApi from '@/services/wordpress-api';
import { Product } from '@/types';

// Products state will be fetched from API
const mockProducts: any[] = [];

const stockStatusLabels: Record<string, { label: string; variant: 'success' | 'warning' | 'destructive' }> = {
  instock: { label: 'In Stock', variant: 'success' },
  outofstock: { label: 'Out of Stock', variant: 'destructive' },
  onbackorder: { label: 'On Backorder', variant: 'warning' },
};

const productStatusLabels: Record<string, { label: string; variant: 'success' | 'warning' | 'destructive' }> = {
  publish: { label: 'Published', variant: 'success' },
  draft: { label: 'Draft', variant: 'warning' },
  pending: { label: 'Pending', variant: 'warning' },
  private: { label: 'Private', variant: 'destructive' },
};

export default function ProductsPage() {
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(false);
  const [products, setProducts] = useState<Product[]>([]);
  const [page, setPage] = useState(1);
  const [perPage] = useState(20);
  const [total, setTotal] = useState(0);
  const [categoryFilter, setCategoryFilter] = useState('all');
  const [categories, setCategories] = useState<Array<{ id: number; name: string }>>([]);
  const [isMediaDialogOpen, setIsMediaDialogOpen] = useState(false);
  const [mediaItems, setMediaItems] = useState<Array<{ id: number; url: string; title: string }>>([]);
  const [mediaPage, setMediaPage] = useState(1);
  const [mediaTotal, setMediaTotal] = useState(0);
  const [stockFilter, setStockFilter] = useState('all');
  const [isAddDialogOpen, setIsAddDialogOpen] = useState(false);
  const [isEditDialogOpen, setIsEditDialogOpen] = useState(false);
  const [selectedProduct, setSelectedProduct] = useState<any>(null);
  const [formData, setFormData] = useState({
    name: '',
    sku: '',
    regular_price: '',
    sale_price: '',
    stock_quantity: '',
    stock_status: 'instock',
    status: 'publish',
    type: 'simple',
    category: '',
    description: '',
    short_description: '',
    manage_stock: true,
    images: [] as Array<{ id: number; url: string; is_featured?: boolean }>,
  });

  const filteredProducts = products.filter((product: any) => {
    const name = (product.name || '').toString().toLowerCase();
    const sku = (product.sku || '').toString().toLowerCase();
    const matchesSearch = name.includes(search.toLowerCase()) || sku.includes(search.toLowerCase());
    const categoryName = product.category || product.categories?.[0]?.name;
    const matchesCategory = categoryFilter === 'all' || categoryName === categoryFilter;
    const stock = product.stock_status || product.stockStatus;
    const matchesStock = stockFilter === 'all' || stock === stockFilter;
    return matchesSearch && matchesCategory && matchesStock;
  });

  const fetchProducts = async () => {
    setLoading(true);
    const res = await wordpressApi.getProducts({ page, perPage, search, status: stockFilter !== 'all' ? stockFilter : undefined });
    if (res.success && res.data) {
      setProducts(res.data.data);
      setTotal(res.data.total);
    } else {
      toast.error(res.error || 'Failed to load products');
    }
    setLoading(false);
  };

  useEffect(() => {
    fetchProducts();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [page, perPage, search, stockFilter]);

  useEffect(() => {
    const loadCategories = async () => {
      const res = await wordpressApi.getProductCategories();
      if (res.success && res.data) {
        setCategories(res.data);
      }
    };
    loadCategories();
  }, []);

  const handleAddProduct = async () => {
    // Build payload for simple product
    const payload: any = {
      name: formData.name,
      sku: formData.sku || undefined,
      type: 'simple',
      status: formData.status,
      regular_price: formData.regular_price,
      sale_price: formData.sale_price || undefined,
      manage_stock: !!formData.manage_stock,
      stock_quantity: formData.manage_stock ? Number(formData.stock_quantity || 0) : undefined,
      stock_status: formData.stock_status,
      short_description: formData.short_description,
      description: formData.description,
      images: formData.images?.map((img) => ({ id: img.id, url: img.url, is_featured: !!img.is_featured })),
    };

    const res = await wordpressApi.createProduct(payload);
    if (res.success && res.data) {
      const newId = (res.data as any).id;
      // Upload images if any files selected
      const files = formData.images.filter((img: any) => img.file) as any[];
      if (files.length) {
        for (let i = 0; i < files.length; i++) {
          const img = files[i];
          await wordpressApi.uploadProductImage(newId, img.file, !!img.is_featured || i === 0);
        }
      }
      toast.success('Product added successfully');
      setIsAddDialogOpen(false);
      fetchProducts();
    } else {
      toast.error(res.error || 'Failed to add product');
    }
    setFormData({
      name: '',
      sku: '',
      regular_price: '',
      sale_price: '',
      stock_quantity: '',
      stock_status: 'instock',
      status: 'publish',
      type: 'simple',
      category: '',
      description: '',
      short_description: '',
      manage_stock: true,
      images: [],
    });
  };

  const handleEditProduct = (product: any) => {
    setSelectedProduct(product);
    setFormData({
      name: product.name,
      sku: product.sku,
      regular_price: product.regular_price.toString(),
      sale_price: product.sale_price?.toString() || '',
      stock_quantity: product.stock_quantity?.toString() || '',
      stock_status: product.stock_status,
      status: product.status,
      type: product.type,
      category: product.category,
      description: '',
      short_description: '',
      manage_stock: product.stock_quantity !== null,
      images: product.image ? [{ id: 1, url: product.image, is_featured: true }] : [],
    });
    setIsEditDialogOpen(true);
  };

  const handleUpdateProduct = async () => {
    if (!selectedProduct) return;

    const payload: any = {
      name: formData.name,
      sku: formData.sku || undefined,
      status: formData.status,
      regular_price: formData.regular_price,
      sale_price: formData.sale_price || undefined,
      manage_stock: !!formData.manage_stock,
      stock_quantity: formData.manage_stock ? Number(formData.stock_quantity || 0) : undefined,
      stock_status: formData.stock_status,
      short_description: formData.short_description,
      description: formData.description,
      images: formData.images?.map((img) => ({ id: img.id, url: img.url, is_featured: !!img.is_featured })),
    };

    const res = await wordpressApi.updateProduct(selectedProduct.id, payload);
    if (res.success) {
      // Upload any new image files selected in the form
      const files = formData.images.filter((img: any) => img.file) as any[];
      if (files.length) {
        for (let i = 0; i < files.length; i++) {
          const img = files[i];
          await wordpressApi.uploadProductImage(selectedProduct.id, img.file, !!img.is_featured && i === 0);
        }
      }
      toast.success('Product updated successfully');
      setIsEditDialogOpen(false);
      fetchProducts();
    } else {
      toast.error(res.error || 'Failed to update product');
    }
  };

  const handleDeleteProduct = async (id: number) => {
    const res = await wordpressApi.deleteProduct(id);
    if (res.success) {
      toast.success('Product deleted successfully');
      fetchProducts();
    } else {
      toast.error(res.error || 'Failed to delete product');
    }
  };

  const handleDuplicateProduct = (product: any) => {
    toast.success('Product duplicated successfully');
  };

  const columns: any[] = [
    {
      key: 'image',
      label: '',
      render: (product: any) => (
        <div className="w-10 h-10 rounded-md bg-muted flex items-center justify-center overflow-hidden">
          <img src={product.image || product.images?.[0]?.src} alt={product.name} className="w-full h-full object-cover" />
        </div>
      ),
    },
    {
      key: 'name',
      header: 'Product',
      render: (product: any) => (
        <div>
          <div className="font-medium">{product.name}</div>
          <div className="text-sm text-muted-foreground">SKU: {product.sku}</div>
        </div>
      ),
    },
    {
      key: 'status',
      header: 'Status',
      render: (product: any) => (
        <StatusBadge 
          status={productStatusLabels[product.status].label} 
          variant={productStatusLabels[product.status].variant} 
        />
      ),
    },
    {
      key: 'stock',
      header: 'Stock',
      render: (product: any) => (
        <div>
          <StatusBadge 
            status={stockStatusLabels[product.stock_status].label} 
            variant={stockStatusLabels[product.stock_status].variant} 
          />
          {product.stock_quantity !== null && (
            <div className="text-sm text-muted-foreground mt-1">
              Qty: {product.stock_quantity}
            </div>
          )}
        </div>
      ),
    },
    {
      key: 'price',
      header: 'Price',
      render: (product: any) => (
        <div>
          <div className="font-medium">${product.price.toFixed(2)}</div>
          {product.sale_price && (
            <div className="text-sm text-muted-foreground line-through">
              ${product.regular_price.toFixed(2)}
            </div>
          )}
        </div>
      ),
    },
    {
      key: 'category',
      header: 'Category',
      render: (product: any) => (
        <Badge variant="outline">{product.category}</Badge>
      ),
    },
    {
      key: 'sales',
      header: 'Sales',
      render: (product: any) => product.total_sales,
    },
    {
      key: 'actions',
      label: '',
      render: (product: any) => (
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="icon">
              <MoreHorizontal className="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem onClick={() => handleEditProduct(product)}>
              <Edit className="h-4 w-4 mr-2" />
              Edit
            </DropdownMenuItem>
            <DropdownMenuItem onClick={() => handleDuplicateProduct(product)}>
              <Copy className="h-4 w-4 mr-2" />
              Duplicate
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem 
              onClick={() => handleDeleteProduct(product.id)}
              className="text-destructive"
            >
              <Trash2 className="h-4 w-4 mr-2" />
              Delete
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      ),
    },
  ];

  useEffect(() => {
    const loadMedia = async () => {
      if (!isMediaDialogOpen) return;
      const res = await wordpressApi.getProductMedia({ page: mediaPage, perPage: 24 });
      if (res.success && res.data) {
        setMediaItems(res.data.data);
        setMediaTotal(res.data.total);
      }
    };
    loadMedia();
  }, [isMediaDialogOpen, mediaPage]);

  const ProductForm = ({ isEdit = false }: { isEdit?: boolean }) => (
    <Tabs defaultValue="general" className="w-full">
      <TabsList className="grid w-full grid-cols-3">
        <TabsTrigger value="general">General</TabsTrigger>
        <TabsTrigger value="images">Images</TabsTrigger>
        <TabsTrigger value="inventory">Inventory</TabsTrigger>
      </TabsList>

      <TabsContent value="general" className="space-y-4 py-4">
        <div className="space-y-2">
          <Label htmlFor="name">Product Name *</Label>
          <Input 
            id="name" 
            placeholder="Enter product name" 
            value={formData.name}
            onChange={(e) => setFormData({ ...formData, name: e.target.value })}
          />
        </div>

      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
          <Label htmlFor="sku">SKU</Label>
          <Input 
            id="sku" 
            placeholder="PROD-001" 
            value={formData.sku}
            onChange={(e) => setFormData({ ...formData, sku: e.target.value })}
          />
        </div>
        <div className="space-y-2">
          <Label htmlFor="category">Category</Label>
          <Select value={formData.category} onValueChange={(value) => setFormData({ ...formData, category: value })}>
            <SelectTrigger>
              <SelectValue placeholder="Select category" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="Themes">Themes</SelectItem>
              <SelectItem value="Plugins">Plugins</SelectItem>
              <SelectItem value="Tools">Tools</SelectItem>
              <SelectItem value="Extensions">Extensions</SelectItem>
              <SelectItem value="Marketing">Marketing</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <div className="space-y-2">
        <Label htmlFor="short_description">Short Description</Label>
        <Textarea 
          id="short_description" 
          placeholder="Brief product description"
          rows={2}
          value={formData.short_description}
          onChange={(e) => setFormData({ ...formData, short_description: e.target.value })}
        />
      </div>

      <div className="space-y-2">
        <Label htmlFor="description">Description</Label>
        <Textarea 
          id="description" 
          placeholder="Detailed product description"
          rows={4}
          value={formData.description}
          onChange={(e) => setFormData({ ...formData, description: e.target.value })}
        />
      </div>

      <div className="space-y-2">
        <Label htmlFor="status">Product Status</Label>
        <Select value={formData.status} onValueChange={(value) => setFormData({ ...formData, status: value })}>
          <SelectTrigger>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="publish">Published</SelectItem>
            <SelectItem value="draft">Draft</SelectItem>
            <SelectItem value="pending">Pending Review</SelectItem>
            <SelectItem value="private">Private</SelectItem>
          </SelectContent>
        </Select>
      </div>
      </TabsContent>

      <TabsContent value="images" className="py-4">
       {/* load media when dialog opens */}
       {isMediaDialogOpen && (
         <>{/* side-effect to load media */}</>
       )}
        <div className="space-y-2">
          <Label>Product Images</Label>
          <p className="text-sm text-muted-foreground mb-4">
            Upload product images. The first image or marked image will be the featured image.
          </p>
          <ImageUpload 
            images={formData.images}
            onImagesChange={(images) => setFormData({ ...formData, images })}
            maxImages={10}
          />
        </div>
      </TabsContent>

      <TabsContent value="inventory" className="space-y-4 py-4">
        <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
          <Label htmlFor="regular_price">Regular Price *</Label>
          <Input 
            id="regular_price" 
            type="number" 
            step="0.01"
            placeholder="0.00" 
            value={formData.regular_price}
            onChange={(e) => setFormData({ ...formData, regular_price: e.target.value })}
          />
        </div>
        <div className="space-y-2">
          <Label htmlFor="sale_price">Sale Price</Label>
          <Input 
            id="sale_price" 
            type="number" 
            step="0.01"
            placeholder="0.00" 
            value={formData.sale_price}
            onChange={(e) => setFormData({ ...formData, sale_price: e.target.value })}
          />
        </div>
      </div>

      <div className="space-y-2">
        <div className="flex items-center justify-between">
          <Label htmlFor="manage_stock">Manage Stock</Label>
          <Switch 
            id="manage_stock"
            checked={formData.manage_stock}
            onCheckedChange={(checked) => setFormData({ ...formData, manage_stock: checked })}
          />
        </div>
      </div>

      {formData.manage_stock && (
        <div className="grid grid-cols-2 gap-4">
          <div className="space-y-2">
            <Label htmlFor="stock_quantity">Stock Quantity</Label>
            <Input 
              id="stock_quantity" 
              type="number" 
              placeholder="0" 
              value={formData.stock_quantity}
              onChange={(e) => setFormData({ ...formData, stock_quantity: e.target.value })}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="stock_status">Stock Status</Label>
            <Select value={formData.stock_status} onValueChange={(value) => setFormData({ ...formData, stock_status: value })}>
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="instock">In Stock</SelectItem>
                <SelectItem value="outofstock">Out of Stock</SelectItem>
                <SelectItem value="onbackorder">On Backorder</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
      )}

      </TabsContent>
    </Tabs>
  );

  return (
    <div className="space-y-6 animate-fade-in">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl md:text-3xl font-bold">Products</h1>
          <p className="text-muted-foreground">Manage your product catalog</p>
        </div>
        <Dialog open={isAddDialogOpen} onOpenChange={setIsAddDialogOpen}>
          <DialogTrigger asChild>
            <Button className="gradient-primary text-primary-foreground">
              <Plus className="h-4 w-4 mr-2" />
              Add Product
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>Add New Product</DialogTitle>
              <DialogDescription>
                Create a new product in your store
              </DialogDescription>
            </DialogHeader>
            <ProductForm />
            <DialogFooter>
              <Button variant="outline" onClick={() => setIsAddDialogOpen(false)}>
                Cancel
              </Button>
              <Button onClick={handleAddProduct}>
                Add Product
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card>
          <CardHeader className="pb-2">
            <CardDescription>Total Products</CardDescription>
            <CardTitle className="text-2xl">{total}</CardTitle>
          </CardHeader>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardDescription>Published</CardDescription>
            <CardTitle className="text-2xl">
              {products.filter((p: any) => p.status === 'publish').length}
            </CardTitle>
          </CardHeader>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardDescription>Out of Stock</CardDescription>
            <CardTitle className="text-2xl text-destructive">
              {products.filter((p: any) => p.stock_status === 'outofstock').length}
            </CardTitle>
          </CardHeader>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardDescription>Total Sales</CardDescription>
            <CardTitle className="text-2xl">
              {products.reduce((sum: number, p: any) => sum + (Number(p.totalSales || p.total_sales) || 0), 0)}
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
                placeholder="Search products..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="pl-10"
              />
            </div>
            <Select value={categoryFilter} onValueChange={setCategoryFilter}>
              <SelectTrigger className="w-full sm:w-[180px]">
                <SelectValue placeholder="Category" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Categories</SelectItem>
                {categories.map((c) => (
                  <SelectItem key={c.id} value={c.name}>{c.name}</SelectItem>
                ))}
              </SelectContent>
            </Select>
            <Select value={stockFilter} onValueChange={setStockFilter}>
              <SelectTrigger className="w-full sm:w-[180px]">
                <SelectValue placeholder="Stock Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Stock</SelectItem>
                <SelectItem value="instock">In Stock</SelectItem>
                <SelectItem value="outofstock">Out of Stock</SelectItem>
                <SelectItem value="onbackorder">On Backorder</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="py-10 text-center text-muted-foreground">Loading products...</div>
          ) : (
            <>
              <DataTable columns={columns} data={filteredProducts} />
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

      {/* Edit Dialog */}
      <Dialog open={isEditDialogOpen} onOpenChange={setIsEditDialogOpen}>
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Edit Product</DialogTitle>
            <DialogDescription>
              Update product information
            </DialogDescription>
          </DialogHeader>
          <ProductForm isEdit />
          <DialogFooter>
            <Button variant="outline" onClick={() => setIsEditDialogOpen(false)}>
              Cancel
            </Button>
            <Button onClick={handleUpdateProduct}>
              Update Product
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
