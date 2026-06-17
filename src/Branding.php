<?php
namespace Aurismore\AAT;

if (!defined('ABSPATH')) exit;

class Branding {
    private $core;

    public function __construct(Core $core) {
        $this->core = $core;

        add_action('login_enqueue_scripts', [$this, 'login_styles'], 999);
        add_action('login_head', [$this, 'login_styles'], 999);
        add_action('login_footer', [$this, 'login_footer_bar'], 9);
        add_filter('login_headerurl', [$this, 'login_url'], 999);
        add_filter('login_headertext', [$this, 'login_title'], 999);
        // Multilingual sites get a Language dropdown above the login form in
        // WP 5.9+. Agencies running client sites in a single language asked
        // for it to be hidden — the branding's whole point is one consistent
        // experience and the dropdown reflows the form when WP swaps it for
        // the localised string assets.
        add_filter('login_display_language_dropdown', '__return_false', 999);

        add_filter('admin_footer_text', [$this, 'footer_text'], PHP_INT_MAX);
        add_filter('update_footer', [$this, 'footer_version'], PHP_INT_MAX);
        add_action('admin_footer', [$this, 'force_footer_text'], PHP_INT_MAX);

        add_action('admin_head', [$this, 'admin_styles'], 999);
    }

    public function branding_enabled($key) {
        return !empty($this->core->settings[$key]);
    }

    private function get_agency_logo_url() {
        return !empty($this->core->settings['login_logo_url']) ? esc_url($this->core->settings['login_logo_url']) : '';
    }

