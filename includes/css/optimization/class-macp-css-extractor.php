<?php
/**
 * Handles extracting CSS files and selectors from HTML
 */
class MACP_CSS_Extractor {
    /**
     * Extract CSS file URLs from HTML
     * 
     * @param string $html HTML content
     * @return array Array of CSS file URLs
     */
    public function extract_css_files(string $html): array {
        $css_files = [];
        
        preg_match_all(
            '/<link[^>]*rel=["\']stylesheet["\'][^>]*href=["\']([^"\']+)["\'][^>]*>/i',
            $html,
            $matches
        );
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $file) {
                $css_files[] = $this->normalize_url($file);
            }
        }

        return array_unique($css_files);
    }

    /**
     * Extract used CSS selectors from HTML
     * 
     * @param string $html HTML content
     * @return array Array of used selectors
     */
    public function extract_used_selectors(string $html): array {
        $selectors = [];
        
        $dom = new DOMDocument();
        @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        $xpath = new DOMXPath($dom);
        $elements = $xpath->query('//*[@class or @id]');
        
        foreach ($elements as $element) {
            $this->extract_classes($element, $selectors);
            $this->extract_ids($element, $selectors);
        }

        return array_unique($selectors);
    }

    /**
     * Extract classes from element
     */
    private function extract_classes(DOMElement $element, array &$selectors): void {
        if ($element->hasAttribute('class')) {
            $classes = explode(' ', $element->getAttribute('class'));
            foreach ($classes as $class) {
                if ($class = trim($class)) {
                    $selectors[] = '.' . $class;
                }
            }
        }
    }

    /**
     * Extract IDs from element
     */
    private function extract_ids(DOMElement $element, array &$selectors): void {
        if ($element->hasAttribute('id')) {
            $selectors[] = '#' . $element->getAttribute('id');
        }
    }

    /**
     * Normalize URL to absolute URL
     */
    private function normalize_url(string $url): string {
        if (strpos($url, '//') === 0) {
            return 'https:' . $url;
        }
        return $url;
    }
}