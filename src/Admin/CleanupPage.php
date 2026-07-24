<?php
namespace Aurismore\AAT\Admin;

if (!defined('ABSPATH')) exit;

class CleanupPage extends Page {
    public function slug() {
        return 'wp-admin-toolkit-cleanup';
    }

    public function title() {
        return __('Cleanup & Restrictions', 'wp-agency-admin-toolkit');
    }

    public function screen() {
        return 'cleanup';
    }

    protected function content($s) {
        $opt = esc_attr(AAT_OPTION);
        $hidden_menu = implode("\n", (array) $s['hidden_menu']);
        $restricted_pages = implode("\n", (array) $s['restricted_pages']);
        $this->settings_form(function () use ($s, $opt, $hidden_menu, $restricted_pages) {
            ?>
            <div class="aat-grid">
                <section class="aat-card">
                    <h2><?php esc_html_e('Client safe mode', 'wp-agency-admin-toolkit'); ?></h2>
                    <?php
                    $this->checkbox($s, 'client_safe_mode', __('Client Safe Mode', 'wp-agency-admin-toolkit'));
                    $this->checkbox($s, 'hide_notices', __('Hide noisy admin notices', 'wp-agency-admin-toolkit'));
                    $this->checkbox($s, 'disable_admin_bar_for_clients', __('Disable top admin bar for clients', 'wp-agency-admin-toolkit'));
                    ?>
                    <p class="description"><?php esc_html_e('Client Safe Mode enforces the risky page restrictions below for affected roles.', 'wp-agency-admin-toolkit'); ?></p>
                </section>

                <section class="aat-card">
                    <h2><?php esc_html_e('Admin menu cleanup', 'wp-agency-admin-toolkit'); ?></h2>
                    <p><?php echo wp_kses(sprintf(/* translators: 1, 2, 3: example menu slugs in code tags. */ __('Enter one menu slug per line. These menus are removed for affected client roles. Examples: %1$s, %2$s, %3$s.', 'wp-agency-admin-toolkit'), '<code>edit-comments.php</code>', '<code>plugins.php</code>', '<code>woocommerce-marketing</code>'), ['code' => []]); ?></p>
                    <textarea name="<?php echo $opt; ?>[hidden_menu]" rows="9"><?php echo esc_textarea($hidden_menu); ?></textarea>
                </section>

                <section class="aat-card aat-wide">
                    <h2><?php esc_html_e('Risky page restrictions', 'wp-agency-admin-toolkit'); ?></h2>
                    <p><?php echo wp_kses(sprintf(/* translators: 1: pagenow in a code tag, 2: admin.php?page=… in a code tag. */ __('Enter one blocked admin page per line. Direct URL visits by affected roles will be blocked. Rules are matched exactly against %1$s and %2$s keys.', 'wp-agency-admin-toolkit'), '<code>pagenow</code>', '<code>admin.php?page=…</code>'), ['code' => []]); ?></p>
                    <textarea name="<?php echo $opt; ?>[restricted_pages]" rows="9"><?php echo esc_textarea($restricted_pages); ?></textarea>
                </section>
            </div>
            <?php
        }, __('Save cleanup settings', 'wp-agency-admin-toolkit'));
    }
}
