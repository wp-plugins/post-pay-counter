<?php

/**
 * @author Stefano Ottolenghi
 * @copyright 2013
 */

require_once( 'ppc_options_fields_class.php' );

class PPC_meta_boxes {
    
    /**
     * Displays the metabox "PRO features" in the Options page (only if not pro version already) 
     *
     * @access  public
     * @since   2.0
    */
    
    static function meta_box_pro_features() { 
        $pro_features = array(
            __( 'Google Analytics' , 'post-pay-counter') => __( 'use your account on the world-leading website visits tracking system to pay writers also basing on how many times their posts were seen.' , 'post-pay-counter'),
            __( 'PayPal' , 'post-pay-counter') => __( 'pay your writers without leaving your website and benefit from a handy and detailed payment history. Let the plugin keep track of how much each writer should be paid basing on past payments.' , 'post-pay-counter'),
            __( 'Valid forever' , 'post-pay-counter') => __( 'buy a license, and it will be yours forever - no renewal or whatever! And you still get updates!' , 'post-pay-counter')
        );
        
        printf( '<p>'.__( 'There are so many things you are missing by not running the PRO version of the Post Pay Counter! Remember that PRO features are always %1$sone click away%2$s!' , 'post-pay-counter'), '<a target="_blank" href="http://www.thecrowned.org/post-pay-counter-pro" title="Post Pay Counter PRO">', '</a>' ).':</p>';
        echo '<ul style="margin: 0 0 15px 2em;">';
        foreach( $pro_features as $key => $single ) {
            echo '<li style="list-style-type: square;"><strong>'.$key.'</strong>: '.$single.'</li>';
        }
        echo '</ul>';
    }
    
    /**
     * Displays the metabox "Support the author" in the Options page (only if not pro version already) 
     *
     * @access  public
     * @since   2.0
    */
    
    static function meta_box_support_the_fucking_author() {
        global $ppc_global_settings;
        
        echo '<p>'.__( 'If you like the Post Pay Counter, there are a couple of crucial things you can do to support its development' , 'post-pay-counter').':</p>';
        echo '<ul style="margin: 0 0 15px 2em; padding: 0">';
        echo '<li style="list-style-image: url(\''.$ppc_global_settings['folder_path'].'style/images/pro.png\');"><a target="_blank" href="http://www.thecrowned.org/post-pay-counter-pro" title="'.__( 'Go PRO' , 'post-pay-counter').'"><strong>'.__( 'Go PRO' , 'post-pay-counter').'</strong></a>. '.__( 'Try the PRO version: more functions, more stuff!' , 'post-pay-counter').'</li>';
        echo '<li style="list-style-image: url(\''.$ppc_global_settings['folder_path'].'style/images/paypal.png\');"><a target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=SM5Q9BVU4RT22" title="'.__( 'Donate money' , 'post-pay-counter').'"><strong>'.__( 'Donate money' , 'post-pay-counter').'</strong></a>. '.__( 'Plugins do not write themselves: they need time, effort, brainstorming and troubleshooting, and I give all of that free of charge. Donations of every amount are absolutely welcome.' , 'post-pay-counter').'</li>';
        echo '<li style="list-style-image: url(\''.$ppc_global_settings['folder_path'].'style/images/amazon.png\');">'.sprintf( __( 'Give me something from my %1$sAmazon Wishlist%2$s.' , 'post-pay-counter'), '<a target="_blank" href="http://www.amazon.it/registry/wishlist/1JWAS1MWTLROQ" title="Amazon Wishlist">', '</a>' ).'</li>';
        echo '<li style="list-style-image: url(\''.$ppc_global_settings['folder_path'].'style/images/star.png\');">'.sprintf( __( 'Rate it in the %1$sWordpress Directory%3$s and share the %2$sofficial page%3$s.' , 'post-pay-counter'), '<a target="_blank" href="http://wordpress.org/extend/plugins/post-pay-counter/" title="Wordpress directory">', '<a target="_blank" href="http://www.thecrowned.org/post-pay-counter" title="Official plugin page">', '</a>' ).'</li>';
        echo '<li style="list-style-image: url(\''.$ppc_global_settings['folder_path'].'style/images/write.png\');">'.__( 'Have a blog or write on some website? Write about the plugin and email me the review!' , 'post-pay-counter').'</li>';
        echo '</ul>';
    }
    
