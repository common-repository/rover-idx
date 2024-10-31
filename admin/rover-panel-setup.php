<?php

require_once ROVER_IDX_PLUGIN_PATH.'admin/rover-templates.php';

add_action('wp_ajax_rover_idx_save_setup', 			'rover_idx_save_setup_callback');
add_action('wp_ajax_rover_idx_save_slug_excludes',	'rover_idx_save_slug_excludes_callback');
add_action('wp_ajax_rover_idx_save_style_settings',	'rover_idx_save_style_settings_callback');
add_action('wp_ajax_rover_idx_reset', 				'rover_idx_reset_callback');
add_action('wp_ajax_rover_idx_quick_start_create',	'rover_idx_quick_start_create_callback');
add_action('wp_ajax_rover_idx_quick_start_info',	'rover_idx_quick_start_info_callback');
add_action('wp_ajax_rover_idx_quick_start_reset',	'rover_idx_quick_start_reset_callback');


add_action('wp_ajax_rover_idx_refresh_js_ver', 		'rover_idx_refresh_js_ver_callback');
add_action('wp_ajax_rover_idx_show_settings', 		'rover_idx_show_settings_callback');


function roveridx_panel_setup_form($atts) {

	require_once ROVER_IDX_PLUGIN_PATH.'rover-content.php';

	global											$rover_idx;

	if (!is_array($theme_options = get_option(ROVER_OPTIONS_THEMING)))
		$theme_options								= array();

	$rover_content									= Rover_IDX_Content::rover_content(
																					'ROVER_COMPONENT_SETUP_PANEL',
																					array_merge(
																						$theme_options,
																						array(
																							'region'				=> $rover_idx->get_first_region(),
																							'regions'				=> implode(',', array_keys($rover_idx->all_selected_regions)),
																							)
																						)
																					);

	?>
	<div class="wrap" data-page="rover_idx">

	<?php echo roveridx_panel_header();	?>

	<?php echo $rover_content['the_html'];	?>

	<?php echo roveridx_panel_footer($panel = 'setup');	?>

	<?php

		$force_wp_update							= true;

	?>
		<input type="hidden" id="wp_force_update" name="wp_force_update" value="'<?php echo $force_wp_update; ?>'" />
	</div><!-- wrap -->
	<?php
	}


function rover_idx_setup_defaults() {
	$perm										= get_option('permalink_structure');
	$url_ends_with_slash						= true;
	if ($perm && substr($perm, -1) != '/')
		$url_ends_with_slash					= false;

	return array(
				'url_ends_with_slash'			=> $url_ends_with_slash,
				'redirect_for_setup'			=> true,
				);
	}



/************************************************/
/*	Callbacks									*/
/************************************************/

function rover_idx_save_setup_callback() {

	check_ajax_referer(ROVERIDX_NONCE, 'security');

	if ( ! current_user_can('manage_options') )
		return;

	global $rover_idx;

	if (isset($_POST['reset']) && (isset($_POST['regions'])))
		{
		if (isset($_POST['did']))
			$rover_idx->update_region_settings(__FUNCTION__, __LINE__, 'domain_id', intval($_POST['did']));

		$rover_idx->update_region_settings(__FUNCTION__, __LINE__, 'regions', $_POST['regions']);
		}
	else
		{
		if (isset($_POST['did']))
			$rover_idx->update_region_settings(__FUNCTION__, __LINE__, 'domain_id', intval($_POST['did']));

		if (isset($_POST['regions']))
			$rover_idx->update_region_settings(__FUNCTION__, __LINE__, 'regions', $_POST['region']);
		else if (count($rover_idx->all_selected_regions) == 0)
			$rover_idx->update_region_settings(__FUNCTION__, __LINE__, 'regions', $_POST['region']);
		else if (isset($_POST['region']))
			$rover_idx->update_region_settings(__FUNCTION__, __LINE__, 'regions', $_POST['region']);

		if (!is_array($region_options = get_option(ROVER_OPTIONS_REGIONS)))
			$region_options						= array();

		if (isset($region_options['region']) && !isset($region_options['regions']))
			$rover_idx->update_region_settings(__FUNCTION__, __LINE__, 'regions', $region_options['region']);
		}

	$responseVar								= array(
	            								       'region_data'	=> $_POST['region_data'],
	            								       'success'		=> true		//	Folks are getting confused when we say 'Settings were not updated'
	            								       );

	flush_rewrite_rules();

	echo json_encode($responseVar);

	die();
	}

function rover_idx_save_slug_excludes_callback()		{

	check_ajax_referer(ROVERIDX_NONCE, 'security');

	if ( ! current_user_can('manage_options') )
		return;

	if (empty($_POST['exclude']))
		{
		echo json_encode(array(
												'exclude'		=> null,
												'success'		=> false
												));
		}
	else
		{
		if (!is_array($rr = get_option(ROVER_OPTIONS_REGIONS)))
			$rr									= array();
		$rr['exclude_slugs']					= sanitize_text_field( $_POST['exclude'] );

		$r										= update_option(ROVER_OPTIONS_REGIONS, $rr);

		$responseVar							= array(
														'exclude'		=> $_POST['exclude'],
														'success'		=> true
														);

		flush_rewrite_rules();

		echo json_encode($responseVar);
		}

	die();
	}

