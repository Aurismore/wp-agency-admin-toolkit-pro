=== WP Agency Admin Toolkit Pro ===
Contributors: wpclienttools, creativedigitalmedia
Tags: admin, dashboard, agency, woocommerce, client dashboard, white label
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.22
License: GPLv2 or later

White-label WordPress and WooCommerce admin cleanup toolkit for agencies. Distributed through WP Client Tools and created by Creative Digital Media.

== Description ==

WP Agency Admin Toolkit Pro helps agencies create cleaner, safer client admin experiences in WordPress. It can add a custom client dashboard, hide unnecessary admin areas, restrict risky settings, brand the login/admin experience, and add support request workflows.

This product is distributed and supported through WP Client Tools. The plugin is created by Creative Digital Media, keeping the agency brand separate from the software sales and licensing brand.

== Features ==

* Custom client dashboard landing page
* Role-based client safe mode
* Admin menu cleanup
* Risky admin page restrictions
* Login page branding with site logo and agency support bar
* Full-screen login background image option
* Floating support request button on client admin pages
* Support modal with email and optional webhook delivery
* Support categories, priority selector and local request log
* Generic, Slack-style and Discord-style webhook payloads
* WooCommerce-aware shortcuts and widgets
* Elementor settings hiding
* Rank Math overview hiding
* MyParcel widget hiding
* Yoast SEO, WP Rocket, LiteSpeed Cache and ACF cleanup helpers
* Import/export settings
* System Status diagnostics page
* Dashboard site snapshot and recently edited content cards

== Changelog ==

