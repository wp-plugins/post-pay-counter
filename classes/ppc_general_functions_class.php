<?php

/**
 * @author Stefano Ottolenghi
 * @copyright 2013
 */

require_once( 'ppc_permissions_class.php' );

class PPC_general_functions {
    
    /**
     * Gets the cumulative settings for the requested user. Takes care to integrate general with current user/author ones
     * 
     * IF GENERAL SETTINGS ARE REQUESTED: if class var has settings, return that, otherwise get_site_option (so if network ones are available, those are got, otherwise blog-specific) - THIS is to 
     * make sure that we have some settings to base our further checks on. THEN check whether network settings should be got or not.
     * IF USER SETTINGS ARE REQUESTED: if a valid user + a user who has specific settings + (current_user can see spcial settings) is requested, get its user_meta. Store user settings in global var as caching.
     * IF NOTHING OF THE PREVIOUS IS MATCHED: return general settings.  
     *
     * @access  public
     * @since   2.0
     * @param   int the desired user id
     * @return  array the requested settings
    */

    static function get_settings( $userid, $check_current_user_cap_special = FALSE ) {
        global $current_user, $ppc_global_settings;
        
        if( $userid == 'general' ) {
            if( isset( $ppc_global_settings['general_settings'] ) ) {
                $return = $ppc_global_settings['general_settings'];
            } else {
                /*$temp_settings = get_site_option( $ppc_global_settings['general_options_name'] );
                if( ! $temp_settings ) {
                    $temp_settings = get_option( $ppc_global_settings['general_options_name'] );
                }
            
                if( $temp_settings['multisite_settings_rule'] == 1 ) {
                    $general_settings = get_site_option( $ppc_global_settings['general_options_name'] );;
                } else {*/
                    /*$general_settings = array();
                    foreach( $general_settings_options as $single ) {
                        $general_settings = array_merge( $general_settings, get_option( $single ) );
                    }*/
                    $return = get_option( $ppc_global_settings['option_name'] );
                //}
            }
            
        } else if( get_userdata( $userid ) AND $user_settings = get_user_option( $ppc_global_settings['option_name'], $userid ) ) {
            $perm = new PPC_permissions();
            
            if( $check_current_user_cap_special == TRUE AND ! $perm->can_see_countings_special_settings() ) {
                $userid = $current_user->ID;
            }
            
            if( isset( $ppc_global_settings['temp']['settings'][$userid] ) AND is_array( $ppc_global_settings['temp']['settings'][$userid] ) AND isset( $ppc_global_settings['temp']['user_settings_options'] ) AND $ppc_global_settings['temp']['user_settings_options'] == $user_settings_options ) {
                $return = $ppc_global_settings['temp']['settings'][$userid];
            } else {
                //if( get_user_option( array_rand( $user_settings_options ), $userid ) ) {
                    //$general_settings = self::get_settings( 'general' );
                
                if( $user_settings == false ) {
                    $user_settings = self::get_settings( 'general' );
                }
                
                    /*foreach( $general_settings as $key => $value ) {
                        if( isset( $user_settings[$key] ) ) {
                            $general_settings[$key] = $user_settings[$key];
                        }
                    }*/ 
                $ppc_global_settings['temp']['settings'][$user_settings['userid']] = $user_settings;
                $return = $user_settings;
                /*} else {
                    $ppc_global_settings['temp']['settings'][$user_settings['userid']] = self::get_settings( 'general' );
                    $return = self::get_settings( 'general' );
                }*/
            }
        } else {
            $return = self::get_settings( 'general' );
        }
        
        /*$ppc_global_settings['temp']['general_settings_options'] = $general_settings_options;
        $ppc_global_settings['temp']['user_settings_options'] = $user_settings_options;*/
        
        return apply_filters( 'ppc_settings', $return );
    }
    
    /**
     * Gets non capitalized input.
     * 
     * Grants compatibility with PHP < 5.3.
     *
     * @access  public
     * @since   2.0.9
     * @param   $string string to lowercase
     * @return  string lowercased
    */
	
	static function lcfirst( $string ) {
        if( function_exists( 'lcfirst' ) ) {
            return lcfirst( $string );
        } else {
            return (string) ( strtolower( substr( $string, 0, 1 ) ).substr( $string, 1 ) );
		}
    }
    
    /**
     * Determines the number of effective words for a given post content.
     * 
     * Trims blockquotes if requested; strip HTML tags (keeping their content). The regex basically reduces all kind of white spaces to one " ", trims punctuation and accounts a word as 
     * "some non-blank char(s) with a space before or after". Apostrophes count as spaces. Keep track of thresholds. 'to_count' holds the to be paid value (threshold) while 'real' the real value.
     *
     * @access  public
     * @since   2.0
     * @param   $post object the WP post object
     * @return  array the words data
    */
    
