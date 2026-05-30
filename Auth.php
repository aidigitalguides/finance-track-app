<?php
// ============================================================
// includes/Auth.php — Authentication & session management
// ============================================================

class Auth {

    // --------------------------------------------------------
    // Start secure session
    // --------------------------------------------------------
    public static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path'     => '/',
                'domain'   => '',
                'secure'   => SESSION_SECURE,
                'httponly' => SESSION_HTTPONLY,
                'samesite' => SESSION_SAMESITE,
            ]);
            session_start();
        }
    }

    // --------------------------------------------------------
    // Register new user
    // --------------------------------------------------------
    public static function register(string $name, string $email, string $password): array {
        $name  = trim(strip_tags($name));
        $email = strtolower(trim($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address'];
        }
        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters'];
        }

        // Check duplicate email
        $existing = Database::fetchOne('SELECT id FROM users WHERE email = ?', [$email]);
        if ($existing) {
            return ['success' => false, 'message' => 'Email already registered'];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);

        $userId = Database::insert(
            'INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)',
            [$name, $email, $hash]
        );

        // Create default settings row
        Database::execute(
            'INSERT INTO user_settings (user_id) VALUES (?)',
            [$userId]
        );

        return ['success' => true, 'user_id' => $userId];
    }

    // --------------------------------------------------------
    // Login
    // --------------------------------------------------------
    public static function login(string $email, string $password, string $ip = ''): array {
        $email = strtolower(trim($email));
        $user  = Database::fetchOne(
            'SELECT id, name, email, password_hash, is_active FROM users WHERE email = ?',
            [$email]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        if (!$user['is_active']) {
            return ['success' => false, 'message' => 'Account is deactivated'];
        }

        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['logged_in'] = true;
        $_SESSION['csrf']      = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));

        // Log last login
        Database::execute(
            'UPDATE users SET last_login = NOW() WHERE id = ?',
            [$user['id']]
        );

        return ['success' => true, 'user' => ['id' => $user['id'], 'name' => $user['name']]];
    }

    // --------------------------------------------------------
    // Logout
    // --------------------------------------------------------
    public static function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // --------------------------------------------------------
    // Check if logged in — redirect if not
    // --------------------------------------------------------
    public static function requireLogin(): int {
        self::startSession();
        if (empty($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
            // If this is an API request, return JSON error instead of redirect
            $script = $_SERVER['SCRIPT_NAME'] ?? '';
            if (strpos($script, '/api/') !== false) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Not authenticated', 'redirect' => 'login.php']);
                exit;
            }
            // For page requests, redirect to login
            $base = dirname($_SERVER['SCRIPT_NAME']);
            $base = rtrim(str_replace('\\', '/', $base), '/');
            header('Location: ' . $base . '/login.php');
            exit;
        }
        return (int) $_SESSION['user_id'];
    }

    // --------------------------------------------------------
    // Get current user ID (returns 0 if not logged in)
    // --------------------------------------------------------
    public static function userId(): int {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    // --------------------------------------------------------
    // CSRF helpers
    // --------------------------------------------------------
    public static function csrfToken(): string {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        }
        return $_SESSION['csrf'];
    }

    public static function verifyCsrf(string $token): bool {
        return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
    }

    // --------------------------------------------------------
    // PIN setup and verification
    // --------------------------------------------------------
    public static function setPin(int $userId, string $pin): bool {
        if (!preg_match('/^\d{4,6}$/', $pin)) return false;
        $hash = password_hash($pin, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        Database::execute('UPDATE users SET pin_hash = ? WHERE id = ?', [$hash, $userId]);
        return true;
    }

    public static function verifyPin(int $userId, string $pin): bool {
        $user = Database::fetchOne('SELECT pin_hash FROM users WHERE id = ?', [$userId]);
        if (!$user || !$user['pin_hash']) return false;
        return password_verify($pin, $user['pin_hash']);
    }
}
