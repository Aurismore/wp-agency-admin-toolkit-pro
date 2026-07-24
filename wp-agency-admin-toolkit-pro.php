<?php
/**
 * Plugin Name: WP Admin Toolkit Pro
 * Plugin URI: https://lubies.shop
 * Description: Pro white-label WordPress and WooCommerce admin dashboard, support ticket and cleanup toolkit for agencies. Sold and supported through WP Client Tools, created by Lubies Factory.
 * Version: 1.31
 * Author: Lubies Factory
 * Author URI: https://lubies.nl
 * Text Domain: wp-agency-admin-toolkit
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AAT_VERSION', '1.31');
define('AAT_FILE', __FILE__);
define('AAT_PATH', plugin_dir_path(__FILE__));
define('AAT_URL', plugin_dir_url(__FILE__));
define('AAT_OPTION', 'aat_settings');
define('AAT_PRODUCT_SLUG', 'wp-agency-admin-toolkit-pro');
define('AAT_CAP', 'manage_agency_toolkit');

// Canonical name uses British spelling. The older AAT_LICENSE_SERVER stays defined
// for any external code or filters that referenced it.
define('AAT_LICENCE_SERVER', 'https://wpclienttools.com');
if (!defined('AAT_LICENSE_SERVER')) {
    define('AAT_LICENSE_SERVER', AAT_LICENCE_SERVER);
}

// PSR-4-style autoloader for Aurismore\AAT\* classes shipped in src/.
spl_autoload_register(function ($class) {
    $prefix = 'Aurismore\\AAT\\';
    $base_dir = AAT_PATH . 'src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';
    if (is_readable($file)) {
        require $file;
    }
});

// Back-compat: register AAT_* class aliases so external code/hooks keep working.
require_once AAT_PATH . 'includes/aliases.php';

register_activation_hook(__FILE__, ['Aurismore\\AAT\\Core', 'activate']);
register_deactivation_hook(__FILE__, ['Aurismore\\AAT\\Core', 'deactivate']);

add_action('plugins_loaded', function () {
    Aurismore\AAT\Core::instance();
});
