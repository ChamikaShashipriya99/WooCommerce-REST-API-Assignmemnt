<?php
require_once 'ApiClient.php';

$client = new ApiClient();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 9;

$response = $client->getProducts($page, $per_page);
$products = [];
$error = null;
$total_pages = 0;
$current_page = 1;

if (isset($response['error'])) {
    $error = $response['error'];
} 
elseif (isset($response['data']) && is_array($response['data'])) {
    $products = $response['data'];
    $total_pages = $response['meta']['total_pages'] ?? 1;
    $current_page = $response['meta']['current_page'] ?? 1;
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
        <!-- Loading State -->
        <div id="loading-state" class="loading-state" style="display: none;">
            <div class="spinner-container">
                <div class="spinner"></div>
                <p>Loading products...</p>
            </div>
        </div>

        <!-- Skeleton Loading State -->
        <div id="skeleton-state" class="skeleton-state" style="display: none;">
            <div class="product-grid">
                <?php for ($i = 0; $i < 9; $i++): ?>
                    <article class="product-card skeleton-card">
                        <div class="skeleton-image"></div>
                        <div class="skeleton-content">
                            <div class="skeleton-line skeleton-title"></div>
                            <div class="skeleton-line skeleton-price"></div>
                        </div>
                    </article>
                <?php endfor; ?>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="error-container" id="error-container">
                <div class="error-icon">⚠️</div>
                <h2>Oops! Something went wrong.</h2>
                <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
                <?php if (strpos($error, 'woocommerce_rest_cannot_view') !== false): ?>
                    <p class="error-hint"><strong>Access Denied:</strong> The API Keys provided are valid but do not have permission to view products. Please generate new keys for an <strong>Administrator</strong> user.</p>
                <?php else: ?>
                    <p class="error-hint">Please check your API connection and try again.</p>
                <?php endif; ?>
                <button class="retry-btn" onclick="retryLoad()">
                    <span class="retry-icon">↻</span>
                    <span>Try Again</span>
                </button>
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

    <?php if ($total_pages > 1 && !$error): ?>
    <div class="container pagination-container">
        <?php if ($current_page > 1): ?>
            <a href="?page=<?php echo $current_page - 1; ?>&per_page=<?php echo $per_page; ?>" class="page-btn prev">Previous</a>
        <?php else: ?>
            <span class="page-btn disabled">Previous</span>
        <?php endif; ?>

        <span class="page-info">Page <?php echo $current_page; ?> of <?php echo $total_pages; ?></span>

        <?php if ($current_page < $total_pages): ?>
            <a href="?page=<?php echo $current_page + 1; ?>&per_page=<?php echo $per_page; ?>" class="page-btn next">Next</a>
        <?php else: ?>
            <span class="page-btn disabled">Next</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
        
    <footer class="app-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> WooCommerce API Client.</p>
            <p>Made By Chamika Shashipriya at DoAcademy</p>
        </div>
    </footer>

    <script>
        function showLoading() {
            document.getElementById('loading-state').style.display = 'block';
            document.getElementById('skeleton-state').style.display = 'none';
            const errorContainer = document.getElementById('error-container');
            if (errorContainer) errorContainer.style.display = 'none';
            const productGrid = document.querySelector('.product-grid');
            if (productGrid) productGrid.style.display = 'none';
        }

        function showSkeleton() {
            document.getElementById('skeleton-state').style.display = 'block';
            document.getElementById('loading-state').style.display = 'none';
            const errorContainer = document.getElementById('error-container');
            if (errorContainer) errorContainer.style.display = 'none';
            const productGrid = document.querySelector('.product-grid');
            if (productGrid) productGrid.style.display = 'none';
        }

        function hideLoading() {
            document.getElementById('loading-state').style.display = 'none';
            document.getElementById('skeleton-state').style.display = 'none';
        }

        function retryLoad() {
            const retryBtn = event.target.closest('.retry-btn');
            const btnText = retryBtn.querySelector('span:last-child');
            const btnIcon = retryBtn.querySelector('.retry-icon');
            
            // Disable button and show loading state
            retryBtn.disabled = true;
            btnText.textContent = 'Retrying...';
            btnIcon.style.animation = 'spin 1s linear infinite';
            
            // Show skeleton loading
            showSkeleton();
            
            // Reload the page
            setTimeout(() => {
                window.location.reload();
            }, 300);
        }

        // Show skeleton on page navigation
        document.addEventListener('DOMContentLoaded', function() {
            const paginationLinks = document.querySelectorAll('.page-btn:not(.disabled)');
            paginationLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    showSkeleton();
                });
            });
        });
    </script>

</body>
</html>
