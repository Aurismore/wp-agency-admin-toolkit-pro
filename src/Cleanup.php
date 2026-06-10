<?php
namespace Aurismore\AAT;

if (!defined('ABSPATH')) exit;

class Cleanup {
    private $core;

    public function __construct(Core $core) {
        $this->core = $core;
        add_action('admin_init', [$this, 'restrict_pages'], 1);
        add_action('wp_before_admin_bar_render', [$this, 'cleanup_admin_bar'], 999);
        add_action('admin_print_scripts', [$this, 'hide_notices'], 0);
        add_action('admin_head', [$this, 'admin_head_css']);
        add_filter('show_admin_bar', [$this, 'maybe_disable_frontend_admin_bar'], 999);
    }

    /**
     * Block direct visits to configured admin pages for affected client roles.
     *
     * Uses exact matching on {pagenow, ?page, ?post_type} keys rather than
     * substring matches against REQUEST_URI, which historically over-blocked
     * (e.g. a rule of `tools.php` matched any URL containing that string).
     */
    public function restrict_pages() {
        if (!$this->core->user_is_affected() || empty($this->core->settings['client_safe_mode'])) return;
        global $pagenow;
        if (!$pagenow) return;

        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        $post_type = isset($_GET['post_type']) ? sanitize_text_field(wp_unslash($_GET['post_type'])) : '';

        $current_keys = [$pagenow];
        if ($page !== '') {
            $current_keys[] = $pagenow . '?page=' . $page;
            // Many rules in the wild are written as "admin.php?page=..." even when WP
            // routes the same page through a different $pagenow. Normalise both forms.
            $current_keys[] = 'admin.php?page=' . $page;
        }
        if ($post_type !== '') {
            $current_keys[] = $pagenow . '?post_type=' . $post_type;
        }

        $blocked = (array) $this->core->settings['restricted_pages'];
        if (!empty($this->core->settings['hide_elementor_settings'])) {
            $blocked = array_merge($blocked, [
                'admin.php?page=elementor',
                'admin.php?page=elementor-tools',
                'admin.php?page=elementor-system-info',
                'admin.php?page=elementor-role-manager',
            ]);
        }
        $blocked = array_unique(array_filter(array_map('trim', $blocked)));

        foreach ($blocked as $rule) {
            if ($rule !== '' && in_array($rule, $current_keys, true)) {
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
