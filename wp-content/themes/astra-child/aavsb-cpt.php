<?php
function register_roster_cpt() {
    $labels = array(
        'name'               => _x('Rosters', 'post type general name', 'aavsb'),
        'singular_name'      => _x('Roster', 'post type singular name', 'aavsb'),
        'menu_name'          => _x('Rosters', 'admin menu', 'aavsb'),
        'name_admin_bar'     => _x('Roster', 'add new on admin bar', 'aavsb'),
        'add_new'            => _x('Add New', 'roster', 'aavsb'),
        'add_new_item'       => __('Add New Roster', 'aavsb'),
        'new_item'           => __('New Roster', 'aavsb'),
        'edit_item'          => __('Edit Roster', 'aavsb'),
        'view_item'          => __('View Roster', 'aavsb'),
        'all_items'          => __('All Rosters', 'aavsb'),
        'search_items'       => __('Search Rosters', 'aavsb'),
        'parent_item_colon'  => __('Parent Rosters:', 'aavsb'),
        'not_found'          => __('No rosters found.', 'aavsb'),
        'not_found_in_trash' => __('No rosters found in Trash.', 'aavsb')
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'roster'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'custom-fields'),
        'show_in_rest'       => true, // Enable the Block Editor
        'menu_icon'          => 'dashicons-portfolio', // Set the icon
    );

    register_post_type('roster', $args);
}
add_action('init', 'register_roster_cpt');