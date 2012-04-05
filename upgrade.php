<?php
/*
	Lecture Journal
	Install/Update Script
	Version 1
*/

if (!defined ( 'LECJOU_VERSION' ) OR !is_super_admin() ) 
	die('Access denied.');

if ( get_site_option( 'Lecture-Journal DB Version' ) < LECJOU_DB_VERSION ) {
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	global $wpdb;
	
	$charset_collate = '';
	if (!empty ($wpdb->charset))
		$charset_collate .= "DEFAULT CHARACTER SET {$wpdb->charset}";
	if (!empty ($wpdb->collate))
		$charset_collate .= " COLLATE {$wpdb->collate}";
			
	$sql[] = "
CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."classmeta` (
meta_id bigint(20) NOT NULL AUTO_INCREMENT,
class_id bigint(20) NOT NULL default 0,
meta_key varchar(255) DEFAULT NULL,
meta_value longtext DEFAULT NULL,
UNIQUE KEY meta_id (meta_id)
) {$charset_collate};";
		
	dbDelta($sql);
	update_site_option( 'Lecture-Journal DB Version', LECJOU_DB_VERSION );
}
