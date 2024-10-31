<?php

function roveridx_panel_header() {

	require_once ROVER_IDX_PLUGIN_PATH.'rover-content.php';

	global						$rover_idx;

	if (!is_array($seo_options = get_option(ROVER_OPTIONS_SEO)))
		$seo_options			= array();

	$rover_content				= Rover_IDX_Content::rover_content(
																	'ROVER_COMPONENT_SETTINGS_HEADER',
																	array(
																		'region'	=> $rover_idx->get_first_region(),
																		'regions'	=> implode(',', array_keys($rover_idx->all_selected_regions)),
																		'settings'	=> $seo_options
																		)
																	);
	return $rover_content['the_html'];
	}

function roveridx_panel_footer($panel)	{

	$current_user	= wp_get_current_user();
	$upload_base	= wp_upload_dir();
	$label_id		= rand(1,9999);

	$the_html		= array();
	$the_html[]		= '<footer class="'.IDX_PLUGIN_NAME.'" data-footer_type="'.$panel.'">';
	$the_html[]		=		'<div style="display:inline-block;width:50%;">';
	$the_html[]		=			'<center><strong>Rover IDX</strong> '.ROVER_VERSION_FULL.' <span style="color:#999;font-size:13px;">on PHP version '.phpversion().'</span></center>';
	$the_html[]		=		'</div>';
	$the_html[]		=		'<div style="display:inline-block;width:50%;">';
	$the_html[]		=			'<center>';
	$the_html[]		=				'<a href="https://www.facebook.com/RoverIDX" title="Rover IDX Facebook page" target="_blank">';
	$the_html[]		=					'<img style="border:none;margin-left:10px;" src="'.ROVER_IDX_PLUGIN_URL.'/images/facebook-icon.png" />';
	$the_html[]		=				'</a>';
	$the_html[]		=			'</center>';
	$the_html[]		=		'</div>';

	$the_html[]		=		'<input type="hidden" id="rover_idx" name="rover_idx" value="1" />';

	$the_html[]		=		'<input type="hidden" id="wp_security" name="security" value="'.wp_create_nonce(ROVERIDX_NONCE).'" />';

	$the_html[]		=		'<input type="hidden" name="wp_name" value="'.sanitize_text_field( $current_user->display_name ).'" />';
	$the_html[]		=		'<input type="hidden" name="wp_email" value="'.sanitize_email( $current_user->user_email ).'" />';
	$the_html[]		=		'<input type="hidden" name="kit" value="'.ROVER_AFFILIATE.'" />';

	$the_html[]		=		'<input type="hidden" name="wp_site_url" class="no-serial" value="'.get_site_url().'" />';
	$the_html[]		=		'<div class="rover-confirm modal fade" role="dialog" aria-labelledby="#'.$label_id.'" aria-hidden="true" style="display:none;position:fixed;top:50%;left:25%;width:50%;z-index:1051;">';
	$the_html[]		=			'<div class="modal-dialog" style="max-width:100%;">';
	$the_html[]		=				'<div class="modal-content">';
	$the_html[]		=					'<div class="modal-header">';
	$the_html[]		=						'<h4 class="modal-title" style="float:left;margin:0;" id="'.$label_id.'">Your question goes here</h4>';
	$the_html[]		=						'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>';
	$the_html[]		=						'<div style="clear:both;"></div>';
	$the_html[]		=						'</div>';
	$the_html[]		=					'<div class="modal-body">';
	$the_html[]		=						'<i class="fa fa-cog fa-spin fa-2x fa-fw" style="margin:30px auto;padding:0;border:0;text-align:center;"></i>';
	$the_html[]		=					'</div>';
	$the_html[]		=					'<div class="modal-footer">';
	$the_html[]		=						'<button type="button" class="yes btn btn-primary float-right" style="margin:0 5px;">Yes</button>';
	$the_html[]		=						'<button type="button" class="no btn btn-primary float-right"  style="margin:0 5px;">No</button>';
	$the_html[]		=					'</div>';
	$the_html[]		=				'</div><!-- /.modal-content -->';
	$the_html[]		=			'</div><!-- /.modal-dialog -->';
	$the_html[]		=		'</div><!-- /#edit_client -->';
	$the_html[]		= '</footer><!-- footer -->';

	return implode('', $the_html);
	}

?>