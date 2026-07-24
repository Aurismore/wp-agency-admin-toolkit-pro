<?php
namespace Aurismore\AAT;

if (!defined('ABSPATH')) exit;

class Integrations {
    private $core;

    public function __construct(Core $core) {
        $this->core = $core;
        add_action('wp_dashboard_setup', [$this, 'hide_selected_dashboard_widgets'], 1000);
        add_action('admin_menu', [$this, 'hide_plugin_admin_pages'], 999);
        add_action('admin_notices', [$this, 'suppress_plugin_notices'], 0);
    }

    public static function detected_plugins() {
        $active = __('Active', 'wp-agency-admin-toolkit');
        $not_active = __('Not active', 'wp-agency-admin-toolkit');
        return [
            'WooCommerce' => class_exists('WooCommerce') ? (defined('WC_VERSION') ? WC_VERSION : $active) : $not_active,
            'Elementor' => defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : $not_active,
            'Rank Math' => defined('RANK_MATH_VERSION') ? RANK_MATH_VERSION : $not_active,
            'MyParcel' => defined('MYPARCEL_VERSION') ? MYPARCEL_VERSION : (class_exists('WC_MyParcel') ? $active : $not_active),
            'Yoast SEO' => defined('WPSEO_VERSION') ? WPSEO_VERSION : $not_active,
            'WP Rocket' => defined('WP_ROCKET_VERSION') ? WP_ROCKET_VERSION : $not_active,
            'LiteSpeed Cache' => defined('LSCWP_V') ? LSCWP_V : (defined('LSCWP_VERSION') ? LSCWP_VERSION : $not_active),
            'Advanced Custom Fields' => defined('ACF_VERSION') ? ACF_VERSION : $not_active,
        ];
    }

    public function hide_selected_dashboard_widgets() {
        if (!$this->core->user_is_affected()) return;

        $targets = [];
        if (!empty($this->core->settings['hide_yoast_overview'])) {
            $targets[] = ['ids' => ['wpseo-dashboard-overview', 'wpseo_dashboard_overview'], 'terms' => ['Yoast', 'SEO']];
        }
        if (!empty($this->core->settings['hide_wp_rocket_notices'])) {
            $targets[] = ['ids' => ['rocket_dashboard_widget', 'wp_rocket_dashboard_widget'], 'terms' => ['WP Rocket', 'Rocket']];
        }
        if (!empty($this->core->settings['hide_litespeed_notices'])) {
            $targets[] = ['ids' => ['litespeed-cache-widget', 'litespeed_dashboard_widget', 'lscwp_dashboard_widget'], 'terms' => ['LiteSpeed', 'LSCache']];
        }

        foreach ($targets as $target) {
            foreach ($target['ids'] as $widget_id) {
                remove_meta_box($widget_id, 'dashboard', 'normal');
                remove_meta_box($widget_id, 'dashboard', 'side');
                remove_meta_box($widget_id, 'dashboard', 'advanced');
            }
        }

        global $wp_meta_boxes;
        if (empty($wp_meta_boxes['dashboard']) || !is_array($wp_meta_boxes['dashboard'])) return;

        foreach ($wp_meta_boxes['dashboard'] as $context => $priorities) {
            if (!is_array($priorities)) continue;
            foreach ($priorities as $priority => $boxes) {
                if (!is_array($boxes)) continue;
                foreach ($boxes as $id => $box) {
                    $title = isset($box['title']) ? wp_strip_all_tags((string) $box['title']) : '';
                    foreach ($targets as $target) {
                        foreach ($target['terms'] as $term) {
                            if (stripos((string) $id, $term) !== false || stripos($title, $term) !== false) {
                                unset($wp_meta_boxes['dashboard'][$context][$priority][$id]);
                                continue 3;
                            }
                        }
                    }
                }
            }
        }
    }

    public function hide_plugin_admin_pages() {
        if (!$this->core->user_is_affected()) return;
        if (!empty($this->core->settings['hide_acf_admin_from_clients'])) {
            remove_menu_page('edit.php?post_type=acf-field-group');
        }
    }

    public function suppress_plugin_notices() {
        if (!$this->core->user_is_affected()) return;
        if (!empty($this->core->settings['hide_wp_rocket_notices']) || !empty($this->core->settings['hide_litespeed_notices'])) {
            echo '<style>.notice[class*="rocket"], .notice[id*="rocket"], .notice[class*="litespeed"], .notice[id*="litespeed"], .notice[class*="lscwp"], .notice[id*="lscwp"]{display:none!important;}</style>';
        }
    }
}
