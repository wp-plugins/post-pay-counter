<?php

/**
 * @author Stefano Ottolenghi
 * @copyright 2013
 */

require_once( 'ppc_save_options_class.php' );

class PPC_ajax_functions {
    
    /**
     * Checks whether the AJAX request is legitimate, if not displays an error that the requesting JS will display.
     *
     * @access  public
     * @since   2.0
     * @param   $nonce string the WP nonce  
    */
    
    static function ppc_check_ajax_referer( $nonce ) {
        if( ! check_ajax_referer( $nonce, false, false ) ) {
            die( __( 'Error: Seems like AJAX request was not recognised as coming from the right page. Maybe hacking around..?' , 'post-pay-counter') );
        }
    }
    
    /**
     * Handles the AJAX request for the counting settings saving.
     *
     * @access  public
     * @since   2.0
     * @param   $nonce string the WP nonce  
    */
    
    function save_counting_settings() {
        self::ppc_check_ajax_referer( 'ppc_save_counting_settings' );
        
        $save_settings = PPC_save_options::save_counting_settings( $_REQUEST['form_data'] );
        if( is_wp_error( $save_settings ) ) {
            die( $save_settings->get_error_message() );
        }
        die( 'ok' );
    }
    
    /**
     * Handles the AJAX request for the misc settings saving.
     *
     * @access  public
     * @since   2.0
     * @param   $nonce string the WP nonce  
    */
    
    function save_misc_settings() {
        self::ppc_check_ajax_referer( 'ppc_save_misc_settings' );
        
        $save_settings = PPC_save_options::save_misc_settings( $_REQUEST['form_data'] );
        if( is_wp_error( $save_settings ) ) {
            die( $save_settings->get_error_message() );
        }
        die( 'ok' );
    }
    
    /**
     * Handles the AJAX request for the permissions saving.
     *
     * @access  public
     * @since   2.0
     * @param   $nonce string the WP nonce  
    */
    
    function save_permissions() {
        self::ppc_check_ajax_referer( 'ppc_save_permissions' );
        
        $save_settings = PPC_save_options::save_permissions( $_REQUEST['form_data'] );
        if( is_wp_error( $save_settings ) ) {
            die( $save_settings->get_error_message() );
        }
        die( 'ok' );
    }
    
    /**
     * Fetches users to be personalized basing on the requested user role.
     *
     * @access  public
     * @since   2.0  
    */
    
    function personalize_fetch_users_by_roles() {
        global $ppc_global_settings;
        self::ppc_check_ajax_referer( 'ppc_personalize_fetch_users_by_roles' );
        
        echo 'ok';
        
        $args = array( 
            'orderby' => 'display_name', 
            'order' => 'ASC', 
            'role' => $_REQUEST['user_role'], 
            'count_total' => true, 
            'fields' => array( 
                'ID', 
                'display_name' 
            ) 
        );
        $args = apply_filters( 'ppc_personalize_fetch_users_args', $args );
        
        $users_to_show = new WP_User_Query( $args );
        if( $users_to_show->get_total() == 0 ) {
            die( __( 'No users found.' , 'post-pay-counter') );
        }
        
        $n = 0;
        $html = '';
        echo '<table>';
        foreach( $users_to_show->results as $single ) {
            if( $n % 3 == 0 ) {
                $html .= '<tr>';
            }   
                $html .= '<td><a href="'.admin_url( $ppc_global_settings['options_menu_link'].'&amp;userid='.$single->ID ).'" title="'.$single->display_name.'">'.$single->display_name.'</a></td>';
            if( $n % 3 == 2 ) {
                $html .= '</tr>';
            }
            
            echo apply_filters( 'ppc_html_personalize_list_print_user', $html );
            
            $n++;
        }
        echo '</table>';
        exit;
    }
    
    /**
     * If a valid user is given, their special settings are deleted.
     *
     * @access  public
     * @since   2.0  
    */
    
    function vaporize_user_settings() {
        global $ppc_global_settings;
        self::ppc_check_ajax_referer( 'ppc_vaporize_user_settings' );
        $user_id = (int) $_REQUEST['user_id'];
        
        if( is_int( $user_id ) ) {
            delete_user_option( $user_id, $ppc_global_settings['option_name'] );
            
            do_action( 'ppc_deleted_user_settings', $user_id );
            
            die( 'ok'.__( 'User\'s settings deleted successfully. You will be redirected to the general options page.' , 'post-pay-counter') );
        }
    }
}
?>