<?php
if (!defined('ABSPATH')) exit;

if (!defined('VOUCHSAFE_DASHBOARD_BASE')) {
  define('VOUCHSAFE_DASHBOARD_BASE', 'https://app.vouchsafe.id');
}

/**
 * Admin menu: Verifications (top-level)
 */
add_action('admin_menu', function () {
  add_menu_page(
    __('Verifications', 'vouchsafe-wp'),
    __('Verifications', 'vouchsafe-wp'),
    'manage_options',
    'vouchsafe-verifications',
    'vouchsafe_render_verifications_page',
    'dashicons-id',
    56
  );
});

/**
 * Format ISO 8601 (UTC/Z) timestamp into WP's date/time in site timezone
 */
function vouchsafe_format_iso_datetime($iso)
{
  if (empty($iso)) return '';
  $ts = strtotime($iso);
  if (!$ts) return esc_html($iso);
  $fmt = get_option('date_format') . ' ' . get_option('time_format');
  return esc_html(wp_date($fmt, $ts));
}

function vouchsafe_render_verifications_page()
{
  if (!current_user_can('manage_options')) return;

  // $nonce_ok = isset($_GET['vs_nonce']) && wp_verify_nonce(wp_unslash($_GET['vs_nonce']), 'vouchsafe_filter_status');
  // $status = ($nonce_ok && isset($_GET['vs_status'])) ? sanitize_text_field(wp_unslash($_GET['vs_status'])) : '';

  // Read + sanitize nonce first (appeases PHPCS).
  $vs_nonce = isset($_GET['vs_nonce']) ? sanitize_text_field(wp_unslash($_GET['vs_nonce'])) : '';
  $nonce_ok = ($vs_nonce && wp_verify_nonce($vs_nonce, 'vouchsafe_filter_status'));

  // Only trust status if nonce is valid.
  $status = ($nonce_ok && isset($_GET['vs_status']))
    ? sanitize_text_field(wp_unslash($_GET['vs_status']))
    : '';



  $notice_html = '';

  if (!function_exists('vouchsafe_list_verifications')) {
    require_once __DIR__ . '/api.php';
  }
  $resp = vouchsafe_list_verifications($status);
  if (is_wp_error($resp)) {
    $notice_html = '<div class="notice notice-error"><p><strong>Error:</strong> ' . esc_html($resp->get_error_message()) . '</p></div>';
    $rows = [];
  } else {
    $rows = $resp;
  }

  // Pull client_id for dashboard links
  $opt = get_option(VOUCHSAFE_OPT, []);
  $client_id_for_links = isset($opt['client_id']) ? trim($opt['client_id']) : '';

  $statuses = ['', 'InProgress', 'ReadyForReview', 'Verified', 'Refused', 'Cancelled', 'LockedOut'];
  $limit = 50; // display cap
?>
  <div class="wrap">
    <h1><?php esc_html_e('Verifications', 'vouchsafe-wp'); ?></h1>

    <?php echo wp_kses_post($notice_html); ?>

    <form method="get" style="margin: 10px 0;">
      <?php wp_nonce_field('vouchsafe_filter_status', 'vs_nonce', false); ?>


      <input type="hidden" name="page" value="vouchsafe-verifications" />
      <label for="vs_status" class="screen-reader-text"><?php esc_html_e('Filter by status:', 'vouchsafe-wp'); ?></label>
      <select name="vs_status" id="vs_status">
        <?php foreach ($statuses as $s): ?>
          <option value="<?php echo esc_attr($s); ?>" <?php selected($status, $s); ?>>
            <?php echo $s ? esc_html($s) : esc_html__('All Statuses', 'vouchsafe-wp'); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php submit_button(__('Filter', 'vouchsafe-wp'), 'secondary', '', false); ?>


      <div class="alignright">
        <?php
        $dash_button_url = '';
        if (! empty($client_id_for_links)) {
          $dash_button_url = esc_url(untrailingslashit(VOUCHSAFE_DASHBOARD_BASE) . '/admin/teams/' . rawurlencode($client_id_for_links));
        }
        ?>
        <?php if ($dash_button_url) : ?>
          <a class="button" href="<?php echo esc_url($dash_button_url); ?>"><?php echo esc_html__('Go to Vouchsafe dashboard', 'vouchsafe-wp'); ?></a>
        <?php endif; ?>
      </div>
    </form>

    <table class="widefat striped">
      <thead>
        <tr>
          <th><?php esc_html_e('Email', 'vouchsafe-wp'); ?></th>
          <th><?php esc_html_e('Status', 'vouchsafe-wp'); ?></th>
          <th><?php esc_html_e('Workflow', 'vouchsafe-wp'); ?></th>
          <th><?php esc_html_e('Started', 'vouchsafe-wp'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="6"><?php esc_html_e('No verifications found.', 'vouchsafe-wp'); ?></td>
          </tr>
        <?php else: ?>
          <?php
          $flow_names = [];
          $flows = vouchsafe_list_flows();
          if (!is_wp_error($flows) && is_array($flows)) {
            foreach ($flows as $f) {
              if (isset($f['id'])) {
                $flow_names[$f['id']] = $f['name'] ?? $f['id'];
              }
            }
          }

          $count = 0;
          foreach ($rows as $r):
            if ($count++ >= $limit) break; // show first N
            $id          = esc_html($r['id'] ?? '');
            $status_txt  = esc_html($r['status'] ?? '');
            $email       = esc_html($r['email'] ?? '');
            $workflow_id = $r['workflow_id'] ?? '';
            $workflow_label = esc_html($flow_names[$workflow_id] ?? $workflow_id);
            $created_at  = vouchsafe_format_iso_datetime($r['created_at'] ?? '');

            $dash_url = '';
            if ($client_id_for_links && $id) {
              $base = untrailingslashit(VOUCHSAFE_DASHBOARD_BASE);
              $dash_url = esc_url($base . '/admin/teams/' . rawurlencode($client_id_for_links) . '/cases/' . rawurlencode($id));
            }

            $flow_url = '';
            if ($client_id_for_links && $workflow_id) {
              $base = untrailingslashit(VOUCHSAFE_DASHBOARD_BASE);
              $flow_url = esc_url($base . '/admin/teams/' . rawurlencode($client_id_for_links) . '/builder/' . rawurlencode($workflow_id));
            }
          ?>
            <tr>
              <td>
                <?php if ($dash_url): ?>
                  <a href="<?php echo esc_url($dash_url); ?>" class="row-title">
                    <?php echo esc_html($email); ?>
                  </a>
                <?php else: ?>
                  <?php echo esc_html($email); ?>
                <?php endif; ?>
              </td>
              <td><?php echo esc_html($status_txt); ?></td>
              <td>
                <?php if ($flow_url): ?>
                  <a href="<?php echo esc_url($flow_url); ?>">
                    <?php echo esc_html($workflow_label); ?>
                  </a>
                <?php else: ?>
                  <?php echo esc_html($workflow_label); ?>
                <?php endif; ?>
              </td>
              <td><?php echo esc_html($created_at); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <div class="tablenav bottom">

      <div class="tablenav-pages one-page">
        <span class="displaying-num">
          <?php
          $current_count = is_array($rows) ? count($rows) : 0;
          printf(
            /* translators: 1: number of items shown, 2: maximum number of items displayed */
            esc_html__(
              'Showing %1$d of up to %2$d items',
              'vouchsafe-wp'
            ),
            (int) $current_count,
            (int) $limit
          );
          ?>
        </span>

      </div>

      <br class="clear" />
    </div>


  </div>
<?php
}
