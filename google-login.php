<?php
/**
 * Google OAuth Login - Refactored Version
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

try {
    // Initiate Google OAuth login
    googleLoginRedirect($googleConfig);

} catch (Exception $e) {
    // Log error and show user-friendly message
    error_log('Google OAuth Error: ' . $e->getMessage());

    // Show error page
    showErrorPage($e->getMessage());
}
