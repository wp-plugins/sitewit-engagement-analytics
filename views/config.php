<?php defined('ABSPATH') or die("No script kiddies please!"); ?>

<div class="wrap sw-config-page">
	<h2><?php _e('Link to SiteWit account'); ?></h2>
	<iframe id="sw-frame" scrolling="no" width="500" seamless="seamless" src="<?php echo $data['sw_signup_url']; ?>"></iframe>

	<div class="sw-link-account" style="display: none;">
		<input type="hidden" id="api-token" name="apiToken" value="" />
		<input type="hidden" id="user-token" name="userToken" value="" />

		<div class="sw-center">
			<button class="button button-primary sw-button-big" id="link-account-btn" type="button">
				<?php _e('Link Your Account') ?>
			</button>
		</div>
	</div>

	<div class="sw-note sw-signup-info sw-default-hide">
		<ul>
			<li><?php _e('We are using the information from currently logged in WordPress user to fill out the form. Please feel free to make changes.') ?></li>
			<li><?php _e('Please provide a secured password for your account') ?></li>
		</ul>
	</div>

	<div class="sw-note sw-login-info sw-default-hide">
		<ul>
			<li><?php _e('If this is not the account you wanted, please navigate to <a target="_blank" href="https://login.sitewit.com">https://login.sitewit.com</a> to log out.') ?></li>
		</ul>
	</div>

	<div class="sw-note sw-form-info sw-default-hide">
		<ul>
			<li><?php _e('This form is under secured SSL connection, so your information is safe with us.') ?></li>
		</ul>
	</div>
</div>

<script type="text/javascript">
	/* This code uses window.postMessage() for inter-window/domain messaging. It's not supported by IE < 8. */
	var swHost = "<?php echo SW_HOST; ?>";  // The host to send the message to. Should be https://login.sitewit.com.
	var swFrame = jQuery("#sw-frame");
	var linkButton = jQuery("#link-account-btn");

	function sameOrigin(host1, host2) {
		// Generalize the domain, removing trailing slash(es)
		host1 = host1.replace(/\/+$/, "");
		host2 = host2.replace(/\/+$/, "");

		return host1 === host2;
	}

	function receiveTokens(event) {
		var oriEvent = event.originalEvent;

		// Don't do anything if the message not come from SiteWit
		if ( ! sameOrigin(oriEvent.origin, swHost)) return;

		// Process the data received
		var data = jQuery.parseJSON(oriEvent.data);
		jQuery.each(data, function(i, item) {
			switch (item.mType) {
				case "wh": // window height
					// Set height of the iframe according to its content's actual height
					swFrame.height(item.wHeight);

					// Show/hide some guidance information based on the type of window the iFrame loaded
					jQuery(".sw-note").hide();

					if (item.wName !== "link-account") {
						jQuery(".sw-form-info").show();
					} else {
						jQuery(".sw-login-info").show();
					}

					if (item.wName === "new-account") {
						jQuery(".sw-signup-info").show();
					}

					break;

				case "tk": // tokens
					if (item.apiToken !== "" && item.userToken !== "") {
						// Show the link button
						jQuery(".sw-link-account").show();

						// Only one master account for this user, link account right away
						if (item.numAcct === 1) {
							linkAccount(item.apiToken, item.userToken);
						} else {
							// Save the tokens outsite the frame
							jQuery("#api-token").val(item.apiToken);
							jQuery("#user-token").val(item.userToken);
						}
					}

					break;

				default:
					break;
			}
		});
	}

	function checkMessage() {
		document.getElementById("sw-frame").contentWindow.postMessage("ping", swHost);
	}

	function linkAccount(apiToken, userToken) {
		// Disable the button so the user won't make multiple requests
		linkButton.attr("disabled", "disabled").text("<?php _e('Linking account...'); ?>");

		// Prepare data to post
		var data = {
			action: "link_account",
			swAjaxNonce: "<?php echo wp_create_nonce( 'sw-link-account-nonce' ); ?>",
			apiToken: apiToken,
			userToken: userToken
		};

		// Make ajax request, expecting JSON response. "ajaxurl" is a global JS variable from WordPress
		jQuery.post(ajaxurl, data, function(response) {
			if (response === -1 || response === null || response.error !== "") {
				linkButton.text("<?php _e('Linking Failed'); ?>");
				alert("<?php _e('Request failed, please try again!'); ?>");
			} else {
				linkButton.text("<?php _e('Success! Redirecting...'); ?>");
			}
			location.reload();
		}, "json");
	}

	jQuery(document).ready(function() {
		jQuery(window).on("message", receiveTokens);

		swFrame.on("load", function() { // everytime the iframe is loaded/reloaded
			checkMessage();
		});

		// Link account button clicked with an ajax request
		linkButton.on("click", function() {
			linkAccount(jQuery("#api-token").val(), jQuery("#user-token").val());
		});
	});
</script>
