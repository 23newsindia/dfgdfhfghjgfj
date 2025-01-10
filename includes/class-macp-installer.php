<?php
class MACP_Installer {
    public static function install() {
        self::create_database_tables();
        self::create_directories();
        self::set_default_options();
        self::schedule_cron_jobs();
    }

    private static function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'macp_used_css';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            url varchar(2000) NOT NULL default '',
            css longtext default NULL,
            hash varchar(32) default '',
            error_code varchar(32) NULL default NULL,
            error_message longtext NULL default NULL,
            retries tinyint(1) NOT NULL default 1,
            is_mobile tinyint(1) NOT NULL default 0,
            status varchar(255) NOT NULL default 'pending',
            modified timestamp NOT NULL default CURRENT_TIMESTAMP,
            last_accessed timestamp NOT NULL default CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY url (url(150), is_mobile),
            KEY modified (modified),
            KEY last_accessed (last_accessed),
            KEY hash (hash)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        MACP_Debug::log('Database tables created');
    }
  
  
  
    private static function schedule_cron_jobs() {
        if (!wp_next_scheduled('macp_process_css_queue')) {
            wp_schedule_event(time(), 'five_minutes', 'macp_process_css_queue');
        }
    }


    private static function create_directories() {
        $dirs = [
            WP_CONTENT_DIR . '/cache/macp',
            WP_CONTENT_DIR . '/cache/macp/used-css',
            WP_CONTENT_DIR . '/cache/min'
        ];

        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
                file_put_contents($dir . '/index.php', '<?php // Silence is golden');
                chmod($dir, 0755);
                MACP_Debug::log('Created directory: ' . $dir);
            }
        }
    }

    private static function set_default_options() {
        $defaults = [
            'macp_enable_html_cache' => 1,
            'macp_enable_gzip' => 1,
            'macp_enable_redis' => 1,
            'macp_minify_html' => 0,
            'macp_enable_js_defer' => 0,
            'macp_enable_js_delay' => 0,
            'macp_enable_varnish' => 0,
            'macp_remove_unused_css' => 0
        ];

        foreach ($defaults as $key => $value) {
            add_option($key, $value);
        }
        
        MACP_Debug::log('Default options set');
    }
}