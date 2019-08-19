<?php 
/*
Plugin Name: ALT Lab More Private Posts Options
Plugin URI:  https://github.com/
Description: Choose your privacy levels with greater precision 
Version:     1.0
Author:      ALT Lab
Author URI:  http://altlab.vcu.edu
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: my-toolset

*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

add_action( 'wp_enqueue_scripts', 'acf_security_load_scripts', 10, 2 );

function acf_security_load_scripts() {                             
    wp_enqueue_style( 'acf-security-addon', plugin_dir_url( __FILE__) . 'css/acf-security.css');
}



//filter content for non admins
function super_privacy_content_filter($content) {
  global $post;
  $post_id = $post->ID;
  $source_url = get_permalink($post_id);
  $warning_flag = '';
  $user = wp_get_current_user();
  if (get_acf_privacy_level($post_id)){
	  $allowed_roles = array_map('strtolower', get_acf_privacy_level($post_id));//array_map('strtolower', $yourArray);
	  //var_dump($ability);
	  $ability = 'foo';	  	
	  if (array_intersect($allowed_roles, $user->roles ) && is_user_logged_in()){
	  	  if (current_user_can('editor')){
	  	  	$warning_flag = '<div id="access-flag"><span class="access-title">Notice: </span>The content below is restricted to those with '. $ability .' rights or higher. This message only appears for those who can edit this content. </div>';	  	  
	  	  }
		  return $warning_flag . $content;
		} 
		else if (!array_intersect($allowed_roles, $user->roles ) && is_user_logged_in()) {
			return 'Your access to this content is restricted. You need ' . $ability . ' permission to see this content.';
		} else if (!is_user_logged_in()){
			return 'Please <a href="' . wp_login_url() . '?orgin=' . $source_url .'" title="Login">login</a> to see if you have access to this content.';
		}
	} else {
		return $content;
	}

}
add_filter( 'the_content', 'super_privacy_content_filter' );


function get_acf_privacy_level($post_id){
	$privacy_setting = get_field( "privacy_settings", $post_id );	
	return $privacy_setting;
}


//LOGIN REDIRECT TO ORIGIN PAGE
function acf_security_login_redirect( $redirect_to, $request, $user ) {
    $source_url = $_GET["orgin"];
    if ($source_url != false) {      
            $redirect_to =  $source_url;
            return $redirect_to;
        } else {
        	return $redirect_to;
        }
 }


add_filter( 'login_redirect', 'acf_security_login_redirect', 10, 3 );



//ACF STUFF

add_filter('acf/settings/save_json', 'save_acf_files_here');
 
function save_acf_files_here( $path ) {
    
    // update path
    $path = plugin_dir_path( __FILE__ ) . '/acf-json';
    
    
    // return
    return $path;
    
}

add_filter('acf/settings/load_json', 'my_acf_json_load_point');

function my_acf_json_load_point( $paths ) {
    
    // remove original path (optional)
    unset($paths[0]);
    
    
    // append path
    $paths[] = plugin_dir_path( __FILE__ ) . '/acf-json';
    
    
    // return
    return $paths;
    
}


add_filter('acf/load_field/name=privacy_settings', 'populate_user_levels');


//ADD ALL AVAILABLE USER ROLES AUTOMATICALLY
function populate_user_levels( $field )
{	
	// reset choices
	$field['privacy_settings'] = array();
	
	global $wp_roles;
	//print("<pre>".print_r($wp_roles,true)."</pre>"); 
	$roles = $wp_roles->get_names();
	foreach ($roles as $role) {
		$field['choices'][ $role ] = $role;
	}

	return $field;
}