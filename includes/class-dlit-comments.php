<?php
/**
 * WordPress Comments integration.
 *
 * Adds the math captcha to the comment form and validates it on submission.
 *
 * @package Dlit_WP_Math_Captcha
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Dlit_Math_Captcha_Comments
 */
class Dlit_Math_Captcha_Comments {

	/**
	 * Constructor — register hooks.
	 */
	public function __construct() {
		add_action( 'comment_form_after_fields', array( $this, 'render_captcha' ) );
		add_action( 'comment_form_logged_in_after', array( $this, 'render_captcha' ) );
		add_filter( 'preprocess_comment', array( $this, 'validate_captcha' ) );
		add_action( 'wp_enqueue_scripts', array( 'Dlit_Math_Captcha', 'enqueue_styles' ) );
	}

	/**
	 * Render the captcha field inside the comment form.
	 */
	public function render_captcha() {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render() returns sanitised HTML.
		echo Dlit_Math_Captcha::render( 'dlit_captcha_answer_comment' );
	}

	/**
	 * Validate the captcha before the comment is processed.
	 *
	 * Hooked to `preprocess_comment` which expects the comment data array to be
	 * returned on success and triggers wp_die() on failure.
	 *
	 * @param array $comment_data Incoming comment data.
	 * @return array Unmodified comment data when validation passes.
	 */
	public function validate_captcha( $comment_data ) {
		// Skip validation for logged-in users who have already approved comments.
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			if ( user_can( $user, 'moderate_comments' ) ) {
				return $comment_data;
			}
		}

		$result = Dlit_Math_Captcha::validate_from_post();

		if ( is_wp_error( $result ) ) {
			wp_die(
				esc_html( $result->get_error_message() ),
				esc_html__( 'Math Captcha Error', 'dlit-wp-math-captcha' ),
				array(
					'response'  => 400,
					'back_link' => true,
				)
			);
		}

		return $comment_data;
	}
}
