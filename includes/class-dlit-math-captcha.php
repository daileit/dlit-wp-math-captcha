<?php
/**
 * Core math captcha generator.
 *
 * Generates a math question, stores the expected answer in a WordPress
 * transient keyed by a unique token, and renders the captcha HTML field.
 *
 * @package Dlit_WP_Math_Captcha
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Dlit_Math_Captcha
 */
class Dlit_Math_Captcha {

	/**
	 * Supported math operations.
	 *
	 * @var string[]
	 */
	const OPERATIONS = array( 'addition', 'subtraction', 'multiplication' );

	/**
	 * Transient TTL in seconds (30 minutes).
	 *
	 * @var int
	 */
	const TRANSIENT_TTL = 1800;

	/**
	 * Nonce action used when rendering and verifying the captcha field.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'dlit_math_captcha_nonce';

	/**
	 * Return min/max bounds for an answer with the requested number of digits.
	 *
	 * @param int $num_digits Number of digits.
	 * @return array{min:int,max:int}
	 */
	private static function get_answer_bounds( $num_digits ) {
		$min = ( 1 === $num_digits ) ? 1 : intval( pow( 10, $num_digits - 1 ) );
		$max = intval( pow( 10, $num_digits ) ) - 1;

		return array(
			'min' => $min,
			'max' => $max,
		);
	}

	/**
	 * Generate a new captcha question and return its data.
	 *
	 * @return array {
	 *     @type string $token    Unique token stored as a transient key.
	 *     @type string $question Human-readable math question string.
	 * }
	 */
	public static function generate() {
		$settings   = dlit_math_captcha_get_settings();
		$num_digits = max( 1, intval( $settings['num_digits'] ) );

		$operations = ! empty( $settings['operations'] ) ? (array) $settings['operations'] : array( 'addition' );
		// Filter to valid operations.
		$operations = array_values(
			array_intersect( $operations, self::OPERATIONS )
		);
		if ( empty( $operations ) ) {
			$operations = array( 'addition' );
		}

		$operation = $operations[ array_rand( $operations ) ];

		$bounds      = self::get_answer_bounds( $num_digits );
		$answer_min  = $bounds['min'];
		$answer_max  = $bounds['max'];
		$operand_max = intval( pow( 10, $num_digits + 1 ) ) - 1;

		$a = 0;
		$b = 0;
		$answer = 0;
		$question = '';

		switch ( $operation ) {
			case 'subtraction':
				$answer = wp_rand( $answer_min, $answer_max );
				$max_b  = max( 1, $operand_max - $answer );
				$b      = wp_rand( 1, $max_b );
				$a      = $answer + $b;
				$question = sprintf( '%d &minus; %d', $a, $b );
				break;

			case 'multiplication':
				for ( $i = 0; $i < 120; $i++ ) {
					$a = wp_rand( 2, min( 99, $operand_max ) );
					$b = wp_rand( 2, min( 99, $operand_max ) );
					$product = $a * $b;

					if ( $product >= $answer_min && $product <= $answer_max ) {
						$answer = $product;
						break;
					}
				}

				if ( 0 === $answer ) {
					$answer = wp_rand( $answer_min, $answer_max );
					$divisors = array();
					$limit    = (int) floor( sqrt( $answer ) );
					for ( $d = 2; $d <= $limit; $d++ ) {
						if ( 0 === $answer % $d ) {
							$other = (int) ( $answer / $d );
							if ( $d <= $operand_max && $other <= $operand_max ) {
								$divisors[] = array( $d, $other );
							}
						}
					}

					if ( ! empty( $divisors ) ) {
						$pair = $divisors[ array_rand( $divisors ) ];
						$a    = $pair[0];
						$b    = $pair[1];
					} else {
						$a = 1;
						$b = $answer;
					}
				}

				$question = sprintf( '%d &times; %d', $a, $b );
				break;

			case 'addition':
			default:
				$answer = wp_rand( $answer_min, $answer_max );
				if ( $answer <= 1 ) {
					$a = 1;
					$b = 0;
				} else {
					$a = wp_rand( 1, $answer - 1 );
					$b = $answer - $a;
				}
				$question = sprintf( '%d + %d', $a, $b );
				break;
		}

		$token = wp_generate_uuid4();
		set_transient( 'dlit_captcha_' . $token, $answer, self::TRANSIENT_TTL );

		return array(
			'token'    => $token,
			'question' => $question,
		);
	}

