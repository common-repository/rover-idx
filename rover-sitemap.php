<?php

require_once 'rover-common.php';

class Rover_IDX_SITEMAP {

	private	static $sitemap_opts			= null;
	private	static $sitemap_file			= null;
	private	static $sitemap_dir				= "/rover_idx_sitemap";
	private	static $upload_dir				= null;
	private	static $upload_url				= null;

	private	static $debug_log				= null;

	public static function build($force_refresh = false)
		{
		global								$rover_idx;

		set_time_limit(300);				#	60 = 1 minutes / 600 = 10 minutes / 1200 = 20 minutes

		self::$debug_log					= array();
		self::$sitemap_opts					= get_option(ROVER_OPTIONS_SEO);
		$successful_notifications			= array();

		self::log(__FUNCTION__, __LINE__, 'Starting...');

		if (self::sitemap_is_disabled())
				{
				self::log(__FUNCTION__, __LINE__, 'Sitemap refresh is disabled');
				return array(
							'success'	=> false,
							'blah'		=> 1,
							'log'		=> implode("<br>", self::$debug_log),
							);
				}

		foreach ($rover_idx->all_selected_regions as $one_region => $region_slugs)
			{
			self::$sitemap_file				= (is_multisite())
													? sprintf("rover_sitemap_%s_%s.xml", get_current_blog_id(), $one_region)
													: sprintf("rover_sitemap_%s.xml", $one_region);

			if ($force_refresh || self::should_we_build_new_sitemap($one_region))
				{
				if (($result_decoded = self::fetch_sitemap_data($one_region)) === false)
					return;

				self::log(__FUNCTION__, __LINE__, 'sitemap = '.self::$sitemap_file	);

#	O L D
				$wp_upload_dir 				= wp_upload_dir();									//	path to upload directory
				self::$upload_dir 			= (empty($wp_upload_dir['basedir']))
													? dirname(__FILE__)							//	We only end up here if wp_upload_dir() failed (unlikely)
													: $wp_upload_dir['basedir'].self::$sitemap_dir;

				self::$upload_url			= (empty($wp_upload_dir['baseurl']))
													? dirname(__FILE__)
													: $wp_upload_dir['baseurl'].self::$sitemap_dir;

#	N E W
				self::$upload_dir			= ABSPATH;											#	path to site directory
				self::$upload_url			= get_site_url();

				self::log(__FUNCTION__, __LINE__, 'count			= '.$result_decoded['count']);
				self::log(__FUNCTION__, __LINE__, 'path will be		= '.self::$upload_dir.'/'.self::$sitemap_file	);
				self::log(__FUNCTION__, __LINE__, 'url will be		= '.self::$upload_url.'/'.self::$sitemap_file	);

				/*
					Create the sitemap file
				*/

				$sitemap_url_gz				= self::sitemap_file_write($result_decoded);

				$search_engines				= array(
													'Google'	=> 'http://www.google.com/webmasters/tools/ping?sitemap=',
													'Bing'		=> 'http://www.bing.com/webmaster/ping.aspx?siteMap=',
													'Yahoo'		=> 'http://search.yahooapis.com/SiteExplorerService/V1/ping?sitemap=',	//	Yahoo has merged with Bing ??
													'Ask'		=> 'http://submissions.ask.com/ping?sitemap='
													);

				/*
					http://freds_real_estate.com/wp-content/uploads/rover_idx_sitemap/rover_sitemap_DESM.xml.gz
				*/

				foreach ($search_engines as $search_engine_name => $search_engine_submission_url)
					{
					$ping_url				= $search_engine_submission_url.$sitemap_url_gz;

					if (self::notify_search_engine($ping_url, $search_engine_name))
						{
						$successful_notifications[] = $search_engine_name;

						self::log(__FUNCTION__, __LINE__, $sitemap_url_gz.' submitted to '.$search_engine_name);
						}
					else
						{
						self::log(__FUNCTION__, __LINE__, 'Attempt to ping '.$search_engine_name.' using '.$ping_url.' has failed');
						}
					}

				if (function_exists('wp_mail'))
					{
					self::$sitemap_opts[$one_region]['url']		= esc_url( self::$upload_url.'/'.self::$sitemap_file.'.gz' );
					}

				//	'desc' was set, above

				self::$sitemap_opts[$one_region]['timestamp']	= date('M d Y H:i:s');
				self::$sitemap_opts[$one_region]['desc']		= number_format($result_decoded['count']).' properties';

				update_option(ROVER_OPTIONS_SEO, self::$sitemap_opts);
				}
			}

		return array(
					'success'	=> ((count($successful_notifications)) ? true : false),
					'log'		=> implode("<br>", self::$debug_log),
					);
		}

	public static function history()
		{
		global								$rover_idx;

		$include_region_label				= (count($rover_idx->all_selected_regions) > 1) ? true : false;
		$never								= true;
		$wp_settings						= get_option(ROVER_OPTIONS_SEO);
		$domain								= get_site_url();
		$domain_len							= strlen($domain);

		$the_html							= array();
		$the_html[]							= '<div class="container-fluid">';

		foreach ($rover_idx->all_selected_regions as $one_region => $region_slugs)
			{
			if (isset($wp_settings[$one_region]))
				{
				$never						= false;

				$the_html[]					=	'<div class="row">';

				if ($include_region_label)
					$the_html[]				=		'<div class="col-md-12">['.$one_region.']</div>';

				$the_html[]					=		'<div class="col-md-12"><span style="color:green;">'.esc_html( $wp_settings[$one_region]['timestamp'] ).'</span><span> / '.esc_html($wp_settings[$one_region]['desc']).'</span></div>';
				$the_html[]					=		'<div class="col-md-12"><a href="'.esc_url($wp_settings[$one_region]['url']).'" target="_blank">'.esc_url(substr($wp_settings[$one_region]['url'], $domain_len)).'</a></div>';

				$the_html[]					=	'</div><!-- row -->';
				}
			}

		if ($never)
			$the_html[]						=	'<div style="font-weight:bold;">Never</div>';

		$the_html[]							= '</div><!-- container -->';

		return array(
					'html'	=> implode('', $the_html),
					'never'	=> $never
					);
		}

