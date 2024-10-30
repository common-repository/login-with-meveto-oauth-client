<?php
/**
 * Plugin Name: Meveto
 * Plugin URI: https://meveto.com
 * Description: This plugin will help you integrate Meveto in your WordPress site to use Meveto's password-less authentication both for you and your users.
 * Version: 3.0.2
 * Author: Meveto Inc
 * Author URI: https://meveto.com
 * License: GPL2
 */

use Meveto\Includes\MevetoOAuthActivator;
use Meveto\Includes\MevetoOAuth;
use Meveto\Includes\MevetoOAuthDeactivator;

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';


ob_start();

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('MEVETO_OAUTH_VERSION', '3.0.2');

/**
 * The value of this global variable defines
 * certain behaviors of the plugin. The enumerated
 * values that may be used are:
 * 
 * - development
 * - testing
 * - production
 */
$GLOBALS['MEVETO_PLUGIN_ENV'] = 'production';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */
function activateMevetoOAuth()
{
    MevetoOAuthActivator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */
function deactivateMevetoOAuth()
{
    MevetoOAuthDeactivator::deactivate();
}

register_activation_hook(__FILE__, 'activateMevetoOAuth');
register_deactivation_hook(__FILE__, 'deactivateMevetoOAuth');

/**
 * Register plugin's Shortcodes
 */
MevetoOAuthActivator::registerShortCodes();

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */

function run_meveto_oauth()
{
    $plugin = new MevetoOAuth();
    $plugin->run();
}

run_meveto_oauth();
