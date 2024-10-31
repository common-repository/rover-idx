<?php

class Rover_IDX_Content
	{
	public static $rover_html					= null;

	private static $rover_body_class			= null;
	private static $rover_title					= null;
	private static $rover_meta_desc				= null;
	private	static $rover_og_images				= null;
	private static $rover_meta_robots			= null;
	private static $rover_meta_keywords			= null;
	private	static $rover_canonical_url			= null;
	private static $rover_component				= null;
	private	static $rover_redirect				= null;

	private static $dynamic_sidebar				= null;

	public static $fetching_api_key				= false;
	private	static $endpoint_key				= "roveridx_endpoint";

	public static function setup_404()
		{
		global									$rover_idx;

		if (isset($rover_idx->roveridx_theming['wp_hook_mode']) && $rover_idx->roveridx_theming['wp_hook_mode']  == 'the_posts')
			add_filter( 'the_posts', 			[ ( 'Rover_IDX_Content' ), 'dynamic_page_the_posts' ] );
		else
			add_action( 'template_redirect',	[ ( 'Rover_IDX_Content' ), 'dynamic_page_template_redirect' ] );

		if (isset($rover_idx->roveridx_theming['wp_prevent_404_guess']) && $rover_idx->roveridx_theming['wp_prevent_404_guess']  == 'yes')
			add_filter('do_redirect_guess_404_permalink', '__return_false');
		}

	public static function dynamic_page_template_redirect()	{

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Starting');

		self::init_404_content();				#	do not return $post
		}

	public static function dynamic_page_the_posts($posts)	{

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Starting');

		remove_filter('the_posts',				[ ( 'Rover_IDX_Content' ), 'dynamic_page_the_posts' ] );	#	Avoid firing twice

		return self::init_404_content($posts);	#	return $post
		}

	private static function init_404_content($posts = null)
		{
		global									$wp, $wp_query, $rover_idx;

		$posts									= array();
		$component								= 'ROVER_COMPONENT_404';
		if (is_string($rover_idx->found_slug) && (substr($rover_idx->found_slug, 0, 6) === "rover-"))
			$component							= $rover_idx->found_slug;
		else if (is_string($rover_idx->found_slug) && (substr($rover_idx->found_slug, 0, 4) === "idx-"))
			$component							= $rover_idx->found_slug;

		$the_rover_content						= self::rover_content(	$component, array('region' => $rover_idx->rover_404_regions));

		self::$rover_html						= (isset($the_rover_content['the_html']))
														? $the_rover_content['the_html']
														: null;
		self::$rover_component					= $component;			#	$the_rover_content['the_component'];
		self::$rover_redirect					= (isset($the_rover_content['the_redirect']))
														? $the_rover_content['the_redirect']
														: null;

		self::setup_dynamic_meta($the_rover_content);

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'rover_component is ['.self::$rover_component.']');
		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, strlen(self::$rover_html).' bytes received from rover_content');
		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'redirect is ['.self::$rover_redirect.']');

		self::redirect_if_necessary();

		if (empty(self::$rover_html) && empty(self::$rover_redirect))
			{
			//	This is a real 404 - let WP do it's thing
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, '*** Not a Dynamic Page ***');

			status_header( 404 );
			$wp_query->is_404					= true;
			}
		else
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, '*** This is a Dynamic Page ***');

			status_header( 200 );
			$wp_query->is_404					= false;

			$posts								= array();
			$posts[]							= self::generate_404_content();

			//	Trick wp_query into thinking this is a page (necessary for wp_title() at least)
			//	Not sure if it's cheating or not to modify global variables in a filter
			//	but it appears to work and the codex doesn't directly say not to.

