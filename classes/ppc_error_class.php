<?php

/**
 * Error handler
 *
 * Used to produce and maybe store errors. Relies on WP_Error object.
 *
 * @package     PPCP
 * @copyright   2014
 * @author 		Stefano Ottolenghi
 */

class PPC_Error {
    
    private $wp_error;
    
    /**
     * Handles an error.
     * 
     * @since   2.21
     * @access  public
     * 
     * @param   $code string Error code
     * @param   $message string Error message
     * @param   $data mixed (optional) Error data
     * @param   $log bool (optional) Logging status
     * @return  object WP_Error with current error details
    */
    
    function __construct( $code, $message, $data = array(), $log = false ) {
        global $ppc_global_settings;
        
        $error_details = array(
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'time' => time()
        );
        
        //If debug or logging enabled, make up detailed error (only shown if requested)
        if( PPC_DEBUG_SHOW OR PPC_DEBUG_LOG OR $log ) {
            $error_details['debug_message'] = 'An error was thrown with code "'.$code.'", message "'.$message.'" and debug data "'.var_export( $data, true ).'".';
        }
        
        if( PPC_DEBUG_SHOW ) {
            $error_details['output'] = $error_details['debug_message'];
        } else {
            $error_details['output'] = $error_details['message'];
        }
        
        //If logging enabled, push error with others
        if( PPC_DEBUG_LOG or $log ) {
            $errors = get_option( $ppc_global_settings['option_errors'] );
            $errors[] = $error_details;
            
            if( ! update_option( $ppc_global_settings['option_errors'], $errors ) ) {
                $this->wp_error = new WP_Error( 'ppc_update_errors', 'Could not update errors option.' );
            }
        }
        
        $this->wp_error = new WP_Error( $error_details['code'], $error_details['output'] );
        
        //$this->return_error();
    }
    
    function return_error() {
        return $this->wp_error;
    }
    
}