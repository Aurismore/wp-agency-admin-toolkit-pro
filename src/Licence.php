<?php
namespace Aurismore\AAT;

if (!defined('ABSPATH')) exit;

class Licence {
    private $core;
    private $cache_key = 'aat_remote_update_info';
    const DAILY_CRON_HOOK = 'aat_licence_daily_check';

    public function __construct(Core $core) {
        $this->core = $core;
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
        add_action('admin_post_aat_clear_update_cache', [$this, 'clear_update_cache']);
        add_action('admin_post_aat_check_licence_now', [$this, 'check_licence_now']);

        // Daily background revalidation against the WCTLM /check endpoint so an
        // expired or remotely-deactivated licence is reflected in the admin UI
        // without waiting for the customer to revisit the settings panel.
        add_action(self::DAILY_CRON_HOOK, [$this, 'cron_revalidate']);
        if (!wp_next_scheduled(self::DAILY_CRON_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::DAILY_CRON_HOOK);
        }
    }

    public function clear_update_cache() {
        if (!current_user_can(AAT_CAP) || !check_admin_referer('aat_clear_update_cache')) {
            wp_die('Access denied.');
        }
        delete_site_transient($this->cache_key);
        wp_clean_plugins_cache(true);
        wp_safe_redirect(admin_url('options-general.php?page=wp-agency-admin-toolkit&aat_update_cache_cleared=1#aat-license'));
        exit;
    }

    public function check_licence_now() {
        if (!current_user_can(AAT_CAP) || !check_admin_referer('aat_check_licence_now')) {
            wp_die('Access denied.');
        }
        $settings = Core::get_settings();
        $licence_key = $settings['licence_key'] ?? '';
        if ($licence_key !== '') {
            $response = self::remote_request('check', $licence_key, 'POST');
            $settings = self::settings_from_licence_response($settings, $licence_key, $response);
            Core::update_settings($settings);
        }
        delete_site_transient($this->cache_key);
        wp_safe_redirect(admin_url('options-general.php?page=wp-agency-admin-toolkit&aat_licence_checked=1#aat-license'));
        exit;
    }

    public function cron_revalidate() {
        $settings = Core::get_settings();
        $licence_key = $settings['licence_key'] ?? '';
        if ($licence_key === '') return;
        $response = self::remote_request('check', $licence_key, 'POST');
        $settings = self::settings_from_licence_response($settings, $licence_key, $response);
        Core::update_settings($settings);
    }

    public static function licence_server() {
        $server = defined('AAT_LICENCE_SERVER')
            ? AAT_LICENCE_SERVER
            : (defined('AAT_LICENSE_SERVER') ? AAT_LICENSE_SERVER : 'https://wpclienttools.com');
        return untrailingslashit(apply_filters('aat_licence_server', $server));
    }

    public static function product_slug() {
        $slug = defined('AAT_PRODUCT_SLUG') ? AAT_PRODUCT_SLUG : 'wp-agency-admin-toolkit-pro';
        return sanitize_title(apply_filters('aat_product_slug', $slug));
    }

    public static function remote_request($endpoint, $licence_key, $method = 'POST') {
        $endpoint = sanitize_key($endpoint);
        $licence_key = sanitize_text_field($licence_key);
        if (!$licence_key) {
            return ['success' => false, 'message' => 'Please enter a licence key.'];
        }

        global $wp_version;
        $url = self::licence_server() . '/wp-json/wctlm/v1/' . $endpoint;
        $body = [
            'licence_key' => $licence_key,
            'product_slug' => self::product_slug(),
            'site_url' => home_url('/'),
            'version' => AAT_VERSION,
            // Telemetry the WCTLM server records on the activation row. Helpful for
            // the agency when triaging "doesn't update on this client" tickets.
            'wp_version' => isset($wp_version) ? (string) $wp_version : get_bloginfo('version'),
            'php_version' => PHP_VERSION,
        ];
        $args = [
            'timeout' => 20,
            'redirection' => 3,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'WP Agency Admin Toolkit Pro/' . AAT_VERSION . '; ' . home_url('/'),
            ],
            'body' => $body,
        ];

        $response = strtoupper($method) === 'GET'
            ? wp_remote_get(add_query_arg($body, $url), $args)
            : wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            return ['success' => false, 'message' => $response->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body_text = wp_remote_retrieve_body($response);
        $decoded = json_decode($body_text, true);
        if (!is_array($decoded)) {
            return ['success' => false, 'message' => 'Invalid response from WP Client Tools.'];
        }
        if ($code < 200 || $code >= 300) {
            $decoded['success'] = false;
            // The server now returns {success:false, code, message} on errors.
            // Surface the code in the message when no human-readable one came back.
            if (empty($decoded['message'])) {
                $decoded['message'] = !empty($decoded['code'])
                    ? 'WP Client Tools error: ' . $decoded['code']
                    : ('WP Client Tools returned HTTP ' . (int) $code . '.');
            }
        }
        return $decoded;
    }

