<?php
namespace Meveto\Admin;

use Meveto\Admin\Partials\Settings;

class MevetoOAuthAdmin
{    
    /**
     * The options name prefix to be used in this plugin
     *
     * @var string
     */
    private $optionNamePrefix = 'meveto_';

    /**
     * Add the Wordpress admin dashboard menu item
     * 
     * @return void
     */
    public function extendMenu()
    {
        /**
         * TODO: Perhaps add an icon URL as well?
         */
        add_menu_page(
            'Meveto Client Settings',
            'Meveto',
            'administrator',
            'meveto-client-settings',
            [$this, 'mevetoOauthSettingsPage']
        );
    }

    public function mevetoOauthSettingsPage()
    {
        echo new Settings();
    }

    public function manage_settings()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'meveto_manage_settings') {
            $this->save_settings();
        }
    }

    private function save_settings()
    {
        /**
         * Meveto configuration
         */
        $client_id = stripslashes(sanitize_text_field($_POST['meveto_oauth_client_id']));
        $client_secret = stripslashes(sanitize_text_field($_POST['meveto_oauth_client_secret']));
        // $scope = stripslashes(sanitize_text_field($_POST['meveto_oauth_scope']));
        // $authorize_url = esc_url_raw($_POST['meveto_oauth_authorize_url']);
        // $token_url = esc_url_raw($_POST['meveto_oauth_token_url']);
        $allow_passwords = stripslashes(sanitize_text_field($_POST['meveto_allow_passwords']));

        /**
         * Pusher configuration
         */
        $pusher_app = stripslashes(sanitize_text_field($_POST['meveto_pusher_app_id']));
        $pusher_key = stripslashes(sanitize_text_field($_POST['meveto_pusher_key']));
        $pusher_secret = stripslashes(sanitize_text_field($_POST['meveto_pusher_secret']));
        $pusher_cluster = stripslashes(sanitize_text_field($_POST['meveto_pusher_cluster']));

        update_option($this->optionNamePrefix . 'oauth_client_id', $client_id);
        update_option($this->optionNamePrefix . 'oauth_client_secret', $client_secret);
        // update_option($this->optionNamePrefix . 'oauth_scope', $scope);
        // update_option($this->optionNamePrefix . 'oauth_authorize_url', $authorize_url);
        // update_option($this->optionNamePrefix . 'oauth_token_url', $token_url);
        update_option($this->optionNamePrefix . 'allow_passwords', $allow_passwords);

        update_option($this->optionNamePrefix . 'pusher_app_id', $pusher_app);
        update_option($this->optionNamePrefix . 'pusher_key', $pusher_key);
        update_option($this->optionNamePrefix . 'pusher_secret', $pusher_secret);
        update_option($this->optionNamePrefix . 'pusher_cluster', $pusher_cluster);
    }

    public function enqueue_styles()
    {
        wp_register_style('meveto-main', plugin_dir_url(__DIR__) . 'assets/css/main.css', []);
        wp_enqueue_style('meveto-main');
        wp_register_style('meveto-admin', plugin_dir_url(__FILE__) . '/css/admin.css', []);
        wp_enqueue_style('meveto-admin');
        wp_register_style('meveto-toaster', plugin_dir_url(__DIR__) . 'assets/css/toaster.css', []);
        wp_enqueue_style('meveto-toaster');
    }

    public function enqueue_scripts()
    {
        wp_register_script('pusher', plugin_dir_url(__DIR__) . 'assets/js/pusher.js', []);
        wp_enqueue_script('pusher');
        wp_register_script('toaster', plugin_dir_url(__DIR__) . 'assets/js/toaster.js', []);
        wp_enqueue_script('toaster');
        wp_register_script('meveto-pusher', plugin_dir_url(__DIR__) . 'assets/js/meveto.pusher.js', []);
        wp_localize_script('meveto-pusher', 'data', [
            'userId' => get_current_user_id() ? get_current_user_id() : null,
            'key' => get_option('meveto_pusher_key') ? get_option('meveto_pusher_key') : null,
            'cluster' => get_option('meveto_pusher_cluster') ? get_option('meveto_pusher_cluster') : null,
            'authEndpoint' => home_url('meveto/pusherauth'),
            'homeUrl' => get_home_url(),
        ]);
        wp_enqueue_script('meveto-pusher');
    }
}
