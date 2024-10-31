<?php

require_once ROVER_IDX_PLUGIN_PATH.'admin/rover-templates.php';
require_once ROVER_IDX_PLUGIN_PATH.'admin/rover-panel-lists.php';
require_once ROVER_IDX_PLUGIN_PATH.'rover-content.php';
require_once ROVER_IDX_PLUGIN_PATH.'rover-sitemap.php';

add_action('wp_ajax_rover_idx_seo',										'rover_idx_seo_callback');
add_action('wp_ajax_rover_idx_do_sitemap',								'rover_idx_do_sitemap_callback');
add_action('wp_ajax_rover_idx_sitemap_history',							'rover_idx_sitemap_history_callback');
add_action('wp_ajax_rover_idx_create_city_dynamic_definitions',			'rover_idx_create_city_dynamic_definitions_callback');
add_action('wp_ajax_rover_idx_create_subdivision_dynamic_definitions',	'rover_idx_create_subdivision_dynamic_definitions_callback');


// Render the Plugin options form
function roveridx_panel_seo_form($wp_settings) {

	global					$rover_idx;

	if (!is_array($seo_options = get_option(ROVER_OPTIONS_SEO)))
		$seo_options		= array();

	$rover_content			= Rover_IDX_Content::rover_content(
																'ROVER_COMPONENT_SEO_PANEL',
																array(
																	'region'	=> $rover_idx->get_first_region(),
																	'regions'	=> implode(',', array_keys($rover_idx->all_selected_regions)),
																	'settings'	=> $seo_options
																	)
																);

	?>
	<div class="wrap" data-page="rover-panel-seo">

		<?php echo roveridx_panel_header();	?>

		<div id="rover-seo-panel">
			<?php echo $rover_content['the_html']; ?>

			<div class="rover-site-pages" style="display:none;"><?php echo roveridx_site_pages(); ?></div>
			<input type="hidden" id="sitemap_enabled_in_options" value="<?php echo roveridx_sitemap_enabled(); ?>">
			<input type="hidden" id="sitemap_updated" value="<?php echo roveridx_sitemap_updated(); ?>">
			<input type="hidden" id="redirect_crawler" value="<?php echo roveridx_crawler_redirect(); ?>">

			<?php
			if (false) {
			?>
			<input type="hidden" name="count_meta_dynamic" value="<?php echo roveridx_count_meta_dynamic(); ?>">
			<input type="hidden" name="count_meta_sidebar" value="<?php echo roveridx_count_meta_sidebar(); ?>">
			<?php
				}
			?>
			<?php echo roveridx_panel_footer($panel = 'seo');	?>

		</div>

	</div><!-- wrap	-->

	<?php

	}


function roveridx_create_seo_meta_panel()	{

	global					$rover_idx, $wpdb;

	if (!is_array($seo_options = get_option(ROVER_OPTIONS_SEO)))
		$seo_options		= array();

	$rover_content			= Rover_IDX_Content::rover_content(	'ROVER_COMPONENT_WP_SEO_DYNAMIC_PAGE_DEF_PANEL',
															array(
																'region'	=> $rover_idx->get_first_region(),
																'regions'	=> implode(',', array_keys($rover_idx->all_selected_regions)),
																'settings'	=> $seo_options
																)
															);
	$the_help				= $rover_content['the_html'];

	$table_opts				= array(
									'cpt'	=> ROVER_IDX_CUSTOM_POST_DYNAMIC_META,
									'top'	=> '<div class="container-fluid">
													<div class="rover-layout-help">'.$the_help.'</div><!-- row -->
													<div class="btn-toolbar">
														<a href="'.admin_url('post-new.php?post_type='.ROVER_IDX_CUSTOM_POST_DYNAMIC_META).'">
															<button type="button" class="add_new_meta btn btn-sm btn-primary mr-2" aria-hidden="true"><span class="fa fa-magic"></span>&nbsp;Add Dynamic Page Definition</button>
														</a>
														<button type="button" class="add_new_meta_subdivisions btn btn-sm mr-2 btn-primary float-right" aria-hidden="true"><span class="fa fa-plus"></span>&nbsp;Subdivisions</button>
														<button type="button" class="add_new_meta_cities btn btn-sm btn-primary float-right" aria-hidden="true"><span class="fa fa-plus"></span>&nbsp;Cities</button>
														<div class="clearfix"></div>
													</div><!-- row -->
												</div><!-- container-fluid -->'
									);

	$wp_list_table	= new Rover_List_Table($table_opts);
	$wp_list_table->prepare_items("SELECT * FROM $wpdb->posts WHERE post_type = '".ROVER_IDX_CUSTOM_POST_DYNAMIC_META."' AND post_status = 'publish'");

	$wp_list_table->display();

	roveridx_seo_dynamic_city_dialog();
	roveridx_seo_dynamic_subdivision_dialog();
	}

