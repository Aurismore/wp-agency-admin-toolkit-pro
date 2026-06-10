<?php
namespace Aurismore\AAT;

if (!defined('ABSPATH')) exit;

class Admin {
    private $core;

    public function __construct(Core $core) {
        $this->core = $core;
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'assets']);
        add_action('admin_post_aat_export', [$this, 'export']);
        add_action('admin_post_aat_import', [$this, 'import']);
        add_action('admin_post_aat_reset_defaults', [$this, 'reset_defaults']);
        add_action('admin_post_aat_save_license', [$this, 'save_license']);
        add_action('admin_post_aat_clear_support_log', [$this, 'clear_support_log']);
        add_filter('plugin_action_links_' . plugin_basename(AAT_FILE), [$this, 'plugin_links']);
    }

    public function plugin_links($links) {
        $url = admin_url('options-general.php?page=wp-agency-admin-toolkit');
        array_unshift($links, '<a href="' . esc_url($url) . '">Settings</a>');
        return $links;
    }

    public function menu() {
        add_options_page('WP Agency Admin Toolkit Pro', 'WP Agency Toolkit Pro', AAT_CAP, 'wp-agency-admin-toolkit', [$this, 'render']);
    }

    public function register_settings() {
        register_setting('aat_settings_group', AAT_OPTION, ['sanitize_callback' => [Core::class, 'sanitize_settings']]);
    }

    private function color_field($key, $label, $value, $description = '') {
        $opt = esc_attr(AAT_OPTION);
        echo '<div class="aat-color-setting">';
        echo '<label for="aat-' . esc_attr($key) . '"><span>' . esc_html($label) . '</span></label>';
        echo '<div class="aat-color-control">';
        echo '<input id="aat-' . esc_attr($key) . '" type="text" class="aat-color-field" name="' . $opt . '[' . esc_attr($key) . ']" value="' . esc_attr($value) . '" data-default-color="' . esc_attr($value) . '" placeholder="#17243B">';
        echo '</div>';
        if ($description) echo '<p class="description">' . esc_html($description) . '</p>';
        echo '</div>';
    }

    public function assets($hook) {
        wp_enqueue_style('aat-admin', AAT_URL . 'assets/css/admin.css', [], AAT_VERSION);
        wp_enqueue_script('aat-admin', AAT_URL . 'assets/js/admin.js', ['jquery', 'wp-color-picker'], AAT_VERSION, true);
        if ($hook === 'settings_page_wp-agency-admin-toolkit') {
            wp_enqueue_media();
            wp_enqueue_style('wp-color-picker');
        }
        wp_localize_script('aat-admin', 'aatSupport', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aat_support_nonce'),
            'optionName' => AAT_OPTION,
        ]);
    }

    public function render() {
        if (!current_user_can(AAT_CAP)) wp_die('Access denied.');
        $s = Core::get_settings();
        $roles = wp_roles()->roles;
        $hidden_menu = implode("\n", (array)$s['hidden_menu']);
        $restricted_pages = implode("\n", (array)$s['restricted_pages']);
        $opt = esc_attr(AAT_OPTION);
        ?>
        <div class="wrap aat-wrap">
            <h1>WP Agency Admin Toolkit Pro</h1>
            <p class="aat-lead">Clean up client dashboards, brand the admin area, add support shortcuts and protect risky WordPress/WooCommerce settings.</p><p class="aat-product-note"><strong>WP Client Tools Pro product.</strong> WP Agency Admin Toolkit Pro is distributed through WP Client Tools and created by Creative Digital Media. Your own agency details below are what clients see inside their dashboard and support areas.</p>
            <div class="aat-admin-tabs" role="navigation" aria-label="WP Agency Toolkit sections">
                <a href="#aat-general">General</a>
                <a href="#aat-dashboard">Dashboard</a>
                <a href="#aat-cleanup">Cleanup</a>
                <a href="#aat-branding">Branding</a>
                <a href="#aat-support-settings">Support</a>
                <a href="#aat-integrations">Integrations</a>
                <a href="#aat-tools">Tools</a>
                <a href="#aat-license">Licence &amp; Updates</a>
                <a href="#aat-status">System Status</a>
            </div>
            <?php if (isset($_GET['aat_imported'])): ?><div class="notice notice-success"><p>Settings imported successfully.</p></div><?php endif; ?>
            <?php if (isset($_GET['aat_reset'])): ?><div class="notice notice-success"><p>Defaults restored successfully.</p></div><?php endif; ?>
            <?php if (isset($_GET['aat_update_cache_cleared'])): ?><div class="notice notice-success"><p>Update cache cleared. WordPress will check WP Client Tools again.</p></div><?php endif; ?>
            <?php if (isset($_GET['aat_license_saved'])): ?><div class="notice notice-success"><p>Licence settings updated.</p></div><?php endif; ?>
            <?php if (isset($_GET['aat_support_log_cleared'])): ?><div class="notice notice-success"><p>Support request log cleared.</p></div><?php endif; ?>

            <form method="post" action="options.php">
                <?php settings_fields('aat_settings_group'); ?>
                <div class="aat-grid">
                    <section class="aat-card" id="aat-general">
                        <h2>General</h2>
                        <label>Agency name shown to clients <input type="text" name="<?php echo $opt; ?>[agency_name]" value="<?php echo esc_attr($s['agency_name']); ?>"></label>
                        <label>Agency website shown to clients <input type="url" name="<?php echo $opt; ?>[agency_url]" value="<?php echo esc_url($s['agency_url']); ?>"></label>
                        <label>Support email <input type="email" name="<?php echo $opt; ?>[support_email]" value="<?php echo esc_attr($s['support_email']); ?>"></label>
                        <label>External support URL <input type="url" name="<?php echo $opt; ?>[support_url]" value="<?php echo esc_url($s['support_url']); ?>"></label>
                        <label>Webhook URL <input type="url" name="<?php echo $opt; ?>[support_webhook]" value="<?php echo esc_url($s['support_webhook']); ?>"></label>
                        <label>Support button label <input type="text" name="<?php echo $opt; ?>[support_button_label]" value="<?php echo esc_attr($s['support_button_label']); ?>"></label>
                        <label>Logout redirect URL <input type="url" name="<?php echo $opt; ?>[logout_redirect_url]" value="<?php echo esc_url($s['logout_redirect_url']); ?>"></label>
                        <p class="description">Where affected client users are sent after logging out. Leave as the website homepage unless a custom thank-you or support page is preferred.</p>
                    </section>

                    <section class="aat-card">
                        <h2>Modules</h2>
                        <?php $checks = [
                            'client_safe_mode'=>'Client Safe Mode', 'hide_notices'=>'Hide noisy admin notices', 'enable_floating_support'=>'Floating request support button',
                            'enable_dashboard_widgets'=>'Fallback WordPress dashboard widgets', 'enable_custom_dashboard'=>'Custom client dashboard landing page', 'enable_site_snapshot'=>'Client dashboard site snapshot card', 'enable_recent_content'=>'Client dashboard recent content card', 'enable_login_branding'=>'Login page branding', 'enable_admin_branding'=>'Admin footer branding', 'enable_wp_admin_branding'=>'wp-admin visual branding', 'disable_admin_bar_for_clients'=>'Disable top admin bar for clients', 'hide_elementor_settings'=>'Hide Elementor settings from clients', 'hide_rank_math_overview'=>'Hide Rank Math Overview dashboard widget', 'hide_myparcel_overview'=>'Hide MyParcel dashboard widget', 'hide_yoast_overview'=>'Hide Yoast SEO dashboard widget', 'hide_wp_rocket_notices'=>'Hide WP Rocket client notices/widgets', 'hide_litespeed_notices'=>'Hide LiteSpeed Cache client notices/widgets', 'hide_acf_admin_from_clients'=>'Hide ACF field group admin from clients', 'enable_support_log'=>'Save support request log', 'login_hide_aux_links'=>'Hide lost password / return links on login'
                        ]; foreach ($checks as $key=>$label): ?>
                            <label class="aat-check"><input type="checkbox" name="<?php echo $opt; ?>[<?php echo esc_attr($key); ?>]" value="1" <?php checked(!empty($s[$key])); ?>> <?php echo esc_html($label); ?></label>
                        <?php endforeach; ?>
                    </section>

                    <section class="aat-card">
                        <h2>Affected roles</h2>
                        <p>Select client-facing roles. Administrators and agency toolkit managers are never restricted.</p>
                        <?php foreach ($roles as $role_key => $role): ?>
                            <label class="aat-check"><input type="checkbox" name="<?php echo $opt; ?>[affected_roles][]" value="<?php echo esc_attr($role_key); ?>" <?php checked(in_array($role_key, (array)$s['affected_roles'], true)); ?>> <?php echo esc_html($role['name']); ?></label>
                        <?php endforeach; ?>
                    </section>

                    <section class="aat-card" id="aat-branding">
                        <h2>Login branding</h2>
                        <p>This appears on <code>/wp-login.php</code>, before the user enters wp-admin. The main login logo automatically uses the current website logo/site icon.</p>
                        <label>Agency/support footer logo URL <input type="url" name="<?php echo $opt; ?>[login_logo_url]" value="<?php echo esc_url($s['login_logo_url']); ?>"></label>
                        <p class="description">Used for the agency logo in the support bar at the bottom of the login page and client dashboard. The main login logo is now pulled automatically from the current website logo.</p>
                        <label>Full-screen login background image</label>
                        <div class="aat-media-field">
                            <input type="hidden" class="aat-media-id" name="<?php echo $opt; ?>[login_background_image_id]" value="<?php echo esc_attr($s['login_background_image_id']); ?>">
                            <input type="url" class="aat-media-url" name="<?php echo $opt; ?>[login_background_image_url]" value="<?php echo esc_url($s['login_background_image_url']); ?>" placeholder="Upload or paste image URL">
                            <button type="button" class="button aat-media-upload" data-title="Choose login background" data-button="Use this background">Upload / choose image</button>
                            <button type="button" class="button aat-media-clear">Remove</button>
                        </div>
                        <?php if (!empty($s['login_background_image_url'])): ?>
                            <div class="aat-media-preview"><img src="<?php echo esc_url($s['login_background_image_url']); ?>" alt="Login background preview"></div>
                        <?php endif; ?>
                        <p class="description">Recommended background size: <strong>1920 × 1080px</strong> minimum, ideally <strong>2560 × 1440px</strong> for large screens. Use WebP/JPG, compressed below 500KB where possible. The upload button stores the image in the WordPress Media Library.</p>
                        <?php $this->color_field('login_background', 'Login background colour', $s['login_background'], 'Choose from the picker or type a hex value.'); ?>
                        <label>Background overlay <input type="text" name="<?php echo $opt; ?>[login_background_overlay]" value="<?php echo esc_attr($s['login_background_overlay']); ?>" placeholder="rgba(23,36,59,0.28)"></label>
                        <p class="description">Overlay accepts values such as <code>rgba(23,36,59,0.28)</code> so transparency can be controlled.</p>
                        <?php $this->color_field('login_button_color', 'Button colour', $s['login_button_color'], 'Used for the primary login button.'); ?>
                        <?php $this->color_field('login_accent_color', 'Accent colour', $s['login_accent_color'], 'Used for highlights and support accents.'); ?>
                        <label>Admin footer text <input type="text" name="<?php echo $opt; ?>[admin_footer_text]" value="<?php echo esc_attr($s['admin_footer_text']); ?>"></label>
                    </section>

                    <section class="aat-card">
                        <h2>wp-admin branding</h2>
                        <p>This customises the admin area after login for affected client roles. The wp-admin logo is now pulled automatically from the current website logo/site icon, so it does not need a separate setting.</p>
                        <div class="aat-colour-panel">
                            <?php $this->color_field('admin_primary_color', 'Primary colour', $s['admin_primary_color'], 'Used for buttons, dashboard hero areas and main admin accents.'); ?>
                            <?php $this->color_field('admin_accent_color', 'Accent colour', $s['admin_accent_color'], 'Used for highlights, borders and secondary visual details.'); ?>
                            <?php $this->color_field('admin_background_color', 'Admin background', $s['admin_background_color'], 'Used behind the branded admin dashboard panels.'); ?>
                        </div>
                        <label class="aat-check"><input type="checkbox" name="<?php echo $opt; ?>[admin_branding_for_agency]" value="1" <?php checked(!empty($s['admin_branding_for_agency'])); ?>> Also apply admin branding to agency administrators</label>
                        <p class="description">The WordPress admin bar logo is automatically hidden when wp-admin branding is active. Client roles can also have the full top WordPress admin bar removed from the Modules section.</p>
                    </section>

                    <section class="aat-card aat-wide" id="aat-cleanup">
                        <h2>Admin cleanup</h2>
                        <p>Enter one menu slug per line. Examples: <code>edit-comments.php</code>, <code>plugins.php</code>, <code>woocommerce-marketing</code>.</p>
                        <textarea name="<?php echo $opt; ?>[hidden_menu]" rows="9"><?php echo esc_textarea($hidden_menu); ?></textarea>
                    </section>

                    <section class="aat-card aat-wide">
                        <h2>Risky page restrictions</h2>
                        <p>Enter one blocked admin page per line. Direct URL visits by affected roles will be blocked. Rules are matched exactly against <code>pagenow</code> and <code>admin.php?page=…</code> keys.</p>
                        <textarea name="<?php echo $opt; ?>[restricted_pages]" rows="9"><?php echo esc_textarea($restricted_pages); ?></textarea>
                    </section>

                    <section class="aat-card aat-wide" id="aat-support-settings">
                        <h2>Support request system</h2>
                        <p>These options polish the Pro support workflow. Support requests can be sent by email, sent to a webhook and optionally saved in a local request log.</p>
                        <label>Support categories <textarea name="<?php echo $opt; ?>[support_categories]" rows="6"><?php echo esc_textarea($s['support_categories'] ?? ''); ?></textarea></label>
                        <p class="description">One category per line. These appear in the Request Support modal.</p>
                        <label>Webhook format
                            <select name="<?php echo $opt; ?>[support_webhook_template]">
                                <option value="generic" <?php selected($s['support_webhook_template'] ?? 'generic', 'generic'); ?>>Generic JSON</option>
                                <option value="slack" <?php selected($s['support_webhook_template'] ?? 'generic', 'slack'); ?>>Slack-style payload</option>
                                <option value="discord" <?php selected($s['support_webhook_template'] ?? 'generic', 'discord'); ?>>Discord-style payload</option>
                            </select>
                        </label>
                        <p class="description">Generic JSON works with most webhook services. Slack and Discord use a cleaner text/embed structure.</p>
                    </section>

                    <section class="aat-card aat-wide" id="aat-integrations">
                        <h2>Plugin integrations</h2>
                        <p>Detected plugin cleanup packs. These settings only affect selected client roles after login.</p>
                        <div class="aat-status-grid aat-integration-grid">
                            <?php foreach (Integrations::detected_plugins() as $plugin_name => $plugin_status): ?>
                                <div class="aat-status-row"><strong><?php echo esc_html($plugin_name); ?></strong><span><?php echo esc_html($plugin_status); ?></span></div>
                            <?php endforeach; ?>
                        </div>
                        <p class="description">The integration panel includes cleanup controls for Yoast SEO, WP Rocket, LiteSpeed Cache and Advanced Custom Fields while keeping existing Rank Math, MyParcel and Elementor controls.</p>
                    </section>

                    <section class="aat-card aat-wide" id="aat-dashboard">
                        <h2>Client dashboard</h2>
                        <label>Dashboard title <input type="text" name="<?php echo $opt; ?>[dashboard_title]" value="<?php echo esc_attr($s['dashboard_title']); ?>"></label>
                        <label>Dashboard layout
                            <select name="<?php echo $opt; ?>[dashboard_layout]">
                                <option value="balanced" <?php selected($s['dashboard_layout'], 'balanced'); ?>>Balanced client handover</option>
                                <option value="commerce" <?php selected($s['dashboard_layout'], 'commerce'); ?>>WooCommerce focused</option>
                                <option value="content" <?php selected($s['dashboard_layout'], 'content'); ?>>Content editing focused</option>
                            </select>
                        </label>
                        <p class="description">This changes the visual emphasis of the client dashboard without changing permissions or restrictions.</p>
                        <label>Welcome message <textarea name="<?php echo $opt; ?>[welcome_message]" rows="4"><?php echo esc_textarea($s['welcome_message']); ?></textarea></label>
                        <h3>Instruction boxes</h3>
                        <?php foreach ((array)$s['instructions'] as $key => $value): ?>
                            <label><?php echo esc_html(ucfirst($key)); ?> <textarea name="<?php echo $opt; ?>[instructions][<?php echo esc_attr($key); ?>]" rows="3"><?php echo esc_textarea($value); ?></textarea></label>
                        <?php endforeach; ?>
                    </section>

                    <section class="aat-card aat-wide">
                        <h2>Dashboard shortcuts</h2>
                        <div id="aat-shortcuts">
                            <?php foreach ((array)$s['shortcuts'] as $i => $shortcut): ?>
                                <div class="aat-shortcut-row">
                                    <input placeholder="Label" name="<?php echo $opt; ?>[shortcuts][<?php echo esc_attr($i); ?>][label]" value="<?php echo esc_attr($shortcut['label']); ?>">
                                    <input placeholder="URL" name="<?php echo $opt; ?>[shortcuts][<?php echo esc_attr($i); ?>][url]" value="<?php echo esc_attr($shortcut['url']); ?>">
                                    <input placeholder="Capability" name="<?php echo $opt; ?>[shortcuts][<?php echo esc_attr($i); ?>][cap]" value="<?php echo esc_attr($shortcut['cap']); ?>">
                                    <button type="button" class="button-link-delete aat-remove-shortcut" aria-label="Remove shortcut">&times;</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="button" id="aat-add-shortcut">Add shortcut</button>
                    </section>
                </div>
                <?php submit_button('Save WP Agency Toolkit Settings'); ?>
            </form>

            <div class="aat-card aat-wide" id="aat-license">
                <h2>Licence &amp; Updates</h2>
                <p>WP Agency Admin Toolkit Pro receives protected updates through WP Client Tools. Enter the customer licence key once; GitHub stays private and is not configured on client websites.</p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="aat_save_license">
                    <?php wp_nonce_field('aat_save_license'); ?>
                    <?php $latest_update = get_site_transient('aat_remote_update_info'); ?>
                    <?php $licence_status = sanitize_key($s['licence_status'] ?? 'inactive'); ?>
                    <div class="aat-license-grid aat-pro-license-grid">
                        <label>Licence key
                            <input type="password" name="<?php echo $opt; ?>[licence_key]" value="<?php echo esc_attr($s['licence_key'] ?? ''); ?>" autocomplete="off" placeholder="Enter your WP Client Tools licence key">
                        </label>
                        <div class="aat-license-summary">
                            <strong>Status</strong>
                            <span class="<?php echo $licence_status === 'active' ? 'aat-status-good' : 'aat-status-bad'; ?>"><?php echo esc_html(ucfirst($licence_status)); ?></span>
                            <?php if (!empty($s['licence_message'])): ?><small><?php echo esc_html($s['licence_message']); ?></small><?php endif; ?>
                        </div>
                        <div class="aat-license-summary">
                            <strong>Product</strong>
                            <span><?php echo esc_html(defined('AAT_PRODUCT_SLUG') ? AAT_PRODUCT_SLUG : 'wp-agency-admin-toolkit-pro'); ?></span>
                            <small>Update server: <?php echo esc_html(Licence::licence_server()); ?></small>
                        </div>
                        <div class="aat-license-summary">
                            <strong>Activations</strong>
                            <span><?php echo esc_html((int)($s['licence_activations_used'] ?? 0)); ?> / <?php echo !empty($s['licence_activation_limit']) ? esc_html((int)$s['licence_activation_limit']) : 'Unlimited'; ?></span>
                            <?php if (!empty($s['licence_expires_at'])): ?><small>Expires: <?php echo esc_html($s['licence_expires_at']); ?></small><?php else: ?><small>Lifetime or not yet confirmed</small><?php endif; ?>
                        </div>
                    </div>
                    <p>
                        <?php submit_button('Save key', 'secondary', 'aat_save_license_key', false); ?>
                        <?php submit_button('Activate licence', 'primary', 'aat_activate_license', false); ?>
                        <?php submit_button('Deactivate licence', 'secondary', 'aat_deactivate_license', false); ?>
                    </p>
                </form>
                <div class="aat-update-status">
                    <strong>Latest WP Client Tools update check:</strong>
                    <?php if (is_array($latest_update) && !empty($latest_update['checked_at'])): ?>
                        <span><?php echo esc_html($latest_update['checked_at']); ?></span>
                        <?php if (!empty($latest_update['error'])): ?><span class="aat-status-bad">Error: <?php echo esc_html($latest_update['error']); ?></span><?php endif; ?>
                        <?php if (!empty($latest_update['version'])): ?><span>Remote version: <?php echo esc_html($latest_update['version']); ?></span><?php endif; ?>
                    <?php else: ?>
                        <span>No protected update check has been cached yet.</span>
                    <?php endif; ?>
                </div>
                <p><a class="button button-secondary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=aat_clear_update_cache'), 'aat_clear_update_cache')); ?>">Clear update cache</a></p>
            </div>

            <div class="aat-card aat-wide" id="aat-tools">
                <h2>Import / Export</h2>
                <p>Export this setup and import it on another client website.</p>
                <p>Safe export excludes sensitive support webhook data. Use full export only for moving settings between your own trusted sites.</p>
                <p>
                    <a class="button button-secondary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=aat_export'), 'aat_export')); ?>">Export safe settings</a>
                    <a class="button button-secondary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=aat_export&include_sensitive=1'), 'aat_export')); ?>">Export full settings</a>
                </p>
                <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php?action=aat_import')); ?>">
                    <?php wp_nonce_field('aat_import'); ?>
                    <input type="file" name="aat_import_file" accept="application/json">
                    <?php submit_button('Import settings', 'secondary', 'submit', false); ?>
                </form>
            </div>

            <div class="aat-card aat-wide" id="aat-support-log">
                <h2>Support request log</h2>
                <p>Latest locally saved support requests. Disable "Save support request log" in Modules if you only want email/webhook delivery.</p>
                <?php $this->render_support_log(); ?>
                <?php $log = get_option('aat_support_log', []); if (is_array($log) && !empty($log)): ?>
                    <p>
                        <a class="button button-secondary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=aat_clear_support_log'), 'aat_clear_support_log')); ?>" onclick="return confirm('Clear all locally saved support requests?');">Clear support log</a>
                    </p>
                <?php endif; ?>
            </div>

            <div class="aat-card aat-wide">
                <h2>Reset</h2>
                <p>Restore WP Agency Admin Toolkit Pro defaults. This keeps the plugin active but replaces saved settings with the recommended WP Client Tools product configuration.</p>
                <p><a class="button button-secondary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=aat_reset_defaults'), 'aat_reset_defaults')); ?>" onclick="return confirm('Reset WP Agency Admin Toolkit Pro settings to defaults?');">Reset to defaults</a></p>
            </div>

            <div class="aat-card aat-wide" id="aat-status">
                <h2>System Status</h2>
                <p>Use this when troubleshooting client sites or preparing support requests. It contains environment details but avoids licence keys and webhook URLs.</p>
                <div class="aat-status-grid">
                    <?php foreach ($this->system_status_rows() as $label => $value): ?>
                        <div class="aat-status-row"><strong><?php echo esc_html($label); ?></strong><span><?php echo esc_html($value); ?></span></div>
                    <?php endforeach; ?>
                </div>
                <h3>Copy system report</h3>
                <textarea class="aat-system-report" rows="12" readonly><?php echo esc_textarea($this->system_report()); ?></textarea>
                <p class="description">Security checks added in v1.11 include safer exports, stricter import handling, webhook URL validation and a diagnostics view for support.</p>
            </div>
        </div>
        <?php
    }

    public function save_license() {
        if (!current_user_can(AAT_CAP) || !check_admin_referer('aat_save_license')) wp_die('Access denied.');
        $settings = Core::get_settings();
        $licence_key = sanitize_text_field($_POST[AAT_OPTION]['licence_key'] ?? '');
        $settings['licence_key'] = $licence_key;
        $settings['enable_private_updates'] = 1;

        if (isset($_POST['aat_activate_license'])) {
            $response = Licence::remote_request('activate', $licence_key);
            $settings = Licence::settings_from_licence_response($settings, $licence_key, $response);
        } elseif (isset($_POST['aat_deactivate_license'])) {
            if ($licence_key) {
                Licence::remote_request('deactivate', $licence_key);
            }
            $settings['licence_status'] = 'inactive';
            $settings['licence_message'] = 'Licence deactivated on this website.';
            $settings['licence_checked_at'] = gmdate('c');
        } else {
            $settings['licence_message'] = $licence_key ? 'Licence key saved. Activate it to enable protected updates.' : 'Licence key removed.';
            if (!$licence_key) {
                $settings['licence_status'] = 'inactive';
                $settings['licence_expires_at'] = '';
                $settings['licence_activations_used'] = 0;
                $settings['licence_activation_limit'] = 0;
            }
        }

        Core::update_settings($settings);
        delete_site_transient('aat_remote_update_info');
        wp_clean_plugins_cache(true);
        wp_safe_redirect(admin_url('options-general.php?page=wp-agency-admin-toolkit&aat_license_saved=1#aat-license'));
        exit;
    }

    public function export() {
        if (!current_user_can(AAT_CAP) || !check_admin_referer('aat_export')) wp_die('Access denied.');
        $settings = Core::get_settings();
        if (empty($_GET['include_sensitive'])) {
            $settings['support_webhook'] = '';
            $settings['licence_key'] = '';
            $settings['licence_status'] = 'inactive';
            $settings['licence_message'] = '';
        }
        $settings['_exported_by'] = 'WP Agency Admin Toolkit Pro ' . AAT_VERSION . ' via WP Client Tools';
        $settings['_exported_at'] = gmdate('c');
        nocache_headers();
        header('Content-Type: application/json; charset=' . get_option('blog_charset'));
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: attachment; filename="wp-agency-admin-toolkit-settings.json"');
        echo wp_json_encode($settings, JSON_PRETTY_PRINT);
        exit;
    }

    public function import() {
        if (!current_user_can(AAT_CAP) || !check_admin_referer('aat_import')) wp_die('Access denied.');

        if (empty($_FILES['aat_import_file']['tmp_name']) || !is_uploaded_file($_FILES['aat_import_file']['tmp_name'])) {
            wp_safe_redirect(admin_url('options-general.php?page=wp-agency-admin-toolkit'));
            exit;
        }

        $file = $_FILES['aat_import_file'];
        if (!empty($file['size']) && (int) $file['size'] > 1048576) {
            wp_die('Import file is too large.');
        }

        $json = file_get_contents($file['tmp_name']);
        if (!is_string($json) || strlen($json) > 1048576) {
            wp_die('Invalid import file.');
        }

        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            wp_die('Invalid JSON import file.');
        }

        unset($data['_exported_by'], $data['_exported_at']);
        Core::update_settings(Core::sanitize_settings($data));
        wp_safe_redirect(admin_url('options-general.php?page=wp-agency-admin-toolkit&aat_imported=1'));
        exit;
    }

    public function reset_defaults() {
        if (!current_user_can(AAT_CAP) || !check_admin_referer('aat_reset_defaults')) wp_die('Access denied.');
        Core::update_settings(Core::defaults());
        wp_safe_redirect(admin_url('options-general.php?page=wp-agency-admin-toolkit&aat_reset=1'));
        exit;
    }

    public function clear_support_log() {
        if (!current_user_can(AAT_CAP) || !check_admin_referer('aat_clear_support_log')) wp_die('Access denied.');
        delete_option('aat_support_log');
        wp_safe_redirect(admin_url('options-general.php?page=wp-agency-admin-toolkit&aat_support_log_cleared=1#aat-support-log'));
        exit;
    }

    private function render_support_log() {
        $log = get_option('aat_support_log', []);
        if (!is_array($log) || empty($log)) {
            echo '<p>No support requests have been logged yet.</p>';
            return;
        }
        echo '<div class="aat-support-log-list">';
        foreach (array_slice(array_reverse($log), 0, 10) as $item) {
            $when = !empty($item['created_at']) ? $item['created_at'] : '';
            echo '<div class="aat-support-log-item">';
            echo '<strong>' . esc_html($item['subject'] ?? 'Support request') . '</strong>';
            echo '<span>' . esc_html(($item['category'] ?? 'General') . ' · ' . ($item['priority'] ?? 'Normal') . ' · ' . $when) . '</span>';
            echo '<p>' . esc_html(wp_trim_words($item['message'] ?? '', 28)) . '</p>';
            echo '</div>';
        }
        echo '</div>';
    }

    private function system_status_rows() {
        global $wp_version;
        $theme = wp_get_theme();
        $uploads = wp_upload_dir();
        return [
            'Plugin version' => AAT_VERSION,
            'WordPress version' => $wp_version,
            'PHP version' => PHP_VERSION,
            'Site URL' => home_url('/'),
            'Admin email configured' => is_email($this->core->settings['support_email'] ?? '') ? 'Yes' : 'No',
            'Webhook configured' => !empty($this->core->settings['support_webhook']) ? 'Yes' : 'No',
            'WooCommerce' => defined('WC_VERSION') ? WC_VERSION : 'Not active',
            'Elementor' => defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : 'Not active',
            'Rank Math' => defined('RANK_MATH_VERSION') ? RANK_MATH_VERSION : 'Not active',
            'MyParcel' => defined('MYPARCEL_VERSION') ? MYPARCEL_VERSION : (class_exists('WC_MyParcel') ? 'Active' : 'Not active'),
            'Yoast SEO' => defined('WPSEO_VERSION') ? WPSEO_VERSION : 'Not active',
            'WP Rocket' => defined('WP_ROCKET_VERSION') ? WP_ROCKET_VERSION : 'Not active',
            'LiteSpeed Cache' => defined('LSCWP_V') ? LSCWP_V : (defined('LSCWP_VERSION') ? LSCWP_VERSION : 'Not active'),
            'Advanced Custom Fields' => defined('ACF_VERSION') ? ACF_VERSION : 'Not active',
            'Theme' => $theme->get('Name') . ' ' . $theme->get('Version'),
            'Multisite' => is_multisite() ? 'Yes' : 'No',
            'Uploads writable' => !empty($uploads['basedir']) && wp_is_writable($uploads['basedir']) ? 'Yes' : 'No',
            'DISALLOW_FILE_EDIT' => defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT ? 'Enabled' : 'Not enabled',
            'Affected roles' => implode(', ', (array) ($this->core->settings['affected_roles'] ?? [])),
            'Licence status' => ucfirst($this->core->settings['licence_status'] ?? 'inactive'),
            'Product slug' => defined('AAT_PRODUCT_SLUG') ? AAT_PRODUCT_SLUG : 'wp-agency-admin-toolkit-pro',
            'Update server' => Licence::licence_server(),
        ];
    }

    private function system_report() {
        $lines = ['WP Agency Admin Toolkit Pro System Report', 'Generated: ' . gmdate('c'), ''];
        foreach ($this->system_status_rows() as $label => $value) {
            $lines[] = $label . ': ' . $value;
        }
        return implode("\n", $lines);
    }
}
