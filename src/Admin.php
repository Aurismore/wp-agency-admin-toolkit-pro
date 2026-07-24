<?php
namespace Aurismore\AAT;

if (!defined('ABSPATH')) exit;

class Admin {
    private $core;
    /** @var Admin\Page[] */
    private $pages = [];

    const MENU_SLUG = 'wp-admin-toolkit';
    const LEGACY_PAGE_SLUG = 'wp-agency-admin-toolkit';

    public function __construct(Core $core) {
        $this->core = $core;
        $this->pages = [
            new Admin\GeneralPage($core),
            new Admin\DashboardPage($core),
            new Admin\BrandingPage($core),
            new Admin\CleanupPage($core),
            new Admin\SupportPage($core),
            new Admin\TicketsPage($core),
            new Admin\IntegrationsPage($core),
            new Admin\ToolsPage($core),
            new Admin\LicencePage($core),
        ];
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_init', [$this, 'maybe_redirect_legacy_page']);
        add_action('admin_enqueue_scripts', [$this, 'assets']);
        add_action('admin_post_aat_export', [$this, 'export']);
        add_action('admin_post_aat_import', [$this, 'import']);
        add_action('admin_post_aat_reset_defaults', [$this, 'reset_defaults']);
        add_action('admin_post_aat_save_license', [$this, 'save_license']);
        add_filter('plugin_action_links_' . plugin_basename(AAT_FILE), [$this, 'plugin_links']);
    }

    /**
     * Slug => nav label for every toolkit page, in menu order. Used for the
     * submenu labels and the cross-page tab navigation.
     */
    public static function pages_nav() {
        return [
            'wp-admin-toolkit' => 'General',
            'wp-admin-toolkit-dashboard' => 'Client Dashboard',
            'wp-admin-toolkit-branding' => 'Branding',
            'wp-admin-toolkit-cleanup' => 'Cleanup',
            'wp-admin-toolkit-support' => 'Support',
            'wp-admin-toolkit-tickets' => 'Tickets',
            'wp-admin-toolkit-integrations' => 'Integrations',
            'wp-admin-toolkit-tools' => 'Tools',
            'wp-admin-toolkit-licence' => 'Licence & Updates',
        ];
    }

    public function plugin_links($links) {
        $url = admin_url('admin.php?page=' . self::MENU_SLUG);
        array_unshift($links, '<a href="' . esc_url($url) . '">Settings</a>');
        return $links;
    }

    public function menu() {
        add_menu_page(
            'WP Admin Toolkit Pro',
            'WP Admin Toolkit',
            AAT_CAP,
            self::MENU_SLUG,
            [$this->pages[0], 'render'],
            'dashicons-admin-tools',
            58
        );
        foreach ($this->pages as $page) {
            add_submenu_page(self::MENU_SLUG, $page->title(), $page->menu_label(), AAT_CAP, $page->slug(), [$page, 'render']);
        }
    }

    /**
     * The plugin lived under Settings > WP Agency Toolkit before v1.26. Keep
     * old bookmarks and links working by redirecting the legacy slug to the
     * new top-level menu.
     */
    public function maybe_redirect_legacy_page() {
        global $pagenow;
        if ($pagenow === 'options-general.php' && isset($_GET['page']) && sanitize_key(wp_unslash($_GET['page'])) === self::LEGACY_PAGE_SLUG) {
            wp_safe_redirect(admin_url('admin.php?page=' . self::MENU_SLUG));
            exit;
        }
    }

    public function register_settings() {
        register_setting('aat_settings_group', AAT_OPTION, ['sanitize_callback' => [Core::class, 'sanitize_settings']]);
    }