    /**
     * Map a WCTLM activate/check response onto the plugin settings array.
     *
     * Used by both the manual save_license flow and the daily revalidation
     * cron. Deactivate responses don't include a licence block — that path
     * is handled separately in Admin::save_license().
     */
    public static function settings_from_licence_response($current_settings, $licence_key, $response) {
        $settings = is_array($current_settings) ? $current_settings : Core::get_settings();
        $settings['licence_key'] = sanitize_text_field($licence_key);
        $settings['licence_status'] = !empty($response['success']) ? 'active' : 'inactive';
        $settings['licence_message'] = sanitize_text_field($response['message'] ?? '');
        $settings['licence_checked_at'] = gmdate('c');

        if (!empty($response['licence']) && is_array($response['licence'])) {
            $licence = $response['licence'];
            $settings['licence_expires_at'] = sanitize_text_field($licence['expires_at'] ?? '');
            $settings['licence_activations_used'] = absint($licence['activations_used'] ?? 0);
            $settings['licence_activation_limit'] = absint($licence['activation_limit'] ?? 0);
        }
        return $settings;
    }

    private function has_active_licence() {
        return !empty($this->core->settings['licence_key']) && (($this->core->settings['licence_status'] ?? '') === 'active');
    }

    public function check_for_update($transient) {
        if (empty($transient) || !is_object($transient)) return $transient;
        if (!$this->has_active_licence()) return $transient;

        $remote = $this->get_remote_info();
        if (!$remote || empty($remote['version']) || empty($remote['download_url'])) return $transient;

        // Always recompute against the installed plugin version. We used to
        // trust $remote['update_available'] from the server, but the remote
        // payload is cached for 6 hours and that flag is computed against
        // whatever AAT_VERSION the server saw on the last /update-check
        // round-trip. After a successful upgrade the cache still says
        // "update available -> 1.24" until it expires, so the plugin offers
        // an upgrade to a version it's already running. version_compare with
        // the current AAT_VERSION is the source of truth.
        $update_available = version_compare(AAT_VERSION, $remote['version'], '<');

        if ($update_available) {
            $plugin_file = plugin_basename(AAT_FILE);
            $item = new \stdClass();
            $item->id = $remote['slug'] ?? self::product_slug();
            $item->slug = $remote['slug'] ?? self::product_slug();
            $item->plugin = $plugin_file;
            $item->new_version = sanitize_text_field($remote['version']);
            $item->url = esc_url_raw($remote['homepage'] ?? 'https://wpclienttools.com/wp-agency-admin-toolkit-pro');
            $item->package = esc_url_raw($remote['download_url']);
            if (!empty($remote['tested'])) $item->tested = sanitize_text_field($remote['tested']);
            if (!empty($remote['requires'])) $item->requires = sanitize_text_field($remote['requires']);
            if (!empty($remote['requires_php'])) $item->requires_php = sanitize_text_field($remote['requires_php']);
            $transient->response[$plugin_file] = $item;
        }
        return $transient;
    }

    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information' || empty($args->slug) || $args->slug !== self::product_slug()) {
            return $result;
        }
        if (!$this->has_active_licence()) return $result;

        $remote = $this->get_remote_info();
        if (!$remote) return $result;

        $info = new \stdClass();
        $info->name = sanitize_text_field($remote['name'] ?? 'WP Agency Admin Toolkit Pro');
        $info->slug = sanitize_key($remote['slug'] ?? self::product_slug());
        $info->version = sanitize_text_field($remote['version'] ?? AAT_VERSION);
        $info->author = '<a href="https://creativedigitalmedia.nl" target="_blank" rel="noopener noreferrer">Creative Digital Media</a>';
        $info->homepage = esc_url_raw($remote['homepage'] ?? 'https://wpclienttools.com/wp-agency-admin-toolkit-pro');
        $info->requires = sanitize_text_field($remote['requires'] ?? '6.0');
        $info->tested = sanitize_text_field($remote['tested'] ?? '');
        $info->requires_php = sanitize_text_field($remote['requires_php'] ?? '7.4');
        $info->download_link = esc_url_raw($remote['download_url'] ?? '');
        $info->sections = $this->sanitize_sections($remote['sections'] ?? []);
        if (empty($info->sections)) {
            $info->sections = [
                'description' => 'WP Agency Admin Toolkit Pro updates are delivered through WP Client Tools after licence validation.',
                'changelog' => !empty($remote['changelog']) ? wp_kses_post($remote['changelog']) : 'No changelog supplied.',
            ];
        }
        return $info;
    }

    public function get_remote_info($force = false) {
        if (!$this->has_active_licence()) {
            return false;
        }
        if (!$force) {
            $cached = get_site_transient($this->cache_key);
            // Treat a cache that was written against a different AAT_VERSION
            // as stale. The cached payload includes the server-computed
            // current_version + update_available + download_url + SHA, all
            // bound to whatever version was running at cache-write time.
            // After an in-place upgrade those are misleading until the
            // transient expires (up to 6 hours), so force a fresh check
            // whenever the installed version moved on.
            if (is_array($cached) && isset($cached['current_version']) && (string) $cached['current_version'] === AAT_VERSION) {
                return $cached;
            }
        }

        $response = self::remote_request('update-check', $this->core->settings['licence_key']);
        if (empty($response['success'])) {
            $this->cache_error($response['message'] ?? 'Update check failed.');
            return false;
        }

        // The server returns both `download_url` and `package` (mirror), and a
        // package_available flag that's authoritative for whether a real ZIP
        // exists. We treat package_available === false as "no update yet".
        $download_url = '';
        foreach (['download_url', 'package'] as $field) {
            if (!empty($response[$field])) {
                $download_url = esc_url_raw($response[$field], ['http', 'https']);
                break;
            }
        }

        $package_available = isset($response['package_available'])
            ? (bool) $response['package_available']
            : !empty($download_url);

        $clean = [
            'name' => 'WP Agency Admin Toolkit Pro',
            'slug' => sanitize_title($response['product_slug'] ?? self::product_slug()),
            'version' => sanitize_text_field($response['latest_version'] ?? $response['new_version'] ?? AAT_VERSION),
            'current_version' => sanitize_text_field($response['current_version'] ?? AAT_VERSION),
            'channel' => sanitize_key($response['channel'] ?? 'stable'),
            'download_url' => $download_url,
            'homepage' => 'https://wpclienttools.com/wp-agency-admin-toolkit-pro',
            'requires' => sanitize_text_field($response['requires'] ?? '6.0'),
            'requires_php' => sanitize_text_field($response['requires_php'] ?? '7.4'),
            'tested' => sanitize_text_field($response['tested'] ?? ''),
            'update_available' => !empty($response['update_available']),
            'update_message' => sanitize_text_field($response['update_message'] ?? ''),
            'package_available' => $package_available,
            'package_status' => sanitize_text_field($response['package_status'] ?? ''),
            'package_message' => sanitize_text_field($response['package_message'] ?? ''),
            'package_source' => sanitize_text_field($response['package_source'] ?? ''),
            // Integrity metadata. Surfaced in System Status so an operator can
            // verify what the server claims about the ZIP before installing.
            'package_sha256' => preg_replace('/[^a-f0-9]/i', '', (string) ($response['package_sha256'] ?? '')),
            'package_signature_ed25519' => preg_replace('/[^A-Za-z0-9+\/=]/', '', (string) ($response['package_signature_ed25519'] ?? '')),
            'package_size_bytes' => absint($response['package_size_bytes'] ?? 0),
            'sections' => [
                'description' => 'WP Agency Admin Toolkit Pro updates are delivered through WP Client Tools after licence validation.',
                'changelog' => !empty($response['changelog']) ? wp_kses_post($response['changelog']) : 'No changelog supplied.',
            ],
            'changelog' => !empty($response['changelog']) ? wp_kses_post($response['changelog']) : '',
            'checked_at' => gmdate('c'),
            'source' => 'wpclienttools',
        ];

        if (empty($clean['version']) || empty($clean['download_url']) || !$clean['package_available']) {
            $this->cache_error($clean['package_message'] ?: ($clean['update_message'] ?: 'WP Client Tools responded but no downloadable package was available.'));
            return false;
        }

        set_site_transient($this->cache_key, $clean, 6 * HOUR_IN_SECONDS);
        return $clean;
    }

    private function cache_error($message) {
        $clean = [
            'checked_at' => gmdate('c'),
            'source' => 'wpclienttools',
            'error' => sanitize_text_field($message),
        ];
        set_site_transient($this->cache_key, $clean, HOUR_IN_SECONDS);
    }

    private function sanitize_sections($sections) {
        $clean = [];
        if (!is_array($sections)) return $clean;
        foreach ($sections as $key => $value) {
            $section_key = sanitize_key($key);
            if (!$section_key) continue;
            $clean[$section_key] = wp_kses_post((string) $value);
        }
        return $clean;
    }
}
