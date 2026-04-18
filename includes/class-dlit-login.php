<?php
/**
 * WordPress Login page integration.
 *
 * Adds the math captcha to the login form and validates it on submission.
 *
 * @package Dlit_WP_Math_Captcha
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Dlit_Math_Captcha_Login
 */
class Dlit_Math_Captcha_Login {

	/**
	 * Constructor — register hooks.
	 */
	public function __construct() {
		add_action( 'login_form', array( $this, 'render_captcha' ) );
		add_filter( 'authenticate', array( $this, 'validate_captcha' ), 30, 3 );
		add_action( 'login_enqueue_scripts', array( 'Dlit_Math_Captcha', 'enqueue_styles' ) );
	}

	/**
	 * Render the captcha field inside the login form.
	 */
	public function render_captcha() {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render() returns sanitised HTML.
		echo Dlit_Math_Captcha::render( 'dlit_captcha_answer_login' );
	}

	/**
	 * Validate the captcha during authentication.
	 *
	 * This filter is called only when a form submission occurs (i.e. when
	 * $_POST['wp-submit'] is set), so programmatic logins are unaffected.
	 *
	 * @param WP_User|WP_Error|null $user     User object or error from a previous filter.
	 * @param string                $username Username.
	 * @param string                $password Password.
	 * @return WP_User|WP_Error
	 */
	public function validate_captcha( $user, $username, $password ) {
		// Only validate on explicit login form submission.
		if ( empty( $_POST['wp-submit'] ) ) {
			return $user;
		}

		// If a previous filter already produced an error, let WordPress handle it.
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		$result = Dlit_Math_Captcha::validate_from_post();

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $user;
	}
}
