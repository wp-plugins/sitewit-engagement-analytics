<?php

function sw_deactivate_plugin( $message ) {
	deactivate_plugins( plugin_basename( __FILE__ ) );

	wp_die( $message );
}

function sw_no_token() {
	return false === get_option( SW_OPTION_NAME_API_TOKEN ) || false === get_option( SW_OPTION_NAME_USER_TOKEN );
}

function sw_no_tracking_code() {
	return false === get_option( SW_OPTION_NAME_TRACKING_SCRIPT );
}

function sw_is_setting_page() {
	global $pagenow;

	return ( $pagenow === 'options-general.php' && $_REQUEST['page'] === SW_SETTING_PAGE );
}

function sw_get_setting_page_link( $absolute = false ) {
	$link = 'options-general.php?page=' . SW_SETTING_PAGE;

	if ( $absolute === true ) {
		$link = get_admin_url() . $link;
	}

	return $link;
}
