<?php
// ============================================================
// api/auth-google.php — Google OAuth 2.0 Handler
// Finance Track v1.0
//
// SETUP STEPS:
// 1. Go to https://console.cloud.google.com/
// 2. Create a project → APIs & Services → Credentials
// 3. Create OAuth 2.0 Client ID (Web application)
// 4. Add Authorised redirect URI:
//    https://app.finance-track.app/api/auth-google.php
// 5. Copy Client ID + Secret → config.php
// ============================================================

require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

Auth::startSession();

$action = $_GET['action'] ?? 'login'; // 'login' or 'register'

// ── STEP 1: Redirect to Google ────────────────────────────────
if (!isset($_GET['code'])) {
    // Store action in session so we know what to do on callback
    $_SESSION['google_oauth_action'] = $action;
    
    $params = http_build_query([
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'access_type'   => 'online',
        'prompt'        => 'select_account',
        'state'         => bin2hex(random_bytes(16)),
    ]);
    
    $_SESSION['oauth_state'] = substr($params, -32); // simple CSRF
    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
    exit;
}

// ── STEP 2: Exchange code for token ──────────────────────────
$code = $_GET['code'] ?? '';
if (!$code) {
    redirectWithError('Google sign-in cancelled.');
}

// Exchange authorization code for access token
$tokenResponse = httpPost('https://oauth2.googleapis.com/token', [
    'code'          => $code,
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code',
]);

if (empty($tokenResponse['access_token'])) {
    error_log('Google OAuth token error: ' . json_encode($tokenResponse));
    redirectWithError('Failed to connect with Google. Please try again.');
}

// ── STEP 3: Get user info from Google ─────────────────────────
$userInfo = httpGet('https://www.googleapis.com/oauth2/v3/userinfo', $tokenResponse['access_token']);

if (empty($userInfo['email'])) {
    redirectWithError('Could not retrieve your Google account info.');
}

$googleId    = $userInfo['sub'];
$email       = strtolower(trim($userInfo['email']));
$name        = $userInfo['name'] ?? explode('@', $email)[0];
$picture     = $userInfo['picture'] ?? null;
$oauthAction = $_SESSION['google_oauth_action'] ?? 'login';

// ── STEP 4: Find or create user ───────────────────────────────
$existingUser = Database::fetchOne(
    'SELECT id, name, is_active, google_id FROM users WHERE email = ? OR google_id = ?',
    [$email, $googleId]
);

if ($existingUser) {
    // User exists — log them in
    if (!$existingUser['is_active']) {
        redirectWithError('This account has been deactivated.');
    }
    
    // Update google_id and avatar if not set
    Database::execute(
        'UPDATE users SET google_id = ?, last_login = NOW(), avatar_url = COALESCE(NULLIF(avatar_url,""), ?) WHERE id = ?',
        [$googleId, $picture, $existingUser['id']]
    );
    
    // Set session
    session_regenerate_id(true);
    $_SESSION['user_id']   = $existingUser['id'];
    $_SESSION['user_name'] = $existingUser['name'];
    $_SESSION['logged_in'] = true;
    $_SESSION['csrf']      = bin2hex(random_bytes(32));
    
    // Check if onboarding was completed
    $settings = Database::fetchOne('SELECT id FROM user_settings WHERE user_id = ?', [$existingUser['id']]);
    $redirect  = $settings ? 'dashboard.php' : 'onboarding.php';
    
    header('Location: ../' . $redirect);
    exit;

} else {
    // New user — register them
    
    // Check if email already taken by non-Google account (edge case)
    $emailExists = Database::fetchOne('SELECT id FROM users WHERE email = ?', [$email]);
    if ($emailExists) {
        // Link google_id to existing email account
        Database::execute('UPDATE users SET google_id = ? WHERE email = ?', [$googleId, $email]);
        
        session_regenerate_id(true);
        $_SESSION['user_id']   = $emailExists['id'];
        $_SESSION['user_name'] = $emailExists['name'];
        $_SESSION['logged_in'] = true;
        $_SESSION['csrf']      = bin2hex(random_bytes(32));
        header('Location: ../dashboard.php');
        exit;
    }
    
    // Create new user
    $userId = Database::insert(
        'INSERT INTO users (name, email, google_id, avatar_url, password_hash) VALUES (?, ?, ?, ?, ?)',
        [$name, $email, $googleId, $picture, password_hash(bin2hex(random_bytes(24)), PASSWORD_BCRYPT)]
    );
    
    // Default settings
    Database::execute('INSERT INTO user_settings (user_id) VALUES (?)', [$userId]);
    
    // Set session
    session_regenerate_id(true);
    $_SESSION['user_id']   = $userId;
    $_SESSION['user_name'] = $name;
    $_SESSION['logged_in'] = true;
    $_SESSION['csrf']      = bin2hex(random_bytes(32));
    
    // Send welcome email
    try {
        require_once '../includes/Mailer.php';
        Mailer::send($email, 'Welcome to Finance Track! 🎉', Mailer::welcomeEmail($name));
    } catch (Throwable $e) {
        error_log('Welcome email failed: ' . $e->getMessage());
    }
    
    header('Location: ../onboarding.php');
    exit;
}

// ── HELPERS ───────────────────────────────────────────────────

function httpPost(string $url, array $data): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    return json_decode($body ?: '{}', true) ?? [];
}

function httpGet(string $url, string $token): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    return json_decode($body ?: '{}', true) ?? [];
}

function redirectWithError(string $msg): void {
    header('Location: ../login.php?error=' . urlencode($msg));
    exit;
}
