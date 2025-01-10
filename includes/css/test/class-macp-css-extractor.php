<?php
class MACP_CSS_Extractor {
    public function extract_css_files($html) {
        $css_files = [];
        
        if (preg_match_all('/<link[^>]*rel=["\']stylesheet["\'][^>]*href=["\']([^"\']+)["\']/', $html, $matches)) {
            $css_files = array_unique($matches[1]);
        }

        return array_map([$this, 'normalize_url'], $css_files);
    }

    public function get_stylesheet_content($url) {
        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            throw new Exception('Failed to fetch stylesheet: ' . $response->get_error_message());
        }
        return wp_remote_retrieve_body($response);
    }

    public function extract_used_selectors($html) {
        $dom = new DOMDocument();
        @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new DOMXPath($dom);
        
        $selectors = [];
        $elements = $xpath->query('//*[@class or @id]');
        
        foreach ($elements as $element) {
            if ($element->hasAttribute('class')) {
                $classes = explode(' ', $element->getAttribute('class'));
                foreach ($classes as $class) {
                    if ($class = trim($class)) {
                        $selectors[] = '.' . $class;
                    }
                }
            }
            if ($element->hasAttribute('id')) {
                $selectors[] = '#' . $element->getAttribute('id');
            }
        }

        return array_unique($selectors);
    }

    private function normalize_url($url) {
        if (strpos($url, '//') === 0) {
            return 'https:' . $url;
        }
        if (strpos($url, '/') === 0) {
            return home_url($url);
        }
        return $url;
    }
}