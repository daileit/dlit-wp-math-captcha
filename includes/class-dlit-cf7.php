<?php
/**
 * Contact Form 7 (CF7) integration.
 *
 * Registers a custom CF7 form-tag `[math_captcha]` that renders the math
 * captcha widget and validates the answer on submission.
 *
 * Usage in CF7 form editor:
 *   [math_captcha]
 *
 * @package Dlit_WP_Math_Captcha
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Dlit_Math_Captcha_CF7
 */
class Dlit_Math_Captcha_CF7 {

	/**
	 * Constructor — register hooks.
	 */
	public function __construct() {
		// Register the custom form-tag.
		add_action( 'wpcf7_init', array( $this, 'register_form_tag' ) );

		// Validate on CF7 submission.
		add_filter( 'wpcf7_validate', array( $this, 'validate_captcha' ), 10, 2 );

		add_action( 'wp_enqueue_scripts', array( 'Dlit_Math_Captcha', 'enqueue_styles' ) );
	}

	/**
	 * Register the `[math_captcha]` form-tag with CF7.
	 */
	public function register_form_tag() {
		if ( ! function_exists( 'wpcf7_add_form_tag' ) ) {
			return;
		}

		wpcf7_add_form_tag(
			array( 'math_captcha', 'math_captcha*' ),
			array( $this, 'render_form_tag' ),
			array( 'name-attr' => true )
		);
	}

	/**
	 * Render the `[math_captcha]` form-tag HTML.
	 *
	 * @param WPCF7_FormTag $tag Current form-tag object.
	 * @return string HTML markup for the captcha widget.
	 */
	public function render_form_tag( $tag ) {
		$field_id = 'dlit_captcha_answer_cf7';

		if ( ! empty( $tag->get_id_option() ) ) {
			$field_id = esc_attr( $tag->get_id_option() );
		}

		return Dlit_Math_Captcha::render( $field_id );
	}

	/**
	 * Validate the math captcha answer on CF7 form submission.
	 *
	 * @param WPCF7_Validation $result   Current validation result object.
	 * @param WPCF7_FormTag[]  $tags     Array of form-tags in the form.
	 * @return WPCF7_Validation Updated validation result.
	 */
	public function validate_captcha( $result, $tags ) {
		// Only run if the form contains a math_captcha tag.
		$captcha_tag = null;
		foreach ( (array) $tags as $tag ) {
			if ( in_array( $tag->type, array( 'math_captcha', 'math_captcha*' ), true ) ) {
				$captcha_tag = $tag;
				break;
			}
		}

		if ( null === $captcha_tag ) {
			return $result;
		}

		$validation = Dlit_Math_Captcha::validate_from_post();

		if ( is_wp_error( $validation ) ) {
			$result->invalidate(
				$captcha_tag,
				$validation->get_error_message()
			);
		}

		return $result;
	}
}
