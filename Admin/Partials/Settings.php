<?php

namespace Meveto\Admin\Partials;

class Settings
{
    /**
     * The Meveto ID of this website (client)
     * 
     * @var string
     */
    protected $clientID = '';

    /**
     * The Meveto secret of this website (client)
     * 
     * @var string
     */
    protected $clientSecret = '';

    /**
     * The OAuth access scope
     * 
     * @var string
     */
    // protected $scope = 'default-client-access';

    /**
     * The Meveto authorization URL
     * 
     * @var string
     */
    // protected $authURL = 'https://dashboard.meveto.com/oauth-client';

    /**
     * The Meveto OAuth token URL
     * 
     * @var string
     */
    // protected $tokenURL = 'https://prod.meveto.com/oauth/token';

    /**
     * Whether to allow users to login to
     * this website using their passwords
     * after they start using Meveto.
     * 
     * This rule also applies to admin users.
     * 
     * @var string
     */
    protected $allowPasswords = 'off';

    /**
     * The Pusher app ID for this website
     * 
     * @var string|null
     */
    protected $pusherAppID = null;

    /**
     * The Pusher app key for this website
     * 
     * @var string|null
     */
    protected $pusherAppKey = null;

    /**
     * The Pusher app secret for this website
     * 
     * @var string|null
     */
    protected $pusherAppSecret = null;

    /**
     * The Pusher app cluster for this website
     * 
     * @var string
     */
    protected $pusherAppCluster = 'mt1';

    /**
     * Setup the settings page.
     * 
     * @return void
     */
    public function __construct()
    {
        // override client ID if an option is already set
        if (! empty(get_option('meveto_oauth_client_id'))) {
            $this->clientID = get_option('meveto_oauth_client_id');
        }

        // override client secret if an option is already set
        if (! empty(get_option('meveto_oauth_client_secret'))) {
            $this->clientSecret = get_option('meveto_oauth_client_secret');
        }

        // override scope if an option is already set
        // if (! empty(get_option('meveto_oauth_scope'))) {
        //     $this->scope = get_option('meveto_oauth_scope');
        // }

        // override auth URL if an option is already set
        // if (! empty(get_option('meveto_oauth_authorize_url'))) {
        //     $this->authURL = get_option('meveto_oauth_authorize_url');
        // }

        // override token URL if an option is already set
        // if (! empty(get_option('meveto_oauth_token_url'))) {
        //     $this->tokenURL = get_option('meveto_oauth_token_url');
        // }

        // override allow passwords if an option is already set
        if (! empty(get_option('meveto_allow_passwords'))) {
            $this->allowPasswords = get_option('meveto_allow_passwords');
        }

        // override the pusher app ID if an option is already set
        if (! empty(get_option('meveto_pusher_app_id'))) {
            $this->pusherAppID = get_option('meveto_pusher_app_id');
        }

        // override the pusher app key if an option is already set
        if (! empty(get_option('meveto_pusher_key'))) {
            $this->pusherAppKey = get_option('meveto_pusher_key');
        }

        // override the pusher app secret if an option is already set
        if (! empty(get_option('meveto_pusher_secret'))) {
            $this->pusherAppSecret = get_option('meveto_pusher_secret');
        }

        // override the pusher app cluster if an option is already set
        if (! empty(get_option('meveto_pusher_cluster'))) {
            $this->pusherAppCluster = get_option('meveto_pusher_cluster');
        }
    }

    /**
     * Produce an HTML string that will be
     * rendered by the required page.
     * 
     * @return string
     */
    public function __toString(): string
    {
        $conditional = $this->allowPasswords == 'on' ? 'checked' : '';

        $html = <<<HTML
        <div class="wrap meveto-settings">
    <h1>Configure Meveto OAuth Client Integration</h1>
    <form method="post" action="">
        <input type="hidden" name="action" value="meveto_manage_settings">
        <h3>
            Client Configuration
        </h3>
        <hr />
        <table class="form-table">
            <tr>
                <th>
                    <span class="meveto-required">*</span> Client ID:
                </th>
                <td>
                    <input class="regular-text" required="" type="text" name="meveto_oauth_client_id" value="$this->clientID">
                </td>
            </tr>
            <tr>
                <th>
                    <span class="meveto-required">*</span> Client Secret:
                </th>
                <td>
                    <input class="regular-text" required="" type="password" name="meveto_oauth_client_secret" value="$this->clientSecret">
                </td>
            </tr>

            <!-- <tr>
                <th>
                    <span class="meveto-required">*</span> Scope:
                </th>
                <td>
                    <input class="regular-text" type="text" name="meveto_oauth_scope" value="$this->scope">
                </td>
            </tr>
            <tr>
                <th>
                    <span class=" meveto-required">*</span> Authorize Endpoint:
                </th>
                <td>
                    <input class="regular-text" type="text" name="meveto_oauth_authorize_url" value="$this->authURL">
                </td>
            </tr>
            <tr>
                <th>
                    <span class=" meveto-required">*</span> Access Token Endpoint:
                </th>
                <td>
                    <input class="regular-text" type="text" name="meveto_oauth_token_url" value="$this->tokenURL">
                </td>
            </tr> -->

            <tr>
                <th>
                    Allow Passwords:
                </th>
                <td>
                    <input type="checkbox" name="meveto_allow_passwords" $conditional >
                    <br />
                    <span class="meveto-required">DO NOT ALLOW PASSWORDS.</span>
                    <br />
                    Meveto plugin only disables password-based login for those users that have logged in to your website using Meveto at least once.
                </td>
            </tr>
        </table>

        <h3>
            Pusher Configuration
        </h3>
        <hr />
        <p>
            We highly recommend you use <a href=" https://pusher.com">Pusher </a> with Meveto so that your website can perform seamless actions when your Meveto users take an action from their Meveto dashboard. For example, when a user logs out from your website using their Meveto dashboard, your website will be able to refresh automatically.
            <br />
            <br />
            <strong>DISCLAIMER:</strong> Meveto is not associated in any way with Pusher. It's a recommendation only.
        </p>
        <table class="form-table">
            <tr>
                <th>
                    Your Pusher App ID:
                </th>
                <td>
                    <input class="regular-text" type="text" name="meveto_pusher_app_id" value="$this->pusherAppID">
                </td>
            </tr>
            <tr>
                <th>
                    Your Pusher App Key:
                </th>
                <td>
                    <input class=" regular-text" type="text" name="meveto_pusher_key" value="$this->pusherAppKey">
                </td>
            </tr>
            <tr>
                <th>
                    Your Pusher App Secret:
                </th>
                <td>
                    <input class=" regular-text" type="text" name="meveto_pusher_secret" value="$this->pusherAppSecret">
                </td>
            </tr>
            <tr>
                <th>
                    Your Pusher App Cluster:
                </th>
                <td>
                    <input class=" regular-text" type="text" name="meveto_pusher_cluster" value="$this->pusherAppCluster">
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" 
                name="submit" 
                id="submit" 
                class="wp-core-ui button" 
                value="Save Settings"
            />
        </p>
    </form>
</div>
HTML;

        return $html;
    }
}