<?php
// api/dues.php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

Auth::startSession();
$userId = Auth::requireLogin();

$action = $_GET['action'] ?? 'upcoming';

if ($action === 'upcoming') {
    $today    = new DateTime('today');
    $in30days = (new DateTime('+30 days'))->format('Y-m-d');
    $dues     = [];

    // EMI dues — only show if EMI is still active (remaining months > 0)
    $emis = Database::fetchAll(
        'SELECT loan_name AS name, emi_amount AS amount, due_day, start_date, tenure_months, "emi" AS type,
                GREATEST(0, tenure_months - GREATEST(0, PERIOD_DIFF(DATE_FORMAT(CURDATE(),"%Y%m"), DATE_FORMAT(start_date,"%Y%m")))) AS remaining_months
         FROM emis WHERE user_id = ? AND is_active = 1',
        [$userId]
    );
    foreach ($emis as $e) {
        // Skip if all EMIs are paid
        if ((int)$e['remaining_months'] <= 0) continue;
        $dueDate  = getDueDate($e['due_day']);
        $daysLeft = (int) $today->diff(new DateTime($dueDate))->days;
        if ($daysLeft <= 30) {
            $dues[] = ['name' => $e['name'], 'amount' => $e['amount'], 'days_left' => $daysLeft, 'type' => 'emi'];
        }
    }

    // Credit card dues
    $cards = Database::fetchAll(
        'SELECT card_name AS name, current_balance AS amount, due_date AS due_day, "card" AS type
         FROM cards WHERE user_id = ? AND is_active = 1 AND current_balance > 0',
        [$userId]
    );
    foreach ($cards as $c) {
        $dueDate  = getDueDate($c['due_day']);
        $daysLeft = (int) $today->diff(new DateTime($dueDate))->days;
        if ($daysLeft <= 30) {
            $dues[] = ['name' => $c['name'] . ' bill', 'amount' => $c['amount'], 'days_left' => $daysLeft, 'type' => 'card'];
        }
    }

    usort($dues, fn($a, $b) => $a['days_left'] - $b['days_left']);
    echo json_encode(['success' => true, 'data' => $dues]);
    exit;
}

function getDueDate(int $day): string {
    $today = new DateTime('today');
    $year  = (int) $today->format('Y');
    $month = (int) $today->format('n');
    $d     = min($day, (int) date('t', mktime(0,0,0,$month,1,$year)));
    $due   = new DateTime("$year-$month-$d");
    if ($due < $today) {
        $month++; if ($month > 12) { $month = 1; $year++; }
        $d   = min($day, (int) date('t', mktime(0,0,0,$month,1,$year)));
        $due = new DateTime("$year-$month-$d");
    }
    return $due->format('Y-m-d');
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
