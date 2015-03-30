<?php

/*
Plugin Name: SiteWit Website Analytics and Search Engine Marketing
Plugin URI: http://www.sitewit.com
Description: SiteWit is a DIY online marketing platform. Start with FREE website analytics and SEO keyword ranking.
Version: 2.0.3
Author: SiteWit
Author URI: http://www.sitewit.com
Text Domain: sitewit-engagement-analytics
Domain path: /languages
License: GPLv2 or later
*/

// This plugin use PHP 5.3 features, so need to exit right away if the PHP version of the host is < 5.3
define( 'SW_PHP_MIN_VERSION', '5.3.0' );
if ( version_compare( PHP_VERSION, SW_PHP_MIN_VERSION, '<' ) ) {
	exit( 'This plugin requires PHP version 5.3 and later! Version 5.4.0 and over is recommended as 5.3 also reached EOL on 14 Aug 2014.' );
}

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

define( 'SW_PLUGIN_FILE', __FILE__ );
define( 'SW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SW_NAMESPACE', 'Sitewit\WpPlugin' );
define( 'SW_SETTING_PAGE', 'sitewitconfig' );
define( 'SW_HOST', 'https://login.sitewit.com/' );
define( 'SW_REST_API_URL', 'https://reporting.sitewit.com/api/' );

// Text Domain for internationalization. MUST match plugin slug!
// Change this and also change the Text Domain in the header above.
define( 'SW_TEXT_DOMAIN', 'sitewit-engagement-analytics' );

define( 'SW_OPTION_NAME_API_TOKEN', 'sw_api_token' );
define( 'SW_OPTION_NAME_USER_TOKEN', 'sw_user_token' );
define( 'SW_OPTION_NAME_TRACKING_SCRIPT', 'sw_tracking_script' );
define( 'SW_OPTION_NAME_MASTER_ACCOUNT', 'sw_master_account' );

define( 'SW_AFFILIATE_ID', '' );    // The affiliate is empty but this is needed for API access

// Support for internationalization
load_plugin_textdomain( SW_TEXT_DOMAIN, false, basename( dirname( __FILE__ ) ) . '/languages' );

// This should contain all the checks to ensure the plugin will operate properly.
function activation_check() {
	// Check for SOAP extension availability so we can connect to our API
	if ( ! function_exists( 'curl_init' ) ) {
		sw_deactivate_plugin( __('This plugin requires cURL PHP extension to be enabled. Please contact your hosting provider to enable it.', SW_TEXT_DOMAIN) );
	}

	// Check if the site already has tracking code injected by cPanel
	// Parsing the .htaccess file and find the inject code. Need test to make sure we can read the .htaccess file.
	$htaccess_file = get_home_path() . '.htaccess';
	if ( true === file_exists( $htaccess_file ) ) {
		$content = file_get_contents( $htaccess_file );
		if ( false !== strpos( $content, "AddOutputFilterByType SUBSTITUTE text/html" )
		     && 1 === preg_match('/sitewit.com\/v3\/\d+\/sw\.js/', $content) )
		{
			sw_deactivate_plugin( __('This site seems to already have tracking code injected by cPanel. Please go to cPanel for SiteWit Reports.', SW_TEXT_DOMAIN) );
		}
	}
}

register_activation_hook( __FILE__, 'activation_check' );

require_once 'init.php';
