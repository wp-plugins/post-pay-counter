<?php

/**
 * @author Stefano Ottolenghi
 * @copyright 2013
 */

class PPC_save_options {
    
    /**
     * Saves the "Counting Settings" metabox options 
     *
     * @access  public
     * @since   2.0
     * @param   $settings array new settings
    */
    
    static function save_counting_settings( $settings ) {
        $current_settings = PPC_general_functions::get_settings( $settings['userid'] );
        $new_settings = array(
            'userid' => $settings['userid'], 'counting_allowed_post_statuses' => array( 'publish' => 1, 'future' => 0, 'pending' => 0 )
        );
        
        //Counting types (checkbox fields)
        $new_settings['basic_payment'] = @PPC_options_fields::get_checkbox_value( $settings['basic_payment'] );
        $new_settings['counting_words'] = @PPC_options_fields::get_checkbox_value( $settings['counting_words'] );
        $new_settings['counting_visits']  = @PPC_options_fields::get_checkbox_value( $settings['counting_visits'] );
        $new_settings['counting_images'] = @PPC_options_fields::get_checkbox_value( $settings['counting_images'] );
        $new_settings['counting_images_include_featured'] = @PPC_options_fields::get_checkbox_value( $settings['counting_images_include_featured'] );
        $new_settings['counting_comments'] = @PPC_options_fields::get_checkbox_value( $settings['counting_comments'] );
        $new_settings['counting_payment_only_when_total_threshold'] = @PPC_options_fields::get_checkbox_value( $settings['counting_payment_only_when_total_threshold'] );
        $new_settings['counting_exclude_quotations'] = @PPC_options_fields::get_checkbox_value( $settings['counting_exclude_quotations'] );
        
        if( @PPC_options_fields::get_checkbox_value( $settings['counting_count_pending_revision_posts'] ) ) {
            $new_settings['counting_allowed_post_statuses']['pending'] = 1;
        } 
        if( @PPC_options_fields::get_checkbox_value( $settings['counting_count_future_scheduled_posts'] ) ) {
            $new_settings['counting_allowed_post_statuses']['future'] = 1;
        }
        
        //Counting methods/systems (Radio fields)
        $counting_words_system = PPC_options_fields::get_radio_value( $settings['counting_words_system'], 'counting_words_system_zonal', 'counting_words_system_incremental' );
        $counting_visits_method = PPC_options_fields::get_radio_value( $settings['counting_visits_method'], 'counting_visits_google_analytics', 'counting_visits_postmeta' );
        $counting_visits_system = PPC_options_fields::get_radio_value( $settings['counting_visits_system'], 'counting_visits_system_zonal', 'counting_visits_system_incremental' );
        $counting_images_system = PPC_options_fields::get_radio_value( $settings['counting_images_system'], 'counting_images_system_zonal', 'counting_images_system_incremental' );
        $counting_comments_system = PPC_options_fields::get_radio_value( $settings['counting_comments_system'], 'counting_comments_system_zonal', 'counting_comments_system_incremental' ); 
        $new_settings = array_merge( $new_settings, $counting_words_system, $counting_visits_method, $counting_visits_system, $counting_images_system, $counting_comments_system );
        
        //Fields that need special attention (text)
        $new_settings['basic_payment_value'] = (float) str_replace( ',', '.', $settings['basic_payment_value'] );
        $new_settings['counting_words_system_incremental_value'] = (float) str_replace( ',', '.', $settings['counting_words_system_incremental_value'] );
        $new_settings['counting_words_threshold_max'] = (int) $settings['counting_words_threshold_max'];
        $new_settings['counting_visits_postmeta_value'] = $settings['counting_visits_postmeta_value'];
        $new_settings['counting_visits_system_incremental_value'] = (float) str_replace( ',', '.', $settings['counting_visits_system_incremental_value'] );
        $new_settings['counting_visits_threshold_max'] = (int) $settings['counting_visits_threshold_max'];
        $new_settings['counting_images_threshold_min'] = (int) $settings['counting_images_threshold_min'];
        $new_settings['counting_images_threshold_max'] = (int) $settings['counting_images_threshold_max'];
        $new_settings['counting_images_system_incremental_value'] = (float) str_replace( ',', '.', $settings['counting_images_system_incremental_value'] );
        $new_settings['counting_comments_threshold_min'] = (int) $settings['counting_comments_threshold_min'];
        $new_settings['counting_comments_threshold_max'] = (int) $settings['counting_comments_threshold_max'];
        $new_settings['counting_comments_system_incremental_value'] = (float) str_replace( ',', '.', $settings['counting_comments_system_incremental_value'] );
        $new_settings['counting_payment_total_threshold'] = (float) str_replace( ',', '.', $settings['counting_payment_total_threshold'] );
        
        foreach( $settings as $option => $value ) {
            
            //If option is a checkbox/radio already dealt with, skip it
            if( $value === NULL )
                continue;
            
            //Counting systems zones
            if( preg_match_all( '/words_([0-9]+)_zone_threshold/', $option, $matches ) === 1 AND $value != 0 ) {
                $new_settings['counting_words_system_zonal_value'][$matches[1][0]]['threshold'] = (int) $value;
            }
            if( preg_match_all( '/words_([0-9]+)_zone_payment/', $option, $matches ) === 1 AND $value != 0 ) {
                $new_settings['counting_words_system_zonal_value'][$matches[1][0]]['payment'] = (float) str_replace( ',', '.', $value );
            }
            if( preg_match_all( '/visits_([0-9]+)_zone_threshold/', $option, $matches ) === 1 AND $value != 0 ) {
                $new_settings['counting_visits_system_zonal_value'][$matches[1][0]]['threshold'] = (int) $value;
            }
            if( preg_match_all( '/visits_([0-9]+)_zone_payment/', $option, $matches ) === 1 AND $value != 0 ) {
                $new_settings['counting_visits_system_zonal_value'][$matches[1][0]]['payment'] = (float) str_replace( ',', '.', $value );
            }
            if( preg_match_all( '/images_([0-9]+)_zone_threshold/', $option, $matches ) === 1 AND $value != 0 ) {
                $new_settings['counting_images_system_zonal_value'][$matches[1][0]]['threshold'] = (int) $value;
            }
            if( preg_match_all( '/images_([0-9]+)_zone_payment/', $option, $matches ) === 1 AND $value != 0 ) {
                $new_settings['counting_images_system_zonal_value'][$matches[1][0]]['payment'] = (float) str_replace( ',', '.', $value );
            }
            if( preg_match_all( '/comments_([0-9]+)_zone_threshold/', $option, $matches ) === 1 AND $value != 0 ) {
                $new_settings['counting_comments_system_zonal_value'][$matches[1][0]]['threshold'] = (int) $value;
            }
            if( preg_match_all( '/comments_([0-9]+)_zone_payment/', $option, $matches ) === 1 AND $value != 0 ) {
                $new_settings['counting_comments_system_zonal_value'][$matches[1][0]]['payment'] = (float) str_replace( ',', '.', $value );
            }
            
        }
        
        $new_settings = apply_filters( 'ppc_save_counting_settings', $new_settings, $current_settings );
        $new = array_merge( $current_settings, $new_settings );
        
        $update = self::update_settings( $settings['userid'], $new );
        if( is_wp_error( $update ) ) {
            return $update;
        }
    }
    