//				$wp_query->post					= $posts[0]->ID;
			$wp_query->post						= $posts[0];
			$wp_query->posts					= array( $posts[0] );

			$wp_query->queried_object			= $posts[0];
			$wp_query->queried_object_id		= $posts[0]->ID;

			$wp_query->is_rover_page			= true;		//	Used for domain '8'

			$wp_query->found_posts				= 1;
			$wp_query->post_count				= 1;
			$wp_query->max_num_pages			= 1;

			add_filter( 'template_include', [ ( 'Rover_IDX_Content' ), 'template_include' ], 99 );

			//	We want this to be a page - more flexible for setting templates dynamically

			$wp_query->is_page					= true;

			$wp_query->is_single				= false;	//	Applicable to Posts
			$wp_query->is_singular				= true;		//	Applicable to Pages

			$wp_query->is_attachment			= false;
			$wp_query->is_archive				= false;
			$wp_query->is_category				= false;
			$wp_query->is_tag					= false;
			$wp_query->is_tax					= false;
			$wp_query->is_author				= false;
			$wp_query->is_date					= false;
			$wp_query->is_year					= false;
			$wp_query->is_month					= false;
			$wp_query->is_day					= false;
			$wp_query->is_time					= false;
			$wp_query->is_search				= false;
			$wp_query->is_feed					= false;
			$wp_query->is_comment_feed			= false;
			$wp_query->is_trackback				= false;
			$wp_query->is_home					= false;
			$wp_query->is_embed					= false;
			$wp_query->is_404					= false;
			$wp_query->is_paged					= false;
			$wp_query->is_admin					= false;
			$wp_query->is_preview				= false;
			$wp_query->is_robots				= false;
			$wp_query->is_posts_page			= false;
			$wp_query->is_post_type_archive		= false;

			// Longer permalink structures may not match the fake post slug and cause a 404 error so we catch the error here
			unset($wp_query->query["error"]);
			$wp_query->query_vars["error"]		= "";

			$wp_query->is_404					= false;

			/* Update globals		*/
			$GLOBALS['wp_query']				= $wp_query;
			$wp->register_globals();
			}

		return $posts;
		}

	private static function generate_404_content()
		{
		global									$rover_idx;

		$the_guid_parts							= self::$rover_redirect;
		if (empty(self::$rover_redirect))
			{
			$the_guid_parts						= $rover_idx->curr_path;
			}

		//	If Rover is creating this content, tell WP to skip the annoying 'wpautop'
		//	function, which loves to wrap double line-breaks in <p> tags

		remove_filter( 'the_content', 'wpautop' );
		remove_filter( 'the_excerpt', 'wpautop' );

		$post									= new stdClass();

		$post->ID								= get_rover_post_id($rover_idx->roveridx_theming);
		$post->post_author						= get_current_user_id();

		//	The safe name for the post.  This is the post slug.

		$post->post_name						= (string) $rover_idx->rover_404_slugs;
		$post->post_type						= 'page';

		//	Not sure if this is even important.  But gonna fill it in anyway.

		$post->guid								= get_bloginfo("wpurl") . $the_guid_parts;

		if (empty($post->post_title) && !empty(self::$rover_title))
			$post->post_title					= self::$rover_title;

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Creating content for '.$rover_idx->rover_404_regions.' ('.strlen(self::$rover_html).' bytes) ['.$post->guid.']');

		#	For Rover dynamic pages - let Rover build the title / desc / canonical meta

		require_once 'rover-third-party.php';
		Rover_IDX_THIRDPARTY::filters();


		remove_action( 'wp_head', 'feed_links_extra', 3 );		#	Removes the links to the extra feeds such as category feeds
		remove_action( 'wp_head', 'feed_links', 2 );			#	Removes links to the general feeds: Post and Comment Feed
		remove_action( 'wp_head', 'rsd_link');					#	Removes the link to the Really Simple Discovery service endpoint, EditURI link
		remove_action( 'wp_head', 'wlwmanifest_link');			#	Removes the link to the Windows Live Writer manifest file.
		remove_action( 'wp_head', 'index_rel_link');			#	Removes the index link
		remove_action( 'wp_head', 'parent_post_rel_link');		#	Removes the prev link
		remove_action( 'wp_head', 'start_post_rel_link');		#	Removes the start link
		remove_action( 'wp_head', 'adjacent_posts_rel_link');	#	Removes the relational links for the posts adjacent to the current post.
		remove_action( 'wp_head', 'wp_generator');				#	Removes the WordPress version - WordPress 2.8.4
		remove_action( 'wp_head', 'rel_canonical' );			#	Remove default canonical url - which will be wrong

		add_filter( 'body_class', [ ( 'Rover_IDX_Content' ), 'body_class' ] );
		add_filter( 'the_title', function( $title, $id = null ) {

			global						$rover_idx;

			#	WordPress applies the_title filter twice
			#	- First as the corresponding post or page title.
			#	- Then as the Menu item title itself.  This happens in the Walker_Nav_Menu class.

			#	It is important that we ONLY affect the page title, and not the Nav title

			if (!is_null($id) && $id == $rover_idx->post_id)
				return strip_tags( self::$rover_title );

			return $title;
			} );

		add_filter( 'wp_head', [ ( 'Rover_IDX_Content' ), 'head_meta_items' ], 5 );

		add_filter( 'wp_robots', function( $robots ) {

			if (strpos(self::$rover_meta_robots, 'noindex') !== false)
				$robots['noindex'] = true;
			if (strpos(self::$rover_meta_robots, 'nofollow') !== false)
				$robots['nofollow'] = true;
			if (strpos(self::$rover_meta_robots, 'noarchive') !== false)
				$robots['noarchive'] = true;

  			return $robots;
			});

		$post->post_content						= self::$rover_html . self::formatted_debug();

		//	Fake post ID to prevent WP from trying to show comments for
		//	a post that doesn't really exist.

		$rover_idx->post_id						= get_rover_post_id($rover_idx->roveridx_theming);
		$post->ID								= $rover_idx->post_id;

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'is using post_id '.$post->ID);

		//	Static means a page, not a post.

		$post->post_status						= 'publish';
		$post->comment_status					= 'closed';
		$post->ping_status						= 'closed';		// self::ping_status;
		$post->filter							= 'raw';		// important!

		$post->comment_count					= 0;

		$post->post_date						= current_time('mysql');
		$post->post_date_gmt					= current_time('mysql', 1);

		$post									= new WP_Post( $post );

