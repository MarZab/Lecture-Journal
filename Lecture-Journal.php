<?php
/*
Plugin Name: Lecture Journal
Plugin URI: http://zabreznik.net/lecture-journal
Description: Keep a journal on attendance and lecture details
Version: 0.2
Author: Marko Zabreznik
Author URI:	http://zabreznik.net
*/

define('LECJOU_VERSION', 0.8);
define('LECJOU_DB_VERSION', 1);
define('LECJOU_PLUGIN_DIR', __DIR__.'/');
define('LECJOU_PLUGIN_URL', plugins_url($path = '/Lecture-Journal/'));


// Installation check
add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', 'lecjou_check_updated' );
function lecjou_check_updated() {
	if ( !is_super_admin() )
		return;
	if (get_site_option( 'Lecture-Journal DB Version' ) < LECJOU_DB_VERSION ) {
		require_once(LECJOU_PLUGIN_DIR.'includes/upgrade.php');
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

	// classes taxonomy
	$labels = array(
		'name' => _x( 'Classes', 'lecjou' ),
		'singular_name' => _x( 'Class', 'lecjou' ),
		'search_items' =>  __( 'Search Classes', 'lecjou' ),
		'popular_items' => null,
		'all_items' => __( 'All Classes', 'lecjou' ),
		'parent_item' => null,
		'parent_item_colon' => null,
		'edit_item' => __( 'Edit Class', 'lecjou' ),
		'update_item' => __( 'Update Class', 'lecjou' ),
		'add_new_item' => __( 'Add New Class', 'lecjou' ),
		'new_item_name' => __( 'New Class Name', 'lecjou' ),
		'separate_items_with_commas' => __( 'Separate classes with commas', 'lecjou' ),
		'add_or_remove_items' => __( 'Add or remove classes', 'lecjou' ),
		'choose_from_most_used' => __( 'Choose from the most used classes', 'lecjou' ),
		'menu_name' => __( 'Classes', 'lecjou' ),
	);
	
	register_taxonomy( 
		'class', 
		'lecture', 
		array( 
			'hierarchical' => false, 
			'label' => 'Classes', 
			'labels' => $labels,
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
	add_filter( 'manage_edit-class_columns', 'lecjou_manage_class_columns', 10, 1);
	add_filter( 'manage_class_custom_column', 'lecjou_manage_class_custom_column', 10, 3 );
	
	// lectures post type
	$args = array(
		'labels' => array(
			'name' => _x( 'Lectures', 'lecjou' ),
			'singular_name' => _x( 'Lecture', 'lecjou' ),
			'menu_name' => _x( 'Lectures', 'lecjou' ),
			'add_new' => _x( 'Add New', 'lecture' ),
			'add_new_item' => _x( 'Add New Lecture', 'lecjou' ),
			'edit_item' => _x( 'Edit Lecture', 'lecjou' ),
			'new_item' => _x( 'New Lecture', 'lecjou' ),
			'view_item' => _x( 'View Lecture', 'lecjou' ),
			'search_items' => _x( 'Search Lectures', 'lecjou' ),
			'not_found' => _x( 'No lectures found', 'lecjou' ),
			'not_found_in_trash' => _x( 'No lectures found in Trash', 'lecjou' ),
			'parent_item_colon' => _x( 'Parent Lecture:', 'lecjou' ),
		),
		'hierarchical' => false,
		'supports' => array( 'title', 'editor', 'author' ),
		'taxonomies' => array( 'classes' ),
		'public' => true,
		'show_ui' => true,
		'show_in_menu' => true,

		'menu_icon' => LECJOU_PLUGIN_URL .'/icons/logo.png',

		'show_in_nav_menus' => true,
		'publicly_queryable' => true,
		'exclude_from_search' => false,
		'has_archive' => true,
		'can_export' => true,

		'capabilities' => array(
			'edit_post' => 'read',
			'edit_posts' => 'read',
			'edit_others_posts' => 'add_users',
			'publish_posts' => 'read',
			'read_post' => 'read',
			'read_private_posts' => 'add_users',
			'delete_post' => 'read'
		)
	);
	register_post_type( 'lecture', $args );
	
}

if (is_admin()) {
	require_once(LECJOU_PLUGIN_DIR.'includes/classaddins.php');
	require_once(LECJOU_PLUGIN_DIR.'includes/editoraddins.php');
	require_once(LECJOU_PLUGIN_DIR.'includes/listaddins.php');
	require_once(LECJOU_PLUGIN_DIR.'includes/editgrades.php');
	require_once(LECJOU_PLUGIN_DIR.'includes/report.php');
}

// // Templates
add_filter('template_include', 'lecjou_set_template');
function lecjou_set_template( $template ){

	// stylesheet
	wp_enqueue_style( 'lecjou-style', plugins_url('style.css', __FILE__) );
	
	$filename = basename($template);

	switch(true) {
		case is_singular('lecture'):
			// do we have the right template
			if (1 == preg_match('/^single-lecture((-(\S*))?).php/',$filename))
				return $template;
			// use default
			return plugin_dir_path(__FILE__ ).'templates/single-lecture.php';
		case is_tax('class'):
			if (1 == preg_match('/^taxonomy-class((-(\S*))?).php/',$filename))
				return $template;
			return plugin_dir_path(__FILE__ ).'templates/taxonomy-class.php';
	}
	return $template;
}

// // Secret
function lecjou_checkSecret($classID){
// check for secret
	$secret = get_metadata('class', $classID, 'secret', false); $secret = $secret[0];
	
	if (isset($secret) && $secret != '') {
		if (isset($_COOKIE['lecjou_secrets']) && in_array($secret, $_COOKIE['lecjou_secrets'])) {
			return true;
		} elseif (isset($_GET['secret']) && $_GET['secret'] == $secret) {
			setcookie('lecjou_secrets['.$classID.']', $secret);
			return true;
		} else {
			return false;
		}
	}
	return true;
}

