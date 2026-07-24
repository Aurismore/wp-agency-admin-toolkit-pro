<?php
namespace Aurismore\AAT\Admin;

use Aurismore\AAT\Integrations;

if (!defined('ABSPATH')) exit;

class IntegrationsPage extends Page {
    public function slug() {
        return 'wp-admin-toolkit-integrations';
    }

    public function title() {
        return __('Plugin Integrations', 'wp-agency-admin-toolkit');
    }

    public function screen() {
        return 'integrations';
    }

    protected function content($s) {
        $this->settings_form(function () use ($s) {
            ?>
            <div class="aat-grid">
                <section class="aat-card">
                    <h2><?php esc_html_e('Integration cleanup', 'wp-agency-admin-toolkit'); ?></h2>
                    <p><?php esc_html_e('These settings only affect selected client roles after login.', 'wp-agency-admin-toolkit'); ?></p>
                    <?php
                    $this->checkbox($s, 'hide_elementor_settings', __('Hide Elementor settings from clients', 'wp-agency-admin-toolkit'));
                    $this->checkbox($s, 'hide_rank_math_overview', __('Hide Rank Math Overview dashboard widget', 'wp-agency-admin-toolkit'));
                    $this->checkbox($s, 'hide_myparcel_overview', __('Hide MyParcel dashboard widget', 'wp-agency-admin-toolkit'));
                    $this->checkbox($s, 'hide_yoast_overview', __('Hide Yoast SEO dashboard widget', 'wp-agency-admin-toolkit'));
                    $this->checkbox($s, 'hide_wp_rocket_notices', __('Hide WP Rocket client notices/widgets', 'wp-agency-admin-toolkit'));
                    $this->checkbox($s, 'hide_litespeed_notices', __('Hide LiteSpeed Cache client notices/widgets', 'wp-agency-admin-toolkit'));
                    $this->checkbox($s, 'hide_acf_admin_from_clients', __('Hide ACF field group admin from clients', 'wp-agency-admin-toolkit'));
                    ?>
                </section>

                <section class="aat-card">
                    <h2><?php esc_html_e('Detected plugins', 'wp-agency-admin-toolkit'); ?></h2>
                    <div class="aat-status-grid aat-integration-grid">
                        <?php foreach (Integrations::detected_plugins() as $plugin_name => $plugin_status): ?>
                            <div class="aat-status-row"><strong><?php echo esc_html($plugin_name); ?></strong><span><?php echo esc_html($plugin_status); ?></span></div>
                        <?php endforeach; ?>
                    </div>
                    <p class="description"><?php esc_html_e('The integration panel includes cleanup controls for Yoast SEO, WP Rocket, LiteSpeed Cache and Advanced Custom Fields while keeping existing Rank Math, MyParcel and Elementor controls.', 'wp-agency-admin-toolkit'); ?></p>
                </section>
            </div>
            <?php
        }, __('Save integration settings', 'wp-agency-admin-toolkit'));
    }
}
