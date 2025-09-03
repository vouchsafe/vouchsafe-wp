<?php

if (!defined('ABSPATH')) exit;

/**
 * Helpers: credentials + token caching
 */
function vouchsafe_get_credentials()
{
  $opt = get_option(VOUCHSAFE_OPT, []);
  $id = $opt['client_id'] ?? '';
  $secret = $opt['client_secret'] ?? '';
  return [$id, $secret];
}

function vouchsafe_get_bearer_token()
{
  // Cache token for the lifetime provided by API (expires_at). We’ll buffer by 5 minutes.
  $cached = get_transient(VOUCHSAFE_TOKEN_TRANSIENT);
  if (is_array($cached) && !empty($cached['token']) && time() < ($cached['exp_ts'] ?? 0)) {
    return $cached['token'];
  }

  list($client_id, $client_secret) = vouchsafe_get_credentials();
  if (!$client_id || !$client_secret) {
    return new WP_Error('vouchsafe_missing_creds', 'Vouchsafe Client ID/Secret are not configured.');
  }

  $resp = wp_remote_post(VOUCHSAFE_API_BASE . '/authenticate', [
    'headers' => ['Content-Type' => 'application/json'],
    'body'    => wp_json_encode(['client_id' => $client_id, 'client_secret' => $client_secret]),
    'timeout' => 15,
  ]);
  if (is_wp_error($resp)) return $resp;

  $code = wp_remote_retrieve_response_code($resp);
  $body = wp_remote_retrieve_body($resp);
  if ($code !== 201) {
    return new WP_Error('vouchsafe_auth_http_' . $code, 'Auth failed: ' . $body);
  }

  $data = json_decode($body, true);
  if (!is_array($data) || empty($data['access_token']) || empty($data['expires_at'])) {
    return new WP_Error('vouchsafe_auth_shape', 'Unexpected auth response.');
  }

  // Compute expiry timestamp with 5 min buffer
  $exp_ts = strtotime($data['expires_at']);
  if (!$exp_ts) $exp_ts = time() + DAY_IN_SECONDS;
  $buffered = max(60, $exp_ts - time() - 300);

  set_transient(VOUCHSAFE_TOKEN_TRANSIENT, [
    'token'  => $data['access_token'],
    'exp_ts' => $exp_ts - 300,
  ], $buffered);

  return $data['access_token'];
}

/**
 * Call POST /verifications -> returns array with 'url', 'id', 'expires_at'
 */
function vouchsafe_request_verification(array $payload)
{
  $token = vouchsafe_get_bearer_token();
  if (is_wp_error($token)) return $token;

  $resp = wp_remote_post(VOUCHSAFE_API_BASE . '/verifications', [
    'headers' => [
      'Content-Type'  => 'application/json',
      'Authorization' => 'Bearer ' . $token,
    ],
    'body'    => wp_json_encode($payload),
    'timeout' => 20,
  ]);
  if (is_wp_error($resp)) return $resp;

  $code = wp_remote_retrieve_response_code($resp);
  $body = wp_remote_retrieve_body($resp);
  if ($code !== 201) {
    return new WP_Error('vouchsafe_verify_http_' . $code, 'Verification request failed: ' . $body);
  }

  $data = json_decode($body, true);
  if (!is_array($data) || empty($data['url'])) {
    return new WP_Error('vouchsafe_verify_shape', 'Unexpected verification response.');
  }
  return $data;
}
