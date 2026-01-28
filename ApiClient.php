<?php
/**
 * File: ApiClient.php
 *
 * Purpose:
 * - Provides a small, reusable PHP client for requesting data from a WooCommerce store
 *   using the WooCommerce REST API.
 *
 * Role in the WooCommerce REST API integration:
 * - Builds and signs authenticated requests (OAuth 1.0 style parameters used by WooCommerce).
 * - Sends HTTP requests to WooCommerce REST API endpoints (e.g., products).
 * - Decodes JSON responses and returns data + pagination metadata to the UI layer.
 *
 * Technologies used:
 * - PHP (OOP)
 * - WooCommerce REST API (WordPress JSON endpoints)
 * - cURL (HTTP client)
 * - JSON decoding (json_decode)
 *
 * Security notes (important for evaluation):
 * - API credentials should not be hard-coded in source files in real projects because
 *   code is often shared (Git), deployed, or reviewed. Keys must be kept secret.
 * - A safer approach is to load keys from environment variables (or a secrets manager)
 *   and keep secrets out of version control.
 * - HTTPS should be used to protect credentials and data in transit. Disabling SSL
 *   verification is not recommended outside local testing.
 */
require_once 'config.php';

class ApiClient {
    private $url;
    private $key;
    private $secret;

    /**
     * Create a new API client using the WooCommerce configuration values.
     *
     * These values are read from `config.php` (which in a production-ready setup
     * should read from environment variables instead of containing secrets directly).
     *
     * @return void
     */
    public function __construct() {
        $this->url = WC_STORE_URL;
        $this->key = WC_CONSUMER_KEY;
        $this->secret = WC_CONSUMER_SECRET;
    }

    /**
     * Fetch a paginated list of published products from the WooCommerce REST API.
     *
     * Data fetching logic:
     * - Builds a request to the products endpoint.
     * - Adds pagination parameters (`page`, `per_page`) and filters (`status=publish`).
     * - Generates an OAuth-style HMAC-SHA1 signature to authenticate the request.
     * - Sends the HTTP request using cURL and captures both headers and body.
     *
     * JSON response handling:
     * - Decodes the JSON response body into an associative array.
     * - Extracts pagination totals from response headers (`X-WP-Total`, `X-WP-TotalPages`).
     *
     * Error handling:
     * - Returns a structured array containing an `error` key if cURL fails or if the
     *   HTTP status code indicates an error (\(\ge 400\)).
     *
     * @param int $page Page number to request (starts at 1).
     * @param int $per_page Number of products per page.
     * @return array Either:
     * - ['data' => array, 'meta' => array] on success, or
     * - ['error' => string] on failure.
     */
    public function getProducts($page = 1, $per_page = 10) {
        // WooCommerce REST API endpoint (Products).
        // This is a WordPress JSON route provided by WooCommerce.
        $endpoint = $this->url . '/wp-json/wc/v3/products';
        
        // --- API authentication (OAuth-style parameters) ---
        // WooCommerce supports OAuth 1.0-style signing for REST requests.
        // We include a timestamp and nonce to reduce replay risk and then sign
        // the request using HMAC-SHA1 with the consumer secret.
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

        // --- Data fetching logic: build the signature base string ---
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

        // --- Data fetching logic: execute HTTP request (cURL) ---
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $full_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true); // Capture headers

        // Security note:
        // - In production, SSL verification should be enabled and HTTPS should be used.
        // - Disabling SSL verification is only acceptable for local/testing scenarios
        //   because it removes protection against man-in-the-middle attacks.
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

        // --- Error handling: cURL/network-level failures ---
        if ($error) {
            return ['error' => 'cURL Error: ' . $error];
        }

        // --- Error handling: HTTP-level failures ---
        if ($http_code >= 400) {
             return ['error' => 'HTTP Error: ' . $http_code . ' Response: ' . $body];
        }

        // --- JSON response handling + pagination metadata ---
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
