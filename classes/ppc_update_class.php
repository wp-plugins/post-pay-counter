<?php

/**
 * @author Stefano Ottolenghi
 * @copyright 2013
 */

class PPC_update_class {
    
    /**
     * Walks through available blogs (maybe multisite) and calls the update procedure
     *
     * @access  public
     * @since   2.0.5
    */
    
    function update() {
        global $wpdb;
        
        if ( ! function_exists( 'is_plugin_active_for_network' ) )
            require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
        
		if( is_plugin_active_for_network( basename( dirname( dirname( __FILE__ ) ).'/post-pay-counter.php' ) ) ) {
			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM ".$wpdb->blogs );
			
            foreach( $blog_ids as $blog_id ) {
				switch_to_blog( $blog_id );
				self::update_exec();
			}
            
			restore_current_blog();
			return;
		}
        
    	self::update_exec();
    }
    
    /**
     * Runs update procedure.
     * 
     * Also updates current version option and pages permissions.
     *
     * @access  public
     * @since   2.0.5
    */
    
    function update_exec() {
        global $ppc_global_settings;
        
        $general_settings = PPC_general_functions::get_settings( 'general' );
		
		/* 
		 * Version 2.1.1 
		 */
		
		//Fixed: installation added personalized user settings in place of general ones
		if( $general_settings['userid'] != 'general' ) {
            delete_option( $ppc_global_settings['option_name'] );
            unset( $ppc_global_settings['general_settings'] );
            
			PPC_install_functions::ppc_install_procedure();
		}
		
		$general_settings = PPC_general_functions::get_settings( 'general' );
		
		PPC_general_functions::manage_cap_allowed_user_roles_plugin_pages( $general_settings['can_see_options_user_roles'], $general_settings['can_see_stats_user_roles'] );
		
        update_option( 'ppc_current_version', $ppc_global_settings['newest_version'] );
    }
}

?>