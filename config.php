<?php
// ============================================================
// config.php — Finance Track Configuration
// Place this OUTSIDE public_html for maximum security.
// If inside public_html, the .htaccess blocks direct access.
// ============================================================

define('DB_HOST',    'localhost');
define('DB_NAME',    'financetrack_db');       // your exact DB name in hPanel
define('DB_USER',    'financetrack_user');     // your exact DB user in hPanel
define('DB_PASS',    'YOUR_STRONG_PASSWORD');  // set in hPanel > Databases
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME',    'Finance Track');
define('APP_URL',     'https://app.finance-track.app');
define('APP_VERSION', '1.0.0');

// ── Google OAuth ─────────────────────────────────────────────
// Get these from https://console.cloud.google.com/
// Authorized redirect URI: https://app.finance-track.app/api/auth-google.php
define('GOOGLE_CLIENT_ID',     'YOUR_GOOGLE_CLIENT_ID_HERE');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET_HERE');
define('GOOGLE_REDIRECT_URI',  APP_URL . '/api/auth-google.php');

// ── Session ──────────────────────────────────────────────────
define('SESSION_NAME',     'ft_sess');
define('SESSION_LIFETIME', 86400);   // 24 hours
define('SESSION_SECURE',   false);   // set true after SSL confirmed
define('SESSION_HTTPONLY',  true);
define('SESSION_SAMESITE', 'Lax');

// ── Security ─────────────────────────────────────────────────
define('BCRYPT_COST', 12);
define('CSRF_TOKEN_LENGTH', 32);

// ── Email ────────────────────────────────────────────────────
define('MAIL_FROM',   'noreply@finance-track.app');
define('MAIL_NAME',   'Finance Track');
define('CRON_SECRET', 'change-this-to-a-random-long-secret-key');

// ── Timezone ─────────────────────────────────────────────────
date_default_timezone_set('Asia/Kolkata');

// ── Error reporting (set display_errors = 0 in production) ──
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
