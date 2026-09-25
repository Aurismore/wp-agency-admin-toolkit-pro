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
        $layout = $this->current_layout();
        ?>
        <div class="wrap aat-client-dashboard aat-dashboard-layout-<?php echo esc_attr($layout); ?>">
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
                <?php foreach ($this->layout_panels($layout) as $panel_key => $wide) {
                    $this->render_grid_panel($panel_key, (bool) $wide, $s, $layout);
                } ?>
            </div>

            <?php $this->render_dashboard_footer_card(); ?>
        </div>
        <?php
    }

    /**
     * Resolve the configured dashboard layout to a known value.
     */
    private function current_layout() {
        $layout = sanitize_key($this->core->settings['dashboard_layout'] ?? 'balanced');
        return in_array($layout, ['balanced', 'commerce', 'content'], true) ? $layout : 'balanced';
    }

    /**
     * Ordered panels per layout, keyed by panel slug with a "wide" (full-width)
     * flag. This is what actually makes the three layouts differ: which panels
     * appear, in what order, and at what emphasis — not just CSS width.
     *
     *  - balanced: an even client-handover overview (this is the historic order).
     *  - commerce: store-first — KPIs and the sales chart lead; content drops down.
     *  - content: editing-first — recently edited content leads; store panels are
     *    omitted even when WooCommerce is active.
     */
    private function layout_panels($layout) {
        switch ($layout) {
            case 'commerce':
                return [
                    'snapshot' => true,
                    'sales_chart' => true,
                    'orders' => false,
                    'content' => false,
                    'tasks' => false,
                    'support' => false,
                    'instructions' => true,
                ];
            case 'content':
                return [
                    'content' => true,
                    'tasks' => true,
                    'snapshot' => false,
                    'support' => false,
                    'instructions' => true,
                ];
            case 'balanced':
            default:
                return [
                    'snapshot' => false,
                    'tasks' => true,
                    'orders' => false,
                    'content' => false,
                    'support' => false,
                    'instructions' => true,
                ];
        }
    }

    /**
     * Which snapshot metrics a layout emphasises: the store layout shows
     * commerce counts, the content layout shows content counts, balanced
     * shows everything.
     */
    private function snapshot_context($layout) {
        if ($layout === 'commerce') return 'commerce';
        if ($layout === 'content') return 'content';
        return 'full';
    }

    private function panel_available($key, $s) {
        switch ($key) {
            case 'snapshot':
                return !empty($s['enable_site_snapshot']);
            case 'sales_chart':
                return $this->sales_chart_enabled();
            case 'orders':
                return class_exists('WooCommerce') && function_exists('wc_get_orders');
            case 'content':
                return !empty($s['enable_recent_content']);
            case 'tasks':
            case 'support':
            case 'instructions':
                return true;
        }
        return false;
    }

    /**
     * Render one grid panel (heading + widget) if it is available for the
     * current user and settings.
     */
    private function render_grid_panel($key, $wide, $s, $layout) {
        if (!$this->panel_available($key, $s)) return;

        $classes = ['aat-panel'];
        if ($wide) $classes[] = 'aat-panel-wide';
        if ($key === 'snapshot') $classes[] = 'aat-site-snapshot-panel';
        if ($key === 'sales_chart') $classes[] = 'aat-sales-panel';

        echo '<section class="' . esc_attr(implode(' ', $classes)) . '">';
        switch ($key) {
            case 'snapshot':
                echo '<h2>' . esc_html__('Site snapshot', 'wp-agency-admin-toolkit') . '</h2>';
                $this->site_snapshot_widget($this->snapshot_context($layout));
                break;
            case 'sales_chart':
                echo '<h2>' . esc_html__('Sales overview', 'wp-agency-admin-toolkit') . '</h2>';
                $this->sales_chart_widget();
                break;
            case 'orders':
                echo '<h2>' . esc_html__('Recent orders', 'wp-agency-admin-toolkit') . '</h2>';
                $this->woocommerce_widget();
                break;
            case 'content':
                echo '<h2>' . esc_html__('Recently edited content', 'wp-agency-admin-toolkit') . '</h2>';
                $this->recent_content_widget();
                break;
            case 'tasks':
                echo '<h2>' . esc_html__('Common tasks', 'wp-agency-admin-toolkit') . '</h2>';
                $this->shortcuts_widget();
                break;
            case 'support':
                echo '<h2>' . esc_html__('Support', 'wp-agency-admin-toolkit') . '</h2>';
                $this->support_widget();
                break;
            case 'instructions':
                echo '<h2>' . esc_html__('Client instructions', 'wp-agency-admin-toolkit') . '</h2>';
                $this->instructions_widget();
                break;
        }
        echo '</section>';
    }

    private function instructions_widget() {
        $s = $this->core->settings;
        echo '<div class="aat-instruction-grid">';
        foreach ((array) $s['instructions'] as $key => $message) {
            if (!$message) continue;
            echo '<div class="aat-instruction-card">';
            echo '<h3>' . esc_html(Core::instruction_heading($key)) . '</h3>';
            echo '<p>' . wp_kses_post(Core::translated_instruction($key, $message)) . '</p>';
            echo '</div>';
        }
        echo '</div>';
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
    public function site_snapshot_widget($context = 'full') {
        $context = in_array($context, ['full', 'commerce', 'content'], true) ? $context : 'full';
        $show_content = ($context === 'full' || $context === 'content');
        $show_commerce = ($context === 'full' || $context === 'commerce');
        $cache_key = 'aat_site_snapshot_' . $context . '_' . get_current_user_id();
        $items = get_transient($cache_key);

        if ($items === false) {
            $items = [];
            if ($show_content && current_user_can('edit_pages')) {
                $counts = wp_count_posts('page');
                $items[] = ['label_key' => 'pages', 'value' => isset($counts->publish) ? (int) $counts->publish : 0, 'url' => admin_url('edit.php?post_type=page')];
            }
            if ($show_content && current_user_can('upload_files')) {
                $media_counts = wp_count_posts('attachment');
                $items[] = ['label_key' => 'media', 'value' => isset($media_counts->inherit) ? (int) $media_counts->inherit : 0, 'url' => admin_url('upload.php')];
            }
            if ($show_commerce && class_exists('WooCommerce') && current_user_can('edit_products')) {
                $product_counts = wp_count_posts('product');
                $items[] = ['label_key' => 'products', 'value' => isset($product_counts->publish) ? (int) $product_counts->publish : 0, 'url' => admin_url('edit.php?post_type=product')];
            }
            if ($show_commerce && function_exists('wc_orders_count') && current_user_can('edit_shop_orders')) {
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
            $created = $order->get_date_created();
            $date = $created ? wc_format_datetime($created, get_option('date_format')) : '';
            echo '<li><a href="' . esc_url($order->get_edit_order_url()) . '">' . esc_html($order_label) . '</a> · ' . esc_html(wc_get_order_status_name($order->get_status())) . ' · ' . wp_kses_post($order->get_formatted_order_total());
            if ($date !== '') {
                echo ' · <span class="aat-order-date">' . esc_html($date) . '</span>';
            }
            echo '</li>';
        }
        echo '</ul>';
    }

    /* ---------------------------------------------------------------------
     * Sales chart (WooCommerce-focused layout)
     * ------------------------------------------------------------------- */

    /**
     * The sales chart shows revenue, so it is limited to WooCommerce being
     * active, the toggle being on, and the viewer having the WooCommerce
     * reporting capability. Client roles without that capability simply do
     * not see the panel.
     */
    private function sales_chart_enabled() {
        return !empty($this->core->settings['enable_sales_chart'])
            && class_exists('WooCommerce')
            && function_exists('wc_get_orders')
            && current_user_can('view_woocommerce_reports');
    }

    private function format_price($amount) {
        $amount = (float) $amount;
        if (function_exists('wc_price')) {
            return trim(html_entity_decode(wp_strip_all_tags(wc_price($amount)), ENT_QUOTES, 'UTF-8'));
        }
        return number_format_i18n($amount, 2);
    }

    private function range_presets() {
        return [
            'this_month' => __('This month', 'wp-agency-admin-toolkit'),
            'last_7' => __('Last 7 days', 'wp-agency-admin-toolkit'),
            'last_30' => __('Last 30 days', 'wp-agency-admin-toolkit'),
            'last_90' => __('Last 90 days', 'wp-agency-admin-toolkit'),
            'this_year' => __('This year', 'wp-agency-admin-toolkit'),
        ];
    }

    private function parse_range_date($value, \DateTimeZone $tz) {
        $value = trim((string) $value);
        if ($value === '') return null;
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, $tz);
        $errors = \DateTimeImmutable::getLastErrors();
        if (!$d || ($errors && (!empty($errors['warning_count']) || !empty($errors['error_count'])))) {
            return null;
        }
        $year = (int) $d->format('Y');
        if ($year < 2000 || $year > 2100) return null;
        return $d;
    }

    private function format_date_range(\DateTimeInterface $start, \DateTimeInterface $end) {
        $fmt = get_option('date_format') ?: 'Y-m-d';
        return wp_date($fmt, $start->getTimestamp()) . ' – ' . wp_date($fmt, $end->getTimestamp());
    }

    /**
     * Resolve the selected date range and its comparison period from the
     * request. Presets each define their own comparison; a custom from/to
     * range is compared to the immediately preceding period of equal length.
     */
    private function current_sales_range() {
        $tz = wp_timezone();
        $today = new \DateTimeImmutable('now', $tz);
        $today_start = $today->setTime(0, 0, 0);
        $today_end = $today->setTime(23, 59, 59);

        $from = isset($_GET['aat_from']) ? $this->parse_range_date(sanitize_text_field(wp_unslash($_GET['aat_from'])), $tz) : null;
        $to = isset($_GET['aat_to']) ? $this->parse_range_date(sanitize_text_field(wp_unslash($_GET['aat_to'])), $tz) : null;
        if ($from && $to) {
            if ($to < $from) { $swap = $from; $from = $to; $to = $swap; }
            $start = $from->setTime(0, 0, 0);
            $end = $to->setTime(23, 59, 59);
            $days = (int) $start->diff($end->setTime(0, 0, 0))->days + 1;
            if ($days > 366) {
                $start = $end->setTime(0, 0, 0)->modify('-365 days');
                $days = 366;
            }
            $cmp_end = $start->modify('-1 day')->setTime(23, 59, 59);
            $cmp_start = $cmp_end->setTime(0, 0, 0)->modify('-' . ($days - 1) . ' days');
            return [
                'preset' => 'custom',
                'start' => $start, 'end' => $end,
                'cmp_start' => $cmp_start, 'cmp_end' => $cmp_end,
                'label' => $this->format_date_range($start, $end),
                'cmp_label' => __('Previous period', 'wp-agency-admin-toolkit'),
            ];
        }

        $preset = isset($_GET['aat_range']) ? sanitize_key(wp_unslash($_GET['aat_range'])) : 'this_month';

        if (in_array($preset, ['last_7', 'last_30', 'last_90'], true)) {
            $n = ['last_7' => 7, 'last_30' => 30, 'last_90' => 90][$preset];
            $start = $today_start->modify('-' . ($n - 1) . ' days');
            $end = $today_end;
            $cmp_end = $start->modify('-1 day')->setTime(23, 59, 59);
            $cmp_start = $cmp_end->setTime(0, 0, 0)->modify('-' . ($n - 1) . ' days');
            $labels = [
                'last_7' => [__('Last 7 days', 'wp-agency-admin-toolkit'), __('Previous 7 days', 'wp-agency-admin-toolkit')],
                'last_30' => [__('Last 30 days', 'wp-agency-admin-toolkit'), __('Previous 30 days', 'wp-agency-admin-toolkit')],
                'last_90' => [__('Last 90 days', 'wp-agency-admin-toolkit'), __('Previous 90 days', 'wp-agency-admin-toolkit')],
            ];
            return ['preset' => $preset, 'start' => $start, 'end' => $end, 'cmp_start' => $cmp_start, 'cmp_end' => $cmp_end, 'label' => $labels[$preset][0], 'cmp_label' => $labels[$preset][1]];
        }

        if ($preset === 'this_year') {
            $year = (int) $today->format('Y');
            $start = new \DateTimeImmutable($year . '-01-01 00:00:00', $tz);
            $end = $today_end;
            $days_in = (int) $start->diff($today_start)->days;
            $cmp_start = new \DateTimeImmutable(($year - 1) . '-01-01 00:00:00', $tz);
            $cmp_end = $cmp_start->modify('+' . $days_in . ' days')->setTime(23, 59, 59);
            return ['preset' => 'this_year', 'start' => $start, 'end' => $end, 'cmp_start' => $cmp_start, 'cmp_end' => $cmp_end, 'label' => __('This year', 'wp-agency-admin-toolkit'), 'cmp_label' => __('Last year', 'wp-agency-admin-toolkit')];
        }

        // Default: this month vs last month, aligned by day of month.
        $start = new \DateTimeImmutable($today->format('Y-m') . '-01 00:00:00', $tz);
        $end = $today_end;
        $prev_month_start = $start->modify('-1 month');
        $prev_month_last = $start->modify('-1 day')->setTime(23, 59, 59);
        $day_index = (int) $start->diff($today_start)->days;
        $cmp_end = $prev_month_start->modify('+' . $day_index . ' days')->setTime(23, 59, 59);
        if ($cmp_end > $prev_month_last) $cmp_end = $prev_month_last;
        return ['preset' => 'this_month', 'start' => $start, 'end' => $end, 'cmp_start' => $prev_month_start, 'cmp_end' => $cmp_end, 'label' => __('This month', 'wp-agency-admin-toolkit'), 'cmp_label' => __('Last month', 'wp-agency-admin-toolkit')];
    }

    /**
     * Daily net sales (order total minus refunds) for processing + completed
     * orders between two instants, bucketed by site-timezone day. The order
     * query is the expensive part, so the per-day map is cached for 30 minutes
     * per date span.
     */
    private function sales_series(\DateTimeInterface $start, \DateTimeInterface $end) {
        $tz = wp_timezone();
        $dates = [];
        $cursor = new \DateTimeImmutable($start->format('Y-m-d') . ' 00:00:00', $tz);
        $last = new \DateTimeImmutable($end->format('Y-m-d') . ' 00:00:00', $tz);
        while ($cursor <= $last) {
            $dates[] = $cursor->format('Y-m-d');
            $cursor = $cursor->modify('+1 day');
        }

        $cache_key = 'aat_sales_' . md5($start->format('Y-m-d') . '|' . $end->format('Y-m-d'));
        $cached = get_transient($cache_key);
        if (is_array($cached) && isset($cached['map']) && is_array($cached['map'])) {
            $map = $cached['map'];
        } else {
            $map = array_fill_keys($dates, 0.0);
            $orders = wc_get_orders([
                'status' => ['wc-processing', 'wc-completed'],
                'date_created' => $start->getTimestamp() . '...' . $end->getTimestamp(),
                'limit' => -1,
                'return' => 'objects',
                'type' => 'shop_order',
            ]);
            foreach ((array) $orders as $order) {
                if (!is_object($order) || !method_exists($order, 'get_date_created')) continue;
                $created = $order->get_date_created();
                if (!$created) continue;
                $key = wp_date('Y-m-d', $created->getTimestamp());
                if (!array_key_exists($key, $map)) continue;
                $net = (float) $order->get_total() - (float) $order->get_total_refunded();
                if ($net < 0) $net = 0.0;
                $map[$key] += $net;
            }
            set_transient($cache_key, ['map' => $map], 30 * MINUTE_IN_SECONDS);
        }

        $totals = [];
        $sum = 0.0;
        foreach ($dates as $dkey) {
            $v = isset($map[$dkey]) ? (float) $map[$dkey] : 0.0;
            $totals[] = $v;
            $sum += $v;
        }
        return ['dates' => $dates, 'totals' => $totals, 'sum' => $sum];
    }

    private function cumulative($arr) {
        $out = [];
        $run = 0.0;
        foreach ($arr as $v) {
            $run += (float) $v;
            $out[] = $run;
        }
        return $out;
    }

    public function sales_chart_widget() {
        if (!$this->sales_chart_enabled()) return;

        $range = $this->current_sales_range();
        $cur = $this->sales_series($range['start'], $range['end']);
        $cmp = $this->sales_series($range['cmp_start'], $range['cmp_end']);
        $cur_cum = $this->cumulative($cur['totals']);
        $cmp_cum = $this->cumulative($cmp['totals']);

        $primary = sanitize_hex_color($this->core->settings['admin_primary_color'] ?? '') ?: '#17243B';
        $muted = '#b8c0cc';

        $this->render_sales_range_picker($range);

        $delta = null;
        if ($cmp['sum'] > 0) {
            $delta = (($cur['sum'] - $cmp['sum']) / $cmp['sum']) * 100;
        }
        echo '<div class="aat-sales-summary">';
        echo '<div class="aat-sales-figure"><span class="aat-sales-dot" style="background:' . esc_attr($primary) . '"></span><span class="aat-sales-figure-label">' . esc_html($range['label']) . '</span><strong>' . esc_html($this->format_price($cur['sum'])) . '</strong></div>';
        echo '<div class="aat-sales-figure"><span class="aat-sales-dot aat-sales-dot-muted"></span><span class="aat-sales-figure-label">' . esc_html($range['cmp_label']) . '</span><strong>' . esc_html($this->format_price($cmp['sum'])) . '</strong></div>';
        if ($delta !== null) {
            $dir = $delta > 0.05 ? 'up' : ($delta < -0.05 ? 'down' : 'flat');
            $arrow = $dir === 'up' ? '▲' : ($dir === 'down' ? '▼' : '▬');
            echo '<div class="aat-sales-delta aat-sales-delta-' . esc_attr($dir) . '">' . esc_html($arrow . ' ' . number_format_i18n(abs($delta), 1) . '%') . '</div>';
        }
        echo '</div>';

        if ($cur['sum'] <= 0 && $cmp['sum'] <= 0) {
            echo '<p class="aat-sales-empty">' . esc_html__('No sales in this period yet.', 'wp-agency-admin-toolkit') . '</p>';
            return;
        }

        $this->render_sales_svg($range, $cur, $cmp, $cur_cum, $cmp_cum, $primary, $muted);
    }

    private function render_sales_range_picker($range) {
        $base = $this->client_dashboard_url();
        echo '<div class="aat-sales-range">';
        echo '<div class="aat-sales-presets" role="group" aria-label="' . esc_attr__('Date range', 'wp-agency-admin-toolkit') . '">';
        foreach ($this->range_presets() as $key => $label) {
            $url = add_query_arg(['aat_range' => $key], $base);
            $cls = ($range['preset'] === $key) ? 'aat-range-btn aat-range-active' : 'aat-range-btn';
            echo '<a class="' . esc_attr($cls) . '" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
        }
        echo '</div>';

        $from_val = ($range['preset'] === 'custom') ? $range['start']->format('Y-m-d') : '';
        $to_val = ($range['preset'] === 'custom') ? $range['end']->format('Y-m-d') : '';
        echo '<form class="aat-sales-custom" method="get" action="' . esc_url(admin_url('admin.php')) . '">';
        echo '<input type="hidden" name="page" value="wp-agency-admin-dashboard">';
        echo '<label class="screen-reader-text" for="aat-sales-from">' . esc_html__('From', 'wp-agency-admin-toolkit') . '</label>';
        echo '<input type="date" id="aat-sales-from" name="aat_from" value="' . esc_attr($from_val) . '">';
        echo '<span class="aat-sales-custom-sep" aria-hidden="true">–</span>';
        echo '<label class="screen-reader-text" for="aat-sales-to">' . esc_html__('To', 'wp-agency-admin-toolkit') . '</label>';
        echo '<input type="date" id="aat-sales-to" name="aat_to" value="' . esc_attr($to_val) . '">';
        echo '<button type="submit" class="button">' . esc_html__('Apply', 'wp-agency-admin-toolkit') . '</button>';
        echo '</form>';
        echo '</div>';
    }

    private function render_sales_svg($range, $cur, $cmp, $cur_cum, $cmp_cum, $primary, $muted) {
        $count_cur = count($cur_cum);
        $count_cmp = count($cmp_cum);
        $n = max($count_cur, $count_cmp, 2);

        $maxY = 1.0;
        foreach ($cur_cum as $v) { if ($v > $maxY) $maxY = $v; }
        foreach ($cmp_cum as $v) { if ($v > $maxY) $maxY = $v; }

        $W = 1000; $H = 340; $padT = 18; $padB = 22;
        $plotH = $H - $padT - $padB;
        $baseline = $padT + $plotH;
        $xi = function ($i) use ($n, $W) { return $n <= 1 ? 0.0 : round(($i / ($n - 1)) * $W, 2); };
        $yv = function ($v) use ($padT, $plotH, $maxY) { return round($padT + (1 - ($v / $maxY)) * $plotH, 2); };

        $cur_points = [];
        $y_cur = [];
        foreach ($cur_cum as $i => $v) { $cur_points[] = $xi($i) . ',' . $yv($v); $y_cur[] = $yv($v); }
        $cmp_points = [];
        $y_cmp = [];
        foreach ($cmp_cum as $i => $v) { $cmp_points[] = $xi($i) . ',' . $yv($v); $y_cmp[] = $yv($v); }
        $cur_line = implode(' ', $cur_points);
        $cmp_line = implode(' ', $cmp_points);

        $area = '';
        if ($count_cur > 0) {
            $area = 'M ' . $xi(0) . ',' . round($baseline, 2)
                . ' L ' . implode(' L ', $cur_points)
                . ' L ' . $xi($count_cur - 1) . ',' . round($baseline, 2) . ' Z';
        }

        $tz = wp_timezone();
        $labels = [];
        $xs = [];
        for ($i = 0; $i < $n; $i++) {
            $xs[] = $xi($i);
            $ds = isset($cur['dates'][$i]) ? $cur['dates'][$i] : (isset($cmp['dates'][$i]) ? $cmp['dates'][$i] : '');
            if ($ds !== '') {
                $ts = (new \DateTimeImmutable($ds . ' 12:00:00', $tz))->getTimestamp();
                $labels[] = wp_date('j M', $ts);
            } else {
                $labels[] = '#' . ($i + 1);
            }
        }
        $cur_fmt = array_map([$this, 'format_price'], $cur_cum);
        $cmp_fmt = array_map([$this, 'format_price'], $cmp_cum);

        $data = wp_json_encode([
            'n' => $n, 'W' => $W, 'baseline' => round($baseline, 2), 'padT' => $padT,
            'x' => $xs, 'yCur' => $y_cur, 'yCmp' => $y_cmp,
            'labels' => $labels, 'cur' => $cur_fmt, 'cmp' => $cmp_fmt,
            'curLabel' => $range['label'], 'cmpLabel' => $range['cmp_label'],
            'cCur' => $primary, 'cCmp' => $muted,
        ]);

        $aria = sprintf(
            /* translators: 1: current period label, 2: current total, 3: comparison period label, 4: comparison total. */
            __('Cumulative sales. %1$s: %2$s. %3$s: %4$s.', 'wp-agency-admin-toolkit'),
            $range['label'], $this->format_price($cur['sum']), $range['cmp_label'], $this->format_price($cmp['sum'])
        );

        echo '<div class="aat-sales-chart" data-series="' . esc_attr($data) . '">';
        echo '<svg viewBox="0 0 ' . $W . ' ' . $H . '" class="aat-sales-svg" role="img" aria-label="' . esc_attr($aria) . '" preserveAspectRatio="xMidYMid meet">';
        echo '<line x1="0" y1="' . round($baseline, 2) . '" x2="' . $W . '" y2="' . round($baseline, 2) . '" class="aat-sales-axis" />';
        if ($area !== '') {
            echo '<path d="' . esc_attr($area) . '" fill="' . esc_attr($primary) . '" fill-opacity="0.08" stroke="none" />';
        }
        echo '<polyline points="' . esc_attr($cmp_line) . '" fill="none" stroke="' . esc_attr($muted) . '" stroke-width="3" stroke-dasharray="7 7" stroke-linecap="round" stroke-linejoin="round" />';
        echo '<polyline points="' . esc_attr($cur_line) . '" fill="none" stroke="' . esc_attr($primary) . '" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />';
        echo '<line class="aat-sales-hairline" x1="0" y1="' . $padT . '" x2="0" y2="' . round($baseline, 2) . '" stroke="' . esc_attr($muted) . '" stroke-width="1.5" style="display:none" />';
        echo '<circle class="aat-sales-marker aat-sales-marker-cmp" r="6" fill="' . esc_attr($muted) . '" style="display:none" />';
        echo '<circle class="aat-sales-marker aat-sales-marker-cur" r="6" fill="' . esc_attr($primary) . '" style="display:none" />';
        echo '</svg>';
        echo '<div class="aat-sales-tooltip" role="status" style="display:none"></div>';
        echo '</div>';
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
