<?php
// api/goals.php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

Auth::startSession();
$userId = Auth::requireLogin();

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? ($body['action'] ?? '');

switch ($action) {
    case 'list':
        $rows = Database::fetchAll(
            'SELECT *,
                    ROUND((saved_amount / NULLIF(target_amount,0)) * 100, 1) AS progress_pct,
                    (target_amount - saved_amount) AS remaining
             FROM savings_goals
             WHERE user_id = ? ORDER BY is_achieved ASC, created_at DESC',
            [$userId]
        );
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    case 'add':
        $name   = trim(strip_tags($body['name'] ?? ''));
        $target = (float)($body['target_amount'] ?? 0);
        $saved  = (float)($body['saved_amount']  ?? 0);
        $date   = $body['target_date'] ?? null;
        $icon   = mb_substr($body['icon'] ?? '🎯', 0, 10);
        $color  = preg_match('/^#[0-9a-fA-F]{6}$/', $body['color_hex'] ?? '') ? $body['color_hex'] : '#1D9E75';

        if (!$name || $target <= 0) { echo json_encode(['success'=>false,'message'=>'Name and target required']); break; }
        $id = Database::insert(
            'INSERT INTO savings_goals (user_id, name, target_amount, saved_amount, target_date, icon, color_hex)
             VALUES (?,?,?,?,?,?,?)',
            [$userId, $name, $target, $saved, $date ?: null, $icon, $color]
        );
        echo json_encode(['success' => true, 'id' => $id]);
        break;

    case 'add_funds':
        $id     = (int)($body['id'] ?? 0);
        $amount = (float)($body['amount'] ?? 0);
        if ($id <= 0 || $amount <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid data']); break; }
        Database::execute(
            'UPDATE savings_goals
             SET saved_amount = LEAST(saved_amount + ?, target_amount),
                 is_achieved  = IF(saved_amount + ? >= target_amount, 1, 0)
             WHERE id = ? AND user_id = ?',
            [$amount, $amount, $id, $userId]
        );
        echo json_encode(['success' => true]);
        break;

    case 'delete':
        $id = (int)($body['id'] ?? 0);
        $ok = Database::execute('DELETE FROM savings_goals WHERE id = ? AND user_id = ?', [$id, $userId]);
        echo json_encode(['success' => $ok > 0]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