    /**
     * Saves the "Misc Settings" metabox options 
     *
     * @access  public
     * @since   2.0
     * @param   $settings array new settings
    */
    
    static function save_misc_settings( $settings ) {
        $current_settings = PPC_general_functions::get_settings( 'general' );
        $new_settings = array( 'counting_allowed_post_types' => array(), 'counting_allowed_user_roles' => array(), 'can_see_options_user_roles' => array(), 'can_see_stats_user_roles' => array() );
        
        $default_stats_time_range = PPC_options_fields::get_radio_value( $settings['default_stats_time_range'], 'default_stats_time_range_month', 'default_stats_time_range_week', 'default_stats_time_range_custom' ); 
        $new_settings = array_merge( $new_settings, $default_stats_time_range );
        $new_settings['default_stats_time_range_custom_value'] = (int) $settings['default_stats_time_range_custom_value'];
        
        foreach( $settings as $option => $value ) {
            
            //If option is a checkbox/radio already dealt with, skip it
            if( $value === NULL )
                continue;
            
            if( strpos( $option, 'post_type_' ) === 0 ) {
                $new_settings['counting_allowed_post_types'][] = $value;
                continue;
            }
            if( strpos( $option, 'user_role_' ) === 0 ) {
                $new_settings['counting_allowed_user_roles'][$value] = $value;
                continue;
            }
            if( strpos( $option, 'can_see_options_user_roles_' ) === 0 ) {
                $new_settings['can_see_options_user_roles'][$value] = $value;
                continue;
            }
            if( strpos( $option, 'can_see_stats_user_roles_' ) === 0 ) {
                $new_settings['can_see_stats_user_roles'][$value] = $value;
                continue;
            }
        }
        
        $new_settings = apply_filters( 'ppc_save_misc_settings', $new_settings, $settings );
        $new = array_merge( $current_settings, $new_settings );
        
        $update = self::update_settings( 'general', $new );
        if( is_wp_error( $update ) ) {
            return $update;
        }
        
        //Update permissions
        PPC_general_functions::manage_cap_allowed_user_roles_plugin_pages( $new_settings['can_see_options_user_roles'], $new_settings['can_see_stats_user_roles'] );
    }
    
