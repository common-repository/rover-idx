<?php

require_once ROVER_IDX_PLUGIN_PATH.'admin/rover-templates.php';
require_once ROVER_IDX_PLUGIN_PATH.'rover-content.php';

// Render the Plugin options form
function roveridx_panel_styling_form($atts) {

	global			$rover_idx, $rover_panel_common;

	?>
	<div id="wp_defaults" class="wrap">
	<?php

	rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Starting');

	if (!is_array($theme_options = get_option(ROVER_OPTIONS_THEMING)))
		$theme_options		= array();

	$upload_dir				= wp_upload_dir();
	$all_templates			= roveridx_get_all_templates();

#foreach($theme_options as $theme_key => $theme_val)
#	{
#	if (isset($theme_options[$theme_key]))
#		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, $theme_key.' is ['.$theme_val.']');
#	else
#		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, $theme_key.' is not set');
#	}
#
#	$page_templates	=		array(
#								'all_templates'			=> implode(",", $all_templates),
#								'property_template'		=> (isset($theme_options['property_template'])
#																	? $theme_options['property_template']
#																	: null),
#								'mc_template'			=> (isset($theme_options['mc_template'])
#																	? $theme_options['mc_template']
#																	: null),
#								'agent_detail_template'	=> (isset($theme_options['agent_detail_template'])
#																	? $theme_options['agent_detail_template']
#																	: null),
#								'rep_template'			=> (isset($theme_options['rep_template'])
#																	? $theme_options['rep_template']
#																	: null),
#								'listing_template'		=> (isset($theme_options['template'])
#																	? $theme_options['template']
#																	: null)
#								);

	$rover_content				= Rover_IDX_Content::rover_content(	'ROVER_COMPONENT_STYLING_PANEL',
														array_merge(
															$theme_options,
															array(
																'region'				=> $rover_idx->get_first_region(),
																'regions'				=> implode(',', array_keys($rover_idx->all_selected_regions)),
//																'settings'				=> $theme_options,
																'rover_css'				=> roveridx_get_css($theme_options),
																'wp_menus'				=> roveridx_get_menus($theme_options),
																'plugin_url'			=> ROVER_IDX_PLUGIN_URL,
																'upload_url'			=> $upload_dir['baseurl'],
																'page_templates'		=> implode(",", $all_templates),
																'all_templates'			=> implode(",", $all_templates),
																'property_template'		=> (isset($theme_options['property_template'])
																									? $theme_options['property_template']
																									: null),
																'mc_template'			=> (isset($theme_options['mc_template'])
																									? $theme_options['mc_template']
																									: null),
																'agent_detail_template'	=> (isset($theme_options['agent_detail_template'])
																									? $theme_options['agent_detail_template']
																									: null),
																'rep_template'			=> (isset($theme_options['rep_template'])
																									? $theme_options['rep_template']
																									: null),
																'template'				=> (isset($theme_options['template'])
																									? $theme_options['template']
																									: null)
																)
															)
														);
	?>

		<div id="rover-styling-panel" class="">

			<?php echo roveridx_panel_header();	?>

			<?php echo $rover_content['the_html'];	?>

			<?php echo roveridx_panel_footer($panel = 'styling');	?>

		</div>
	</div><!-- wrap -->


<?php
	}

function roveridx_get_menus($theme_options)	{

	$menu_names				= array();
	if (false)
		{
		foreach(get_registered_nav_menus() as $k => $v)
			$menu_names[]	= wp_get_nav_menu_name($k);
		}
	else
		{
		foreach ( get_registered_nav_menus() as $location => $description ) {
			$menu_names[]	= $location;
			}
		}

	return implode(',', $menu_names);
	}

function roveridx_load_google_api($theme_options)	{

	return (isset($theme_options['load_google_api']) && $theme_options['load_google_api'] === 'No')
							? 'rover-no-google-api'
							: 'rover-load-google-api';
	}


function rover_idx_theme_defaults($post_id = null) {

	require_once ROVER_IDX_PLUGIN_PATH.'rover-scheduled.php';

	$theme_defaults	= array(
							'css_framework'			=> ROVER_DEFAULT_CSS_FRAMEWORK,
							'login_button'			=> 'menu-primary',
							'load_admin_bootstrap'	=> 'Yes',
							'load_emojis'			=> 'No',
							'load_fontawesome'		=> 'Yes',
							'load_google_api'		=> 'Yes',
							'load_google_libraries'	=> null,
							'google_map_key'		=> null,
							'site_version'			=> ROVER_VERSION_FULL,
							'js_version'			=> roveridx_refresh_js_ver($force_refresh = false)
							);

	if (!is_null($post_id))
		$theme_defaults['rover_post_id']	= $post_id;

	return $theme_defaults;
	}


