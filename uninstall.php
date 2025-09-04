<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
  exit;
}

delete_option('vouchsafe_credentials');
delete_transient('vouchsafe_access_token');
delete_transient('vouchsafe_flows_cache');
