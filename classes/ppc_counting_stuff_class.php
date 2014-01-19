<?php

/**
 * @author Stefano Ottolenghi
 * @copyright 2013
 */

class PPC_counting_stuff {
    public static $current_counting_system_value;
    public static $settings;
    
    /**
     * Switches through the possible counting systems and determines which one is active. 
     * 
     * Populates the class variable holding the payment value of the current system so that the methods which do the countings can rely on it without having to determine it every time.
     *
     * @access  public
     * @since   2.0
     * @param   $counting_type string the counting type (words, visits, images, comments)
     * @return  string the counting system currently in use (possible are: zonal, incremental)  
    */
    
    static function get_current_counting_system( $counting_type ) {
        $counting_systems = array( 'zonal', 'incremental' );
        
        foreach( $counting_systems as $single ) {
            $system = 'counting_'.$counting_type.'_system_'.$single;
            $system_value = 'counting_'.$counting_type.'_system_'.$single.'_value';
            
            if( self::$settings[$system] == 1 ) {
                return array( 'counting_system' => 'counting_system_'.$single, 'counting_system_value' => self::$settings[$system_value] );
            }
        }
    }
    
    /**
     * Assigns proper countings and payment data to each post.
     *
     * @access  public
     * @since   2.0
     * @param   $data array an array of WP posts
     * @param   $author array optional an array of user ids of whom stats should be taken
     * @return  array the posts array along with their counting & payment data  
    */
    
    static function data2cash( $data, $author = NULL ) {
        $last_post_author = '';
        $processed_data = array();
        
        foreach( $data as $single ) {
            //Not to overload server db, only select settings if different from previous one
            if( $single->post_author != $last_post_author ) {
                self::$settings = PPC_general_functions::get_settings( $single->post_author, TRUE );
            }
            $last_post_author = $single->post_author;
            
            $single->ppc_count = self::get_post_countings( $single );
            
            $post_payment = self::get_post_payment( $single->ppc_count );
            $single->ppc_payment = $post_payment['ppc_payment'];
            $single->ppc_misc = $post_payment['ppc_misc'];
            
            $processed_data[$single->ID] = apply_filters( 'ppc_post_counting_payment_data', $single, $author );
        }
        
        return $processed_data;
    }
    
    /**
     * Retrieves countings for the given post.
     *
     * @access  public
     * @since   2.0
     * @param   $post object a WP posts
     * @return  array the posts array along with their counting data  
    */
    
    static function get_post_countings( $post ) {
        $ppc_count = array(
            'real' => array( 
                'basic' => 0,
                'words' => 0,
                'visits' => 0,
                'images' => 0, 
                'comments' => 0 
            ),
            'to_count' => array(
                'basic' => 0,
                'words' => 0,
                'visits' => 0,
                'images' => 0, 
                'comments' => 0
            )
        );
        
        if( self::$settings['basic_payment'] == 1 ) {
            $ppc_count['real']['basic'] = 1;
            $ppc_count['to_count']['basic'] = 1;
        }
        
        if( self::$settings['counting_words'] == 1 ) {
            $words = PPC_general_functions::count_post_words( $post );
            $ppc_count['real']['words'] = $words['real'];
            $ppc_count['to_count']['words'] = $words['to_count'];
        }
        
        if( self::$settings['counting_visits'] == 1 ) {
            $visits = PPC_general_functions::get_post_visits( $post );
            $ppc_count['real']['visits'] = $visits['real'];
            $ppc_count['to_count']['visits'] = $visits['to_count'];
        }
        
        if( self::$settings['counting_images'] == 1 ) {
            $images = PPC_general_functions::count_post_images( $post );
            $ppc_count['real']['images'] = $images['real'];
            $ppc_count['to_count']['images'] = $images['to_count'];
        }
        
        if( self::$settings['counting_comments'] == 1 ) {
            $comments = PPC_general_functions::get_post_comments( $post );
            $ppc_count['real']['comments'] = $comments['real'];
            $ppc_count['to_count']['comments'] = $comments['to_count'];
        }
        
        return apply_filters( 'ppc_get_post_countings', $ppc_count );
    }
    
