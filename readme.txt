=== Dlit WP Math Captcha ===
Contributors: daileit
Tags: captcha, math captcha, spam protection, comments, woocommerce, contact form 7
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.3.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A simple math captcha plugin for comments, login, registration, WooCommerce reviews, and Contact Form 7.

== Description ==

Dlit WP Math Captcha adds configurable math captcha protection to:

- WordPress comments
- Login form
- Registration form
- WooCommerce product reviews
- Contact Form 7 forms via [math_captcha]

Features:

- Answer-digit based difficulty (1-3 digits)
- Configurable operations: addition, subtraction, multiplication
- Per-integration simple one-line mode to minimize UI impact
- Single-use transient token verification
- Nonce verification for submissions

== Installation ==

1. Upload the plugin folder to /wp-content/plugins/.
2. Activate the plugin in WordPress.
3. Go to Settings > Math Captcha.

== Frequently Asked Questions ==

= How do I use it with Contact Form 7? =

Add this tag in your CF7 form:

[math_captcha]

Optional flags (no colons):

[math_captcha simple]       — force compact one-line layout
[math_captcha full]         — force full layout with description
[math_captcha id:my-id]     — set a custom HTML id on the input
[math_captcha id:my-id simple]  — combine
Tag syntax	Effect
[math_captcha]	uses Settings → Display Mode setting
[math_captcha class:simple]	forces simple one-line layout
[math_captcha class:full]	forces full layout with note
[math_captcha id:my-id]	custom HTML id on the input
[math_captcha id:my-id class:simple]	combined
== Changelog ==

= 1.1.0 =
- Added answer-digit based difficulty generation.
- Added per-integration simple one-line display mode.
- Improved comments and WooCommerce review validation flow.

== Upgrade Notice ==

= 1.1.0 =
Includes answer-digit difficulty and compact per-integration display controls.
