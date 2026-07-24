<?php
namespace Aurismore\AAT\Admin;

if (!defined('ABSPATH')) exit;

class LicencePage extends Page {
    public function slug() {
        return 'wp-admin-toolkit-licence';
    }

    public function title() {
        return 'Licence & Updates';
    }

    protected function notices() {
        parent::notices();
        if (isset($_GET['aat_license_saved'])) $this->notice('Licence settings updated.');
    }

    protected function content($s) {
        $opt = esc_attr(AAT_OPTION);
        $licence_status = sanitize_key($s['licence_status'] ?? 'inactive');
        $licence_active = ($licence_status === 'active');
        $licence_key = $s['licence_key'] ?? '';
        $expires = $s['licence_expires_at'] ?? '';
        ?>
        <div class="aat-card aat-wide" id="aat-license">
            <h2>Licence &amp; Updates</h2>
            <p>Enter your WP Client Tools licence key to enable protected updates.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="aat_save_license">
                <?php wp_nonce_field('aat_save_license'); ?>
                <div class="aat-license-grid aat-pro-license-grid">
                    <label>Licence key
                        <input type="password" name="<?php echo $opt; ?>[licence_key]" value="<?php echo esc_attr($licence_key); ?>" autocomplete="off" placeholder="Enter your WP Client Tools licence key">
                    </label>
                    <div class="aat-license-summary">
                        <strong>Status</strong>
                        <span class="<?php echo $licence_active ? 'aat-status-good' : 'aat-status-bad'; ?>">
                            <?php echo $licence_active ? 'Active' : ($licence_key ? 'Not active' : 'Not activated'); ?>
                        </span>
                        <?php if ($licence_active && $expires): ?>
                            <small>Expires: <?php echo esc_html($expires); ?></small>
                        <?php elseif (!$licence_active && !empty($s['licence_message']) && $licence_key): ?>
                            <small><?php echo esc_html($s['licence_message']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <p>
                    <?php submit_button($licence_active ? 'Re-activate licence' : 'Activate licence', 'primary', 'aat_activate_license', false); ?>
                    <?php if ($licence_active): ?>
                        <?php submit_button('Deactivate licence', 'secondary', 'aat_deactivate_license', false); ?>
                    <?php endif; ?>
                </p>
            </form>
        </div>
        <?php
    }
}