    /**
     * Computes payment data for the given post. Checks payment threshold.
     *
     * @access  public
     * @since   2.0
     * @param   $post_countings array the post countings
     * @return  array the payment data  
    */
    
    static function get_post_payment( $post_countings ) {
        $ppc_misc = array();
        $ppc_payment = self::get_countings_payment( $post_countings );
        
        $ppc_misc['exceed_threshold'] = false;
        if( self::$settings['counting_payment_total_threshold'] != 0 ) {
            if( $ppc_payment['normal']['total'] > self::$settings['counting_payment_total_threshold'] ) {
                $ppc_payment['normal']['total'] = self::$settings['counting_payment_total_threshold'];
                $ppc_misc['exceed_threshold'] = true;
            }
        }
        
        $ppc_misc['tooltip'] = self::build_payment_details_tooltip( $post_countings, $ppc_payment['normal'] );
        
        return apply_filters( 'ppc_get_post_payment', array( 'ppc_payment' => $ppc_payment, 'ppc_misc' => $ppc_misc ) );
    }
    
    /**
     * Computes payment data for the given items.
     *
     * @access  public
     * @since   2.0
     * @param   $countings array the countings
     * @return  array the payment data  
    */
    
    static function get_countings_payment( $countings ) {
        $ppc_payment = array( 'normal' => array() );
        
        //Basic payment
        if( self::$settings['basic_payment'] ) {
            $basic_pay = self::basic_payment( $countings['to_count']['basic'] );
            $ppc_payment['normal']['basic'] = $basic_pay;
        }
        
        //Words payment
        if( self::$settings['counting_words'] ) {
            $words_pay = self::words_payment( $countings['to_count']['words'] );
            $ppc_payment['normal']['words'] = $words_pay;
        }
        
        //Visits payment
        if( self::$settings['counting_visits'] ) {
            $visits_pay = self::visits_payment( $countings['to_count']['visits'] );
            $ppc_payment['normal']['visits'] = $visits_pay;
        }
        
        //Images payment
        if( self::$settings['counting_images'] ) {
            $images_pay = self::images_payment( $countings['to_count']['images'] );
            $ppc_payment['normal']['images'] = $images_pay;
        }
        
        //Comments payment
        if( self::$settings['counting_comments'] ) {
            $comments_pay = self::comments_payment( $countings['to_count']['comments'] );
            $ppc_payment['normal']['comments'] = $comments_pay;
        }
        
        $ppc_payment['normal']['total'] = array_sum( $ppc_payment['normal'] );
        
        return $ppc_payment;
    }
    
    /**
     * Builds tooltip holding payment details.
     *
     * @access  public
     * @since   2.0.2
     * @param   $countings array PPC count
     * @param   $payment array PPC payment
     * @return  string tooltip
    */
    
    static function build_payment_details_tooltip( $countings, $payment ) {
        $tooltip = '';
        
        if( self::$settings['basic_payment'] ) {
            $tooltip .= __( 'Basic payment' , 'post-pay-counter').': '.$countings['to_count']['basic'].' => '.sprintf( '%.2f', $payment['basic'] ).'&#13;';
        }
        
        if( self::$settings['counting_words'] ) {
            $tooltip .= __( 'Words payment' , 'post-pay-counter').': '.$countings['to_count']['words'].' => '.sprintf( '%.2f', $payment['words'] ).'&#13;';
        }
        
        if( self::$settings['counting_visits'] ) {
            $tooltip .= __( 'Visits payment' , 'post-pay-counter').': '.$countings['to_count']['visits'].' => '.sprintf( '%.2f', $payment['visits'] ).'&#13;';
        }
        
        if( self::$settings['counting_images'] ) {
            $tooltip .=  __( 'Images payment' , 'post-pay-counter').': '.$countings['to_count']['images'].' => '.sprintf( '%.2f', $payment['images'] ).'&#13;';
        }
        
        if( self::$settings['counting_comments'] ) {
            $tooltip .= __( 'Comments payment' , 'post-pay-counter').': '.$countings['to_count']['comments'].' => '.sprintf( '%.2f', $payment['comments'] );
        }
        
        return apply_filters( 'ppc_payment_details_tooltip', $tooltip );
    }
    
