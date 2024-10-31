<?php

define('ROVER_DEBUG_KEY',							'roveridx_debug');
define('ROVERIDX_NONCE',							'roveridx-security-key');
define('ROVERIDX_SS_NONCE',							'nonce-site-search');
define('ROVERIDX_DEF_POST_ID',						-487);

define('WP_TEMPLATE_KEY',							'_wp_page_template');
define('ROVERIDX_META_PAGE_ID',						'_roveridx_page_id');

define('ROVERIDX_FBML_NS_URI',						'http://www.facebook.com/2008/fbml');


function roveridx_get_version()	{
	return ROVER_VERSION;
	}

function roveridx_val_is_checked($settings, $key)	{
	return (is_array($settings) && isset($settings[$key]) && $settings[$key] == true)
					? 'checked=checked'
					: '';
	}
function roveridx_val_is_selected($settings, $key, $val_to_compare)	{
	return (is_array($settings) && isset($settings[$key]) && $settings[$key] == $val_to_compare)
					? 'selected=selected'
					: '';
	}
function roveridx_get_val($settings, $key)	{
	return (is_array($settings) && isset($settings[$key]))
					? $settings[$key]
					: '';
	}

function get_rover_post_id($theming_options) {
	if (!empty($theming_options) && is_array($theming_options) && isset($theming_options['rover_post_id']))
		$rover_post_id			= $theming_options['rover_post_id'];		//	WooThemes 'Empire' conflicts with -1, so we're using a more unique value
	else
		$rover_post_id			= ROVERIDX_DEF_POST_ID;

	return $rover_post_id;
	}

function clean_domain($domain)
	{
	$clean_domain				= str_replace(
											array('http://', 'https://', 'www.', '//'),
											'',
											$domain
											);

	if ('/' == substr($clean_domain, -1, 1))
		$clean_domain			= substr($clean_domain, 0, -1);	//	Return all but last char

	return strtolower($clean_domain);
	}

function rover_idx_error_log($file, $func, $line, $str)	{

	global									$rover_idx;

	if ($rover_idx && rover_idx_is_debuggable())
		{
		$debug_str							= sprintf( '%1$s %2$s %3$s: %4$s', basename($file), $func, $line, $str);

		$rover_idx->debug_html[]			= $debug_str;

		error_log($debug_str);
		}

	}

function rover_idx_curr_url()	{

	$url  = @( $_SERVER["HTTPS"] != 'on' ) ? 'http://'.$_SERVER["SERVER_NAME"] :  'https://'.$_SERVER["SERVER_NAME"];
	$url .= ( $_SERVER["SERVER_PORT"] != 80 ) ? ":".$_SERVER["SERVER_PORT"] : "";
	$url .= $_SERVER["REQUEST_URI"];

	return $url;
	}

function rover_idx_is_debuggable()	{

	global									$rover_idx;

	if ($rover_idx)
		{
		if (is_null($rover_idx->is_debuggable))
			{
			$is_debuggable						= false;

			if (defined('WP_DEBUG') && WP_DEBUG === true)
				$is_debuggable					= true;

			if (defined('ROVER_IDX_DEBUG') && ROVER_IDX_DEBUG === true)
				$is_debuggable					= true;

			if (isset($_GET['roveridx_debug']) && $_GET['roveridx_debug'] > 0)
				$is_debuggable					= true;

			$rover_idx->is_debuggable			= $is_debuggable;
			}

		return $rover_idx->is_debuggable;
		}

	return false;
	}


function rover_idx_validate_bool($val)	{

	return ($val === true || $val == 'true' || $val == 1)
		? true
		: false;

	}

function rover_idx_validate_yes_no($val)	{

	return (strcasecmp($val, 'Yes') === 0)
		? 'Yes'
		: 'No';

	}

function rover_parse_url($var)
	{
	/**
	*  Use this function to parse out the query array element from
	*  the output of parse_url().
	*/
//	$var  = parse_url($var, PHP_URL_QUERY);
	$var  = html_entity_decode($var);
	$var  = explode('&', $var);
	$arr  = array();

	if (is_array($var))
		{
		foreach($var as $val)
			{
			$x          = explode('=', $val);
			$arr[$x[0]] = $x[1];
			}
		}
	unset($val, $x, $var);
	return $arr;
	}


function strip_cross_domain_parenthesis_from_JSON($result)		//	Remove leading and trailing parenthesis that we get from cross-domain json
	{
	$pos = strpos($result, '({');		//	Left ({
	if ($pos !== false)
		$result = substr($result,  $pos+1);

	$pos = strrpos($result, '});');		//	Right })
	if ($pos !== false)
		$result = substr($result,  0, $pos+1);

	return $result;
	}


