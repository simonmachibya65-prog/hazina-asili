<?php
/**
 * OAuth Controller — handles Google and Microsoft social login.
 * Flow: Redirect to provider → User authorizes → Callback with code → Exchange for token → Get user info → Login/Register
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/ActivityLog.php';

$provider = sanitize($_GET['provider'] ?? '');
$action   = sanitize($_GET['action'] ?? 'redirect');

if (!in_array($provider, ['google', 'microsoft'])) {
    setFlash('error', 'Invalid OAuth provider.');
    redirect('views/auth/login.php');
}

switch ($action) {
    case 'redirect':
        handleRedirect($provider);
        break;
    case 'callback':
        handleCallback($provider);
        break;
    default:
        redirect('views/auth/login.php');
}

// ──────────────────────────────────────────────────────────────────────────────
// REDIRECT — Send user to provider's authorization page
// ──────────────────────────────────────────────────────────────────────────────

function handleRedirect(string $provider): void {
    // Generate state token to prevent CSRF
    $state = generateToken(16);
    $_SESSION['oauth_state'] = $state;
    $_SESSION['oauth_provider'] = $provider;

    if ($provider === 'google') {
        if (empty(GOOGLE_CLIENT_ID)) {
            setFlash('error', 'Google login is not configured. Please contact the administrator.');
            redirect('views/auth/login.php');
        }
        $params = http_build_query([
            'client_id'     => GOOGLE_CLIENT_ID,
            'redirect_uri'  => GOOGLE_REDIRECT_URI,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'access_type'   => 'offline',
            'prompt'        => 'select_account',
        ]);
        header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
        exit;
    }

    if ($provider === 'microsoft') {
        if (empty(MICROSOFT_CLIENT_ID)) {
            setFlash('error', 'Microsoft login is not configured. Please contact the administrator.');
            redirect('views/auth/login.php');
        }
        $params = http_build_query([
            'client_id'     => MICROSOFT_CLIENT_ID,
            'redirect_uri'  => MICROSOFT_REDIRECT_URI,
            'response_type' => 'code',
            'scope'         => 'openid email profile User.Read',
            'state'         => $state,
            'response_mode' => 'query',
        ]);
        header('Location: https://login.microsoftonline.com/common/oauth2/v2.0/authorize?' . $params);
        exit;
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// CALLBACK — Provider redirects back with authorization code
// ──────────────────────────────────────────────────────────────────────────────

function handleCallback(string $provider): void {
    // Verify state to prevent CSRF
    $state = $_GET['state'] ?? '';
    if (!$state || !isset($_SESSION['oauth_state']) || !hash_equals($_SESSION['oauth_state'], $state)) {
        unset($_SESSION['oauth_state']);
        setFlash('error', 'Invalid OAuth state. Please try again.');
        redirect('views/auth/login.php');
    }
    unset($_SESSION['oauth_state']);

    // Check for errors from provider
    if (!empty($_GET['error'])) {
        $errorDesc = sanitize($_GET['error_description'] ?? $_GET['error']);
        setFlash('error', 'Login cancelled: ' . $errorDesc);
        redirect('views/auth/login.php');
    }

    $code = $_GET['code'] ?? '';
    if (!$code) {
        setFlash('error', 'No authorization code received.');
        redirect('views/auth/login.php');
    }

    // Exchange code for access token
    $tokenData = exchangeCodeForToken($provider, $code);
    if (!$tokenData) {
        setFlash('error', 'Failed to get access token from ' . ucfirst($provider) . '.');
        redirect('views/auth/login.php');
    }

    // Get user profile from provider
    $profile = getUserProfile($provider, $tokenData['access_token']);
    if (!$profile || empty($profile['email'])) {
        setFlash('error', 'Could not retrieve your profile from ' . ucfirst($provider) . '.');
        redirect('views/auth/login.php');
    }

    // Login or register the user
    loginOrRegisterOAuth($provider, $profile);
}

// ──────────────────────────────────────────────────────────────────────────────
// Exchange authorization code for access token
// ──────────────────────────────────────────────────────────────────────────────

function exchangeCodeForToken(string $provider, string $code): ?array {
    if ($provider === 'google') {
        $url = 'https://oauth2.googleapis.com/token';
        $params = [
            'code'          => $code,
            'client_id'     => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri'  => GOOGLE_REDIRECT_URI,
            'grant_type'    => 'authorization_code',
        ];
    } elseif ($provider === 'microsoft') {
        $url = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
        $params = [
            'code'          => $code,
            'client_id'     => MICROSOFT_CLIENT_ID,
            'client_secret' => MICROSOFT_CLIENT_SECRET,
            'redirect_uri'  => MICROSOFT_REDIRECT_URI,
            'grant_type'    => 'authorization_code',
        ];
    } else {
        return null;
    }

    $response = httpPost($url, $params);
    if (!$response || empty($response['access_token'])) {
        error_log("OAuth token exchange failed for {$provider}: " . json_encode($response));
        return null;
    }

    return $response;
}

// ──────────────────────────────────────────────────────────────────────────────
// Get user profile from provider API
// ──────────────────────────────────────────────────────────────────────────────

function getUserProfile(string $provider, string $accessToken): ?array {
    if ($provider === 'google') {
        $url = 'https://www.googleapis.com/oauth2/v2/userinfo';
    } elseif ($provider === 'microsoft') {
        $url = 'https://graph.microsoft.com/v1.0/me';
    } else {
        return null;
    }

    $response = httpGet($url, $accessToken);
    if (!$response) return null;

    // Normalize profile data
    if ($provider === 'google') {
        return [
            'email' => $response['email'] ?? null,
            'name'  => $response['name'] ?? ($response['given_name'] ?? 'User'),
            'avatar'=> $response['picture'] ?? null,
            'id'    => $response['id'] ?? null,
        ];
    }

    if ($provider === 'microsoft') {
        return [
            'email' => $response['mail'] ?? $response['userPrincipalName'] ?? null,
            'name'  => $response['displayName'] ?? 'User',
            'avatar'=> null, // Microsoft Graph needs separate call for photo
            'id'    => $response['id'] ?? null,
        ];
    }

    return null;
}

// ──────────────────────────────────────────────────────────────────────────────
// Login existing user or create new account
// ──────────────────────────────────────────────────────────────────────────────

function loginOrRegisterOAuth(string $provider, array $profile): void {
    $userModel = new User();
    $logModel  = new ActivityLog();

    $email = strtolower(trim($profile['email']));
    $name  = $profile['name'];

    // Check if user already exists
    $user = $userModel->findByEmail($email);

    if (!$user) {
        // Register new user as researcher
        $userId = $userModel->create([
            'name'        => $name,
            'email'       => $email,
            'password'    => generateToken(32), // Random password (won't be used for OAuth users)
            'role'        => ROLE_RESEARCHER,
            'institution' => null,
        ]);
        $user = $userModel->findByEmail($email);
        $logModel->log($userId, 'oauth_register', "Registered via {$provider}: {$email}", 'user', $userId);
        setFlash('success', "Welcome! Your account was created via " . ucfirst($provider) . ".");
    } else {
        $logModel->log($user['id'], 'oauth_login', "Logged in via {$provider}");
    }

    // Create session
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user'] = [
        'id'    => $user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
        'role'  => $user['role'],
    ];
    $_SESSION['last_activity'] = time();
    $_SESSION['oauth_provider'] = $provider;

    // Redirect to researcher dashboard (OAuth users are always researchers)
    redirect('views/researcher/dashboard.php');
}

// ──────────────────────────────────────────────────────────────────────────────
// HTTP Helpers
// ──────────────────────────────────────────────────────────────────────────────

function httpPost(string $url, array $params): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($params),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("OAuth httpPost error: {$error}");
        return null;
    }

    return json_decode($response, true);
}

function httpGet(string $url, string $bearerToken): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $bearerToken,
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("OAuth httpGet error: {$error}");
        return null;
    }

    return json_decode($response, true);
}
