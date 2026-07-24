<?php
namespace Aurismore\AAT;

if (!defined('ABSPATH')) exit;

class Dashboard {
    private $core;

    public function __construct(Core $core) {
        $this->core = $core;
        add_action('admin_menu', [$this, 'client_dashboard_menu'], 5);
        add_action('admin_init', [$this, 'redirect_default_dashboard'], 0);
        add_filter('login_redirect', [$this, 'login_redirect'], 20, 3);
        add_filter('logout_redirect', [$this, 'logout_redirect'], 20, 3);
        add_action('wp_dashboard_setup', [$this, 'widgets']);
        add_action('wp_dashboard_setup', [$this, 'maybe_hide_rank_math_overview'], 999);
        add_action('wp_dashboard_setup', [$this, 'maybe_hide_myparcel_overview'], 999);
        add_action('admin_notices', [$this, 'contextual_instructions']);
    }

    public function custom_dashboard_enabled() {
        return !empty($this->core->settings['enable_custom_dashboard']) && is_user_logged_in() && current_user_can('read');
    }

    public function client_dashboard_url() {
        return admin_url('admin.php?page=wp-agency-admin-dashboard');
    }

    private function dashboard_title() {
        return Core::translated_setting($this->core->settings, 'dashboard_title');
    }

    public function client_dashboard_menu() {
        if (!$this->custom_dashboard_enabled()) return;
        add_menu_page(
            esc_html($this->dashboard_title()),
            esc_html($this->dashboard_title()),
            'read',
            'wp-agency-admin-dashboard',
            [$this, 'render_client_dashboard'],
            'dashicons-dashboard',
            2
        );
    }

    public function redirect_default_dashboard() {
        if (!$this->custom_dashboard_enabled()) return;
        if (wp_doing_ajax() || wp_doing_cron()) return;

        global $pagenow;
        if ($pagenow === 'index.php') {
            wp_safe_redirect($this->client_dashboard_url());
            exit;
        }
    }

    public function login_redirect($redirect_to, $requested_redirect_to, $user) {
        if (!$user || is_wp_error($user)) return $redirect_to;
        if (!empty($this->core->settings['enable_custom_dashboard']) && user_can($user, 'read')) {
            return $this->client_dashboard_url();
        }
        return $redirect_to;
    }

    public function logout_redirect($redirect_to, $requested_redirect_to, $user) {
        if (!$user || is_wp_error($user)) return $redirect_to;
        if (user_can($user, AAT_CAP) || user_can($user, 'manage_options')) return $redirect_to;

        $affected_roles = isset($this->core->settings['affected_roles']) && is_array($this->core->settings['affected_roles']) ? $this->core->settings['affected_roles'] : [];
        if (!array_intersect($affected_roles, (array) $user->roles)) return $redirect_to;

        $url = !empty($this->core->settings['logout_redirect_url']) ? $this->core->settings['logout_redirect_url'] : home_url('/');
        return esc_url_raw($url);
    }

    private function render_dashboard_footer_card() {
        $s = $this->core->settings;
        $agency_url = !empty($s['agency_url']) ? $s['agency_url'] : home_url('/');
        $agency_logo = !empty($s['login_logo_url']) ? $s['login_logo_url'] : '';
        ?>
        <div class="aat-dashboard-footer-card">
            <div class="aat-dashboard-footer-brand">
                <a href="<?php echo esc_url($agency_url); ?>" target="_blank" rel="noopener noreferrer" class="aat-dashboard-footer-logo-link">
                    <?php if ($agency_logo): ?>
                        <img src="<?php echo esc_url($agency_logo); ?>" alt="<?php echo esc_attr($s['agency_name']); ?>">
                    <?php else: ?>
                        <span><?php echo esc_html($s['agency_name']); ?></span>
                    <?php endif; ?>
                </a>
            </div>
            <div class="aat-dashboard-footer-action">
                <button type="button" class="button button-primary aat-open-support"><?php echo esc_html(Core::translated_setting($s, 'support_button_label')); ?></button>
            </div>
        </div>
        <?php
    }

