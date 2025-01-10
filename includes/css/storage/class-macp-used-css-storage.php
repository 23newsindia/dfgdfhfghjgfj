<?php
/**
 * Handles storage of optimized CSS
 */
class MACP_Used_CSS_Storage {
    /** @var string */
    private $cache_path;
    
    /** @var MACP_Filesystem */
    private $filesystem;

    public function __construct() {
        $this->filesystem = new MACP_Filesystem();
        $this->cache_path = WP_CONTENT_DIR . '/cache/macp/used-css/';
        
        if (!file_exists($this->cache_path)) {
            wp_mkdir_p($this->cache_path);
        }
    }

    /**
     * Save optimized CSS
     */
    public function save(string $url, string $css): bool {
        $hash = md5($css);
        $file_path = $this->cache_path . $hash . '.css';
        
        if ($this->filesystem->write_file($file_path, $css)) {
            return $this->save_to_db($url, $css, $hash);
        }

        return false;
    }

    /**
     * Save CSS info to database
     */
    private function save_to_db(string $url, string $css, string $hash): bool {
        global $wpdb;
        
        return (bool) $wpdb->insert(
            $wpdb->prefix . 'macp_used_css',
            [
                'url' => $url,
                'css' => $css,
                'hash' => $hash,
                'status' => 'completed'
            ],
            ['%s', '%s', '%s', '%s']
        );
    }
}