function rover_contrast_color($hexcolor){

	$hexcolor		= str_replace('#', '', $hexcolor);
	$len			= strlen($hexcolor);

	if ($len === 3)
		$hexcolor	= $hexcolor . $hexcolor;

	if ($len === 6)
		{
		$r			= hexdec(substr($hexcolor,0,2));
		$g			= hexdec(substr($hexcolor,2,2));
		$b			= hexdec(substr($hexcolor,4,2));

		$yiq		= (($r*299)+($g*587)+($b*114))/1000;

		return ($yiq >= 128) ? 'black' : 'white';
		}

	return 'white';
	}

function roveridx_css_and_js() {

	global						$rover_idx;

	$upload_dir					= wp_upload_dir();
	$is_rover_admin				= false;

	$js_ver						= (isset($rover_idx->roveridx_theming['js_version']) && !empty($rover_idx->roveridx_theming['js_version']))
										? $rover_idx->roveridx_theming['js_version']
										: ROVER_JS_VERSION;

	if (is_admin())
		{
		//	Only bother to load our jQuery when we are on our pages

		if (is_array($_GET) && isset($_GET['page']))
			{
			$the_page			= $_GET['page'];

			if ((strpos($the_page, IDX_PLUGIN_NAME) !== false) || (strpos($the_page, IDX_PANEL_SLUG) !== false))
				{
				$is_rover_admin	= true;
				}
			}
		}

	rover_load_bootstrap($is_rover_admin);
	rover_remove_emojis();


	//	************	CSS		***************

	if (is_admin())
		{
		$screen			= get_current_screen();
		if (isset($screen->base ) && $screen->base === 'dashboard')
			rover_load_flot();
		}

	}


function is_rover_panel($component)
	{
	if ((substr($component, 6) === 'rover-') && (substr($component, -6) === '-panel'))
		return true;

	return false;
	}

function is_this_panel($panel)
	{
	global					$wp, $rover_idx;

	foreach (explode('/', $rover_idx->curr_path) as $url_part)
		{
		if (strcmp($url_part, $panel) === 0)
			{
			return true;
			}
		}

	return false;
	}

function rover_load_bootstrap($is_rover_admin)
	{
	global					$rover_idx;

	$do_it					= false;

	if ( is_admin() )		#	On a Rover settings page in Admin.  Purposefully do not load Bootstrap if we are in admin, but not on a Rover page
		{
		$do_it				= ($is_rover_admin) ? true : false;
		}
	else if (is_this_panel('rover-control-panel') || is_this_panel('rover-custom-listing-panel') || is_this_panel('rover-market-conditions'))
		{
		$do_it				= true;
		}
	if (@$rover_idx->roveridx_theming['load_admin_bootstrap'] == 'No')
		{
		$do_it				= false;
		}

	if ($do_it)
		{
		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Loading Bootstrap ');

		wp_register_style(	'rover-bootstrap-css',
							ROVER_ENGINE_SSL . ROVER_VERSION . '/js/bootstrap4.5.2/css/bootstrap.min.css',
							array(),
							$ver = null,
							'all');
		wp_enqueue_style(	'rover-bootstrap-css' );

		wp_register_script( 'rover-bootstrap-popper-js',
							ROVER_ENGINE_SSL . ROVER_VERSION . '/js/bootstrap4.5.2/js/popper.min.js',
							$dep = array(),
							$ver = null,
							$in_footer = true);
		wp_enqueue_script( 'rover-bootstrap-popper-js' );

		wp_register_script( 'rover-bootstrap-js',
							ROVER_ENGINE_SSL . ROVER_VERSION . '/js/bootstrap4.5.2/js/bootstrap.min.js',
							$dep = array(),
							$ver = null,
							$in_footer = true);
		wp_enqueue_script( 'rover-bootstrap-js' );
		}
	}

function rover_remove_emojis()
	{
	global					$rover_idx;

	if (@$rover_idx->roveridx_theming['load_emojis'] == 'No')
		{
		remove_action('wp_head', 'print_emoji_detection_script', 7);
		remove_action('wp_print_styles', 'print_emoji_styles');
		}
	}

function rover_load_flot()
	{
	wp_register_script(		'flot',
							ROVER_ENGINE_SSL . ROVER_VERSION . '/js/flot/jquery.flot.js',
							$dep = array('jquery'),
							$ver = null,
							$in_footer = true);
	wp_register_script(		'flotstack',
							ROVER_ENGINE_SSL . ROVER_VERSION . '/js/flot/jquery.flot.stack.js',
							$dep = array('flot'),
							$ver = null,
							$in_footer = true);
	wp_register_script(		'flotcat',
							ROVER_ENGINE_SSL . ROVER_VERSION . '/js/flot/jquery.flot.categories.js',
							$dep = array('flot'),
							$ver = null,
							$in_footer = true);
	wp_register_script(		'flotresize',
							ROVER_ENGINE_SSL. ROVER_VERSION . '/js/flot/jquery.flot.resize.js',
							$dep = array('flot'),
							$ver = null,
							$in_footer = true);

	wp_enqueue_script(		'flot');
	wp_enqueue_script(		'flotstack');
	wp_enqueue_script(		'flotcat');
	wp_enqueue_script(		'flotresize');
	}
?>