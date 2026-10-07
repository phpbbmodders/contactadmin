// Globals read by phpBB's BBCode editor (editor.js)
var form_name = 'contactadmin';
var text_name = 'contact_admin_info';
var load_draft = false;
var upload = false;
var imageTag = false;

(function($) { // Avoid conflicts with other libraries

	'use strict';

	var $form = $('#contactadmin');
	var settings = $form.data();

	// Show who the chosen contact bot is, or why that user can't be used
	$('#contact_bot_user').change(function() {
		var userId = $(this).val();

		$.ajax({
			url: settings.botInfoUrl.replace(/_bot_info\/[0-9]*/g, '_bot_info/' + userId),
			dataType: 'json',
			success: function(json) {
				var $link = $('#bot_user_link');

				if (json.error === 'CONTACT_NO_BOT_USER') {
					$link.html($('<span class="error"></span>').text(settings.langNoBotUser)).show();
				} else if (json.error === 'CONTACT_BOT_IS_GUEST' || json.error === 'CONTACT_BOT_IS_BOT') {
					var warning = (json.error === 'CONTACT_BOT_IS_GUEST') ? settings.langBotIsGuest : settings.langBotIsBot;
					$link.html(json.user_link).append('<br>', $('<span class="contactadmin-warning"></span>').text(warning)).show();
				} else {
					$link.html(json.user_link).show();
				}
			}
		});
	});

	// Hide the options that don't apply to the selected contact method
	$("input[name='contact_method']").each(function() {
		if ($(this).is(':checked') && this.value == settings.methodEmail) {
			$('.contact-options').hide();
		}
		if ($(this).is(':checked') && this.value == settings.methodPost) {
			$('.contact-who').hide();
		}
	});

	// Show or hide the options when the contact method changes
	$("input[name='contact_method']").change(function() {
		if (this.value == settings.methodEmail || this.value == settings.methodPm) {
			$('.contact-who').show('slow');
			if (this.value == settings.methodEmail) {
				$('.contact-options').hide('slow');
			}
		}
		if (this.value == settings.methodPost || this.value == settings.methodPm) {
			$('.contact-options').show('slow');
			if (this.value == settings.methodPost) {
				$('.contact-who').hide('slow');
			}
		}
	});

})(jQuery);
