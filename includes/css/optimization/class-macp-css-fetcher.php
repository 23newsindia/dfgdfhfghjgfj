<?php
/**
 * Handles fetching CSS content
 */
class MACP_CSS_Fetcher {
    /**
     * Get CSS file content
     * 
     * @param string $url CSS file URL
     * @return string|false CSS content or false on failure
     */
    public function get_css_content(string $url) {
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            return false;
        }

        return wp_remote_retrieve_body($response);
    }
}