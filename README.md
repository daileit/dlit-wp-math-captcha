# Dlit WP Math Captcha

A simple WordPress plugin that applies a math captcha on comments, product reviews, login page, sign-up page, and Contact Form 7 forms.  
The difficulty is fully configurable: choose the number of digits in the answer (1–3) and the allowed math operations (addition, subtraction, multiplication).

---

## Features

| Location | Hook / Action |
|---|---|
| WordPress Comments | `comment_form_after_fields` / `preprocess_comment` |
| WooCommerce Product Reviews | `woocommerce_review_before_comment_form_fields` |
| Login Page | `login_form` / `authenticate` |
| Registration Page | `register_form` / `registration_errors` |
| Contact Form 7 | `[math_captcha]` form-tag |

- **Configurable difficulty** — 1 to 3 digits per operand; enable any combination of addition, subtraction, and multiplication.
- **Configurable difficulty** — 1 to 3 digits in the answer; operands may use one extra digit to keep challenges natural (for example, `43 - 37 = ?` for 1-digit answers).
- **Simple one-line mode per integration** — compact captcha layout enabled by default for each integration to save space and avoid UI disruption.
- **Secure token system** — answers are stored as WordPress transients (server-side), not in cookies or hidden fields.  Each token is single-use to prevent replay attacks.
- **Nonce protection** — every form submission is verified with a WordPress nonce.
- **Logged-in moderators bypass** — users with `moderate_comments` capability skip the captcha on comment/review forms.
- **Programmatic login bypass** — the login captcha only triggers on explicit `wp-submit` form submissions.

---

## Installation

1. Zip the folder with `zip -r dlit-wp-math-captcha.zip . -x "*/.*" ".git*"`
2. Upload and unzip the `dlit-wp-math-captcha.zip` to `/wp-content/plugins/`. You can also use the plugin page to upload.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Go to **Settings → Math Captcha** to configure which forms are protected and the difficulty.

---

## Configuration

Navigate to **Settings → Math Captcha**.

### Integrations

Enable or disable the captcha for each supported location:

- WordPress Comments
- Login Page
- Registration Page
- WooCommerce Product Reviews *(requires WooCommerce)*
- Contact Form 7 *(requires CF7)*

### Difficulty

| Setting | Description |
|---|---|
| **Number of digits in answer** | Controls answer size (1 = 1–9, 2 = 10–99, 3 = 100–999). Operands may use one extra digit. |
| **Allowed operations** | Check at least one of: Addition (+), Subtraction (−), Multiplication (×). |

### Display Mode

Enable or disable **Simple one-line layout** separately for each integration:

- Comments
- Login
- Registration
- WooCommerce Reviews
- Contact Form 7

Simple layout is enabled by default and renders just one compact line for the captcha question and answer input.

### Contact Form 7 Usage

Add the `[math_captcha]` tag anywhere inside your CF7 form template:

```
[math_captcha]
```

---

## Requirements

- WordPress 5.0+
- PHP 7.4+
- WooCommerce (optional, for product-review captcha)
- Contact Form 7 (optional, for CF7 captcha)

---

## License

GPL-2.0+. See [LICENSE](LICENSE).

