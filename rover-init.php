<?php

require_once ROVER_IDX_PLUGIN_PATH.'rover-shared.php';
require_once ROVER_IDX_PLUGIN_PATH.'rover-common.php';
require_once ROVER_IDX_PLUGIN_PATH.'rover-content.php';
#require_once ROVER_IDX_PLUGIN_PATH.'rover-custom-post-types.php';
require_once ROVER_IDX_PLUGIN_PATH.'rover-version.php';


class Rover_IDX
	{
	public $roveridx_regions		= null;
	public $roveridx_theming		= null;
	public $curr_path				= null;
	public $is_debuggable			= null;
	public $debug_html				= array();
	public $all_selected_regions	= null;
	public $post_id					= null;

	public	$rover_404_regions		= null;
	public	$rover_404_slugs		= null;
	public	$found_slug				= null;

	private	$first_selected_region	= null;
	private	$bootstrap_ready		= null;

	function __construct() {

		global						$wp;

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, __CLASS__);

		if (!is_array($this->roveridx_regions = get_option(ROVER_OPTIONS_REGIONS)))
			$this->roveridx_regions						= array();
		if (!is_array($this->roveridx_theming = get_option(ROVER_OPTIONS_THEMING)))
			$this->roveridx_theming						= array();
		$this->curr_path								= (empty($wp->request))
																? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
																: $wp->request;
		$this->curr_path_parts							= array_filter(explode('/', $this->curr_path));

		if (!isset($this->roveridx_regions['domain_id']))
			$this->roveridx_regions['domain_id']		= null;
		if (!isset($this->roveridx_regions['regions']))
			$this->roveridx_regions['regions']			= null;

		add_action( 'wp_enqueue_scripts', 				'roveridx_css_and_js', 99 );		//	load late
//		add_action( 'wp_print_footer_scripts', 			'roveridx_css_and_js_footer' );

		add_action( 'init',								array($this,	'rewrite_rules'));
		add_action(	'wp_footer',						array($this,	'add_login_button'));
		add_filter( 'wp_nav_menu_items',				array($this,	'add_login_menu'), 10, 2 );

		add_filter( 'script_loader_tag',				array($this,	'add_attributes_to_script'), 10, 3 );

		add_action(	'do_robots', 						array($this,	'robots'), 100, 0);
		add_action( 'wp_head',							array($this,	'load_css_and_preload'));
		add_action( 'wp_footer',						array($this,	'load_js'));

		add_action( 'admin_head',						array($this,	'load_css_and_preload'));
		add_action( 'admin_footer',						array($this,	'load_js'));

		add_action( 'roveridx_cron_hourly',				array($this,	'cron_hourly'));
		add_action( 'roveridx_cron_daily',				array($this,	'cron_daily'));

		add_action( 'rest_api_init',					array($this,	'rest_endpoints') );

		add_action( 'wp_ajax_idx_site_posts',			array($this,	'idx_site_posts_callback') );
		add_action( 'wp_ajax_nopriv_idx_site_posts',	array($this,	'idx_site_posts_callback') );

		add_filter( 'rocket_preload_exclude_urls', function( $regexes, $url ) {

			#	WP Rocket
			#	Preloading of pages or links, especially in large MLS's, cause tens of thousands of
			#	page requests every few hours.  Prohibit these dynamic pages from preloading.

			$permalink_structure						= get_option('permalink_structure');
			$url_ends_in_slash							= (is_null($permalink_structure) && substr($permalink_structure, -1) != '/')
																? false
																: true;

			foreach(array_unique(array_map('strtolower', $this->all_selected_regions)) as $one_state)
				{
				$url_to_compare							= ($url_ends_in_slash)
																? get_site_url().$one_state
																: get_site_url().'/'.$one_state;
				if (substr($url, 0, strlen($url_to_compare)) == $url_to_compare)
					$regexes[]							= $url;
				}

			});

/*
add_action('amp_post_template_css','ampforwp_add_custom_css_example', 11);

function ampforwp_add_custom_css_example() {

	'body {
		background: red;
	}';
}
*/



//		add_filter( 'language_attributes',				array($this, 'fbml_add_namespace'));
//		add_filter( 'opengraph_type', 					array($this, 'fb_og_type' ));

		if ( !defined( 'WP_INSTALLING' ) || WP_INSTALLING === false )
			{
			$this->all_selected_regions					= $this->get_selected_regions();

			if ( is_admin() )
				{
				add_action( 'plugins_loaded',			array($this, 'init_admin'), 15 );
				}
			else
				{
				add_action( 'plugins_loaded',			array($this, 'init_front'));
				}

			if (is_admin() && (!defined( 'DOING_AJAX' ) || ! DOING_AJAX))
				{
				if (isset($this->roveridx_regions['redirect_for_setup']))
					{
					if ( true === $this->roveridx_regions['redirect_for_setup'] )
						add_action( 'init', array( $this, 'redirect_to_setup' ) );
					}
				}
			}
		else
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'WP_INSTALLING is true - skipping init_admin() and init_front()' );
			}
		}

	public function get_first_region()	{
		return $this->first_selected_region;
		}

	public function refresh_css()	{

		$the_content						= Rover_IDX_Content::rover_content('rover-default-css');
		if (!empty($the_content['the_html']) && strpos($the_content['the_html'], 'request blocked') === false)
			update_option(ROVER_OPTIONS_CSS_DEFAULT, $the_content['the_html'], true);

		$the_content						= Rover_IDX_Content::rover_content('rover-amp-css');
		if (!empty($the_content['the_html']) && strpos($the_content['the_html'], 'request blocked') === false)
			update_option(ROVER_OPTIONS_CSS_AMP, $the_content['the_html'], true);
		}

	public function admin_notice_upgraded()	{

		$class 			= 'notice notice-info rover-notice is-dismissible';
		$message		= 'Rover IDX has been upgraded to '.ROVER_VERSION.'.';

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
		}

	public function rewrite_rules() {

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'add_rewrite_rules ');

		global			$rover_idx;

		if (defined('TRP_PLUGIN_DIR'))
			{
			if ($this->check_url_for_idx_keys() !== false)
				{
				add_action('trp_before_running_hooks', function($trp_loader) {
						$trp_loader->remove_hook( 'init', 'create_gettext_translated_global' );
						$trp_loader->remove_hook( 'shutdown', 'machine_translate_gettext' );
						}, 10, 2);
				}
			}

		$rover_post_id	= get_rover_post_id($this->roveridx_theming);

		foreach(array_unique(array_map('strtolower', $this->all_selected_regions)) as $one_state)
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'add_rewrite_rules: adding ['.$one_state.']');

			add_rewrite_rule( '^(.*)/'.$one_state.'/?', 'index.php?p='.$rover_post_id, 'top' );		/*	singlefamily/ma	*/
			add_rewrite_rule( '^'.$one_state.'/(.*)/?', 'index.php?p='.$rover_post_id, 'top' );		/*	ma/brewster		*/
			}
		}

	public function add_login_button()	{

		require_once ROVER_IDX_PLUGIN_PATH.'rover-menus.php';

		$Rover_IDX_Menus			= new Rover_IDX_Menus();

		echo $Rover_IDX_Menus->add_login_button();
		}

	public function add_login_menu($items, $args)	{

		require_once ROVER_IDX_PLUGIN_PATH.'rover-menus.php';

		$Rover_IDX_Menus			= new Rover_IDX_Menus();

		return $Rover_IDX_Menus->add_login_menu($items, $args);
		}

	public function fbml_add_namespace( $output ) {

		//	Does not W3C validate

		$output .= ' xmlns:fb="' . esc_attr(ROVERIDX_FBML_NS_URI) . '"';

		return $output;
		}

	public function fb_og_type( $type ) {
		if (is_singular())
			$type = "article";
		else
			$type = "blog";
		return $type;
		}

	public function init_admin()
		{
		$this->upgrade_options();

		add_action( 'update_option_permalink_structure' , array($this, 'permalinks_have_been_updated'), 10, 2 );

		require_once ROVER_IDX_PLUGIN_PATH.'admin/rover-admin-init.php';
		}

	public function init_front()
		{
		global						$wp, $post;

		$this->upgrade_options();

		$http_accept				= (isset($_SERVER['HTTP_ACCEPT']))
											? strtolower($_SERVER['HTTP_ACCEPT'])
											: "";
		$is_valid_request			= (strpos($http_accept, "text/html") === false && strpos($http_accept, "*/*") === false)
											? false
											: true;
		$is_valid_request			= true;
		$the_page_clean				= preg_replace('/[^a-zA-Z0-9]/', '', $this->curr_path);

		//	We don't seem to have access to $post->ID this early.  So we have to test if the page
		//	exists manually.  If it does exist, DO NOT EXECUTE the 404 code.

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Starting... [php version '.phpversion().']');
		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'REQUEST_URI ['.$_SERVER["REQUEST_URI"].']');
		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'parsed REQUEST_URI ['.$this->curr_path.']');
		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'the_page_clean ['.$the_page_clean.']');

