<?php
require_once ROVER_IDX_PLUGIN_PATH.'rover-shared.php';
require_once ROVER_IDX_PLUGIN_PATH.'admin/rover-panel-setup.php';
require_once ROVER_IDX_PLUGIN_PATH.'admin/rover-panel-styling.php';
require_once ROVER_IDX_PLUGIN_PATH.'admin/rover-panel-lead-generation.php';
require_once ROVER_IDX_PLUGIN_PATH.'admin/rover-panel-seo.php';
require_once ROVER_IDX_PLUGIN_PATH.'admin/rover-panel-migrate-ds.php';
require_once ROVER_IDX_PLUGIN_PATH.'admin/rover-panel-social.php';
require_once ROVER_IDX_PLUGIN_PATH.'admin/rover-panel-help.php';
require_once ROVER_IDX_PLUGIN_PATH.'rover-database.php';

class Rover_IDX_admin
	{
	public function __construct ()
		{
		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__,		'this is the admin panel');

		if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'customize.php') !== false)
			return false;

		require_once ROVER_IDX_PLUGIN_PATH.'widgets/init.php';

		add_action('admin_init',								array( $this, 'roveridx_admin_init'));
		add_action('admin_head',								array( $this, 'roveridx_mce_buttons'));
		add_action('admin_menu',								array( $this, 'roveridx_admin_menu'));
		add_action('admin_enqueue_scripts', 					'roveridx_css_and_js' );

		add_action('update_option_permalink_structure',			array( $this, 'roveridx_update_permalink'));

		add_filter('plugin_action_links',						'roveridx_plugin_action_links', 10, 2 );

		add_action( 'wp_dashboard_setup', 						array( $this, 'roveridx_dashboard_active_summary'));
		add_action( 'wp_dashboard_setup', 						array( $this, 'roveridx_dashboard_visitor_activity'));
		add_action( 'wp_dashboard_setup', 						array( $this, 'roveridx_dashboard_mail'));

		add_filter( 'admin_body_class', 						array( $this, 'roveridx_admin_body_class'));
		}

	function roveridx_admin_init()	{

		global $rover_idx;

		register_setting( 'roveridx_region_options', 			ROVER_OPTIONS_REGIONS, 'roveridx_validate_options' );
		register_setting( 'roveridx_theming_options',			ROVER_OPTIONS_THEMING );
		register_setting( 'roveridx_seo_options',				ROVER_OPTIONS_SEO );
		register_setting( 'roveridx_social_options',			ROVER_OPTIONS_SOCIAL );

		if (count($rover_idx->all_selected_regions) == 0)
			add_action('admin_notices',							array($this, 'roveridx_admin_notice_no_region_set'));

		if (empty(get_option('permalink_structure')))
			add_action('admin_notices',							array($this, 'roveridx_admin_notice_permalink_unsupported'));

		if ($this->roveridx_has_unmigrated_diverse_solutions('notice'))
			add_action('admin_notices',							array($this, 'roveridx_admin_notice_diverse_solutions_migration'));
		}

	function roveridx_admin_menu() {

		add_menu_page(
				'Rover IDX Real Estate Solution - Management Page',
				'Rover IDX',
				'manage_options',
				IDX_PLUGIN_NAME,
				'roveridx_panel_setup_form',
				ROVER_IDX_PLUGIN_URL.'/images/roverLogo16.png',
				$position = null);
		add_submenu_page(
				IDX_PLUGIN_NAME,							//	$parent_slug,
				'Rover IDX Look and Feel',					//	$page_title,
				'Styling',									//	$menu_title,
				'edit_posts',								//	$capability,
				IDX_PLUGIN_NAME.'-styling',					//	$menu_slug,
				'roveridx_panel_styling_form');				//	$function
		add_submenu_page(
				IDX_PLUGIN_NAME,							//	$parent_slug,
				'Rover IDX Lead Panel',						//	$page_title,
				'Lead Generation',							//	$menu_title,
				'manage_options',							//	$capability,
				IDX_PLUGIN_NAME.'-lead-generation',			//	$menu_slug,
				'roveridx_panel_lead_form');				//	$function
		add_submenu_page(
				IDX_PLUGIN_NAME,							//	$parent_slug,
				'Rover IDX SEO Panel',						//	$page_title,
				'SEO',										//	$menu_title,
				'manage_options',							//	$capability,
				IDX_PLUGIN_NAME.'-seo',						//	$menu_slug,
				'roveridx_panel_seo_form');					//	$function
		add_submenu_page(
				IDX_PLUGIN_NAME,							//	$parent_slug,
				'Rover IDX Social Panel',					//	$page_title,
				'Social',									//	$menu_title,
				'edit_posts',								//	$capability,
				IDX_PLUGIN_NAME.'-social',					//	$menu_slug,
				'roveridx_social_panel_form');				//	$function
		add_submenu_page(
				IDX_PLUGIN_NAME,							//	$parent_slug,
				'Help',										//	$page_title,
				'Help',										//	$menu_title,
				'edit_posts',								//	$capability,
				IDX_PLUGIN_NAME.'-help',					//	$menu_slug,
				'roveridx_panel_help_form');				//	$function

		if ($this->roveridx_has_unmigrated_diverse_solutions('menu'))
			{
			add_submenu_page(
				IDX_PLUGIN_NAME,							//	$parent_slug,
				'Rover IDX Migrate Diverse Solutions',		//	$page_title,
				'Migrate DS',								//	$menu_title,
				'edit_posts',								//	$capability,
				IDX_PLUGIN_NAME.'-migrate-ds',				//	$menu_slug,
				'roveridx_migrate_ds_panel_form');			//	$function
			}

		global $submenu;
		if ( isset( $submenu[IDX_PLUGIN_NAME] ) )
			$submenu[IDX_PLUGIN_NAME][0][0] = __( 'General', IDX_PLUGIN_NAME );
		}

	function roveridx_admin_notice_permalink_unsupported()	{

		$class 			= 'notice notice-warning rover-notice is-dismissible';
		$message		= 'Rover IDX does not support the \"?p=123\" WP Permalink structure.  Please choose a different permalink setting so Rover Dynamic Pages display correctly.';

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
		}

	function roveridx_admin_notice_diverse_solutions_migration()	{

		$class 			= 'notice notice-info rover-notice rover-notice-ds is-dismissible';
		$message		= array();
		$message[]		= '<a href="admin.php?page=rover-panel-migrate-ds">Click here</a> to migrate your <strong>Diverse Solutions</strong> IDX pages to <strong>Rover IDX</strong> pages';
		$message[]		= 'All meta and settings will be maintained, and the DS IDX Data Filters will be converted to the appropriate Rover IDX shortcode.';

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, implode('<br>', $message) );

		}

	function roveridx_admin_notice_no_region_set()	{

		global			$rover_idx;

		$class 			= 'notice notice-warning rover-notice is-dismissible';
		$message		= 'Rover IDX - Please <a href="'.admin_url('admin.php?page=rover-idx').'">select an MLS region</a>.';

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
		}

	function roveridx_admin_body_class($classes) {

		$rover_classes				= array($classes, 'rover-enabled');

		if (defined('ROVER_INSTALLATION_SOURCE'))
			{
			if ( ROVER_INSTALLATION_SOURCE == "installation-source-native-repo")
				$rover_classes[]	= 'rover-native';
			elseif ( ROVER_INSTALLATION_SOURCE == "installation-source-wp-repo")
				$rover_classes[]	= 'rover-wp-repo';
			}

		return implode(' ', $rover_classes);
		}

	function roveridx_update_permalink()	{

		global 								$rover_idx;

		$perm								= get_option('permalink_structure');
		$url_ends_with_slash				= ($perm && substr($perm, -1) != '/')
													? false
													: true;

		$rover_idx->update_region_settings(__FUNCTION__, __LINE__, 'url_ends_with_slash', $url_ends_with_slash);
		}


	function roveridx_mce_buttons() {

		if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
			return;

		if ( get_user_option('rich_editing') == 'true' ) {

			add_filter('mce_buttons',			array( $this, 'roveridx_mce_shortcode_button' ));
			add_filter("mce_external_plugins",	array( $this, 'roveridx_tinymce_plugin' ));
			}
		}

	function roveridx_tinymce_plugin($plugin_array) {
		$plugin_array['roveridx_shortcode_button'] = plugins_url('js/rover-shortcode-buttons.js', __FILE__);
		return $plugin_array;
		}

	function roveridx_mce_shortcode_button($buttons) {

		array_push($buttons, "|", "roveridx_shortcode_button");
		return $buttons;
		}

	function roveridx_setup_rover_page()	{

		global 		$wpdb;
		$post_id 	= null;

		$one_row = $wpdb->get_row( "SELECT * FROM $wpdb->postmeta WHERE meta_key = '".ROVERIDX_META_PAGE_ID."'" );

		//	If we've setup a rover post_id in the past, use it

		if (is_null($one_row))
			{
			$rover_post					= array();
			$rover_post['post_title']	= 'Rover IDX page';
			$rover_post['post_content']	= 'This post will be deleted immediately';
			$rover_post['post_type']	= 'page';
			$rover_post['post_status']	= 'draft';
			$rover_post['post_author']	= 0;

			//	Insert the post into the database
			$post_id					= wp_insert_post( $rover_post );
			if (!is_wp_error($post_id)) {

				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Create post_meta '.ROVERIDX_META_PAGE_ID.' ['.$post_id.']');

				//	Add postmeta
				update_post_meta($post_id, ROVERIDX_META_PAGE_ID, $post_id);

				//	Delete that post - we don't need it anymore.  We're only reserving the post_id for our exclusive use
				wp_delete_post($post_id, $force_delete = false);
				}
			}
		else
			{
			$post_id = $one_row->post_id;
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Using already-existing rover page id '.$post_id);
			}

		return $post_id;
		}

	function roveridx_has_unmigrated_diverse_solutions($check_for = null)	{

		global 		$wpdb;

		$ds			= get_option('roveridx_has_diverse_solutions');
		if ($ds == 'no')
			return false;

		if ($check_for === 'notice')
			{
			if (get_option('roveridx_dismissed_diverse_solutions') == 'yes')
				return false;
			}

		$one_row	= $wpdb->get_row( "SELECT * FROM $wpdb->posts
									WHERE post_type = 'ds-idx-listings-page'
									AND post_status = 'publish'" );

		if (is_null($one_row))
			{
			update_option( 'roveridx_has_diverse_solutions', 'no' );
			return false;
			}

		return true;
		}

	function roveridx_dashboard_active_summary()	{

		require_once ROVER_IDX_PLUGIN_PATH.'admin/rover-dashboard.php';

		global $rover_idx_dashboard;

		wp_add_dashboard_widget(
					 'roveridx_dashboard_active',											// Widget slug.
					 'Rover IDX - Active Listings',											// Title.
					 array(&$rover_idx_dashboard, 'dashboard_active_summary')				// Display function.
			);

		}

	function roveridx_dashboard_visitor_activity()			{

		require_once ROVER_IDX_PLUGIN_PATH.'admin/rover-dashboard.php';

		global $rover_idx_dashboard;

		wp_add_dashboard_widget(
					 'roveridx_dashboard_activity',											// Widget slug.
					 'Rover IDX - Visitor Activity',										// Title.
					 array(&$rover_idx_dashboard, 'dashboard_activity')						// Display function.
			);
		}

	function roveridx_dashboard_mail()			{

		require_once ROVER_IDX_PLUGIN_PATH.'admin/rover-dashboard.php';

		global $rover_idx_dashboard;

		wp_add_dashboard_widget(
					 'roveridx_dashboard_mail',												// Widget slug.
					 'Rover IDX - Mail',													// Title.
					 array(&$rover_idx_dashboard, 'dashboard_mail')							// Display function.
			);
		}

	}

global $rover_idx_admin;
$rover_idx_admin = new Rover_IDX_admin();



function roveridx_plugin_action_links( $links, $file ) {	// Display a Settings link on the main Plugins page

	if ( $file == plugin_basename( __FILE__ ) ) {
		$roveridx_links = '<a href="'.get_admin_url().'options-general.php?page=rover-idx/roveridx.php">'.__('Settings').'</a>';
		// make the 'Settings' link appear first
		array_unshift( $links, $roveridx_links );
		}

	return $links;
	}


function roveridx_validate_options($input) {				// Sanitize and validate input. Accepts an array, return a sanitized array.
	 // strip html from textboxes
	 //	$input['textarea_one'] =  wp_filter_nohtml_kses($input['textarea_one']);	// Sanitize textarea input (strip html tags, and escape characters)
	 //	$input['txt_one'] =  wp_filter_nohtml_kses($input['txt_one']); 				// Sanitize textbox input (strip html tags, and escape characters)
	 return $input;
	 }

?>