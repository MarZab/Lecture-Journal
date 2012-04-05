<?php
/*
Plugin Name: Lecture Journal
Plugin URI: http://zabreznik.net/lecture-journal
Description: Keep a journal on attendance and lecture details
Version: 0.2
Author: Marko Zabreznik
Author URI:	http://zabreznik.net
*/

define('LECJOU_VERSION', 0.2);
define('LECJOU_DB_VERSION', 1);
define('LECJOU_PLUGIN_DIR', __DIR__.'/');
define('LECJOU_PLUGIN_URL', plugins_url($path = '/Lecture-Journal/'));


// Installation check
add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', 'lecjou_check_updated' );
function lecjou_check_updated() {
	if ( !is_super_admin() )
		return;
	if (get_site_option( 'Lecture-Journal DB Version' ) < LECJOU_DB_VERSION ) {
		require_once(LECJOU_PLUGIN_DIR.'upgrade.php');
	}
}

// register new post type
add_action( 'init', 'lecjou_register_lecture' );
function lecjou_register_lecture() {

	if (is_admin()) {
		// JS
		wp_enqueue_script( 'suggest' );
	}

	global $wpdb;
	$variable_name = 'classmeta';
	$wpdb->$variable_name = $wpdb->prefix.'classmeta';
	
	register_taxonomy( 
		'class', 
		'lecture', 
		array( 
			'hierarchical' => false, 
			'label' => 'Classes', 
			'query_var' => true,
			'show_in_nav_menus' => false,
			'show_tagcloud' => false,
			'capabilities' => array(
				'manage_terms' => 'add_users',
				'edit_terms' => 'add_users',
				'delete_terms' => 'add_users',
				'assign_terms' => 'read',
			)
		) 
	);
	
	// lectures post type
    $args = array( 
        'hierarchical' => false,
        'supports' => array( 'title', 'editor', 'author' ),
        'taxonomies' => array( 'classes' ),
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,

        'show_in_nav_menus' => true,
        'publicly_queryable' => true,
        'exclude_from_search' => false,
        'has_archive' => true,
        'can_export' => true,

    );
	register_post_type( 'lecture', $args );
	
}