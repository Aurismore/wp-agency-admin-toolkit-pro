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
        if (isset($_GET['aat_licence_checked'])) $this->notice(__('Licence revalidated against WP Client Tools.', 'wp-agency-admin-toolkit'));
    }

    protected function content($s) {
        $opt = esc_attr(AAT_OPTION);
        $licence_active = (sanitize_key($s['licence_status'] ?? 'inactive') === 'active');
        $licence_key = (string) ($s['licence_key'] ?? '');
        $has_key = ($licence_key !== '');

        $expires = '';
        $expires_raw = (string) ($s['licence_expires_at'] ?? '');
        if ($expires_raw !== '') {
            $ts = strtotime($expires_raw);
            $expires = $ts ? date_i18n(get_option('date_format'), $ts) : $expires_raw;
        }

        // The purchased product's name comes from the WCTLM server (1.4.14+);
        // fall back to the product's own name until the server has been checked.
        $product_name = (string) ($s['licence_product_name'] ?? '');
        if ($product_name === '') {
            $product_name = 'WP Admin Toolkit Pro';
        }

        // Fixed number of bullets so the markup doesn't leak the key length.
        $masked_key = $has_key ? str_repeat('•', 8) . substr($licence_key, -4) : '';

        $account_url = apply_filters('aat_account_url', 'https://wpclienttools.com/account');
        $check_url = wp_nonce_url(admin_url('admin-post.php?action=aat_check_licence_now&aat_redirect=licence'), 'aat_check_licence_now');

        if ($licence_active) {
            $status_label = __('Active', 'wp-agency-admin-toolkit');
            $status_class = 'aat-licence-status-active';
        } else {
            $status_label = $has_key ? __('Not active', 'wp-agency-admin-toolkit') : __('Not activated', 'wp-agency-admin-toolkit');
            $status_class = 'aat-licence-status-inactive';
        }
        ?>
        <div class="aat-card aat-wide aat-licence-panel" id="aat-license">
            <h2><?php esc_html_e('Licence Settings', 'wp-agency-admin-toolkit'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="aat_save_license">
                <?php wp_nonce_field('aat_save_license'); ?>

                <div class="aat-licence-row">
                    <div class="aat-licence-row-text">
                        <strong><?php esc_html_e('Status:', 'wp-agency-admin-toolkit'); ?></strong>
                        <span class="aat-licence-status <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_label); ?></span>
                        <?php if (!$licence_active && $has_key && !empty($s['licence_message'])): ?>
                            <small class="aat-licence-note"><?php echo esc_html($s['licence_message']); ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="aat-licence-actions">
                        <?php if ($has_key && !$licence_active): ?>
                            <?php submit_button(__('Activate licence', 'wp-agency-admin-toolkit'), 'primary', 'aat_activate_license', false); ?>
                        <?php endif; ?>
                        <?php if ($has_key): ?>
                            <a class="button" href="<?php echo esc_url($check_url); ?>"><span class="dashicons dashicons-update-alt" aria-hidden="true"></span> <?php esc_html_e('Check licence status', 'wp-agency-admin-toolkit'); ?></a>
                        <?php endif; ?>
                        <a class="button" href="<?php echo esc_url($account_url); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('My Account', 'wp-agency-admin-toolkit'); ?></a>
                    </div>
                </div>

                <?php if ($licence_active): ?>
                    <div class="aat-licence-row">
                        <div class="aat-licence-row-text">
                            <strong><?php esc_html_e('Subscription:', 'wp-agency-admin-toolkit'); ?></strong>
                            <?php
                            echo esc_html($product_name);
                            if ($expires) {
                                /* translators: %s: licence expiry date. */
                                echo ' — ' . esc_html(sprintf(__('expires %s', 'wp-agency-admin-toolkit'), $expires));
                            }
                            ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($has_key): ?>
                    <input type="hidden" name="<?php echo $opt; ?>[licence_key]" value="<?php echo esc_attr($licence_key); ?>">
                    <div class="aat-licence-row">
                        <div class="aat-licence-row-text"><?php
                            echo wp_kses(
                                sprintf(
                                    /* translators: %s: masked licence key. */
                                    __('You&#8217;re connected with licence key %s. Want to activate this website with a different licence key?', 'wp-agency-admin-toolkit'),
                                    '<strong>' . esc_html($masked_key) . '</strong>'
                                ),
                                ['strong' => []]
                            );
                        ?></div>
                        <div class="aat-licence-actions">
                            <button type="button" class="button aat-switch-licence"><?php esc_html_e('Switch licence key', 'wp-agency-admin-toolkit'); ?></button>
                        </div>
                    </div>
                    <div class="aat-licence-row aat-licence-switch-row">
                        <label class="screen-reader-text" for="aat-new-licence-key"><?php esc_html_e('Licence key', 'wp-agency-admin-toolkit'); ?></label>
                        <input type="password" id="aat-new-licence-key" class="aat-licence-key-input" name="<?php echo $opt; ?>[licence_key]" placeholder="<?php esc_attr_e('Enter your WP Client Tools licence key', 'wp-agency-admin-toolkit'); ?>" autocomplete="off" disabled required>
                        <div class="aat-licence-actions">
                            <?php submit_button(__('Activate licence', 'wp-agency-admin-toolkit'), 'primary', 'aat_activate_license', false); ?>
                            <button type="button" class="button aat-licence-switch-cancel"><?php esc_html_e('Cancel', 'wp-agency-admin-toolkit'); ?></button>
                        </div>
                    </div>
                    <div class="aat-licence-row">
                        <div class="aat-licence-row-text"><?php esc_html_e('Want to deactivate the licence for any reason?', 'wp-agency-admin-toolkit'); ?></div>
                        <div class="aat-licence-actions">
                            <?php submit_button(__('Disconnect', 'wp-agency-admin-toolkit'), 'secondary', 'aat_deactivate_license', false, ['onclick' => "return confirm('" . esc_js(__("Deactivate this website's licence?", 'wp-agency-admin-toolkit')) . "');"]); ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="aat-licence-row aat-licence-connect-row">
                        <label class="screen-reader-text" for="aat-new-licence-key"><?php esc_html_e('Licence key', 'wp-agency-admin-toolkit'); ?></label>
                        <input type="password" id="aat-new-licence-key" class="aat-licence-key-input" name="<?php echo $opt; ?>[licence_key]" placeholder="<?php esc_attr_e('Enter your WP Client Tools licence key', 'wp-agency-admin-toolkit'); ?>" autocomplete="off" required>
                        <div class="aat-licence-actions">
                            <?php submit_button(__('Activate licence', 'wp-agency-admin-toolkit'), 'primary', 'aat_activate_license', false); ?>
                        </div>
                    </div>
                    <p class="description"><?php esc_html_e('Enter your WP Client Tools licence key to enable protected updates.', 'wp-agency-admin-toolkit'); ?></p>
                <?php endif; ?>
            </form>
        </div>
        <?php
    }
}
