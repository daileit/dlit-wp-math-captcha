<?php
/**
 * Contact Form 7 (CF7) integration.
 *
 * Registers a custom CF7 form-tag `[math_captcha]` that renders the math
 * captcha widget and validates the answer on submission.
 *
 * Usage in CF7 form editor:
 *   [math_captcha]                        — uses settings defaults
 *   [math_captcha id:my-id]               — custom HTML id on the input
 *   [math_captcha class:simple]           — force simple one-line layout
 *   [math_captcha class:full]             — force full layout
 *   [math_captcha id:my-id class:simple]  — combine options
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

		add_action( 'wp_enqueue_scripts', array( 'Dlit_Math_Captcha', 'enqueue_styles' ) );
	}

	/**
	 * Register the `[math_captcha]` form-tag with CF7 and attach per-tag
	 * validation filters, which is the correct approach in CF7 5.x+.
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

		// Per-tag validation filters — one per type variant (required/optional).
		add_filter( 'wpcf7_validate_math_captcha',  array( $this, 'validate_captcha' ), 10, 2 );
		add_filter( 'wpcf7_validate_math_captcha*', array( $this, 'validate_captcha' ), 10, 2 );
	}

	/**
	 * Resolve layout mode from tag options, falling back to plugin settings.
	 *
	 * Supports `class:simple` and `class:full` options on the tag.
	 *
	 * @param WPCF7_FormTag $tag CF7 form-tag object.
	 * @return bool True for simple/compact layout, false for full layout.
	 */
	private function resolve_simple_layout( $tag ) {
		$classes = $tag->get_class_option();

		if ( false !== strpos( $classes, 'simple' ) ) {
			return true;
		}
		if ( false !== strpos( $classes, 'full' ) ) {
			return false;
		}

		// Fall back to plugin admin setting.
		$settings = dlit_math_captcha_get_settings();
		return ! empty( $settings['simple_cf7'] );
	}

	/**
	 * Render the `[math_captcha]` form-tag HTML.
	 *
	 * @param WPCF7_FormTag $tag Current form-tag object.
	 * @return string HTML markup for the captcha widget.
	 */
	public function render_form_tag( $tag ) {
		$field_id = 'dlit_captcha_answer_cf7';

		$id_option = $tag->get_id_option();
		if ( ! empty( $id_option ) ) {
			$field_id = sanitize_html_class( $id_option );
		}

		$simple = $this->resolve_simple_layout( $tag );

		return Dlit_Math_Captcha::render( $field_id, $simple );
	}

	/**
	 * Validate the math captcha answer for the specific [math_captcha] tag.
	 *
	 * Hooked via `wpcf7_validate_math_captcha` and `wpcf7_validate_math_captcha*`
	 * which are the correct per-tag-type validation filters in CF7 5.x+.
	 *
	 * @param WPCF7_Validation $result Current validation result.
	 * @param WPCF7_FormTag    $tag    The math_captcha form-tag being validated.
	 * @return WPCF7_Validation Updated validation result.
	 */
	public function validate_captcha( $result, $tag ) {
		$validation = Dlit_Math_Captcha::validate_from_post();

		if ( is_wp_error( $validation ) ) {
			$result->invalidate( $tag, $validation->get_error_message() );
		}

		return $result;
	}
}
