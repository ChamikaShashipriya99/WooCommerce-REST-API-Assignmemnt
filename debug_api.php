<?php
require_once 'config.php';

function test_url($url, $method = 'GET') {
    echo "Testing URL: $url\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Debug-Script/1.0');
    
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "cURL Error: $error\n";
    } else {
        echo "HTTP Code: " . $info['http_code'] . "\n";
        echo "Response Snippet: " . substr($response, 0, 150) . "...\n";
        echo "Response Length: " . strlen($response) . "\n";
    }
    echo "----------------------------------------\n";
}

// 1. Check Root JSON (No Auth) - Should return detailed WP JSON info
test_url(WC_STORE_URL . '/wp-json/');

// 2. Check Products with Query Param Auth
$params = [
    'consumer_key' => WC_CONSUMER_KEY,
    'consumer_secret' => WC_CONSUMER_SECRET,
    'per_page' => 1
];
test_url(WC_STORE_URL . '/wp-json/wc/v3/products?' . http_build_query($params));

// 3. Check Products with Basic Auth
$auth_url = WC_STORE_URL . '/wp-json/wc/v3/products?per_page=1';
echo "Testing Basic Auth at: $auth_url\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $auth_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_USERPWD, WC_CONSUMER_KEY . ':' . WC_CONSUMER_SECRET);
curl_setopt($ch, CURLOPT_USERAGENT, 'Debug-Script/1.0');

$response = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);
echo "HTTP Code: " . $info['http_code'] . "\n";
echo "Response: " . substr($response, 0, 150) . "\n";
echo "----------------------------------------\n";

// 4. Check Categories
$cat_params = [
    'consumer_key' => WC_CONSUMER_KEY,
    'consumer_secret' => WC_CONSUMER_SECRET,
    'per_page' => 1
];
test_url(WC_STORE_URL . '/wp-json/wc/v3/products/categories?' . http_build_query($cat_params));

// 5. Check INVALID Keys (To distinguishing between "Bad Perms" and "Keys Ignored")
echo "Testing INVALID keys...\n";
$bad_params = [
    'consumer_key' => 'ck_invalid_test_key_123',
    'consumer_secret' => 'cs_invalid_test_secret_123',
    'per_page' => 1
];
// 6. Check Rewrite Rule Bypass (index.php/wp-json/...)
echo "Testing index.php Bypass...\n";
$bypass_url = 'http://localhost/woocommerce/index.php/wp-json/wc/v3/products?' . http_build_query($cat_params);
test_url($bypass_url);

// 7. Check Standard WP Posts (Should be public)
echo "Testing WP Posts (Public)...\n";
test_url(WC_STORE_URL . '/wp-json/wp/v2/posts?per_page=1');

// 8. Check OAuth Param Names (Alternative to consumer_key)
echo "Testing oauth_consumer_key param style...\n";
$oauth_params = [
    'oauth_consumer_key' => WC_CONSUMER_KEY,
    'oauth_consumer_secret' => WC_CONSUMER_SECRET,
    'oauth_signature_method' => 'HMAC-SHA1', // Just forcing it to look like OAuth
    'per_page' => 1
];
test_url(WC_STORE_URL . '/wp-json/wc/v3/products?' . http_build_query($oauth_params));
