<?php
require_once 'config.php';

class ApiClient {
    private $url;
    private $key;
    private $secret;

    public function __construct() {
        $this->url = WC_STORE_URL;
        $this->key = WC_CONSUMER_KEY;
        $this->secret = WC_CONSUMER_SECRET;
    }

    public function getProducts($page = 1, $per_page = 10) {
        $endpoint = $this->url . '/wp-json/wc/v3/products';
        
        $params = [
            'page' => $page,
            'per_page' => $per_page,
            'status' => 'publish',
            'oauth_consumer_key' => $this->key,
            'oauth_timestamp' => time(),
            'oauth_nonce' => bin2hex(random_bytes(16)),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_version' => '1.0',
        ];

        // 1. Sort parameters by key
        ksort($params);

        // 2. Normalize parameters
        $normalized_params = [];
        foreach ($params as $key => $value) {
            $normalized_params[] = rawurlencode($key) . '=' . rawurlencode($value);
        }
        $param_string = implode('&', $normalized_params);

        // 3. Create Signature Base String
        $base_string = 'GET&' . rawurlencode($endpoint) . '&' . rawurlencode($param_string);

        // 4. Create Signature Key
        $secret_key = rawurlencode($this->secret) . '&';

        // 5. Generate Signature
        $signature = base64_encode(hash_hmac('sha1', $base_string, $secret_key, true));

        // 6. Add signature to params
        $params['oauth_signature'] = $signature;

        // 7. Build final query string
        $query_string = http_build_query($params);
        $full_url = $endpoint . '?' . $query_string;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $full_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true); // Capture headers
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'WooCommerce-API-Client/1.0');
        
        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header_string = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => 'cURL Error: ' . $error];
        }

        if ($http_code >= 400) {
             return ['error' => 'HTTP Error: ' . $http_code . ' Response: ' . $body];
        }

        // Parse headers for pagination
        $total_products = 0;
        $total_pages = 0;
        if (preg_match('/x-wp-total:\s*(\d+)/i', $header_string, $matches)) {
            $total_products = (int)$matches[1];
        }
        if (preg_match('/x-wp-totalpages:\s*(\d+)/i', $header_string, $matches)) {
            $total_pages = (int)$matches[1];
        }

        $data = json_decode($body, true);

        return [
            'data' => $data,
            'meta' => [
                'total_products' => $total_products,
                'total_pages' => $total_pages,
                'current_page' => $page
            ]
        ];
    }
}
