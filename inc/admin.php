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
});

function vouchsafe_sanitize_credentials($input)
{
  $existing = get_option(VOUCHSAFE_OPT, ['client_id' => '', 'client_secret' => '', 'workflow_id' => '']);
  $out = [
    'client_id'     => $existing['client_id'] ?? '',
    'client_secret' => $existing['client_secret'] ?? '',
    'workflow_id'   => $existing['workflow_id'] ?? '',
  ];

  if (is_array($input)) {
    if (isset($input['client_id'])) {
      $out['client_id'] = trim(sanitize_text_field($input['client_id']));
    }
    if (isset($input['client_secret'])) {
      $new = trim($input['client_secret']);
      if ($new !== '') {
        $out['client_secret'] = wp_strip_all_tags($new, true);
      }
    }
    if (isset($input['workflow_id'])) {
      $out['workflow_id'] = sanitize_text_field($input['workflow_id']);
    }
  }

  // Clear cached token if credentials changed
  if (
    ($existing['client_id'] ?? '') !== $out['client_id'] ||
    ($existing['client_secret'] ?? '') !== $out['client_secret']
  ) {
    delete_transient(VOUCHSAFE_TOKEN_TRANSIENT);
  }

  return $out;
}

function vouchsafe_get_flows_cached()
{
  $flows = get_transient('vouchsafe_flows_cache');
  return (is_array($flows) && !empty($flows)) ? $flows : [];
}

