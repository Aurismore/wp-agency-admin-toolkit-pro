<?php
namespace Aurismore\AAT\Admin;

if (!defined('ABSPATH')) exit;

class SupportPage extends Page {
    public function slug() {
        return 'wp-admin-toolkit-support';
    }

    public function title() {
        return 'Support Settings';
    }

    public function screen() {
        return 'support';
    }

    protected function content($s) {
        $opt = esc_attr(AAT_OPTION);
        $this->settings_form(function () use ($s, $opt) {
            ?>
            <div class="aat-grid">
                <section class="aat-card">
                    <h2>Support contact</h2>
                    <label>Support email <input type="email" name="<?php echo $opt; ?>[support_email]" value="<?php echo esc_attr($s['support_email']); ?>"></label>
                    <label>External support URL <input type="url" name="<?php echo $opt; ?>[support_url]" value="<?php echo esc_url($s['support_url']); ?>"></label>
                    <label>Webhook URL <input type="url" name="<?php echo $opt; ?>[support_webhook]" value="<?php echo esc_url($s['support_webhook']); ?>"></label>
                    <label>Support button label <input type="text" name="<?php echo $opt; ?>[support_button_label]" value="<?php echo esc_attr($s['support_button_label']); ?>"></label>
                </section>

                <section class="aat-card">
                    <h2>Support modules</h2>
                    <?php
                    $this->checkbox($s, 'enable_floating_support', 'Floating request support button');
                    $this->checkbox($s, 'enable_support_log', 'Store support requests as tickets in the database');
                    ?>
                    <p class="description">Tickets are saved before email/webhook delivery is attempted, so a request is kept even when delivery fails. Manage them on the <a href="<?php echo esc_url(admin_url('admin.php?page=wp-admin-toolkit-tickets')); ?>">Tickets</a> page.</p>
                </section>

                <section class="aat-card aat-wide">
                    <h2>Request form</h2>
                    <p>Support requests can be sent by email, sent to a webhook and stored as tickets in the database.</p>
                    <label>Support categories <textarea name="<?php echo $opt; ?>[support_categories]" rows="6"><?php echo esc_textarea($s['support_categories'] ?? ''); ?></textarea></label>
                    <p class="description">One category per line. These appear in the Request Support modal.</p>
                    <label>Webhook format
                        <select name="<?php echo $opt; ?>[support_webhook_template]">
                            <option value="generic" <?php selected($s['support_webhook_template'] ?? 'generic', 'generic'); ?>>Generic JSON</option>
                            <option value="slack" <?php selected($s['support_webhook_template'] ?? 'generic', 'slack'); ?>>Slack-style payload</option>
                            <option value="discord" <?php selected($s['support_webhook_template'] ?? 'generic', 'discord'); ?>>Discord-style payload</option>
                        </select>
                    </label>
                    <p class="description">Generic JSON works with most webhook services. Slack and Discord use a cleaner text/embed structure.</p>
                </section>
            </div>
            <?php
        }, 'Save support settings');
    }
}
