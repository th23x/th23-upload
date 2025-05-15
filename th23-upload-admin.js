jQuery(document).ready(function($){

	// Media Library: Add / remove watermark for an attachment
	let locked = [];
	$('a.th23-upload-admin-watermark').click(function(e) {

		e.preventDefault();
		e.stopPropagation();
		$(this).blur();

		const attachment_id = $(this).attr('data-attachment');

		// avoid multiple clicks before one execution is finished
		if(locked.indexOf(attachment_id) !== -1) {
			return;
		}
		locked.push(attachment_id);

		// keep attachment actions visible during execution
		const row_actions = $(this).closest('.row-actions');
		row_actions.css({ 'position': 'initial' });

		// show waiting text
		$(this).html('<span class="blinking">' + $(this).attr('data-wait') + '</span>');

		const data = {
			action: 'th23_upload_watermark',
			nonce: $(this).attr('data-nonce'),
			id: attachment_id,
			do: $(this).attr('data-do'),
		};
		// make $(this) accessible upon response
		const item = $(this);
		$.post(ajaxurl, data, function(r) {
			if(r.result == 'success') {
				// show success message
				item.html('<span class="success">' + r.msg + '</span>');
				// 3 seconds after success, reset attachment actions visibility, change action link, unlock to be used again
				setTimeout(function(){
					row_actions.css({ 'position': '' });
					item.attr('data-do', r.do);
					item.attr('data-wait', r.wait);
					item.html(r.html);
					const pos = locked.indexOf(attachment_id);
					if(pos !== -1) locked.splice(pos, 1);
				}, 3000);
			}
			else {
				// show error
				item.html('<span class="error"><span class="blinking">' + r.msg + '</span></span>');
			}
		});

	});

	// Plugin Settings: Handle watermark image selection

	// click on watermark image label in options page - open image selection, not highlight (hidden) input field
	$('label[for="input_watermarks_image"]').click(function(e) {
		e.preventDefault();
		$('#th23-upload-watermark-image').click();
	});

	// toggle show / hide watermark image selection
	$('#th23-upload-watermark-image').click(function() {
		$('#th23-upload-watermark-selection').toggleClass('hidden');
		if(!$('#th23-upload-watermark-selection').hasClass('hidden')) {
			$('html, body').animate({ scrollTop: ($('#th23-upload-watermark-image').offset().top - 50) }, 300);
		}
	});

	// select watermark image from list - note: on-click ensures dynamically generated elements (uploaded watermarks) get the event
	$('#th23-upload-watermark-selection').on('click', 'img', function(e){
		$('#th23-upload-watermark-image img').remove();
		$('#th23-upload-watermark-image').append('<img src="' + $(this).attr('src') + '" />');
		$('#input_watermarks_image').val($(this).attr('data-file'));
		$('#th23-upload-watermark-placeholder, #th23-upload-watermark-unavailable, #th23-upload-watermark-selection').addClass('hidden');
	});

	// add watermark image to list and select uploaded image
	$('#th23-upload-watermark-file').on('change', function(e) {
		// note: use FormData to properly handle file upload
		let form_data = new FormData();
		form_data.append('action', 'th23_upload_watermark');
		form_data.append('nonce', $('#th23-upload-watermark-nonce').val());
		form_data.append('file', $(this).prop('files')[0]);
		form_data.append('do', 'upload');
		$.ajax({
			url: ajaxurl,
			type: 'post',
			contentType: false,
			processData: false,
			data: form_data,
			success: function(r) {
				if(r.result == 'success') {
					// make newly uploaded the selected watermark
					$('#th23-upload-watermark-image img').remove();
					$('#th23-upload-watermark-image').append('<img src="' + r.item_url + '" />');
					$('#input_watermarks_image').val(r.item);
					$('#th23-upload-watermark-placeholder, #th23-upload-watermark-unavailable, #th23-upload-watermark-selection').addClass('hidden');
					// remove previous watermark with same filename from selection list
					if(r.replace) {
						$('#th23-upload-watermark-selection img[data-file="' + r.item + '"]').closest('.th23-upload-watermark-item').remove();
					}
					// add newly uploaded watermark to the beginning of the selection list / after upload entry
					$('#th23-upload-watermark-selection .upload-button').after(r.html);
				}
				else {
					const parent = $('#th23-upload-watermark-selection .upload-button').first();
					$('.message', parent).remove();
					parent.append('<div class="message error">' + r.msg + '</div>');
				}
			},
		});
		// clear file upload input field
		$('#th23-upload-watermark-file').val('');
	});

	// delete watermark image
	// note: on-click ensures dynamically generated elements (uploaded watermarks) get the event
	$('#th23-upload-watermark-selection').on('click', '.delete', function(e){
		e.stopPropagation();
		// make file accessible upon response
		const file = $(this).attr('data-file');
		const data = {
			action: 'th23_upload_watermark',
			nonce: $('#th23-upload-watermark-nonce').val(),
			file: file,
			do: 'delete',
		};
		// make $(this) accessible upon response
		const item = $(this);
		$.post(ajaxurl, data, function(r) {
			const parent = item.closest('.th23-upload-watermark-item');
			if(r.result == 'success') {
				parent.remove();
				// unset selected, in case this was just deleted
				if($('#input_watermarks_image').val() == file) {
					$('#th23-upload-watermark-image img').remove();
					$('#th23-upload-watermark-placeholder').removeClass('hidden');
					$('#input_watermarks_image').val('');
				}
			}
			else {
				$('.message', parent).remove();
				parent.append('<div class="message error">' + r.msg + '</div>');
			}
		});
	});

	// Plugin Settings: Mass actions for add/remove watermark to all attachments
	let attachments, len, action, nonce, i, stop;
	// trigger ajax call
	function trigger_ajax() {
		if(i < len && stop != 1) {
			// call ajax
			const data = {
				action: 'th23_upload_watermark',
				nonce: nonce,
				id: attachments[i],
				do: action,
			};
			$.post(ajaxurl, data, function(r) {
				// update progress bar
				$('#th23-upload-mass-bar > div').css({ 'width': ((i + 1) / len * 100) + '%' });
				// add attachment to done list
				$('#th23-upload-mass-last').prepend('<div>' + r.item + '<div><span class="' + r.result + '">' + r.msg + '</span></div></div>');
				i++;
				trigger_ajax();
			});
		}
		else {
			// nothing more to do, hide "stop" / unhide "close"
			$('#th23-upload-mass-stop').addClass('hidden');
			$('#th23-upload-mass-close').removeClass('hidden');
		}
	}
	// confirm checkbox
	$('#th23-upload-mass-confirm').on('change', function() {
		if($('#th23-upload-mass-confirm').is(':checked')) {
			$('.th23-upload-mass-confirm').removeClass('error');
		}
	});
	// start mass action
	$('#th23-upload-mass-buttons input[type=button]').click(function() {

		$(this).blur();

		// check for confirmation
		$('.th23-upload-mass-confirm label').removeClass('blinking temp');
		if(!$('#th23-upload-mass-confirm').is(':checked')) {
			$('.th23-upload-mass-confirm').addClass('error');
			// note: assess .width() first to trigger animation restart properly upon subsequent clicks
			$('.th23-upload-mass-confirm label').width();
			$('.th23-upload-mass-confirm label').addClass('blinking temp');
			return;
		}
		$('#th23-upload-mass-confirm').prop('checked', false);

		// get attachment ids
		attachments = $('#th23-upload-attachments').val().split(',');
		len = attachments.length;
		if(len > 0) {
			// start mass action
			$('#th23-upload-mass-trigger').addClass('hidden');
			$('#th23-upload-mass-progress, #th23-upload-mass-stop').removeClass('hidden');
			action = $(this).attr('data-action');
			nonce = $(this).attr('data-nonce');
			i = 0;
			stop = 0;
			trigger_ajax();
		}

	});
	// stop button
	$('#th23-upload-mass-stop').click(function() {
		$(this).addClass('hidden');
		stop = 1;
	});
	// close button
	$('#th23-upload-mass-close').click(function() {
		$('#th23-upload-mass-trigger').removeClass('hidden');
		$('#th23-upload-mass-progress, #th23-upload-mass-close').addClass('hidden');
		$('#th23-upload-mass-bar > div').css({ 'width': '1%' });
		$('#th23-upload-mass-last').html('');
	});

});