function rover_idx_theme_callback() {

	if ( current_user_can('manage_options') )
		{
		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Starting');

		check_ajax_referer(ROVERIDX_NONCE, 'security');

		if (!is_array($theme_options = get_option(ROVER_OPTIONS_THEMING)))
			$theme_options									= array();

		foreach($_POST as $key => $val)
			$theme_options[$key]							= sanitize_text_field( $val );

		if (isset($theme_options['action']))
			unset($theme_options['action']);
		if (isset($theme_options['security']))
			unset($theme_options['security']);

		$r 													= update_option(ROVER_OPTIONS_THEMING, $theme_options);
		if ($r === true)
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Theme options were changed');
		else
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Theme options were not changed');

		roveridx_set_template_meta($new_theme_options['rover_post_id'], sanitize_text_field( $_POST['template'] ) );

		$responseVar = array(
	                    'theme'							=> $theming_array['theme'],
	                    'css_framework'					=> $theming_array['css_framework'],
	                    'css'							=> $theming_array['css'],
	                    'success'						=> $r
	                    );

    	echo json_encode($responseVar);
		}

	die();
	}


add_action('wp_ajax_rover_idx_theme', 'rover_idx_theme_callback');


function rover_idx_fetch_theme_settings_callback() {

	if ( current_user_can('manage_options') )
		{
		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Starting');

		check_ajax_referer(ROVERIDX_NONCE, 'security');

    	echo json_encode(array(
	                    'success'						=> true,
	                    'settings'						=> get_option(ROVER_OPTIONS_THEMING)
	                    ));
		}

	die();
	}


add_action('wp_ajax_rover_idx_fetch_theme_settings', 'rover_idx_fetch_theme_settings_callback');



function rover_idx_overwrite_theme_settings_callback() {

	rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Starting');

	check_ajax_referer(ROVERIDX_NONCE, 'security');

	if ( current_user_can('manage_options') )
		{
		$source_wp_theme_options						= $_POST['source_wp_theme_options'];

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'source_wp_theme_options ['.$source_wp_theme_options.']');

		$unsanitized_object								= $source_wp_theme_options;

		if (is_array($unsanitized_object))
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'unsanitized_object is an array of ['.count($unsanitized_object).'] items');
		else
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'unsanitized_object is NOT an array ');

		$new_theme_options								= array();
		foreach($unsanitized_object as $option_key => $option_val)
			$new_theme_options[$option_key]				= sanitize_text_field( $unsanitized_object[$option_key] );

		$r												= update_option(ROVER_OPTIONS_THEMING, $new_theme_options);

		echo json_encode(array(
	                    'success'						=> true,
						'msg'							=> 'Wordpress settings have been updated'
	                    ));
		}

	die();
	}


add_action('wp_ajax_rover_idx_overwrite_theme_settings', 'rover_idx_overwrite_theme_settings_callback');


function roveridx_get_css($options)
	{
	$css_files				= array();
	$all_css				= array();

	if ($handle = opendir(ROVER_IDX_PLUGIN_PATH.'/css')) {

	    /* This is the correct way to loop over the directory. */
	    while (false !== ($file = readdir($handle))) {
		    if (strpos($file, '.css', 1))
		    	{
			    $css_files[]= $file;
	        	}
	        }

	    closedir($handle);
	    }

	sort($css_files);

	foreach($css_files as $one_css_file)
		{
	    $the_css			= substr($one_css_file, 0, (strlen($one_css_file) - 4));
	    $sel				= (isset($options['css']) && $options['css'] == $one_css_file)	? 'selected=selected' : '';
	    $all_css[]			= '<option value="'.$one_css_file.'" '.$sel.'>'.$the_css.'</option>';
		}

	$upload_dir = wp_upload_dir();
	if (file_exists($upload_dir['basedir'].'/rover-custom.css'))
		{
		$sel				= (isset($options['css']) && $options['css'] == 'rover-custom.css')	? 'selected=selected' : '';
		$all_css[]			= '<option value="rover-custom.css" '.$sel.'>rover-custom.css</option>';
		}

	return implode('', $all_css);
	}
function roveridx_theme_is_selected($options, $theme)	{
	return (isset($options['theme']) && $options['theme'] == $theme)
						?	'selected=selected'
						:	'';
	}
function roveridx_use_themes_fullpage_mechanism()		{
	if (function_exists('genesis_unregister_layout') ||
		function_exists('woo_post_meta'))
		{
		return true;
		}

	return false;
	}

function roveridx_get_all_templates()	{

	$all_templates			= array();

	foreach ( get_page_templates() as $template_name => $template_filename ) {
		$all_templates[]	= $template_filename;
		}

	return $all_templates;
	}

function roveridx_get_templates($all_templates, $options, $key)	{

	$val					= ($options && isset($options[$key]))
									? $options[$key]
									: null;
	$one_selected			= false;

	$the_html				= array();
	foreach ($all_templates as $one_template)
		{
		$selected			= null;

		if ($one_template == $val)
			{
			$selected 		= 'selected=selected';
			$one_selected 	= true;
			}

		$the_html[]			= "<option value='".$one_template."' ".$selected."> ".$one_template."</option>";
		}

	#	Add as first entry
	array_unshift($the_html , "<option value='".(($one_selected) ? '' : 'selected')."'> Use default page template</option>");

	return implode("", $the_html);
	}


?>