    public function render_client_dashboard() {
        if (!$this->custom_dashboard_enabled()) wp_die(esc_html__('Access denied.', 'wp-agency-admin-toolkit'));
        $s = $this->core->settings;
        $site_logo = Core::get_site_logo_url();
        ?>
        <div class="wrap aat-client-dashboard aat-dashboard-layout-<?php echo esc_attr($s['dashboard_layout'] ?? 'balanced'); ?>">
            <div class="aat-hero-card">
                <div class="aat-hero-main">
                    <div class="aat-site-branding">
                        <?php if ($site_logo): ?>
                            <img src="<?php echo esc_url($site_logo); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
                        <?php else: ?>
                            <span><?php echo esc_html(get_bloginfo('name')); ?></span>
                        <?php endif; ?>
                    </div>
                    <h1><?php echo esc_html($this->dashboard_title()); ?></h1>
                    <div class="aat-welcome-copy"><?php echo wp_kses_post(wpautop(Core::translated_setting($s, 'welcome_message'))); ?></div>
                </div>
                <div class="aat-hero-actions">
                    <a class="button aat-logout-button" href="<?php echo esc_url(wp_logout_url(!empty($s['logout_redirect_url']) ? $s['logout_redirect_url'] : home_url('/'))); ?>"><?php esc_html_e('Log out', 'wp-agency-admin-toolkit'); ?></a>
                </div>
            </div>

            <div class="aat-dashboard-grid">
                <?php if (!empty($s['enable_site_snapshot'])): ?>
                    <section class="aat-panel aat-site-snapshot-panel">
                        <h2><?php esc_html_e('Site snapshot', 'wp-agency-admin-toolkit'); ?></h2>
                        <?php $this->site_snapshot_widget(); ?>
                    </section>
                <?php endif; ?>

                <section class="aat-panel aat-panel-wide">
                    <h2><?php esc_html_e('Common tasks', 'wp-agency-admin-toolkit'); ?></h2>
                    <?php $this->shortcuts_widget(); ?>
                </section>

                <?php if (class_exists('WooCommerce')): ?>
                    <section class="aat-panel">
                        <h2><?php esc_html_e('Recent orders', 'wp-agency-admin-toolkit'); ?></h2>
                        <?php $this->woocommerce_widget(); ?>
                    </section>
                <?php endif; ?>

                <?php if (!empty($s['enable_recent_content'])): ?>
                    <section class="aat-panel">
                        <h2><?php esc_html_e('Recently edited content', 'wp-agency-admin-toolkit'); ?></h2>
                        <?php $this->recent_content_widget(); ?>
                    </section>
                <?php endif; ?>

                <section class="aat-panel">
                    <h2><?php esc_html_e('Support', 'wp-agency-admin-toolkit'); ?></h2>
                    <?php $this->support_widget(); ?>
                </section>

                <section class="aat-panel aat-panel-wide">
                    <h2><?php esc_html_e('Client instructions', 'wp-agency-admin-toolkit'); ?></h2>
                    <div class="aat-instruction-grid">
                        <?php foreach ((array)$s['instructions'] as $key => $message): ?>
                            <?php if (!$message) continue; ?>
                            <div class="aat-instruction-card">
                                <h3><?php echo esc_html(Core::instruction_heading($key)); ?></h3>
                                <p><?php echo wp_kses_post(Core::translated_instruction($key, $message)); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <?php $this->render_dashboard_footer_card(); ?>
        </div>
        <?php
    }

    private function snapshot_label($key) {
        switch ($key) {
            case 'pages':
                return __('Published pages', 'wp-agency-admin-toolkit');
            case 'media':
                return __('Media files', 'wp-agency-admin-toolkit');
            case 'products':
                return __('Published products', 'wp-agency-admin-toolkit');
            case 'orders':
                return __('Processing orders', 'wp-agency-admin-toolkit');
        }
        return (string) $key;
    }

