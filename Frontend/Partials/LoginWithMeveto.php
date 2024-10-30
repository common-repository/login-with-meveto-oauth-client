<?php

namespace Meveto\Frontend\Partials;

class LoginWithMeveto
{

	/**
	 * This method renders HTML for displaying the login form
	 * 
	 */
	public static function renderLoginWithMevetoPage()
	{
?>
		<div id="mevetmeveto-main-page-container">
			<h2 class="meveto-custom-page-header">
				Meveto has prevented your account from being accessed with a password!
			</h2>
			<p class="meveto-para">
                You must login to your account on this website using Meveto. If you need help with your account on this website, 
                contact them or if you need help with your Meveto account, then contact Meveto support.
			</p>
			<div class="meveto-container">
                <div class="meveto-form-element">
						<a href="<?php echo home_url().'/meveto/login'; ?>" class="meveto-button">Login with Meveto</a>
				</div>
			</div>
		</div>
<?php
	}
}