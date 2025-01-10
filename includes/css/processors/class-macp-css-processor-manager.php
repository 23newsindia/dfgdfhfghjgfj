<?php
/**
 * Manages CSS processing and optimization
 */
class MACP_CSS_Processor_Manager {
    private $table_name;
    private $debug;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'macp_used_css';
        $this->debug = new MACP_Debug();
        
        // Initialize hooks
        if (get_option('macp_remove_unused_css', 0)) {
            add_action('wp', [$this, 'maybe_process_page']);
        }
    }
  

    public function maybe_process_page() {
    if (is_admin() || is_user_logged_in()) {
        return;
    }

    $url = MACP_URL_Helper::get_current_url();
    
    // Validate URL belongs to your site
    $site_host = parse_url(home_url(), PHP_URL_HOST);
    $url_host = parse_url($url, PHP_URL_HOST);
    
    if ($site_host !== $url_host) {
        return;
    }

    // Add URL pattern validation
    if (!$this->is_valid_url_pattern($url)) {
        return;
    }

    global $wpdb;
    
    // Check if URL is already in queue or processed
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$this->table_name} WHERE url = %s",
        $url
    ));

        if (!$existing) {
        $wpdb->insert(
            $this->table_name,
            [
                'url' => $url,
                'status' => 'pending',
                'modified' => current_time('mysql'),
                'last_accessed' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s']
        );
    }
}

private function is_valid_url_pattern($url) {
    // Define allowed URL patterns
    $allowed_patterns = [
        '/^' . preg_quote(home_url(), '/') . '\/[a-zA-Z0-9\-_\/]+\/?$/',
        '/^' . preg_quote(home_url(), '/') . '\/?$/'
    ];

    foreach ($allowed_patterns as $pattern) {
        if (preg_match($pattern, $url)) {
            return true;
        }
    }

    return false;
}
  
  }