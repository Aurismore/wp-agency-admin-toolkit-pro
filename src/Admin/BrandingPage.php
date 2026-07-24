<?php
namespace Aurismore\AAT\Admin;

if (!defined('ABSPATH')) exit;

class BrandingPage extends Page {
    public function slug() {
        return 'wp-admin-toolkit-branding';
    }

    public function title() {
        return 'Branding';
    }

    public function screen() {
        return 'branding';
    }

    protected function content($s) {
        $opt = esc_attr(AAT_OPTION);
        $this->settings_form(function () use ($s, $opt) {
            ?>
            <div class="aat-grid">
                <section class="aat-card">
                    <h2>Branding modules</h2>
                    <?php
                    $this->checkbox($s, 'enable_login_branding', 'Login page branding');
                    $this->checkbox($s, 'enable_admin_branding', 'Admin footer branding');
                    $this->checkbox($s, 'enable_wp_admin_branding', 'wp-admin visual branding');
                    $this->checkbox($s, 'login_hide_aux_links', 'Hide lost password / return links on login');
                    ?>
                </section>

                <section class="aat-card">
                    <h2>wp-admin branding</h2>
                    <p>This customises the admin area after login for affected client roles. The wp-admin logo is pulled automatically from the current website logo/site icon, so it does not need a separate setting.</p>
                    <div class="aat-colour-panel">
                        <?php $this->color_field('admin_primary_color', 'Primary colour', $s['admin_primary_color'], 'Used for buttons, dashboard hero areas and main admin accents.'); ?>
                        <?php $this->color_field('admin_accent_color', 'Accent colour', $s['admin_accent_color'], 'Used for highlights, borders and secondary visual details.'); ?>
                        <?php $this->color_field('admin_background_color', 'Admin background', $s['admin_background_color'], 'Used behind the branded admin dashboard panels.'); ?>
                    </div>
                    <label class="aat-check"><input type="checkbox" name="<?php echo $opt; ?>[admin_branding_for_agency]" value="1" <?php checked(!empty($s['admin_branding_for_agency'])); ?>> Also apply admin branding to agency administrators</label>
                    <p class="description">The WordPress admin bar logo is automatically hidden when wp-admin branding is active. Client roles can also have the full top WordPress admin bar removed from the Cleanup page.</p>
                </section>

                <section class="aat-card aat-wide">
                    <h2>Login branding</h2>
                    <p>This appears on <code>/wp-login.php</code>, before the user enters wp-admin. The main login logo automatically uses the current website logo/site icon.</p>
                    <label>Agency/support footer logo URL <input type="url" name="<?php echo $opt; ?>[login_logo_url]" value="<?php echo esc_url($s['login_logo_url']); ?>"></label>
                    <p class="description">Used for the agency logo in the support bar at the bottom of the login page and client dashboard. The main login logo is pulled automatically from the current website logo.</p>
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
            </div>
            <?php
        }, 'Save branding settings');
    }
}
