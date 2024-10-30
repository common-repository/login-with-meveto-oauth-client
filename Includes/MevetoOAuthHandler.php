<?php

namespace Meveto\Includes;

use Meveto\Client\MevetoService;
use Meveto\Logging\Log;

/**
 * This class handles all the OAuth related HTTP requests.
 * The class makes use of the Meveto PHP SDK to perform
 * the OAuth actions.
 * 
 * @since 2.0.0
 */
class MevetoOAuthHandler
{
    /**
     * Object of the Meveto OAuth handler service
     * 
     * @var MevetoService
     */
    protected $meveto;

    /**
     * Instantiate the Meveto OAuth handler.
     * Using the Meveto PHP SDK
     * 
     * @return void
     */
    public function __construct()
    {
        /** @var array The Meveto SDK config array */
        $config = [
            'redirect_url' => 'http://localhost/wordpress/meveto/redirect',
        ];

        /**
         * Check if the plugin is running in production then process
         * accordingly.
         */
        if ($GLOBALS['MEVETO_PLUGIN_ENV'] && $GLOBALS['MEVETO_PLUGIN_ENV'] === 'production') {
            $config['redirect_url'] = sprintf(
                "%s%s/%s",
                (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://",
                $_SERVER['HTTP_HOST'],
                'meveto/redirect'
            );
        } else {
            /**
             * If the plugin is not in production, then override the OAuth
             * endpoints to not use the Meveto live production servers.
             */
            $config = array_merge($config, [
                'authEndpoint' => 'http://localhost:3000/oauth-client',
                'tokenEndpoint' => 'http://127.0.0.1:8000/oauth/token',
            ]);
        }

        /**
         * If the required options are not set, perhaps the plugin is not
         * activated yet. Anyway, abort setting up the OAuth service.
         */
        if (
            get_option('meveto_oauth_client_id') &&
            get_option('meveto_oauth_client_secret')
        ) {
            $this->meveto = new MevetoService(array_merge(
                [
                    'id' => get_option('meveto_oauth_client_id'),
                    'secret' => get_option('meveto_oauth_client_secret'),
                ],
                $config
            ));
        }
    }

    /**
     * Initiate the user login process
     * 
     * @return string The URL to redirect the user to
     */
    public function login()
    {
        $ct = isset($_GET['client_token']) ? stripslashes(sanitize_text_field($_GET['client_token'])) : null;
        $st = isset($_GET['sharing_token']) ? stripslashes(sanitize_text_field($_GET['sharing_token'])) : null;

        $this->meveto->setState($this->setLocalState());

        return $this->meveto->login($ct, $st);
    }

    /**
     * Get an access token by exchanging an Oauth authorization code.
     * and then use the access token to get the resource owner's Meveto
     * ID.
     * 
     * If the process fails, this method will return an array that contains
     * a 'title' and a 'message' key or otherwise, it will return the Meveto
     * ID of the user which is almost all the times an integer.
     * 
     * @return mixed
     */
    public function getMevetoUser()
    {
        /**
         * Latest error that may occur. Defaults to empty
         * array.
         * 
         * @var array
         */
        $error = [];

        try {

            // First verify the state parameter
            if (! $this->verifyLocalState($_GET['state'])) {
                $error['title'] = 'Invalid application state.';
                $error['message'] = 'The state parameter that was sent by this application does not match the one that was received in the OAuth response.';
                return $error;
            }

            // Exchange the code with an access token
            $response = $this->meveto->getAccessToken($_GET['code']);

            // Make sure that an access token was returned
            if ($response['error']) {
                $error['title'] = $response['error'];
                $error['message'] = $response['error_description'];
                return $error;
            }

            $data = $this->meveto->getResourceOwnerData($response['access_token']);

            // Again, make sure that a user was returned
            if ($data['error']) {
                $error['title'] = $response['error'];
                $error['message'] = $response['error_description'];
                return $error;
            }

            // If all succeeds, return the user
            return $data['user'];

        } catch(\Exception $e) {
            /**
             * This must be an error with the network connection
             * TODO: handle exceptions that may happen if the plugin
             * TODO: can't communicate with Meveto servers
             */
        }
    }

    /**
     * Get the user that is associated with the current webhook event
     * 
     * @param string $userToken The user token delivered by a webhook call
     * @return mixed
     */
    public function getTokenUser($userToken)
    {
        try {
            return $this->meveto->getTokenUser($userToken);
        } catch(\Exception $e) {
            /**
             * This must be an error with the network connection
             * TODO: handle exceptions that may happen if the plugin
             * TODO: can't communicate with Meveto servers
             */
        }

        return null;
    }

    /**
     * Generate a random bytes string to use as state
     * for the OAuth flow.
     * 
     * @return string
     */
    private function setLocalState()
    {
        $state = bin2hex(random_bytes(64));

        // Make sure to store the state in the database
        global $wpdb;

        $insertionQuery = <<<MS
        INSERT INTO `$wpdb->dbname`.`{$wpdb->prefix}meveto_states`
        (
            `id`,
            `state`
        )
        VALUES
        (
            NULL,
            '$state'
        )
MS;

        $wpdb->query(trim($insertionQuery));

        return $state;
    }

    /**
     * Generate a random bytes string to use as state
     * for the OAuth flow.
     * 
     * @param string|null $state
     * @return bool
     */
    private function verifyLocalState($state = null)
    {
        global $wpdb;

        $selectionQuery = <<<MS
        SELECT `state` FROM `$wpdb->dbname`.`{$wpdb->prefix}meveto_states`
        WHERE BINARY `state` = '$state'
MS;

        $found = $wpdb->get_row(trim($selectionQuery));

        /**
         * If the state was found, delete it and
         * return true
         */
        if (is_object($found)) {
            $deletionQuery = <<<MS
            DELETE FROM `$wpdb->dbname`.`{$wpdb->prefix}meveto_states`
            WHERE BINARY `state` = '$state'
MS;

            $wpdb->query(trim($deletionQuery));

            return $found->state === $state;
        }

        return false;
    }
}
