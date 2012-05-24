<?php
/*
Plugin Name: Lecture Journal
Plugin URI: http://zabreznik.net/lecture-journal
Description: Keep a journal on attendance and lecture details
Version: 0.2
Author: Marko Zabreznik
Author URI:	http://zabreznik.net
*/

define('LECJOU_VERSION', 0.4);
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

//  //  Class Addins

// class extra fields edit
add_action( 'class_edit_form_fields', 'lecjou_class_edit_form_fields', 10, 2);
function lecjou_class_edit_form_fields($tag,$taxonomy) {
	$school_year = get_metadata($taxonomy, $tag->term_id, 'school_year', true);
	$location = get_metadata($taxonomy, $tag->term_id, 'location', true);
	$startdate = get_metadata($taxonomy, $tag->term_id, 'startdate', true);
	$enddate  = get_metadata($taxonomy, $tag->term_id, 'enddate', true);

	$secret  = get_metadata($taxonomy, $tag->term_id, 'secret', true);

	foreach(get_metadata($taxonomy, $tag->term_id, 'lecturers', false) as $l) {
		$lecturers[] = $l['user_login'];
	}
	$lecturers = ($lecturers) ? implode(',',$lecturers) : '';

	$students  = get_metadata($taxonomy, $tag->term_id, 'students', false);

	// glej spodi k shranjuje
	$studentsfields = array(
		'name'  => __( 'Name', 'lecjou' ),
		'email' => __( 'E-mail', 'lecjou' ),
		'phone' => __( 'Phone', 'lecjou' ),
		'test1' => __( 'Test 1', 'lecjou' ),
		'test2' => __( 'Test 2', 'lecjou' ),
		'test3' => __( 'Test 3', 'lecjou' ),
		'test4' => __( 'Test 4', 'lecjou' ),
		'notes' => __( 'Notes', 'lecjou' ),
	);
?>
	<tr class="form-field">
		<th scope="row" valign="top"><label for="school_year">School Year</label></th>
		<td>
			<input type="text" name="school_year" id="school_year"
				value="<?php echo $school_year; ?>"/><br />
				<span class="description">The school year of this class.</span>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row" valign="top"><label for="location">Location</label></th>
		<td>
			<input type="text" name="location" id="location"
				value="<?php echo $location; ?>"/><br />
				<span class="description">Where this class will be held.</span>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row" valign="top"><label for="startdate">Start Date</label></th>
		<td>
			<input type="text" name="startdate" id="startdate"
				value="<?php echo $startdate; ?>"/><br />
				<span class="description">When this class will start.</span>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row" valign="top"><label for="enddate">End Date</label></th>
		<td>
			<input type="text" name="enddate" id="enddate"
				value="<?php echo $enddate; ?>"/><br />
				<span class="description">When this class will end.</span>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row" valign="top"><label for="lecturers">Lecturers</label></th>
		<td>
			<input type="text" name="lecturers" id="lecturers"
				value="<?php echo $lecturers; ?>"/><br />
				<span class="description">Who will lecture this class.</span>
				<script>
jQuery(document).ready(function() {
	jQuery('#lecturers').suggest("<?php echo get_bloginfo('wpurl'); ?>/wp-admin/admin-ajax.php?action=lecjou_lecturers",{multiple:true, multipleSep: ","});
});
				</script>
		</td>
	</tr>
	<tr class="form-field">
        <th scope="row" valign="top"><label for="secret">Class Access Secret</label></th>
        <td>
            <input type="text" name="secret" id="secret" 
                value="<?php echo $secret; ?>"/><br />
				<span class="description">Secret for this class. Change to recall all access links.</span>
        </td>
    </tr>
	<tr class="form-field">
		<th scope="row" valign="top"><label for="students">Students</label></th>
		<td>
<style>
#lecjou_students td {padding:0px}
.lecjou_student_delete {width:20px !important}
</style>
<table id="lecjou_students">
	<thead>
<?php
foreach ($studentsfields as $f)
	echo '<th>',$f,'</th>';
?>
		<th class="lecjou_student_delete">Delete</th>
	</thead>
	<tbody>
<?php
	$i = 0;
	foreach ($students as $student) {
		echo '<tr>';
		foreach ($studentsfields as $k => $f) {
			if ( $k == 'name'){
				echo '<td><input type="text" name="students['.$i.']['.$k.']" value="'.$student[$k].'" readonly="readonly"/></td>';
			}
			else{
				echo '<td><input type="text" name="students['.$i.']['.$k.']" value="'.$student[$k].'"/></td>';
			}
		}
		echo '<td class="lecjou_student_delete"><input type="checkbox" name="students['.$i.'][delete]" value=""/></td></tr>';
		$i++;
	}
	// print some empty fields
	for ( $j = 10; $j>0; $j--) {
		echo '<tr>';
		foreach ($studentsfields as $k => $f)
			echo '<td><input type="text" name="students['.$i.']['.$k.']" value=""/></td>';
		echo '</tr>';
		$i++;
	}

?>
	</tbody>
</table>
	<br />
	<span class="description">The students in this class.</span>
		</td>
	</tr>
	<?php
}
// class extra fields save
add_action( 'edited_class', 'lecjou_edited_class', 10, 2);
function lecjou_edited_class($term_id, $tt_id) {
	$taxonomy='class';
	if (!$term_id) return;

	if (isset($_POST['school_year']))
		update_metadata($taxonomy, $term_id, 'school_year',$_POST['school_year']);
	if (isset($_POST['location']))
		update_metadata($taxonomy, $term_id, 'location',$_POST['location']);
	if (isset($_POST['startdate']))
		update_metadata($taxonomy, $term_id, 'startdate',$_POST['startdate']);
	if (isset($_POST['enddate']))
		update_metadata($taxonomy, $term_id, 'enddate',$_POST['enddate']);
	if (isset($_POST['secret']) && $_POST['secret'] != '')
		update_metadata($taxonomy, $term_id, 'secret', $_POST['secret']);
	else
		update_metadata($taxonomy, $term_id, 'secret', uniqid());

	if (isset($_POST['lecturers'])) {
		delete_metadata($taxonomy, $term_id, 'lecturers' );
		foreach (explode(',',$_POST['lecturers']) as $lec) {
			$user = get_userdatabylogin(trim($lec));
			if($user){
				add_metadata($taxonomy, $term_id, 'lecturers', array('user_login'=>$user->user_login, 'display_name'=>$user->display_name), false);
			}
		}
	}

	if (isset($_POST['students'])) {
		delete_metadata($taxonomy, $term_id, 'students' );
		foreach ($_POST['students'] as $stu) {
			$stu['name'] = trim($stu['name']);
			if ($stu['name'] != '' && !isset($stu['delete']))
				add_metadata($taxonomy, $term_id, 'students', $stu, false);
		}
	}
}

