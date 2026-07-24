<?php
namespace Aurismore\AAT\Admin;

if (!defined('ABSPATH')) exit;

class SupportPage extends Page {
    public function slug() {
        return 'wp-admin-toolkit-support';
    }

    public function title() {
        return __('Support Settings', 'wp-agency-admin-toolkit');
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
                    <h2><?php esc_html_e('Support contact', 'wp-agency-admin-toolkit'); ?></h2>
                    <label><?php esc_html_e('Support email', 'wp-agency-admin-toolkit'); ?> <input type="email" name="<?php echo $opt; ?>[support_email]" value="<?php echo esc_attr($s['support_email']); ?>"></label>
                    <label><?php esc_html_e('External support URL', 'wp-agency-admin-toolkit'); ?> <input type="url" name="<?php echo $opt; ?>[support_url]" value="<?php echo esc_url($s['support_url']); ?>"></label>
                    <label><?php esc_html_e('Webhook URL', 'wp-agency-admin-toolkit'); ?> <input type="url" name="<?php echo $opt; ?>[support_webhook]" value="<?php echo esc_url($s['support_webhook']); ?>"></label>
                    <label><?php esc_html_e('Support button label', 'wp-agency-admin-toolkit'); ?> <input type="text" name="<?php echo $opt; ?>[support_button_label]" value="<?php echo esc_attr($s['support_button_label']); ?>"></label>
                </section>

                <section class="aat-card">
                    <h2><?php esc_html_e('Support modules', 'wp-agency-admin-toolkit'); ?></h2>
                    <?php
                    $this->checkbox($s, 'enable_floating_support', __('Floating request support button', 'wp-agency-admin-toolkit'));
                    $this->checkbox($s, 'enable_support_log', __('Store support requests as tickets in the database', 'wp-agency-admin-toolkit'));
                    ?>
                    <p class="description"><?php
                        echo wp_kses(
                            sprintf(
                                /* translators: %s: link to the Tickets page. */
                                __('Tickets are saved before email/webhook delivery is attempted, so a request is kept even when delivery fails. Manage them on the %s page.', 'wp-agency-admin-toolkit'),
                                '<a href="' . esc_url(admin_url('admin.php?page=wp-admin-toolkit-tickets')) . '">' . esc_html__('Tickets', 'wp-agency-admin-toolkit') . '</a>'
                            ),
                            ['a' => ['href' => []]]
                        );
                    ?></p>
                </section>

                <section class="aat-card aat-wide">
                    <h2><?php esc_html_e('Request form', 'wp-agency-admin-toolkit'); ?></h2>
                    <p><?php esc_html_e('Support requests can be sent by email, sent to a webhook and stored as tickets in the database.', 'wp-agency-admin-toolkit'); ?></p>
                    <label><?php esc_html_e('Support categories', 'wp-agency-admin-toolkit'); ?> <textarea name="<?php echo $opt; ?>[support_categories]" rows="6"><?php echo esc_textarea($s['support_categories'] ?? ''); ?></textarea></label>
                    <p class="description"><?php esc_html_e('One category per line. These appear in the Request Support modal. The shipped default categories translate automatically into each user\'s admin language.', 'wp-agency-admin-toolkit'); ?></p>
                    <label><?php esc_html_e('Webhook format', 'wp-agency-admin-toolkit'); ?>
                        <select name="<?php echo $opt; ?>[support_webhook_template]">
                            <option value="generic" <?php selected($s['support_webhook_template'] ?? 'generic', 'generic'); ?>><?php esc_html_e('Generic JSON', 'wp-agency-admin-toolkit'); ?></option>
                            <option value="slack" <?php selected($s['support_webhook_template'] ?? 'generic', 'slack'); ?>><?php esc_html_e('Slack-style payload', 'wp-agency-admin-toolkit'); ?></option>
                            <option value="discord" <?php selected($s['support_webhook_template'] ?? 'generic', 'discord'); ?>><?php esc_html_e('Discord-style payload', 'wp-agency-admin-toolkit'); ?></option>
                        </select>
                    </label>
                    <p class="description"><?php esc_html_e('Generic JSON works with most webhook services. Slack and Discord use a cleaner text/embed structure.', 'wp-agency-admin-toolkit'); ?></p>
                </section>
            </div>
            <?php
        }, __('Save support settings', 'wp-agency-admin-toolkit'));
    }
}