    /**
     * Displays the metabox "Miscellanea" in the Options page  
     *
     * @access  public
     * @since   2.0
    */
    
    static function meta_box_misc_settings( $post, $current_settings ) {
        global $wp_roles, $ppc_global_settings;
        $current_settings = $current_settings['args'];
        
        echo '<form id="ppc_misc_settings" method="post">';
        
        //Post types to be included in countings
        echo '<p>'.__( 'Choose the post types you would like to be included in countings. There are some you may have never seen: they are probably Wordpress built-in ones.', 'post-pay-counter').'</p>';
        
        $all_post_types = get_post_types();
        $allowed_post_types = $current_settings['counting_allowed_post_types'];
        
        foreach ( $all_post_types as $single ) {
            $checked = '';
            
            if( in_array( $single, $allowed_post_types ) ) {
                $checked = 'checked="checked"';
            }
                
            echo '<p style="height: 11px;">';
            echo '<label for="post_type_'.$single.'">';
            echo '<input type="checkbox" name="post_type_'.$single.'" id="post_type_'.$single.'" value="'.$single.'" '.$checked.' /> '.ucfirst( $single );
            echo '</label>';
            echo '</p>';
        }
        do_action( 'ppc_misc_settings_after_allowed_post_types', $current_settings );
        
        //User roles to be included in countings
        echo '<p>'.__( 'Choose the user roles whose posts you would like to be included in countings.', 'post-pay-counter').'</p>';
        
        foreach( $wp_roles->role_names as $key => $value ) {
            $checked = '';
            
            if( in_array( $key, $current_settings['counting_allowed_user_roles'] ) ) {
                $checked = 'checked="checked"';
            }
            
            echo '<p style="height: 11px;">';
            echo '<label for="user_role_'.$key.'">';
            echo '<input type="checkbox" name="user_role_'.$key.'" id="user_role_'.$key.'" value="'.$key.'" '.$checked.' /> '.$value;
            echo '</label>';
            echo '</p>';
        }
        do_action( 'ppc_misc_settings_after_allowed_user_roles', $current_settings );
        
        //Plugin options page access permissions
        echo '<p style="margin-top: 20px;">'.__( 'Plugin Options can be viewed and edited by following user roles' , 'post-pay-counter').'</p>';
        foreach( $wp_roles->role_names as $key => $value ) {
            if( in_array( $key, $current_settings['can_see_options_user_roles'] ) )
                $checked = ' checked="checked"';
            
            echo '<p style="height: 11px;"><label><input type="checkbox" name="can_see_options_user_roles_'.$key.'" value="'.$key.'"'.@$checked.'> '.$value.'</label></p>';
            unset( $checked );
        }
        do_action( 'ppc_misc_settings_after_options_allowed_user_roles', $current_settings );
        
        //Plugin stats page access permissions
        echo '<p style="margin-top: 20px;">'.__( 'Plugin Stats page can be viewed by following user roles' , 'post-pay-counter').'</p>';
        foreach( $wp_roles->role_names as $key => $value ) {
            $checked = '';
            if( in_array( $key, $current_settings['can_see_stats_user_roles'] ) ) {
                $checked = ' checked="checked"';
            }
            
            echo '<p style="height: 11px;"><label><input type="checkbox" name="can_see_stats_user_roles_'.$key.'" value="'.$key.'"'.$checked.'> '.$value.'</label></p>';
        }
        do_action( 'ppc_misc_settings_after_stats_allowed_user_roles', $current_settings );
        
        //Default stats time range
        echo '<p style="margin-top: 20px;">'.__( 'These allow you to define the default stats time range. When you open up the stats page, the time range here selected will be shown (although you will be able to change it). More information in the tooltips.' , 'post-pay-counter').'</p>';
        echo PPC_HTML_functions::echo_p_field( 'Current week', $current_settings['default_stats_time_range_week'], 'radio', 'default_stats_time_range', __( 'With this, the plugin will display in the stats all the published posts from the beginning of the week to the current day (week starts on Monday). This will be the default settings: you will still be able to change the time range the way you want it. You should select this if you usually pay your writers weekly.' , 'post-pay-counter'), 'default_stats_time_range_week', 'default_stats_time_range_week' );
        echo PPC_HTML_functions::echo_p_field( 'Current month', $current_settings['default_stats_time_range_month'], 'radio', 'default_stats_time_range', __( 'With this, the plugin will display in the stats all the published posts from the beginning of the month to the current day. This will be the default settings: you will still be able to change the time range the way you want it. You should select this if you usually pay your writers monthly.' , 'post-pay-counter'), 'default_stats_time_range_month', 'default_stats_time_range_month' );
        echo PPC_HTML_functions::echo_p_field( 'This custom number of days', $current_settings['default_stats_time_range_custom'], 'radio', 'default_stats_time_range', __( 'With this, you can manually customize the rime range for the published posts the plugin will display in the stats. This will be the default settings: you will still be able to change the time range the way you want it. So, for example, if you set this to 365 days, in the stats page it will automatically be selected a time frame that goes from the current day to the previous 365 days' , 'post-pay-counter'), 'default_stats_time_range_custom', 'default_stats_time_range_custom' );
        echo '<div id="default_stats_time_range_custom_content" class="section">';
        echo PPC_HTML_functions::echo_text_field( 'default_stats_time_range_custom_value', $current_settings['default_stats_time_range_custom_value'], __( 'The desired time range (days)' , 'post-pay-counter') );
        echo '</div>';
        do_action( 'ppc_misc_settings_after_default_time_range', $current_settings );
        ?>
        
        <div class="ppc_save_success" id="ppc_misc_settings_success"><?php _e( 'Settings were successfully updated.' , 'post-pay-counter'); ?></div>
        <div class="ppc_save_error" id="ppc_misc_settings_error"></div>
        <div class="save_settings">
            <img src="<?php echo $ppc_global_settings['folder_path'].'style/images/ajax-loader.gif'; ?>" title="<?php _e( 'Loading' , 'post-pay-counter'); ?>" alt="<?php _e( 'Loading' , 'post-pay-counter'); ?>" class="ajax_loader" id="ppc_misc_settings_ajax_loader" />
            <input type="hidden" name="userid" value="<?php echo $current_settings['userid']; ?>" />
            <input type="submit" class="button-primary" name="ppc_save_misc_settings" id="ppc_save_misc_settings" value="<?php _e( 'Save options' , 'post-pay-counter') ?>" />
        </div>
        <div class="clear"></div>
        </form>
    <?php }
    
