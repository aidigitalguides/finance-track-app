<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
Auth::startSession();
$userId = Auth::requireLogin();
$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? ($body['action'] ?? '');

switch ($action) {
    case 'add':
        $name    = trim(strip_tags($body['name'] ?? 'Salary'));
        $type    = $body['type'] ?? 'salary';
        $amount  = (float)($body['amount'] ?? 0);
        $freq    = $body['frequency'] ?? 'monthly';
        $day     = (int)($body['day_of_month'] ?? 1);
        if ($amount <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid amount']); break; }
        $id = Database::insert(
            'INSERT INTO income_sources (user_id, name, type, amount, frequency, day_of_month) VALUES (?,?,?,?,?,?)',
            [$userId, $name, $type, $amount, $freq, $day]
        );
        echo json_encode(['success'=>true,'id'=>$id]);
        break;
    case 'list':
        $rows = Database::fetchAll('SELECT * FROM income_sources WHERE user_id = ? AND is_active = 1 ORDER BY created_at DESC', [$userId]);
        echo json_encode(['success'=>true,'data'=>$rows]);
        break;
    case 'delete':
        $id = (int)($body['id'] ?? 0);
        Database::execute('UPDATE income_sources SET is_active = 0 WHERE id = ? AND user_id = ?', [$id, $userId]);
        echo json_encode(['success'=>true]);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error'=>'Invalid action']);
}
