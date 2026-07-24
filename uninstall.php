<?php
/**
 * Uninstall WP Admin Toolkit Pro.
 *
 * Removes plugin options, the support tickets table, the legacy support log,
 * the update-info transient, the custom Client Admin role, and the
 * agency-toolkit capability that the plugin adds to the Administrator role.
 *
 * Settings are intentionally preserved across deactivation. They are only
 * cleared here, when the site owner explicitly deletes the plugin.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

if (is_multisite()) {
    $sites = get_sites(['fields' => 'ids', 'number' => 0]);
    foreach ($sites as $site_id) {
        switch_to_blog((int) $site_id);
        aat_uninstall_cleanup();
        restore_current_blog();
    }
} else {
    aat_uninstall_cleanup();
}

function aat_uninstall_cleanup() {
    global $wpdb;

    delete_option('aat_settings');
    delete_option('aat_support_log');
    delete_option('aat_product_brand_version');
    delete_option('aat_db_version');
    delete_site_transient('aat_remote_update_info');

    // Remove the support tickets table. $wpdb->prefix follows the switched
    // blog on multisite, so each site's table is dropped in turn.
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}aat_tickets");

    // Clear the daily WCTLM /check revalidation cron, if scheduled.
    if (function_exists('wp_clear_scheduled_hook')) {
        wp_clear_scheduled_hook('aat_licence_daily_check');
    }

    if (function_exists('remove_role')) {
        remove_role('aat_client_admin');
    }

    $admin = get_role('administrator');
    if ($admin) {
        $admin->remove_cap('manage_agency_toolkit');
    }
}