// class manage columns
function lecjou_manage_class_columns($columns) {
	// hack, inject some style to hide quick edit
	echo '<style>.row-actions .inline {display:none} #col-right{float:none !important;width:auto !important}</style>';
	$columns['school_year'] = "School Year";
	$columns['lecturers'] = "Lecturers";
    $columns['secret'] = "Secret Link";
	return $columns;
}
// class manage columns custom column
function lecjou_manage_class_custom_column( $row_content, $column_name, $term_id ) {
	switch($column_name) {
		case 'lecturers':
			$r = '<ul><li>';
			foreach(get_metadata('class', $term_id, 'lecturers', false) as $l) {
				$lecturers[] = $l['display_name'];
			}
			if ($lecturers)
				$r.= implode('</li><li>',$lecturers);
			else
				return;
			return $r.'</li></ul>';
			break;
		case 'school_year':
			return get_metadata('class', $term_id, 'school_year', true);
			break;
		case 'secret':
			$link = get_term_link(intval($term_id), 'class');
			return sprintf('<a href="%s%ssecret=%s">Link</a>',
				$link,
				((strpos($link, '?'))?'&amp;':'?'),
				get_metadata('class', $term_id, 'secret', true)
			);
	}
}

// ajax suggest usernames
function lecjou_lecturers_ajax() {
	global $wpdb;
	$s = stripslashes( $_GET['q'] );

	if ( false !== strpos( $s, ',' ) ) {
		$s = explode( ',', $s );
		$s = $s[count( $s ) - 1];
	}
	$s = trim( $s );
	if ( strlen( $s ) < 2 )
		die; // require 2 chars for matching

	$results = $wpdb->get_col( $wpdb->prepare( "SELECT u.user_login FROM $wpdb->users as u WHERE u.user_login LIKE (%s)", '%' . like_escape( $s ) . '%' ) );

	echo join( $results, "\n" );
	die;
}
add_action('wp_ajax_lecjou_lecturers', 'lecjou_lecturers_ajax');