#		Save this post (by it's ID) for the duration of this request
#
#		https://developer.wordpress.org/reference/classes/wp_object_cache/
#		By default, the object cache is non-persistent. This means that data stored in the cache resides
#		in memory only and only for the duration of the request. Cached data will not be stored persistently
#		across page loads unless you install a persistent caching plugin.

		if (!is_rover_panel(self::$rover_component))
			wp_cache_add( $post->ID, $post, 'posts' );

		return $post;
		}

	public static function head_meta_items() {

		$meta_html						= array();

		if (!empty(self::$rover_title))
			$meta_html[]				= "<title>".strip_tags( self::$rover_title )."</title>";

		if (!empty(self::$rover_meta_desc))
			$meta_html[]				= "<meta name='description' content='".self::$rover_meta_desc."' />";

#		if (!empty(self::$rover_meta_robots))
#			$meta_html[]				= "<meta name='robots' content='".self::$rover_meta_robots."' />";

		if (!empty(self::$rover_meta_keywords))
			$meta_html[]				= "<meta name='keywords' content='".self::$rover_meta_keywords."' />";

		if (!empty(self::$rover_canonical_url))
			$meta_html[]				= "<link rel='canonical' class='rover idx' href='".self::$rover_canonical_url."' />";

		$meta_html[]					= "<meta name='generator' content='Rover IDX ".ROVER_VERSION."' />";

		if (!empty(self::$rover_title))
			$meta_html[]				= "<meta property='og:title' content='".strip_tags( self::$rover_title )."'>";
		if (!empty(self::$rover_meta_desc))
			$meta_html[]				= "<meta property='og:description' content='".self::$rover_meta_desc."'>";
		if (!empty(self::$rover_canonical_url))
			$meta_html[]				= "<meta property='og:url' content='".self::$rover_canonical_url."'>";

		foreach(explode(',', self::$rover_og_images) as $one_img)
			$meta_html[]				= "<meta property='og:image' content='".$one_img."'>";

		$meta_html[]					= "<meta property='og:updated_time' content='".date('Y-m-dTH:i:s+00:00')."' />";

		if (count($meta_html))
			{
			echo "\n\n<!-- This site is optimized with the Rover IDX plugin version ".ROVER_VERSION_FULL." - https://wordpress.org/plugins/rover-idx/ -->\n";
			echo implode("\n", $meta_html);
			echo "\n<!-- / Rover IDX meta items -->\n\n";
			}
		}


