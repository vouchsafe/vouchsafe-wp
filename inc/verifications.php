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
    __('Verifications', 'vouchsafe'),
    __('Verifications', 'vouchsafe'),
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

  $status = isset($_GET['vs_status']) ? sanitize_text_field($_GET['vs_status']) : '';
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
    <h1><?php esc_html_e('Verifications', 'vouchsafe'); ?></h1>

    <?php echo $notice_html; ?>

    <form method="get" style="margin: 10px 0;">


      <input type="hidden" name="page" value="vouchsafe-verifications" />
      <label for="vs_status" class="screen-reader-text"><?php esc_html_e('Filter by status:', 'vouchsafe'); ?></label>
      <select name="vs_status" id="vs_status">
        <?php foreach ($statuses as $s): ?>
          <option value="<?php echo esc_attr($s); ?>" <?php selected($status, $s); ?>>
            <?php echo $s ? esc_html($s) : esc_html__('All Statuses', 'vouchsafe'); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php submit_button(__('Filter'), 'secondary', '', false); ?>


      <div class="alignright">
        <a class="button" href="https://app.vouchsafe.id/admin/teams/<?php echo $client_id_for_links ?>">Go to Vouchsafe dashboard</a>
      </div>
    </form>

    <table class="widefat striped">
      <thead>
        <tr>
          <th><?php esc_html_e('Email', 'vouchsafe'); ?></th>
          <th><?php esc_html_e('Status', 'vouchsafe'); ?></th>
          <th><?php esc_html_e('Workflow', 'vouchsafe'); ?></th>
          <th><?php esc_html_e('Started', 'vouchsafe'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="6"><?php esc_html_e('No verifications found.', 'vouchsafe'); ?></td>
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
                  <a href="<?php echo $dash_url; ?>" class="row-title">
                    <?php echo $email; ?>
                  </a>
                <?php else: ?>
                  <?php echo $email; ?>
                <?php endif; ?>
              </td>
              <td><?php echo $status_txt; ?></td>
              <td>
                <?php if ($flow_url): ?>
                  <a href="<?php echo $flow_url; ?>">
                    <?php echo $workflow_label; ?>
                  </a>
                <?php else: ?>
                  <?php echo $workflow_label; ?>
                <?php endif; ?>
              </td>
              <td><?php echo $created_at; ?></td>
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
            esc_html__(
              'Showing %1$d of up to %2$d items',
              'vouchsafe'
            ),
            $current_count,
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
