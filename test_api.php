<?php
require_once 'ApiClient.php';

$client = new ApiClient();
$products = $client->getProducts();

if (isset($products['error'])) {
    echo "Error: " . $products['error'] . "\n";
} else {
    echo "Connection successful!\n";
    echo "Found " . count($products) . " products.\n";
    if (count($products) > 0) {
        echo "First product: " . $products[0]['name'] . "\n";
    }
}
