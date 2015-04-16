<?php namespace Sitewit\WpPlugin;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class SW_Logger
{
	public static $logger = null;

	function __construct() {
		if ( WP_DEBUG &&  class_exists( '\Monolog\Logger' ) ) {
			self::$logger = new \Monolog\Logger('SiteWit WP Logger');
			self::$logger->pushHandler(new \Monolog\Handler\BrowserConsoleHandler(), \Monolog\Logger::DEBUG);
		}
	}

	public static function log( $type, $message ) {
		if ( null === self::$logger ) return;

		self::$logger->addDebug( $type, (array) $message );
	}
}
