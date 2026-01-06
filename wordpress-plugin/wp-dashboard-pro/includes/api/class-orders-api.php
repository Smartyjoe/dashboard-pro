<?php
/**
 * Orders API
 * Handles WooCommerce order management endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Dashboard_Pro_Orders_API extends WP_Dashboard_Pro_API_Base {
    
    /**
     * Register routes
     */
    public function register_routes() {
        // Get orders list
        $this->register_route('/orders', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_orders'),
            'permission_callback' => $this->check_permission('edit_shop_orders'),
        ));
        
        // Get single order
        $this->register_route('/orders/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_order'),
            'permission_callback' => $this->check_permission('edit_shop_orders'),
        ));
        
        // Create order
        $this->register_route('/orders', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_order'),
            'permission_callback' => $this->check_permission('edit_shop_orders'),
        ));
        
        // Update order
        $this->register_route('/orders/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_order'),
            'permission_callback' => $this->check_permission('edit_shop_orders'),
        ));
        
        // Delete order
        $this->register_route('/orders/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_order'),
            'permission_callback' => $this->check_permission('delete_shop_orders'),
        ));
        
        // Update order status
        $this->register_route('/orders/(?P<id>\d+)/status', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_order_status'),
            'permission_callback' => $this->check_permission('edit_shop_orders'),
        ));
        
        // Add order note
        $this->register_route('/orders/(?P<id>\d+)/notes', array(
            'methods' => 'POST',
            'callback' => array($this, 'add_order_note'),
            'permission_callback' => $this->check_permission('edit_shop_orders'),
        ));
        
        // Get order notes
        $this->register_route('/orders/(?P<id>\d+)/notes', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_order_notes'),
            'permission_callback' => $this->check_permission('edit_shop_orders'),
        ));
        
        // Get order statuses
        $this->register_route('/orders/statuses', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_order_statuses'),
            'permission_callback' => $this->check_permission('edit_shop_orders'),
        ));
        
        // Bulk update orders
        $this->register_route('/orders/bulk', array(
            'methods' => 'POST',
            'callback' => array($this, 'bulk_update'),
            'permission_callback' => $this->check_permission('edit_shop_orders'),
        ));
        
        // Get order stats
        $this->register_route('/orders/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_order_stats'),
            'permission_callback' => $this->check_permission('edit_shop_orders'),
        ));
    }
    
    /**
     * Get orders list
     */
    public function get_orders($request) {
        if (!class_exists('WooCommerce')) {
            return $this->error_response('WooCommerce is not installed or activated', 400);
        }
        
        $pagination = $this->get_pagination_params($request);
        $status = $request->get_param('status');
        $customer = $request->get_param('customer');
        $search = $request->get_param('search');
        $date_from = $request->get_param('date_from');
        $date_to = $request->get_param('date_to');
        $payment_method = $request->get_param('payment_method');
        
        $args = array(
            'limit' => $pagination['per_page'],
            'offset' => ($pagination['page'] - 1) * $pagination['per_page'],
            'orderby' => $pagination['orderby'] === 'created_at' ? 'date' : $pagination['orderby'],
            'order' => $pagination['order'],
            'return' => 'ids',
        );
        
        if ($status) {
            // Handle both 'wc-' prefixed and non-prefixed statuses
            $status_value = strpos($status, 'wc-') === 0 ? $status : 'wc-' . $status;
            $args['status'] = $status_value;
        }
        
        if ($customer) {
            $args['customer'] = $customer;
        }
        
        if ($search) {
            $args['s'] = $search;
        }
        
        if ($date_from) {
            $args['date_created'] = '>=' . $date_from;
        }
        
        if ($date_to) {
            if (isset($args['date_created'])) {
                $args['date_created'] .= '...' . $date_to;
            } else {
                $args['date_created'] = '<=' . $date_to;
            }
        }
        
        if ($payment_method) {
            $args['payment_method'] = $payment_method;
        }
        
        // Get orders
        $order_ids = wc_get_orders($args);
        
        // Get total count
        $total_args = $args;
        unset($total_args['limit']);
        unset($total_args['offset']);
        $total = count(wc_get_orders($total_args));
        
        // Format orders
        $orders = array();
        foreach ($order_ids as $order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $orders[] = $this->format_order($order);
            }
        }
        
        return $this->success_response(
            $this->format_pagination_response(
                $orders,
                $total,
                $pagination['page'],
                $pagination['per_page']
            )
        );
    }
    
    /**
     * Get single order
     */
    public function get_order($request) {
        if (!class_exists('WooCommerce')) {
            return $this->error_response('WooCommerce is not installed or activated', 400);
        }
        
        $order_id = $request['id'];
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return $this->error_response('Order not found', 404);
        }
        
        return $this->success_response($this->format_order($order, true));
    }
    
    /**
     * Create order
     */
    public function create_order($request) {
        if (!class_exists('WooCommerce')) {
            return $this->error_response('WooCommerce is not installed or activated', 400);
        }
        
        try {
            $order = wc_create_order();
            
            // Set customer
            $customer_id = $request->get_param('customer_id');
            if ($customer_id) {
                $order->set_customer_id($customer_id);
            }
            
            // Add line items
            $line_items = $request->get_param('line_items');
            if ($line_items && is_array($line_items)) {
                foreach ($line_items as $item) {
                    $product_id = isset($item['product_id']) ? intval($item['product_id']) : 0;
                    $quantity = isset($item['quantity']) ? intval($item['quantity']) : 1;
                    
                    if ($product_id) {
                        $product = wc_get_product($product_id);
                        if ($product) {
                            $order->add_product($product, $quantity);
                        }
                    }
                }
            }
            
            // Set billing address
            $billing = $request->get_param('billing');
            if ($billing && is_array($billing)) {
                $order->set_billing_first_name($billing['first_name'] ?? '');
                $order->set_billing_last_name($billing['last_name'] ?? '');
                $order->set_billing_company($billing['company'] ?? '');
                $order->set_billing_address_1($billing['address_1'] ?? '');
                $order->set_billing_address_2($billing['address_2'] ?? '');
                $order->set_billing_city($billing['city'] ?? '');
                $order->set_billing_state($billing['state'] ?? '');
                $order->set_billing_postcode($billing['postcode'] ?? '');
                $order->set_billing_country($billing['country'] ?? '');
                $order->set_billing_email($billing['email'] ?? '');
                $order->set_billing_phone($billing['phone'] ?? '');
            }
            
            // Set shipping address
            $shipping = $request->get_param('shipping');
            if ($shipping && is_array($shipping)) {
                $order->set_shipping_first_name($shipping['first_name'] ?? '');
                $order->set_shipping_last_name($shipping['last_name'] ?? '');
                $order->set_shipping_company($shipping['company'] ?? '');
                $order->set_shipping_address_1($shipping['address_1'] ?? '');
                $order->set_shipping_address_2($shipping['address_2'] ?? '');
                $order->set_shipping_city($shipping['city'] ?? '');
                $order->set_shipping_state($shipping['state'] ?? '');
                $order->set_shipping_postcode($shipping['postcode'] ?? '');
                $order->set_shipping_country($shipping['country'] ?? '');
            }
            
            // Set payment method
            $payment_method = $request->get_param('payment_method');
            if ($payment_method) {
                $order->set_payment_method($payment_method);
            }
            
            // Set payment method title
            $payment_method_title = $request->get_param('payment_method_title');
            if ($payment_method_title) {
                $order->set_payment_method_title($payment_method_title);
            }
            
            // Set status
            $status = $request->get_param('status');
            if ($status) {
                $order->set_status($status);
            }
            
            // Calculate totals
            $order->calculate_totals();
            
            // Save order
            $order->save();
            
            // Add order note if provided
            $note = $request->get_param('customer_note');
            if ($note) {
                $order->add_order_note($note, 1);
            }
            
            return $this->success_response(
                $this->format_order($order, true),
                'Order created successfully',
                201
            );
            
        } catch (Exception $e) {
            return $this->error_response('Failed to create order: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Update order
     */
    public function update_order($request) {
        if (!class_exists('WooCommerce')) {
            return $this->error_response('WooCommerce is not installed or activated', 400);
        }
        
        $order_id = $request['id'];
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return $this->error_response('Order not found', 404);
        }
        
        try {
            // Update billing address
            $billing = $request->get_param('billing');
            if ($billing && is_array($billing)) {
                if (isset($billing['first_name'])) $order->set_billing_first_name($billing['first_name']);
                if (isset($billing['last_name'])) $order->set_billing_last_name($billing['last_name']);
                if (isset($billing['company'])) $order->set_billing_company($billing['company']);
                if (isset($billing['address_1'])) $order->set_billing_address_1($billing['address_1']);
                if (isset($billing['address_2'])) $order->set_billing_address_2($billing['address_2']);
                if (isset($billing['city'])) $order->set_billing_city($billing['city']);
                if (isset($billing['state'])) $order->set_billing_state($billing['state']);
                if (isset($billing['postcode'])) $order->set_billing_postcode($billing['postcode']);
                if (isset($billing['country'])) $order->set_billing_country($billing['country']);
                if (isset($billing['email'])) $order->set_billing_email($billing['email']);
                if (isset($billing['phone'])) $order->set_billing_phone($billing['phone']);
            }
            
            // Update shipping address
            $shipping = $request->get_param('shipping');
            if ($shipping && is_array($shipping)) {
                if (isset($shipping['first_name'])) $order->set_shipping_first_name($shipping['first_name']);
                if (isset($shipping['last_name'])) $order->set_shipping_last_name($shipping['last_name']);
                if (isset($shipping['company'])) $order->set_shipping_company($shipping['company']);
                if (isset($shipping['address_1'])) $order->set_shipping_address_1($shipping['address_1']);
                if (isset($shipping['address_2'])) $order->set_shipping_address_2($shipping['address_2']);
                if (isset($shipping['city'])) $order->set_shipping_city($shipping['city']);
                if (isset($shipping['state'])) $order->set_shipping_state($shipping['state']);
                if (isset($shipping['postcode'])) $order->set_shipping_postcode($shipping['postcode']);
                if (isset($shipping['country'])) $order->set_shipping_country($shipping['country']);
            }
            
            // Update payment method
            $payment_method = $request->get_param('payment_method');
            if ($payment_method !== null) {
                $order->set_payment_method($payment_method);
            }
            
            // Update payment method title
            $payment_method_title = $request->get_param('payment_method_title');
            if ($payment_method_title !== null) {
                $order->set_payment_method_title($payment_method_title);
            }
            
            // Recalculate totals if requested
            if ($request->get_param('recalculate') === true) {
                $order->calculate_totals();
            }
            
            // Save order
            $order->save();
            
            return $this->success_response(
                $this->format_order($order, true),
                'Order updated successfully'
            );
            
        } catch (Exception $e) {
            return $this->error_response('Failed to update order: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Delete order
     */
    public function delete_order($request) {
        if (!class_exists('WooCommerce')) {
            return $this->error_response('WooCommerce is not installed or activated', 400);
        }
        
        $order_id = $request['id'];
        $force = $request->get_param('force') === true;
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return $this->error_response('Order not found', 404);
        }
        
        try {
            if ($force) {
                // Permanently delete
                $order->delete(true);
                $message = 'Order permanently deleted';
            } else {
                // Move to trash
                $order->delete(false);
                $message = 'Order moved to trash';
            }
            
            return $this->success_response(
                array('deleted' => true),
                $message
            );
            
        } catch (Exception $e) {
            return $this->error_response('Failed to delete order: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Update order status
     */
    public function update_order_status($request) {
        if (!class_exists('WooCommerce')) {
            return $this->error_response('WooCommerce is not installed or activated', 400);
        }
        
        $order_id = $request['id'];
        $status = $this->sanitize_string($request->get_param('status'));
        $note = $request->get_param('note');
        
        if (!$status) {
            return $this->error_response('Status is required', 400);
        }
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return $this->error_response('Order not found', 404);
        }
        
        try {
            // Update status
            $order->update_status($status, $note ?: '', true);
            
            return $this->success_response(
                array(
                    'id' => $order->get_id(),
                    'status' => $order->get_status(),
                    'status_name' => wc_get_order_status_name($order->get_status()),
                ),
                'Order status updated successfully'
            );
            
        } catch (Exception $e) {
            return $this->error_response('Failed to update order status: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Add order note
     */
    public function add_order_note($request) {
        if (!class_exists('WooCommerce')) {
            return $this->error_response('WooCommerce is not installed or activated', 400);
        }
        
        $order_id = $request['id'];
        $note = $request->get_param('note');
        $is_customer_note = $request->get_param('is_customer_note') === true ? 1 : 0;
        
        if (!$note) {
            return $this->error_response('Note content is required', 400);
        }
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return $this->error_response('Order not found', 404);
        }
        
        try {
            $note_id = $order->add_order_note($note, $is_customer_note);
            
            return $this->success_response(
                array(
                    'note_id' => $note_id,
                    'note' => $note,
                    'is_customer_note' => $is_customer_note,
                ),
                'Note added successfully'
            );
            
        } catch (Exception $e) {
            return $this->error_response('Failed to add note: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get order notes
     */
    public function get_order_notes($request) {
        if (!class_exists('WooCommerce')) {
            return $this->error_response('WooCommerce is not installed or activated', 400);
        }
        
        $order_id = $request['id'];
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return $this->error_response('Order not found', 404);
        }
        
        $notes = wc_get_order_notes(array(
            'order_id' => $order_id,
        ));
        
        $formatted_notes = array();
        foreach ($notes as $note) {
            $formatted_notes[] = array(
                'id' => $note->id,
                'content' => $note->content,
                'is_customer_note' => $note->customer_note,
                'date_created' => $note->date_created->date('Y-m-d H:i:s'),
                'added_by' => $note->added_by,
            );
        }
        
        return $this->success_response($formatted_notes);
    }
    
    /**
     * Get order statuses
     */
    public function get_order_statuses($request) {
        if (!class_exists('WooCommerce')) {
            return $this->error_response('WooCommerce is not installed or activated', 400);
        }
        
        $statuses = wc_get_order_statuses();
        
        $formatted_statuses = array();
        foreach ($statuses as $slug => $name) {
            // Remove 'wc-' prefix for consistency
            $slug = str_replace('wc-', '', $slug);
            $formatted_statuses[] = array(
                'slug' => $slug,
                'name' => $name,
            );
        }
        
        return $this->success_response($formatted_statuses);
    }
    
    /**
     * Bulk update orders
     */
    public function bulk_update($request) {
        if (!class_exists('WooCommerce')) {
            return $this->error_response('WooCommerce is not installed or activated', 400);
        }
        
        $order_ids = $request->get_param('order_ids');
        $action = $this->sanitize_string($request->get_param('action'));
        $data = $request->get_param('data');
        
        if (!$order_ids || !is_array($order_ids)) {
            return $this->error_response('Order IDs are required', 400);
        }
        
        if (!$action) {
            return $this->error_response('Action is required', 400);
        }
        
        $updated = 0;
        $failed = 0;
        
        foreach ($order_ids as $order_id) {
            $order = wc_get_order($order_id);
            
            if (!$order) {
                $failed++;
                continue;
            }
            
            try {
                switch ($action) {
                    case 'delete':
                        $order->delete(false);
                        break;
                        
                    case 'delete_permanently':
                        $order->delete(true);
                        break;
                        
                    case 'update_status':
                        if (isset($data['status'])) {
                            $order->update_status($data['status'], '', true);
                        }
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
                'total' => count($order_ids),
            ),
            sprintf('%d orders updated, %d failed', $updated, $failed)
        );
    }
    
    /**
     * Get order statistics
     */
    public function get_order_stats($request) {
        if (!class_exists('WooCommerce')) {
            return $this->error_response('WooCommerce is not installed or activated', 400);
        }
        
        $date_from = $request->get_param('date_from');
        $date_to = $request->get_param('date_to');
        
        $args = array(
            'return' => 'ids',
            'limit' => -1,
        );
        
        if ($date_from && $date_to) {
            $args['date_created'] = $date_from . '...' . $date_to;
        } elseif ($date_from) {
            $args['date_created'] = '>=' . $date_from;
        } elseif ($date_to) {
            $args['date_created'] = '<=' . $date_to;
        }
        
        // Get all orders
        $order_ids = wc_get_orders($args);
        
        // Calculate stats
        $total_orders = count($order_ids);
        $total_sales = 0;
        $status_counts = array();
        
        foreach ($order_ids as $order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $total_sales += $order->get_total();
                
                $status = $order->get_status();
                if (!isset($status_counts[$status])) {
                    $status_counts[$status] = 0;
                }
                $status_counts[$status]++;
            }
        }
        
        return $this->success_response(array(
            'total_orders' => $total_orders,
            'total_sales' => $total_sales,
            'average_order_value' => $total_orders > 0 ? $total_sales / $total_orders : 0,
            'status_counts' => $status_counts,
        ));
    }
    
    /**
     * Format order for response
     */
    private function format_order($order, $detailed = false) {
        $data = array(
            'id' => $order->get_id(),
            'order_number' => $order->get_order_number(),
            'status' => $order->get_status(),
            'status_name' => wc_get_order_status_name($order->get_status()),
            'currency' => $order->get_currency(),
            'total' => $order->get_total(),
            'subtotal' => $order->get_subtotal(),
            'total_tax' => $order->get_total_tax(),
            'shipping_total' => $order->get_shipping_total(),
            'discount_total' => $order->get_discount_total(),
            'customer_id' => $order->get_customer_id(),
            'payment_method' => $order->get_payment_method(),
            'payment_method_title' => $order->get_payment_method_title(),
            'date_created' => $order->get_date_created() ? $order->get_date_created()->date('Y-m-d H:i:s') : null,
            'date_modified' => $order->get_date_modified() ? $order->get_date_modified()->date('Y-m-d H:i:s') : null,
            'date_paid' => $order->get_date_paid() ? $order->get_date_paid()->date('Y-m-d H:i:s') : null,
            'date_completed' => $order->get_date_completed() ? $order->get_date_completed()->date('Y-m-d H:i:s') : null,
        );
        
        // Add customer info
        $data['customer'] = array(
            'id' => $order->get_customer_id(),
            'email' => $order->get_billing_email(),
            'first_name' => $order->get_billing_first_name(),
            'last_name' => $order->get_billing_last_name(),
        );
        
        if ($detailed) {
            // Add billing address
            $data['billing'] = array(
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'company' => $order->get_billing_company(),
                'address_1' => $order->get_billing_address_1(),
                'address_2' => $order->get_billing_address_2(),
                'city' => $order->get_billing_city(),
                'state' => $order->get_billing_state(),
                'postcode' => $order->get_billing_postcode(),
                'country' => $order->get_billing_country(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
            );
            
            // Add shipping address
            $data['shipping'] = array(
                'first_name' => $order->get_shipping_first_name(),
                'last_name' => $order->get_shipping_last_name(),
                'company' => $order->get_shipping_company(),
                'address_1' => $order->get_shipping_address_1(),
                'address_2' => $order->get_shipping_address_2(),
                'city' => $order->get_shipping_city(),
                'state' => $order->get_shipping_state(),
                'postcode' => $order->get_shipping_postcode(),
                'country' => $order->get_shipping_country(),
            );
            
            // Add line items
            $data['line_items'] = array();
            foreach ($order->get_items() as $item_id => $item) {
                $product = $item->get_product();
                $data['line_items'][] = array(
                    'id' => $item_id,
                    'product_id' => $item->get_product_id(),
                    'variation_id' => $item->get_variation_id(),
                    'name' => $item->get_name(),
                    'quantity' => $item->get_quantity(),
                    'subtotal' => $item->get_subtotal(),
                    'total' => $item->get_total(),
                    'tax' => $item->get_total_tax(),
                    'sku' => $product ? $product->get_sku() : '',
                    'price' => $product ? $product->get_price() : 0,
                );
            }
            
            // Add shipping lines
            $data['shipping_lines'] = array();
            foreach ($order->get_items('shipping') as $item_id => $item) {
                $data['shipping_lines'][] = array(
                    'id' => $item_id,
                    'method_title' => $item->get_method_title(),
                    'method_id' => $item->get_method_id(),
                    'total' => $item->get_total(),
                );
            }
            
            // Add customer note
            $data['customer_note'] = $order->get_customer_note();
        }
        
        return $data;
    }
}
