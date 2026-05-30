<?php
// api/budgets.php
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
        $month = (int)($_GET['month'] ?? date('n'));
        $year  = (int)($_GET['year']  ?? date('Y'));
        $rows  = Database::fetchAll(
            'SELECT b.id, b.amount AS budget, b.alert_at_pct,
                    c.name, c.icon, c.color_hex,
                    COALESCE(SUM(t.amount),0) AS spent
             FROM budgets b
             JOIN categories c ON c.id = b.category_id
             LEFT JOIN transactions t
               ON t.category_id = b.category_id
              AND t.user_id = b.user_id
              AND MONTH(t.txn_date) = b.month
              AND YEAR(t.txn_date) = b.year
              AND t.type = "expense"
             WHERE b.user_id = ? AND b.month = ? AND b.year = ? AND b.is_active = 1
             GROUP BY b.id
             ORDER BY (spent/b.amount) DESC',
            [$userId, $month, $year]
        );
        // Add percentage and alert flag
        foreach ($rows as &$r) {
            $r['pct']       = $r['budget'] > 0 ? round(($r['spent'] / $r['budget']) * 100, 1) : 0;
            $r['over']      = $r['spent'] > $r['budget'];
            $r['alert']     = $r['pct'] >= $r['alert_at_pct'] && !$r['over'];
        }
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    case 'add':
    case 'update':
        $catId      = (int)($body['category_id'] ?? 0);
        $amount     = (float)($body['amount'] ?? 0);
        $month      = (int)($body['month'] ?? date('n'));
        $year       = (int)($body['year']  ?? date('Y'));
        $alertAt    = (int)($body['alert_at_pct'] ?? 80);
        if ($catId <= 0 || $amount <= 0) {
            echo json_encode(['success'=>false,'message'=>'Invalid data']); break;
        }
        Database::execute(
            'INSERT INTO budgets (user_id, category_id, month, year, amount, alert_at_pct)
             VALUES (?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE amount=VALUES(amount), alert_at_pct=VALUES(alert_at_pct)',
            [$userId, $catId, $month, $year, $amount, $alertAt]
        );
        echo json_encode(['success' => true]);
        break;

    case 'delete':
        $id = (int)($body['id'] ?? 0);
        $ok = Database::execute(
            'UPDATE budgets SET is_active = 0 WHERE id = ? AND user_id = ?',
            [$id, $userId]
        );
        echo json_encode(['success' => $ok > 0]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
