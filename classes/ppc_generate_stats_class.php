<?php

/**
 * @author Stefano Ottolenghi
 * @copyright 2013
 */

class PPC_generate_stats {
    
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
            'post_status' => array_keys( $settings['counting_allowed_post_statuses'] ),
            'date_query' => array(
                'after' => date( 'Y-m-d H:m:s', $time_start ),
                'before' => date( 'Y-m-d H:m:s', $time_end ),
                'inclusive' => true
            ),
            'orderby' => 'author',
            'order' => 'ASC',
            'posts_per_page' => -1,
            'suppress_filters' => FALSE
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
            return new WP_Error( 'empty_selection', __( 'No posts were selected' , 'post-pay-counter'), $args );
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
            if( $user_settings['counting_payment_only_when_total_threshold'] == 1 ) {
                if( $single->ppc_payment['exceed_threshold'] == false ) {
                    continue;
                }
            }
            @$sorted_array[$single->post_author]['total']['ppc_count']['basic'] += $single->ppc_count['basic'];
            @$sorted_array[$single->post_author]['total']['ppc_count']['words']['real'] += $single->ppc_count['words']['real'];
            @$sorted_array[$single->post_author]['total']['ppc_count']['words']['to_count'] += $single->ppc_count['words']['to_count'];
            @$sorted_array[$single->post_author]['total']['ppc_count']['visits']['real'] += $single->ppc_count['visits']['real'];
            @$sorted_array[$single->post_author]['total']['ppc_count']['visits']['to_count'] += $single->ppc_count['visits']['to_count'];
            @$sorted_array[$single->post_author]['total']['ppc_count']['images']['real'] += $single->ppc_count['images']['real'];
            @$sorted_array[$single->post_author]['total']['ppc_count']['images']['to_count'] += $single->ppc_count['images']['to_count'];
            @$sorted_array[$single->post_author]['total']['ppc_count']['comments']['real'] += $single->ppc_count['comments']['real'];
            @$sorted_array[$single->post_author]['total']['ppc_count']['comments']['to_count'] += $single->ppc_count['comments']['to_count'];
            @$sorted_array[$single->post_author]['total']['ppc_count']['posts']++;
        }
        
        foreach( $sorted_array as $author => &$single ) {
            $single['total']['ppc_payment'] = PPC_counting_stuff::get_author_payment( $single['total']['ppc_count'], $single['total']['ppc_count']['posts'] );
            
            unset($single); //prevents reference from remaining in the array
        }
        
        $sorted_array = apply_filters( 'ppc_generated_raw_stats', $sorted_array );
        do_action( 'ppc_generated_raw_stats' );
        return $sorted_array;
    }
    
    /**
     * Makes stats ready for output.
     * 
     * An array is setup containing the heading columns and the rows data. These will be shown on output of any format: html, csv, pdf...
     *
     * @access  public
     * @since   2.0
     * @param   $data array a group_stats_by_author result
     * @param   $general_or_author mixed whether 'general' or [int] settings
     * @return  array the formatted stats
    */
    
    static function format_stats_for_output( $data, $general_or_author ) {
        $formatted_stats = array( 
            'cols' => array(), 
            'data' => array() 
        );
        
        if( $general_or_author ==  'general' ) {
            $formatted_stats['cols']['author_id'] = __( 'Author ID' , 'post-pay-counter');
            $formatted_stats['cols']['author_name'] = __( 'Author Name' , 'post-pay-counter');
            $formatted_stats['cols']['author_written_posts'] = __( 'Written posts' , 'post-pay-counter');
            $formatted_stats['cols']['author_total_payment'] = __( 'Total payment' , 'post-pay-counter');
            
            $formatted_stats['cols'] = apply_filters( 'ppc_general_stats_format_stats_after_cols_default', $formatted_stats['cols'] );
            
            foreach( $data as $author => $posts ) {
                $author_data = get_userdata( $author );
                
                $formatted_stats['data'][$author]['author_id'] = $author;
                $formatted_stats['data'][$author]['author_name'] = $author_data->display_name;
                $formatted_stats['data'][$author]['author_written_posts'] = $posts['total']['ppc_count']['posts'];
                $formatted_stats['data'][$author]['author_total_payment'] = $posts['total']['ppc_payment']['total'];
                
                $formatted_stats['data'][$author] = apply_filters( 'ppc_general_stats_format_stats_after_each_default', $formatted_stats['data'][$author], $author, $posts );
            }
        
        } else if( is_numeric( $general_or_author ) ) {
            list( $author, $author_stats ) = each( $data ); 
            $user_settings = PPC_general_functions::get_settings( $author, true );
            
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
                if( $key === 'total' ) { continue; }
                
                $post_date = explode( ' ', $post->post_date );
                $post_permalink = get_permalink( $post->ID );
                
                $formatted_stats['data'][$author][$post->ID]['post_id'] = $post->ID;
                $formatted_stats['data'][$author][$post->ID]['post_title'] = $post->post_title;
                $formatted_stats['data'][$author][$post->ID]['post_type'] = $post->post_type;
                $formatted_stats['data'][$author][$post->ID]['post_status'] = $post->post_status;
                $formatted_stats['data'][$author][$post->ID]['post_publication_date'] = $post_date[0];
                
                if( $user_settings['counting_words'] ) {
                    $formatted_stats['data'][$author][$post->ID]['post_words_count'] = $post->ppc_count['words']['real'];
                } if( $user_settings['counting_visits'] ) {
                    $formatted_stats['data'][$author][$post->ID]['post_visits_count'] = $post->ppc_count['visits']['real'];
                } if( $user_settings['counting_comments'] ) {
                    $formatted_stats['data'][$author][$post->ID]['post_comments_count'] = $post->ppc_count['comments']['real'];
                } if( $user_settings['counting_images'] ) {
                    $formatted_stats['data'][$author][$post->ID]['post_images_count'] = $post->ppc_count['images']['real'];
                }
                
                $formatted_stats['data'][$author][$post->ID]['post_total_payment'] = $post->ppc_payment['total'];
                
                $formatted_stats['data'][$author][$post->ID] = apply_filters( 'ppc_author_stats_format_stats_after_each_default', $formatted_stats['data'][$author][$post->ID], $author, $post );
            }
        }
        
        return apply_filters( 'ppc_format_stats_return', $formatted_stats );
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
            $overall_stats['posts'] += $single['total']['ppc_count']['posts'];
            $overall_stats['payment'] += $single['total']['ppc_payment']['total'];
        }
        
        return apply_filters( 'ppc_overall_stats', $overall_stats );
    }
}
?>