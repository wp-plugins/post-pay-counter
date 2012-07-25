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
    			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM ".$wpdb->blogs );
    			
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
    
    //Called when creating a new blog on multiste. If plugin was activated with a network-wide activation, activate and install it on the new blog too
    function post_pay_counter_new_blog_install( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
        global $wpdb;
        
    	if ( is_plugin_active_for_network( basename( __DIR__ ).'/post-pay-counter.php' ) ) {
    		switch_to_blog( $blog_id );
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
                'userID'                                                        => 'general',
                'counting_type_words'                                           => 1,
                'counting_type_visits'                                          => 0,
                'counting_type_visits_method_plugin'                            => 1,
                'counting_system_zones'                                         => 0,
                'counting_system_unique_payment'                                => 1,
                'ordinary_zones'                                                => 'a:5:{i:1;a:2:{s:4:"zone";i:100;s:7:"payment";d:1;}i:2;a:2:{s:4:"zone";i:300;s:7:"payment";d:3;}i:3;a:2:{s:4:"zone";i:500;s:7:"payment";d:5;}i:4;a:2:{s:4:"zone";i:700;s:7:"payment";d:7;}i:5;a:2:{s:4:"zone";i:900;s:7:"payment";d:9;}}',
                'add_five_more_zones'                                           => 0,
                'unique_payment'                                                => '0.01000',
                'minimum_fee_enable'                                            => 0,
                'minimum_fee_value'                                             => '0.00000',
                'bonus_comment_count'                                           => '30',
                'bonus_comment_payment'                                         => '0.50',
                'bonus_image_payment'                                           => '0.10',
                'count_pending_revision_posts'                                  => 0,
                'count_future_scheduled_posts'                                  => 0,
                'exclude_quotations_from_countings'                             => 0,
                'count_visits_guests'                                           => 1,
                'count_visits_registered'                                       => 1,
                'count_visits_authors'                                          => 1,
                'count_visits_bots'                                             => 0,
                'post_types_to_include_in_counting'                             => 'a:2:{i:0;s:4:"post";i:1;s:4:"page";}',
                'user_roles_to_include_in_counting'                             => 'a:5:{i:0;s:13:"administrator";i:1;s:6:"editor";i:2;s:6:"author";i:3;s:11:"contributor";i:4;s:10:"subscriber";}',
                'publication_time_range_week'                                   => 0,
                'publication_time_range_month'                                  => 1,
                'publication_time_range_custom'                                 => 0,
                'allow_payment_bonuses'                                         => 0,
                'can_view_others_general_stats'                                 => 1,
                'can_view_others_detailed_stats'                                => 1,
                'can_view_overall_stats'                                        => 1,
                'can_view_special_settings_countings'                           => 1,
                'can_view_overlay_counting_details'                             => 1,
                'can_view_paid_amount'                                          => 1,
                'can_view_posts_word_count_post_list'                           => 1,
                'can_csv_export'                                                => 1,
                'permission_options_page_user_roles'                            => 'a:2:{i:0;s:13:"Administrator";i:1;s:6:"Editor";}',
                'permission_stats_page_user_roles'                              => 'a:5:{i:0;s:13:"Administrator";i:1;s:6:"Editor";i:2;s:6:"Author";i:3;s:11:"Contributor";i:4;s:10:"Subscriber";}',
                'trial_auto'                                                    => 0,
                'trial_manual'                                                  => 1,
                'trial_period'                                                  => 20,
                'trial_period_posts'                                            => 1,
                'trial_period_days'                                             => 0,
                'trial_enable'                                                  => 0
            ),
            
            'trial' => array( 
                'userID'                                                        => 'trial',
                'counting_type_words'                                           => 1,
                'counting_type_visits'                                          => 0,
                'counting_type_visits_method_plugin'                            => 1,
                'counting_system_zones'                                         => 0,
                'counting_system_unique_payment'                                => 1,
                'ordinary_zones'                                                => 'a:5:{i:1;a:2:{s:4:"zone";i:100;s:7:"payment";d:1;}i:2;a:2:{s:4:"zone";i:300;s:7:"payment";d:3;}i:3;a:2:{s:4:"zone";i:500;s:7:"payment";d:5;}i:4;a:2:{s:4:"zone";i:700;s:7:"payment";d:7;}i:5;a:2:{s:4:"zone";i:900;s:7:"payment";d:9;}}',
                'add_five_more_zones'                                           => 0,
                'unique_payment'                                                => '0.01000',
                'minimum_fee_enable'                                            => 0,
                'minimum_fee_value'                                             => '0.00000',
                'bonus_comment_count'                                           => '30',
                'bonus_comment_payment'                                         => '0.50',
                'bonus_image_payment'                                           => '0.10',
                'count_pending_revision_posts'                                  => 0,
                'count_future_scheduled_posts'                                  => 0,
                'exclude_quotations_from_countings'                             => 0,
                'count_visits_guests'                                           => 1,
                'count_visits_registered'                                       => 1,
                'count_visits_authors'                                          => 1,
                'count_visits_bots'                                             => 0,
                'post_types_to_include_in_counting'                             => 'a:2:{i:0;s:4:"post";i:1;s:4:"page";}',
                'user_roles_to_include_in_counting'                             => 'a:5:{i:0;s:13:"administrator";i:1;s:6:"editor";i:2;s:6:"author";i:3;s:11:"contributor";i:4;s:10:"subscriber";}',
                'publication_time_range_week'                                   => 0,
                'publication_time_range_month'                                  => 1,
                'publication_time_range_custom'                                 => 0,
                'allow_payment_bonuses'                                         => 0,
                'can_view_others_general_stats'                                 => 1,
                'can_view_others_detailed_stats'                                => 1,
                'can_view_overall_stats'                                        => 1,
                'can_view_special_settings_countings'                           => 1,
                'can_view_overlay_counting_details'                             => 1,
                'can_view_paid_amount'                                          => 1,
                'can_view_posts_word_count_post_list'                           => 1,
                'can_csv_export'                                                => 1,
                'permission_options_page_user_roles'                            => 'a:2:{i:0;s:13:"Administrator";i:1;s:6:"Editor";}',
                'permission_stats_page_user_roles'                              => 'a:5:{i:0;s:13:"Administrator";i:1;s:6:"Editor";i:2;s:6:"Author";i:3;s:11:"Contributor";i:4;s:10:"Subscriber";}',
                'trial_auto'                                                    => 0,
                'trial_manual'                                                  => 1,
                'trial_period'                                                  => 20,
                'trial_period_posts'                                            => 1,
                'trial_period_days'                                             => 0,
                'trial_enable'                                                  => 0
            )
        );
        
        //Alter table to allow post counting and create the new plugin's table
        $wpdb->query( "CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."post_pay_counter` (
            `ID` int(255) NOT NULL AUTO_INCREMENT,
            `userID` varchar(255) NOT NULL,
            `counting_type_words` int(1) NOT NULL DEFAULT '1',
            `counting_type_visits` int(1) NOT NULL DEFAULT '0',
            `counting_type_visits_method_plugin` int(1) NOT NULL DEFAULT '1',
            `counting_system_zones` int(1) NOT NULL DEFAULT '0',
            `counting_system_unique_payment` int(1) NOT NULL DEFAULT '1',
            `ordinary_zones` TEXT NULL,
            `add_five_more_zones` int(1) NOT NULL DEFAULT '0',
            `unique_payment` decimal(10,5) DEFAULT NULL,
            `minimum_fee_enable` int(1) NOT NULL DEFAULT '0',
            `minimum_fee_value` decimal(10,5) NOT NULL DEFAULT '0.00000',
            `bonus_comment_count` int(255) DEFAULT NULL,
            `bonus_comment_payment` decimal(10,2) DEFAULT NULL,
            `bonus_image_payment` decimal(10,2) DEFAULT NULL,
            `count_pending_revision_posts` int(1) NOT NULL DEFAULT '0',
            `count_future_scheduled_posts` int(1) NOT NULL DEFAULT '1',
            `exclude_quotations_from_countings` int(1) NOT NULL DEFAULT '0',
            `count_visits_guests` int(1) NOT NULL DEFAULT '1',
            `count_visits_registered` int(1) NOT NULL DEFAULT '1',
            `count_visits_authors` int(1) NOT NULL DEFAULT '1',
            `count_visits_bots` int(1) NOT NULL DEFAULT '0',
            `post_types_to_include_in_counting` TEXT NULL,
            `user_roles_to_include_in_counting` TEXT NULL,
            `publication_time_range_week` int(1) NOT NULL DEFAULT '0',
            `publication_time_range_month` int(1) NOT NULL DEFAULT '1',
            `publication_time_range_custom` int(1) NOT NULL DEFAULT '0',
            `publication_time_range_custom_value` int(5) DEFAULT NULL,
            `allow_payment_bonuses` int(1) NOT NULL DEFAULT '0',
            `can_view_others_general_stats` int(1) NOT NULL DEFAULT '1',
            `can_view_others_detailed_stats` int(1) NOT NULL DEFAULT '0',
            `can_view_overall_stats` int(1) NOT NULL DEFAULT '1',
            `can_view_special_settings_countings` int(1) NOT NULL DEFAULT '0',
            `can_view_overlay_counting_details` int(1) NOT NULL DEFAULT '1',
            `can_view_paid_amount` int(1) NOT NULL DEFAULT '1',
            `can_view_posts_word_count_post_list` int(1) NOT NULL DEFAULT '1',
            `can_csv_export` int(1) NOT NULL DEFAULT '0',
            `permission_options_page_user_roles` TEXT NULL,
            `permission_stats_page_user_roles` TEXT NULL,
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
        
        //If there are no settings in the db yet, add the predefined ones (general + trial) and update the related vars
        if( ! is_object( @$post_pay_counter_functions->get_settings( 'general' ) ) ) {
            $wpdb->insert( $wpdb->prefix.'post_pay_counter', $predefined_settings['general'] );
        }
        if( ! is_object( @$post_pay_counter_functions->get_settings( 'trial' ) ) ) {
            $wpdb->insert( $wpdb->prefix.'post_pay_counter', $predefined_settings['trial'] );
        }
        $post_pay_counter_functions->options_changed_vars_update_to_reflect();
        
        //Add the needed columns to wp_posts if they do not exist
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->posts."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'post_pay_counter'" ) ) {
            $wpdb->query( "ALTER TABLE ".$wpdb->posts." ADD post_pay_counter INT(15) NULL COMMENT 'Keeps track of payments dates (Post Pay Counter)'" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->posts."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'post_pay_counter_count'" ) ) {
            $wpdb->query( "ALTER TABLE ".$wpdb->posts." ADD post_pay_counter_count INT(255) NULL COMMENT 'Keeps track of payments values (Post Pay Counter)'" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->posts."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'post_pay_counter_paid'" ) ) {
            $wpdb->query( "ALTER TABLE ".$wpdb->posts." ADD post_pay_counter_paid TEXT NULL COMMENT 'Post Pay Counter plugin paying dates and amounts tracking'" );
        }
        
        //Assign capabilities to default user roles for options and stats pages access permission
        $post_pay_counter_functions->define_allowed_user_roles_options_page( $post_pay_counter_functions->allowed_user_roles_options_page, $post_pay_counter_functions->allowed_user_roles_stats_page );
    }
}

?>