<?php
/**
 * Plugin Name: WooCommerce Not Picked Up Status
 * Description: Adds a custom "Not Picked Up" status for WooCommerce orders, including support for bulk actions.
 * Version: 1.1
 * Author: Jeramey Jannene
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Add custom order status
add_action('init', 'register_not_picked_up_order_status');

function register_not_picked_up_order_status() {
    register_post_status('wc-not-picked-up', array(
        'label'                     => _x('Not Picked Up', 'Order status', 'woocommerce'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Not Picked Up (%s)', 'Not Picked Up (%s)', 'woocommerce'),
    ));
}

// Add the new status to WooCommerce's list of statuses
add_filter('wc_order_statuses', 'add_not_picked_up_to_order_statuses');

function add_not_picked_up_to_order_statuses($order_statuses) {
    $new_statuses = array();

    // Insert "Not Picked Up" after "On Hold"
    foreach ($order_statuses as $key => $status) {
        $new_statuses[$key] = $status;

        if ('wc-on-hold' === $key) {
            $new_statuses['wc-not-picked-up'] = _x('Not Picked Up', 'Order status', 'woocommerce');
        }
    }

    return $new_statuses;
}

// Prevent customer notifications for "Not Picked Up" status
add_filter('woocommerce_email_actions', 'remove_not_picked_up_email_notifications');

function remove_not_picked_up_email_notifications($email_actions) {
    $to_remove = array(
        'woocommerce_order_status_not-picked-up', 
        'woocommerce_order_status_not-picked-up_to_processing',
        'woocommerce_order_status_not-picked-up_to_completed',
    );

    return array_diff($email_actions, $to_remove);
}

// Add "Not Picked Up" to bulk actions dropdown
add_filter('bulk_actions-edit-shop_order', 'add_bulk_action_not_picked_up');

function add_bulk_action_not_picked_up($bulk_actions) {
    $bulk_actions['mark_not-picked-up'] = __('Change status to Not Picked Up', 'woocommerce');
    return $bulk_actions;
}

// Handle bulk action for "Not Picked Up"
add_action('handle_bulk_actions-edit-shop_order', 'handle_bulk_action_not_picked_up', 10, 3);

function handle_bulk_action_not_picked_up($redirect_to, $action, $post_ids) {
    if ($action === 'mark_not-picked-up') {
        foreach ($post_ids as $post_id) {
            $order = wc_get_order($post_id);
            if ($order) {
                $order->update_status('not-picked-up', __('Order marked as Not Picked Up', 'woocommerce'));
            }
        }
        $redirect_to = add_query_arg('marked_not_picked_up', count($post_ids), $redirect_to);
    }
    return $redirect_to;
}

// Add a notice after bulk action
add_action('admin_notices', 'not_picked_up_bulk_action_notice');

function not_picked_up_bulk_action_notice() {
    if (isset($_GET['marked_not_picked_up'])) {
        $count = intval($_GET['marked_not_picked_up']);
        printf('<div class="notice notice-success is-dismissible"><p>%s</p></div>', 
            sprintf(_n('%s order marked as Not Picked Up.', '%s orders marked as Not Picked Up.', $count, 'woocommerce'), $count)
        );
    }
}