    /**
     * Saves the "Permissions" metabox options 
     *
     * @access  public
     * @since   2.0
     * @param   $settings array new settings
    */
    
    static function save_permissions( $settings ) {
        $current_settings = PPC_general_functions::get_settings( $settings['userid'] );
        $new_settings = array( 'userid' => $settings['userid'] );
        
        $new_settings['can_see_others_general_stats'] = @PPC_options_fields::get_checkbox_value( $settings['can_see_others_general_stats'] );
        $new_settings['can_see_others_detailed_stats'] = @PPC_options_fields::get_checkbox_value( $settings['can_see_others_detailed_stats'] );
        $new_settings['can_see_countings_special_settings'] = @PPC_options_fields::get_checkbox_value( $settings['can_see_countings_special_settings'] );
        
        $new_settings = apply_filters( 'ppc_save_permissions_settings', $new_settings, $settings );
        $new = array_merge( $current_settings, $new_settings );
        
        $update = self::update_settings( $settings['userid'], $new );
        if( is_wp_error( $update ) ) {
            return $update;
        }
    }
    
    /**
     * Stores the new settings in the database, depending on whether it's user settings or general. If general, also update global var 
     *
     * @access  public
     * @since   2.0
     * @param   string the userid of the to be updated item (general, trial, [int])
     * @param   $settings array the new settings
    */
    
    static function update_settings( $userid, $settings ) {
        global $ppc_global_settings;
        
        if( $settings == PPC_general_functions::get_settings( $settings['userid'] ) ) { return; } //avoid updating with same data, which would result in an error
        
        if( is_numeric( $userid ) ) {
            $settings['userid'] = (int) $settings['userid'];
            if( ! $update = update_user_option( $userid, $ppc_global_settings['option_name'], $settings ) ) {
                return new WP_Error( 'save_settings_error', __( 'Error: updating settings failed.' , 'post-pay-counter') );
            }
        } else if( $userid == 'general' ) {
            if( ! $update = update_option( $ppc_global_settings['option_name'], $settings ) ) {
                return new WP_Error( 'save_settings_error', __( 'Error: updating settings failed.' , 'post-pay-counter') );
            }
            $ppc_global_settings['general_settings'] = $settings;
        }
        
        do_action( 'ppc_settings_updated' );
    }
}
?>