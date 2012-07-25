<?php

//Uninstall must have been triggered by WordPress, otherwise exit
if( !defined( 'WP_UNINSTALL_PLUGIN' ) )
    exit;

global $wpdb;

//Delete plugin's columns in wp_posts and the plugin's custom table
//NOTE THAT UNISTALLING THE PLUGIN DELETES ALL THE SAVED SETTINGS
function post_pay_counter_uninstall_procedure() {
    global $wpdb;
    
    if( $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->posts."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'post_pay_counter'" ) )
        $wpdb->query( 'ALTER TABLE '.$wpdb->posts.' DROP post_pay_counter' );
    
    if( $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->posts."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'post_pay_counter_count'" ) )
        $wpdb->query( 'ALTER TABLE '.$wpdb->posts.' DROP post_pay_counter_count' );
    
    if( $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->posts."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'post_pay_counter_paid'" ) )
        $wpdb->query( 'ALTER TABLE '.$wpdb->posts.' DROP post_pay_counter_paid' );
    
    if( $wpdb->query( 'SHOW TABLES FROM '.$wpdb->dbname.' LIKE "'.$wpdb->prefix.'post_pay_counter"' ) )
        $wpdb->query( 'DROP TABLE '.$wpdb->prefix.'post_pay_counter' );
    
    if( get_option( 'ppc_version' ) )
        delete_option( 'ppc_version' );
    
    /*if( get_option( 'ppc_ga_last_update' ) )
        delete_option( 'ppc_ga_last_update' );*/
    
    $post_payment_bonuses = get_posts( 'numberposts=-1&post_type=post&post_status=any' );
    foreach( $post_payment_bonuses as $single )
        delete_post_meta( $single->ID, 'payment_bonus' );
}

//If working on a multisite blog
if( function_exists( 'is_multisite' ) AND is_multisite() ) {
    
	//Get all blog ids; foreach them and call the uninstall procedure on each of them
	$blog_ids = $wpdb->get_col( 'SELECT blog_id FROM '.$wpdb->blogs );
	
    //Get all blog ids; foreach them and call the install procedure on each of them if the plugin table is found
    foreach( $blog_ids as $blog_id ) {
		switch_to_blog( $blog_id );
        post_pay_counter_uninstall_procedure();
	}
    
    //Go back to the main blog and return - so that if not multisite or not network activation, run the procedure once
	restore_current_blog();
	return;
}
post_pay_counter_uninstall_procedure();

?>