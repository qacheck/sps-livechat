var ajax_livechat_create = null;
var chat = false;
function sps_livechat_status() {
	jQuery.ajax({
		url: slc.ajaxurl+'?action=livechat_status&token='+slc.token,
		success: function(res) {
			//console.log(res);
			if(res.length==0) {
				jQuery('#chat-area').hide();
				jQuery('#name-area').html('');
				jQuery('#chat-area').html('');
				jQuery('#btn-end').attr('disabled', "disabled");
				jQuery('#input-name-area').show();
				jQuery('#send-message-area').hide();
			} else {
				jQuery('#chat-area').show();
				jQuery('#name-area').html('Tên của bạn: '+res.post.post_title);
				jQuery('#name-area').data('pid',res.post.ID);
				jQuery('#name-area').data('auth',res.post.post_excerpt);
				jQuery('#name-area').show();
				jQuery('#input-name-area').hide();
				jQuery('#btn-end').removeAttr('disabled');
				jQuery('#send-message-area').show();
				var messages = '';
				if(res.messages.length>0) {
					jQuery.each(res.messages, function(index, value) {
						if(value.auth==0) {
							messages += '<p><b>'+res.post.post_title+' </b><i>('+value.time+')</i><br>'+value.message+'</p>';
						} else {
							messages += '<p><b>'+res.post.post_excerpt+' </b><i>('+value.time+')</i><br>'+value.message+'</p>';
						}
					});
				} else {
					messages = '<i>Chào '+res.post.post_title+', Chúng tôi có thể giúp gì cho bạn?</i>';
				}
				jQuery('#chat-area').html(messages);
				if(chat) {
					jQuery('#chat-area').animate({
						scrollTop: jQuery('#chat-area')[0].scrollHeight
					},
					0);
					chat = false;
				}
			}

			setTimeout(sps_livechat_status, 1500);
		}
	});
}

function sps_livechat_create(name) {
	if(ajax_livechat_create!==null) {
		ajax_livechat_create.abort();
	}
	ajax_livechat_create = jQuery.post(slc.ajaxurl+'?action=livechat_create', {token: slc.token, name: name}, function(res){
		if(res<=0) {
			alert('Tên không hợp lệ, vui lòng nhập tên khác!');
		}
	});
}

function sps_livechat_send(text) {
	var pid = jQuery('#name-area').data('pid');
	jQuery.post(slc.ajaxurl+'?action=livechat_update', {token:slc.token, pid: pid, text: text}, function(res){
		//console.log(res);
		chat = true;
	});
}

function sps_livechat_end() {
	var pid = jQuery('#name-area').data('pid');
	jQuery.post(slc.ajaxurl+'?action=livechat_end', {token:slc.token, pid: pid}, function(res){
		//console.log(res);
	});
}

function getCaret(el) { 
    if (el.selectionStart) { 
        return el.selectionStart; 
    } else if (document.selection) { 
        el.focus();
        var r = document.selection.createRange(); 
        if (r == null) { 
            return 0;
        }
        var re = el.createTextRange(), rc = re.duplicate();
        re.moveToBookmark(r.getBookmark());
        rc.setEndPoint('EndToStart', re);
        return rc.text.length;
    }  
    return 0; 
}

jQuery(function($){

	$('#btn-send').attr('disabled', "disabled");
	$('#name-area').hide();
	$('#input-name-area').hide();

	sps_livechat_status();

	$('#input-name-submit').on('click', function(e) {
		var name = $('#input-name').val();
		if(name!='') {
			sps_livechat_create(name);
		}
	});

	$('#sendie').on('keyup', function(event) {
		event.preventDefault();
		if (event.keyCode == 13) {
	        var content = this.value;  
	        var caret = getCaret(this);          
	        if(event.shiftKey){
	            this.value = content.substring(0, caret - 1) + "\n" + content.substring(caret, content.length);
	            event.stopPropagation();
	        } else {
	        	var text = content.substring(0, caret - 1) + content.substring(caret, content.length);
	            this.value = '';
	            sps_livechat_send(text);
	        }
	    }
	});

	$('#btn-end').on('click', function(e) {
		sps_livechat_end();
	});
});