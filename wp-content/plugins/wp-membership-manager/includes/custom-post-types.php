<?php

function wpmm_register_post_type() {
    $labels = array(
        'name'               => _x( 'Membership Plans', 'post type general name', 'wpmm' ),
        'singular_name'      => _x( 'Membership Plan', 'post type singular name', 'wpmm' ),
        'menu_name'          => _x( 'Membership Plans', 'admin menu', 'wpmm' ),
        'name_admin_bar'     => _x( 'Membership Plan', 'add new on admin bar', 'wpmm' ),
        'add_new'            => _x( 'Add New', 'membership plan', 'wpmm' ),
        'add_new_item'       => __( 'Add New Membership Plan', 'wpmm' ),
        'new_item'           => __( 'New Membership Plan', 'wpmm' ),
        'edit_item'          => __( 'Edit Membership Plan', 'wpmm' ),
        'view_item'          => __( 'View Membership Plan', 'wpmm' ),
        'all_items'          => __( 'All Membership Plans', 'wpmm' ),
        'search_items'       => __( 'Search Membership Plans', 'wpmm' ),
        'parent_item_colon'  => __( 'Parent Membership Plans:', 'wpmm' ),
        'not_found'          => __( 'No membership plans found.', 'wpmm' ),
        'not_found_in_trash' => __( 'No membership plans found in Trash.', 'wpmm' )
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'membership-plan' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array( 'title', 'editor' ),
    );

    register_post_type( 'membership_plan', $args );
}
add_action( 'init', 'wpmm_register_post_type' );
