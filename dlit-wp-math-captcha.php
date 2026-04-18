<?php
/**
 * Plugin Name: Dlit WP Math Captcha
 * Plugin URI:  https://github.com/daileit/dlit-wp-math-captcha
 * Description: A simple WordPress plugin that applies math captcha on comments, product reviews, login page, sign up page, and CF7 forms. Configurable difficulty (number of digits, operation type).
 * Version:     1.2.0
 * Author:      Daileit
 * Author URI:  https://github.com/daileit
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: dlit-wp-math-captcha
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DLIT_MATH_CAPTCHA_VERSION', '1.0.0' );
define( 'DLIT_MATH_CAPTCHA_PLUGIN_FILE', __FILE__ );
define( 'DLIT_MATH_CAPTCHA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DLIT_MATH_CAPTCHA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once DLIT_MATH_CAPTCHA_PLUGIN_DIR . 'includes/class-dlit-math-captcha.php';
require_once DLIT_MATH_CAPTCHA_PLUGIN_DIR . 'includes/class-dlit-comments.php';
require_once DLIT_MATH_CAPTCHA_PLUGIN_DIR . 'includes/class-dlit-login.php';
require_once DLIT_MATH_CAPTCHA_PLUGIN_DIR . 'includes/class-dlit-register.php';
require_once DLIT_MATH_CAPTCHA_PLUGIN_DIR . 'includes/class-dlit-woocommerce.php';
require_once DLIT_MATH_CAPTCHA_PLUGIN_DIR . 'includes/class-dlit-cf7.php';

if ( is_admin() ) {
	require_once DLIT_MATH_CAPTCHA_PLUGIN_DIR . 'admin/class-dlit-admin.php';
	new Dlit_Math_Captcha_Admin();
}

/**
 * Returns the plugin default settings.
 *
 * @return array
 */
function dlit_math_captcha_defaults() {
	return array(
		'enable_comments'  => 1,
		'enable_login'     => 0,
		'enable_register'  => 0,
		'enable_woo'       => 0,
		'enable_cf7'       => 0,
		'simple_comments'  => 1,
		'simple_login'     => 1,
		'simple_register'  => 1,
		'simple_woo'       => 1,
		'simple_cf7'       => 1,
		'num_digits'       => 1,
		'operations'       => array( 'addition' ),
	);
}

/**
 * Returns the merged plugin settings.
 *
 * @return array
 */
function dlit_math_captcha_get_settings() {
	$saved    = get_option( 'dlit_math_captcha_settings', array() );
	$defaults = dlit_math_captcha_defaults();
	return wp_parse_args( $saved, $defaults );
}

// Initialise integrations after all plugins have loaded.
add_action( 'plugins_loaded', 'dlit_math_captcha_init' );

/**
 * Instantiate active integrations.
 */
function dlit_math_captcha_init() {
	$settings = dlit_math_captcha_get_settings();

	if ( ! empty( $settings['enable_comments'] ) ) {
		new Dlit_Math_Captcha_Comments();
	}

	if ( ! empty( $settings['enable_login'] ) ) {
		new Dlit_Math_Captcha_Login();
	}

	if ( ! empty( $settings['enable_register'] ) ) {
		new Dlit_Math_Captcha_Register();
	}

	if ( ! empty( $settings['enable_woo'] ) && class_exists( 'WooCommerce' ) ) {
		new Dlit_Math_Captcha_WooCommerce();
	}

	if ( ! empty( $settings['enable_cf7'] ) && class_exists( 'WPCF7' ) ) {
		new Dlit_Math_Captcha_CF7();
	}
}

register_activation_hook( __FILE__, 'dlit_math_captcha_activate' );

/**
 * Plugin activation: set default options.
 */
function dlit_math_captcha_activate() {
	if ( false === get_option( 'dlit_math_captcha_settings' ) ) {
		add_option( 'dlit_math_captcha_settings', dlit_math_captcha_defaults() );
	}
}

register_deactivation_hook( __FILE__, 'dlit_math_captcha_deactivate' );

/**
 * Plugin deactivation hook (reserved for future cleanup).
 */
function dlit_math_captcha_deactivate() {}
