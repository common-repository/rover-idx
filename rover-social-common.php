<?php
require_once ABSPATH.'wp-admin/includes/image.php';		#	Needed for wp_generate_attachment_metadata()
require_once(ABSPATH.'wp-admin/includes/file.php');
require_once(ABSPATH.'wp-admin/includes/media.php');
require_once ABSPATH.'wp-admin/includes/taxonomy.php';	#	Needed for wp_create_category()
if( !class_exists( 'WP_Http' ) )
	require_once( ABSPATH . WPINC. '/class-http.php' );


class Rover_IDX_SOCIAL {

	private static	$last_search_date				= null;
	private static	$now_search_date				= null;

	private static	$all_updated_properties			= null;

	private static	$thumb_width					= 110;
	private static	$image_width					= 400;

	private static	$wp_post_author					= null;
	private static	$post_to_wp_comments			= null;

	private static	$fb_oauth_exceptions			= 0;
	private static	$fb_access_token				= null;
	private static	$fb_app_id						= 374057345957459;
	private static	$fb_app_secret					= '2bc5721d4143ab1352fe8031a713cb6a';

	private static	$email_on_error					= false;
	private static	$email_on_error_to_user			= false;
	private static	$email_on_post					= false;
	private static	$email_on_post_to_user			= false;

	private static	$previous_user_id				= null;

	private static	$settings						= null;
	private static	$latest_prop_updated_date		= null;

	private static	$url_ends_in_slash				= true;
	private static	$ret_html						= array();

	public static function refresh($fix_missing_images = false) {

    	add_action( 'http_api_curl', function ($ch, $parsed_args, $url) {

			if (strpos($url, 'wasabisys.com') !== false) {
            	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Referer:']);
        		}

	    	}, 10, 3);

		add_filter("http_request_host_is_external", function ($is, $host, $url) {
			return true;
			}, 10, 3);

		require_once ROVER_IDX_PLUGIN_PATH.'rover-database.php';

		//	*******
		//	It's time to make the donuts
		//	*******

		self::start();

		if (self::has_updated_properties())
			{
			if (self::is_enabled('post_to_wp'))
				self::post_to_wordpress();

			if (self::is_enabled('post_to_fb'))
				self::post_to_facebook();

			if (self::is_enabled('post_to_gp'))
				self::post_to_googleplus();

			if (self::is_enabled('post_to_tw'))
				self::post_to_twitter();

			self::finish();
			}
		else
			{
			self::log(__FILE__, __FUNCTION__, __LINE__, 'There are no updates at this time.');
			}

		if ($fix_missing_images)
			{
			self::fix_wordpress_posts();
			return self::$ret_html;
			}

		return true;
		}

	private static function start() {

		if (!is_array(self::$settings = get_option(ROVER_OPTIONS_SOCIAL)))
			self::$settings					= array();

		if (is_array(self::$settings))
			{
			foreach(array('post_to_wp', 'post_to_fb', 'post_to_gp', 'post_to_tw') as $key)
				{
				if (isset(self::$settings[$key]))
					{
					if (is_bool(self::$settings[$key]))
						self::log(__FILE__, __FUNCTION__, __LINE__, $key.' is bool ['.((self::$settings[$key] === true) ? 'true' : 'false').']');
					else if (is_numeric(self::$settings[$key]))
						self::log(__FILE__, __FUNCTION__, __LINE__, $key.' is numeric ['.self::$settings[$key].']');
					else if (is_string(self::$settings[$key]))
						self::log(__FILE__, __FUNCTION__, __LINE__, $key.' is string ['.self::$settings[$key].']');
					}
				}

			self::$wp_post_author			= (isset(self::$settings['post_to_wp_as_user']) && is_numeric(self::$settings['post_to_wp_as_user']))
													? self::$settings['post_to_wp_as_user']
													: 1;		//	Admin

			self::$post_to_wp_comments		= (isset(self::$settings['post_to_wp_comments']) && self::$settings['post_to_wp_comments'] == 'enabled')
													? 'open'
													: 'closed';	//	Default is 'No'

			self::$email_on_error			= (isset(self::$settings['email_on_error']) && self::$settings['email_on_error'] == true) ? true : false;
			self::$email_on_error_to_user	= (isset(self::$settings['email_on_error_to_user']))
													? self::$settings['email_on_error_to_user']
													: get_bloginfo('admin_email');

			self::$email_on_post			= (isset(self::$settings['email_on_post']) && self::$settings['email_on_post'] == true) ? true : false;
			self::$email_on_post_to_user	= (isset(self::$settings['email_on_post_to_user']))
													? self::$settings['email_on_post_to_user']
													: get_bloginfo('admin_email');

 			self::$previous_user_id			= get_current_user_id();
			$user							= get_user_by( 'id', self::$wp_post_author );

			if ($user === false)
				{
				$users						= get_users('role=administrator');
				if (count($users))
					{
					self::log(__FILE__, __FUNCTION__, __LINE__, ' grabbed 1st administrator');
					$user					= reset($users);
					}
				}

			if ($user)
				{
				self::log(__FILE__, __FUNCTION__, __LINE__, ' logging in as ['.$user->ID.']');

				#	wp_set_current_user - we MUST do this for wp_upload_bits()

				wp_set_current_user( $user->ID, $user->user_login );
#				wp_set_auth_cookie( $user->ID );
#				do_action('wp_login', $user->user_login, $user);

				$user->add_cap('unfiltered_upload', true);

				self::log(__FILE__, __FUNCTION__, __LINE__, ' logged in as ['.$user->ID.'] ['.print_r($user->user_login, true).']');
				if ($user->has_cap('unfiltered_upload'))
					self::log(__FILE__, __FUNCTION__, __LINE__, ' logged in user has_cap [unfiltered_upload]');
				}
			else
				{
				self::log(__FILE__, __FUNCTION__, __LINE__, ' Not logged in ['.self::$wp_post_author.']');
				}
			}

		$permalink_structure				= get_option('permalink_structure');
		if (!is_null($permalink_structure) && substr($permalink_structure, -1) != '/')
			self::$url_ends_in_slash		= false;

		$search_since						= self::get_last_search_date();

		self::get_updated_properties($search_since);
		}

