<?php
namespace Aurismore\AAT\Admin;

if (!defined('ABSPATH')) exit;

class LicencePage extends Page {
    public function slug() {
        return 'wp-admin-toolkit-licence';
    }

    public function title() {
        return __('Licence & Updates', 'wp-agency-admin-toolkit');
    }

    protected function notices() {
        parent::notices();
        if (isset($_GET['aat_license_saved'])) $this->notice(__('Licence settings updated.', 'wp-agency-admin-toolkit'));
    }

    protected function content($s) {
        $opt = esc_attr(AAT_OPTION);
        $licence_status = sanitize_key($s['licence_status'] ?? 'inactive');
        $licence_active = ($licence_status === 'active');
        $licence_key = $s['licence_key'] ?? '';
        $expires = $s['licence_expires_at'] ?? '';
        ?>
        <div class="aat-card aat-wide" id="aat-license">
            <h2><?php esc_html_e('Licence & Updates', 'wp-agency-admin-toolkit'); ?></h2>
            <p><?php esc_html_e('Enter your WP Client Tools licence key to enable protected updates.', 'wp-agency-admin-toolkit'); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="aat_save_license">
                <?php wp_nonce_field('aat_save_license'); ?>
                <div class="aat-license-grid aat-pro-license-grid">
                    <label><?php esc_html_e('Licence key', 'wp-agency-admin-toolkit'); ?>
                        <input type="password" name="<?php echo $opt; ?>[licence_key]" value="<?php echo esc_attr($licence_key); ?>" autocomplete="off" placeholder="<?php esc_attr_e('Enter your WP Client Tools licence key', 'wp-agency-admin-toolkit'); ?>">
                    </label>
                    <div class="aat-license-summary">
                        <strong><?php esc_html_e('Status', 'wp-agency-admin-toolkit'); ?></strong>
                        <span class="<?php echo $licence_active ? 'aat-status-good' : 'aat-status-bad'; ?>">
                            <?php echo esc_html($licence_active ? __('Active', 'wp-agency-admin-toolkit') : ($licence_key ? __('Not active', 'wp-agency-admin-toolkit') : __('Not activated', 'wp-agency-admin-toolkit'))); ?>
                        </span>
                        <?php if ($licence_active && $expires): ?>
                            <small><?php
                                /* translators: %s: licence expiry date. */
                                echo esc_html(sprintf(__('Expires: %s', 'wp-agency-admin-toolkit'), $expires));
                            ?></small>
                        <?php elseif (!$licence_active && !empty($s['licence_message']) && $licence_key): ?>
                            <small><?php echo esc_html($s['licence_message']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <p>
                    <?php submit_button($licence_active ? __('Re-activate licence', 'wp-agency-admin-toolkit') : __('Activate licence', 'wp-agency-admin-toolkit'), 'primary', 'aat_activate_license', false); ?>
                    <?php if ($licence_active): ?>
                        <?php submit_button(__('Deactivate licence', 'wp-agency-admin-toolkit'), 'secondary', 'aat_deactivate_license', false); ?>
                    <?php endif; ?>
                </p>
            </form>
        </div>
        <?php
    }
}