    /**
     * Displays the metabox "Counting settings" in the Options page  
     *
     * @access  public
     * @since   2.0
     * @param   object WP post object
     * @param   array plugin settings
    */
    
    static function meta_box_counting_settings( $post, $current_settings ) {
        global $wp_roles, $ppc_global_settings;
        $current_settings = $current_settings['args'];
        
        echo '<p>'.__( 'Here you can define the criteria which post payments will be computed with.' , 'post-pay-counter').'</p>';
        echo '<form action="" id="ppc_counting_settings" method="post">';
        
        //Basic payment
        echo '<div class="section">';
        echo '<div class="title">'.__( 'Basic payment' , 'post-pay-counter').'</div>';
        echo '<div class="main">';
        echo PPC_HTML_functions::echo_p_field( __( 'Basic, assured payment' , 'post-pay-counter'), $current_settings['basic_payment'], 'checkbox', 'basic_payment', __( 'You may define a starting value for post payment. This means that each post will earn at least this amount, to which all the other credits will be added. In this way you can be sure that no post will be paid less than a certain amount, but that only valuable posts will make it to higher points.' , 'post-pay-counter') );
        echo '</div>';
        echo '<div class="content">';
        echo PPC_HTML_functions::echo_text_field( 'basic_payment_value', $current_settings['basic_payment_value'], __( 'Basic payment fixed value' , 'post-pay-counter') );
        echo '</div>';
        echo '</div>';
        do_action( 'ppc_counting_settings_after_basic_payment', $current_settings );
        
        //Words payment
        echo '<div class="section">';
        echo '<div class="title">'.__( 'Payment on word counting' , 'post-pay-counter').'</div>';
        echo '<div class="main">';
        echo PPC_HTML_functions::echo_p_field( __( 'Words contribute to payment computation' , 'post-pay-counter'), $current_settings['counting_words'], 'checkbox', 'counting_words', __( 'You may define a post value basing on the number of words that make it up as well. The longer a post is, the more time is supposed to have taken the author to write it, the more it should be paid. You will be able to choose how much each word is worth.' , 'post-pay-counter') );
        echo '</div>';
        echo '<div class="content">';
        echo '<div class="title">'.__( 'Counting system' , 'post-pay-counter').'</div>';
        echo PPC_options_fields::echo_payment_systems( 'words', array( 'counting_words_system_zonal' => $current_settings['counting_words_system_zonal'], 'counting_words_system_zonal_value' => $current_settings['counting_words_system_zonal_value'], 'counting_words_system_incremental' => $current_settings['counting_words_system_incremental'], 'counting_words_system_incremental_value' => $current_settings['counting_words_system_incremental_value'] ) );
        echo '<div class="title">'.__( 'Counting options' , 'post-pay-counter').'</div>';
        echo PPC_HTML_functions::echo_text_field( 'counting_words_threshold_max', $current_settings['counting_words_threshold_max'], __( 'Stop counting words after word # (0 = infinite)' , 'post-pay-counter') );
        echo '</div>';
        echo '</div>';
        do_action( 'ppc_counting_settings_after_words_payment', $current_settings );
        
        //Visits payment
        echo '<div class="section">';
        echo '<div class="title">'.__( 'Payment on visit counting' , 'post-pay-counter').'</div>';
        echo '<div class="main">';
        echo PPC_HTML_functions::echo_p_field( __( 'Visits contribute to payment computation' , 'post-pay-counter'), $current_settings['counting_visits'], 'checkbox', 'counting_visits', __( 'You may define a post value basing on the number of visits that it registers as well. The more people see a post, the more interesting the post is supposed to be, the more it should be paid. You will be able to choose how much each visit is worth.' , 'post-pay-counter') );
        echo '</div>';
        echo '<div class="content">';
        echo '<div class="title">'.__( 'Counting method' , 'post-pay-counter').'</div>';
        echo PPC_HTML_functions::echo_p_field( __( 'I have my own visit counter' , 'post-pay-counter'), $current_settings['counting_visits_postmeta'], 'radio', 'counting_visits_method', sprintf( __( 'If you already have some plugin counting visits for you, and you know the %1$s name it stores them into, you can use those data to compute payments. Activate this setting and put the %1$s in the field below.' , 'post-pay-counter'), '<em>postmeta</em>' ), 'counting_visits_postmeta', 'counting_visits_postmeta' );
        echo '<div id="counting_visits_postmeta_content" class="field_value">';
        echo PPC_HTML_functions::echo_text_field( 'counting_visits_postmeta_value', $current_settings['counting_visits_postmeta_value'], __( 'The postmeta holding the visits' , 'post-pay-counter') );
        echo '</div>';
        do_action( 'ppc_counting_settings_after_visits_counting_method', $current_settings );
        echo '<div class="title">'.__( 'Counting system' , 'post-pay-counter').'</div>';
        echo PPC_options_fields::echo_payment_systems( 'visits', array( 'counting_visits_system_zonal' => $current_settings['counting_visits_system_zonal'], 'counting_visits_system_zonal_value' => $current_settings['counting_visits_system_zonal_value'], 'counting_visits_system_incremental' => $current_settings['counting_visits_system_incremental'], 'counting_visits_system_incremental_value' => $current_settings['counting_visits_system_incremental_value'] ) );
        echo '<div class="title">'.__( 'Counting options' , 'post-pay-counter').'</div>';
        echo PPC_HTML_functions::echo_text_field( 'counting_visits_threshold_max', $current_settings['counting_visits_threshold_max'], __( 'Stop counting visits after visit # (0 = infinite)' , 'post-pay-counter') );
        echo '</div>';
        echo '</div>';
        do_action( 'ppc_counting_settings_after_visits_payment', $current_settings );
        
        //Images payment
        echo '<div class="section">';
        echo '<div class="title">'.__( 'Payment on images counting' , 'post-pay-counter').'</div>';
        echo '<div class="main">';
        echo PPC_HTML_functions::echo_p_field( __( 'Images contribute to payment computation' , 'post-pay-counter'), $current_settings['counting_images'], 'checkbox', 'counting_images', sprintf( __( 'You may define a post value basing on the number of images it contains. Maybe more images make a post cleaerer to the readers, and should thus be paid something more. You will be able to choose: when you want the image counting to come in, meaning how many images are free of charge and after which one they should be paid; how much each image is worth; how many images at maximum should be paid (0 = no maximum, infinite). E.g. we have a post with 5 images, and the fields below are set like this: %s. The image payment would be 1.0 bacause image #3 and image #4 are counted.' , 'post-pay-counter'), '<em>2; 0.5; 4</em>' ) );
        echo '</div>';
        echo '<div class="content">';
        echo '<div class="title">'.__( 'Counting system' , 'post-pay-counter').'</div>';
        echo PPC_options_fields::echo_payment_systems( 'images', array( 'counting_images_system_zonal' => $current_settings['counting_images_system_zonal'], 'counting_images_system_zonal_value' => $current_settings['counting_images_system_zonal_value'], 'counting_images_system_incremental' => $current_settings['counting_images_system_incremental'], 'counting_images_system_incremental_value' => $current_settings['counting_images_system_incremental_value'] ) );
        echo '<div class="title">'.__( 'Counting options' , 'post-pay-counter').'</div>';
        echo PPC_HTML_functions::echo_text_field( 'counting_images_threshold_min', $current_settings['counting_images_threshold_min'], __( 'Start paying per image after image #' , 'post-pay-counter') );
        echo PPC_HTML_functions::echo_text_field( 'counting_images_value', $current_settings['counting_images_value'], 'Payment for each image' );
        echo PPC_HTML_functions::echo_text_field( 'counting_images_threshold_max', $current_settings['counting_images_threshold_max'], __( 'Stop paying per image after image #' , 'post-pay-counter') );
        echo PPC_HTML_functions::echo_p_field( 'Include featured image in counting', $current_settings['counting_images_include_featured'], 'checkbox', 'counting_images_include_featured', __( 'Determines whether the featured image will be included in image counting.' , 'post-pay-counter') );
        echo '</div>';
        echo '</div>';
        do_action( 'ppc_counting_settings_after_images_payment', $current_settings );
        
        //Comments payment
        echo '<div class="section">';
        echo '<div class="title">'.__( 'Payment on comments counting' , 'post-pay-counter').'</div>';
        echo '<div class="main">';
        echo PPC_HTML_functions::echo_p_field( __( 'Comments contribute to payment computation' , 'post-pay-counter'), $current_settings['counting_comments'], 'checkbox', 'counting_comments', sprintf( __( 'You may define a post value basing on the number of comments it receives. You will be able to choose: when you want the comment counting to come in, meaning how many comments are free of charge and after which one they should be paid; how much each comment is worth; how many comments at maximum should be paid (0 = no maximum, infinite). E.g. we have a post with 30 images, and the fields below are set like this: %s. The comment payment would be 2.5 bacause comments from #11 included to #25 included are counted.' , 'post-pay-counter'), '<em>10; 0.1; 25</em>' ) );
        echo '</div>';
        echo '<div class="content">';
        echo '<div class="title">'.__( 'Counting system' , 'post-pay-counter').'</div>';
        echo PPC_options_fields::echo_payment_systems( 'comments', array( 'counting_comments_system_zonal' => $current_settings['counting_comments_system_zonal'], 'counting_comments_system_zonal_value' => $current_settings['counting_comments_system_zonal_value'], 'counting_comments_system_incremental' => $current_settings['counting_comments_system_incremental'], 'counting_comments_system_incremental_value' => $current_settings['counting_comments_system_incremental_value'] ) );
        echo '<div class="title">'.__( 'Counting options' , 'post-pay-counter').'</div>';
        echo PPC_HTML_functions::echo_text_field( 'counting_comments_threshold_min', $current_settings['counting_comments_threshold_min'], __( 'Start paying per comment after comment #' , 'post-pay-counter') );
        echo PPC_HTML_functions::echo_text_field( 'counting_comments_value', $current_settings['counting_comments_value'], __( 'Payment for each comment' , 'post-pay-counter') );
        echo PPC_HTML_functions::echo_text_field( 'counting_comments_threshold_max', $current_settings['counting_comments_threshold_max'], __( 'Stop paying per comment after comment #' , 'post-pay-counter') );
        echo '</div>';
        echo '</div>';
        do_action( 'ppc_counting_settings_after_comments_payment', $current_settings );
        
        //Total payment
        echo '<div class="section">';
        echo '<div class="title">'.__( 'Total payment' , 'post-pay-counter').'</div>';
        echo '<div class="main">';
        echo PPC_HTML_functions::echo_text_field( 'counting_payment_total_threshold', $current_settings['counting_payment_total_threshold'], __( 'Set payment maximum (0 = infinite)' , 'post-pay-counter') );
        echo PPC_HTML_functions::echo_p_field( __( 'Pay only when the total payment threshold is reached' , 'post-pay-counter'), $current_settings['counting_payment_only_when_total_threshold'], 'checkbox', 'counting_payment_only_when_total_threshold', __( 'Check this if you want to pay items only when they reach the max payment threshold. Other items will appear grayed out.' , 'post-pay-counter'), 'counting_payment_only_when_total_threshold', 'counting_payment_only_when_total_threshold' );
        echo '</div>';
        echo '</div>';
        do_action( 'ppc_counting_settings_after_total_payment', $current_settings );
        
        //Misc
        echo '<div class="section">';
        echo '<div class="title">'.__( 'Miscellanea counting settings' , 'post-pay-counter').'</div>';
        echo '<div class="main">';
        echo PPC_HTML_functions::echo_p_field( 'Count pending revision posts', $current_settings['counting_allowed_post_statuses']['pending'], 'checkbox', 'counting_count_pending_revision_posts', __( 'While published posts are automatically counted, you can decide to include pending revision ones or not.' , 'post-pay-counter') );
        echo PPC_HTML_functions::echo_p_field( 'Count future scheduled posts', $current_settings['counting_allowed_post_statuses']['future'], 'checkbox', 'counting_count_future_scheduled_posts', __( 'While published posts are automatically counted, you can decide to include future planned ones or not.' , 'post-pay-counter') );
        echo PPC_HTML_functions::echo_p_field( 'Exclude quoted content from word counting', $current_settings['counting_exclude_quotations'], 'checkbox', 'counting_exclude_quotations', __( 'If checked all the words contained into a <em>blockquote</em> tag will not be taken into account when counting. Use this to prevent interviews and such stuff to be counted as normal words.' , 'post-pay-counter') );
        echo '</div>';
        echo '</div>';
        do_action( 'ppc_counting_settings_after_misc', $current_settings );
        
        echo '<div class="ppc_save_success" id="ppc_counting_settings_success">'.__( 'Settings were successfully updated.' , 'post-pay-counter').'</div>';
        echo '<div class="ppc_save_error" id="ppc_counting_settings_error"></div>';
        echo '<div class="save_settings">';
        echo '<img src="'.$ppc_global_settings['folder_path'].'style/images/ajax-loader.gif'.'" title="'.__( 'Loading' , 'post-pay-counter').'" alt="'.__( 'Loading' , 'post-pay-counter').'" class="ajax_loader" id="ppc_counting_settings_ajax_loader" />';
        echo '<input type="hidden" name="userid" value="'.$current_settings['userid'].'" />';
        echo '<input type="submit" class="button-primary" name="ppc_save_counting_settings" id="ppc_save_counting_settings" value="'.__( 'Save options' , 'post-pay-counter').'" />';
        echo '</div>';
        echo '<div class="clear"></div>';
        echo '</form>';
   }
    
