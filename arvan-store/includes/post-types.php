<?php
if (!defined('ABSPATH')) {
    exit;
}

function arvan_store_register_post_types() {
    $labels = array(
        'name'                  => _x('Arvan Orders', 'Post type general name', 'arvan-store'),
        'singular_name'         => _x('Arvan Order', 'Post type singular name', 'arvan-store'),
        'menu_name'             => _x('Arvan Orders', 'Admin Menu text', 'arvan-store'),
        'name_admin_bar'        => _x('Arvan Order', 'Add New on Toolbar', 'arvan-store'),
        'add_new'               => __('Add New', 'arvan-store'),
        'add_new_item'          => __('Add New Order', 'arvan-store'),
        'new_item'              => __('New Order', 'arvan-store'),
        'edit_item'             => __('Edit Order', 'arvan-store'),
        'view_item'             => __('View Order', 'arvan-store'),
        'all_items'             => __('All Orders', 'arvan-store'),
        'search_items'          => __('Search Orders', 'arvan-store'),
        'not_found'             => __('No orders found.', 'arvan-store'),
        'not_found_in_trash'    => __('No orders found in Trash.', 'arvan-store'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => false,
        'rewrite'            => false,
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => 56,
        'menu_icon'          => 'dashicons-cart',
        'supports'           => array('title', 'custom-fields'),
        'show_in_rest'       => false,
    );

    register_post_type('arvan_order', $args);
}
add_action('init', 'arvan_store_register_post_types');
