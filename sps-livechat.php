<?php
session_start();
/*
 * Plugin Name: SPS Live Chat
 */
define( 'SLC_PLUGIN', __FILE__ );
// W:\domains\dev2018.loc\wp\wp-content\plugins\sso\sso.php

define( 'SLC_PLUGIN_URL', untrailingslashit( plugins_url( '', SLC_PLUGIN ) ) );
// http://dev2018.loc/wp/wp-content/plugins/sso

define( 'SLC_PLUGIN_BASENAME', plugin_basename( SLC_PLUGIN ) );
// sso/sso.php

define( 'SLC_PLUGIN_NAME', trim( dirname( SLC_PLUGIN_BASENAME ), '/' ) );
// sso

define( 'SLC_PLUGIN_DIR', untrailingslashit( dirname( SLC_PLUGIN ) ) );


function sps_livechat_post_type() {
	$args = array(
		'labels' => array(
			'name' => 'SPS Live Chat',
			'singular_name' => 'SPS Live Chat',
			'menu_name' => 'Live Chat'
		),
		'public' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'rewrite' => false,
		'query_var' => false,
		'has_archive' => false,
		'supports' => array('title')
	);
	register_post_type( 'sps-livechat', $args );
}
add_action( 'init', 'sps_livechat_post_type' );

function sps_livechat_status() {
	$token = isset($_GET['token']) ? $_GET['token'] : '';
	$response = array();
	$messages = array();
	if($token == md5(session_id())) {
		$query = get_posts(array(
			'posts_per_page' => 1,
			'post_type' => 'sps-livechat',
			'post_status' => 'pending',
			'meta_key' => 'token',
			'meta_value' => $token
		));
		//print_r($query);
		if($query) {
			$response['post'] = $query[0];
			$response['messages'] = get_post_meta( $query[0]->ID, 'messages', true );
		}
	}
	wp_send_json( $response );
	die;
}
add_action( 'wp_ajax_livechat_status', 'sps_livechat_status' );
add_action( 'wp_ajax_nopriv_livechat_status', 'sps_livechat_status' );

function sps_livechat_check_exist($name, $token) {
	$query = get_posts(array(
			'nopaging' => 1,
			'post_type' => 'sps-livechat',
			's' => $name,
			'meta_key' => 'token',
			'meta_value' => $token
		));
	if($query) {
		return $query[0]->ID;
	}
	return 0;
}

function sps_livechat_create() {
	$return = 0;
	$token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
	$name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
	$users = get_users( array( 'role'=>'administrator' ) );
	if($token == md5(session_id()) && $name!='') {
		$update = sps_livechat_check_exist($name, $token);
		if($update) {
			$data = array(
				'ID' => $update,
				'post_status' => 'pending'
			);
			$return = wp_update_post($data);
		} else {
			$data = array(
				'post_type'    => 'sps-livechat',
				'post_title'    => $name,
				//'post_name'    => $token,
				'post_status'   => 'pending',
				'post_author'   => $users[0]->ID,
				'post_excerpt' => $users[0]->display_name,
				'meta_input' => array('messages' => array(),'token' => $token),
				'post_date_gmt' => current_time( 'mysql', true )
			);
			$return = wp_insert_post( $data );
		}
	}
	wp_send_json($return);
	die;
}
add_action( 'wp_ajax_livechat_create', 'sps_livechat_create' );
add_action( 'wp_ajax_nopriv_livechat_create', 'sps_livechat_create' );

function sps_livechat_update() {
	$token = isset($_POST['token']) ? $_POST['token'] : '';
	$pid = isset($_POST['pid']) ? $_POST['pid'] : '';
	$text = isset($_POST['text']) ? $_POST['text'] : '';
	if($token == md5(session_id()) && $pid>0 && $text!='') {
		$messages = get_post_meta( $pid, 'messages', true );
		$messages[] = array(
			'auth' => 0,
			'time' => current_time( 'd-m-Y H:i:s' ),
			'message' => nl2br(esc_html($text))
		);
		update_post_meta( $pid, 'messages', $messages );
	}
	die;
}
add_action( 'wp_ajax_livechat_update', 'sps_livechat_update' );
add_action( 'wp_ajax_nopriv_livechat_update', 'sps_livechat_update' );

function sps_livechat_end() {
	$token = isset($_POST['token']) ? $_POST['token'] : '';
	$pid = isset($_POST['pid']) ? $_POST['pid'] : '';
	if($token == md5(session_id()) && $pid>0) {
		wp_publish_post( $pid );
	}
	die;
}
add_action( 'wp_ajax_livechat_end', 'sps_livechat_end' );
add_action( 'wp_ajax_nopriv_livechat_end', 'sps_livechat_end' );

function sps_livechat_chatbox($atts) {
	//$session_id = session_id();
	?>
	<style type="text/css">
		#input-name-area {
			margin-bottom: 1em;
		}
		#input-name {
			margin: 0 15px 0 0;
		}
		#input-name-submit {
			line-height: 22px;
		}
		#chat-area {
			padding: 10px;
			border: #ddd 1px solid;
			background: #eee;
			margin-bottom: 1em;
			max-height: 200px;
			overflow-y: auto;
		}
		#sendie {
			width: 100%;
		}
	</style>
	<div id="chat-page-wrap">
	    <p id="name-area" data-pid="0" data-auth=""></p>
	    <div id="input-name-area"><input type="text" id="input-name" placeholder="Nhập tên của bạn"><button type="button" id="input-name-submit">Chat</button></div>
	    
	    <div id="chat-wrap">
	    	<div id="chat-area"></div>
	    </div>
	    
	    <form id="send-message-area">
	        <textarea id="sendie" maxlength="100" placeholder="Nhập tin nhắn rồi enter"></textarea>
	        <p><button id="btn-end" type="button" disabled="disabled">Thoát chat</button></p>
	    </form>
	</div>
	<?php
}
add_shortcode( 'sps-livechat', 'sps_livechat_chatbox' );

function sps_livechat_scripts() {
	wp_enqueue_script('sps-livechat', SLC_PLUGIN_URL.'/sps-livechat.js', array('jquery'), '', true);
	wp_localize_script( 'sps-livechat', 'slc', array('ajaxurl'=>admin_url('admin-ajax.php'), 'token'=>md5(session_id())) );
}
add_action( 'wp_enqueue_scripts', 'sps_livechat_scripts' );

if(is_admin()) {
	require_once  SLC_PLUGIN_DIR.'/sps-livechat-admin.php';
}