//		foreach (debug_backtrace() as $btdKey => $btdVal)
//			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, '['.$btdKey.'] File: '.$btdVal['file'].' / Function: '.$btdVal['function'].' / Line: '.$btdVal['line']);


		$the_page					= get_page_by_path($this->curr_path, OBJECT, ['page','post']);
		if (!is_null($the_page))
			{
			//	This page may contain one or more shortcodes

			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'path ['.$this->curr_path.'] exists in WP as page ['.$the_page->ID.']');
			}
		else if (empty($the_page_clean))
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'path ['.$this->curr_path.'] always exists and is not a 404');
			}
		else if (($part = $this->url_contains_troll_url_parts()) !== false)
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'path ['.$this->curr_path.'] contains troll part ['.$part.']');
			}
		else
			{
			if ($is_valid_request)
				{
				global							$wp_version;

				if ($this->check_url_for_idx_keys() !== false)
					{
					global						$wp_query;

					if ($this->url_should_avoid_cache())
						nocache_headers();

					#	Contact 7 bug:
					#	https://wordpress.org/support/topic/new-block-editor-php-code-errors-with-404/

					if ( defined( 'WPCF7_PLUGIN_DIR' ) )	{
						remove_action( 'init', 'wpcf7_init_block_editor_assets' );
						}

					if ($wp_query && isset($wp_query->query_vars) && is_array($wp_query->query_vars))
						$wp_query->query_vars["error"]	= "";							#	Make sure this is not set to 404 until after we've checked for dynamic

					Rover_IDX_Content::setup_404();

					if ($wp && isset($wp->query_vars))
						$wp->query_vars			= array('post_type' => 'page');

					if (version_compare($wp_version, '6.0.0') >= 0)
						{
						#	WordPress 6 `do_parse_request` behaves differently, probably
						#	due to all the performance work they implemented.

						rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, ' WordPress 6.0.0 detected, skipping do_parse_request');
						}
					else
						{
						add_filter('do_parse_request', function($do_parse, $wp) {		#	Skip parse_request(), which may send an early 404 header

							$wp->query_vars			= array('post_type' => 'page');		#	https://roots.io/routing-wp-requests/

							return false;

							}, 10, 2);
						}
					}

				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'path ['.$this->curr_path.'] does not exist in WP');
				}
			else
				{
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'This REQUEST is looking for an image - ignore!');
				}
			}

		require_once ROVER_IDX_PLUGIN_PATH.'rover-shortcodes.php';
		require_once ROVER_IDX_PLUGIN_PATH.'widgets/init.php';

		//	Cron jobs

		if ( !wp_next_scheduled('roveridx_cron_daily') ) {
			wp_schedule_event( time(), 'daily', 'roveridx_cron_daily' );
			}

		if ( !wp_next_scheduled('roveridx_cron_hourly') ) {
			wp_schedule_event( time(), 'hourly', 'roveridx_cron_hourly' );
			}
		}

	public function is_amp()
		{
		#	AMP for WordPress is activated
		#	By Automattic
		#	https://amp-wp.org/

		if (function_exists( 'is_amp_endpoint' ) && is_amp_endpoint())
			return true;

		#	AMP for WP - Accelerated Mobile Pages
		#	By Ahmed Kaludi, Mohammed Kaludi
		#	https://wordpress.org/plugins/accelerated-mobile-pages/\

		if (function_exists( 'ampforwp_is_amp_endpoint' ) && ampforwp_is_amp_endpoint())
			return true;

		#	weeblrAMP is activated
		#	By WeeblrPress
		#	https://www.weeblrpress.com/accelerated-mobile-pages/weeblramp

		if (class_exists('Weeblramp_Api') && Weeblramp_Api::isAMPRequest())
			return true;

		return false;
		}

	public function update_region_settings($fn, $ln, $key, $val)	{

		if (!is_array($region_options = get_option(ROVER_OPTIONS_REGIONS)))
			$region_options			= array();

		foreach($region_options as $r_key => $r_val)
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, sprintf("[%s] [%s] [%s] => [%s]", $fn, $ln, $r_key, $r_val));

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, sprintf("[%s] [%s] [%s] => [%s]", $fn, $ln, $key, $val));

		#	Remove old `slug` data
		$new_region_options			= array();
		foreach($region_options as $r_key => $r_val)
			{
			if ((strpos($r_key, "slug") === false) && ($r_val !== "region"))
				{
				$new_region_options[$r_key]	= $r_val;
				}
			else
				{
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, sprintf("[%s] [%s] new_region_options [remove] [%s] => [%s]", $fn, $ln, $r_key, $r_val));
				}
			}

		foreach($new_region_options as $r_key => $r_val)
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, sprintf("new_region_options [final] [%s] => [%s]", $r_key, $r_val));
			}

		$new_region_options[$key]	= $val;
		$ret						= update_option(ROVER_OPTIONS_REGIONS, $new_region_options);

		foreach($region_options as $r_key => $r_val)
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, sprintf("[%s] [%s] [%s] => [%s]", $fn, $ln, $r_key, $r_val));

		$this->roveridx_regions		= $new_region_options;

		return $ret;
		}

	public function redirect_to_setup() {

		$this->update_region_settings(__FUNCTION__, __LINE__, 'redirect_for_setup', false);

		wp_redirect( admin_url('admin.php?page=rover-idx') );

		exit;
		}

	public function cron_hourly() {

		require_once ROVER_IDX_PLUGIN_PATH.'rover-social-common.php';

		Rover_IDX_SOCIAL::refresh();
		}

	public function cron_daily() {

		require_once ROVER_IDX_PLUGIN_PATH.'rover-sitemap.php';

		Rover_IDX_SITEMAP::build();
		}

	public function rest_endpoints() {

		register_rest_route(
							IDX_REST_NAME,
							'/settings',
							array(
								'methods'					=> 'GET',
								'callback'					=> function () {

																	$the_html				= array();
																	$the_html[]				= '<table>';
																	foreach(array(
																				'Region'	=> ROVER_OPTIONS_REGIONS,
																				'Styling'	=> ROVER_OPTIONS_THEMING,
																				'SEO'		=> ROVER_OPTIONS_SEO,
																				'Social'	=> ROVER_OPTIONS_SOCIAL,
																				)
																			as $label => $option_key)
																		{
																		if (!is_array($opts = get_option($option_key)))
																			$opts			= array();

																		$the_html[]			=	sprintf('<tr><td colspan=2><h4 style="margin:20px 0 0 0;">%s</h4></td></tr>', $label);
																		foreach ($opts as $key => $val) {
																			$the_html[]		=	sprintf('<tr><td>%s</td><td><pre>%s</pre></td></tr>', $key, print_r($val, true));
																			}
																		}
																	$the_html[]				= '</table>';

																	header('Content-Type: text/html');
																	echo implode('', $the_html);
																	exit();

																	},
								'permission_callback'		=> function() { return ''; }
								)
							);

		register_rest_route(
							IDX_REST_NAME,
							'/settings/forced_encoding/unset',
							array(
								'methods'					=> 'POST',
								'callback'					=> function ($request) {

																	$key						= 'forced_encoding';
																	$result						= '';
																	if (is_array($rr = get_option(ROVER_OPTIONS_REGIONS)))
																		{
																		if (isset($rr[$key]))
																			{
																			unset($rr[$key]);

																			update_option(ROVER_OPTIONS_REGIONS, $rr);

																			$result				= $key.' has been unset';
																			}
																		else
																			{
																			$result				= $key.' was not set';
																			}
																		}

																	return new WP_REST_Response(
																		$result,
																		200,
																		array(
																			'Content-Type' => 'text/html; charset=utf-8',
																			)
																		);

																	exit();
																	},
								'permission_callback'		=> function() { return ''; }
								)
							);

		register_rest_route(
							IDX_REST_NAME,
							'/sitemap',
							array(
								'methods'					=> 'GET',
#								'callback'					=> array($this, 'cron_daily'),
								'callback'					=> function () {
																	require_once ROVER_IDX_PLUGIN_PATH.'rover-sitemap.php';

																	$ret		= Rover_IDX_SITEMAP::build($force_refresh = true);
																	if (isset($ret['log']))
#																		return new WP_REST_Response( str_replace("<br>", "\r\n", $ret['log']), 200 );
																		return new WP_REST_Response(
																			$ret['log'],
																			200,
																			array(
																				'Content-Type' => 'text/html; charset=utf-8',
																				)
																			);

																	return new WP_Error( 'sitemap-fail', __( 'message', '<pre>'.print_r($ret, true).'</pre>' ), array( 'status' => 500 ) );
																	},
								'permission_callback'		=> function() { return ''; }
								)
							);

		register_rest_route(
							IDX_REST_NAME,
							'/fub',
							array(
								'methods'					=> 'POST',
								'callback'					=> function () {

																	require_once ROVER_IDX_PLUGIN_PATH.'rover-content.php';

																	$ret		= Rover_IDX_Content::rover_content('webhook_fub', $_POST);

																	return new WP_REST_Response(
																		$ret,
																		200,
																		array(
																			'Content-Type' => 'text/html; charset=utf-8',
																			)
																		);

																	},
								'permission_callback'		=> function() { return ''; }
								)
							);
		}

	public function idx_site_posts_callback()	{

		check_ajax_referer(ROVERIDX_SS_NONCE, 'security');

		global 						$wpdb;

		$have_all_pages				= true;
		$limit						= 250;

		$rows_pages					= array();
		$sql						= array();
		$sql[]						= "SELECT ID, post_title";
		$sql[]						= "FROM $wpdb->posts";
		$sql[]						= "WHERE post_type = 'page'";
		$sql[]						= "AND post_status = 'publish'";
		if (isset($_POST['search_text']))
			$sql[]					= "AND (post_title LIKE '%".$_POST['search_text']."%' OR post_content LIKE '%".$_POST['search_text']."%')";
		$sql[]						= "LIMIT ".$limit;
		$rows						= $wpdb->get_results(implode(' ', $sql));
		foreach($rows as $row)
			{
			$rows_pages[]			= array(
									'key'				=> 'page',
									'name'				=> $row->post_title,
									'redirect'			=> get_permalink($row->ID)
									);
			}
		if (count($rows) === $limit)
			$have_all_pages			= false;

		$rows_post					= array();
		$sql						= array();
		$sql[]						= "SELECT ID, post_title";
		$sql[]						= "FROM $wpdb->posts";
		$sql[]						= "WHERE post_type = 'post'";
		$sql[]						= "AND post_status = 'publish'";
		if (isset($_POST['search_text']))
			$sql[]					= "AND (post_title LIKE '%".$_POST['search_text']."%' OR post_content LIKE '%".$_POST['search_text']."%')";
		$sql[]						= "LIMIT ".$limit;
		$rows						= $wpdb->get_results(implode(' ', $sql));
		foreach($rows as $row)
			{
			$rows_post[]			= array(
									'key'				=> 'post',
									'name'				=> $row->post_title,
									'redirect'			=> get_permalink($row->ID)
									);
			}

		if (count($rows) === $limit)
			$have_all_pages			= false;

		echo json_encode(array(
									'pages'				=> $rows_pages,
									'posts'				=> $rows_post,
									'have_all_pages'	=> $have_all_pages
									));

		die();
		}

	public function add_attributes_to_script($tag, $handle, $src)	{

		if ( 'rover-fontawesome-js' === $handle ) {
			$tag = '<script type="text/javascript" src="' . esc_url( $src ) . '" defer class="fontawesome-js" crossorigin="anonymous"></script>';
			}

		return $tag;
		}

	public function robots() {
//		header( 'Content-Type: text/plain; charset=utf-8' );

		global						$rover_idx;
		$sitemap_opts				= get_option(ROVER_OPTIONS_SEO);

		if ($sitemap_opts === false)
			return;

		if (!is_array($sitemap_opts))
			return;

		do_action( 'do_robotstxt' );

		$output						= null;
		$public						= get_option( 'blog_public' );
		if ( '0' != $public ) {

			foreach ($this->all_selected_regions as $one_region => $region_slugs)
				{
				if (isset($sitemap_opts[$one_region]))
					{
					$output .= "Sitemap: ".$sitemap_opts[$one_region]['url']."\n";
					}
				}

			}

		echo apply_filters('robots_txt', $output, $public);
		}


	public function load_css_and_preload() {

		global						$rover_idx;

		echo "<link rel='dns-prefetch' href='https://c.roveridx.com' />";
		echo "<link rel='preconnect' href='https://s3.us-west-1.wasabisys.com/'>\n";

		$css_framework				= 'rover';
		$load_fontawesome			= 'true';
		if (is_array($this->roveridx_theming))
			{
			if (isset($this->roveridx_theming['css_framework']))
				{
				$css_framework		= $this->roveridx_theming['css_framework'];
				}

			if (isset($this->roveridx_theming['load_fontawesome']))
				{
				$load_fontawesome	= ($this->roveridx_theming['load_fontawesome'] == 'Yes')
											? 'true'
											: 'false';
				}
			}

		#	Cloudflare ips come to us duplicated
		#	2a06:98c0:3600::103, 2a06:98c0:3600::103

		$user_ip					= array_unique( array_map('trim', explode(',', $_SERVER["REMOTE_ADDR"])) );
		$user_ip					= (is_array($user_ip) && isset($user_ip[0]))
											? $user_ip[0]
											: $_SERVER["REMOTE_ADDR"];

		$rover_site_auth			= array(
									"all_regions"			=> implode(",", array_keys($this->all_selected_regions)),
									"css_framework"			=> ((isset($this->roveridx_theming['css_framework']) ? $this->roveridx_theming['css_framework'] : 'rover')),
									"d"						=> get_site_url(),
									"domain"				=> clean_domain(get_site_url()),
									"did"					=> $this->roveridx_regions['domain_id'],
									"domain_id"				=> $this->roveridx_regions['domain_id'],
									"fav_requires_login"	=> "open",
									"is_multi_region"		=> ((count($this->all_selected_regions) > 1) ? 'true' : 'false'),
									"idx_url"				=> IDX_ENDPOINT_URL,
									"items"					=> 25,
									"load_fontawesome"		=> $load_fontawesome,
									"logged_in_email"		=> "",
									"logged_in_user_id"		=> "",
									"logged_in_authkey"		=> "",
									"page_url"				=> "/",
									"pdf_requires_login"	=> "open",
									"prop_anon_views_curr"	=> 0,
									"prop_details"			=> "link",
									"prop_requires_login"	=> "open",
									"region"				=> implode(",", array_keys($this->all_selected_regions)),
									"register_before_or_after_prop_display"	=> "after",
									"user_ip"				=> $user_ip,
									);
		echo "<script type='text/javascript'>var rover_site_auth = ".json_encode($rover_site_auth)."</script>";

		if ($this->is_amp())
			echo "<style class='rover-amp'>".get_option(ROVER_OPTIONS_CSS_AMP)."</style>";
		else
			echo "<style class='rover'>".get_option(ROVER_OPTIONS_CSS_DEFAULT)."</style>";
		}

	public function load_js() {

		if ($this->is_amp())
			return null;

		$js_ver						= (isset($this->roveridx_theming['js_version']) && !empty($this->roveridx_theming['js_version']))
											? $this->roveridx_theming['js_version']
											: ROVER_JS_VERSION;

		#	https://js.roveridx.com/3.0.0/js/1800002/rover.min.js
		$js_url						= (isset($_GET['jscdn']))
											? sprintf("https://c.roveridx.com/%s/js/rover.js", ROVER_VERSION)
											: sprintf("https://c.roveridx.com/%s/js/%s/rover.min.js", ROVER_VERSION, $js_ver);

		$the_js						= array();
		$the_js[]					= '<script type="text/javascript" class="rover_idx_boot_js">';
		$the_js[]					= 'function rover_idx_boot_js() {';
		$the_js[]					=	'var element = document.createElement("script");';
		$the_js[]					=	'element.src = "'.$js_url.'";';
		$the_js[]					=	'element.className = "roveridx";';
		$the_js[]					=	'element.setAttribute("data-js_ver", '.$js_ver.');';
		$the_js[]					=	'document.body.appendChild(element);';
		$the_js[]					=	'}';

		$the_js[]					= 'if (window.addEventListener)';
		$the_js[]					=	'{window.addEventListener("load", rover_idx_boot_js, false)}';
		$the_js[]					= 'else if (window.attachEvent)';
		$the_js[]					=	'{window.attachEvent("onload", rover_idx_boot_js)}';
		$the_js[]					= 'else ';
		$the_js[]					=	'{window.onload = rover_idx_boot_js};';
		$the_js[]					= '</script>';

		echo implode('', $the_js);
		}

	public function permalinks_have_been_updated( $oldvalue, $_newvalue )
		{
		$url_ends_with_slash					= ($_newvalue && substr($_newvalue, -1) != '/')
														? false
														: true;

		$this->update_region_settings(__FUNCTION__, __LINE__, 'url_ends_with_slash', $url_ends_with_slash);
		}

	private function get_selected_regions()	{

		$region_data									= array();
		if ($this->roveridx_regions === false || !is_array($this->roveridx_regions))
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, '[regions] is not set!');
			return $region_data;
			}

		if (isset($this->roveridx_regions['regions']))
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, '[regions] ['.$this->roveridx_regions['regions'].']');

			/*
				Single region:
					regions				INTERMOUNTAIN|ID|OR					( region|st|st|st )
				Multi-regions will look like:
					regions				INTERMOUNTAIN|ID|OR||BAINMLS|ID		( region|st||region|st )
			*/

			foreach(explode('||', $this->roveridx_regions['regions']) as $one_region)
				{
				$region_parts							= explode('|', $one_region);

				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, '[regions] ['.print_r($region_parts, true).']');

				$region									= $region_parts[0];
				$region_data[$region]					= implode(',', array_slice($region_parts, 1));

				if (is_null($this->first_selected_region))
					$this->first_selected_region		= $region;
				}
			}

		return $region_data;
		}

	public function check_url_for_idx_keys()	{

		//	Check if the requested page matches our target

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'url ['.$this->curr_path.']');

		if (($this->found_slug !== false) && !is_null($this->found_slug)){
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'returning previously found slug ['.$this->found_slug.']');
			return $this->found_slug;
			}

		foreach ($this->curr_path_parts as $url_part)
			{
			#	So we don't serve up dynamic pages for example.com/2015/04/ma (it looks like Google likes
			#	to crawl these, and just specifying the state takes forever with MIDFLORIDA

#			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Looking at urlpart '.$url_part);
			$url_part							= str_replace('/', '', $url_part);

			$this->found_slug					= $this->match_slug($url_part);
			if ($this->found_slug !== false){
				return $this->found_slug;
				}

			$this->found_slug					= $this->match_region_slug($url_part);
			if ($this->found_slug !== false){
				return $this->found_slug;
				}

			$this->found_slug					= $this->match_standard_page_slug($url_part);
			if ($this->found_slug !== false){
				return $this->found_slug;
				}
			}

		$this->found_slug						= false;

