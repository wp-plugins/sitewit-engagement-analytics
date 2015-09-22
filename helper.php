<?php

function sw_deactivate_plugin( $message ) {
	deactivate_plugins( plugin_basename( __FILE__ ) );

	wp_die( $message );
}

function sw_no_token() {
	return ! get_option( SW_OPTION_NAME_API_TOKEN ) || ! get_option( SW_OPTION_NAME_USER_TOKEN );
}

function sw_no_tracking_code() {
	return ! get_option( SW_OPTION_NAME_TRACKING_SCRIPT );
}

function sw_get_tracking_code() {
    $code = get_option( SW_OPTION_NAME_TRACKING_SCRIPT );

    return ( ! $code ) ? false : $code;
}

function sw_is_setting_page() {
	global $pagenow;

	return ( $pagenow === 'options-general.php' && isset($_REQUEST['page']) && $_REQUEST['page'] === SW_SETTING_PAGE );
}

function sw_get_setting_page_link( $absolute = false ) {
	$link = 'options-general.php?page=' . SW_SETTING_PAGE;

	if ( $absolute === true ) {
		$link = get_admin_url() . $link;
	}

	return $link;
}