    /**
     * Computes basic payment.
     *
     * @access  public
     * @since   2.0
     * @param   $basic int how many basics to pay
     * @return  float the payment data  
    */
    
    static function basic_payment( $basic ) {
        return apply_filters( 'ppc_basic_payment_value', $basic_payment = self::$settings['basic_payment_value']*$basic );
    }
    
    /**
     * Computes words payment.
     *
     * @access  public
     * @since   2.0
     * @param   $post_words int post words count
     * @return  array the payment data  
    */
    
    static function words_payment( $post_words ) {
        $words_counting_system_data = self::get_current_counting_system( 'words' );
        return apply_filters( 'ppc_words_payment_value', self::$words_counting_system_data['counting_system']( $post_words, $words_counting_system_data['counting_system_value'] ) );
    }
    
    /**
     * Computes visits payment.
     *
     * @access  public
     * @since   2.0
     * @param   $post_words int post visits count
     * @return  array the payment data  
    */
    
    static function visits_payment( $post_visits ) {
        $visits_counting_system_data = self::get_current_counting_system( 'visits' );
        return apply_filters( 'ppc_visits_payment_value', self::$visits_counting_system_data['counting_system']( $post_visits, $visits_counting_system_data['counting_system_value'] ) );
    }
    
    /**
     * Computes images payment.
     *
     * @access  public
     * @since   2.0
     * @param   $post_images int post images count
     * @return  array the payment data  
    */
    
    static function images_payment( $post_images ) {
        $images_counting_system_data = self::get_current_counting_system( 'images' );
        return apply_filters( 'ppc_images_payment_value', self::$images_counting_system_data['counting_system']( $post_images, $images_counting_system_data['counting_system_value'] ) );
    }
    
    /**
     * Computes comments payment.
     *
     * @access  public
     * @since   2.0
     * @param   $post_comments int post comments count
     * @return  array the payment data
    */
    
    static function comments_payment( $post_comments ) {
        $comments_counting_system_data = self::get_current_counting_system( 'comments' );
        return apply_filters( 'ppc_comments_payment_value', self::$comments_counting_system_data['counting_system']( $post_comments, $comments_counting_system_data['counting_system_value'] ) );
    }
    
    /**
     * Cycles through set zones, finds the one that suites each post counting and sets it as payment.
     *
     * @access  public
     * @since   2.0
     * @param   $post_counting int post count
     * @param   $counting_system_value array the zonal system settings for this counting type
     * @return  float the payment for the given counting
    */
    
    static function counting_system_zonal( $post_counting, $counting_system_value ) {
        if( $post_counting < $counting_system_value[0]['threshold'] ) {
            return 0;
        }
        
        $n = 0;
        $zones_count = count( $counting_system_value );
        while( $n < $zones_count ) {
            if( $post_counting >= $counting_system_value[$n]['threshold'] AND $n == ( $zones_count - 1 ) ) {
                return $counting_system_value[$n]['payment'];
            }
            if( $post_counting >= $counting_system_value[$n]['threshold'] AND $post_counting < $counting_system_value[$n+1]['threshold'] ) {
                return $counting_system_value[$n]['payment'];
            }
            ++$n;
        }
    }
    
    /**
     * Multiplies each post counting by the set incremental payment.
     *
     * @access  public
     * @since   2.0
     * @param   $post_counting int post count
     * @param   $counting_system_value array the incremental system settings for this counting type
     * @return  float the payment for the given counting
    */
    
    static function counting_system_incremental( $post_counting, $counting_system_value ) {
        return $payment = $post_counting * $counting_system_value;
    }
    
    /**
     * Computes merit bonus: retrieves merit bonus from database and adds it.
     *
     * @access  public
     * @param   $post_id int post id
     * @return  
     * @todo
    */
    
    /*function add_merit_bonus( $post ) {
        global $PPC_settings;
        
        if( $PPC_settings->allow_merit_bonus == 1 ) {
            if( $payment_bonus = get_post_meta( $single->ID, 'payment_bonus', true ) ) {
                $post->payment['bonus_merit'] = $payment_bonus;
            } else {
                $post->payment['bonus_merit'] = 0;
            }
        }
        
        return $post;
    }*/
}
?>