    static function count_post_words( $post ) {
        $settings = self::get_settings( $post->post_author, TRUE );
        
        $post_words = array( 
            'real' => 0, 
            'to_count' => 0 
        );
        
        if( $settings['counting_exclude_quotations'] ) {
            $post_content = preg_replace( '/<(blockquote|q)>.*<\/(blockquote|q)>/s', '', $post->post_content );
        }
        
        $post_words['real'] = (int) preg_match_all( '/\S+\s|\s\S+/', preg_replace( '/[.(),;:!?%#$¿"_+=\\/-]+/', '', preg_replace( '/\'&nbsp;|&#160;|\r|\n|\r\n|\s+/', ' ', strip_tags( $post->post_content ) ) ), $arr );
        
        if( $settings['counting_words_threshold_max'] > 0 AND $post_words['real'] > $settings['counting_words_threshold_max'] ) {
            $post_words['to_count'] = $settings['counting_words_threshold_max'];
        } else {
            $post_words['to_count'] = $post_words['real'];
        }
        
        return apply_filters( 'ppc_counted_post_words', $post_words );
    }
    
    /**
     * Determines the number of visits for a given post. 
     * 
     * Keeps track of thresholds. 'to_count' holds the to be paid value (threshold) while 'real' the real value.
     *
     * @access  public
     * @since   2.0
     * @param   object the WP post object
     * @return  array the words data
    */
    
    static function get_post_visits( $post ) {
        global $ppc_global_settings;
        $settings = self::get_settings( $post->post_author, TRUE );
        
        $post_visits = array( 
            'real' => 0, 
            'to_count' => 0 
        );
        
        $visits_postmeta = apply_filters( 'ppc_counting_visits_postmeta', $settings['counting_visits_postmeta_value'] );
        
        $post_visits['real'] = (int) get_post_meta( $post->ID, $visits_postmeta, TRUE );
        
        if( $settings['counting_visits_threshold_max'] > 0 AND $post_visits['real'] > $settings['counting_visits_threshold_max'] ) {
            $post_visits['to_count'] = $settings['counting_visits_threshold_max'];
        } else {
            $post_visits['to_count'] = $post_visits['real'];
        }
        
        return apply_filters( 'ppc_counted_post_visits', $post_visits );
    }
    
    /**
     * Determines the number of images for a given post. 
     * 
     * Keeps track of thresholds. Uses a regex. If requested, fetaured image is counted as well. 'to_count' holds the to be paid value (threshold) while 'real' the real value.
     *
     * @access  public
     * @since   2.0
     * @param   object the WP post object
     * @return  array the words data
    */
    
    static function count_post_images( $post ) {
        $settings = self::get_settings( $post->post_author, TRUE );
        
        $post_images = array( 
            'real' => 0, 
            'to_count' => 0 
        );
                
        if( preg_match_all( '/<img[^>]*>/', $post->post_content, $array_all_imgs ) ) {
            $post_images['real'] = (int) ( count( $array_all_imgs[0] ) - 1 );
        }
        
        if( $settings['counting_images_include_featured'] ) {
            if( has_post_thumbnail( $post->ID ) ) {
                ++$post_images['real'];
            }
        }
        
        if( $post_images['real'] <= $settings['counting_images_threshold_min'] ) {
            $post_images['to_count'] = 0;
        } else {
            if( $settings['counting_images_threshold_max'] == 0 AND $settings['counting_images_threshold_min'] == 0 ) { //If both upper and lower thresholds are 0, then no limit
                $post_images['to_count'] = $post_images['real'];
            } else if( $settings['counting_images_threshold_max'] > 0 AND $post_images['real'] > $settings['counting_images_threshold_max'] ) {
                $post_images['to_count'] = $settings['counting_images_threshold_max'] - $settings['counting_images_threshold_min'];
            } else {
                $post_images['to_count'] = $post_images['real'] - $settings['counting_images_threshold_min'];
            }
        }
        
        return apply_filters( 'ppc_counted_post_images', $post_images );
    }
    
    /**
     * Determines the number of comments for a given post. 
     * 
     * Keeps track of thresholds. 'to_count' holds the to be paid value (threshold) while 'real' the real value.
     *
     * @access  public
     * @since   2.0
     * @param   object the WP post object
     * @return  array the words data
    */
    
    static function get_post_comments( $post ) {
        $settings = self::get_settings( $post->post_author, TRUE );
        
        $post_comments = array( 
            'real' => (int) $post->comment_count, 
            'to_count' => 0 
        );
        
        if( $post_comments['real'] <= $settings['counting_comments_threshold_min'] ) {
            $post_comments['to_count'] = 0;
        } else {
            if( $settings['counting_comments_threshold_max'] == 0 AND $settings['counting_comments_threshold_min'] == 0 ) { //If both upper and lower thresholds are 0, then no limit
                $post_comments['to_count'] = $post_comments['real'];
            } else if( $settings['counting_comments_threshold_max'] > 0 AND $post_comments['real'] > $settings['counting_comments_threshold_max'] ) {
                $post_comments['to_count'] = $settings['counting_comments_threshold_max'] - $settings['counting_comments_threshold_min'];
            } else {
                $post_comments['to_count'] = $post_comments['real'] - $settings['counting_comments_threshold_min'];
            }
        }
        
        return apply_filters( 'ppc_counted_post_comments', $post_comments );
    }
    
