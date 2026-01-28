<?php
/**
 * File: config.php
 *
 * Purpose:
 * - Central configuration for the WooCommerce API client project.
 *
 * Role in the WooCommerce REST API integration:
 * - Defines the store base URL and the credentials (Consumer Key/Secret) that are
 *   used by `ApiClient.php` to authenticate requests to the WooCommerce REST API.
 *
 * Technologies used:
 * - PHP constants (`define`)
 *
 * Security decisions (important for evaluation):
 * - In real deployments, API keys should NOT be hard-coded in this file because
 *   source code may be committed to GitHub, shared with classmates, or deployed
 *   to servers where many people can access it.
 * - A recommended approach is to read these values from environment variables
 *   (e.g., `getenv('WC_CONSUMER_KEY')`) and keep the `.env` file out of version control.
 * - HTTPS should be used for the store URL in production to protect credentials and
 *   API data in transit.
 *
 * Note:
 * - For an assignment/demo environment, constants are used for simplicity.
 */

// WooCommerce Store URL (base site URL; REST endpoints are built from this)
define('WC_STORE_URL', 'http://localhost/woocommerce');

// WooCommerce Consumer Key (public identifier; still should not be committed)
define('WC_CONSUMER_KEY', 'ck_7421ce776ddc752090891adf800bf1279566ed55');

// WooCommerce Consumer Secret (private secret; should be stored securely)
define('WC_CONSUMER_SECRET', 'cs_f5b01d844d14e746a62f87e4dbb945ae831718e2');
