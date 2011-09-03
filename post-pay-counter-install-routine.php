<?php

include_once( 'post-pay-counter-functions.php' );

class post_pay_counter_install_routine {
    
    //Install the plugin. Unfortunately the first releases of the plugin weren't very well-thought, so we have to do many things to people who update 
    function post_pay_counter_install() {
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
                'can_view_old_stats'                    => 1,
                'can_view_others_general_stats'         => 1,
                'can_view_others_detailed_stats'        => 1,
                'can_view_overall_stats'                => 1,
                'can_view_special_settings_countings'   => 1,
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
                'can_view_old_stats'                    => 1,
                'can_view_others_general_stats'         => 1,
                'can_view_others_detailed_stats'        => 1,
                'can_view_overall_stats'                => 1,
                'can_view_special_settings_countings'   => 1,
                'can_csv_export'                        => 1,
                'trial_auto'                            => 0,
                'trial_manual'                          => 1,
                'trial_period'                          => 20,
                'trial_period_posts'                    => 1,
                'trial_period_days'                     => 0,
                'trial_enable'                          => 0
            )
        );
        
        
        /*//If it's somebody updating from <= 0.94...
        if( $updating_user = get_option( 'opt_counter' ) ) {
            
            //Get, explode and convert old option string in the new array-for-table var
            $opt_exploding          = explode( '^', $updating_user );
            $opt_exploding_again    = explode( 'e', $opt_exploding[1] );
            $n                      = 5;
            
            while( $n >= 0 ) {
            	$opt_exploding_zones[$n] = explode( '=', $opt_exploding_again[$n] );
            	--$n;
            }
            
            //Define new array values and start playing...
            if( $opt_exploding[0] == 'on' ) {
                $predefined_options['general']['count_pending_revision_posts']  = 1;
                $predefined_options['trial']['count_pending_revision_posts']    = 1;
            } else {
                $predefined_options['general']['count_pending_revision_posts']  = 0;
                $predefined_options['trial']['count_pending_revision_posts']    = 0;
            }
            
            $predefined_options['general']['zone1_count']           = $opt_exploding_zones[0][0];
            $predefined_options['trial']['zone1_count']             = $opt_exploding_zones[0][0];
            $predefined_options['general']['zone1_payment']         = $opt_exploding_zones[0][1];
            $predefined_options['trial']['zone1_payment']           = $opt_exploding_zones[0][1];
            $predefined_options['general']['zone2_count']           = $opt_exploding_zones[1][0];
            $predefined_options['trial']['zone2_count']             = $opt_exploding_zones[1][0];
            $predefined_options['general']['zone2_payment']         = $opt_exploding_zones[1][1];
            $predefined_options['trial']['zone2_payment']           = $opt_exploding_zones[1][1];
            $predefined_options['general']['zone3_count']           = $opt_exploding_zones[2][0];
            $predefined_options['trial']['zone3_count']             = $opt_exploding_zones[2][0];
            $predefined_options['general']['zone3_payment']         = $opt_exploding_zones[2][1];
            $predefined_options['trial']['zone3_payment']           = $opt_exploding_zones[2][1];
            $predefined_options['general']['zone4_count']           = $opt_exploding_zones[3][0];
            $predefined_options['trial']['zone4_count']             = $opt_exploding_zones[3][0];
            $predefined_options['general']['zone4_payment']         = $opt_exploding_zones[3][1];
            $predefined_options['trial']['zone4_payment']           = $opt_exploding_zones[3][1];
            $predefined_options['general']['zone5_count']           = $opt_exploding_zones[4][0];
            $predefined_options['trial']['zone5_count']             = $opt_exploding_zones[4][0];
            $predefined_options['general']['zone5_payment']         = $opt_exploding_zones[4][1];
            $predefined_options['trial']['zone5_payment']           = $opt_exploding_zones[4][1];
            $predefined_options['general']['bonus_comment_count']   = $opt_exploding_zones[5][0];
            $predefined_options['trial']['bonus_comment_count']     = $opt_exploding_zones[5][0];
            $predefined_options['general']['bonus_comment_payment'] = $opt_exploding_zones[5][1];
            $predefined_options['trial']['bonus_comment_payment']   = $opt_exploding_zones[5][1];
            
            //Delete old settings, now stored in the new array
            delete_option( 'opt_counter' );
            
            //As a security measure, delete all csv stats ever created
            while( false !== ( $file = readdir( opendir( __DIR__ ) ) ) ) {
                if( pathinfo( $file, PATHINFO_EXTENSION ) == 'csv' )
                    unlink( __DIR__.'\\'.$file );
            }
            
            //Delete the old 'monthly_post_counter' columns, we'll create the new ones later...
            if( $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->posts."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'monthly_post_counter'" ) ) {
                $wpdb->query( "ALTER TABLE ".$wpdb->posts." DROP 'monthly_post_counter' " );
            }
            if( $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->posts."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'monthly_post_counter_count'" ) ) {
                $wpdb->query( "ALTER TABLE ".$wpdb->posts." DROP 'monthly_post_counter_count' " );
            }
        }*/
    
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
            `can_view_old_stats` int(1) NOT NULL DEFAULT '1',
            `can_view_others_general_stats` int(1) NOT NULL DEFAULT '1',
            `can_view_others_detailed_stats` int(1) NOT NULL DEFAULT '0',
            `can_view_overall_stats` int(1) NOT NULL DEFAULT '1',
            `can_view_special_settings_countings` int(1) NOT NULL DEFAULT '0',
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
        
        //Populate the new columns with old posts data too (to do it update the general_settings var)
        $post_pay_counter_functions->general_settings = $post_pay_counter_functions->get_settings( 'general' );
        $post_pay_counter_functions->update_all_posts_count();
    }
}

?>