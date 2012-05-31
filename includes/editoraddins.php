<?php
/*
	Editor Addins
*/

if (!defined ( 'LECJOU_VERSION' ))
	die('Access denied.');

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