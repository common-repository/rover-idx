<?php

if (!defined('ROVER_ENGINE_SSL'))
	define('ROVER_ENGINE_SSL',									'https://c.roveridx.com/');

if (!defined('IDX_ENDPOINT_URL'))
	define('IDX_ENDPOINT_URL',									'https://c.roveridx.com/3.0.0/');

if (!defined('ROVER_OPTIONS_THEMING'))
	define('ROVER_OPTIONS_THEMING',								'roveridx_theming');

if (!defined('ROVER_OPTIONS_REGIONS'))
	define('ROVER_OPTIONS_REGIONS',								'roveridx_regions');

if (!defined('ROVER_OPTIONS_SEO'))
	define('ROVER_OPTIONS_SEO',									'roveridx_seo');

if (!defined('ROVER_OPTIONS_SOCIAL'))
	define('ROVER_OPTIONS_SOCIAL',								'roveridx_social');

if (!defined('ROVER_OPTIONS_CSS_DEFAULT'))
	define('ROVER_OPTIONS_CSS_DEFAULT',							'roveridx_css_default');

if (!defined('ROVER_OPTIONS_CSS_FA5'))
	define('ROVER_OPTIONS_CSS_FA5',								'roveridx_css_fa5');

if (!defined('ROVER_OPTIONS_CSS_AMP'))
	define('ROVER_OPTIONS_CSS_AMP',								'roveridx_css_amp');

if (!defined('ROVER_INSTALLATION_SOURCE'))
	define('ROVER_INSTALLATION_SOURCE',							'installation-source-wp-repo');

if (!defined('ROVER_DEFAULT_CSS_FRAMEWORK'))
	define('ROVER_DEFAULT_CSS_FRAMEWORK',						'rover');

if (!defined('ROVER_AFFILIATE'))
	define('ROVER_AFFILIATE',									'rover');

define('ROVER_BRAND_URL', 										'https://roveridx.com');
define('ROVER_BRAND_BYLINE',									'Powered By Rover IDX');
define('ROVER_BRAND_LOGO',										'roverLogo.jpg');
define('ROVER_BRAND_LOGO_16',									'roverLogo16.jpg');
define('ROVER_BRAND_LOGO_24',									'roverLogo24.jpg');
define('ROVER_BRAND_LOGO_48',									'roverLogo48.jpg');


function roveridx_default_slugs()
	{
	return 	array(
				'mlnumber', 'rentalcode', 'saved-search',
				'listing-agent-mlsid', 'listing-office-mlsid', 'rover-unsubscribe',
				'agent-detail', 'idx', 'archived-email', 'my-favorite-listings', 'client-home-value'
				);
	}


?>