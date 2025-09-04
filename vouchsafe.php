<?php

/**
 * Plugin Name: Vouchsafe
 * Author URI: https://vouchsafe.id
 * Description: Request and manage Vouchsafe verifications from your WordPress website.
 * Version: 0.1.0
 * Author: Vouchsafe
 * Requires at least: 6.0
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: vouchsafe
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

define('VOUCHSAFE_OPT', 'vouchsafe_credentials');
define('VOUCHSAFE_API_BASE', 'https://app.vouchsafe.id/api/v1');
define('VOUCHSAFE_TOKEN_TRANSIENT', 'vouchsafe_access_token');

/**
 * Activation and deactivation
 */
register_activation_hook(__FILE__, function () {
  if (false === get_option(VOUCHSAFE_OPT, false)) {
    add_option(VOUCHSAFE_OPT, ['client_id' => '', 'client_secret' => ''], '', 'no');
  }
  vouchsafe_add_rewrite_rules();
  flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
  flush_rewrite_rules();
});

/**
 * Add "Settings" link to entry in plugin list
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
  $url = admin_url('options-general.php?page=vouchsafe-settings');
  array_unshift($links, '<a href="' . esc_url($url) . '">Settings</a>');
  return $links;
});

add_action('init', function () {
  load_plugin_textdomain('vouchsafe', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

include_once "inc/admin.php";
include_once "inc/api.php";
include_once "inc/endpoint.php";
include_once "inc/verifications.php";
