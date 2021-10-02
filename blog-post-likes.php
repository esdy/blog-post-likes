<?php
/**
 * Plugin Name: Blog Post Likes
 * Plugin URI: https://dwanjala.com
 * Description: Finally, a plugin to track likes and dislikes of posts without necessarily 
 requiring login! The plugin used the devices MAC address to identify the user reading a 
 particular post. Unlike IP Addresses which tend to change from time to time, the MAC 
 address is constant making it possible to identify post’s and device’s relationship 
 and user interaction with the thumbs up and thumbs down icons.
 * Version: 1.0.0
 * Author: Esdy Wanjala
 * Author URI: https://www.dwanjala.com
 */
define('EWBPL_BLOG_POST_LIKES_PATH',plugin_dir_path(__FILE__));
include(EWBPL_BLOG_POST_LIKES_PATH . 'public/includes/update-likes.php');

add_action( 'the_content', 'ewbpl_blog_post_likes');

//get blog post likes
function ewbpl_blog_post_likes($content)
{
	$post_id = get_post()->ID;
	$user_mac = ewbpl_user_mac_address();
	$isLiked = ewbpl_podt_is_liked($post_id, $user_mac)[0];
	$liked_style = (isset($isLiked->likes) && $isLiked->likes == 1)?'style="color:red"':""; 
	$disliked_style = (isset($isLiked->dislikes) && $isLiked->dislikes == 1)?'style="color:red"':""; 
	$likes = (ewbpl_fetch_likes($post_id)->likes == '')?0:ewbpl_fetch_likes($post_id)->likes;
	$dislikes = (ewbpl_fetch_likes($post_id)->dislikes == '')?0:ewbpl_fetch_likes($post_id)->dislikes;
	$like_icons = '<p><br><i class="icofont-thumbs-up liked-post" id="like-post" value="'.$post_id.'" ' .$liked_style .'></i> <span id="post-likes">' .$likes.'  </span> <i class="icofont-thumbs-down disliked-post" id="dislike-post" value="'.$post_id.'" ' .$disliked_style .'></i><span id="post-dislikes"> ' . $dislikes.'</span></p>';
	if(is_single()){
		return $like_icons . $content;
	}
	
	return $content;
}

//get user MAC Address to help identify unique likes
function ewbpl_user_mac_address()
{
	$macAddress = exec('getmac');
	$macAddress = strtok($macAddress, ' ');
  
	return $macAddress;
}


//fetch likes and dislikes
function ewbpl_fetch_likes($post_id)
{
	global $wpdb;
	$table_name = $wpdb->prefix . 'post_likes';
	$where = "WHERE post_id = $post_id";
	$prepare = $wpdb->prepare("SELECT SUM(likes) AS likes,SUM(dislikes) AS dislikes FROM $table_name $where");
	$query = $wpdb->get_results($prepare)[0];

	return $query;
	
}

//check if already liked
function ewbpl_podt_is_liked($post_id, $user_mac)
{
	global $wpdb;
	$table_name = $wpdb->prefix . 'post_likes';
	$where = "WHERE post_id = $post_id AND user_mac = '$user_mac'";
	$prepare = $wpdb->prepare("SELECT * FROM $table_name $where");
	$query = $wpdb->get_results($prepare);

	return $query;
}
	

//create table on activating plugin
function ewbpl_likes_create_table(){
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	 //Create the post_likes table
	 $table_name = $wpdb->prefix . 'post_likes';
	 $sql = "CREATE TABLE IF NOT EXISTS $table_name (
				 likes_id INTEGER NOT NULL AUTO_INCREMENT,
				 post_id INTEGER NOT NULL,
				 user_mac VARCHAR(32) NOT NULL,
				 likes INTEGER NOT NULL DEFAULT 0,
				 dislikes INTEGER NOT NULL DEFAULT 0,
				 cdate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				 udate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				 PRIMARY KEY (likes_id)
			) $charset_collate;";
	 dbDelta( $sql );
}

register_activation_hook(ABSPATH . 'wp-content/plugins/blog-post-likes/blog-post-likes.php','ew_bpl_likes_create_table' );