	private static function finish() {

		//	Update the search_datetime, so we don't find these same properties again

		if (self::has_updated_properties())
			{
			$key								= self::get_last_search_date_key();
			self::$settings[$key]				= self::get_now_search_date();
			self::$settings['fb_access_token']	= self::$fb_access_token;
			if (!is_null(self::$latest_prop_updated_date))
				self::$settings['search_datetime_']	= self::$latest_prop_updated_date;

			update_option(ROVER_OPTIONS_SOCIAL, self::$settings);
			}

		if (!empty(self::$previous_user_id))
			wp_set_current_user( self::$previous_user_id );
		}

	private static function has_updated_properties() {
		if (is_null(self::$all_updated_properties))
			self::log(__FILE__, __FUNCTION__, __LINE__, 'all_updated_properties is null');
		else
			self::log(__FILE__, __FUNCTION__, __LINE__, 'all_updated_properties has '.count(self::$all_updated_properties).' items');

		return (!is_null(self::$all_updated_properties) && count(self::$all_updated_properties)) ? true : false;
		}
	private static function all_updated_properties() {
		return self::$all_updated_properties;
		}
	private static function get_last_search_date_key()	{
		return 'search_datetime_';
		}

	private static function get_last_search_date()	{
		if (is_null(self::$last_search_date))
			{
			//	Get timestamp from Rover SQL Server.  This is important, because the timestamps
			//	we get must be in the same TZ as the timestamps we send to fetch data.

			date_default_timezone_set('UTC');
			$search_now_datetime_unix	= strtotime('now');
			self::$now_search_date		= date('Y-m-d H:i:s', $search_now_datetime_unix);
			self::log(__FILE__, __FUNCTION__, __LINE__, 'it is currently '.self::$now_search_date);

			$key						= self::get_last_search_date_key();

			self::log(__FILE__, __FUNCTION__, __LINE__, $key);

			if (is_object(self::$settings))
				self::log(__FILE__, __FUNCTION__, __LINE__, 'this->settings is object');
			else if (is_array(self::$settings))
				self::log(__FILE__, __FUNCTION__, __LINE__, 'this->settings is array');

			if (is_array(self::$settings) && isset(self::$settings[$key]))
				{
				$search_datetime_unix	= strtotime(self::$settings[$key]);
				self::$last_search_date	= date('Y-m-d H:i:s', $search_datetime_unix);

				self::log(__FILE__, __FUNCTION__, __LINE__, 'key exists, using '.self::$last_search_date);
				}
			else
				{
				$search_datetime_unix	= strtotime("-1 day");
				self::$last_search_date	= date('Y-m-d H:i:s', $search_datetime_unix);

				self::log(__FILE__, __FUNCTION__, __LINE__, 'key does not exist, creating '.self::$last_search_date);
				}
			}

		return self::$last_search_date;
		}
	private static function get_now_search_date()	{
		return self::$now_search_date;
		}

	private static function get_updated_properties($search_since)	{

		global							$rover_idx;

		if (
			!self::is_enabled('post_to_wp') &&
			!self::is_enabled('post_to_fb') &&
			!self::is_enabled('post_to_gp') &&
			!self::is_enabled('post_to_tw')
			)
			{
			self::log(__FILE__, __FUNCTION__, __LINE__, 'social updates are disabled - Terminating.');
			return false;
			}

		self::log(__FILE__, __FUNCTION__, __LINE__, 'Looking for updates since '.$search_since);
		self::log(__FILE__, __FUNCTION__, __LINE__, 'There are '.count($rover_idx->all_selected_regions).' selected regions ['.implode(',', array_keys($rover_idx->all_selected_regions)).']');

		//	Specify HTML email message format

		if (function_exists('add_filter'))
			add_filter('wp_mail_content_type', function() { return "text/html"; } );

		foreach ($rover_idx->all_selected_regions as $one_region => $region_slugs)
			{
			$rover_content				= Rover_IDX_Content::rover_content(
																			'rover-social-fetch-updated',
																			array(
																				'region'			=> $one_region,
																				'domain'			=> get_site_url(),
																				'since'				=> $search_since
																				)
																			);

			self::log(__FILE__, __FUNCTION__, __LINE__, '['.$rover_content['the_html'].']');

			$result_decoded				= json_decode($rover_content['the_html'], true);

			if ($result_decoded === null)
				{
				self::log(__FILE__, __FUNCTION__, __LINE__, 'xml did not decode correctly');
				self::log(__FILE__, __FUNCTION__, __LINE__, $result);

				if (function_exists('wp_mail') && self::$email_on_error)
					{
					wp_mail(self::$email_on_error_to_user,
							get_site_url().': Rover IDX Social: xml did not decode correctly',
							'Oops');
					}

				return false;
				}

			self::$all_updated_properties	= array();

			if (is_array($result_decoded) && isset($result_decoded['properties']) && count($result_decoded['properties']))
				{
				//	Remove properties that have already been posted

				foreach ($result_decoded['properties'] as $prop)
					{
					self::log(__FILE__, __FUNCTION__, __LINE__, '['.$prop['id'].'] ['.$prop['mlnumber'].']');

					if (is_array($prop))
						self::log(__FILE__, __FUNCTION__, __LINE__, 'prop is [array] of ['.count($prop).'] items');
					else if (is_object($prop))
						self::log(__FILE__, __FUNCTION__, __LINE__, 'prop is [object] of ['.count($prop).'] items');

					if (roveridx_already_in_tracking($prop['id'], $prop['region']))
						self::log(__FILE__, __FUNCTION__, __LINE__, $prop['id'].' has already been posted');
					else
						self::$all_updated_properties[] = $prop;
					}

				if (count(self::$all_updated_properties) === 0)
					{
					return false;
					}

				//	The remaining properties can be posted

				$theHTML		=	'<div id="rover-properties">';
				foreach (self::$all_updated_properties as $prop)
					{
					$theHTML	.= '<div style="width:700px;">';
					$theHTML	.=		'<div style="float:left;width:130px;margin:0 10px;">';
					$theHTML	.=			'<img src="'.$prop['image'].'" width="'.self::$thumb_width.'" /></a>';
					$theHTML	.=		'</div>';
					$theHTML	.=		'<div style="float:left;width:500px;margin-left:10px;">';
					$theHTML	.=			'<div>'.$prop['type'].'</div>';
					$theHTML	.=			'<a href="'.get_site_url().'/'.$prop['link'].'" target="_blank">'.$prop['address'].'</a><br>';
					$theHTML	.=			'<div>Updated: '.$prop['upd_date'].' (SQL time)</div>';
					$theHTML	.=			'<div style="color:#444;">'.$prop['desc'].'</div>';
					$theHTML	.=		'</div>';
					$theHTML	.= '</div>';
					$theHTML	.= '<hr style="color:#999;">';
					$theHTML	.= '<div style="clear:both;margin-bottom:10px;"></div>';
					}

				$theHTML		.= '</div>';
				$theHTML		.= '<div style="margin-top:20px;">';
				$theHTML		.=		'Search Since Time: '.self::get_last_search_date().'<br />';
				$theHTML		.=		'Now is: '.self::get_now_search_date();
				$theHTML		.= '</div>';

				if (self::$email_on_post)
					{
					wp_mail(self::$email_on_post_to_user,
							get_site_url().': Rover IDX Social: We have '.count(self::$all_updated_properties).' updated properties',
							$theHTML);
					}
				}
			}
		}

