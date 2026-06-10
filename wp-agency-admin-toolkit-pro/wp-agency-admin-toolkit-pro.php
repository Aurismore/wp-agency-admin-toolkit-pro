<?php
/**
 * Plugin Name: WP Agency Admin Toolkit Pro
 * Plugin URI: https://wpclienttools.com/wp-agency-admin-toolkit-pro
 * Description: Pro white-label WordPress and WooCommerce admin dashboard, support and cleanup toolkit for agencies. Sold and supported through WP Client Tools, created by Creative Digital Media.
 * Version: 1.21
 * Author: Creative Digital Media
 * Author URI: https://creativedigitalmedia.nl
 * Text Domain: wp-agency-admin-toolkit
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AAT_VERSION', '1.21');
define('AAT_FILE', __FILE__);
define('AAT_PATH', plugin_dir_path(__FILE__));
define('AAT_URL', plugin_dir_url(__FILE__));
define('AAT_OPTION', 'aat_settings');
define('AAT_PRODUCT_SLUG', 'wp-agency-admin-toolkit-pro');
define('AAT_LICENSE_SERVER', 'https://wpclienttools.com');
define('AAT_CAP', 'manage_agency_toolkit');

require_once AAT_PATH . 'includes/class-aat-core.php';
require_once AAT_PATH . 'includes/class-aat-admin.php';
require_once AAT_PATH . 'includes/class-aat-cleanup.php';
require_once AAT_PATH . 'includes/class-aat-branding.php';
require_once AAT_PATH . 'includes/class-aat-dashboard.php';
require_once AAT_PATH . 'includes/class-aat-support.php';
require_once AAT_PATH . 'includes/class-aat-integrations.php';
require_once AAT_PATH . 'includes/class-aat-license.php';

register_activation_hook(__FILE__, ['AAT_Core', 'activate']);
register_deactivation_hook(__FILE__, ['AAT_Core', 'deactivate']);

add_action('plugins_loaded', function () {
    AAT_Core::instance();
});
