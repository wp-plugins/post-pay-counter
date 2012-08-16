<?php
/*
Plugin Name: Post Pay Counter
Plugin URI: http://www.thecrowned.org/post-pay-counter
Description: The Post Pay Counter plugin allows you to easily calculate and handle author's pay on a multi-author blog by computing every written post remuneration basing on admin defined rules. Define the time range you would like to have stats about, and the plugin will do the rest.
Author: Stefano Ottolenghi
Version: 1.3.4.6
Author URI: http://www.thecrowned.org/
*/

/* copyright Stefano Ottolenghi 2012
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

//If trying to open this file out of wordpress, warn and exit
if( ! function_exists( 'add_action' ) )
    die( 'This file is not meant to be called directly' );

include_once( 'post-pay-counter-functions.php' );
include_once( 'post-pay-counter-options-functions.php' );
include_once( 'post-pay-counter-install-routine.php' );
include_once( 'post-pay-counter-update-procedures.php' );
//include_once( 'gapi.class.php' );

class post_pay_counter_core {
    public static   $post_pay_counter_options_menu_slug,
                    $post_pay_counter_options_menu_link,
                    $post_pay_counter_stats_menu_link,
                    $post_pay_counter_db_table,
                    $edit_options_counter_settings,
                    $general_settings,
                    $current_counting_method_word,
                    $allowed_status,
                    $allowed_post_types,
                    $allowed_user_roles,
                    $ordinary_zones,
                    $allowed_user_roles_options_page,
                    $allowed_user_roles_stats_page,
                    $publication_time_range_start,
                    $publication_time_range_end,
                    $ppc_current_version,
                    $ppc_newest_version;
    
    const POST_PAY_COUNTER_DEBUG = FALSE;
    
    function __construct() {
        global $wpdb;
        
        self::$ppc_newest_version           = '1.3.4.6';
        self::$post_pay_counter_db_table    = $wpdb->prefix.'post_pay_counter';
                
        //Select general settings
        self::$general_settings = post_pay_counter_functions_class::get_settings( 'general' );
        
        //If current_version option does not exist or is DIFFERENT from the latest release number, launch the update procedures. If update is run, also updates all the class variables and the option in the db
        if( ! ( self::$ppc_current_version = get_option( 'ppc_current_version' ) ) OR self::$ppc_current_version != self::$ppc_newest_version ) {
            post_pay_counter_update_procedures::update();
            post_pay_counter_functions_class::options_changed_vars_update_to_reflect( TRUE );
            post_pay_counter_functions_class::manage_cap_allowed_user_groups_plugin_pages( self::$allowed_user_roles_options_page, self::$allowed_user_roles_stats_page );
            update_option( 'ppc_current_version', self::$ppc_newest_version );
            self::$ppc_current_version = self::$ppc_newest_version;
            echo '<div id="message" class="updated fade"><p><strong>Post Pay Counter was successfully updated to version '.self::$ppc_current_version.'.</strong> Want to have a look at the <a href="'.admin_url( self::$post_pay_counter_options_menu_link ).'" title="Go to Options page">Options page</a>, or at the <a href="http://wordpress.org/extend/plugins/post-pay-counter/changelog/" title="Go to Changelog">Changelog</a>?</p></div>';
        }
            
        //Just as a comfort, define the word suitable for countings, depending on the chosen counting type 
        if( self::$general_settings->counting_type_words == 1 )
            self::$current_counting_method_word = 'words';
        else
            self::$current_counting_method_word = 'visits';
        
        //Define publication time range depending on chosen settings: if monthly it depends on current month days number, weekly always 7, otherwise custom
        if( self::$general_settings->publication_time_range_week == 1 ) {
            self::$publication_time_range_start   = time() - ( ( date( 'N' )-1 )*24*60*60 );
            self::$publication_time_range_end     = time();
        } else if( self::$general_settings->publication_time_range_month == 1 ) {
            self::$publication_time_range_start   = time() - ( ( date( 'j' )-1 )*24*60*60 );
            self::$publication_time_range_end     = time();
        } else if( self::$general_settings->publication_time_range_custom == 1 ) {
            self::$publication_time_range_start   = time() - ( self::$general_settings->publication_time_range_custom_value*24*60*60 );
            self::$publication_time_range_end     = time();
        }
        
        //...Define allowed post types, status, user roles to include in counting and user roles allowed to see plugin pages
        post_pay_counter_functions_class::options_changed_vars_update_to_reflect();
        
        /*//Define visits time range 
        if( self::$general_settings->visits_time_range_equal_to_pub ) {
            self::visits_time_range_start  = self::$general_settings->publication_time_range_start;
            self::visits_time_range_end    = self::$general_settings->publication_time_range_end;
        } else if( self::$general_settings->visits_time_range_rules_selection ) {
            self::visits_time_range_start  = self::$general_settings->publication_time_range_start;
            self::visits_time_range_end    = self::$general_settings->publication_time_range_end;
        } */
        
        //If debug is requested, print a lot of debug stuff that should allow me to troubleshoot any problem users may encounter
        if( self::POST_PAY_COUNTER_DEBUG == TRUE ) {
            echo 'PHP Version: '.phpversion().'<br />';
            echo 'Installed plugin version: '.self::$ppc_current_version.'<br />';
            echo 'General settings object: ';var_dump( self::$general_settings );
            echo 'PPC class vars: ';var_dump( $this );
            echo 'WP Permissions: ',var_dump( get_option( 'wp_user_roles' ) );
            echo 'PPC install errors: ';var_dump( get_option( 'ppc_install_error' ) );
        }
        
        //Add left menu entries for both stats and options pages; update procedure
        add_action( 'admin_menu', array( $this, 'post_pay_counter_admin_menus' ) );
        
        //Hook for the install procedure
        register_activation_hook( __FILE__, array( 'post_pay_counter_install_routine', 'post_pay_counter_install' ) );
        
        //Hook on blog adding on multisite wp to install the plugin there either
        add_action( 'wpmu_new_blog', array( 'post_pay_counter_install_routine', 'post_pay_counter_new_blog_install' ), 10, 6); 
        
        //Hook to update single posts counting on status change
        add_action( 'transition_post_status', array( $this, 'post_pay_counter_update_post_counting' ), 10, 3 );
        
        //Load the styles/jses for metaboxes, then call all the add_meta_box functions and implement the jQuery datepicker
        add_action( 'load-post-pay-counter_page_post_pay_counter_options', array( $this, 'on_load_post_pay_counter_options_page' ) );
        add_action( 'load-toplevel_page_post_pay_counter_show_stats', array( $this, 'on_load_post_pay_counter_stats_page' ) );
        
        //Inject proper css stylesheets to make the two meta box columns 50% large equal and js tooltip activation snippet
        add_action( 'admin_head-post-pay-counter_page_post_pay_counter_options', array( $this, 'post_pay_counter_head' ) );
        
        //Hook in wp_head to record visits when counting type is visits
        add_action( 'wp_head', array( $this, 'post_pay_counter_count_view' ) );
        
        //Hook to show custom action links besides the usual "Edit" and "Deactivate"
        add_filter('plugin_action_links', array( $this, 'post_pay_counter_settings_meta_link' ), 10, 2);
        add_filter('plugin_row_meta', array( $this, 'post_pay_counter_donate_meta_link' ), 10, 2);
        
        //Hook to show the posts' word count as a column in the posts list
        add_filter( 'manage_posts_columns', array( $this, 'post_pay_counter_column_word_count' ) );
        add_action( 'manage_posts_custom_column', array( $this, 'post_pay_counter_column_word_count_populate' ) );
        
        //Manage AJAX calls (visit counting)
        add_action( 'wp_ajax_post_pay_counter_register_view_ajax', array( $this, 'post_pay_counter_register_view_ajax' ) );
        add_action( 'wp_ajax_nopriv_post_pay_counter_register_view_ajax', array( $this, 'post_pay_counter_register_view_ajax' ) );
        //Manage AJAX calls (marking posts as paid)
        add_action( 'wp_ajax_post_pay_counter_post_paid_status', array( 'post_pay_counter_functions_class', 'post_pay_counter_post_paid_update' ) );
    }
    
    //Adds first level side menu
    function post_pay_counter_admin_menus() {
        add_menu_page( 'Post Pay Counter', 'Post Pay Counter', 'post_pay_counter_access_stats', 'post_pay_counter_show_stats', array( $this, 'post_pay_counter_show_stats' ) );
        add_submenu_page( 'post_pay_counter_show_stats', 'Post Pay Counter Stats', 'Stats', 'post_pay_counter_access_stats', 'post_pay_counter_show_stats', array( $this, 'post_pay_counter_show_stats' ) );
        self::$post_pay_counter_stats_menu_link     = 'admin.php?page=post_pay_counter_show_stats';
        self::$post_pay_counter_options_menu_slug   = add_submenu_page( 'post_pay_counter_show_stats', 'Post Pay Counter Options', 'Options', 'post_pay_counter_manage_options', 'post_pay_counter_options', array( $this, 'post_pay_counter_options' ) );
        self::$post_pay_counter_options_menu_link   = 'admin.php?page=post_pay_counter_options';
    }
    
    function post_pay_counter_head() { ?>        
<script type="text/javascript">
    /* <![CDATA[ */
        jQuery(document).ready(function($) {
            $(".tooltip_container").tipTip({
                activation: "hover",
                keepAlive:  "true",
                maxWidth:   "300px"
            });
            
            //Enter key will always trigger the Save Options submit, not the others
            $("#post_pay_counter_form input").keypress(function (e) {
                if ((e.which && e.which == 13) || (e.keyCode && e.keyCode == 13)) {
                    $('#post_pay_counter_options_save').click();
                    return false;
                } else {
                    return true;
                }
            });
            
        });
    /* ]]> */
</script>

<style type="text/css">
    #side-info-column {
        width: 49%;
    }
    .inner-sidebar #side-sortables {
        width: 100%;
    }
    .has-right-sidebar #post-body-content {
        width: 49%;
        margin-right: 390px;
    }
    .has-right-sidebar #post-body {
        margin-right: -50%;
    }
    #post-body #normal-sortables {
        width: 100%;
    }
    .section_title {
        font-weight: bold;
        text-align: left;
        margin-bottom: -5px;
        margin-top: 20px;
    }
