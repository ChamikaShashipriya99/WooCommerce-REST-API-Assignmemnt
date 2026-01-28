<?php
/**
 * File: test_api.php
 *
 * Purpose:
 * - Simple command-style script to test whether the WooCommerce API credentials and
 *   connectivity are working.
 *
 * Role in the WooCommerce REST API integration:
 * - Instantiates `ApiClient` and performs a products request.
 * - Prints a success message or an error message based on the API response.
 *
 * Technologies used:
 * - PHP
 * - WooCommerce REST API (via `ApiClient`)
 *
 * Security notes:
 * - This script does not contain credentials directly; it relies on configuration.
 * - For production use, ensure credentials come from environment variables and
 *   that the store is accessed over HTTPS.
 */
require_once 'ApiClient.php';

$client = new ApiClient();

// --- Data fetching logic ---
// Make a basic request to confirm API connectivity and authentication.
$products = $client->getProducts();

// --- Error handling / JSON response handling ---
// `getProducts()` returns either ['error' => ...] or ['data' => ... , 'meta' => ...].
if (isset($products['error'])) {
    echo "Error: " . $products['error'] . "\n";
} else {
    echo "Connection successful!\n";
    echo "Found " . count($products) . " products.\n";
    if (count($products) > 0) {
        echo "First product: " . $products[0]['name'] . "\n";
    }
}
