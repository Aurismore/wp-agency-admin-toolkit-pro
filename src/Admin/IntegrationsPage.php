<?php
namespace Aurismore\AAT\Admin;

use Aurismore\AAT\Integrations;

if (!defined('ABSPATH')) exit;

class IntegrationsPage extends Page {
    public function slug() {
        return 'wp-admin-toolkit-integrations';
    }

    public function title() {
        return 'Plugin Integrations';
    }

    public function screen() {
        return 'integrations';
    }

    protected function content($s) {
        $this->settings_form(function () use ($s) {
            ?>
            <div class="aat-grid">
                <section class="aat-card">
                    <h2>Integration cleanup</h2>
                    <p>These settings only affect selected client roles after login.</p>
                    <?php
                    $this->checkbox($s, 'hide_elementor_settings', 'Hide Elementor settings from clients');
                    $this->checkbox($s, 'hide_rank_math_overview', 'Hide Rank Math Overview dashboard widget');
                    $this->checkbox($s, 'hide_myparcel_overview', 'Hide MyParcel dashboard widget');
                    $this->checkbox($s, 'hide_yoast_overview', 'Hide Yoast SEO dashboard widget');
                    $this->checkbox($s, 'hide_wp_rocket_notices', 'Hide WP Rocket client notices/widgets');
                    $this->checkbox($s, 'hide_litespeed_notices', 'Hide LiteSpeed Cache client notices/widgets');
                    $this->checkbox($s, 'hide_acf_admin_from_clients', 'Hide ACF field group admin from clients');
                    ?>
                </section>

                <section class="aat-card">
                    <h2>Detected plugins</h2>
                    <div class="aat-status-grid aat-integration-grid">
                        <?php foreach (Integrations::detected_plugins() as $plugin_name => $plugin_status): ?>
                            <div class="aat-status-row"><strong><?php echo esc_html($plugin_name); ?></strong><span><?php echo esc_html($plugin_status); ?></span></div>
                        <?php endforeach; ?>
                    </div>
                    <p class="description">The integration panel includes cleanup controls for Yoast SEO, WP Rocket, LiteSpeed Cache and Advanced Custom Fields while keeping existing Rank Math, MyParcel and Elementor controls.</p>
                </section>
            </div>
            <?php
        }, 'Save integration settings');
    }
}
