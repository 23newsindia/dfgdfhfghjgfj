<?php
/**
 * Handles the HTML processing pipeline
 */
class MACP_HTML_Processor {
    private $minifier;
    private $options;
    private $css_optimizer;
    private $cache_path;

    public function __construct() {
        $this->minifier = new MACP_HTML_Minifier();
        $this->css_optimizer = new MACP_CSS_Optimizer();
        $this->options = [
            'minify_html' => get_option('macp_minify_html', 0),
            'minify_css' => get_option('macp_minify_css', 0),
            'minify_js' => get_option('macp_minify_js', 0)
        ];
        $this->cache_path = WP_CONTENT_DIR . '/cache/macp/used-css/';
        
        if (!file_exists($this->cache_path)) {
            wp_mkdir_p($this->cache_path);
        }
    }

    public function process($html) {
        if (empty($html) || !get_option('macp_remove_unused_css', 0)) {
            return $html;
        }

        // Extract all CSS links
        preg_match_all('/<link[^>]*rel=["\']stylesheet["\'][^>]*href=["\']([^"\']+)["\'][^>]*>/i', $html, $matches);
        
        if (empty($matches[0])) {
            return $html;
        }

        $optimized_css = '';
        $removed_tags = [];

        // Get current URL for cache key
        $current_url = MACP_URL_Helper::get_current_url();
        $cache_key = md5($current_url);
        $cache_file = $this->cache_path . $cache_key . '.css';

        // If we have cached optimized CSS for this URL, use it
        if (file_exists($cache_file)) {
            $optimized_css = file_get_contents($cache_file);
            // Remove all original stylesheet links
            foreach ($matches[0] as $tag) {
                $html = str_replace($tag, '', $html);
            }
            
            // Add optimized CSS before </head>
            if (!empty($optimized_css)) {
                $optimized_tag = "<style id=\"macp-optimized-css\">\n{$optimized_css}</style>\n</head>";
                $html = str_replace('</head>', $optimized_tag, $html);
            }
        } else {
            // Process each stylesheet and generate optimized CSS
            foreach ($matches[1] as $index => $css_url) {
                // Skip Google Fonts and other external fonts
                if (strpos($css_url, 'fonts.googleapis.com') !== false || 
                    strpos($css_url, 'fonts.gstatic.com') !== false) {
                    continue;
                }

                $css_content = $this->process_css_file($css_url, $html);
                if ($css_content !== false) {
                    $optimized_css .= "/* Source: {$css_url} */\n" . $css_content . "\n";
                    $removed_tags[] = $matches[0][$index];
                }
            }

            // Save optimized CSS
            if (!empty($optimized_css)) {
                file_put_contents($cache_file, $optimized_css);
                
                // Remove all processed stylesheet links
                foreach ($removed_tags as $tag) {
                    $html = str_replace($tag, '', $html);
                }
                
                // Add optimized CSS before </head>
                $optimized_tag = "<style id=\"macp-optimized-css\">\n{$optimized_css}</style>\n</head>";
                $html = str_replace('</head>', $optimized_tag, $html);
            }
        }

        return $html;
    }

    /**
     * Process CSS file content
     *
     * @param string $url CSS file URL
     * @param string $html HTML content
     * @return string|false
     */
    private function process_css_file($url, $html) {
        $css_content = wp_remote_get($url);
        if (is_wp_error($css_content)) {
            return false;
        }
        
        $css_content = wp_remote_retrieve_body($css_content);
        if (empty($css_content)) {
            return false;
        }

        return $this->css_optimizer->optimize($css_content, $html);
    }
}
