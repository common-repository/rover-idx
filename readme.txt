=== Rover IDX ===
Author: Rover IDX, LLC
Contributors: stevemullen
Author URL: https://roveridx.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Tags: IDX, MLS, multiple listing service, RETS, WebApi, rover idx, idx wordpress, wordpress idx, wordpress mls, idx wordpress plugin, integrated idx, real estate, real estate wordpress, WordPress Plugin, realtor, broker
Requires at least: 3.1
Tested up to: 6.6.2
Stable tag: 3.0.0.2906

== Description ==

<h3>* Rover IDX is a fast and highly configurable IDX for your Office or Agent website *</h3>

Rover IDX for Wordpress is a mobile responsive real estate IDX plugin, providing fast, searchable real estate listings for website visitors.  The plugin is highly configurable and flexible, allowing you to create a unique website.  Most data is stored on our highly scalable servers, and renders on your WordPress website with lightning speed.

<h3>Rover IDX for Wordpress</h3>

<h4>Up and running in minutes</h4>
Activate Rover IDX, choose the MLS region, and add the [rover_idx_full_page] shortcode to a page.  Boom - your site is displaying searchable real estate listings.  You can then customize what cities are offered for search, what the search panel looks like, what the listing layout looks like, and how the property details page is displayed.

<h4>Unbelievable levels of customization</h4>
Out of the box, Rover IDX just works.  You do not need to know html, css, or php.  Further, Rover offers many listing layout templates to choose from, so you can customize your listings with a click.  You can also customize the search panel with drag and drop efficiency.  If you do want to get into the nitty gritty, you can create your own layout templates - and we offer basic templates to get you started.

<h4>Terrific support</h4>
Ask us questions - we are here to help.  Have a feature request?  Let's talk about it!

<h4>Integrate Multiple MLS's seamlessly</h4>
Display listings from multiple MLS's in one place.  Our automatic de-dupe feature displays ensures the same listings from two different MLS's will not display twice.

<h4>Maps</h4>
Maps can be integrated into [rover_idx_full_page] as a dynamic search experience.  Allow visitors to draw shapes on the map to limit searches to within those shapes.  Define shapes (called defined locations) (towns, counties, subdivisions), and allow visitors to search by clicking on these defined locations.

<h4>CRM</h4>
Rover IDX has a CRM, used to track and organize your leads.  See the searches / properties visitors have viewed.  Add Notes and Todo's.  Keep a follow up calendar.

CRM Integration with Follow Up Boss directly by adding your FUB Api Key.  Integrate with Top Producer, Zapier and others with their parseable email features.

<h4>WordPress Widgets</h4>
Add straightforward widgets for Quick Search, Ask a Question, Featured Listings, Quick Links, and Mortgage and Affordability Calculators.

