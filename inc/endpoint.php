<?php
if (! defined('ABSPATH')) {
  exit;
}

/**
 * Rewrite "/vouchsafe/request" to "index.php?vouchsafe_request=1"
 */
function vouchsafe_add_rewrite_rules()
{
  add_rewrite_rule('^vouchsafe/request/?$', 'index.php?vouchsafe_request=1', 'top');
}
add_action('init', 'vouchsafe_add_rewrite_rules');

add_filter(
  'query_vars',
  function ($vars) {
    $vars[] = 'vouchsafe_request';
    return $vars;
  }
);

/**
 * Endpoint controller: /vouchsafe/request
 *
 * Accepts query params:
 *  - REQUIRED: email
 *  - OPTIONAL: first_name, last_name, street_address, postcode, date_of_birth,
 *              workflow_id, external_id, redirect_url, expires_at, fallback
 *
 * Behaviour:
 *  - On success: 302 -> Vouchsafe 'url'
 *  - On error: if 'fallback' provided, 302 -> fallback; else wp_die with message.
 *
 * Security posture:
 *  - This is a public GET endpoint used from links. Nonces are not required here.
 *  - We strictly allowlist keys and sanitize/validate each at the read point.
 */
add_action(
  'template_redirect',
  function () {
    if (! get_query_var('vouchsafe_request')) {
      return;
    }

    // Collect query params (public endpoint: use strict sanitization).
    $email    = isset($_GET['email']) ? sanitize_email(wp_unslash($_GET['email'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $fallback = isset($_GET['fallback']) ? esc_url_raw(wp_unslash($_GET['fallback'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    if (! $email || ! is_email($email)) {
      return vouchsafe_fail(__('Missing or invalid email.', 'vouchsafe-wp'), $fallback);
    }

    // Only accept these keys and sanitize each appropriately.
    $map = array(
      'first_name'     => 'first_name',
      'last_name'      => 'last_name',
      'street_address' => 'street_address',
      'postcode'       => 'postcode',
      'date_of_birth'  => 'date_of_birth',
      'workflow_id'    => 'workflow_id',
      'external_id'    => 'external_id',
      'redirect_url'   => 'redirect_url',
      'expires_at'     => 'expires_at',
    );

    $payload = array('email' => $email);

    foreach ($map as $query_key => $body_key) {
      if (isset($_GET[$query_key])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $raw = wp_unslash($_GET[$query_key]); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
        switch ($query_key) {
          case 'redirect_url':
            $val = esc_url_raw($raw);
            break;
          default:
            $val = sanitize_text_field($raw);
            break;
        }
        if ('' !== $val) {
          $payload[$body_key] = $val;
        }
      }
    }

    // Request verification.
    $result = vouchsafe_request_verification($payload);
    if (is_wp_error($result)) {
      return vouchsafe_fail(__('Sorry, we could not start your verification.', 'vouchsafe-wp'), $fallback);
    }

    // Redirect to returned URL.
    $target = isset($result['url']) ? esc_url_raw($result['url']) : '';
    if (empty($target)) {
      return vouchsafe_fail(__('Sorry, we could not start your verification.', 'vouchsafe-wp'), $fallback);
    }

    wp_redirect($target, 302);
    exit;
  }
);

/**
 * Fail helper for the endpoint.
 *
 * @param string $message  Error message for wp_die.
 * @param string $fallback Optional fallback URL to redirect to.
 */
function vouchsafe_fail($message, $fallback = '')
{
  if ($fallback) {
    wp_safe_redirect($fallback, 302);
    exit;
  }
  wp_die(esc_html($message), esc_html__('Verification Error', 'vouchsafe-wp'), array('response' => 500));
}
