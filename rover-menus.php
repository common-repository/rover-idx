<?php

class Rover_IDX_Menus
	{
	private $bootstrap_ready			= false;

	private	$primary_li_classes			= null;
	private	$primary_a_classes			= null;
	private	$primary_a_dropdown_classes	= null;
	private	$primary_a_span_classes		= null;
	private	$primary_a_caret_classes	= null;

	private	$dropdown_ul_classes		= null;
	private	$dropdown_li_classes		= null;
	private	$dropdown_a_classes			= null;
	private	$dropdown_a_data			= null;
	private	$dropdown_li_a_span_classes	= null;
	private	$li_scope					= null;

	public function add_login_button()	{

		global							$rover_idx;

		if (!empty($rover_idx->roveridx_theming['login_button']) && $rover_idx->roveridx_theming['login_button'] != 'none')
			{
			if ($rover_idx->roveridx_theming['login_button'] == 'link')
				{
				return do_shortcode("[rover_idx_login hide_login_in_footer=true show_login_as_text=true]");
				}
			else if ($rover_idx->roveridx_theming['login_button'] == 'banner')
				{
				return $this->login_dropdown_banner();
				}
			else if ($rover_idx->roveridx_theming['login_button'] == 'button')
				{
				return $this->login_dropdown_button();
				}
			}

		return null;
		}

	public function add_login_menu( $items, $args ) {

		$login_button					= (($val = $this->login_option_value($args, 'login_button')) !== null)
												? $val
												: null;

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, ' login_button ['.$login_button.'] / args ['.print_r($args, true).']');

		$login_label					= (($val = $this->login_option_value($args, 'login_label')) !== null)
												? $val
												: 'Login';
		$login_dropdown					= (($val = $this->login_option_value($args, 'login_dropdown')) !== null)
												? $val
												: 'display';

		$fav_label						= (($val = $this->login_option_value($args, 'fav_label')) !== null)
												? $val
												: 'Favorites';
		$fav_dropdown					= (($val = $this->login_option_value($args, 'fav_dropdown')) !== null)
												? $val
												: 'display';

		$ss_label						= (($val = $this->login_option_value($args, 'ss_label')) !== null)
												? $val
												: 'Saved Searches';
		$ss_dropdown					= (($val = $this->login_option_value($args, 'ss_dropdown')) !== null)
												? $val
												: 'display';

		$add_login						= false;
		$add_favorites					= (($val = $this->login_option_value($args, 'fav')) !== null)
												? true
												: false;
		$add_saved_searches				= (($val = $this->login_option_value($args, 'ss')) !== null)
												? true
												: false;
		if (!empty($login_button))
			{
			$parts						= explode(';', $login_button);
			$menu_location				= (is_array($parts) && isset($parts[0]))
												? $parts[0]
												: null;
			$other_menu_items			= (is_array($parts) && isset($parts[1]))
												? $parts[1]
												: null;

			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, ' menu_location ['.$menu_location.'] / theme_location ['.$args->theme_location.']');

			$display_now				= (is_object($args) && (property_exists($args, 'shortcode_params')))
												? true
												: (($menu_location == $args->theme_location) ? true : false);

			if ($display_now && !is_null($other_menu_items))
				{
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Adding Rover Login / Register menu');
				$add_login				= true;

				foreach(explode(',', $other_menu_items) as $other)
					{
					if ($other == 'fav')
						$add_favorites		= true;
					else if ($other == 'ss')
						$add_saved_searches	= true;
					}
				}
			else
				{
				return $items;
				}
			}

		$this->setup_menu_classes();

		$the_html				= array();
		$the_html[]				= $items;
		if ($add_favorites)
			{
			if ($fav_dropdown === 'hide')
				{
				$the_html[]		= '<li class="'.$this->primary_li_classes.' menu-item-type-rover-favorites">';
				$the_html[]		=	'<a href="/rover-control-panel/my-favorites/" class="rover-fav-label '.$this->primary_a_classes.'" rel="nofollow">'.$fav_label.' (<span class="num_favs">0</span>)</a>';
				$the_html[]		= '</li>';
				}
			else
				{
				$the_html[]		= '<li class="'.$this->primary_li_classes.(($this->bootstrap_ready) ? ' dropdown' : '').' menu-item-has-children menu-item-type-rover-favorites">';
				$the_html[]		=	'<a href="#" class="rover-fav-label '.$this->primary_a_dropdown_classes.'" '.$this->dropdown_a_data.' rel="nofollow" onclick="return false;">Favorites (<span class="num_favs">0</span>)</a>';
				$the_html[]		=	'<ul class="sub-menu '.$this->dropdown_ul_classes.' rover-dropdown-menu fav">';
				$the_html[]		=		'<li class="'.$this->dropdown_li_classes.'" '.$this->li_scope.'><a href="#" class="'.$this->dropdown_a_classes.'" onclick="return false;">You currently have no favorite listings</a></li>';
				$the_html[]		=	'</ul>';
				$the_html[]		= '</li>';
				}
			}

		if ($add_saved_searches)
			{
			if ($ss_dropdown === 'hide')
				{
				$the_html[]		= '<li class="'.$this->primary_li_classes.' menu-item-type-rover-saved-searches">';
				$the_html[]		=	'<a href="/rover-control-panel/my-saved-searches/" class="rover-ss-label '.$this->primary_a_classes.'" rel="nofollow">'.$ss_label.' (<span class="num_ss">0</span>)</a>';
				$the_html[]		= '</li>';
				}
			else
				{
				$the_html[]		= '<li class="'.$this->primary_li_classes.(($this->bootstrap_ready) ? ' dropdown' : '').' menu-item-has-children menu-item-type-rover-saved-searches">';
				$the_html[]		=	'<a href="#" class="rover-ss-label '.$this->primary_a_dropdown_classes.'" '.$this->dropdown_a_data.' rel="nofollow" onclick="return false;">Saved Searches (<span class="num_ss">0</span>)</a>';
				$the_html[]		=	'<ul class="sub-menu '.$this->dropdown_ul_classes.' rover-dropdown-menu ss">';
				$the_html[]		=		'<li class="'.$this->dropdown_li_classes.'" '.$this->li_scope.'><a href="#" class="'.$this->dropdown_a_classes.'" onclick="return false;">You currently have no saved searches</a></li>';
#				$the_html[]		=		'<li class="'.$this->dropdown_li_classes.'" '.$this->li_scope.'><a href="/rover-control-panel/my-saved-searches/" class="'.$this->dropdown_a_classes.'" rel="nofollow">Manage my Saved Searches <i class="fa fa-cog"></i></a></li>';
				$the_html[]		=	'</ul>';
				$the_html[]		= '</li>';
				}
			}

		if ($add_login)
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'adding <li>');

			if ($login_dropdown === 'hide')
				{
				$the_html[]		= '<li class="'.$this->primary_li_classes.' menu-item-type-rover-login">';
				$the_html[]		=	'<a href="#" class="rover-login rover-login-label '.$this->primary_a_classes.'" data-label="'.$login_label.'">'.$login_label.'</a>';
				$the_html[]		= '</li>';
				}
			else
				{
				$the_html[]		= '<li class="'.$this->primary_li_classes.(($this->bootstrap_ready) ? ' dropdown' : '').' menu-item-has-children menu-item-type-rover-login">';
				$the_html[]		=	'<a href="#" class="rover-login-label '.$this->primary_a_dropdown_classes.'" '.$this->dropdown_a_data.' data-label="'.$login_label.'">';
				$the_html[]		=		'<span class="'.$this->primary_a_span_classes.' login-dropdown-label">'.$login_label.'</span>';
				$the_html[]		=		'<span role="presentation" class="'.$this->primary_a_caret_classes.' dropdown-menu-toggle"></span>';
				$the_html[]		=	'</a>';
				$the_html[]		=	'<ul class="sub-menu '.$this->dropdown_ul_classes.' rover-dropdown-menu rover-framework rover-login-framework">';
				$the_html[]		=		$this->login_dropdown_items();
				$the_html[]		=	'</ul>';
				$the_html[]		= '</li>';
				}
			}

		return implode('', $the_html);
		}

	private function login_dropdown_items()	{

		$scope			= 'itemscope="itemscope" itemtype="https://www.schema.org/SiteNavigationElement" ';

		$the_html		= array();
		$the_html[]		= '<li class="menu-item showIfNotLoggedIn '.$this->dropdown_li_classes.'" '.$scope.'><a href="#" class="rover-login '.$this->dropdown_a_classes.'" rel="nofollow">'.$this->login_dropdown_item_span("Login").'</a></li>';
		$the_html[]		= '<li class="menu-item showIfNotLoggedIn '.$this->dropdown_li_classes.'" '.$scope.'><a href="#" class="rover-register '.$this->dropdown_a_classes.'" rel="nofollow">'.$this->login_dropdown_item_span("Register").'</a></li>';

		$the_html[]		= '<li class="menu-item showIfClient '.$this->dropdown_li_classes.' rover-control-panel" '.$scope.'><a href="/rover-control-panel" class="'.$this->dropdown_a_classes.'" rel="nofollow">'.$this->login_dropdown_item_span("Control Panel").'</a></li>';
		$the_html[]		= '<li class="menu-item showIfClient '.$this->dropdown_li_classes.' rover-control-panel fav" '.$scope.'><a href="/rover-control-panel/my-favorites/" class="'.$this->dropdown_a_classes.'" rel="nofollow">'.$this->login_dropdown_item_span("My Favorites").'</a></li>';
		$the_html[]		= '<li class="menu-item showIfClient '.$this->dropdown_li_classes.' rover-control-panel ss" '.$scope.'><a href="/rover-control-panel/my-saved-searches/" class="'.$this->dropdown_a_classes.'" rel="nofollow">'.$this->login_dropdown_item_span("My Saved Searches").'</a></li>';

		$the_html[]		= '<li class="menu-item showIfAgent '.$this->dropdown_li_classes.' rover-custom-listing-panel" '.$scope.'><a href="/rover-custom-listing-panel/" class="'.$this->dropdown_a_classes.'" rel="nofollow">'.$this->login_dropdown_item_span("Custom Listings Panel").'</a></li>';
		$the_html[]		= '<li class="menu-item showIfAgent '.$this->dropdown_li_classes.' rover-report-panel" '.$scope.'><a href="/rover-report-panel/" class="'.$this->dropdown_a_classes.'" rel="nofollow">'.$this->login_dropdown_item_span("Report Panel").'</a></li>';
		$the_html[]		= '<li class="menu-item showIfAgent '.$this->dropdown_li_classes.' rover-market-panel" '.$scope.'><a href="/rover-market-conditions/" class="'.$this->dropdown_a_classes.'" rel="nofollow">'.$this->login_dropdown_item_span("Market Conditions").'</a></li>';

		$the_html[]		= '<li class="menu-item showIfClient '.$this->dropdown_li_classes.' rover-logout" '.$scope.'><a href="#" class="'.$this->dropdown_a_classes.'" rel="nofollow">'.$this->login_dropdown_item_span("Logout").'</a></li>';

		return implode('', $the_html);
		}

	private function login_dropdown_item_span($str)	{

		if ($this->dropdown_li_a_span_classes && !empty($this->dropdown_li_a_span_classes))
			return sprintf("<span class='%s'>%s</span>", $this->dropdown_li_a_span_classes, $str);

		return $str;
		}

	private function login_dropdown_banner()	{

		$the_html		= array();
		$the_html[]		= '<div id="roverContent" class="rover-framework rover-login-framework rover login-at-top" data-reg_context="rover-login-framework">';
		$the_html[]		=	'<div id="headerTopLine" class="show_just_this_topline">';
		$the_html[]		=		$this->login_dropdown();
		$the_html[]		=		$this->login_saved_search_dropdown();
		$the_html[]		=		$this->login_favorites_dropdown();
		$the_html[]		=		$this->roveridx_msg();
		$the_html[]		=		'<div style="clear:both;"></div>';
		$the_html[]		=	'</div>';
		$the_html[]		= '</div>';

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'html = '.strlen(implode(',', $the_html)).' bytes');

		return implode('', $the_html);
		}

	private function login_dropdown_button()	{

		$the_html		= array();
		$the_html[]		= '<div id="roverContent" class="rover-framework rover-login-framework rover login-at-top" data-reg_context="rover-login-framework">';
		$the_html[]		=	'<div id="headerTopLine" class="show_just_this_topline">';
		$the_html[]		=		$this->login_dropdown();
		$the_html[]		=		'<div style="clear:both;"></div>';
		$the_html[]		=	'</div>';
		$the_html[]		=	'<script type="text/javascript">var rlm = document.querySelector(".rover-login-move");if (rlm) {document.body.insertAdjacentHTML("beforebegin", rlm.innerHTML);}</script>';
		$the_html[]		= '</div>';

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'html = '.strlen(implode(',', $the_html)).' bytes');

		return implode('', $the_html);
		}

	private function login_option_value($args, $key)	{

		global					$rover_idx;

		#	1st - look in shortcode params

		if (is_object($args) && (property_exists($args, 'shortcode_params')) && (property_exists($args->shortcode_params, $key)) && !empty($args->shortcode_params->$key))
			return $args->shortcode_params->$key;

		if (isset($rover_idx->roveridx_theming[$key]) && !empty($rover_idx->roveridx_theming[$key]))
			return $rover_idx->roveridx_theming[$key];

		return null;
		}

	private function setup_menu_classes()
		{
		global						$rover_idx;

		$this->bootstrap_ready		= (isset($rover_idx->roveridx_theming['menus_bootstrap_ready']) && ($rover_idx->roveridx_theming['menus_bootstrap_ready'] == 'enable'))
											? true
											: false;

		#	P R I M A R Y

		$items						= array("menu-item", "rover-nav-item");
		if (isset($rover_idx->roveridx_theming['menu_primary_li_classes']) && !empty($rover_idx->roveridx_theming['menu_primary_li_classes']))
			$items[]				= $rover_idx->roveridx_theming['menu_primary_li_classes'];
		if ($this->bootstrap_ready)
			$items[]				= "nav-item";
		$this->primary_li_classes	= implode(' ', $items);

		$items						= array();
		if (isset($rover_idx->roveridx_theming['menu_primary_a_classes']) && !empty($rover_idx->roveridx_theming['menu_primary_a_classes']))
			$items[]				= $rover_idx->roveridx_theming['menu_primary_a_classes'];
		if ($this->bootstrap_ready)
			$items[]				= "dropdown-toggle nav-link";
		$this->primary_a_classes	= implode(' ', $items);

		$this->primary_a_dropdown_classes	= ($this->bootstrap_ready)
													? $this->primary_a_classes . ' dropdown-toggle'
													: $this->primary_a_classes . ' menu-link rover-dropdown-toggle';

		$items						= array();
		if (isset($rover_idx->roveridx_theming['menu_primary_a_span_classes']) && !empty($rover_idx->roveridx_theming['menu_primary_a_span_classes']))
			$items[]				= $rover_idx->roveridx_theming['menu_primary_a_span_classes'];
		$this->primary_a_span_classes	= implode(' ', $items);

		$items						= array();
		if (isset($rover_idx->roveridx_theming['menu_primary_a_caret_title_classes']) && !empty($rover_idx->roveridx_theming['menu_primary_a_caret_title_classes']))
			$items[]				= $rover_idx->roveridx_theming['menu_primary_a_caret_title_classes'];
		$this->primary_a_caret_classes	= implode(' ', $items);


		#	D R O P D O W N

		$items						= array();
		if (isset($rover_idx->roveridx_theming['menu_dropdown_ul_classes']) && !empty($rover_idx->roveridx_theming['menu_dropdown_ul_classes']))
			$items[]				= $rover_idx->roveridx_theming['menu_dropdown_ul_classes'];
		if ($this->bootstrap_ready)
			$items[]				= "dropdown-menu";
		$this->dropdown_ul_classes	= implode(' ', $items);

		$items						= array();
		if (isset($rover_idx->roveridx_theming['menu_dropdown_li_classes']) && !empty($rover_idx->roveridx_theming['menu_dropdown_li_classes']))
			$items[]				= $rover_idx->roveridx_theming['menu_dropdown_li_classes'];
		if ($this->bootstrap_ready)
			$items[]				= "nav-item";
		$this->dropdown_li_classes	= implode(' ', $items);

		$items						= array();
		$items[]					= "menu-link";
		if (isset($rover_idx->roveridx_theming['menu_dropdown_li_a_classes']) && !empty($rover_idx->roveridx_theming['menu_dropdown_li_a_classes']))
			$items[]				= $rover_idx->roveridx_theming['menu_dropdown_li_a_classes'];
		if ($this->bootstrap_ready)
			$items[]				= "dropdown-item";
		$this->dropdown_a_classes	= implode(' ', $items);

		if ($this->bootstrap_ready)
			$this->dropdown_a_data	= 'data-toggle="dropdown" aria-haspopup="true" aria-expanded="true" aria-current="page"';

		$items						= array();
		if (isset($rover_idx->roveridx_theming['menu_dropdown_li_a_span_classes']) && !empty($rover_idx->roveridx_theming['menu_dropdown_li_a_span_classes']))
			$items[]				= $rover_idx->roveridx_theming['menu_dropdown_li_a_span_classes'];
		$this->dropdown_li_a_span_classes	= implode(' ', $items);

		}

	private function roveridx_msg()	{

		$the_html[]		=		'<p class="rover-msg">';
		$the_html[]		=			'<span class="rover-msg-icon" style="display: none;">';
		$the_html[]		=				'<i class="fa fa-spinner fa-pulse fa-spin"></i>';
		$the_html[]		=			'</span>';
		$the_html[]		=			'<span class="rover-msg-text" style="display: inline;"></span>';
		$the_html[]		=		'</p>';

		return implode('', $the_html);
		}

	private function login_dropdown()	{

		$the_html[]		=		'<div class="dropdown rover_login_dropdown floatRight">';
		$the_html[]		=			'<a href="#" id="rover-login" class="rover-button-dropdown rover-dropdown-toggle rover-background rover-button" '.$this->dropdown_a_data.' rel="nofollow" style="">';
		$the_html[]		=				'<span class="rover-button-dropdown-label rover-login-label rover-nowrap">Login/Register</span> ';
		$the_html[]		=				'<u class="ri arrow arrow-down"></u>';
		$the_html[]		=			'</a>';
		$the_html[]		=			'<ul class="sub-menu '.$this->primary_ul_classes.' rover-dropdown-menu right" style="display:none;">';
		$the_html[]		=				$this->login_dropdown_items();
		$the_html[]		=			'</ul>';
		$the_html[]		=		'</div>';

		return implode('', $the_html);
		}

	private function login_saved_search_dropdown()	{

		$the_html[]		=		'<div class="dropdown rover_saved_search_dropdown rover_saved_search_count floatRight">';
		$the_html[]		=			'<a href="#" id="rover-login" class="rover-button-dropdown rover-dropdown-toggle rover-background rover-button" '.$this->dropdown_a_data.' rel="nofollow" style="">';
		$the_html[]		=				'<span class="rover-button-dropdown-label rover-nowrap">Saved Searches (0)</span> ';
		$the_html[]		=				'<u class="ri arrow arrow-down"></u>';
		$the_html[]		=			'</a>';
		$the_html[]		=			'<ul class="sub-menu '.$this->primary_ul_classes.' rover-dropdown-menu right" style="display:none;">';
		$the_html[]		=			'</ul>';
		$the_html[]		=		'</div>';

		return implode('', $the_html);
		}

	private function login_favorites_dropdown()	{

		$the_html[]		=		'<div class="dropdown rover_saved_search_dropdown rover_favorite_count floatRight">';
		$the_html[]		=			'<a href="#" id="rover-login" class="rover-button-dropdown rover-dropdown-toggle rover-background rover-button" '.$this->dropdown_a_data.' rel="nofollow" style="">';
		$the_html[]		=				'<span class="rover-button-dropdown-label rover-nowrap">Favorites (0)</span> ';
		$the_html[]		=				'<u class="ri arrow arrow-down"></u>';
		$the_html[]		=			'</a>';
		$the_html[]		=			'<ul class="sub-menu '.$this->primary_ul_classes.' rover-dropdown-menu right" style="display:none;">';
		$the_html[]		=			'</ul>';
		$the_html[]		=		'</div>';

		return implode('', $the_html);
		}

	}

?>