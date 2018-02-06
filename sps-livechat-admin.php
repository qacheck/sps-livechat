<?php
function sps_livechat_admin_scripts($hook) {
	$screen = get_current_screen();
	global $post;
	//print_r($screen);
	if($screen->base=='post' && $screen->post_type=='sps-livechat') {
		wp_enqueue_script('sps-livechat-admin', SLC_PLUGIN_URL.'/sps-livechat-admin.js', array('jquery'), '', true);
		wp_localize_script( 'sps-livechat-admin', 'slc', array('ajaxurl'=>admin_url('admin-ajax.php'), 'post'=>$post) );
	}
}
add_action( 'admin_enqueue_scripts', 'sps_livechat_admin_scripts' );


function sps_livechat_admin_posts_author($query) {
	global $current_user;
    if (is_admin() && !current_user_can('edit_others_posts')) {
        $query->set('author', $current_user->ID);
    }
}
add_action( 'pre_get_posts', 'sps_livechat_admin_posts_author', 10, 1 );

function sps_livechat_admin_token_column_display( $column, $post_id ) {
    if ($column == 'token'){
        echo esc_html(get_post_meta( $post_id, 'token', true ));
    }
}
add_action( 'manage_sps-livechat_posts_custom_column' , 'sps_livechat_admin_token_column_display', 10, 2 );

function sps_livechat_admin_token_column( $columns ) {
    return array_merge( $columns, 
        array( 'token' => 'Token' ) );
}
add_filter( 'manage_sps-livechat_posts_columns' , 'sps_livechat_admin_token_column' );

function sps_livechat_admin_meta_boxes() {
	add_meta_box( 'sps-livechat-admin', "SPS Live Chat", 'sps_livechat_admin_chatbox_callback', 'sps-livechat' );
}
add_action( 'add_meta_boxes', 'sps_livechat_admin_meta_boxes' );

function sps_livechat_admin_chatbox_callback($post) {
	//print_r($post);
	$token = get_post_meta( $post->ID, 'token', true );
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
	    
	    <div id="chat-wrap">
	    	<div id="chat-area"></div>
	    </div>
	    
	    <form id="send-message-area">
	        <div>Your message: </div>
	        <textarea id="sendie" maxlength="100" data-token="<?=$token?>" data-pid="<?=$post->ID?>" ></textarea>
	        <p><button id="btn-end" type="button" disabled="disabled">Kết thúc</button></p>
	    </form>

	</div>
	<?php
}

function sps_livechat_admin_remove_submitdiv() {
	remove_meta_box( 'submitdiv', 'sps-livechat', 'side' );
}
add_action( 'admin_menu', 'sps_livechat_admin_remove_submitdiv' );

function livechat_admin_status() {
	$token = isset($_GET['token']) ? $_GET['token'] : '';
	$post_id = isset($_GET['post']) ? absint( $_GET['post'] ) : 0;
	$response = array();
	$messages = array();
	if($post_id && $token!='') {
		$data = get_post($post_id);
		if($data) {
			$token_store = get_post_meta( $post_id, 'token', true );
			if($token == $token_store) {
				$response['post'] = $data;
				$messages = get_post_meta( $post_id, 'messages', true );
				$response['messages'] = $messages;
			}
		}
	}
	wp_send_json( $response );
	die;
}
add_action( 'wp_ajax_livechat_admin_status', 'livechat_admin_status' );

function livechat_update_admin() {
	$token = isset($_POST['token']) ? $_POST['token'] : '';
	$pid = isset($_POST['pid']) ? $_POST['pid'] : '';
	$text = isset($_POST['text']) ? $_POST['text'] : '';
	if($token !='' && $pid>0 && $text!='') {
		$token_store = get_post_meta( $pid, 'token', true );
		if($token==$token_store) {
			$messages = get_post_meta( $pid, 'messages', true );
			$messages[] = array(
				'auth' => 1,
				'time' => current_time( 'd-m-Y H:i:s' ),
				'message' => nl2br(esc_html($text))
			);
			update_post_meta( $pid, 'messages', $messages );
		}
	}
	die;
}
add_action( 'wp_ajax_livechat_update_admin', 'livechat_update_admin' );

function livechat_admin_end() {
	$token = isset($_POST['token']) ? $_POST['token'] : '';
	$pid = isset($_POST['pid']) ? $_POST['pid'] : '';
	if($token !='' && $pid>0) {
		$token_store = get_post_meta( $pid, 'token', true );
		if($token==$token_store) {
			wp_publish_post( $pid );
		}
	}
	die;
}
add_action( 'wp_ajax_livechat_admin_end', 'livechat_admin_end' );

function livechat_admin_notify() {
	$query = new WP_Query(array(
		'nopaging' => true,
		'post_type' => 'sps-livechat',
		'post_status' => 'pending'
	));
	$count = 0;
	if($query->have_posts()) {
		$count = $query->post_count;
	}
	wp_send_json( $count );
	die;
}
add_action( 'wp_ajax_livechat_admin_notify', 'livechat_admin_notify' );

function sps_livechat_admin_footer_scripts() {
	?>
	<script type="text/javascript">
		jQuery(function($){
			var sps_livechat_menu = $('#menu-posts-sps-livechat .wp-menu-name');
			sps_livechat_menu.append('<span class="update-plugins" style="display:none;"><span class="plugin-count">1</span></span>');
			function sps_livechat_admin_notify() {
				$.get('<?=admin_url('admin-ajax.php')?>?action=livechat_admin_notify', function(res){
					//console.log(res);
					if(res>0) {
						sps_livechat_menu.find('.update-plugins').show().find('.plugin-count').text(res);
					} else {
						sps_livechat_menu.find('.update-plugins').hide();
					}
					setTimeout(sps_livechat_admin_notify, 2000);
				});
			}
			sps_livechat_admin_notify();
		});
	</script>
	<?php
}
add_action( 'admin_print_footer_scripts', 'sps_livechat_admin_footer_scripts' );