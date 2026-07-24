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
        return 'Tools & System Status';
    }

    protected function notices() {
        parent::notices();
        if (isset($_GET['aat_imported'])) $this->notice('Settings imported successfully.');
        if (isset($_GET['aat_reset'])) $this->notice('Defaults restored successfully.');
        if (isset($_GET['aat_update_cache_cleared'])) $this->notice('Update cache cleared. WordPress will check WP Client Tools again.');
        if (isset($_GET['aat_licence_checked'])) $this->notice('Licence revalidated against WP Client Tools.');
    }

    protected function content($s) {
        ?>
        <div class="aat-card aat-wide" id="aat-tools">
            <h2>Import / Export</h2>
            <p>Export this setup and import it on another client website.</p>
            <p>Safe export excludes sensitive support webhook data. Use full export only for moving settings between your own trusted sites.</p>
            <p>
                <a class="button button-secondary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=aat_export'), 'aat_export')); ?>">Export safe settings</a>
                <a class="button button-secondary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=aat_export&include_sensitive=1'), 'aat_export')); ?>">Export full settings</a>
            </p>
            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php?action=aat_import')); ?>">
                <?php wp_nonce_field('aat_import'); ?>
                <input type="file" name="aat_import_file" accept="application/json">
                <?php submit_button('Import settings', 'secondary', 'submit', false); ?>
            </form>
        </div>

        <div class="aat-card aat-wide">
            <h2>Reset</h2>
            <p>Restore WP Admin Toolkit Pro defaults. This keeps the plugin active but replaces saved settings with the recommended WP Client Tools product configuration.</p>
            <p><a class="button button-secondary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=aat_reset_defaults'), 'aat_reset_defaults')); ?>" onclick="return confirm('Reset WP Admin Toolkit Pro settings to defaults?');">Reset to defaults</a></p>
        </div>

        <div class="aat-card aat-wide" id="aat-status">
            <h2>System Status</h2>
            <p>Use this when troubleshooting client sites or preparing support requests. It contains environment details but avoids licence keys and webhook URLs.</p>
            <div class="aat-status-grid">
                <?php foreach ($this->system_status_rows() as $label => $value): ?>
                    <div class="aat-status-row"><strong><?php echo esc_html($label); ?></strong><span><?php echo esc_html($value); ?></span></div>
                <?php endforeach; ?>
            </div>
            <p>
                <a class="button button-secondary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=aat_check_licence_now'), 'aat_check_licence_now')); ?>">Check licence now</a>
                <a class="button button-secondary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=aat_clear_update_cache'), 'aat_clear_update_cache')); ?>">Clear update cache</a>
            </p>
            <h3>Copy system report</h3>
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
        $ticket_counts = Tickets::counts();
        $tickets_row = Tickets::is_installed()
            ? sprintf('%d total · %d new · %d in progress', $ticket_counts['all'], $ticket_counts['new'], $ticket_counts['in_progress'])
            : 'Table not installed';
        return [
            'Plugin version' => AAT_VERSION,
            'WordPress version' => $wp_version,
            'PHP version' => PHP_VERSION,
            'Site URL' => home_url('/'),
            'Admin email configured' => is_email($this->core->settings['support_email'] ?? '') ? 'Yes' : 'No',
            'Webhook configured' => !empty($this->core->settings['support_webhook']) ? 'Yes' : 'No',
            'Support tickets' => $tickets_row,
            'WooCommerce' => defined('WC_VERSION') ? WC_VERSION : 'Not active',
            'Elementor' => defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : 'Not active',
            'Rank Math' => defined('RANK_MATH_VERSION') ? RANK_MATH_VERSION : 'Not active',
            'MyParcel' => defined('MYPARCEL_VERSION') ? MYPARCEL_VERSION : (class_exists('WC_MyParcel') ? 'Active' : 'Not active'),
            'Yoast SEO' => defined('WPSEO_VERSION') ? WPSEO_VERSION : 'Not active',
            'WP Rocket' => defined('WP_ROCKET_VERSION') ? WP_ROCKET_VERSION : 'Not active',
            'LiteSpeed Cache' => defined('LSCWP_V') ? LSCWP_V : (defined('LSCWP_VERSION') ? LSCWP_VERSION : 'Not active'),
            'Advanced Custom Fields' => defined('ACF_VERSION') ? ACF_VERSION : 'Not active',
            'Theme' => $theme->get('Name') . ' ' . $theme->get('Version'),
            'Multisite' => is_multisite() ? 'Yes' : 'No',
            'Uploads writable' => !empty($uploads['basedir']) && wp_is_writable($uploads['basedir']) ? 'Yes' : 'No',
            'DISALLOW_FILE_EDIT' => defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT ? 'Enabled' : 'Not enabled',
            'Affected roles' => implode(', ', (array) ($this->core->settings['affected_roles'] ?? [])),
            'Licence status' => ucfirst($this->core->settings['licence_status'] ?? 'inactive'),
            'Licence last checked' => $this->core->settings['licence_checked_at'] ?? '',
            'Product slug' => defined('AAT_PRODUCT_SLUG') ? AAT_PRODUCT_SLUG : 'wp-agency-admin-toolkit-pro',
            'Update server' => Licence::licence_server(),
            'Remote version' => $remote_version ?: 'Not cached',
            'Update channel' => $remote_channel ?: '(default)',
            'Package SHA-256' => $remote_sha ?: 'Not advertised',
        ];
    }

    private function system_report() {
        $lines = ['WP Admin Toolkit Pro System Report', 'Generated: ' . gmdate('c'), ''];
        foreach ($this->system_status_rows() as $label => $value) {
            $lines[] = $label . ': ' . $value;
        }
        return implode("\n", $lines);
    }
}
