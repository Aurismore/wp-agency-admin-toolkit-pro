<?php
if (!defined('ABSPATH')) exit;

class AAT_Core {
    private static $instance = null;
    public $settings = [];

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->settings = self::get_settings();
        add_action('init', [$this, 'load_textdomain']);
        add_action('init', [$this, 'maybe_migrate_product_brand_defaults']);
        add_action('init', [$this, 'ensure_admin_capability']);
        new AAT_Admin($this);
        new AAT_Cleanup($this);
        new AAT_Branding($this);
        new AAT_Dashboard($this);
        new AAT_Support($this);
        new AAT_Integrations($this);
        new AAT_License($this);
    }

    public function load_textdomain() {
        load_plugin_textdomain('wp-agency-admin-toolkit', false, dirname(plugin_basename(AAT_FILE)) . '/languages');
    }

    public function maybe_migrate_product_brand_defaults() {
        $saved_version = get_option('aat_product_brand_version', '');
        if (version_compare((string) $saved_version, '1.17', '>=')) {
            return;
        }

        $settings = self::get_settings();
        $changed = false;

        if (($settings['agency_name'] ?? '') === 'Creative Digital Media') {
            $settings['agency_name'] = 'Your Agency';
            $changed = true;
        }

        if (($settings['agency_url'] ?? '') === 'https://creativedigitalmedia.nl') {
            $settings['agency_url'] = home_url('/');
            $changed = true;
        }

        if (($settings['admin_footer_text'] ?? '') === 'Managed with care by Creative Digital Media') {
            $settings['admin_footer_text'] = 'Managed with care by your website team';
            $changed = true;
        }


        if ($changed) {
            self::update_settings($settings);
            $this->settings = $settings;
        }

        update_option('aat_product_brand_version', AAT_VERSION, false);
    }

    public static function defaults() {
        return [
            'agency_name' => 'Your Agency',
            'agency_url' => home_url('/'),
            'support_email' => get_option('admin_email'),
            'support_url' => '',
            'support_webhook' => '',
            'support_button_label' => 'Request Support',
            'enable_support_log' => 1,
            'support_categories' => "General website help\nContent change\nWooCommerce / orders\nTechnical issue\nUrgent issue",
            'support_webhook_template' => 'generic',
            'logout_redirect_url' => home_url('/'),
            'client_safe_mode' => 1,
            'hide_notices' => 1,
            'enable_floating_support' => 1,
            'enable_dashboard_widgets' => 1,
            'enable_custom_dashboard' => 1,
            'enable_site_snapshot' => 1,
            'enable_recent_content' => 1,
            'enable_login_branding' => 1,
            'enable_admin_branding' => 1,
            'enable_wp_admin_branding' => 1,
            'admin_branding_for_agency' => 0,
            'disable_admin_bar_for_clients' => 1,
            'hide_elementor_settings' => 1,
            'hide_rank_math_overview' => 1,
            'hide_myparcel_overview' => 1,
            'hide_yoast_overview' => 1,
            'hide_wp_rocket_notices' => 1,
            'hide_litespeed_notices' => 1,
            'hide_acf_admin_from_clients' => 1,
            'enable_private_updates' => 1,
            'licence_key' => '',
            'licence_status' => 'inactive',
            'licence_message' => '',
            'licence_checked_at' => '',
            'licence_expires_at' => '',
            'licence_activations_used' => 0,
            'licence_activation_limit' => 0,
            'admin_primary_color' => '#17243B',
            'admin_accent_color' => '#f4c7de',
            'admin_background_color' => '#f7f8fb',
            'affected_roles' => ['editor', 'shop_manager', 'aat_client_admin'],
            'hidden_menu' => [
                'edit.php',
                'edit-comments.php',
                'tools.php',
                'themes.php',
                'plugins.php',
                'users.php',
                'options-general.php',
                'woocommerce-marketing',
                'wc-admin&path=/analytics/overview',
                'elementor',
            ],
            'restricted_pages' => [
                'plugins.php',
                'plugin-install.php',
                'themes.php',
                'theme-install.php',
                'theme-editor.php',
                'plugin-editor.php',
                'update-core.php',
                'options-general.php',
                'options-permalink.php',
                'tools.php',
                'users.php',
                'user-new.php',
                'site-health.php',
                'customize.php',
                'admin.php?page=wc-settings',
                'admin.php?page=wc-status',
                'admin.php?page=elementor',
                'admin.php?page=elementor-tools',
                'admin.php?page=elementor-system-info',
                'admin.php?page=elementor-role-manager',
            ],
            'login_logo_url' => '',
            'login_background_image_url' => '',
            'login_background_image_id' => 0,
            'login_background_overlay' => 'rgba(23,36,59,0.28)',
            'login_hide_aux_links' => 1,
            'login_background' => '#f7f8fb',
            'login_button_color' => '#17243B',
            'login_accent_color' => '#f4c7de',
            'admin_footer_text' => 'Managed with care by your website team',
            'dashboard_title' => 'Client Dashboard',
            'dashboard_layout' => 'balanced',
            'welcome_message' => 'Welcome. Use the shortcuts below for the most common website tasks. If anything feels unclear, request support before changing technical settings.',
            'instructions' => [
                'products' => 'Use Products to add or update store items. Avoid changing tax, payment, shipping or advanced WooCommerce settings unless your agency asks you to.',
                'orders' => 'Use Orders to review, process and update customer purchases. Always double-check payment and shipping details before changing an order status.',
                'pages' => 'Use Pages to edit website content. Make small changes and preview before publishing.',
                'media' => 'Upload clear, compressed images. Avoid very large files because they can slow down the website.',
            ],
            'shortcuts' => [
                ['label' => 'View Orders', 'url' => 'admin.php?page=wc-orders', 'cap' => 'edit_shop_orders'],
                ['label' => 'Add Product', 'url' => 'post-new.php?post_type=product', 'cap' => 'edit_products'],
                ['label' => 'Products', 'url' => 'edit.php?post_type=product', 'cap' => 'edit_products'],
                ['label' => 'Pages', 'url' => 'edit.php?post_type=page', 'cap' => 'edit_pages'],
                ['label' => 'Media Library', 'url' => 'upload.php', 'cap' => 'upload_files'],
                ['label' => 'View Website', 'url' => home_url('/'), 'cap' => 'read'],
            ],
        ];
    }

    public static function get_settings() {
        $saved = get_option(AAT_OPTION, []);
        if (!is_array($saved)) $saved = [];
        return wp_parse_args($saved, self::defaults());
    }

    public static function update_settings($settings) {
        $settings = wp_parse_args($settings, self::defaults());
        update_option(AAT_OPTION, $settings, false);
    }

    public static function activate() {
        if (!get_option(AAT_OPTION)) {
            self::update_settings(self::defaults());
        }

        $admin = get_role('administrator');
        if ($admin && !$admin->has_cap(AAT_CAP)) {
            $admin->add_cap(AAT_CAP);
        }

        add_role('aat_client_admin', 'Client Admin', [
            'read' => true,
            'upload_files' => true,
            'edit_posts' => true,
            'edit_pages' => true,
            'edit_published_pages' => true,
            'publish_pages' => true,
            'edit_products' => true,
            'publish_products' => true,
            'read_private_products' => true,
            'edit_shop_orders' => true,
            'read_shop_order' => true,
            'view_woocommerce_reports' => true,
        ]);

        if (!defined('DISALLOW_FILE_EDIT')) {
            // The plugin also blocks editor screens in admin, but agencies should define DISALLOW_FILE_EDIT in wp-config.php for full protection.
        }
    }

    public static function deactivate() {
        // Keep settings and custom role for safety. Agencies can remove manually if needed.
    }

    public function ensure_admin_capability() {
        $admin = get_role('administrator');
        if ($admin && !$admin->has_cap(AAT_CAP)) {
            $admin->add_cap(AAT_CAP);
        }
    }

    public function current_user_is_agency() {
        return current_user_can(AAT_CAP) || current_user_can('manage_options');
    }

    public function user_is_affected() {
        if (!is_user_logged_in()) return false;
        if ($this->current_user_is_agency()) return false;
        $user = wp_get_current_user();
        $roles = isset($this->settings['affected_roles']) && is_array($this->settings['affected_roles']) ? $this->settings['affected_roles'] : [];
        return (bool) array_intersect($roles, (array) $user->roles);
    }

    public static function sanitize_settings($input) {
        $defaults = self::defaults();
        $clean = [];
        $clean['agency_name'] = sanitize_text_field($input['agency_name'] ?? $defaults['agency_name']);
        $clean['agency_url'] = esc_url_raw($input['agency_url'] ?? '', ['http', 'https']);
        $clean['support_email'] = sanitize_email($input['support_email'] ?? '');
        $clean['support_url'] = esc_url_raw($input['support_url'] ?? '', ['http', 'https']);
        $clean['support_webhook'] = esc_url_raw($input['support_webhook'] ?? '', ['http', 'https']);
        $clean['support_button_label'] = sanitize_text_field($input['support_button_label'] ?? $defaults['support_button_label']);
        $clean['support_categories'] = self::sanitize_multiline_text($input['support_categories'] ?? $defaults['support_categories']);
        $template = sanitize_key($input['support_webhook_template'] ?? $defaults['support_webhook_template']);
        $clean['support_webhook_template'] = in_array($template, ['generic', 'slack', 'discord'], true) ? $template : 'generic';
        $clean['licence_key'] = sanitize_text_field($input['licence_key'] ?? ($defaults['licence_key'] ?? ''));
        $licence_status = sanitize_key($input['licence_status'] ?? ($defaults['licence_status'] ?? 'inactive'));
        $clean['licence_status'] = in_array($licence_status, ['active', 'inactive', 'unknown'], true) ? $licence_status : 'inactive';
        $clean['licence_message'] = sanitize_text_field($input['licence_message'] ?? '');
        $clean['licence_checked_at'] = sanitize_text_field($input['licence_checked_at'] ?? '');
        $clean['licence_expires_at'] = sanitize_text_field($input['licence_expires_at'] ?? '');
        $clean['licence_activations_used'] = absint($input['licence_activations_used'] ?? 0);
        $clean['licence_activation_limit'] = absint($input['licence_activation_limit'] ?? 0);
        $clean['logout_redirect_url'] = esc_url_raw($input['logout_redirect_url'] ?? $defaults['logout_redirect_url'], ['http', 'https']);
        foreach (['client_safe_mode','hide_notices','enable_floating_support','enable_dashboard_widgets','enable_custom_dashboard','enable_site_snapshot','enable_recent_content','enable_login_branding','enable_admin_branding','enable_wp_admin_branding','admin_branding_for_agency','disable_admin_bar_for_clients','hide_elementor_settings','hide_rank_math_overview','hide_myparcel_overview','hide_yoast_overview','hide_wp_rocket_notices','hide_litespeed_notices','hide_acf_admin_from_clients','enable_support_log','login_hide_aux_links'] as $key) {
            $clean[$key] = !empty($input[$key]) ? 1 : 0;
        }
        $clean['affected_roles'] = array_values(array_filter(array_map('sanitize_key', (array)($input['affected_roles'] ?? []))));
        $clean['hidden_menu'] = self::sanitize_lines($input['hidden_menu'] ?? '');
        $clean['restricted_pages'] = self::sanitize_lines($input['restricted_pages'] ?? '');
        $clean['login_logo_url'] = esc_url_raw($input['login_logo_url'] ?? '', ['http', 'https']);
        $clean['login_background_image_url'] = esc_url_raw($input['login_background_image_url'] ?? '', ['http', 'https']);
        $clean['login_background_image_id'] = absint($input['login_background_image_id'] ?? 0);
        $clean['login_background_overlay'] = sanitize_text_field($input['login_background_overlay'] ?? $defaults['login_background_overlay']);
        $clean['login_background'] = sanitize_hex_color($input['login_background'] ?? '') ?: $defaults['login_background'];
        $clean['login_button_color'] = sanitize_hex_color($input['login_button_color'] ?? '') ?: $defaults['login_button_color'];
        $clean['login_accent_color'] = sanitize_hex_color($input['login_accent_color'] ?? '') ?: $defaults['login_accent_color'];
        $clean['admin_footer_text'] = sanitize_text_field($input['admin_footer_text'] ?? '');
        $clean['admin_primary_color'] = sanitize_hex_color($input['admin_primary_color'] ?? '') ?: $defaults['admin_primary_color'];
        $clean['admin_accent_color'] = sanitize_hex_color($input['admin_accent_color'] ?? '') ?: $defaults['admin_accent_color'];
        $clean['admin_background_color'] = sanitize_hex_color($input['admin_background_color'] ?? '') ?: $defaults['admin_background_color'];
        $clean['dashboard_title'] = sanitize_text_field($input['dashboard_title'] ?? $defaults['dashboard_title']);
        $layout = sanitize_key($input['dashboard_layout'] ?? $defaults['dashboard_layout']);
        $clean['dashboard_layout'] = in_array($layout, ['balanced', 'commerce', 'content'], true) ? $layout : 'balanced';
        $clean['welcome_message'] = wp_kses_post($input['welcome_message'] ?? '');
        $clean['instructions'] = [];
        foreach (($input['instructions'] ?? []) as $key => $value) {
            $clean['instructions'][sanitize_key($key)] = wp_kses_post($value);
        }
        $clean['shortcuts'] = [];
        if (!empty($input['shortcuts']) && is_array($input['shortcuts'])) {
            foreach ($input['shortcuts'] as $shortcut) {
                if (empty($shortcut['label']) || empty($shortcut['url'])) continue;
                $clean['shortcuts'][] = [
                    'label' => sanitize_text_field($shortcut['label']),
                    'url' => esc_url_raw($shortcut['url'], ['http', 'https']),
                    'cap' => sanitize_key($shortcut['cap'] ?? 'read'),
                ];
            }
        }
        return wp_parse_args($clean, $defaults);
    }

    public static function sanitize_lines($value) {
        if (is_array($value)) return array_values(array_filter(array_map('sanitize_text_field', $value)));
        $lines = preg_split('/\r\n|\r|\n/', (string)$value);
        return array_values(array_filter(array_map('sanitize_text_field', $lines)));
    }

    public static function sanitize_multiline_text($value) {
        $lines = is_array($value) ? $value : preg_split('/\r\n|\r|\n/', (string) $value);
        $lines = array_values(array_filter(array_map('sanitize_text_field', (array) $lines)));
        return implode("\n", $lines);
    }
}
