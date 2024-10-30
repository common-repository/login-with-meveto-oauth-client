<?php

namespace Meveto\Shortcodes;

use Meveto\Frontend\Partials\MevetoLoginButton;

class MevetoOAuthPublicButton
{
    function generateShortCode()
    {
        return MevetoLoginButton::renderLoginButton();
    }
    
    public function addShortCode()
    {
        add_shortcode('public_oauth_button', array($this, 'generateShortCode'));
    }
}