//add js and css files
function ewbpl_plugin_enqueue_scripts() {
	wp_enqueue_style(
				'Ico_Font',
				plugins_url('/public/css/icofont.min.css', __FILE__)
	);	
	wp_enqueue_script(
				'ajax-script', 
				plugins_url( '/public/js/blog-post-likes-public.js', __FILE__ ), 
				array('jquery')
	);	
	wp_localize_script(
			'ajax-script', 
			'my_ajax_object',
            array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) 
	);
}

add_action( 'wp_enqueue_scripts', 'ewbpl_plugin_enqueue_scripts');

//update post_likes
function ewbpl_update_likes($post_data){
	global $wpdb;
	$table_name = $wpdb->prefix . 'post_likes';
	$user_mac = ewbpl_user_mac_address();
	$isLiked = ewbpl_podt_is_liked($post_data['post_id'],$user_mac);
	if(empty($isLiked)){
		$column = ($post_data['value'] == 1)?'likes':'dislikes';
		$insert = $wpdb->insert("$table_name",['post_id' =>$post_data['post_id'],"$column" => 1,'user_mac'=>$user_mac]);
		$new_likes = (!empty(ewbpl_fetch_likes($post_data['post_id'])->likes))?ewbpl_fetch_likes($post_data['post_id'])->likes:0;
		$new_dislikes = (!empty(ewbpl_fetch_likes($post_data['post_id'])->dislikes))?ewbpl_fetch_likes($post_data['post_id'])->dislikes:0;
		$liked = (empty($post_data['value']))?0:$post_data['value'];
		echo json_encode(['likes'=> $new_likes, 
		'dislikes'=>$new_dislikes,'liked'=>$liked]);
		die();
	}else if(!empty($isLiked)){
		$column = ($post_data['value'] == 1)?'likes':'dislikes';
		$likes = ($post_data['value'] == 1)?1:0;
		$dislikes = ($post_data['value'] == 0)?1:0;
		$update = $wpdb->update("$table_name",['likes'=>$likes,'dislikes'=>$dislikes],['post_id' =>$post_data['post_id'],'user_mac'=>$user_mac]);
		$new_likes = (!empty(ewbpl_fetch_likes($post_data['post_id'])->likes))?ewbpl_fetch_likes($post_data['post_id'])->likes:0;
		$new_dislikes = (!empty(ewbpl_fetch_likes($post_data['post_id'])->dislikes))?ewbpl_fetch_likes($post_data['post_id'])->dislikes:0;		
		$liked = (empty($post_data['value']))?0:$post_data['value'];
		echo json_encode(['likes'=> $new_likes, 
		'dislikes'=>$new_dislikes,'liked'=>$liked]);
		die();		
	}
}

/* ====  Admin Section === */
 
//add Likes to WP Dashboard
function ewbpl_blog_post_likes_admin($newcolumn){
    $newcolumn['post_likes'] = __('Likes');
    return $newcolumn;
}
 
//populate the likes column
function ewbpl_post_custom_column_likes_admin($column_name, $id){
     
    if($column_name === 'post_likes'){
		$likes = (ewbpl_fetch_likes(get_the_ID())->likes == '')?0:ewbpl_fetch_likes(get_the_ID())->likes;
        echo $likes;
    }
}
//Init the views fucntion
add_filter('manage_posts_columns', 'ewbpl_blog_post_likes_admin');
add_action('manage_posts_custom_column', 'ewbpl_post_custom_column_likes_admin',10,2);

//add Likes to WP Dashboard
function ewbpl_blog_post_dislikes_admin($newcolumn){
    $newcolumn['post_dislikes'] = __('Dislikes');
    return $newcolumn;
}
 
//populate the likes column
function ewbpl_post_custom_column_dislikes_admin($column_name, $id){
     
    if($column_name === 'post_dislikes'){
		$dislikes = (ewbpl_fetch_likes(get_the_ID())->dislikes == '')?0:ewbpl_fetch_likes(get_the_ID())->dislikes;
        echo $dislikes;
    }
}
//Init the views fucntion
add_filter('manage_posts_columns', 'ewbpl_blog_post_dislikes_admin');
add_action('manage_posts_custom_column', 'ewbpl_post_custom_column_dislikes_admin',10,2);