    /**
     * Displays the metabox "Permissions" in the Options page  
     *
     * @access  public
     * @since   2.0
     * @param   object WP post object
     * @param   array plugin settings
    */
    
    static function meta_box_permissions( $post, $current_settings ) {
        global $ppc_global_settings;
        $current_settings = $current_settings['args'];
        
        echo '<form action="" id="ppc_permissions" method="post">';
        echo '<p>'.__( 'Just a few fields to help you preventing users from seeing things they should not see. Administrators are subject to the same permissions; if you wish they did not, personalize their user settings.' , 'post-pay-counter').'</p>';
        echo PPC_HTML_functions::echo_p_field( __( 'Users can see other users\' general stats' , 'post-pay-counter'), $current_settings['can_see_others_general_stats'], 'checkbox', 'can_see_others_general_stats', __( 'If unchecked, users will only be able to see their stats in the general page. Other users\' names, posts and pay counts will not be displayed.' , 'post-pay-counter') );
        echo PPC_HTML_functions::echo_p_field( __( 'Users can see other users\' detailed stats' , 'post-pay-counter'), $current_settings['can_see_others_detailed_stats'], 'checkbox', 'can_see_others_detailed_stats', __( 'If unchecked, users will not be able to see other users\' detailed stats (ie. written posts details) but still able to see general ones. ' , 'post-pay-counter') );
        echo PPC_HTML_functions::echo_p_field( __( 'Let users know if other users have personalized settings' , 'post-pay-counter'), $current_settings['can_see_countings_special_settings'], 'checkbox', 'can_see_countings_special_settings', __( 'If you personalize settings by user, keep this in mind. If unchecked, users will not see personalized settings in countings, they will believe everybody is still using general settings. Anyway, the selected posts author will see them.' , 'post-pay-counter') );
        do_action( 'ppc_permissions_settings_after_default', $current_settings );
        ?>
        
        <div class="ppc_save_success" id="ppc_permissions_success"><?php _e( 'Settings were successfully updated.' , 'post-pay-counter'); ?></div>
        <div class="ppc_save_error" id="ppc_permissions_error"></div>
        <div class="save_settings">
            <img src="<?php echo $ppc_global_settings['folder_path'].'style/images/ajax-loader.gif'; ?>" title="<?php _e( 'Loading' , 'post-pay-counter'); ?>" alt="<?php _e( 'Loading' , 'post-pay-counter'); ?>" class="ajax_loader" id="ppc_permissions_ajax_loader" />
            <input type="hidden" name="userid" value="<?php echo $current_settings['userid']; ?>" />
            <input type="submit" class="button-primary" name="ppc_save_permissions" id="ppc_save_permissions" value="<?php _e( 'Save options' , 'post-pay-counter') ?>" />
        </div>
        <div class="clear"></div>
        </form>
    <?php }
    
