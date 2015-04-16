<?php defined('ABSPATH') or die("No script kiddies please!"); ?>

<div class="wrap sw-config-page">
	<h2>
		<?php _e('SiteWit Dashboard', SW_TEXT_DOMAIN) ?>
	</h2>

	<div class="sw-report-shortcuts">
		<div class="shortcut banner" id="sw-link-newcamp">
			<img src="<?php echo SW_PLUGIN_URL . 'assets/banner.png'; ?>" width="552px" />
		</div>

		<div class="shortcut shortcut-tile" id="sw-link-marketing">
			<span class="dashicons dashicons-welcome-widgets-menus"></span>
			<h1><?php _e('Marketing'); ?></php></h1>
		</div>
		<div class="shortcut shortcut-tile" id="sw-link-leads">
			<span class="dashicons dashicons-groups"></span>
			<h1><?php _e('Leads'); ?></php></h1>
		</div>
		<div class="shortcut shortcut-tile" id="sw-link-stats">
			<span class="dashicons dashicons-chart-line"></span>
			<h1><?php _e('Stats'); ?></php></h1>
		</div>
	</div>

	<h3><?php _e('Change account'); ?></h3>
	<div class="sw-message">
		<?php _e('If you want to link this WordPress site to another SiteWit account, please click <a id="reset-link" href="javascript:void(0);">here</a>'); ?>
	</div>


	<h3><?php _e('Contact Us'); ?></h3>
	<div class="sw-contact">
		Call us: 1-877-474-8394 (Monday to Friday: 9:00 AM - 6:00 PM EST)<br/>
		Email: <a href="mailto:support@sitewit.com">support@sitewit.com</a><br/>
		Create a support <a target="_blank" href="http://support.sitewit.com/hc/en-us/requests/new">ticket</a><br/>
		Or find us
			<a target="_blank" href="https://www.facebook.com/SiteWit"><span class="dashicons dashicons-facebook"></span></a>
			<a target="_blank" href="https://twitter.com/SiteWit"><span class="dashicons dashicons-twitter"></span></a>
			<a target="_blank" href="https://plus.google.com/115202446868642776828"><span class="dashicons dashicons-googleplus"></span></a>
	</div>
</div>

<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery("#reset-link").on("click", function() {
			if (confirm("<?php _e('Are you sure you want to re-link? Current information will be lost!'); ?>")) {
				// Request to clear associated data of the SiteWit account being linked
				var data = {
					action: "reset_account",
					swAjaxNonce: "<?php echo wp_create_nonce( 'sw-reset-account-nonce' ); ?>"
				};

				// Make ajax request, expecting JSON response. "ajaxurl" is a global JS variable from WordPress
				jQuery.post(ajaxurl, data, function (response) {
					if (response === -1 || response === null) {
						alert("<?php _e('Request failed, please try again!'); ?>");
					} else {
						// Refresh the page (with no cache) and user will be presented with the config page
						location.reload(true);
					}
				}, "json");
			}
		});

		jQuery("div.shortcut").on("click", function() {
			var link = "<?php echo SW_HOST; ?>";
			var acc = "<?php echo get_option( SW_OPTION_NAME_MASTER_ACCOUNT, '' ); ?>";
			var elId = jQuery(this).attr("id").split("-");
			switch(elId[2]) {
				case "newcamp":
					link += "smb/campaigns/new/Default.aspx?load=new";
					break;
				case "marketing":
					link += "smb/campaigns/new/Default.aspx?load=new";
					break;
				case "leads":
					link += "smb/connect/dashboard";
					break;
				case "stats":
					link += "smb/analytics";
					break;
			}

			if (acc !== "") {
				if (link.indexOf("?") === -1) {
					link += "?setacct=" + acc;
				} else {
					link += "&setacct=" + acc;
				}
			}

			var win = window.open(link, "_blank");
			win.focus();
		});
	});
</script>
