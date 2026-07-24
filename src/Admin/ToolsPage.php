<?php
namespace Aurismore\AAT\Admin;

use Aurismore\AAT\Licence;
use Aurismore\AAT\Tickets;

if (!defined('ABSPATH')) exit;

class ToolsPage extends Page {
    public function slug() {
        return 'wp-admin-toolkit-tools';
    }

    public function title() {
        return __('Tools & System Status', 'wp-agency-admin-toolkit');
    }

    protected function notices() {
        parent::notices();
        if (isset($_GET['aat_imported'])) $this->notice(__('Settings imported successfully.', 'wp-agency-admin-toolkit'));
        if (isset($_GET['aat_reset'])) $this->notice(__('Defaults restored successfully.', 'wp-agency-admin-toolkit'));
        if (isset($_GET['aat_update_cache_cleared'])) $this->notice(__('Update cache cleared. WordPress will check WP Client Tools again.', 'wp-agency-admin-toolkit'));
        if (isset($_GET['aat_licence_checked'])) $this->notice(__('Licence revalidated against WP Client Tools.', 'wp-agency-admin-toolkit'));
    }

    protected function content($s) {
        ?>
        <div class="aat-card aat-wide" id="aat-tools">
            <h2><?php esc_html_e('Import / Export', 'wp-agency-admin-toolkit'); ?></h2>
            <p><?php esc_html_e('Export this setup and import it on another client website.', 'wp-agency-admin-toolkit'); ?></p>
            <p><?php esc_html_e('Safe export excludes sensitive support webhook data. Use full export only for moving settings between your own trusted sites.', 'wp-agency-admin-toolkit'); ?></p>
            <p>
                <a class="button button-secondary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=aat_export'), 'aat_export')); ?>"><?php esc_html_e('Export safe settings', 'wp-agency-admin-toolkit'); ?></a>
                <a class="button button-secondary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=aat_export&include_sensitive=1'), 'aat_export')); ?>"><?php esc_html_e('Export full settings', 'wp-agency-admin-toolkit'); ?></a>
            </p>
            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php?action=aat_import')); ?>">
                <?php wp_nonce_field('aat_import'); ?>
                <input type="file" name="aat_import_file" accept="application/json">
                <?php submit_button(__('Import settings', 'wp-agency-admin-toolkit'), 'secondary', 'submit', false); ?>
            </form>
        </div>

        <div class="aat-card aat-wide">
            <h2><?php esc_html_e('Reset', 'wp-agency-admin-toolkit'); ?></h2>
            <p><?php esc_html_e('Restore WP Admin Toolkit Pro defaults. This keeps the plugin active but replaces saved settings with the recommended WP Client Tools product configuration.', 'wp-agency-admin-toolkit'); ?></p>
            <p><a class="button button-secondary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=aat_reset_defaults'), 'aat_reset_defaults')); ?>" onclick="return confirm('<?php echo esc_js(__('Reset WP Admin Toolkit Pro settings to defaults?', 'wp-agency-admin-toolkit')); ?>');"><?php esc_html_e('Reset to defaults', 'wp-agency-admin-toolkit'); ?></a></p>
        </div>

        <div class="aat-card aat-wide" id="aat-status">
            <h2><?php esc_html_e('System Status', 'wp-agency-admin-toolkit'); ?></h2>
            <p><?php esc_html_e('Use this when troubleshooting client sites or preparing support requests. It contains environment details but avoids licence keys and webhook URLs.', 'wp-agency-admin-toolkit'); ?></p>
            <div class="aat-status-grid">
                <?php foreach ($this->system_status_rows() as $label => $value): ?>
                    <div class="aat-status-row"><strong><?php echo esc_html($label); ?></strong><span><?php echo esc_html($value); ?></span></div>
                <?php endforeach; ?>
            </div>
            <p>
                <a class="button button-secondary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=aat_check_licence_now'), 'aat_check_licence_now')); ?>"><?php esc_html_e('Check licence now', 'wp-agency-admin-toolkit'); ?></a>
                <a class="button button-secondary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=aat_clear_update_cache'), 'aat_clear_update_cache')); ?>"><?php esc_html_e('Clear update cache', 'wp-agency-admin-toolkit'); ?></a>
            </p>
            <h3><?php esc_html_e('Copy system report', 'wp-agency-admin-toolkit'); ?></h3>
            <textarea class="aat-system-report" rows="12" readonly><?php echo esc_textarea($this->system_report()); ?></textarea>
        </div>
        <?php
    }

