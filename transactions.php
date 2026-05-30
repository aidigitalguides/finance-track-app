<?php
// api/transactions.php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/Transaction.php';

Auth::startSession();
$userId = Auth::requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$raw    = file_get_contents('php://input');
$body   = json_decode($raw, true) ?? [];
$action = $body['action'] ?? ($_GET['action'] ?? '');

switch ($action) {
    case 'add':
        echo json_encode(Transaction::add($userId, $body));
        break;

    case 'list':
        $filters = [
            'month'       => (int)($_GET['month'] ?? date('n')),
            'year'        => (int)($_GET['year']  ?? date('Y')),
            'type'        => $_GET['type'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'search'      => $_GET['search'] ?? '',
            'limit'       => (int)($_GET['limit'] ?? 50),
            'offset'      => (int)($_GET['offset'] ?? 0),
        ];
        echo json_encode(['success' => true, 'data' => Transaction::getList($userId, $filters)]);
        break;

    case 'summary':
        $month = (int)($_GET['month'] ?? date('n'));
        $year  = (int)($_GET['year']  ?? date('Y'));
        echo json_encode(['success' => true, 'data' => Transaction::getMonthlySummary($userId, $month, $year)]);
        break;

    case 'breakdown':
        $month = (int)($_GET['month'] ?? date('n'));
        $year  = (int)($_GET['year']  ?? date('Y'));
        echo json_encode(['success' => true, 'data' => Transaction::getCategoryBreakdown($userId, $month, $year)]);
        break;

    case 'overview':
        echo json_encode(['success' => true, 'data' => Transaction::getSixMonthOverview($userId)]);
        break;

    case 'delete':
        $txnId = (int)($body['id'] ?? 0);
        $ok    = Transaction::delete($userId, $txnId);
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Deleted' : 'Not found']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