</style>
    <?php }
    
    //Reponsable of the datepicker's files loading
    function on_load_post_pay_counter_stats_page() {
        wp_enqueue_script( 'jquery-ui-datepicker', plugins_url( 'js/jquery.ui.datepicker.min.js', __FILE__ ), array('jquery', 'jquery-ui-core' ) );
        wp_enqueue_style( 'jquery.ui.theme', plugins_url( 'style/ui-lightness/jquery-ui-1.8.15.custom.css', __FILE__ ) );
    }
    
    function on_load_post_pay_counter_options_page() {
        //This is metaboxes stuff
        wp_enqueue_script( 'post' );
        add_meta_box( 'post_pay_counter_counting_settings', 'Counting Settings', array( $this, 'meta_box_counting_settings' ), self::$post_pay_counter_options_menu_slug, 'normal' );
        add_meta_box( 'post_pay_counter_trial_settings', 'Trial Settings', array( $this, 'meta_box_trial_settings' ), self::$post_pay_counter_options_menu_slug, 'normal' );
        add_meta_box( 'post_pay_counter_update_countings', 'Update Stats', array( $this, 'meta_box_update_countings' ), self::$post_pay_counter_options_menu_slug, 'normal' );
        add_meta_box( 'post_pay_counter_personalize_settings', 'Personalize Settings', array( $this, 'meta_box_personalize_settings' ), self::$post_pay_counter_options_menu_slug, 'side' );
        add_meta_box( 'post_pay_counter_permissions', 'Permissions', array( $this, 'meta_box_permissions' ), self::$post_pay_counter_options_menu_slug, 'side' );
        add_meta_box( 'post_pay_counter_support', 'Support the author', array( $this, 'meta_box_support_the_author' ), self::$post_pay_counter_options_menu_slug, 'side' );
        
        //And this is for the tooltips
        wp_enqueue_script( 'jquery-tooltip-plugin', plugins_url( 'js/jquery.tiptip.min.js', __FILE__ ), array( 'jquery' ) );
        wp_enqueue_style( 'jquery.tooltip.theme', plugins_url( 'style/tipTip.css', __FILE__ ) );
    }
    
    //Show the "Settings" link in the plugins list (under the title)
    function post_pay_counter_settings_meta_link( $links, $file ) {
       //Make sure we are on the right plugin
       if ( $file == plugin_basename( __FILE__ ) )
            $links[] = '<a href="'.admin_url( self::$post_pay_counter_options_menu_link ).'" title="'.__('Settings').'">'.__('Settings').'</a>';
     
        return $links;
    }
    
    //Show the "Donate" link in the plugins list (under the description)
    function post_pay_counter_donate_meta_link( $links, $file ) {
       //Make sure we are on the right plugin
       if ( $file == plugin_basename( __FILE__ ) )
            $links[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7UH3J3CLVHP8L" title="'.__('Donate').'">'.__('Donate').'</a>';
     
        return $links;
    }
    
    //Adds the 'Word count' column in the post list page
    function post_pay_counter_column_word_count( $columns ) {
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
        
		if( ! is_array( $ordinary_zones->ordinary_zones ) )
			$ordinary_zones = unserialize( $counting_settings->ordinary_zones );
        
        //If posts word count should be showed, we check if the counting system zones is in use and, if yes, compare the word count to the first zone count. When word count is below the first zone, its opacity is reduced
        if( $counting_settings->can_view_posts_word_count_post_list == 1 ) {
            if( $name == 'post_pay_counter_word_count' ) {
                $word_count = post_pay_counter_functions_class::count_post_words( $post->post_content );
                
                if( self::$general_settings->counting_type_words == 1 AND $counting_settings->counting_system_zones == 1 AND $word_count < $ordinary_zones[1]['zone'] )
                    echo '<span style="opacity: 0.60">'.$word_count.' words</span>';
                else
                    echo $word_count.' words';
            }
        }
    }
    
    //Function to record visits with plugin's method
    function post_pay_counter_count_view() {
    	global $wpdb,
               $current_user,
               $post;
        
        //If not a single post page, return
        if( ! is_singular() )
            return;
        
        //On some blogs the plugin triggered errors because the $post var was an array instead of an object. Dunno how and why it could happen, but this fixes that
        $post = (object) $post;
        
        //Intersecate the unserialized array of allowed user groups with the groups the post writer belongs to, then continue only if resulting array is not empty 
        $user_roles_intersection = array_intersect( unserialize( self::$general_settings->user_roles_to_include_in_counting ), get_userdata( $post->post_author )->roles );
        
        //Continue only if counting type is plugin visits. Only accept posts of the allowed post types, status and user groups
    	if( strpos( self::$allowed_status, $post->post_status ) !== FALSE 
        AND strpos( self::$allowed_post_types, $post->post_type ) !== FALSE 
        AND ! empty( $user_roles_intersection ) 
        AND self::$general_settings->counting_type_visits == 1 
        AND self::$general_settings->counting_type_visits_method_plugin == 0 ) {
            
            //If the post has expired (meaning the number of past days since its publishing exceeds the counting payment time range selected by the admin), return
            /*if( ( time() - $post->post_pay_counter ) > ( post_pay_counter_functions_class::publication_time_range_end - post_pay_counter_functions_class::publication_time_range_start ) ) )
                return;*/
            
            //Skip visits that shouldn't be counted: logged-in users/authors and guests things
            if( ( is_user_logged_in() AND ( self::$general_settings->count_visits_registered == 0 OR ( $post->post_author == $current_user->ID AND self::$general_settings->count_visits_authors == 0 ) ) )
            OR ( ! is_user_logged_in() AND self::$general_settings->count_visits_guests == 0 ) )
                return;
            
            //If bots visits shouldn't be counted, and current visit is from a bot, return
            if( self::$general_settings->count_visits_bots == 0 ) {
                
                //Thanks to Wp-Postviews for the array list
    			$bots_to_exclude = array( 
                    'Google'        => 'googlebot', 
                    'Google'        => 'google', 
                    'MSN'           => 'msnbot', 
                    'Alex'          => 'ia_archiver', 
                    'Lycos'         => 'lycos', 
                    'Ask Jeeves'    => 'jeeves', 
                    'Altavista'     => 'scooter', 
                    'AllTheWeb'     => 'fast-webcrawler', 
                    'Inktomi'       => 'slurp@inktomi', 
                    'Turnitin.com'  => 'turnitinbot', 
                    'Technorati'    => 'technorati', 
                    'Yahoo'         => 'yahoo', 
                    'Findexa'       => 'findexa', 
                    'NextLinks'     => 'findlinks', 
                    'Gais'          => 'gaisbo', 
                    'WiseNut'       => 'zyborg', 
                    'WhoisSource'   => 'surveybot', 
                    'Bloglines'     => 'bloglines', 
                    'BlogSearch'    => 'blogsearch', 
                    'PubSub'        => 'pubsub', 
                    'Syndic8'       => 'syndic8', 
                    'RadioUserland' => 'userland', 
                    'Gigabot'       => 'gigabot', 
                    'Become.com'    => 'become.com'
                );
    			
    			foreach( $bots_to_exclude as $single ) {
    				if( stristr($_SERVER['HTTP_USER_AGENT'], $single ) !== false ) {
    					return;
    				}
    			}
    		}
            
            //If visitor doesn't have a valid cookie, set it and update db visits count
            //Set cookie via AJAX request (wp_head is too late to set a cookie beacuse of headers already sent, and init is too early because of $post unavailability)
            if( ! isset( $_COOKIE['post_pay_counter_view-'.$post->ID] ) ) { ?>
        
        <!-- Post Pay Counter views count start -->
        <script type="text/javascript">
            /* <![CDATA[ */
                var data = {
                    action:                 "post_pay_counter_register_view_ajax",
                    security_nonce:         "<?php echo wp_create_nonce( 'post_pay_counter_register_view_ajax' ); ?>",
                    post_id:                "<?php echo $post->ID; ?>",
                    post_status:            "<?php echo $post->post_status; ?>",
                    post_date:              "<?php echo $post->post_date; ?>",
                    post_author:            "<?php echo $post->post_author; ?>",
                    post_pay_counter_date:  "<?php echo $post->post_pay_counter; ?>",
                    post_pay_counter_count: "<?php echo $post->post_pay_counter_count; ?>",
                };
                
                jQuery.post( "<?php echo admin_url( 'admin-ajax.php' ); ?>", data );
            /* ]]> */
        </script>						
        <!-- Post Pay Counter views count end -->
        
    		<?php }
    	}
    }
    
    //Sets the view cookie and register the visit in the db (AJAX called)
    function post_pay_counter_register_view_ajax() {
        //Verify that the request was really issued by the plugin
        check_ajax_referer( 'post_pay_counter_register_view_ajax', 'security_nonce' );        
        
        //Set cookie and update db visits value
        setcookie( 'post_pay_counter_view-'.$_REQUEST['post_id'], 'post_pay_counter_view-'.home_url().'-'.$_REQUEST['post_id'], time()+86400, '/' );
        post_pay_counter_functions_class::update_single_counting( $_REQUEST['post_id'], $_REQUEST['post_status'], $_REQUEST['post_date'], $_REQUEST['post_author'], $_REQUEST['post_pay_counter_date'], $_REQUEST['post_pay_counter_count'] );
        exit;
    }
    
    //Shows the content of the metabox related to counting settings
    function meta_box_counting_settings() {
        global $wp_roles;
        
        if ( ! isset( $wp_roles ) )
            $wp_roles = new WP_Roles();
        
        if( self::$edit_options_counter_settings->userID == 'general' ) { ?>
        <div class="section_title" style="margin-top: 0px;">Counting type</div>
            <?php post_pay_counter_options_functions_class::echo_p_field( 'Count posts pay basing on their words', self::$edit_options_counter_settings->counting_type_words, 'radio', 'counting_type', 'The words that make up posts content will be used to compute the right pay, basing on the next bunch of settings. Change will automatically trigger a stats regenerate.', 'Words', 'counting_type_words' );
            post_pay_counter_options_functions_class::echo_p_field( 'Count posts pay basing on their visits', self::$edit_options_counter_settings->counting_type_visits, 'radio', 'counting_type', 'Unique daily visits will be used to compute the right pay, basing on the next bunch of settings. A simple cookie is used (<strong>notice</strong> that deleting it and refreshing the post page make the counter to log a new visit), and you can define what kinds of visits you want to be counted. Change will automatically trigger a stats regenerate.', 'Visits', 'counting_type_visits' ); ?>
            
        <!-- <div id="counting_type_visits_methods">
            <div class="section_title">Counting method</div>
        <?php /*post_pay_counter_options_functions_class::echo_p_field( 'Count visits using the plugin\'s built-in system', self::$edit_options_counter_settings->counting_type_visits_method_plugin, 'radio', 'counting_type_visits_method', 'A simple cookie is used (<strong>notice</strong> that deleting it and refreshing the post page make the counter to log a new visit), and you can define what kinds of visits you want to be counted. This is the simplest method, that does not need you to be registered anywhere.', 'counting_type_visits_method_plugin', 'counting_type_visits_method_plugin' );
        post_pay_counter_options_functions_class::echo_p_field( 'Count visits using Google Analytics', self::$edit_options_counter_settings->counting_type_visits_method_google_analytics, 'radio', 'counting_type_visits_method', 'Use your Google Analytics account to count the visits related to each post and use those data to compute payments. Your domain obviously has to be already registered on Google Analytics. No data (including your authentication credentials) will be sent to the plugin\'s author nor to any third parties. ', 'counting_type_visits_method_google_analytics', 'counting_type_visits_method_google_analytics' );*/ ?>
        </div> -->
        
        <div class="section_title">Counting system</div>
        <?php } else { ?>
        <div class="section_title" style="margin-top: 0px;">Counting system</div>
        <?php }
        post_pay_counter_options_functions_class::echo_p_field( 'Use the multiple zones system', self::$edit_options_counter_settings->counting_system_zones, 'radio', 'counting_system', 'With this system you can define up to 5 zones of retribution, so that from X words/visits to Y words/visits the same pay will be applied (eg. from 200 words to 300 words pay 2.00). It does not matter how many words/visits a post has, but only in what zone it lies on.', 'counting_system_zones', 'counting_system_zones' ); ?>
        <div id="counting_system_zones_content">
            <table style="border: none; margin-left: 3em; width: 100%;">
    			<thead>
                    <tr>
                        <th width="50%" align="left">Words/Visits n&deg;</th>
    			        <th width="50%" align="left">Payment</th>
                    </tr>
                </thead>
    			<tbody>
                
        <?php $n = 1; 
        while( $n <= 5 ) { ?>
                    <tr>
                        <td width="50%"><input type="text" name="zone<?php echo $n; ?>_count" value="<?php echo self::$edit_options_counter_settings->ordinary_zones[$n]['zone']; ?>" /></td>
                        <td width="50%"><input type="text" name="zone<?php echo $n; ?>_payment" value="<?php printf( '%.2f', self::$edit_options_counter_settings->ordinary_zones[$n]['payment'] ); ?>" /></td>
                    </tr>
            <?php ++$n;
        } ?>
                
                </tbody>
    		</table>
            
            <?php if( count( self::$edit_options_counter_settings->ordinary_zones ) > 5 ) {
                $add_five_more_zones_checked = ' checked="checked"';
            } ?>
            
            <p class="p_options">
                <span style="float: right; width: 20px; text-align: right;">
                    <img src="<?php echo plugins_url( 'style/images/info.png', __FILE__ ); ?>" title="If you need to fragment your payment zones more accurately, this allows you to add five more zones to the counting routine. When checked, the plugin will automatically take into account all the ten zones, while when unchecked the standard five are used." class="tooltip_container" />
                </span>
                <label>
                    <span style="float: left; width: 5%;">    
                        <input type="checkbox" name="add_five_more_zones" id="add_five_more_zones"<?php echo @$add_five_more_zones_checked; ?> />
                    </span>
                    <span style="width: 90%;">Add five more zones</span>
                </label>
            </p>

            
            
            <div id="add_five_more_zones_content">
                <table style="border: none; margin-left: 3em; width: 100%;">
    			<thead>
                    <tr>
                        <th width="50%" align="left">Words/Visits n&deg;</th>
    			        <th width="50%" align="left">Payment</th>
                    </tr>
                </thead>
    			<tbody>
                    
            <?php while( $n <= 10 ) { ?>
                    <tr>
                        <td width="50%"><input type="text" name="zone<?php echo $n; ?>_count" value="<?php echo @self::$edit_options_counter_settings->ordinary_zones[$n]['zone']; ?>" /></td>
                        <td width="50%"><input type="text" name="zone<?php echo $n; ?>_payment" value="<?php printf( '%.2f', @self::$edit_options_counter_settings->ordinary_zones[$n]['payment'] ); ?>" /></td>
                    </tr>
                <?php ++$n;
            } ?>
                    
                </tbody>
    		</table>
            </div>
            
        </div>
        <?php post_pay_counter_options_functions_class::echo_p_field( 'Use the unique payment system', self::$edit_options_counter_settings->counting_system_unique_payment, 'radio', 'counting_system', 'With this system, every word/visit is important since each single one more means a higher pay. Just think that the words/visits number will be multiplied for the unique payment value you enter.', 'counting_system_unique_payment', 'counting_system_unique_payment' ); ?>
        <div style="margin-left: 3em;" id="counting_system_unique_payment_content">
            <label>Unique payment value <input type="text" name="unique_payment_value" value="<?php echo self::$edit_options_counter_settings->unique_payment ?>" /></label>
            
        </div>
        
        <?php if( self::$edit_options_counter_settings->userID == 'general' ) { ?>
        <div class="section_title">Counting options</div>
        <div id="counting_type_visits_options">
            <!--<div id="counting_type_visits_method_plugin_content">-->
        <?php post_pay_counter_options_functions_class::echo_p_field( 'Count visits from guests', self::$edit_options_counter_settings->count_visits_guests, 'checkbox', 'count_visits_guests', 'Define whether visits coming from <em>non</em> logged-in users should be counted or not.' );
        post_pay_counter_options_functions_class::echo_p_field( 'Count visits from registered users', self::$edit_options_counter_settings->count_visits_registered, 'checkbox', 'count_visits_registered', 'Define whether visits coming from logged-in users should be counted or not.' );
        post_pay_counter_options_functions_class::echo_p_field( 'Count visits from the post author', self::$edit_options_counter_settings->count_visits_authors, 'checkbox', 'count_visits_authors', 'Define whether visits coming from the author of the selected post should be counted or not.' );
        post_pay_counter_options_functions_class::echo_p_field( 'Count visits from bots', self::$edit_options_counter_settings->count_visits_bots, 'checkbox', 'count_visits_bots', 'Define whether visits coming from search engines crawlers should be counted or not.' ); ?>
            <!--</div>
            <div id="counting_type_visits_method_google_analytics_content">
                <label for="counting_type_visits_system_google_analytics_email" style="width: 125px; float: left; margin-top: 12px;">Analytics email:</label>
                <input style="height: 20px; margin-top: 12px;" type="text" id="counting_type_visits_method_google_analytics_email" name="counting_type_visits_method_google_analytics_email" size="30" value="<?php //echo self::$edit_options_counter_settings->counting_type_visits_method_google_analytics_email ?>" />
                <span style="float: right; width: 20px; text-align: right; margin-top: 12px;">
                    <img src="<?php //echo plugins_url( 'style/images/info.png', __FILE__ ); ?>" title="Put here the email address associated with the Google Analytics account you use to monitor this website. It will not be used anywhere but for the authentication to your Google account to retrieve the visits data (nor it will be sent to any remote server)." class="tooltip_container" />
                </span>
                <br />
                <label for="counting_type_visits_method_google_analytics_password" style="width: 125px; float: left;">Analytics password:</label>
                <input style="height: 20px;" type="text" id="counting_type_visits_method_google_analytics_password" name="counting_type_visits_method_google_analytics_password" size="30" value="<?php //echo self::$edit_options_counter_settings->counting_type_visits_method_google_analytics_password ?>" />
                <span style="float: right; width: 20px; height: 13px; text-align: right;">
                    <img src="<?php //echo plugins_url( 'style/images/info.png', __FILE__ ); ?>" title="Put here the password associated with the Google Analytics account you use to monitor this website. It will not be used anywhere but for the authentication to your Google account to retrieve the visits data (nor it will be sent to any remote server)." class="tooltip_container" />
                </span>
                <br />
                <label for="counting_type_visits_method_google_analytics_profile_id" style="width: 125px; float: left;">Analytics profile ID:</label>
                <input style="height: 20px;" type="text" id="counting_type_visits_method_google_analytics_profile_id" name="counting_type_visits_method_google_analytics_profile_id" size="30"
                 value="<?php //echo self::$edit_options_counter_settings->counting_type_visits_method_google_analytics_profile_id ?>" />
                 <span style="float: right; width: 20px; height: 13px; text-align: right;">
                    <img src="<?php //echo plugins_url( 'style/images/info.png', __FILE__ ); ?>" title="Put here the profile ID associated with the this website in Google Analytics. If you do not know <strong>where to find it</strong>, go to the plugin's FAQs or Google a bit, just know that it is not the UA-xxxxxxx-x one." class="tooltip_container" />
                </span>
        <?php /*post_pay_counter_options_functions_class::echo_p_field( 'Use pageviews', self::$edit_options_counter_settings->counting_type_visits_method_google_analytics_pageviews, 'radio', 'counting_type_visits_method_google_analytics_pageviews', 'Quoting Google Support: \'A pageview is defined as a view of a page on your site that is being tracked by the Analytics tracking code. If a visitor hits reload after reaching the page, this will be counted as an additional pageview\'', 'counting_type_visits_method_google_analytics_pageviews', 'counting_type_visits_method_google_analytics_pageviews' );
            post_pay_counter_options_functions_class::echo_p_field( 'Use unique pageviews', self::$edit_options_counter_settings->counting_type_visits_method_google_analytics_unique_pageviews, 'radio', 'counting_type_visits_method_google_analytics_pageviews', 'Quoting Google Support: \'A unique pageview, as seen in the Top Content report, aggregates pageviews that are generated by the same user during the same session. A unique pageview represents the number of sessions during which that page was viewed one or more times\'', 'counting_type_visits_method_google_analytics_unique_pageviews', 'counting_type_visits_method_google_analytics_unique_pageviews' );*/ ?>
                <div class="section_title">Update frequency</div>
        <?php /*post_pay_counter_options_functions_class::echo_p_field( 'Update data every request', self::$edit_options_counter_settings->counting_type_visits_method_google_analytics_update_request, 'radio', 'counting_type_visits_method_google_analytics_update_time', 'Choose this if you want the plugin to always provide updated data from Google Analytics. Your countings will always be as updated as possible, but it will be slower since it will have to request data from Google every time stats are viewed. Overall stats, when shown, always rely on the local database not to slow down the whole page.', 'counting_type_visits_method_google_analytics_update_request', 'counting_type_visits_method_google_analytics_update_request' );
            post_pay_counter_options_functions_class::echo_p_field( 'Update data every hour', self::$edit_options_counter_settings->counting_type_visits_method_google_analytics_update_hour, 'radio', 'counting_type_visits_method_google_analytics_update_time', 'Choose this if you want the plugin to update from Google Analytics every hour. Countings will not be as updated as if data were requested each stats view, but plugin\'s pages will be faster while still having decent updated countings. Overall stats, when shown, always rely on the local database not to slow down the whole page.', 'counting_type_visits_method_google_analytics_update_hour', 'counting_type_visits_method_google_analytics_update_hour' );
            post_pay_counter_options_functions_class::echo_p_field( 'Update data every day', self::$edit_options_counter_settings->counting_type_visits_method_google_analytics_update_day, 'radio', 'counting_type_visits_method_google_analytics_update_time', 'Choose this if you want the plugin to update data from Google Analytics every day. This will provide the least updated data among the three choices, but since it relies most on the local database, it is the fastest. Overall stats, when shown, always rely on the local database not to slow down the whole page.', 'counting_type_visits_method_google_analytics_update_day', 'counting_type_visits_method_google_analytics_update_day' );*/ ?>
                <div class="section_title">Visits time range</div>
        <?php /*post_pay_counter_options_functions_class::echo_p_field( 'Visits range is equal to publication range', self::$edit_options_counter_settings->visits_time_range_equal_to_pub, 'radio', 'visits_time_range', 'When computing stats, the default setting will work as follows: given a publication time range, all the posts which publication date falls into that range are selected. Then, the related visits are retrieved from Google Analytics taking into account the visits recorded in the selected publication time range. E.g.: you ask for posts published from 1<sup>st</sup> May to 31<sup>st</sup> May: to compute their payments visits from 1<sup>st</sup> May to 31<sup>st</sup> May are taken into account, even for a post that was published on 31<sup>st</sup>. However, this will be the default setting: it will still be possible to change the visits time range the way you want.', 'visits_time_range_equal_to_pub', 'visits_time_range_equal_to_pub' );
        post_pay_counter_options_functions_class::echo_p_field( 'Given a publication range, the visits one is defined accordingly', self::$edit_options_counter_settings->visits_time_range_each_post_accordingly, 'radio', 'visits_time_range', 'When computing stats, the default setting will work as follows: given a publication time range, visits that were recorded between the publication date of a certain post and the desired number of days after are retrieved. In this way, each post will have a different visits time range, making sure that every post is paid basing on the same time frame. In the stats page, different graphics will highlight the posts of which the visits time range is complete, and the ones of which it is not. E.g.: If you choose this option, and set the related input field to 30 days, it will mean that a post published on 1<sup>st</sup> May will have taken into account all the visits recorded between 1<sup>st</sup> and 31<sup>rd</sup> May; and if you will look at stats on 20<sup>th</sup>, the plugin will tell you that only 20 days have been taken into account, and that thus the counting is not really complete. However, this will be the default setting: it will still be possible to change the visits time range the way you want.', 'visits_time_range_each_post_accordingly', 'visits_time_range_each_post_accordingly' );*/ ?>
                <div id="visits_time_range_each_post_accordingly_content">
                    <label for="visits_time_range_each_post_accordingly_value" style="width: 230px; float: left; margin-left: 3em;">Take into account visits for the following (days):</label>
                    <input style="height: 20px;" type="text" id="visits_time_range_each_post_accordingly_value" name="visits_time_range_each_post_accordingly_value" size="5" maxlength="5"
                     value="<?php //echo self::$edit_options_counter_settings->visits_time_range_each_post_accordingly_value ?>" />
                     <span style="float: right; width: 20px; height: 13px; text-align: right;">
                        <img src="<?php //echo plugins_url( 'style/images/info.png', __FILE__ ); ?>" title="Put here the number of days (max 5 digits) of which you want the plugin to take into account visits when computing payments." class="tooltip_container" />
                    </span>
                </div>
        <?php /*post_pay_counter_options_functions_class::echo_p_field( 'Given a visits range, all the posts having visits are selected', self::$edit_options_counter_settings->visits_time_range_rules_selection, 'radio', 'visits_time_range', 'When computing stats, the default settings will work as follows: given a visits time range, all the posts having visits recorded in that time frame will be selected. This way you will be sure that every single visit, even after years the post has been published, will be paid and rewarded. This could be an incentive for your writers to write less but better posts. However, this will be the default setting: it will still be possible to change the visits time range the way you want.', 'visits_time_range_rules_selection', 'visits_time_range_rules_selection' );*/ ?>
            </div>-->
        </div>
        <div id="counting_type_words_options">
        <?php post_pay_counter_options_functions_class::echo_p_field( 'Count pending revision posts', self::$edit_options_counter_settings->count_pending_revision_posts, 'checkbox', 'count_pending_revision_posts', 'While published posts are automatically counted, you can decide to include pending revision ones or not. Change will automatically trigger a stats regenerate.' );
        post_pay_counter_options_functions_class::echo_p_field( 'Count future scheduled posts', self::$edit_options_counter_settings->count_future_scheduled_posts, 'checkbox', 'count_future_scheduled_posts', 'While published posts are automatically counted, you can decide to include future planned ones or not. Change will automatically trigger a stats regenerate.' );
        post_pay_counter_options_functions_class::echo_p_field( 'Exclude quoted content from word counting', self::$edit_options_counter_settings->exclude_quotations_from_countings, 'checkbox', 'exclude_quotations_from_countings', 'If checked all the words contained into a <em>quote</em> tag will not be taken into account when counting. Use this to prevent interviews and such stuff to be counted as normal words. Change will automatically trigger a stats regenerate.' ); ?>
        </div>
        
        <div class="section_title">Post types to include in counting</div>
        <span style="float: right; width: 20px; text-align: right; margin-top: -12px;">
            <img src="<?php echo plugins_url( 'style/images/info.png', __FILE__ ); ?>" title="All the posts of the post types below that you will check will be included in counting. There are some you may have never seen: they are Wordpress built-in ones. Post related to normal posts and Page to normal pages. Change will automatically trigger a stats regenerate." class="tooltip_container" />
        </span>
        
        <?php //Get WP post types and current included ones
        $custom_post_types          = get_post_types();
        $custom_post_types_current  = unserialize( self::$edit_options_counter_settings->post_types_to_include_in_counting );
        
        //Cycle through the WP post types and display a list of them, checking the related checkbox when post type is also present in the current allowed ones array
        foreach ( $custom_post_types as $single ) {
            
            if( in_array( $single, $custom_post_types_current ) ) {
                $checked = 'checked="checked"';
            } ?>
                
            <p style="height: 11px;">
                <label for="custom_post_type_<?php echo $single; ?>">
                    <input type="checkbox" name="custom_post_type_<?php echo $single; ?>" id="custom_post_type_<?php echo $single; ?>" value="<?php echo $single; ?>" <?php echo @$checked; ?> /> <?php echo ucfirst( $single ); ?>
                </label>
            </p>
        <?php unset( $checked );
        } ?>
        
        <div class="section_title">User groups to include in counting</div>
        <span style="float: right; width: 20px; text-align: right; margin-top: -12px;">
            <img src="<?php echo plugins_url( 'style/images/info.png', __FILE__ ); ?>" title="All the posts of the post types below that you will check will be included in counting. There are some you may have never seen: they are Wordpress built-in ones. Post relates to normal posts and Page to normal pages. Change will automatically trigger a stats regenerate." class="tooltip_container" />
        </span>
        
        <?php //Foreach user roles and show them
        foreach( $wp_roles->role_names as $key => $value ) {
            
            if( in_array( $key, unserialize( self::$general_settings->user_roles_to_include_in_counting ) ) ) {
                $checked = 'checked="checked"';
            } ?>
            
            <p style="height: 11px;">
                <label for="user_role_<?php echo $key; ?>">
                    <input type="checkbox" name="user_role_<?php echo $key; ?>" id="user_role_<?php echo $key; ?>" value="<?php echo $key; ?>" <?php echo @$checked; ?> /> <?php echo $value; ?>
                </label>
            </p>
        <?php unset( $checked );
        } ?>
        
        <div class="section_title">Publication time range</div>
        <?php post_pay_counter_options_functions_class::echo_p_field( 'Payment takes place weekly', self::$edit_options_counter_settings->publication_time_range_week, 'radio', 'publication_time_range', 'With this, the plugin will display in the stats all the published posts from the beginning of the week to the current day (week starts on Monday). This will be the default settings: you will still be able to change the time range the way you want it. You should select this if you usually pay your writers weekly.', 'publication_time_range_week', 'publication_time_range_week' );
        post_pay_counter_options_functions_class::echo_p_field( 'Payment takes place monthly', self::$edit_options_counter_settings->publication_time_range_month, 'radio', 'publication_time_range', 'With this, the plugin will display in the stats all the published posts from the beginning of the month to the current day. This will be the default settings: you will still be able to change the time range the way you want it. You should select this if you usually pay your writers monthly.', 'publication_time_range_month', 'publication_time_range_month' );
        post_pay_counter_options_functions_class::echo_p_field( 'Payment takes place on a custom basis', self::$edit_options_counter_settings->publication_time_range_custom, 'radio', 'publication_time_range', 'With this, you can manually customize the rime range for the published posts the plugin will display in the stats. This will be the default settings: you will still be able to change the time range the way you want it. So, for example, if you set this to 365 days, in the stats page it will automatically be selected a time frame that goes from the current day to the previous 365 days', 'publication_time_range_custom', 'publication_time_range_custom' ); ?>
        <div id="publication_time_range_custom_content">
            <label for="payment_data_range_custom_value" style="width: 230px; float: left; margin-left: 3em;">How often payment takes place (days):</label>
            <input style="height: 20px;" type="text" id="publication_time_range_custom_value" name="publication_time_range_custom_value" size="5" maxlength="5"
             value="<?php echo self::$edit_options_counter_settings->publication_time_range_custom_value ?>" />
             <span style="float: right; width: 20px; height: 13px; text-align: right;">
                <img src="<?php echo plugins_url( 'style/images/info.png', __FILE__ ); ?>" title="Put here the number of days (max 5 digits) you want the plugin to take into account when selecting posts." class="tooltip_container" />
            </span>
        </div>
        <?php } ?>
        
        <div class="section_title">Additional settings</div>
        <p>
            <label>Award a &euro; <input type="text" name="bonus_comment_payment" value="<?php echo self::$edit_options_counter_settings->bonus_comment_payment ?>" size="2" /> bonus</label> <label>when a post goes over <input type="text" name="bonus_comment_count" value="<?php echo self::$edit_options_counter_settings->bonus_comment_count ?>" size="1" /> comments</label>
            <br />
            <label>After the first image, credit &euro; <input type="text" name="bonus_image_payment" value="<?php echo self::$edit_options_counter_settings->bonus_image_payment ?>" size="3" /> for each image more</label>
        </p>
        <?php post_pay_counter_options_functions_class::echo_p_field( 'Allow single post payment bonuses', self::$edit_options_counter_settings->allow_payment_bonuses, 'checkbox', 'allow_payment_bonuses', 'If checked, a custom field will allow to award a post bonus in the writing page. Do this by creating a new custom field named <em>payment_bonus</em> with the value you want to be the bonus (read the FAQ for details). <strong>Take care</strong> because everyone who can edit posts can also handle this custom field, potentially having their posts payed more without your authorization. In the stats page you will anyway see what posts have bonuses.' ); 
        post_pay_counter_options_functions_class::echo_p_field( 'Enable minimum fee', self::$edit_options_counter_settings->minimum_fee_enable, 'checkbox', 'minimum_fee_enable', 'The minimum fee function allows to always credit writers a fixed amout of money of your choice. When enabled, the posts which do not reach the minimum fee thresold will be rounded to that. So if the minimum fee value is set to &euro; 1.00 and a given post would only account for &euro; 0.50, the payment will be rounded to &euro; 1.00. When active, no posts will be paid less than the value you specify below.', 'minimum_fee_enable', 'minimum_fee_enable' ); ?>
        <div style="margin-left: 3em;" id="minimum_fee_enable_content">
            <p>
                <label>Minimum fee value <input type="text" name="minimum_fee_value" value="<?php echo self::$edit_options_counter_settings->minimum_fee_value; ?>"></label>
            </p>
        </div>
        <p>
        
        <?php //Show this only if we're in a particular author settings page (paypal address input field)
        if( is_numeric( self::$edit_options_counter_settings->userID ) ) { ?>
            <p>
                <label>Add here the user's paypal address for an easier payment <input type="text" name="paypal_address" size="28" value="<?php echo self::$edit_options_counter_settings->paypal_address ?>" /></label>
            </p>
         <?php }
    }
    
    function meta_box_permissions() {
        global $wp_roles; 
        
        if ( ! isset( $wp_roles ) )
            $wp_roles = new WP_Roles(); ?>
        
        <p>Just a few fields to help you keeping users away from where they should not be. Remember that administrators override all these permissions even if their settings have been personalized.</p>
        <?php post_pay_counter_options_functions_class::echo_p_field( 'Make other users\' general stats viewable', self::$edit_options_counter_settings->can_view_others_general_stats, 'checkbox', 'can_view_others_general_stats', 'If unchecked, users will only be able to see their stats in the general page. Other users\' names, posts and pay counts will not be displayed.' );
        post_pay_counter_options_functions_class::echo_p_field( 'Make other users\' detailed stats viewable', self::$edit_options_counter_settings->can_view_others_detailed_stats, 'checkbox', 'can_view_others_detailed_stats', 'If unchecked, other users will not be able to see other user\'s detailed stats (ie. written posts details) but still able to see general ones. ' );
        post_pay_counter_options_functions_class::echo_p_field( 'Make overall stats viewable', self::$edit_options_counter_settings->can_view_overall_stats, 'checkbox', 'can_view_overall_stats', 'Responsible of the <em>Overall Stats</em> box displaying. It shows some interesting data regarding your blog since you started it, but their generation it is quite heavy since it selects all the counted posts ever. <strong>For Google Analytics users:</strong> do not worry, overall stats never need further Google requests, it just always retrieves all the data from your own database.' );
        post_pay_counter_options_functions_class::echo_p_field( 'Make viewable the use of special settings in countings', self::$edit_options_counter_settings->can_view_special_settings_countings, 'checkbox', 'can_view_special_settings_countings', 'If you personalize settings by user, keep this in mind. If unchecked, users will not see personalized settings in countings, they will believe everybody is still using general settings. Anyway, the selected posts author will see them.' );
        post_pay_counter_options_functions_class::echo_p_field( 'Make countings details viewable as overlay', self::$edit_options_counter_settings->can_view_overlay_counting_details, 'checkbox', 'can_view_overlay_counting_details', 'When checked, it will be possible to see how the final payment amount is generated, from what sections the money come from.' );
        post_pay_counter_options_functions_class::echo_p_field( 'Make paid amount visible (no marking allowed)', self::$edit_options_counter_settings->can_view_paid_amount, 'checkbox', 'can_view_paid_amount', 'When checked, it will be possible to see how how much a post has already been paid. Only administrators will be able to mark/unmark posts as paid, though.' );
        post_pay_counter_options_functions_class::echo_p_field( 'Make posts word count visible in post list', self::$edit_options_counter_settings->can_view_posts_word_count_post_list, 'checkbox', 'can_view_posts_word_count_post_list', 'Check this if you want the word counts for each post to be showed as a column in the Wordpress post list. If using the zones counting system and the word count for a post is below the first zone, its opcaity will be reduced.' );
        post_pay_counter_options_functions_class::echo_p_field( 'Allow stats to be downloadable as csv files', self::$edit_options_counter_settings->can_csv_export, 'checkbox', 'can_csv_export', 'If checked, a link in the bottom of the stats table will allow to download the displayed data as a csv file for offline consulting.' );
        
        if( self::$edit_options_counter_settings->userID == 'general' ) {
            echo '<p style="margin-top: 20px;">Plugin Options can be viewed and edited by following user roles</p>';
            foreach( $wp_roles->role_names as $key => $value ) {
                if( in_array( $key, self::$allowed_user_roles_options_page ) )
                    $checked = ' checked="checked"';
                
                echo '<p style="height: 10px;"><label><input type="checkbox" name="permission_options_page_user_roles_'.$key.'" value="'.$key.'"'.@$checked.'> '.$value.'</label></p>';
                unset( $checked );
            }
            echo '<p style="margin-top: 20px;">Plugin Stats page can be viewed by following user roles</p>';
            foreach( $wp_roles->role_names as $key => $value ) {
                if( in_array( $key, self::$allowed_user_roles_stats_page ) )
                    $checked = ' checked="checked"';
                
                echo '<p style="height: 10px;"><label><input type="checkbox" name="permission_stats_page_user_roles_'.$key.'" value="'.$key.'"'.@$checked.'> '.$value.'</label></p>';
                unset( $checked );
            }
        }
        
    }
    
    function meta_box_update_countings() { ?>
        <p>Use this section to manually rebuild stats if you are experiencing problems. Use this function if you would like to have prior to the install date posts counted. If you are in a personalize settings user page, only the posts of that author will be updated. It is not necessary to update countings on a settings change, the plugin will take care of that when needed.</p>
        <div>        
            <span style="float: left; text-align: left; width: 50%;">
                <input type="submit" name="post_pay_counter_update_stats_countings" value="Update stats countings" class="button-secondary"  />
                <span style="width: 20px; height: 13px; text-align: right;"><img src="<?php echo plugins_url( 'style/images/info.png', __FILE__ ); ?>" title="Use this to rebuild the stats countings. If your chosen counting type is visits, they will all be set to 0, while if it is words, they will be newly computed basing on the posts content." class="tooltip_container" /></span>
            </span>
            <span style="float: right; text-align: right; width: 50%;">
                <input type="submit" name="post_pay_counter_update_stats_countings_and_dates" value="Update stats countings AND dates" class="button-secondary"  />
                <span style="width: 20px; height: 13px; text-align: right;"><img src="<?php echo plugins_url( 'style/images/info.png', __FILE__ ); ?>" title="Apart from updating stats countings (see previous tooltip for info), it also updates the datas related to them. This may generate some differences with your current stats because it uses as datas the ones Wordpress saved in the database, but it is the real way to reset the plugin data." class="tooltip_container" /></span>
            </span>
        </div>
        <div class="clear"></div>
    <?php }
    
    function meta_box_trial_settings() { ?>
        <p>Did you know you can also define some trial settings, so that new authors will be payed differently for their first writing period (and also have diverse permissions and everything)? </p>
        <p>First of all, define the trial counting settings from <a href="<?php echo admin_url( self::$post_pay_counter_options_menu_link.'&amp;userid=trial' ) ?>" title="Trial settings">this page</a>.</p>
        <?php post_pay_counter_options_functions_class::echo_p_field( 'Automatic trial', self::$edit_options_counter_settings->trial_auto, 'radio', 'trial_type', 'This way, the plugin will handle all the trial stuff by itself. After you will have defined how long you want it to last (days or posts since user subscribed), forget it.', 'trial_auto', 'trial_auto' ); ?>
        <p style="margin-left: 3em;" id="trial_auto_content">
            <label>Define the period you want it to run: <br /> <input type="text" name="trial_period" value="<?php echo self::$edit_options_counter_settings->trial_period ?>" size="5" /></label>
            <span style="margin-left: 2em;"><label>
        <?php echo post_pay_counter_options_functions_class::checked_or_not( self::$edit_options_counter_settings->trial_period_days, 'radio', 'trial_period_type', 'Days' ); ?> Days </label> <label> Posts <?php echo post_pay_counter_options_functions_class::checked_or_not( self::$edit_options_counter_settings->trial_period_posts, 'radio', 'trial_period_type', 'Posts' ); ?>
            </label>
            </span>
        </p>
        <?php post_pay_counter_options_functions_class::echo_p_field( 'Manual trial', self::$edit_options_counter_settings->trial_manual, 'radio', 'trial_type', 'Else, if you prefer to have a little more control over it, you can just select this and manually opt-in and out the trial option from the single users\' pages.', 'trial_manual', 'trial_manual' );
        //Show the "enable trial for this user" only if in a user's page
        if( is_numeric( self::$edit_options_counter_settings->userID ) ) { ?>
        <div style="margin-left: 3em;" id="trial_manual_content">
            <?php post_pay_counter_options_functions_class::echo_p_field( 'Enable trial for the selected user', self::$edit_options_counter_settings->trial_enable, 'checkbox', 'trial_enable', 'Opt-in/out trial settings for the selected user' ); ?>
        </div>
        <?php }
    }
    
    function meta_box_personalize_settings() {
        global $wpdb;
        
        //General settings, valid for every editor. Showing users with personalized settings on the right
    	if( ! is_numeric( self::$edit_options_counter_settings->userID ) ) {
    		  
          //Select all users who have different settings already in place
          $personalized_users = $wpdb->get_results( 'SELECT userID FROM '.self::$post_pay_counter_db_table.' WHERE userID != "general" AND userID != "trial"', ARRAY_A ); 
          
          echo '<strong>Showing '.self::$edit_options_counter_settings->userID.' settings</strong>';
          
          //If special users are detected, show them
          if( $wpdb->num_rows > 0 ) { ?>
            
            <p>The following users have different settings, click to view and edit. Other users can be found in the list below.</p>
            <div>
            
            <?php $already_personalized = array();
            $n = 0; 
            foreach( $personalized_users as $single ) {
                $userdata = get_userdata( $single['userID'] );
                
                if( $n % 3 == 0 )
                    echo '<span style="float: left; width: 34%;">';
                else if( $n % 3 == 1 )
                    echo '<span style="width: 33%;">';
                else
                    echo '<span style="float: right; width: 33%;">';
                
                echo '<a href="'.admin_url( self::$post_pay_counter_options_menu_link.'&amp;userid='.$single['userID'] ).'" title="View and edit special settings for \''.htmlspecialchars( $userdata->display_name ).'\'">'.$userdata->display_name.'</a>
                </span>';
                
                $already_personalized[$single['userID']] = $single['userID'];
                ++$n;
            } ?>
            
                <div class="clear"></div>
            </div>
            
      <?php } else { ?>
        <p>No users have different settings. Learn how to personalize settings from the form below.</p>
      <?php }
          
        //Personalized users' settings
		} else { ?>
		<strong>Showing settings for "<a href="<?php echo admin_url( 'user-edit.php?user_id='.$_GET['userid'] ); ?>" title="Go to user page" style="color: #000000; text-decoration: none;"><?php echo get_userdata( $_GET['userid'] )->display_name; ?></a>"</strong>
        <p>
            <a href="<?php echo admin_url( self::$post_pay_counter_options_menu_link ); ?>" title="General settings">Go back to general settings</a><br />
            <a href="<?php echo wp_nonce_url( admin_url( self::$post_pay_counter_options_menu_link.'&amp;delete='.$_GET['userid'] ), 'post_pay_counter_options_delete_user_settings' ); ?>" title="Delete this user's settings">Delete this user's settings</a>
        </p>
		<?php }
        
        //Right form to go to personalized user's settings page ?>
        <p><strong>Personalize single user settings</strong><br />
        Some people's posts are better than somebody others'? If you would like, you can adjust settings for each user, so that posts will be payed differently and they will have different permissions and trial settings, too.</p>
        <p>Select a username from the list below and let's go! NOTE that users who already have special settings are not shown here (look above), and that only the first 250 users are shown here to prevent the plugin from hanging. You can easily personalize the other users by changing the userID parameter at the end of the URL.</p>
        
        <?php if( ! @is_numeric( $_GET['userid'] ) ) { ?>
        <div style="height: 8em; overflow: auto;">
            <table width="100%">
                <thead>
                     <tr>
                        <th width="34%" align="left">Author Name</th>
                        <th width="16%" align="left">Last Post</th>
                        <th width="34%" align="left">Author Name</th>
                        <th width="15%" align="left">Last Post</th>
                    </tr>
                </thead>
            
        <?php //Select and show all the users in database
        
        //This is to prevent very populated blogs from hanging when selecting writers from the database.
        // First, select the number of rows of users that we'll have to handle
        /*$users_to_select = $wpdb->get_var( 'SELECT COUNT(*) FROM '.$wpdb->users );
        
        //The mechanism comes into play only if there are more 
        if( $users_to_select > 1000 ) {
            $users_to_arrange = array();
            $n = 1;
            
            while( $users_to_select / 1000 >= $n ) {
                $users_to_arrange[] = $wpdb->get_results( 'SELECT ID FROM '.$wpdb->users.' ORDER BY user_nicename ASC LIMIT '.($n*1000-1000).','.$n*1000 );
                $n++;
            }
            
            $n = 0;
            $users_to_arrange_count = count( $users_to_arrange );
            while( $users_to_arrange_count > ( $n + 1 ) ) {
                $users_to_arrange[$n+1] = array_merge( $users_to_arrange[$n], $users_to_arrange[$n+1] );
                ++$n;
            }
            $users_to_show = $users_to_arrange[( $users_to_arrange_count - 1 )];
            
        } else {
            $users_to_show = $wpdb->get_results( 'SELECT ID FROM '.$wpdb->users.' ORDER BY user_nicename ASC' );
        }*/
        
        //Select and show first 250 users - prevent hanging
        $users_to_show = get_users( array( 'orderby' => 'display_name', 'order' => 'ASC', 'number' => 250 ) );
            
        $n = 0;
        foreach( $users_to_show as $single ) {
            
            //Don't show users who already have personalized settings, they have already been shown in the upper part. Silencing in case there are no personalized users.
            if( @in_array( $single->ID, $already_personalized ) )
                continue;
            
            $userdata       = get_userdata( $single->ID ); 
            $last_post      = $wpdb->get_row( 'SELECT ID, post_date FROM '.$wpdb->posts.' WHERE post_author = '.$single->ID.' ORDER BY post_date DESC LIMIT 0,1' );
            $last_post_date = @date( 'Y/m/d', strtotime( $last_post->post_date ) );
            
            if( $wpdb->num_rows == 0 )
                $last_post_date = '--';
            
            if( $n % 2 == 0 )
                echo '<tr>';
                
                echo '<td style="font-size: 12px;"><a href="'.admin_url( self::$post_pay_counter_options_menu_link.'&amp;userid='.$single->ID ).'" title="'.$userdata->display_name.'">'.$userdata->display_name.'</a></td>
                <td style="font-size: 11px;"><a href="'.@get_permalink( $last_post->ID ).'" title="Go to last post" style="color: #000000; text-decoration: none;">'.$last_post_date.'</a></td>';
            
            if( $n % 2 != 0 )
                echo '</tr>';
            
            $n++;
        } ?>

            </table>
        </div>
    <?php } 
    }
    
    function meta_box_support_the_author() { ?>
<p>If you like the Post Pay Counter, there are a couple of things you can do to support its development:</p>
<ul style="margin: 0 0 15px 2em;">
    <li style="list-style-image: url('<?php echo plugins_url( 'style/images/paypal.png', __FILE__ ); ?>');"><a target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=7UH3J3CLVHP8L" title="Donate money"><strong>Donate money</strong></a>. The plugin is free and is developed in my free time: a small income would make everything easier.</li>
    <li style="list-style-image: url('<?php echo plugins_url( 'style/images/amazon.png', __FILE__ ); ?>');">Give me something from my <a target="_blank" href="http://www.amazon.it/registry/wishlist/1JWAS1MWTLROQ" title="Amazon Wishlist">Amazon Wishlist</a>.</li>
    <li style="list-style-image: url('<?php echo plugins_url( 'style/images/feedback.png', __FILE__ ); ?>');">Suggest new functions and ideas you would like to see in the next release of the plugin, or report bugs you've found at the <a target="_blank" href="http://www.thecrowned.org/post-pay-counter" title="Plugin official page">official page</a>.</li>
    <li style="list-style-image: url('<?php echo plugins_url( 'style/images/star.png', __FILE__ ); ?>');">Rate it in the <a target="_blank" href="http://wordpress.org/extend/plugins/post-pay-counter/" title="Wordpress directory">Wordpress Directory</a> and share the <a target="_blank" href="http://www.thecrowned.org/post-pay-counter" title="Official plugin page">official page</a>.</li>
    <li style="list-style-image: url('<?php echo plugins_url( 'style/images/write.png', __FILE__ ); ?>');">Have a blog or write on some website? Write about the plugin!</li>
</ul>
    <?php }
    
    //Updating options routine
    function post_pay_counter_options_save( $_POST ) {
        global  $wpdb,
                $wp_roles;
        
        //Nonce check
        check_admin_referer( 'post_pay_counter_main_form_update' );
        
        $current_counting_settings  = post_pay_counter_functions_class::get_settings( $_POST['userID'] );
        $new_settings               = array();
        
        /* COUNTING SETTINGS BOX */
        $new_settings['userID']                 = $_POST['userID'];
        $new_settings['unique_payment']         = (float) str_replace( ',', '.', $_POST['unique_payment_value'] );
        $new_settings['minimum_fee_value']      = (float) str_replace( ',', '.', $_POST['minimum_fee_value'] );
        $new_settings['bonus_comment_count']    = (int) $_POST['bonus_comment_count'];
        $new_settings['bonus_comment_payment']  = (float) str_replace( ',', '.', $_POST['bonus_comment_payment'] );
        $new_settings['bonus_image_payment']    = (float) str_replace( ',', '.', $_POST['bonus_image_payment'] );
        
        //Ordinary zones count fields
        $new_settings['ordinary_zones'] = array (
            1 => array(
                'zone'      => (int) $_POST['zone1_count'], 
                'payment'   => (float) str_replace( ',', '.', $_POST['zone1_payment'] )
            ),
            2 => array(
                'zone'      => (int) $_POST['zone2_count'], 
                'payment'   => (float) str_replace( ',', '.', $_POST['zone2_payment'] )
            ),
            3 => array(
                'zone'      => (int) $_POST['zone3_count'], 
                'payment'   => (float) str_replace( ',', '.', $_POST['zone3_payment'] )
            ),
            4 => array(
                'zone'      => (int) $_POST['zone4_count'], 
                'payment'   => (float) str_replace( ',', '.', $_POST['zone4_payment'] )
            ),
            5 => array(
                'zone'      => (int) $_POST['zone5_count'], 
                'payment'   => (float) str_replace( ',', '.', $_POST['zone5_payment'] )
            )
        );
        $add_five_more_zones = @post_pay_counter_options_functions_class::update_options_checkbox_value( $_POST['add_five_more_zones'] );
        
        //If add five more zones is selected, to it all over again for supplementary zones
        if( $add_five_more_zones == 1 ) {
            $supplementary_zones_temp = array (
                6 => array(
                    'zone'      => (int) $_POST['zone6_count'], 
                    'payment'   => (float) str_replace( ',', '.', $_POST['zone6_payment'] )
                ),
                7 => array(
                    'zone'      => (int) $_POST['zone7_count'], 
                    'payment'   => (float) str_replace( ',', '.', $_POST['zone7_payment'] )
                ),
                8 => array(
                    'zone'      => (int) $_POST['zone8_count'], 
                    'payment'   => (float) str_replace( ',', '.', $_POST['zone8_payment'] )
                ),
                9 => array(
                    'zone'      => (int) $_POST['zone9_count'], 
                    'payment'   => (float) str_replace( ',', '.', $_POST['zone9_payment'] )
                ),
                10 => array(
                    'zone'      => (int) $_POST['zone10_count'], 
                    'payment'   => (float) str_replace( ',', '.', $_POST['zone10_payment'] )
                )
            );
            $new_settings['ordinary_zones'] = $new_settings['ordinary_zones'] + $supplementary_zones_temp; //Not using array_merge because of reindexing
        }
        $new_settings['ordinary_zones'] = serialize( $new_settings['ordinary_zones'] );
        
        //These counting settings only apply to the _general_ set
        if( $_POST['userID'] == 'general' ) {
            switch( $_POST['counting_type'] ) {
                case 'Words':
                    $new_settings['counting_type_words']                = 1;
                    $new_settings['counting_type_visits']               = 0;
                    $new_settings['counting_type_visits_method_plugin'] = 0;
                    break;
                    
                case 'Visits':
                    $new_settings['counting_type_words']                = 0;
                    $new_settings['counting_type_visits']               = 1;
                    $new_settings['counting_type_visits_method_plugin'] = 1;
                    break;
                    
                default:
                    $new_settings['counting_type_words']                = self::$general_settings->counting_type_words;
                    $new_settings['counting_type_visits']               = self::$general_settings->counting_type_visits;
                    $new_settings['counting_type_visits_method_plugin'] = self::$general_settings->counting_type_visits_method_plugin;
                    break;
            }
            
            /*//If chosen type is visits and chosen method is Google Analytics, assure provided credentials are valid: attempt access and throw exception on fail
            if( $new_settings['counting_type_visits'] == 1 ) {
                switch( $_POST['counting_type_visits_method'] ) {
                    case 'counting_type_visits_method_plugin':
                        $new_settings['counting_type_visits_method_plugin']             = 1;
                        $new_settings['counting_type_visits_method_google_analytics']   = 0;
                        break;
                    
                    //If counting method is Google Analytics
                    case 'counting_type_visits_method_google_analytics':
                        $new_settings['counting_type_visits_method_plugin']             = 0;
                        $new_settings['counting_type_visits_method_google_analytics']   = 1;
                        
                        //If Google Analytics settings have been changed, verify if they are valid
                        if( $new_settings['counting_type_visits_method_google_analytics']       != self::$general_settings->counting_type_visits_method_google_analytics
                        OR $_POST['counting_type_visits_method_google_analytics_email']         != self::$general_settings->counting_type_visits_method_google_analytics_email
                        OR $_POST['counting_type_visits_method_google_analytics_password']      != self::$general_settings->counting_type_visits_method_google_analytics_password
                        OR $_POST['counting_type_visits_method_google_analytics_profile_id']    != self::$general_settings->counting_type_visits_method_google_analytics_profile_id ) {                        
                            try {
                                //Login
                                $ga = new gapi( $_POST['counting_type_visits_method_google_analytics_email'], $_POST['counting_type_visits_method_google_analytics_password'] );
                                //Profile selection try
                                $ga->requestReportData( $_POST['counting_type_visits_method_google_analytics_profile_id'], array( 'browser' ), array( 'pageviews' ), null, null, date( 'Y-m-d' ), date( 'Y-m-d' ), 1, 1 );
                            } catch ( Exception $e ) {
                                echo '<div id="message" class="error fade"><p><strong>There was a problem with your Google Analytics credentials: '.$e->getMessage().'. Check the submitted data and try again.</strong></p></div>';
                                return;
                            }
                        }
                        
                        $new_settings['counting_type_visits_method_google_analytics_email']         = $_POST['counting_type_visits_method_google_analytics_email'];
                        $new_settings['counting_type_visits_method_google_analytics_password']      = $_POST['counting_type_visits_method_google_analytics_password'];
                        $new_settings['counting_type_visits_method_google_analytics_profile_id']    = $_POST['counting_type_visits_method_google_analytics_profile_id'];
                        
                        switch( $_POST['counting_type_visits_method_google_analytics_update_time'] ) {
                            case 'counting_type_visits_method_google_analytics_update_request':
                                $new_settings['counting_type_visits_method_google_analytics_update_request']    = 1;
                                $new_settings['counting_type_visits_method_google_analytics_update_hour']       = 0;
                                $new_settings['counting_type_visits_method_google_analytics_update_day']        = 0;
                                break;
                                
                            case 'counting_type_visits_method_google_analytics_update_hour':
                                $new_settings['counting_type_visits_method_google_analytics_update_request']    = 0;
                                $new_settings['counting_type_visits_method_google_analytics_update_hour']       = 1;
                                $new_settings['counting_type_visits_method_google_analytics_update_day']        = 0;
                                
                                break;
                            
                            case 'counting_type_visits_method_google_analytics_update_day':
                                $new_settings['counting_type_visits_method_google_analytics_update_request']    = 0;
                                $new_settings['counting_type_visits_method_google_analytics_update_hour']       = 0;
                                $new_settings['counting_type_visits_method_google_analytics_update_day']        = 1;
                                break;
                                
                            default:
                                $new_settings['counting_type_visits_method_google_analytics_update_request']    = self::$general_settings->counting_type_visits_method_google_analytics_update_request;
                                $new_settings['counting_type_visits_method_google_analytics_update_hour']       = self::$general_settings->counting_type_visits_method_google_analytics_update_hour;
                                $new_settings['counting_type_visits_method_google_analytics_update_day']        = self::$general_settings->counting_type_visits_method_google_analytics_update_hour;
                                break;
                        }
                        
                        switch( $_POST['counting_type_visits_method_google_analytics_pageviews'] ) {
                            case 'counting_type_visits_method_google_analytics_pageviews':
                                $new_settings['counting_type_visits_method_google_analytics_pageviews']         = 1;
                                $new_settings['counting_type_visits_method_google_analytics_unique_pageviews']  = 0;
                                break;
                                
                            case 'counting_type_visits_method_google_analytics_unique_pageviews':
                                $new_settings['counting_type_visits_method_google_analytics_pageviews']         = 0;
                                $new_settings['counting_type_visits_method_google_analytics_unique_pageviews']  = 1;
                                break;
                                
                            default:
                                $new_settings['counting_type_visits_method_google_analytics_pageviews']         = self::$general_settings->counting_type_visits_method_google_analytics_pageviews;
                                $new_settings['counting_type_visits_method_google_analytics_unique_pageviews']  = self::$general_settings->counting_type_visits_method_google_analytics_unique_pageviews;
                                break;
                        }
                        
                        break;
                        
                    default:
                        $new_settings['counting_type_visits_method_plugin']             = self::$general_settings->counting_type_visits_method_plugin;
                        $new_settings['counting_type_visits_method_google_analytics']   = self::$general_settings->counting_type_visits_method_google_analytics;
                        break;
                }
            }*/
            
            switch( $_POST['publication_time_range'] ) {
                case 'publication_time_range_month':
                    $new_settings['publication_time_range_month']           = 1;
                    $new_settings['publication_time_range_week']            = 0;
                    $new_settings['publication_time_range_custom']          = 0;
                    break;
                
                case 'publication_time_range_week':
                    $new_settings['publication_time_range_month']           = 0;
                    $new_settings['publication_time_range_week']            = 1;
                    $new_settings['publication_time_range_custom']          = 0;
                    break;
                
                case 'publication_time_range_custom':
                    
                    if( (int) $_POST['publication_time_range_custom_value'] == 0 ) {
                        echo '<div id="message" class="error fade"><p><strong>You cannot select a custom publication time range without specifing a days number.</strong></p></div>';
                        return;
                    }
                    
                    $new_settings['publication_time_range_month']           = 0;
                    $new_settings['publication_time_range_week']            = 0;
                    $new_settings['publication_time_range_custom']          = 1;
                    $new_settings['publication_time_range_custom_value']    = (int) $_POST['publication_time_range_custom_value'];
                    break;
            }
            
            $new_settings['count_pending_revision_posts']       = @post_pay_counter_options_functions_class::update_options_checkbox_value( $_POST['count_pending_revision_posts'] );
            $new_settings['count_future_scheduled_posts']       = @post_pay_counter_options_functions_class::update_options_checkbox_value( $_POST['count_future_scheduled_posts'] );
            $new_settings['exclude_quotations_from_countings']  = @post_pay_counter_options_functions_class::update_options_checkbox_value( $_POST['exclude_quotations_from_countings'] );
            $new_settings['count_visits_guests']                = @post_pay_counter_options_functions_class::update_options_checkbox_value( $_POST['count_visits_guests'] );
            $new_settings['count_visits_registered']            = @post_pay_counter_options_functions_class::update_options_checkbox_value( $_POST['count_visits_registered'] );
            $new_settings['count_visits_authors']               = @post_pay_counter_options_functions_class::update_options_checkbox_value( $_POST['count_visits_authors'] );
            $new_settings['count_visits_bots']                  = @post_pay_counter_options_functions_class::update_options_checkbox_value( $_POST['count_visits_bots'] );
        
            //Cycle through $_POST global variable and take into account all the fields that start with 'custom_post_type_' or 'user_role_'
            //Add each of them to an array that will be serialized and put into the database. 
            $pts_to_include                     = array();
            $user_roles_to_include              = array();
            $permission_options_page_user_roles = array();
            $permission_stats_page_user_roles   = array();
            foreach( $_POST as $key => $value ) {
                if( strpos( $key, 'custom_post_type_' ) === 0 ) {
                    $pts_to_include[] = $value;
                }
                if( strpos( $key, 'user_role_' ) === 0 ) {
                    $user_roles_to_include[] = $value;
                }
                
                if( $_POST['userID'] == 'general' ) {
                    if( strpos( $key, 'permission_options_page_user_roles_' ) === 0 ) {
                        $permission_options_page_user_roles[] = $value;
                        
                    }
                    if( strpos( $key, 'permission_stats_page_user_roles_' ) === 0 ) {
                        $permission_stats_page_user_roles[] = $value;
                    }
                }
            }
            $new_settings['post_types_to_include_in_counting']  = serialize( $pts_to_include );
            $new_settings['user_roles_to_include_in_counting']  = serialize( $user_roles_to_include );
            $new_settings['permission_stats_page_user_roles']   = serialize( $permission_stats_page_user_roles );
                
                if( ! empty( $permission_options_page_user_roles ) ) {
                    $new_settings['permission_options_page_user_roles'] = serialize( $permission_options_page_user_roles );
                } else {
                    echo '<div id="message" class="error fade"><p><strong>At least one user group must be able to view and edit Options!</strong></p></div>';
                    return;
                }
            
            post_pay_counter_functions_class::manage_cap_allowed_user_groups_plugin_pages( $permission_options_page_user_roles, $permission_stats_page_user_roles );
        }
        
        switch( $_POST['counting_system'] ) {
            case 'counting_system_zones':
                $new_settings['counting_system_zones']              = 1;
                $new_settings['counting_system_unique_payment']     = 0;
                break;
                
            case 'counting_system_unique_payment':
                $new_settings['counting_system_zones']              = 0;
                $new_settings['counting_system_unique_payment']     = 1;
                break;
                
            default:
                $new_settings['counting_system_zones']              = $current_counting_settings->counting_system_zones;
                $new_settings['counting_system_unique_payment']     = $current_counting_settings->counting_system_unique_payment;
                break;
        }
        
        $new_settings['minimum_fee_enable']     = @post_pay_counter_options_functions_class::update_options_checkbox_value( $_POST['minimum_fee_enable'] );
        $new_settings['allow_payment_bonuses']  = @post_pay_counter_options_functions_class::update_options_checkbox_value( $_POST['allow_payment_bonuses'] );
                    
        //If we're dealing with personalized options, check paypal address and add it to the query array
        if( is_int( $_POST['userID'] ) AND get_userdata( $_POST['userID'] ) ) {
            if( is_string( $_POST['paypal_address'] ) AND strlen( $_POST['paypal_address'] ) > 0 ) {
                if( is_email( $_POST['paypal_address'] ) ) {
                    $new_settings['paypal_address'] = $_POST['paypal_address'];
                } else {
                    echo '<div id="message" class="error fade"><p><strong>The entered paypal e-mail address is not valid. Check it and try again.</strong></p></div>';
                    return;
                }
            }
        }
        
        /* PERMISSIONS BOX */
        $new_settings['can_view_others_general_stats']          = @post_pay_counter_options_functions_class::update_options_checkbox_value( $_POST['can_view_others_general_stats'] );
        $new_settings['can_view_others_detailed_stats']         = @post_pay_counter_options_functions_class::update_options_checkbox_value( $_POST['can_view_others_detailed_stats'] );
        $new_settings['can_view_overall_stats']                 = @post_pay_counter_options_functions_class::update_options_checkbox_value( $_POST['can_view_overall_stats'] );
        $new_settings['can_view_special_settings_countings']    = @post_pay_counter_options_functions_class::update_options_checkbox_value( $_POST['can_view_special_settings_countings'] );
        $new_settings['can_view_overlay_counting_details']      = @post_pay_counter_options_functions_class::update_options_checkbox_value( $_POST['can_view_overlay_counting_details'] );
        $new_settings['can_view_paid_amount']                   = @post_pay_counter_options_functions_class::update_options_checkbox_value( $_POST['can_view_paid_amount'] );
        $new_settings['can_view_posts_word_count_post_list']    = @post_pay_counter_options_functions_class::update_options_checkbox_value( $_POST['can_view_posts_word_count_post_list'] );
        $new_settings['can_csv_export']                         = @post_pay_counter_options_functions_class::update_options_checkbox_value( $_POST['can_csv_export'] );
        
        /* TRIAL BOX (only if not referring from trial settings page) */
        if( $_POST['userID'] != 'trial' ) {
            $new_settings['trial_period'] = trim( (int) $_POST['trial_period'] );
            
            switch( $_POST['trial_type'] ) {
                case 'trial_auto':
                    $new_settings['trial_auto']      = 1;
                    $new_settings['trial_manual']    = 0;
                    break;
                    
                case 'trial_manual':
                    $new_settings['trial_auto']      = 0;
                    $new_settings['trial_manual']    = 1;
                    break;
                    
                default:
                    $new_settings['trial_auto']      = $current_counting_settings->general_settings['trial_auto'];
                    $new_settings['trial_manual']    = $current_counting_settings->general_settings['trial_manual'];;
                    break;
            }
            
            switch( $_POST['trial_period_type'] ) {
                case 'Days':
                    $new_settings['trial_period_days']   = 1;
                    $new_settings['trial_period_posts']  = 0;
                    break;
                    
                case 'Posts':
                    $new_settings['trial_period_days']   = 0;
                    $new_settings['trial_period_posts']  = 1;
                    break;
                    
                default:
                    $new_settings['trial_period_days']   = $current_counting_settings->general_settings['trial_period_days'];
                    $new_settings['trial_period_posts']  = $current_counting_settings->general_settings['trial_period_posts'];;
                    break;
            }
            
            if( is_numeric( $_POST['userID'] ) AND get_userdata( $_POST['userID'] ) )
                $new_settings['trial_enable'] = @post_pay_counter_options_functions_class::update_options_checkbox_value( $_POST['trial_enable'] );
        }
        
        //Check if there are already saved settings for the requested ID: if yes, update that record, otherwise, create a new one
        if( is_object( $current_counting_settings ) )
            $wpdb->update( $wpdb->prefix.'post_pay_counter', $new_settings, array( 'userID' => $new_settings['userID'] ) );
        else
            $wpdb->insert( $wpdb->prefix.'post_pay_counter', $new_settings );
        
        //If the counting type or pending revision/future scheduled counting status or exclude quotations has changed or list of post types to include in counting, update all the database posts records to reflect changes
        if( isset( $new_settings['counting_type_words'] ) AND $new_settings['counting_type_words'] != @self::$general_settings->counting_type_words
        OR isset( $new_settings['counting_type_visits'] ) AND $new_settings['counting_type_visits'] != @self::$general_settings->counting_type_visits 
        /*OR isset( $new_settings['counting_type_visits_method_google_analytics'] ) AND $new_settings['counting_type_visits_method_google_analytics'] != @self::$general_settings->counting_type_visits_method_google_analytics
        OR isset( $new_settings['counting_type_visits_method_google_analytics_pageviews'] ) AND $new_settings['counting_type_visits_method_google_analytics_pageviews'] != @self::$general_settings->counting_type_visits_method_google_analytics_pageviews*/
        OR isset( $new_settings['count_pending_revision_posts'] ) AND $new_settings['count_pending_revision_posts'] != @self::$general_settings->count_pending_revision_posts
        OR isset( $new_settings['count_future_scheduled_posts'] ) AND $new_settings['count_future_scheduled_posts'] != @self::$general_settings->count_future_scheduled_posts
        /*OR isset( $new_settings['publication_time_range_week'] ) AND $new_settings['publication_time_range_week'] != @$current_counting_settings->publication_time_range_week
        OR isset( $new_settings['publication_time_range_custom_value'] ) AND $new_settings['publication_time_range_custom_value'] != @$current_counting_settings->publication_time_range_custom_value*/
        OR isset( $new_settings['exclude_quotations_from_countings'] ) AND $new_settings['exclude_quotations_from_countings'] != @$current_counting_settings->exclude_quotations_from_countings
        OR isset( $new_settings['post_types_to_include_in_counting'] ) AND $new_settings['post_types_to_include_in_counting'] != @$current_counting_settings->post_types_to_include_in_counting
        OR isset( $new_settings['user_roles_to_include_in_counting'] ) AND $new_settings['user_roles_to_include_in_counting'] != @$current_counting_settings->user_roles_to_include_in_counting ) {
            
            //If updating general settings, also update the relative class object. Can't do this before because we wouldn't be able to do all those condition checks
            //Need it to properly update all posts, othwerise old settings would be used for the task
            if( $new_settings['userID'] == 'general' ) {
                post_pay_counter_functions_class::options_changed_vars_update_to_reflect( TRUE );
            }
            
            /*//If current settings are personalized (valid user id), only update that authors' posts, else everybody's
            
            //AS OF NOW, THE SETTINGS THAT WOULD REQUIRE A STATS UPDATE CAN'T BE PERSONALIZED BY USER
            
            if( is_numeric( $new_settings['userID'] ) )
                post_pay_counter_functions_class::update_all_posts_count( FALSE, $new_settings['userID'] );
            else*/
                post_pay_counter_functions_class::update_all_posts_count();
		}
        
        //If updating general settings, also update the relative class object. Doing this again because it may not have been done before...
        //Need it to show the new settings in the options page, othwerise old settings would be used
        if( $new_settings['userID'] == 'general' ) {
            post_pay_counter_functions_class::options_changed_vars_update_to_reflect( TRUE );
        }
        
        echo '<div id="message" class="updated fade"><p><strong>Post Pay Counter settings updated.</strong> New settings take place immediately! <a href="'.admin_url( self::$post_pay_counter_stats_menu_link ).'">Go to stats now &raquo;</a></p></div>';
    }
    
    //Function to show the options page
    function post_pay_counter_options() {
        global $wpdb,
               $current_user;
        
        post_pay_counter_functions_class::fix_messed_up_stuff();
        
        /** DELETE USER'S SETTINGS **/
        if( isset( $_GET['delete'] ) AND $vaporized_userdata = get_userdata( (int) $_GET['delete'] ) AND $current_user->user_level >= 7 ) {
            $_GET['delete'] = (int) $_GET['delete'];
            
            //Nonce check
            check_admin_referer( 'post_pay_counter_options_delete_user_settings' );
            
            //Check if requested personalized settings do exist, if yes, delete it
            if( is_object( post_pay_counter_functions_class::get_settings( $_GET['delete'] ) ) ) {
                $wpdb->query( $wpdb->prepare( 'DELETE FROM '.self::$post_pay_counter_db_table.' WHERE userID = '.$_GET['delete'] ) );
                
                //Update user's posts countings
                post_pay_counter_functions_class::update_all_posts_count( FALSE, $_GET['delete'] );
                
                echo '<div id="message" class="updated fade"><p><strong>Personalized settings for user "'.$vaporized_userdata->display_name.'" deleted successfully.</strong></p></div>';
            } else {
                echo '<div id="message" class="error fade"><p><strong>There are no special settings for user "'.$vaporized_userdata->display_name.'".</strong></p></div>';
            }
        }
        
        /** SHOW SETTINGS **/ ?> 
        <div class="wrap">
        
        <?php //Checks for saving options and rebuilding stats actions. For the latter, if $_POST['userID'] is numeric, update posts records only of that author
        //Options update
        if( isset( $_POST['post_pay_counter_options_save'] ) ) {
            $this->post_pay_counter_options_save( $_POST );
        
        //Stats countings update
        } else if( isset( $_POST['post_pay_counter_update_stats_countings'] ) ) {
            //Nonce check
            check_admin_referer( 'post_pay_counter_main_form_update' );
            
            //Distinguish between all posts update (second) and only one author's posts update (first) depending on the page that triggers the update
            if( is_numeric( $_POST['userID'] ) )
                post_pay_counter_functions_class::update_all_posts_count( FALSE, $_POST['userID'] );
            else
                post_pay_counter_functions_class::update_all_posts_count();
                
            echo '<div id="message" class="updated fade"><p><strong>Stats successfully updated.</strong> <a href="'.admin_url( self::$post_pay_counter_stats_menu_link ).'">Go to stats now &raquo;</a></p></div>';
        
        //Stats countings and dates update
        } else if( isset( $_POST['post_pay_counter_update_stats_countings_and_dates'] ) ) {
            //Nonce check
            check_admin_referer( 'post_pay_counter_main_form_update' );
            
            //Distinguish between all posts update (second) and only one author's posts update (first) depending on the page that triggers the update
            if( is_numeric( $_POST['userID'] ) )
                post_pay_counter_functions_class::update_all_posts_count( TRUE, $_POST['userID'] );
            else
                post_pay_counter_functions_class::update_all_posts_count( TRUE );
                
            echo '<div id="message" class="updated fade"><p><strong>Stats successfully updated.</strong> <a href="'.admin_url( self::$post_pay_counter_stats_menu_link ).'">Go to stats now &raquo;</a></p></div>';
        } ?>
            
            <div style="float: right; color: #777; margin-top: 15px;">Installed version: <?php echo self::$ppc_current_version; ?></div>
            <div style="float: left;"><h2>Post Pay Counter Options</h2></div>
            <div style="clear: both;"></div>
            <p>From this page you can configure the Post Pay Counter plug-in. You will find all the information you need inside each following box and, for each available function, hovering on the info icon on the right of them. Generated stats are always available at <a href="<?php echo admin_url( self::$post_pay_counter_stats_menu_link ) ?>" title="Go to Stats">this page</a>, where you will find many details about each post (its post type, status, date, <?php echo self::$current_counting_method_word ?>, images and comments count, payment value, paid amount) with tons of general statistics and the ability to browse old stats. If you want to be able to see stats since the first published post, use the Update Stats box below.</p>
            
            <script type="text/javascript">
            //Javascript snippet to hide two different set of settings depending on the selected radio
            function post_pay_counter_radio_auto_toggle(toggle_click_1, toggle_click_1_content, toggle_click_2, toggle_click_2_content) {
                var element_1 = jQuery(toggle_click_1);
                var element_2 = jQuery(toggle_click_2);
                
                //At page load, check which radio field is unchecked and hide the relative settings content
                if(element_1.attr(("checked")) == undefined) {
                    jQuery(toggle_click_1_content).hide();
                } else if(element_2.attr(("checked")) == undefined) {
                    jQuery(toggle_click_2_content).hide();
                }
                
                //When a radio field gets changed, update the opacity and hide (slide) the other set
                jQuery.each([element_1, element_2], function(i,v) {
                    v.bind("click", function() {
                        if(jQuery(this).attr(("id")) == jQuery(toggle_click_1).attr("id")) {
                            jQuery(toggle_click_2_content).css("opacity", "0.40");
                            jQuery(toggle_click_2_content).slideUp();
                            jQuery(toggle_click_1_content).css("opacity", "1");
                            jQuery(toggle_click_1_content).slideDown();
                        } else if(jQuery(this).attr(("id")) == jQuery(toggle_click_2).attr("id")) {
                            jQuery(toggle_click_1_content).css("opacity", "0.40");
                            jQuery(toggle_click_1_content).slideUp();
                            jQuery(toggle_click_2_content).css("opacity", "1");
                            jQuery(toggle_click_2_content).slideDown();
                        }
                    });
                });
            }
            
            //The same, but with checkbox fields
            function post_pay_counter_checkbox_auto_toggle(toggle_click, toggle_click_content) {
                var element = jQuery(toggle_click);
                
                //At page load, check whether checkbox is checked, if not do not show div
                if(element.attr(("checked")) == undefined) {
                    jQuery(toggle_click_content).hide();
                }
                
                //When the checkbox field gets changed, update the opacity and hide (slide) the div
                jQuery(element).bind("click", function() {
                    if(jQuery(this).attr(("checked")) == undefined) {
                        jQuery(toggle_click_content).css("opacity", "0.40");
                        jQuery(toggle_click_content).slideUp();
                    } else if(jQuery(this).attr(("checked")) == "checked") {
                        jQuery(toggle_click_content).css("opacity", "1");
                        jQuery(toggle_click_content).slideDown();
                    }
                });
            }
            
            jQuery(function () {
                //Counting type visits methods
                //post_pay_counter_radio_auto_toggle("#counting_type_words", "#counting_type_words_methods", "#counting_type_visits", "#counting_type_visits_methods");
                //Two sets of options (ga, cookie duration vs count pending...)
                post_pay_counter_radio_auto_toggle("#counting_type_words", "#counting_type_words_options", "#counting_type_visits", "#counting_type_visits_options");
                //Counting systems
                post_pay_counter_radio_auto_toggle("#counting_system_zones", "#counting_system_zones_content", "#counting_system_unique_payment", "#counting_system_unique_payment_content");
                post_pay_counter_checkbox_auto_toggle("#add_five_more_zones", "#add_five_more_zones_content");
                //Counting type visits methods
                //post_pay_counter_radio_auto_toggle("#counting_type_visits_method_plugin", "#counting_type_visits_method_plugin_content", "#counting_type_visits_method_google_analytics", "#counting_type_visits_method_google_analytics_content");
                //GA/Visits date range
                //post_pay_counter_radio_auto_toggle("#visits_time_range_each_post_accordingly", "#visits_time_range_each_post_accordingly_content", "#visits_time_range_equal_to_pub", "#visits_time_range_equal_to_pub_content");
                //post_pay_counter_radio_auto_toggle("#visits_time_range_each_post_accordingly", "#visits_time_range_each_post_accordingly_content", "#visits_time_range_rules_selection", "#visits_time_range_rules_selection_content");
                //Payment date range
                post_pay_counter_radio_auto_toggle("#publication_time_range_custom", "#publication_time_range_custom_content", "#publication_time_range_week", "#publication_time_range_week_content");
                post_pay_counter_radio_auto_toggle("#publication_time_range_custom", "#publication_time_range_custom_content", "#publication_time_range_month", "#publication_time_range_month_content");
                //Minimum fee value text input field
                post_pay_counter_checkbox_auto_toggle("#minimum_fee_enable", "#minimum_fee_enable_content");
                //CPTs list toggle
                post_pay_counter_checkbox_auto_toggle("#include_cpt_in_counting", "#include_cpt_in_counting_content");
                
                <?php if( ! isset( $_GET['userid'] ) OR ( isset( $_GET['userid'] ) AND $_GET['userid'] != 'trial' ) ) { ?>
                post_pay_counter_radio_auto_toggle("#trial_auto", "#trial_auto_content", "#trial_manual", "#trial_manual_content");
                <?php } ?>
                
            });
        </script>
            
        <form action="" method="post" id="post_pay_counter_form">            
        
        <?php //Select settings depending on the given _GET parameter. 
        //If not asking for trial, check the userid. If valid and available as personalized settings, get those settings; 
        //if only exists as user in the blog but plugin doesn't have special settings registered, take general settings changing the userid in the array (need it to be numeric for the metaboxes checks); 
        //if it isn't numeric, simply take general settings.
		
        if( isset( $_GET['userid'] ) AND $_GET['userid'] == 'trial' ) {
            self::$edit_options_counter_settings = post_pay_counter_functions_class::get_settings( 'trial' );
            
            //If trial settings, strip the trial options and update stats boxes; move the permission one to the left 
            remove_meta_box( 'post_pay_counter_trial_settings', $this->post_pay_counter_options_menu_slug, 'normal' );
            remove_meta_box( 'post_pay_counter_update_countings', $this->post_pay_counter_options_menu_slug, 'side' );
            remove_meta_box( 'post_pay_counter_permissions', $this->post_pay_counter_options_menu_slug, 'side' );
            add_meta_box( 'post_pay_counter_permissions', 'Permissions', array( $this, 'meta_box_permissions' ), $this->post_pay_counter_options_menu_slug, 'normal' );
            
        } else {
            
            if( isset( $_GET['userid'] ) AND is_numeric( $_GET['userid'] ) ) {
                
                if( ! get_userdata( (int) $_GET['userid'] ) ) {
                    echo '<strong>The requested user does not exist.</strong>';
                    return;
                }
                
                self::$edit_options_counter_settings = post_pay_counter_functions_class::get_settings( (int) $_GET['userid'], TRUE, FALSE );
                
                //If current page is a new userid special settings or has trial settings, take general/trial settings but change the userid
                if( self::$edit_options_counter_settings->userID == 'general' ) {
                    self::$edit_options_counter_settings->userID = $_GET['userid'];
                 } else if( self::$edit_options_counter_settings->userID == 'trial' ) {
                    self::$edit_options_counter_settings->userID = $_GET['userid'];
                 }
                                
            } else {
                self::$edit_options_counter_settings = self::$general_settings;
            }
        }
        
        self::$edit_options_counter_settings->ordinary_zones = unserialize( self::$edit_options_counter_settings->ordinary_zones );
        
        //Nonces for major security
        wp_nonce_field( 'post_pay_counter_main_form_update' );
        wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
        wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
        
            <div id="poststuff" class="metabox-holder has-right-sidebar">
                <div id="side-info-column" class="inner-sidebar">
            	<?php do_meta_boxes( self::$post_pay_counter_options_menu_slug, 'side', null ); ?>
                </div>
                <div id="post-body" class="has-sidebar">
                    <div id="post-body-content" class="has-sidebar-content">
            	<?php do_meta_boxes( self::$post_pay_counter_options_menu_slug, 'normal', null ); ?>
                    </div>
                </div>
            </div>
            <div class="clear"></div>
            <input type="hidden" name="userID" value="<?php echo self::$edit_options_counter_settings->userID ?>" />
            <input type="submit" class="button-primary" name="post_pay_counter_options_save" id="post_pay_counter_options_save" value="<?php _e( 'Save options' ) ?>" />
        </form>
        </div>
    <?php }
    
    //Function to update the counting payment on post_save
    function post_pay_counter_update_post_counting( $new_status, $old_status, $post ) {
        
        $post = (object) $post;
        
        //Intersecate the unserialized array of allowed user groups with the groups the post writer belongs to, then continue only if resulting array is not empty 
        $user_roles_intersection = array_intersect( unserialize( self::$general_settings->user_roles_to_include_in_counting ), get_userdata( $post->post_author )->roles );
        
        //Only accept posts of the allowed post types, status and user groups
        if( strpos( self::$allowed_status, $new_status ) !== FALSE AND strpos( self::$allowed_post_types, $post->post_type ) !== FALSE AND ! empty( $user_roles_intersection ) ) {
            
            //Call update counter value function
            post_pay_counter_functions_class::update_single_counting( $post->ID, $post->post_status, $post->post_date, $post->post_author, $post->post_pay_counter, $post->post_pay_counter_count, $post->post_content );
        
        //If the post is a draft or anyway shoudn't be counted, set the fields to null. Do this because: publish a post, it gets counted; 
        //put the same post in draft, you don't lose the counting values and thus even if you republish it it still has the old date
        } else {
             if( $post->post_pay_counter != '' )
                $wpdb->query( 'UPDATE '.$wpdb->posts.' SET post_pay_counter = NULL, post_pay_counter_count = NULL, post_pay_counter_paid = NULL WHERE ID = '.$post_id );
        }
    }
    
    //Showing stats
    function post_pay_counter_show_stats() {
        global $wpdb,
               $current_user;
        
        post_pay_counter_functions_class::fix_messed_up_stuff();
        
        //Merging _GET and _POST data due to the time range form available in the stats page header. 
        //We don't know whether the user is choosing the time frame from the form (via POST data) or if they arrived to this page following a link (via GET data)
        $get_and_post = array_merge( $_GET, $_POST );
        
        //Validate time range values (start and end), if set. They must be isset, numeric and positive. 
        //If something's wrong, start and end time are taken from the default settings the user set (publication time range) and defined in the construct of functions
        if( ( isset( $get_and_post['tstart'] ) AND ( ! is_numeric( $get_and_post['tstart'] ) OR  $get_and_post['tstart'] < 0 ) )
        OR ( isset( $get_and_post['tend'] ) AND ( ! is_numeric( $get_and_post['tend'] ) OR  $get_and_post['tend'] < 0 ) )
        OR ( ! isset( $get_and_post['tend'] ) OR ! isset( $get_and_post['tend'] ) ) ) {
            //If user has selected a time range, convert it into unix timestamp
            if( strtotime( @$get_and_post['tstart'] ) AND strtotime( @$get_and_post['tend'] ) ) {
                $get_and_post['tstart'] = strtotime( $get_and_post['tstart'].' 00:00:01' );
                $get_and_post['tend']   = strtotime( $get_and_post['tend'].' 23:59:59' );
            } else {
                $get_and_post['tstart'] = mktime( 0, 0, 1, date( 'm', self::$publication_time_range_start ), date( 'd', self::$publication_time_range_start ), date( 'Y', self::$publication_time_range_start ) );
                $get_and_post['tend']   = mktime( 23, 59, 59, date( 'm', self::$publication_time_range_end ), date( 'd', self::$publication_time_range_end ), date( 'Y', self::$publication_time_range_end ) );
            }
        }
        
        $current_user_settings  = post_pay_counter_functions_class::get_settings( $current_user->ID, TRUE );
        $alternate              = '';
        
        //CSV file exporting feature
        if( isset( $get_and_post['export'] ) AND $get_and_post['export'] == 'csv' AND ( $current_user->user_level >= 7 OR $current_user_settings->can_csv_export == 1 ) ) {
            post_pay_counter_functions_class::csv_export( @$get_and_post['author'], @$get_and_post['tstart'], @$get_and_post['tend'] );
        } ?>
        
        <div class="wrap">
            <h2>Post Pay Counter Stats</h2>
        
        <?php if( isset( $get_and_post['author'] ) AND is_numeric( $get_and_post['author'] ) AND $userdata = get_userdata( $get_and_post['author'] ) ) {
                
                //Generate stats for the requested month (which could also be the current one) and asked author. Then show the header part
                $generated_stats    = post_pay_counter_functions_class::generate_stats( $get_and_post['author'], $get_and_post['tstart'], $get_and_post['tend'] );
                $user_display_name  = $userdata->display_name;
                
                post_pay_counter_functions_class::show_stats_page_header( $user_display_name, admin_url( self::$post_pay_counter_stats_menu_link.'&amp;author='.$get_and_post['author'].'&amp;tstart='.$get_and_post['tstart'].'&amp;tend='.$get_and_post['tend'] ), $get_and_post['tstart'], $get_and_post['tend'] ); ?>
                
                <?php //If the returned value is a string, it means we had an error, and we show it
                if( is_string( $generated_stats ) ) {
                    echo $generated_stats;
                
                } else { ?>
                    <table class="widefat fixed">
                        <thead>
                    	   <tr>
                                <th scope="col" width="34%">Post title</th>
                                <th scope="col" width="10%">Post type</th>
                                <th scope="col" width="8%">Status</th>
                                <th scope="col" width="8%">Date</th>
                                <th scope="col" width="7%"><?php echo ucfirst( self::$current_counting_method_word ); ?></th>                                
                                <th scope="col" width="11%">Comments</th>
                                <th scope="col" width="6%">Imgs</th>
                                <th scope="col" width="9%">Payment</th>

                    <?php //Show paid column only if user is allowed to or is admin
                    if( $current_user_settings->can_view_paid_amount == 1 OR $current_user->user_level >= 7 ) {
                        
                        //Tick/Untick all can only be seen and triggered by admins
                        if( $current_user->user_level >= 7 ) {
                            $links = ' <span style="font-size: 10px;"><a href="#" class="one_to_rule_them_all_check" title="Click here to check all the paid checkboxes below">Tick</a>|<a href="#" class="one_to_rule_them_all_uncheck" title="Click here to uncheck all the paid checkboxes below">Untick</a></span>';
                        } ?>
                                <th scope="col" width="10%">Paid<?php echo @$links; ?></th>
                    <?php } ?>

                    		</tr>
                    	</thead>
                    	<tfoot>
                            <tr>
                                <th scope="col" width="34%">Post title</th>
                                <th scope="col" width="10%">Post type</th>
                                <th scope="col" width="8%">Status</th>
                                <th scope="col" width="8%">Date</th>
                                <th scope="col" width="7%"><?php echo ucfirst( self::$current_counting_method_word ); ?></th>                                
                                <th scope="col" width="11%">Comments</th>
                                <th scope="col" width="6%">Imgs</th>
                                <th scope="col" width="9%">Payment</th>

                    <?php //Show paid column only if user is allowed to or is admin
                    if( $current_user_settings->can_view_paid_amount == 1 OR $current_user->user_level >= 7 ) {
                        
                        //Tick/Untick all can only be seen and triggered by admins
                        if( $current_user->user_level >= 7 ) {
                            $links = ' <span style="font-size: 10px;"><a href="#" class="one_to_rule_them_all_check" title="Click here to check all the paid checkboxes below">Tick</a>|<a href="#" class="one_to_rule_them_all_uncheck" title="Click here to uncheck all the paid checkboxes below">Untick</a></span>';
                        } ?>
                                <th scope="col" width="10%">Paid<?php echo @$links; ?></th>
                    <?php } ?>

                    		</tr>
                    	</tfoot>
                        <tbody>
                    
                    <?php $n = 0; 
                    foreach( $generated_stats['general_stats'] as $single ) {
                        
                        $paid_checked = 0;
                        
                        //If user can, prepare span with all the counting details for overlay display
                        if( $current_user_settings->can_view_overlay_counting_details == 1 OR $current_user->user_level >= 7 ) {
                            $payment_to_show = '<span style="border-bottom: 1px dotted;"><a title="Ordinary payment (words/visits only): &euro; '.$single['ordinary_payment'].'; Post bonus: &euro; '.$single['payment_bonus'].'; Minimum fee rounding: &euro; '.$single['minimum_fee'].'; Image bonus: &euro; '.$single['image_bonus'].'; Comment bonus: &euro; '.$single['comment_bonus'].'" style="text-decoration: none; color: #000000;">&euro; '.$single['post_payment'].'</a></span>';
                        } else {
                            $payment_to_show = $single['post_payment'];
                        }
                        
                        //Wrap post title if too long
                        if( strlen( $single['post_title'] ) > 40 )
                            $title_to_show = substr( $single['post_title'], 0, 40 ).'...';
                        else
                            $title_to_show = $single['post_title']; 
                        
                        //Class alternate adding
                        if( $n % 2 == 1 )
                            $alternate = ' class="alternate"';
                        
                        //If payment value is 0, make the row opacity lighter
                        if( $single['post_payment'] == 0 ) { ?>
                            <tr style="opacity: 0.60; height: 25px;"<?php echo $alternate ?>>
                        <?php } else { ?>
                            <tr style="height: 25px;" <?php echo $alternate ?>>
                        <?php } ?>
                        
                                <td><a href="<?php echo get_permalink( $single['ID'] ); ?>" title="<?php echo $single['post_title']; ?>"><?php echo $title_to_show; ?></a></td>
                                <td><?php echo $single['post_type']; ?></td>
                                <td><?php echo $single['post_status']; ?></td>
                                <td><?php echo $single['post_date']; ?></td>
                                <td><?php echo $single['words_count']; ?></td>
                                <td><?php echo $single['comment_count']; ?></td>
                                <td><?php echo $single['image_count']; ?></td>
                                <td><?php echo $payment_to_show; ?></td>
                                <td>

                        <?php //Show paid amount only if user is allowed to or is admin
                        if( $current_user_settings->can_view_paid_amount == 1 OR $current_user->user_level >= 7 ) {
                            
                            $total_paid     = 0;
                            $still_to_pay   = $single['post_payment'];
                            
                            //Check whether the post has been paid and, if so, check the related _paid_ checkbox
                            if( $single['is_post_paid'] != NULL ) {
                                $is_post_paid       = unserialize( $single['is_post_paid'] );
                                $total_paid         = 0;
                                $payment_history    = 'Payment history:';
                                
                                //Unfold payment history
                                foreach( $is_post_paid as $payment ) {
                                    $total_paid         = sprintf( '%.2f', $total_paid + $payment[0] );
                                    $payment_history    .= '
    '.date( 'd/m/Y', $payment[1] ).' => &euro; '.sprintf( '%.2f', $payment[0] );
                                    $still_to_pay       = sprintf( '%.2f', $single['post_payment'] - $total_paid );
                                }
                                
                                //Show checkbox and hidden fields and javascript code and everything related to marking-as-paid only if admin
                                if( $current_user->user_level >= 7 ) {
                                
                                    //Checkbox checking
                                    if( $total_paid == $single['post_payment'] ) {
                                        $paid_checked = ' checked="checked"';
                                    
                                    //If the total paid amount is different from current post payment, catch user attention and warn them
                                    } else {
                                        $span_style = ' background-color: yellow;';
                                        $total_paid = $total_paid.' <abbr title="This item requires your attention. In total, you have paid this post &euro; '.$total_paid.' but, as of now, the counting total amounts to &euro; '.$single['post_payment'].'. Thus, you owe the author &euro; '.sprintf( '%.2f', $single['post_payment'] - $total_paid ).'. Checking the checkbox you will mark this as solved.">?</abbr>';
                                    }
                                }
                                    
                            }
                            
                            if( $current_user->user_level >= 7 ) { ?>
                            
                                        <span style="float: left;">
                                            <input type="checkbox" class="paid_status_update" id="<?php echo $single['ID']; ?>" accesskey="<?php echo $still_to_pay ?>" name="paid_status_update"<?php echo $paid_checked; ?> />
                                            <input type="hidden" name="post_id" value="<?php echo $single['ID']; ?>" />
                                            <input type="hidden" name="now_paid" value="<?php echo $still_to_pay ?>" />
                                            <input type="hidden" name="payment_history" value="<?php echo base64_encode( serialize( $single['is_post_paid'] ) ); ?>" />
                                            <input type="hidden" name="post_payment" value="<?php echo $single['post_payment']; ?>" />
                                        </span>
                                
                            <?php } 
                        }
                        
                        //Show paid amount, this also to users who are allowed to
                        if( $single['is_post_paid'] != NULL ) { ?>
                                        <span style="border-bottom: 1px dotted; font-size: xx-small; text-align: left; margin-left: 10px;<?php echo @$span_style; ?>" id="amount_<?php echo $single['ID']; ?>">
                                            <a title="<?php if( $current_user->user_level >= 7 ) { echo $payment_history; } ?>" style="text-decoration: none; color: #000000;">&euro; <?php echo $total_paid; ?></a>
                                        </span>
                        <?php }
                        
                        //Javascript code only to admins
                        if( $current_user->user_level >= 7 ) { ?>
                            
                                        <script type="text/javascript">
                                            /* <![CDATA[ */
                                                jQuery(document).ready(function($) {
                                                    jQuery('.paid_status_update').unbind('change').change( function () { //The unbinding prevents the .change from firing multiple times
                                                        var clicked_checkbox = jQuery(this);
                                                        var data = {
                                                            action:             "post_pay_counter_post_paid_status",
                                                            security_nonce:     "<?php echo wp_create_nonce( 'post_pay_counter_post_paid_update' ); ?>",
                                                            post_id:            jQuery(this).parent().children('input[name="post_id"]').val(),
                                                            now_paid:           jQuery(this).parent().children('input[name="now_paid"]').val(),
                                                            payment_history:    jQuery(this).parent().children('input[name="payment_history"]').val(),
                                                            checked:            jQuery(this).attr('checked')
                                                        };                         
                                                        
                                                        jQuery.post(ajaxurl, data, function(response) {
                                                            if( jQuery(clicked_checkbox).attr('checked') == 'checked' ) {
                                                                jQuery('#amount_'+clicked_checkbox.parent().children('input[name="post_id"]').val()).remove();
                                                                jQuery('<span/>', {
                                                                    id: 'amount_'+clicked_checkbox.parent().children('input[name="post_id"]').val(),
                                                                    style: 'margin-left: 10px; border-bottom: 1px dotted; font-size: xx-small; text-align: left;',
                                                                    text: '\u20AC '+jQuery(clicked_checkbox).parent().children('input[name="post_payment"]').val(), //\u20AC is €
                                                                }).appendTo(jQuery(clicked_checkbox).closest('td'));
                                                            } else {
                                                                jQuery('#amount_'+clicked_checkbox.parent().children('input[name="post_id"]').val()).remove();
                                                            }
                                                        });
                                                        
                                                    });
                                                    
                                                     //Selectall-at-once function
                                                    jQuery('.one_to_rule_them_all_check').click(function (e) {
                                                        event.stopPropagation();
                                                        e.preventDefault();
                                                        
                                                        jQuery('.paid_status_update').each(function() {
                                                            jQuery(this).attr('checked', 'checked');
                                                            jQuery(this).trigger('change');
                                                        });
                                                    });
                                                    
                                                    jQuery('.one_to_rule_them_all_uncheck').click(function (e) {
                                                        event.stopPropagation();
                                                        e.preventDefault();
                                                        jQuery('.paid_status_update').each(function() {
                                                            jQuery(this).removeAttr('checked');
                                                            jQuery(this).trigger('change');
                                                        });
                                                    });
                                                });
                                            /* ]]> */
                                        </script>
                            
                        <?php }
                        $span_style = '';
                        $n++; ?>
                            
                                    </td>
                                </tr>
                        
                    <?php } ?>
                        
                            </tbody>
                        </table>
                
                <?php $this->post_pay_counter_show_total_stats( $generated_stats['overall_stats'], $current_user_settings, $get_and_post );
                
                }
        
        //Here we have general stats instead, without any author selection
        } else {
                
                //Generate stats for the requested month (which could also be the current one), but no author. Then show the header part
                $generated_stats = post_pay_counter_functions_class::generate_stats( false, $get_and_post['tstart'], $get_and_post['tend'] ); 
                post_pay_counter_functions_class::show_stats_page_header( 'General', admin_url( self::$post_pay_counter_stats_menu_link.'&amp;tstart='.$get_and_post['tstart'].'&amp;tend='.$get_and_post['tend'] ), $get_and_post['tstart'], $get_and_post['tend'] );
                        
                //If the returned value is a string, it means we had an error
                if( is_string( $generated_stats ) ) {
                    echo $generated_stats;
                } else { ?>
                
                <table class="widefat fixed">
                    <thead>
                        <tr>
                			<th scope="col">Author</th>
                			<th scope="col" width="13%">Written posts</th>
                			<th scope="col" width="17%">Total payment</th>
                
                <?php //If current_user == admin, show paypal addresses
                if( $current_user->user_level >= 7 ) { ?>
                    <th scope="col" width="25%">PayPal address</th>
                <?php } ?>
                              
                </tr>
        	</thead>
        	<tfoot>
        		<tr>
        			<th scope="col">Author</th>
        			<th scope="col" width="13%">Written posts</th>
    	            <th scope="col" width="17%">Total payment</th>
                    
                <?php if( $current_user->user_level >= 7 ) { ?>
                    <th scope="col" width="25%">PayPal address</th>
                <?php } ?>
                        
        		</tr>
        	</tfoot>
            <tbody>
                
                <?php $n = 0; 
                foreach( $generated_stats['general_stats'] as $key => $value ) {
    
                    $author_display_name    = get_userdata( $key )->display_name;
                    $author_paypal_address  = @post_pay_counter_functions_class::get_settings( $key )->paypal_address;
                    
                    //If user can, prepare span with all the counting details for overlay display
                    if( $current_user_settings->can_view_overlay_counting_details == 1 OR $current_user->user_level >= 7 ) {
                        $payment_to_show = '<span style="border-bottom: 1px dotted;"><a title="Ordinary payment (words/visits only): &euro; '.$value['ordinary_payment'].'; Post bonus: &euro; '.$value['payment_bonus'].'; Minimum fee rounding: &euro; '.$value['minimum_fee'].'; Image bonus: &euro; '.$value['image_bonus'].'; Comment bonus: &euro; '.$value['comment_bonus'].'" style="text-decoration: none; color: #000000;">&euro; '.$value['total_payment'].'</a></span>';
                    } else {
                        $payment_to_show = $value['total_payment'];
                    }
                    
                    //Class alternate adding
                    if( $n % 2 == 1 )
                        $alternate = ' class="alternate"';
                        
                    //If payment value is 0, make the row opacity lighter
                    if( $value['total_payment'] == 0 ) { ?>
                <tr style="opacity: 0.60;"<?php echo $alternate ?>>
                    <?php } else { ?>
                <tr<?php echo $alternate ?>>
                    <?php }
                    
                    //If current user can't see detailed stats, user's names aren't links but its one
                    if( $current_user_settings->can_view_others_detailed_stats == 0 AND $current_user->user_level < 7 AND $key != $current_user->ID ) { ?>
                    <td><?php echo $author_display_name; ?></td>
                    <?php } else { ?>
                    <td><a href="<?php echo admin_url( self::$post_pay_counter_stats_menu_link.'&amp;author='.$key.'&amp;tstart='.$get_and_post['tstart'].'&amp;tend='.$get_and_post['tend'] ) ?>" title="<?php echo $author_display_name ?>"><?php echo $author_display_name ?></a></td>
                    <?php } ?>
                    
                    <td><?php echo $value['posts'] ?></td>
                    <td><?php echo $payment_to_show ?></td>
                        
                         <?php if( $current_user->user_level >= 7 ) { ?>
                    <td><?php echo $author_paypal_address ?></td>
                         <?php } ?>
                    
                </tr>
                <?php $n++;
                } ?>
                
        </tbody>
    </table>
    
            <?php $this->post_pay_counter_show_total_stats( $generated_stats['overall_stats'], $current_user_settings, $get_and_post );
            
            }
		}
        
        //Showing overall stats, since blog started (if current user is allowed to)
        if( $current_user_settings->can_view_overall_stats == 1 OR $current_user->user_level >= 7 )
            post_pay_counter_functions_class::generate_overall_stats();
        
    }
    
    function post_pay_counter_show_total_stats( $total_stats, $current_user_settings, $get_and_post ) {
        global $current_user; ?>
        <br />
        <br />
        
        <table class="widefat fixed">
            <tr>
                <td width="40%">Total displayed posts:</td>
                <td align="left" width="10%"><?php echo $total_stats['total_posts'] ?></td>
                <td width="35%">Total displayed payment:</td>
                <td align="left" width="15%">&euro; <?php echo $total_stats['total_payment']; echo @$total_stats['payment_bonus']; ?></td>
            </tr>
            <tr>
                <td width="40%">Total displayed <?php echo self::$current_counting_method_word; ?>:</td>
                <td align="left" width="10%"><?php echo $total_stats['total_counting'] ?></td>
                <td width="35%">Total displayed admin bonus:</td>
                <td align="left" width="15%">&euro; <?php echo $total_stats['total_bonus']; ?></td>
            </tr>
        <?php //Show the other rows only if using zones as counting system 
        if( $current_user_settings->counting_system_zones == 1 ) { ?>
            <tr class="alternate">
    			<td width="40%">N&deg; of posts below the first zone (<<?php echo self::$ordinary_zones[1]['zone'] ?> words):</td>
    			<td align="left" width="10%"><?php echo @(int) $total_stats['0zone'] ?></td>
                <td width="40%">N&deg; of posts in the first zone (<?php echo self::$ordinary_zones[1]['zone'].'-'.self::$ordinary_zones[2]['zone'] ?> words):</td>
    			<td align="left" width="10%"><?php echo @(int) $total_stats['1zone'] ?></td>
    		</tr>
            <tr>
                <td width="40%">N&deg; of posts in the second zone (<?php echo self::$ordinary_zones[2]['zone'].'-'.self::$ordinary_zones[3]['zone'] ?> words):</td>
    			<td align="left" width="10%"><?php echo @(int) $total_stats['2zone'] ?></td>
    			<td width="40%">N&deg; of posts in the third zone (<?php echo self::$ordinary_zones[3]['zone'].'-'.self::$ordinary_zones[4]['zone'] ?> words):</td>
    			<td align="left" width="10%"><?php echo @(int) $total_stats['3zone'] ?></td>
    		</tr>
            <tr class="alternate">
                <td width="40%">N&deg; of posts in the fourth zone (<?php echo self::$ordinary_zones[4]['zone'].'-'.self::$ordinary_zones[5]['zone'] ?> words):</td>
    			<td align="left" width="10%"><?php echo @(int) $total_stats['4zone'] ?></td>
    	<?php if( self::$general_settings->add_five_more_zones == 1 ) { ?>
    			<td width="40%">N&deg; of posts in the fifth zone (<?php echo self::$ordinary_zones[5]['zone'].'-'.self::$ordinary_zones[6]['zone'] ?> words):</td>
    	<?php } else { ?>
                <td width="40%">N&deg; of posts in the fifth zone (<?php echo self::$ordinary_zones[5]['zone'] ?>+ words):</td>
        <?php } ?>
                <td align="left" width="10%"><?php echo @(int) $total_stats['5zone'] ?></td>
            </tr>
        <?php if( count( self::$ordinary_zones ) > 5 ) { ?>
            <tr>
                <td width="40%">N&deg; of posts in the sixth zone (<?php echo self::$ordinary_zones[6]['zone'].'-'.self::$ordinary_zones[7]['zone'] ?> words):</td>
    			<td align="left" width="10%"><?php echo @(int) $total_stats['6zone'] ?></td>
                <td width="40%">N&deg; of posts in the seventh zone (<?php echo self::$ordinary_zones[7]['zone'].'-'.self::$ordinary_zones[8]['zone'] ?> words):</td>
                <td align="left" width="10%"><?php echo @(int) $total_stats['7zone'] ?></td>
            </tr>
            <tr class="alternate">
                <td width="40%">N&deg; of posts in the eigth zone (<?php echo self::$ordinary_zones[8]['zone'].'-'.self::$ordinary_zones[9]['zone'] ?> words):</td>
    			<td align="left" width="10%"><?php echo @(int) $total_stats['8zone'] ?></td>
                <td width="40%">N&deg; of posts in the ninth zone (<?php echo self::$ordinary_zones[9]['zone'].'-'.self::$ordinary_zones[10]['zone'] ?> words):</td>
                <td align="left" width="10%"><?php echo @(int) $total_stats['9zone'] ?></td>
            </tr>
            <tr>
                <td align="center" width="80%" colspan="2">N&deg; of posts in the tenth zone (<?php echo self::$ordinary_zones[10]['zone'] ?>+ words):</td>
                <td align="center" width="20%" colspan="2"><?php echo @(int) $total_stats['10zone'] ?></td>
            </tr>
       <?php } ?>
            
            <?php }
            
            //Check if current user is allowed to csv export, using the noheader parameter to allow csv download
            if( $current_user_settings->can_csv_export == 1 OR $current_user->user_level >= 7 ) { ?>
            <tr>
                <?php if( isset( $get_and_post['author'] ) ) { ?>
                <td colspan="4" align="center"><a href="<?php echo wp_nonce_url( admin_url( self::$post_pay_counter_stats_menu_link.'&amp;author='.$get_and_post['author'].'&amp;tstart='.$get_and_post['tstart'].'&amp;tend='.$get_and_post['tend'].'&amp;export=csv&amp;noheader=true' ), 'post_pay_counter_csv_export_author' ) ?>" title="Export to csv">Export stats to csv</a></td>
                <?php } else { ?>
                <td colspan="4" align="center"><a href="<?php echo wp_nonce_url( admin_url( self::$post_pay_counter_stats_menu_link.'&amp;tstart='.$get_and_post['tstart'].'&amp;tend='.$get_and_post['tend'].'&amp;export=csv&amp;noheader=true' ), 'post_pay_counter_csv_export_general' ) ?>" title="Export to csv">Export stats to csv</a></td>
                <?php } ?>
            </tr>
            <?php } ?>
        </table>
    <?php }
}

new post_pay_counter_core();

?>