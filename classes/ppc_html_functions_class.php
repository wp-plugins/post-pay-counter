<?php

/**
 * @author Stefano Ottolenghi
 * @copyright 2013
 */

require_once( 'ppc_permissions_class.php' );

class PPC_HTML_functions {
    
    /**
     * Shows header part for the stats page, including the form to adjust the time window
     *
     * @access  public
     * @since   2.0
     * @param   $current_page string current page title
     * @param   $page_permalink string current page permalink
     * @param   $current_time_start int stats time start
     * @param   $current_time_end int stats time end
    */
    
    function show_stats_page_header( $current_page, $page_permalink, $current_time_start, $current_time_end ) {
        global $ppc_global_settings;
        
        $first_available_post = get_posts( array( 
            'numberposts' => 1, 
            'orderby' => 'post_date',
            'order' => 'ASC'
        ) );
        
        if( count( $first_available_post ) == 0 ) {
            $first_available_post = time();
        } else {
            $first_available_post = strtotime( $first_available_post[0]->post_date );
        }
		?>

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
            <?php echo sprintf( __( 'Showing stats from %1$s to %2$s' , 'post-pay-counter'), '<input type="text" name="tstart" id="post_pay_counter_time_start" class="mydatepicker" value="'.date( 'Y/m/d', $ppc_global_settings['temp']['tstart'] ).'" accesskey="'.$ppc_global_settings['temp']['tstart'].'" size="8" />', '<input type="text" name="tend" id="post_pay_counter_time_end" class="mydatepicker" value="'.date( 'Y/m/d', $ppc_global_settings['temp']['tend'] ).'" accesskey="'.$ppc_global_settings['temp']['tend'].'" size="8" />' ).' - "'.$current_page.'"'; ?>
        </h3>
    </span>
    <span style="float: right; text-align: center;">
        <input type="submit" class="button-secondary" name="post_pay_counter_submit" value="<?php _e( 'Update time range' , 'post-pay-counter') ?>" /><br />
        <a href="<?php echo $page_permalink; ?>" title="<?php _e( 'Get current view permalink' , 'post-pay-counter'); ?>" style="font-size: smaller;"><?php _e( 'Get current view permalink' , 'post-pay-counter'); ?></a>
    </span>
</form>
<div class="clear"></div>
<hr class="ppc_hr_divider" />
    <?php }
    
    /**
     * Shows HTML stats.
     *
     * @access  public
     * @since   2.0
     * @param   $formatted_data array formatted stats
     * @param   $raw_data array ordered-by-author stats
     * @param   $general_or_author mixed whether 'general' stats or [int] detailed ones
    */
    
    function get_html_stats( $formatted_data, $raw_data, $general_or_author ) {
        global $current_user;
        $perm = new PPC_permissions();
        
        echo '<table class="widefat fixed" id="ppc_stats_table">';
        echo '<thead>';
        echo '<tr>';
        foreach( $formatted_data['cols'] as $col_id => $value ) { //cols are the same both for general and user
            echo '<th scope="col">'.$value.'</th>';
        }
        
        if( $general_or_author ==  'general' ) {
            do_action( 'ppc_general_stats_html_cols_after_default' );
        } else if( is_numeric( $general_or_author ) ) {
            do_action( 'ppc_author_stats_html_cols_after_default' );
        }
        
        echo '</tr>';
        echo '</thead>';
        
        echo '<tfoot>';
        echo '<tr>';
        foreach( $formatted_data['cols'] as $col_id => $value ) {
            echo '<th scope="col">'.$value.'</th>';
        }
        
        if( $general_or_author ==  'general' ) {
            do_action( 'ppc_general_stats_html_cols_after_default' );
        } else if( is_numeric( $general_or_author ) ) {
            do_action( 'ppc_author_stats_html_cols_after_default' );
        }
        
        echo '</tr>';
        echo '</tfoot>';
        
        echo '<tbody>';
        
        if( $general_or_author ==  'general' ) {
            
            foreach( $formatted_data['data'] as $author_id => $author_data ) {
                echo '<tr>';
                
                foreach( $author_data as $field_name => $field_value ) {
                    //Cases in which other stuff needs to be added to the output
                    switch( $field_name ) {
                        case 'author_name':
                            if( $perm->can_see_others_detailed_stats() OR $author_id == $current_user->ID ) {
                                $field_value = '<a href="'.PPC_general_functions::get_the_author_link( $author_id ).'" title="'.__( 'Go to detailed view' , 'post-pay-counter').'">'.$field_value.'</a>';
                            }
                            break;
                        
                        case 'author_total_payment':
                            $field_value = '<abbr title="'.$raw_data[$author_id]['total']['ppc_payment']['tooltip'].'" class="ppc_payment_column">'.$field_value.'</abbr>';
                            break;
                    }
                    
                    echo '<td class="'.$field_name.'">'.apply_filters( 'ppc_general_stats_html_each_field_value', $field_value, $field_name, $raw_data[$author_id] ).'</td>';
                }
                
                do_action( 'ppc_general_stats_html_after_each_default', $author_id, $formatted_data, $raw_data );
                
                echo '</tr>';
            }
        
        } else if( is_numeric( $general_or_author ) ) {
            list( $author, $author_stats ) = each( $formatted_data['data'] );
            $user_settings = PPC_general_functions::get_settings( $author, true );
            
            foreach( $formatted_data['data'] as $author_id => $author_stats ) {
                
                foreach( $author_stats as $post_id => $post_stats ) {
                    $post = $raw_data[$author_id][$post_id];
                    
                    $tr_opacity = '';
                    if( $user_settings['counting_payment_only_when_total_threshold'] == 1 ) {
                        if( $post->ppc_payment['exceed_threshold'] == false ) {
                            $tr_opacity = ' style="opacity: 0.40;"';
                        }
                    }
                    
                    echo '<tr'.$tr_opacity.'>';
                    
                    foreach( $post_stats as $field_name => $field_value ) {
                        $post_permalink = get_permalink( $post->ID );
                        
                        switch( $field_name ) {
                            case 'post_title':
                                $field_value = '<a href="'.$post_permalink.'" title="'.$post->post_title.'">'.$field_value.'</a>';
                                break;
                            
                            case 'post_total_payment':
                                $field_value = '<abbr title="'.$post->ppc_payment['tooltip'].'" class="ppc_payment_column">'.$field_value.'</abbr>';
                                break;
                        }
                        
                        echo '<td class="'.$field_name.'">'.apply_filters( 'ppc_author_stats_html_each_field_value', $field_value, $field_name, $post ).'</td>';
                    }
                    
                    do_action( 'ppc_author_stats_html_after_each_default', $author_id, $formatted_data, $post );
                    
                    echo '</tr>';
                }
            }
        }
        
        echo '</tbody>';
        echo '</table>';
    }
    
