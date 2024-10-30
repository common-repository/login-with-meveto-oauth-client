<?php

namespace Meveto\Frontend;

use \WP_Widget;
use Meveto\Frontend\Partials\MevetoLoginButton;

class MevetoOAuthWidget extends WP_Widget
{
    public function __construct()
    {
        parent::__construct(false, 'Meveto OAuth Login Widget', ['description' => __('Login to Apps with Meveto', 'flw')]);
    }

    public function widget($args, $instance)
    {
        /**
         * The class responsible for displaying 'Login with Meveto' link
         */
        MevetoLoginButton::renderLoginButton();
    }

    public function update($new_instance, $old_instance)
    {
        $instance = [];
        $instance['wid_title'] = strip_tags($new_instance['wid_title']);

        return $instance;
    }

    public function register_meveto_widget()
    {
        register_widget(get_class($this));
    }

    public function show_login_button()
    {
        /**
         * The class responsible for displaying 'Login with Meveto' link
         */
        MevetoLoginButton::renderLoginButton();
    }
}
