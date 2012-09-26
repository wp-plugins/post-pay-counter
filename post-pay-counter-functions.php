<?php

class post_pay_counter_functions_class extends post_pay_counter_core {
    
    //Define a string of allowed status to use into queries and data selection routines. Need to be a function itself due to stats regenerate after settings update
    function define_allowed_status() {
        parent::$allowed_status = '"publish"';
        if( isset( parent::$general_settings->count_future_scheduled_posts ) AND parent::$general_settings->count_future_scheduled_posts == 1 )
            parent::$allowed_status .= ',"future"';
        if( isset( parent::$general_settings->count_pending_revision_posts ) AND parent::$general_settings->count_pending_revision_posts == 1 )
            parent::$allowed_status .= ',"pending"';
    }
    
    //Turn the unserialized array of allowed post types into a string to use into queries. Need to be a function itself due to stats regenerate after settings update
    function define_allowed_post_types() {
        if( isset( parent::$general_settings->post_types_to_include_in_counting ) AND strlen( parent::$general_settings->post_types_to_include_in_counting ) > 0 )
            parent::$allowed_post_types = '"'.@implode( '","', unserialize( parent::$general_settings->post_types_to_include_in_counting ) ).'"';
    }
    
    //Turn the unserialized array of allowed post types into a string to use into queries. Need to be a function itself due to stats regenerate after settings update
    function define_allowed_user_roles() {
        if( isset( parent::$general_settings->user_roles_to_include_in_counting ) AND strlen( parent::$general_settings->user_roles_to_include_in_counting ) > 0 )
            parent::$allowed_user_roles = @implode( '|', unserialize( parent::$general_settings->user_roles_to_include_in_counting ) );
    }
    
    //Provides unserialized arrays of zones
    function unserialize_zones() {
        if( isset( parent::$general_settings->ordinary_zones ) AND strlen( parent::$general_settings->ordinary_zones ) > 0 )
            parent::$ordinary_zones = unserialize( parent::$general_settings->ordinary_zones );
    }
    
    //Provides unserialized arrays of user roles allowed to view options page
    function define_allowed_user_roles_options_page() {
        if( isset( parent::$general_settings->permission_options_page_user_roles ) AND strlen( parent::$general_settings->permission_options_page_user_roles ) > 0 )
            parent::$allowed_user_roles_options_page = unserialize( parent::$general_settings->permission_options_page_user_roles );
    }
    
    //Provides unserialized arrays of user roles allowed to view stats page
    function define_allowed_user_roles_stats_page() {
        if( isset( parent::$general_settings->permission_stats_page_user_roles ) AND strlen( parent::$general_settings->permission_stats_page_user_roles ) > 0 )
            parent::$allowed_user_roles_stats_page = unserialize( parent::$general_settings->permission_stats_page_user_roles );
    }
    
    function options_changed_vars_update_to_reflect( $get_settings = FALSE ) {
        
        //Update general_settings var only if expressly requested
        if( $get_settings == TRUE )
            parent::$general_settings = self::get_settings( 'general' );
        
        self::define_allowed_post_types();
        self::define_allowed_status();
        self::define_allowed_user_roles();
        self::unserialize_zones();
        self::define_allowed_user_roles_options_page();
        self::define_allowed_user_roles_stats_page();
    }
    
