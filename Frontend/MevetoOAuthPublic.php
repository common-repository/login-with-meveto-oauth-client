<?php

namespace Meveto\Frontend;

use Meveto\Includes\MevetoOAuthHandler;
use Meveto\Logging\Log as WriteLog;
use Pusher\Pusher;
use WP_User;

class MevetoOAuthPublic
{
    /**
     * The actions that will be intercepted and processed
     * by the plugin
     * 
     * @var array
     */
    private $allowed_actions = [
        'meveto/login',
        'meveto/redirect',
        'meveto/webhook',
        'connect-to-meveto',
        'meveto/pusherauth',

        '/meveto/login',
        '/meveto/redirect',
        '/meveto/webhook',
        '/connect-to-meveto',
        '/meveto/pusherauth',
    ];

    /**
     * The Meveto OAuth handler
     * 
     * @var MevetoOAuthHandler
     */
    public $handler;

    /**
     * Initiate the plugin. Bootstrap the Meveto handler
     * for OAuth
     * 
     * @return void
     */
    public function __construct()
    {
        $this->handler = new MevetoOAuthHandler();
    }

    /**
     * Register the custom page status for Meveto pages
     * 
     * @return void
     */
    public function meveto_post_status()
    {
        $args = [
            'label' => _x( 'Meveto page', 'Status General Name', 'text_domain' ),
            'label_count' => _n_noop( 'Meveto page (%s)',  'Meveto pages (%s)', 'text_domain' ), 
            'public' => true,
            'show_in_admin_all_list' => false,
            'show_in_admin_status_list' => true,
            'exclude_from_search' => true,
        ];

        register_post_status('meveto_page', $args);
    }

    /**
     * Styles to register and enqueue.
     * 
     * The enqueued scripts and styles are tested in a different class
     * @codeCoverageIgnore
     */
    public function enqueue_styles()
    {
        wp_register_style('meveto-main', plugin_dir_url(__DIR__) . 'assets/css/main.css', []);
        wp_enqueue_style('meveto-main');
        wp_register_style('meveto-button', plugin_dir_url(__DIR__) . 'assets/css/widget.css', []);
        wp_enqueue_style('meveto-button');
        wp_register_style('meveto-toaster', plugin_dir_url(__DIR__) . 'assets/css/toaster.css', []);
        wp_enqueue_style('meveto-toaster');
        wp_register_style('meveto-no-user', plugin_dir_url(__DIR__) . 'assets/css/no_user.css', []);
        wp_enqueue_style('meveto-no-user');
    }