    /**
     * Shows HTML overall stats.
     *
     * @access  public
     * @since   2.0
     * @param   $overall_stats array overall stats
    */
    
    static function print_overall_stats( $overall_stats ) {
        global $ppc_global_settings;
        $perm = new PPC_permissions();
        $general_settings = PPC_general_functions::get_settings( 'general' );
        
        echo '<table class="widefat fixed">';
        echo '<tr>';
        echo '<td width="40%">Total displayed posts:</td>';
        echo '<td align="left" width="10%">'.$overall_stats['posts'].'</td>';
        echo '<td width="35%">Total displayed payment:</td>';
        echo '<td align="left" width="15%">'.sprintf( '%.2f', $overall_stats['payment'] ).'</td>';
        echo '</tr>';
        
        //Check if current user is allowed to pdf export, using the noheader parameter to allow csv download
        if( 1 == 0 AND $perm->can_pdf_export() ) { ?>
        <tr>
            <?php if( isset( $get_and_post['author'] ) ) { ?>
            <td colspan="4" align="center"><a href="<?php //echo wp_nonce_url( admin_url( self::$stats_menu_link.'&amp;author='.$get_and_post['author'].'&amp;tstart='.$get_and_post['tstart'].'&amp;tend='.$get_and_post['tend'].'&amp;export=csv&amp;noheader=true' ), 'post_pay_counter_csv_export_author' ) ?>" title="Export to pdf">Export stats to pdf</a></td>
            <?php } else { ?>
            <td colspan="4" align="center"><a href="<?php //echo wp_nonce_url( admin_url( self::$stats_menu_link.'&amp;tstart='.$get_and_post['tstart'].'&amp;tend='.$get_and_post['tend'].'&amp;export=csv&amp;noheader=true' ), 'post_pay_counter_csv_export_general' ) ?>" title="Export to csv">Export stats to csv</a></td>
            <?php } ?>
        </tr>
        <?php }
        
        echo '</table>';
        do_action( 'ppc_overall_stats' );
    }
    
    /**
     * Prints settings fields enclosing them in a <p>: a checkbox/radio in a floated-left span, the tooltip info on the right and the description in the middle.
     *
     * @access  public
     * @since   2.0
     * @param   $text string the field description
     * @param   $setting string the current setting value
     * @param   $field string the input type (checkbox or radio)
     * @param   $name string the field name
     * @param   $tooltip_description string optional the tooltip description
     * @param   $value string optional the field value (for radio)
     * @param   $id string optional the field id
     * @return  string the html 
    */
    
    function echo_p_field( $text, $setting, $field, $name, $tooltip_description = NULL, $value = NULL, $id = NULL ) {
	   global $ppc_global_settings;
    
        $html = '<p style="height: 11px;">';
        $html .= '<span class="ppc_tooltip">';
        $html .= '<img src="'.$ppc_global_settings['folder_path'].'style/images/info.png'.'" title="'.$tooltip_description.'" class="tooltip_container" />';
        $html .= '</span>';
        $html .= '<label>';
        $html .= '<span class="checkable_input">';
         
        if( $field == 'radio' ) { 
            $html .= PPC_options_fields::generate_radio_field( $setting, $name, $value, $id ); 
        } else if( $field == 'checkbox' ) { 
            $html .= PPC_options_fields::generate_checkbox_field( $setting, $name, $id ); 
        }
                
        $html .= '</span>';
        $html .= $text;
        $html .= '</label>';
        $html .= '</p>';
        
        return $html;
    }
    
    /**
     * Prints settings fields enclosing them in a <p>: a checkbox/radio in a floated-left span, the tooltip info on the right and the description in the middle.
     *
     * @access  public
     * @since   2.0
     * @param   $field_name string the field name
     * @param   $field_value string the field value
     * @param   $label_text string the label text
     * @param   $size int optional the text field size
     * @return  string the html
    */
    
    function echo_text_field( $field_name, $field_value, $label_text, $size = 12 ) {
        $html = '<p>';
        $html .= '<label for="'.$field_name.'">'.$label_text.'</label>';
        $html .= '<input type="text" name="'.$field_name.'" id="'.$field_name.'" size="'.$size.'" value="'.$field_value.'" class="ppc_align_right" />';
        $html .= '</p>';
        
        return $html;
    }
}
?>