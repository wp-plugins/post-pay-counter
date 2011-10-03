<?php

include_once( 'post-pay-counter-functions.php' );

class post_pay_counter_install_routine {
    
    //Initialize the installation and calls the real install procedure
    function post_pay_counter_install() {
        global $wpdb;
        
        //If working on a multisite blog
    	if ( function_exists( 'is_multisite' ) AND is_multisite() ) {
    		
            //If it is a network activation run the activation function for each blog id
    		if ( isset( $_GET['networkwide'] ) AND ( $_GET['networkwide'] == 1 ) ) {
    			//Get all blog ids; foreach them and call the install procedure on each of them
    			$blog_ids = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM ".$wpdb->blogs ) );
    			
                foreach ( $blog_ids as $blog_id ) {
    				switch_to_blog( $blog_id );
    				$this->post_pay_counter_install_procedure();
    			}
                
                //Go back to the main blog and return - so that if not multisite or not network activation, run the procedure once
    			restore_current_blog();
    			return;
    		}	
    	} 
    	$this->post_pay_counter_install_procedure();
    }
    
    //Called when creating a new blog on multiste - launch the install procedure on it either
    function post_pay_counter_new_blog_install( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
        global $wpdb;
        
        //If plugin was activated with network activation, install it also on the new site
    	if (is_plugin_active_for_network( basename( __DIR__ ).'/post-pay-counter.php' ) ) {
    		switch_to_blog($blog_id);
    		$this->post_pay_counter_install_procedure();
    		restore_current_blog();
    	}
    }
    
