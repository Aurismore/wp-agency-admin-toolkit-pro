=== WP Admin Toolkit Pro ===
Contributors: wpclienttools, lubiesfactory
Tags: admin, dashboard, agency, woocommerce, client dashboard, white label
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.30
License: GPLv2 or later

White-label WordPress and WooCommerce admin cleanup toolkit for agencies. Distributed through WP Client Tools and created by Lubies Factory.

== Description ==

WP Admin Toolkit Pro helps agencies create cleaner, safer client admin experiences in WordPress. It can add a custom client dashboard, hide unnecessary admin areas, restrict risky settings, brand the login/admin experience, and run a database-backed support ticket workflow.

This product is distributed and supported through WP Client Tools. The plugin is created by Lubies Factory, keeping the agency brand separate from the software sales and licensing brand.

== Features ==

* Custom client dashboard landing page
* Role-based client safe mode
* Admin menu cleanup
* Risky admin page restrictions
* Separate admin pages per settings area under a top-level WP Admin Toolkit menu
* Login page branding with site logo and agency support bar
* Full-screen login background image option
* Floating support request button on client admin pages
* Support modal with email and optional webhook delivery
* Support requests stored as database tickets with a status workflow (New, In progress, Resolved, Closed)
* Tickets admin page with status filters, bulk actions and a detail view
* Support categories and priority selector
* Generic, Slack-style and Discord-style webhook payloads
* WooCommerce-aware shortcuts and widgets
* Elementor settings hiding
* Rank Math overview hiding
* MyParcel widget hiding
* Yoast SEO, WP Rocket, LiteSpeed Cache and ACF cleanup helpers
* Import/export settings
* System Status diagnostics page
* Dashboard site snapshot and recently edited content cards
* Fully translation-ready: every screen follows each admin user's own profile language, with Dutch (nl_NL) included

== Changelog ==

= 1.30 =
* Rebranded the plugin creator from Creative Digital Media to Lubies Factory across the plugin header (Author and Description), readme, the "created by" note on the General settings page, and the author link shown in the WordPress update-info popup. The Author URI is now https://lubies.nl.
* WP Client Tools remains the distribution, sales and licensing brand — the licence server, the "My Account" link and the product page are unchanged. Only the creator credit changed.
* Historical changelog entries and the one-time brand-default migration (which matches values saved by older versions) are intentionally left as-is so existing installs migrate correctly.
* Updated the Dutch (nl_NL) translation for the two affected sentences.

= 1.29 =
* The Licence & Updates panel's Subscription row now shows the real purchased product name returned by the WP Client Tools Licence Manager (server 1.4.14+), instead of a hardcoded product name. It falls back to "WP Admin Toolkit Pro" until the licence has been checked against a server that supplies the name.
* Stored the product name in the plugin settings from the activate/check response, and cleared it when the licence key is removed. No new user-facing strings were added.
* Compatible with the WCTLM response change that returns `key_last4` instead of the full licence key in the licence block — the plugin never read that field, so nothing else changes.

= 1.28 =
* **Redesigned the Licence & Updates page** into a clean row-based licence panel modelled on Elementor's licence screen: a status row ("Status: Active" in green) with "Check licence status" and "My Account" buttons, a subscription row showing the product and expiry date, a "You're connected with licence key ••••••••1234" row with a "Switch licence key" flow that only reveals the key input when needed, and a "Want to deactivate the licence for any reason?" row with a Disconnect button (with confirmation).
* The licence key is no longer shown in an always-visible field; it appears masked (fixed-length bullets plus the last 4 characters). When no key is activated yet, the panel shows the key input with an Activate button directly.
* "Check licence status" on the licence page returns to the licence page (the same button on Tools still returns to System Status). "My Account" opens the WP Client Tools account page; the URL is filterable via `aat_account_url`.
* Disconnect now falls back to the stored key for the remote deactivation call if the form's key field is empty, so the deactivation always reaches WP Client Tools.
* All new panel strings are translated, including Dutch (nl_NL).

= 1.27 =
* **The dashboard and all plugin screens now automatically show in each admin user's own language.** Every plugin string (~320) is wrapped in WordPress translation functions under the `wp-agency-admin-toolkit` text domain, so the per-user profile language (Users > Profile > Language) that WordPress already resolves for wp-admin now applies to the client dashboard, all nine settings pages, the support modal and its responses, the ticket screens, and the protected-page block screen. Users without a profile language get the site language, as always.
* Ships a complete Dutch (nl_NL) translation (`.po`, `.mo` and the WP 6.5+ `.l10n.php` performance format) plus the POT template in `languages/` for adding further languages.
* Untouched default content still translates: the welcome message, instruction boxes, dashboard title, shortcut labels, support categories, footer text and support button label are operator content and stay exactly as typed — but while they are byte-identical to the shipped English default, they render in the viewing user's language instead.
* Ticket statuses and priorities keep their stored database values and translate for display only, so existing tickets and filters are unaffected. The site snapshot cache now stores label keys instead of label text, so cached labels follow the viewer's language.
* Support emails and webhook payloads to the agency are composed in the site's default language regardless of which language the submitting client uses; the on-screen confirmation the client sees stays in their own language. Delivered emails keep the ticket number.
* The handful of JavaScript strings (sending/sent/error states, shortcut editor placeholders) are translated server-side and passed to the script.
* Client Admin role name, licence/update messages and WooCommerce webhook field labels are translated at generation time.

