<?php
namespace Aurismore\AAT\Admin;

use Aurismore\AAT\Core;

if (!defined('ABSPATH')) exit;

class DashboardPage extends Page {
    public function slug() {
        return 'wp-admin-toolkit-dashboard';
    }

    public function title() {
        return __('Client Dashboard Settings', 'wp-agency-admin-toolkit');
    }

    public function screen() {
        return 'dashboard';
    }

    protected function content($s) {
        $opt = esc_attr(AAT_OPTION);
        $this->settings_form(function () use ($s, $opt) {
            ?>
            <div class="aat-grid">
                <section class="aat-card">
                    <h2><?php esc_html_e('Dashboard modules', 'wp-agency-admin-toolkit'); ?></h2>
                    <?php
                    $this->checkbox($s, 'enable_custom_dashboard', __('Custom client dashboard landing page', 'wp-agency-admin-toolkit'));
                    $this->checkbox($s, 'enable_dashboard_widgets', __('Fallback WordPress dashboard widgets', 'wp-agency-admin-toolkit'));
                    $this->checkbox($s, 'enable_site_snapshot', __('Client dashboard site snapshot card', 'wp-agency-admin-toolkit'));
                    $this->checkbox($s, 'enable_recent_content', __('Client dashboard recent content card', 'wp-agency-admin-toolkit'));
                    ?>
                </section>

                <section class="aat-card">
                    <h2><?php esc_html_e('Dashboard content', 'wp-agency-admin-toolkit'); ?></h2>
                    <label><?php esc_html_e('Dashboard title', 'wp-agency-admin-toolkit'); ?> <input type="text" name="<?php echo $opt; ?>[dashboard_title]" value="<?php echo esc_attr($s['dashboard_title']); ?>"></label>
                    <label><?php esc_html_e('Dashboard layout', 'wp-agency-admin-toolkit'); ?>
                        <select name="<?php echo $opt; ?>[dashboard_layout]">
                            <option value="balanced" <?php selected($s['dashboard_layout'], 'balanced'); ?>><?php esc_html_e('Balanced client handover', 'wp-agency-admin-toolkit'); ?></option>
                            <option value="commerce" <?php selected($s['dashboard_layout'], 'commerce'); ?>><?php esc_html_e('WooCommerce focused', 'wp-agency-admin-toolkit'); ?></option>
                            <option value="content" <?php selected($s['dashboard_layout'], 'content'); ?>><?php esc_html_e('Content editing focused', 'wp-agency-admin-toolkit'); ?></option>
                        </select>
                    </label>
                    <p class="description"><?php esc_html_e('This changes the visual emphasis of the client dashboard without changing permissions or restrictions.', 'wp-agency-admin-toolkit'); ?></p>
                    <label><?php esc_html_e('Welcome message', 'wp-agency-admin-toolkit'); ?> <textarea name="<?php echo $opt; ?>[welcome_message]" rows="4"><?php echo esc_textarea($s['welcome_message']); ?></textarea></label>
                    <p class="description"><?php esc_html_e('Content you type here is shown exactly as written. Fields left at their shipped default translate automatically into each user\'s admin language.', 'wp-agency-admin-toolkit'); ?></p>
                </section>

                <section class="aat-card aat-wide">
                    <h2><?php esc_html_e('Instruction boxes', 'wp-agency-admin-toolkit'); ?></h2>
                    <?php foreach ((array) $s['instructions'] as $key => $value): ?>
                        <label><?php echo esc_html(Core::instruction_heading($key)); ?> <textarea name="<?php echo $opt; ?>[instructions][<?php echo esc_attr($key); ?>]" rows="3"><?php echo esc_textarea($value); ?></textarea></label>
                    <?php endforeach; ?>
                </section>

                <section class="aat-card aat-wide">
                    <h2><?php esc_html_e('Dashboard shortcuts', 'wp-agency-admin-toolkit'); ?></h2>
                    <div id="aat-shortcuts">
                        <?php foreach ((array) $s['shortcuts'] as $i => $shortcut): ?>
                            <div class="aat-shortcut-row">
                                <input placeholder="<?php esc_attr_e('Label', 'wp-agency-admin-toolkit'); ?>" name="<?php echo $opt; ?>[shortcuts][<?php echo esc_attr($i); ?>][label]" value="<?php echo esc_attr($shortcut['label']); ?>">
                                <input placeholder="<?php esc_attr_e('URL', 'wp-agency-admin-toolkit'); ?>" name="<?php echo $opt; ?>[shortcuts][<?php echo esc_attr($i); ?>][url]" value="<?php echo esc_attr($shortcut['url']); ?>">
                                <input placeholder="<?php esc_attr_e('Capability', 'wp-agency-admin-toolkit'); ?>" name="<?php echo $opt; ?>[shortcuts][<?php echo esc_attr($i); ?>][cap]" value="<?php echo esc_attr($shortcut['cap']); ?>">
                                <button type="button" class="button-link-delete aat-remove-shortcut" aria-label="<?php esc_attr_e('Remove shortcut', 'wp-agency-admin-toolkit'); ?>">&times;</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="button" id="aat-add-shortcut"><?php esc_html_e('Add shortcut', 'wp-agency-admin-toolkit'); ?></button>
                </section>
            </div>
            <?php
        }, __('Save dashboard settings', 'wp-agency-admin-toolkit'));
    }
}
