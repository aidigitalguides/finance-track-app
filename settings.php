<?php
// api/settings.php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

Auth::startSession();
$userId = Auth::requireLogin();

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? 'save';

if ($action === 'set_pin') {
    $pin = $body['pin'] ?? '';
    if (!preg_match('/^\d{4,6}$/', $pin)) {
        echo json_encode(['success'=>false,'message'=>'PIN must be 4–6 digits']);
        exit;
    }
    $hash = password_hash($pin, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
    Database::execute('UPDATE users SET pin_hash = ? WHERE id = ?', [$hash, $userId]);
    echo json_encode(['success' => true]);
    exit;
}

// Save individual setting
$allowed = ['dark_mode','large_text','notif_budget','notif_emi','notif_card','tax_regime','default_view','first_day_week'];
$key     = $body['key'] ?? '';
$value   = $body['value'] ?? '';

if (!in_array($key, $allowed)) {
    echo json_encode(['success'=>false,'message'=>'Invalid setting key']);
    exit;
}

// Validate value types
if (in_array($key, ['dark_mode','large_text','notif_budget','notif_emi','notif_card','first_day_week'])) {
    $value = $value ? 1 : 0;
}
if ($key === 'tax_regime' && !in_array($value, ['old','new'])) {
    echo json_encode(['success'=>false,'message'=>'Invalid value']); exit;
}

// Upsert settings row
Database::execute(
    "INSERT INTO user_settings (user_id, $key) VALUES (?, ?)
     ON DUPLICATE KEY UPDATE $key = VALUES($key)",
    [$userId, $value]
);

echo json_encode(['success' => true]);
