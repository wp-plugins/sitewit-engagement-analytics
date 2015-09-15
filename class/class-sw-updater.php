<?php namespace Sitewit\WpPlugin;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class SW_Updater
{
    /**
     * Update the tracking script to properly handle HTTPS sites.
     */
    public function UpdateFrom1To2()
    {
        // Tracking script option
        $tracking_script = get_option( SW_OPTION_NAME_TRACKING_SCRIPT );

        if ( false === $tracking_script || $tracking_script === '' ) return false;

        $tracking_script = str_replace( '"https" === document.location.protocol', '"https:" === document.location.protocol', $tracking_script );

        update_option( SW_OPTION_NAME_TRACKING_SCRIPT, $tracking_script );

        return true;
    }
}
