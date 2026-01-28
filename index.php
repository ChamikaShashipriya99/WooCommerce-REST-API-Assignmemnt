<?php
/**
 * File: index.php
 *
 * Purpose:
 * - Main web page for displaying WooCommerce products in a simple storefront-style UI.
 *
 * Role in the WooCommerce REST API integration:
 * - Uses `ApiClient.php` to call the WooCommerce REST API.
 * - Handles request parameters (pagination), processes the API response, and renders
 *   HTML output for products, errors, and pagination controls.
 *
 * Technologies used:
 * - PHP (functions, arrays, output buffering)
 * - WooCommerce REST API (via `ApiClient`)
 * - HTML/CSS (page layout)
 * - JavaScript (basic loading/retry behavior)
 *
 * Security notes:
 * - This file does not store API keys directly; credentials are provided via configuration.
 * - In a production system, credentials should come from environment variables and
 *   requests should be made over HTTPS.
 */
require_once 'ApiClient.php';

/**
 * Fetch products from WooCommerce API
 * 
 * @param int $page Page number
 * @param int $perPage Products per page
 * @return array ['products' => [], 'error' => null|string, 'meta' => []]
 */
function fetchProducts($page, $perPage) {
    $client = new ApiClient();

    // --- Data fetching logic ---
    // Calls the API client which performs the authenticated request to WooCommerce.
    $response = $client->getProducts($page, $perPage);
    
    // --- JSON response handling ---
    // Normalize the API client response into a consistent structure for the UI.
    $result = [
        'products' => [],
        'error' => null,
        'meta' => [
            'total_pages' => 0,
            'current_page' => $page
        ]
    ];
    
    // --- Error handling ---
    // If the client returns an error string, show it in the UI.
    if (isset($response['error'])) {
        $result['error'] = $response['error'];
    } elseif (isset($response['data']) && is_array($response['data'])) {
        $result['products'] = $response['data'];
        $result['meta']['total_pages'] = $response['meta']['total_pages'] ?? 1;
        $result['meta']['current_page'] = $response['meta']['current_page'] ?? $page;
    } else {
        $result['error'] = "Unexpected response from API.";
    }
    
    return $result;
}

/**
 * Render product card HTML
 * 
 * @param array $product Product data
 * @return string HTML
 */
function renderProductCard($product) {
    // Basic data extraction with fallbacks to keep the UI stable.
    $price = $product['price_html'] ?? ($product['price'] ? '$' . $product['price'] : '');
    $imageSrc = $product['images'][0]['src'] ?? 'https://via.placeholder.com/300x300?text=No+Image';
    $status = $product['stock_status'] ?? 'unknown';
    $statusLabel = str_replace('-', ' ', $status);
    $name = htmlspecialchars($product['name']);
    
    ob_start();
    ?>
    <article class="product-card">
        <div class="product-image-wrapper">
            <span class="stock-badge <?php echo htmlspecialchars($status); ?>">
                <?php echo ucwords($statusLabel); ?>
            </span>
            <img src="<?php echo htmlspecialchars($imageSrc); ?>" alt="<?php echo $name; ?>" loading="lazy">
        </div>
        <div class="product-info">
            <h2 class="product-title"><?php echo $name; ?></h2>
            <div class="product-meta">
                <span class="product-price"><?php echo $price; ?></span>
            </div>
        </div>
    </article>
    <?php
    return ob_get_clean();
}

/**
 * Render products grid
 * 
 * @param array $products Array of product data
 * @return string HTML
 */
function renderProducts($products) {
    if (empty($products)) {
        return '<div class="empty-state">
            <h2>No products found</h2>
            <p>We couldn\'t find any products in the store right now.</p>
        </div>';
    }
    
    ob_start();
    ?>
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <?php echo renderProductCard($product); ?>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render error message with retry button
 * 
 * @param string $errorMessage Error message to display
 * @return string HTML
 */
function renderError($errorMessage) {
    // Detect a common WooCommerce permission error to provide a more helpful hint.
    $isAccessDenied = strpos($errorMessage, 'woocommerce_rest_cannot_view') !== false;
    
    ob_start();
    ?>
    <div class="error-container" id="error-container">
        <div class="error-icon">⚠️</div>
        <h2>Oops! Something went wrong.</h2>
        <p class="error-message"><?php echo htmlspecialchars($errorMessage); ?></p>
        <?php if ($isAccessDenied): ?>
            <p class="error-hint">
                <strong>Access Denied:</strong> The API Keys provided are valid but do not have permission to view products. 
                Please generate new keys for an <strong>Administrator</strong> user.
            </p>
        <?php else: ?>
            <p class="error-hint">Please check your API connection and try again.</p>
        <?php endif; ?>
        <button class="retry-btn" onclick="retryLoad()">
            <span class="retry-icon">↻</span>
            <span>Try Again</span>
        </button>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render loading states (spinner and skeleton)
 * 
 * @return string HTML
 */
function renderLoadingStates() {
    ob_start();
    ?>
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
    <?php
    return ob_get_clean();
}

/**
 * Render pagination controls
 * 
 * @param int $currentPage Current page number
 * @param int $totalPages Total number of pages
 * @param int $perPage Products per page
 * @return string HTML
 */
function renderPagination($currentPage, $totalPages, $perPage) {
    if ($totalPages <= 1) {
        return '';
    }
    
    ob_start();
    ?>
    <div class="container pagination-container">
        <?php if ($currentPage > 1): ?>
            <a href="?page=<?php echo $currentPage - 1; ?>&per_page=<?php echo $perPage; ?>" class="page-btn prev">Previous</a>
        <?php else: ?>
            <span class="page-btn disabled">Previous</span>
        <?php endif; ?>

        <span class="page-info">Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?></span>

        <?php if ($currentPage < $totalPages): ?>
            <a href="?page=<?php echo $currentPage + 1; ?>&per_page=<?php echo $perPage; ?>" class="page-btn next">Next</a>
        <?php else: ?>
            <span class="page-btn disabled">Next</span>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// --- Main execution / controller logic ---
// Reads pagination inputs from the query string, calls the API, and renders output.
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 9;

$data = fetchProducts($page, $perPage);
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
        <?php echo renderLoadingStates(); ?>

        <?php if ($data['error']): ?>
            <?php echo renderError($data['error']); ?>
        <?php else: ?>
            <?php echo renderProducts($data['products']); ?>
        <?php endif; ?>
    </main>

    <?php if (!$data['error']): ?>
        <?php echo renderPagination($data['meta']['current_page'], $data['meta']['total_pages'], $perPage); ?>
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
