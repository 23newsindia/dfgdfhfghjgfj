<?php
/*
Plugin Name: My Advanced Cache Plugin
Description: Integrates Redis for object caching and static HTML caching with WP Rocket-like interface
Version: 1.3
Author: Your Name
*/

if (!defined('ABSPATH')) exit;

define('MACP_PLUGIN_FILE', __FILE__);
define('MACP_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Load Composer autoloader
if (file_exists(MACP_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once MACP_PLUGIN_DIR . 'vendor/autoload.php';
}

// Load installer first
require_once MACP_PLUGIN_DIR . 'includes/class-macp-installer.php';

// Register activation hook
register_activation_hook(__FILE__, ['MACP_Installer', 'install']);

// Load utility classes first
require_once MACP_PLUGIN_DIR . 'includes/class-macp-debug.php';
require_once MACP_PLUGIN_DIR . 'includes/class-macp-filesystem.php';
require_once MACP_PLUGIN_DIR . 'includes/class-macp-url-helper.php';
require_once MACP_PLUGIN_DIR . 'includes/css/test/class-macp-css-test-handler.php';

// Add to the existing requires section:
require_once MACP_PLUGIN_DIR . 'includes/css/test/class-macp-css-test-controller.php';
require_once MACP_PLUGIN_DIR . 'includes/css/test/class-macp-url-validator.php';
require_once MACP_PLUGIN_DIR . 'includes/css/test/class-macp-css-test-results.php';

// Watch for changes: Adding Cron Schedule Interval Filter (Top Section)
add_filter('cron_schedules', 'macp_add_cron_interval');

function macp_add_cron_interval($schedules) {
    $schedules['five_minutes'] = array(
        'interval' => 300,
        'display'  => esc_html__('Every Five Minutes'),
    );
    return $schedules;
}
// Above: Added cron_schedules filter.

// Load Redis classes
require_once MACP_PLUGIN_DIR . 'includes/redis/class-macp-redis-connection.php';
require_once MACP_PLUGIN_DIR . 'includes/redis/class-macp-redis-status.php';
require_once MACP_PLUGIN_DIR . 'includes/class-macp-redis.php';

// Load metrics classes
require_once MACP_PLUGIN_DIR . 'includes/metrics/class-macp-metrics-collector.php';
require_once MACP_PLUGIN_DIR . 'includes/metrics/class-macp-metrics-calculator.php';
require_once MACP_PLUGIN_DIR . 'includes/metrics/class-macp-metrics-display.php';
require_once MACP_PLUGIN_DIR . 'includes/metrics/class-macp-metrics-recorder.php';

// Load core functionality classes
require_once MACP_PLUGIN_DIR . 'includes/class-macp-cache-helper.php';
require_once MACP_PLUGIN_DIR . 'includes/class-macp-html-cache.php';

// Watch for changes: Adding CSS Queue Processing Hooks
register_activation_hook(__FILE__, 'macp_schedule_css_processing');
add_action('macp_process_css_queue_cron', 'macp_process_css_queue_callback');

function macp_schedule_css_processing() {
    if (!wp_next_scheduled('macp_process_css_queue_cron')) {
        wp_schedule_event(time(), 'five_minutes', 'macp_process_css_queue_cron');
    }
}

function macp_process_css_queue_callback() {
    $processor = new MACP_CSS_Queue_Processor();
    $processor->process_queue();
}
// Above: CSS queue processing hooks and functions added.

// Load minification classes
require_once MACP_PLUGIN_DIR . 'includes/minify/class-macp-html-minifier.php';
require_once MACP_PLUGIN_DIR . 'includes/minify/class-macp-minify-css.php';
require_once MACP_PLUGIN_DIR . 'includes/minify/class-macp-minify-js.php';
require_once MACP_PLUGIN_DIR . 'includes/minify/class-macp-minify-html.php';

// Load CSS optimization classes first
require_once MACP_PLUGIN_DIR . 'includes/css/utils/class-macp-css-patterns.php';
require_once MACP_PLUGIN_DIR . 'includes/css/utils/class-macp-css-optimizer.php';
require_once MACP_PLUGIN_DIR . 'includes/css/class-macp-css-config.php';
require_once MACP_PLUGIN_DIR . 'includes/css/class-macp-css-extractor.php';
require_once MACP_PLUGIN_DIR . 'includes/css/class-macp-css-minifier.php';

// In my-advanced-cache-plugin.php, add:
require_once MACP_PLUGIN_DIR . 'includes/css/test/class-macp-css-test-service.php';

// Load JavaScript optimization classes
require_once MACP_PLUGIN_DIR . 'includes/js/class-macp-script-attributes.php';
require_once MACP_PLUGIN_DIR . 'includes/js/class-macp-script-rules.php';
require_once MACP_PLUGIN_DIR . 'includes/js/class-macp-script-handler.php';
require_once MACP_PLUGIN_DIR . 'includes/js/class-macp-script-processor.php';
require_once MACP_PLUGIN_DIR . 'includes/js/class-macp-script-exclusions.php';
require_once MACP_PLUGIN_DIR . 'includes/js/class-macp-js-optimizer.php';
require_once MACP_PLUGIN_DIR . 'includes/js/class-macp-js-buffer-handler.php';
require_once MACP_PLUGIN_DIR . 'includes/js/class-macp-js-loader.php';
require_once MACP_PLUGIN_DIR . 'includes/js/class-macp-js-tag-processor.php';
require_once MACP_PLUGIN_DIR . 'includes/js/class-macp-defer-handler.php';
require_once MACP_PLUGIN_DIR . 'includes/js/class-macp-delay-handler.php';

// Load Varnish-related classes
require_once MACP_PLUGIN_DIR . 'includes/varnish/class-macp-vcl-generator.php';
require_once MACP_PLUGIN_DIR . 'includes/varnish/class-macp-varnish.php';

// Load admin classes
require_once MACP_PLUGIN_DIR . 'includes/admin/class-macp-settings-manager.php';
require_once MACP_PLUGIN_DIR . 'includes/admin/class-macp-admin-settings.php';
require_once MACP_PLUGIN_DIR . 'includes/admin/class-macp-admin-assets.php';
require_once MACP_PLUGIN_DIR . 'includes/admin/class-macp-varnish-settings.php';
require_once MACP_PLUGIN_DIR . 'includes/class-macp-admin.php';
require_once MACP_PLUGIN_DIR . 'includes/class-macp-admin-bar.php';
require_once MACP_PLUGIN_DIR . 'includes/class-macp-debug-utility.php';

// Load Critical CSS classes
require_once MACP_PLUGIN_DIR . 'includes/critical-css/class-macp-critical-css-generation.php';
require_once MACP_PLUGIN_DIR . 'includes/critical-css/class-macp-css-fetcher.php';
require_once MACP_PLUGIN_DIR . 'includes/critical-css/class-macp-css-processor.php';
require_once MACP_PLUGIN_DIR . 'includes/critical-css/class-macp-critical-css.php';

require_once MACP_PLUGIN_DIR . 'includes/css/processors/class-macp-css-processor-manager.php';
require_once MACP_PLUGIN_DIR . 'includes/css/processors/class-macp-css-queue-processor.php';

// Load CSS parsers
require_once MACP_PLUGIN_DIR . 'includes/css/parsers/class-macp-css-selector-parser.php';

// Then load CSS extractor
require_once MACP_PLUGIN_DIR . 'includes/css/class-macp-css-extractor.php';

require_once MACP_PLUGIN_DIR . 'includes/redis/class-macp-redis-primer.php';

// Load HTML processors
require_once MACP_PLUGIN_DIR . 'includes/html/processors/class-macp-html-processor.php';

// Load Lazy Load processors
require_once MACP_PLUGIN_DIR . 'includes/lazy-load/processors/class-macp-custom-attribute-processor.php';
require_once MACP_PLUGIN_DIR . 'includes/lazy-load/processors/class-macp-image-processor.php';
require_once MACP_PLUGIN_DIR . 'includes/lazy-load/class-macp-lazy-load-processor.php';
require_once MACP_PLUGIN_DIR . 'includes/lazy-load/class-macp-lazy-load.php';

require_once MACP_PLUGIN_DIR . 'includes/js/class-macp-script-loader.php';

// Initialize CSS optimization if enabled
if (get_option('macp_remove_unused_css', 0)) {
    add_action('template_redirect', function() {
        ob_start(function($html) {
            if (empty($html)) {
                return $html;
            }
            try {
                $processor = new MACP_HTML_Processor();
                return $processor->process($html);
            } catch (Exception $e) {
                error_log('MACP HTML Processing Error: ' . $e->getMessage());
                return $html; // Return original HTML on error
            }
        });
    }, 1);
}


// Include the main plugin class last
require_once MACP_PLUGIN_DIR . 'includes/class-macp-plugin.php';

// AJAX LOAD
require_once MACP_PLUGIN_DIR . 'includes/ajax/class-macp-css-test-ajax.php';

// Initialize the plugin
function MACP() {
    return MACP_Plugin::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'MACP');
