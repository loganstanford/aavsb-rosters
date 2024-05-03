<?php

require 'vendor/autoload.php';
require_once('aavsb-cpt.php');
require_once('handle-roster.php');
require_once('generate-roster-excel.php');

function enqueue_aavsb_theme_styles() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('child-style', get_stylesheet_directory_uri() . '/style.css', array('parent-style'));
    wp_enqueue_style('aavsb', get_stylesheet_directory_uri() . '/css/aavsb.css');
}

add_action('wp_enqueue_scripts', 'enqueue_aavsb_theme_styles');

function enqueue_roster_styles_and_scripts() {
    // Check if the New Roster template is being used for the current page
    if ( is_page_template( 'template-new-roster.php' ) || is_page_template('template-edit-roster.php') || is_page_template('template-manage-rosters.php') ) {
        // Enqueue Bootstrap CSS
        wp_enqueue_style( 'bootstrap-css', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css', array(), '4.5.2' );

        // Enqueue Font Awesome
        wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css', array(), '6.0.0-beta3' );

        // Enqueue custom styles
        wp_enqueue_style( 'roster-style', get_stylesheet_directory_uri() . '/css/roster.css', array(), '1.0' );
        wp_enqueue_script( 'roster-script', get_stylesheet_directory_uri() . '/js/roster.js', array('jquery'), '1.0', true );
    }

    if (is_page_template('template-new-roster.php')) {
        wp_enqueue_style('new-roster-style', get_stylesheet_directory_uri() . '/css/new-roster.css');
        wp_enqueue_script('new-roster-script', get_stylesheet_directory_uri() . '/js/new-roster.js', array('jquery'), '1.0', true);
        wp_localize_script('new-roster-script', 'aavsbAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('submit_roster_nonce')
        ));
    }

    if (is_page_template('template-edit-roster.php')) {
        wp_enqueue_style('edit-roster-style', get_stylesheet_directory_uri() . '/css/edit-roster.css');
        wp_enqueue_script('edit-roster-script', get_stylesheet_directory_uri() . '/js/edit-roster.js', array('jquery'), '1.0', true);
        wp_localize_script('edit-roster-script', 'aavsbAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('update_roster_nonce')
        ));
    }

    if (is_page_template('template-manage-rosters.php')) {
        wp_enqueue_style('manage-rosters-style', get_stylesheet_directory_uri() . '/css/manage-rosters.css');
        wp_enqueue_script('manage-rosters-script', get_stylesheet_directory_uri() . '/js/manage-rosters.js', array('jquery'), '1.0', true);
        wp_localize_script('manage-rosters-script', 'aavsbAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('export_roster_nonce')
        ));
    }
}

add_action( 'wp_enqueue_scripts', 'enqueue_roster_styles_and_scripts' );