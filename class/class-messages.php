<?php namespace Sitewit\WpPlugin;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class Messages
{
	protected static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Messages;
		}

		return self::$instance;
	}

	private function build_message( $message_class = 'updated', array $args ) {
		$message_format = '<div class="%s"><p>' . $args[0] . '</p></div>';

		array_shift($args);
		if ( count( $args ) > 0 ) {
			array_unshift( $args, $message_class );
		}

		return vsprintf( $message_format, $args );
	}

	public function add_info( $message = '' ) {
		return ($message) ? $this->build_message( 'updated', func_get_args() ) : '';
	}

	public function add_error( $message = '' ) {
		return ($message) ? $this->build_message( 'error', func_get_args() ) : '';
	}

	public function add_warning( $message = '' ) {
		return ($message) ? $this->build_message( 'update-nag', func_get_args() ) : '';
	}
}
