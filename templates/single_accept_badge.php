<?php
/**
 * Template Name: Accept Badge
 *
 */

get_header(); // Loads the header.php template. 
//Getting the filename and the id from the url
        if(isset( $_GET['filename']) && ($_GET['id']) ){
            $path_json = $_GET['filename']; //Gets the encoded Json Path
            $path_json = base64_decode(str_rot13($path_json)); //Decodes the path
            $badge_name=$_GET['id'];
            $json_content = file_get_contents($path_json); //Get the Json content from the Json path
            $json_obj = json_decode($json_content); //Decode the Json content (get all the information into $json_obj)
            b4l_save_badge_user_profil($json_obj);
        }
        ?>
        <!-- Issuer API script (OpenBadges backpack) -->
        <script src="https://backpack.openbadges.org/issuer.js"></script>
        <script type="text/javascript">
            //Function for issuing the badge
            jQuery(document).ready(function($) {
                $('.js-required').hide();
                //Checks the navigator because the Issuer API isn't supported on MSIE Browsers
                if (/MSIE (\d+\.\d+);/.test(navigator.userAgent)){  
                    $('.acceptclick').hide();
                    $('.browserSupport').show(); //Writes an error message
                }else{
                    $('.browserSupport').hide();
                }
                $('#badge-error').hide();
                $('.acceptclick').click(function() {		
                    var assertionUrl = '<?php echo $path_json; ?>';
                    OpenBadges.issue([''+assertionUrl+''], function(errors, successes) {
                        if (errors.length > 0 ) {
                            $('#badge-error').show(); //Displays an error message
                            $.ajax({
                                url: '<?php get_post_type_archive_link( 'badge' ); ?>',
                                type: 'POST',
                                data: { 
                                    action:'award_action'
                                }
                            });
                        }

                        if (successes.length > 0) {
                            $('.acceptclick').hide();
                            $('#badge-error').hide();
                            $.ajax({
                                url: '<?php get_post_type_archive_link( 'badge' ); ?>',
                                type: 'POST',
                                data: { 
                                    action:'award_action'
                                }
                            });
                        }	
                    });    
                });
            });
       </script>
   
        
        <div id="bsp-award-actions-wrap">
            <div id="badgeSuccess">
                <h2>Congratulations! The "<?php echo $badge_name; ?>" badge has been awarded to you.</h2>
		        <!-- Just registered users can get the badge -->
				
                    <?php
                    if ( !is_user_logged_in() )  : // Message for non logged in users.?>
                        <h2>Remember, just registered users can accept the badge. <a title="Log in" style="color: #f78181;" href="<?php echo wp_login_url(); ?>">Log in</a> or <a title="Register" style="color: #f78181;" href="<?php echo wp_registration_url(); ?>">open an account</a>.</h2>
                    <?php endif; ?>
                        
                    <?php 
                    if ( is_user_logged_in() )  :  // Active link for winners of a badge.?>
                        <h2 class="acceptclick">Please <a href='#' class='acceptclick'>accept</a> the award.</h2>
                    <?php endif; ?>
                            
                    <?php
                    if(is_user_logged_in() && $json_obj->{'badge'}->{'classlink'} != 'nolink') : 
                        $link = $json_obj->{'badge'}->{'classlink'}; 
                    ?>
                        <h2>Don't forget to give a rating to the class <a style="color: #f78181;" href='<?php echo $link ?>'>HERE</a>.</h2>
                    <?php endif; ?>
				
            </div>
        </div>
        <div class="browserSupport">
            <p><span style="background-color: #f78181;"><strong>Microsoft Internet Explorer is not supported at this time. Please use Firefox or Chrome to retrieve your award.</strong></span></p>
        </div>
        <div id="badge-error">
            <p><span style="background-color: #f78181;"><strong>An error occured while adding this badge to your backpack.</strong></span></p>
        </div>
        
<?php get_footer(); // Loads the footer.php template.
       
       

/**
 * Save the data from the JSON file which is saved in the Wordpress server to 
 * display them on the user profile.
 * 
 * @author Alexandre LEVACHER
 * @since 1.1.2
 * @param String $json_path Json path
 */
function b4l_save_badge_user_profil($json_obj){
    //Checks the type of badge to know where to save the data
    if($json_obj->{'badge'}->{'typeofbadge'} == 'Student') {
        b4l_insert_data_into_user_role_badges_profil_table($json_obj, 'b4l_userStudentBadgesProfil');
    } 
    if($json_obj->{'badge'}->{'typeofbadge'} == 'Teacher') {
        b4l_insert_data_into_user_role_badges_profil_table($json_obj, 'b4l_userTeacherBadgesProfil');
    }
}


/**
 * Save the data from the JSON into the database
 * 
 * @author Alexandre LEVACHER
 * @since 1.1.2
 * @param Object $json_obj Json object which contains all the data
 * @param String $table_name Database table name in which the data will be saved
 */
function b4l_insert_data_into_user_role_badges_profil_table($json_obj,$table_name){
    global $wpdb;
    global $current_user;
    get_currentuserinfo();
    
    //If this certification is not already on the database table for a user, we save the data
    if(!($wpdb->get_row($wpdb->prepare( "SELECT * FROM ".$wpdb->prefix.$table_name." WHERE user_badge_id = ".$current_user->ID.'_'.$json_obj->{'badge'}->{'level'}.'_'.$json_obj->{'badge'}->{'language'}, "" )))) {
        $wpdb->insert(
            $wpdb->prefix . $table_name,
            array(
                'user_badge_id' => $current_user->ID.'_'.$json_obj->{'badge'}->{'level'}.'_'.$json_obj->{'badge'}->{'language'}.'_'.badge_teacher,
                'user_id' => $current_user->ID,
                'badge_level' => $json_obj->{'badge'}->{'level'},
                'badge_language' => $json_obj->{'badge'}->{'language'},
                'badge_date' => $json_obj->{'issued_on'},
                'badge_image' => $json_obj->{'badge'}->{'image'},
                'badge_teacher' => $json_obj->{'badge'}->{'teacher'},
                'badge_comment' => $json_obj->{'badge'}->{'comment'}
            )
        );
    }
}