<?php

error_reporting(E_ALL);

$json = json_decode(urldecode($_REQUEST['json']));

if ($json->public_key != MOXI9_PUBLIC_KEY || $json->private_key != MOXI9_PRIVATE_KEY) {
	exit('Keys do not match.');
}

// Define the client ID, based on what M9 sends us
define('MOXI9_CLIENT_ID', $json->client_site_id);

function _show_error($error) {
	echo 'alert("' . $error . '");';
	exit;
}

function _call($action, $params, $method = 'GET') {

	$params['app_id'] = MOXI9_APP_ID;
	$params = http_build_query($params);

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, MOXI9_API_URL . $action . '/' . ($method == 'GET' ? '&' . $params : ''));

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

function d($data) {
	echo '<pre>';
	print_r($data);
	echo '</pre>';
}

if (isset($json->post) && isset($json->post->val)) {

	if (!empty($json->post->val->soundcloud_url)) {

		$url = urlencode($json->post->val->soundcloud_url);

		$data = @file_get_contents('http://soundcloud.com/oembed?url=' . $url . '&format=json');
		if (substr($data, 0, 1) == '{') {
			$data = json_decode($data);

			$iframe = $data->html;
			$data->html = str_replace(array('<iframe', '></iframe>'), array('[IFRAME', '][/IFRAME]'), $data->html);

			$stream_id = _call('stream/add', array('user_id' => $json->user->id, 'type' => MOXI9_APP_ID, 'content' => $data->description, 'soundcloud' => (array) $data), 'POST');

			$stream = _call('stream/get', array('user_id' => $json->user->id, 'id' => $stream_id));

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

			echo 'var soundcloud_html = ' . json_encode(array('html' => $new_feed)) . ';';
			echo '$(\'#js_feed_content\').prepend(soundcloud_html.html);';
			exit;
		}

		_show_error('Not a valid SoundCloud URL.');
	}

	_show_error('Missing SoundCloud URL.');
}

echo 'stream:' . MOXI9_APP_ID;

?>
