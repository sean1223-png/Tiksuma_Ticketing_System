<?php
/**
 * Google Authentication Functions
 * Reusable functions for Google OAuth login and callback handling
 */

// Load Google API PHP Client Library
require_once __DIR__ . '/../vendor/autoload.php';

if (!class_exists('Google_Client')) {
    throw new Exception('Google API Client library not found. Please ensure the Google API PHP Client is installed via Composer.');
}

/**
 * Initiates Google OAuth login by redirecting to Google's authorization URL
 *
 * @param array $googleConfig Google OAuth configuration array
 * @throws Exception If configuration is invalid or OAuth setup fails
 */
function googleLoginRedirect($googleConfig) {
    // Validate configuration
    if (empty($googleConfig['client_id']) || empty($googleConfig['client_secret'])) {
        throw new Exception('Google OAuth configuration is incomplete. Please check your settings.');
    }

    // Initialize Google Client
    $client = new Google_Client();
    $client->setClientId($googleConfig['client_id']);
    $client->setClientSecret($googleConfig['client_secret']);
    $client->setRedirectUri($googleConfig['redirect_uri']);

    // Set scopes
    $client->addScope('email');
    $client->addScope('profile');

    // Additional security settings
    $client->setAccessType('offline'); // Request refresh token
    $client->setPrompt('select_account'); // Force account selection
    $client->setIncludeGrantedScopes(true);

    // Generate CSRF token for state parameter
    $state = bin2hex(random_bytes(32));
    $client->setState($state);

    // Store state in session for verification
    $_SESSION['oauth_state'] = $state;
    $_SESSION['oauth_provider'] = 'google';

    // Generate the Google OAuth URL
    $auth_url = $client->createAuthUrl();

    // Validate URL before redirect
    if (filter_var($auth_url, FILTER_VALIDATE_URL)) {
        header('Location: ' . $auth_url);
        exit;
    } else {
        throw new Exception('Invalid OAuth URL generated');
    }
}

/**
 * Handles Google OAuth callback and processes user authentication
 *
 * @param array $googleConfig Google OAuth configuration array
 * @param array $dbConfig Database configuration array
 * @throws Exception If callback processing fails
 */
function handleGoogleCallback($googleConfig, $dbConfig) {
    // Verify state parameter for CSRF protection
    if (!isset($_GET['state']) || !isset($_SESSION['oauth_state'])) {
        throw new Exception('Missing state parameter for security verification.');
    }

    if ($_GET['state'] !== $_SESSION['oauth_state']) {
        throw new Exception('Invalid state parameter. Possible CSRF attack.');
    }

    // Clear the state from session
    unset($_SESSION['oauth_state'], $_SESSION['oauth_provider']);

    // Check for authorization code
    if (!isset($_GET['code'])) {
        throw new Exception('No authorization code received from Google.');
    }

    // Initialize Google Client
    $client = new Google_Client();
    $client->setClientId($googleConfig['client_id']);
    $client->setClientSecret($googleConfig['client_secret']);
    $client->setRedirectUri($googleConfig['redirect_uri']);

    // Fetch access token
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (isset($token['error'])) {
        throw new Exception('Google OAuth Error: ' . $token['error_description']);
    }

    if (!isset($token['access_token'])) {
        throw new Exception('No access token received from Google.');
    }

    // Set access token
    $client->setAccessToken($token['access_token']);

    // Get user profile information
    $oauth = new Google_Service_Oauth2($client);
    $profile = $oauth->userinfo->get();

    if (!$profile || !isset($profile->email)) {
        throw new Exception('Unable to retrieve user profile from Google.');
    }

    // Connect to database
    $conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['database']);

    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    // Check if user exists in database
    $email = $profile->email;
    $stmt = $conn->prepare("SELECT id, username, user_type, profile_picture FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Existing user - log them in
        $user = $result->fetch_assoc();

        // Update profile picture if Google has a newer one
        if ($profile->picture && $profile->picture !== $user['profile_picture']) {
            $updateStmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $updateStmt->bind_param("si", $profile->picture, $user['id']);
            $updateStmt->execute();
            $updateStmt->close();
        }

        $stmt->close();

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $email;
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['profile_picture'] = $profile->picture ?: $user['profile_picture'];
        $_SESSION['login_method'] = 'google';

        // Redirect to appropriate dashboard
        redirectToDashboard($user['user_type']);

    } else {
        // New user - create account
        $username = $profile->name ?: 'User_' . time();
        $userType = 'user'; // Default user type

        // Insert new user
        $insertStmt = $conn->prepare("INSERT INTO users (username, email, user_type, profile_picture, created_at) VALUES (?, ?, ?, ?, NOW())");
        $insertStmt->bind_param("ssss", $username, $email, $userType, $profile->picture);

        if ($insertStmt->execute()) {
            $newUserId = $conn->insert_id;
            $insertStmt->close();
            $stmt->close();

            // Set session variables for new user
            $_SESSION['user_id'] = $newUserId;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['user_type'] = $userType;
            $_SESSION['profile_picture'] = $profile->picture ?: 'default-profile.png';
            $_SESSION['login_method'] = 'google';

            // Redirect to dashboard
            redirectToDashboard($userType);

        } else {
            throw new Exception('Failed to create user account: ' . $conn->error);
        }
    }

    $conn->close();
}

/**
 * Redirects user to appropriate dashboard based on user type
 *
 * @param string $userType The type of user (user, admin, it_staff)
 */
function redirectToDashboard($userType = 'user') {
    $redirectUrl = 'dashboard.php'; // Default for regular users

    if ($userType === 'admin') {
        $redirectUrl = 'admin-dashboards.php';
    } elseif ($userType === 'it_staff') {
        $redirectUrl = 'it-dashboard.php';
    }

    header('Location: ' . $redirectUrl);
    exit;
}

/**
 * Displays an error page with custom message and title
 *
 * @param string $message Error message to display
 * @param string $title Page title (default: 'Authentication Error')
 */
function showErrorPage($message, $title = 'Authentication Error') {
    http_response_code(400);
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>' . htmlspecialchars($title) . '</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f8f9fa; }
            .error-container { max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .error { color: #d9534f; font-size: 18px; margin-bottom: 20px; }
            .retry { margin-top: 20px; }
            .retry a { color: #337ab7; text-decoration: none; padding: 10px 20px; border: 1px solid #337ab7; border-radius: 5px; }
            .retry a:hover { background: #337ab7; color: white; }
        </style>
    </head>
    <body>
        <div class="error-container">
            <h1 class="error">' . htmlspecialchars($title) . '</h1>
            <p>' . htmlspecialchars($message) . '</p>
            <div class="retry">
                <a href="index.php">← Back to Login</a>
            </div>
        </div>
    </body>
    </html>';
    exit;
}
