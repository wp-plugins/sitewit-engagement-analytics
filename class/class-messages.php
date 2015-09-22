<?php namespace Sitewit\WpPlugin;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class Messages
{
	private function build_message( $message_class = 'updated', array $args ) {
		$message_format = '<div class="%s"><p>' . $args[0] . '</p></div>';

		array_shift($args);
		if ( count( $args ) > 0 ) {
			array_unshift( $args, $message_class );
		}

		return vsprintf( $message_format, $args );
	}

	public static function add_info( $message = '' ) {
		return ($message) ? self::build_message( 'updated', func_get_args() ) : '';
	}

	public static function add_error( $message = '' ) {
		return ($message) ? self::build_message( 'error', func_get_args() ) : '';
	}

	public static function add_warning( $message = '' ) {
		return ($message) ? self::build_message( 'update-nag', func_get_args() ) : '';
	}
}
