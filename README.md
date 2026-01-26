# WooCommerce REST API Client

A standalone PHP application that fetches and displays products from a WooCommerce store using the REST API. This project demonstrates secure API authentication (OAuth 1.0a), JSON data handling, and premium frontend design.

## Features

-   **Secure Authentication**: Uses OAuth 1.0a (HMAC-SHA1) to ensure robust connectivity, even on local HTTP environments.
-   **Dynamic Product Listing**: Fetches products in real-time from your WooCommerce store.
-   **Premium UI**: A responsive, modern grid layout with "Outfit" typography and hover effects.
-   **Smart Status Badges**: Automatically displays "In Stock" or "Out of Stock" badges.
-   **Error Handling**: Gracefully handles API errors (like 401 Unauthorized) and empty states with user-friendly messages.

## Prerequisites

-   A running WooCommerce store (Localhost or Live).
-   PHP 7.4 or higher.
-   cURL extension enabled in PHP.

## Setup Instructions

1.  **Get API Keys**:
    -   Go to your WordPress Dashboard -> WooCommerce -> Settings -> Advanced -> REST API.
    -   Click "Add Key".
    -   **Important**: Select a User with **Administrator** or **Shop Manager** role.
    -   Set Permissions to **Read/Write**.
    -   Generate API Key.

2.  **Configure the Application**:
    -   Open `config.php`.
    -   Update the following constants with your details:

    ```php
    define('WC_STORE_URL', 'http://localhost/woocommerce'); // Your Store URL
    define('WC_CONSUMER_KEY', 'ck_...'); // Your Consumer Key
    define('WC_CONSUMER_SECRET', 'cs_...'); // Your Consumer Secret
    ```

3.  **Run**:
    -   Place the project folder in your web server's root (e.g., `c:/wamp64/www/woocommerce-api-client`).
    -   Open your browser and navigate to `http://localhost/woocommerce-api-client/`.

## Troubleshooting

-   **401 Access Denied / "Sorry, you cannot list resources"**:
    -   Ensure your API Keys were generated for an **Administrator** user.
    -   If using WAMP/Localhost, ensure the keys are correct in `config.php`. The built-in OAuth 1.0a implementation handles most server-side stripping issues automatically.

## Project Structure

-   `index.php`: Main entry point. Fetches and renders the product grid.
-   `ApiClient.php`: Handles the OAuth 1.0a signature generation and HTTP requests.
-   `config.php`: Stores your sensitive API credentials.
-   `style.css`: Contains all custom styling for the application.

---
*Built for WooCommerce REST API integration assessment by Chamika shashipriya Under DoAcademy Full stack Traning program*
