<?php
/*
Plugin Name: WooCommerce Products Meta Data
Description: Fetch and display WooCommerce products with custom meta data via woocommerce REST API.
Version: 1.0
Author: Tanmay Patil
*/

// Register custom REST API endpoint
add_action('rest_api_init', 'product_meta_endpoint');

function product_meta_endpoint() {
    register_rest_route(
        'wc/v3', 
        '/products/meta/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_product_with_meta',
        'args' => array(
            'id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ),
        ),
    ));
}

function get_product_with_meta($data) {
    global $wpdb;

    $product_id = $data['id'];

    $table_postmeta = $wpdb->prefix . 'postmeta';
    $table_product_meta_lookup = $wpdb->prefix . 'wc_product_meta_lookup';
    $table_posts = $wpdb->prefix . 'posts';

    // Query to fetch _wc_points_earned meta value
    $points_earned_query = $wpdb->prepare("
        SELECT meta_value
        FROM $table_postmeta
        WHERE meta_key = '_wc_points_earned'
        AND post_id = %d
    ", $product_id);

    $points_earned = $wpdb->get_var($points_earned_query);


    // Query to fetch _wc_points_max_discount meta value
    $max_discount_query = $wpdb->prepare("
        SELECT meta_value
        FROM $table_postmeta
        WHERE meta_key = '_wc_points_max_discount'
        AND post_id = %d
    ", $product_id);

    $max_discount = $wpdb->get_var($max_discount_query);


    // Query to fetch product data from wp_wc_product_meta_lookup table
    $product_data_query = $wpdb->prepare("
        SELECT *
        FROM $table_product_meta_lookup
        WHERE product_id = %d
    ", $product_id);

    $product_data = $wpdb->get_row($product_data_query);

    // Query to fetch product data from wp_posts table
    $product_post_query = $wpdb->prepare("
        SELECT *
        FROM $table_posts
        WHERE ID = %d
    ", $product_id);

    $product_post = $wpdb->get_row($product_post_query);

    Query to fetch additional meta data for the product
    $additional_meta_query = $wpdb->prepare("
        SELECT meta_key, meta_value
        FROM {$wpdb->prefix}postmeta
        WHERE post_id = %d
    ", $product_id);

    $additional_meta = $wpdb->get_results($additional_meta_query, ARRAY_A);

    // Prepare response data
    $response_data = array(
        'id' => $product_id,
        'name' => $product_post->post_title,
        'price' => $product_data->min_price,
        'wc_points_earned' => $points_earned,
        'wc_points_max_discount' => $max_discount,
        'additional_meta' => $additional_meta,
    );

    return rest_ensure_response($response_data);
}
