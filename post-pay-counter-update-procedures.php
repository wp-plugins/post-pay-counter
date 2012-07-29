<?php

class post_pay_counter_update_procedures extends post_pay_counter_core {
    
    //Make the update procedure work for multisite: if plugin was netwowrk-wide-activated, run the update procedure for each blog
    function update() {
        global $wpdb;
        
        if ( ! function_exists( 'is_plugin_active_for_network' ) )
            require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
        
		if ( is_plugin_active_for_network( dirname( __FILE__ ).'/post-pay-counter.php' ) ) {
			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM ".$wpdb->blogs );
			
            foreach ( $blog_ids as $blog_id ) {
                //If plugin table does not exist, go to next blog
                if( ! $wpdb->query( 'SHOW TABLES FROM '.$wpdb->dbname.' LIKE "'.self::$post_pay_counter_db_table.'"' ) )
                    continue;
                
				switch_to_blog( $blog_id );
				self::update_exec();
			}
            
			restore_current_blog();
			return;
		} 
    	self::update_exec();
    }
    
    function update_exec() {
        global $wpdb;
        
        // ADD \\
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'allow_payment_bonuses'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `allow_payment_bonuses` INT(1) NOT NULL DEFAULT '0' AFTER count_visits_bots" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'minimum_fee_enable'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `minimum_fee_enable` INT(1) NOT NULL DEFAULT '0' AFTER unique_payment" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'minimum_fee_value'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `minimum_fee_value` decimal(10,5) NOT NULL DEFAULT '0' AFTER minimum_fee_enable" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'can_view_posts_word_count_post_list'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `can_view_posts_word_count_post_list` INT(1) NOT NULL DEFAULT '1'" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'can_view_overlay_counting_details'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `can_view_overlay_counting_details` INT(1) NOT NULL DEFAULT '1' AFTER can_view_posts_word_count_post_list" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'counting_type_visits_method_plugin'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `counting_type_visits_method_plugin` INT(1) NOT NULL DEFAULT '1' AFTER counting_type_visits" );
        }
        /* Google Analytics related stuff
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'counting_type_visits_method_google_analytics'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `counting_type_visits_method_google_analytics` INT(1) NOT NULL DEFAULT '1' AFTER counting_type_visits_method_plugin" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'counting_type_visits_method_google_analytics_email'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `counting_type_visits_method_google_analytics_email` VARCHAR(255) DEFAULT NULL AFTER counting_type_visits_method_google_analytics" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'counting_type_visits_method_google_analytics_password'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `counting_type_visits_method_google_analytics_password` VARCHAR(255) DEFAULT NULL AFTER counting_type_visits_method_google_analytics_email" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'counting_type_visits_method_google_analytics_profile_id'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `counting_type_visits_method_google_analytics_profile_id` INT(15) DEFAULT NULL AFTER counting_type_visits_method_google_analytics_password" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'counting_type_visits_method_google_analytics_pageviews'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `counting_type_visits_method_google_analytics_pageviews` INT(1) NOT NULL DEFAULT '1' AFTER counting_type_visits_method_google_analytics_profile_id" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'counting_type_visits_method_google_analytics_unique_pageviews'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `counting_type_visits_method_google_analytics_unique_pageviews` INT(1) NOT NULL DEFAULT '0' AFTER counting_type_visits_method_google_analytics_pageviews" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'counting_type_visits_method_google_analytics_update_request'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `counting_type_visits_method_google_analytics_update_request` INT(1) NOT NULL DEFAULT '0' AFTER counting_type_visits_method_google_analytics_unique_pageviews" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'counting_type_visits_method_google_analytics_update_hour'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `counting_type_visits_method_google_analytics_update_hour` INT(1) NOT NULL DEFAULT '1' AFTER counting_type_visits_method_google_analytics_update_request" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'counting_type_visits_method_google_analytics_update_day'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `counting_type_visits_method_google_analytics_update_day` INT(1) NOT NULL DEFAULT '0' AFTER counting_type_visits_method_google_analytics_update_hour" );
        } */
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'count_future_scheduled_posts'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `count_future_scheduled_posts` INT(1) NOT NULL DEFAULT '0' AFTER count_pending_revision_posts" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'exclude_quotations_from_countings'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `exclude_quotations_from_countings` INT(1) NOT NULL DEFAULT '0' AFTER count_future_scheduled_posts" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'publication_time_range_week'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `publication_time_range_week` INT(1) NOT NULL DEFAULT '0' AFTER count_visits_bots" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'publication_time_range_month'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `publication_time_range_month` INT(1) NOT NULL DEFAULT '1' AFTER publication_time_range_week" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'publication_time_range_custom'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `publication_time_range_custom` INT(1) NOT NULL DEFAULT '0' AFTER publication_time_range_month" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'publication_time_range_custom_value'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `publication_time_range_custom_value` INT(5) DEFAULT NULL AFTER publication_time_range_custom" );
        }
        //Visits time range stuff - Google Analytics related
        /*if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'visits_time_range_equal_to_pub'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `visits_time_range_equal_to_pub` INT(1) NOT NULL DEFAULT '0' AFTER publication_time_range_custom_value" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'visits_time_range_each_post_accordingly'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `visits_time_range_each_post_accordingly` INT(1) NOT NULL DEFAULT '1' AFTER visits_time_range_equal_to_pub" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'visits_time_range_each_post_accordingly_value'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `visits_time_range_each_post_accordingly_value` INT(5) DEFAULT '30' AFTER visits_time_range_each_post_accordingly" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'visits_time_range_rules_selection'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `visits_time_range_rules_selection` INT(1) NOT NULL DEFAULT '0' AFTER visits_time_range_each_post_accordingly_value" );
        }*/
        
        //1.3
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->posts."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'post_pay_counter_paid'" ) ) {
            $wpdb->query( "ALTER TABLE ".$wpdb->posts." ADD post_pay_counter_paid TEXT NULL COMMENT 'Post Pay Counter plugin paying dates and amounts tracking'" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'post_types_to_include_in_counting'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `post_types_to_include_in_counting` TEXT NULL AFTER count_visits_bots" );
            $wpdb->update( $wpdb->prefix."post_pay_counter", array( 'post_types_to_include_in_counting' => 'a:2:{i:0;s:4:"post";i:1;s:4:"page";}'), array( 'userID' => 'general' ) );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'user_roles_to_include_in_counting'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `user_roles_to_include_in_counting` TEXT NULL AFTER post_types_to_include_in_counting" );
            $wpdb->update( $wpdb->prefix."post_pay_counter", array( 'user_roles_to_include_in_counting' => 'a:5:{i:0;s:13:"administrator";i:1;s:6:"editor";i:2;s:6:"author";i:3;s:11:"contributor";i:4;s:10:"subscriber";}'), array( 'userID' => 'general' ) );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'can_view_paid_amount'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `can_view_paid_amount` INT(1) NOT NULL DEFAULT '1' AFTER can_view_overlay_counting_details" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'permission_options_page_user_roles'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `permission_options_page_user_roles` TEXT NOT NULL AFTER can_csv_export" );
            $wpdb->update( $wpdb->prefix."post_pay_counter", array( 'permission_options_page_user_roles' => 'a:2:{i:0;s:13:"administrator";i:1;s:6:"editor";}' ), array( 'userID' => 'general' ) );
        } else {
            $old_permission = unserialize( $wpdb->get_var( "SELECT permission_options_page_user_roles FROM ".parent::$post_pay_counter_db_table." WHERE userID = 'general'" ) );
            if( empty( $old_permission ) ) {
                $new_permission = array( 'administrator', 'editor' );
            } else {
                $new_permission = array();
                foreach( $old_permission as $single ) {
                    $new_permission[] = post_pay_counter_functions_class::lcfirst( $single );
                }
            }
            $wpdb->update( $wpdb->prefix."post_pay_counter", array( 'permission_options_page_user_roles' => serialize( $new_permission ) ), array( 'userID' => 'general' ) );
            $wpdb->update( $wpdb->prefix."post_pay_counter", array( 'permission_options_page_user_roles' => serialize( $new_permission ) ), array( 'userID' => 'trial' ) );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'permission_stats_page_user_roles'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `permission_stats_page_user_roles` TEXT NULL AFTER permission_options_page_user_roles" );
            $wpdb->update( $wpdb->prefix."post_pay_counter", array( 'permission_stats_page_user_roles' => 'a:5:{i:0;s:13:"administrator";i:1;s:6:"editor";i:2;s:6:"author";i:3;s:11:"contributor";i:4;s:10:"subscriber";}' ), array( 'userID' => 'general' ) );
        } else {
            $old_permission = unserialize( $wpdb->get_var( "SELECT permission_stats_page_user_roles FROM ".parent::$post_pay_counter_db_table." WHERE userID = 'general'" ) );
            if( empty( $old_permission ) ) {
                $new_permission = array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' );
            } else {
                $new_permission = array();
                foreach( $old_permission as $single ) {
                    $new_permission[] = post_pay_counter_functions_class::lcfirst( $single );
                }
            }
            $wpdb->update( $wpdb->prefix."post_pay_counter", array( 'permission_stats_page_user_roles' => serialize( $new_permission ) ), array( 'userID' => 'general' ) );
            $wpdb->update( $wpdb->prefix."post_pay_counter", array( 'permission_stats_page_user_roles' => serialize( $new_permission ) ), array( 'userID' => 'trial' ) );
        }
        
        //Move the ordinary zones system fields into a serialized array in database
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'ordinary_zones'" ) ) {
            
            //Add new field
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` ADD `ordinary_zones` TEXT NULL AFTER counting_system_unique_payment" );
            
            //Migrate
            $old_ordinary_zones = $wpdb->get_results( "SELECT zone1_count, zone2_count, zone3_count, zone4_count, zone5_count, zone1_payment, zone2_payment, zone3_payment, zone4_payment, zone5_payment
            FROM ".parent::$post_pay_counter_db_table."
            WHERE userID = 'general'", ARRAY_A );
            
            $new_ordinary_zones = array(
                1 => array(
                    'zone'      => $old_ordinary_zones[0]["zone1_count"],
                    'payment'   => $old_ordinary_zones[0]["zone1_payment"]
                ),
                2 => array(
                    'zone'      => $old_ordinary_zones[0]["zone2_count"],
                    'payment'   => $old_ordinary_zones[0]["zone2_payment"]
                ),
                3 => array(
                    'zone'      => $old_ordinary_zones[0]["zone3_count"],
                    'payment'   => $old_ordinary_zones[0]["zone3_payment"]
                ),
                4 => array(
                    'zone'      => $old_ordinary_zones[0]["zone4_count"],
                    'payment'   => $old_ordinary_zones[0]["zone4_payment"]
                ),
                5 => array(
                    'zone'      => $old_ordinary_zones[0]["zone5_count"],
                    'payment'   => $old_ordinary_zones[0]["zone5_payment"]
                )
            );
            
            $wpdb->update( $wpdb->prefix."post_pay_counter", array( "ordinary_zones" => serialize( $new_ordinary_zones ) ), array( "userID" => "general" ) );
        
            //Drop old columns
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` DROP `zone1_count`,
            DROP `zone2_count`,
            DROP `zone3_count`,
            DROP `zone4_count`,
            DROP `zone5_count`,
            DROP `zone1_payment`,
            DROP `zone2_payment`,
            DROP `zone3_payment`,
            DROP `zone4_payment`,
            DROP `zone5_payment`" );
        }
        
        // DROP \\
        if( $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'can_view_payment_bonuses'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` DROP `can_view_payment_bonuses`" );
        }
        if( $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'minimum_fee_only_when_zero'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` DROP `minimum_fee_only_when_zero`" );
        }
        if( $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'minimum_fee_only_when_zero_regardless_bonuses'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` DROP `minimum_fee_only_when_zero_regardless_bonuses`" );
        }
        if( $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'minimum_fee_always'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` DROP `minimum_fee_always`" );
        }
        
        //1.3
        if( $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".parent::$post_pay_counter_db_table."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'can_view_old_stats'" ) ) {
            $wpdb->query( "ALTER TABLE `".parent::$post_pay_counter_db_table."` DROP `can_view_old_stats`" );
        }
        
    }
    
}

?>