	private static function sitemap_is_disabled()
		{
		if (
			is_array(self::$sitemap_opts) &&
			isset(self::$sitemap_opts['sitemap_enabled']) &&
			(self::$sitemap_opts['sitemap_enabled'] === "disabled")
			)
			{
			self::log(__FUNCTION__, __LINE__, 'Sitemap refresh is disabled');
			return true;
			}

		return false;
		}

	private static function should_we_build_new_sitemap($one_region)
		{
		$build_it	= true;

		if (is_array(self::$sitemap_opts) &&
			isset(self::$sitemap_opts[$one_region]) &&
			isset(self::$sitemap_opts[$one_region]['timestamp']))
			{
			$last_successful_date	= strtotime(self::$sitemap_opts[$one_region]['timestamp']);

			if (date('Y') == date('Y', $last_successful_date)	&&
				date('m') == date('m', $last_successful_date)	&&
				date('d') == date('d', $last_successful_date))
				{
				self::log(__FUNCTION__, __LINE__, 'We already built a '.$one_region.' sitemap today (on '.self::$sitemap_opts[$one_region]['timestamp'].')');
				$build_it	= false;
				}
			else
				{
				self::log(__FUNCTION__, __LINE__, 'timestamp '.self::$sitemap_opts[$one_region]['timestamp'].' is not today.  We will build a sitemap');
				}
			}
		else
			{
			if (is_array(self::$sitemap_opts))
				{
				if (isset(self::$sitemap_opts[$one_region]) && !isset(self::$sitemap_opts[$one_region]['timestamp']))
					self::log(__FUNCTION__, __LINE__, '"timestamp" is not a key in sitemaps_opts['.$one_region.'] - we will build sitemap');
				else if (isset(self::$sitemap_opts[$one_region]))
					self::log(__FUNCTION__, __LINE__, $one_region.' is not a key in sitemaps_opts - we will build sitemap');
				}
			else
				{
				self::log(__FUNCTION__, __LINE__, 'sitemaps_opts is not an array - we will build sitemap');
				}
			}

		return $build_it;
		}

	private static function fetch_sitemap_data($one_region)
		{
		global								$rover_idx;

		$perm								= get_option('permalink_structure');
		$url_ends_with_slash				= ($perm && substr($perm, -1) != '/')
													? false
													: true;

		require_once ROVER_IDX_PLUGIN_PATH.'rover-content.php';

		$rover_content						= Rover_IDX_Content::rover_content(
																				'rover-seo-regenerate-sitemap',
																				array(
																					'region'				=> $one_region,
																					'regions'				=> implode(',', array($one_region)),
																					'url_ends_with_slash'	=> $url_ends_with_slash,
																					'sitemap_domain'		=> get_site_url()
																					)
																				);

		$result_decoded						= json_decode($rover_content['the_html'], true);

		if ($result_decoded === null)
			{
			self::log(__FUNCTION__, __LINE__, 'xml did not decode correctly');
			self::log(__FUNCTION__, __LINE__, $rover_content['the_html']);
			return false;
			}

		if (!is_array($result_decoded))
			{
			self::log(__FUNCTION__, __LINE__, 'Output is not an array - aborting sitemap creation');
			return false;
			}

		return $result_decoded;
		}

	private static function sitemap_file_write($result_decoded)
		{
		if (!is_dir( self::$upload_dir ))
			mkdir( self::$upload_dir, 0755, true );

		$sitemap_path						= rtrim(self::$upload_dir,"/")."/".self::$sitemap_file;
		$sitemap_path_gz					= rtrim(self::$upload_dir,"/")."/".self::$sitemap_file.".gz";

		self::copy_file_to_local($result_decoded['sitemap_url'], $sitemap_path);
		self::copy_file_to_local($result_decoded['sitemap_gz_url'], $sitemap_path_gz);

		return self::$upload_url.'/'.self::$sitemap_file.'.gz';
		}

	static function copy_file_to_local($dest_url, $local_file)
		{
		$fp									= fopen ($local_file, 'w+');
		//Here is the file we are downloading, replace spaces with %20
		$ch									= curl_init($dest_url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 50);

		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		curl_exec($ch);
		curl_close($ch);

		fclose($fp);

		self::log(__FUNCTION__, __LINE__, 'Copied ['.$dest_url.'] to ['.$local_file.'] [<span style="color:green;">'.number_format(filesize($local_file)).' bytes</span>]');
		}

	static function notify_search_engine($sitemap_url) {

		$curl_handle = curl_init();
		curl_setopt($curl_handle,CURLOPT_URL,$sitemap_url);
		curl_setopt($curl_handle,CURLOPT_CONNECTTIMEOUT,2);
		curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,1);
		$buffer = curl_exec($curl_handle);
		curl_close($curl_handle);

		if (empty($buffer))
			return false;

		return true;
		}

	private static function log($func, $line, $str)	{

		self::$debug_log[]	= sprintf(
									'%1$s [%2$s] %3$s: %4$s',
									basename(__FILE__),
									$func,
									$line,
									$str
									);
		}
	}

?>