    /**
     * Gets the link to the stats page of the requested author with the proper start and end time
     *
     * @access  public
     * @since   2.0
     * @param   $author_id int the author id
     * @return  string the link to their stats
    */
    
    static function get_the_author_link( $author_id ) {
        global $ppc_global_settings;
        
        return apply_filters( 'ppc_get_author_link', admin_url( $ppc_global_settings['stats_menu_link'].'&amp;author='.$author_id.'&amp;tstart='.$ppc_global_settings['stats_tstart'].'&amp;tend='.$ppc_global_settings['stats_tend'] ) );
    }
    
    /**
     * Makes sure each user role has or has not the requested capability to see options and stats pages. 
     * 
     * Called when updating settings and updating/installing.
     *
     * @access  public
     * @since   2.0.4
     * @param   $allowed_user_roles_options_page array user roles allowed to see plugin options
     * @param   $allowed_user_roles_stats_page array user roles allowed to see plugin stats
    */
    
    static function manage_cap_allowed_user_roles_plugin_pages( $allowed_user_roles_options_page, $allowed_user_roles_stats_page ) {
        global $wp_roles, $ppc_global_settings;
        
        if ( ! isset( $wp_roles ) )
            $wp_roles = new WP_Roles();
		
        $wp_roles_to_use = array();
        foreach( $wp_roles->role_names as $key => $value ) {
            $wp_roles_to_use[] = $key;
        }
        
        $allowed_user_roles_stats_page_add_cap       = array_intersect( $allowed_user_roles_stats_page, $wp_roles_to_use );
        $allowed_user_roles_stats_page_remove_cap    = array_diff( $wp_roles_to_use, $allowed_user_roles_stats_page );
        $allowed_user_roles_options_page_add_cap     = array_intersect( $allowed_user_roles_options_page, $wp_roles_to_use );
        $allowed_user_roles_options_page_remove_cap  = array_diff( $wp_roles_to_use, $allowed_user_roles_options_page );
        
        foreach( $allowed_user_roles_options_page_add_cap as $single ) {
            $current_role = get_role( self::lcfirst( $single ) );
            
            if( is_object( $current_role ) AND ! $current_role->has_cap( $ppc_global_settings['cap_manage_options'] ) ) {
                $current_role->add_cap( $ppc_global_settings['cap_manage_options'] );
            }
        }
        
        foreach( $allowed_user_roles_options_page_remove_cap as $single ) {
            $current_role = get_role( self::lcfirst( $single ) );
            
            if( is_object( $current_role ) AND $current_role->has_cap( $ppc_global_settings['cap_manage_options'] ) ) {
                $current_role->remove_cap( $ppc_global_settings['cap_manage_options'] );
            }
        }
        
        foreach( $allowed_user_roles_stats_page_add_cap as $single ) {
            $current_role = get_role( self::lcfirst( $single ) );
            
            if( is_object( $current_role ) AND ! $current_role->has_cap( $ppc_global_settings['cap_access_stats'] ) ) {
                $current_role->add_cap( $ppc_global_settings['cap_access_stats'] );
            }
        }
        
        foreach( $allowed_user_roles_stats_page_remove_cap as $single ) {
            $current_role = get_role( self::lcfirst( $single ) );
            
            if( is_object( $current_role ) AND $current_role->has_cap( $ppc_global_settings['cap_access_stats'] ) ) {
                $current_role->remove_cap( $ppc_global_settings['cap_access_stats'] );
            }
        }
    }
    
    /**
     * Defines default stats time range depending on chosen settings.
     * 
     * Stores settings in plugin's global var.
     *
     * @access  public
     * @since   2.1
     * @param   $settings array plugin settings
    */
    
    static function get_default_stats_time_range( $settings ) {
        global $ppc_global_settings;
        
        if( $settings['default_stats_time_range_week'] == 1 ) {
            $ppc_global_settings['stats_tstart'] = strtotime( '00:00:00' ) - ( ( date( 'N' )-1 )*24*60*60 );
        } else if( $settings['default_stats_time_range_month'] == 1 ) {
            $ppc_global_settings['stats_tstart'] = strtotime( '00:00:00' ) - ( ( date( 'j' )-1 )*24*60*60 );
        } else if( $settings['default_stats_time_range_custom'] == 1 ) {
            $ppc_global_settings['stats_tstart'] = strtotime( '00:00:00' ) - ( $settings['default_stats_time_range_custom_value']*24*60*60 );
        }
        
        $ppc_global_settings['stats_tend'] = time();
    }
}
?>