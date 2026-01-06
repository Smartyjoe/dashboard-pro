<?php
/**
 * Products API
 * Handles WooCommerce product management endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Dashboard_Pro_Products_API extends WP_Dashboard_Pro_API_Base {
    
    /**
     * Register routes
     */
    public function register_routes() {
        // Get products list
        $this->register_route('/products', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_products'),
            'permission_callback' => $this->check_permission('edit_products'),
        ));
        
        // Get single product
        $this->register_route('/products/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_product'),
            'permission_callback' => $this->check_permission('edit_products'),
        ));
        
        // Create product
        $this->register_route('/products', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_product'),
            'permission_callback' => $this->check_permission('edit_products'),
        ));
        
        // Update product
        $this->register_route('/products/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_product'),
            'permission_callback' => $this->check_permission('edit_products'),
        ));
        
        // Delete product
        $this->register_route('/products/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_product'),
            'permission_callback' => $this->check_permission('delete_products'),
        ));
        
        // Update product stock
        $this->register_route('/products/(?P<id>\d+)/stock', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_stock'),
            'permission_callback' => $this->check_permission('edit_products'),
        ));
        
        // Upload product image
        $this->register_route('/products/(?P<id>\d+)/image', array(
            'methods' => 'POST',
            'callback' => array($this, 'upload_product_image'),
            'permission_callback' => $this->check_permission('edit_products'),
        ));
        
        // Delete product image
        $this->register_route('/products/(?P<id>\d+)/image/(?P<image_id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_product_image'),
            'permission_callback' => $this->check_permission('edit_products'),
        ));
        
        // Get product categories
        $this->register_route('/products/categories', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_categories'),
            'permission_callback' => $this->check_permission('edit_products'),
        ));
        
        // Get product tags
        $this->register_route('/products/tags', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_tags'),
            'permission_callback' => $this->check_permission('edit_products'),
        ));

        // Get media library (images only)
        $this->register_route('/products/media', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_media'),
            'permission_callback' => $this->check_permission('edit_products'),
        ));
        
        // Bulk update products
        $this->register_route('/products/bulk', array(
            'methods' => 'POST',
            'callback' => array($this, 'bulk_update'),
            'permission_callback' => $this->check_permission('edit_products'),
        ));
    }
    
    /**
     * Get products list
     */
    public function get_products($request) {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return $this->error_response('WooCommerce is not installed or activated', 400);
        }
        
        $pagination = $this->get_pagination_params($request);
        $status = $request->get_param('status');
        $category = $request->get_param('category');
        $search = $request->get_param('search');
        $type = $request->get_param('type');
        $stock_status = $request->get_param('stock_status');
        
        $args = array(
            'limit' => $pagination['per_page'],
            'offset' => ($pagination['page'] - 1) * $pagination['per_page'],
            'orderby' => $pagination['orderby'],
            'order' => $pagination['order'],
            'return' => 'ids',
        );
        
        if ($status) {
            $args['status'] = $status;
        }
        
        if ($category) {
            $args['category'] = array($category);
        }
        
        if ($search) {
            $args['s'] = $search;
        }
        
        if ($type) {
            $args['type'] = $type;
        }
        
        if ($stock_status) {
            $args['stock_status'] = $stock_status;
        }
        
        // Get products
        $product_ids = wc_get_products($args);
        
        // Get total count
        $total_args = $args;
        unset($total_args['limit']);
        unset($total_args['offset']);
        $total = count(wc_get_products($total_args));
        
        // Format products
        $products = array();
        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                $products[] = $this->format_product($product);
            }
        }
        
        return $this->success_response(
            $this->format_pagination_response(
                $products,
                $total,
                $pagination['page'],
                $pagination['per_page']
            )
        );
    }
    
    /**
     * Get single product
     */
    public function get_product($request) {
        if (!class_exists('WooCommerce')) {
            return $this->error_response('WooCommerce is not installed or activated', 400);
        }
        
        $product_id = $request['id'];
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return $this->error_response('Product not found', 404);
        }
        
        return $this->success_response($this->format_product($product, true));
    }
    
    /**
     * Create product
     */
    public function create_product($request) {
        if (!class_exists('WooCommerce')) {
            return $this->error_response('WooCommerce is not installed or activated', 400);
        }
        
        $name = $this->sanitize_string($request->get_param('name'));
        $type = $this->sanitize_string($request->get_param('type')) ?: 'simple';
        $description = $request->get_param('description');
        $short_description = $request->get_param('short_description');
        $sku = $this->sanitize_string($request->get_param('sku'));
        $price = $request->get_param('regular_price');
        $sale_price = $request->get_param('sale_price');
        $stock_quantity = $request->get_param('stock_quantity');
        $stock_status = $this->sanitize_string($request->get_param('stock_status')) ?: 'instock';
        $manage_stock = $request->get_param('manage_stock');
        $categories = $request->get_param('categories');
        $tags = $request->get_param('tags');
        $images = $request->get_param('images');
        $status = $this->sanitize_string($request->get_param('status')) ?: 'publish';
        
        // Validate required fields
        $validation = $this->validate_required_params($request, array('name'));
        if (is_wp_error($validation)) {
            return $this->error_response($validation->get_error_message(), 400);
        }
        
        try {
            // Create product based on type
            $product = new WC_Product_Simple();
            
            if ($type === 'variable') {
                $product = new WC_Product_Variable();
            } elseif ($type === 'grouped') {
                $product = new WC_Product_Grouped();
            } elseif ($type === 'external') {
                $product = new WC_Product_External();
            }
            
            // Set basic data
            $product->set_name($name);
            $product->set_status($status);
            
            if ($description) {
                $product->set_description(wp_kses_post($description));
            }
            
            if ($short_description) {
                $product->set_short_description(wp_kses_post($short_description));
            }
            
            if ($sku) {
                $product->set_sku($sku);
            }
            
            // Set pricing
            if ($price !== null && $type !== 'grouped') {
                $product->set_regular_price($price);
            }
            
            if ($sale_price !== null && $type !== 'grouped') {
                $product->set_sale_price($sale_price);
            }
            
            // Set stock
            if ($manage_stock !== null) {
                $product->set_manage_stock($manage_stock);
            }
            
            if ($manage_stock && $stock_quantity !== null) {
                $product->set_stock_quantity($stock_quantity);
            }
            
            $product->set_stock_status($stock_status);
            
            // Save product
            $product_id = $product->save();
            
            // Set categories
            if ($categories && is_array($categories)) {
                wp_set_object_terms($product_id, array_map('intval', $categories), 'product_cat');
            }
            
            // Set tags
            if ($tags && is_array($tags)) {
                wp_set_object_terms($product_id, array_map('intval', $tags), 'product_tag');
            }
            
            // Set images
            if ($images && is_array($images)) {
                $this->set_product_images($product_id, $images);
            }
            
            $product = wc_get_product($product_id);
            
            return $this->success_response(
                $this->format_product($product, true),
                'Product created successfully',
                201
            );
            
        } catch (Exception $e) {
            return $this->error_response('Failed to create product: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Update product
     */
    public function update_product($request) {
        if (!class_exists('WooCommerce')) {
            return $this->error_response('WooCommerce is not installed or activated', 400);
        }
        
        $product_id = $request['id'];
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return $this->error_response('Product not found', 404);
        }
        
        try {
            // Update name
            if ($request->get_param('name') !== null) {
                $product->set_name($this->sanitize_string($request->get_param('name')));
            }
            
            // Update status
            if ($request->get_param('status') !== null) {
                $product->set_status($this->sanitize_string($request->get_param('status')));
            }
            
            // Update description
            if ($request->get_param('description') !== null) {
                $product->set_description(wp_kses_post($request->get_param('description')));
            }
            
            // Update short description
            if ($request->get_param('short_description') !== null) {
                $product->set_short_description(wp_kses_post($request->get_param('short_description')));
            }
            
            // Update SKU
            if ($request->get_param('sku') !== null) {
                $product->set_sku($this->sanitize_string($request->get_param('sku')));
            }
            
            // Update pricing
            if ($request->get_param('regular_price') !== null) {
                $product->set_regular_price($request->get_param('regular_price'));
            }
            
            if ($request->get_param('sale_price') !== null) {
                $product->set_sale_price($request->get_param('sale_price'));
            }
            
            // Update stock
            if ($request->get_param('manage_stock') !== null) {
                $product->set_manage_stock($request->get_param('manage_stock'));
            }
            
            if ($request->get_param('stock_quantity') !== null) {
                $product->set_stock_quantity($request->get_param('stock_quantity'));
            }
            
            if ($request->get_param('stock_status') !== null) {
                $product->set_stock_status($this->sanitize_string($request->get_param('stock_status')));
            }
            
            // Save product
            $product->save();
            
            // Update categories
            if ($request->get_param('categories') !== null) {
                $categories = $request->get_param('categories');
                if (is_array($categories)) {
                    wp_set_object_terms($product_id, array_map('intval', $categories), 'product_cat');
                }
            }
            
            // Update tags
            if ($request->get_param('tags') !== null) {
                $tags = $request->get_param('tags');
                if (is_array($tags)) {
                    wp_set_object_terms($product_id, array_map('intval', $tags), 'product_tag');
                }
            }
            
            // Update images
            if ($request->get_param('images') !== null) {
                $images = $request->get_param('images');
                if (is_array($images)) {
                    $this->set_product_images($product_id, $images);
                }
            }
            
            $product = wc_get_product($product_id);
            
            return $this->success_response(
                $this->format_product($product, true),
                'Product updated successfully'
            );
            
        } catch (Exception $e) {
            return $this->error_response('Failed to update product: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Delete product
     */
    public function delete_product($request) {
        if (!class_exists('WooCommerce')) {
            return $this->error_response('WooCommerce is not installed or activated', 400);
        }
        
        $product_id = $request['id'];
        $force = $request->get_param('force') === true;
        
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return $this->error_response('Product not found', 404);
        }
        
        try {
            if ($force) {
                // Permanently delete
                $product->delete(true);
                $message = 'Product permanently deleted';
            } else {
                // Move to trash
                $product->delete(false);
                $message = 'Product moved to trash';
            }
            
            return $this->success_response(
                array('deleted' => true),
                $message
            );
            
        } catch (Exception $e) {
            return $this->error_response('Failed to delete product: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Update product stock
     */
    public function update_stock($request) {
        if (!class_exists('WooCommerce')) {
            return $this->error_response('WooCommerce is not installed or activated', 400);
        }
        
        $product_id = $request['id'];
        $stock_quantity = $request->get_param('stock_quantity');
        $stock_status = $request->get_param('stock_status');
        
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return $this->error_response('Product not found', 404);
        }
        
        try {
            if ($stock_quantity !== null) {
                $product->set_stock_quantity($stock_quantity);
            }
            
            if ($stock_status !== null) {
                $product->set_stock_status($this->sanitize_string($stock_status));
            }
            
            $product->save();
            
            return $this->success_response(
                array(
                    'stock_quantity' => $product->get_stock_quantity(),
                    'stock_status' => $product->get_stock_status(),
                ),
                'Stock updated successfully'
            );
            
        } catch (Exception $e) {
            return $this->error_response('Failed to update stock: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Upload product image
     */
    public function upload_product_image($request) {
        if (!class_exists('WooCommerce')) {
            return $this->error_response('WooCommerce is not installed or activated', 400);
        }
        
        $product_id = $request['id'];
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return $this->error_response('Product not found', 404);
        }
        
        $files = $request->get_file_params();
        
        if (empty($files['image'])) {
            return $this->error_response('No image file provided', 400);
        }
        
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        try {
            $attachment_id = media_handle_upload('image', $product_id);
            
            if (is_wp_error($attachment_id)) {
                return $this->error_response('Failed to upload image: ' . $attachment_id->get_error_message(), 500);
            }
            
            $is_featured = $request->get_param('is_featured') === true;
            
            if ($is_featured || !$product->get_image_id()) {
                $product->set_image_id($attachment_id);
            } else {
                $gallery_ids = $product->get_gallery_image_ids();
                $gallery_ids[] = $attachment_id;
                $product->set_gallery_image_ids($gallery_ids);
            }
            
            $product->save();
            
            return $this->success_response(
                array(
                    'id' => $attachment_id,
                    'url' => wp_get_attachment_url($attachment_id),
                    'is_featured' => $is_featured || !count($product->get_gallery_image_ids()),
                ),
                'Image uploaded successfully'
            );
            
        } catch (Exception $e) {
            return $this->error_response('Failed to upload image: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Delete product image
     */
    public function delete_product_image($request) {
        if (!class_exists('WooCommerce')) {
            return $this->error_response('WooCommerce is not installed or activated', 400);
        }
        
        $product_id = $request['id'];
        $image_id = $request['image_id'];
        
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return $this->error_response('Product not found', 404);
        }
        
        try {
            // Check if it's the featured image
            if ($product->get_image_id() == $image_id) {
                $product->set_image_id('');
            } else {
                // Remove from gallery
                $gallery_ids = $product->get_gallery_image_ids();
                $gallery_ids = array_diff($gallery_ids, array($image_id));
                $product->set_gallery_image_ids($gallery_ids);
            }
            
            $product->save();
            
            // Delete the attachment
            wp_delete_attachment($image_id, true);
            
            return $this->success_response(
                array('deleted' => true),
                'Image deleted successfully'
            );
            
        } catch (Exception $e) {
            return $this->error_response('Failed to delete image: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get product categories
     */
    public function get_categories($request) {
        $args = array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        );
        
        $parent = $request->get_param('parent');
        if ($parent !== null) {
            $args['parent'] = $parent;
        }
        
        $categories = get_terms($args);
        
        if (is_wp_error($categories)) {
            return $this->error_response('Failed to get categories', 500);
        }
        
        $formatted_categories = array_map(function($category) {
            return array(
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'parent' => $category->parent,
                'count' => $category->count,
                'description' => $category->description,
            );
        }, $categories);
        
        return $this->success_response($formatted_categories);
    }
    
    /**
     * Get product media (images only)
     */
    public function get_media($request) {
        $pagination = $this->get_pagination_params($request);
        $search = $request->get_param('search');

        $args = array(
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'post_status'    => 'inherit',
            'posts_per_page' => $pagination['per_page'],
            'offset'         => ($pagination['page'] - 1) * $pagination['per_page'],
        );

        if ($search) {
            $args['s'] = sanitize_text_field($search);
        }

        $query = new WP_Query($args);

        $items = array();
        foreach ($query->posts as $attachment) {
            $items[] = array(
                'id'    => $attachment->ID,
                'url'   => wp_get_attachment_url($attachment->ID),
                'title' => get_the_title($attachment->ID),
            );
        }

        return $this->success_response(
            $this->format_pagination_response(
                $items,
                intval($query->found_posts),
                $pagination['page'],
                $pagination['per_page']
            )
        );
    }

    /**
     * Get product tags
     */
    public function get_tags($request) {
        $args = array(
            'taxonomy' => 'product_tag',
            'hide_empty' => false,
        );
        
        $search = $request->get_param('search');
        if ($search) {
            $args['search'] = $search;
        }
        
        $tags = get_terms($args);
        
        if (is_wp_error($tags)) {
            return $this->error_response('Failed to get tags', 500);
        }
        
        $formatted_tags = array_map(function($tag) {
            return array(
                'id' => $tag->term_id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'count' => $tag->count,
            );
        }, $tags);
        
        return $this->success_response($formatted_tags);
    }
    
    /**
     * Bulk update products
     */
    public function bulk_update($request) {
        if (!class_exists('WooCommerce')) {
            return $this->error_response('WooCommerce is not installed or activated', 400);
        }
        
        $product_ids = $request->get_param('product_ids');
        $action = $this->sanitize_string($request->get_param('action'));
        $data = $request->get_param('data');
        
        if (!$product_ids || !is_array($product_ids)) {
            return $this->error_response('Product IDs are required', 400);
        }
        
        if (!$action) {
            return $this->error_response('Action is required', 400);
        }
        
        $updated = 0;
        $failed = 0;
        
        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);
            
            if (!$product) {
                $failed++;
                continue;
            }
            
            try {
                switch ($action) {
                    case 'delete':
                        $product->delete(false);
                        break;
                        
                    case 'delete_permanently':
                        $product->delete(true);
                        break;
                        
                    case 'update_status':
                        if (isset($data['status'])) {
                            $product->set_status($data['status']);
                            $product->save();
                        }
                        break;
                        
                    case 'update_stock_status':
                        if (isset($data['stock_status'])) {
                            $product->set_stock_status($data['stock_status']);
                            $product->save();
                        }
                        break;
                        
                    case 'update_price':
                        if (isset($data['regular_price'])) {
                            $product->set_regular_price($data['regular_price']);
                        }
                        if (isset($data['sale_price'])) {
                            $product->set_sale_price($data['sale_price']);
                        }
                        $product->save();
                        break;
                        
                    default:
                        $failed++;
                        continue 2;
                }
                
                $updated++;
                
            } catch (Exception $e) {
                $failed++;
            }
        }
        
        return $this->success_response(
            array(
                'updated' => $updated,
                'failed' => $failed,
                'total' => count($product_ids),
            ),
            sprintf('%d products updated, %d failed', $updated, $failed)
        );
    }
    
    /**
     * Format product for response
     */
    private function format_product($product, $detailed = false) {
        $data = array(
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'slug' => $product->get_slug(),
            'type' => $product->get_type(),
            'status' => $product->get_status(),
            'featured' => $product->is_featured(),
            'sku' => $product->get_sku(),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'on_sale' => $product->is_on_sale(),
            'stock_status' => $product->get_stock_status(),
            'stock_quantity' => $product->get_stock_quantity(),
            'manage_stock' => $product->get_manage_stock(),
            'permalink' => get_permalink($product->get_id()),
            'date_created' => $product->get_date_created() ? $product->get_date_created()->date('Y-m-d H:i:s') : null,
            'date_modified' => $product->get_date_modified() ? $product->get_date_modified()->date('Y-m-d H:i:s') : null,
        );
        
        // Add image
        $image_id = $product->get_image_id();
        if ($image_id) {
            $data['image'] = array(
                'id' => $image_id,
                'url' => wp_get_attachment_url($image_id),
                'thumbnail' => wp_get_attachment_image_url($image_id, 'thumbnail'),
            );
        } else {
            $data['image'] = null;
        }
        
        if ($detailed) {
            // Add detailed information
            $data['description'] = $product->get_description();
            $data['short_description'] = $product->get_short_description();
            $data['weight'] = $product->get_weight();
            $data['length'] = $product->get_length();
            $data['width'] = $product->get_width();
            $data['height'] = $product->get_height();
            $data['virtual'] = $product->is_virtual();
            $data['downloadable'] = $product->is_downloadable();
            $data['sold_individually'] = $product->is_sold_individually();
            $data['purchase_note'] = $product->get_purchase_note();
            $data['shipping_class_id'] = $product->get_shipping_class_id();
            $data['reviews_allowed'] = $product->get_reviews_allowed();
            $data['average_rating'] = $product->get_average_rating();
            $data['rating_count'] = $product->get_rating_count();
            $data['total_sales'] = $product->get_total_sales();
            
            // Add categories
            $category_ids = $product->get_category_ids();
            $data['categories'] = array();
            foreach ($category_ids as $category_id) {
                $category = get_term($category_id, 'product_cat');
                if ($category && !is_wp_error($category)) {
                    $data['categories'][] = array(
                        'id' => $category->term_id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                    );
                }
            }
            
            // Add tags
            $tag_ids = $product->get_tag_ids();
            $data['tags'] = array();
            foreach ($tag_ids as $tag_id) {
                $tag = get_term($tag_id, 'product_tag');
                if ($tag && !is_wp_error($tag)) {
                    $data['tags'][] = array(
                        'id' => $tag->term_id,
                        'name' => $tag->name,
                        'slug' => $tag->slug,
                    );
                }
            }
            
            // Add gallery images
            $gallery_ids = $product->get_gallery_image_ids();
            $data['gallery_images'] = array();
            foreach ($gallery_ids as $gallery_id) {
                $data['gallery_images'][] = array(
                    'id' => $gallery_id,
                    'url' => wp_get_attachment_url($gallery_id),
                    'thumbnail' => wp_get_attachment_image_url($gallery_id, 'thumbnail'),
                );
            }
            
            // Add attributes for variable products
            if ($product->is_type('variable')) {
                $data['attributes'] = $product->get_attributes();
                $data['variations'] = $product->get_children();
            }
        }
        
        return $data;
    }
    
    /**
     * Set product images
     */
    private function set_product_images($product_id, $images) {
        if (empty($images)) {
            return;
        }
        
        $featured_set = false;
        $gallery_ids = array();
        
        foreach ($images as $image) {
            if (isset($image['id'])) {
                $image_id = intval($image['id']);
                
                if (isset($image['is_featured']) && $image['is_featured'] && !$featured_set) {
                    update_post_meta($product_id, '_thumbnail_id', $image_id);
                    $featured_set = true;
                } else {
                    $gallery_ids[] = $image_id;
                }
            }
        }
        
        if (!empty($gallery_ids)) {
            update_post_meta($product_id, '_product_image_gallery', implode(',', $gallery_ids));
        }
    }
}
