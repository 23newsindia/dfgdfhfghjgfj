<?php defined('ABSPATH') || exit; ?>

<div class="macp-card">
    <h2>Lazy Loading</h2>
    
    <div class="macp-option-group">
        <label class="macp-toggle">
            <input type="checkbox" 
                   name="macp_enable_lazy_load" 
                   value="1" 
                   <?php checked(get_option('macp_enable_lazy_load'), 1); ?>>
            <span class="macp-toggle-slider"></span>
            Enable Lazy Loading
        </label>
        
        <p class="description">
            Lazy loading delays loading of images and iframes until they enter the viewport, improving initial page load time.
        </p>
    </div>

    <div class="macp-exclusion-section">
        <h3>Exclude from Lazy Loading</h3>
        <p class="description">Enter URLs or patterns to exclude from lazy loading (one per line). Images with classes 'no-lazy' or 'skip-lazy' are automatically excluded.</p>
        
        <textarea name="macp_lazy_load_excluded" rows="5" class="large-text code"><?php 
            echo esc_textarea(implode("\n", get_option('macp_lazy_load_excluded', []))); 
        ?></textarea>
    </div>

    <div class="notice notice-info inline">
        <p><strong>Tips:</strong></p>
        <ul>
            <li>Add class "no-lazy" or "skip-lazy" to images you don't want to lazy load</li>
            <li>Above-the-fold images should be excluded for better performance</li>
            <li>Critical images like logos should be excluded</li>
        </ul>
    </div>
</div>