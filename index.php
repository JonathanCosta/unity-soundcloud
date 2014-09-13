<?php

// Check if our local config file exists
if (!file_exists('./config.php')) {
	exit('Rename config.php.new to config.php');
}

// Load the config file
require_once('./config.php');

// Make sure it is a valid M9 request
if (empty($_REQUEST['json'])) {
	exit('Not a valid M9 call.');
}

// Decode the request and convert it into an object
$json = json_decode(urldecode($_REQUEST['json']));

// Make sure the public and private keys match
if ($json->public_key != MOXI9_PUBLIC_KEY || $json->private_key != MOXI9_PRIVATE_KEY) {
	exit('Keys do not match.');
}

// Define the client ID, based on what M9 sends us
define('MOXI9_CLIENT_ID', $json->client_site_id);

/**
 * Small function to output an error when a SoundCloud URL is invalid
 *
 * @param $error Error to output
 */
function _show_error($error) {
	echo 'alert("' . $error . '");';
	exit;
}

/**
 * API call to M9 servers
 * We use this here to add/get feeds
 * @see http://unity.moxi9.com/docs/command/stream
 *
 * @param $action Command
 * @param $params ARRAY of anything you want to pass to M9
 * @param string $method
 * @return mixed
 */
function _call($action, $params, $method = 'GET') {

	$params['app_id'] = MOXI9_APP_ID;
	$params = http_build_query($params);

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, 'https://api.moxi9.com/' . $action . '/' . ($method == 'GET' ? '&' . $params : ''));

	if ($method == 'POST') {
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
	}

	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_USERPWD, '' . MOXI9_PUBLIC_KEY . ':' . MOXI9_PRIVATE_KEY . '');
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('MOXI9-CLIENT: ' . MOXI9_CLIENT_ID));
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	$data = curl_exec($curl);
	curl_close($curl);

	if (substr($data, 0, 1) == '{' || substr($data, 0, 1) == '[' || substr($data, 0, 1) == '"') {
		$data = json_decode($data);
	}

	return $data;
}

// Check if this is a $_POST from a form
if (isset($json->post) && isset($json->post->val)) {

	// Make sure the user provided a SoundCloud URL
	if (!empty($json->post->val->soundcloud_url)) {

		// Encode the URL to pass along to SoundCloud
		$url = urlencode($json->post->val->soundcloud_url);

		$data = @file_get_contents('http://soundcloud.com/oembed?url=' . $url . '&format=json');

		// Quick check to see if we returned a JSON object
		if (substr($data, 0, 1) == '{') {
			$data = json_decode($data);

			$iframe = $data->html;
			$data->html = str_replace(array('<iframe', '></iframe>'), array('[IFRAME', '][/IFRAME]'), $data->html);

			// Add this post to the activity feed
			$stream_id = _call('stream/add', array('user_id' => $json->user->id, 'type' => MOXI9_APP_ID, 'content' => (!empty($data->description) ? $data->description : '&nbsp;&nbsp;'), 'soundcloud' => (array) $data), 'POST');

			// Get the feed from what we just added
			$stream = _call('stream/get', array('user_id' => $json->user->id, 'id' => $stream_id));

			// Build the HTML for the feed, until PHPfox has support for this.
			// This HTML is only used when posting to the feed. It is not saved or cached anywhere
			$new_feed = '
				<div class="js_feed_view_more_entry_holder">
					<div class="row_feed_loop row2">
						<div class="activity_feed_image">
							<a href="#"><img src="' . $json->user->thumbnail_url . '" width="50" height="50" /></a>
						</div>
						<div class="activity_feed_content_holder">
							<div class="activity_feed_content">
								<div class="activity_feed_content_text">
									<div class="activity_feed_content_info">
										<span class="user_profile_link_span"><a href="#">' . $json->user->name . '</a></span>
									</div>
									<div class="activity_feed_content_no_image">
										<a href="#" class="activity_feed_content_link_title" onclick="return false;" style="cursor:default;">' . $data->title . '</a>
										<div class="activity_feed_content_display">' . $stream->content_parsed . '</div>
									</div>
									<div class="activity_feed_content_status">
										<div style="margin-top:10px;">
											' . $iframe . '
										</div>
									</div>
								</div>
								<div class="activity_feed_time">
									Just now
								</div>

							</div>
						</div>
					</div>
				</div>
			';

			// Add this to the activity feed
			echo 'var soundcloud_html = ' . json_encode(array('html' => $new_feed)) . ';';
			echo '$(\'#js_feed_content\').prepend(soundcloud_html.html);';
			echo '$Core.resetActivityFeedForm();';
			exit;
		}

		_show_error('Not a valid SoundCloud URL.');
	}

	_show_error('Missing SoundCloud URL.');
}

// If nothing is posted to the feed lets just display the feed and only load SoundCloud entries
// Learn more at: http://unity.moxi9.com/docs/apps/using-streams
echo 'stream:' . MOXI9_APP_ID;

?>
