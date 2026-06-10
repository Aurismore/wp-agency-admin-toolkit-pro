<?php
/**
 * Back-compat: keep the original AAT_* class names available for any external
 * code that referenced them directly (custom child plugins, mu-plugins, etc.).
 *
 * class_alias triggers the autoloader for the namespaced source class, so the
 * order of these calls is not significant.
 */

if (!defined('ABSPATH')) exit;

$aat_class_aliases = [
    'AAT_Core'         => Aurismore\AAT\Core::class,
    'AAT_Admin'        => Aurismore\AAT\Admin::class,
    'AAT_Cleanup'      => Aurismore\AAT\Cleanup::class,
    'AAT_Branding'     => Aurismore\AAT\Branding::class,
    'AAT_Dashboard'    => Aurismore\AAT\Dashboard::class,
    'AAT_Support'      => Aurismore\AAT\Support::class,
    'AAT_Integrations' => Aurismore\AAT\Integrations::class,
    // Both spellings are aliased to the same namespaced class so existing
    // configs (AAT_License) and the new canonical name (AAT_Licence) work.
    'AAT_License'      => Aurismore\AAT\Licence::class,
    'AAT_Licence'      => Aurismore\AAT\Licence::class,
];

foreach ($aat_class_aliases as $alias => $target) {
    if (!class_exists($alias, false)) {
        class_alias($target, $alias);
    }
}
unset($aat_class_aliases);