	/**
	 * Verify the user answer for a given token.
	 *
	 * The transient is deleted after a single verification attempt to prevent
	 * replay attacks.
	 *
	 * @param string     $token          The captcha token.
	 * @param string|int $user_answer    The answer provided by the user.
	 * @return bool True when the answer is correct, false otherwise.
	 */
	public static function verify( $token, $user_answer ) {
		$token = sanitize_text_field( $token );

		if ( empty( $token ) ) {
			return false;
		}

		$transient_key    = 'dlit_captcha_' . $token;
		$expected_answer  = get_transient( $transient_key );

		// Always delete the transient to prevent replay.
		delete_transient( $transient_key );

		if ( false === $expected_answer ) {
			return false;
		}

		return intval( $user_answer ) === intval( $expected_answer );
	}

	/**
	 * Render the captcha HTML (question + hidden token + answer input).
	 *
	 * @param string $field_id      Optional CSS id for the answer input. Defaults to 'dlit_captcha_answer'.
	 * @param bool   $simple_layout Whether to render a compact one-line layout.
	 * @return string HTML markup.
	 */
	public static function render( $field_id = 'dlit_captcha_answer', $simple_layout = true ) {
		$data  = self::generate();
		$nonce = wp_create_nonce( self::NONCE_ACTION );

		$html  = '<div class="dlit-math-captcha-wrap' . ( $simple_layout ? ' dlit-math-captcha-simple' : '' ) . '">';

		if ( ! $simple_layout ) {
			$label = esc_html__( 'Math Captcha', 'dlit-wp-math-captcha' );
			$note  = esc_html__( 'Please solve this math problem to prove you are human:', 'dlit-wp-math-captcha' );

			$html .= '<label for="' . esc_attr( $field_id ) . '">';
			$html .= '<strong>' . $label . '</strong><br>';
			$html .= '<span class="dlit-captcha-note">' . $note . '</span>';
			$html .= '</label>';
		}

		$html .= '<div class="dlit-captcha-question" aria-label="' . esc_attr__( 'Math question', 'dlit-wp-math-captcha' ) . '">';
		$html .= wp_kses( $data['question'] . ' = ?', array() );
		$html .= '</div>';
		$html .= '<input type="number" id="' . esc_attr( $field_id ) . '" name="dlit_captcha_answer" class="dlit-captcha-input" required autocomplete="off" aria-label="' . esc_attr__( 'Math captcha answer', 'dlit-wp-math-captcha' ) . '">';
		$html .= '<input type="hidden" name="dlit_captcha_token" value="' . esc_attr( $data['token'] ) . '">';
		$html .= wp_nonce_field( self::NONCE_ACTION, 'dlit_captcha_nonce_field', true, false );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Validate the captcha from the current POST request.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public static function validate_from_post() {
		// Verify nonce.
		$nonce = isset( $_POST['dlit_captcha_nonce_field'] ) ? sanitize_text_field( wp_unslash( $_POST['dlit_captcha_nonce_field'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return new WP_Error(
				'dlit_captcha_invalid_nonce',
				__( 'Security check failed. Please reload the page and try again.', 'dlit-wp-math-captcha' )
			);
		}

		$token  = isset( $_POST['dlit_captcha_token'] ) ? sanitize_text_field( wp_unslash( $_POST['dlit_captcha_token'] ) ) : '';
		$answer = isset( $_POST['dlit_captcha_answer'] ) ? sanitize_text_field( wp_unslash( $_POST['dlit_captcha_answer'] ) ) : '';

		if ( '' === $answer ) {
			return new WP_Error(
				'dlit_captcha_empty',
				__( 'Please answer the math captcha.', 'dlit-wp-math-captcha' )
			);
		}

		if ( ! self::verify( $token, $answer ) ) {
			return new WP_Error(
				'dlit_captcha_wrong',
				__( 'Incorrect captcha answer. Please try again.', 'dlit-wp-math-captcha' )
			);
		}

		return true;
	}

	/**
	 * Enqueue the frontend stylesheet.
	 */
	public static function enqueue_styles() {
		wp_enqueue_style(
			'dlit-math-captcha',
			DLIT_MATH_CAPTCHA_PLUGIN_URL . 'assets/css/dlit-math-captcha.css',
			array(),
			DLIT_MATH_CAPTCHA_VERSION
		);
	}
}
