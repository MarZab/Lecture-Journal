<?php 

// Template for a class (lecture list)
// for TwentyEleven

// Print header
get_header();

$class = get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) );

if (empty($class))
	die('Lecture Journal Error: Taxonomy Error');

global $wp_query;
$wp_query->set('posts_per_page','10000');
query_posts($wp_query->query_vars); 

?>
<section id="primary">
	<div id="content" role="main">
		<header class="page-header">
			<h1 class="entry-title"><?php echo $class->name; ?></h1>
		</header>
<?php
if (have_posts()) {
?>
		<table style="width:100%">
			<tbody>
<?php
while( have_posts() ) { 
	the_post();
?>
				<tr>
					<td class="lecjou_tl"><?php the_time('l, F jS, Y'); ?></td>
					<td><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></td>
				</tr>
<?php
}
?>
			</tbody>
		</table>
<?php
}
else {
	echo '<p style="text-align:center">Sorry, no posts matched your criteria.</p>';
}
?>
	</div><!-- #content -->
</section><!-- #primary -->
<?php

get_footer();