    /**
     * Scripts to register and enqueue.
     * 
     * The enqueued scripts and styles are tested in a different class
     * @codeCoverageIgnore
     */
    public function enqueue_scripts()
    {
        wp_register_script('meveto-pusher-service', plugin_dir_url(__DIR__) . 'assets/js/pusher.js', []);
        wp_enqueue_script('meveto-pusher-service');
        wp_register_script('meveto-toaster', plugin_dir_url(__DIR__) . 'assets/js/toaster.js', []);
        wp_enqueue_script('meveto-toaster');
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

    /**
     * The add endpoints are covered with tests in a different class
     * @codeCoverageIgnore
     */
    public function add_endpoints()
    {
        add_rewrite_endpoint('meveto', EP_ROOT);
    }

    /**
     * This method runs as soon as the WP core loads and when
     * there's a wp_login hook fired i.e. when a user logs in
     * using a password.
     * 
     * If $userLogin argument is defined, that indicates that this
     * method was invoked by a login attempt using a password.
     * 
     * @param string|null $userLogin The WP_User user_login attribute
     * @return void
     */
    public function process_meveto_auth($userLogin = null, $user = null)
    {
        /**
         * Check if both the parameters are passed then the auth
         * process is triggered by the wp_login hook and this must
         * have been a login attempt via a password or any non-Meveto
         * way.
         */
        if ($userLogin && $user) {
            $passwordAttempt = true;
        } else {
            $passwordAttempt = false;
        }
        
        /**
         * An authenticated user may still exist even if the $user
         * param is not passed i.e. the auth is not triggered by the
         * wp_login hook.
         */
        if (!$user) {
            $user = wp_get_current_user();
        }

        /**
         * If an authenticated user was not found, WP sets the ID
         * attribute to integer 0 (Zero)
         */
        if ($user && $user->ID !== 0) {
            /**
             * Check if the current user has started using Meveto. If so, then make sure the user is
             * logged in using Meveto. If the admin has chosen to allow passwords, then skip.
             * Check the option for 'meveto_allow_passwords'
             */
            global $wpdb;
            $table = $wpdb->prefix . 'meveto_users';
            $query = "SELECT last_logged_in, last_logged_out FROM `{$wpdb->dbname}`.`{$table}` WHERE `id` = '{$user->ID}'";
            $mevetoUser = $wpdb->get_row($query);

            if ($mevetoUser != null) {
                if ($passwordAttempt) {
                    // The user is not logged in via Meveto. If passwords are not allowed, Log the user out.
                    if (get_option('meveto_allow_passwords') == 'on') {
                        // Since passwords are allowed, then update the last_logged_in time for the current user
                        $timestamp = time();
                        $query = "UPDATE `{$wpdb->dbname}`.`{$table}` SET `last_logged_in` = '{$timestamp}' WHERE `{$table}`.`id` = '{$user->ID}'";
                        $wpdb->query($query);
                    } else {
                        // Otherwise, do not let the user login using a password.
                        wp_logout();

                        // Redirect the user to the warning page
                        $url = $this->getMevetoPageURL('meveto_login_with_meveto_page');

                        $redirect = wp_redirect(home_url($url));

                        /**
                         * If the redirect is successful and the plugin is
                         * not in testing mode
                         */
                        if ($redirect && $GLOBALS['MEVETO_PLUGIN_ENV'] !== 'testing') exit;
                    }
                } else {
                    if ($mevetoUser->last_logged_out != null && ($mevetoUser->last_logged_out > $mevetoUser->last_logged_in)) {                        
                        /**
                         * If it's not a fresh login attempt, then the currently logged in user has requested a
                         * logout from their Meveto dashboard. Make sure to log the user out.
                         */
                        wp_logout();
                        
                        $redirect = wp_redirect(home_url());

                        /**
                         * If the redirect is successful and the plugin is
                         * not in testing mode
                         */
                        if ($redirect && $GLOBALS['MEVETO_PLUGIN_ENV'] !== 'testing') exit;
                    }
                }
            }
        }
    }

    /**
     * @codeCoverageIgnore
     */
    public function process_meveto_login()
    {
        global $wp;
        $action = $wp->request;

        if ($action == '' or $action == null) {
            global $wp_query;
            $action = $wp_query->query['pagename'];

            if ($action == '' or $action == null) {
                $action = $wp_query->query_vars['meveto'];
            }
        }

        if (in_array($action, $this->allowed_actions)) {
            switch ($action) {
                case 'meveto/login':
                case '/meveto/login':
                    $this->action_login();
                    break;
                case 'meveto/webhook':
                case '/meveto/webhook':
                    $this->action_process_webhook();
                    break;
                case 'meveto/redirect':
                case '/meveto/redirect':
                    $this->action_callback();
                    break;
                case 'connect-to-meveto':
                case '/connect-to-meveto':
                    $this->action_connect_to_meveto();
                    break;
                case 'meveto/pusherauth':
                case '/meveto/pusherauth':
                    $this->action_auth_pusher();
                    break;
            }
        }
    }

    /**
     * Get the URL for the connect to Meveto page
     * 
     * @param string $page The Meveto page identifier
     * @param string|null $mevetoUserId The Meveto user ID
     * @return string|null The request URL or Null if not found
     */
    public function getMevetoPageURL($page, $mevetoUserId = null)
    {
        $pageID = get_option($page);

        if ($pageID) {
            $page = get_post($pageID);

            // remove the '/' character from the end of the URL if it's there.
            $url = rtrim($page->post_name, '/');

            // Attach the meveto_id query param if required
            return $mevetoUserId ? $url . '?meveto_id=' . $mevetoUserId : $url;
        }

        return null;
    }

    public function action_login()
    {
        $redirect = wp_redirect($this->handler->login());

        /**
         * If the redirect is successful and the plugin is
         * not in testing mode
         */
        if ($redirect && $GLOBALS['MEVETO_PLUGIN_ENV'] !== 'testing') exit;
    }

    public function action_callback()
    {
        $mevetoUser = $this->handler->getMevetoUser();

        /**
         * If the result is an array, there has been an error
         * 
         * TODO: Implement a page that will display the error messages.
         */
        if (is_array($mevetoUser)) {

        }

        // Otherwise, continue the process
        $this->login_user($mevetoUser);
    }

    public function login_user($mevetoUserId)
    {
        // First grab user from the database by meveto_id
        global $wpdb;
        $table = $wpdb->prefix . 'users';
        $query = "SELECT * FROM `{$wpdb->dbname}`.`{$table}` WHERE `meveto_id` = '{$mevetoUserId}'";
        $user = $wpdb->get_row($query);
        if ($user !== null) {
            // Set Meveto users record. First check if the Meveto users record exist for this user already or not.
            $table = $wpdb->prefix . 'meveto_users';
            $query = "SELECT * FROM `{$wpdb->dbname}`.`{$table}` WHERE `id` = '{$user->ID}'";
            $meveto_user = $wpdb->get_row($query, 'ARRAY_A');
            $timestamp = time();
            if ($meveto_user == null) {
                // Meveto user was not found. This is probably user's very first time using Meveto with this website.
                $query = "INSERT INTO `{$wpdb->dbname}`.`{$table}` (`id`, `last_logged_in`) VALUES ('{$user->ID}', '{$timestamp}')";
                $wpdb->query($query);
            } else {
                // Update the record for this Meveto user
                $query = "UPDATE `{$wpdb->dbname}`.`{$table}` SET `last_logged_in` = '{$timestamp}' WHERE `{$table}`.`id` = '{$user->ID}'";
                $wpdb->query($query);
            }
            wp_set_current_user($user->ID, $user->user_login);
            wp_set_auth_cookie($user->ID);

            $redirect = wp_redirect(home_url());

            /**
             * If the redirect is successful and the plugin is
             * not in testing mode
             */
            if ($redirect && $GLOBALS['MEVETO_PLUGIN_ENV'] !== 'testing') exit;
        } else {
            $actionURL = $this->getMevetoPageURL('meveto_connect_page', $mevetoUserId);

            // redirect user to connect to Meveto page.
            $redirect = wp_redirect(home_url($actionURL));

            /**
             * If the redirect is successful and the plugin is
             * not in testing mode
             */
            if ($redirect && $GLOBALS['MEVETO_PLUGIN_ENV'] !== 'testing') exit;
        }
    }

    public function action_connect_to_meveto()
    {
        if (
            isset($_POST['login_name']) &&
            isset($_POST['login_password']) &&
            isset($_GET['meveto_id'])
        ) {
            $login_name = stripslashes(sanitize_text_field($_POST['login_name']));
            $login_password = stripslashes(sanitize_text_field($_POST['login_password']));
            $mevetoId = stripslashes(sanitize_text_field($_GET['meveto_id']));

            $user = wp_authenticate($login_name, $login_password);

            if ($user instanceof WP_User && $user->data->meveto_id === null) {
                // Set meveto_id for the user and log the user in
                global $wpdb;
                $table = $wpdb->prefix . 'users';
                $query = "UPDATE `{$wpdb->dbname}`.`{$table}` SET `meveto_id` = '{$mevetoId}' WHERE `{$table}`.`id` = '{$user->ID}'";
                $wpdb->query($query);

                // The login method will also exit the scrip after redirecting user
                $this->login_user($mevetoId);
            }

            if (is_wp_error($user)) {
                $_SESSION['meveto_error_message'] = "You have entered incorrect login credentials.";
            }

            /**
             * Check and see if a Meveto ID is already associated with this user,
             * then reject the login attempt
             */
            if ($user->data->meveto_id !== null) {
                $_SESSION['meveto_error_message'] = "The account you are trying to connect to, is already associated with another Meveto ID.
                If you have lost access to the other Meveto account, then contact Meveto for assistance or contact this website's owner.";
            }

            $actionURL = $this->getMevetoPageURL('meveto_connect_page', $mevetoId);

            // redirect user to connect to Meveto page.
            $redirect = wp_redirect(home_url($actionURL));

            /**
             * If the redirect is successful and the plugin is
             * not in testing mode
             */
            if ($redirect && $GLOBALS['MEVETO_PLUGIN_ENV'] !== 'testing') exit;
        }
    }

    public function action_auth_pusher()
    {
        if (is_user_logged_in()) {
            $channel = stripslashes(sanitize_text_field($_POST['channel_name']));
            $socket = stripslashes(sanitize_text_field($_POST['socket_id']));

            // Make sure the logged in user and the owns the private channel. Extract user ID from the channel name
            $array = explode('.', $channel);
            $userID = array_values(array_slice($array, -1))[0];
            if ($userID == get_current_user_id()) {
                $pusher = $this->instantiatePusher();
                status_header(200);
                echo $pusher->socket_auth($channel, $socket);
            }
        } else {
            status_header(403);
            echo esc_html("Forbidden");
        }
        exit();
    }

    public function action_process_webhook()
    {
        /**
         * Grab content from the request of the webhook call.
         * 
         * @var array Holds info of the webhook call
         */
        $data = json_decode(file_get_contents("php://input"), true) ?? $_REQUEST;

        /**
         * First let's attempt to identify the associated local user.
         * Begin by trying to get the user's Meveto ID an exchange for
         * the one time user token.
         * 
         * If a local user can not be identified, the plugin should
         * return a 404 http status right away.
         */
        $mevetoUser = $this->handler->getTokenUser($data['user_token']);

        if (! $mevetoUser) {
            status_header(404);
            exit();
        }

        // Find the corresponding local user for the Meveto ID
        global $wpdb;
        $table = $wpdb->prefix . 'users';
        $query = "SELECT * FROM `{$wpdb->dbname}`.`{$table}` WHERE `meveto_id` = '{$mevetoUser}'";
        $user = $wpdb->get_row($query);

        if (! $user) {
            status_header(404);
            exit();
        }

        /**
         * Switch over type of the event.
         * The webhook call is expected to contain a 'type' field that
         * indicates a particular event/action initiated by the user.
         */
        switch ($data['type']) {
            /**
             * In case the user wants to logout.
             * Logout event
             */
            case 'User_Logged_Out':
                $table = $wpdb->prefix . 'meveto_users';
                $timestamp = time();
                // Update the last_logged_out record for this Meveto user
                $query = "UPDATE `{$wpdb->dbname}`.`{$table}` SET `last_logged_out` = '{$timestamp}' WHERE `{$table}`.`id` = '{$user->ID}'";
                $wpdb->query($query);

                // Trigger pusher event
                $pusher = $this->instantiatePusher();
                $data['message'] = '';
                $pusher->trigger('private-Meveto-Kill.' . $user->ID, 'logout', $data);
                status_header(200);
                exit();
                break;
            
            /**
             * In case the user wants to remove Meveto protection from their
             * account on this website.
             * 
             * This action will nullify the 'meveto_id' attribute of the user's
             * table for this particular user.
             */
            case 'Meveto_Protection_Removed':
                $query = "UPDATE `{$wpdb->dbname}`.`{$table}` SET `meveto_id` = NULL WHERE `{$table}`.`ID` = '{$user->ID}'";
                $wpdb->query($query);

                // Next, remove the Meveto Users record for the user
                $table = $wpdb->prefix . 'meveto_users';
                $query = "DELETE FROM `{$wpdb->dbname}`.`{$table}` WHERE `{$table}`.`id` = '{$user->ID}'";
                $wpdb->query($query);

                /**
                 * Event if the user could not be found here locally, send a 200 response to Meveto regardless.
                 * This is because the User could possibly have not mapped their Meveto account to a local
                 * user account.
                 */
                status_header(200);
                exit();
                break;
        }
    }

    public function instantiatePusher()
    {
        // Determine whether to use TLS or not
        $tls = false;
        if (
            isset($_SERVER['HTTPS']) &&
            ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
            isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
            $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
        ) {
            $tls = true;
        }
        $options = array(
            'cluster' => get_option('meveto_pusher_cluster'),
            'useTLS' => $tls
        );
        $pusher = new Pusher(
            get_option('meveto_pusher_key'),
            get_option('meveto_pusher_secret'),
            get_option('meveto_pusher_app_id'),
            $options
        );

        return $pusher;
    }
}