    public function login_styles() {
        $s = $this->core->settings;
        if (!$this->branding_enabled('enable_login_branding')) return;

        static $printed = false;
        if ($printed) return;
        $printed = true;

        $site_logo = esc_url(Core::get_site_logo_url());
        $background = sanitize_hex_color($s['login_background'] ?? '') ?: '#f7f8fb';
        $bg_image_raw = !empty($s['login_background_image_url']) ? esc_url_raw($s['login_background_image_url'], ['http', 'https']) : '';
        $bg_image_css = $bg_image_raw ? Core::css_quote($bg_image_raw) : '';
        $overlay = Core::sanitize_css_color($s['login_background_overlay'] ?? 'rgba(23,36,59,0.28)', 'rgba(23,36,59,0.28)');
        $button = sanitize_hex_color($s['login_button_color'] ?? '') ?: '#17243B';
        $accent = sanitize_hex_color($s['login_accent_color'] ?? '') ?: '#f4c7de';
        $button_dark = Core::darken_hex_color($button, 0.18);
        ?>
        <style id="aat-login-branding">
            html body.login {
                min-height: 100vh !important;
                margin: 0 !important;
            }
            body.login {
                background-color: <?php echo esc_attr($background); ?> !important;
                <?php if ($bg_image_css): ?>
                background-image: linear-gradient(<?php echo esc_attr($overlay); ?>, <?php echo esc_attr($overlay); ?>), url(<?php echo $bg_image_css; ?>) !important;
                background-size: cover !important;
                background-position: center center !important;
                background-repeat: no-repeat !important;
                background-attachment: fixed !important;
                <?php else: ?>
                background: <?php echo esc_attr($background); ?> !important;
                <?php endif; ?>
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                box-sizing: border-box !important;
                padding: 32px 16px 130px !important;
                position: relative !important;
            }
            body.login * { box-sizing: border-box; }
            body.login #login {
                width: 360px !important;
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 auto !important;
            }
            body.login h1 {
                margin: 0 0 22px !important;
                padding: 0 !important;
                text-align: center !important;
            }
            body.login h1 a {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                margin: 0 auto !important;
                width: 100% !important;
                max-width: 360px !important;
                <?php if ($site_logo): ?>
                height: 120px !important;
                font-size: 0 !important;
                background-image: url(<?php echo Core::css_quote($site_logo); ?>) !important;
                <?php else: ?>
                min-height: 72px !important;
                font-size: 24px !important;
                font-weight: 800 !important;
                letter-spacing: -0.03em !important;
                <?php endif; ?>
                padding: 18px !important;
                background-color: #fff !important;
                background-position: center center !important;
                background-repeat: no-repeat !important;
                background-size: contain !important;
                background-origin: content-box !important;
                border-radius: 18px !important;
                text-indent: 0 !important;
                overflow: hidden !important;
                outline: none !important;
                box-shadow: 0 20px 60px rgba(23,36,59,.12) !important;
                color: <?php echo esc_attr($button); ?> !important;
                line-height: 1.2 !important;
                text-decoration: none !important;
            }
            body.login form {
                margin-top: 0 !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
                border: 0 !important;
                border-radius: 18px !important;
                box-shadow: 0 20px 60px rgba(23,36,59,.12) !important;
            }
            body.login #nav,
            body.login #backtoblog,
            body.login .privacy-policy-page-link {
                text-align: center !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
            }
            <?php if (!empty($s['login_hide_aux_links'])): ?>
            body.login #nav,
            body.login #backtoblog { display: none !important; }
            <?php endif; ?>
            body.login label { color: <?php echo esc_attr($button); ?> !important; }
            body.login .button.wp-hide-pw { color: <?php echo esc_attr($button); ?> !important; }
            body.login.wp-core-ui .button-primary,
            body.login .wp-core-ui .button-primary,
            .wp-core-ui body.login .button-primary {
                background: <?php echo esc_attr($button); ?> !important;
                border-color: <?php echo esc_attr($button); ?> !important;
                box-shadow: none !important;
                border-radius: 10px !important;
                font-weight: 800 !important;
            }
            body.login #backtoblog a:hover,
            body.login #nav a:hover,
            body.login .privacy-policy-page-link a:hover { color: <?php echo esc_attr($accent); ?> !important; }
            body.login input:focus {
                border-color: <?php echo esc_attr($accent); ?> !important;
                box-shadow: 0 0 0 1px <?php echo esc_attr($accent); ?> !important;
            }
            /* "You are now logged out.", "Check your email for the
               confirmation link.", password-reset confirmation, etc. Default
               WP renders these as a flat .message box that looks foreign
               next to the rounded, shadowed form. Match the form's radius +
               soft shadow and use the accent colour as the left rule so the
               status box reads as part of the same panel. #login_error gets
               the same treatment with a red rule. */
            body.login .message,
            body.login #login_error,
            body.login .notice {
                width: 100% !important;
                margin: 0 0 22px !important;
                padding: 14px 18px !important;
                background: #fff !important;
                border: 0 !important;
                border-left: 4px solid <?php echo esc_attr($accent); ?> !important;
                border-radius: 14px !important;
                box-shadow: 0 20px 60px rgba(23,36,59,.12) !important;
                color: <?php echo esc_attr($button); ?> !important;
                font-weight: 600 !important;
                line-height: 1.4 !important;
            }
            body.login #login_error {
                border-left-color: #b91c1c !important;
            }
            body.login .message a,
            body.login #login_error a,
            body.login .notice a {
                color: <?php echo esc_attr($button); ?> !important;
                text-decoration: underline !important;
            }
            .aat-login-footer-card {
                position: fixed !important;
                left: 50% !important;
                bottom: 22px !important;
                transform: translateX(-50%) !important;
                width: 360px !important;
                max-width: calc(100vw - 32px) !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                background: #fff !important;
                border: 1px solid #dcdcde !important;
                border-radius: 18px !important;
                padding: 16px !important;
                box-shadow: 0 12px 30px rgba(23,36,59,.12) !important;
                z-index: 9999 !important;
            }
            .aat-login-footer-brand {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                width: 100% !important;
            }
            .aat-login-footer-logo-link {
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                text-decoration: none !important;
                color: <?php echo esc_attr($button); ?> !important;
                font-weight: 900 !important;
                font-size: 18px !important;
                letter-spacing: -0.03em !important;
            }
            .aat-login-footer-logo-link img {
                display: block !important;
                max-width: 100% !important;
                max-height: 48px !important;
                width: auto !important;
                height: auto !important;
            }
            .aat-login-footer-card .button,
            .aat-login-footer-card .button-primary {
                border-radius: 10px !important;
                border: 1px solid <?php echo esc_attr($button); ?> !important;
                background: <?php echo esc_attr($button); ?> !important;
                color: #fff !important;
                padding: 10px 16px !important;
                min-height: auto !important;
                line-height: 1.2 !important;
                font-weight: 800 !important;
                text-align: center !important;
                text-decoration: none !important;
                box-shadow: none !important;
            }
            .aat-login-footer-card .button:hover,
            .aat-login-footer-card .button:focus {
                background: <?php echo esc_attr($button_dark); ?> !important;
                border-color: <?php echo esc_attr($button_dark); ?> !important;
                color: #fff !important;
            }
            @media (max-height: 760px) {
                body.login { align-items: flex-start !important; padding-top: 24px !important; }
                .aat-login-footer-card { position: static !important; transform: none !important; margin: 28px auto 0 !important; }
            }
            @media (max-width: 520px) {
                html body.login { min-width: 0 !important; overflow-x: hidden !important; }
                body.login {
                    display: block !important;
                    width: 100% !important;
                    min-height: 100vh !important;
                    padding: 20px 16px 24px !important;
                    background-attachment: scroll !important;
                }
                body.login #login { width: 100% !important; max-width: 100% !important; padding: 0 !important; margin: 0 auto !important; }
                body.login h1 { width: 100% !important; margin: 0 0 16px !important; }
                body.login h1 a {
                    width: 100% !important;
                    max-width: 100% !important;
                    <?php if ($site_logo): ?>height: 96px !important;<?php else: ?>min-height: 84px !important;<?php endif; ?>
                    padding: 16px !important;
                    border-radius: 16px !important;
                }
                body.login form {
                    width: 100% !important;
                    max-width: 100% !important;
                    margin: 0 !important;
                    padding: 24px 18px !important;
                    border-radius: 16px !important;
                }
                body.login form p, body.login .user-pass-wrap, body.login .forgetmenot, body.login .submit { width: 100% !important; margin-left: 0 !important; margin-right: 0 !important; }
                body.login label { display: block !important; width: 100% !important; margin-bottom: 8px !important; line-height: 1.35 !important; }
                body.login input[type="text"], body.login input[type="password"], body.login input[type="email"] {
                    width: 100% !important; max-width: 100% !important; min-height: 48px !important;
                    margin: 0 0 16px !important; padding: 10px 44px 10px 12px !important;
                    font-size: 16px !important; border-radius: 10px !important;
                }
                body.login .wp-pwd { position: relative !important; width: 100% !important; display: block !important; }
                body.login .button.wp-hide-pw { right: 2px !important; top: 2px !important; width: 44px !important; height: 44px !important; min-width: 44px !important; min-height: 44px !important; padding: 0 !important; }
                body.login .forgetmenot { float: none !important; display: block !important; margin: 0 0 16px !important; }
                body.login .forgetmenot label { display: inline-flex !important; align-items: center !important; gap: 8px !important; margin: 0 !important; }
                body.login .forgetmenot input[type="checkbox"] { width: 18px !important; height: 18px !important; margin: 0 !important; }
                body.login .submit { float: none !important; clear: both !important; display: block !important; margin: 0 !important; padding: 0 !important; }
                body.login.wp-core-ui .button-primary, body.login .wp-core-ui .button-primary, .wp-core-ui body.login .button-primary {
                    width: 100% !important; min-height: 48px !important; margin: 0 !important;
                    padding: 12px 18px !important; text-align: center !important; font-size: 16px !important;
                }
                body.login #nav, body.login #backtoblog, body.login .privacy-policy-page-link {
                    width: 100% !important; margin: 16px 0 0 !important; padding: 0 !important; text-align: center !important;
                }
                .aat-login-footer-card {
                    position: static !important; left: auto !important; bottom: auto !important; transform: none !important;
                    width: 100% !important; max-width: 100% !important; display: block !important;
                    margin: 20px auto 0 !important; padding: 16px !important; text-align: center !important; border-radius: 16px !important;
                }
                .aat-login-footer-logo-link { justify-content: center !important; width: 100% !important; }
                .aat-login-footer-logo-link img { max-width: 100% !important; max-height: 48px !important; }
            }
        </style>
        <?php
    }

    public function login_footer_bar() {
        $s = $this->core->settings;
        if (!$this->branding_enabled('enable_login_branding')) return;
        $agency_url = !empty($s['agency_url']) ? $s['agency_url'] : home_url('/');
        $agency_logo = $this->get_agency_logo_url();
        ?>
        <div class="aat-login-footer-card">
            <div class="aat-login-footer-brand">
                <a href="<?php echo esc_url($agency_url); ?>" target="_blank" rel="noopener noreferrer" class="aat-login-footer-logo-link">
                    <?php if ($agency_logo): ?>
                        <img src="<?php echo esc_url($agency_logo); ?>" alt="<?php echo esc_attr($s['agency_name']); ?>">
                    <?php else: ?>
                        <span><?php echo esc_html($s['agency_name']); ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
        <?php
    }

    public function admin_styles() {
        $s = $this->core->settings;
        if (!$this->branding_enabled('enable_wp_admin_branding')) return;
        if (empty($s['admin_branding_for_agency']) && !$this->core->user_is_affected()) return;

        $primary = sanitize_hex_color($s['admin_primary_color'] ?? '') ?: '#17243B';
        $accent = sanitize_hex_color($s['admin_accent_color'] ?? '') ?: '#f4c7de';
        $background = sanitize_hex_color($s['admin_background_color'] ?? '') ?: '#f7f8fb';
        $primary_dark = Core::darken_hex_color($primary, 0.18);
        $logo_raw = esc_url_raw(Core::get_site_logo_url(), ['http', 'https']);
        ?>
        <style id="aat-wp-admin-branding">
            body.wp-admin { background: <?php echo esc_attr($background); ?> !important; }
            #wpadminbar { background: <?php echo esc_attr($primary); ?> !important; }
            #adminmenu, #adminmenuback, #adminmenuwrap { background: <?php echo esc_attr($primary); ?> !important; }
            #adminmenu a, #adminmenu .wp-submenu a, #adminmenu div.wp-menu-image:before { color: rgba(255,255,255,.92) !important; }
            #adminmenu .wp-submenu { background: <?php echo esc_attr($primary_dark); ?> !important; }
            #adminmenu li.menu-top:hover, #adminmenu li.opensub>a.menu-top, #adminmenu li>a.menu-top:focus,
            #adminmenu .wp-has-current-submenu .wp-submenu .wp-submenu-head,
            #adminmenu .wp-menu-arrow, #adminmenu .wp-menu-arrow div, #adminmenu li.current a.menu-top,
            #adminmenu li.wp-has-current-submenu a.wp-has-current-submenu { background: <?php echo esc_attr($accent); ?> !important; color: <?php echo esc_attr($primary); ?> !important; }
            #adminmenu li.current a.menu-top div.wp-menu-image:before,
            #adminmenu li.wp-has-current-submenu a.wp-has-current-submenu div.wp-menu-image:before { color: <?php echo esc_attr($primary); ?> !important; }
            .wrap h1, .wrap h2, .aat-panel h2, .aat-card h2 { color: <?php echo esc_attr($primary); ?>; }
            .wp-core-ui .button-primary { background: <?php echo esc_attr($primary); ?> !important; border-color: <?php echo esc_attr($primary); ?> !important; }
            .wp-core-ui .button-primary:hover, .wp-core-ui .button-primary:focus { background: <?php echo esc_attr($primary_dark); ?> !important; border-color: <?php echo esc_attr($primary_dark); ?> !important; }
            a, .subsubsub a .count { color: <?php echo esc_attr($primary); ?>; }
            a:hover, a:focus { color: <?php echo esc_attr($primary_dark); ?>; }
            .notice-info, div.updated { border-left-color: <?php echo esc_attr($accent); ?> !important; }
            #wp-admin-bar-wp-logo{display:none!important;}
            <?php if ($logo_raw): ?>
            #wp-admin-bar-root-default:before{
                content:'';display:block;float:left;width:32px;height:32px;margin:0 8px 0 4px;background:url(<?php echo Core::css_quote($logo_raw); ?>) center center/contain no-repeat;
            }
            <?php endif; ?>
        </style>
        <?php
    }

    public function login_url() {
        return home_url('/');
    }

    public function login_title() {
        return esc_attr(get_bloginfo('name'));
    }

    private function get_footer_html() {
        $agency = esc_html($this->core->settings['agency_name'] ?? '');
        $url = esc_url($this->core->settings['agency_url'] ?? '');
        $footer = esc_html($this->core->settings['admin_footer_text'] ?? '');

        if (!$footer) {
            $footer = $agency ? 'Managed by ' . $agency : '';
        }

        if ($url && $agency) {
            return $footer . ' · <a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $agency . '</a>';
        }
        return $footer;
    }

    public function footer_text($text) {
        if (!$this->branding_enabled('enable_admin_branding')) return $text;
        return $this->get_footer_html();
    }

    public function footer_version($text) {
        if (!$this->branding_enabled('enable_admin_branding')) return $text;
        return '';
    }

    public function force_footer_text() {
        if (!$this->branding_enabled('enable_admin_branding')) return;
        $footer = wp_kses_post($this->get_footer_html());
        ?>
        <script id="aat-force-admin-footer">
        (function(){
            var left = document.getElementById('footer-left');
            var right = document.getElementById('footer-upgrade');
            if (left) { left.innerHTML = <?php echo wp_json_encode($footer); ?>; }
            if (right) { right.innerHTML = ''; }
        }());
        </script>
        <style id="aat-force-admin-footer-style">
            #footer-left { font-weight: 600; }
            #footer-upgrade { display: none !important; }
        </style>
        <?php
    }
}
