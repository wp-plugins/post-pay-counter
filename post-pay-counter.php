<?php
/*
Plugin Name: Post Pay Counter
Plugin URI: http://www.thecrowned.org/post-pay-counter
Description: The Post Pay Counter plugin allows you to easily calculate and handle author's pay on a multi-author blog by computing every written post remuneration basing on admin defined rules. Define the time range of which you would like to have stats about, and the plugin will do the rest.
Author: Stefano Ottolenghi
Version: 1.1.5
Author URI: http://www.thecrowned.org/

  Copyright 2011  Ottolenghi Stefano  (email: webmaster@thecrowned.org)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

//If trying to open this file out of wordpress, warn and exit
if( ! function_exists( 'add_action' ) )
    die( 'This file is not meant to be called directly' );

include_once( 'post-pay-counter-functions.php' );
include_once( 'post-pay-counter-install-routine.php' );

class post_pay_counter_core {
    public $post_pay_counter_functions,
           $post_pay_counter_install,
           $post_pay_counter_options_menu,
           $edit_options_counter_settings;
    
    function __construct() {        
        $this->post_pay_counter_functions   = new post_pay_counter_functions_class();
        $this->post_pay_counter_install     = new post_pay_counter_install_routine();
        
        //Add left menu entries for both stats and options pages
        add_action( 'admin_menu', array( $this, 'post_pay_counter_admin_menus' ) );
        
        //Hook for the install procedure
        register_activation_hook( __FILE__, array( $this->post_pay_counter_install, 'post_pay_counter_install' ) );
        
        //Hook on blog adding on multisite wp to install the plugin there either
        add_action( 'wpmu_new_blog', array( $this->post_pay_counter_install, 'post_pay_counter_new_blog_install' ), 10, 6); 
        
        //Hook to update single posts counting on status change
        add_action( 'transition_post_status', array( $this, 'post_pay_counter_update_post_counting' ), 10, 3 );
        
        //Load the styles/jses for metaboxes, then call all the add_meta_box functions and implement the jQuery datepicker
        add_action( 'load-settings_page_post_pay_counter_options', array( $this, 'on_load_post_pay_counter_options_page' ) );
        add_action( 'load-settings_page_post_pay_counter_show_stats', array( $this, 'on_load_post_pay_counter_stats_page' ) );
        
        //Inject proper css stylesheets to make the two meta box columns 50% large equal and js tooltip activation snippet
        add_action( 'admin_head-settings_page_post_pay_counter_options', array( $this, 'post_pay_counter_metabox_css' ) );
        
        //Hook in init to record visits when counting type is visits
        add_action( 'wp_head', array( $this, 'post_pay_counter_count_view' ) );
        
        //Hook to show custom action links besides the usual "Edit" and "Deactivate"
        add_filter('plugin_action_links', array( $this, 'post_pay_counter_settings_meta_link' ), 10, 2);
        add_filter('plugin_row_meta', array( $this, 'post_pay_counter_donate_meta_link' ), 10, 2);
        
        //Manage AJAX calls (visit counting)
        add_action('wp_ajax_post_pay_counter_register_view_ajax', array( $this, 'post_pay_counter_register_view_ajax' ) );
        add_action('wp_ajax_nopriv_post_pay_counter_register_view_ajax', array( $this, 'post_pay_counter_register_view_ajax' ) );
    }
    
    function post_pay_counter_admin_menus() {
        $this->post_pay_counter_options_menu = add_options_page( 'Post Pay Counter Options', 'Post Pay Counter Options', 'manage_options', 'post_pay_counter_options', array( &$this, 'post_pay_counter_options' ) );
        add_options_page( 'Post Pay Counter Stats', 'Post Pay Counter Stats', 'edit_posts', 'post_pay_counter_show_stats', array( &$this, 'post_pay_counter_show_stats' ) );
    }
    
    function post_pay_counter_metabox_css() {        
        echo '<script type="text/javascript">
            /* <![CDATA[ */
                jQuery(document).ready(function() {
                    jQuery(".tooltip_container").tipTip({
                        activation: "click",
                        maxWidth: "300px"
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
        </style>';
    }
    
    //Reponsable for the datepicker's files loading
    function on_load_post_pay_counter_stats_page() {
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'jquery-ui-core' );
        wp_enqueue_script( 'jquery-ui-datepicker',  plugins_url( 'js/jquery.ui.datepicker.min.js', __FILE__ ), array('jquery', 'jquery-ui-core' ) );
        wp_enqueue_style( 'jquery.ui.theme', plugins_url( 'style/ui-lightness/jquery-ui-1.8.15.custom.css', __FILE__ ) );
    }
    
    function on_load_post_pay_counter_options_page() {
        //This is metaboxes stuff
        wp_enqueue_script( 'post' );
        add_meta_box( 'post_pay_counter_counting_settings', 'Counting Settings', array( $this, 'meta_box_counting_settings' ), $this->post_pay_counter_options_menu, 'normal' );
        add_meta_box( 'post_pay_counter_trial_settings', 'Trial Settings', array( $this, 'meta_box_trial_settings' ), $this->post_pay_counter_options_menu, 'normal' );
        add_meta_box( 'post_pay_counter_update_countings', 'Update Stats', array( $this, 'meta_box_update_countings' ), $this->post_pay_counter_options_menu, 'normal' );
        add_meta_box( 'post_pay_counter_personalize_settings', 'Personalize Settings', array( $this, 'meta_box_personalize_settings' ), $this->post_pay_counter_options_menu, 'side' );
        add_meta_box( 'post_pay_counter_permissions', 'Permissions', array( $this, 'meta_box_permissions' ), $this->post_pay_counter_options_menu, 'side' );
        add_meta_box( 'post_pay_counter_support', 'Support the author', array( $this, 'meta_box_support_the_author' ), $this->post_pay_counter_options_menu, 'side' );
        
        //And this is for the options page tooltips
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'jquery-tooltip-plugin', plugins_url( 'js/jquery.tiptip.min.js', __FILE__ ) );
        wp_enqueue_style( 'jquery.tooltip.theme', plugins_url( 'style/tipTip.css', __FILE__ ) );
    }
    
    //Show the "Settings" link in the plugins list
    function post_pay_counter_settings_meta_link( $links, $file ) {
       //Make sure we are on the right plugin
       if ( $file == plugin_basename( __FILE__ ) )
            $links[] = '<a href="'.admin_url( 'options-general.php?page=post_pay_counter_options' ).'" title="'.__('Settings').'">'.__('Settings').'</a>';
     
        return $links;
    }
    
    //Show the "Donate" link in the plugins list
    function post_pay_counter_donate_meta_link( $links, $file ) {
       //Make sure we are on the right plugin
       if ( $file == plugin_basename( __FILE__ ) )
            $links[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7UH3J3CLVHP8L" title="'.__('Donate').'">'.__('Donate').'</a>';
     
        return $links;
    }

    
    //Function to record visits
    function post_pay_counter_count_view() {
    	global $wpdb,
               $current_user,
               $post;
        
        //If it's a post and it's published
    	if( is_single() AND $post->post_status == 'publish' ) {
        
            //If avaiable, select special user settings, otherwise general settings will do
            $user_settings = $this->post_pay_counter_functions->get_settings( @$post->post_author, TRUE );
            
            //If chosen counting type is not visits, return. Cannot check this in the construct cause it's too early
            if( $user_settings->counting_type_visits == 0 )
                return;
    	   
           //Skip visits that shouldn't be counted: logged-in users/authors and guests things
            if( ( is_user_logged_in() AND ( $user_settings->count_visits_registered == 0 OR ( $post->post_author == $current_user->ID AND $user_settings->count_visits_authors == 0 ) ) )
            OR ( ! is_user_logged_in() AND $user_settings->count_visits_guests == 0 ) )
                return;
            
            //If bots visits shouldn't be counted, and current visit is from a bot, return
            if( $user_settings->count_visits_bots == 0 ) {
                
                //Thanks to Wp-Postviews for the array list
    			$bots_to_exlude = array( 
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
    			
    			foreach( $bots_to_exlude as $single ) {
    				if( stristr($_SERVER['HTTP_USER_AGENT'], $single ) !== false ) {
    					return;
    				}
    			}
    		}
            
            //If visitor doesn't have a valid cookie, set it and update db visits count
            if( ! isset( $_COOKIE['post_pay_counter_view-'.$post->ID] ) ) {
                //Set cookie via AJAX request (wp_head is too late to set a cookie beacuse of headers already sent, and init is too early because of $post unavaiability)
                echo '<!-- Post Pay Counter Views Count -->';
                wp_print_scripts( 'jquery' );	
    			echo '<script type="text/javascript">
        			/* <![CDATA[ */
                        var data = {
                            action: "post_pay_counter_register_view_ajax",
                            post_id: "'.$post->ID.'",
                            post_status: "'.$post->post_status.'",
                        };
                        
                        jQuery.post( "'.admin_url( 'admin-ajax.php' ).'", data );
        			/* ]]> */
    			</script>						
    			<!-- Post Pay Counter Views Count End -->';
    		}
    	}
    }
    
    //Sets the view cookie and register the visit in the db (AJAX called)
    function post_pay_counter_register_view_ajax() {
        setcookie( 'post_pay_counter_view-'.$_REQUEST['post_id'], 'post_pay_counter_view-'.get_bloginfo( 'url' ).'-'.$_REQUEST['post_id'], time()+86400, '/' );
        $this->post_pay_counter_functions->update_single_counting( $_REQUEST['post_id'], $_REQUEST['post_status'] );
    }
    
    function meta_box_counting_settings() { ?>
        <div style="font-weight: bold; text-align: left;">Counting type</div>
            <?php $this->post_pay_counter_functions->echo_p_field( 'Count posts pay basing on their words', $this->edit_options_counter_settings->counting_type_words, 'radio', 'counting_type', 'The words that make up posts content will be used to compute the right pay, basing on the next bunch of settings', 'Words', 'counting_type_words' );
            $this->post_pay_counter_functions->echo_p_field( 'Count posts pay basing on their unique daily visits', $this->edit_options_counter_settings->counting_type_visits, 'radio', 'counting_type', 'Unique daily visits will be used to compute the right pay,  basing on the next bunch of settings. A simple cookie is used (<strong>notice</strong> that deleting it and refreshing the post page make the counter to log a new visit), and you can define what kinds of visits you want to be counted.', 'Visits', 'counting_type_visits' ); ?>
            <br />        
        <div style="font-weight: bold; text-align: left;">Counting system</div>
        <?php $this->post_pay_counter_functions->echo_p_field( 'Use the multiple zones system', $this->edit_options_counter_settings->counting_system_zones, 'radio', 'counting_system', 'With this system you can define up to 5 zones of retribution, so that from X words/visits to Y words/visits the same pay will be applied (eg. from 200 words to 300 words pay 2.00). It doesn\'t matter how many words/visits a post has, but only in what zone it lies on.', 'counting_system_zones', 'counting_system_zones' ); ?>
        <div id="counting_system_zones_content">
            <table style="border: none; margin-left: 3em; width: 100%;">
    			<thead>
                    <tr>
                        <th width="50%" align="left">Words/Visits n&deg;</th>
    			        <th width="50%" align="left">Payment</th>
                    </tr>
                </thead>
    			<tbody>
                    <tr>
                        <td width="50%"><input type="text" name="zone1_count" value="<?php echo $this->edit_options_counter_settings->zone1_count ?>" /></td>
                        <td width="50%"><input type="text" name="zone1_payment" value="<?php echo $this->edit_options_counter_settings->zone1_payment ?>" /></td>
                    </tr>
    				<tr>
                        <td width="50%"><input type="text" name="zone2_count" value="<?php echo $this->edit_options_counter_settings->zone2_count ?>" /></td>
                        <td width="50%"><input type="text" name="zone2_payment" value="<?php echo $this->edit_options_counter_settings->zone2_payment ?>" /></td>
                    </tr>
    				<tr>
                        <td width="50%"><input type="text" name="zone3_count" value="<?php echo $this->edit_options_counter_settings->zone3_count ?>" /></td>
                        <td width="50%"><input type="text" name="zone3_payment" value="<?php echo $this->edit_options_counter_settings->zone3_payment ?>" /></td>
                    </tr>
    				<tr>
                        <td width="50%"><input type="text" name="zone4_count" value="<?php echo $this->edit_options_counter_settings->zone4_count ?>" /></td>
                        <td width="50%"><input type="text" name="zone4_payment" value="<?php echo $this->edit_options_counter_settings->zone4_payment ?>" /></td>
                    </tr>
    				<tr>
                        <td width="50%"><input type="text" name="zone5_count" value="<?php echo $this->edit_options_counter_settings->zone5_count ?>" /></td>
                        <td width="50%"><input type="text" name="zone5_payment" value="<?php echo $this->edit_options_counter_settings->zone5_payment ?>" /></td>
                    </tr>
                </tbody>
    		</table>
        </div>
        <?php $this->post_pay_counter_functions->echo_p_field( 'Use the unique payment system', $this->edit_options_counter_settings->counting_system_unique_payment, 'radio', 'counting_system', 'With this system, every word/visit is important since each single one more means a higher pay. Just think that the words/visits number will be multiplied for the unique payment value you enter.', 'counting_system_unique_payment', 'counting_system_unique_payment' ); ?>
        <div style="margin-left: 3em;" id="counting_system_unique_payment_content">
            <label>Unique payment value <input type="text" name="unique_payment_value" value="<?php echo $this->edit_options_counter_settings->unique_payment ?>" /></label>
            <br />
        </div>
        <br />
        <div style="font-weight: bold; text-align: left;">Counting options</div>
        <p>
            <label>Award a <input type="text" name="bonus_comment_payment" value="<?php echo $this->edit_options_counter_settings->bonus_comment_payment ?>" size="3" /> comment bonus</label> <label>when a post goes over <input type="text" name="bonus_comment_count" value="<?php echo $this->edit_options_counter_settings->bonus_comment_count ?>" size="2" /> comments</label>
        </p>
        <p>
            <label>After the first image, credit <input type="text" name="bonus_image_payment" value="<?php echo $this->edit_options_counter_settings->bonus_image_payment ?>" size="4" /> for each image more</label>
        </p>
        <div id="counting_type_words_content">
        <?php $this->post_pay_counter_functions->echo_p_field( 'Count pending revision posts', $this->edit_options_counter_settings->count_pending_revision_posts, 'checkbox', 'count_pending_revision_posts', 'While published and scheduled posts are automatically counted, you can decide to include pending revision ones or not.' ); ?>
        </div>
        <div id="counting_type_visits_content">        
        <?php $this->post_pay_counter_functions->echo_p_field( 'Count visits from guests', $this->edit_options_counter_settings->count_visits_guests, 'checkbox', 'count_visits_guests', 'Define whether visits coming from <em>non</em> logged-in users should be counted or not.' );
            $this->post_pay_counter_functions->echo_p_field( 'Count visits from registered users', $this->edit_options_counter_settings->count_visits_registered, 'checkbox', 'count_visits_registered', 'Define whether visits coming from logged-in users should be counted or not.' );
            $this->post_pay_counter_functions->echo_p_field( 'Count visits from the post author', $this->edit_options_counter_settings->count_visits_authors, 'checkbox', 'count_visits_authors', 'Define whether visits coming from the author of the selected post should be counted or not.' );
            $this->post_pay_counter_functions->echo_p_field( 'Count visits from bots', $this->edit_options_counter_settings->count_visits_bots, 'checkbox', 'count_visits_bots', 'Define whether visits coming from search engines crawlers should be counted or not.' ); ?>
        </div>
            
        <?php $this->post_pay_counter_functions->echo_p_field( 'Allow post payment bonuses', $this->edit_options_counter_settings->allow_payment_bonuses, 'checkbox', 'allow_payment_bonuses', 'If checked, a custom field will allow to award a post bonus in the writing page. Do this by creating a new custom field named <em>payment_bonus</em> with the value you want to be the bonus (read the FAQ for details). Take care because everyone who can edit posts can also handle this custom field, potentially having their posts payed more without your authorization. In the stats page you will anyway see what posts have bonuses, which are shown in brackets.' ); 
        
        //Show this only if we're in a particular author settings page (paypal address input field)
        if( is_numeric( $this->edit_options_counter_settings->userID ) ) { ?>
            <p>
                <label>Add here the user's paypal address for an easier payment <input type="text" name="paypal_address" size="28" value="<?php echo $this->edit_options_counter_settings->paypal_address ?>" /></label>
            </p>
         <?php }
    }
    
    function meta_box_permissions() { ?>
        <p>Just a few field to help you keeping users away from where they shouldn't be. Remember that administrators override all these permissions even if they're set as personalized settings.</p>
        <?php $this->post_pay_counter_functions->echo_p_field( 'Make other users\' general stats viewable', $this->edit_options_counter_settings->can_view_others_general_stats, 'checkbox', 'can_view_others_general_stats', 'If unchecked, users will only be able to see their stats in the general page. Other users\' names, posts and pay counts won\'t be displayed.' );
        $this->post_pay_counter_functions->echo_p_field( 'Make other users\' detailed stats viewable', $this->edit_options_counter_settings->can_view_others_detailed_stats, 'checkbox', 'can_view_others_detailed_stats', 'If unchecked, other users won\'t be able to see other user\'s detailed stats (ie. written posts details) but still able to see general ones. ' );
        $this->post_pay_counter_functions->echo_p_field( 'Make old stats viewable', $this->edit_options_counter_settings->can_view_old_stats, 'checkbox', 'can_view_old_stats', 'If checked, users won\'t be able to view stats with a start time prior to the first day of the current month.' );
        $this->post_pay_counter_functions->echo_p_field( 'Make overall stats viewable', $this->edit_options_counter_settings->can_view_overall_stats, 'checkbox', 'can_view_overall_stats', 'Responsible of the <em>Overall Stats</em> box displaying. It shows some interesting data regarding your blog since you started it, but their generation it\'s quite heavy since it selects all the conted posts ever.' );
        $this->post_pay_counter_functions->echo_p_field( 'Make viewable the use of special settings in countings', $this->edit_options_counter_settings->can_view_special_settings_countings, 'checkbox', 'can_view_special_settings_countings', 'If you personalize settings by user, keep this in mind. If unchecked, users won\'t see personalized settings in countings, they\'ll believe everybody is still using general settings. Anyway, the selected posts author will see them.' );
        $this->post_pay_counter_functions->echo_p_field( 'Make post bonuses visible to other users', $this->edit_options_counter_settings->can_view_payment_bonuses, 'checkbox', 'can_view_payment_bonuses', 'If you sometimes award really well written posts with payment bonuses, you may also want to hide them to certain or all users.' );
        $this->post_pay_counter_functions->echo_p_field( 'Allow stats to be downloadable as csv files', $this->edit_options_counter_settings->can_csv_export, 'checkbox', 'can_csv_export', 'If checked, a link in the bottom of the stats table will allow to download the displayed data as a csv file for offline consulting.' );
    }
    
    function meta_box_update_countings() { ?>
        <p>Use this section to manually rebuild stats if you are experiencing problems. Use this function if you would like to have prior to the install date posts counted. If you are in a personalize settings by user page, only the posts of that author will be updated. It is not necessary to update countings on a settings change, the plugin will care about that.</p>
        <div>        
            <span style="float: left; text-align: left; width: 50%;">
                <input type="submit" name="post_pay_counter_update_stats_countings" value="Update stats countings" class="button-secondary"  />
                <span style="width: 20px; height: 13px; text-align: right;"><img src="<?php echo plugins_url( 'style/images/info.png', __FILE__ ); ?>" title="Use this to rebuild the stats countings. If your chosen counting type is visits, they will all be set to 0, while if it is words, they will be newly computed basing on the posts content." class="tooltip_container" /></span>
            </span>
            <span style="float: right; text-align: right; width: 50%;">
                <input type="submit" name="post_pay_counter_update_stats_countings_and_dates" value="Update stats countings AND dates" class="button-secondary"  />
                <span style="width: 20px; height: 13px; text-align: right;"><img src="<?php echo plugins_url( 'style/images/info.png', __FILE__ ); ?>" title="Apart from updating stats countings (see previous tooltip for info), it also updates the datas related to them. This may generate some differences with your current stats because it uses as datas the ones Wordpress saved in the database, but it's the real way to reset the plugin data." class="tooltip_container" /></span>
            </span>
        </div>
        <div class="clear"></div>
    <?php }
    
    function meta_box_trial_settings() { ?>
        <p>Did you know you can also define some trial settings, so that new authors will be payed differently for their first writing period (and also have diverse permissions and everything)? </p>
        <p>First of all, define the trial counting settings from <a href="<?php echo admin_url( 'options-general.php?page=post_pay_counter_options&amp;userid=trial' ) ?>" title="Trial settings">this page</a>.</p>
        <?php $this->post_pay_counter_functions->echo_p_field( 'Automatic trial', $this->edit_options_counter_settings->trial_auto, 'radio', 'trial_type', 'This way, the plugin will handle all the trial stuff by itself. After you will have defined how long you want it to last (days or posts since user subscribed), forget it.', 'trial_auto', 'trial_auto' ); ?>
        <p style="margin-left: 3em;" id="trial_auto_content">
            <label>Define the period you want it to run: <br /> <input type="text" name="trial_period" value="<?php echo $this->edit_options_counter_settings->trial_period ?>" size="5" /></label>
            <span style="margin-left: 2em;"><label>
        <?php echo $this->post_pay_counter_functions->checked_or_not( $this->edit_options_counter_settings->trial_period_days, 'radio', 'trial_period_type', 'Days' ); ?> Days </label> <label> Posts <?php echo $this->post_pay_counter_functions->checked_or_not( $this->edit_options_counter_settings->trial_period_posts, 'radio', 'trial_period_type', 'Posts' ); ?>
            </label>
            </span>
        </p>
        <?php $this->post_pay_counter_functions->echo_p_field( 'Manual trial', $this->edit_options_counter_settings->trial_manual, 'radio', 'trial_type', 'Else, if you prefer to have a little more control over it, you can just select this and manually opt-in and out the trial option from the single users\' pages.', 'trial_manual', 'trial_manual' );
        //Show the "enable trial for this user" only if in a user's page
        if( is_numeric( $this->edit_options_counter_settings->userID ) ) { ?>
        <div style="margin-left: 3em;" id="trial_manual_content">
            <?php $this->post_pay_counter_functions->echo_p_field( 'Enable trial for the selected user', $this->edit_options_counter_settings->trial_enable, 'checkbox', 'trial_enable', 'Opt-in/out trial settings for the selected user' ); ?>
        </div>
        <?php }
    }
    
    function meta_box_personalize_settings() {
        global $wpdb;
        
        //General settings, valid for every editor. Showing users with personalized settings on the right
    	if( ! is_numeric( $this->edit_options_counter_settings->userID ) ) {
    		  
          //Select all users who have different settings already in place
          $personalized_users = $wpdb->get_results( 'SELECT userID FROM '.$wpdb->prefix.'post_pay_counter WHERE userID != "general" AND userID != "trial"', ARRAY_A ); 
          
          if( $this->edit_options_counter_settings->userID == 'general' )
            echo '<strong>Showing general settings</strong>';
          else if( $this->edit_options_counter_settings->userID == 'trial' )
            echo '<strong>Showing trial settings</strong>';
          
          //If special users are detected, show them
          if( $wpdb->num_rows > 0 ) { ?>
            
            <p>The following users have different settings, click to view and edit. All the other users can be found in the list below.</p>
            <div>
            
            <?php $n = 0; 
            foreach( $personalized_users as $single ) {
                $userdata = get_userdata( $single['userID'] );
                
                if( $n % 3 == 0 )
                    echo '<span style="float: left; width: 34%;">';
                else if( $n % 3 == 1 )
                    echo '<span style="width: 33%;">';
                else
                    echo '<span style="float: right; width: 33%;">';
                
                echo '<a href="'.admin_url( 'options-general.php?page=post_pay_counter_options&amp;userid='.$single['userID'] ).'" title="View and edit special settings for \''.htmlspecialchars($userdata->nickname ).'\'">'.$userdata->nickname.'</a>
                </span>';
                
                ++$n;
            } ?>
            
                <div class="clear"></div>
            </div>
            
      <?php } else { ?>
        <p>No users have different settings. Learn how to personalize settings from the form below.</p>
      <?php }
          
        //Personalized users settings
		} else { ?>
		<strong>Showing settings for "<a href="<?php echo admin_url( 'user-edit.php?user_id='.$_GET['userid'] ); ?>" title="Go to user page" style="color: #000000; text-decoration: none;"><?php echo get_userdata( $_GET['userid'] )->nickname; ?></a>"</strong>
        <p>
            <a href="<?php echo admin_url( 'options-general.php?page=post_pay_counter_options' ); ?>" title="General settings">Go back to general settings</a><br />
            <a href="<?php echo wp_nonce_url( admin_url( 'options-general.php?page=post_pay_counter_options&amp;delete='.$_GET['userid'] ), 'post_pay_counter_options_delete_user_settings' ); ?>" title="Delete this user's settings">Delete this user's settings</a>
        </p>
		<?php }
        
        //Right form to go to personalized user's settings page ?>
        <br />
        <strong>Personalize single user settings</strong>
        <p>Some people's posts are better than somebody others'? If you would like, you can adjust settings for each user, so that posts will be payed differently and they will have different permissions and trial settings, too.</p>
        <p>Select a username from the list below and let's go (users who already have special settings are not shown here, look above)!</p>
        <div style="height: 8em; overflow: auto;">
            <table width="100%">
                <thead>
                     <tr>
                        <th width="33%" align="left">Author Name</th>
                        <th width="17%" align="left">Last Post</th>
                        <th width="33%" align="left">Author Name</th>
                        <th width="15%" align="left">Last Post</th>
                    </tr>
                </thead>
            
        <?php //Select and show all the users in database
        $users_to_show = $wpdb->get_results( 'SELECT ID FROM '.$wpdb->users.' ORDER BY user_nicename ASC' );
        $n = 0;
        
        foreach( $users_to_show as $single ) {
            
            //Don't show users who already have personalized settings, they will be shown in the upper part
            if( $this->post_pay_counter_functions->get_settings( $single->ID ) )
                continue;
            
            $userdata = get_userdata( $single->ID ); 
            
            //If user is at least subscriber (can edit_posts), put it in the list
            if( $userdata->user_level >= 1 ) {
                $last_post      = $wpdb->get_row( 'SELECT ID, post_date FROM '.$wpdb->posts.' WHERE post_author = '.$single->ID.' ORDER BY post_date DESC LIMIT 0,1' );
                $last_post_date = @date( 'Y/m/d', strtotime( $last_post->post_date ) );
                
                if( $wpdb->num_rows == 0 )
                    $last_post_date = '--';
                
                if( $n % 2 == 0 )
                    echo '<tr>';
                    
                    echo '<td style="font-size: 12px;"><a href="'.admin_url( 'options-general.php?page=post_pay_counter_options&amp;userid='.$single->ID ).'" title="'.$userdata->nickname.'">'.$userdata->nickname.'</a></td>
                    <td style="font-size: 11px;"><a href="'.@get_permalink( $last_post->ID ).'" title="Go to post" style="color: #000000; text-decoration: none;">'.$last_post_date.'</a></td>';
                
                if( $n % 2 != 0 )
                    echo '</tr>';
                
                $n++;
            }
        }

        echo '</table>
        </div>';
    }
    
    function meta_box_support_the_author() { ?>
        <p>If you like the Post Pay Counter, there are a couple of things you can do to support its development:</p>
        <ul style="margin: 0 0 15px 2em;">
            <li style="list-style-image: url('<?php echo plugins_url( 'style/images/feedback.png', __FILE__ ); ?>');">Suggest new functions and ideas you would like to see in the next release of the plugin, or report bugs you've found at the <a href="http://www.thecrowned.org/post-pay-counter" title="Plugin official page">official page</a>.</li>
            <li style="list-style-image: url('<?php echo plugins_url( 'style/images/star.png', __FILE__ ); ?>');">Rate it in the <a href="http://wordpress.org/extend/plugins/post-pay-counter/" title="Wordpress directory">Wordpress Directory</a> and share the <a href="http://www.thecrowned.org/post-pay-counter" title="Official plugin page">official page</a>.</li>
            <li style="list-style-image: url('<?php echo plugins_url( 'style/images/write.png', __FILE__ ); ?>');">Have a blog or write on some website? Write about the plugin!</li>
            <li style="list-style-image: url('<?php echo plugins_url( 'style/images/paypal.png', __FILE__ ); ?>');"><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=7UH3J3CLVHP8L" title="Donate money"><strong>Donate money</strong></a>. The plugin is free and is developed in my free time: a small income would make everything easier.</li>
        </ul>
    <?php }
    
    //Updating options routine
    function post_pay_counter_options_save( $_POST ) {
        global $wpdb;
        
        //Nonce check
        check_admin_referer( 'post_pay_counter_main_form_update' );
        
        $current_counting_settings  = $this->post_pay_counter_functions->get_settings( $_POST['userID'] );
        $new_settings               = array();
        
        /* COUNTING SETTINGS BOX */
        $new_settings['userID']                 = $_POST['userID'];
        $new_settings['zone1_count']            = (int) $_POST['zone1_count'];
        $new_settings['zone1_payment']          = (float) str_replace( ',', '.', $_POST['zone1_payment'] );
        $new_settings['zone2_count']            = (int) $_POST['zone2_count'];
        $new_settings['zone2_payment']          = (float) str_replace( ',', '.', $_POST['zone2_payment'] );
        $new_settings['zone3_count']            = (int) $_POST['zone3_count'];
        $new_settings['zone3_payment']          = (float) str_replace( ',', '.', $_POST['zone3_payment'] );
        $new_settings['zone4_count']            = (int) $_POST['zone4_count'];
        $new_settings['zone4_payment']          = (float) str_replace( ',', '.', $_POST['zone4_payment'] );
        $new_settings['zone5_count']            = (int) $_POST['zone5_count'];
        $new_settings['zone5_payment']          = (float) str_replace( ',', '.', $_POST['zone5_payment'] );
        $new_settings['unique_payment']         = (float) str_replace( ',', '.', $_POST['unique_payment_value'] );
        $new_settings['bonus_comment_count']    = (int) $_POST['bonus_comment_count'];
        $new_settings['bonus_comment_payment']  = (float) str_replace( ',', '.', $_POST['bonus_comment_payment'] );
        $new_settings['bonus_image_payment']    = (float) str_replace( ',', '.', $_POST['bonus_image_payment'] );
        
        switch( $_POST['counting_type'] ) {
            case 'Words':
                $new_settings['counting_type_words']     = 1;
                $new_settings['counting_type_visits']    = 0;
                break;
                
            case 'Visits':
                $new_settings['counting_type_words']     = 0;
                $new_settings['counting_type_visits']    = 1;
                break;
                
            default:
                $new_settings['counting_type_words']     = $current_counting_settings->counting_type_words;
                $new_settings['counting_type_visits']    = $current_counting_settings->counting_type_visits;
                break;
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
        
        $new_settings['count_pending_revision_posts']   = @$this->post_pay_counter_functions->update_options_checkbox_value( $_POST['count_pending_revision_posts'] );
        $new_settings['count_visits_guests']            = @$this->post_pay_counter_functions->update_options_checkbox_value( $_POST['count_visits_guests'] );
        $new_settings['count_visits_registered']        = @$this->post_pay_counter_functions->update_options_checkbox_value( $_POST['count_visits_registered'] );
        $new_settings['count_visits_authors']           = @$this->post_pay_counter_functions->update_options_checkbox_value( $_POST['count_visits_authors'] );
        $new_settings['count_visits_bots']              = @$this->post_pay_counter_functions->update_options_checkbox_value( $_POST['count_visits_bots'] );
        $new_settings['allow_payment_bonuses']          = @$this->post_pay_counter_functions->update_options_checkbox_value( $_POST['allow_payment_bonuses'] );
            
        //If we're dealing with personalized options, check paypal address and add it to the query array
        if( is_numeric( $_POST['userID'] ) AND get_userdata( $_POST['userID'] ) ) {
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
        $new_settings['can_view_old_stats']                     = @$this->post_pay_counter_functions->update_options_checkbox_value( $_POST['can_view_old_stats'] );
        $new_settings['can_view_others_general_stats']          = @$this->post_pay_counter_functions->update_options_checkbox_value( $_POST['can_view_others_general_stats'] );
        $new_settings['can_view_others_detailed_stats']         = @$this->post_pay_counter_functions->update_options_checkbox_value( $_POST['can_view_others_detailed_stats'] );
        $new_settings['can_view_overall_stats']                 = @$this->post_pay_counter_functions->update_options_checkbox_value( $_POST['can_view_overall_stats'] );
        $new_settings['can_view_special_settings_countings']    = @$this->post_pay_counter_functions->update_options_checkbox_value( $_POST['can_view_special_settings_countings'] );
        $new_settings['can_view_payment_bonuses']               = @$this->post_pay_counter_functions->update_options_checkbox_value( $_POST['can_view_payment_bonuses'] );
        $new_settings['can_csv_export']                         = @$this->post_pay_counter_functions->update_options_checkbox_value( $_POST['can_csv_export'] );
        
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
            
            if( is_int( $_POST['userID'] ) AND get_userdata( $_POST['userID'] ) ) {
                $new_settings['trial_enable'] = @$this->post_pay_counter_functions->update_options_checkbox_value( $_POST['trial_enable'] );
            }
        }
                        
        //Check if there are already saved settings for the requested ID: if yes, update that record, otherwise, create a new one
        if( is_object( $current_counting_settings ) )
            $wpdb->update( $wpdb->prefix.'post_pay_counter', $new_settings, array( 'userID' => $new_settings['userID'] ) );
        else
            $wpdb->insert( $wpdb->prefix.'post_pay_counter', $new_settings );
        
        //If updating general settings, also update the relative class object. 
        //Need it to properly update all posts and show the new settings in the options page, othwerise old settings/options will be used for the task
        if( $new_settings['userID'] == 'general' )
            $this->post_pay_counter_functions->general_settings = $this->post_pay_counter_functions->get_settings( 'general' );
        
        //If the counting type or pending revision counting status has changed, update all the database posts records
        if( $new_settings['counting_type_words'] != @$current_counting_settings->counting_type_words
        OR  $new_settings['count_pending_revision_posts'] != @$current_counting_settings->count_pending_revision_posts ) {
            
            //If current settings are personalized (valid user id), only update that authors' posts, else everybody's
            if(is_numeric($new_settings['userID']))
                $this->post_pay_counter_functions->update_all_posts_count(FALSE, $new_settings['userID']);
            else
                $this->post_pay_counter_functions->update_all_posts_count();
		}
        
        echo '<div id="message" class="updated fade"><p><strong>Post Pay Counter settings updated.</strong> New settings take place immediately! <a href="'.admin_url( 'options-general.php?page=post_pay_counter_show_stats' ).'">Go to stats now &raquo;</a></p></div>';
    }
    
    //Function to show the options page
    function post_pay_counter_options() {
        global $wpdb,
               $current_user;
        
        /** DELETE USER'S SETTINGS **/
        if( isset( $_GET['delete'] ) AND get_userdata( (int) $_GET['delete'] ) AND $current_user->user_level >= 7 ) {
            $_GET['delete'] = (int) $_GET['delete'];
            
            //Nonce check
            check_admin_referer( 'post_pay_counter_options_delete_user_settings' );
            
            //Check if requested personalized settings do exist, if yes, delete it
            if( is_object( $this->post_pay_counter_functions->get_settings( $_GET['delete'] ) ) ) {
                $wpdb->query( 
                 $wpdb->prepare( 'DELETE FROM '.$wpdb->prefix.'post_pay_counter WHERE userID='.$_GET['delete'] )
                );
                
                //Update user's posts countings
                $this->post_pay_counter_functions->update_all_posts_count( FALSE, $_GET['delete'] );
                
                echo '<div id="message" class="updated fade"><p><strong>Personalized settings for user "'.get_userdata( $_GET['delete'] )->nickname.'" deleted successfully.</strong></p></div>';
            } else {
                echo '<div id="message" class="error fade"><p><strong>There are no special settings for user "'.get_userdata( $_GET['delete'] )->nickname.'".</strong></p></div>';
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
            
            //Distinguish between all posts update (second) and only one author's posts update (first)
            if( is_numeric( $_POST['userID'] ) )
                $this->post_pay_counter_functions->update_all_posts_count( FALSE, $_POST['userID'] );
            else
                $this->post_pay_counter_functions->update_all_posts_count();
                
            echo '<div id="message" class="updated fade"><p><strong>Stats successfully updated.</strong> <a href="'.admin_url( 'options-general.php?page=post_pay_counter_show_stats' ).'">Go to stats now &raquo;</a></p></div>';
        
        //Stats countings and dates update
        } else if( isset( $_POST['post_pay_counter_update_stats_countings_and_dates'] ) ) {
            //Nonce check
            check_admin_referer( 'post_pay_counter_main_form_update' );
            
            //Distinguish between all posts update (second) and only one author's posts update (first)
            if( is_numeric( $_POST['userID'] ) )
                $this->post_pay_counter_functions->update_all_posts_count( TRUE, $_POST['userID'] );
            else
                $this->post_pay_counter_functions->update_all_posts_count( TRUE );
                
            echo '<div id="message" class="updated fade"><p><strong>Stats successfully updated.</strong> <a href="'.admin_url( 'options-general.php?page=post_pay_counter_show_stats' ).'">Go to stats now &raquo;</a></p></div>';
        } ?>
        
            <h2>Post Pay Counter Options</h2>
            <p>From this page you can configure the Post Pay Counter plug-in. You will find all the information you need inside each following box and, for each avaiable function, clicking on the info icon on the right of them. Generated stats are always avaiable at <a href="<?php echo admin_url( 'options-general.php?page=post_pay_counter_show_stats' ) ?>" title="Go to Stats">this page</a>, where you will find many details about each post (its status, date, words, images and comments count, payment value) with tons of general statistics and the ability to browse old stats. If you want to be able to see stats since the first published post, use the Update Stats box below.</p>
            
            <script type="text/javascript">
            //Javascript snippet to hide two different set of settings depending on the selected radio
            function post_pay_counter_auto_toggle(toggle_click_1, toggle_click_1_content, toggle_click_2, toggle_click_2_content) {
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
            
            jQuery(function () {
                post_pay_counter_auto_toggle("#counting_type_words", "#counting_type_words_content", "#counting_type_visits", "#counting_type_visits_content");
                post_pay_counter_auto_toggle("#counting_system_zones", "#counting_system_zones_content", "#counting_system_unique_payment", "#counting_system_unique_payment_content");
                
                <?php if( ! isset( $_GET['userid'] ) OR ( isset( $_GET['userid'] ) AND $_GET['userid'] != 'trial' ) ) { ?>
                post_pay_counter_auto_toggle("#trial_auto", "#trial_auto_content", "#trial_manual", "#trial_manual_content");
                <?php } ?>
                
            });
        </script>
            
        <form action="" method="post">            
        
        <?php //Select settings depending on the given _GET parameter. 
        //If not asking for trial, check the userid. If valid and avaiable as personalized settings, get those settings; 
        //if only exists as user in the blog but plugin doesn't have special settings registered, take general settings changing the userid in the array (need it to be numeric for the metaboxes checks); 
        //if it isn't numeric, simply take general settings.
        if( isset( $_GET['userid'] ) AND $_GET['userid'] == 'trial' ) {
            $this->edit_options_counter_settings = $this->post_pay_counter_functions->get_settings( 'trial' );
            
            //If trial settings, strip the trial options and update stats boxes
            remove_meta_box( 'post_pay_counter_trial_settings', $this->post_pay_counter_options_menu, 'side' );
            remove_meta_box( 'post_pay_counter_update_countings', $this->post_pay_counter_options_menu, 'normal' );
            
        } else {
            
            if( isset( $_GET['userid'] ) AND is_numeric( $_GET['userid'] ) ) {
                
                if( ! get_userdata( (int) $_GET['userid'] ) ) {
                    echo '<strong>The requested user does not exist.</strong>';
                    return;
                }
                
                $this->edit_options_counter_settings = $this->post_pay_counter_functions->get_settings( (int) $_GET['userid'], TRUE );
                
                //If current page is a new userid special settings, take general settings but change the userid
                if( $this->edit_options_counter_settings->userID == 'general' )
                    $this->edit_options_counter_settings->userID = $_GET['userid'];
                                
            } else {
                $this->edit_options_counter_settings = $this->post_pay_counter_functions->general_settings;
            }
        } 
        
        //Nonces for major security
        wp_nonce_field( 'post_pay_counter_main_form_update' );
        wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
        wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
        
            <div id="poststuff" class="metabox-holder has-right-sidebar">
                <div id="side-info-column" class="inner-sidebar">
            	<?php do_meta_boxes( 'settings_page_post_pay_counter_options', 'side', null ); ?>
                </div>
                <div id="post-body" class="has-sidebar">
                    <div id="post-body-content" class="has-sidebar-content">
            	<?php do_meta_boxes( 'settings_page_post_pay_counter_options', 'normal', null ); ?>
                    </div>
                </div>
            </div>
            <div class="clear"></div>
            <input type="hidden" name="userID" value="<?php echo $this->edit_options_counter_settings->userID ?>" />
            <input type="submit" class="button-primary" name="post_pay_counter_options_save" value="<?php _e( 'Save options' ) ?>" />
        </form>
        </div>
    <?php }
    
    //Function to update the counting payment on post_save
    function post_pay_counter_update_post_counting( $new_status, $old_status, $post ) {
        global $wpdb;
        
        //Call update counter value function
        $this->post_pay_counter_functions->update_single_counting( $post->ID, $new_status );
    }
    
    //Showing stats
    function post_pay_counter_show_stats() {
        global $wpdb,
               $current_user;
        
        //Merging _GET and _POST data due to the time range form avaiable in the stats page header. 
        //I don't know whether the user is choosing the time frame from the form (via POST data) or if they arrived to a page where I linked them (via GET data)
        $get_and_post = array_merge( $_GET, $_POST );
        
        //Validate time range values (start and end), if set. They must be isset, numeric and positive. 
        //If something's wrong, start time is the first day of the current month and end time is today
        if( ( isset( $get_and_post['tstart'] ) AND ( ! is_numeric( $get_and_post['tstart'] ) OR  $get_and_post['tstart'] < 0 ) )
        OR ( isset( $get_and_post['tend'] ) AND ( ! is_numeric( $get_and_post['tend'] ) OR  $get_and_post['tend'] < 0 ) )
        OR ( ! isset( $get_and_post['tend'] ) OR ! isset( $get_and_post['tend'] ) ) ) {
            //If user has selected a time range, convert it into unix timestamp
            if( strtotime( @$get_and_post['tstart'] ) AND strtotime( @$get_and_post['tend'] ) ) {
                $get_and_post['tstart'] = strtotime( $get_and_post['tstart'].' 00:00:01' );
                $get_and_post['tend']   = strtotime( $get_and_post['tend'].' 23:59:59' );
            } else {
                $get_and_post['tstart'] = mktime( 0, 0, 1, date( 'm' ), 1, date( 'Y' ) );
                $get_and_post['tend']   = mktime( 23, 59, 59, date( 'm' ), date( 'd' ), date( 'Y' ) );
            }
        }
        
        $current_user_settings  = $this->post_pay_counter_functions->get_settings( $current_user->ID, TRUE );
        $alternate              = '';
        
        //CSV file exporting feature
        if( isset( $get_and_post['export'] ) AND $get_and_post['export'] == 'csv' AND ( $current_user->user_level >= 7 OR $current_user_settings->can_csv_export == 1 ) ) {
            $this->post_pay_counter_functions->csv_export( @$get_and_post['author'], @$get_and_post['tstart'], @$get_and_post['tend'] );
        } ?>
        
        <div class="wrap">
            <h2>Post Pay Counter Stats</h2>
        
        <?php if( isset( $get_and_post['author'] ) AND is_numeric( $get_and_post['author'] ) AND get_userdata( $get_and_post['author'] ) ) {
                
                //Generate stats for the requested month (which could also be teh current one) and asked author. Then show the header part
                $generated_stats    = $this->post_pay_counter_functions->generate_stats( $get_and_post['author'], $get_and_post['tstart'], $get_and_post['tend'] );
                $user_nickname      = get_userdata( $get_and_post['author'] )->nickname;
                
                $this->post_pay_counter_functions->show_stats_page_header( $user_nickname, admin_url( 'options-general.php?page=post_pay_counter_show_stats&amp;author='.$get_and_post['author'].'&amp;tstart='.$get_and_post['tstart'].'&amp;tend='.$get_and_post['tend'] ), $get_and_post['tstart'], $get_and_post['tend'] ); ?>
                
                <?php //If the returned value is a string, it means we had an error, and we show it
                if( is_string( $generated_stats ) ) {
                    echo $generated_stats;
                
                } else { ?>
                    <table class="widefat fixed">
                        <thead>
                    		<tr>
                    			<th scope="col" width="52%">Post title</th>
                                <th scope="col" width="8%">Status</th>
                                <th scope="col" width="8%">Date</th>
                    			
                                <?php //Display the right column name depending on the set counting type
                                if( $current_user_settings->counting_type_words == 1 ) { ?>
                                <th scope="col" width="7%">Words</th>
                                <?php } else { ?>
                                <th scope="col" width="7%">Visits</th>    
                                <?php } ?>
                                
                    			<th scope="col" width="11%">Comments</th>
                                <th scope="col" width="8%">Images</th>
                    			<th scope="col" width="9%">Payment</th>
                    		</tr>
                    	</thead>
                    	<tfoot>
                    		<tr>
                    			<th scope="col" width="54%">Post title</th>
                                <th scope="col" width="8%">Status</th>
                                <th scope="col" width="8%">Date</th>
                    			
                                <?php //Display the right column name depending on the set counting type
                                if( $current_user_settings->counting_type_words == 1 ) { ?>
                                <th scope="col" width="7%">Words</th>
                                <?php } else { ?>
                                <th scope="col" width="7%">Visits</th>    
                                <?php } ?>
                                
                    			<th scope="col" width="11%">Comments</th>
                                <th scope="col" width="8%">Images</th>
                    			<th scope="col" width="9%">Payment</th>
                    		</tr>
                    	</tfoot>
                        <tbody>
                    
                    <?php $n = 0; 
                    foreach( $generated_stats['general_stats'] as $single ) {
                        
                        //If there's a bonus associated with current post, attach it
                        $payment_bonus = 0;
                        if( $single['payment_bonus'] != 0 )
                            $payment_bonus = '<br /><span style="font-size: smaller;">(> '.$single['payment_bonus'].')</span>';
                        else
                            unset( $payment_bonus );
                        
                        //Wrap post title if too long
                        if( strlen( $single['post_title'] ) > 85 )
                            $title_to_show = substr( $single['post_title'], 0, 85 ).'...';
                        else
                            $title_to_show = $single['post_title']; 
                        
                        //Class alternate adding
                        if( $n % 2 == 1 )
                            $alternate = ' class="alternate"';
                        
                        //If payment value is 0, make the row opacity lighter
                        if( $single['post_payment'] == 0 ) { ?>
                        <tr style="opacity: 0.60;"<?php echo $alternate ?>>
                        <?php } else { ?>
                            <tr<?php echo $alternate ?>>
                        <?php } ?>
                        
                        <td><a href="<?php echo get_permalink( $single['ID'] ); ?>" title="<?php echo $single['post_title']; ?>"><?php echo $title_to_show; ?></a></td>
                        <td><?php echo $single['post_status']; ?></td>
                        <td><?php echo $single['post_date']; ?></td>
                        <td><?php echo $single['words_count']; ?></td>
                        <td><?php echo $single['comment_count']; ?></td>
                        <td><?php echo $single['image_count']; ?></td>
                        <td>&euro; <?php printf( '%.2f', $single['post_payment'] ); echo @$payment_bonus; ?></td>
                    </tr>
                        
                    <?php $n++;
                    } ?>
                
                    </tbody>
                </table>
                
                <br />
                <br />
                
                <table class="widefat fixed">
                    <tr>
                        <td width="40%">Total displayed posts:</td>
                        <td align="left" width="10%"><?php echo @(int) $generated_stats['overall_stats']['total_posts'] ?></td>
                        <td width="35%">Total displayed payment:</td>
                        <td align="left" width="15%">&euro; <?php printf( '%.2f', @$generated_stats['overall_stats']['total_payment'] ); echo @$generated_stats['overall_stats']['payment_bonus']; ?></td>
                    </tr>
                <?php //Show the other rows only if using zones as counting system 
                if( $current_user_settings->counting_system_zones == 1 ) { ?>
                    <tr class="alternate">
            			<td width="40%">N&deg; of posts below the first zone (<<?php echo $this->post_pay_counter_functions->general_settings->zone1_count ?> words):</td>
            			<td align="left" width="10%"><?php echo @(int) $generated_stats['overall_stats']['0zone'] ?></td>
                        <td width="40%">N&deg; of posts in the first zone (<?php echo $this->post_pay_counter_functions->general_settings->zone1_count.'-'.$this->post_pay_counter_functions->general_settings->zone2_count ?> words):</td>
            			<td align="left" width="10%"><?php echo @(int) $generated_stats['overall_stats']['1zone'] ?></td>
            		</tr>
                    <tr>
                        <td width="40%">N&deg; of posts in the second zone (<?php echo $this->post_pay_counter_functions->general_settings->zone2_count.'-'.$this->post_pay_counter_functions->general_settings->zone3_count ?> words):</td>
            			<td align="left" width="10%"><?php echo @(int) $generated_stats['overall_stats']['2zone'] ?></td>
            			<td width="40%">N&deg; of posts in the third zone (<?php echo $this->post_pay_counter_functions->general_settings->zone3_count.'-'.$this->post_pay_counter_functions->general_settings->zone4_count ?> words):</td>
            			<td align="left" width="10%"><?php echo @(int) $generated_stats['overall_stats']['3zone'] ?></td>
            		</tr>
                    <tr class="alternate">
                        <td width="40%">N&deg; of posts in the fourth zone (<?php echo $this->post_pay_counter_functions->general_settings->zone4_count.'-'.$this->post_pay_counter_functions->general_settings->zone5_count ?> words):</td>
            			<td align="left" width="10%"><?php echo @(int) $generated_stats['overall_stats']['4zone'] ?></td>
            			<td width="40%">N&deg; of posts in the fifth zone (<?php echo $this->post_pay_counter_functions->general_settings->zone5_count ?>+ words):</td>
            			<td align="left" width="10%"><?php echo @(int) $generated_stats['overall_stats']['5zone'] ?></td>
                    </tr>
                    
                    <?php }
                    //Check if current user is allowed to csv export, using the noheader parameter to allow csv download
                    if( $current_user_settings->can_csv_export == 1 OR $current_user->user_level >= 7 ) { ?>
                    <tr>
                        <td colspan="4" align="center"><a href="<?php echo wp_nonce_url( admin_url( 'options-general.php?page=post_pay_counter_show_stats&amp;author='.$get_and_post['author'].'&amp;tstart='.$get_and_post['tstart'].'&amp;tend='.$get_and_post['tend'].'&amp;export=csv&amp;noheader=true' ), 'post_pay_counter_csv_export_author' ) ?>" title="Export to csv">Export stats to csv</a></td>
                    </tr>
                    <?php } ?>
    
                </table>
                <?php }
        
        //Here we have general stats instead, without any author selection
        } else {
                
                //Generate stats for the requested month (which could also be the current one), but no author. Then show the header part
                $generated_stats = $this->post_pay_counter_functions->generate_stats( false, $get_and_post['tstart'], $get_and_post['tend'] ); 
                $this->post_pay_counter_functions->show_stats_page_header( 'General', admin_url( 'options-general.php?page=post_pay_counter_show_stats&amp;tstart='.$get_and_post['tstart'].'&amp;tend='.$get_and_post['tend'] ), $get_and_post['tstart'], $get_and_post['tend'] );
                        
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
    
                    $author_display_name    = get_userdata( $key )->nickname;
                    $author_paypal_address  = @$this->post_pay_counter_functions->get_settings( $key )->paypal_address;
                    
                    //If there's a bonus associated with current post, attach it
                    $payment_bonus = 0;
                    if( $value['payment_bonus'] != 0 )
                        $payment_bonus = ' <span style="font-size: smaller;">(> '.$value['payment_bonus'].')</span>';
                    else
                        unset( $payment_bonus );
                    
                    //Class alternate adding
                    if( $n % 2 == 1 )
                        $alternate = ' class="alternate"';
                        
                    //If payment value is 0, make the row opacity lighter
                    if( $value['payment'] == 0 ) { ?>
                <tr style="opacity: 0.60;"<?php echo $alternate ?>>
                    <?php } else { ?>
                <tr<?php echo $alternate ?>>
                    <?php }
                    
                    //If current user can't see detailed stats, user's names aren't links but its one
                    if( $current_user_settings->can_view_others_detailed_stats == 0 AND $current_user->user_level < 7 AND $key != $current_user->ID ) { ?>
                    <td><?php echo $author_display_name ?></td>
                    <?php } else { ?>
                    <td><a href="<?php echo admin_url( 'options-general.php?page=post_pay_counter_show_stats&amp;author='.$key.'&amp;tstart='.$get_and_post['tstart'].'&amp;tend='.$get_and_post['tend'] ) ?>" title="<?php echo $author_display_name ?>"><?php echo $author_display_name ?></a></td>
                    <?php } ?>
                    
                    <td><?php echo $value['posts'] ?></td>
                    <td>&euro; <?php printf( '%.2f', $value['payment'] ); echo @$payment_bonus; ?></td>
                        
                         <?php if( $current_user->user_level >= 7 ) { ?>
                    <td><?php echo $author_paypal_address ?></td>
                         <?php } ?>
                    
                </tr>
                <?php $n++;
                } ?>
                
        </tbody>
    </table>
    
    <br />
    <br />
    
    <table class="widefat fixed">
		<tr>
			<td width="40%">Total displayed posts:</td>
			<td align="left" width="10%"><?php echo @(int) $generated_stats['overall_stats']['total_posts'] ?></td>
			<td width="35%">Total displayed payment:</td>
			<td align="left" width="15%">&euro; <?php printf( '%.2f', @$generated_stats['overall_stats']['total_payment'] ); echo @$generated_stats['overall_stats']['payment_bonus']; ?></td>
		</tr>
        <?php //Show the other rows only if using zones counting system 
        if( $current_user_settings->counting_system_zones == 1 ) { ?>
        <tr class="alternate">
			<td width="40%">N&deg; of posts below the first zone (<<?php echo $this->post_pay_counter_functions->general_settings->zone1_count ?> words):</td>
			<td align="left" width="10%"><?php echo @(int) $generated_stats['overall_stats']['0zone'] ?></td>
            <td width="40%">N&deg; of posts in the first zone (<?php echo $this->post_pay_counter_functions->general_settings->zone1_count.'-'.$this->post_pay_counter_functions->general_settings->zone2_count ?> words):</td>
			<td align="left" width="10%"><?php echo @(int) $generated_stats['overall_stats']['1zone'] ?></td>
		</tr>
        <tr>
            <td width="40%">N&deg; of posts in the second zone (<?php echo $this->post_pay_counter_functions->general_settings->zone2_count.'-'.$this->post_pay_counter_functions->general_settings->zone3_count ?> words):</td>
			<td align="left" width="10%"><?php echo @(int) $generated_stats['overall_stats']['2zone'] ?></td>
			<td width="40%">N&deg; of posts in the third zone (<?php echo $this->post_pay_counter_functions->general_settings->zone3_count.'-'.$this->post_pay_counter_functions->general_settings->zone4_count ?> words):</td>
			<td align="left" width="10%"><?php echo @(int) $generated_stats['overall_stats']['3zone'] ?></td>
		</tr>
        <tr class="alternate">
            <td width="40%">N&deg; of posts in the fourth zone (<?php echo $this->post_pay_counter_functions->general_settings->zone4_count.'-'.$this->post_pay_counter_functions->general_settings->zone5_count ?> words):</td>
			<td align="left" width="10%"><?php echo @(int) $generated_stats['overall_stats']['4zone'] ?></td>
			<td width="40%">N&deg; of posts in the fifth zone (<?php echo $this->post_pay_counter_functions->general_settings->zone5_count ?>+ words):</td>
			<td align="left" width="10%"><?php echo @(int) $generated_stats['overall_stats']['5zone'] ?></td>
        </tr>
               
               <?php }
                //Check if current user is allowed to csv export, if so show the link
                if( $current_user_settings->can_csv_export == 1 OR $current_user->user_level >= 7 ) { ?>
        <tr>
            <td colspan="4" align="center"><a href="<?php echo wp_nonce_url( admin_url( 'options-general.php?page=post_pay_counter_show_stats&amp;tstart='.$get_and_post['tstart'].'&amp;tend='.$get_and_post['tend'].'&amp;export=csv&amp;noheader=true' ), 'post_pay_counter_csv_export_general' ) ?>" title="Export to csv">Export stats to csv</a></td>
        </tr>
                <?php } ?>
    </table>
            <?php }
		}
        
        //Showing overall stats, since blog started ( if current user is allowed to )
        if( $current_user_settings->can_view_overall_stats == 1 OR $current_user->user_level >= 7 )
            $this->post_pay_counter_functions->generate_overall_stats();
        
    }
}

new post_pay_counter_core();

?>