<?php
require_once ROVER_IDX_PLUGIN_PATH.'rover-content.php';

class Rover_IDX_Shortcodes
	{
	function __construct() {

		add_shortcode( 'rover_idx_listings',			array($this, 'rover_idx_listings') );

		add_shortcode( 'rover_idx_full_page', 			array($this, 'rover_idx_full_page') );
		add_shortcode( 'rover_idx_results',				array($this, 'rover_idx_results') );
		add_shortcode( 'rover_idx_results_as_table',	array($this, 'rover_idx_results_as_table') );
		add_shortcode( 'rover_idx_results_as_map',		array($this, 'rover_idx_results_as_map') );

		add_shortcode( 'rover_idx_property_details',	array($this, 'rover_idx_property_details') );

		add_shortcode( 'rover_idx_search_panel',		array($this, 'rover_idx_search_panel') );
		add_shortcode( 'rover_idx_site_search',			array($this, 'rover_idx_site_search') );

		add_shortcode( 'rover_idx_navbar',				array($this, 'rover_idx_navbar') );
		add_shortcode( 'rover_idx_testimonials',		array($this, 'rover_idx_testimonials') );


		add_shortcode( 'rover_idx_links', 				array($this, 'rover_idx_links') );
		add_shortcode( 'rover_idx_login', 				array($this, 'rover_idx_login') );

		add_shortcode( 'rover_idx_plugin', 				array($this, 'rover_idx_plugin') );
		add_shortcode( 'rover_idx_report', 				array($this, 'rover_idx_report') );
		add_shortcode( 'rover_idx_home_worth', 			array($this, 'rover_idx_home_worth') );
		add_shortcode( 'rover_idx_home_worth_two_page',	array($this, 'rover_idx_home_worth_two_page') );

		add_shortcode( 'rover_idx_cta', 				array($this, 'rover_idx_cta') );
		add_shortcode( 'rover_idx_contact', 			array($this, 'rover_idx_contact') );
		add_shortcode( 'rover_idx_register', 			array($this, 'rover_idx_register') );

		add_shortcode( 'rover_idx_slider', 				array($this, 'rover_idx_slider') );
		add_shortcode( 'rover_idx_searchslider', 		array($this, 'rover_idx_searchslider') );

		add_shortcode( 'rover_idx_marketconditions',	array($this, 'rover_idx_marketconditions') );
		add_shortcode( 'rover_idx_unsubscribe',			array($this, 'rover_idx_unsubscribe') );

		add_shortcode( 'rover_idx_widget',				array($this, 'rover_idx_widget') );

		add_shortcode( 'rover_idx_settings',			array($this, 'rover_idx_settings') );

		add_shortcode( 'rover_idx_agent',				array($this, 'rover_idx_agent') );
		add_shortcode( 'rover_idx_agents',				array($this, 'rover_idx_agents') );

		add_shortcode( 'rover_idx_endpoint',			array($this, 'rover_idx_endpoint') );

		/*	SEO Rets			*/

		add_shortcode( 'sr-listings',					array($this, 'seo_rets_listings') );
		add_shortcode( 'sr-list',						array($this, 'seo_rets_links') );

		/*	Diverse Solutions	*/

		add_shortcode( 'idx-listings',					array($this, 'dsidxpress_listings') );
		add_shortcode( 'idx-quick-search',				array($this, 'dsidxpress_search') );

		/*	Do not texturize Rover shortcodes!	*/

		add_filter( 'no_texturize_shortcodes',			array($this, 'rover_no_wptexturize' ) );
		}

	/*	shortcodes	*/

	function rover_idx_listings($atts)	{

		$the_rover_content								= Rover_IDX_Content::rover_content('ROVER_COMPONENT_LISTINGS', $atts);

		return (isset($the_rover_content['the_html'])) ? $the_rover_content['the_html'] : null;
		}

	function rover_idx_full_page($atts)	{

		$the_rover_content								=  Rover_IDX_Content::rover_content('ROVER_COMPONENT_FULL_PAGE', $atts);

		return $the_rover_content['the_html'];
		}
	function rover_idx_results($atts)	{

		$the_rover_content								= Rover_IDX_Content::rover_content('ROVER_COMPONENT_RESULTS', $atts);

		return $the_rover_content['the_html'];
		}
	function rover_idx_results_as_table($atts)	{

		$the_rover_content								= Rover_IDX_Content::rover_content('ROVER_COMPONENT_RESULTS_AS_TABLE', $atts);

		return $the_rover_content['the_html'];
		}
	function rover_idx_results_as_map($atts)	{

		$the_rover_content								= Rover_IDX_Content::rover_content('ROVER_COMPONENT_RESULTS_AS_MAP', $atts);

		return $the_rover_content['the_html'];
		}
	function rover_idx_property_details($atts)	{

		$the_rover_content								= Rover_IDX_Content::rover_content('ROVER_COMPONENT_PROP_DETAILS', $atts);

		return $the_rover_content['the_html'];
		}
	function rover_idx_search_panel($atts)	{

		$the_rover_content								= Rover_IDX_Content::rover_content('ROVER_COMPONENT_SEARCH_PANEL', $atts);

		return $the_rover_content['the_html'];
		}
	function rover_idx_testimonials($atts)		{

		$the_rover_content								= Rover_IDX_Content::rover_content('ROVER_COMPONENT_TESTIMONIALS', $atts);

		return $the_rover_content['the_html'];
		}

	function rover_idx_site_search($atts)		{

		$the_rover_content								= Rover_IDX_Content::rover_content('ROVER_COMPONENT_SITE_SEARCH', $atts);
		if (isset($the_rover_content['the_html']))
			{
			#	Add nonce

			$the_rover_content['the_html']				= str_replace('IDX_AJAX', admin_url('admin-ajax.php'), $the_rover_content['the_html']);
			$the_rover_content['the_html']				= str_replace('IDX_SS_NONCE', wp_create_nonce(ROVERIDX_SS_NONCE), $the_rover_content['the_html']);

			return $the_rover_content['the_html'];
			}

		return null;
		}

	function rover_idx_links($atts)	{

		if (!is_array($atts))
			$atts										= array();

		$atts['plugin_type']							= 'quickSearchLinks';
		$atts['plugin_height']							= 'auto';
		if (is_array($atts) && isset($atts['object']) && $atts['object'] == 'city')
			$atts['all_cities']							= '*';

		$the_rover_content								= Rover_IDX_Content::rover_content('ROVER_COMPONENT_PLUGIN', $atts);

		return $the_rover_content['the_html'];
		}

	function rover_idx_login($atts)	{

		global					$rover_idx;

		$ul_class 				= (is_array($atts) && isset($atts['ul_class']))
										? $atts['ul_class']
										: 'rover_login_ul';

		$the_css				= array();
		$the_css[]				= 'ul#'.$ul_class.' {';
		$the_css[]				=	'list-style-type: none;';
		$the_css[]				=	'position: relative;';
		$the_css[]				=	'}';
		$the_css[]				= 'ul#'.$ul_class.' > li {';
		$the_css[]				=	'display: inline-block;';
		$the_css[]				=	'text-align: left;';
		$the_css[]				=	'}';
		$the_css[]				= 'ul#'.$ul_class.' > li > a {';
		$the_css[]				=	'display: block;';
		$the_css[]				=	'border: none;';
		$the_css[]				=	'padding: 15px 20px;';
		$the_css[]				=	'position: relative;';
		$the_css[]				=	'}';
		$the_css[]				= 'ul#'.$ul_class.' ul.rover-login-framework.sub-menu {';
		$the_css[]				=	'list-style-type: none;';
		$the_css[]				=	'height: 0px;';
		$the_css[]				=	'opacity: 0;';
		$the_css[]				=	'position: absolute;';
		$the_css[]				=	'}';
		$the_css[]				= 'ul#'.$ul_class.' ul.rover-login-framework.sub-menu li {';
		$the_css[]				=	'list-style-type: none;';
		$the_css[]				=	'line-height: 2;';
		$the_css[]				=	'}';
		$the_css[]				= 'ul#'.$ul_class.' > li:hover > ul.rover-login-framework,';
		$the_css[]				= 'ul#'.$ul_class.'  > li > ul.rover-login-framework:hover {';
		$the_css[]				=	'background-color: #fff;';
		$the_css[]				=	'border: 1px solid #eee;';
		$the_css[]				=	'height: auto;';
		$the_css[]				=	'opacity: 1;';
		$the_css[]				=	'transition: all 0.5s linear;';
		$the_css[]				=	'transition-property: opacity, height, margin;';
		$the_css[]				=	'z-index: 9999;';
		$the_css[]				=	'}';
		$the_css[]				= 'ul#'.$ul_class.' ul.rover-login-framework > li:hover > a {';
		$the_css[]				=	'background-color: #efefef;';
		$the_css[]				=	'opacity: 0.8;';
		$the_css[]				=	'}';

		$login_button			= null;
		if (is_array($atts) && isset($atts['login_button']) && !empty($atts['login_button']))
			$login_button		= $atts['login_button'];
		else if (is_array($rover_idx->roveridx_theming) && isset($rover_idx->roveridx_theming['login_button']) && !empty($rover_idx->roveridx_theming['login_button']))
			$login_button		= $rover_idx->roveridx_theming['login_button'];

		$parts					= explode(';', $login_button);
		$menu_location			= (is_array($parts) && isset($parts[0]))
										? $parts[0]
										: null;

		$args					= new stdClass();
		$args->theme_location	= $menu_location;
		$args->shortcode_params	= (object) $atts;

		return sprintf("<style>%s</style><ul id='%s'>%s</ul>", implode('', $the_css), $ul_class, $rover_idx->add_login_menu(null, $args));
		}

	function rover_idx_plugin($atts)	{

		$the_rover_content								= Rover_IDX_Content::rover_content('ROVER_COMPONENT_PLUGIN', $atts);

		return $the_rover_content['the_html'];
		}
	function rover_idx_report($atts)	{

		$the_rover_content								= Rover_IDX_Content::rover_content('rover-report-panel', $atts);

		return $the_rover_content['the_html'];
		}
	function rover_idx_home_worth($atts)	{

		$the_rover_content								= Rover_IDX_Content::rover_content('ROVER_COMPONENT_HOME_WORTH', $atts);

		return $the_rover_content['the_html'];
		}
	function rover_idx_home_worth_two_page($atts)	{

		$the_rover_content								= Rover_IDX_Content::rover_content('ROVER_COMPONENT_HOME_WORTH_TWO_PAGE', $atts);

		return $the_rover_content['the_html'];
		}
	function rover_idx_cta($atts)		{

		$the_rover_content								= Rover_IDX_Content::rover_content('ROVER_COMPONENT_CTA', $atts);

		return $the_rover_content['the_html'];
		}
	function rover_idx_contact($atts)		{

		$the_rover_content								= Rover_IDX_Content::rover_content('ROVER_COMPONENT_CONTACT', $atts);

		return $the_rover_content['the_html'];
		}
	function rover_idx_register($atts)		{

		$the_rover_content								= Rover_IDX_Content::rover_content('ROVER_COMPONENT_REGISTER', $atts);

		return $the_rover_content['the_html'];
		}
	function rover_idx_slider($atts)	{

		$the_rover_content								= Rover_IDX_Content::rover_content('ROVER_COMPONENT_SLIDER', $atts);

		return $the_rover_content['the_html'];
		}
	function rover_idx_searchslider($atts)	{

		$the_rover_content								= Rover_IDX_Content::rover_content('ROVER_COMPONENT_SEARCHSLIDER', $atts);

		return $the_rover_content['the_html'];
		}
	function rover_idx_marketconditions($atts)	{

		$the_rover_content								= Rover_IDX_Content::rover_content('rover-market-conditions', $atts);

		return $the_rover_content['the_html'];
		}
	function rover_idx_unsubscribe($atts)	{

		$the_rover_content								= Rover_IDX_Content::rover_content('ROVER_COMPONENT_UNSUBSCRIBE', $atts);

		return $the_rover_content['the_html'];
		}

	function rover_idx_settings($atts)	{

		$the_rover_content								= Rover_IDX_Content::rover_content('rover-settings-panel', $atts);

		return $the_rover_content['the_html'];
		}

	function rover_idx_agent($atts)	{

		$the_rover_content								= Rover_IDX_Content::rover_content('ROVER_COMPONENT_AGENT_DETAIL_PAGE', $atts);

		return $the_rover_content['the_html'];
		}

	function rover_idx_agents($atts)	{

		$the_rover_content								= Rover_IDX_Content::rover_content('ROVER_COMPONENT_AGENT_LIST', $atts);

		return $the_rover_content['the_html'];
		}

	function rover_idx_endpoint($atts)	{

		$the_rover_content								= Rover_IDX_Content::rover_content('ROVER_COMPONENT_AUTHENTICATED_USER_ENDPOINT', $atts);

		return $the_rover_content['the_html'];
		}

	/*	Map other vendors shortcodes to our functions	*/

	function seo_rets_listings($atts)	{

		require_once ROVER_IDX_PLUGIN_PATH.'rover-shortcodes-seorets.php';

		$seorets										= new Rover_IDX_Shortcodes_SEORETS();
		$atts											= $seorets->map_seorets_to_rover($atts);

		$the_rover_content								= Rover_IDX_Content::rover_content('ROVER_COMPONENT_RESULTS_AS_TABLE', $atts);

		return $the_rover_content['the_html'];
		}

	function seo_rets_links($atts)		{

		require_once ROVER_IDX_PLUGIN_PATH.'rover-shortcodes-seorets.php';

		$seorets										= new Rover_IDX_Shortcodes_SEORETS();
		$atts											= $seorets->map_seorets_to_rover($atts);

		$atts['plugin_type']							= 'quickSearchLinks';
		$atts['plugin_height']							= 'auto';
		$atts['all_cities']								= ($atts['object'] == 'city')
																? '*'
																: null;
		$atts['quick_search_include_counts']			= $atts['include_counts'];
		$atts['quick_search_include_areas']				= $atts['include_areas'];

		$the_rover_content								= Rover_IDX_Content::rover_content('ROVER_COMPONENT_PLUGIN', $atts);

		return $the_rover_content['the_html'];
		}

	function dsidxpress_listings($atts)	{

		require_once ROVER_IDX_PLUGIN_PATH.'rover-shortcodes-dsidxpress.php';

		$dsidxpress										= new Rover_IDX_Shortcodes_DS();
		$atts											= $dsidxpress->map_dsidxpress_to_rover($atts);

		$the_rover_content								= Rover_IDX_Content::rover_content('ROVER_COMPONENT_RESULTS', $atts);

		return $the_rover_content['the_html'];
		}

	function dsidxpress_search($atts)	{

		if (isset($atts['format']) && ($atts['format'] == 'horizontal') )
			{
			$atts['search_panel_layout']				= 'custom';
			$atts['prop_type_control_style']			= 1;
			$atts['template_fields']					= 'buildCitySelect,buildBeds,buildBaths,buildPrice,buildSqFt';
			$atts['all_per_row']						= 6;
			}
		else
			{
			$atts['search_panel_layout']				= 'custom';
			$atts['city_buttons_per_row']				= 1;
			$atts['prop_type_control_style']			= 1;
			$atts['proptype_buttons_per_row']			= 1;
			$atts['template_fields']					= 'buildCitySelect,buildBeds,buildBaths,buildPrice,buildSqFt,buildAcres';
			}

		unset($atts['format']);

		$the_rover_content								= Rover_IDX_Content::rover_content('ROVER_COMPONENT_SEARCH_PANEL', $atts);

		return $the_rover_content['the_html'];
		}

	function rover_idx_widget($atts) {

		global											$wp_widget_factory;

		extract(shortcode_atts(array(
			'widget_name' => FALSE
		), $atts));

		$widget_name									= wp_specialchars($widget_name);

		if (!is_a($wp_widget_factory->widgets[$widget_name], 'WP_Widget'))
			{
			$wp_class									= 'WP_Widget_'.ucwords(strtolower($class));

			if (!is_a($wp_widget_factory->widgets[$wp_class], 'WP_Widget'))
				return '<p>'.sprintf(__("%s: Widget class not found. Make sure this widget exists and the class name is correct"),'<strong>'.$class.'</strong>').'</p>';
			else
				$class									= $wp_class;
			}

		ob_start();
		the_widget($widget_name, $atts, array('widget_id'=>'arbitrary-instance-'.rand(10000,99999),
			'before_widget' => '',
			'after_widget' => '',
			'before_title' => '',
			'after_title' => ''
		));
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
		}

	function rover_no_wptexturize( $shortcodes ) {

		$shortcodes[]								= 'sr-listings';
		$shortcodes[]								= 'sr-list';

		$shortcodes[]								= 'rover_idx_full_page';
		$shortcodes[]								= 'rover_idx_listings';
		$shortcodes[]								= 'rover_idx_results';
		$shortcodes[]								= 'rover_idx_results_as_table';
		$shortcodes[]								= 'rover_idx_results_as_map';
		$shortcodes[]								= 'rover_idx_search_panel';

		$shortcodes[]								= 'rover_idx_property_details';

		$shortcodes[]								= 'rover_idx_testimonials';

		$shortcodes[]								= 'rover_idx_links';
		$shortcodes[]								= 'rover_idx_contact';
		$shortcodes[]								= 'rover_idx_register';
		$shortcodes[]								= 'rover_idx_plugin';
		$shortcodes[]								= 'rover_idx_report';
		$shortcodes[]								= 'rover_idx_cta';
		$shortcodes[]								= 'rover_idx_slider';
		$shortcodes[]								= 'rover_idx_searchslider';
		$shortcodes[]								= 'rover_idx_marketconditions';
		$shortcodes[]								= 'rover_idx_unsubscribe';
		$shortcodes[]								= 'rover_idx_widget';

		$shortcodes[]								= 'rover_idx_settings';
		$shortcodes[]								= 'rover_idx_agent';
		$shortcodes[]								= 'rover_idx_agents';

		return $shortcodes;
		}

	}

new Rover_IDX_Shortcodes();

?>