	private static function refresh_facebook_token($code) {

		// If we get a code, it means that we have re-authed the user
		//and can get a valid access_token.

		$token_url	= "https://graph.facebook.com/oauth/access_token"
						. "?client_id=" . self::$fb_app_id
						. $app_id . "&redirect_uri=" . urlencode($my_url)
						. "&client_secret=" . self::$fb_app_secret
						. "&code=" . $code . "&display=popup";

		self::log(__FILE__, __FUNCTION__, __LINE__, 'token_url: '.$token_url);

		if ($does_not_work === true)
			{
			$response = file_get_contents($token_url);	//	Generating ->	HTTP request failed! HTTP/1.0 400 Bad Request
			$params = null;
			parse_str($response, $params);
			$access_token = $params['access_token'];
			}
		else
			{
			$response = self::curl_file_get_contents($token_url);

			self::log(__FILE__, __FUNCTION__, __LINE__, 'response is '.$response);

			$result_oauth = json_decode($response, true);
			if (is_array($result_oauth))
				{
				if (isset($result_oauth['error']))
					{
					self::log(__FILE__, __FUNCTION__, __LINE__, $result_oauth['message'].' / '.$result_oauth['type'].' / '.$result_oauth['code']);
					return null;
					}
				else
					{
					$access_token = $result_oauth['access_token'];
					}
				}
			else
				{
				self::log(__FILE__, __FUNCTION__, __LINE__, 'Decoded result is not an array - failing');
				return null;
				}
			}



		//	Verify that this token works

	    $token_url	= "https://graph.facebook.com/me"
	    				. "?access_token=" . $access_token;

	    $response = self::curl_file_get_contents($token_url);

	    $result_oauth = json_decode($response, true);
		if (is_array($result_oauth) && isset($result_oauth['error']))
			{
			self::log(__FILE__, __FUNCTION__, __LINE__, $result_oauth['message'].' / '.$result_oauth['type'].' / '.$result_oauth['code']);
			return null;
			}

		return $access_token;
		}
	private static function curl_file_get_contents($url) {
		//	note this wrapper function exists in order to circumvent PHP’s strict obeying of HTTP error
		//	codes.  In this case, Facebook returns error code 400 which PHP obeys and wipes out the response.

		$c = curl_init();
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_URL, $url);
		$contents = curl_exec($c);
		$err  = curl_getinfo($c,CURLINFO_HTTP_CODE);
		curl_close($c);
		if ($contents)
			return $contents;
		else
			return false;
		}

	private static function is_enabled($key) {
		if (isset(self::$settings[$key]))
			{
			if (self::$settings[$key] === true)
				return true;
			if (self::$settings[$key] == 'enabled')
				return true;
			}

		return false;
		}

	private static function post_to_wordpress() {
		$ping_status						= get_option('default_ping_status');

		foreach (self::$all_updated_properties as $one_property)
			{
			self::log(__FILE__, __FUNCTION__, __LINE__, $one_property['address']);

			#	Trim leading slash from 'link', if it exists.
			#	Avoid double slashes like https://realestate.com//fl/orlando/123-main-drive/

			$wp_post_content				=	'<a href="'.get_site_url().'/'.ltrim($one_property['link'], "/").'" target="_blank">'.$one_property['address'].' offered at '.$one_property['price'].'</a>';
			$wp_post_content				.=	'<div style="margin:20px 0 0 0;">'.$one_property['desc'].'</div>';

			//	These Posts will all have a Rover category stamp (ease of finding them all later)

			$wp_cat							= 'Property';
			$wp_cat_value					= $wp_cat;
			if ($one_property['type'] == 'New') {
				$wp_cat						= 'New Property';
				$wp_cat_value				= $wp_cat;
				}
			else if ($one_property['type'] == 'Price') {
				$wp_cat						= 'Updated Property';
				$wp_cat_value				= 'price-'.preg_replace('/[^0-9]/', '', $one_property['price']);
				}
			else if ($one_property['type'] == 'Sold') {
				$wp_cat						= 'Sold Property';
				$wp_cat_value				= $wp_cat;
				}
			else if ($one_property['type'] == 'OpenHouse') {
				$wp_cat						= 'Open House';
				$wp_cat_value				= 'open-'.date('Y-m-d', strtotime($one_property['upd_date']));
				}

			if (self::should_we_post($wp_cat, $one_property))
				{
				$cat_id						= get_cat_ID($wp_cat);
				if (!$cat_id)
					$cat_id					= wp_create_category($wp_cat);

				$rover_cat					= 'Rover IDX Property';
				$rover_cat_id				= get_cat_ID($rover_cat);
				if (!$rover_cat_id)
					$rover_cat_id			= wp_create_category($rover_cat);

				$rover_post					= array(
													'post_title'		=> $one_property['title'],
													'post_content'		=> $wp_post_content,
													'post_status'		=> 'publish',
													'post_author'		=> self::$wp_post_author,
													'ping_status'		=> $ping_status,
													'post_category'		=> array($cat_id, $rover_cat_id),
													'comment_status'	=> self::$post_to_wp_comments,
													'filter' 			=> true		//	Don't sanitize the post_content
													);

				if (isset($one_property['ourListingDate']) && !empty($one_property['ourListingDate']))
					{
					$the_date								= strtotime($one_property['ourListingDate']);
					if ($the_date !== false)
						{
						$rover_post['post_date']			= date("Y-m-d H:i:s", $the_date);
						$rover_post['post_date_gmt']		= gmdate("Y-m-d H:i:s", $the_date);

						self::$latest_prop_updated_date		= (is_null(self::$latest_prop_updated_date) || ($the_date > strtotime(self::$latest_prop_updated_date)))
																	? date("Y-m-d H:i:s", $the_date)
																	: self::$latest_prop_updated_date;
						}
					}

				$post_id									= wp_insert_post( $rover_post );

				if (is_wp_error($post_id)) {
					$errors = $post_id->get_error_messages();
					foreach ($errors as $error) {
						self::log(__FILE__, __FUNCTION__, __LINE__, 'wp_error: '.$error);
						}
					}
				else
					{
					add_post_meta($post_id, 'rover-idx-mlnumber', $one_property['mlnumber']);
					add_post_meta($post_id, 'rover-idx-cat', $wp_cat_value);

					self::log(__FILE__, __FUNCTION__, __LINE__, 'Requesting ['.$one_property['image'].']');

					roveridx_update_tracking($one_property['id'], $one_property['region']);

					self::add_featured_image($post_id, $one_property);
					}
				}
			}
		}

	private static function post_to_facebook() {
		require_once 'facebook.php';

		try {
			$facebook = new Facebook(array(
										'appId'  		=> self::$fb_app_id,
										'secret' 		=> self::$fb_app_secret,
										'fileUpload'	=> true
										));

			if (isset(self::$settings['fb_access_token']))
				{
				self::$fb_access_token	= self::$settings['fb_access_token'];

				if (self::$settings['fb_id'])
					{
					self::log(__FILE__, __FUNCTION__, __LINE__, 'Facebook ID: '.self::$settings['fb_id']);
					self::log(__FILE__, __FUNCTION__, __LINE__, 'Facebook Token: '.self::$fb_access_token);

//					foreach (self::$all_updated_properties as $one_property)
//						{
						do {

							if (self::$fb_oauth_exceptions >= 2)
								break;

							$all_updated	= self::$all_updated_properties;
							$one_property	= $all_updated[0];

							$short_desc	= substr($one_property['desc'], 0, 155);	//	Desc has 155 char limit in Facebook
							$pos		= strpos($short_desc, '. ');				//	Attempt to limit it by the end of a sentence.
							if ($pos !== false)
								$short_desc = substr($short_desc, 0, $pos);

							$url 		=	"https://graph.facebook.com/".self::$settings['fb_id']."/feed";


							if (count(self::$all_updated_properties) > 1)											//	Facebook combines multiple posts if posted within minutes of each other.
								{																					//	Avoid that by posting summary of updated properties for this hour.
								$name		=	self::build_mult_title();
								$link		=	self::build_mult_link().'/page_title/'.urlencode($name);
								$desc		=	self::build_mult_desc();
								}
							else
								{
								$name		=	$one_property['title'];
								$link		=	$one_property['link'].'/page_title/'.urlencode($one_property['title']);
								$desc		=	$short_desc;
								}

							$prop_link		=	get_site_url();
							$prop_link		.=	(substr($prop_link, -1) == '/')	? '' : '/';
							$prop_link		.=	$link;
							$prop_link		.=	(self::$url_ends_in_slash)	? '/' : '';

							self::log(__FILE__, __FUNCTION__, __LINE__, 'prop_link: '.$prop_link);

							$fb_post	=	array(
												'access_token'	=> self::$fb_access_token,
												'name'			=> $name,										//	The Message to be posted above the actual link post
//												'name'			=> $one_property['title'],						//	Title of the URL to be posted
												'link'			=> $prop_link,									//	Direct (Full) URL of the Link to be posted
												'picture'		=> $one_property['image'],						//	Absolute URL of the accompanying image to be posted
												'description'	=> $desc										//	A short description text about the post
												);

							self::log(__FILE__, __FUNCTION__, __LINE__, 'message - '.$fb_post['message']);
							self::log(__FILE__, __FUNCTION__, __LINE__, 'link - '.$fb_post['link']);
							self::log(__FILE__, __FUNCTION__, __LINE__, 'description - '.$fb_post['description']);
							self::log(__FILE__, __FUNCTION__, __LINE__, 'picture - '.$fb_post['picture']);

							$status = $facebook->api('/'.self::$settings['fb_id'].'/feed', 'POST', $fb_post);
							if ($status)
								{
								if (count(self::$all_updated_properties) > 1)
									{
									if (self::$email_on_post)
										{
										wp_mail(self::$email_on_post_to_user,
												get_site_url().': Rover IDX Social: Facebook Post successful',
												'Rover IDX posted '.count(self::$all_updated_properties).' properties to Facebook');
										}
									}
								else
									{
									if (self::$email_on_post)
										{
										wp_mail(self::$email_on_post_to_user,
												get_site_url().': Rover IDX Social: Facebook Post successful',
												'Rover IDX posted '.count(self::$all_updated_properties).' properties to Facebook');
										}
									}

								self::log(__FILE__, __FUNCTION__, __LINE__, 'Facebook Post successful');
								break;
								}
							else
								{
								if (self::$email_on_error)
									{
									wp_mail(self::$email_on_error_to_user,
											get_site_url().': Rover IDX Social: Facebook Post failure',
											'Rover IDX encountered ['.$status.'] when posting to Facebook');
									}

								self::log(__FILE__, __FUNCTION__, __LINE__, 'Facebook Post failed - '.$status);
								self::$fb_oauth_exceptions++;

								//	Get a refreshed token, and try again

								self::$fb_access_token	= $facebook->getAccessToken();
								}
							} while ( true );
//						}
					}
				else	//	Not logged in
					{
					if (self::$email_on_error)
						{
						wp_mail(self::$email_on_error_to_user,
								get_site_url().': Rover IDX Social: Not logged in to Facebook',
								'Rover would like to post updates to Facebook, but the Facebook settings were not finalized - Not logged in to Facebook');
						}
					}
				}
			else
				{
				if (self::$email_on_error)
					{
					wp_mail(self::$email_on_error_to_user,
							get_site_url().': Rover IDX Social: Facebook not been setup',
							'Facebook updates have been enabled, but Facebook credentials have not been setup yet.');
					}
				}
			}
		catch(FacebookApiException $e) {
			self::log(__FILE__, __FUNCTION__, __LINE__, 'Facebook Exception: '.$e->getType().' / '.$e->getMessage());
			}
		}

	private static function post_to_googleplus() {
		foreach (self::$all_updated_properties as $one_property)
			{

			}
		}

	private static function post_to_twitter() {
		foreach (self::$all_updated_properties as $one_property)
			{

			}
		}

	private static function fix_wordpress_posts() {

		global							$rover_idx;

		self::$ret_html					= array();
		if (!self::is_enabled('post_to_wp'))
			{
			self::$ret_html[]			= __FUNCTION__.' '.__LINE__.' - social updates are disabled';
			return self::$ret_html;
			}

		if (!is_array(self::$settings = get_option(ROVER_OPTIONS_SOCIAL)))
			self::$settings					= array();

		if (is_array(self::$settings))
			{
			self::$wp_post_author		= (isset(self::$settings['post_to_wp_as_user']) && is_numeric(self::$settings['post_to_wp_as_user']))
												? self::$settings['post_to_wp_as_user']
												: 1;		//	Admin
			}

		$user							= get_user_by( 'id', self::$wp_post_author );

		if ($user === false)
			{
			$users						= get_users('role=administrator');
			if (count($users))
				{
				self::$ret_html[]		= __FUNCTION__.' '.__LINE__.' - grabbed 1st administrator';
				$user					= reset($users);
				}
			}

		if ($user)
			{
 			self::$previous_user_id		= get_current_user_id();
			self::$ret_html[]			= __FUNCTION__.' '.__LINE__.' - logging in as ['.$user->ID.']';

			#	wp_set_current_user - we MUST do this for wp_upload_bits()

			wp_set_current_user( $user->ID, $user->user_login );
#			wp_set_auth_cookie( $user->ID );
#				do_action('wp_login', $user->user_login, $user);

			$user->add_cap('unfiltered_upload', true);

			self::$ret_html[]			= __FUNCTION__.' '.__LINE__.' - logged in as ['.$user->ID.'] ['.print_r($user->user_login, true).']';
			if ($user->has_cap('unfiltered_upload'))
				self::$ret_html[]		= __FUNCTION__.' '.__LINE__.' - logged in user has_cap [unfiltered_upload]';
			}
		else
			{
			self::$ret_html[]			= __FUNCTION__.' '.__LINE__.' - Not logged in ['.self::$wp_post_author.']';
			}

		$mlnumbers						= array();
		foreach (get_posts(array(
										'category'		=> get_cat_ID('Rover IDX Property'),
										'numberposts' 	=> 500,
										'order'			=> 'DESC'
										)) as $post)
			{
#			self::$ret_html[]			= __FUNCTION__.' '.__LINE__.' - considering post ['.$post->ID.']';
			if ( !has_post_thumbnail( $post->ID ))
				{
				$mln					= get_post_meta( $post->ID, 'rover-idx-mlnumber', $single = true );
				if (!empty($mln))
					{
					$mlnumbers[$mln]	= $post->ID;
					self::$ret_html[]	= __FUNCTION__.' '.__LINE__.' - post ['.$post->ID.'] / mlnumber ['.$mln.'] has no featured image';
					}
				}
			}

		self::$ret_html[]				= __FUNCTION__.' '.__LINE__.' - found: '.count($mlnumbers).' with missing featured image';
		foreach($mlnumbers as $mln => $post_id)
			{
			self::$ret_html[]			= __FUNCTION__.' '.__LINE__.' - mlnumber ['.$mln.'] / post ['.$post_id.']';
			}

		foreach ($rover_idx->all_selected_regions as $one_region => $region_slugs)
			{
			require_once ROVER_IDX_PLUGIN_PATH.'rover-content.php';

			$rover_content				= Rover_IDX_Content::rover_content(
																			'rover-social-fix-posts',
																			array(
																				'region'			=> $one_region,
																				'domain'			=> get_site_url(),
																				'mlnumbers'			=> array_keys($mlnumbers)
																				)
																			);

			self::log(__FILE__, __FUNCTION__, __LINE__, '['.$rover_content['the_html'].']');

			$result_decoded				= json_decode($rover_content['the_html'], true);

			if ($result_decoded === null)
				{
				self::$ret_html[]		= __FUNCTION__.' '.__LINE__.' - json did not decode correctly';

				if (function_exists('wp_mail') && self::$email_on_error)
					wp_mail(self::$email_on_error_to_user, get_site_url().': Rover IDX Social: xml did not decode correctly', 'Oops');

				return false;
				}

			if (is_array($result_decoded) && isset($result_decoded['properties']) && count($result_decoded['properties']))
				{
				#	Update featured image

				foreach ($result_decoded['properties'] as $prop)
					{
					self::$ret_html[]	= __FUNCTION__.' '.__LINE__.' - ['.$prop['id'].'] ['.$prop['mlnumber'].']';

					if (isset($mlnumbers[$prop['mlnumber']]))
						{
						$post_id		= $mlnumbers[$prop['mlnumber']];
						self::add_featured_image($post_id, $prop);
						}
					else
						{
self::$ret_html[]	= __FUNCTION__.' '.__LINE__.' - ['.$prop['mlnumber'].'] is not in ['.print_r(array_keys($mlnumbers), true).']';
						}
#					foreach($mlnumbers as $post_id => $mln)
#						{
#						if ($mln == $prop['mlnumber'])
#							self::add_featured_image($post_id, $prop);
#						}
					}
				}
			else
				{
				self::$ret_html[]		= __FUNCTION__.' '.__LINE__.' - no properties returned';
				}
			}

		if ($user)
			{
			if (!empty(self::$previous_user_id))
				wp_set_current_user( self::$previous_user_id );
			}
		}

	private static function add_featured_image($post_id, $prop)
		{
		$dest_file						= sprintf("%s.jpg", strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '', $prop['region'].'-'.$prop['mlnumber'])));

		self::log(__FILE__, __FUNCTION__, __LINE__, 'photo url: '.$prop['image'].' -> '.$dest_file);
		if (false)
			{
			$attachment					= wp_upload_bits($dest_file, null, file_get_contents($prop['image']));
			if ( empty( $attachment['error'] ) )
				{
self::$ret_html[]		= __FUNCTION__.' '.__LINE__.' - post_id ['.$post_id.'] attachment ['.print_r($attachment, true).']';
				$postinfo				= array(
											'post_mime_type'	=> wp_check_filetype( basename( $attachment['file'] ), null ),
											'post_title'		=> 'image for '.$prop['title'],
											'post_content'		=> $prop['mlnumber'],
											'post_status'		=> 'inherit',
											);

				$attach_id				= wp_insert_attachment( $postinfo, $attachment['file'], $post_id );
				$attach_data			= wp_generate_attachment_metadata( $attach_id, $attachment['file'] );
				wp_update_attachment_metadata( $attach_id,  $attach_data );

				self::$ret_html[]		= __FUNCTION__.' '.__LINE__.' - post_id ['.$post_id.'] attach_id ['.$attach_id.']';

				self::prepend_image_to_post($post_id, $prop, $attach_id);
				}
			else
				{
				self::$ret_html[]		= __FUNCTION__.' '.__LINE__.' - wp_upload_bits returned error: '.$attachment['error'];
				}
			}
		else if (true)
			{
			self::$ret_html[]			= __FUNCTION__.' '.__LINE__.' - post_id ['.$post_id.'] about to download_url ['.$prop['image'].']';

			$tmp						= download_url( $prop['image'] );	#	put in a temp location

			if ( is_wp_error( $tmp ) ) {
				$errors					= $tmp->get_error_messages();
				foreach ($errors as $error) {

					#	download_url()

					self::$ret_html[]	= __FUNCTION__.' '.__LINE__.' - post_id ['.$post_id.'] download_url failed ['.print_r($error, true).']';
					self::log(__FILE__, __FUNCTION__, __LINE__, 'download_url failed: '.$error);
					@unlink( $tmp );

					#	download_url failed.  Try with curl

#					$fp					= tmpfile();
					$tmp				= sprintf('%s/%s', sys_get_temp_dir(), $dest_file);

					$ch					= curl_init($prop['image']);

					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: image/jpeg"));
					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
					curl_setopt($ch, CURLOPT_FILE, fopen($tmp, 'wb'));

					$raw				= curl_exec($ch);
					curl_close ($ch);

#					if (file_put_contents($tmp, $raw))
					if (file_exists($tmp))
						{
#						$meta_data			= stream_get_meta_data($fp);
#						$tmp				= $meta_data["uri"];	#	for media_handle_sideload()

						self::$ret_html[]	= __FUNCTION__.' '.__LINE__.' - post_id ['.$post_id.'] curl saved file to ['.print_r($tmp, true).']';
						}
					else
						{
						self::$ret_html[]	= __FUNCTION__.' '.__LINE__.' - post_id ['.$post_id.'] curl failed';
						return false;
						}

					fclose($fp);
					}
				} // check for errors
			else
				{
				self::$ret_html[]			= __FUNCTION__.' '.__LINE__.' - post_id ['.$post_id.'] download_url returned ['.print_r($tmp, true).']';
				}

			#	download image to this server
#			$attach_id						= media_handle_sideload( $prop['image'], $post_id, $prop['mlnumber']);
			$attach_id						= media_handle_sideload( array(
											'name'					=> $dest_file,
											'tmp_name'				=> $tmp
											), $post_id );

			#	Check for handle sideload errors.
			if ( is_wp_error( $attach_id ) ) {
				$errors						= $attach_id->get_error_messages();
				foreach ($errors as $error) {
					self::log(__FILE__, __FUNCTION__, __LINE__, 'media_handle_sideload failed: '.$error);
				    $mime_type				= mime_content_type($tmp);

					self::$ret_html[]		= __FUNCTION__.' '.__LINE__.' - post_id ['.$post_id.'] media_handle_sideload failed ['.print_r($error, true).'] - mime_type ['.print_r($mime_type, true).'] - allowed mime types are ['.print_r(get_allowed_mime_types(), true).']';
					}
				return false;
				}

			self::$ret_html[]				= __FUNCTION__.' '.__LINE__.' - post_id ['.$post_id.'] media_handle_sideload returned ['.print_r($attach_id, true).']';
			self::log(__FILE__, __FUNCTION__, __LINE__, '[post_id '.$post_id.'] ['.$attach_id.']');

			self::prepend_image_to_post($post_id, $prop, $attach_id);
			}
		else
			{
		$request							= new WP_Http();
		$photo								= $request->request( $prop['image'] );

		if (is_a($photo, 'WP_Error'))
			{
			self::log(__FILE__, __FUNCTION__, __LINE__, 'WP_Http request returned an error: '.print_r($photo, true));
			}
		else
			{
			if( $photo['response']['code'] == 200 )
				{
				$attachment					= wp_upload_bits( $dest_file, null, $photo['body'], date("Y-m", strtotime( $photo['headers']['last-modified'] ) ) );
				if( empty( $attachment['error'] ) )
					{
					$filetype				= wp_check_filetype( basename( $attachment['file'] ), null );

					$postinfo				= array(
												'post_mime_type'	=> $filetype['type'],
												'post_title'		=> 'image for '.$prop['title'],
												'post_content'		=> '',
												'post_status'		=> 'inherit',
												);

					$attach_id				= wp_insert_attachment( $postinfo, $attachment['file'], $post_id );
					$attach_data			= wp_generate_attachment_metadata( $attach_id, $attachment['file'] );
					wp_update_attachment_metadata( $attach_id,  $attach_data );

					self::log(__FILE__, __FUNCTION__, __LINE__, '[post_id '.$post_id.'] ['.$attach_id.']');

					self::prepend_image_to_post($post_id, $prop, $attach_id);
					}
				else
					{
					self::log(__FILE__, __FUNCTION__, __LINE__, 'wp_upload_bits returned error: '.$attachment['error']);
					}
				}
			else
				{
				self::log(__FILE__, __FUNCTION__, __LINE__, 'WP_Http request returned error: '.$photo['response']['code']);
				}
			}
			}
		}

	private static function prepend_image_to_post($post_id, $prop, $attach_id)
		{
		#	Add image as Featured Image
		$ret							= set_post_thumbnail( $post_id, $attach_id );
		self::$ret_html[]				= __FUNCTION__.' '.__LINE__.' - post_id ['.$post_id.'] set_post_thumbnail returned ['.(($ret === true) ? 'true' : 'false').']';


		#	Insert the image at the beginning of the post
		$post							= get_post($post_id, 'ARRAY_A');
		$image							= wp_get_attachment_image_src( $attach_id );
		if ($image)
			{
			if ($image[1] <= self::$image_width)			//	If image provided is smaller than what we wanted
				{
				$image_width			= $image[1];
				$image_height			= $image[2];
				}
			else
				{
				$image_width			= self::$image_width;		//	Proportional resizing
				$image_height			= ((self::$image_width / $image[1]) * $image[2]);
				}

			$image_tag					= '<a href="'.get_site_url().'/'.$prop['link'].'" target="_blank"><img src="'.$image[0].'" width="'.$image_width.'" height="'.$image_height.'" class="alignleft" /></a>';

			//	Add image above the content
			$post['post_content']		= $image_tag . $post['post_content'];

			$post_id					= wp_update_post( $post );
			if ( is_wp_error( $post_id ) ) {
				$errors					= $post_id->get_error_messages();
				foreach ($errors as $error) {
					self::$ret_html[]	= __FUNCTION__.' '.__LINE__.' - post_id ['.$post_id.'] wp_update_post failed ['.print_r($error, true).']';
					self::log(__FILE__, __FUNCTION__, __LINE__, 'wp_update_post failed: '.$error);
					}
				return false;
				}
			}
		}

	#	Prevent duplicate 'New' and 'Sold' postings

	private static function should_we_post($wp_cat, $one_property)
		{
		global							$wpdb;

		$meta_for_mlnumber				= $wpdb->get_results("SELECT * FROM $wpdb->postmeta
										WHERE meta_key = 'rover-idx-mlnumber'
										AND meta_value = '".$one_property['mlnumber']."'");

		if ($wp_cat == 'New') {			#	If any meta exists, we've posted this listing previously.  Do not post it again.
			if (!is_null($meta_for_mlnumber))
				{
				self::log(__FILE__, __FUNCTION__, __LINE__, ' Skipping '.$one_property['mlnumber'].' ['.$wp_cat.'] - post '.$one_row->meta_id.' '.$one_row->post_id.' already exists');
				return false;
				}
			}
		else if ($wp_cat == 'Price') {
			$lookup_price				= 'price-'.preg_replace('/[^0-9]/', '', $one_property['price']);
			foreach( $meta_for_mlnumber as $one_row) {
				if ($one_row->meta_key === 'rover-idx-cat' && $one_row->meta_value === $lookup_price)
					{
					self::log(__FILE__, __FUNCTION__, __LINE__, ' Skipping '.$one_property['mlnumber'].' ['.$wp_cat.'] - post '.$one_row->meta_id.' '.$one_row->post_id.' already exists');
					return false;
					}
				}
			}
		else if ($wp_cat == 'Sold') {
			foreach( $meta_for_mlnumber as $one_row) {
				if ($one_row->meta_key === 'rover-idx-cat' && $one_row->meta_value === $wp_cat)
					{
					self::log(__FILE__, __FUNCTION__, __LINE__, ' Skipping '.$one_property['mlnumber'].' ['.$wp_cat.'] - post '.$one_row->meta_id.' '.$one_row->post_id.' already exists');
					return false;
					}
				}
			}
		else if ($wp_cat == 'OpenHouse') {
			$lookup_date				= 'open-'.date('Y-m-d', strtotime($one_property['upd_date']));
			foreach( $meta_for_mlnumber as $one_row) {
				if ($one_row->meta_key === 'rover-idx-cat' && $one_row->meta_value === $lookup_date)
					{
					self::log(__FILE__, __FUNCTION__, __LINE__, ' Skipping '.$one_property['mlnumber'].' ['.$wp_cat.'] - post '.$one_row->meta_id.' '.$one_row->post_id.' already exists');
					return false;
					}
				}
			}
		else {
			#	Not sure what to do with this one
			}

		return true;
		}

	private static function build_mult_title()
		{
		$props_new	= 0;
		$props_upt	= 0;
		$props_sold	= 0;
		$props_open	= 0;

		if (count(self::$all_updated_properties) < 2)
			return null;

		foreach (self::$all_updated_properties as $one_property)
			{
			self::log(__FILE__, __FUNCTION__, __LINE__, 'prop type: '.$one_property['type']);

			if ($one_property['type'] == 'New')
				$props_new++;
			else if ($one_property['type'] == 'Price')
				$props_upt++;
			else if ($one_property['type'] == 'Sold')
				$props_sold++;
			else if ($one_property['type'] == 'OpenHouse')
				$props_open++;
			}

		self::log(__FILE__, __FUNCTION__, __LINE__, 'props_new : '.$props_new);
		self::log(__FILE__, __FUNCTION__, __LINE__, 'props_upt : '.$props_upt);
		self::log(__FILE__, __FUNCTION__, __LINE__, 'props_sold: '.$props_sold);

		$theTitle = array();
		if ($props_new > 0)
			{
			$theTitle[]	= ($props_new === 1) ?
									'1 New Listing'
									: ($props_new.' New Listings');
			}

		if ($props_upt > 0)
			{
			$theTitle[]	= ($props_upt === 1)
									? '1 Updated Listing'
									: ($props_upt.' Updated Listings');
			}

		if ($props_sold > 0)
			{
			$theTitle[]	= ($props_sold === 1)
									? '1 Sold Listing'
									: ($props_sold.' Sold Listings');
			}

		if ($props_open > 0)
			{
			$theTitle[]	= ($props_open === 1)
									? '1 Open House'
									: ($props_open.' Open Houses');
			}

		self::log(__FILE__, __FUNCTION__, __LINE__, 'title has '.count($theTitle).' items: '.implode(', ', $theTitle));

		return implode(', ', $theTitle);
		}
	private static function build_mult_link()
		{
		if (count(self::$all_updated_properties) < 2)
			return null;

		$mlnumbers = array();
		foreach (self::$all_updated_properties as $one_property)
			{
			$mlnumbers[] = $one_property['mlnumber'];
			}

		return ('mlnumber/'.implode(',', $mlnumbers));
		}
	private static function build_mult_desc()
		{
		$props_new		= 0;
		$props_upt		= 0;
		$props_sold		= 0;
		$props_open		= 0;
		$cities_new		= array();
		$cities_upt		= array();
		$cities_sold	= array();
		$cities_open	= array();
		$theDesc		= array();

		if (count(self::$all_updated_properties) < 2)
			return null;

		foreach (self::$all_updated_properties as $one_property)
			{
			if ($one_property['type'] == 'New')
				{
				$props_new++;
				$cities_new[] = $one_property['city'];
				}
			else if ($one_property['type'] == 'Price')
				{
				$props_upt++;
				$cities_upt[] = $one_property['city'];
				}
			else if ($one_property['type'] == 'Sold')
				{
				$props_sold++;
				$cities_sold[] = $one_property['city'];
				}
			else if ($one_property['type'] == 'OpenHouse')
				{
				$props_open++;
				$cities_open[] = $one_property['city'];
				}
			}

		$cities_new		= array_unique($cities_new);
		$cities_upt		= array_unique($cities_upt);
		$cities_sold	= array_unique($cities_sold);
		$cities_open	= array_unique($cities_open);

		if ($props_new > 0)
			{
			sort($cities_new);
			$theDesc[]	= ($props_new === 1)
								? ('1 New Listing in '.implode(',', $cities_new))
								: ($props_new.' New Listings in '.implode(',', $cities_new));
			}

		if ($props_upt > 0)
			{
			sort($cities_upt);
			$theDesc[]	= ($props_upt === 1)
								? ('1 Updated Listing in '.implode(',', $cities_upt))
								: ($props_upt.' Updated Listings in '.implode(',', $cities_upt));
			}

		if ($props_sold > 0)
			{
			sort($cities_sold);
			$theDesc[]	= ($props_sold === 1)
								? ('1 Sold Listing in '.implode(',', $cities_sold))
								: ($props_sold.' Sold Listings in '.implode(',', $cities_sold));
			}

		if ($props_open > 0)
			{
			sort($cities_open);
			$theDesc[]	= ($props_open === 1)
								? ('1 Open House in '.implode(',', $cities_open))
								: ($props_open.' Open Houses in '.implode(',', $cities_open));
			}

		return implode('<br>', $theDesc);
		}


	private static function log($file, $func, $line, $str)	{

		if ((isset($_GET[ROVER_DEBUG_KEY])) && (intval($_GET[ROVER_DEBUG_KEY]) > 0))
#		if (true)
			{
			error_log(
				sprintf( '[%s] %s <strong>%s</strong> %s: %s\n',
						get_site_url(),
						basename($file),
						$func,
						$line,
						$str
						)
				);
			}

		}
	}


?>