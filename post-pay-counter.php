<?php
/*
Plugin Name: Post Pay Counter
Plugin URI: http://www.thecrowned.org/post-pay-counter
Description: Easily calculate and handle authors' pay on a multi-author blog by computing posts' remuneration basing on admin defined rules. Define the time range you would like to have stats about, and the plugin will do the rest.
Author: Stefano Ottolenghi
Version: 2.0.4
Author URI: http://www.thecrowned.org/
*/

/** Copyright Stefano Ottolenghi 2013
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

//If trying to open this file out of wordpress, warn and exit
if( ! function_exists( 'add_action' ) )
    die( 'This file is not meant to be called directly.' );

require_once( 'classes/ppc_general_functions_class.php' );
require_once( 'classes/ppc_generate_stats_class.php' );
require_once( 'classes/ppc_counting_stuff_class.php' );
require_once( 'classes/ppc_html_functions_class.php' );
require_once( 'classes/ppc_options_fields_class.php' );
require_once( 'classes/ppc_ajax_functions_class.php' );
require_once( 'classes/ppc_install_functions_class.php' );
require_once( 'classes/ppc_permissions_class.php' );
require_once( 'classes/ppc_meta_boxes_class.php' );

class post_pay_counter {
    public static $options_page_settings;
    //const POST_PAY_COUNTER_DEBUG = FALSE;
    
    function __construct() {
        global $ppc_global_settings;
        
        $ppc_global_settings['current_version'] = get_option( 'ppc_current_version' );
        $ppc_global_settings['newest_version'] = '2.0.4';
        $ppc_global_settings['option_name'] = 'ppc_settings';
        $ppc_global_settings['folder_path'] = plugins_url( '/', __FILE__ );
        $ppc_global_settings['options_menu_link'] = 'admin.php?page=post_pay_counter_options';
        $ppc_global_settings['stats_menu_link'] = 'admin.php?page=post_pay_counter_show_stats';
        $ppc_global_settings['cap_manage_options'] = 'post_pay_counter_manage_options';
        $ppc_global_settings['cap_access_stats'] = 'post_pay_counter_access_stats';
        $ppc_global_settings['temp'] = array( 'settings' => array() );
        $ppc_global_settings['general_settings'] = PPC_general_functions::get_settings( 'general' );
        
        //If current_version option does not exist or is DIFFERENT from the latest release number, launch the update procedures. If update is run, also updates all the class variables and the option in the db
        /*if( ! ( self::$ppc_current_version = get_option( 'ppc_current_version' ) ) OR self::$ppc_current_version != self::$ppc_newest_version ) {
            post_pay_counter_update_procedures::update();
            post_pay_counter_functions_class::options_changed_vars_update_to_reflect( TRUE );
            post_pay_counter_functions_class::manage_cap_allowed_user_groups_plugin_pages( self::$allowed_user_roles_options_page, self::$allowed_user_roles_stats_page );
            update_option( 'ppc_current_version', self::$ppc_newest_version );
            self::$ppc_current_version = self::$ppc_newest_version;
            echo '<div id="message" class="updated fade"><p><strong>Post Pay Counter was successfully updated to version '.self::$ppc_current_version.'.</strong> Want to have a look at the <a href="'.admin_url( self::$options_menu_link ).'" title="Go to Options page">Options page</a>, or at the <a href="http://wordpress.org/extend/plugins/post-pay-counter/changelog/" title="Go to Changelog">Changelog</a>?</p></div>';
        }*/
        
        //If debug is requested, print a lot of debug stuff that should allow me to troubleshoot any problem users may encounter
        /*if( self::POST_PAY_COUNTER_DEBUG == TRUE ) {
            echo 'PHP Version: '.phpversion().'<br />';
            echo 'Installed plugin version: '.$ppc_global_settings['ppc_current_version'].'<br />';
            echo 'General settings object: ';var_dump( $ppc_global_settings['general_settings'] );
            echo 'PPC class vars: ';var_dump( $ppc_global_settings );
            echo 'WP Permissions: ',var_dump( get_option( 'wp_user_roles' ) );
            echo 'PPC install errors: ';var_dump( get_option( 'ppc_install_error' ) );
        }*/
        
        //Add left menu entries for both stats and options pages
        add_action( 'admin_menu', array( &$this, 'post_pay_counter_admin_menus' ) );
        //add_action( 'network_admin_menu', array( $this, 'post_pay_counter_network_admin_menus' ) );
        
        //Hook for the install procedure
        register_activation_hook( __FILE__, array( 'PPC_install_functions', 'ppc_install' ) );
        
        //Hook on blog adding on multisite wp to install the plugin there either
        add_action( 'wpmu_new_blog', array( 'PPC_install_functions', 'ppc_new_blog_install' ), 10, 6);
        
        //On load plugin pages
        add_action( 'load-post-pay-counter_page_post_pay_counter_options', array( &$this, 'on_load_options_page_get_settings' ), 9 );
        add_action( 'load-post-pay-counter_page_post_pay_counter_options', array( &$this, 'on_load_options_page_enqueue' ), 10 );
        add_action( 'load-toplevel_page_post_pay_counter_show_stats', array( &$this, 'on_load_stats_page' ) );
        //add_action( 'load-toplevel_page_post_pay_counter_show_network_stats', array( &$this, 'on_load_stats_page' ) );
        
        //Localization
        add_action( 'plugins_loaded', array( &$this, 'load_localization' ) );
        
        //Custom links besides the usual "Edit" and "Deactivate"
        add_filter( 'plugin_action_links', array( &$this, 'ppc_settings_meta_link' ), 10, 2 );
        add_filter( 'plugin_row_meta', array( &$this, 'ppc_donate_meta_link' ), 10, 2 );
        
        //Hook to show the posts' word count as a column in the posts list
        //add_filter( 'manage_posts_columns', array( $this, 'post_pay_counter_column_word_count' ) );
        //add_action( 'manage_posts_custom_column', array( $this, 'post_pay_counter_column_word_count_populate' ) );
        
        //Manage AJAX calls
        add_action( 'wp_ajax_ppc_save_counting_settings', array( 'PPC_ajax_functions', 'save_counting_settings' ) );
        add_action( 'wp_ajax_ppc_save_permissions', array( 'PPC_ajax_functions', 'save_permissions' ) );
        add_action( 'wp_ajax_ppc_save_misc_settings', array( 'PPC_ajax_functions', 'save_misc_settings' ) );
        add_action( 'wp_ajax_ppc_personalize_fetch_users_by_roles', array( 'PPC_ajax_functions', 'personalize_fetch_users_by_roles' ) );
        add_action( 'wp_ajax_ppc_vaporize_user_settings', array( 'PPC_ajax_functions', 'vaporize_user_settings' ) );
    }
    
    /**
     * Adds first level side menu "Post Pay Counter"
     *
     * @access  public
     * @since   2.0
    */
    
    function post_pay_counter_admin_menus() {
        global $ppc_global_settings;
        
        add_menu_page( 'Post Pay Counter', 'Post Pay Counter', $ppc_global_settings['cap_access_stats'], 'post_pay_counter_show_stats', array( $this, 'show_stats' ) );
        add_submenu_page( 'post_pay_counter_show_stats', 'Post Pay Counter Stats', 'Stats', 'post_pay_counter_access_stats', 'post_pay_counter_show_stats', array( $this, 'show_stats' ) );
        $ppc_global_settings['options_menu_slug'] = add_submenu_page( 'post_pay_counter_show_stats', 'Post Pay Counter Options', 'Options', $ppc_global_settings['cap_manage_options'], 'post_pay_counter_options', array( $this, 'show_options' ) );
    }
    
    //Adds first level side menu (network admin)
    /*function post_pay_counter_network_admin_menus() {
        add_menu_page( 'Post Pay Counter', 'Post Pay Counter', 'post_pay_counter_access_stats', 'post_pay_counter_show_network_stats', array( $this, 'post_pay_counter_show_network_stats' ) );
        add_submenu_page( 'post_pay_counter_show_network_stats', 'Post Pay Counter Stats', 'Stats', 'post_pay_counter_access_stats', 'post_pay_counter_show_network_stats', array( $this, 'post_pay_counter_show_network_stats' ) );
        $ppc_global_settings['stats_menu_link'] = 'admin.php?page=post_pay_counter_show_network_stats';
        $ppc_global_settings['options_menu_slug'] = add_submenu_page( 'post_pay_counter_show_network_stats', 'Post Pay Counter Options', 'Options', 'post_pay_counter_manage_options', 'post_pay_counter_network_options', array( $this, 'post_pay_counter_network_options' ) );
        $ppc_global_settings['options_menu_link'] = 'admin.php?page=post_pay_counter_network_options';
    }*/
    
    /**
     * Reponsible of the datepicker's files, plugin's js and css loading in the stats page
     *
     * @access  public
     * @since   2.0
    */
    
    function on_load_stats_page() {
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
        
        wp_enqueue_script( 'jquery-ui-datepicker', $ppc_global_settings['folder_path'].'js/jquery.ui.datepicker.min.js', array( 'jquery', 'jquery-ui-core' ) );
        wp_enqueue_style( 'jquery.ui.theme', $ppc_global_settings['folder_path'].'style/ui-lightness/jquery-ui-1.8.15.custom.css' );
        wp_enqueue_style( 'ppc_stats_style', $ppc_global_settings['folder_path'].'style/ppc_stats_style.css' );
        wp_enqueue_script( 'ppc_stats_effects', $ppc_global_settings['folder_path'].'js/ppc_stats_effects.js', array( 'jquery' ) );
        wp_localize_script( 'ppc_stats_effects', 'ppc_stats_effects_vars', array(
            'datepicker_mindate' => date( 'y/m/d', $first_available_post ),
            'datepicker_maxdate' => date( 'y/m/d' )
        ) );
    } 
    
    /**
     * Loads metaboxes and tooltips js+css in the plugin options page and all the js and css needed, plus the strings js needs (nonces and localized text).
     *
     * @access  public
     * @since   2.0
    */
    
    function on_load_options_page_enqueue() {
        global $ppc_global_settings;
        wp_enqueue_script( 'post' );
        
        add_meta_box( 'post_pay_counter_counting_settings', 'Counting Settings', array( 'PPC_meta_boxes', 'meta_box_counting_settings' ), $ppc_global_settings['options_menu_slug'], 'normal', 'default', self::$options_page_settings );
        add_meta_box( 'post_pay_counter_permissions', 'Permissions', array( 'PPC_meta_boxes', 'meta_box_permissions' ), $ppc_global_settings['options_menu_slug'], 'normal', 'default', self::$options_page_settings );
        add_meta_box( 'post_pay_counter_support_the_fucking_author', 'Support the author', array( 'PPC_meta_boxes', 'meta_box_support_the_fucking_author' ), $ppc_global_settings['options_menu_slug'], 'side' );
        add_meta_box( 'post_pay_counter_pro_features', 'Everything you\'re missing by not being PRO', array( 'PPC_meta_boxes', 'meta_box_pro_features' ), $ppc_global_settings['options_menu_slug'], 'side' );
        
        
        if( ! isset( $_GET['userid'] ) OR ( isset( $_GET['userid'] ) AND ! is_numeric( $_GET['userid'] ) ) ) {
            add_meta_box( 'post_pay_counter_personalize_settings', 'Personalize Settings', array( 'PPC_meta_boxes', 'meta_box_personalize_settings' ), $ppc_global_settings['options_menu_slug'], 'side', 'default', self::$options_page_settings );
            add_meta_box( 'post_pay_counter_misc', 'Miscellanea', array( 'PPC_meta_boxes', 'meta_box_misc_settings' ), $ppc_global_settings['options_menu_slug'], 'side', 'default', self::$options_page_settings );
            //add_meta_box( 'post_pay_counter_trial_settings', 'Trial Settings', array( $this, 'meta_box_trial_settings' ), $ppc_global_settings['options_menu_slug'], 'normal', 'default', self::$options_page_settings );
        }
        
        wp_enqueue_style( 'jquery.tooltip.theme', $ppc_global_settings['folder_path'].'style/tipTip.css' );
        wp_enqueue_style( 'ppc_options_style', $ppc_global_settings['folder_path'].'style/ppc_options_style.css' );
        wp_enqueue_script( 'jquery-tooltip-plugin', $ppc_global_settings['folder_path'].'js/jquery.tiptip.min.js', array( 'jquery' ) );
        wp_enqueue_script( 'ppc_options_ajax_stuff', $ppc_global_settings['folder_path'].'js/ppc_options_ajax_stuff.js', array( 'jquery' ) );
        wp_localize_script( 'ppc_options_ajax_stuff', 'ppc_options_ajax_stuff_vars', array(
            'nonce_ppc_save_counting_settings' => wp_create_nonce( 'ppc_save_counting_settings' ),
            'nonce_ppc_save_permissions' => wp_create_nonce( 'ppc_save_permissions' ),
            'nonce_ppc_save_misc_settings' => wp_create_nonce( 'ppc_save_misc_settings' ),
            'nonce_ppc_personalize_fetch_users_by_roles' => wp_create_nonce( 'ppc_personalize_fetch_users_by_roles' ),
            'nonce_ppc_vaporize_user_settings' => wp_create_nonce( 'ppc_vaporize_user_settings' ),
            'localized_vaporize_user_success' => __( 'User\'s settings successfully deleted. You will be redirected to the general options page.' , 'post-pay-counter'),
            'ppc_options_url' => $ppc_global_settings['options_menu_link']
        ) );
		wp_enqueue_script( 'ppc_options_effects', $ppc_global_settings['folder_path'].'js/ppc_options_effects.js', array( 'jquery' ) );
		wp_localize_script( 'ppc_options_effects', 'ppc_options_effects_vars', array(
            'counting_words_current_zones_count' => count( self::$options_page_settings['counting_words_system_zonal_value'] ),
			'counting_visits_current_zones_count' => count( self::$options_page_settings['counting_visits_system_zonal_value'] ),
            'counting_images_current_zones_count' => count( self::$options_page_settings['counting_images_system_zonal_value'] ),
            'counting_comments_current_zones_count' => count( self::$options_page_settings['counting_comments_system_zonal_value'] ),
            'localized_too_many_zones' => __( 'No more than 10 zones are allowed.' , 'post-pay-counter'),
            'localized_too_few_zones' => __( 'No less than 2 zones are allowed.' , 'post-pay-counter'),
            'localized_need_threshold' => __( 'A payment threshold must first be set.' , 'post-pay-counter')
        ) );
    }
    
    /**
     * Selects the correct settings for the Options page.
     * 
     * Acts depending on the given $_GET['userid']: general, trial or user-personalized. 
     * If a valid user id is asked which does not have any personalized settings, get general ones and set the userid field to the user's one, unsetting all only-general options.
     *
     * @access  public
     * @since   2.0
    */
   
    function on_load_options_page_get_settings() {
        //Trial settings
        /*if( isset( $_GET['userid'] ) AND $_GET['userid'] == 'trial' ) {
            self::$options_page_settings = PPC_general_functions::get_settings( 'trial' ); 
        
        //NOT trial
        } else {*/
            
            //Numeric userid
            if( isset( $_GET['userid'] ) AND is_numeric( $_GET['userid'] ) ) {
                
                if( ! get_userdata( (int) $_GET['userid'] ) ) {
                    echo '<strong>'.__( 'The requested user does not exist.' , 'post-pay-counter').'</strong>';
                    return;
                }
                $settings = PPC_general_functions::get_settings( (int) $_GET['userid'] );
                
                //User who never had personalized settings is being set
                if( $settings['userid'] == 'general' ) {
                    $settings['userid'] = (int) $_GET['userid'];
                    unset( $settings['multisite_settings_rule'] );
                    unset( $settings['can_see_options_user_roles'] );
                    unset( $settings['can_see_stats_user_roles'] );
                    unset( $settings['counting_allowed_user_roles'] );
                    unset( $settings['counting_allowed_post_types'] );
                    unset( $settings['default_stats_time_range_month'] );
                    unset( $settings['default_stats_time_range_week'] );
                    unset( $settings['default_stats_time_range_custom'] );
                    unset( $settings['default_stats_time_range_custom_value'] );
                }
                
                $settings = apply_filters( 'ppc_unset_only_general_settings_personalize_user', $settings );
            
            //General
            } else {
                $settings = PPC_general_functions::get_settings( 'general' );
            }
            
            $settings = apply_filters( 'ppc_selected_options_settings', $settings );
            self::$options_page_settings = $settings;
        //}
    }
    
    /**
     * Loads localization files
     *
     * @access  public
     * @since   2.0
    */
    
    function load_localization() {
        load_plugin_textdomain( 'post-pay-counter', false, dirname( plugin_basename( __FILE__ ) ).'/lang/' );
    }
    
    /**
     * Shows the "Settings" link in the plugins list (under the title)
     *
     * @access  public
     * @since   2.0
     * @param   $links array links already in place
     * @param   $file string current plugin-file
    */
    
    function ppc_settings_meta_link( $links, $file ) {
        global $ppc_global_settings;
       
       if( $file == plugin_basename( __FILE__ ) ) {
            $links[] = '<a href="'.admin_url( $ppc_global_settings['options_menu_link'] ).'" title="'.__('Settings', 'post-pay-counter').'">'.__('Settings', 'post-pay-counter').'</a>';
       }
     
        return $links;
    }
    
    /**
     * Shows the "Donate" link in the plugins list (under the description)
     *
     * @access  public
     * @since   2.0
     * @param   $links array links already in place
     * @param   $file string current plugin-file
    */
    
    function ppc_donate_meta_link( $links, $file ) {
       if( $file == plugin_basename( __FILE__ ) ) {
            $links[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7UH3J3CLVHP8L" title="'.__('Donate', 'post-pay-counter').'">'.__('Donate', 'post-pay-counter').'</a>';
       }
     
        return $links;
    }
    
    //Adds the 'Word count' column in the post list page
    /*function post_pay_counter_column_word_count( $columns ) {
        global $current_user;
        
        //If posts word count should be showed
        if( post_pay_counter_functions_class::get_settings( $current_user->ID, TRUE )->can_view_posts_word_count_post_list == 1 )
            $columns['post_pay_counter_word_count'] = 'Word Count';
        
        return $columns;
    }
    
    //Populates the newly added 'Word count' column
    function post_pay_counter_column_word_count_populate( $name ) {
        global  $post,
                $current_user;
        
        $post               = (object) $post;
        $counting_settings  = post_pay_counter_functions_class::get_settings( $current_user->ID, TRUE );
        
        //If posts word count should be showed, we check if the counting system zones is in use and, if yes, compare the word count to the first zone count. When word count is below the first zone, its opacity is reduced
        if( $counting_settings->can_view_posts_word_count_post_list == 1 ) {
            if( $name == 'post_pay_counter_word_count' ) {
                $word_count = post_pay_counter_functions_class::count_post_words( $post->post_content );
                
                if( self::$global_settings->general_settings->counting_type_words == 1 AND $counting_settings->counting_system_zones == 1 AND $word_count < $counting_settings->ordinary_zones[1]['zone'] )
                    echo '<span style="opacity: 0.60">'.$word_count.' words</span>';
                else
                    echo $word_count.' words';
            }
        }
    }*/
    
    /**
     * Shows the Options page
     *
     * @access  public
     * @since   2.0
    */
    
    function show_options() {
        global $wpdb, $current_user, $ppc_global_settings;
         
        echo '<div class="wrap">';
        echo '<div style="float: right; color: #777; margin-top: 15px;">'.apply_filters( 'ppc_options_installed_version', __( 'Installed version' , 'post-pay-counter').': '.$ppc_global_settings['current_version'] ).'</div>';
        echo '<h2>Post Pay Counter '.__( 'Options' , 'post-pay-counter').'</h2>';
        echo '<div style="clear: both;"></div>';
        echo '<p>'.__( 'From this page you can configure the Post Pay Counter plugin. You will find all the information you need inside each following box and, for every available function, clicking on the info icon on the right of them.' , 'post-pay-counter').'</p>';         
        
        if( is_numeric( self::$options_page_settings['userid'] ) ) {
            $userdata = get_userdata( self::$options_page_settings['userid'] );
            echo '<p style="text-transform: uppercase; font-size: x-small; margin-bottom: -3px; text-align: center;">';
            echo '<a href="'.$ppc_global_settings['options_menu_link'].'" title="'.__( 'Go back to general settings' , 'post-pay-counter').'" style="float: left; color: black; ">'.__( 'Back to general' , 'post-pay-counter').'</a>';
            echo '<a href="#" id="vaporize_user_settings" accesskey="'.self::$options_page_settings['userid'].'" title="'.__( 'Delete user\'s settings' , 'post-pay-counter').'" style="float: right; color: red; ">'.__( 'Delete user\'s settings' , 'post-pay-counter').'</a>';
            echo __( 'Currently editing user:' , 'post-pay-counter').' "'.$userdata->display_name.'"';
            echo '</p>';
        } 
        
        do_action( 'ppc_html_options_before_boxes' );
        
        wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
        wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
        
            <div id="poststuff" class="metabox-holder has-right-sidebar">
                <div id="post-body" class="has-sidebar">
                    <div id="post-body-content" class="has-sidebar-content">
            	<?php do_meta_boxes( $ppc_global_settings['options_menu_slug'], 'normal', null ); ?>
                    </div>
                </div>
                <div id="side-info-column" class="inner-sidebar">
            	<?php do_meta_boxes( $ppc_global_settings['options_menu_slug'], 'side', null ); ?>
                </div>
            </div>
        </div>
    <?php }
    
    /**
     * Shows the Stats page.
     *
     * @access  public
     * @since   2.0
    */
    
    function show_stats() {
        global $wpdb, $current_user, $ppc_global_settings;
        $general_settings = PPC_general_functions::get_settings( 'general' );
        $perm = new PPC_permissions();
        
        //Define default stats time range depending on chosen settings: if monthly it depends on current month days number, weekly always 7, otherwise custom
        if( $general_settings['default_stats_time_range_week'] == 1 ) {
            $ppc_global_settings['default_stats_tstart'] = time() - ( ( date( 'N' )-1 )*24*60*60 );
            $ppc_global_settings['default_stats_tend'] = time();
        } else if( $general_settings['default_stats_time_range_month'] == 1 ) {
            $ppc_global_settings['default_stats_tstart'] = time() - ( ( date( 'j' )-1 )*24*60*60 );
            $ppc_global_settings['default_stats_tend'] = time();
        } else if( $general_settings['default_stats_time_range_custom'] == 1 ) {
            $ppc_global_settings['default_stats_tstart'] = time() - ( $general_settings['default_stats_time_range_custom_value']*24*60*60 );
            $ppc_global_settings['default_stats_tend'] = time();
        }
        
        //Merging _GET and _POST data due to the time range form available in the stats page header. Don't know whether the user is choosing the time frame from the form (POST) or arrived following a link (GET)
        $get_and_post = array_merge( $_GET, $_POST );
        
        //Validate time range values (start and end), if set. They must be isset, numeric and positive. 
        //If something's wrong, start and end time are taken from the default settings the user set (publication time range) and defined in the construct
        if( ( isset( $get_and_post['tstart'] ) AND ( ! is_numeric( $get_and_post['tstart'] ) OR $get_and_post['tstart'] < 0 ) )
        OR ( isset( $get_and_post['tend'] ) AND ( ! is_numeric( $get_and_post['tend'] ) OR $get_and_post['tend'] < 0 ) ) ) {
            $get_and_post['tstart'] = strtotime( $get_and_post['tstart'].' 00:00:01' );
            $get_and_post['tend']   = strtotime( $get_and_post['tend'].' 23:59:59' );
        } else if ( ! isset( $get_and_post['tstart'] ) OR ! isset( $get_and_post['tend'] ) ) {
            $get_and_post['tstart'] = mktime( 0, 0, 1, date( 'm', $ppc_global_settings['default_stats_tstart'] ), date( 'd', $ppc_global_settings['default_stats_tstart'] ), date( 'Y', $ppc_global_settings['default_stats_tstart'] ) );
            $get_and_post['tend']   = mktime( 23, 59, 59, date( 'm', $ppc_global_settings['default_stats_tend'] ), date( 'd', $ppc_global_settings['default_stats_tend'] ), date( 'Y', $ppc_global_settings['default_stats_tend'] ) );
        }
        $ppc_global_settings['temp']['tstart'] = apply_filters( 'ppc_stats_defined_time_start', $get_and_post['tstart'] );
        $ppc_global_settings['temp']['tend'] = apply_filters( 'ppc_stats_defined_time_end', $get_and_post['tend'] );
        
        //CSV file exporting feature
        //if( isset( $get_and_post['export'] ) AND $get_and_post['export'] == 'csv' AND ( $current_user->user_level >= 7 OR $current_user_settings->can_csv_export == 1 ) )
            //post_pay_counter_functions_class::csv_export( @$get_and_post['author'], @$get_and_post['tstart'], @$get_and_post['tend'] );
        
        echo '<div class="wrap">';
        echo '<h2>'.__( 'Post Pay Counter Stats' , 'post-pay-counter').'</h2>';
        
        //AUTHOR
        if( isset( $get_and_post['author'] ) AND is_numeric( $get_and_post['author'] ) AND $userdata = get_userdata( $get_and_post['author'] ) ) {
            echo PPC_HTML_functions::show_stats_page_header( $userdata->display_name, PPC_general_functions::get_the_author_link( $get_and_post['author'] ), $get_and_post['tstart'], $get_and_post['tend'] );
            
            if( ! $perm->can_see_others_detailed_stats() AND $current_user->ID != $get_and_post['author'] ) {
                echo __( 'Error: you are not allowed to see this page' , 'post-pay-counter');
                return;
            }
            
            $stats = PPC_generate_stats::produce_stats( $get_and_post['tstart'], $get_and_post['tend'], array( $get_and_post['author'] ) );
            if( is_wp_error( $stats ) ) {
                echo ( 'Error: '.$stats->get_error_message() );
                return;
            }
            
            do_action( 'ppc_html_stats_author_before_stats_form' );
            
            echo '<form action="#" method="post" id="ppc_stats">';
            echo PPC_HTML_functions::get_html_stats( $stats['formatted_stats'], $stats['raw_stats'], array( $get_and_post['author'] ) );
            echo '</form>';
            
            do_action( 'ppc_html_stats_author_after_stats_form' );
            
            echo '<div class="ppc_table_divider"></div>';
            
        //GENERAL STATS
        } else {
            echo PPC_HTML_functions::show_stats_page_header( __( 'General' , 'post-pay-counter'), admin_url( $ppc_global_settings['stats_menu_link'].'&amp;tstart='.$get_and_post['tstart'].'&amp;tend='.$get_and_post['tend'] ), $get_and_post['tstart'], $get_and_post['tend'] );
            
            //If CU can't see others' general, behave as if detailed for him
            if( ! $perm->can_see_others_general_stats() ) {
                $author = array( $current_user->ID );
            } else {
                $author = NULL;
            }
            
            $stats = PPC_generate_stats::produce_stats( $get_and_post['tstart'], $get_and_post['tend'], $author );
            if( is_wp_error( $stats ) ) {
                echo ( 'Error: '.$stats->get_error_message() );
                return;
            }
            
            do_action( 'ppc_html_stats_general_before_stats_form' );
            
            echo '<form action="#" method="post" id="ppc_stats">';
            echo PPC_HTML_functions::get_html_stats( $stats['formatted_stats'], $stats['raw_stats'] );
            echo '</form>';
            
            do_action( 'ppc_html_stats_general_after_stats_form' );
            
            echo '<div class="ppc_table_divider"></div>';
        }
        
        $overall_stats = PPC_generate_stats::get_overall_stats( $stats['raw_stats'] );
        echo PPC_HTML_functions::print_overall_stats( $overall_stats );
        do_action( 'ppc_html_stats_after_overall_stats' );
    }
}

$ppc_global_settings = array();
new post_pay_counter();

?>