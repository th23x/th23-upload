jQuery(document).ready(function($){

	// handle dismissable notices
	$('.th23-notice.is-dismissible').each(function() {
		var notice = $(this);
		$('button.notice-dismiss', this).click(function() {
			notice.fadeTo( 100, 0, function() { notice.slideUp( 100, function() { notice.remove(); }); });
		});
	});

	// handle changes of screen options
	$('#th23-admin-screen-options input').change(function() {
		var data = {
			action: 'th23_admin_screen_options',
			plugin: $('#th23-admin-screen-options-plugin').val(),
			nonce: $('#th23-admin-screen-options-nonce').val(),
		};
		// add screen option fields to data dynamically
		$('#th23-admin-screen-options input').each(function() {
			if($(this).attr('type') == 'checkbox') {
				var value = $(this).is(':checked');
			}
			else {
				var value = $(this).val();
			}
			if(typeof $(this).attr('name') != 'undefined') {
				data[$(this).attr('name')] = value;
			}
		});
		// saving user preference
		$.post(ajaxurl, data, function() {});
		// change live classes
		var classBase = $(this).attr('data-class');
		var classAdd = '';
		if($(this).attr('type') == 'checkbox') {
			if($(this).is(':checked')) {
				classAdd = classBase;
			}
		}
		else {
			classAdd = classBase + '-' + $(this).val().split(' ').join('_');
		}
		$("#th23-admin-options").removeClass(function(index, className) {
			var regex = new RegExp('(^|\\s)' + classBase + '.*?(\\s|$)', 'g');
			return (className.match(regex) || []).join(' ');
		}).addClass(classAdd);
	});

	// handle show/hide of children options (up to 2 child levels deep)
	$('#th23-admin-options input[data-childs]').change(function() {
		if($(this).is(':checked')) {
			// loop through childs as selectors, for all that contain inputs with data-childs attribute, show this childs, if parent input is checked - and finally show ourselves as well
			$($(this).attr('data-childs')).each(function() {
				if($('input[data-childs]', this).is(':checked')) {
					$($('input[data-childs]', this).attr('data-childs')).show();
				}
			}).show();
		}
		else {
			// loop through childs as selectors, for all that contain inputs with data-childs attribute, hide this childs - and finally ourselves as well
			$($(this).attr('data-childs')).each(function() {
				$($('input[data-childs]', this).attr('data-childs')).hide();
			}).hide();
		}
	});

	// remove any "disabled" attributes from settings before submitting - to fetch/ perserve values
	$('.th23-admin-options-submit').click(function() {
		$('#th23-admin-options input[name="th23-admin-options-do"]').val('submit');
		$('#th23-admin-options :input').removeProp('disabled');
		$('#th23-admin-options').submit();
	});

	// handle option template functionality - adding/ removing user defined lines
	$('#th23-admin-options button[id^=template-add-]').click(function() {
		var option = $(this).val();
		// create "random" id based on microtime
		var id = 'm' + Date.now();
		// clone from template row, change ids and insert above invisible template row
		var row = $('#' + option + '-template').clone(true, true).attr('id', option + '-' + id);
		$('input', row).each(function(){
			$(this).attr('id', $(this).attr('id').replace('_template', '_' + id));
			$(this).attr('name', $(this).attr('name').replace('_template', '_' + id));
		});
		$('#template-remove-' + option + '-template', row).attr('id', 'template-remove-' + option + '-' + id).attr('data-element', id);
		$('#' + option + '-template').before(row);
		// add element to elements field
		var elements = $('#input_' + option + '_elements');
		elements.val(elements.val() + ',' + id);
	});
	$('#th23-admin-options button[id^=template-remove-]').click(function() {
		var option = $(this).val();
		var id = $(this).attr('data-element');
		// remove row
		$('#' + option + '-' + id).remove();
		// remove element from elements field
		var elements = $('#input_' + option + '_elements');
		elements.val(elements.val().replace(',' + id, ''));
	});

	// toggle show / hide eg for longer descriptions
	// usage: <a href="" class="toggle-switch">switch</a><span class="toggle-show-hide" style="display: none;">show / hide</span>
	$('#th23-admin-options .toggle-switch').click(function(e) {
		$(this).blur().next('.toggle-show-hide').toggle();
		e.preventDefault();
	});

	// shared x-plugin
	$('#th23-admin-options input ~ span.shared').click(function() {
		$('#' + $(this).attr('data-target')).val($(this).attr('data-shared'));
	});

	// suggested pre-fill
	// usage: <a href="" class="copy" data-target="input_OPTION_SLUG" data-copy="SUGGESTED VALUE">click here</a>
	$('#th23-admin-options a.copy').click(function(e) {
		e.preventDefault();
		$('#' + $(this).attr('data-target')).val($(this).attr('data-copy'));
		$(this).blur();
	});

});