function vouchsafe_render_settings_page()
{
  if (!current_user_can('manage_options')) return;

  $opt   = get_option(VOUCHSAFE_OPT, ['client_id' => '', 'client_secret' => '', 'workflow_id' => '']);
  $flows_notice = '';

  // Handle "Test connection / Fetch flows" action
  if (isset($_POST['vouchsafe_action']) && $_POST['vouchsafe_action'] === 'fetch_flows' && check_admin_referer('vouchsafe_fetch_flows', '_vsf')) {
    if (!function_exists('vouchsafe_list_flows')) {
      require_once __DIR__ . '/api.php';
    }
    $resp = vouchsafe_list_flows();
    if (is_wp_error($resp)) {
      $flows_notice = '<div class="notice notice-error"><p><strong>Connection failed:</strong> ' . esc_html($resp->get_error_message()) . '</p></div>';
      delete_transient('vouchsafe_flows_cache');
    } else {
      set_transient('vouchsafe_flows_cache', $resp, 10 * MINUTE_IN_SECONDS);
      $flows_notice = '<div class="notice notice-success"><p><strong>Success:</strong> Retrieved ' . count($resp) . ' flow(s). Choose one below and save.</p></div>';
    }
  }

  $flows = vouchsafe_get_flows_cached();
  $has_creds = !empty($opt['client_id']) && !empty($opt['client_secret']);
  $selected_flow = $opt['workflow_id'] ?? '';

?>
  <div class="wrap">
    <h1>Vouchsafe Settings</h1>

    <style>
      .vouchsafe-step {
        background: #fff;
        border: 1px solid #dcdcde;
        border-radius: 6px;
        padding: 16px 20px;
        margin: 16px 0;
      }

      .vouchsafe-step h2 {
        margin: 0 0 10px;
        font-size: 1.2rem;
      }

      .vouchsafe-step .stepnum {
        display: inline-block;
        background: #2271b1;
        color: #fff;
        border-radius: 999px;
        width: 26px;
        height: 26px;
        line-height: 26px;
        text-align: center;
        margin-right: 8px;
        font-weight: 600;
      }

      .vouchsafe-actions {
        margin-top: 10px;
      }

      .description {
        color: #646970;
      }

      .inline-form {
        display: inline;
      }
    </style>

    <!-- Step 1: API credentials -->
    <div class="vouchsafe-step">
      <h2><span class="stepnum">1</span> API credentials</h2>
      <p class="description">Paste your Client ID and Client Secret from the <strong>API Integration</strong> tab of the <a href="https://app.vouchsafe.id">Vouchsafe dashboard</a>.</p>
      <form method="post" action="options.php">
        <?php
        settings_fields('vouchsafe_settings_group');
        $client_id = esc_attr($opt['client_id'] ?? '');
        echo '<table class="form-table" role="presentation"><tbody>';
        echo '<tr><th scope="row"><label for="vouchsafe_client_id">Client ID</label></th><td>';
        echo '<input id="vouchsafe_client_id" type="text" class="regular-text" name="' . esc_attr(VOUCHSAFE_OPT) . '[client_id]" value="' . esc_attr($client_id) . '" autocomplete="off" />';
        echo '</td></tr>';
        echo '<tr><th scope="row"><label for="vouchsafe_client_secret">Client Secret</label></th><td>';
        echo '<input id="vouchsafe_client_secret" type="password" class="regular-text" name="' . esc_attr(VOUCHSAFE_OPT) . '[client_secret]" value="" placeholder="••••••••••••" autocomplete="new-password" />';
        echo '<p class="description">Leave blank to keep your existing secret.</p>';
        echo '</td></tr>';
        echo '</tbody></table>';
        submit_button('Save API credentials');
        ?>
      </form>
    </div>

    <!-- Step 2: Test connection & choose flow -->
    <div class="vouchsafe-step">
      <h2><span class="stepnum">2</span> Test connection and choose flow</h2>
      <p class="description">Fetch your published verification flows and select one to use. This flow will be used unless a specific <code>workflow_id</code> is provided in the request URL.</p>

      <?php if (!$has_creds): ?>
        <div class="notice notice-warning">
          <p>Save your API credentials in Step 1 before fetching flows.</p>
        </div>
      <?php endif; ?>

      <?php echo wp_kses_post($flows_notice); ?>

      <div class="vouchsafe-actions">
        <form method="post" class="inline-form">
          <?php wp_nonce_field('vouchsafe_fetch_flows', '_vsf'); ?>
          <input type="hidden" name="vouchsafe_action" value="fetch_flows" />
          <?php submit_button('Test connection & fetch flows', 'secondary', 'submit', false, $has_creds ? [] : ['disabled' => 'disabled']); ?>
        </form>
      </div>

      <form method="post" action="options.php" style="margin-top:10px;">
        <?php
        settings_fields('vouchsafe_settings_group');
        $flows = vouchsafe_get_flows_cached();
        echo '<table class="form-table" role="presentation"><tbody>';
        echo '<tr><th scope="row"><label for="vouchsafe_workflow_id">Default verification flow</label></th><td>';
        echo '<select id="vouchsafe_workflow_id" name="' . esc_attr(VOUCHSAFE_OPT) . '[workflow_id]" class="regular-text" ' . (!$has_creds ? 'disabled' : '') . '>';
        if (is_array($flows) && !empty($flows)) {
          echo '<option value="">— Select a flow —</option>';
          foreach ($flows as $flow) {
            if (!is_array($flow) || empty($flow['id'])) continue;
            $id = esc_attr($flow['id']);
            $name = esc_html($flow['name'] ?? $flow['id']);
            $sel = selected($selected_flow, $id, false);
            echo '<option value="' . esc_attr($id) . '" ' . ($sel ? 'selected="selected"' : '') . '>' . esc_html($name) . '</option>';
          }
        } else {
          echo '<option disabled value="">Fetch flows first</option>';
        }
        echo '</select>';
        if ($selected_flow) {
          echo '<p class="description">Currently selected flow ID: <code>' . esc_html($selected_flow) . '</code></p>';
        }
        echo '</td></tr>';
        echo '</tbody></table>';
        submit_button('Save selected flow', 'primary', 'submit', true, (!$has_creds ? ['disabled' => 'disabled'] : []));
        ?>
      </form>
    </div>

    <!-- Step 3: How to request verifications -->
    <div class="vouchsafe-step">
      <h2><span class="stepnum">3</span> Request verifications</h2>
      <ol>
        <li>Direct your users to: <code><?php echo esc_html(home_url('/vouchsafe/request')); ?>?email={merge-tag}</code></li>
        <li>Optional URL params to pass additional details:
          <code>first_name</code>, <code>last_name</code>, <code>workflow_id</code> (overrides the default),
          <code>external_id</code>, <code>redirect_url</code>, <code>street_address</code>,
          <code>postcode</code>, <code>date_of_birth</code>, <code>expires_at</code>, and a <code>fallback</code> URL.
        </li>
        <li>On success, users are redirected to the Vouchsafe verification URL.</li>
      </ol>
      <p class="description">Tip: After activating this plugin, visit <em>Settings → Permalinks</em> and click <strong>Save</strong> once to ensure rewrites are flushed.</p>
    </div>
  </div>
<?php
}
