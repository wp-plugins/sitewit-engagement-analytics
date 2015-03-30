<?php namespace Sitewit\WpPlugin;

use Httpful\Exception\ConnectionErrorException;
use Httpful\Request;
use Sitewit\Exception\SW_Api_Exception;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class SW_Api
{
	public static $instance = null;

	function __construct( $api_token, $user_token ) {
		$template = Request::init()
			->addHeader( 'AccountAuth', base64_encode( "{$api_token}:{$user_token}" ) )
			->expectsJson();

		Request::ini($template);
	}

	public function get_account() {
		$response = Request::get( SW_REST_API_URL . 'account/getaccount' )->send();

		if ( $response->code !== 200 ) {
			throw new SW_Api_Exception( $response->raw_body );
		}

		// Returned JSON will be detected automatically and decoded into "body" property
		return $response->body;
	}
}