//  //  Editor Addins

// editor class selection meta box
add_action( 'add_meta_boxes', 'lecjou_add_custom_box' );
function lecjou_add_custom_box() {
    add_meta_box( 'lecjou_editor_details', __( 'Lecture Details', 'lecjou' ), 'lecjou_detailsbox', 'lecture', 'side' );
	add_meta_box( 'lecjou_editor_attendance', __( 'Lecture Attendance', 'lecjou' ), 'lecjou_attendancebox', 'lecture', 'normal' );
}

// remove classes metabox
function lecjou_remove_page_fields() {
	remove_meta_box( 'tagsdiv-class', 'lecture', 'side' );
}
add_action( 'admin_menu' , 'lecjou_remove_page_fields' );

// editor details box
function lecjou_detailsbox( $post ) {
	$classlist=get_terms('class', 'hide_empty=0');
	$classlistcurrent = wp_get_object_terms($post->ID, 'class');
	if (is_array($classlistcurrent))$classlistcurrent = $classlistcurrent[0]->term_id;
	else $classlistcurrent =-1;

	$customfields = array(
		'unit' => __( 'Unit', 'lecjou' ),
		'topics' => __( 'Topic', 'lecjou' ),
		'textbook' => __( 'Textbook Page', 'lecjou' ),
		'workbook' => __( 'Workbook Page', 'lecjou' ),
		'homework' => __( 'Homework Page', 'lecjou' ),
	);
	// get saved details
	$customfieldsdata = get_post_meta($post->ID, 'lecjou_details', true );

	echo '<style>.lecjou_details {width:100%} .lecjou_details_right input, .lecjou_details_right select { float:right} </style>
	<table class="lecjou_details"><tbody>';

	// class selection
	echo '<tr><td><label class="lecjou_details_label" for="lecjou_class">Class:</label></td><td class="lecjou_details_right"><select style="width: 162px;" name="lecjou_class"><option value=""></option>';
	foreach ($classlist as $class) {
		echo '<option value="'.$class->term_id.'"';
		if ($classlistcurrent ==$class->term_id) echo ' selected="selected"';
		echo '>'.$class->name.'</option>';
	}
	echo '</select></td></tr>';

	// number/len
	echo '<tr><td><label class="lecjou_details_label">',__( 'Num. / Len.', 'lecjou' ),'<label></td><td class="lecjou_details_right">';

	echo'<select style="width: 73px;" name="lecjou_details[count]"><option value="">';
	foreach ( array('1','2','3') as $n ) {
		echo '<option value="'.$n.'"';
		if ($customfieldsdata['count'] == $n) echo ' selected="selected"';
		echo '>'.$n.'h</option>';
	}

	echo '<input type="text" id="lecjou_details_number" name="lecjou_details[number]" value="';
	if (isset ($customfieldsdata['number'])) echo $customfieldsdata['number'];
	echo '" size="10" />';

	echo '</td></tr>';

	// other fields
	foreach ($customfields as $key => $field) {
		echo '<tr><td><label class="lecjou_details_label" for="lecjou_details[',$key,']">',$field,':</label></td><td class="lecjou_details_right"><textarea rows="2" cols="25" id="',$key,'" name="lecjou_details[',$key,']">';
		if (isset ($customfieldsdata[$key])) echo $customfieldsdata[$key];
		echo '</textarea></td></tr>';
	}
	echo '</tbody></table>';
}

