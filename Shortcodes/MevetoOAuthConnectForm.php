<?php

namespace Meveto\Shortcodes;

use Meveto\Frontend\Partials\MevetoConnectPage;

class MevetoOAuthConnectForm
{

    function generateShortCode()
    {
        return MevetoConnectPage::renderLoginForm();
    }
    
    public function addShortCode()
    {
        add_shortcode('connect_to_meveto_form', array($this, 'generateShortCode'));
    }
}
