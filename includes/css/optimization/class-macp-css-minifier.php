<?php
/**
 * Handles CSS minification and optimization
 */
class MACP_CSS_Minifier {
    /**
     * Remove unused CSS selectors
     * 
     * @param string $css Original CSS content
     * @param array $used_selectors Array of used selectors
     * @return string Optimized CSS
     */
    public function remove_unused_css(string $css, array $used_selectors): string {
        // Remove comments
        $css = $this->remove_comments($css);
        
        // Split into rules
        $rules = $this->split_rules($css);
        
        return $this->filter_used_rules($rules, $used_selectors);
    }

    /**
     * Remove CSS comments
     */
    private function remove_comments(string $css): string {
        return preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
    }

    /**
     * Split CSS into rules
     */
    private function split_rules(string $css): array {
        preg_match_all('/([^{]+){[^}]*}/s', $css, $matches);
        return $matches[0] ?? [];
    }

    /**
     * Filter rules to keep only used ones
     */
    private function filter_used_rules(array $rules, array $used_selectors): string {
        $used_css = '';
        
        foreach ($rules as $rule) {
            $selector = trim(preg_replace('/\s*{.*$/s', '', $rule));
            
            if ($this->is_selector_used($selector, $used_selectors)) {
                $used_css .= $rule . "\n";
            }
        }

        return $used_css;
    }

    /**
     * Check if selector is used
     */
    private function is_selector_used(string $selector, array $used_selectors): bool {
        foreach ($used_selectors as $used_selector) {
            if (strpos($selector, $used_selector) !== false) {
                return true;
            }
        }
        return false;
    }
}