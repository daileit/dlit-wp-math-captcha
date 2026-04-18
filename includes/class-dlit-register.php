<?php
/**
 * WordPress Registration page integration.
 *
 * Adds the math captcha to the user registration form and validates it.
 *
 * @package Dlit_WP_Math_Captcha
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Dlit_Math_Captcha_Register
 */
class Dlit_Math_Captcha_Register {

	/**
	 * Constructor — register hooks.
	 */
	public function __construct() {
		add_action( 'register_form', array( $this, 'render_captcha' ) );
		add_filter( 'registration_errors', array( $this, 'validate_captcha' ), 10, 3 );
		add_action( 'login_enqueue_scripts', array( 'Dlit_Math_Captcha', 'enqueue_styles' ) );
	}

	/**
	 * Render the captcha field inside the registration form.
	 */
	public function render_captcha() {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render() returns sanitised HTML.
		echo Dlit_Math_Captcha::render( 'dlit_captcha_answer_register' );
	}

	/**
	 * Validate the captcha during user registration.
	 *
	 * Hooked to `registration_errors` — append a WP_Error on failure.
	 *
	 * @param WP_Error $errors               Registration errors collected so far.
	 * @param string   $sanitized_user_login Sanitised login name.
	 * @param string   $user_email           User e-mail.
	 * @return WP_Error Errors object (possibly with new captcha error appended).
	 */
	public function validate_captcha( $errors, $sanitized_user_login, $user_email ) {
		$result = Dlit_Math_Captcha::validate_from_post();

		if ( is_wp_error( $result ) ) {
			$errors->add(
				$result->get_error_code(),
				$result->get_error_message()
			);
		}

		return $errors;
	}
}
