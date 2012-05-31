<?php
/*
	List Addins
*/

if (!defined ( 'LECJOU_VERSION' ))
	die('Access denied.');

// Filter the request to just give posts for the given taxonomy, if applicable.
add_action( 'restrict_manage_posts', 'lecjou_filterpostsgui' );
function lecjou_filterpostsgui() {
	global $typenow;

	// If you only want this to work for your specific post type,
	// check for that $type here and then return.
	// This function, if unmodified, will add the dropdown for each
	// post type / taxonomy combination.

	$post_types = get_post_types( array( '_builtin' => false ) );

	if ( in_array( $typenow, $post_types ) ) {
		$filters = get_object_taxonomies( $typenow );

		foreach ( $filters as $tax_slug ) {
			$tax_obj = get_taxonomy( $tax_slug );
			wp_dropdown_categories( array(
				'show_option_all' => __('Show All '.$tax_obj->label ),
				'taxonomy' 	  => $tax_slug,
				'name' 		  => $tax_obj->name,
				'hide_empty'  => false, 
				'orderby' 	  => 'name',
				'selected' 	  => $_GET[$tax_slug],
				'hierarchical' 	  => 0,
				'show_count' 	  => false
			) );
		}
	}
}

// filter posts
add_filter( 'parse_query', 'lecjou_filterposts' );
function lecjou_filterposts( $query ) {
	global $pagenow, $typenow;
	if ( 'edit.php' == $pagenow ) {
		$filters = get_object_taxonomies( $typenow );
		foreach ( $filters as $tax_slug ) {
			$var = &$query->query_vars[$tax_slug];
			if ( isset( $var ) ) {
				$term = get_term_by( 'id', $var, $tax_slug );
				$var = $term->slug;
			}
		}
	}
}

// display columns
add_filter( "manage_lecture_posts_columns", "lecjou_changecolumns" );
function lecjou_changecolumns( $cols ) {
	// hack, inject some style to hide quick edit
	echo '<style>.row-actions .inline {display:none}</style>';

	$cols['class'] = __( 'Class', 'trans' );

	return $cols;
}

// display column data
add_action( "manage_posts_custom_column", "lecjou_customcolums", 10, 2 );
function lecjou_customcolums( $column, $post_id ) {
	switch ( $column ) {
		case "class":
			foreach (wp_get_post_terms($post_id, 'class') as $class)
				echo '<a href="'.get_term_link($class).'">'.$class->name.'</a>';
			break;
	}
}