<?php
class MACP_CSS_Test_Handler {
    private $extractor;
    private $validator;
    private $results_formatter;

    public function __construct() {
        $this->extractor = new MACP_CSS_Extractor();
        $this->validator = new MACP_URL_Validator();
        $this->results_formatter = new MACP_CSS_Test_Results();
    }

    public function test_url($url) {
        try {
            // Validate URL
            if (!$this->validator->is_valid_url($url)) {
                throw new Exception('Invalid URL provided');
            }

            // Get page HTML
            $response = wp_remote_get($url);
            if (is_wp_error($response)) {
                throw new Exception('Failed to fetch URL: ' . $response->get_error_message());
            }

            $html = wp_remote_retrieve_body($response);
            if (empty($html)) {
                throw new Exception('Empty response from URL');
            }

            // Extract and process CSS files
            $css_files = $this->extractor->extract_css_files($html);
            $results = [];

            foreach ($css_files as $file) {
                $result = $this->process_css_file($file, $html);
                if ($result) {
                    $results[] = $result;
                }
            }

            return $this->results_formatter->format_results($results);

        } catch (Exception $e) {
            error_log('MACP CSS Test Error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function process_css_file($file, $html) {
        try {
            $content = $this->extractor->get_stylesheet_content($file);
            if (!$content) {
                return null;
            }

            $original_size = strlen($content);
            $optimized_content = $this->optimize_css($content, $html);
            $optimized_size = strlen($optimized_content);

            return [
                'file' => $file,
                'originalSize' => $original_size,
                'optimizedSize' => $optimized_size,
                'success' => true
            ];

        } catch (Exception $e) {
            return [
                'file' => $file,
                'originalSize' => 0,
                'optimizedSize' => 0,
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function optimize_css($css, $html) {
        $used_selectors = $this->extractor->extract_used_selectors($html);
        $optimizer = new MACP_CSS_Optimizer();
        return $optimizer->optimize($css, $used_selectors);
    }
}