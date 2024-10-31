<?php

require_once ROVER_IDX_PLUGIN_PATH.'admin/rover-templates.php';


// Render the Plugin options form
function roveridx_panel_mobile_form($atts) {

	global					$rover_idx;
			
	?>		
	<div class="wrap <?php echo esc_attr( rover_plugins_identifier() ); ?>" data-page="rover-panel-mobile">

		<div id="rover-mobile-panel" class="rover-tabs">

		<?php

			if (count($rover_idx->all_selected_regions) === 0)
				{
				echo 		'<div>Please select one or more Regions from the main RoverIDX settings panel.</div>';
				}
			else
				{
				require_once ROVER_IDX_PLUGIN_PATH.'rover-content.php';

				global $rover_idx_content;
				$rover_content	=		$rover_idx_content->rover_content(	'ROVER_COMPONENT_WP_MOBILE_PANEL', 
																		array(
																			'region'	=> $rover_idx->get_first_region(), 
																			'regions'	=> implode(',', array_keys($rover_idx->all_selected_regions)),
																			'settings'	=> get_option(ROVER_OPTIONS_SEO)
																			)
																		);					
				echo		$rover_content['the_html'];
				}
		?>

		<?php echo roveridx_panel_footer($panel = 'mobile');	?>

	</div><!-- wrap	-->

	<?php
	}


function rover_idx_mobile_callback() {

	check_ajax_referer(ROVERIDX_NONCE, 'security');

	$sitemap_disabled			= ($_POST['disabled'] === true || $_POST['disabled'] == 'true') 
											? true 
											: false;

	$seo_array					= array();
	$seo_array['disabled']		= $sitemap_disabled;
	$seo_array['updated']		= date("Y-m-d H:i:s");

	$r							= update_option(ROVER_OPTIONS_SEO, $seo_array);

	$responseVar = array(
	                    'disabled'	=> $seo_array['disabled'],
	                    'updated'	=> $seo_array['updated'],
	                    'success'	=> $r
	                    );

    echo json_encode($responseVar);
	
	die();
	}

add_action('wp_ajax_rover_idx_mobile', 'rover_idx_mobile_callback');


?>