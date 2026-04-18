<?php
/**
 * Admin settings page.
 *
 * Registers the plugin settings under Settings > Math Captcha and provides
 * the settings form for configuring which integrations are active and the
 * captcha difficulty.
 *
 * @package Dlit_WP_Math_Captcha
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Dlit_Math_Captcha_Admin
 */
class Dlit_Math_Captcha_Admin {

	/**
	 * Settings option key.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'dlit_math_captcha_settings';

	/**
	 * Constructor — register hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add the settings page under the Settings menu.
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'Math Captcha Settings', 'dlit-wp-math-captcha' ),
			__( 'Math Captcha', 'dlit-wp-math-captcha' ),
			'manage_options',
			'dlit-math-captcha',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register the settings, sections, and fields.
	 */
	public function register_settings() {
		register_setting(
			'dlit_math_captcha_group',
			self::OPTION_KEY,
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		// ── Integration section ───────────────────────────────────────────
		add_settings_section(
			'dlit_integrations',
			__( 'Integrations', 'dlit-wp-math-captcha' ),
			array( $this, 'render_integrations_section' ),
			'dlit-math-captcha'
		);

		$integrations = array(
			'enable_comments' => __( 'WordPress Comments', 'dlit-wp-math-captcha' ),
			'enable_login'    => __( 'Login Page', 'dlit-wp-math-captcha' ),
			'enable_register' => __( 'Registration Page', 'dlit-wp-math-captcha' ),
			'enable_woo'      => __( 'WooCommerce Product Reviews (requires WooCommerce)', 'dlit-wp-math-captcha' ),
			'enable_cf7'      => __( 'Contact Form 7 (requires CF7)', 'dlit-wp-math-captcha' ),
		);

		foreach ( $integrations as $key => $label ) {
			add_settings_field(
				$key,
				$label,
				array( $this, 'render_checkbox_field' ),
				'dlit-math-captcha',
				'dlit_integrations',
				array( 'key' => $key )
			);
		}

		// ── Difficulty section ────────────────────────────────────────────
		add_settings_section(
			'dlit_difficulty',
			__( 'Difficulty', 'dlit-wp-math-captcha' ),
			array( $this, 'render_difficulty_section' ),
			'dlit-math-captcha'
		);

		add_settings_field(
			'num_digits',
			__( 'Number of digits per operand', 'dlit-wp-math-captcha' ),
			array( $this, 'render_num_digits_field' ),
			'dlit-math-captcha',
			'dlit_difficulty'
		);

		add_settings_field(
			'operations',
			__( 'Allowed operations', 'dlit-wp-math-captcha' ),
			array( $this, 'render_operations_field' ),
			'dlit-math-captcha',
			'dlit_difficulty'
		);
	}

	// ── Section callbacks ─────────────────────────────────────────────────

	/**
	 * Render the integrations section description.
	 */
	public function render_integrations_section() {
		echo '<p>' . esc_html__( 'Choose where the math captcha should appear.', 'dlit-wp-math-captcha' ) . '</p>';
	}

	/**
	 * Render the difficulty section description.
	 */
	public function render_difficulty_section() {
		echo '<p>' . esc_html__( 'Configure the difficulty of the generated math questions.', 'dlit-wp-math-captcha' ) . '</p>';
	}

	// ── Field callbacks ───────────────────────────────────────────────────

	/**
	 * Render a checkbox field for a given integration key.
	 *
	 * @param array $args Field arguments including 'key'.
	 */
	public function render_checkbox_field( $args ) {
		$settings = dlit_math_captcha_get_settings();
		$key      = $args['key'];
		$checked  = ! empty( $settings[ $key ] );
		printf(
			'<input type="checkbox" name="%s[%s]" value="1" %s>',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $key ),
			checked( $checked, true, false )
		);
	}

	/**
	 * Render the number-of-digits select field.
	 */
	public function render_num_digits_field() {
		$settings   = dlit_math_captcha_get_settings();
		$num_digits = intval( $settings['num_digits'] );
		echo '<select name="' . esc_attr( self::OPTION_KEY ) . '[num_digits]">';
		for ( $i = 1; $i <= 3; $i++ ) {
			printf(
				'<option value="%d" %s>%d</option>',
				$i,
				selected( $num_digits, $i, false ),
				$i
			);
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Each operand will be a number with this many digits (1–3).', 'dlit-wp-math-captcha' ) . '</p>';
	}

	/**
	 * Render the allowed-operations checkbox group.
	 */
	public function render_operations_field() {
		$settings   = dlit_math_captcha_get_settings();
		$selected   = (array) $settings['operations'];

		$all_ops = array(
			'addition'       => __( 'Addition (+)', 'dlit-wp-math-captcha' ),
			'subtraction'    => __( 'Subtraction (−)', 'dlit-wp-math-captcha' ),
			'multiplication' => __( 'Multiplication (×)', 'dlit-wp-math-captcha' ),
		);

		foreach ( $all_ops as $op => $label ) {
			printf(
				'<label><input type="checkbox" name="%s[operations][]" value="%s" %s> %s</label><br>',
				esc_attr( self::OPTION_KEY ),
				esc_attr( $op ),
				checked( in_array( $op, $selected, true ), true, false ),
				esc_html( $label )
			);
		}
		echo '<p class="description">' . esc_html__( 'At least one operation must be selected.', 'dlit-wp-math-captcha' ) . '</p>';
	}

	// ── Settings sanitisation ─────────────────────────────────────────────

	/**
	 * Sanitise and validate the incoming settings array.
	 *
	 * @param array $input Raw input from the settings form.
	 * @return array Sanitised settings.
	 */
	public function sanitize_settings( $input ) {
		$clean = array();

		$boolean_keys = array( 'enable_comments', 'enable_login', 'enable_register', 'enable_woo', 'enable_cf7' );
		foreach ( $boolean_keys as $key ) {
			$clean[ $key ] = ! empty( $input[ $key ] ) ? 1 : 0;
		}

		$num_digits = isset( $input['num_digits'] ) ? intval( $input['num_digits'] ) : 1;
		$clean['num_digits'] = max( 1, min( 3, $num_digits ) );

		$valid_ops = Dlit_Math_Captcha::OPERATIONS;
		$ops       = isset( $input['operations'] ) ? (array) $input['operations'] : array();
		$ops       = array_values( array_intersect( $ops, $valid_ops ) );

		// Ensure at least addition is always enabled.
		if ( empty( $ops ) ) {
			$ops = array( 'addition' );
			add_settings_error(
				self::OPTION_KEY,
				'dlit_ops_required',
				__( 'At least one operation must be selected. Addition has been selected by default.', 'dlit-wp-math-captcha' )
			);
		}

		$clean['operations'] = $ops;

		return $clean;
	}

	// ── Settings page renderer ────────────────────────────────────────────

	/**
	 * Render the settings page HTML.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Math Captcha Settings', 'dlit-wp-math-captcha' ); ?></h1>
			<?php settings_errors( self::OPTION_KEY ); ?>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'dlit_math_captcha_group' );
				do_settings_sections( 'dlit-math-captcha' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
