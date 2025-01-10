<?php
class MACP_CSS_Test_Service {
    private $extractor;
    private $optimizer;
    
    public function __construct() {
        $this->extractor = new MACP_CSS_Extractor();
        $this->optimizer = new MACP_CSS_Optimizer();
    }

    public function test_url($url) {
        try {
            $response = wp_remote_get($url);
            if (is_wp_error($response)) {
                throw new Exception('Failed to fetch URL: ' . $response->get_error_message());
            }

            $html = wp_remote_retrieve_body($response);
            if (empty($html)) {
                throw new Exception('Empty response from URL');
            }

            $css_files = $this->extractor->extract_css_files($html);
            return $this->process_css_files($css_files, $html);
        } catch (Exception $e) {
            error_log('MACP CSS Test Error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function process_css_files($css_files, $html) {
        $results = [];
        foreach ($css_files as $file) {
            try {
                $content = $this->extractor->get_stylesheet_content($file);
                if (!$content) continue;

                $original_size = strlen($content);
                $used_selectors = $this->extractor->extract_used_selectors($html);
                $optimized_content = $this->optimizer->optimize($content, $used_selectors);
                $optimized_size = strlen($optimized_content);

                $results[] = [
                    'file' => $file,
                    'originalSize' => $original_size,
                    'optimizedSize' => $optimized_size,
                    'success' => true
                ];
            } catch (Exception $e) {
                $results[] = [
                    'file' => $file,
                    'originalSize' => 0,
                    'optimizedSize' => 0,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        return $results;
    }
}
