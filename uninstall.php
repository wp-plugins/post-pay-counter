<?php

//Uninstall must have been triggered by wordpress, otherwise exit
if( !defined( 'WP_UNINSTALL_PLUGIN' ) )
    exit;

global $wpdb;
        
//Delete plugin's columns in wp_posts and the plugin's custom table
//NOTE THAT UNISTALLING THE PLUGIN DELETES ALL THE SAVED SETTINGS
$wpdb->query( 'ALTER TABLE '.$wpdb->posts.' DROP post_pay_counter' );
$wpdb->query( 'ALTER TABLE '.$wpdb->posts.' DROP post_pay_counter_count' );
$wpdb->query( 'DROP TABLE '.$wpdb->prefix.'post_pay_counter' );

?>