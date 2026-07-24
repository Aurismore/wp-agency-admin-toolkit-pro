<?php
namespace Aurismore\AAT\Admin;

if (!defined('ABSPATH')) exit;

class BrandingPage extends Page {
    public function slug() {
        return 'wp-admin-toolkit-branding';
    }

    public function title() {
        return __('Branding', 'wp-agency-admin-toolkit');
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
                    <h2><?php esc_html_e('Branding modules', 'wp-agency-admin-toolkit'); ?></h2>
                    <?php
                    $this->checkbox($s, 'enable_login_branding', __('Login page branding', 'wp-agency-admin-toolkit'));
                    $this->checkbox($s, 'enable_admin_branding', __('Admin footer branding', 'wp-agency-admin-toolkit'));
                    $this->checkbox($s, 'enable_wp_admin_branding', __('wp-admin visual branding', 'wp-agency-admin-toolkit'));
                    $this->checkbox($s, 'login_hide_aux_links', __('Hide lost password / return links on login', 'wp-agency-admin-toolkit'));
                    ?>
                </section>

                <section class="aat-card">
                    <h2><?php esc_html_e('wp-admin branding', 'wp-agency-admin-toolkit'); ?></h2>
                    <p><?php esc_html_e('This customises the admin area after login for affected client roles. The wp-admin logo is pulled automatically from the current website logo/site icon, so it does not need a separate setting.', 'wp-agency-admin-toolkit'); ?></p>
                    <div class="aat-colour-panel">
                        <?php $this->color_field('admin_primary_color', __('Primary colour', 'wp-agency-admin-toolkit'), $s['admin_primary_color'], __('Used for buttons, dashboard hero areas and main admin accents.', 'wp-agency-admin-toolkit')); ?>
                        <?php $this->color_field('admin_accent_color', __('Accent colour', 'wp-agency-admin-toolkit'), $s['admin_accent_color'], __('Used for highlights, borders and secondary visual details.', 'wp-agency-admin-toolkit')); ?>
                        <?php $this->color_field('admin_background_color', __('Admin background', 'wp-agency-admin-toolkit'), $s['admin_background_color'], __('Used behind the branded admin dashboard panels.', 'wp-agency-admin-toolkit')); ?>
                    </div>
                    <label class="aat-check"><input type="checkbox" name="<?php echo $opt; ?>[admin_branding_for_agency]" value="1" <?php checked(!empty($s['admin_branding_for_agency'])); ?>> <?php esc_html_e('Also apply admin branding to agency administrators', 'wp-agency-admin-toolkit'); ?></label>
                    <p class="description"><?php esc_html_e('The WordPress admin bar logo is automatically hidden when wp-admin branding is active. Client roles can also have the full top WordPress admin bar removed from the Cleanup page.', 'wp-agency-admin-toolkit'); ?></p>
                </section>

                <section class="aat-card aat-wide">
                    <h2><?php esc_html_e('Login branding', 'wp-agency-admin-toolkit'); ?></h2>
                    <p><?php echo wp_kses(sprintf(/* translators: %s: wp-login.php in a code tag. */ __('This appears on %s, before the user enters wp-admin. The main login logo automatically uses the current website logo/site icon.', 'wp-agency-admin-toolkit'), '<code>/wp-login.php</code>'), ['code' => []]); ?></p>
                    <label><?php esc_html_e('Agency/support footer logo URL', 'wp-agency-admin-toolkit'); ?> <input type="url" name="<?php echo $opt; ?>[login_logo_url]" value="<?php echo esc_url($s['login_logo_url']); ?>"></label>
                    <p class="description"><?php esc_html_e('Used for the agency logo in the support bar at the bottom of the login page and client dashboard. The main login logo is pulled automatically from the current website logo.', 'wp-agency-admin-toolkit'); ?></p>
                    <label><?php esc_html_e('Full-screen login background image', 'wp-agency-admin-toolkit'); ?></label>
                    <div class="aat-media-field">
                        <input type="hidden" class="aat-media-id" name="<?php echo $opt; ?>[login_background_image_id]" value="<?php echo esc_attr($s['login_background_image_id']); ?>">
                        <input type="url" class="aat-media-url" name="<?php echo $opt; ?>[login_background_image_url]" value="<?php echo esc_url($s['login_background_image_url']); ?>" placeholder="<?php esc_attr_e('Upload or paste image URL', 'wp-agency-admin-toolkit'); ?>">
                        <button type="button" class="button aat-media-upload" data-title="<?php esc_attr_e('Choose login background', 'wp-agency-admin-toolkit'); ?>" data-button="<?php esc_attr_e('Use this background', 'wp-agency-admin-toolkit'); ?>"><?php esc_html_e('Upload / choose image', 'wp-agency-admin-toolkit'); ?></button>
                        <button type="button" class="button aat-media-clear"><?php esc_html_e('Remove', 'wp-agency-admin-toolkit'); ?></button>
                    </div>
                    <?php if (!empty($s['login_background_image_url'])): ?>
                        <div class="aat-media-preview"><img src="<?php echo esc_url($s['login_background_image_url']); ?>" alt="<?php esc_attr_e('Login background preview', 'wp-agency-admin-toolkit'); ?>"></div>
                    <?php endif; ?>
                    <p class="description"><?php echo wp_kses(__('Recommended background size: <strong>1920 × 1080px</strong> minimum, ideally <strong>2560 × 1440px</strong> for large screens. Use WebP/JPG, compressed below 500KB where possible. The upload button stores the image in the WordPress Media Library.', 'wp-agency-admin-toolkit'), ['strong' => []]); ?></p>
                    <?php $this->color_field('login_background', __('Login background colour', 'wp-agency-admin-toolkit'), $s['login_background'], __('Choose from the picker or type a hex value.', 'wp-agency-admin-toolkit')); ?>
                    <label><?php esc_html_e('Background overlay', 'wp-agency-admin-toolkit'); ?> <input type="text" name="<?php echo $opt; ?>[login_background_overlay]" value="<?php echo esc_attr($s['login_background_overlay']); ?>" placeholder="rgba(23,36,59,0.28)"></label>
                    <p class="description"><?php echo wp_kses(sprintf(/* translators: %s: example rgba() value in a code tag. */ __('Overlay accepts values such as %s so transparency can be controlled.', 'wp-agency-admin-toolkit'), '<code>rgba(23,36,59,0.28)</code>'), ['code' => []]); ?></p>
                    <?php $this->color_field('login_button_color', __('Button colour', 'wp-agency-admin-toolkit'), $s['login_button_color'], __('Used for the primary login button.', 'wp-agency-admin-toolkit')); ?>
                    <?php $this->color_field('login_accent_color', __('Accent colour', 'wp-agency-admin-toolkit'), $s['login_accent_color'], __('Used for highlights and support accents.', 'wp-agency-admin-toolkit')); ?>
                    <label><?php esc_html_e('Admin footer text', 'wp-agency-admin-toolkit'); ?> <input type="text" name="<?php echo $opt; ?>[admin_footer_text]" value="<?php echo esc_attr($s['admin_footer_text']); ?>"></label>
                </section>
            </div>
            <?php
        }, __('Save branding settings', 'wp-agency-admin-toolkit'));
    }
}
