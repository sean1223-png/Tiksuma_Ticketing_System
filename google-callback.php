<?php
/**
 * Google OAuth Callback - Refactored Version
 * Uses reusable functions from includes/google-auth-functions.php
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load Google authentication functions
require_once __DIR__ . '/includes/google-auth-functions.php';

// Load configuration
$googleConfig = require __DIR__ . '/config/google-oauth.php';

// Database configuration
$dbConfig = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'tiksumadb'
];

try {
    // Handle Google OAuth callback
    handleGoogleCallback($googleConfig, $dbConfig);

} catch (Exception $e) {
    // Log error
    error_log('Google OAuth Callback Error: ' . $e->getMessage());

    // Clear any partial session data
    if (isset($_SESSION['oauth_state'])) {
        unset($_SESSION['oauth_state'], $_SESSION['oauth_provider']);
    }

    // Show error page
    showErrorPage($e->getMessage());
}
