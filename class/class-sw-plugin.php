<?php namespace Sitewit\WpPlugin;

use Sitewit\Exception\SW_Api_Exception;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class SW_Plugin
{
	private static $instance = null;

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new SW_Plugin;
		}

		return self::$instance;
	}

	/**
	 * A starting point to the plugin initialization.
	 * - Add hooks to create
	 *   + Config/Settings menu
	 *   + Print the tracking code onto the footer
	 */
	public function init_hooks() {
		// Add a settings menu to settings/config page of the plugin
		add_action( 'admin_menu', function() {
			// The last argument is the class + function where the page will be rendered
			// The class must be declared as full namespaced as it's under 2 closure
			add_options_page( __('SiteWit Configuration', SW_TEXT_DOMAIN), __('SiteWit', SW_TEXT_DOMAIN),
				'manage_options', SW_SETTING_PAGE, array( '\Sitewit\WpPlugin\SW_Plugin', 'config_page' ) );
		} );

		// Load assets for admin setting page(s)
		if ( sw_is_setting_page() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_assets' ) );
		}

		// Tell user to continue linking SiteWit account
		if ( ( sw_no_token() || sw_no_tracking_code() ) && sw_is_setting_page() ) {
			// Show notice
			add_action( 'admin_notices', function() {
				echo Messages::add_warning(
					__('<strong>SiteWit is almost ready.</strong> You must <a href="%s">link your SiteWit account</a> in order for it to work.'),
					sw_get_setting_page_link()
				);
			} );
		}

		// Add a handler to create account link via AJAX
		if ( is_admin() ) {
			add_action( 'wp_ajax_link_account', array( $this, 'ajax_link_account' ) );
			add_action( 'wp_ajax_reset_account', array( $this, 'ajax_reset_account' ) );
		}

		// Add tracking code to front-end footer if available
		if ( ( $tracking_script = get_option( SW_OPTION_NAME_TRACKING_SCRIPT ) ) !== false ) {
			// This JS code containing a function call should be placed before the actual "sw.js" file call.
			add_action( 'wp_footer', function() {
				echo
<<<"TRACKINGCODE"
<script type="text/javascript">
	var swPreRegister = function() {

	}
</script>
TRACKINGCODE;
			} );

			add_action( 'wp_footer', function() use ($tracking_script) {
				echo $tracking_script;
			} );
		}
	}

	/**
	 * Render the configuration/setting page.
	 * - The config page will include the process to smoothly sign the user(s) up with a SiteWit account.
	 * - When the account is setup properly, it shows the user with a set of icons to quickly navigate to SiteWit reports.
	 */
	public function config_page() {
		// Get SW_Plugin instance because this function might get called from closure and not really have access to $this
		$inc = \Sitewit\WpPlugin\SW_Plugin::get_instance();

		// Include the correct view files
		if ( false !== get_option( SW_OPTION_NAME_API_TOKEN ) && false !== get_option( SW_OPTION_NAME_USER_TOKEN ) ) {
			// Has the tokens, so show the Settings page
			$inc->view( 'settings' );
		} else {
			$current_user = wp_get_current_user();
			$sw_affiliate_id = get_option( SW_OPTION_NAME_AFFILIATE_ID );
			if ( false === $sw_affiliate_id) $sw_affiliate_id = '';

			$args = array(
				'sw_signup_url' => SW_HOST . 'auth/newaccount-wp.aspx?'
													. 'aff=' . urlencode(strtolower($sw_affiliate_id))
													. '&u=' . urlencode(home_url())                     // site url
													. '&n=' . urlencode($current_user->display_name)    // name
													. '&e=' . urlencode($current_user->user_email),     // email
				'sw_login_url' => SW_HOST . 'plugins/wordpress/Default.aspx?login=true'
 			);

			$inc->view( 'config', $args );
		}
	}

	/**
	 * @param string $name Name of the view to be loaded. The file must locates in "views" folder.
	 * @param array  $data Data to be passed to the view
	 */
	public function view( $name, array $data = array() ) {
		$file = SW_PLUGIN_DIR . "views/{$name}.php";
		if ( true === file_exists( $file ) ) {
			// Include the template file
			include $file;
		}
	}

	/**
	 * Load some common assets for (only) admin page
	 */
	public function load_admin_assets() {
		wp_enqueue_style( 'sw-style', SW_PLUGIN_URL . 'assets/sw-style.css' );

		wp_enqueue_script( 'jquery-core' ); // In case there's no jQuery loaded yet
	}

	/**
	 * Perform link account upon receiving an AJAX request. CSRF protection enabled!
	 */
	public function ajax_link_account() {
		// Security check
		check_ajax_referer( 'sw-link-account-nonce', 'swAjaxNonce' );

		$api_token = sanitize_text_field( $_POST['apiToken'] );
		$user_token = sanitize_text_field( $_POST['userToken'] );

		$error = '';

		// Check if the tokens are working by making request to SiteWit API
		if ( $api_token && $user_token ) {
			try {
				$sw_api = new SW_Api( $api_token, $user_token );

				$account_info = $sw_api->get_account();

				if ( isset( $account_info->accountNumber ) ) {
					// Save the tracking code with account number
					$tracking_script =
<<<"TRACKINGSCRIPT"
	<script type="text/javascript">
		var scheme = ( "https:" === document.location.protocol ) ? "https://" : "http://";
		document.write(decodeURI("%3Cscript src='" + scheme + "analytics.sitewit.com/v3/{$account_info->accountNumber}/sw.js' type='text/javascript'%3E%3C/script%3E"));
	</script>
TRACKINGSCRIPT;

					update_option( SW_OPTION_NAME_TRACKING_SCRIPT, $tracking_script );
					update_option( SW_OPTION_NAME_API_TOKEN, $api_token );
					update_option( SW_OPTION_NAME_USER_TOKEN, $user_token );

					if ( isset( $account_info->eaid ) ) {
						update_option( SW_OPTION_NAME_MASTER_ACCOUNT, $account_info->eaid );
					}
				}
			} catch ( SW_Api_Exception $e ) {
				$error = 'SW API call unsuccessful! ' . $e->getMessage();
			} catch ( \Exception $e ) {
				// Think of something to log the catchable errors
				$error = 'Catched exception: ' . $e->getMessage();
			}
		}

		echo json_encode(array(
			'error' => $error
		));

		wp_die(); // For ajax to return immediately with the JSON
	}

	public function ajax_reset_account() {
		// Security check
		check_ajax_referer( 'sw-reset-account-nonce', 'swAjaxNonce' );

		// Check passed, so just remove database-stored value
		delete_option( SW_OPTION_NAME_TRACKING_SCRIPT );
		delete_option( SW_OPTION_NAME_API_TOKEN );
		delete_option( SW_OPTION_NAME_USER_TOKEN );
		delete_option( SW_OPTION_NAME_MASTER_ACCOUNT );

		echo json_encode( array( 'success' => true ) );

		wp_die();
	}
}
