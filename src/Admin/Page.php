<?php
namespace Aurismore\AAT\Admin;

use Aurismore\AAT\Admin;
use Aurismore\AAT\Core;

if (!defined('ABSPATH')) exit;

/**
 * Base class for the toolkit's admin pages. Each page renders its own screen
 * under the top-level WP Admin Toolkit menu and, when it has a settings form,
 * declares which sanitizer screen it submits via screen() — see
 * Core::screen_fields() for how partial saves are merged.
 */
abstract class Page {
    protected $core;

    public function __construct(Core $core) {
        $this->core = $core;
    }

    abstract public function slug();

    abstract public function title();

    abstract protected function content($s);

    public function menu_label() {
        $nav = Admin::pages_nav();
        return $nav[$this->slug()] ?? $this->title();
    }

    /** Sanitizer screen key for this page's settings form; '' when the page has no options.php form. */
    public function screen() {
        return '';
    }

    public function url($args = []) {
        return add_query_arg($args, admin_url('admin.php?page=' . $this->slug()));
    }

    public function render() {
        if (!current_user_can(AAT_CAP)) wp_die('Access denied.');
        $s = Core::get_settings();
        echo '<div class="wrap aat-wrap">';
        echo '<h1>' . esc_html($this->title()) . '</h1>';
        $this->nav();
        $this->notices();
        $this->content($s);
        echo '</div>';
    }

    protected function nav() {
        $current = $this->slug();
        echo '<div class="aat-admin-tabs" role="navigation" aria-label="WP Admin Toolkit pages">';
        foreach (Admin::pages_nav() as $slug => $label) {
            $class = $slug === $current ? ' class="aat-tab-current"' : '';
            echo '<a' . $class . ' href="' . esc_url(admin_url('admin.php?page=' . $slug)) . '">' . esc_html($label) . '</a>';
        }
        echo '</div>';
    }

    protected function notices() {
        if (isset($_GET['settings-updated'])) {
            $this->notice('Settings saved.');
        }
    }

    protected function notice($message, $type = 'success') {
        echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
    }

    /**
     * Wrap fields in an options.php form carrying this page's screen marker.
     */
    protected function settings_form(callable $render_fields, $button_label = 'Save changes') {
        echo '<form method="post" action="' . esc_url(admin_url('options.php')) . '">';
        settings_fields('aat_settings_group');
        echo '<input type="hidden" name="' . esc_attr(AAT_OPTION) . '[_aat_screen]" value="' . esc_attr($this->screen()) . '">';
        $render_fields();
        submit_button($button_label);
        echo '</form>';
    }

    protected function checkbox($s, $key, $label) {
        $opt = esc_attr(AAT_OPTION);
        echo '<label class="aat-check"><input type="checkbox" name="' . $opt . '[' . esc_attr($key) . ']" value="1" ' . checked(!empty($s[$key]), true, false) . '> ' . esc_html($label) . '</label>';
    }

    protected function color_field($key, $label, $value, $description = '') {
        $opt = esc_attr(AAT_OPTION);
        echo '<div class="aat-color-setting">';
        echo '<label for="aat-' . esc_attr($key) . '"><span>' . esc_html($label) . '</span></label>';
        echo '<div class="aat-color-control">';
        echo '<input id="aat-' . esc_attr($key) . '" type="text" class="aat-color-field" name="' . $opt . '[' . esc_attr($key) . ']" value="' . esc_attr($value) . '" data-default-color="' . esc_attr($value) . '" placeholder="#17243B">';
        echo '</div>';
        if ($description) echo '<p class="description">' . esc_html($description) . '</p>';
        echo '</div>';
    }
}