    public function assets($hook) {
        wp_enqueue_style('aat-admin', AAT_URL . 'assets/css/admin.css', [], AAT_VERSION);
        wp_enqueue_script('aat-admin', AAT_URL . 'assets/js/admin.js', ['jquery', 'wp-color-picker'], AAT_VERSION, true);
        if (strpos((string) $hook, self::MENU_SLUG) !== false) {
            wp_enqueue_media();
            wp_enqueue_style('wp-color-picker');
        }
        wp_localize_script('aat-admin', 'aatSupport', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aat_support_nonce'),
            'optionName' => AAT_OPTION,
        ]);
    }

    public function save_license() {
        if (!current_user_can(AAT_CAP) || !check_admin_referer('aat_save_license')) wp_die('Access denied.');
        $settings = Core::get_settings();
        $licence_key = sanitize_text_field($_POST[AAT_OPTION]['licence_key'] ?? '');
        $settings['licence_key'] = $licence_key;
        $settings['enable_private_updates'] = 1;

        if (isset($_POST['aat_activate_license'])) {
            $response = Licence::remote_request('activate', $licence_key);
            $settings = Licence::settings_from_licence_response($settings, $licence_key, $response);
        } elseif (isset($_POST['aat_deactivate_license'])) {
            if ($licence_key) {
                Licence::remote_request('deactivate', $licence_key);
            }
            $settings['licence_status'] = 'inactive';
            $settings['licence_message'] = 'Licence deactivated on this website.';
            $settings['licence_checked_at'] = gmdate('c');
        } else {
            $settings['licence_message'] = $licence_key ? 'Licence key saved. Activate it to enable protected updates.' : 'Licence key removed.';
            if (!$licence_key) {
                $settings['licence_status'] = 'inactive';
                $settings['licence_expires_at'] = '';
                $settings['licence_activations_used'] = 0;
                $settings['licence_activation_limit'] = 0;
            }
        }

        Core::update_settings($settings);
        delete_site_transient('aat_remote_update_info');
        wp_clean_plugins_cache(true);
        wp_safe_redirect(admin_url('admin.php?page=wp-admin-toolkit-licence&aat_license_saved=1'));
        exit;
    }

    public function export() {
        if (!current_user_can(AAT_CAP) || !check_admin_referer('aat_export')) wp_die('Access denied.');
        $settings = Core::get_settings();
        if (empty($_GET['include_sensitive'])) {
            $settings['support_webhook'] = '';
            $settings['licence_key'] = '';
            $settings['licence_status'] = 'inactive';
            $settings['licence_message'] = '';
        }
        $settings['_exported_by'] = 'WP Admin Toolkit Pro ' . AAT_VERSION . ' via WP Client Tools';
        $settings['_exported_at'] = gmdate('c');
        nocache_headers();
        header('Content-Type: application/json; charset=' . get_option('blog_charset'));
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: attachment; filename="wp-admin-toolkit-settings.json"');
        echo wp_json_encode($settings, JSON_PRETTY_PRINT);
        exit;
    }

    public function import() {
        if (!current_user_can(AAT_CAP) || !check_admin_referer('aat_import')) wp_die('Access denied.');

        if (empty($_FILES['aat_import_file']['tmp_name']) || !is_uploaded_file($_FILES['aat_import_file']['tmp_name'])) {
            wp_safe_redirect(admin_url('admin.php?page=wp-admin-toolkit-tools'));
            exit;
        }

        $file = $_FILES['aat_import_file'];
        if (!empty($file['size']) && (int) $file['size'] > 1048576) {
            wp_die('Import file is too large.');
        }

        $json = file_get_contents($file['tmp_name']);
        if (!is_string($json) || strlen($json) > 1048576) {
            wp_die('Invalid import file.');
        }

        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            wp_die('Invalid JSON import file.');
        }

        unset($data['_exported_by'], $data['_exported_at']);
        Core::update_settings(Core::sanitize_settings($data));
        wp_safe_redirect(admin_url('admin.php?page=wp-admin-toolkit-tools&aat_imported=1'));
        exit;
    }

    public function reset_defaults() {
        if (!current_user_can(AAT_CAP) || !check_admin_referer('aat_reset_defaults')) wp_die('Access denied.');
        Core::update_settings(Core::defaults());
        wp_safe_redirect(admin_url('admin.php?page=wp-admin-toolkit-tools&aat_reset=1'));
        exit;
    }
}
