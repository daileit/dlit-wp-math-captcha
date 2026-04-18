<?php
/**
 * WooCommerce product-review (comment) integration.
 *
 * Adds the math captcha to the WooCommerce review form and validates it
 * before the review is saved.
 *
 * @package Dlit_WP_Math_Captcha
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Dlit_Math_Captcha_WooCommerce
 */
class Dlit_Math_Captcha_WooCommerce {

	/**
	 * Constructor — register hooks.
	 */
	public function __construct() {
		// WooCommerce review form field.
		add_action( 'woocommerce_review_before_comment_form_fields', array( $this, 'render_captcha' ) );

		// Validate before the comment is inserted (shares the WordPress comments pipeline).
		add_filter( 'preprocess_comment', array( $this, 'validate_captcha' ) );

		add_action( 'wp_enqueue_scripts', array( 'Dlit_Math_Captcha', 'enqueue_styles' ) );
	}

	/**
	 * Render the captcha field inside the WooCommerce review form.
	 */
	public function render_captcha() {
		$settings = dlit_math_captcha_get_settings();
		$simple   = ! empty( $settings['simple_woo'] );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render() returns sanitised HTML.
		echo Dlit_Math_Captcha::render( 'dlit_captcha_answer_woo', $simple );
	}

	/**
	 * Validate the captcha before a WooCommerce review is saved.
	 *
	 * Only acts when the comment is submitted through the WooCommerce review
	 * form (identified by `comment_type === 'review'` or the `woo_captcha`
	 * guard in the POST data).
	 *
	 * @param array $comment_data Incoming comment data.
	 * @return array Unmodified comment data when validation passes.
	 */
	public function validate_captcha( $comment_data ) {
		// Only intercept product reviews.
		if ( empty( $_POST['rating'] ) && ! isset( $_POST['dlit_captcha_token'] ) ) {
			return $comment_data;
		}

		// Skip for moderators / editors.
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
