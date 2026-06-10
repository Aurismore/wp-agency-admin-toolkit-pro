<?php
if (!defined('ABSPATH')) exit;

class AAT_Support {
    private $core;

    public function __construct($core) {
        $this->core = $core;
        add_action('admin_footer', [$this, 'admin_modal']);
        add_action('wp_ajax_aat_send_support', [$this, 'send_support']);
        add_action('admin_bar_menu', [$this, 'admin_bar_button'], 90);
    }

    public function admin_bar_button($wp_admin_bar) {
        if (!is_user_logged_in() || !current_user_can('read') || empty($this->core->settings['enable_floating_support'])) return;
        $wp_admin_bar->add_node([
            'id' => 'aat-support-request',
            'title' => esc_html($this->core->settings['support_button_label']),
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
            echo '<button type="button" class="aat-floating-support aat-open-support">' . esc_html($this->core->settings['support_button_label']) . '</button>';
        }

        $this->render_modal();
    }

    private function render_modal() {
        ?>
        <div id="aat-support-modal" class="aat-modal" aria-hidden="true">
            <div class="aat-modal-panel" role="dialog" aria-modal="true" aria-labelledby="aat-support-title">
                <button type="button" class="aat-modal-close" aria-label="Close">×</button>
                <h2 id="aat-support-title">Request Support</h2>
                <p>Describe what you need help with. The request will include the current page and basic site details.</p>
                <form id="aat-support-form">
                    <input type="hidden" name="action" value="aat_send_support">
                    <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('aat_support_nonce')); ?>">
                    <?php $categories = $this->get_categories(); if (!empty($categories)): ?>
                        <label>Category
                            <select name="category">
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo esc_attr($category); ?>"><?php echo esc_html($category); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    <?php endif; ?>
                    <label>Priority
                        <select name="priority">
                            <option value="Normal">Normal</option>
                            <option value="High">High</option>
                            <option value="Urgent">Urgent</option>
                        </select>
                    </label>
                    <label>Subject <input type="text" name="subject" maxlength="120" required></label>
                    <label>Message <textarea name="message" rows="6" maxlength="3000" required></textarea></label>
                    <label class="aat-consent"><input type="checkbox" name="include_diagnostics" value="1" checked> Include basic diagnostic details so support can respond faster.</label>
                    <button type="submit" class="button button-primary">Send Request</button>
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

    private function log_request($item) {
        if (empty($this->core->settings['enable_support_log'])) return;
        $log = get_option('aat_support_log', []);
        if (!is_array($log)) $log = [];
        $log[] = [
            'created_at' => current_time('mysql'),
            'subject' => sanitize_text_field($item['subject'] ?? ''),
            'category' => sanitize_text_field($item['category'] ?? ''),
            'priority' => sanitize_text_field($item['priority'] ?? ''),
            'message' => sanitize_textarea_field($item['message'] ?? ''),
            'user' => sanitize_text_field($item['user'] ?? ''),
            'page' => esc_url_raw($item['page'] ?? ''),
        ];
        $log = array_slice($log, -50);
        update_option('aat_support_log', $log, false);
    }

    private function get_request_ip() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        return preg_replace('/[^0-9a-fA-F:\.]/', '', (string) $ip);
    }


    private function webhook_url_is_allowed($url) {
        $url = esc_url_raw((string) $url, ['http', 'https']);
        if (!$url || !wp_http_validate_url($url)) return false;
        $host = wp_parse_url($url, PHP_URL_HOST);
        if (!$host) return false;
        $ip = gethostbyname($host);
        if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
            $private = !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
            if ($private) return false;
        }
        return $url;
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
                        ['name' => 'Category', 'value' => $category, 'inline' => true],
                        ['name' => 'Priority', 'value' => $priority, 'inline' => true],
                        ['name' => 'Site', 'value' => home_url('/'), 'inline' => false],
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
            wp_send_json_error(['message' => 'Invalid request method.'], 405);
        }

        check_ajax_referer('aat_support_nonce', 'nonce');

        $is_logged_in = is_user_logged_in();
        if (!$is_logged_in || !current_user_can('read')) {
            wp_send_json_error(['message' => 'Please log in to request support.'], 403);
        }

        $user = $is_logged_in ? wp_get_current_user() : null;
        $subject = sanitize_text_field(wp_unslash($_POST['subject'] ?? 'Website support request'));
        $subject = $subject ? substr($subject, 0, 120) : 'Website support request';
        $message = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));
        $message = substr($message, 0, 3000);
        $category = sanitize_text_field(wp_unslash($_POST['category'] ?? 'General website help'));
        $allowed_categories = $this->get_categories();
        if (!in_array($category, $allowed_categories, true)) $category = $allowed_categories[0] ?? 'General website help';
        $priority = sanitize_text_field(wp_unslash($_POST['priority'] ?? 'Normal'));
        if (!in_array($priority, ['Normal', 'High', 'Urgent'], true)) $priority = 'Normal';
        $include_diagnostics = !empty($_POST['include_diagnostics']);

        if ($message === '') {
            wp_send_json_error(['message' => 'Please add a message before sending.'], 400);
        }

        $s = $this->core->settings;
        $referer = wp_get_referer();
        $details = [
            'Site' => home_url('/'),
            'Page' => $referer ? esc_url_raw($referer) : (is_admin() ? admin_url() : wp_login_url()),
            'User' => $user->display_name . ' (' . $user->user_email . ')',
            'IP' => $this->get_request_ip(),
            'WordPress' => get_bloginfo('version'),
            'WooCommerce' => defined('WC_VERSION') ? WC_VERSION : 'Not active',
            'Theme' => wp_get_theme()->get('Name') . ' ' . wp_get_theme()->get('Version'),
            'PHP' => PHP_VERSION,
            'Browser' => sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? '')),
        ];
        $body = "Support request from " . home_url('/') . "\n\n";
        $body .= "Subject: " . $subject . "\n";
        $body .= "Category: " . $category . "\n";
        $body .= "Priority: " . $priority . "\n\n";
        $body .= "Message:\n" . $message . "\n\n";
        $body .= "Context:\n";
        if ($include_diagnostics) {
            foreach ($details as $key => $value) $body .= $key . ': ' . $value . "\n";
        } else {
            $body .= 'Diagnostics not included by requester.' . "\n";
        }

        $sent = false;
        if (!empty($s['support_email'])) {
            $headers = ['Reply-To: ' . $user->user_email];
            $sent = wp_mail($s['support_email'], '[' . get_bloginfo('name') . '] ' . $subject, $body, $headers);
        }
        if (!empty($s['support_webhook']) && ($webhook_url = $this->webhook_url_is_allowed($s['support_webhook']))) {
            $response = wp_safe_remote_post($webhook_url, [
                'timeout' => 8,
                'redirection' => 2,
                'headers' => ['Content-Type' => 'application/json'],
                'body' => wp_json_encode($this->webhook_payload($body, $subject, $category, $priority)),
            ]);
            if (!is_wp_error($response)) {
                $sent = true;
            }
        }
        if ($sent) {
            $this->log_request([
                'subject' => $subject,
                'category' => $category,
                'priority' => $priority,
                'message' => $message,
                'user' => $user->display_name . ' (' . $user->user_email . ')',
                'page' => $referer ? esc_url_raw($referer) : home_url('/'),
            ]);
            wp_send_json_success(['message' => 'Support request sent.']);
        }
        wp_send_json_error(['message' => 'The request could not be sent. Check the support email/webhook settings.'], 500);
    }
}
