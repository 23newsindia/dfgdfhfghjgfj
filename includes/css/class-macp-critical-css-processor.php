<?php
class MACP_CSS_Processor {
    private $used_css_table;
    private $filesystem;
    private $cache_path;

    public function __construct() {
        $this->used_css_table = new MACP_Used_CSS_Table();
        $this->filesystem = new MACP_Filesystem();
        $this->cache_path = WP_CONTENT_DIR . '/cache/macp/used-css/';
        
        if (!file_exists($this->cache_path)) {
            wp_mkdir_p($this->cache_path);
        }
    }

    public function process($url, $html) {
        global $wpdb;
        
        // Extract all CSS
        $css_files = $this->extract_css_files($html);
        $used_selectors = $this->extract_used_selectors($html);
        
        $optimized_css = '';
        foreach ($css_files as $file) {
            $css_content = $this->get_css_content($file);
            if (!$css_content) {
                continue;
            }
            
            $optimized_css .= $this->remove_unused_css($css_content, $used_selectors);
        }

        // Save optimized CSS
        $hash = md5($optimized_css);
        $file_path = $this->cache_path . $hash . '.css';
        
        if ($this->filesystem->write_file($file_path, $optimized_css)) {
            $wpdb->insert(
                $wpdb->prefix . 'macp_used_css',
                [
                    'url' => $url,
                    'css' => $optimized_css,
                    'hash' => $hash,
                    'status' => 'completed'
                ],
                ['%s', '%s', '%s', '%s']
            );
        }

        return $optimized_css;
    }

    private function extract_css_files($html) {
        $css_files = [];
        
        // Extract <link> tags
        preg_match_all('/<link[^>]*rel=["\']stylesheet["\'][^>]*href=["\']([^"\']+)["\'][^>]*>/i', $html, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $file) {
                if (strpos($file, '//') === 0) {
                    $file = 'https:' . $file;
                }
                $css_files[] = $file;
            }
        }

        return array_unique($css_files);
    }

    private function extract_used_selectors($html) {
        $selectors = [];
        
        // Create DOM document
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        
        // Get all elements with class or ID
        $xpath = new DOMXPath($dom);
        $elements = $xpath->query('//*[@class or @id]');
        
        foreach ($elements as $element) {
            // Extract classes
            if ($element->hasAttribute('class')) {
                $classes = explode(' ', $element->getAttribute('class'));
                foreach ($classes as $class) {
                    if ($class = trim($class)) {
                        $selectors[] = '.' . $class;
                    }
                }
            }
            
            // Extract IDs
            if ($element->hasAttribute('id')) {
                $selectors[] = '#' . $element->getAttribute('id');
            }
        }

        return array_unique($selectors);
    }

    private function get_css_content($url) {
        if (strpos($url, '//') === 0) {
            $url = 'https:' . $url;
        }

        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            return false;
        }

        return wp_remote_retrieve_body($response);
    }

    private function remove_unused_css($css, $used_selectors) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Split into rules
        preg_match_all('/([^{]+){[^}]*}/s', $css, $matches);
        
        $used_css = '';
        foreach ($matches[0] as $rule) {
            $selector = trim(preg_replace('/\s*{.*$/s', '', $rule));
            
            // Keep if selector is used
            foreach ($used_selectors as $used_selector) {
                if (strpos($selector, $used_selector) !== false) {
                    $used_css .= $rule . "\n";
                    break;
                }
            }
        }

        return $used_css;
    }
}