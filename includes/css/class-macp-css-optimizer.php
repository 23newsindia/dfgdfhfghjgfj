<?php
class MACP_CSS_Optimizer {
    private $extractor;
    private $minifier;
    private $storage;
    private $debug;
    private $safelist = [];
    private $media_queries = [];
    private $is_mobile = false;

    public function __construct() {
        $this->extractor = new MACP_CSS_Extractor();
        $this->minifier = new MACP_CSS_Minifier();
        $this->storage = new MACP_Used_CSS_Storage();
        $this->debug = new MACP_Debug();
        $this->is_mobile = wp_is_mobile();
        $this->init_safelist();
    }

    private function init_safelist() {
        // Default safelist for responsive classes
        $this->safelist = [
            'mobile-*',
            'tablet-*',
            'desktop-*',
            'sm:*',
            'md:*',
            'lg:*',
            'xl:*',
            'hidden-*',
            'show-*',
            'visible-*',
            'col-*',
            'row-*',
            'grid-*',
            'flex-*',
            'order-*',
            'w-*',
            'h-*',
            'gap-*',
            'space-*',
            'p-*',
            'm-*',
            // Add WordPress default classes
            'wp-*',
            'alignfull',
            'alignwide',
            'has-*',
            // Add common framework classes
            'container',
            'container-fluid',
            'row',
            'col',
            'nav',
            'navbar',
            'btn',
            'card',
            'modal'
        ];

        /**
         * Filter the safelist of CSS selectors
         * 
         * @param array $safelist Array of CSS selectors to preserve
         */
        $this->safelist = apply_filters('macp_css_safelist', $this->safelist);
    }

    public function optimize($html) {
        if (!$this->should_process()) {
            return $html;
        }

        try {
            // Extract all CSS
            $css_files = $this->extractor->extract_css_files($html);
            $used_selectors = $this->extractor->extract_used_selectors($html);
            
            // Store media queries separately
            $this->extract_media_queries($css_files);

            $optimized_css = '';
            foreach ($css_files as $file) {
                $css_content = $this->get_css_content($file);
                if (!$css_content) continue;
                
                // Process regular CSS
                $optimized_css .= $this->process_css($css_content, $used_selectors);
            }

            // Add back preserved media queries
            $optimized_css .= $this->process_media_queries($used_selectors);

            // Add font-display: swap
            $optimized_css = $this->apply_font_display_swap($optimized_css);

            // Save optimized CSS
            $this->storage->save(MACP_URL_Helper::get_current_url(), $optimized_css, $this->is_mobile);

            // Replace CSS in HTML
            $html = $this->replace_css_in_html($html, $optimized_css);

            return $html;

        } catch (Exception $e) {
            $this->debug->log('CSS optimization error: ' . $e->getMessage());
            return $html;
        }
    }

    private function extract_media_queries($css_files) {
        foreach ($css_files as $file) {
            $content = $this->get_css_content($file);
            if (!$content) continue;

            preg_match_all('/@media[^{]+\{([^{}]|{[^{}]*})*\}/i', $content, $matches);
            
            if (!empty($matches[0])) {
                foreach ($matches[0] as $media_query) {
                    // Preserve mobile-first and responsive media queries
                    if (
                        strpos($media_query, 'min-width') !== false ||
                        strpos($media_query, 'max-width') !== false ||
                        strpos($media_query, 'orientation') !== false
                    ) {
                        $this->media_queries[] = $media_query;
                    }
                }
            }
        }
    }

    private function process_css($css, $used_selectors) {
        // Remove comments and whitespace
        $css = $this->minifier->minify($css);

        // Split into rules
        preg_match_all('/([^{]+){([^}]*)}/', $css, $matches);

        $processed_css = '';
        
        if (!empty($matches[0])) {
            foreach ($matches[0] as $i => $rule) {
                $selectors = explode(',', trim($matches[1][$i]));
                $keep_rule = false;

                foreach ($selectors as $selector) {
                    $selector = trim($selector);

                    // Keep if selector is in safelist
                    if ($this->is_safelisted($selector)) {
                        $keep_rule = true;
                        break;
                    }

                    // Keep if selector is used in HTML
                    if ($this->is_selector_used($selector, $used_selectors)) {
                        $keep_rule = true;
                        break;
                    }
                }

                if ($keep_rule) {
                    $processed_css .= $rule;
                }
            }
        }

        return $processed_css;
    }

    private function process_media_queries($used_selectors) {
        $processed_queries = '';

        foreach ($this->media_queries as $query) {
            // Extract inner CSS
            preg_match('/@media[^{]+{(.*?)}/s', $query, $matches);
            
            if (!empty($matches[1])) {
                $inner_css = $this->process_css($matches[1], $used_selectors);
                if (!empty($inner_css)) {
                    $processed_queries .= str_replace($matches[1], $inner_css, $query);
                }
            }
        }

        return $processed_queries;
    }

    private function is_safelisted($selector) {
        foreach ($this->safelist as $pattern) {
            $pattern = str_replace('*', '.*', $pattern);
            if (preg_match('/^' . $pattern . '$/', $selector)) {
                return true;
            }
        }
        return false;
    }

    private function is_selector_used($selector, $used_selectors) {
        // Handle pseudo-classes and pseudo-elements
        $selector = preg_replace('/:(?:hover|focus|active|visited|first-child|last-child|nth-child|before|after).*/', '', $selector);
        
        foreach ($used_selectors as $used_selector) {
            if (strpos($used_selector, $selector) !== false) {
                return true;
            }
        }
        return false;
    }

    private function replace_css_in_html($html, $optimized_css) {
        // Remove existing stylesheets
        $html = preg_replace('/<link[^>]*rel=["\']stylesheet["\'][^>]*>/i', '', $html);
        
        // Remove inline styles except those in safelist
        $html = preg_replace_callback('/<style[^>]*>(.*?)<\/style>/is', function($matches) {
            if (strpos($matches[0], 'data-no-optimize') !== false) {
                return $matches[0];
            }
            return '';
        }, $html);

        // Add optimized CSS
        $css_tag = sprintf(
            '<style id="macp-optimized-css">%s</style>',
            $optimized_css
        );

        return str_replace('</head>', $css_tag . '</head>', $html);
    }

    private function apply_font_display_swap($css) {
        return preg_replace_callback(
            '/(@font-face\s*{[^}]*})/',
            function($matches) {
                if (strpos($matches[0], 'font-display') === false) {
                    return str_replace('}', 'font-display:swap;}', $matches[0]);
                }
                return $matches[0];
            },
            $css
        );
    }

    private function should_process() {
        return get_option('macp_remove_unused_css', 0) 
            && !is_admin() 
            && !is_user_logged_in();
    }

    private function get_css_content($url) {
        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            $this->debug->log('Failed to fetch CSS: ' . $response->get_error_message());
            return false;
        }
        return wp_remote_retrieve_body($response);
    }
}