    //Install the plugin
    function post_pay_counter_install_procedure() {
        global $wpdb;
		
        //Here are the two arries of predefined options
        $predefined_settings = array( 
            'general' => array( 
                'userID'                                => 'general',
                'counting_type_words'                   => 1,
                'counting_type_visits'                  => 0,
                'counting_system_zones'                 => 0,
                'counting_system_unique_payment'        => 1,
                'zone1_count'                           => '200',
                'zone1_payment'                         => '2.00',
                'zone2_count'                           => '350',
                'zone2_payment'                         => '3.50',
                'zone3_count'                           => '500',
                'zone3_payment'                         => '5.00',
                'zone4_count'                           => '800',
                'zone4_payment'                         => '8.00',
                'zone5_count'                           => '1200',
                'zone5_payment'                         => '12.00',
                'unique_payment'                        => '0.01000',
                'bonus_comment_count'                   => '30',
                'bonus_comment_payment'                 => '0.50',
                'bonus_image_payment'                   => '0.10',
                'count_pending_revision_posts'          => 0,
                'count_visits_guests'                   => 1,
                'count_visits_registered'               => 1,
                'count_visits_authors'                  => 1,
                'count_visits_bots'                     => 0,
                'allow_payment_bonuses'                 => 0,
                'can_view_old_stats'                    => 1,
                'can_view_others_general_stats'         => 1,
                'can_view_others_detailed_stats'        => 1,
                'can_view_overall_stats'                => 1,
                'can_view_special_settings_countings'   => 1,
                'can_view_payment_bonuses'              => 0,
                'can_csv_export'                        => 1,
                'trial_auto'                            => 0,
                'trial_manual'                          => 1,
                'trial_period'                          => 20,
                'trial_period_posts'                    => 1,
                'trial_period_days'                     => 0,
                'trial_enable'                          => 0
            ),
            
            'trial' => array( 
                'userID'                                => 'trial',
                'counting_type_words'                   => 1,
                'counting_type_visits'                  => 0,
                'counting_system_zones'                 => 0,
                'counting_system_unique_payment'        => 1,
                'zone1_count'                           => '200',
                'zone1_payment'                         => '2.00',
                'zone2_count'                           => '350',
                'zone2_payment'                         => '3.50',
                'zone3_count'                           => '500',
                'zone3_payment'                         => '5.00',
                'zone4_count'                           => '800',
                'zone4_payment'                         => '8.00',
                'zone5_count'                           => '1200',
                'zone5_payment'                         => '12.00',
                'unique_payment'                        => '0.01000',
                'bonus_comment_count'                   => '30',
                'bonus_comment_payment'                 => '0.50',
                'bonus_image_payment'                   => '0.10',
                'count_pending_revision_posts'          => 0,
                'count_visits_guests'                   => 1,
                'count_visits_registered'               => 1,
                'count_visits_authors'                  => 1,
                'count_visits_bots'                     => 0,
                'allow_payment_bonuses'                 => 0,
                'can_view_old_stats'                    => 1,
                'can_view_others_general_stats'         => 1,
                'can_view_others_detailed_stats'        => 1,
                'can_view_overall_stats'                => 1,
                'can_view_special_settings_countings'   => 1,
                'can_view_payment_bonuses'              => 0,
                'can_csv_export'                        => 1,
                'trial_auto'                            => 0,
                'trial_manual'                          => 1,
                'trial_period'                          => 20,
                'trial_period_posts'                    => 1,
                'trial_period_days'                     => 0,
                'trial_enable'                          => 0
            )
        );
        
        
        //If it's somebody updating from <= 1.1.3, add the two payment bonuses columns
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'can_view_payment_bonuses'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `can_view_payment_bonuses` INT(1) NOT NULL DEFAULT '0' AFTER can_view_special_settings_countings" );
        }
        
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'allow_payment_bonuses'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `allow_payment_bonuses` INT(1) NOT NULL DEFAULT '0' AFTER count_visits_bots" );
        }
    
        //Alter table to allow post counting and create the new plugin's table
        $wpdb->query( "CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."post_pay_counter` (
            `ID` int(255) NOT NULL AUTO_INCREMENT,
            `userID` varchar(255) NOT NULL,
            `counting_type_words` int(1) NOT NULL DEFAULT '1',
            `counting_type_visits` int(1) NOT NULL DEFAULT '0',
            `counting_system_zones` int(1) NOT NULL DEFAULT '0',
            `counting_system_unique_payment` int(1) NOT NULL DEFAULT '1',
            `zone1_count` int(255) DEFAULT NULL,
            `zone1_payment` decimal(10,2) DEFAULT NULL,
            `zone2_count` int(255) DEFAULT NULL,
            `zone2_payment` decimal(10,2) DEFAULT NULL,
            `zone3_count` int(255) DEFAULT NULL,
            `zone3_payment` decimal(10,2) DEFAULT NULL,
            `zone4_count` int(255) DEFAULT NULL,
            `zone4_payment` decimal(10,2) DEFAULT NULL,
            `zone5_count` int(255) DEFAULT NULL,
            `zone5_payment` decimal(10,2) DEFAULT NULL,
            `unique_payment` decimal(10,5) DEFAULT NULL,
            `bonus_comment_count` int(255) DEFAULT NULL,
            `bonus_comment_payment` decimal(10,2) DEFAULT NULL,
            `bonus_image_payment` decimal(10,2) DEFAULT NULL,
            `count_pending_revision_posts` int(1) NOT NULL DEFAULT '0',
            `count_visits_guests` int(1) NOT NULL DEFAULT '1',
            `count_visits_registered` int(1) NOT NULL DEFAULT '1',
            `count_visits_authors` int(1) NOT NULL DEFAULT '1',
            `count_visits_bots` int(1) NOT NULL DEFAULT '0',
            `allow_payment_bonuses` INT(1) NOT NULL DEFAULT '0',
            `can_view_old_stats` int(1) NOT NULL DEFAULT '1',
            `can_view_others_general_stats` int(1) NOT NULL DEFAULT '1',
            `can_view_others_detailed_stats` int(1) NOT NULL DEFAULT '0',
            `can_view_overall_stats` int(1) NOT NULL DEFAULT '1',
            `can_view_special_settings_countings` int(1) NOT NULL DEFAULT '0',
            `can_view_payment_bonuses` INT(1) NOT NULL DEFAULT '0',
            `can_csv_export` int(1) NOT NULL DEFAULT '0',
            `paypal_address` varchar(255) DEFAULT NULL,
            `trial_auto` int(1) NOT NULL DEFAULT '0',
            `trial_manual` int(1) NOT NULL DEFAULT '1',
            `trial_period` int(10) NOT NULL DEFAULT '20',
            `trial_period_days` int(1) NOT NULL DEFAULT '0',
            `trial_period_posts` int(1) NOT NULL DEFAULT '1',
            `trial_enable` int(1) NOT NULL DEFAULT '0',
            PRIMARY KEY (`ID`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;" );
        
        $post_pay_counter_functions = new post_pay_counter_functions_class();
        
        //If there are no settings in the db yet, add the predefined ones (general + trial)
        if( ! is_object( @$post_pay_counter_functions->get_settings( 'general' ) ) ) {
            $wpdb->insert( $wpdb->prefix.'post_pay_counter', $predefined_settings['general'] );
        }
        if( ! is_object( @$post_pay_counter_functions->get_settings( 'trial' ) ) ) {
            $wpdb->insert( $wpdb->prefix.'post_pay_counter', $predefined_settings['trial'] );
        }
        
        //Add the needed columns to wp_posts if they do not exist
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->posts."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'post_pay_counter'" ) ) {
            $wpdb->query( "ALTER TABLE ".$wpdb->posts." ADD post_pay_counter INT( 15 ) NULL COMMENT 'Keeps track of payments dates (Post Pay Counter)'" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->posts."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'post_pay_counter_count'" ) ) {
            $wpdb->query( "ALTER TABLE ".$wpdb->posts." ADD post_pay_counter_count INT( 255 ) NULL COMMENT 'Keeps track of payments values (Post Pay Counter)'" );
        }
        
    }
}

?>