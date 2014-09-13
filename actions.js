
// Change app343 with your APP ID
var APP_ID = 'app343';

// Function to post an AJAX request to our app
soundcloud_post = function(obj) {

	$('#soundcloud_form').find('.button').addClass('button_off').attr('disabled', true);
	$('#soundcloud_form').find('.table_clear_ajax').show();

	// POST to our app based on the APP_ID URL
	Core.post(getParam('sJsHome') + 'index.php?do=/' + APP_ID + '/', $(obj).parents('form:first'), function() {
		$('#soundcloud_form').find('.button').removeClass('button_off').attr('disabled', false);
		$('#soundcloud_form').find('.table_clear_ajax').hide();
	});
};

// Load routine when the document is ready
Core.action.soundcloud = function() {

	if ($('.activity_feed_form_share').length && !$('.soundcloud_link').length) {

		// Find SoundCloud menu and add an icon
		$('.menu_' + APP_ID).find('span.icon').css({
			border: '0px',
			background: 'url(\'//da1a3suzxtd3z.cloudfront.net/assets/apps/soundcloud/menu.png\') no-repeat'
		});

		// Add SoundCloud phrase to the activity feed form
		$('.activity_feed_form_attach').append('<li class="soundcloud_link"><a href="#" rel="view_more_link"><div>SoundCloud</div></a></li>');

		// Build the form to add SoundCloud URLs
		var html = '';
		html += '<div class="table_clear"><span class="js_attach_holder"><input type="text" class="global_link_input" placeholder="http://" name="val[soundcloud_url]"></span>' +
			'<ul class="table_clear_button"><li><input type="button" value="Share" class="button" onclick="soundcloud_post(this)" /></li><li class="table_clear_ajax"></li></ul><div class="clear"></div>' +
			'</div><div class="extra_info">Paste the URL to the SoundCloud playlist</div>';
		$('.activity_feed_form_holder').append('<div id="soundcloud_form" class="global_attachment_holder_section" style="display:none;">' + html + '</div>');
	}

	// Click event to show the SoundCloud form we built earlier
	$('.soundcloud_link a').click(function() {

		$('.activity_feed_form_attach a.active').removeClass('active');
		$('.global_attachment_holder_section').hide();
		$('.activity_feed_form_button').hide();
		$('#soundcloud_form').show();
		$(this).addClass('active');

		return false;
	});

	if ($('#page_core_index_member').length) {

		// Loop thru only our apps feed
		$('.stream_type_' + APP_ID + ' .activity_feed_json:not(.is_data_build)').each(function() {
			var this_obj = $(this).parents('.row_feed_loop:first');

			var html = this_obj.find('.activity_feed_json:first').html();
			$(this).addClass('is_data_build');
			if (html.substr(0, 1) == '{') {
				html = $.parseJSON(html);

				// Make sure its a SoundCloud object we added
				if (isset(html.soundcloud)) {
					var feed_status = this_obj.find('.activity_feed_content_status');

					this_obj.find('.activity_feed_content_status').before('<div class="activity_feed_content_no_image"><a href="#" class="activity_feed_content_link_title" onclick="return false;" style="cursor:default;">' + html.soundcloud.title + '</a><div class="activity_feed_content_display">' + feed_status.html() + '</div></div>');
					var image = new Image();
					image.onload = function() {
						feed_status.html('<div style="background:url(\'' + html.soundcloud.thumbnail_url + '\'); width:' + image.width + 'px; height:' + image.height + 'px; margin:10px auto;"></div>');
					};
					image.src = html.soundcloud.thumbnail_url;
				}
			}

			// Scroll even to only load the song if its in view
			$(window).scroll(function() {
				if ($Core.isInView(this_obj.find('.activity_feed_content_status')) && !$(this_obj).hasClass('is_build')) {
					$(this_obj).addClass('is_build');
					var html = this_obj.find('.activity_feed_json:first').html();
					if (html.substr(0, 1) == '{') {
						html = $.parseJSON(html);
						if (isset(html.soundcloud)) {
							var iframe = html.soundcloud.html.replace('[IFRAME', '<iframe').replace('][/IFRAME]', '></iframe>');

							this_obj.find('.activity_feed_content_status').html('<div style="margin:10px auto;">' + iframe + '</div>');
						}
					}
				}
			});
		});
	}
};
