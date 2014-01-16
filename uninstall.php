<?php

/**
 * @author Stefano Ottolenghi
 * @copyright 2013
 */

//Uninstall must have been triggered by WordPress
if( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
    exit;

include_once( 'classes/ppc_general_functions_class.php' );

global $wpdb;

function ppc_uninstall_procedure() {
    global $wpdb;
    
    if( get_option( 'ppc_current_version' ) )
        delete_option( 'ppc_current_version' );
    
    if( get_option( 'ppc_install_error' ) )
        delete_option( 'ppc_install_error' );
    
    if( get_option( 'ppc_settings' ) )
        delete_option( 'ppc_settings' );
        
	$all_users = get_users( 'fields=ID' );
	foreach( $all_users as $user_id ) {
		delete_user_option( $user_id, 'ppc_settings' );
	}
    /*$all_posts = get_posts( array( 'fields' => 'ids', 'posts_per_page' => -1, 'post_type' => 'any' ) );
	foreach( $all_posts as $post_id ) {
		delete_post_meta( $post_id, 'ppc_payment_bonus' );
	}*/
}

//If working on a multisite blog, get all blog ids, foreach them and call the uninstall procedure on each of them
if( function_exists( 'is_multisite' ) AND is_multisite() ) {
    
	$blog_ids = $wpdb->get_col( 'SELECT blog_id FROM '.$wpdb->blogs );
    foreach( $blog_ids as $blog_id ) {
		switch_to_blog( $blog_id );
        ppc_uninstall_procedure();
	}
    
	restore_current_blog();
	return;
}
ppc_uninstall_procedure();

?>