<h4>Conveniently Add Shortcodes</h4>
Within the Wordpress visual editor, Rover IDX offers a add-a-shortcode button with the kitchen sink of parameters.  Just adjust the parameters you care about, and remove or ignore the rest.  [Rover IDX shortcode guide](https://roveridx.com/documentation/shortcode-help/ "Rover IDX shortcode guide")

<h4>Unbelievable SEO</h4>
All Rover IDX pages are SEO friendly.  Clean urls, and there are no iframes or subdomains.  Rover IDX also adds structured data where appropriate.  All the property detail pages are in your domain, and Googlebot will find them.  And Rover IDX maintains a sitemap of all the active listings represented by your website, and the various crawlers are updated every 24 hours with that updated sitemap.

<h4>Mobile friendly</h4>
All Rover IDX pages are mobile friendly, as long as the Wordpress theme you've chosen is also mobile friendly.  Rover IDX has a Mobile settings page that allows you to tune mobile behavior.


== Installation ==

1. Upload the `rover-idx` folder to the `/wp-content/plugins/` directory
1. Activate the Rover IDX plugin through the 'Plugins' menu in WordPress
1. Configure the plugin by going to the `Rover IDX` menu that appears in your admin menu

== Frequently Asked Questions ==

= Does Rover IDX work with any theme? =

Yes, as long as the theme follows Wordpress conventions.  Themes created for Wordpress 3+ are better.

= How is Rover IDX priced?  =

See https://roveridx.com/resources/pricing/ for pricing.

= How many websites does my Rover IDX subscription allow?  =

You can run one subscription of Rover IDX on up to 5 websites for the active agent.

= If I have multiple websites with Rover IDX, can I tie them together? =

Yes!  Rover IDX has a 'link this website' feature that allows you and your clients to log into any of the websites and manage their settings and criteria.

= Does Rover IDX work with my MLS region? =

We work with about 50 MLS's, and that list is growing every month.  If we don't currently have your MLS region, we are happy to investigate adding it.  We do all the paperwork (except your signatures), and setting up a new region usually takes 2-3 days.

== Screenshots ==

1. Setting up Rover IDX is a breeze
2. Type in the name or broker last name of the office, and select the active agents.  You can set meta data for the agents as well.
3. Choose from any of the listing layout templates that ship with Rover IDX, or build your own.
4. Super easy to add searchable pages - [rover_idx_full_page] creates a fully searchable page with search panel, listing results, and in 2-column mode a sidebar with highlighted listings.
5. Simple, clean layouts for highly usable pages.
6. Don't forget to include a map.  Visitors love maps!
7. Easy add links to your sidebar, footer, or page.  These links take advantage of the Rover IDX dynamic url feature.  If the page does not exist in your website, Rover IDX will parse the url, hoping that the url is calling for searchable listings specified by the url.  See https://roveridx.com/features/roverurl/ for more information.

== Changelog ==

= 3.0.0.2906 =

* Tighten security for automatic real estate posts

= 3.0.0.2905 =

* Close Authentication Bypass vulnerability

= 3.0.0.2903 =

* Completed WordPress 6.6.2 testing
* Improved no-cache resiliency for /rover-control-panel/

= 3.0.0.2860 =

* Completed WordPress 6.5 testing
* Resolved older host communication error

= 3.0.0.2827 =

* Fix php deprecation menu error when hosting on php 8.2

= 3.0.0.2807 =

*  disable wp_cache_add() as it is conflicting with wordpress.com

= 3.0.0.2806 =

*  php 8.1 error when Reset

= 3.0.0.2793 =

*  Auto blog posting - Retry for servers that prohibit allow_url_fopen
*  New feature [rover_idx_site_search] shortcode

= 3.0.0.2772 =

*  Fix for nocache regression in /rover-control-panel/

= 3.0.0.2771 =

*  Tested up to WordPress 6.2
*  Extend timeout to 60 seconds on slow servers

= 3.0.0.2731 =

*  Allow /rover-login-panel/ to use default page template

= 3.0.0.2717 =

*  Avoid cPanel update that has broken net.ipv4.tcp_fastopen
*  Add feature to prevent WordPress automatic URL guessing

= 3.0.0.2661 =

*  Tested up to WordPress 6.1

= 3.0.0.2660 =

*  Fix issue that prevented auto-blog images from being correctly posted

= 3.0.0.2654 =

*  Improve date formatting for auto-blog posts

= 3.0.0.2647 =

*  [rover_idx_links] Fix error when no parameters given in shortcode

= 3.0.0.2627 =

*  Fix intermittent crash when changing permalinks

= 3.0.0.2619 =

*  Fix for page with same slug structure as property page

= 3.0.0.2615 =

*  Fix for WordPress 6.0 do_parse_request issue

= 3.0.0.2613 =

*  Rover IDX is WordPress 6.0-ready

= 3.0.0.2611 =

*  Fix bug affecting display of sitemap history

= 3.0.0.2605 =

*  Improve RankMath integration.

= 3.0.0.2604 =

*  Fix for egregious Contact Form 7 bug that vendor is ignoring.

= 3.0.0.2531 =

*  Add missing file

= 3.0.0.2527 =

*  IMPORTANT: Sitemaps moved to root of site
*  Improve integration for Yoast SEO plugin
*  Add integration for SEO Framework plugin
*  Add integration for All in One SEO plugin
*  Add integration for Rank Math SEO
*  Add integration for SEOPress
*  Add integration for Squirrly SEO

= 3.0.0.2561 =

*  WordPress 5.9 ready

= 3.0.0.2480 =

*  When changing MLS region, also update other key settings

= 3.0.0.2400 =

*  Fix for critical error loading seo panel for some configurations

= 3.0.0.2397 =

*  Validate IP Resolve address which memcached seems to sometimes typecast to an integer

= 3.0.0.2392 =

*  New parseable email feature for Top Producer, Zapier and other CRM integration

= 3.0.0.2387 =

*  WordPress 5.8 certified

= 3.0.0.2385 =

*  Resolve php warning for missing HTTP_ACCEPT request header

= 3.0.0.2371 =

*  Add ability to add custom classes to more parts of the Login / Register menu

= 3.0.0.2363 =

*  Improve Login / Register menu layout

= 3.0.0.2353 =

*  Relax connection timeout limit for slower shared hosting

= 3.0.0.2350 =

*  Fix build upload error

= 3.0.0.2349 =

*  Improve blog post url structure

= 3.0.0.2244 =

*  WordPress 5.7 certified

= 3.0.0.2235 =

*  Fix url regression on setup
*  Relax connect timeout for slower shared servers

= 3.0.0.2215 =

*  Fix menu slug error

= 3.0.0.2214 =

*  Improve auto-blogging duplication handling
*  Allow configurable endpoint

= 3.0.0.2193 =

*  Modify menu html to prevent display:block interfering with layout

= 3.0.0.2140 =

*  Testing up to WordPress 5.6
*  Resolve php 7 warning on styling

= 3.0.0.2133 =

*  Resolve php 7 warning on setup

= 3.0.0.2095 =

*  Improve datetime mgmt for auto-blogging
*  Improve menu layout

= 3.0.0.2057 =

*  Improve dropdown Login / Register menu structure for some themes

= 3.0.0.2053 =

*  resolve undeclared ROVER_JS
*  For some themes, add presentation span for dropdown Login / Register menu


= 3.0.0.2028 =

*  preload important css during activation

= 3.0.0.2024 =

*  Improve support for Yoast premium plugin and Rover IDX dynamic page titles

= 3.0.0.1861 =

*  resolve logging error

= 3.0.0.1860 =

*  resolve kit error

= 3.0.0.1859 =

* Performance:  Refactor javascript loading for significant performance
* Performance:  Remove all jQuery dependancy.  Your theme can now dequeue jQuery, for faster page loads
* Performance:  Searches are now faster
* Customization:  Search panel colors can now be changed via plugin settings
* Usability:  Mobile touch Slideshow now available for many listing layouts


= 2.1.0.2192 =

* Performance:  Tune javascript loading for significant performance boost

= 2.1.0.2105 =

* Performance:  Faster DNS lookup for faster page loads.
* Allow selecting default page template for dynamic page layouts

= 2.1.0.2080 =

* Feature:  Ability to add `Favorites` and `Saved Searches` menus to Wordpress menu.

= 2.1.0.2043 =

* improve auto-blogging tracking
* improve auto-blog titles

= 2.1.0.2042 =

* deactivation and reactivation could result in some settings being lost
* add logging to detect settings changes

= 2.1.0.2041 =

* Fix php warning on hosts displaying warnings

= 2.1.0.2034 =

* Avoid situation where non-expanded shortcode can appear in cached page

= 2.1.0.2027 =

* Upgrade admin panels to Bootstrap 4

= 2.1.0.1985 =

* Fix Social key mismatch for auto-blogging

= 2.1.0.1985 =

* Add reset feature to clear Wordpress options

= 2.1.0.1978 =

* Fix setup issue that resulted in incomplete data being saved to Wordpress options

= 2.1.0.1971 =

* Complete testing for Wordpress 5.1 support - no issues

= 2.1.0.1755 =

* Minor fix for multi-region handling

= 2.1.0.1749 =

* Rover IDX 2.1.0 release

= 2.1.0.1327 =

* Tested up to Wordpress 4.9 / add capability to exclude url slugs / additional debug capability

= 2.1.0.1326 =

* Add helpful debug / php 7 check for item existence

= 2.1.0.1325 =

* Improve logic of url parsing to avoid conflict with third party plugin

= 2.1.0.1324 =

* Improve reliability of sitemap when `allow_url_fopen` is disabled

= 2.1.0.1323 =

* Fix PHP 7 incompatibility with affordability calculator

= 2.1.0.1318 =

* Fix regression in previous release

= 2.1.0.1317 =

* Improve admin_notices to warn administrator when activated Rover plugin needs a region to be selected

= 2.1.0.1316 =

* Improve algorithm that decides to load js / css from cdn for performance

= 2.1.0.1315 =

* add back old [rover_idx_login shortcode] that has been removed before 2.0 was released
* improve reliability of email unsubscribe

= 2.1.0.1314 =

* prepare for new feature - custom, non-MLS listings
* default system pages (/rover-control-panel/, /rover-custom-listing-panel/) to `naked` page template for maximum screen real estate
* improved reliability of automatic sitemap generation

= 2.1.0.1313 =

* added 'Search Panel Orientation' radio button to Rover Omni Search, Rover - Search by MLS Number, and Rover - Search by School widgets

= 2.1.0.1312 =

* quick search widget now defaults to price range dropdown, which is more mobile friendly
* declare support for Wordpress 4.8

= 2.1.0.1309 =

* added help text to Rover IDX >> SEO >> Dynamic Page Definitions
* improved reliability of automatic sitemap generation

= 2.1.0.1308 =

* quick fix for regression in 1307

= 2.1.0.1307 =

* Improve reliability of automatic sitemap generation
* Fix error when loading rover-control-panel using the `naked_page.php` template

= 2.1.0.1306 =

* PHP warning for admin panel
* Load help (Rover IDX >> Help) over https

= 2.1.0.1304 =

* Remove empty 'Last' label from recently viewed widget
* Resolve issue with selected cities not being passed to new and updated widget

= 2.1.0.1302 =

* Improve custom template - no longer need to create a page using that template to see template in dropdown
* Default dynamic page post_type to page
* Follow WP codex example to return custom template, and not exit

= 2.1.0.1301 =

* Fix strpos() issue for older Rover version upgrades

= 2.1.0.1294 =

* Fix uncommon path that could result in css not loaded for back end plugin

= 2.1.0.1274 =

* New search panels!
* Facebook Login!
* Property Highlight Banners!
* Map polygon search for visitors!
* Automatically add Login / Register to Wordpress menu!
* New rover_idx_registration shortcode - highly customizable!
* New rover_idx_contact shortcode - highly customizable!

= 1.2.6.1733 =

* Resolve parse error on old unsupported php versions

= 1.2.6.1731 =

* Improve saving of Styling settings to Wordpress options.

= 1.2.6.1605 =

* Rover IDX will now process Diverse Solutions shortcodes and IDX Data Filters.

= 1.2.6.1604 =

* Avoid duplicating Rover shortcode button on Visual tab when editing Post / Page
* Add 'listings per row' to Styling >> Listings page settings
* Use key 'search_panel_layout' for all quick search widgets
* Allow "Print" to work correctly when in a property details dialog (worked correctly in a new window)

= 1.2.6 =

* Although Rover IDX is 8 years old and used in many real estate sites, this is the initial Wordpress repository release.

== Upgrade Notice ==