= 1.26 =
* **Renamed the product to WP Admin Toolkit Pro.** The plugin file name, folder, product slug, option names and text domain are intentionally unchanged so existing installs keep receiving updates through WP Client Tools without re-activation.
* **Separate admin pages.** The single settings screen is replaced by a top-level "WP Admin Toolkit" menu with dedicated pages: General, Client Dashboard, Branding, Cleanup, Support, Tickets, Integrations, Tools and Licence & Updates. The old Settings > WP Agency Toolkit URL redirects to the new menu, and the pill navigation now links between real pages.
* Settings saves are per-page: each page submits a screen marker and the sanitizer only updates that page's fields, so saving one page can no longer reset another page's values (including unchecked checkboxes).
* **Support tickets are now stored in the database.** Requests are saved to a dedicated `aat_tickets` table with a status workflow (New, In progress, Resolved, Closed) *before* email/webhook delivery is attempted, so a request is no longer lost when delivery fails or nothing is configured. Delivered emails and ticket confirmations include the ticket number.
* New Tickets admin page with status filter views and counts, search, bulk status changes, delete, a detail view showing the full message and consented diagnostics, and a "new tickets" count bubble on the admin menu.
* Entries in the old option-based support request log are migrated into the tickets table on upgrade (original dates kept, status New) and the old log option is removed. The tickets table is created via an `aat_db_version` upgrade gate, so sites that update in place get it without re-activating.
* Fixed: the "Admin menu cleanup" hidden-menu list is enforced again for affected client roles. The setting was saved but ignored since its consumer was removed as a dead no-op in v1.22.
* The operator buttons "Check licence now" and "Clear update cache" are available on the Tools page next to System Status.
* Uninstall now also drops the tickets table and the `aat_db_version` option.

= 1.25 =
* **Bug fix: "Update available" kept appearing after the plugin was already updated.** When AAT 1.23 polled /update-check, the WCTLM server replied with `{update_available: true, version: "1.24"}` and the response was cached in the `aat_remote_update_info` site transient for 6 hours. After the user installed 1.24, the next render of WP's update list read the cached payload, saw `update_available: true`, and offered an upgrade to a version that was already running. The flag was authoritative at cache-write time but stale after the in-place upgrade. Two-part fix: (1) `check_for_update()` now always recomputes `update_available` from `version_compare(AAT_VERSION, $remote['version'], '<')`, ignoring the cached server flag. (2) `get_remote_info()` treats the cache as stale when `$cached['current_version']` differs from the running `AAT_VERSION`, so the cache refreshes on the very next call after an upgrade rather than after a 6-hour timeout. Operators who want to force the refresh immediately can still use the existing "Clear update cache" button in the Licence & Updates panel.

= 1.24 =
* Login page footer card is now sized to match the login form width (360px) and uses equal padding on all sides so the agency logo sits centred with even space around it, instead of stretching to ~760px wide with an off-balance interior.
* New: the WordPress 5.9+ language switcher dropdown above the login form is now hidden. On multilingual installs this dropdown reflowed the form when WP swapped in localised strings and conflicted with the single-brand experience the toolkit is built around.
* "You are now logged out.", "Check your email for the confirmation link.", password-reset confirmations and login errors now render with the same rounded corners, soft drop shadow, and accent left-border as the login form itself, instead of WP's default flat info box. Login errors use a red left-border to stay distinguishable from informational messages.

= 1.23 =
* Simplified the customer-facing Licence & Updates panel. Removed the Product slug, Update server, Activations, "Latest update check" diagnostic block, and the "Check licence now" / "Clear update cache" operator buttons. Status now shows a clean "Active" / "Not active" / "Not activated" with the expiry date when available. The same diagnostics are still surfaced in the System Status tab for operators.
* Dropped the redundant "Save key" button; entering a key and clicking "Activate licence" saves and activates in one step.

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
2. Activate WP Admin Toolkit Pro.
3. Configure settings under the WP Admin Toolkit admin menu.
4. Select affected client roles and enable the modules you need.
