<?php
require_once ROVER_IDX_PLUGIN_PATH.'admin/rover-templates.php';
require_once ROVER_IDX_PLUGIN_PATH.'rover-content.php';


function roveridx_panel_lead_form($atts) {

	global					$rover_idx;

	$rover_content			= Rover_IDX_Content::rover_content(
																'ROVER_COMPONENT_WP_LEAD_GENERATION_PANEL'
																);
	?>		
	<div class="wrap" data-page="rover-panel-lead-generation">

		<?php echo roveridx_panel_header();	?>

		<div id="rover_lead_generation">
			<?php echo $rover_content['the_html'];	?>

			<?php echo roveridx_panel_footer($panel = 'lead_generation');	?>
		</div>

	</div><!-- wrap	-->

	<?php
	}


?>