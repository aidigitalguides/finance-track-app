<?php
// api/reports.php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/Transaction.php';

Auth::startSession();
$userId = Auth::requireLogin();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'monthly':
        $month = (int)($_GET['month'] ?? date('n'));
        $year  = (int)($_GET['year']  ?? date('Y'));
        $summary   = Transaction::getMonthlySummary($userId, $month, $year);
        $breakdown = Transaction::getCategoryBreakdown($userId, $month, $year);
        echo json_encode(['success'=>true,'data'=>['summary'=>$summary,'breakdown'=>$breakdown]]);
        break;

    case 'yearly':
        $year  = (int)($_GET['year'] ?? date('Y'));
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $s = Transaction::getMonthlySummary($userId, $m, $year);
            $months[] = ['month' => $m, 'year' => $year] + $s;
        }
        $totals = [
            'income'  => array_sum(array_column($months, 'income')),
            'expense' => array_sum(array_column($months, 'expense')),
            'savings' => array_sum(array_column($months, 'savings')),
        ];
        echo json_encode(['success'=>true,'data'=>['months'=>$months,'totals'=>$totals]]);
        break;

    case 'export_csv':
        // Override content type for CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="financetrack_' . date('Y-m') . '.csv"');
        $month = (int)($_GET['month'] ?? date('n'));
        $year  = (int)($_GET['year']  ?? date('Y'));
        $rows  = Transaction::getList($userId, ['month'=>$month,'year'=>$year,'limit'=>500]);
        $out   = fopen('php://output', 'w');
        fputcsv($out, ['Date','Type','Category','Amount','Payment Method','Note']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['txn_date'], $r['type'], $r['category_name'], $r['amount'], $r['payment_method'], $r['note']]);
        }
        fclose($out);
        exit;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
