<?php
namespace Aurismore\AAT\Admin;

if (!defined('ABSPATH')) exit;

class CleanupPage extends Page {
    public function slug() {
        return 'wp-admin-toolkit-cleanup';
    }

    public function title() {
        return 'Cleanup & Restrictions';
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
                    <h2>Client safe mode</h2>
                    <?php
                    $this->checkbox($s, 'client_safe_mode', 'Client Safe Mode');
                    $this->checkbox($s, 'hide_notices', 'Hide noisy admin notices');
                    $this->checkbox($s, 'disable_admin_bar_for_clients', 'Disable top admin bar for clients');
                    ?>
                    <p class="description">Client Safe Mode enforces the risky page restrictions below for affected roles.</p>
                </section>

                <section class="aat-card">
                    <h2>Admin menu cleanup</h2>
                    <p>Enter one menu slug per line. These menus are removed for affected client roles. Examples: <code>edit-comments.php</code>, <code>plugins.php</code>, <code>woocommerce-marketing</code>.</p>
                    <textarea name="<?php echo $opt; ?>[hidden_menu]" rows="9"><?php echo esc_textarea($hidden_menu); ?></textarea>
                </section>

                <section class="aat-card aat-wide">
                    <h2>Risky page restrictions</h2>
                    <p>Enter one blocked admin page per line. Direct URL visits by affected roles will be blocked. Rules are matched exactly against <code>pagenow</code> and <code>admin.php?page=…</code> keys.</p>
                    <textarea name="<?php echo $opt; ?>[restricted_pages]" rows="9"><?php echo esc_textarea($restricted_pages); ?></textarea>
                </section>
            </div>
            <?php
        }, 'Save cleanup settings');
    }
}
