<?php

/**
 * @author Stefano Ottolenghi
 * @copyright 2013
 */

class PPC_generate_stats {
    
    /**
     * Produces stats by calling all needed methods. 
     * 
     * This is the highest-level method.
     *
     * @access  public
     * @since   2.0.2
     * @param   $time_start int the start time range timestamp
     * @param   $time_end int the end time range timestamp
     * @param   $author array optional an array of users for detailed stats
     * @return  array raw stats + formatted for output stats
    */
    
    static function produce_stats( $time_start, $time_end, $author = NULL ) {
        global $current_user;
        
        $perm = new PPC_permissions();
        
        //If general stats & CU can't see others' general, behave as if detailed for him
        if( ! is_array( $author ) AND ! $perm->can_see_others_general_stats() ) {
            $requested_posts = PPC_generate_stats::get_requested_posts( $time_start, $time_end, array( $current_user->ID ) );
        } else {
            $requested_posts = PPC_generate_stats::get_requested_posts( $time_start, $time_end, $author );
        }
        
        if( is_wp_error( $requested_posts ) ) {
            return $requested_posts;
        }
        
        $cashed_requested_posts = PPC_counting_stuff::data2cash( $requested_posts, $author );
        if( is_wp_error( $cashed_requested_posts ) ) {
            return $cashed_requested_posts;
        }
        
        $grouped_by_author_stats = PPC_generate_stats::group_stats_by_author( $cashed_requested_posts );
        if( is_wp_error( $grouped_by_author_stats ) ) {
            return $grouped_by_author_stats;
        }
        
        $formatted_stats = PPC_generate_stats::format_stats_for_output( $grouped_by_author_stats, $author );
        
        unset( $requested_posts, $cashed_requested_posts ); //Hoping to free some memory
        return array( 'raw_stats' => $grouped_by_author_stats, 'formatted_stats' => $formatted_stats );
    }
    
    /**
     * Builds an array of posts to be counted given the timeframe, complete with their data.
     *
     * @access  public
     * @since   2.0
     * @param   $time_start int the start time range timestamp
     * @param   $time_end int the end time range timestamp
     * @param   $author array optional an array of users for detailed stats
     * @return  array the array of WP posts object to be counted
    */
    
    static function get_requested_posts( $time_start, $time_end, $author = NULL ) {
        global $current_user, $wpdb, $ppc_global_settings;
        
        $settings = PPC_general_functions::get_settings( $current_user->ID );
        $args = array(
            'post_type' => $settings['counting_allowed_post_types'],
            'post_status' => array_keys( $settings['counting_allowed_post_statuses'], 1 ), //Only statuses with 1 as value are selected
            'date_query' => array(
                'after' => date( 'Y-m-d H:m:s', $time_start ),
                'before' => date( 'Y-m-d H:m:s', $time_end ),
                'inclusive' => true
            ),
            'orderby' => 'author',
            'order' => 'ASC',
            'posts_per_page' => -1,
            'ignore_sticky_posts' => 1
        );
        
        //If a user_id is provided, and is valid, posts only by that author are selected 
        if( is_array( $author ) ) {
            $args['author__in'] = $author;
        
        //If no user_id is provided, posts from all the allowed user roles users are selected
        } else {
            $allowed_users = array();
            foreach( $settings['counting_allowed_user_roles'] as $user_role => $value ) {
                $temp_array = get_users( array( 'role' => $user_role ) );
                array_merge( $allowed_users, $temp_array );
            }
            $args['author__in'] = $allowed_users;
        }
        
        $args = apply_filters( 'ppc_generate_stats_args', $args );
        
        $requested_posts = new WP_Query( $args );
        
        if( $requested_posts->found_posts == 0 ) {
            return new WP_Error( 'empty_selection', __( 'Error: no posts were selected' , 'post-pay-counter'), $args );
        }
        
        return $requested_posts->posts;
    }
    
    /**
     * Groups posts array by their authors and computes authors total (count+payment)
     *
     * @access  public
     * @since   2.0
     * @param   $data array the counting data
     * @return  array the counting data, grouped by author id
    */
    
