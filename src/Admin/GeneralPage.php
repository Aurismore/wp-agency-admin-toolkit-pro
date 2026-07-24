<?php
namespace Aurismore\AAT\Admin;

if (!defined('ABSPATH')) exit;

class GeneralPage extends Page {
    public function slug() {
        return 'wp-admin-toolkit';
    }

    public function title() {
        /* translators: %s: page name. */
        return sprintf(__('WP Admin Toolkit Pro — %s', 'wp-agency-admin-toolkit'), __('General', 'wp-agency-admin-toolkit'));
    }

    public function screen() {
        return 'general';
    }

    protected function content($s) {
        $opt = esc_attr(AAT_OPTION);
        $roles = wp_roles()->roles;
        $this->settings_form(function () use ($s, $opt, $roles) {
            ?>
            <div class="aat-grid">
                <section class="aat-card">
                    <h2><?php esc_html_e('Agency details', 'wp-agency-admin-toolkit'); ?></h2>
                    <p class="aat-product-note"><strong><?php esc_html_e('WP Client Tools Pro product.', 'wp-agency-admin-toolkit'); ?></strong> <?php esc_html_e('WP Admin Toolkit Pro is distributed through WP Client Tools and created by Creative Digital Media. Your own agency details below are what clients see inside their dashboard and support areas.', 'wp-agency-admin-toolkit'); ?></p>
                    <label><?php esc_html_e('Agency name shown to clients', 'wp-agency-admin-toolkit'); ?> <input type="text" name="<?php echo $opt; ?>[agency_name]" value="<?php echo esc_attr($s['agency_name']); ?>"></label>
                    <label><?php esc_html_e('Agency website shown to clients', 'wp-agency-admin-toolkit'); ?> <input type="url" name="<?php echo $opt; ?>[agency_url]" value="<?php echo esc_url($s['agency_url']); ?>"></label>
                    <label><?php esc_html_e('Logout redirect URL', 'wp-agency-admin-toolkit'); ?> <input type="url" name="<?php echo $opt; ?>[logout_redirect_url]" value="<?php echo esc_url($s['logout_redirect_url']); ?>"></label>
                    <p class="description"><?php esc_html_e('Where affected client users are sent after logging out. Leave as the website homepage unless a custom thank-you or support page is preferred.', 'wp-agency-admin-toolkit'); ?></p>
                </section>

                <section class="aat-card">
                    <h2><?php esc_html_e('Affected roles', 'wp-agency-admin-toolkit'); ?></h2>
                    <p><?php esc_html_e('Select client-facing roles. Administrators and agency toolkit managers are never restricted.', 'wp-agency-admin-toolkit'); ?></p>
                    <?php foreach ($roles as $role_key => $role): ?>
                        <label class="aat-check"><input type="checkbox" name="<?php echo $opt; ?>[affected_roles][]" value="<?php echo esc_attr($role_key); ?>" <?php checked(in_array($role_key, (array) $s['affected_roles'], true)); ?>> <?php echo esc_html(translate_user_role($role['name'])); ?></label>
                    <?php endforeach; ?>
                </section>
            </div>
            <?php
        }, __('Save general settings', 'wp-agency-admin-toolkit'));
    }
}