function rover_idx_save_style_settings_callback()	{

	check_ajax_referer(ROVERIDX_NONCE, 'security');

	if ( ! current_user_can('edit_posts') )
		return;

	if (!is_array($theme_options = get_option(ROVER_OPTIONS_THEMING)))
		$theme_options							= array();

	$one_key									= (isset($_POST['key']))
														? $_POST['key']
														: null;
	$one_val									= (isset($_POST['val']))
														? $_POST['val']
														: null;

	rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, ' key ['.$one_key.'] / val ['.$one_val.']');

	if (!is_null($one_key) && !is_null($one_val))
		{
		if (in_array($one_key, array('load_admin_bootstrap', 'load_emojis', 'load_fontawesome', 'load_google_api')))	/*	'Yes' / 'No' values	*/
			$theme_options[$one_key]			= rover_idx_validate_yes_no($one_val);
		else if (is_bool($one_val) || (is_string($one_val) && in_array($one_val, array('true', 'false'))))
			$theme_options[$one_key]			= rover_idx_validate_bool($one_val);
		else
			$theme_options[$one_key]			= sanitize_text_field($one_val);
		}
	else
		{
		foreach($_POST as $one_key => $one_val)
			{
			if (!in_array($key, array('action', 'security')))
				$theme_options[$one_key]		= sanitize_text_field($one_val);
			}
		}

	rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, ' post sanitize ['.$one_key.'] => ['.$theme_options[$one_key].']');

	$r											= update_option(ROVER_OPTIONS_THEMING, $theme_options);

	$responseVar								= array(
														'success'		=> true		//	Folks are getting confused when we say 'Settings were not updated'
														);

														echo json_encode($responseVar);

	die();
	}

function rover_idx_reset_callback(){

	check_ajax_referer(ROVERIDX_NONCE, 'security');

	if ( ! current_user_can('manage_options') )
		return;

	roveridx_uninstall();

	echo json_encode(array('success'	=> true));

	die();
	}

function rover_idx_quick_start_create_callback()		{

	require_once ROVER_IDX_PLUGIN_PATH.'rover-database.php';

	check_ajax_referer(ROVERIDX_NONCE, 'security');

	if ( ! current_user_can('manage_options') )
		return;

	$responseVar	= array(
							'html'				=> roveridx_create_quick_setup_pages($_POST)
							);

	echo json_encode($responseVar);

	die();
	}

function rover_idx_quick_start_info_callback()	{

	require_once ROVER_IDX_PLUGIN_PATH.'rover-database.php';

	check_ajax_referer(ROVERIDX_NONCE, 'security');

	if ( ! current_user_can('manage_options') )
		return;

	$responseVar	= array(
							'html'				=> roveridx_fetch_quick_setup_pages()
							);

	echo json_encode($responseVar);

	die();
	}

function rover_idx_quick_start_reset_callback()	{

	require_once ROVER_IDX_PLUGIN_PATH.'rover-database.php';

	check_ajax_referer(ROVERIDX_NONCE, 'security');

	if ( ! current_user_can('manage_options') )
		return;

	$responseVar	= array(
							'html'				=> roveridx_reset_quick_setup_pages()
							);

	echo json_encode($responseVar);

	die();
	}

function rover_idx_refresh_js_ver_callback()	{

	check_ajax_referer(ROVERIDX_NONCE, 'security');

	if ( ! current_user_can('edit_posts') )
		return;

	require_once ROVER_IDX_PLUGIN_PATH.'rover-scheduled.php';

	$responseVar	= array(
							'ver'				=> roveridx_refresh_js_ver($force_refresh = true)
							);

	echo json_encode($responseVar);

	die();
	}

function rover_idx_show_settings_callback() {

	check_ajax_referer(ROVERIDX_NONCE, 'security');

	if ( ! current_user_can('manage_options') )
		return json_encode(['html'	=> 'Apologies - you are not authorized to see this data']);

	$the_html[]				= '<div>';

	foreach(array(
				'Region'	=> ROVER_OPTIONS_REGIONS,
				'Theme'		=> ROVER_OPTIONS_THEMING,
				'SEO'		=> ROVER_OPTIONS_SEO,
				'Social'	=> ROVER_OPTIONS_SOCIAL,
				)
			as $label => $option_key)
		{
		$the_html[]			=	'<div class="rover-row">';
		$the_html[]			=		'<div class="rover-col-12"><strong>'.$label.'</strong></div>';
		$the_html[]			=	'</div>';

		if (!is_array($opts = get_option($option_key)))
			$opts			= array();

		foreach ($opts as $key => $val) {
			$the_html[]		=	'<div class="rover-row">';
			$the_html[]		=		'<div class="rover-col-3">'.$key.'</div>';
			$the_html[]		=		(is_array($val) || is_object($val))
										? '<div class="rover-col-9" style="hyphens: auto;word-wrap: break-word;">'.print_r($val, true).'</div>'
										: '<div class="rover-col-9" style="hyphens: auto;word-wrap: break-word;">'.$val.'</div>';
			$the_html[]		=	'</div>';
			}
		}

	$the_html[]				=	'</div>';

	$responseVar = array(
						'html'		=> implode('', $the_html)
						);

	echo json_encode($responseVar);

	die();
	}

?>