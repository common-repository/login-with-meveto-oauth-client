<?php

use Meveto\Frontend\MevetoOAuthPublic;
use Meveto\Includes\MevetoOAuthActivator;

/**
 * Tests the main class that deals with most of
 * the Meveto's core functionality.
 * 
 * @package Meveto_Wp_Plugin
 */
class MevetoOauthPublicTest extends WP_UnitTestCase
{
	/**
	 * This object represents an instance of the
	 * class associated with this test class.
	 * 
	 * @var Meveto_OAuth_Public
	 */
	public $classObj = null;

	/**
	 * @var WP_User
	 */
	public $user = null;

	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();

		// Set the plugin's environment to testing
		$GLOBALS['MEVETO_PLUGIN_ENV'] = 'testing';
	}

	/**
	 * Setup an environment for tests.
	 * 
	 * @backupGlobals enabled
	 */
	public function setUp()
	{
		parent::setUp();

		add_filter( 'wp_redirect', 'wp_halt_redirect', 1, 2);

		// Activate the plugin
		MevetoOAuthActivator::activate();

		// Initialize an object of the target class
		$this->classObj = new MevetoOAuthPublic();

		// Update some required options
		update_option('meveto_oauth_client_id', '1000');
		update_option('meveto_oauth_client_secret', 'test-client-secret');
		update_option('meveto_oauth_scope', 'default-client-access');
		update_option('meveto_oauth_authorize_url', 'https://dashboard.meveto.com/oauth/authorize');

		// Get a test user
		$this->user = $this->getTestUser();
	}

	/**
	 * Clean up after a test is complete
	 */
	public function tearDown() {
		remove_filter( 'wp_redirect', 'wp_halt_redirect', 1);

		$this->classObj = null;

		$this->user = null;
	}

	/**
	 * @test
	 */
	public function test_process_meveto_auth()
	{
		$currentUser = null;

		// Set the current user
		wp_set_current_user($this->user->ID, $this->user->user_login);
		wp_set_auth_cookie($this->user->ID);

		/**
		 * At this stage, the test user has not used Meveto to login to
		 * this site yet. The plugin should not prevent the user from
		 * being logged in.
		 */
		$this->classObj->process_meveto_auth($this->user->user_login, $this->user);

		/**
		 * As a test assertion, the ID of the current user must exist and
		 * should be equal to the ID set on the $this->user variable.
		 */
		$this->assertTrue(is_user_logged_in(), "Normal password based user login");
		$currentUser = wp_get_current_user();
		$this->assertEquals($currentUser->ID, $this->user->ID, "User ID on normal password based login");

		/**
		 * Now attempt to mark the user as having logged in with Meveto
		 * before.
		 */
		$userLoginWithMeveto = $this->makeLoginWithMeveto($this->user->ID);

		// Also allow passwords by admin
		update_option('meveto_allow_passwords', 'on');

		/**
		 * The user has now logged in with Meveto before, and the admin
		 * of the website has allowed passwords, therefore, the  plugin
		 * should not log the user out and instead only update the user's
		 * last logged in time.
		 * 
		 * But before processing, assert that the last logged in time is
		 * NULL at the moment.
		 */
		$this->assertNull($userLoginWithMeveto['last_logged_in'], "Last login time");

		$this->classObj->process_meveto_auth($this->user->user_login, $this->user);

		/**
		 * The user should be still logged in and the last logged in
		 * time should not be NULL anymore and the user must still be
		 * logged in.
		 */
		$userLoginWithMeveto = $this->makeLoginWithMeveto($this->user->ID);
		$this->assertNotNull($userLoginWithMeveto['last_logged_in'], "Last login time");
		$this->assertTrue(is_user_logged_in(), "If user is logged in or not");

		/**
		 * Next, disallow the passwords and repeat the process.
		 * This time, the plugin must log the user out.
		 */
		update_option('meveto_allow_passwords', 'off');

		/**
		 * The plugin is expected to produce a redirect.
		 * The redirect is being intercepted and converted to an exception
		 */
		try {
			// Invoke the process
			$this->classObj->process_meveto_auth($this->user->user_login, $this->user);
			$redirect = [];
		} catch (Exception $e) {
			$redirect = json_decode($e->getMessage(), true);
		}
		$this->assertNotEmpty($redirect);
		$this->assertEquals($this->classObj->getMevetoPageURL('meveto_login_with_meveto_page'), $redirect['location']);
		$this->assertEquals(302, $redirect['status']);

		// There should not be any logged in user
		$this->assertFalse(is_user_logged_in(), "If user is logged in or not");

		/**
		 * Now, let's set the current user again and simulate the
		 * logout request from Meveto dashboard.
		 */
		wp_set_current_user($this->user->ID, $this->user->user_login);
		wp_set_auth_cookie($this->user->ID);

		// Simulate the logout
		$this->logoutFromMevetoDashboard($this->user->ID);

		/**
		 * As soon as the logout has been request, this process will be
		 * invoked by the user's very next move.
		 * 
		 * But before invoking the plugin's process, let's assert the user
		 * is currently logged in and no longer logged in after the process.
		 */
		$this->assertTrue(is_user_logged_in(), "If user is logged in or not");

		/**
		 * The plugin is expected to produce a redirect.
		 * The redirect is being intercepted and converted to an exception
		 */
		try {
			// Invoke the process
			$this->classObj->process_meveto_auth();
			$redirect = [];
		} catch (Exception $e) {
			$redirect = json_decode( $e->getMessage(), true );
		}

		$this->assertNotEmpty($redirect);
		$this->assertEquals(home_url(), $redirect['location']);
		$this->assertEquals(302, $redirect['status']);

		// Assert the user is not logged in anymore
		$this->assertFalse(is_user_logged_in(), "If user is logged in or not");
	}

	/**
	 * @test
	 */
	public function test_getMevetoPageURL()
	{
		$pid = get_option('meveto_connect_page');
		$page = get_post($pid);
		$url = rtrim($page->guid, '/');
		$this->assertEquals($url.'?meveto_id=1000', $this->classObj->getMevetoPageURL('meveto_connect_page', '1000'));

		$pid = get_option('meveto_login_with_meveto_page');
		$page = get_post($pid);
		$url = rtrim($page->guid, '/');
		$this->assertEquals($url, $this->classObj->getMevetoPageURL('meveto_login_with_meveto_page'));
	}

	/**
	 * @test
	 */
	public function test_action_login()
	{
		/**
		 * The WP redirect is intercepted.
		 * Catch the redirect locations and assert against them
		 */

		// Trying plain authorization URL
		try {
			$this->classObj->action_login();
			$redirect = [];
		} catch(Exception $e) {
			$redirect = json_decode($e->getMessage(), true);
		}
		$this->assertNotEmpty($redirect);

		// Asserting that string has string because the actual URL will have a random state parameter
		$this->assertStringContainsString(
			'http://localhost:3000/oauth-client?client_id=1000&scope=default-client-access&response_type=code&redirect_uri=http://localhost/wordpress/meveto/redirect',
			urldecode($redirect['location']
		));
		$this->assertEquals(302, $redirect['status']);

		// Set client token query parameter
		$_GET['client_token'] = 'token123';
		try {
			$this->classObj->action_login();
			$redirect = [];
		} catch(Exception $e) {
			$redirect = json_decode($e->getMessage(), true);
		}
		$this->assertNotEmpty($redirect);
		$this->assertStringContainsString('&client_token=token123', urldecode($redirect['location']));
		$this->assertEquals(302, $redirect['status']);

		// Unset the client token query parameter and set the sharing token
		unset($_GET['client_token']);
		$_GET['sharing_token'] = 'token123';
		try {
			$this->classObj->action_login();
			$redirect = [];
		} catch(Exception $e) {
			$redirect = json_decode($e->getMessage(), true);
		}
		$this->assertNotEmpty($redirect);
		$this->assertStringContainsString('&sharing_token=token123', urldecode($redirect['location']));
		$this->assertEquals(302, $redirect['status']);

		/**
		 * Since the sharing token query param is already set,
		 * set the client token query param as well and assert
		 * against the presence of both.
		 */
		$_GET['client_token'] = 'token123';
		try {
			$this->classObj->action_login();
			$redirect = [];
		} catch(Exception $e) {
			$redirect = json_decode($e->getMessage(), true);
		}
		$this->assertNotEmpty($redirect);
		$this->assertStringContainsString('&client_token=token123&sharing_token=token123', urldecode($redirect['location']));
		$this->assertEquals(302, $redirect['status']);
	}

	/**
	 * Add a user to the database for tests
	 * 
	 * @return WP_User
	 */
	public function getTestUser()
	{
		global $wpdb;

        $tablename = $wpdb->prefix . 'users';

        // Prepare the user query
        $query = "INSERT INTO `" . $wpdb->dbname . "`.`" . $tablename . "` 
        (`ID`, `meveto_id`, `user_login`, `user_pass`, `user_nicename`, `user_email`, `user_url`, `user_registered`, `user_activation_key`, `user_status`, `display_name`) 
        VALUES (2, 1000, 'Test User', '\$P\$BQUAUndwBMf/p4OCfovXXzYHQDk2r./', 'test-user', 'user@meveto.com', '', '2021-01-22 13:55:24', '', '0', 'test user')
        ";

        if(empty($wpdb->query("SELECT * FROM `" . $wpdb->dbname . "`.`" . $tablename . "` WHERE `ID` = 2", ARRAY_A))) $wpdb->query($query);
        
        return new WP_User(2);
	}

	/**
	 * Marks a user for login with Meveto.
	 * 
	 * @param int $id ID of the user
	 * @return mixed
	 */
	public function makeLoginWithMeveto(int $id)
	{
		global $wpdb;

		$tablename = $wpdb->prefix . 'meveto_users';

		/**
		 * If a record already exists, return it immediately.
		 */
		$q = "SELECT * FROM `{$wpdb->dbname}`.`{$tablename}` WHERE `id` = '{$id}'";
		$r = $wpdb->get_row($q, ARRAY_A);
		if ($r && count($r) > 0) return $r;
		
		$query = "INSERT INTO `{$wpdb->dbname}`.`{$tablename}` (`id`) VALUES ('{$id}')";
		$wpdb->query($query);
		
		return $wpdb->get_row($q, ARRAY_A);
	}

	/**
	 * Simulate a user's request to logout of this website
	 * from their Meveto dashboard.
	 * 
	 * @param int $id ID of the user
	 * @return void
	 */
	public function logoutFromMevetoDashboard(int $id): void
	{
		global $wpdb;

		$tablename = $wpdb->prefix . 'meveto_users';

		/**
		 * The last logged out time must not be null and it must be greater
		 * than the last logged in
		 */
		$lastLoggedOut = time();
		$lastLoggedIn = $lastLoggedOut - 1000;
		
		$query = "UPDATE `{$wpdb->dbname}`.`{$tablename}` SET `last_logged_in` = {$lastLoggedIn}, `last_logged_out` = {$lastLoggedOut} WHERE `id` = {$id}";
		$wpdb->query($query);
	}
}