// editor attendance box
function lecjou_attendancebox( $post ) {
	$class = wp_get_object_terms($post->ID, 'class');
	if (count($class) < 1) {
		echo '<p style="text-align:center">'.__( 'Select a Class above to set attendance.', 'lecjou' ).'</p>';
		return;
	}

	// get list of students
	$students  = get_metadata('class', $class[0]->term_id, 'students', false);

	// get old attendance data
	$attendance = get_post_meta($post->ID, 'lecjou_attendance', true );
	?>
<table id="lecjou_attendance" style="width:100%; text-align:center">
	<thead>
		<th>Name</th>
		<th>Attendance</th>
		<th>Homework</th>
		<th>E-mail</th>
		<th>Phone</th>
		<th>Note</th>
		<th>Grades</th>
	</thead>
	<tbody>
<?php
	$i = 0;
	foreach ($students as $student) {
		echo '<tr>';
		echo '<td>'.$student['name'].'<input type="hidden" name="students['.$i.'][name]" value="'.$student['name'].'"/></td>';
		echo '<td><input type="checkbox" name="students['.$i.'][A]" '.((isset($attendance[$student['name']]['A']))?'checked="checked"':'').'/></td>';
		echo '<td><input type="checkbox" name="students['.$i.'][H]" '.((isset($attendance[$student['name']]['H']))?'checked="checked"':'').'/></td>';
		echo '<td>'.(isset($student['email'])?$student['email']:'').'</td>';
		echo '<td>'.(isset($student['phone'])?$student['phone']:'').'</td>';
		echo '<td><input type="text" name="students['.$i.'][note]" value="'.((isset($attendance[$student['name']]['note']))?$attendance[$student['name']]['note']:'').'"/></td>';
		echo '<td><a href="post.php?page=lecjou_gradesedit&amp;class='.$class[0]->term_id.'&amp;student='.$student['name'].'"><img src="'.LECJOU_PLUGIN_URL .'/icons/grade.png" alt="Grade '.$student['name'].'" title="Grade '.$student['name'].'" ></a></td>';
		echo '</tr>';
		$i++;
	}
	?>
		</tbody>
		</table>
	<?php
}

// editor class selection meta box save content
add_action( 'save_post', 'lecjou_save_postdata' );
function lecjou_save_postdata( $post_id ) {
	// dont deal with autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
		return;

	// Check post type and permissions
	if ( 'lecture' != $_POST['post_type'] || !current_user_can( 'edit_post', $post_id ) ) 
		return;
	  
	// save details
	update_post_meta($post_id, 'lecjou_details', $_POST['lecjou_details'], false);
	
	// save class
	wp_set_object_terms( $post_id, intval($_POST['lecjou_class']), 'class', false );
	
	// save attendance
	$attendance = array();
	foreach ($_POST['students'] as $stu) {
		$attendance[$stu['name']] = $stu;
	}
	update_post_meta($post_id, 'lecjou_attendance', $attendance, false);
}

// List Addins

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

