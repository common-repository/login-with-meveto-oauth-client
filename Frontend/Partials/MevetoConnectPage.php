<?php

namespace Meveto\Frontend\Partials;

class MevetoConnectPage
{
	/**
	 * This method renders HTML for displaying the login form
	 * 
	 */
	public static function renderLoginForm()
	{
		// First let's get the Meveto page where the form will be submitted.
		$pageID = get_option('meveto_connect_page');
		$actionURL = '';

		// Next make sure, the page is available and retrieve the URL for it from page's "post_name"
		if ($pageID) {
			$page = get_post($pageID);
			$url = rtrim($page->post_name, '/'); // removing the '/' character from the end of the URL if it's there.
			$actionURL = home_url($url . '?meveto_id=' . stripslashes(sanitize_text_field($_GET['meveto_id']))); // Attach the supplied Meveto ID to as a query param.
		}
?>
		<div id="mevetmeveto-main-page-container">
			<h2 class="meveto-custom-page-header">
				Connect your Meveto account with your account on this website
			</h2>
			<p class="meveto-para">
				Meveto could not log you in at the moment because it seems your Meveto account is not connected to any account on this website.
				If you already have an account on this website, you can connect it to your Meveto account by simply filling the form below.
			</p>
			<div class="meveto-container">
				<?php
				if (isset($_SESSION['meveto_error_message'])) {
				?>
					<div class="meveto-error-box-no-user">
						<?php echo $_SESSION['meveto_error_message']; ?>
					</div>
				<?php
					unset($_SESSION['meveto_error_message']);
				}
				?>
				<form method="post" action="<?php echo $actionURL; ?>">
					<div class="meveto-form-element">
						<label class="meveto-label" for="login_name">Your login email/username</label>
						<input type="text" name="login_name" id="login_name" placeholder="Your login email/username on this website" required>
					</div>
					<div class="meveto-form-element">
						<label class="meveto-label" for="login_password">Your password</label>
						<input type="password" name="login_password" id="login_password" placeholder="Your login password on this website" required>
					</div>
					<div class="meveto-form-element">
						<button type="submit" class="meveto-button">Connect to Meveto</button>
					</div>
				</form>
			</div>
		</div>
<?php
	}
}