function roveridx_create_seo_sidebar_panel()	{

	global			$wpdb;

	$table_opts		= array(
							'cpt'	=> ROVER_IDX_CUSTOM_POST_DYNAMIC_SIDEBAR,
							'top'	=> '<div class="container-fluid">
											<div class="row rover-layout-help">
												<span class="bold">Dynamic Page Sidebars</span> are designed to be used with <span class="bold">Dynamic Page Definitions</span>.  If your property detail page is configured to use a template that displays a sidebar, you can configure what is displayed within that sidebar here.  This has great lead generation tool potential - for instance, adding a call-to-action in the sidebar with specific property references.
											</div>
											<div class="row btn-toolbar">
												<a href="'.admin_url('post-new.php?post_type='.ROVER_IDX_CUSTOM_POST_DYNAMIC_SIDEBAR).'">
													<button type="button" class="add_new_meta btn btn-sm btn-primary" aria-hidden="true"><span class="fa fa-magic"></span>&nbsp;Add Dynamic Page Sidebar</button>
												</a>
											</div>
										</div>'
							);

	$wp_list_table	= new Rover_List_Table($table_opts);
	$wp_list_table->prepare_items("SELECT * FROM $wpdb->posts WHERE post_type = '".ROVER_IDX_CUSTOM_POST_DYNAMIC_SIDEBAR."' AND post_status = 'publish'");

	$wp_list_table->display();
	}

function rover_idx_seo_defaults() {

	return array(
				'sitemap_enabled'	=> 'enabled',
				'crawler_redirect'	=> '404'
				);

	}

function roveridx_site_pages()
	{
	$the_html								= array();
	foreach ( get_pages(array('post_status' => 'publish')) as $page )
		{
		$the_html[]							= '<option value="' . get_page_link( $page->ID ) . '">'.$page->post_title.'</option>';
		}

	return implode('', $the_html);
	}

function roveridx_sitemap_enabled()
	{
	if (!is_array($seo_options = get_option(ROVER_OPTIONS_SEO)))
		$seo_options		= array();

	$enabled								= (isset($seo_options['sitemap_enabled']) && ($seo_options['sitemap_enabled'] == 'disabled'))
													? 'disabled'
													: 'enabled';

	return $enabled;
	}

function roveridx_sitemap_updated()
	{
	if (!is_array($seo_options = get_option(ROVER_OPTIONS_SEO)))
		$seo_options						= array();

	if (roveridx_sitemap_enabled() === "enabled")
		{
		if (isset($seo_options['updated']) && !empty($seo_options['updated']))
			return $seo_options['updated'];
		}

	return null;
	}

function roveridx_crawler_redirect()
	{
	if (!is_array($seo_options = get_option(ROVER_OPTIONS_SEO)))
		$seo_options						= array();
	$crawler_redirect						= (isset($seo_options['redirect_crawler']))
													? $seo_options['redirect_crawler']
													: 'home';

	return $crawler_redirect;
	}

