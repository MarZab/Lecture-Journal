<?php
/*
	Class Addins
*/

if (!defined ( 'LECJOU_VERSION' ))
	die('Access denied.');

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
	$fields  = get_metadata($taxonomy, $tag->term_id, 'fields', true);
	if (!$fields) {
		$fields = 'test1,test2,test3,test4,notes';
	}

	// glej spodi k shranjuje
	$studentsfields = array(
		'name'  => __( 'Name', 'lecjou' ),
		'email' => __( 'E-mail', 'lecjou' ),
		'phone' => __( 'Phone', 'lecjou' ),
	);
	foreach(array_map('trim',explode(",",$fields)) as $field) {
		$studentsfields[$field] = __( ucwords($field), 'lecjou' );
	}
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
        <th scope="row" valign="top"><label for="fields">Class Student Fields</label></th>
        <td>
            <input type="text" name="fields" id="fields"
                value="<?php echo $fields; ?>"/><br />
				<span class="description">Fields for grading students, see below. ( WILL REMOVE DATA :) )</span>
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
	if (isset($_POST['fields']))
		update_metadata($taxonomy, $term_id, 'fields',$_POST['fields']);

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