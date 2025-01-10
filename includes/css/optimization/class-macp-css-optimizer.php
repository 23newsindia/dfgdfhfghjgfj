<?php
/**
 * Main CSS optimization controller
 */
class MACP_CSS_Optimizer {
    /** @var MACP_CSS_Extractor */
    private $extractor;

    /** @var MACP_CSS_Minifier */
    private $minifier;

    /** @var MACP_CSS_Fetcher */
    private $fetcher;

    /** @var MACP_Used_CSS_Storage */
    private $storage;

    public function __construct() {
        $this->extractor = new MACP_CSS_Extractor();
        $this->minifier = new MACP_CSS_Minifier();
        $this->fetcher = new MACP_CSS_Fetcher();
        $this->storage = new MACP_Used_CSS_Storage();
    }

    /**
     * Optimize CSS in HTML content
     */
    public function optimize(string $html): string {
        if (!$this->should_process()) {
            return $html;
        }

        $url = $this->get_current_url();
        $optimized_css = $this->process_css($url, $html);

        if (!empty($optimized_css)) {
            $html = $this->replace_css($html, $optimized_css);
        }

        return $html;
    }

    /**
     * Optimize HTML by extracting, processing, and inlining CSS.
     */
    public function optimize_html($html) {
        if (empty($html)) {
            return $html;
        }

        // Extract all CSS links
        preg_match_all('/<link[^>]*rel=["\']stylesheet["\'][^>]*href=["\']([^"\']+)["\'][^>]*>/i', $html, $matches);
        if (empty($matches[0])) {
            return $html;
        }

        $processed_css = '';
        foreach ($matches[1] as $css_url) {
            // Fetch CSS content
            $response = wp_remote_get($css_url);
            if (!is_wp_error($response)) {
                $css_content = wp_remote_retrieve_body($response);
                if (!empty($css_content)) {
                    // Process the CSS
                    $used_selectors = $this->extractor->extract_used_selectors($html);
                    $optimized_css = $this->minifier->remove_unused_css($css_content, $used_selectors);
                    $processed_css .= $optimized_css . "\n";
                }
            }
        }

        // Remove all original CSS links
        $html = preg_replace('/<link[^>]*rel=["\']stylesheet["\'][^>]*>/i', '', $html);

        // Add optimized CSS to head
        if (!empty($processed_css)) {
            $css_tag = "<style id=\"macp-optimized-css\">\n" . $processed_css . "</style>";
            $html = preg_replace('/<\/head>/', $css_tag . "\n</head>", $html);
        }

        return $html;
    }

    /**
     * Process CSS optimization
     */
    public function process_css($css_content, $html = '') {
        // Get settings
        $should_minify = get_option('macp_minify_css', 0);
        $should_remove_unused = get_option('macp_remove_unused_css', 0);

        // If both are enabled, minify first then remove unused
        if ($should_minify && $should_remove_unused) {
            // First minify
            $minifier = new MACP_CSS_Minifier();
            $css_content = $minifier->minify($css_content);

            // Then remove unused CSS
            $used_selectors = $this->extractor->extract_used_selectors($html);
            $css_content = $this->minifier->remove_unused_css($css_content, $used_selectors);

            return $css_content;
        }

        // If only minify is enabled
        if ($should_minify) {
            $minifier = new MACP_CSS_Minifier();
            return $minifier->minify($css_content);
        }

        // If only unused CSS removal is enabled
        if ($should_remove_unused) {
            $used_selectors = $this->extractor->extract_used_selectors($html);
            return $this->minifier->remove_unused_css($css_content, $used_selectors);
        }

        return $css_content;
    }

    /**
     * Replace original CSS with optimized version
     */
    private function replace_css($html, $optimized_css) {
        // Remove all existing stylesheet links except critical ones
        $html = preg_replace('/<link[^>]*rel=["\']stylesheet["\'][^>]*>/i', '', $html);

        // Add optimized CSS before </head>
        $css_tag = sprintf(
            '<style id="macp-optimized-css" type="text/css">%s</style>',
            $optimized_css
        );

        return str_replace('</head>', $css_tag . '</head>', $html);
    }

    /**
     * Check if optimization should run
     */
    private function should_process(): bool {
        return get_option('macp_remove_unused_css', 0) 
            && !is_admin() 
            && !is_user_logged_in();
    }

    /**
     * Get current page URL
     */
    private function get_current_url(): string {
        global $wp;
        return home_url($wp->request);
    }
}
