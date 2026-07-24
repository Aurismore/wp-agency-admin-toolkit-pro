<?php
namespace Aurismore\AAT\Admin;

if (!defined('ABSPATH')) exit;

class DashboardPage extends Page {
    public function slug() {
        return 'wp-admin-toolkit-dashboard';
    }

    public function title() {
        return 'Client Dashboard Settings';
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
                    <h2>Dashboard modules</h2>
                    <?php
                    $this->checkbox($s, 'enable_custom_dashboard', 'Custom client dashboard landing page');
                    $this->checkbox($s, 'enable_dashboard_widgets', 'Fallback WordPress dashboard widgets');
                    $this->checkbox($s, 'enable_site_snapshot', 'Client dashboard site snapshot card');
                    $this->checkbox($s, 'enable_recent_content', 'Client dashboard recent content card');
                    ?>
                </section>

                <section class="aat-card">
                    <h2>Dashboard content</h2>
                    <label>Dashboard title <input type="text" name="<?php echo $opt; ?>[dashboard_title]" value="<?php echo esc_attr($s['dashboard_title']); ?>"></label>
                    <label>Dashboard layout
                        <select name="<?php echo $opt; ?>[dashboard_layout]">
                            <option value="balanced" <?php selected($s['dashboard_layout'], 'balanced'); ?>>Balanced client handover</option>
                            <option value="commerce" <?php selected($s['dashboard_layout'], 'commerce'); ?>>WooCommerce focused</option>
                            <option value="content" <?php selected($s['dashboard_layout'], 'content'); ?>>Content editing focused</option>
                        </select>
                    </label>
                    <p class="description">This changes the visual emphasis of the client dashboard without changing permissions or restrictions.</p>
                    <label>Welcome message <textarea name="<?php echo $opt; ?>[welcome_message]" rows="4"><?php echo esc_textarea($s['welcome_message']); ?></textarea></label>
                </section>

                <section class="aat-card aat-wide">
                    <h2>Instruction boxes</h2>
                    <?php foreach ((array) $s['instructions'] as $key => $value): ?>
                        <label><?php echo esc_html(ucfirst($key)); ?> <textarea name="<?php echo $opt; ?>[instructions][<?php echo esc_attr($key); ?>]" rows="3"><?php echo esc_textarea($value); ?></textarea></label>
                    <?php endforeach; ?>
                </section>

                <section class="aat-card aat-wide">
                    <h2>Dashboard shortcuts</h2>
                    <div id="aat-shortcuts">
                        <?php foreach ((array) $s['shortcuts'] as $i => $shortcut): ?>
                            <div class="aat-shortcut-row">
                                <input placeholder="Label" name="<?php echo $opt; ?>[shortcuts][<?php echo esc_attr($i); ?>][label]" value="<?php echo esc_attr($shortcut['label']); ?>">
                                <input placeholder="URL" name="<?php echo $opt; ?>[shortcuts][<?php echo esc_attr($i); ?>][url]" value="<?php echo esc_attr($shortcut['url']); ?>">
                                <input placeholder="Capability" name="<?php echo $opt; ?>[shortcuts][<?php echo esc_attr($i); ?>][cap]" value="<?php echo esc_attr($shortcut['cap']); ?>">
                                <button type="button" class="button-link-delete aat-remove-shortcut" aria-label="Remove shortcut">&times;</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="button" id="aat-add-shortcut">Add shortcut</button>
                </section>
            </div>
            <?php
        }, 'Save dashboard settings');
    }
}