#	public static function wpseo_schema_webpage($data) {
#
#		$data['url']							= strtok($_SERVER["REQUEST_URI"],'?');	#	uri minus query string
#		$data['name']							= self::$rover_title;
#
#		return $data;
#		}

	public static function body_class($classes) {

		if (self::$rover_body_class)
			{
			if (is_array($classes))
				$classes[]						= self::$rover_body_class;
			else
				$classes						= array(self::$rover_body_class);
			}

		return $classes;
		}

	public static function update_site_settings($atts)	{

		if (is_array($atts) && count($atts))
			{
			$the_rover_content					= self::rover_content(
																'ROVER_COMPONENT_UPDATE_SITE_SETTINGS',
																$atts
																);
			}

		}

	public static function build_regions_string()	{

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'starting');

		$the_rover_content						= self::rover_content(
																'ROVER_COMPONENT_REBUILD_REGIONS',
																array('not-region' => 'Not used', 'not-regions' => 'Not Used')
																);

		$regions_str							= $the_rover_content['the_html'];

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'regions string ['.$regions_str.']');

		return $regions_str;
		}

	private static function get_api_key()	{

		global									$rover_idx;

		if ( self::$fetching_api_key )
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'already fetching');
			return null;
			}

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'starting');

		//	if necessary, fetch a new api key

		if (empty($rover_idx->roveridx_regions['api_key']))
			{
			self::$fetching_api_key				= true;

			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Fetching new API key');

			$the_rover_content					= self::rover_content(
																'ROVER_COMPONENT_GET_API_KEY',
																array('not-region' => 'Not used', 'not-regions' => 'Not Used')
																);

			$api_key							= $the_rover_content['the_html'];
//			$api_key							= json_decode($the_rover_content['the_html'], true);

			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Received new API key ['.$api_key.']');

			#	Unusual error path may case ROVER_COMPONENT_GET_API_KEY to return
			#	api_key=%3Cdiv+style%3D%22display%3Anone%22%3Ejson_decode%28%29+failed+%5B%3Cspan+style%3D%22color%3Ared%3B%22%3Erequest+%28%29+is+missing+required+parameters+%5Bcomponent%5D%3C%2Fspan%3E%5D%3C%2Fdiv%3E

			if (!empty($api_key) && (strpos($api_key, 'json_decode') === false))
				{
				if ($rover_idx->update_region_settings(__FUNCTION__, __LINE__, 'api_key', $api_key))
					rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Saved new API key to Region options');

				return $api_key;
				}
			}

		if (empty($rover_idx->roveridx_regions['api_key']))
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'failed');
			return null;
			}

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Returning ['.$rover_idx->roveridx_regions['api_key'].']');

		return $rover_idx->roveridx_regions['api_key'];
		}

	private static function check_js_version($rover_content)	{

		global									$rover_idx;

		if (isset($rover_content['the_js_ver']) && !empty($rover_content['the_js_ver']))
			{
			$newest_js_ver						= $rover_content['the_js_ver'];
			$current_js_ver						= (isset($rover_idx->roveridx_theming['js_version']))
														? $rover_idx->roveridx_theming['js_version']
														: ROVER_JS_VERSION;

			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, ' latest_js_ver ['.$newest_js_ver.'] / ['.$current_js_ver.']');

			if ((version_compare($newest_js_ver, $current_js_ver) !== 0))
				{
				$theme_opts						= get_option(ROVER_OPTIONS_THEMING);

				if (is_array($theme_opts))
					{
					$theme_opts['js_version']		= $newest_js_ver;
					update_option(ROVER_OPTIONS_THEMING, $theme_opts);

					$rover_idx->roveridx_theming	= $theme_opts;

					$rover_idx->refresh_css();
					}
				}
			}
		}

	private static function check_endpoint_resolve($rover_content)	{

		if (isset($rover_content['the_endpoint_resolve']) && !empty($rover_content['the_endpoint_resolve']))
			{
			$curr_endpoint_resolve				= get_option(self::$endpoint_key);

			if ($curr_endpoint_resolve === false)
				update_option(self::$endpoint_key, $rover_content['the_endpoint_resolve']);
			else if (strcmp($curr_endpoint_resolve, $rover_content['the_endpoint_resolve']) !== 0)
				update_option(self::$endpoint_key, $rover_content['the_endpoint_resolve']);
			}
		}

	private static function setup_dynamic_meta($the_rover_content = null)	{

		$robots									= array();

		if (!is_null($the_rover_content))
			{
			#	T I T L E
			if (isset($the_rover_content['the_title']) && !empty($the_rover_content['the_title']))
				self::$rover_title				= $the_rover_content['the_title'];

			#	D E S C R I P T I O N
			if (isset($the_rover_content['the_meta_desc']) && !empty($the_rover_content['the_meta_desc']))
				self::$rover_meta_desc			= $the_rover_content['the_meta_desc'];

			#	O G  I M A G E S
			if (isset($the_rover_content['the_og_images']))
				self::$rover_og_images			= $the_rover_content['the_og_images'];

			if (isset($the_rover_content['the_meta_attribs']) && is_array($the_rover_content['the_meta_attribs']))
				{
				#	B O D Y  C L A S S
				if (isset($the_rover_content['the_meta_attribs']['body_class']) && !empty($the_rover_content['the_meta_attribs']['body_class']))
					self::$rover_body_class		= $the_rover_content['the_meta_attribs']['body_class'];

				#	R O B O T S
				if (isset($the_rover_content['the_meta_attribs']['robots_noindex']) && ($the_rover_content['the_meta_attribs']['robots_noindex'] == 1))
					$robots[]					= 'noindex';
				if (isset($the_rover_content['the_meta_attribs']['robots_nofollow']) && ($the_rover_content['the_meta_attribs']['robots_nofollow'] == 1))
					$robots[]					= 'nofollow';
				if (isset($the_rover_content['the_meta_attribs']['robots_noarchive']) && ($the_rover_content['the_meta_attribs']['robots_noarchive'] == 1))
					$robots[]					= 'noarchive';

				if (count($robots))
					self::$rover_meta_robots	= implode(', ', $robots);

				#	C A N O N I C A L  U R L
				if (isset($the_rover_content['the_meta_attribs']['canonical_url']) && !empty($the_rover_content['the_meta_attribs']['canonical_url']))
					self::$rover_canonical_url	= $the_rover_content['the_meta_attribs']['canonical_url'];

				#	K E Y W O R D S
				if (isset($the_rover_content['the_meta_attribs']['keywords']) && !empty($the_rover_content['the_meta_attribs']['keywords']))
					self::$rover_meta_keywords= $the_rover_content['the_meta_attribs']['keywords'];
				}

			}
		}

	private static function page_template_set($page_template)
		{
		global									$rover_idx;

		if (
			(isset($rover_idx->roveridx_theming[$page_template]))	&&
			(!empty($rover_idx->roveridx_theming[$page_template]))
			)
			return true;

		return false;
		}

	public static function template_include($template)
		{
		global									$rover_idx;

		$path_to_template						= array();
		$template_exists						= false;
		$html_fragment							= substr(self::$rover_html, 0, 100);

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Component is '.self::$rover_component.' ['.$html_fragment.']');

		$page_template							= @$rover_idx->roveridx_theming['template'];

		if (in_array(
					self::$rover_component,
					array(
						'rover-control-panel',
						'rover-custom-listing-panel',
						'rover-login-panel'
						)
					)
				)
			{
			$path_to_template[]					= ROVER_IDX_PLUGIN_PATH . 'templates/naked_page.php';
			}
		else if ((strcmp($page_template, 'rover-naked') === 0) || (is_array($_GET) && isset($_GET['print'])))
			{
			//	User is printing page - Retrieve stripped template from Rover
			$path_to_template[]					= ROVER_IDX_PLUGIN_PATH . 'templates/naked_page.php';
			}
		else
			{
			if (strpos($html_fragment, 'rover-prop-detail-framework') !== false && self::page_template_set('property_template'))
				{
				$path_to_template[]				= get_stylesheet_directory() . '/' . $rover_idx->roveridx_theming['property_template'];
				$path_to_template[]				= get_template_directory() . '/' . $rover_idx->roveridx_theming['property_template'];

				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Recommending template ['.$rover_idx->roveridx_theming['property_template'].']');
				}
			else if (strpos($html_fragment, 'rover-market-conditions-framework') !== false && self::page_template_set('mc_template'))
				{
				$path_to_template[]				= get_stylesheet_directory() . '/' . $rover_idx->roveridx_theming['mc_template'];
				$path_to_template[]				= get_template_directory() . '/' . $rover_idx->roveridx_theming['mc_template'];

				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Recommending template ['.$rover_idx->roveridx_theming['mc_template'].']');
				}
			else if (strpos($html_fragment, 'rover-report-framework') !== false && self::page_template_set('rep_template'))
				{
				$path_to_template[]				= get_stylesheet_directory() . '/' . $rover_idx->roveridx_theming['rep_template'];
				$path_to_template[]				= get_template_directory() . '/' . $rover_idx->roveridx_theming['rep_template'];

				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Recommending template ['.$rover_idx->roveridx_theming['rep_template'].']');
				}
			else if (strpos($html_fragment, 'rover-agent-framework') !== false && self::page_template_set('agent_detail_template'))
				{
				$path_to_template[]				= get_stylesheet_directory() . '/' . $rover_idx->roveridx_theming['agent_detail_template'];
				$path_to_template[]				= get_template_directory() . '/' . $rover_idx->roveridx_theming['agent_detail_template'];

				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Recommending template ['.$rover_idx->roveridx_theming['agent_detail_template'].']');
				}
			}

		if ($path_to_template == array() && self::page_template_set('template'))
			{
			$path_to_template[]					= get_stylesheet_directory() . '/' . $rover_idx->roveridx_theming['template'];
			$path_to_template[]					= get_template_directory() . '/' . $rover_idx->roveridx_theming['template'];
			}

		foreach($path_to_template as $one_path)
			{
			if (file_exists($one_path))
				{
				/*	Success!	*/
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Setting template to ['.$one_path.']');
				return $one_path;
				}
			}


		/*	Page templates are not set in Styling >> Quick Start.  Use default theme page template	*/

		$path_to_template						= null;
		$path_to_template						= get_page_template();

		if (!file_exists($path_to_template))
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Default template ['.$path_to_template.'] not found.  Giving up.');
			return $template;
			}

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Using template ['.$path_to_template.']');

		if (!empty($path_to_template) && file_exists($path_to_template))
			{
			//	We don't want to go down this path every time, just because the website designer
			//	hasn't selected a 'template' page.  So set it, and let them change it if they ever
			//	get around to it.

			if (!is_array($current_theme_options = get_option(ROVER_OPTIONS_THEMING)))
				$current_theme_options			= array();
			$current_theme_options['theme']		= 'unused';

			update_option(ROVER_OPTIONS_THEMING, $current_theme_options );

			$rover_idx->roveridx_theming		= $current_theme_options;

			return $path_to_template;
			}
		else
			{
			if (empty($path_to_template))
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Fatal error: [path_to_template] is empty!');
			if (file_exists($path_to_template))
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Fatal error: ['.$path_to_template.'] does not exist!');
			}

		return $template;
		}

	private static function redirect_if_necessary()
		{
		if (!empty(self::$rover_redirect))
			{
			#	This is a non-active listing page, and a crawler is the requestor.
			#	We can redirect to the Home page, or a 404 page.  Simply doing nothing
			#	will fall through to the 404 page.

			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'rover_redirect is ['.self::$rover_redirect.'] ');

			if (self::$rover_redirect == "404")
				{
				return true;												#	Redirect to 404 page
				}
			else if (self::$rover_redirect == "home")
				{
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Redirecting to '.get_site_url());

				wp_redirect( get_site_url(), 301 );							#	Redirect to 'Home' page
				exit;
				}
			else 		//	specific
				{
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Redirecting to '.self::$rover_redirect);

				$redirect_type				= (strpos(self::$rover_redirect, "rover-login-panel") !== false)
													? 302
													: 301;

				wp_redirect( self::$rover_redirect, $redirect_type );		#	Redirect to specific page
				exit;
				}
			}

		return false;
		}

	private static function translate_component($component)	{

		global									$rover_idx;

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, ' ['.$component.']');

		if ($component == 'ROVER_COMPONENT_404')
			{
			//	For certain types of pages, skip the 404 engine

			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, ' Comparing ['.$rover_idx->rover_404_slugs.'] with [agent-detail]');
			if ($rover_idx->rover_404_slugs == 'agent-detail')
				{
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, ' ['.$rover_idx->rover_404_slugs.'] returning [ROVER_COMPONENT_AGENT_DETAIL_PAGE]');
				return 'ROVER_COMPONENT_AGENT_DETAIL_PAGE';
				}
			}

		return $component;
		}

	private static function formatted_debug()		{

		global									$rover_idx;

		if (is_array($rover_idx->debug_html) && count($rover_idx->debug_html))
			{
			return '<div class="rover-debug-html" style="display:none;"><div>'.implode('<br>', $rover_idx->debug_html).'</div></div>';
			}

		return null;
		}

	public static function rover_content($component, $atts = null)	{

		global									$rover_idx, $post, $wp_version;

		$page									= (isset($post))
														? $post->ID
														: get_rover_post_id($rover_idx->roveridx_theming);
		$api_key								= self::get_api_key();
		$post_str								= null;

		$vars_array								= array(
													'component'			=>	self::translate_component($component),
													'is_wp'				=>	true,
													'signature'			=>	'67d14e7729d3a8446ebf5e5e97f684db',
													'cookies'			=>	self::cookies(),
													'domain_id'			=>	$rover_idx->roveridx_regions['domain_id'],
													'domain'			=>	get_site_url(),
													'page'				=>	$page,
													'api_key'			=>	$api_key,
													'user_agent'		=>	((isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : null),
													'user_ip'			=>	((isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : null),
													'server_ip'			=>	((isset($_SERVER['SERVER_ADDR'])) ? $_SERVER['SERVER_ADDR'] : null),
													'path_url'			=>	$rover_idx->curr_path,
													'query_url'			=>	http_build_query($_REQUEST),
													'is_amp'			=>	$rover_idx->is_amp(),
													'force_crawler'		=>	intval(@$_GET['crawler']),					//	'?crawler=1'
													'wp_permalinks'		=>	get_option('permalink_structure'),
													'version_php'		=>	phpversion(),
													'version_wp'		=>	$wp_version
													);

		if ( is_user_logged_in() )
			{
			$current_user						= wp_get_current_user();
			$guid								= get_user_meta($current_user->ID, 'rover_guid', $single = true);
			if (!empty($guid))
				$vars_array['guid']				= $guid;
			}

		$atts									= (is_array($atts))
														? array_merge($atts, $vars_array)
														: $vars_array;

		//	If no 'region' parameter is specified in shortcode, assume the all `regions` in roveridx_regions
		if (!isset($atts['region']) || empty($atts['region']))
			{
			$atts['region']						= implode(',', array_keys($rover_idx->all_selected_regions));

			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'no `region` specified - using  ['.$atts['region'].']');

			if (!isset($atts['region']))
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, '[key does not exist] Forcing region to '.$atts['region']);
			if (empty($atts['region']))
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, '[empty] Forcing region to '.$atts['region']);
			}

		if (isset($_GET[ROVER_DEBUG_KEY]) === true)
			{
			$atts[ROVER_DEBUG_KEY]				= intval($_GET[ROVER_DEBUG_KEY]);
			}

		if (rover_idx_is_debuggable())
			{
			if (is_array($atts))
				{
				foreach ($atts as $atts_key => $atts_val)
					{
					rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, $atts_key.' => '.print_r($atts_val, true));
					}
				}

	//		$btd = debug_backtrace();
	//		$btd_str = null;
	//		foreach ($btd as $btdKey => $btdVal)
	//			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, '['.$btdKey.'] File: '.$btdVal['file'].' / Function: '.$btdVal['function'].' / Line: '.$btdVal['line']);
			}

		if (self::is_local_component($component))
			{
			$ret_data							= self::local_content($component);
			}
		else
			{
			$post_str							= http_build_query($atts);

			$url								= sprintf("https://ep3.roveridx.com/%s/php/request.php", ROVER_VERSION);

			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, $url);
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, $post_str);

			if (self::test_for_modsec($post_str))
				{
				return array(
							'the_html'	=> '<div style="color:red;margin:40px auto;text-align:center;">This request appears to be an attempt at SQL injection attacks, cross-site scripting, or a path traversal attacks.</div>'
							);
				}

			$curl_timeout						= (in_array($component, array('rover-seo-regenerate-sitemap', 'rover-report-panel')))
														? 120
														: 60;

			$curl_start							= microtime(true);

			$ch									= curl_init();
			$ch_opts							= array(
														CURLOPT_URL				=> $url,
														CURLOPT_RETURNTRANSFER	=> true,
														CURLOPT_CONNECTTIMEOUT	=> 30,

														CURLOPT_TIMEOUT			=> $curl_timeout,
														CURLOPT_HTTPHEADER		=> array(
																						'Content-Type: application/x-www-form-urlencoded',
																						'Content-Length: '.strlen($post_str)
																						),
														CURLOPT_POST			=> true,
														CURLOPT_POSTFIELDS		=> $post_str,
														CURLOPT_FAILONERROR		=> true
														);

