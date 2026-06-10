<?php
if (!defined('ABSPATH')) exit;

class AAT_Dashboard {
    private $core;
    public function __construct($core) {
        $this->core = $core;
        add_action('admin_menu', [$this, 'client_dashboard_menu'], 5);
        add_action('admin_menu', [$this, 'maybe_hide_wordpress_dashboard'], 998);
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

    public function client_dashboard_menu() {
        if (!$this->custom_dashboard_enabled()) return;
        add_menu_page(
            esc_html($this->core->settings['dashboard_title']),
            esc_html($this->core->settings['dashboard_title']),
            'read',
            'wp-agency-admin-dashboard',
            [$this, 'render_client_dashboard'],
            'dashicons-dashboard',
            2
        );
    }

    public function maybe_hide_wordpress_dashboard() {
        // Keep the native Dashboard item visible so the left wp-admin menu remains intact for every role.
        return;
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


    private function get_site_logo_url() {
        $custom_logo_id = function_exists('get_theme_mod') ? absint(get_theme_mod('custom_logo')) : 0;
        if ($custom_logo_id) {
            $logo = wp_get_attachment_image_url($custom_logo_id, 'full');
            if ($logo) return $logo;
        }

        if (function_exists('get_site_icon_url') && get_site_icon_url(192)) {
            return get_site_icon_url(192);
        }

        return '';
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
                <button type="button" class="button button-primary aat-open-support"><?php echo esc_html($s['support_button_label']); ?></button>
            </div>
        </div>
        <?php
    }

    public function render_client_dashboard() {
        if (!$this->custom_dashboard_enabled()) wp_die('Access denied.');
        $s = $this->core->settings;
        $site_logo = $this->get_site_logo_url();
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
                    <h1><?php echo esc_html($s['dashboard_title']); ?></h1>
                    <div class="aat-welcome-copy"><?php echo wp_kses_post(wpautop($s['welcome_message'])); ?></div>
                </div>
                <div class="aat-hero-actions">
                    <a class="button aat-logout-button" href="<?php echo esc_url(wp_logout_url(!empty($s['logout_redirect_url']) ? $s['logout_redirect_url'] : home_url('/'))); ?>">Log out</a>
                </div>
            </div>

            <div class="aat-dashboard-grid">
                <?php if (!empty($s['enable_site_snapshot'])): ?>
                    <section class="aat-panel aat-site-snapshot-panel">
                        <h2>Site snapshot</h2>
                        <?php $this->site_snapshot_widget(); ?>
                    </section>
                <?php endif; ?>

                <section class="aat-panel aat-panel-wide">
                    <h2>Common tasks</h2>
                    <?php $this->shortcuts_widget(); ?>
                </section>

                <?php if (class_exists('WooCommerce')): ?>
                    <section class="aat-panel">
                        <h2>Recent orders</h2>
                        <?php $this->woocommerce_widget(); ?>
                    </section>
                <?php endif; ?>

                <?php if (!empty($s['enable_recent_content'])): ?>
                    <section class="aat-panel">
                        <h2>Recently edited content</h2>
                        <?php $this->recent_content_widget(); ?>
                    </section>
                <?php endif; ?>

                <section class="aat-panel">
                    <h2>Support</h2>
                    <?php $this->support_widget(); ?>
                </section>

                <section class="aat-panel aat-panel-wide">
                    <h2>Client instructions</h2>
                    <div class="aat-instruction-grid">
                        <?php foreach ((array)$s['instructions'] as $key => $message): ?>
                            <?php if (!$message) continue; ?>
                            <div class="aat-instruction-card">
                                <h3><?php echo esc_html(ucfirst(str_replace('_', ' ', $key))); ?></h3>
                                <p><?php echo wp_kses_post($message); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <?php $this->render_dashboard_footer_card(); ?>
        </div>
        <?php
    }


    public function site_snapshot_widget() {
        $items = [];
        if (current_user_can('edit_pages')) {
            $counts = wp_count_posts('page');
            $items[] = ['label' => 'Published pages', 'value' => isset($counts->publish) ? (int) $counts->publish : 0, 'url' => admin_url('edit.php?post_type=page')];
        }
        if (current_user_can('upload_files')) {
            $media_counts = wp_count_posts('attachment');
            $items[] = ['label' => 'Media files', 'value' => isset($media_counts->inherit) ? (int) $media_counts->inherit : 0, 'url' => admin_url('upload.php')];
        }
        if (class_exists('WooCommerce') && current_user_can('edit_products')) {
            $product_counts = wp_count_posts('product');
            $items[] = ['label' => 'Published products', 'value' => isset($product_counts->publish) ? (int) $product_counts->publish : 0, 'url' => admin_url('edit.php?post_type=product')];
        }
        if (function_exists('wc_get_orders') && current_user_can('edit_shop_orders')) {
            $processing_count = function_exists('wc_orders_count') ? wc_orders_count('processing') : 0;
            $items[] = ['label' => 'Processing orders', 'value' => (int) $processing_count, 'url' => admin_url('admin.php?page=wc-orders&status=wc-processing')];
        }
        if (empty($items)) {
            echo '<p>No snapshot items are available for this user role.</p>';
            return;
        }
        echo '<div class="aat-snapshot-grid">';
        foreach ($items as $item) {
            echo '<a class="aat-snapshot-item" href="' . esc_url($item['url']) . '"><strong>' . esc_html($item['value']) . '</strong><span>' . esc_html($item['label']) . '</span></a>';
        }
        echo '</div>';
    }

    public function recent_content_widget() {
        $post_types = [];
        if (current_user_can('edit_pages')) $post_types[] = 'page';
        if (current_user_can('edit_posts')) $post_types[] = 'post';
        if (post_type_exists('product') && current_user_can('edit_products')) $post_types[] = 'product';

        if (empty($post_types)) {
            echo '<p>No editable content is available for this user role.</p>';
            return;
        }

        $query = new WP_Query([
            'post_type' => $post_types,
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => 5,
            'orderby' => 'modified',
            'order' => 'DESC',
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
        ]);

        if (!$query->have_posts()) {
            echo '<p>No recent content found.</p>';
            return;
        }

        echo '<ul class="aat-recent-content-list">';
        while ($query->have_posts()) {
            $query->the_post();
            $edit_url = get_edit_post_link(get_the_ID(), 'raw');
            $type = get_post_type_object(get_post_type());
            echo '<li><a href="' . esc_url($edit_url) . '">' . esc_html(get_the_title() ?: '(no title)') . '</a><span>' . esc_html($type ? $type->labels->singular_name : get_post_type()) . ' · ' . esc_html(get_the_modified_date()) . '</span></li>';
        }
        wp_reset_postdata();
        echo '</ul>';
    }

    public function widgets() {
        if (!$this->core->user_is_affected() || empty($this->core->settings['enable_dashboard_widgets'])) return;
        wp_add_dashboard_widget('aat_welcome', esc_html($this->core->settings['dashboard_title']), [$this, 'welcome_widget']);
        wp_add_dashboard_widget('aat_shortcuts', 'Website Shortcuts', [$this, 'shortcuts_widget']);
        wp_add_dashboard_widget('aat_support', 'Agency Support', [$this, 'support_widget']);
        if (class_exists('WooCommerce')) {
            wp_add_dashboard_widget('aat_woocommerce_snapshot', 'Store Snapshot', [$this, 'woocommerce_widget']);
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
        echo '<div class="aat-widget">' . wp_kses_post(wpautop($this->core->settings['welcome_message'])) . '<p><strong>Tip:</strong> When in doubt, use the support button before changing technical settings.</p></div>';
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
            echo '<a class="aat-shortcut" href="' . esc_url($url) . '">' . esc_html($shortcut['label']) . '</a>';
        }
        echo '</div>';
    }

    public function support_widget() {
        $s = $this->core->settings;
        echo '<div class="aat-widget"><p>Need help with the website? Send a request with page context so your agency can respond faster.</p>';
        echo '<button type="button" class="button button-primary aat-open-support">' . esc_html($s['support_button_label']) . '</button>';
        if (!empty($s['support_url'])) {
            echo ' <a class="button" target="_blank" rel="noopener noreferrer" href="' . esc_url($s['support_url']) . '">Open Support Portal</a>';
        }
        echo '</div>';
    }

    public function woocommerce_widget() {
        if (!function_exists('wc_get_orders')) {
            echo '<p>WooCommerce is active, but order helper functions are not available.</p>';
            return;
        }
        $orders = wc_get_orders(['limit' => 5, 'orderby' => 'date', 'order' => 'DESC', 'return' => 'objects']);
        if (empty($orders)) {
            echo '<p>No recent orders found.</p>';
            return;
        }
        echo '<ul class="aat-order-list">';
        foreach ($orders as $order) {
            echo '<li><a href="' . esc_url($order->get_edit_order_url()) . '">Order #' . esc_html($order->get_order_number()) . '</a> · ' . esc_html(wc_get_order_status_name($order->get_status())) . ' · ' . wp_kses_post($order->get_formatted_order_total()) . '</li>';
        }
        echo '</ul>';
    }

    public function contextual_instructions() {
        if (!$this->core->user_is_affected() || empty($this->core->settings['enable_dashboard_widgets'])) return;
        $screen = get_current_screen();
        if (!$screen) return;
        $instructions = (array)$this->core->settings['instructions'];
        $message = '';
        if ($screen->id === 'edit-product' || $screen->post_type === 'product') $message = $instructions['products'] ?? '';
        if ($screen->id === 'edit-shop_order' || $screen->id === 'woocommerce_page_wc-orders') $message = $instructions['orders'] ?? '';
        if ($screen->id === 'edit-page' || $screen->post_type === 'page') $message = $instructions['pages'] ?? '';
        if ($screen->id === 'upload') $message = $instructions['media'] ?? '';
        if ($message) echo '<div class="notice aat-client-note"><p>' . wp_kses_post($message) . '</p></div>';
    }
}