    private function system_status_rows() {
        global $wp_version;
        $theme = wp_get_theme();
        $uploads = wp_upload_dir();
        $update_info = get_site_transient('aat_remote_update_info');
        $remote_version = is_array($update_info) ? ($update_info['version'] ?? '') : '';
        $remote_channel = is_array($update_info) ? ($update_info['channel'] ?? '') : '';
        $remote_sha = is_array($update_info) && !empty($update_info['package_sha256']) ? substr($update_info['package_sha256'], 0, 12) . '…' : '';
        $yes = __('Yes', 'wp-agency-admin-toolkit');
        $no = __('No', 'wp-agency-admin-toolkit');
        $not_active = __('Not active', 'wp-agency-admin-toolkit');
        $ticket_counts = Tickets::counts();
        $tickets_row = Tickets::is_installed()
            /* translators: 1: total ticket count, 2: new ticket count, 3: in-progress ticket count. */
            ? sprintf(__('%1$d total · %2$d new · %3$d in progress', 'wp-agency-admin-toolkit'), $ticket_counts['all'], $ticket_counts['new'], $ticket_counts['in_progress'])
            : __('Table not installed', 'wp-agency-admin-toolkit');
        return [
            __('Plugin version', 'wp-agency-admin-toolkit') => AAT_VERSION,
            __('WordPress version', 'wp-agency-admin-toolkit') => $wp_version,
            __('PHP version', 'wp-agency-admin-toolkit') => PHP_VERSION,
            __('Site URL', 'wp-agency-admin-toolkit') => home_url('/'),
            __('Admin email configured', 'wp-agency-admin-toolkit') => is_email($this->core->settings['support_email'] ?? '') ? $yes : $no,
            __('Webhook configured', 'wp-agency-admin-toolkit') => !empty($this->core->settings['support_webhook']) ? $yes : $no,
            __('Support tickets', 'wp-agency-admin-toolkit') => $tickets_row,
            'WooCommerce' => defined('WC_VERSION') ? WC_VERSION : $not_active,
            'Elementor' => defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : $not_active,
            'Rank Math' => defined('RANK_MATH_VERSION') ? RANK_MATH_VERSION : $not_active,
            'MyParcel' => defined('MYPARCEL_VERSION') ? MYPARCEL_VERSION : (class_exists('WC_MyParcel') ? __('Active', 'wp-agency-admin-toolkit') : $not_active),
            'Yoast SEO' => defined('WPSEO_VERSION') ? WPSEO_VERSION : $not_active,
            'WP Rocket' => defined('WP_ROCKET_VERSION') ? WP_ROCKET_VERSION : $not_active,
            'LiteSpeed Cache' => defined('LSCWP_V') ? LSCWP_V : (defined('LSCWP_VERSION') ? LSCWP_VERSION : $not_active),
            'Advanced Custom Fields' => defined('ACF_VERSION') ? ACF_VERSION : $not_active,
            __('Theme', 'wp-agency-admin-toolkit') => $theme->get('Name') . ' ' . $theme->get('Version'),
            __('Multisite', 'wp-agency-admin-toolkit') => is_multisite() ? $yes : $no,
            __('Uploads writable', 'wp-agency-admin-toolkit') => !empty($uploads['basedir']) && wp_is_writable($uploads['basedir']) ? $yes : $no,
            'DISALLOW_FILE_EDIT' => defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT ? __('Enabled', 'wp-agency-admin-toolkit') : __('Not enabled', 'wp-agency-admin-toolkit'),
            __('Affected roles', 'wp-agency-admin-toolkit') => implode(', ', (array) ($this->core->settings['affected_roles'] ?? [])),
            __('Licence status', 'wp-agency-admin-toolkit') => ucfirst($this->core->settings['licence_status'] ?? 'inactive'),
            __('Licence last checked', 'wp-agency-admin-toolkit') => $this->core->settings['licence_checked_at'] ?? '',
            __('Product slug', 'wp-agency-admin-toolkit') => defined('AAT_PRODUCT_SLUG') ? AAT_PRODUCT_SLUG : 'wp-agency-admin-toolkit-pro',
            __('Update server', 'wp-agency-admin-toolkit') => Licence::licence_server(),
            __('Remote version', 'wp-agency-admin-toolkit') => $remote_version ?: __('Not cached', 'wp-agency-admin-toolkit'),
            __('Update channel', 'wp-agency-admin-toolkit') => $remote_channel ?: __('(default)', 'wp-agency-admin-toolkit'),
            __('Package SHA-256', 'wp-agency-admin-toolkit') => $remote_sha ?: __('Not advertised', 'wp-agency-admin-toolkit'),
        ];
    }

    private function system_report() {
        /* translators: this is the first line of the copyable plain-text system report. */
        $lines = [__('WP Admin Toolkit Pro System Report', 'wp-agency-admin-toolkit'), __('Generated:', 'wp-agency-admin-toolkit') . ' ' . gmdate('c'), ''];
        foreach ($this->system_status_rows() as $label => $value) {
            $lines[] = $label . ': ' . $value;
        }
        return implode("\n", $lines);
    }
}
