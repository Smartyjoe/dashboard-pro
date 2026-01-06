import React, { useState, useRef } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { X, Upload, Image as ImageIcon } from 'lucide-react';
import { toast } from 'sonner';

interface ImageItem { id: number; url: string; is_featured?: boolean; file?: File }

interface ImageUploadProps {
  images?: ImageItem[];
  onImagesChange?: (images: ImageItem[]) => void;
  maxImages?: number;
  accept?: string;
}

export function ImageUpload({ 
  images = [], 
  onImagesChange,
  maxImages = 10,
  accept = 'image/*'
}: ImageUploadProps) {
  const [uploadedImages, setUploadedImages] = useState<ImageItem[]>(images);
  const [isDragging, setIsDragging] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const handleFileSelect = async (files: FileList | null) => {
    if (!files || files.length === 0) return;

    const remainingSlots = maxImages - uploadedImages.length;
    if (files.length > remainingSlots) {
      toast.error(`You can only upload ${remainingSlots} more image(s)`);
      return;
    }

    // Validate file types and sizes
    const validFiles: File[] = [];
    for (let i = 0; i < files.length; i++) {
      const file = files[i];
      
      // Check file type
      if (!file.type.startsWith('image/')) {
        toast.error(`${file.name} is not an image file`);
        continue;
      }

      // Check file size (max 5MB)
      if (file.size > 5 * 1024 * 1024) {
        toast.error(`${file.name} is too large. Max size is 5MB`);
        continue;
      }

      validFiles.push(file);
    }

    if (validFiles.length === 0) return;

    // Convert files to data URLs for preview
    const newImages = await Promise.all(
      validFiles.map(async (file) => {
        return new Promise<ImageItem>((resolve) => {
          const reader = new FileReader();
          reader.onload = (e) => {
            resolve({
              id: Date.now() + Math.random(), // Temporary ID
              url: e.target?.result as string,
              file,
            });
          };
          reader.readAsDataURL(file);
        });
      })
    );

    const updatedImages = [...uploadedImages, ...newImages];
    setUploadedImages(updatedImages);
    onImagesChange?.(updatedImages);
    toast.success(`${validFiles.length} image(s) uploaded successfully`);
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(false);
    handleFileSelect(e.dataTransfer.files);
  };

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(true);
  };

  const handleDragLeave = () => {
    setIsDragging(false);
  };

  const handleRemoveImage = (id: number) => {
    const updatedImages = uploadedImages.filter(img => img.id !== id);
    setUploadedImages(updatedImages);
    onImagesChange?.(updatedImages);
    toast.success('Image removed');
  };

  const handleSetFeatured = (id: number) => {
    const updatedImages = uploadedImages.map(img => ({
      ...img,
      is_featured: img.id === id
    }));
    setUploadedImages(updatedImages);
    onImagesChange?.(updatedImages);
    toast.success('Featured image updated');
  };

  return (
    <div className="space-y-4">
      {/* Upload Area */}
      <div
        className={`border-2 border-dashed rounded-lg p-8 text-center transition-colors ${
          isDragging
            ? 'border-primary bg-primary/5'
            : 'border-muted-foreground/25 hover:border-muted-foreground/50'
        }`}
        onDrop={handleDrop}
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
      >
        <input
          ref={fileInputRef}
          type="file"
          accept={accept}
          multiple
          className="hidden"
          onChange={(e) => handleFileSelect(e.target.files)}
        />
        
        <div className="flex flex-col items-center gap-2">
          <div className="w-12 h-12 rounded-full bg-muted flex items-center justify-center">
            <Upload className="h-6 w-6 text-muted-foreground" />
          </div>
          <div>
            <p className="text-sm font-medium">
              Drop images here or{' '}
              <button
                type="button"
                onClick={() => fileInputRef.current?.click()}
                className="text-primary hover:underline"
              >
                browse
              </button>
            </p>
            <p className="text-xs text-muted-foreground mt-1">
              Supports: JPG, PNG, GIF, WebP (Max 5MB each, up to {maxImages} images)
            </p>
          </div>
        </div>
      </div>

      {/* Image Gallery */}
      {uploadedImages.length > 0 && (
        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
          {uploadedImages.map((image) => (
            <Card key={image.id} className="relative group overflow-hidden">
              <div className="aspect-square relative">
                <img
                  src={image.url}
                  alt="Product"
                  className="w-full h-full object-cover"
                />
                
                {/* Overlay */}
                <div className="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-2">
                  <Button
                    type="button"
                    size="sm"
                    variant="secondary"
                    onClick={() => handleSetFeatured(image.id)}
                    disabled={image.is_featured}
                  >
                    {image.is_featured ? 'Featured' : 'Set Featured'}
                  </Button>
                  <Button
                    type="button"
                    size="sm"
                    variant="destructive"
                    onClick={() => handleRemoveImage(image.id)}
                  >
                    <X className="h-4 w-4" />
                  </Button>
                </div>

                {/* Featured Badge */}
                {image.is_featured && (
                  <div className="absolute top-2 left-2 bg-primary text-primary-foreground text-xs px-2 py-1 rounded">
                    Featured
                  </div>
                )}
              </div>
            </Card>
          ))}
        </div>
      )}

      {/* Empty State */}
      {uploadedImages.length === 0 && (
        <div className="text-center py-8 text-muted-foreground">
          <ImageIcon className="h-12 w-12 mx-auto mb-2 opacity-50" />
          <p className="text-sm">No images uploaded yet</p>
        </div>
      )}
    </div>
  );
}
