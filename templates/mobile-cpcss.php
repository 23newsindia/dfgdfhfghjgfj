<?php
defined('ABSPATH') || exit;

// Make sure settings_manager is available
if (!isset($settings_manager)) {
    return;
}

$mobile_cpcss_active = $settings_manager->get_setting('async_css_mobile', 0);
$is_generating = get_option('macp_mobile_cpcss_generating', false);
?>
<div id="macp-mobile-cpcss-view" class="macp-card">
    <h2><?php esc_html_e('Mobile Critical CSS', 'my-advanced-cache-plugin'); ?></h2>
    
    <?php if (!$mobile_cpcss_active): ?>
    <div class="macp-field-description">
        <?php esc_html_e('Your website currently uses the same Critical Path CSS for both desktop and mobile.', 'my-advanced-cache-plugin'); ?>
    </div>
    <div class="macp-field-description">
        <?php esc_html_e('Enable this option to generate mobile-specific Critical CSS for better mobile performance.', 'my-advanced-cache-plugin'); ?>
    </div>
    <?php endif; ?>

    <label class="macp-toggle">
        <input type="checkbox" 
               name="macp_async_css_mobile" 
               value="1" 
               <?php checked($mobile_cpcss_active, 1); ?>
               data-nonce="<?php echo wp_create_nonce('macp_admin_nonce'); ?>">
        <span class="macp-toggle-slider"></span>
        <?php esc_html_e('Enable Mobile-Specific Critical CSS', 'my-advanced-cache-plugin'); ?>
    </label>

    <?php if ($mobile_cpcss_active): ?>
    <div class="macp-action-buttons" style="margin-top: 15px;">
        <button type="button" 
                id="macp-generate-mobile-cpcss" 
                class="button button-secondary"
                <?php echo $is_generating ? 'disabled' : ''; ?>
                data-nonce="<?php echo wp_create_nonce('macp_admin_nonce'); ?>">
            <?php echo $is_generating ? 'Generating...' : 'Regenerate Mobile Critical CSS'; ?>
        </button>
    </div>
    <?php endif; ?>
</div>