var chat = false;
function sps_livechat_admin_status() {
	var token = jQuery('#sendie').data('token');
	jQuery.ajax({
		url: slc.ajaxurl+'?action=livechat_admin_status&post='+slc.post.ID+'&token='+token,
		success: function(res) {
			console.log(res);
			if(res.post.post_status=='pending') {
				jQuery('#sendie').removeAttr('disabled');
				jQuery('#btn-end').removeAttr('disabled');
			} else {
				jQuery('#sendie').attr('disabled', "disabled");
				jQuery('#btn-end').attr('disabled', "disabled");
			}
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
				messages = '<i>Messages area</i>';
			}
			jQuery('#chat-area').html(messages);

			if(chat) {
				jQuery('#chat-area').animate({
					scrollTop: jQuery('#chat-area')[0].scrollHeight
				},
				0);
				chat = false;
			}

			setTimeout(sps_livechat_admin_status, 1500);
		}
	});
}

function sps_livechat_admin_send(text) {
	var pid = jQuery('#sendie').data('pid');
	var token = jQuery('#sendie').data('token');
	jQuery.post(slc.ajaxurl+'?action=livechat_update_admin', {token: token, pid: pid, text: text}, function(res){
		//console.log(res);
		chat = true;
	});
}

function sps_livechat_admin_end() {
	var pid = jQuery('#sendie').data('pid');
	var token = jQuery('#sendie').data('token');
	jQuery.post(slc.ajaxurl+'?action=livechat_admin_end', {token: token, pid: pid}, function(res){
		
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

	sps_livechat_admin_status();

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
	            sps_livechat_admin_send(text);
	        }
	    }
	});

	$('#btn-end').on('click', function(e) {
		sps_livechat_admin_end();
	});


});