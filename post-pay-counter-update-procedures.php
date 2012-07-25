<?php

class post_pay_counter_update_procedures {
    
    function __construct( $ppc_current_version, $ppc_newest_version ) {
        global $wpdb;
        
        // ADD \\
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'allow_payment_bonuses'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `allow_payment_bonuses` INT(1) NOT NULL DEFAULT '0' AFTER count_visits_bots" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'minimum_fee_enable'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `minimum_fee_enable` INT(1) NOT NULL DEFAULT '0' AFTER unique_payment" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'minimum_fee_value'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `minimum_fee_value` decimal(10,5) NOT NULL DEFAULT '0' AFTER minimum_fee_enable" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'can_view_posts_word_count_post_list'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `can_view_posts_word_count_post_list` INT(1) NOT NULL DEFAULT '1' AFTER can_view_payment_bonuses" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'can_view_overlay_counting_details'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `can_view_overlay_counting_details` INT(1) NOT NULL DEFAULT '1' AFTER can_view_posts_word_count_post_list" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'counting_type_visits_method_plugin'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `counting_type_visits_method_plugin` INT(1) NOT NULL DEFAULT '1' AFTER counting_type_visits" );
        }
        /* Google Analytics related stuff
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'counting_type_visits_method_google_analytics'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `counting_type_visits_method_google_analytics` INT(1) NOT NULL DEFAULT '1' AFTER counting_type_visits_method_plugin" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'counting_type_visits_method_google_analytics_email'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `counting_type_visits_method_google_analytics_email` VARCHAR(255) DEFAULT NULL AFTER counting_type_visits_method_google_analytics" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'counting_type_visits_method_google_analytics_password'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `counting_type_visits_method_google_analytics_password` VARCHAR(255) DEFAULT NULL AFTER counting_type_visits_method_google_analytics_email" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'counting_type_visits_method_google_analytics_profile_id'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `counting_type_visits_method_google_analytics_profile_id` INT(15) DEFAULT NULL AFTER counting_type_visits_method_google_analytics_password" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'counting_type_visits_method_google_analytics_pageviews'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `counting_type_visits_method_google_analytics_pageviews` INT(1) NOT NULL DEFAULT '1' AFTER counting_type_visits_method_google_analytics_profile_id" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'counting_type_visits_method_google_analytics_unique_pageviews'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `counting_type_visits_method_google_analytics_unique_pageviews` INT(1) NOT NULL DEFAULT '0' AFTER counting_type_visits_method_google_analytics_pageviews" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'counting_type_visits_method_google_analytics_update_request'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `counting_type_visits_method_google_analytics_update_request` INT(1) NOT NULL DEFAULT '0' AFTER counting_type_visits_method_google_analytics_unique_pageviews" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'counting_type_visits_method_google_analytics_update_hour'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `counting_type_visits_method_google_analytics_update_hour` INT(1) NOT NULL DEFAULT '1' AFTER counting_type_visits_method_google_analytics_update_request" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'counting_type_visits_method_google_analytics_update_day'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `counting_type_visits_method_google_analytics_update_day` INT(1) NOT NULL DEFAULT '0' AFTER counting_type_visits_method_google_analytics_update_hour" );
        } */
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'count_future_scheduled_posts'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `count_future_scheduled_posts` INT(1) NOT NULL DEFAULT '0' AFTER count_pending_revision_posts" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'exclude_quotations_from_countings'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `exclude_quotations_from_countings` INT(1) NOT NULL DEFAULT '0' AFTER count_future_scheduled_posts" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'publication_time_range_week'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `publication_time_range_week` INT(1) NOT NULL DEFAULT '0' AFTER count_visits_bots" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'publication_time_range_month'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `publication_time_range_month` INT(1) NOT NULL DEFAULT '1' AFTER publication_time_range_week" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'publication_time_range_custom'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `publication_time_range_custom` INT(1) NOT NULL DEFAULT '0' AFTER publication_time_range_month" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'publication_time_range_custom_value'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `publication_time_range_custom_value` INT(5) DEFAULT NULL AFTER publication_time_range_custom" );
        }
        //Visits time range stuff - Google Analytics related
        /*if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'visits_time_range_equal_to_pub'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `visits_time_range_equal_to_pub` INT(1) NOT NULL DEFAULT '0' AFTER publication_time_range_custom_value" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'visits_time_range_each_post_accordingly'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `visits_time_range_each_post_accordingly` INT(1) NOT NULL DEFAULT '1' AFTER visits_time_range_equal_to_pub" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'visits_time_range_each_post_accordingly_value'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `visits_time_range_each_post_accordingly_value` INT(5) DEFAULT '30' AFTER visits_time_range_each_post_accordingly" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'visits_time_range_rules_selection'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `visits_time_range_rules_selection` INT(1) NOT NULL DEFAULT '0' AFTER visits_time_range_each_post_accordingly_value" );
        }*/
        
        //1.3
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->posts."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'post_pay_counter_paid'" ) ) {
            $wpdb->query( "ALTER TABLE ".$wpdb->posts." ADD post_pay_counter_paid TEXT NULL COMMENT 'Post Pay Counter plugin paying dates and amounts tracking'" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'post_types_to_include_in_counting'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `post_types_to_include_in_counting` TEXT NULL AFTER count_visits_bots" );
            $wpdb->update( $wpdb->prefix."post_pay_counter", array( 'post_types_to_include_in_counting' => 'a:2:{i:0;s:4:"post";i:1;s:4:"page";}'), array( 'userID' => 'general' ) );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'user_roles_to_include_in_counting'" ) ) {
            $wpdb->update( $wpdb->prefix."post_pay_counter", array( 'user_roles_to_include_in_counting' => 'a:5:{i:0;s:13:"administrator";i:1;s:6:"editor";i:2;s:6:"author";i:3;s:11:"contributor";i:4;s:10:"subscriber";}'), array( 'userID' => 'general' ) );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'can_view_paid_amount'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `can_view_paid_amount` INT(1) NOT NULL DEFAULT '1' AFTER can_view_overlay_counting_details" );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'permission_options_page_user_roles'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `permission_options_page_user_roles` TEXT NOT NULL AFTER can_csv_export" );
            $wpdb->update( $wpdb->prefix."post_pay_counter", array( 'permission_options_page_user_roles' => 'a:2:{i:0;s:13:"Administrator";i:1;s:6:"Editor";}' ), array( 'userID' => 'general' ) );
        }
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'permission_stats_page_user_roles'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `permission_stats_page_user_roles` TEXT NULL AFTER permission_options_page_user_roles" );
            $wpdb->update( $wpdb->prefix."post_pay_counter", array( 'permission_stats_page_user_roles' => 'a:5:{i:0;s:13:"Administrator";i:1;s:6:"Editor";i:2;s:6:"Author";i:3;s:11:"Contributor";i:4;s:10:"Subscriber";}' ), array( 'userID' => 'general' ) );
        }
        
        //Move the ordinary zones system fields into a serialized array in database
        if( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'ordinary_zones'" ) ) {
            
            //Add new field
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` ADD `ordinary_zones` TEXT NULL AFTER counting_system_unique_payment" );
            
            //Migrate
            $old_ordinary_zones = $wpdb->get_results( "SELECT zone1_count, zone2_count, zone3_count, zone4_count, zone5_count, zone1_payment, zone2_payment, zone3_payment, zone4_payment, zone5_payment
            FROM ".$wpdb->prefix."post_pay_counter
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
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` DROP `zone1_count`,
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
        if( $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'can_view_payment_bonuses'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` DROP `can_view_payment_bonuses`" );
        }
        if( $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'minimum_fee_only_when_zero'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` DROP `minimum_fee_only_when_zero`" );
        }
        if( $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'minimum_fee_only_when_zero_regardless_bonuses'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` DROP `minimum_fee_only_when_zero_regardless_bonuses`" );
        }
        if( $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'minimum_fee_always'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` DROP `minimum_fee_always`" );
        }
        
        //1.3
        if( $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->prefix."post_pay_counter' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'can_view_old_stats'" ) ) {
            $wpdb->query( "ALTER TABLE `".$wpdb->prefix."post_pay_counter` DROP `can_view_old_stats`" );
        }
        
    }
    
}

?>