= 1.22 =
* Reorganised the source tree: plugin files now live at the repository root and the release ZIP is built into a `wp-agency-admin-toolkit-pro/` folder by the GitHub Actions workflow.
* Modernised the codebase under the `Aurismore\AAT\` namespace with a PSR-4-style autoloader. `AAT_*` class names are preserved via aliases so existing hook callbacks and integrations keep working.
* Standardised on the British "licence" spelling. Added `AAT_LICENCE_SERVER`; kept `AAT_LICENSE_SERVER` and `AAT_License` as aliases.
* Hardened the page-restriction logic: rules are now matched exactly against `pagenow` and `admin.php?page=…` keys instead of substring-matching `REQUEST_URI`, fixing cases where rules like `tools.php` could over-block unrelated URLs.
* Hardened branding output: option-name attributes are explicitly `esc_attr`-escaped, and CSS `url(...)` and `content` values use CSS-safe quoting.
* Replaced the homegrown webhook host check with `wp_safe_remote_post`. The original `gethostbyname` filter was vulnerable to DNS rebinding and gave a false sense of security; WordPress's own host filter is the real defence.
* Removed dead `hide_menus()` and `maybe_hide_wordpress_dashboard()` no-ops left over from 1.17.
* De-duplicated `get_site_logo_url()` (it lived in two classes) into a single `Core::get_site_logo_url()` helper.
* Admin-area hover colours are now derived from the configured primary colour instead of a hard-coded shade.
* Site Snapshot widget caches `wp_count_posts` results in a 5-minute per-user transient.
* Added Remove button to dashboard shortcuts editor and Clear button to the support log.
* Added a Slack-style webhook payload that was previously listed in the UI but unimplemented.
* Declared WooCommerce HPOS (custom order tables) compatibility.
* Added `uninstall.php` so plugin options, the support log, the `aat_client_admin` role and the `manage_agency_toolkit` capability are removed when the plugin is deleted.
* Added a strict `release.yml` workflow that verifies the tag matches the plugin header, the `AAT_VERSION` constant, and the readme `Stable tag` before publishing the ZIP. Added an `auto-tag.yml` workflow that creates the `v{version}` tag automatically on push to `main` so version bumps publish a release without manual tagging.
* Updated the WP Client Tools Licence Manager (WCTLM) client to match the v1.4.9 server contract: licence-check requests now include `wp_version` and `php_version` telemetry that the server records on the activation row; the `update-check` response is parsed for the new `update_available`, `channel`, `package`, `package_available`, `package_status`, `package_message`, `package_sha256`, `package_signature_ed25519` and `package_size_bytes` fields; and a daily background revalidation job calls the new `/wctlm/v1/check` endpoint so an expired or remotely-deactivated licence is reflected in the admin without a manual visit.
* Added a "Check licence now" admin button (next to "Clear update cache") that calls `/check` immediately.
* System Status now surfaces the cached remote version, update channel, last-checked timestamp and a truncated package SHA-256 so the operator can verify what the server claims about the ZIP before installing.

= 1.21 =
* Renamed the product build to WP Agency Admin Toolkit Pro.
* Replaced direct GitHub update settings with a WP Client Tools licence key workflow.
* Protected updates now run through the WP Client Tools Licence Manager endpoint.
* Removed customer-facing GitHub repository, asset and token fields from the plugin settings.
* Added licence activation/deactivation/status details inside the settings panel.

= 1.20 =
* Removed the pre-login support request button and modal from wp-login.php.
* Removed unauthenticated support AJAX submissions. Support requests now require a logged-in backend user.
* Added extra support form hardening: POST-only requests, logged-in capability checks, category validation and safer Reply-To handling.

= 1.19 =
* Improved mobile wp-login styling so the logo, form, fields, login button and support footer use full-width responsive spacing.

= 1.18 =
* Removed the custom update endpoint option; automatic updates now use WP Client Tools licence validation for the Pro build.
* Simplified the Updates settings panel to the minimum required licence key fields.
* Added WordPress colour pickers to colour settings while preserving typed hex input.
* Improved the styling around colour settings.
* Fixed Request Support modal availability for administrator/dashboard users.

= 1.17 =
* Renamed the plugin publicly to WP Agency Admin Toolkit.
* Replaced the main dashboard for all users while keeping the left wp-admin menu intact.
* Added GitHub Releases automatic updater support.

= 1.16 =
* Updated product positioning for WP Client Tools distribution.
* Kept Creative Digital Media as the creator/developer credit.
* Updated plugin URI and update placeholders to wpclienttools.com.
* Replaced Creative Digital Media defaults with neutral client-agency defaults for fresh installs.
* Added safe migration for untouched old default branding values.
* Updated settings copy so agency fields are clearly client-facing, not product-seller fields.
* Rechecked PHP syntax across plugin files.

= 1.15 =
* Added Licence & Updates panel for the Pro/private update workflow.
* Added private update checker scaffold.
* Added update details popup support through the WordPress plugin information API.
* Added update cache clearing tool and update diagnostics in System Status.
* Kept existing client dashboard, support and integration behaviour intact.


= 1.14 =
* Combined the planned v1.13 support-system release and v1.14 integration-polish release into one package.
* Added support request categories.
* Added priority selector to support requests.
* Added optional diagnostic consent message to the support modal.
* Added local support request log with the latest saved requests.
* Added webhook format selector for generic JSON, Slack-style and Discord-style payloads.
* Added plugin integration status panel.
* Added cleanup helpers for Yoast SEO, WP Rocket, LiteSpeed Cache and Advanced Custom Fields.
* Added extra system status rows for newly supported integrations.
* Added styling for support log and integration panels.
* Rechecked PHP syntax across plugin files.

= 1.12 =
* Added optional Site Snapshot card to the client dashboard.
* Added optional Recently Edited Content card to the client dashboard.
* Added dashboard layout selector for balanced, WooCommerce-focused and content-focused layouts.
* Improved spacing and padding around Import / Export, Reset and System Status boxes.
* Added dashboard card hover polish for snapshot items and recent content lists.
* Rechecked PHP syntax across plugin files.

= 1.11 =
* Added System Status diagnostics panel.
* Added copyable system report.
* Added safer settings export that excludes webhook data by default.
* Added full export option for trusted migrations.
* Added reset-to-defaults action.
* Added settings section navigation tabs.
* Hardened webhook URL validation.
* Added browser context to support requests.
* Tightened URL sanitisation for settings.
* Rechecked PHP syntax across plugin files.

= 1.10 =
* Added MyParcel dashboard widget hiding option.
* Kept v1.0.9 behaviour as base.

= 1.0.9 =
* Improved floating support button behaviour and styling.
* Added Rank Math Overview visibility control.

== Installation ==

1. Upload the plugin ZIP via Plugins > Add New > Upload Plugin.
2. Activate WP Agency Admin Toolkit Pro.
3. Configure settings under Settings > WP Agency Toolkit.
4. Select affected client roles and enable the modules you need.
