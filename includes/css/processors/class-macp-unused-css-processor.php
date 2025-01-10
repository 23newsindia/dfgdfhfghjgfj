<?php
class MACP_Unused_CSS_Processor {
    private $css_extractor;
    private $safelist;

    public function __construct() {
        $this->css_extractor = new MACP_CSS_Extractor();
        $this->safelist = MACP_CSS_Config::get_safelist();
    }

    public function process($css_content, $html) {
        // Store media queries separately
        $mediaQueries = [];
        preg_match_all('/@media[^{]+\{([^{}]|{[^{}]*})*\}/i', $css_content, $matches);
        
        if (!empty($matches[0])) {
            $mediaQueries = $matches[0];
            // Temporarily remove media queries from CSS
            $css_content = preg_replace('/@media[^{]+\{([^{}]|{[^{}]*})*\}/i', '', $css_content);
        }

        $used_selectors = $this->css_extractor->extract_used_selectors($html);
        $filtered_css = $this->filter_css($css_content, $used_selectors);

        // Add back all media queries
        if (!empty($mediaQueries)) {
            $filtered_css .= "\n" . implode("\n", $mediaQueries);
        }
        
        return $filtered_css;
    }

    private function filter_css($css, $used_selectors) {
        $filtered = '';
        
        // Split CSS into rules
        preg_match_all('/([^{]+){[^}]*}/s', $css, $matches);
        
        foreach ($matches[0] as $rule) {
            if ($this->should_keep_rule($rule, $used_selectors)) {
                $filtered .= $rule . "\n";
            }
        }
        
        return $filtered;
    }

    private function should_keep_rule($rule, $used_selectors) {
        $selectors = explode(',', trim(preg_replace('/\s*{.*$/s', '', $rule)));
        
        foreach ($selectors as $selector) {
            $selector = trim($selector);
            
            // Keep if in safelist
            if ($this->is_safelisted($selector)) {
                return true;
            }
            
            // Keep if used in HTML
            if ($this->is_selector_used($selector, $used_selectors)) {
                return true;
            }
        }
        
        return false;
    }

    private function is_safelisted($selector) {
        // Always keep essential selectors and media queries
        if (in_array($selector, ['html', 'body', '*']) || 
            strpos($selector, '@media') === 0 || 
            strpos($selector, '@keyframes') === 0 ||
            strpos($selector, '@supports') === 0) {
            return true;
        }

        foreach ($this->safelist as $pattern) {
            if (fnmatch($pattern, $selector)) {
                return true;
            }
        }

        return false;
    }

    private function is_selector_used($selector, $used_selectors) {
        foreach ($used_selectors as $used_selector) {
            if (strpos($used_selector, $selector) !== false) {
                return true;
            }
        }
        return false;
    }
}
