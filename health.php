<?php
// api/health.php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/Transaction.php';
require_once '../includes/HealthScore.php';

Auth::startSession();
$userId = Auth::requireLogin();

$month = (int)($_GET['month'] ?? date('n'));
$year  = (int)($_GET['year']  ?? date('Y'));

$result = HealthScore::calculate($userId, $month, $year);
echo json_encode(['success' => true, 'data' => $result]);
