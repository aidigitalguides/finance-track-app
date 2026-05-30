<?php
// ============================================================
// cron/send-reminders.php — Daily reminder job
// ============================================================
// Set up in Hostinger hPanel → Cron Jobs:
// Command: php /home/u429732427/domains/financetrack.aidigitalguides.com/public_html/cron/send-reminders.php
// Schedule: Once daily at 9:00 AM   →   0 9 * * *
// ============================================================

if (php_sapi_name() !== 'cli') {
    $secret = $_GET['key'] ?? '';
    if (!defined('CRON_SECRET') || !hash_equals(CRON_SECRET, $secret)) {
        http_response_code(403); die('Forbidden');
    }
}

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/Database.php';
require_once dirname(__DIR__) . '/includes/Mailer.php';

$log   = [];
$today = new DateTime('today');

// ── EMI REMINDERS ─────────────────────────────────────────
$emis = Database::fetchAll(
    'SELECT e.*, u.name AS user_name, u.email AS user_email,
            COALESCE(us.notif_emi, 1) AS notif_emi,
            GREATEST(0, e.tenure_months - GREATEST(0,
              PERIOD_DIFF(DATE_FORMAT(CURDATE(),"%Y%m"), DATE_FORMAT(e.start_date,"%Y%m"))
            )) AS remaining_months
     FROM emis e
     JOIN users u ON u.id = e.user_id
     LEFT JOIN user_settings us ON us.user_id = e.user_id
     WHERE e.is_active = 1 AND u.is_active = 1 AND COALESCE(us.notif_emi, 1) = 1', []
);

foreach ($emis as $emi) {
    if ((int)$emi['remaining_months'] <= 0) continue;

    $dueDay = (int)$emi['due_day'];
    $year   = (int)$today->format('Y');
    $month  = (int)$today->format('n');
    $d      = min($dueDay, (int)date('t', mktime(0,0,0,$month,1,$year)));
    $due    = new DateTime("$year-$month-$d");
    if ($due < $today) {
        $month++; if ($month > 12) { $month = 1; $year++; }
        $d   = min($dueDay, (int)date('t', mktime(0,0,0,$month,1,$year)));
        $due = new DateTime("$year-$month-$d");
    }

    $daysLeft = (int)$today->diff($due)->days;
    if (!in_array($daysLeft, [7, 3, 1])) continue;

    $alreadySent = Database::fetchOne(
        'SELECT id FROM email_notifications WHERE user_id = ? AND type = "emi_reminder" AND ref_id = ? AND DATE(sent_at) = CURDATE()',
        [$emi['user_id'], $emi['id']]
    );
    if ($alreadySent) continue;

    $sent = Mailer::send(
        $emi['user_email'],
        ($daysLeft === 1 ? '⚠️ URGENT: ' : '📅 ') . "EMI due in {$daysLeft} day" . ($daysLeft > 1 ? 's' : '') . " — {$emi['loan_name']}",
        Mailer::emiReminderEmail($emi['user_name'], $emi['loan_name'], number_format((float)$emi['emi_amount']), $due->format('d M Y'), $daysLeft)
    );

    Database::insert(
        'INSERT INTO email_notifications (user_id, type, ref_id, subject, status) VALUES (?,?,?,?,?)',
        [$emi['user_id'], 'emi_reminder', $emi['id'], "EMI reminder: {$emi['loan_name']}", $sent?'sent':'failed']
    );
    $log[] = ($sent?'✅':'❌') . " EMI → {$emi['user_email']} ({$emi['loan_name']}, {$daysLeft}d)";
}

// ── BUDGET ALERTS ──────────────────────────────────────────
$budgets = Database::fetchAll(
    'SELECT b.*, c.name AS cat_name, u.name AS user_name, u.email AS user_email,
            COALESCE(us.notif_budget, 1) AS notif_budget,
            COALESCE(SUM(t.amount), 0) AS spent
     FROM budgets b
     JOIN categories c ON c.id = b.category_id
     JOIN users u ON u.id = b.user_id
     LEFT JOIN user_settings us ON us.user_id = b.user_id
     LEFT JOIN transactions t ON t.category_id = b.category_id AND t.user_id = b.user_id
       AND MONTH(t.txn_date) = b.month AND YEAR(t.txn_date) = b.year AND t.type = "expense"
     WHERE b.is_active = 1 AND b.month = ? AND b.year = ? AND u.is_active = 1
       AND COALESCE(us.notif_budget, 1) = 1
     GROUP BY b.id',
    [(int)date('n'), (int)date('Y')]
);

foreach ($budgets as $b) {
    if ($b['budget'] <= 0) continue;
    $pct = ($b['spent'] / $b['budget']) * 100;
    if ($pct < ($b['alert_at_pct'] ?? 80)) continue;

    $alreadySent = Database::fetchOne(
        'SELECT id FROM email_notifications WHERE user_id = ? AND type = "budget_alert" AND ref_id = ? AND DATE(sent_at) = CURDATE()',
        [$b['user_id'], $b['id']]
    );
    if ($alreadySent) continue;

    $sent = Mailer::send(
        $b['user_email'],
        "Budget alert: {$b['cat_name']} — " . round($pct) . "% used",
        Mailer::budgetAlertEmail($b['user_name'], $b['cat_name'], $b['spent'], $b['budget'], $pct)
    );

    Database::insert(
        'INSERT INTO email_notifications (user_id, type, ref_id, subject, status) VALUES (?,?,?,?,?)',
        [$b['user_id'], 'budget_alert', $b['id'], "Budget alert: {$b['cat_name']}", $sent?'sent':'failed']
    );
    $log[] = ($sent?'✅':'❌') . " Budget → {$b['user_email']} ({$b['cat_name']}, " . round($pct) . "%)";
}

$summary = date('Y-m-d H:i:s') . " — " . count(array_filter($log, fn($l) => str_starts_with($l, '✅'))) . "/" . count($log) . " sent";
error_log("Finance Track cron: " . $summary);
echo $summary . "\n" . implode("\n", $log) . "\n";