    /**
     * Displays the metabox "Personalize settings" in the Options page  
     *
     * @access  public
     * @since   2.0
     * @param   object WP post object
     * @param   array plugin settings
    */
    
    function meta_box_personalize_settings( $post, $current_settings ) {
        global $wpdb, $ppc_global_settings, $wp_roles;
        $current_settings = $current_settings['args'];
        
        $already_personalized = new WP_User_Query( array( 
            'meta_key' => $wpdb->prefix.$ppc_global_settings['option_name'],
            'meta_value' => '',
            'meta_compare' => '!=',
            'count_total' => true,
            'fields' => array( 
                'ID', 
                'display_name' 
            )
        ) );
        
        if( $already_personalized->total_users > 0 ) {
            echo '<p>'.__( 'The following users have different settings, click to edit them.' , 'post-pay-counter').'</p>';
            echo '<div>';
            
            $n = 0; 
            foreach( $already_personalized->results as $single ) {
                if( $n % 2 == 0 ) {
                    echo '<span style="float: left; width: 50%;">';
                } else {
                    echo '<span style="float: right; width: 50%;">';
                }
                
                echo '<a href="'.admin_url( $ppc_global_settings['options_menu_link'].'&amp;userid='.$single->ID ).'" title="'.__( 'View and edit special settings for user' , 'post-pay-counter').' \''.htmlspecialchars( $single->display_name ).'\'">'.$single->display_name.'</a>
                </span>';
                
                $n++;
            }
            
            echo '<div class="clear"></div>';
            echo '</div>';
            
        } else {
            echo '<p>'.__( 'No users have different settings. Learn how to personalize settings from the section below.' , 'post-pay-counter').'</p>';
        }
        
        echo '<p><strong>'.__( 'Personalize single user settings' , 'post-pay-counter').'</strong><br />';
        echo __( 'Some people\'s posts are better than somebody others\'? You can adjust settings for each user, so that they will have different permissions and their posts will be payed differently.' , 'post-pay-counter').'</p>';
        echo '<p>'.__( 'First, select a user role. You will see all users from that role: clicking on one you will be headed to the settings page for that specific user.' , 'post-pay-counter').'</p>';
        echo '<div id="ppc_personalize_user_roles">';
        echo '<p><strong>'.__( 'User roles' , 'post-pay-counter').'</strong><br />';
        
        $n = 0;
        foreach( $wp_roles->roles as $role ) {
            if( $n % 2 == 0 ) {
                echo '<span style="float: left; width: 50%;">';
            } else {
                echo '<span style="float: right; width: 50%;">';
            }
            echo '<a href="" title="'.$role['name'].'" id="'.$role['name'].'" class="ppc_personalize_roles">'.$role['name'].'</a>';
            echo '</span>';
            
            $n++;
        }
        
        echo '</p>';
        echo '<div class="clear"></div>';
        echo '</div>';
        echo '<div style="height: 8em; overflow: auto; display: none;" id="ppc_personalize_users">';
        echo '<p><strong>'.__( 'Available users' , 'post-pay-counter').'</strong><br />';
        echo '<span id="ppc_users"></span>';
        echo '</p>';
        echo '</div>';
        echo '<div class="save_settings">';
        echo '<img src="'.$ppc_global_settings['folder_path'].'style/images/ajax-loader.gif'.'" title="'.__( 'Loading' , 'post-pay-counter').'" alt="'.__( 'Loading' , 'post-pay-counter').'" class="ajax_loader" id="ppc_personalize_settings_ajax_loader" />';
        echo '</div>';
        echo '<div class="clear"></div>';
    }
}
?>