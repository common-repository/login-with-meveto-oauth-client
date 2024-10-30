<?php

namespace Meveto\Includes;

use Meveto\Admin\MevetoOAuthAdmin;
use Meveto\Frontend\MevetoOAuthPublic;
use Meveto\Frontend\MevetoOAuthWidget;

class MevetoOAuth
{
    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @access protected
     * @var MevetoOAuthLoader
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @access protected
     * @var string
     */
    protected $pluginName = 'Meveto';

    /**
     * The current version of the plugin.
     *
     * @access protected
     * @var string
     */
    protected $version = '0.0.0';

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     * 
     * The following files that make up the plugin:
     *
     * - MevetoOAuthLoader. Orchestrates the hooks of the plugin.
     * - MevetoOAuthAdmin. Defines all hooks for the admin area.
     * - MevetoOAuthPublic. Defines all hooks for the public side of the site.
     * 
     * @return void
     */
    public function __construct()
    {
        if(defined('MEVETO_OAUTH_VERSION')) $this->version = MEVETO_OAUTH_VERSION;

        $this->loader = new MevetoOAuthLoader();

        $this->defineAdminHooks();
        $this->defineWidgetHooks();
        $this->definePublicHooks();
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @access private
     */
    private function defineAdminHooks()
    {
        $plugin_admin = new MevetoOAuthAdmin();
        $this->loader->add_action('admin_init', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_init', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'extendMenu');
        $this->loader->add_action('admin_init', $plugin_admin, 'manage_settings');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @access private
     */
    private function definePublicHooks()
    {
        $plugin_public = new MevetoOAuthPublic();

        $this->loader->add_action('init', $plugin_public, 'add_endpoints');
        $this->loader->add_action('init', $plugin_public, 'meveto_post_status', 0);
        $this->loader->add_action('wp', $plugin_public, 'process_meveto_auth', 10);
        $this->loader->add_action('wp', $plugin_public, 'process_meveto_login');

        // We wanna pass an argument to the "wp_login" action so we specify it as 1 in the 4rth arg
        $this->loader->add_action('wp_login', $plugin_public, 'process_meveto_auth', 20, 2);
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_action('login_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('login_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }

    /**
     * Register all of the hooks related to the widgets functionality
     * of the plugin.
     *
     * @access private
     */
    private function defineWidgetHooks()
    {
        $plugin_widget = new MevetoOAuthWidget();

        $this->loader->add_action('widgets_init', $plugin_widget, 'register_meveto_widget');
        $this->loader->add_action('login_form', $plugin_widget, 'show_login_button');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @return string
     */
    public function getPluginName()
    {
        return $this->pluginName;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return MevetoOAuthLoader
     */
    public function getLoader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }
}