function roveridx_count_meta_dynamic()
	{
	global									$wpdb;

	$num									= $wpdb->get_var("SELECT count(*) as the_count FROM $wpdb->posts
														WHERE post_type = '".ROVER_IDX_CUSTOM_POST_DYNAMIC_META."'
														AND post_status = 'publish'");

	return $num;
	}

function roveridx_count_meta_sidebar()
	{
	global									$wpdb;

	$num									= $wpdb->get_var("SELECT count(*) as the_count FROM $wpdb->posts
														WHERE post_type = '".ROVER_IDX_CUSTOM_POST_DYNAMIC_SIDEBAR."'
														AND post_status = 'publish'");

	return $num;
	}

function rover_idx_refresh_dynamic_definitions()
	{
	global						$wpdb;

	$wp_list_table				= new Rover_List_Table();
	$wp_list_table->prepare_items("SELECT * FROM $wpdb->posts WHERE post_type = '".ROVER_IDX_CUSTOM_POST_DYNAMIC_META."' AND post_status = 'publish'");

	ob_start();
	$wp_list_table->display_rows_or_placeholder();

	return ob_get_clean();
	}

function roveridx_seo_dynamic_city_dialog()
	{
	global						$rover_idx, $wpdb;

	$rover_content				= Rover_IDX_Content::rover_content(	'ROVER_COMPONENT_GET_STATES_AND_CITIES',
																	array(
																		'region'	=> $rover_idx->get_first_region(),
																		'regions'	=> implode(',', array_keys($rover_idx->all_selected_regions))
																		)
																	);

	$checkbox_html				= array();

	$all_cities					= json_decode($rover_content['the_html'], true);
	$all_cities					= (is_array($all_cities))
										? $all_cities
										: array();
	foreach ($all_cities as $one_state => $cities)
		{
		$checkbox_html[]		=	'<div class="col-md-12"><h4>'.$one_state.'</h4></div>';

		foreach ($cities as $one_city)
			{
			$disabled			= false;

			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, "SELECT ID FROM $wpdb->posts
												WHERE post_type = '".ROVER_IDX_CUSTOM_POST_DYNAMIC_META."'
												AND post_status = 'publish'
												AND post_name = '".preg_replace('/[^a-zA-Z0-9]/', '', $one_state.$one_city)."'");

			$dyn_meta			= $wpdb->get_row("SELECT ID FROM $wpdb->posts
												WHERE post_type = '".ROVER_IDX_CUSTOM_POST_DYNAMIC_META."'
												AND post_status = 'publish'
												AND post_name = '".preg_replace('/[^a-zA-Z0-9]/', '', $one_state.$one_city)."'");

			if (!is_null($dyn_meta))
				$disabled		= true;

			$checkbox_html[]	=	'<div class="col-md-4'.(($disabled) ? ' disabled' : '').'">
										<div class="checkbox">
											<label>
												<input name="city[]" type="checkbox" value="'.esc_html($one_city).'" '.(($disabled) ? 'disabled' : '').' /> '.esc_html($one_city).'
											</label>
										</div>
									</div>';
			}
		}

	echo 		'<div class="modal fade" id="roveridx_seo_dynamic_city_dialog">
					<div class="modal-dialog modal-lg">
						<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
								<h4 class="modal-title">Create Dynamic Meta Definitions for Selected Cities</h4>
							</div>
							<div class="modal-body">
								<form class="form-inline" role="form">
									'.implode('', $checkbox_html).'
									<div class="clearfix"></div>
								</form>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-default sel-all pull-left">Select All</button>
								<button type="button" class="btn btn-default sel-none pull-left">Select None</button>

								<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
								<button type="button" class="btn btn-primary">Create</button>
							</div>
						</div><!-- /.modal-content -->
					</div><!-- /.modal-dialog -->
				</div><!-- /.modal -->';
	}

function roveridx_seo_dynamic_subdivision_dialog()
	{
	global					$rover_idx, $wpdb;

	$dyn_data				= array();
	foreach($wpdb->get_results("SELECT ID FROM $wpdb->posts
												WHERE post_type = '".ROVER_IDX_CUSTOM_POST_DYNAMIC_META."'
												AND post_status = 'publish'
												AND post_name LIKE '%subdivision%'") as $one)
		{
		$dyn_meta[]			= $one->post_name;
		}

	#	preg_replace('/[^a-zA-Z0-9]/', '', ($state.'subdivision'.$one_subdivision))

	echo 		'<div class="modal fade" id="roveridx_seo_dynamic_subdivision_dialog" data-dyn="'.implode(',', $dyn_meta).'">
					<div class="modal-dialog modal-lg">
						<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
								<h4 class="modal-title">Create Dynamic Meta Definitions for Selected Subdivisions</h4>
							</div>
							<div class="modal-body">
								<form class="form-inline" role="form">
									<div style="margin: 100px auto;"><i class="fa fa-cog fa-spin fa-3x fa-fw"></i></div>
								</form>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-default sel-all pull-left">Select All</button>
								<button type="button" class="btn btn-default sel-none pull-left">Select None</button>

								<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
								<button type="button" class="btn btn-primary">Create</button>
							</div>
						</div><!-- /.modal-content -->
					</div><!-- /.modal-dialog -->
				</div><!-- /.modal -->';
	}



/*************************************************/
//	Callbacks
/*************************************************/

function rover_idx_seo_callback()	{

	check_ajax_referer(ROVERIDX_NONCE, 'security');

	if ( current_user_can('edit_posts') )
		{
		if (!is_array($seo_opts = get_option(ROVER_OPTIONS_SEO)))
			$seo_opts					= array();

		$seo_opts['sitemap_enabled']	= sanitize_text_field( $_POST['sitemap_enabled'] );
		$seo_opts['crawler_redirect']	= sanitize_text_field( $_POST['crawler_redirect'] );

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'POST[sitemap_enabled] ['.$_POST['sitemap_enabled'].']');
		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'seo_opts[disabled] ['.($seo_opts['sitemap_enabled']).']');
		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'seo_opts[crawler_redirect] ['.$seo_opts['crawler_redirect'].']');

		$r								= update_option(ROVER_OPTIONS_SEO, $seo_opts);

		$responseVar					= array(
	                    				'success'	=> true
	                    				);

		echo json_encode($responseVar);
		}

	die();
	}

function rover_idx_do_sitemap_callback() {

	check_ajax_referer(ROVERIDX_NONCE, 'security');

	if ( current_user_can('edit_posts') )
		{
		$ret							= Rover_IDX_SITEMAP::build($force_refresh = true);

		$history						= Rover_IDX_SITEMAP::history();

		if (is_array($ret))
			$ret['html']				= $history['html'];
		else
			$ret['html']				= 'sitemap refresh failed for an unknown reason.';

	    echo json_encode($ret);
		}

	die();
	}


function rover_idx_sitemap_history_callback() {

	check_ajax_referer(ROVERIDX_NONCE, 'security');

	if ( current_user_can('edit_posts') )
		{
		$history						= Rover_IDX_SITEMAP::history();

		$responseVar = array(
	    				                'html'		=> $history['html'],
										'never'		=> $history['never'],
										'domain'	=> get_site_url(),
	                    				'success'	=> true
	                    				);

		echo json_encode($responseVar);
		}

	die();
	}

function rover_idx_create_city_dynamic_definitions_callback()	{

	global						$wpdb;

	check_ajax_referer(ROVERIDX_NONCE, 'security');

	if ( current_user_can('edit_posts') )
		{
		$added					= 0;
		$cities					= array();
		foreach (explode('&', sanitize_text_field( $_POST['cities'] ) ) as $post_key => $post_val)
			{
			$one_row			= explode('=', $post_val);
			$one_location_data	= explode('__', $one_row[1]);
			$state				= $one_location_data[0];
			$city				= $one_location_data[1];

			$dyn_meta			= $wpdb->get_row(
								"SELECT ID FROM $wpdb->posts
								WHERE post_type = '".ROVER_IDX_CUSTOM_POST_DYNAMIC_META."'
								AND post_status = 'publish'
								AND post_name = '".preg_replace('/[^a-zA-Z0-9]/', '', $state.$city)."'"
								);

			if (is_null($dyn_meta))
				{
				//	Doesn't exist - add it

				$id = wp_insert_post( array(
								'comment_status' => 'closed',
								'ping_status'    => 'closed',
								'post_content'   => '[rover_crm_inbound]',
								'post_name'      => preg_replace('/[^a-zA-Z0-9]/', '', $state.'/'.$city),
								'post_status'    => 'publish',
								'post_title'     => $state.'/'.$city,
								'post_type'      => ROVER_IDX_CUSTOM_POST_DYNAMIC_META
								), $wp_error );

				if ($id)
					{
					add_post_meta($id, 'rover_idx_page_title', 'Homes for sale in '.$city);
					$added++;
					}
				}
			}

		$responseVar			= array(
	                    		'success'	=> $added,
	                    		'tbody'		=> rover_idx_refresh_dynamic_definitions()
	                    		);

    	echo json_encode($responseVar);
		}

	die();
	}

function rover_idx_create_subdivision_dynamic_definitions_callback()	{

	global						$wpdb;

	check_ajax_referer(ROVERIDX_NONCE, 'security');

	if ( current_user_can('edit_posts') )
		{
		$added					= 0;
		$cities					= array();
		foreach (explode('&', sanitize_text_field( $_POST['subdivisions'] ) ) as $post_key => $post_val)
			{
			$one_row			= explode('=', $post_val);
			$one_location_data	= explode('__', $one_row[1]);
			$state				= $one_location_data[0];
			$city				= $one_location_data[1];
			$subdivision		= $one_location_data[2];

			$dyn_meta			= $wpdb->get_row(
								"SELECT ID FROM $wpdb->posts
								WHERE post_type = '".ROVER_IDX_CUSTOM_POST_DYNAMIC_META."'
								AND post_status = 'publish'
								AND post_name = '".preg_replace('/[^a-zA-Z0-9]/', '', $state.'subdivision'.$subdivision)."'"
								);

			if (is_null($dyn_meta))
				{
				//	Doesn't exist - add it

				$id = wp_insert_post( array(
								'comment_status' => 'closed',
								'ping_status'    => 'closed',
								'post_content'   => '[rover_crm_inbound]',
								'post_name'      => preg_replace('/[^a-zA-Z0-9]/', '', $state.'subdivision'.$subdivision),
								'post_status'    => 'publish',
								'post_title'     => $state.'/subdivision/'.$subdivision,
								'post_type'      => ROVER_IDX_CUSTOM_POST_DYNAMIC_META
								), $wp_error );

				if ($id)
					{
					add_post_meta($id, 'rover_idx_page_title', 'Homes for sale in '.$city.' subdivision of '.$subdivision);
					$added++;
					}
				}
			}

		$responseVar			= array(
	                    		'success'	=> $added,
	                    		'tbody'		=> rover_idx_refresh_dynamic_definitions()
	                    		);

    	echo json_encode($responseVar);
		}

	die();
	}
?>