    /**
     * Snapshot items use wp_count_posts internally, which hits the DB for each
     * post type and is not persistently cached. Wrap in a short transient so
     * dashboard loads are cheap even on busy sites. Only label *keys* are
     * cached; labels resolve per request so they follow the viewer's language.
     */
    public function site_snapshot_widget() {
        $cache_key = 'aat_site_snapshot_' . get_current_user_id();
        $items = get_transient($cache_key);

        if ($items === false) {
            $items = [];
            if (current_user_can('edit_pages')) {
                $counts = wp_count_posts('page');
                $items[] = ['label_key' => 'pages', 'value' => isset($counts->publish) ? (int) $counts->publish : 0, 'url' => admin_url('edit.php?post_type=page')];
            }
            if (current_user_can('upload_files')) {
                $media_counts = wp_count_posts('attachment');
                $items[] = ['label_key' => 'media', 'value' => isset($media_counts->inherit) ? (int) $media_counts->inherit : 0, 'url' => admin_url('upload.php')];
            }
            if (class_exists('WooCommerce') && current_user_can('edit_products')) {
                $product_counts = wp_count_posts('product');
                $items[] = ['label_key' => 'products', 'value' => isset($product_counts->publish) ? (int) $product_counts->publish : 0, 'url' => admin_url('edit.php?post_type=product')];
            }
            if (function_exists('wc_orders_count') && current_user_can('edit_shop_orders')) {
                $processing_count = wc_orders_count('processing');
                $items[] = ['label_key' => 'orders', 'value' => (int) $processing_count, 'url' => admin_url('admin.php?page=wc-orders&status=wc-processing')];
            }
            set_transient($cache_key, $items, 5 * MINUTE_IN_SECONDS);
        }

        if (empty($items)) {
            echo '<p>' . esc_html__('No snapshot items are available for this user role.', 'wp-agency-admin-toolkit') . '</p>';
            return;
        }
        echo '<div class="aat-snapshot-grid">';
        foreach ($items as $item) {
            // Pre-1.27 transients cached the label text itself; fall back to it.
            $label = isset($item['label_key']) ? $this->snapshot_label($item['label_key']) : ($item['label'] ?? '');
            echo '<a class="aat-snapshot-item" href="' . esc_url($item['url']) . '"><strong>' . esc_html(number_format_i18n($item['value'])) . '</strong><span>' . esc_html($label) . '</span></a>';
        }
        echo '</div>';
    }

    public function recent_content_widget() {
        $post_types = [];
        if (current_user_can('edit_pages')) $post_types[] = 'page';
        if (current_user_can('edit_posts')) $post_types[] = 'post';
        if (post_type_exists('product') && current_user_can('edit_products')) $post_types[] = 'product';

        if (empty($post_types)) {
            echo '<p>' . esc_html__('No editable content is available for this user role.', 'wp-agency-admin-toolkit') . '</p>';
            return;
        }

        $query = new \WP_Query([
            'post_type' => $post_types,
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => 5,
            'orderby' => 'modified',
            'order' => 'DESC',
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
        ]);

        if (!$query->have_posts()) {
            echo '<p>' . esc_html__('No recent content found.', 'wp-agency-admin-toolkit') . '</p>';
            return;
        }

        echo '<ul class="aat-recent-content-list">';
        while ($query->have_posts()) {
            $query->the_post();
            $edit_url = get_edit_post_link(get_the_ID(), 'raw');
            $type = get_post_type_object(get_post_type());
            echo '<li><a href="' . esc_url($edit_url) . '">' . esc_html(get_the_title() ?: __('(no title)', 'wp-agency-admin-toolkit')) . '</a><span>' . esc_html($type ? $type->labels->singular_name : get_post_type()) . ' · ' . esc_html(get_the_modified_date()) . '</span></li>';
        }
        wp_reset_postdata();
        echo '</ul>';
    }