    //Makes sure each user role has or has not the requested capability to see options and stats pages. Called when updating settings and updating/installing.
    function manage_cap_allowed_user_groups_plugin_pages( $allowed_user_roles_options_page, $allowed_user_roles_stats_page ) {
        global $wp_roles;
        
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
            
            if( is_object( $current_role ) AND ! $current_role->has_cap( 'post_pay_counter_manage_options' ) )
                $current_role->add_cap( 'post_pay_counter_manage_options' );
        }
        foreach( $allowed_user_roles_options_page_remove_cap as $single ) {
            $current_role = get_role( self::lcfirst( $single ) );
            
            if( is_object( $current_role ) AND $current_role->has_cap( 'post_pay_counter_manage_options' ) )
                $current_role->remove_cap( 'post_pay_counter_manage_options' );
        }
        foreach( $allowed_user_roles_stats_page_add_cap as $single ) {
            $current_role = get_role( self::lcfirst( $single ) );
            
            if( is_object( $current_role ) AND ! $current_role->has_cap( 'post_pay_counter_access_stats' ) )
                $current_role->add_cap( 'post_pay_counter_access_stats' );
        }
        foreach( $allowed_user_roles_stats_page_remove_cap as $single ) {
            $current_role = get_role( self::lcfirst( $single ) );
            
            if( is_object( $current_role ) AND $current_role->has_cap( 'post_pay_counter_access_stats' ) )
                $current_role->remove_cap( 'post_pay_counter_access_stats' );
        }
    }
    
    function lcfirst( $string ) {
        if( function_exists( 'lcfirst' ) )
            return lcfirst( $string );
        else
            return (string) ( strtolower( substr( $string, 0, 1 ) ).substr( $string, 1 ) );
    }
    
    //Fix things like missing plugin table and default settings
    function fix_messed_up_stuff() {
        global $wpdb;
        
        if( ! $wpdb->query( 'SHOW TABLES FROM '.$wpdb->dbname.' LIKE "'.parent::$post_pay_counter_db_table.'"' ) )
            post_pay_counter_install_routine::post_pay_counter_create_table();
             
        if( ! is_object( parent::$general_settings ) OR ! is_object( self::get_settings( 'trial' ) ) ) {
            post_pay_counter_install_routine::post_pay_counter_insert_default_settings();
            self::options_changed_vars_update_to_reflect( TRUE );
            self::manage_cap_allowed_user_groups_plugin_pages( parent::$allowed_user_roles_options_page, parent::$allowed_user_roles_stats_page );
        }
        
        /*if( ( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->posts."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'post_pay_counter'" ) )
        OR ( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->posts."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'post_pay_counter_count'" ) )
        OR ( ! $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$wpdb->posts."' AND TABLE_SCHEMA = '".$wpdb->dbname."' AND COLUMN_NAME = 'post_pay_counter_paid'" ) ) )
            post_pay_counter_install_routine::add_wp_posts_plugin_columns();*/
    }
    
    //Select settings. Gets in one facoltative parameter, userID, and returns the counting settings as an object
    function get_settings( $user_id, $return_general = FALSE, $maybe_trial = TRUE ) {
        global $wpdb;
        
        //If requesting a valid user, check if we need trial settings or not
        if( is_numeric( $user_id ) AND $userdata = get_userdata( $user_id ) ) {
            
            //Select requested user's trial settings 
            $author_settings = $wpdb->get_row( 'SELECT trial_manual, trial_auto, trial_period_days, trial_period_posts, trial_period, trial_enable FROM '.parent::$post_pay_counter_db_table.' WHERE userID="'.$user_id.'"' );
            
            //If requested user doesn't have special settings, take general ones
            if( ! is_object( $author_settings ) )
                $author_settings = parent::$general_settings;
            
            //If should return trial settings when user is under trial...
            if( $maybe_trial ) {
                //If trial manual is selected and author trial is enabled
                if( $author_settings->trial_manual == 1 AND $author_settings->trial_enable == 1 ) {
                    $user_id = 'trial';
                
                //If trial auto is selected
                } else if( ( $author_settings->trial_auto == 1 ) ) {
                    
                    //If selected trial period is days, check the registered time against the current one - trial period
                    if( $author_settings->trial_period_days == 1 ) {
                        $registered_date_explode = explode( ' ', $userdata->user_registered );
                        if( ( time() - ( $author_settings->trial_period *24*60*60 ) ) < strtotime( $registered_date_explode[0] ) ) {
                            $user_id = 'trial';
                        }
                        
                    //Else if selected trial period is posts
                    } else if( $author_settings->trial_period_posts == 1 ) {
                        
                        //Count user's current posts
                        $user_current_posts = count_user_posts( $user_id );
                        
                        //If user doesn't have a high enough number of posts yet, get trial settings
                        if( $user_current_posts <= $author_settings->trial_period )
                            $user_id = 'trial';
                    }
                }
            }
        }
        
        //Query the database for user settings (where user could also be 'general' or 'trial')
        $counting_settings = $wpdb->get_row(
                              $wpdb->prepare( 'SELECT * FROM '.parent::$post_pay_counter_db_table.' WHERE userID = "'.$user_id.'"' )
                             );
        
        //If for some reason (like not having special settings for a particular user) special settings are not available, and $return_general is TRUE, return general ones
        if( ! is_object( $counting_settings ) AND $return_general == TRUE AND is_object( parent::$general_settings ) ) 
            $counting_settings = parent::$general_settings;
		
        return $counting_settings;
    }
    
    function update_exec() {
        //If current_version option does not exist or is DIFFERENT from the latest release number, launch the update procedures. If update is run, also update all the class variables and the option in the db
        if( ! ( parent::$ppc_current_version = get_option( 'ppc_current_version' ) ) OR parent::$ppc_current_version != parent::$ppc_newest_version ) {
            post_pay_counter_update_procedures::update();
            self::options_changed_vars_update_to_reflect( TRUE );
            self::manage_cap_allowed_user_groups_plugin_pages( parent::$allowed_user_roles_options_page, parent::$allowed_user_roles_stats_page );
            update_option( 'ppc_current_version', parent::$ppc_newest_version );
            parent::$ppc_current_version = parent::$ppc_newest_version;
            echo '<div id="message" class="updated fade"><p><strong>Post Pay Counter was successfully updated to version '.parent::$ppc_current_version.'.</strong> Want to have a look at the <a href="'.admin_url( parent::$post_pay_counter_options_menu_link ).'" title="Go to Options page">Options page</a>, or at the <a href="http://wordpress.org/extend/plugins/post-pay-counter/changelog/" title="Go to Changelog">Changelog</a>?</p></div>';
        }
    }
    
    //Generate stats function. Does queries and countings and returns an array of data
    function generate_stats( $author = false, $time_start = false, $time_end = false ) {
        global $wpdb,
               $current_user;
        
        //Select plugin settings of current user or, if unavailable, general settings
        $user_settings = self::get_settings( $current_user->ID, TRUE );
        
		if( ! is_array( $user_settings->ordinary_zones ) )
			$user_settings->ordinary_zones = unserialize( $user_settings->ordinary_zones );
        
        /** PERMISSION CHECK **/
        //Check if the requested user exists and if current user is allowed to see others' detailed stats
        if( $author ) {
            
            if( $current_user->ID != $author AND ( $user_settings->can_view_others_detailed_stats == 0 AND $current_user->user_level < 8 ) )
                return 'You are not authorized to view this page';
                
            if( ! get_userdata( $author ) )
                return 'The requested user does not exist.';
            
            $user_data = get_userdata( $author );
        }
        
        /** POST SELECT **/
        //If no post types are available, return
        if( parent::$allowed_post_types == '""' )
            return 'No posts were selected because no post types were chosen in the Options to be included in counting.';
           
        //If no user groups are available, return
        if( parent::$allowed_user_roles == '' )
            return 'No posts were selected because no user groups were chosen in the Options to be included in counting.';      
        
        //Query the database for posts from a defined author ...
        if( $author ) {
            $selected_posts = $wpdb->get_results( 
             $wpdb->prepare( 'SELECT ID, post_title, comment_count, post_status, post_date, post_type, post_pay_counter_count, post_pay_counter_paid 
                FROM '.$wpdb->posts.' INNER JOIN '.$wpdb->usermeta.' 
                    ON '.$wpdb->usermeta.'.user_id = '.$wpdb->posts.'.post_author 
                    WHERE '.$wpdb->usermeta.'.meta_key = "'.$wpdb->get_blog_prefix().'capabilities" 
                    AND '.$wpdb->usermeta.'.meta_value REGEXP ("'.parent::$allowed_user_roles.'") 
                    AND '.$wpdb->posts.'.post_author = "'.$author.'" 
                    AND '.$wpdb->posts.'.post_type IN ('.parent::$allowed_post_types.') 
                    AND '.$wpdb->posts.'.post_pay_counter BETWEEN '.$time_start.' AND '.$time_end.' 
                    AND '.$wpdb->posts.'.post_status IN ('.parent::$allowed_status.') 
                ORDER BY '.$wpdb->posts.'.post_date DESC' )
            );
        
        //Or, query the database for posts from any author ...
        } else {
            $selected_posts = $wpdb->get_results( 
             $wpdb->prepare( 'SELECT ID, post_title, comment_count, post_status, post_author, post_date, post_pay_counter_count 
                FROM '.$wpdb->posts.' WHERE '.$wpdb->posts.'.post_type IN ('.parent::$allowed_post_types.') 
                    AND '.$wpdb->posts.'.post_pay_counter BETWEEN '.$time_start.' AND '.$time_end.'
                    AND '.$wpdb->posts.'.post_status IN ('.parent::$allowed_status.')' )
            );
        }
               
        /** POST COUNTING ROUTINE **/
        //Return if no posts are selected
        if( $wpdb->num_rows == 0 )
            return 'No available stats for the requested time frame/author. Try changing the time frame from the fields above and then press <em>Update time range</em>. You may also want to Regenerate Countings from the Options page in order to view posts since blog started.';
        
        $ids_to_request = array();
        $stats_response = array();
        $totale         = array();
        $overall_stats  = array( 'total_payment' => 0.00, 'total_posts' => 0 );
        $total_posts    = 0;
        $total_payment  = 0;
        
        foreach( $selected_posts as $single ) {
            $ids_to_request[] = $single->ID;
        }
        
        $ids_content2cash = self::content2cash( $ids_to_request/*, $time_start, $time_end*/ );
        
        //If content2cash does not return any value, return
        if( count( $ids_content2cash ) == 0 )
            return 'No available stats for the requested time frame/author. Try changing the time frame from the fields above and then press <em>Update time range</em>. You may also want to Regenerate Countings from the Options page in order to view posts since blog started.';
        
        //If the post_author field is set, return _GENERAL STATS_ divided per author
        if( isset( $selected_posts[0]->post_author ) ) {
            
            foreach( $selected_posts as $single ) {
                
                //If current user can't and is not admin, don't show other authors' information
                if( $single->post_author != $current_user->ID AND ( $user_settings->can_view_others_general_stats == 0 AND $current_user->user_level < 8 ) )
                    continue;
                
                //If post was not returned by content2cash (probably because of invalid status), continue
                if( ! isset( $ids_content2cash[$single->ID] ) )
                    continue;
                
                //Create a multidimensional array divided for author's names. Using silence operator to avoid notices for non-existent variable
                @$totale[$single->post_author]['total_payment']     = sprintf( '%.2f', $totale[$single->post_author]['total_payment'] + $ids_content2cash[$single->ID]['total_payment'] );
                @$totale[$single->post_author]['ordinary_payment']  = sprintf( '%.2f', $totale[$single->post_author]['ordinary_payment'] + $ids_content2cash[$single->ID]['ordinary_payment'] );
                @$totale[$single->post_author]['minimum_fee']       = sprintf( '%.2f', $totale[$single->post_author]['minimum_fee'] + $ids_content2cash[$single->ID]['minimum_fee'] );
                @$totale[$single->post_author]['payment_bonus']     = sprintf( '%.2f', $totale[$single->post_author]['payment_bonus'] + $ids_content2cash[$single->ID]['payment_bonus'] );
                @$totale[$single->post_author]['image_bonus']       = sprintf( '%.2f', $totale[$single->post_author]['image_bonus'] + $ids_content2cash[$single->ID]['image_bonus'] );
                @$totale[$single->post_author]['comment_bonus']     = sprintf( '%.2f', $totale[$single->post_author]['comment_bonus'] + $ids_content2cash[$single->ID]['comment_bonus'] );
                @$totale[$single->post_author]['posts']++;
                
                //Overall stats
                @$overall_stats['total_payment']    = sprintf( '%.2f', $overall_stats['total_payment'] + $ids_content2cash[$single->ID]['total_payment'] );
                @$overall_stats['total_bonus']      = sprintf( '%.2f', $overall_stats['total_bonus'] + $ids_content2cash[$single->ID]['payment_bonus'] );
                @$overall_stats['total_counting']   = $overall_stats['total_counting'] + $ids_content2cash[$single->ID]['content_count'];
                @$overall_stats['total_posts']++;
                
                //If using zones_system, define the payment area the post fits in (overall stats)
                if( $user_settings->counting_system_zones == 1 ) {
                    
                    //If post is below lowest zone, mark it as such and go to next
                    if( $ids_content2cash[$single->ID]['content_count'] < $user_settings->ordinary_zones[1]['zone'] ) {
                        @$overall_stats['0zone']++;
                        continue;
                    }
                    
                    $n = 1;
                    while( $n <= 5 ) { //Run 5 times
                        //If post lies in the last available zone (the fifth), do not specify a roof for its counting
                        //I.e. it is not between x and y words but only "above x". Only if not using supplementary zones
                        if( $n == 5 AND count( $user_settings->ordinary_zones ) < 5 ) {
                            if( $ids_content2cash[$single->ID]['content_count'] >= $user_settings->ordinary_zones[$n]['zone'] ) {
                                @$overall_stats[$n.'zone']++;
                            }
                        } else {
                            if( $ids_content2cash[$single->ID]['content_count'] >= $user_settings->ordinary_zones[$n]['zone'] AND $ids_content2cash[$single->ID]['content_count'] < $user_settings->ordinary_zones[$n+1]['zone'] ) {
                                @$overall_stats[$n.'zone']++;
                            }
                        }
                    
                    ++$n;
                    }
                    
                    if( count( $user_settings->ordinary_zones ) > 5 ) {
                        while( $n <= 10 ) { //Run 5 more times
                            //If post lies in the last available zone (the fifth), do not specify a roof for its counting
                            //I.e. it is not between x and y words but only "above x".
                            if( $n == 10 ) {
                                if( $ids_content2cash[$single->ID]['content_count'] >= $user_settings->ordinary_zones[$n]['zone'] ) {
                                    @$overall_stats[$n.'zone']++;
                                }
                            } else {
                                if( $ids_content2cash[$single->ID]['content_count'] >= $user_settings->ordinary_zones[$n]['zone'] AND $ids_content2cash[$single->ID]['content_count'] < $user_settings->ordinary_zones[$n+1]['zone'] ) {
                                    @$overall_stats[$n.'zone']++;
                                }
                            }
                        
                            ++$n;
                        }
                    }
                }
            }
        
        //If it isn't set, return stats of _ONE AUTHOR_ only
        } else {
            
            foreach( $selected_posts as $single ) {
                
                //If post was not returned by content2cash (probably because of invalid status), continue
                if( ! isset( $ids_content2cash[$single->ID] ) )
                    continue;
                
                $post_date_array = explode( ' ', $single->post_date );
                
                $totale[] = array(
                    'ID'                => $single->ID,
                    'post_title'        => $single->post_title,
                    'comment_count'     => (int) $single->comment_count,
                    'comment_bonus'     => $ids_content2cash[$single->ID]['comment_bonus'],
                    'image_count'       => $ids_content2cash[$single->ID]['image_count'],
                    'image_bonus'       => $ids_content2cash[$single->ID]['image_bonus'],
                    'post_date'         => date( 'd/m/y', strtotime( $post_date_array[0] ) ),
                    'post_status'       => $single->post_status,
                    'post_type'         => $single->post_type,
                    'words_count'       => $ids_content2cash[$single->ID]['content_count'],
                    'minimum_fee'       => $ids_content2cash[$single->ID]['minimum_fee'],
                    'ordinary_payment'  => $ids_content2cash[$single->ID]['ordinary_payment'],
                    'payment_bonus'     => $ids_content2cash[$single->ID]['payment_bonus'],
                    'post_payment'      => $ids_content2cash[$single->ID]['total_payment'],
                    'is_post_paid'      => $single->post_pay_counter_paid
                );
                
                @$overall_stats['total_payment']    = sprintf( '%.2f', $overall_stats['total_payment'] + $ids_content2cash[$single->ID]['total_payment'] );
                @$overall_stats['total_bonus']      = sprintf( '%.2f', $overall_stats['total_bonus'] + $ids_content2cash[$single->ID]['payment_bonus'] );
                @$overall_stats['total_counting']   = $overall_stats['total_counting'] + $ids_content2cash[$single->ID]['content_count'];
                @$overall_stats['total_posts']++;
                
                //If using zones_system, define the payment area the post fits in (overall stats)
                if( $user_settings->counting_system_zones == 1 ) {
                    
                    //If post is below lowest zone, mark it as such and go to next
                    if( $ids_content2cash[$single->ID]['content_count'] < $user_settings->ordinary_zones[1]['zone'] ) {
                        @$overall_stats['0zone']++;
                        continue;
                    }
                    
                    $n = 1;
                    while( $n <= 5 ) { //Run 5 times
                        //If post lies in the last available zone (the fifth), do not specify a roof for its counting
                        //I.e. it is not between x and y words but only "above x". Only if not using supplementary zones
                        if( $n == 5 AND count( $user_settings->ordinary_zones ) < 5 ) {
                            if( $ids_content2cash[$single->ID]['content_count'] >= $user_settings->ordinary_zones[$n]['zone'] ) {
                                @$overall_stats[$n.'zone']++;
                            }
                        } else {
                            if( $ids_content2cash[$single->ID]['content_count'] >= $user_settings->ordinary_zones[$n]['zone'] AND $ids_content2cash[$single->ID]['content_count'] < $user_settings->ordinary_zones[$n+1]['zone'] ) {
                                @$overall_stats[$n.'zone']++;
                            }
                        }
                    
                    ++$n;
                    }
                    
                    if( count( $user_settings->ordinary_zones ) > 5 ) {
                        while( $n <= 10 ) { //Run 5 more times
                            //If post lies in the last available zone (the fifth), do not specify a roof for its counting
                            //I.e. it is not between x and y words but only "above x".
                            if( $n == 10 ) {
                                if( $ids_content2cash[$single->ID]['content_count'] >= $user_settings->ordinary_zones[$n]['zone'] ) {
                                    @$overall_stats[$n.'zone']++;
                                }
                            } else {
                                if( $ids_content2cash[$single->ID]['content_count'] >= $user_settings->ordinary_zones[$n]['zone'] AND $ids_content2cash[$single->ID]['content_count'] < $user_settings->ordinary_zones[$n+1]['zone'] ) {
                                    @$overall_stats[$n.'zone']++;
                                }
                            }
                        
                            ++$n;
                        }
                    }
                }
            }
        }
        
        //Build and return final array             
        $stats_response['general_stats'] = $totale;
        $stats_response['overall_stats'] = $overall_stats;
        
        return $stats_response;
    }
    
    //CSV file export function
    function csv_export( $author = false, $time_start = false, $time_end = false ) {
        global $current_user;
        
        //Little csv headers (blog name, URL...)
        $csv_file = '|| '.get_bloginfo('name').' - '.home_url().' ||

';
        
        //Define csv file name
        $csv_file_name = 'PPC__';
        
        //Date to show 
        $csv_file_name  .= date( 'Y/m/d', $time_start ).'-'.date( 'Y/m/d', $time_end ).'__';
        $csv_file       .= '"Showing stats from '.date( 'Y/m/d', $time_start ).' to '.date( 'Y/m/d', $time_end );
        
        //Author (if set) to show
        $author_data = get_userdata( $author );
        if( ! $author_data ) {
            $csv_file_name  .= 'General.csv';
            $csv_file       .= ' - General"';
        } else {
            $csv_file_name  .= $author_data->display_name.'.csv';
            $csv_file       .= ' - User \''.$author_data->display_name.'\'"';
        }
        
        $csv_file .= ';

';
           
        //Define stats to generate: if asking for author's...
        if( isset( $author ) AND get_userdata( $author ) ) {
            
            //Nonce check
            check_admin_referer( 'post_pay_counter_csv_export_author' );
            
            //Generate stats author
            $generated_stats    = self::generate_stats( $author, $time_start, $time_end );
            $csv_file          .= '"Post title";;"Post type";;"Status";;"Date";;"'.ucfirst( parent::$current_counting_method_word ).'";;"Comments";;"Images";;"Payment";;';
        
        //General stats
        } else {
            
            //Nonce check
            check_admin_referer( 'post_pay_counter_csv_export_general' );
            
            //Generate stats general
            $generated_stats    = self::generate_stats( false, $time_start, $time_end );
            $csv_file           .= '"Author";;"Written posts";;"Total payment";';
            if( $current_user->user_level >= 8 )
                $csv_file  .= ';"Paypal address";';
        
        }
        
        $csv_file .= '
';
        
        /** CSV OUTPUT STARTS **/
        //If stats are per author...
        if( strpos( $csv_file, ';"Status";' ) ) {
            foreach( $generated_stats['general_stats'] as $single ) {
                $csv_file .= '"'.utf8_decode( $single['post_title'] ).'";;"'.$single['post_type'].'";;"'.$single['post_status'].'";;"'.$single['post_date'].'";;"'.$single['words_count'].'";;"'.$single['comment_count'].'";;"'.$single['image_count'].'";;"'.$single['post_payment'].'";
';
            }
            
        //Otherwise, they'll be general ones...
        } else {
            foreach( $generated_stats['general_stats'] as $key => $value ) {
                $csv_file .= '"'.utf8_decode( get_userdata( $key )->display_name ).'";;"'.$value['posts'].'";;"'.$value['total_payment'].'";';
                
                if( $current_user->user_level >= 8 )
                    $csv_file .= ';"'.@self::get_settings( $key )->paypal_address.'";';
                    
                $csv_file .= '
';
                
            }
        }
        
        $csv_file .= '
';
        $csv_file .= ';;;"Total posts";;"Total payment";
';
        $csv_file .= ';;;"'.$generated_stats['overall_stats']['total_posts'].'";;"'.$generated_stats['overall_stats']['total_payment'].'";';
        //$csv_file .= ';;;;;;;;;;;"Generated by Pay Post Counter";';
        
        //Download headers
        header( 'Content-Type: application/force-download' );
    	header( 'Content-Type: application/octet-stream' );
    	header( 'Content-Type: application/download' );
    	header( 'Content-Disposition: attachment; filename='.$csv_file_name.';' );
    	header( 'Content-Transfer-Encoding: binary' );
    	header( 'Content-Length: '.strlen( $csv_file ) );
        die( $csv_file );
    }
    
    //Shows header part for the stats page, including the form to adjust the time window
    function show_stats_page_header( $current_page, $page_permalink, $current_time_start, $current_time_end ) {
        global $wpdb;
        
        $first_available_post = $wpdb->get_var( 'SELECT post_pay_counter FROM '.$wpdb->posts.' WHERE post_pay_counter IS NOT NULL ORDER BY post_pay_counter ASC LIMIT 0,1' );
		
		if( $first_available_post == NULL )
            $first_available_post = time(); ?>

        <script type="text/javascript">
            jQuery(document).ready(function() {
                jQuery('#post_pay_counter_time_start').datepicker({
                    dateFormat : 'yy/mm/dd',
                    minDate : '<?php echo date( 'y/m/d', $first_available_post ); ?>',
                    maxDate: '<?php echo date( 'y/m/d' ); ?>',
                    changeMonth : true,
                    changeYear : true,
                    showButtonPanel: true,
                    showOtherMonths: true,
                    selectOtherMonths: true,
                    showAnim: "slideDown",
                    onSelect: function(dateText, inst) {
                        jQuery('#post_pay_counter_time_end').datepicker('option','minDate', new Date(inst.selectedYear, inst.selectedMonth, inst.selectedDay));
                    }
                });
                jQuery('#post_pay_counter_time_end').datepicker({
                    dateFormat : 'yy/mm/dd',
                    minDate : '<?php echo date( 'y/m/d', $first_available_post ); ?>',
                    maxDate : '<?php echo date( 'y/m/d' ); ?>',
                    changeMonth : true,
                    changeYear : true,
                    showButtonPanel: true,
                    showOtherMonths: true,
                    selectOtherMonths: true,
                    showAnim: "slideDown",
                    onSelect: function(dateText, inst) {
                         jQuery('#post_pay_counter_time_start').datepicker('option','maxDate', new Date(inst.selectedYear, inst.selectedMonth, inst.selectedDay));
                    }      
                });
            });
        </script>
        <form action="" method="post">
            <span style="float: left; text-align: center;">
                <h3 style="margin: 10px 0 5px;">
                    Showing stats from <input type="text" name="tstart" id="post_pay_counter_time_start" class="mydatepicker" value="<?php echo date( 'Y/m/d', $current_time_start ); ?>" size="7" /> to <input type="text" name="tend" id="post_pay_counter_time_end" class="mydatepicker" value="<?php echo date( 'Y/m/d', $current_time_end ); ?>" size="7" /> - 
                    <?php if( $current_page == 'General') { ?>
                    General
                    <?php } else { ?>
                    User "<?php echo $current_page; ?>"
                    <?php } ?>
                
                </h3>
            </span>
            <span style="float: right; text-align: center;">
                <input type="submit" class="button-secondary" name="post_pay_counter_submit" value="<?php _e( 'Update time range' ) ?>" /><br />
                <a href="<?php echo $page_permalink; ?>" title="Get what-you-are-seeing permalink" style="font-size: smaller;">Get current view permalink</a>
            </span>
        </form>
		<div style="clear: both; "></div>
        <hr style="border-color: #ccc; border-style: solid; border-width: 1px 0 0; clear: both; margin: 5px 0 20px; height: 0;" />
    <?php }
    
    //Given an array of ids, it retrieves all the data from Google Analytics. We need start_time and end_time to define from what day to what day data should be selected.
    //NOTE: the time range is not used not to select the post in that range, to select the data of the requested posts in the selected time range
    
    //
    //THIS FUNCTIONS IS NOT IN USE YET DUE TO ANALYTICS CHANGES
    //
    
    function google_analytics_get_data( $post_ids ) {
        $micro1= microtime(true);
        //Assure we are just using the right counting type and method, otherwise return
        if( parent::$general_settings->counting_type_visits == 1 AND parent::$general_settings->counting_type_visits_method_google_analytics == 1 ) {
            
            set_time_limit( 300 );
            $max_results    = 10000;    //Analytics doesn't allow more than 10000 rows at once to be pulled
            $ids_paths      = array();
            $ids_countings  = array();
            $n              = 0;
            
            //Foreach the input post ids array to get their page slugs. We end having an array of IDs => Slugs that will be later used to get the post ID given the path
            foreach( $post_ids as $single ) {
                $post_permalink = get_permalink( $single );
                $url_components = parse_url( $post_permalink );
            
                //Define page slug
                if( isset( $url_components['query'] ) AND $url_components['query'] != '' )
                    $ids_paths[$single] = rtrim( $url_components['path'].'?'.$url_components['query'], '/' );
                else
                    $ids_paths[$single] = rtrim( $url_components['path'], '/' );
                
                $n++;
            }
            
            //Define data to retrieve (pageviews or unique pageviews) depending on settings
            if( parent::$general_settings->counting_type_visits_method_google_analytics_pageviews == 1 )
                $metrics = array( 'pageviews' );
            else if ( parent::$general_settings->counting_type_visits_method_google_analytics_unique_pageviews == 1 )
                $metrics = array( 'uniquePageviews' );
            
            //Login to Google Analytics, throw exception on failure
            try {
                $ga = new gapi( parent::$general_settings->counting_type_visits_method_google_analytics_email, parent::$general_settings->counting_type_visits_method_google_analytics_password);
            } catch ( Exception $e ) {
                echo '<div id="message" class="error fade"><p><strong>There was a problem logging into Google Analytics: '.$e->getMessage().'.</strong></p></div>';
            }
            
            //Retrieve data in blocks - here on it gets complicated...
            $ga_results_array       = array();
            $total_results          = 1; //For $total_results raison d'etre look 3 rows below, comment on row 526
            $n                      = 0;
            
            //$total_results at the beginning is set to 1 and $n is 0, so it enters the while the first time because 1 > 10000*0. Then, for $total_results, look on comment at row 533
            while ( $total_results > ( $n * $max_results ) ) {
                
                try {
                    //      ||GET DATA||
                    //Asking for pagePath and pageviews/uniquePageviews for every page seen between the payment start date and end date, in blocks of 10000 rows.
                    //All those data are stored into an array depending on the number of necessary requests 
                    //$total_results is then set to the request total rows, so that if there are more than 10000 results we throw a new request from results n° 10000 to n° 20000, and so on...
                    $ga->requestReportData( parent::$general_settings->counting_type_visits_method_google_analytics_profile_id, array( 'pagePath' ), $metrics, '', '', date( 'Y-m-d', parent::$publication_time_range_start ), date( 'Y-m-d', parent::$publication_time_range_end ), 1+$max_results*$n, $max_results*( $n+1 ) );
                    var_dump($ga_results_array[$n] = $ga->getResults());
                    var_dump(parent::$publication_time_range_start);
                    var_dump(parent::$publication_time_range_end);
                    $total_results = $ga->getTotalResults();
                } catch ( Exception $e ) {
                    echo '<div id="message" class="error fade"><p><strong>There was a problem selecting Google Analytics data: '.$e->getMessage().'.</strong></p></div>';
                }
                
                $n++;
            }
            echo 'Executed '.$n.' requests.<br>Total rows: '.$total_results.' <br>';
            
            //The result array indexes are here merged to put together all the rows. All the data converge in the last index, which value is then stored into $ga_results.
            $n = 0;
            $ga_results_array_count = count( $ga_results_array );
            while( $ga_results_array_count > ( $n + 1 ) ) {
                $ga_results_array[$n+1] = array_merge( $ga_results_array[$n], $ga_results_array[$n+1] );
                ++$n;
            }
            $ga_results = $ga_results_array[( $ga_results_array_count - 1 )]; //So $ga_results now holds all the data retrieved from Google Analytics
                        
            //Store retrieved pageviews/unique pageviews into proper vars which will be later used and converted into money amount
            foreach( $ga_results as $single ) {
                
                //For each result, get the related pagePath and search for it into the ids_paths array to get its ID
                if( ! empty( $single ) ) {
                    $ga_dimensions  = $single->getDimesions();
                    $current_id     = array_search( $ga_dimensions['pagePath'], $ids_paths );
                    
                    //Then, for $array_countings, create a new index with the current ID and the pageview/uniquePageview related to the ID previously got
                    if( parent::$general_settings->counting_type_visits_method_google_analytics_pageviews == 1 )
                        $ids_countings[$current_id] = $single->getPageviews();
                    else if ( parent::$general_settings->counting_type_visits_method_google_analytics_unique_pageviews == 1 )
                        $ids_countings[$current_id] = $single->getUniquePageviews();
                
                //If the result doesn't have any value, set its counting to 0
                } else {
                    $ids_countings[$current_id] = 0;
                }
                
            }
            
            $micro2= microtime(true);
            echo 'Tempo di esecuzione '.($micro2 - $micro1).' secondi';
            return $ids_countings;
        } else {
            return;
        }
    }
    
    //Given an array of IDs => Countings, it updates the WordPress database with the most updated Google Analytics data. Must be called *after* google_analytics_get_data.
    function google_analytics_update_database( $ids_countings, $regenerate_dates = FALSE ) {
        global $wpdb;
        
        //Usual situation, it updates the local database with the data got by google_analytics_get_data
        if( $regenerate_dates == FALSE ) {
            foreach( $ids_countings as $key => $value ) {
                $wpdb->update( $wpdb->posts, array( 'post_pay_counter_count' => $value ), array( 'ID' => $key ) );
            }
        
        //Apart from updating the local countings, it also rebuilds dates basing on post publishing dates
        } else {
            foreach( $ids_countings as $key => $value ) {
                $post_data = get_post( $key );
                $wpdb->update( $wpdb->posts, array( 'post_pay_counter_count' => $value, 'post_pay_counter' => strtotime( $post_data->post_date ) ), array( 'ID' => $key ) );
            }
        }
    }
    
    //Convert words count into the needed money payment value. Start time and end time are for Google Analytics use
    function content2cash( $post_ids, /*$start_time, $end_time,*/ $overall_stats = FALSE ) {
        global  $wpdb,
                $current_user;
        
        /*//If used method is Google Analytics and data should not be updated every request, select last update time. If last update time option doesn't exist, add it
        if( parent::$general_settings->counting_type_visits == 1 AND parent::$general_settings->counting_type_visits_method_google_analytics == 1 AND parent::$general_settings->counting_type_visits_method_google_analytics_update_request == 0 ) {
            if( ! $ga_last_update = get_option( 'ppc_ga_last_update' ) ) { //If exists, ppc_ga_last_update is retrieved and stored in $ga_last_update
                add_option( 'ppc_ga_last_update', time() );
                $ga_last_update = time();
            }
        }
        
        //If counting type is visits and counting method is Google Analytics, retrieve the remote data all at once to avoid slowness. Only get and update GA data if requested 
        if(  parent::$general_settings->counting_type_visits == 1 AND parent::$general_settings->counting_type_visits_method_google_analytics == 1 AND $overall_stats == FALSE AND 
           ( parent::$general_settings->counting_type_visits_method_google_analytics_update_request   == 1
        OR ( parent::$general_settings->counting_type_visits_method_google_analytics_update_hour      == 1    AND $ga_last_update + 3600  < time() )
        OR ( parent::$general_settings->counting_type_visits_method_google_analytics_update_day       == 1    AND $ga_last_update + 86400 < time() ) ) ) {
            
            $ids_countings = self::google_analytics_get_data( $post_ids );
            self::google_analytics_update_database( $ids_countings );
            update_option( 'ppc_ga_last_update', time() );
            
        } else {*/
            //We join the array indexes in a single string and we select all the data for the requested ids
            $post_data = $wpdb->get_results( 'SELECT ID, post_pay_counter_count FROM '.$wpdb->posts.' WHERE ID IN ('.join( ',', $post_ids ).')', ARRAY_A );
            
            //Now cycle them and put the count values in a new array
            foreach( $post_data as $single ) {
                $ids_countings[$single['ID']] = $single['post_pay_counter_count'];
            }
        //}
        
        $counting_settings                  = self::get_settings( $current_user->ID, TRUE );
        $ids_payments                       = array();
		
		if( ! is_array( $counting_settings->ordinary_zones ) )
			$counting_settings->ordinary_zones  = unserialize( $counting_settings->ordinary_zones );
        
        foreach( $ids_countings as $key => $single ) {
            $post_data                          = get_post( $key );
            $author_settings                    = self::get_settings( $post_data->post_author, TRUE );
            $post_payment                       = 0;
            $admin_bonus                        = 0;
			
			if( ! is_array( $author_settings->ordinary_zones ) )
				$author_settings->ordinary_zones  = unserialize( $author_settings->ordinary_zones );
            
            //If user can, special settings are retrieved from db and used for countings
            if( $current_user->ID == $post_data->post_author OR $current_user->user_level >= 8 OR $counting_settings->can_view_special_settings_countings == 1 )
                $counting_settings = $author_settings;
            
            //Only accept posts of the allowed post types and status
            if( strpos( parent::$allowed_status, $post_data->post_status ) AND strpos( parent::$allowed_post_types, $post_data->post_type ) !== FALSE ) {
                
                //If using unique payment system, get the value and multiply it for the number of words/visits of the post
                if( $counting_settings->counting_system_unique_payment == 1 ) {
                    $post_payment = round( $counting_settings->unique_payment * $ids_countings[$key], 2 );
                
                //If using zones system, define what payment area the post fits in
                } else {
                    
                    $n = 1;
                        while( $n <= 5 ) { //Run 5 times
                            
                            //If post lies in the last available zone (the fifth), do not specify a roof for its counting
                            //I.e. it is not between x and y words but only "above x". Only if not using supplementary zones
                            if( $n == 5 AND count( $counting_settings->ordinary_zones ) < 6 ) {
                                if( $ids_countings[$key] >= ( $counting_settings->ordinary_zones[$n]['zone'] ) ) {
                                    $post_payment = $counting_settings->ordinary_zones[$n]['payment'];
                                }
                            } else {
                                if( $ids_countings[$key] >= ( $counting_settings->ordinary_zones[$n]['zone'] ) AND $ids_countings[$key] < $counting_settings->ordinary_zones[$n+1]['zone'] ) {
                                    $post_payment = $counting_settings->ordinary_zones[$n]['payment'];
                        		}
                            }
                            
                            ++$n;
                        }
                        
                    if( count( $counting_settings->ordinary_zones ) > 5 ) { //Also using supplementary zones
                        while( $n <= 10 ) { //Run 5 more times
                            
                            //If post lies in the last available zone (the tenth), do not specify a roof for its counting
                            //I.e. it is not between x and y words but only "above x".
                            if( $n == 10 ) {
                                if( $ids_countings[$key] >= ( $counting_settings->ordinary_zones[$n]['zone'] ) ) {
                                    $post_payment = $counting_settings->ordinary_zones[$n]['payment'];
                                }
                            } else {
                                if( $ids_countings[$key] >= ( $counting_settings->ordinary_zones[$n]['zone'] ) AND $ids_countings[$key] < $counting_settings->ordinary_zones[$n+1]['zone'] ) {
                                    $post_payment = $counting_settings->ordinary_zones[$n]['payment'];
                        		}
                            }
                            ++$n;
                        }
                    }
                }
            } else {
                continue;
            }
            $ordinary_payment = $post_payment;
            
            //Comment bonus
            if( $post_data->comment_count >= $counting_settings->bonus_comment_count ) {
                $comment_bonus  = $counting_settings->bonus_comment_payment;
                $post_payment   = $post_payment + $comment_bonus;
            }
            
            //Credit the image bonus if there's more than one image in the processed post            
            if( $counting_settings->bonus_image_payment != '' ) {
                if( preg_match_all( '/<img[^>]*>/', $post_data->post_content, $array_all_imgs ) ) {
                    $array_all_imgs_count = count( $array_all_imgs[0] );
                    if( $array_all_imgs_count > 1 ) {
                        $image_bonus    = ( $array_all_imgs_count - 1 ) * $counting_settings->bonus_image_payment;
                        $post_payment   = $post_payment + $image_bonus;
                    }
                }
            }
            
            //If post payment is still lower than the minimum fee, and it should be rounded, let's do it
            if( $post_payment < $counting_settings->minimum_fee_value AND $counting_settings->minimum_fee_enable == 1 ) {
                $minimum_fee  = $counting_settings->minimum_fee_value - $post_payment;
                $post_payment = $post_payment + $minimum_fee;
            }
            
            //Define admin defined bonus if available
            if( ( $author_settings->allow_payment_bonuses == 1 ) ) {
                $payment_bonus    = @get_post_meta( $key, 'payment_bonus', true );
                $post_payment     = $post_payment + $payment_bonus;
            }
            
            //Final array to return
            $ids_payments[$key]['content_count']    = (int) $ids_countings[$key];
            $ids_payments[$key]['ordinary_payment'] = sprintf( '%.2f', $ordinary_payment );
            $ids_payments[$key]['total_payment']    = sprintf( '%.2f', $post_payment );
            $ids_payments[$key]['payment_bonus']    = sprintf( '%.2f', @$payment_bonus );
            $ids_payments[$key]['minimum_fee']      = sprintf( '%.2f', @$minimum_fee );
            $ids_payments[$key]['image_count']      = @( (int) $array_all_imgs_count );
            $ids_payments[$key]['image_bonus']      = sprintf( '%.2f', @$image_bonus );
            $ids_payments[$key]['comment_bonus']    = sprintf( '%.2f', @$comment_bonus );
            
            //Clear variables to prevent data to be confused with coming posts
            unset( $ordinary_payment, $post_payment, $payment_bonus, $minimum_fee, $array_all_imgs_count, $image_bonus, $comment_bonus );
        }
        
        return $ids_payments;
    }
    
    //Requested to update all database posts. For every selected post, get the counting through the update_single_counting and store them in an array which will be used at the end to launch a single query
    function update_all_posts_count( $update_dates = FALSE, $author_id = FALSE ) {
        global $wpdb;
        
        $sql_where      = '';
        $all_posts_data = array();
        
        //If given author id is not valid, set it to false
        if( $author_id AND ! get_userdata( $author_id ) )
            $author_id = FALSE;
        
        //If author ID is given, define SQL WHERE statement
        if( $author_id )
            $sql_where = ' WHERE post_author = '.$author_id;
        
        //If user explicitly asking, delete countings and dates to start from scratch 
        if( $update_dates == TRUE ) {
            $wpdb->query( 'UPDATE '.$wpdb->posts.' SET post_pay_counter = NULL, post_pay_counter_count = NULL, post_pay_counter_paid = NULL'.$sql_where );
        
        //Else, only delete countings
        } else {
            $wpdb->query( 'UPDATE '.$wpdb->posts.' SET post_pay_counter_count = NULL, post_pay_counter_paid = NULL'.$sql_where );
        }
        
        //Select and update all the records (if $author_id is a valid user ID, only select and update the related user's posts)
        if( $author_id ) {
            $old_posts = $wpdb->get_results( 'SELECT ID, post_status, post_date, post_author, post_content, post_pay_counter, post_pay_counter_count 
                FROM '.$wpdb->posts.' INNER JOIN '.$wpdb->usermeta.'
                    ON '.$wpdb->usermeta.'.user_id = '.$wpdb->posts.'.post_author 
                    WHERE '.$wpdb->usermeta.'.meta_key = "'.$wpdb->get_blog_prefix().'capabilities" 
                    AND '.$wpdb->usermeta.'.meta_value REGEXP ("'.parent::$allowed_user_roles.'") 
                    AND '.$wpdb->posts.'.post_status IN ('.parent::$allowed_status.')
                    AND '.$wpdb->posts.'.post_type IN ('.parent::$allowed_post_types.')
                    AND '.$wpdb->posts.'.post_author = '.$author_id.' 
            ' );
        } else {
            $old_posts = $wpdb->get_results( 'SELECT ID, post_status, post_date, post_author, post_content, post_pay_counter, post_pay_counter_count 
                FROM '.$wpdb->posts.' INNER JOIN '.$wpdb->usermeta.'
                    ON '.$wpdb->usermeta.'.user_id = '.$wpdb->posts.'.post_author 
                    WHERE '.$wpdb->usermeta.'.meta_key = "'.$wpdb->get_blog_prefix().'capabilities" 
                    AND '.$wpdb->usermeta.'.meta_value REGEXP ("'.parent::$allowed_user_roles.'") 
                    AND '.$wpdb->posts.'.post_status IN ('.parent::$allowed_status.')
                    AND '.$wpdb->posts.'.post_type IN ('.parent::$allowed_post_types.')
            ' );
        }
        
        /*//If using google analytics method, we need to get data from ga first, and then to update the local database with the new information
        if( parent::$general_settings->counting_type_visits == 1 AND parent::$general_settings->counting_type_visits_method_google_analytics == 1 ) {
            
            //Create an array of ids to pass to analytics functions
            $ids_to_request = array();
            foreach( $old_posts as $single ) {
                $ids_to_request[] = $single->ID;
            }
            
            $ids_countings = self::google_analytics_get_data( $ids_to_request );
            self::google_analytics_update_database( $ids_countings, TRUE );
        
        //Otherwise, for plugin method visits or words counting type, just run through selected posts and update database fields
        } else {*/
            $post_pay_counter_date_field_update     = 'post_pay_counter = CASE ID '; //27 chars long string
            $post_pay_counter_count_field_update    = 'post_pay_counter_count = CASE ID ';
            
            foreach( $old_posts as $single ) {
                $post_counting = self::update_single_counting( $single->ID, $single->post_status, $single->post_date, $single->post_author, $single->post_pay_counter, $single->post_pay_counter_count, $single->post_content );
                
                if( isset( $post_counting['post_pay_counter'] ) )
                    $post_pay_counter_date_field_update .= 'WHEN '.$single->ID.' THEN "'.$post_counting['post_pay_counter'].'" ';
                
                if( isset( $post_counting['post_pay_counter_count'] ) )
                    $post_pay_counter_count_field_update .= 'WHEN '.$single->ID.' THEN '.$post_counting['post_pay_counter_count'].' ';
                
                unset( $post_counting );
            }
            
            $update_query = 'UPDATE '.$wpdb->posts.' SET '.$post_pay_counter_count_field_update.'END';
            
            //Append update-counting-dates only if at least one date is available
            if( strlen( $post_pay_counter_date_field_update ) > 27 )
                $update_query .= ', '.$post_pay_counter_date_field_update.'END';
            
            $wpdb->query( $update_query );
        //}
    }
    
    //Function used to update the database posts counting values. If caller function is update_all_posts_count, just return the post counting without executing query
    function update_single_counting( $post_id, $post_status, $post_date, $post_author, $post_pay_counter_date, $post_pay_counter_count, $post_content = NULL ) {
        global $wpdb;
        
        //If current post has a date but not a counting value, clear its fields
         //if( $post_pay_counter_count == NULL )
                //$wpdb->query( 'UPDATE '.$wpdb->posts.' SET post_pay_counter = NULL, post_pay_counter_count = NULL, post_pay_counter_paid = NULL WHERE ID = '.$post_id );
        
        //Define the suitable counting value and do the maths
        if( parent::$general_settings->counting_type_words == 1 )
            $count_value = self::count_post_words( $post_content );
        else if ( parent::$general_settings->counting_type_visits == 1 AND parent::$general_settings->counting_type_visits_method_plugin == 1 )
            $count_value = $post_pay_counter_count + 1;
                    
        //Now create array data to update db fields
        $update_counting_query = array( 'post_pay_counter_count' => $count_value );
        
        //Update the plugin date field only if it's empty (i.e. the post has never been counted)
        if( $post_pay_counter_date == '' ) {
            //If current post status is future, set the counting time to NOW otherwise it would go to the publish date 
            //(i.e. write a post on 30/08, gets planned for 02/09 => it will show up on 02/09 and will be payed the following month => not the expected behaviour). 
            //If it's publish, take the publishing date and use it as counting time
            if( $post_status == 'publish' )
                $update_counting_query['post_pay_counter'] = strtotime( $post_date );
            else
                $update_counting_query['post_pay_counter'] = time();
        }
        
        //Return values now if mass update
        $backtrace = debug_backtrace();
        if( $backtrace[1]['function'] == 'update_all_posts_count' )
            return $update_counting_query;
        
        $update_counting_conditions = array( 'ID' => $post_id );
        
        //Run update query
        $wpdb->update( $wpdb->posts, $update_counting_query, $update_counting_conditions );
    }
    
    //Routine that determines the number of effective words for a given post content
    function count_post_words( $post_content ) {
        //Trim blockquotes if requested
        if( parent::$general_settings->exclude_quotations_from_countings == 1 )
            $post_content = preg_replace( '/<blockquote>.*<\/blockquote>/s', '', $post_content );
        
        //Strip HTML tags, then regex: reduce all kind of white spaces to one " ", trim punctuation and account a word as "some non-blank chars which have a space before or after" 
        $count_value = (int) preg_match_all( '/\S+\s|\s\S+/', preg_replace( '/[0-9.(),;:!?%#$¿\'"_+=\\/-]+/', '', preg_replace( '/&nbsp;|&#160;|\r|\n|\r\n|\s+/', ' ', strip_tags( $post_content ) ) ), $arr );
        
        return $count_value;
    }
    
    function post_pay_counter_post_paid_update() {
        global $wpdb;
        
        //Check that the request was really issued by the plugin
        check_ajax_referer( 'post_pay_counter_post_paid_update', 'security_nonce' );
        
        //If checkbox was checked, add the amount now paid along with the current time to the array containing the payment history.
        //It is necessary to do all that stuff with serialize/base64 because of chars that would close the HTML attribute fields 
        if( isset( $_POST['checked'] ) ) {
            $payment_history    = unserialize( html_entity_decode( unserialize( base64_decode( $_POST['payment_history'] ) ) ) );
            $payment_history[]  = array(
                $_POST['now_paid'],
                time()
            );
            
            $update_array   = array( 'post_pay_counter_paid' => serialize( $payment_history ) );
            $where_array    = array( 'ID' => $_POST['post_id'] );
            $wpdb->update( $wpdb->posts, $update_array, $where_array );
        
        //Else, if the checkbox was unchecked, set the paid field to NULL
        } else {
            $wpdb->query( $wpdb->prepare( 'UPDATE '.$wpdb->posts.' SET post_pay_counter_paid = NULL WHERE ID = '.$_POST['post_id'] ) );
        }
        
        exit;
    }
    
    //Generate overall stats
    function generate_overall_stats() {
        global $wpdb; ?>
        
        <br />
        <h3 style="text-align: center; margin-bottom: 0.4em;">Showing overall stats, since the first counted post...</h3>
        
        <?php $raw_stats = $wpdb->get_results( 'SELECT ID, post_pay_counter, post_pay_counter_count, post_author, post_date FROM '.$wpdb->posts.' WHERE post_pay_counter IS NOT NULL ORDER BY post_date ASC', ARRAY_A );
        
        //If no stats are available, return
        if( $wpdb->num_rows == 0 ) {
            echo 'No available stats. Start blogging, and everything will appear...';
            return;
        }
        
        $total_counted_posts            = $wpdb->num_rows;
        $users_who_have_ever_written    = array();
        $overall_payment_value          = 0;
        $total_words_ever               = 0;
        $ids_to_request                 = array();
        
        foreach( $raw_stats as $single ) {
            $ids_to_request[]           = $single['ID'];
            $total_users[]              = $single['post_author'];
            $overall_avaiable_months[]  = date( 'm/Y', $single['post_pay_counter'] );
        }
        
        $ids_content2cash = self::content2cash( $ids_to_request, /*strtotime( $raw_stats[0]['post_date'] ), time(),*/ TRUE );
        foreach( $ids_content2cash as $single ) {
            $overall_payment_value      = $overall_payment_value + $single['total_payment'];
            $total_words_ever           = $total_words_ever + $single['content_count'];
        } 
        
        //Count how many months we have        
        @$overall_avaiable_months       = array_unique( $overall_avaiable_months );
        $overall_avaiable_months_count  = count( $overall_avaiable_months );
        
        //Count how many users have ever written something since blog start
        @$users_who_have_ever_written   = count( array_unique( $total_users ) );
        
        //Count averages of posts/payments (monthly)
        @$monthly_posts_average         = round( $total_counted_posts / $overall_avaiable_months_count );
        @$monthly_posts_payment_average = round( $overall_payment_value / $overall_avaiable_months_count, 2 );
        
        //Count average words number and payment per post
        @$words_average_per_post        = round( $total_words_ever / $total_counted_posts );
        @$payment_average_per_post      = round( $overall_payment_value / $total_counted_posts, 2 );
        
        //Count and sort all the user records to retrieve the most active one (username + written posts)
        @$user_posts_count              = array_count_values( $total_users );
        @arsort( $user_posts_count, SORT_NUMERIC );
        @$most_active_user_name         = get_userdata( ( key( $user_posts_count ) ) )->display_name;
        @$most_active_user_posts        = current( $user_posts_count ); ?>
        
        <table class="widefat fixed">
    		<tr>
    			<td width="40%">Total spent money:</td>
    			<td width="10%">&euro; <?php printf( '%.2f', $overall_payment_value ) ?></td>
                <td width="33%">Total <?php echo parent::$current_counting_method_word ?> ever:</td>
    			<td width="17%"><?php echo (int) $total_words_ever ?></td>
    		</tr>
    		<tr class="alternate">
    			<td width="40%">Monthly total payment average:</td>
    			<td width="10%">&euro; <?php printf( '%.2f', $monthly_posts_payment_average ) ?></td>
                <td width="33%">Monthly posts number average:</td>
    			<td width="17%"><?php echo (int) $monthly_posts_average ?></td>
    		</tr>
            <tr>
                <td width="40%">Single post payment average:</td>
    			<td width="10%">&euro; <?php printf( '%.2f', $payment_average_per_post ) ?></td>
                <td width="33%">Single post <?php echo parent::$current_counting_method_word ?> average:</td>
    			<td width="17%"><?php echo (int) $words_average_per_post ?></td>
            </tr>
            <tr class="alternate">
                <td width="40%">Number of users who have ever written a post (at least):</td>
    			<td width="10%"><?php echo (int) $users_who_have_ever_written ?></td>
                <td width="33%">Most active user name:</td>
    			<td width="17%"><?php echo $most_active_user_name ?> <span style="font-size: smaller;">(<?php echo (int) $most_active_user_posts ?> posts)</span></td>
            </tr>
       </table>
    <?php }
}

?>