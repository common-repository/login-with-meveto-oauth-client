<?php

namespace Meveto\Shortcodes;

use Meveto\Frontend\Partials\LoginWithMeveto;

class MevetoLoginWithMeveto
{

    function generateShortCode()
    {
        return LoginWithMeveto::renderLoginWithMevetoPage();
    }
    
    public function addShortCode()
    {
        add_shortcode('login_with_meveto_page', array($this, 'generateShortCode'));
    }
}
