<?php
require_once 'ApiClient.php';

$client = new ApiClient();
// Fetch 50 products to ensure we see all 17 items.
$products_data = $client->getProducts(1, 50);
$products = [];
$error = null;

if (isset($products_data['error'])) {
    $error = $products_data['error'];
} 
// WooCommerce API returns the array of products directly on success, NOT wrapped in 'data' usually, 
// UNLESS there is an error.
// The ApiClient returns decoded JSON.
// If it's a list, it's an array of objects.
// If it's an error, it's an object with 'code', 'message' etc. OR our custom 'error' key from ApiClient.

elseif (isset($products_data['code'])) {
     // API returned a specific error payload (like the 401 we saw)
     $error = $products_data['message'];
} elseif (is_array($products_data)) {
    $products = $products_data;
} else {
    $error = "Unexpected response from API.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WooCommerce Products</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header class="app-header">
        <div class="container">
            <h1>Our Collection</h1>
            <p>Curated products just for you.</p>
        </div>
    </header>

    <main class="container">
        <?php if ($error): ?>
            <div class="error-container">
                <div class="error-icon">⚠️</div>
                <h2>Oops! Something went wrong.</h2>
                <p><?php echo htmlspecialchars($error); ?></p>
                <?php if (strpos($error, 'woocommerce_rest_cannot_view') !== false): ?>
                    <p class="error-hint"><strong>Access Denied:</strong> The API Keys provided are valid but do not have permission to view products. Please generate new keys for an <strong>Administrator</strong> user.</p>
                <?php else: ?>
                    <p class="error-hint">Please check your API connection and try again.</p>
                <?php endif; ?>
            </div>
        <?php elseif (empty($products)): ?>
            <div class="empty-state">
                <h2>No products found</h2>
                <p>We couldn't find any products in the store right now.</p>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <?php 
                        $price = $product['price_html'] ?? ($product['price'] ? '$' . $product['price'] : '');
                        $image_src = $product['images'][0]['src'] ?? 'https://via.placeholder.com/300x300?text=No+Image';
                        $status = $product['stock_status'] ?? 'unknown';
                        $status_label = str_replace('-', ' ', $status);
                    ?>
                    <article class="product-card">
                        <div class="product-image-wrapper">
                            <span class="stock-badge <?php echo htmlspecialchars($status); ?>">
                                <?php echo ucwords($status_label); ?>
                            </span>
                            <img src="<?php echo htmlspecialchars($image_src); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" loading="lazy">
                        </div>
                        <div class="product-info">
                            <h2 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h2>
                            <div class="product-meta">
                                <span class="product-price"><?php echo $price; // price_html contains HTML ?></span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    
    <footer class="app-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> WooCommerce API Client. Built with PHP & CSS.</p>
        </div>
    </footer>

</body>
</html>
