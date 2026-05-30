<?php
// api/auth.php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

Auth::startSession();

$raw    = file_get_contents('php://input');
$body   = json_decode($raw, true) ?? [];
$action = $body['action'] ?? '';

switch ($action) {
    case 'register':
        $result = Auth::register(
            $body['name'] ?? '',
            $body['email'] ?? '',
            $body['password'] ?? ''
        );
        if ($result['success']) {
            Auth::login($body['email'], $body['password']);
            $result['redirect'] = 'onboarding.php';
            // Send welcome email (non-blocking)
            try {
                require_once '../includes/Mailer.php';
                Mailer::send(
                    $body['email'],
                    'Welcome to Finance Track! 🎉',
                    Mailer::welcomeEmail($body['name'] ?? 'there')
                );
            } catch (Throwable $e) { error_log('Welcome email failed: ' . $e->getMessage()); }
        }
        echo json_encode($result);
        break;

    case 'login':
        $result = Auth::login(
            $body['email'] ?? '',
            $body['password'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? ''
        );
        echo json_encode($result);
        break;

    case 'logout':
        Auth::logout();
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
