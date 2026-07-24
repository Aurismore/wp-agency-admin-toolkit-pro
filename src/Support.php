<?php
namespace Aurismore\AAT;

if (!defined('ABSPATH')) exit;

class Support {
    private $core;

    public function __construct(Core $core) {
        $this->core = $core;
        add_action('admin_footer', [$this, 'admin_modal']);
        add_action('wp_ajax_aat_send_support', [$this, 'send_support']);
        add_action('admin_bar_menu', [$this, 'admin_bar_button'], 90);
    }

    private function support_button_label() {
        return Core::translated_setting($this->core->settings, 'support_button_label');
    }

    public function admin_bar_button($wp_admin_bar) {
        if (!is_user_logged_in() || !current_user_can('read') || empty($this->core->settings['enable_floating_support'])) return;
        $wp_admin_bar->add_node([
            'id' => 'aat-support-request',
            'title' => esc_html($this->support_button_label()),
            'href' => '#aat-support-modal',
            'meta' => ['class' => 'aat-open-support-node'],
        ]);
    }

    private function is_client_dashboard_screen() {
        if (isset($_GET['page']) && sanitize_key(wp_unslash($_GET['page'])) === 'wp-agency-admin-dashboard') {
            return true;
        }
        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
            if ($screen && $screen->id === 'toplevel_page_wp-agency-admin-dashboard') {
                return true;
            }
        }
        return false;
    }

    public function admin_modal() {
        if (!is_user_logged_in() || !current_user_can('read') || empty($this->core->settings['enable_floating_support'])) return;

        $show_floating_button = !$this->is_client_dashboard_screen();
        if ($show_floating_button) {
            echo '<button type="button" class="aat-floating-support aat-open-support">' . esc_html($this->support_button_label()) . '</button>';
        }
        $this->render_modal();
    }

    private function render_modal() {
        ?>
        <div id="aat-support-modal" class="aat-modal" aria-hidden="true">
            <div class="aat-modal-panel" role="dialog" aria-modal="true" aria-labelledby="aat-support-title">
                <button type="button" class="aat-modal-close" aria-label="<?php esc_attr_e('Close', 'wp-agency-admin-toolkit'); ?>">&times;</button>
                <h2 id="aat-support-title"><?php esc_html_e('Request Support', 'wp-agency-admin-toolkit'); ?></h2>
                <p><?php esc_html_e('Describe what you need help with. The request will include the current page and basic site details.', 'wp-agency-admin-toolkit'); ?></p>
                <form id="aat-support-form">
                    <input type="hidden" name="action" value="aat_send_support">
                    <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('aat_support_nonce')); ?>">
                    <?php $categories = $this->get_categories(); if (!empty($categories)): ?>
                        <label><?php esc_html_e('Category', 'wp-agency-admin-toolkit'); ?>
                            <select name="category">
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo esc_attr($category); ?>"><?php echo esc_html(Core::translated_support_category($category)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    <?php endif; ?>
                    <label><?php esc_html_e('Priority', 'wp-agency-admin-toolkit'); ?>
                        <select name="priority">
                            <?php foreach (Tickets::priority_labels() as $priority_value => $priority_label): ?>
                                <option value="<?php echo esc_attr($priority_value); ?>"><?php echo esc_html($priority_label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label><?php esc_html_e('Subject', 'wp-agency-admin-toolkit'); ?> <input type="text" name="subject" maxlength="120" required></label>
                    <label><?php esc_html_e('Message', 'wp-agency-admin-toolkit'); ?> <textarea name="message" rows="6" maxlength="3000" required></textarea></label>
                    <label class="aat-consent"><input type="checkbox" name="include_diagnostics" value="1" checked> <?php esc_html_e('Include basic diagnostic details so support can respond faster.', 'wp-agency-admin-toolkit'); ?></label>
                    <button type="submit" class="button button-primary"><?php esc_html_e('Send Request', 'wp-agency-admin-toolkit'); ?></button>
                    <span class="aat-support-status"></span>
                </form>
            </div>
        </div>
        <?php
    }

    private function get_categories() {
        $raw = $this->core->settings['support_categories'] ?? '';
        $lines = preg_split('/\r\n|\r|\n/', (string) $raw);
        $lines = array_values(array_filter(array_map('sanitize_text_field', (array) $lines)));
        return $lines ?: ['General website help'];
    }

    private function get_request_ip() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        return preg_replace('/[^0-9a-fA-F:\.]/', '', (string) $ip);
    }

    private function webhook_payload($body, $subject, $category, $priority) {
        $template = $this->core->settings['support_webhook_template'] ?? 'generic';
        if ($template === 'discord') {
            return [
                'content' => '**' . $subject . '**',
                'embeds' => [[
                    'title' => $subject,
                    'description' => $body,
                    'fields' => [
                        ['name' => __('Category', 'wp-agency-admin-toolkit'), 'value' => $category, 'inline' => true],
                        ['name' => __('Priority', 'wp-agency-admin-toolkit'), 'value' => $priority, 'inline' => true],
                        ['name' => __('Site', 'wp-agency-admin-toolkit'), 'value' => home_url('/'), 'inline' => false],
                    ],
                ]],
            ];
        }
        if ($template === 'slack') {
            return [
                'text' => '*' . $subject . '*',
                'attachments' => [[
                    'fallback' => $subject,
                    'text' => $body,
                    'fields' => [
                        ['title' => __('Category', 'wp-agency-admin-toolkit'), 'value' => $category, 'short' => true],
                        ['title' => __('Priority', 'wp-agency-admin-toolkit'), 'value' => $priority, 'short' => true],
                        ['title' => __('Site', 'wp-agency-admin-toolkit'), 'value' => home_url('/'), 'short' => false],
                    ],
                ]],
            ];
        }
        return [
            'text' => $body,
            'subject' => $subject,
            'category' => $category,
            'priority' => $priority,
            'site' => home_url('/'),
        ];
    }

    public function send_support() {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            wp_send_json_error(['message' => __('Invalid request method.', 'wp-agency-admin-toolkit')], 405);
        }

        check_ajax_referer('aat_support_nonce', 'nonce');

        if (!is_user_logged_in() || !current_user_can('read')) {
            wp_send_json_error(['message' => __('Please log in to request support.', 'wp-agency-admin-toolkit')], 403);
        }

        $user = wp_get_current_user();
        $subject = sanitize_text_field(wp_unslash($_POST['subject'] ?? ''));
        $subject = $subject ? substr($subject, 0, 120) : __('Website support request', 'wp-agency-admin-toolkit');
        $message = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));
        $message = substr($message, 0, 3000);
        $category = sanitize_text_field(wp_unslash($_POST['category'] ?? 'General website help'));
        $allowed_categories = $this->get_categories();
        if (!in_array($category, $allowed_categories, true)) $category = $allowed_categories[0] ?? 'General website help';
        $priority = sanitize_text_field(wp_unslash($_POST['priority'] ?? 'Normal'));
        if (!in_array($priority, ['Normal', 'High', 'Urgent'], true)) $priority = 'Normal';
        $include_diagnostics = !empty($_POST['include_diagnostics']);

        if ($message === '') {
            wp_send_json_error(['message' => __('Please add a message before sending.', 'wp-agency-admin-toolkit')], 400);
        }

        $s = $this->core->settings;
        $referer = wp_get_referer();

        // The ticket is stored before delivery is attempted so a request is
        // never lost when email/webhook delivery fails or is unconfigured.
        $ticket_id = 0;
        if (!empty($s['enable_support_log'])) {
            $ticket_id = Tickets::create([
                'user_id' => $user->ID,
                'user_name' => $user->display_name,
                'user_email' => $user->user_email,
                'subject' => $subject,
                'message' => $message,
                'category' => $category,
                'priority' => $priority,
                'page_url' => $referer ? esc_url_raw($referer) : home_url('/'),
                'diagnostics' => '', // filled below, inside the site-locale switch
            ]);
        }

        // Email and webhook go to the agency, so compose them in the site's
        // default language rather than the submitting user's admin language.
        $switched_locale = switch_to_locale(get_locale());

        $details = [
            __('Site', 'wp-agency-admin-toolkit') => home_url('/'),
            __('Page', 'wp-agency-admin-toolkit') => $referer ? esc_url_raw($referer) : (is_admin() ? admin_url() : wp_login_url()),
            __('User', 'wp-agency-admin-toolkit') => $user->display_name . ' (' . $user->user_email . ')',
            __('IP', 'wp-agency-admin-toolkit') => $this->get_request_ip(),
            __('WordPress', 'wp-agency-admin-toolkit') => get_bloginfo('version'),
            'WooCommerce' => defined('WC_VERSION') ? WC_VERSION : __('Not active', 'wp-agency-admin-toolkit'),
            __('Theme', 'wp-agency-admin-toolkit') => wp_get_theme()->get('Name') . ' ' . wp_get_theme()->get('Version'),
            'PHP' => PHP_VERSION,
            __('Browser', 'wp-agency-admin-toolkit') => sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? '')),
        ];

        if ($ticket_id && $include_diagnostics) {
            global $wpdb;
            $wpdb->update(Tickets::table_name(), ['diagnostics' => (string) wp_json_encode($details)], ['id' => $ticket_id], ['%s'], ['%d']);
        }

        /* translators: %s: site URL. */
        $body = sprintf(__('Support request from %s', 'wp-agency-admin-toolkit'), home_url('/')) . "\n\n";
        if ($ticket_id) {
            /* translators: %d: ticket number. */
            $body .= sprintf(__('Ticket: #%d', 'wp-agency-admin-toolkit'), $ticket_id) . "\n";
        }
        $body .= __('Subject', 'wp-agency-admin-toolkit') . ': ' . $subject . "\n";
        $body .= __('Category', 'wp-agency-admin-toolkit') . ': ' . $category . "\n";
        $body .= __('Priority', 'wp-agency-admin-toolkit') . ': ' . $priority . "\n\n";
        $body .= __('Message', 'wp-agency-admin-toolkit') . ":\n" . $message . "\n\n";
        $body .= __('Context', 'wp-agency-admin-toolkit') . ":\n";
        if ($include_diagnostics) {
            foreach ($details as $key => $value) $body .= $key . ': ' . $value . "\n";
        } else {
            $body .= __('Diagnostics not included by requester.', 'wp-agency-admin-toolkit') . "\n";
        }

        $sent = false;
        if (!empty($s['support_email'])) {
            $headers = ['Reply-To: ' . $user->user_email];
            $mail_subject = '[' . get_bloginfo('name') . ']' . ($ticket_id ? ' [#' . $ticket_id . ']' : '') . ' ' . $subject;
            $sent = wp_mail($s['support_email'], $mail_subject, $body, $headers);
        }
        if (!empty($s['support_webhook'])) {
            // wp_safe_remote_post applies WP's own host filter, blocking loopback
            // and private-range destinations through http_request_host_is_external /
            // wp_http_validate_url. We rely on that rather than maintaining a
            // duplicate gethostbyname/private-range check that gave a false sense
            // of security against DNS rebinding.
            $response = wp_safe_remote_post(esc_url_raw($s['support_webhook']), [
                'timeout' => 8,
                'redirection' => 2,
                'headers' => ['Content-Type' => 'application/json'],
                'body' => wp_json_encode($this->webhook_payload($body, $subject, $category, $priority)),
            ]);
            if (!is_wp_error($response)) {
                $sent = true;
            }
        }

        if ($switched_locale) {
            restore_previous_locale();
        }

        if ($sent) {
            wp_send_json_success(['message' => __('Support request sent.', 'wp-agency-admin-toolkit'), 'ticket_id' => $ticket_id]);
        }
        if ($ticket_id) {
            /* translators: %d: ticket number. */
            wp_send_json_success(['message' => sprintf(__('Support request saved as ticket #%d. Your team will pick it up from the ticket list.', 'wp-agency-admin-toolkit'), $ticket_id), 'ticket_id' => $ticket_id]);
        }
        wp_send_json_error(['message' => __('The request could not be sent. Check the support email/webhook settings.', 'wp-agency-admin-toolkit')], 500);
    }
}
