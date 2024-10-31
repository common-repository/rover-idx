<?php

require_once 'rover-common.php';

class Rover_IDX_THIRDPARTY {

	private	static $debug_log				= null;

	public static function filters()
		{
		#	Yoast SEO

		if ( defined( 'WPSEO_VERSION' ) ) {

			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'WPSEO_VERSION is defined');

			#	https://developer.yoast.com/customization/apis/metadata-api/#generic-presenters

#			remove_action( 'template_redirect', array( WPSEO_Frontend::get_instance(), 'clean_permalink' ), 1 );

			if (version_compare(WPSEO_VERSION, 14) > 0)
				{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'WPSEO_VERSION > 14');

				#	Yoast v14+ - remove all Yoast title / meta output
				$front_end		= YoastSEO()->classes->get( Yoast\WP\SEO\Integrations\Front_End_Integration::class );
				remove_action( 'wpseo_head', [ $front_end, 'present_head' ], -9999 );
#				add_filter( 'wpseo_robots',  '__return_false' );
				add_filter( 'wpseo_googlebot', '__return_false' );	# Yoast SEO 14.x or newer
				add_filter( 'wpseo_bingbot', '__return_false' );	# Yoast SEO 14.x or newer
				}
			else if (class_exists('WPSEO_Frontend'))
				{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'WPSEO_Frontend');

				#	Yoast pre-v14 - remove all Yoast title / meta output
				$wp_thing		= WPSEO_Frontend::get_instance();
				remove_action( 'wp_head', array( $wp_thing, 'head' ), 1 );
				}
			else
				{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'WPSEO');

				add_filter( 'wpseo_title', '__return_false' );		#	Will stop Yoast SEO from changing the titles.
				add_filter( 'wpseo_metadesc', '__return_false' );

				add_filter( 'wpseo_opengraph_title', '__return_false' );
				add_filter( 'wpseo_opengraph_desc', '__return_false' );
				add_filter( 'wpseo_opengraph_image', '__return_false' );
				add_filter( 'wpseo_opengraph_url', '__return_false' );
				add_filter( 'wpseo_canonical', '__return_false' );

				add_filter( 'wpseo_og_article_published_time', '__return_false' );
				add_filter( 'wpseo_og_article_modified_time', '__return_false' );
				add_filter( 'wpseo_og_og_updated_time', '__return_false' );

#				add_filter( 'wpseo_schema_webpage', array($this, 'wpseo_schema_webpage') );
				}
			}

		#	All in One SEO

		if ( defined( 'AIOSEO_PHP_VERSION_DIR' ) ) {

			#	https://aioseo.com/docs/aioseo_disable/

			add_filter( 'aioseo_disable', function( $details ) {
				return true;
				});

#			Is this used to notify AIOSEO of our sitemaps?
#			add_filter( 'aioseo_sitemap_indexes', function( $indexes ) {
#
#				$indexes[] = [
#					'loc'     => 'https://somedomain.com/custom-sitemap.xml',
#					'lastmod' => aioseo()->helpers->dateTimeToIso8601( '2021-09-08 12:02' ),
#					'count'   => 1000
#					];
#				return $indexes;
#				});
			}

		#	Rank Math SEO

		if ( class_exists( 'RankMath' ) ) {

			#	https://rankmath.com/kb/filters-hooks-api-developer/

if (false)	#	documented, but not working
	{
				remove_all_actions( 'rank_math/head' );
	}
else
	{
				remove_all_actions( 'rank_math/head' );
				remove_all_actions( 'rank_math/opengraph/facebook' );
				remove_all_actions( 'rank_math/opengraph/twitter' );
				remove_all_actions( 'rank_math/frontend/robots' );

				add_filter('rank_math/frontend/title', '__return_false');
				add_filter( 'rank_math/frontend/robots', function( $robots ) {

					$robots["index"] = 'index';

					return $robots;
					});
	}

			}

		#	The SEO Framework

		if ( defined( 'THE_SEO_FRAMEWORK_PLUGIN_BASE_FILE' ) ) {

			#	https://theseoframework.com/docs/api/filters/

			add_filter( 'the_seo_framework_title_from_custom_field', '__return_false', 10, 2 );
			add_filter( 'the_seo_framework_title_from_generation', '__return_false', 10, 2 );

			add_filter( 'the_seo_framework_custom_field_description', '__return_false', 10, 2 );
			add_filter( 'the_seo_framework_generated_description', '__return_false', 10, 2 );


			add_filter( 'the_seo_framework_ogtitle_output', '__return_false' );
			add_filter( 'the_seo_framework_ogdescription_output', '__return_false' );
			add_filter( 'the_seo_framework_ogurl_output', '__return_false' );
			add_filter( 'the_seo_framework_rel_canonical_output', '__return_false', 10, 2 );

			add_filter( 'the_seo_framework_image_details', function( $details ) {
				#	Do not allow SEO Framework to select the og:images
				return array();
				});

			add_filter( 'the_seo_framework_modifiedtime_output', '__return_false' );
			}

		#	SEO Press

		if ( defined( 'SEOPRESS_PLUGIN_DIR_PATH' ) ) {

			#	https://www.seopress.org/support/hooks/

			add_action( 'wp_head', 'rover_sp_remove_my_action', 0 );
			function rover_sp_remove_my_action(){
				remove_action( 'wp_head', 'seopress_social_website_option', 1 );//priority matter
				}

			add_filter( 'seopress_titles_canonical', '__return_false' );
			add_filter( 'seopress_titles_desc', '__return_false' );

			add_filter( 'seopress_social_og_title', '__return_false' );
			add_filter( 'seopress_social_og_desc', '__return_false' );
			add_filter( 'seopress_social_og_thumb', '__return_false' );
			add_filter( 'seopress_social_og_url', '__return_false' );

			}

		#	Slim SEO

		if ( defined( 'SLIM_SEO_DIR' ) ) {

			add_filter( 'slim_seo_canonical_url', '__return_false' );

			}

		#	Squirrly SEO

		if ( defined( 'SQ_VERSION' ) ) {

			#	https://www.seopress.org/support/hooks/

			add_filter( 'sq_title', function( $desc ) {			return null;		}, 11);

			add_filter( 'sq_canonical', function( $desc ) {		return null;		}, 11);

			add_filter( 'sq_description', function( $desc ) {	return null;		}, 11);

			add_filter( 'sq_open_graph', function( $og ) {		return array();		}, 11);

			add_filter( 'sq_twitter_card', function( $tw ) {	return array();		}, 11);

			}

		#	Jetpack

		if ( defined( 'JETPACK__VERSION' ) ) {
			add_filter( 'jetpack_enable_open_graph', '__return_false' );
			}

		#	WooCommerce

		if ( class_exists( 'WooCommerce' ) ) {
			remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
			remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);
			}

		#	Genesis themes

		if ( function_exists( 'genesis_grid_loop' ) )		{
			remove_action( 'wp_head', 'genesis_robots_meta');
			remove_action( 'wp_head', 'genesis_canonical', 5);
			remove_action( 'genesis_meta','genesis_robots_meta' );
			remove_action( 'genesis_after_post_content', 'genesis_post_meta' );
			}

		#	x theme

		if ( function_exists( 'x_get_content_layout' ) )	{
			remove_filter( 'the_content', 'sharing_display', (19 + 1) );
			add_filter( 'sharing_show', '__return_false', 9999 );
			}

		}
	}

?>