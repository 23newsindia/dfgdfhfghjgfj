<?php
class MACP_CSS_Test_Ajax {
    private $test_service;
    
    public function __construct() {
        $this->test_service = new MACP_CSS_Test_Service();
        add_action('wp_ajax_macp_test_unused_css', [$this, 'handle_test_request']);
    }

    public function handle_test_request() {
        try {
            if (!check_ajax_referer('macp_admin_nonce', 'nonce', false)) {
                throw new Exception('Invalid security token');
            }

            if (!current_user_can('manage_options')) {
                throw new Exception('Unauthorized access');
            }

            $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : home_url('/');
            $results = $this->test_service->test_url($url);
            
            wp_send_json_success([
                'results' => $results,
                'url' => $url
            ]);

        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }
    }
}
