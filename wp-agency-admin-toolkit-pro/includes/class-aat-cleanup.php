<?php
if (!defined('ABSPATH')) exit;

class AAT_Cleanup {
    private $core;
    public function __construct($core) {
        $this->core = $core;
        add_action('admin_menu', [$this, 'hide_menus'], 999);
        add_action('admin_init', [$this, 'restrict_pages'], 1);
        add_action('wp_before_admin_bar_render', [$this, 'cleanup_admin_bar'], 999);
        add_action('admin_print_scripts', [$this, 'hide_notices'], 0);
        add_action('admin_head', [$this, 'admin_head_css']);
        add_filter('show_admin_bar', [$this, 'maybe_disable_frontend_admin_bar'], 999);
    }

    public function hide_menus() {
        // WP Agency Admin Toolkit v1.17 keeps the left wp-admin menu intact for all users, including client roles and administrators.
        // Risky direct-page blocking can still be handled by client_safe_mode below when enabled.
        return;
    }

    public function restrict_pages() {
        if (!$this->core->user_is_affected() || empty($this->core->settings['client_safe_mode'])) return;
        global $pagenow;
        $current = $pagenow;
        if (!empty($_GET['page'])) {
            $current .= '?page=' . sanitize_text_field(wp_unslash($_GET['page']));
        }
        if (!empty($_GET['post_type'])) {
            $current .= '?post_type=' . sanitize_text_field(wp_unslash($_GET['post_type']));
        }

        $blocked = (array)$this->core->settings['restricted_pages'];
        if (!empty($this->core->settings['hide_elementor_settings'])) {
            $blocked = array_merge($blocked, [
                'admin.php?page=elementor',
                'admin.php?page=elementor-tools',
                'admin.php?page=elementor-system-info',
                'admin.php?page=elementor-role-manager',
            ]);
        }
        foreach ($blocked as $rule) {
            $rule = trim($rule);
            if ($rule === '') continue;
            if ($pagenow === $rule || $current === $rule || strpos($current, $rule) !== false || strpos($_SERVER['REQUEST_URI'] ?? '', $rule) !== false) {
                wp_die(
                    '<h1>Protected agency setting</h1><p>This area can affect the website, payments, theme, plugins or store settings. Please request support before making changes here.</p><p><a class="button button-primary" href="' . esc_url(admin_url()) . '">Return to dashboard</a></p>',
                    'Protected Agency Setting',
                    ['response' => 403]
                );
            }
        }
    }

    public function cleanup_admin_bar() {
        if (!$this->core->user_is_affected()) return;
        global $wp_admin_bar;
        if (!$wp_admin_bar) return;
        foreach (['wp-logo', 'updates', 'comments', 'new-post', 'new-plugin', 'new-theme', 'customize', 'themes', 'menus', 'widgets', 'elementor_edit_page', 'elementor_app_site_editor'] as $node) {
            $wp_admin_bar->remove_node($node);
        }
    }

    public function maybe_disable_frontend_admin_bar($show) {
        if ($this->core->user_is_affected() && !empty($this->core->settings['disable_admin_bar_for_clients'])) {
            return false;
        }
        return $show;
    }

    public function hide_notices() {
        if (!$this->core->user_is_affected() || empty($this->core->settings['hide_notices'])) return;
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
        remove_all_actions('network_admin_notices');
        remove_all_actions('user_admin_notices');
    }

    public function admin_head_css() {
        if (!$this->core->user_is_affected()) return;
        echo '<style>.update-nag,.notice-warning.update-message,.woocommerce-message.updated{display:none!important}.aat-client-note{border-left:4px solid #f4c7de;background:#fff;padding:12px 14px;margin:12px 0}</style>';
        if (!empty($this->core->settings['disable_admin_bar_for_clients'])) {
            echo '<style id="aat-hide-admin-bar">#wpadminbar{display:none!important}html.wp-toolbar{padding-top:0!important}body.admin-bar #wpwrap{padding-top:0!important}</style>';
        }
    }
}