#			Ticket #: 11553745
#			Even with CURLOPT_TCP_FASTOPEN defined, we do not know if net.ipv4.tcp_fastopen is enabled

#			if (defined('CURLOPT_TCP_FASTOPEN'))
#				{
#				$fastopen_enabled				= ini_get('net.ipv4.tcp_fastopen');
#
#				if (intval($fastopen_enabled) > 0)
#					{
#					rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'net.ipv4.tcp_fastopen is '.$fastopen_enabled);
#					$ch_opts[CURLOPT_TCP_FASTOPEN]	= 1;
#					}
#				}

			if (defined('CURLOPT_RESOLVE'))
				{
				if (($curr_endpoint_resolve = get_option(self::$endpoint_key)) !== false) {
					if (strpos($curr_endpoint_resolve, ":") !== false)
						$ch_opts[CURLOPT_RESOLVE]	= array($curr_endpoint_resolve);
					}
				}

			#	https://php.watch/articles/curl-php-accept-encoding-compression
			#	https://zachrussell.net/tools/brotli-test/
			#	Ticket #17089153 - Litespeed settings blocking connection to plugin

			if (defined('CURLOPT_ENCODING'))
				{
				#	Ticket #: 17089153
				#	Error # 23 - "Unable to communicate with server. Failed writing received data to disk/application"

				$ch_opts[CURLOPT_ENCODING]		= (isset($rover_idx->roveridx_regions['forced_encoding']))
														? $rover_idx->roveridx_regions['forced_encoding']
														: "";		#	deflate, gzip, br, zstd
				}

			if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4'))
				$ch_opts[CURLOPT_IPRESOLVE]		= CURL_IPRESOLVE_V4;

			curl_setopt_array( $ch, $ch_opts );

			$ret_data							= curl_exec($ch);
			$curl_errno							= curl_errno($ch);
			$curl_error							= curl_error($ch);

			$curl_timers						= curl_getinfo($ch);

			curl_close ($ch);

			$curl_stop							= microtime(true);
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'curl took ['.floatval(round($curl_stop - $curl_start, 5)).'] seconds');

			if ($curl_errno > 0)
				{
				return self::return_communication_error($curl_errno, $curl_error);
				}
			}

		$rover_content							= json_decode($ret_data, true);

		if (is_null($rover_content))
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'json_decode() failed on ['.$ret_data.']');

			self::add_content_error('['.$component.'] json_decode('.__LINE__.') failed ['.$ret_data.']');
			self::add_content_error('<div data-post_str>'.$post_str.'</div>');

			if (self::is_local_component($component))
				self::add_content_error('<div>is_local_component()</div>');