    static function group_stats_by_author( $data ) {
        $sorted_array = array();
        
        foreach( $data as $post_id => $single ) {
            $sorted_array[$single->post_author][$post_id] = $single;
            $user_settings = PPC_general_functions::get_settings( $single->post_author, true );
            
            //Don't include in general stats count posts below threshold
            if( $user_settings['counting_payment_only_when_total_threshold'] ) {
                if( $single->ppc_misc['exceed_threshold'] == false ) {
                    continue;
                }
            }
            
            //Compute total countings
            foreach( $single->ppc_count['normal_count']['real'] as $what => $value ) {
                //Avoid notices of non isset index
    			if( ! isset( $sorted_array[$single->post_author]['total']['ppc_count']['normal_count']['real'][$what] ) ) {
    				$sorted_array[$single->post_author]['total']['ppc_count']['normal_count']['real'][$what] = $single->ppc_count['normal_count']['real'][$what];
                    $sorted_array[$single->post_author]['total']['ppc_count']['normal_count']['to_count'][$what] = $single->ppc_count['normal_count']['to_count'][$what];
    			} else {
    				$sorted_array[$single->post_author]['total']['ppc_count']['normal_count']['real'][$what] += $single->ppc_count['normal_count']['real'][$what];
                    $sorted_array[$single->post_author]['total']['ppc_count']['normal_count']['to_count'][$what] += $single->ppc_count['normal_count']['to_count'][$what];
    			}
            }
            
            //Compute total payment
            foreach( $single->ppc_payment['normal_payment'] as $what => $value ) {
                //Avoid notices of non isset index
    			if( ! isset( $sorted_array[$single->post_author]['total']['ppc_payment']['normal_payment'][$what] ) ) {
    				$sorted_array[$single->post_author]['total']['ppc_payment']['normal_payment'][$what] = $value;
    			} else {
    				$sorted_array[$single->post_author]['total']['ppc_payment']['normal_payment'][$what] += $value;
    			}
            }
            
            if( ! isset( $sorted_array[$single->post_author]['total']['ppc_misc']['posts'] ) ) {
                $sorted_array[$single->post_author]['total']['ppc_misc']['posts'] = 1;
            } else {
                $sorted_array[$single->post_author]['total']['ppc_misc']['posts']++;
            }
            
            $sorted_array[$single->post_author] = apply_filters( 'ppc_sort_stats_by_author_foreach_post', $sorted_array[$single->post_author], $single );
        }
        
        foreach( $sorted_array as $author => &$stats ) {
            $user_settings = PPC_general_functions::get_settings( $author, true );
            
            //Check total threshold
            if( $user_settings['counting_payment_total_threshold'] != 0 ) {
                if( $stats['total']['ppc_payment']['normal_payment']['total'] > $stats['total']['ppc_misc']['posts'] * $user_settings['counting_payment_total_threshold'] ) {
                    $stats['total']['ppc_payment']['normal_payment']['total'] = $stats['total']['ppc_misc']['posts'] * $user_settings['counting_payment_total_threshold'];
                }
            }
            
            //Get tooltip
            $stats['total']['ppc_misc']['tooltip_normal_payment'] = PPC_counting_stuff::build_payment_details_tooltip( $stats['total']['ppc_count']['normal_count']['to_count'], $stats['total']['ppc_payment']['normal_payment'] );
            
            $stats = apply_filters( 'ppc_sort_stats_by_author_foreach_author', $stats, $author, $user_settings );
            
            unset( $stats );
        }
        
        return apply_filters( 'ppc_generated_raw_stats', $sorted_array );
    }
    
    /**
     * Makes stats ready for output.
     * 
     * An array is setup containing the heading columns and the rows data. These will be shown on output of any format: html, csv, pdf...
     *
     * @access  public
     * @since   2.0
     * @param   $data array a group_stats_by_author result
     * @param   $author array optional whether detailed stats
     * @return  array the formatted stats
    */
    
