<?php

if (!defined('ABSPATH')) exit;

/**
 * Rewrite "/vouchsafe/request" to "index.php?vouchsafe_request=1"
 */
function vouchsafe_add_rewrite_rules()
{
  add_rewrite_rule('^vouchsafe/request/?$', 'index.php?vouchsafe_request=1', 'top');
}
add_action('init', 'vouchsafe_add_rewrite_rules');

add_filter('query_vars', function ($vars) {
  $vars[] = 'vouchsafe_request';
  return $vars;
});

/**
 * Endpoint controller: /vouchsafe/request
 * Accepts query params:
 *  - REQUIRED: email
 *  - OPTIONAL: first_name, last_name, street_address, postcode, date_of_birth,
 *              workflow_id, external_id, redirect_url, expires_at, fallback
 * On success: 302 -> Vouchsafe 'url'
 * On error: if 'fallback' provided, 302 -> fallback; else wp_die with message.
 */
add_action('template_redirect', function () {
  if (!get_query_var('vouchsafe_request')) return;

  // Collect query params
  $email = isset($_GET['email']) ? sanitize_email(wp_unslash($_GET['email'])) : '';
  $fallback = isset($_GET['fallback']) ? esc_url_raw(wp_unslash($_GET['fallback'])) : '';

  if (!$email || !is_email($email)) {
    return vouchsafe_fail('Missing or invalid email.', $fallback);
  }

  // Build a payload
  $map = [
    'first_name'     => 'first_name',
    'last_name'      => 'last_name',
    'street_address' => 'street_address',
    'postcode'       => 'postcode',
    'date_of_birth'  => 'date_of_birth',
    'workflow_id'    => 'workflow_id',
    'external_id'    => 'external_id',
    'redirect_url'   => 'redirect_url',
    'expires_at'     => 'expires_at',
  ];

  $payload = ['email' => $email];
  foreach ($map as $queryKey => $bodyKey) {
    if (isset($_GET[$queryKey])) {
      // Very light sanitisation: these are identifiers/claims, not HTML
      $val = trim(wp_unslash($_GET[$queryKey]));
      if ($val !== '') $payload[$bodyKey] = $val;
    }
  }

  // Request verification
  $result = vouchsafe_request_verification($payload);
  if (is_wp_error($result)) {
    // Log a minimal error
    $msg = $result->get_error_message();
    error_log('[Vouchsafe] Verification request failed: ' . $msg);
    return vouchsafe_fail('Sorry, we could not start your verification.', $fallback);
  }

  // Redirect to returned URL
  $target = $result['url'];
  wp_redirect($target, 302);
  exit;
});

function vouchsafe_fail($message, $fallback = '')
{
  if ($fallback) {
    wp_safe_redirect($fallback, 302);
    exit;
  }
  wp_die(esc_html($message), 'Verification Error', ['response' => 500]);
}
