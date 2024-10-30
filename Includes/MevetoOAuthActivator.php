<?php

namespace Meveto\Includes;

use Meveto\Shortcodes\MevetoLoginWithMeveto;
use Meveto\Shortcodes\MevetoOAuthConnectForm;
use Meveto\Shortcodes\MevetoOAuthPublicButton;

class MevetoOAuthActivator
{
    /**
     * URL slug of the connect to Meveto page
     * @var string
     */
    protected static $connectPageSlug = 'connect-to-meveto';

    /**
     * URL slug of the warning page when attempting to login
     * using a password
     * 
     * @var string
     */
    protected static $warningPageSlug = 'login-with-meveto';

    /**
     * Process the plugin's activation
     */
    public static function activate()
    {
        /**
         * Before doing anything, first let's make sure the currently logged in user is
         * authorized to activate plugins.
         * 
         * However, if the plugin is running in tests, then skip
         */
        if (! current_user_can('activate_plugins') && $GLOBALS['MEVETO_PLUGIN_ENV'] !== 'testing') return;

        /**
         * Add endpoint for 'meveto'
         */
        add_rewrite_endpoint('meveto', EP_ROOT);

        /**
         * First, let's setup the database for the Meveto plugin.
         */
        global $wpdb;

        // Add 'meveto_id' column to the 'users' table if it doesn't exist
        $check_column_query = "SHOW COLUMNS FROM `" . $wpdb->prefix . 'users' . "` LIKE 'meveto_id'";
        $result = $wpdb->get_results($check_column_query, ARRAY_A);
        if (count($result) === 0) {
            $wpdb->query("ALTER TABLE `" . $wpdb->dbname . "`.`" . $wpdb->prefix . 'users' . "` ADD `meveto_id` VARCHAR(255) NULL DEFAULT NULL AFTER `ID`");
            $wpdb->query("ALTER TABLE `" . $wpdb->dbname . "`.`" . $wpdb->prefix . 'users' . "` ADD UNIQUE `users_meveto_id_unique` (`meveto_id`)");
        }

        // The query for creating the meveto_users table
        $MevetoUsersTableQuery = <<<MU
        CREATE TABLE IF NOT EXISTS `$wpdb->dbname`.`{$wpdb->prefix}meveto_users` 
        (
            `id` BIGINT UNSIGNED NOT NULL,
            `last_logged_in` BIGINT NULL DEFAULT NULL,
            `last_logged_out` BIGINT NULL DEFAULT NULL,
            UNIQUE `wp_meveto_users_id_unique` (`id`)
        ) ENGINE = InnoDB CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci
MU;

        // The query for creating the meveto_states table
        $MevetoStatesTableQuery = <<<MS
        CREATE TABLE IF NOT EXISTS `$wpdb->dbname`.`{$wpdb->prefix}meveto_states`
        (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `state` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE = InnoDB CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci
MS;

        // Attempt to create the required tables if they don't exist.
        $wpdb->query(trim($MevetoUsersTableQuery));
        $wpdb->query(trim($MevetoStatesTableQuery));

        /**
         * Next, create required pages.
         */
        $currentUser = wp_get_current_user();
        if (null === $wpdb->get_row("SELECT post_name FROM {$wpdb->prefix}posts WHERE post_name = '". self::$connectPageSlug ."'", 'ARRAY_A')) {
            $page = array(
                'post_type'   => 'page',
                'post_title'  => __('Connect to Meveto'),
                'post_status' => 'meveto_page',
                'post_author' => $currentUser->ID,
                'post_content' => '[connect_to_meveto_form]',
                'comment_status' => 'closed',
                'ping_status' => 'closed',
            );

            // insert the post into the database
            $pageID = wp_insert_post($page);

            // store as an option to keep track of the page by ID
            update_option('meveto_connect_page', $pageID);
        }

        if (null === $wpdb->get_row("SELECT post_name FROM {$wpdb->prefix}posts WHERE post_name = '". self::$warningPageSlug ."'", 'ARRAY_A')) {
            $page = array(
                'post_type'   => 'page',
                'post_title'  => __('Login with Meveto'),
                'post_status' => 'meveto_page',
                'post_author' => $currentUser->ID,
                'post_content' => '[login_with_meveto_page]',
                'comment_status' => 'closed',
                'ping_status' => 'closed',
            );

            // insert the post into the database
            $pageID = wp_insert_post($page);

            // store as an option to keep track of the page by ID
            update_option('meveto_login_with_meveto_page', $pageID);
        }
    }

    /**
     * Register shortcodes of the plugin
     */
    public static function registerShortCodes()
    {
        $mopb = new MevetoOAuthPublicButton;
        $mopb->addShortCode();

        $mocb = new MevetoOAuthConnectForm;
        $mocb->addShortCode();

        $mlwm = new MevetoLoginWithMeveto;
        $mlwm->addShortCode();
    }
}
