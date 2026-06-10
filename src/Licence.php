<?php
namespace Aurismore\AAT;

if (!defined('ABSPATH')) exit;

class Licence {
    private $core;
    private $cache_key = 'aat_remote_update_info';

    public function __construct(Core $core) {
        $this->core = $core;
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
        add_action('admin_post_aat_clear_update_cache', [$this, 'clear_update_cache']);
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

        $url = self::licence_server() . '/wp-json/wctlm/v1/' . $endpoint;
        $args = [
            'timeout' => 20,
            'redirection' => 3,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'WP Agency Admin Toolkit Pro/' . AAT_VERSION . '; ' . home_url('/'),
            ],
            'body' => [
                'licence_key' => $licence_key,
                'product_slug' => self::product_slug(),
                'site_url' => home_url('/'),
                'version' => AAT_VERSION,
            ],
        ];

        $response = strtoupper($method) === 'GET' ? wp_remote_get(add_query_arg($args['body'], $url), $args) : wp_remote_post($url, $args);
        if (is_wp_error($response)) {
            return ['success' => false, 'message' => $response->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($body)) {
            return ['success' => false, 'message' => 'Invalid response from WP Client Tools.'];
        }
        if ($code < 200 || $code >= 300) {
            $body['success'] = false;
            $body['message'] = $body['message'] ?? ('WP Client Tools returned HTTP ' . (int) $code . '.');
        }
        return $body;
    }

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

        if (version_compare(AAT_VERSION, $remote['version'], '<')) {
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
            if (is_array($cached)) return $cached;
        }

        $response = self::remote_request('update-check', $this->core->settings['licence_key']);
        if (empty($response['success'])) {
            $this->cache_error($response['message'] ?? 'Update check failed.');
            return false;
        }

        $clean = [
            'name' => 'WP Agency Admin Toolkit Pro',
            'slug' => self::product_slug(),
            'version' => sanitize_text_field($response['latest_version'] ?? AAT_VERSION),
            'download_url' => esc_url_raw($response['download_url'] ?? '', ['http', 'https']),
            'homepage' => 'https://wpclienttools.com/wp-agency-admin-toolkit-pro',
            'requires' => sanitize_text_field($response['requires'] ?? '6.0'),
            'requires_php' => sanitize_text_field($response['requires_php'] ?? '7.4'),
            'tested' => sanitize_text_field($response['tested'] ?? ''),
            'sections' => [
                'description' => 'WP Agency Admin Toolkit Pro updates are delivered through WP Client Tools after licence validation.',
                'changelog' => !empty($response['changelog']) ? wp_kses_post($response['changelog']) : 'No changelog supplied.',
            ],
            'changelog' => !empty($response['changelog']) ? wp_kses_post($response['changelog']) : '',
            'checked_at' => gmdate('c'),
            'source' => 'wpclienttools',
        ];

        if (empty($clean['version']) || empty($clean['download_url'])) {
            $this->cache_error('WP Client Tools responded but no downloadable package was available.');
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
