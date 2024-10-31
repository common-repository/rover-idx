<?php

require_once ROVER_IDX_PLUGIN_PATH.'admin/rover-templates.php';

// Render the Plugin options form
function roveridx_panel_help_form() {

	$the_html			= array();
	$the_html[] 		= roveridx_panel_header();

	$the_html[]			= '<div style="min-height:800px;" class="rover-admin-framework">';
	$the_html[]			=	'<iframe src="https://roveridx.com/documentation/shortcode-help/" style="width:100%;height:800px;"></iframe>';
	$the_html[]			= '</div>';

	$the_html[] 		= roveridx_panel_footer('help');

	echo implode('', $the_html);
	}
?>