#			foreach($atts as $v_key => $v_val)
#				self::add_content_error('<div data-atts> ['.$v_key.'] ['.$v_val.']</div>');

#			foreach($vars_array as $v_key => $v_val)
#				self::add_content_error('<div data-vars_array> ['.$v_key.'] ['.$v_val.']</div>');

			$rover_content						= array();
			$rover_content['the_html']			= $ret_data;
			}
		else
			{
			$rover_content['the_html']			= str_replace('ROVER_DYNAMIC_SIDEBAR', self::$dynamic_sidebar, $rover_content['the_html']);

			self::$rover_og_images				= null;
			if (isset($rover_content['the_og_images']) && !empty($rover_content['the_og_images']))
				self::$rover_og_images			= $rover_content['the_og_images'];

			if (true)
				self::dump_curl_timers($curl_timers);

			if (rover_idx_is_debuggable())
				{
				if (
					is_array($rover_content) &&
					isset($rover_content['the_html']) &&
					is_string($rover_content['the_html']) &&
					($the_html_len = strlen($rover_content['the_html'])) > 0
					)
					{
					rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'the_html is '.$the_html_len.' bytes');
					rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'the_og_images are '.strlen($rover_content['the_og_images']).' bytes');
					}
				else
					{
					rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'the_html is zero bytes');
					}
				}

			self::check_js_version($rover_content);
			self::check_endpoint_resolve($rover_content);
			}

		return $rover_content;
		}

	private static function add_curl_content_error($opt)
		{
		global									$rover_idx;

		$rover_idx->debug_html[]				= '<div data-'.$opt.'>'.$opt.' is not defined on this server</div>';
		}

	private static function add_content_error($str)
		{
		global									$rover_idx;

		$rover_idx->debug_html[]				= '<div data-str>'.$str.'</div>';
		}

	private static function dump_curl_timers($curl_timers)
		{
		if (false)
			{
			foreach($curl_timers as $curl_key => $curl_val)
				{
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'curl_timers ['.$curl_key.'] => ['.((is_array($curl_val)) ? implode(',', $curl_val): $curl_val).'] seconds');
				}
			}

		if (true)
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'curl_timer summary ********');

			$the_sum							= 0;
			foreach(array('namelookup_time_us', 'connect_time_us', 'appconnect_time_us', 'pretransfer_time_us', 'redirect_time_us', 'starttransfer_time_us') as $key)
				{
				$the_time						= (isset($curl_timers[$key]))
														? ($curl_timers[$key] / 1000000)
														: 0;
				$the_sum						+= $the_time;

				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'curl_timers ['.str_pad($key, 22).'] => ['.str_pad($the_time, 8).'] seconds');
				}

			if (isset($curl_timers['total_time_us']))
				{
				$total_time_us					= $curl_timers['total_time_us'] / 1000000;
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'curl_timers ['.str_pad('total_time_us', 22).'] => ['.str_pad($total_time_us, 8).'] seconds');
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'curl_timers ['.str_pad('*_time_us adds up to', 22).'] => ['.str_pad($the_sum, 8).'] seconds');
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'curl_timers ['.str_pad('unaccounted for', 22).'] => ['.str_pad($total_time_us - $the_sum, 8).'] seconds');
				}
			}
		}

	private static function cookies()
		{
		$the_cookies							= array();

		if (isset($_COOKIE) && is_array($_COOKIE) && count($_COOKIE))
			{
			$rover_cookie_key					= 'rover_';
			$len								= strlen($rover_cookie_key);
			foreach ($_COOKIE as $key => $value)
				{
				$sub							= substr($key, 0, $len);
				if (strcasecmp($rover_cookie_key, $sub) === 0)
					{
					$the_cookies[]				= $key.'='.urlencode($value);
					}
				}
			}

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, ' is '.implode(';', $the_cookies));

		return implode(';', $the_cookies);
		}

	private static function return_communication_error($curl_errno, $curl_error)	{

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'curl_exec error ['.$curl_errno.']: '.$curl_error);

		#	https://curl.se/libcurl/c/libcurl-errors.html

		$the_html								= array();
		$the_html[]								= '<div style="color:red;margin:40px auto;text-align:center;">';
		$the_html[]								=	'<svg style="height:100px;width:100px;display:block;margin:0 auto;text-align:center;" aria-hidden="true" focusable="false" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" class="bolt">';
		$the_html[]								=		'<path fill="currentColor" d="M377.8 167.9c-8.2-14.3-23.1-22.9-39.6-22.9h-94.4l28.7-87.5c3.7-13.8.8-28.3-7.9-39.7C255.8 6.5 242.5 0 228.2 0H97.7C74.9 0 55.4 17.1 52.9 37.1L.5 249.3c-1.9 13.8 2.2 27.7 11.3 38.2C20.9 298 34.1 304 48 304h98.1l-34.9 151.7c-3.2 13.7-.1 27.9 8.6 38.9 8.7 11.1 21.8 17.4 35.9 17.4 16.3 0 31.5-8.8 38.8-21.6l183.2-276.7c8.4-14.3 8.4-31.5.1-45.8zM160.1 457.4L206.4 256H47.5L97.7 48l127.6-.9L177.5 193H334L160.1 457.4z" class=""></path>';
		$the_html[]								=	'</svg>';
		$the_html[]								=	'['.$curl_errno.'] - Unable to communicate with server.  '.curl_strerror($curl_errno).'<br><br>';
		$the_html[]								=	'<div style="display:none">';
		$the_html[]								=		'<div class="php-version">'.print_r(phpversion(), true).'</div>';
		$the_html[]								=		'<div class="php-info">'.ob_start();phpinfo();print(ob_end_clean()).'</div>';
		$the_html[]								=		'<div class="curl-version">'.print_r(curl_version(), true).'</div>';
		$the_html[]								=	'</div>';
		$the_html[]								= '</div>';

		if ($curl_errno == 23)
			{
			#	Ticket #: 17089153
			#	Error # 23 - "Unable to communicate with server. Failed writing received data to disk/application"
			#
			#	This webhost does not correctly support brotli, even though they passed:
			#		'Accept-Encoding:		gzip, deflate, br, zstd'
			#	And our LiteSpeed server wants to encode using brotli
			#
			#	BlueHost seems to be a common host with this issue.
			#
			#	So we will retry, but save a setting that this cause this site to force 'deflate' as the CURLOPT_ENCODING

			if (is_array($rr = get_option(ROVER_OPTIONS_REGIONS)))
				{
				if (!isset($rr['forced_encoding']))
					{
					$rr['forced_encoding']		= 'deflate';

					update_option(ROVER_OPTIONS_REGIONS, $rr);

					$rover_idx->roveridx_regions	= get_option(ROVER_OPTIONS_REGIONS);
					}
				}
			}

		return array(
					'the_html'	=> implode('', $the_html)
					);
		}

	private static function is_local_component($component)		{

		if (in_array(
					$component,
					array(
						'rover-debug-page'
						)
					))
			{
			return true;		//	Force 'remote' for WP Plugin setup panels
			}

		return false;
		}

	private static function local_content($component)		{

		global									$rover_idx;

		$the_html								= array();
		$the_title								= null;
		$the_meta_desc							= null;
		$the_meta_attribs						= null;

		switch($component)
			{
			case 'rover-debug-page':
				$the_title						= 'Rover Debug Page';

				$the_html[]						= '<h3>Region settings</h3>';
				foreach($rover_idx->roveridx_regions as $key => $val)
					$the_html[]					= '['.$key.'] => ['.$val.']';

				$the_html[]						= '<h3>Theme settings</h3>';
				foreach($rover_idx->roveridx_theming as $key => $val)
					$the_html[]					= '['.$key.'] => ['.$val.']';

				$the_html[]						= '<h3>Regions</h3>';
				foreach($rover_idx->all_selected_regions as $key => $val)
					$the_html[]					= '['.$key.'] => ['.$val.']';

				$curr_theme						= wp_get_theme();
				$the_html[]						= '<h3>Templates for ['.$curr_theme->get('Name').'] ['.$curr_theme->get('ThemeURI').'] ['.$curr_theme->get('Version').']</h3>';
				foreach($curr_theme->get_page_templates() as $key => $val)
					$the_html[]					= '['.$key.'] => ['.$val.']';
				break;
			}

		return json_encode( array(
					'the_html'					=> '<div style="margin:100px 20%;">'.implode('<br>', $the_html).'</div>',
					'the_title'					=> $the_title,
					'the_meta_desc'				=> $the_meta_desc,
					'the_meta_attribs'			=> $the_meta_attribs
					));
		}

	private static function test_for_modsec($post_str)
		{
		/*	Test for modsec	*/

		$pattern								= "(insert[[:space:]]+into.+values|select.*from.+[a-z|A-Z|0-9]|select.+from|bulk[[:space:]]+insert|union.+select|convert.+\\\\(.*from))";

		if (preg_match($pattern, $post_str) == 1)
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'security alert!');
			return true;

			wp_mail("info@roveridx.com",
					get_site_url().': post_str will trigger modsec',
					$post_str);
			}

		return false;
		}
	}

?>