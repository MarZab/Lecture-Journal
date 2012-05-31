<?php
/*
	Reporting
*/

if (!defined ( 'LECJOU_VERSION' ))
	die('Access denied.');


// // get report data
function lecjou_reportdata ($lecturer) {
	if (!$lecturer) wp_die( __('Selected user does not exist.') );
	
	$data = array();
	/*
	$data[] = array (
		'datetime' => 'Date/Time',
		'name' => 'Name',
		'link' => 'Link',
		'class' => 'Class',
		'number' => 'Lecture #',
		'count' => 'Length',
		'unit' => 'Unit',
		'topics' => 'Topic',
		'substitute' => 'Substitute',
	);
	*/
	
	query_posts('author='.$lecturer->ID.'&showposts=-1&post_type=lecture');
	while (have_posts()) {
		the_post();
		$class = wp_get_object_terms(get_the_ID(), 'class');
		$customfieldsdata = get_post_meta(get_the_ID(), 'lecjou_details', true );
		
		$substitute = true;
		if (count($class)>0)
			foreach (get_metadata('class', $class[0]->term_id, 'lecturers', false) as $l) {
				if ($l['user_login'] == $lecturer->user_login) {
					$substitute = false;
					break;
				}
			}
		$data[] = array(
			'datetime' => get_the_time('Y-m-d H:i'),
			'name' => get_the_title(),
			'link' => get_permalink(),
			'class' => (count($class)>0) ? $class[0]->name : false,
			'number' => (isset($customfieldsdata['count'])) ? $customfieldsdata['count'] : false,
			'count' => (isset($customfieldsdata['count'])) ? $customfieldsdata['count'] : false,
			'unit' => (isset($customfieldsdata['unit'])) ? $customfieldsdata['unit'] : false,
			'topics' => (isset($customfieldsdata['topics'])) ? $customfieldsdata['topics'] : false,
			'substitute' => $substitute
		);
	}
	return $data;
}

// // export report ajax
function lecjou_report_ajax() {
	if (isset($_GET['lecturer']) && is_numeric($_GET['lecturer']) && current_user_can('add_users')) {
		// display report of another user
		$lecturer = get_userdata($_GET['lecturer']);
		if (!$lecturer) wp_die( __('Selected user does not exist.') );
	}
	else $lecturer = wp_get_current_user();
	
	header('Content-Encoding: UTF-8');
	header('Content-type: text/html; charset=UTF-8');
	header("Content-Disposition: attachment; filename=".$lecturer->user_nicename.".xls"); 

	echo '<html><meta http-equiv="Content-Type" content="text/html" charset="utf-8" /><table><tr><th>';
	
	echo implode( "</th><th>",
		array(
			'Class',
			'Date/Time',
			'Length',
			'Name',
			'Lecture #',
			'Unit',
			'Topic',
			'Substitute'
	));
	echo "</th></tr>";
	
	foreach (lecjou_reportdata($lecturer) as $row) {
		echo '<tr><td>';
		echo implode( '</td><td>',
			array(
				$row['class'],
				$row['datetime'],
				$row['count'],
				$row['name'],
				$row['number'],
				$row['unit'],
				$row['topics'],
				(($row['substitute'])? 'Yes' : '')
		));
		echo "</td></tr>";
	}
	echo "</table></html>";
	die();
}
add_action('wp_ajax_lecjou_report', 'lecjou_report_ajax');


// // report page
function lecjou_report() {
	if (isset($_GET['lecturer']) && is_numeric($_GET['lecturer']) && current_user_can('add_users')) {
		// display report of another user
		$lecturer = get_userdata($_GET['lecturer']);
		if (!$lecturer) wp_die( __('Selected user does not exist.') );
	}
	else $lecturer = wp_get_current_user();

	?>
<div class="wrap">
<div id="icon-edit" class="icon32 icon32-posts-lecture"><br></div><h2>Report for <?php echo $lecturer->display_name; ?> <a href="admin-ajax.php?action=lecjou_report&lecturer=<?php echo $lecturer->ID; ?>" class="add-new-h2">Download</a></h2>

<style>
#lecjou_report {width:100%; margin-top:20px; text-align:left}
#lecjou_report th {border-bottom:1px solid #aaa}
</style>
<table id="lecjou_report">
	<thead>
		<tr>
			<th>Class</th>
			<th>Date/Time</th>
			<th>Length</th>
			<th>Name</th>
			<th>Substitute</th>
		</tr>
	</thead>
	<tbody>
	
<?php 
	foreach (lecjou_reportdata($lecturer) as $row) {
	?>
		<tr>			
			<td><?php echo ($row['class']) ? $row['class'] : '&nbsp;'; ?></td>
			<td><?php echo $row['datetime']; ?></td> 
			<td><?php echo ($row['count']) ? $row['count'] : '&nbsp;'; ?></td>
			<td><a href="<?php echo $row['link']; ?>" > <?php echo $row['name']; ?></a></td>
			<td><?php echo ($row['substitute']) ? 'Yes' : '&nbsp;'; ?></td>
		</tr>
<?php } ?>
	</tbody>
</table>	
</div>
	<?php
}
add_action('admin_menu', 'lecjou_report_menu');
function lecjou_report_menu() {
	add_submenu_page('edit.php?post_type=lecture', 'Report', 'Report', 'read', 'lecjou_report', 'lecjou_report');
}

// // users page report links
// display columns
add_filter( "manage_users_columns", "lecjou_users_columns" );
function lecjou_users_columns( $cols ) {
	$cols['report'] = __( 'Report', 'lecjou' );
	unset($cols['posts']);
	return $cols;
}

// display column data
add_action( "manage_users_custom_column", "lecjou_users_columnsdata", 10, 3 );
function lecjou_users_columnsdata( $value, $column_name, $id ) {
  switch ( $column_name ) {
    case "report":
		return '<a href="edit.php?post_type=lecture&page=lecjou_report&lecturer='.$id.'">Report</a>'; 
    break;
  }
}