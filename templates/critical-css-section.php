<?php defined('ABSPATH') || exit; ?>

<div class="macp-card">
    <h2>Critical CSS</h2>
    
    <div class="macp-status-card">
        <div class="macp-status-indicator <?php echo get_transient('macp_critical_css_generation_running') ? 'active' : 'inactive'; ?>">
            <?php echo get_transient('macp_critical_css_generation_running') ? 'Generation in Progress' : 'Ready'; ?>
        </div>
    </div>

    <div class="macp-option-group">
        <label class="macp-toggle">
            <input type="checkbox" 
                   name="macp_enable_critical_css" 
                   value="1" 
                   <?php checked(get_option('macp_enable_critical_css'), 1); ?>>
            <span class="macp-toggle-slider"></span>
            Enable Critical CSS
        </label>

        <p class="description">
            Critical CSS improves page load performance by inlining essential CSS and deferring non-critical styles.
        </p>
    </div>

    <div class="macp-action-buttons" style="margin-top: 15px;">
        <button type="button" 
                id="macp-generate-critical-css" 
                class="button button-primary"
                <?php echo get_transient('macp_critical_css_generation_running') ? 'disabled' : ''; ?>>
            Generate Critical CSS
        </button>

        <button type="button" 
                id="macp-clear-critical-css" 
                class="button button-secondary">
            Clear Critical CSS Cache
        </button>
    </div>

    <div class="notice notice-info inline" style="margin-top: 15px;">
        <p>
            <strong>Note:</strong> Critical CSS generation may take several minutes depending on your site's size.
            The process runs in the background and you can continue using your site.
        </p>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#macp-generate-critical-css').on('click', function() {
        const $button = $(this);
        $button.prop('disabled', true).text('Generating...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'macp_generate_critical_css',
                nonce: macp_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.macp-status-indicator')
                        .removeClass('inactive')
                        .addClass('active')
                        .text('Generation in Progress');
                } else {
                    alert('Failed to start Critical CSS generation: ' + response.data);
                    $button.prop('disabled', false).text('Generate Critical CSS');
                }
            },
            error: function() {
                alert('Failed to start Critical CSS generation');
                $button.prop('disabled', false).text('Generate Critical CSS');
            }
        });
    });

    $('#macp-clear-critical-css').on('click', function() {
        const $button = $(this);
        $button.prop('disabled', true).text('Clearing...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'macp_clear_critical_css',
                nonce: macp_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $button.text('Cache Cleared!');
                    setTimeout(function() {
                        $button.prop('disabled', false).text('Clear Critical CSS Cache');
                    }, 2000);
                } else {
                    alert('Failed to clear Critical CSS cache: ' + response.data);
                    $button.prop('disabled', false).text('Clear Critical CSS Cache');
                }
            },
            error: function() {
                alert('Failed to clear Critical CSS cache');
                $button.prop('disabled', false).text('Clear Critical CSS Cache');
            }
        });
    });
});
</script>