    public function widgets() {
        if (!$this->core->user_is_affected() || empty($this->core->settings['enable_dashboard_widgets'])) return;
        wp_add_dashboard_widget('aat_welcome', esc_html($this->dashboard_title()), [$this, 'welcome_widget']);
        wp_add_dashboard_widget('aat_shortcuts', esc_html__('Website Shortcuts', 'wp-agency-admin-toolkit'), [$this, 'shortcuts_widget']);
        wp_add_dashboard_widget('aat_support', esc_html__('Agency Support', 'wp-agency-admin-toolkit'), [$this, 'support_widget']);
        if (class_exists('WooCommerce')) {
            wp_add_dashboard_widget('aat_woocommerce_snapshot', esc_html__('Store Snapshot', 'wp-agency-admin-toolkit'), [$this, 'woocommerce_widget']);
        }
    }

    public function maybe_hide_rank_math_overview() {
        if (!$this->core->user_is_affected() || empty($this->core->settings['hide_rank_math_overview'])) return;

        $widget_ids = [
            'rank_math_dashboard_widget',
            'rank_math_dashboard_widget_display',
            'rank_math_overview',
            'rank_math_dashboard_overview',
        ];

        foreach ($widget_ids as $widget_id) {
            remove_meta_box($widget_id, 'dashboard', 'normal');
            remove_meta_box($widget_id, 'dashboard', 'side');
            remove_meta_box($widget_id, 'dashboard', 'advanced');
        }

        global $wp_meta_boxes;
        if (empty($wp_meta_boxes['dashboard']) || !is_array($wp_meta_boxes['dashboard'])) return;

        foreach ($wp_meta_boxes['dashboard'] as $context => $priorities) {
            if (!is_array($priorities)) continue;
            foreach ($priorities as $priority => $boxes) {
                if (!is_array($boxes)) continue;
                foreach ($boxes as $id => $box) {
                    $title = isset($box['title']) ? wp_strip_all_tags((string) $box['title']) : '';
                    if (stripos((string) $id, 'rank_math') !== false || stripos($title, 'Rank Math') !== false) {
                        unset($wp_meta_boxes['dashboard'][$context][$priority][$id]);
                    }
                }
            }
        }
    }

    public function maybe_hide_myparcel_overview() {
        if (!$this->core->user_is_affected() || empty($this->core->settings['hide_myparcel_overview'])) return;

        $widget_ids = [
            'myparcel_dashboard_widget',
            'myparcel_overview',
            'myparcel_wc_dashboard_widget',
            'woocommerce_myparcel_dashboard_widget',
            'wc_myparcel_dashboard_widget',
        ];

        foreach ($widget_ids as $widget_id) {
            remove_meta_box($widget_id, 'dashboard', 'normal');
            remove_meta_box($widget_id, 'dashboard', 'side');
            remove_meta_box($widget_id, 'dashboard', 'advanced');
        }

        global $wp_meta_boxes;
        if (empty($wp_meta_boxes['dashboard']) || !is_array($wp_meta_boxes['dashboard'])) return;

        foreach ($wp_meta_boxes['dashboard'] as $context => $priorities) {
            if (!is_array($priorities)) continue;
            foreach ($priorities as $priority => $boxes) {
                if (!is_array($boxes)) continue;
                foreach ($boxes as $id => $box) {
                    $title = isset($box['title']) ? wp_strip_all_tags((string) $box['title']) : '';
                    $id_string = (string) $id;
                    if (stripos($id_string, 'myparcel') !== false || stripos($title, 'MyParcel') !== false || stripos($title, 'My Parcel') !== false) {
                        unset($wp_meta_boxes['dashboard'][$context][$priority][$id]);
                    }
                }
            }
        }
    }