/*
	Edit grades page
*/
add_action('admin_menu', 'lecjou_gradesedit_menu');
function lecjou_gradesedit_menu() {
	add_submenu_page(null, 'Grades', 'Grades', 'read', 'lecjou_gradesedit', 'lecjou_gradesedit');
}
function lecjou_gradesedit() {
	$taxonomy = 'class';

	// get class from GET
	if (!isset($_GET['class']) || !is_numeric($_GET['class'])) wp_die( __('You do not have permission to access this page.').' 1' );
	$class = get_term_by('id',$_GET['class'],'class');
	if ($class === FALSE) wp_die( __('You do not have permission to access this page.').' 2' );
	
	
	// can current teacher edit this class or is admin
	if (!current_user_can('add_users')) {
		$u = wp_get_current_user();
		$lecturers = array();
		foreach(get_metadata($taxonomy, $class->term_id, 'lecturers', false) as $l) {
			$lecturers[] = $l['user_login'];
		}
		if (!in_array($u->user_login, $lecturers))
			wp_die( __('You do not have permission to access this page.') );
			
		unset($u,$lecturers);
	}
	
	// get student from GET
	if (!isset($_GET['student']) || !preg_match('#^[a-�0-9\x20]+$#i', $_GET['student'])) wp_die( __('You do not have permission to access this page.').' 3' );
	
	// locate student in DB
	$student = FALSE;
	foreach (get_metadata($taxonomy, $class->term_id, 'students', false) as $s) {
		if ($s['name'] == $_GET['student']) {
			$student = $s;
			break;
		}
	}
	if ($student === FALSE) wp_die( __('Student Can Not be Found.').' 4' );
	
	// check for edits from POST
	if (isset($_POST['students']) && is_array($_POST['students'])) {
		$poststudent = $_POST['students'][0];
		if ( $student['name'] === $poststudent['name'] ) {
			$newstudent = $student;
			foreach (array('test1','test2','test3','test4','notes') as $field)
				$newstudent[$field] = $poststudent[$field];
				
			// update metadata for student
			if (!update_metadata ($taxonomy, $class->term_id, 'students', $newstudent, $student )) wp_die( __('Error saving data.').' 5' );
			
			$student = $newstudent;
		} else wp_die( __('You do not have permission to access this page.').' 4' );
	}

	// display form
	$studentsfields = array(
		'name' => 	__( 'Name', 'lecjou' ),
		'email' => 	__( 'E-mail', 'lecjou' ),
		'phone' => 	__( 'Phone', 'lecjou' ),
		'test1' => 	__( 'Test 1', 'lecjou' ),
		'test2' =>	__( 'Test 2', 'lecjou' ),
		'test3' => 	__( 'Test 3', 'lecjou' ),
		'test4' => 	__( 'Test 4', 'lecjou' ),
		'notes' => 	__( 'Notes', 'lecjou' ),
	);
	
?>
	<style>
		#studentgradesform input {
			float:left;
			margin-bottom:10px
		}
		#studentgradesform label {
			width:50px; display:block; float:left; clear: left;
		}
		#studentgradesform, .studentgradesform p {
			margin:20px 0 0 40px;
		}
		#studentgradesformsubmit {
			margin-left:91px;
			clear:left;
			float:left;
		}
	</style>
	
	
	<div class="wrap studentgradesform">
		<div id="icon-edit" class="icon32 icon32-posts-lecture"><br></div><h2>Grade <strong><?php echo $student['name']; ?></strong></h2>
		<p>Class: <strong><?php echo $class->name; ?></strong></p>

		<form action="#" method="post">
			<ul id="studentgradesform">
<?php
	$i=0;
	foreach ($studentsfields as $k => $f) {
		echo '<li><label for="students['.$i.']['.$k.']">',$f,'</label>';
		if ( $k == 'name' || $k == 'email' || $k == 'phone' ){
			echo '<input type="text" name="students['.$i.']['.$k.']" value="'.$student[$k].'" readonly="readonly"/></li>';
		}
		else{
			echo '<input type="text" name="students['.$i.']['.$k.']" value="'.$student[$k].'"/></li>';
		}
	}
?>
			</ul>
			<input id="studentgradesformsubmit" type="submit" value="Submit" />
		</form>
	</div>
	<?php
}