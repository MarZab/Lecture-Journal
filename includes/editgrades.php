<?php
/*
	Edit grades
*/

if (!defined ( 'LECJOU_VERSION' ))
	die('Access denied.');

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
	if (!isset($_GET['student']) || !preg_match('#^[a-ž0-9\x20]+$#i', $_GET['student'])) wp_die( __('You do not have permission to access this page.').' 3' );
	
	// locate student in DB
	$student = FALSE;
	foreach (get_metadata($taxonomy, $class->term_id, 'students', false) as $s) {
		if ($s['name'] == $_GET['student']) {
			$student = $s;
			break;
		}
	}
	if ($student === FALSE) wp_die( __('Student Can Not be Found.').' 4' );
	
	$fields  = get_metadata($taxonomy, $class->term_id, 'fields', true);
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

	// check for edits from POST
	if (isset($_POST['students']) && is_array($_POST['students'])) {
		$poststudent = $_POST['students'][0];
		if ( $student['name'] === $poststudent['name'] ) {
			$newstudent = $student;
			foreach ($studentsfields as $field => $name)
				$newstudent[$field] = $poststudent[$field];
				
			// update metadata for student
			if (!update_metadata ($taxonomy, $class->term_id, 'students', $newstudent, $student )) wp_die( __('Error saving data.').' 5' );
			
			$student = $newstudent;
		} else wp_die( __('You do not have permission to access this page.').' 4' );
	}
	
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