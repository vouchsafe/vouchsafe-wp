<?php

if (!defined('ABSPATH')) exit;

/**
 * Settings page
 */
add_action('admin_menu', function () {
  add_options_page(
    'Vouchsafe Settings',
    'Vouchsafe',
    'manage_options',
    'vouchsafe-settings',
    'vouchsafe_render_settings_page'
  );
});

add_action('admin_init', function () {
  register_setting('vouchsafe_settings_group', VOUCHSAFE_OPT, [
    'type'              => 'array',
    'sanitize_callback' => 'vouchsafe_sanitize_credentials',
    'show_in_rest'      => false,
  ]);

  add_settings_section(
    'vouchsafe_main',
    'API Credentials',
    function () {
      echo '<p>Paste your Vouchsafe client ID and client secret from the <a href="https://vouchsafe.id">Vouchsafe dashboard</a>. Only administrators can view or change these.</p>';
    },
    'vouchsafe-settings'
  );

  add_settings_field(
    'vouchsafe_client_id',
    'Client ID',
    'vouchsafe_field_client_id',
    'vouchsafe-settings',
    'vouchsafe_main'
  );

  add_settings_field(
    'vouchsafe_client_secret',
    'Client Secret',
    'vouchsafe_field_client_secret',
    'vouchsafe-settings',
    'vouchsafe_main'
  );
});

function vouchsafe_sanitize_credentials($input)
{
  $existing = get_option(VOUCHSAFE_OPT, ['client_id' => '', 'client_secret' => '']);
  $out = [
    'client_id'     => $existing['client_id'] ?? '',
    'client_secret' => $existing['client_secret'] ?? '',
  ];

  if (is_array($input)) {
    // Client ID: safe to normalize
    if (isset($input['client_id'])) {
      $out['client_id'] = trim(sanitize_text_field($input['client_id']));
    }

    // Secret: don't mangle symbols; just strip any HTML tags.
    if (isset($input['client_secret'])) {
      $new = trim($input['client_secret']);
      if ($new !== '') {
        // Remove any tags but keep punctuation/symbols intact
        $out['client_secret'] = wp_kses($new, []);
      }
      // else: leave as the existing saved secret
    }
  }

  // If either credential changed, clear our cached bearer token
  if (
    ($existing['client_id'] ?? '') !== $out['client_id'] ||
    ($existing['client_secret'] ?? '') !== $out['client_secret']
  ) {
    delete_transient(VOUCHSAFE_TOKEN_TRANSIENT);
  }

  return $out;
}


function vouchsafe_field_client_id()
{
  $creds = get_option(VOUCHSAFE_OPT, []);
  $val = isset($creds['client_id']) ? esc_attr($creds['client_id']) : '';
  echo '<input type="text" name="' . esc_attr(VOUCHSAFE_OPT) . '[client_id]" value="' . $val . '" class="regular-text" autocomplete="off" />';
}

function vouchsafe_field_client_secret()
{
  echo '<input type="password" name="' . esc_attr(VOUCHSAFE_OPT) . '[client_secret]" value="" class="regular-text" autocomplete="new-password" placeholder="••••••••••••" />';
}

function vouchsafe_render_settings_page()
{
  if (!current_user_can('manage_options')) return;
?>
  <div class="wrap">
    <h1>Vouchsafe Settings</h1>
    <form method="post" action="options.php">
      <?php
      settings_fields('vouchsafe_settings_group');
      do_settings_sections('vouchsafe-settings');
      submit_button('Save Changes');
      ?>
    </form>

    <hr />
    <h2>Quick Tips</h2>
    <ol>
      <li>After activating this plugin, visit <em>Settings → Permalinks</em> and click <strong>Save</strong> once to ensure rewrites are flushed.</li>
      <li>Set your form’s confirmation URL to: <code><?php echo esc_html(home_url('/vouchsafe/request')); ?>?email={merge-tag}</code></li>
      <li>You can optionally pass additional fields: <code>first_name</code>, <code>last_name</code>, <code>workflow_id</code>, <code>external_id</code>, <code>redirect_url</code>, <code>street_address</code>, <code>postcode</code>, <code>date_of_birth</code>, <code>expires_at</code>, and a <code>fallback</code> URL for graceful failure.</li>
    </ol>
  </div>
<?php
}
