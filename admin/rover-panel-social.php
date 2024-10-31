<?php
require_once ROVER_IDX_PLUGIN_PATH.'admin/rover-templates.php';
require_once ROVER_IDX_PLUGIN_PATH.'rover-content.php';

add_action('wp_ajax_rover_idx_social', 'rover_idx_social_callback');
add_action('wp_ajax_rover_idx_refresh_social', 'rover_idx_refresh_social_callback');

function roveridx_social_panel_form($atts) {

	global							$rover_idx, $wpdb;

	$posts							= $wpdb->get_results( "SELECT * FROM $wpdb->posts AS p
											LEFT JOIN $wpdb->term_relationships as r ON p.ID = r.object_ID
											LEFT JOIN $wpdb->term_taxonomy as tax ON r.term_taxonomy_id = tax.term_taxonomy_id
											LEFT JOIN $wpdb->terms as terms ON tax.term_id = terms.term_id
											WHERE	p.post_type = 'post'
											AND	p.post_status = 'publish'
											AND	p.ID = r.object_id
											AND terms.name = 'Rover IDX Property'" );

	$settings						= get_option(ROVER_OPTIONS_SOCIAL);

	$post_to_wp_comments			= (isset($settings['post_to_wp_comments']))
											? $settings['post_to_wp_comments']
											: false;

	$email_on_error					= (isset($settings['email_on_error']))
											? $settings['email_on_error']
											: false;

	$email_on_post					= (isset($settings['email_on_post']))
											? $settings['email_on_post']
											: false;

	# Get the ID of a given category
	$category_id					= get_cat_ID( 'Rover IDX Property' );

    # Get the URL of this category
	$category_link					= get_category_link( $category_id );

	$settings['control_post_to_wp_as_user']	= rover_wp_user_select($settings, 'post_to_wp_as_user');
	$settings['wp_post_count']		= count($posts);
	$settings['wp_posts_url']		= $category_link;

	$the_content					= Rover_IDX_Content::rover_content(
																'ROVER_COMPONENT_SOCIAL_PANEL',
																array(
																	'region'	=> $rover_idx->get_first_region(),
																	'regions'	=> implode(',', array_keys($rover_idx->all_selected_regions)),
																	'settings'	=> $settings
																	)
																);
	?>

	<div id="wp_defaults" class="wrap" data-page="rover-panel-social">

	<?php echo roveridx_panel_header();	?>

		<div id="rover-social-panel">

			<?php
			echo $the_content['the_html'];
			?>

			<p class="submit">
				<span id="jq_msg"></span>
			</p>

		</div>

	<?php echo roveridx_panel_footer($panel = 'social');	?>

	</div>

<?php

	}

function rover_idx_social_defaults() {
	return array(
						'post_new'					=> 'disabled',
						'post_price_change'			=> 'disabled',
						'post_sold'					=> 'disabled',
						'post_open_houses'			=> 'disabled',
						'post_monthly_data'			=> 'disabled',

						'post_to_wp'				=> 'disabled',
						'post_to_wp_as_user'		=> 1,
						'post_to_wp_comments'		=> 'disabled',
						'post_to_fb'				=> 'disabled',
						'post_to_gp'				=> 'disabled',
						'post_to_tw'				=> 'disabled',

						'email_on_error'			=> 'disabled',
						'email_on_error_to_user'	=> '',
						'email_on_post'				=> 'disabled',
						'email_on_post_to_user'		=> '',

						'publish_office_listings'	=> 'active_agents',

						'fb_access_token'			=> null,
						'facebook_app'				=> 'disabled'
						);
	}


function rover_idx_social_callback() {

	check_ajax_referer(ROVERIDX_NONCE, 'security');

	if ( current_user_can('edit_posts') )
		{
		$text_fields								= array(
													'post_to_wp', 'post_to_fb', 'post_to_tw',
													'post_new', 'post_price_change', 'post_sold',
													'post_open_houses', 'post_monthly_data',
													'post_to_wp_comments', 'publish_office_listings',
													'email_on_error', 'email_on_post',
													'facebook_app', 'fb_app_id', 'fb_access_token', 'fb_name'
													);
		$int_fields										= array(
													'post_to_wp_as_user', 'email_on_error_to_user', 'email_on_post_to_user'
													);
		$settings										= get_option(ROVER_OPTIONS_SOCIAL);

		foreach($_POST as $key => $val)
			{
			#	'enabled' or 'disabled'

			if (in_array($key, $text_fields))
				$settings[$key]						= sanitize_text_field($_POST[$key]);
			else if (in_array($key, $int_fields))
				$settings[$key]						= intval($_POST[$key]);
			}

		$settings['rand']							= rand(0,1500);

		$r											= update_option(ROVER_OPTIONS_SOCIAL, $settings);

		$responseVar								= array(
													'success'					=> $r,
													'post_to_wp'				=> $settings['post_to_wp'],
													'post_to_fb'				=> $settings['post_to_fb'],
													'post_to_gp'				=> $settings['post_to_gp'],
													'post_to_tw'				=> $settings['post_to_tw'],
													'facebook_app'				=> $settings['facebook_app']
													);

    	echo json_encode($responseVar);
		}

	die();
	}

function rover_idx_refresh_social_callback() {

	check_ajax_referer(ROVERIDX_NONCE, 'security');

	$responseVar		= array(
						'success'				=> false,
						'response'				=> 'Apologies - not authorized'
						);

	if ( current_user_can('edit_posts') )
		{
		require_once ROVER_IDX_PLUGIN_PATH.'rover-social-common.php';

		$refresh_ret		= Rover_IDX_SOCIAL::refresh($fix_missing_images = true);

		$responseVar		= array(
							'success'			=> true,
							'response'			=> $refresh_ret
							);
		}

	echo json_encode($responseVar);

	die();
	}

function rover_wp_user_select($settings, $key)
	{
	global $wpdb;

	$the_html		= array();

	$authors		= $wpdb->get_results("SELECT ID, user_nicename from $wpdb->users ORDER BY display_name");
	foreach($authors as $author)
		{
		$selected	= (count($authors) == 1)
							? 'selected=selected'
							: roveridx_val_is_selected($settings, $key, $author->ID);

		$the_html[]	=	"<option value='".$author->ID."' ".$selected."> ".$author->user_nicename."</option>";
		}

	return implode('', $the_html);
	}

?>