    public function welcome_widget() {
        echo '<div class="aat-widget">' . wp_kses_post(wpautop(Core::translated_setting($this->core->settings, 'welcome_message')));
        echo '<p><strong>' . esc_html__('Tip:', 'wp-agency-admin-toolkit') . '</strong> ' . esc_html__('When in doubt, use the support button before changing technical settings.', 'wp-agency-admin-toolkit') . '</p></div>';
    }

    public function shortcuts_widget() {
        echo '<div class="aat-shortcut-grid">';
        foreach ((array)$this->core->settings['shortcuts'] as $shortcut) {
            $label = isset($shortcut['label']) ? trim((string) $shortcut['label']) : '';
            if (strcasecmp($label, 'Log Out') === 0 || strcasecmp($label, 'Logout') === 0) continue;
            $cap = !empty($shortcut['cap']) ? $shortcut['cap'] : 'read';
            if (!current_user_can($cap)) continue;
            $url = $shortcut['url'];
            if (strpos($url, 'http') !== 0) $url = admin_url($url);
            echo '<a class="aat-shortcut" href="' . esc_url($url) . '">' . esc_html(Core::translated_shortcut_label($label)) . '</a>';
        }
        echo '</div>';
    }

    public function support_widget() {
        $s = $this->core->settings;
        echo '<div class="aat-widget"><p>' . esc_html__('Need help with the website? Send a request with page context so your agency can respond faster.', 'wp-agency-admin-toolkit') . '</p>';
        echo '<button type="button" class="button button-primary aat-open-support">' . esc_html(Core::translated_setting($s, 'support_button_label')) . '</button>';
        if (!empty($s['support_url'])) {
            echo ' <a class="button" target="_blank" rel="noopener noreferrer" href="' . esc_url($s['support_url']) . '">' . esc_html__('Open Support Portal', 'wp-agency-admin-toolkit') . '</a>';
        }
        echo '</div>';
    }

    public function woocommerce_widget() {
        if (!function_exists('wc_get_orders')) {
            echo '<p>' . esc_html__('WooCommerce is active, but order helper functions are not available.', 'wp-agency-admin-toolkit') . '</p>';
            return;
        }
        $orders = wc_get_orders(['limit' => 5, 'orderby' => 'date', 'order' => 'DESC', 'return' => 'objects']);
        if (empty($orders)) {
            echo '<p>' . esc_html__('No recent orders found.', 'wp-agency-admin-toolkit') . '</p>';
            return;
        }
        echo '<ul class="aat-order-list">';
        foreach ($orders as $order) {
            /* translators: %s: order number. */
            $order_label = sprintf(__('Order #%s', 'wp-agency-admin-toolkit'), $order->get_order_number());
            echo '<li><a href="' . esc_url($order->get_edit_order_url()) . '">' . esc_html($order_label) . '</a> · ' . esc_html(wc_get_order_status_name($order->get_status())) . ' · ' . wp_kses_post($order->get_formatted_order_total()) . '</li>';
        }
        echo '</ul>';
    }

    public function contextual_instructions() {
        if (!$this->core->user_is_affected() || empty($this->core->settings['enable_dashboard_widgets'])) return;
        $screen = get_current_screen();
        if (!$screen) return;
        $instructions = (array)$this->core->settings['instructions'];
        $message = '';
        if ($screen->id === 'edit-product' || $screen->post_type === 'product') $message = Core::translated_instruction('products', $instructions['products'] ?? '');
        if ($screen->id === 'edit-shop_order' || $screen->id === 'woocommerce_page_wc-orders') $message = Core::translated_instruction('orders', $instructions['orders'] ?? '');
        if ($screen->id === 'edit-page' || $screen->post_type === 'page') $message = Core::translated_instruction('pages', $instructions['pages'] ?? '');
        if ($screen->id === 'upload') $message = Core::translated_instruction('media', $instructions['media'] ?? '');
        if ($message) echo '<div class="notice aat-client-note"><p>' . wp_kses_post($message) . '</p></div>';
    }
}
