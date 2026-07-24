<?php
namespace Aurismore\AAT\Admin;

if (!defined('ABSPATH')) exit;

class GeneralPage extends Page {
    public function slug() {
        return 'wp-admin-toolkit';
    }

    public function title() {
        return 'WP Admin Toolkit Pro — General';
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
                    <h2>Agency details</h2>
                    <p class="aat-product-note"><strong>WP Client Tools Pro product.</strong> WP Admin Toolkit Pro is distributed through WP Client Tools and created by Creative Digital Media. Your own agency details below are what clients see inside their dashboard and support areas.</p>
                    <label>Agency name shown to clients <input type="text" name="<?php echo $opt; ?>[agency_name]" value="<?php echo esc_attr($s['agency_name']); ?>"></label>
                    <label>Agency website shown to clients <input type="url" name="<?php echo $opt; ?>[agency_url]" value="<?php echo esc_url($s['agency_url']); ?>"></label>
                    <label>Logout redirect URL <input type="url" name="<?php echo $opt; ?>[logout_redirect_url]" value="<?php echo esc_url($s['logout_redirect_url']); ?>"></label>
                    <p class="description">Where affected client users are sent after logging out. Leave as the website homepage unless a custom thank-you or support page is preferred.</p>
                </section>

                <section class="aat-card">
                    <h2>Affected roles</h2>
                    <p>Select client-facing roles. Administrators and agency toolkit managers are never restricted.</p>
                    <?php foreach ($roles as $role_key => $role): ?>
                        <label class="aat-check"><input type="checkbox" name="<?php echo $opt; ?>[affected_roles][]" value="<?php echo esc_attr($role_key); ?>" <?php checked(in_array($role_key, (array) $s['affected_roles'], true)); ?>> <?php echo esc_html($role['name']); ?></label>
                    <?php endforeach; ?>
                </section>
            </div>
            <?php
        }, 'Save general settings');
    }
}
