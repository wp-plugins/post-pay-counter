<?php

class post_pay_counter_functions_class {
    public $general_settings;
    
    public function __construct() {
        global $wpdb;
        
        //Select general settings (if they exist). We use them here, in the main plugin file and in the install one
        if( $wpdb->query( 'SHOW TABLES FROM '.$wpdb->dbname.' LIKE "'.$wpdb->prefix.'post_pay_counter"' ) )
            $this->general_settings = @$this->get_settings( 'general' );
    }
    
    //Select settings. Gets in one facoltative parameter, userID, and returns the counting settings as an object
    function get_settings( $user_id, $return_general = FALSE ) {
        global $wpdb;
        
        //If requesting a valid user, check if we need trial settings or not
        if( is_numeric( $user_id ) AND $userdata = get_userdata( $user_id ) ) {
            
            //Select requested user's trial settings 
            $author_settings   = $wpdb->get_row( 
                                   $wpdb->prepare( 'SELECT trial_manual, trial_auto, trial_period_days, trial_period_posts, trial_period, trial_enable FROM '.$wpdb->prefix.'post_pay_counter WHERE userID="'.$user_id.'"' )
                                );
            
            //If requested user doesn't have special settings, take general ones
            if( ! is_object( $author_settings ) )
                $author_settings = $this->general_settings;
            
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
        
        //Query the database for user settings (where user could also be 'general' or 'trial')
        $counting_settings = $wpdb->get_row(
                              $wpdb->prepare( 'SELECT * FROM '.$wpdb->prefix.'post_pay_counter WHERE userID = "'.$user_id.'"' )
                             );
                             
        
        //If for some reason (like not having special settings for a particular user) special settings are not avaiable, and $return_general is TRUE, return general ones
        if( ! is_object( $counting_settings ) AND $return_general == TRUE ) 
            $counting_settings = $this->general_settings;
            
        return $counting_settings;
    }
    
    //Outputs the html code for checkbox and radio fields checking whether the field should be checked or not
    function checked_or_not( $setting, $field, $name, $value = NULL, $id = NULL ) {
        if( $field == 'radio' ) {
            if( $setting == 1 ) {
                return '<input type="'.$field.'" name="'.$name.'" value="'.$value.'" id="'.$id.'" checked="checked" />';
            } else {
                return '<input type="'.$field.'" name="'.$name.'" value="'.$value.'" id="'.$id.'" />';
            }
        } else if( $field == 'checkbox' ) {
            if( $setting == 1 ) {
                return '<input type="'.$field.'" name="'.$name.'" id="'.$id.'" checked="checked" />';
            } else {
                return '<input type="'.$field.'" name="'.$name.'" id="'.$id.'" />';
            }
        }
    }
    
    //Used to print settings fields: a checkbox/radio on the left span and the related description on the right span
    function echo_p_field( $text, $setting, $field, $name, $tooltip_description = NULL, $value = NULL, $id = NULL ) { ?>
        <p style="min-height: 12px;">
            <span style="float: right; width: 20px; height: 13px; text-align: right;"><img src="<?php echo plugins_url( 'style/images/info.png', __FILE__ ); ?>" title="<?php echo $tooltip_description; ?>" class="tooltip_container" /></span>
            <label>
                <span style="float: left; width: 5%;">    
        <?php echo $this->checked_or_not( $setting, $field, $name, $value, $id ); ?>
                </span>
                <span style="width: 90%;"><?php echo $text ?></span>
            </label>
        </p>
    <?php }
    
    //Used when updating plugin options; defining checkboxes values
    function update_options_checkbox_value( $checkbox ) {
        if( ! isset( $checkbox ) )
            return 0;
        else
            return 1;
    }
    
    //Generate stats function. Does queries and countings and returns an array of data
    function generate_stats( $author = false, $time_start = false, $time_end = false ) {
        global $wpdb,
               $current_user;
        
        //Select plugin settings of current user or, if unavaiable, general settings
        $user_settings = $this->get_settings( $current_user->ID, TRUE );
        
        /** PERMISSION CHECK **/
        //Check if the requested user exists and if current user is allowed to see others' detailed stats
        if( $author ) {
            if( ! ( $user_settings->can_view_others_detailed_stats == 1 OR $current_user->ID == $author OR $current_user->user_level >= 7 ) )
                return 'You are not authorized to view this page';
                
            if( ! get_userdata( $author ) )
                return 'The requested user does not exist.';
            
            $user_data = get_userdata( $author );
        }
        
        //Check if current user is allowed to see old stats
        if( $time_start != mktime( 0, 0, 0, date( 'm' ), 1, date( 'Y' ) ) AND $user_settings->can_view_old_stats == 0 AND $current_user->user_level < 7 )
            return 'You are not authorized to view this page';
        
        /** POST SELECT **/
        //Query the database for posts from a defined author ...
        if( $author ) {
            $selected_posts = $wpdb->get_results( 
             $wpdb->prepare( 'SELECT ID, post_title, comment_count, post_status, post_date, post_pay_counter_count FROM '.$wpdb->posts.' WHERE post_author = "'.$author.'" AND post_type = "post" AND post_pay_counter BETWEEN '.$time_start.' AND '.$time_end.' AND ( post_status = "publish" OR post_status = "future" OR post_status = "pending" ) ORDER BY post_date DESC' )
            );
        
        //Or, query the database for posts from any author ...
        } else {               
            $selected_posts = $wpdb->get_results( 
             $wpdb->prepare( 'SELECT ID, post_title, comment_count, post_status, post_author, post_date, post_pay_counter_count FROM '.$wpdb->posts.' WHERE post_type = "post" AND post_pay_counter BETWEEN '.$time_start.' AND '.$time_end.' AND ( post_status = "publish" OR post_status = "future" OR post_status = "pending" )' )
            );
        }
        
        /** POST COUNTING ROUTINE **/
        //Return if no posts are selected
        if( $wpdb->num_rows == 0 )
            return 'No avaiable stats for the requested time frame/author. Try changing the time frame from the fields above and then press <em>Update time range</em>.';
        
        $stats_response = array();
        $totale         = array();
        $overall_stats  = array();
        $total_posts    = 0;
        $total_payment  = 0;
        
        //If the post_author field is set, return general stats divided per author
        if( isset( $selected_posts[0]->post_author ) ) {
            
            foreach( $selected_posts as $single ) {
                
                //If current user can't and is not admin, don't show other authors' information
                if( $user_settings->can_view_others_general_stats == 0 AND $current_user->user_level < 7 AND $single->post_author != $current_user->ID )
                    continue;
                
                //Get post payment value
                $post_payment = $this->content2cash( $single->ID );
                
                //Create a multidimensional array divided for author's names. Using silence operator to avoid notices
                @$totale[$single->post_author]['payment']       = $totale[$single->post_author]['payment'] + $post_payment['total_payment'] + $post_payment['payment_bonus'];
                @$totale[$single->post_author]['payment_bonus'] = $totale[$single->post_author]['payment_bonus'] + $post_payment['payment_bonus'];
                @$totale[$single->post_author]['posts']++;
                
                //Overall stats
                @$overall_stats['total_payment'] = $overall_stats['total_payment'] + $post_payment['total_payment'] + $post_payment['payment_bonus'];
                @$overall_stats['payment_bonus'] = $overall_stats['payment_bonus'] + $post_payment['payment_bonus'];
                @$overall_stats['total_posts']++;
                
                //If using zones_system, define the payment area the post fits in
                if( $user_settings->counting_system_zones == 1 ) {
                    if( $single->post_pay_counter_count < $user_settings->zone1_count ) {
                        @$overall_stats['0zone']++;
                    } else if( $single->post_pay_counter_count >= $user_settings->zone1_count AND $single->post_pay_counter_count < $user_settings->zone2_count ) {
                        @$overall_stats['1zone']++;
            		} else if( $single->post_pay_counter_count >= $user_settings->zone2_count AND $single->post_pay_counter_count < $user_settings->zone3_count ) {
                        @$overall_stats['2zone']++;
                    } else if( $single->post_pay_counter_count >= $user_settings->zone3_count AND $single->post_pay_counter_count < $user_settings->zone4_count ) {
                        @$overall_stats['3zone']++;
                    } else if( $single->post_pay_counter_count >= $user_settings->zone4_count AND $single->post_pay_counter_count < $user_settings->zone5_count ) {
                        @$overall_stats['4zone']++;
                    } else if( $single->post_pay_counter_count >= $user_settings->zone5_count ) {
                        @$overall_stats['5zone']++;
                    }
                }
            }
        
        //If it isn't set, return stats of one author only
        } else {
            
            foreach( $selected_posts as $single ) {
                
                //Calling content2cash, which also ensures the post_status is equal to one of the admitted ones
                $post_payment = $this->content2cash( $single->ID );
                $post_date_array = explode( ' ', $single->post_date );
                
                $totale[] = array(
                    'ID'            => $single->ID,
                    'post_title'    => $single->post_title,
                    'comment_count' => (int) $single->comment_count,
                    'image_count'   => (int) $post_payment['image_count'],
                    'post_date'     => date( 'd/m/y', strtotime( $post_date_array[0] ) ),
                    'post_status'   => $single->post_status,
                    'words_count'   => (int) $single->post_pay_counter_count,
                    'post_payment'  => $post_payment['total_payment'] + $post_payment['payment_bonus'],
                    'payment_bonus' => $post_payment['payment_bonus']
                );
                
                @$overall_stats['total_payment'] = $overall_stats['total_payment'] + $post_payment['total_payment'] + $post_payment['payment_bonus'];
                @$overall_stats['payment_bonus'] = $overall_stats['payment_bonus'] + $post_payment['payment_bonus'];
                @$overall_stats['total_posts']++;
                
                //If using zones_system, define the payment area the post fits in
                if( $user_settings->counting_system_zones == 1 ) { 
                    if( $single->post_pay_counter_count < $user_settings->zone1_count ) {
                        @$overall_stats['0zone']++;
                    } else if( $single->post_pay_counter_count >= $user_settings->zone1_count AND $single->post_pay_counter_count < $user_settings->zone2_count ) {
                        @$overall_stats['1zone']++;
            		} else if( $single->post_pay_counter_count >= $user_settings->zone2_count AND $single->post_pay_counter_count < $user_settings->zone3_count ) {
                        @$overall_stats['2zone']++;
                    } else if( $single->post_pay_counter_count >= $user_settings->zone3_count AND $single->post_pay_counter_count < $user_settings->zone4_count ) {
                        @$overall_stats['3zone']++;
                    } else if( $single->post_pay_counter_count >= $user_settings->zone4_count AND $single->post_pay_counter_count < $user_settings->zone5_count ) {
                        @$overall_stats['4zone']++;
                    } else if( $single->post_pay_counter_count >= $user_settings->zone5_count ) {
                        @$overall_stats['5zone']++;
                    }
                }
            }
        }
        
        //Build and return final array, if equal to 0 unsetting it to prevent 0 from showing along with the total payment, like € 30.440
        if( $overall_stats['payment_bonus'] != 0 )
            $overall_stats['payment_bonus'] = ' <span style="font-size: smaller">(> '.$overall_stats['payment_bonus'].')</span>';
        else
            unset( $overall_stats['payment_bonus'] );
             
        $stats_response['general_stats'] = $totale;
        $stats_response['overall_stats'] = $overall_stats;
        
        return $stats_response;
    }
    
    //CSV file export function
    function csv_export( $author = false, $time_start = false, $time_end = false ) {
        global $current_user;
        
        //Define csv file name
        $csv_file_name = 'MC__';
        
        //Date to show 
        $csv_file_name  .= date( 'Y/m/d', $time_start ).'-'.date( 'Y/m/d', $time_end ).'__';
        $csv_file       = '"Showing stats from '.date( 'Y/m/d', $time_start ).' to '.date( 'Y/m/d', $time_end );
        
        //Author (if set) to show
        $author_data = get_userdata( $author );
        if( ! $author_data ) {
            $csv_file_name  .= 'General.csv';
            $csv_file       .= ' - General"';
        } else {
            $csv_file_name  .= $author_data->nickname.'.csv';
            $csv_file       .= ' - User \''.$author_data->nickname.'\'"';
        }
        
        $csv_file .= ';

';
           
        //Define stats to generate: if asking for author's...
        if( isset( $author ) AND get_userdata( $author ) ) {
            
            //Nonce check
            check_admin_referer( 'post_pay_counter_csv_export_author' );
            
            //Generate stats author
            $generated_stats    = $this->generate_stats( $author, $time_start, $time_end );
            $csv_file          .= '"Post title";"Status";"Date";"Words";"Comments";"Images";"Payment";';
        
        //General stats
        } else {
            
            //Nonce check
            check_admin_referer( 'post_pay_counter_csv_export_general' );
            
            //Generate stats general
            $generated_stats    = $this->generate_stats( false, $time_start, $time_end );
            $csv_file           .= '"Author";"Written posts";"Total payment";';
            if( $current_user->user_level >= 7 )
                $csv_file  .= '"Paypal address";';
        
        }
        
        $csv_file .= '
';
        
        /** CSV OUTPUT STARTS **/
        //If stats are per author...
        if( strpos( $csv_file, ';"Status";' ) ) {
            foreach( $generated_stats['general_stats'] as $single ) {
                $csv_file .= '"'.utf8_decode( $single['post_title'] ).'";"'.$single['post_status'].'";"'.$single['post_date'].'";"'.$single['words_count'].'";"'.$single['comment_count'].'";"'.$single['image_count'].'";"'.$single['post_payment'].'";
';
            }
            
        //Otherwise, they'll be general ones...
        } else {
            foreach( $generated_stats['general_stats'] as $key => $value ) {
                $csv_file .= '"'.utf8_decode( get_userdata( $key )->nickname ).'";"'.$value['posts'].'";"'.$value['payment'].'";';
                
                if( $current_user->user_level >= 7 )
                    $csv_file .= @$this->get_settings( $key )->paypal_address.';';
                    
                $csv_file .= '
';
                
            }
        }
        
        //Download headers
        header( 'Content-Type: application/force-download' );
    	header( 'Content-Type: application/octet-stream' );
    	header( 'Content-Type: application/download' );
    	header( 'Content-Disposition: attachment; filename='.$csv_file_name.';' );
    	header( 'Content-Transfer-Encoding: binary' );
    	header( 'Content-Length: '.strlen( $csv_file ) );
        echo $csv_file;
        exit;
    }
    
    //Shows header part for the stats page, including the form to adjust the time window
    function show_stats_page_header( $current_page, $page_permalink, $current_time_start, $current_time_end ) {
        global $wpdb;
        
        $first_avaiable_post = $wpdb->get_row( 'SELECT post_pay_counter FROM '.$wpdb->posts.' WHERE post_pay_counter IS NOT NULL ORDER BY post_pay_counter ASC LIMIT 0,1' ); ?>

        <script type="text/javascript">
            jQuery(document).ready(function() {
                jQuery('#post_pay_counter_time_start').datepicker({
                    dateFormat : 'yy/mm/dd',
                    minDate : '<?php echo date( 'y/m/d', $first_avaiable_post->post_pay_counter ); ?>',
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
                    minDate : '<?php echo date( 'y/m/d', $first_avaiable_post->post_pay_counter ); ?>',
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
    
    //Convert words count into the needed money payment value
    function content2cash( $post_id ) {
        global $wpdb,
               $current_user;
        
        $post_data              = get_post( $post_id );
        $post_words             = $post_data->post_pay_counter_count;
        $author_settings        = $this->get_settings( $post_data->post_author, TRUE );
        $current_user_settings  = $this->get_settings( $current_user->ID, TRUE );
        $post_payment           = 0;
        $admin_bonus            = 0;
        
        //If user can, special settings are retrieved from db and used for countings.
        if( $current_user->ID == $post_data->post_author OR $current_user->user_level >= 7 OR $current_user_settings->can_view_special_settings_countings == 1 )
            $counting_settings = $author_settings;
        
        //If using unique payment system, get the value and multiply it for the number of words/visits of the post
        if( $counting_settings->counting_system_unique_payment == 1 ) {
            $post_payment = round( $counting_settings->unique_payment * $post_words, 2 );
        } else {
            //If using zones system, define what payment area the post fits in
            if( $post_data->post_status == 'publish' OR $post_data->post_status == 'future' OR ( $post_data->post_status == 'pending' AND $counting_settings->count_pending_revision_posts == 1 ) AND $post_data->post_type == 'post' ) {
                if( $post_words >= $counting_settings->zone1_count AND $post_words < $counting_settings->zone2_count ) {
                    $post_payment = $counting_settings->zone1_payment;
        		} else if( $post_words >= $counting_settings->zone2_count AND $post_words < $counting_settings->zone3_count ) {
                    $post_payment = $counting_settings->zone2_payment;
                } else if( $post_words >= $counting_settings->zone3_count AND $post_words < $counting_settings->zone4_count ) {
                    $post_payment = $counting_settings->zone3_payment;
                } else if( $post_words >= $counting_settings->zone4_count AND $post_words < $counting_settings->zone5_count ) {
                    $post_payment = $counting_settings->zone4_payment;
                } else if( $post_words >= $counting_settings->zone5_count ) {
                    $post_payment = $counting_settings->zone5_payment;
                }
            }
        }
            
        //Comment bonus
        if( $post_data->comment_count >= $counting_settings->bonus_comment_count ) {
            $post_payment = $post_payment + $counting_settings->bonus_comment_payment;
        }
        
        //Credit the image bonus if there's more than one image in the processed post            
        if( $counting_settings->bonus_image_payment != '' ) {
            if( preg_match_all( '/<img[^>]*>/', $post_data->post_content, $array_all_imgs ) ) {
                $array_all_imgs_count = count( $array_all_imgs[0] );
                if( $array_all_imgs_count > 1 ) {
                    $post_payment = $post_payment + ( ( $array_all_imgs_count - 1 ) * $counting_settings->bonus_image_payment );
                }
            }
        }
        
        //Define admin defined bonus if available and allowed (or user is admin or author of current post)
        if( ( $author_settings->allow_payment_bonuses == 1 AND $current_user_settings->can_view_payment_bonuses == 1 ) OR $current_user->user_level >= 7 OR $current_user->ID == $post_data->post_author ) {
            $payment_bonus  = @get_post_meta( $post_id, 'payment_bonus', true );
            $post_payment   = $post_payment + $payment_bonus;
        }

        return array(
            'total_payment' => $post_payment,
            'payment_bonus' => sprintf( '%.2f', @$payment_bonus ),
            'image_count'   => @( (int) $array_all_imgs_count )
        );
    }
    
    //Requested to update all database posts. Called on installation first
    function update_all_posts_count( $update_dates = FALSE, $author_id = FALSE ) {
        global $wpdb;
        
        $sql_where = '';
        
        //If given author id is not valid, set it to false
        if( $author_id AND ! get_userdata( $author_id ) )
            $author_id = FALSE;
        
        //If author ID is given, define SQL WHERE statement
        if( $author_id )
            $sql_where = ' WHERE post_author = '.$author_id;
        
        //If user explicitly asking, delete countings and dates to start from scratch 
        if( $update_dates == TRUE ) {
            $wpdb->query( 'UPDATE '.$wpdb->posts.' SET post_pay_counter = NULL, post_pay_counter_count = NULL'.$sql_where );
        
        //Else, only delete countings
        } else {
            $wpdb->query( 'UPDATE '.$wpdb->posts.' SET post_pay_counter_count = NULL'.$sql_where );
        }
        
        //Select and update all the records (if $author_id is a valid user ID, only update the related user's posts)
        if( $author_id ) {
            $old_posts = $wpdb->get_results(
             $wpdb->prepare( 'SELECT ID, post_status FROM '.$wpdb->posts.' WHERE post_type = "post" AND post_parent = 0 AND post_author = '.$author_id.' AND ( post_status = "publish" OR post_status = "future" OR post_status = "pending" )' )
            );
        } else {
            $old_posts = $wpdb->get_results(
             $wpdb->prepare( 'SELECT ID, post_status FROM '.$wpdb->posts.' WHERE post_type = "post" AND post_parent = 0 AND ( post_status = "publish" OR post_status = "future" OR post_status = "pending" )' )
            );
        }
        
        //Run through selected posts and update database fields.
        foreach( $old_posts as $single ) {
            $this->update_single_counting( $single->ID, $single->post_status );
        }
    }
    
    //Function used to update the database posts counting values
    function update_single_counting( $post_id, $post_status ) {
        global $wpdb;
        
        $post_data          = get_post( $post_id );
        $counting_settings  = $this->get_settings( $post_data->post_author, TRUE );
        
        //Consider only published, future without counting type visits and pending revision posts with counting typw words and count pending revision posts = 1
        if( $post_status == 'publish' 
        OR ( $post_status == 'future' AND $counting_settings->counting_type_visits == 0 )
        OR ( $post_status == 'pending' AND $counting_settings->counting_type_words == 1 AND $counting_settings->count_pending_revision_posts == 1 ) ) {
            
            //Define the suitable counting value and do the maths
            if( $counting_settings->counting_type_words == 1 ) {
                $count_value    = str_word_count( strip_tags( $post_data->post_content ) );
            } else if ( $counting_settings->counting_type_visits == 1 ) {
                $old_visits     = $wpdb->get_var( 'SELECT post_pay_counter_count FROM '.$wpdb->posts.' WHERE ID = '.$post_id );
                $count_value    = $old_visits + 1;
            }
            
            //Now create array data and update db fields
            $update_counting_query = array( 'post_pay_counter_count' => $count_value );
            
            //Update the plugin date field only if it's empty ( i.e. the post has never been counted )
            if( $post_data->post_pay_counter == '' ) {
                //If current post status is future, set the counting time to NOW otherwise it would go to the publish date 
                //(ie. write a posto on 30/08, get planned for 02/09 => it will show up on 02/09 and will be payed the following month => not the expected behaviour). 
                //If it's publish, take the publish date and use it as counting time
                if( $post_status == 'future' )
                    $update_counting_query['post_pay_counter'] = time();
                else
                    $update_counting_query['post_pay_counter'] = strtotime( $post_data->post_date );
            }
            
            $update_counting_conditions = array( 'ID' => $post_id );
            
            //Run update query
            $wpdb->update( $wpdb->posts, $update_counting_query, $update_counting_conditions );
        
        //If the post is a draft or anyway shoudn't be counted, set the fields to null. Do this because: publish a post, it gets counted; 
        //put the same post in draft, you don't lose the counting values and thus even if you republish it it still has the old date
        } else {
            if( $post_data->post_pay_counter != '' )
                $wpdb->query(
                 $wpdb->prepare( 'UPDATE '.$wpdb->posts.' SET post_pay_counter = NULL, post_pay_counter_count = NULL WHERE ID = '.$post_id )
                );
        }
    }
    
    //Generate overall stats
    function generate_overall_stats() {
        global $wpdb; ?>
        
        <br />
        <h3 style="text-align: center; margin-bottom: 0.4em;">Showing overall stats, since the first counted post...</h3>
        
        <?php $raw_stats = $wpdb->get_results( 'SELECT ID, post_pay_counter, post_pay_counter_count, post_author FROM '.$wpdb->posts.' WHERE post_pay_counter IS NOT NULL', ARRAY_A );
        
        //If no stats are avaiable, return
        if( $wpdb->num_rows == 0 ) {
            echo 'No avaiable stats. Start blogging, and everything will appear...';
            return;
        } 
        
        $total_counted_posts            = $wpdb->num_rows;
        $users_who_have_ever_written    = array();
        $overall_payment_value          = 0;
        $total_words_ever               = 0;
        
        foreach( $raw_stats as $single ) {
            $single_payment             = $this->content2cash( $single['ID'] );
            $total_users[]              = $single['post_author'];
            $overall_payment_value      = $overall_payment_value + $single_payment['total_payment'];
            $overall_avaiable_months[]  = date( 'm/Y', $single['post_pay_counter'] );
            $total_words_ever           = $total_words_ever + $single['post_pay_counter_count'];
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
        @$most_active_user_name         = get_userdata( ( key( $user_posts_count ) ) )->user_login;
        @$most_active_user_posts        = current( $user_posts_count ); ?>
        
        <table class="widefat fixed">
    		<tr>
    			<td width="40%">Total spent money:</td>
    			<td width="10%">&euro; <?php printf( '%.2f', $overall_payment_value ) ?></td>
                <td width="38%">Total words/visits ever:</td>
    			<td width="12%"><?php echo (int) $total_words_ever ?></td>
    		</tr>
    		<tr class="alternate">
    			<td width="40%">Monthly total payment average:</td>
    			<td width="10%">&euro; <?php printf( '%.2f', $monthly_posts_payment_average ) ?></td>
                <td width="38%">Monthly posts number average:</td>
    			<td width="12%"><?php echo (int) $monthly_posts_average ?></td>
    		</tr>
            <tr>
                <td width="40%">Single post payment average:</td>
    			<td width="10%">&euro; <?php printf( '%.2f', $payment_average_per_post ) ?></td>
                <td width="38%">Single post words/visits average:</td>
    			<td width="12%"><?php echo (int) $words_average_per_post ?></td>
            </tr>
            <tr class="alternate">
                <td width="40%">Number of users who have ever written a post (at least):</td>
    			<td width="10%"><?php echo (int) $users_who_have_ever_written ?></td>
                <td width="38%">Most active user name:</td>
    			<td width="12%"><?php echo $most_active_user_name ?> <span style="font-size: smaller;">(<?php echo (int) $most_active_user_posts ?> posts)</span></td>
            </tr>
       </table>
    <?php }
}

?>