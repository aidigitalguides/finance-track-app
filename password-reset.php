<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/Mailer.php';

Auth::startSession();

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? '';

switch ($action) {

    case 'request':
        $email = strtolower(trim($body['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success'=>false,'message'=>'Invalid email address']);
            exit;
        }

        // Always return same message to prevent email enumeration
        $msg = "If {$email} is registered, you'll receive a reset link within a few minutes. Check your spam folder too.";

        $user = Database::fetchOne('SELECT id, name FROM users WHERE email = ? AND is_active = 1', [$email]);
        if ($user) {
            // Delete old tokens for this email
            Database::execute('DELETE FROM password_resets WHERE email = ?', [$email]);

            // Generate secure token
            $token   = bin2hex(random_bytes(32));
            $expires = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

            Database::insert(
                'INSERT INTO password_resets (email, token, expires_at) VALUES (?,?,?)',
                [$email, $token, $expires]
            );

            $resetUrl = APP_URL . '/reset-password.php?token=' . $token;
            $sent = Mailer::send(
                $email,
                'Reset your Finance Track password',
                Mailer::passwordResetEmail($user['name'], $resetUrl)
            );

            if (!$sent) {
                error_log("Failed to send password reset email to {$email}");
            }
        }

        echo json_encode(['success' => true, 'message' => $msg]);
        break;

    case 'reset':
        $token    = trim($body['token'] ?? '');
        $password = $body['password'] ?? '';

        if (!$token || strlen($password) < 8) {
            echo json_encode(['success'=>false,'message'=>'Invalid request']);
            exit;
        }

        $row = Database::fetchOne(
            'SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()',
            [$token]
        );

        if (!$row) {
            echo json_encode(['success'=>false,'message'=>'Reset link is invalid or has expired. Please request a new one.']);
            exit;
        }

        // Update password
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        Database::execute('UPDATE users SET password_hash = ? WHERE email = ?', [$hash, $row['email']]);

        // Mark token as used
        Database::execute('UPDATE password_resets SET used = 1 WHERE token = ?', [$token]);

        // Invalidate all sessions for this user
        $user = Database::fetchOne('SELECT id FROM users WHERE email = ?', [$row['email']]);
        if ($user) {
            Database::execute('DELETE FROM sessions WHERE user_id = ?', [$user['id']]);
        }

        echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