#		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Does not match any slugs');

		return false;
		}

	private function match_slug($url_part)
		{
		$found_regions							= array();
		$found_slugs							= array();
		foreach ($this->all_selected_regions as $one_region => $region_slugs)
			{
			foreach (explode(',', $region_slugs) as $one_slug)
				{
				if (empty($one_slug))
					continue;

				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Comparing '.$url_part.' to '.$one_slug);

				if (
					strcasecmp($url_part, $one_slug) === 0 ||
					strcasecmp(str_replace('-', ' ', $url_part), $one_slug) === 0
					)
					{
					rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Found slug ('.$one_slug.')');

					$found_regions[]			= $one_region;
					$found_slugs[]				= $one_slug;
					}
				}
			}

		if (count($found_regions))
			{
			$this->rover_404_regions			= implode(',', $found_regions);
			$this->rover_404_slugs				= implode(',', array_unique($found_slugs));

			return $found_slugs[0];
			}

		return false;
		}

	private function match_region_slug($url_part)
		{
		$matched_parts							= array();
		foreach ($this->all_selected_regions as $one_region => $region_slugs)
			{
			foreach(explode(',', $url_part) as $one_segment_of_part)
				{
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Comparing '.$url_part.' to '.$one_region);

				if (strcasecmp($one_segment_of_part, $one_region) === 0)
					{
					rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Found slug ('.$one_region.')');
					$matched_parts[]			= $one_region;
					}
				}
			}

		if (count($matched_parts))
			{
			$this->rover_404_regions			= implode(',', $matched_parts);
			$this->rover_404_slugs				= implode(',', $matched_parts);

			return $matched_parts;
			}

		return false;
		}

	private function match_standard_page_slug($url_part)
		{
		if (strlen($url_part) === 0)
			return false;

		foreach(array('rover-','idx-') as $standard_slug_part)
			{
			if (substr_compare($url_part, $standard_slug_part, 0, min(strlen($standard_slug_part), strlen($url_part))) === 0)
				{
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, $url_part.' may be an idx standard slug');
				return $url_part;
				}

			$page_type_slugs					= roveridx_default_slugs();

			if (isset($this->roveridx_regions['exclude_slugs']) && !empty($this->roveridx_regions['exclude_slugs']))
				{
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Exclude slugs: ['.$this->roveridx_regions['exclude_slugs'].']');

				$page_type_slugs				= array_diff($page_type_slugs, explode(',', $this->roveridx_regions['exclude_slugs']));
				}

			foreach ($page_type_slugs as $one_rover_slug)
				{
				if (strcmp($url_part, $one_rover_slug) === 0)
					{
					rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, $url_part.' may be a Rover standard slug');

					$this->rover_404_regions	= implode(',', array_keys($this->all_selected_regions));
					$this->rover_404_slugs		= implode(',', $page_type_slugs);

					return $url_part;
					}
				}
			}

		return false;
		}

	private function upgrade_options()	{

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, ' ');

		if (!isset($this->roveridx_regions['domain_id']) || empty($this->roveridx_regions['domain_id']))
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, ' `domain_id` is not set - leaving');
			return false;					/*	not yet setup	*/
			}


		/*	Migrate regions to new schema in ROVER_OPTIONS_REGIONS	*/

		if (
			(!isset($this->roveridx_regions['regions'])) ||
			(empty($this->roveridx_regions['regions']))
			)
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, ' `regions` is not correctly set');

			$regions						= array();
			foreach($this->roveridx_regions as $one_key => $one_val)
				{
				if (strpos($one_key, 'slug') !== false)
					{
					/*
						Turn this:
							slugINTERMOUNTAIN	ID,OR
						Into this:
							regions				INTERMOUNTAIN|ID|OR
						Multi-regions will look like:
							regions				INTERMOUNTAIN|ID|OR||BAINMLS|ID
					*/
					$regions[]				= sprintf("%s|%s", str_replace('slug', '', $one_key), str_replace(',', '|', $one_val));
					}
				}

			if (count($regions) === 0)			/*	Ugh - current string is really munged.  Rebuild it from the saved ClientDomains	*/
				{
				$this->update_region_settings(__FUNCTION__, __LINE__, 'regions', Rover_IDX_Content::build_regions_string());
				}
			else								/*	update $this->roveridx_regions and update on-disk options	*/
				{
				$this->update_region_settings(__FUNCTION__, __LINE__, 'regions', implode('||', $regions));
				}
			}

		$site_version						= (isset($this->roveridx_theming['site_version']) && !empty($this->roveridx_theming['site_version']))
													? $this->roveridx_theming['site_version']
													: null;

		if (is_null($site_version) || (version_compare(ROVER_VERSION, $site_version) === 1))
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, ' site_version ['.ROVER_VERSION.'] / ['.$site_version.']');

			if (!is_array($theme_opts = get_option(ROVER_OPTIONS_THEMING)))
				$theme_opts					= array();

			if (ROVER_VERSION == "2.1.0")
				{
				//	We no longer need Bootstrap
				$theme_opts['css_framework']= 'rover';

				Rover_IDX_Content::update_site_settings(array('css_framework'	=> 'rover'));
				}

			$theme_opts['site_version']		= ROVER_VERSION;

			update_option(ROVER_OPTIONS_THEMING, $theme_opts);

			$this->roveridx_theming			= $theme_opts;

			add_action('admin_notices',	array($this, 'admin_notice_upgraded'));
			}

		$css								= get_option(ROVER_OPTIONS_CSS_DEFAULT);

		if ($css === false)
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, '['.ROVER_OPTIONS_CSS_DEFAULT.'] does not exist');
		else
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, '['.ROVER_OPTIONS_CSS_DEFAULT.'] is currently ['.strlen($css).'] bytes');

		if (empty($css) || str_contains($css, 'Unable to communicate with server'))
			$this->refresh_css();
		}

	private function url_should_avoid_cache()
		{
		$nocache_urls				= array(
											"rover-control-panel"			=> true,
											"idx-control-panel"				=> true,
											"rover-custom-listing-panel"	=> true,
											"idx-custom-listing-panel"		=> true,
											"rover-login-panel"				=> true,
											"idx-login-panel"				=> true,
											);

		foreach ($this->curr_path_parts as $url_part)
			{
			if (isset($nocache_urls[strtolower($url_part)]))
				return true;
			}

		return false;
		}

	private function url_contains_troll_url_parts()
		{
		$known_troll_parts			= array(
											"exclusive-dorder"				=> true,
											"price-dorder"					=> true,
											"prop-url"						=> true,
											);

		foreach ($this->curr_path_parts as $url_part)
			{
			if (isset($known_troll_parts[strtolower($url_part)]))
				return $url_part;
			}

		return false;
		}
	}



global			$rover_idx;

if (!is_object($rover_idx))
	{
	$rover_idx	= new Rover_IDX();
	}


?>