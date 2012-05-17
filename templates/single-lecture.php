<?php

// Template for a single lecture
// for TwentyEleven

// lecture custom fields
$customfields = array(
	'number' => __( 'Lecture #', 'lecjou' ),
	'unit' => __( 'Unit', 'lecjou' ),
	'topics' => __( 'Topic', 'lecjou' ),
	'textbook' => __( 'Textbook Page', 'lecjou' ),
	'workbook' => __( 'Workbook Page', 'lecjou' ),
	'homework' => __( 'Homework Page', 'lecjou' ),
);

// proper name of lecturer, given his login
function getLecturer( $login ) {
	$user = get_user_by('login', $login);
	return $user->display_name;
}

// print class and lecturers
function printClassLecturers($class, $lecturers){
	echo __( 'This lecture is part of ', 'lecjou' ),'<br /><a href="',get_term_link($class),'">',$class->name,'</a><br />';

	// "this, this and this."
	$i = count($lecturers);
	if ($i > 0) {
		echo __( 'Held by ', 'lecjou' );
		for($i--;$i > 1;$i--) {
			echo getLecturer($lecturers[$i]['user_login']).', ';
		}
		if ( $i > 0 ) echo getLecturer($lecturers[1]['user_login']),__( ' and ', 'lecjou') ;
		echo getLecturer($lecturers[0]['user_login']).'.';
	}
}

// Print header
get_header();

if (have_posts()) {
	the_post();
	
	// get the class
	$class = wp_get_object_terms($post->ID, 'class');

	if (count($class) > 0) {
		$class = $class[0];

		$lecturers  = get_metadata('class', $class->term_id, 'lecturers', false);
		$students  = get_metadata('class', $class->term_id, 'students', false);
		$attendance = get_post_meta(get_the_ID(), 'lecjou_attendance', true );
		
		// get data from database
		$customfieldsdata = get_post_meta(get_the_ID(), 'lecjou_details', true );
?>
<div id="primary">
	<div id="content" role="main">
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		
			<aside>
				<p><?php printClassLecturers($class, $lecturers); ?></p>
				<h3><?php echo __( 'Details', 'lecjou' );  ?></h3>
				<table class="lecjou_details">
					<tbody>
<?php

// print custom fields
foreach ($customfields as $k => $f){
	if(isset($customfieldsdata[$k]) && $customfieldsdata[$k] != ''){
		echo '<tr><td>',$f,'</td><td class="lecjou_tr">',$customfieldsdata[$k],'</td></tr>';
	}
}
?>
					</tbody>
				</table>
				<h3><?php echo __( 'Attendance', 'lecjou' );  ?></h3>
				<table class="lecjou_attendance">
					<tbody>
<?php

// print attendance and homework
foreach ($attendance as $s){
	echo '<tr><td>',$s['name'],'</td><td class="lecjou_tr">',(isset($s['A'])?'A':'&nbsp;'),'</td><td class="lecjou_tr">',(isset($s['H'])?'H':'&nbsp;'),'</td></tr>';
}
?>
					</tbody>
				</table>
			</aside><!-- aside -->
		
			<header class="entry-header">
				<h1 class="entry-title"><?php the_title(); ?></h1>
			</header><!-- .entry-header -->
			<div class="entry-content">
				<?php the_content(); ?>
			</div><!-- .entry-content -->
		</article>

	</div><!-- #content -->
</div><!-- #primary -->
<?php 
	}
	else {
		// lecture MUST belong to a class
		echo '<p style="text-align:center">'.__( 'This lecture does not belong to a class.', 'lecjou' ).'</p>';
	}
}
else {
	// not found
	echo '<p style="text-align:center">'.__('Sorry, no posts matched your criteria.', 'lecjou').'</p>';
}  

get_footer();