    static function format_stats_for_output( $data, $author = NULL ) {
        $formatted_stats = array( 
            'cols' => array(), 
            'stats' => array() 
        );
        
        if( is_array( $author ) ) {
            list( $author_id, $author_stats ) = each( $data ); 
            $user_settings = PPC_general_functions::get_settings( $author_id, true );
            
            $formatted_stats['cols']['post_id'] = __( 'ID' , 'post-pay-counter');
            $formatted_stats['cols']['post_title'] = _x( 'Title', '(Stats page) Post title' , 'post-pay-counter');
            $formatted_stats['cols']['post_type'] = _x( 'Type', '(Stats page) Post type' , 'post-pay-counter');
            $formatted_stats['cols']['post_status'] = _x( 'Status', '(Stats page) Post status' , 'post-pay-counter');
            $formatted_stats['cols']['post_publication_date'] = _x( 'Pub. Date', '(Stats page) Post publication date' , 'post-pay-counter');
            
            if( $user_settings['counting_words'] ) {
                $formatted_stats['cols']['post_words_count'] = _x( 'Words', '(Stats page) Post words number' , 'post-pay-counter');
            } if( $user_settings['counting_visits'] ) {
                $formatted_stats['cols']['post_visits_count'] = _x( 'Visits', '(Stats page) Post visits number' , 'post-pay-counter');
            } if( $user_settings['counting_comments'] ) {
                $formatted_stats['cols']['post_comments_count'] = _x( 'Comments', '(Stats page) Post comments number' , 'post-pay-counter');
            } if( $user_settings['counting_images'] ) {
                $formatted_stats['cols']['post_images_count'] = _x( 'Imgs', '(Stats page) Post images number' , 'post-pay-counter');
            }
            
            $formatted_stats['cols']['post_total_payment'] = _x( 'Total Pay', '(Stats page) Post total payment' , 'post-pay-counter');
            
            $formatted_stats['cols'] = apply_filters( 'ppc_author_stats_format_stats_after_cols_default', $formatted_stats['cols'] );
            
            foreach( $author_stats as $key => $post ) {
                if( $key === 'total' ) { continue; } //Skip author's total
                
                $post_date = explode( ' ', $post->post_date );
                $post_permalink = get_permalink( $post->ID );
                
                $formatted_stats['stats'][$author_id][$post->ID]['post_id'] = $post->ID;
                $formatted_stats['stats'][$author_id][$post->ID]['post_title'] = $post->post_title;
                $formatted_stats['stats'][$author_id][$post->ID]['post_type'] = $post->post_type;
                $formatted_stats['stats'][$author_id][$post->ID]['post_status'] = $post->post_status;
                $formatted_stats['stats'][$author_id][$post->ID]['post_publication_date'] = $post_date[0];
                
                if( $user_settings['counting_words'] ) {
                    $formatted_stats['stats'][$author_id][$post->ID]['post_words_count'] = $post->ppc_count['normal_count']['real']['words'];
                } if( $user_settings['counting_visits'] ) {
                    $formatted_stats['stats'][$author_id][$post->ID]['post_visits_count'] = $post->ppc_count['normal_count']['real']['visits'];
                } if( $user_settings['counting_comments'] ) {
                    $formatted_stats['stats'][$author_id][$post->ID]['post_comments_count'] = $post->ppc_count['normal_count']['real']['comments'];
                } if( $user_settings['counting_images'] ) {
                    $formatted_stats['stats'][$author_id][$post->ID]['post_images_count'] = $post->ppc_count['normal_count']['real']['images'];
                }
                
                $formatted_stats['stats'][$author_id][$post->ID]['post_total_payment'] = sprintf( '%.2f', $post->ppc_payment['normal_payment']['total'] );
                
                $formatted_stats['stats'][$author_id][$post->ID] = apply_filters( 'ppc_author_stats_format_stats_after_each_default', $formatted_stats['stats'][$author_id][$post->ID], $author_id, $post );
            }
            
        } else {
            $formatted_stats['cols']['author_id'] = __( 'Author ID' , 'post-pay-counter');
            $formatted_stats['cols']['author_name'] = __( 'Author Name' , 'post-pay-counter');
            $formatted_stats['cols']['author_written_posts'] = __( 'Written posts' , 'post-pay-counter');
            $formatted_stats['cols']['author_total_payment'] = __( 'Total payment' , 'post-pay-counter');
            
            $formatted_stats['cols'] = apply_filters( 'ppc_general_stats_format_stats_after_cols_default', $formatted_stats['cols'] );
            
            foreach( $data as $author_id => $posts ) {
                $author_data = get_userdata( $author_id );
                
                $formatted_stats['stats'][$author_id]['author_id'] = $author_id;
                $formatted_stats['stats'][$author_id]['author_name'] = $author_data->display_name;
                $formatted_stats['stats'][$author_id]['author_written_posts'] = $posts['total']['ppc_misc']['posts'];
                $formatted_stats['stats'][$author_id]['author_total_payment'] = sprintf( '%.2f', $posts['total']['ppc_payment']['normal_payment']['total'] );
                
                $formatted_stats['stats'][$author_id] = apply_filters( 'ppc_general_stats_format_stats_after_each_default', $formatted_stats['stats'][$author_id], $author_id, $posts );
            }
        }
        
        return apply_filters( 'ppc_formatted_stats', $formatted_stats );
    }
    
    /**
     * Computes overall stats.
     *
     * @access  public
     * @since   2.0
     * @param   $data array a group_stats_by_author result
     * @return  array the overall stats
    */
    
    static function get_overall_stats( $stats ) {
        $overall_stats = array( 
            'posts' => 0, 
            'payment' => 0 
        );
        
        foreach( $stats as $single ) {
            $overall_stats['posts'] += $single['total']['ppc_misc']['posts'];
            $overall_stats['payment'] += sprintf( '%.2f', $single['total']['ppc_payment']['normal_payment']['total'] );
        }
        
        return apply_filters( 'ppc_overall_stats', $overall_stats );
    }
}
?>