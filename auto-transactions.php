<?php
// ============================================================
// cron/auto-transactions.php — Auto-create EMI & card transactions
// ============================================================
// Run daily at midnight: 0 0 * * *
// Command: php /home/u429732427/domains/financetrack.aidigitalguides.com/public_html/cron/auto-transactions.php
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
$todayStr = $today->format('Y-m-d');
$todayDay = (int)$today->format('j');
$todayMonth = (int)$today->format('n');
$todayYear  = (int)$today->format('Y');

// ── AUTO EMI TRANSACTIONS ──────────────────────────────────
// On EMI due day each month, auto-create an expense transaction
$emis = Database::fetchAll(
    'SELECT e.*, u.name AS user_name, u.email AS user_email,
            GREATEST(0, e.tenure_months - GREATEST(0,
              PERIOD_DIFF(DATE_FORMAT(CURDATE(),"%Y%m"), DATE_FORMAT(e.start_date,"%Y%m"))
            )) AS remaining_months,
            c.id AS emi_cat_id
     FROM emis e
     JOIN users u ON u.id = e.user_id
     LEFT JOIN categories c ON c.name = "Loan & EMI" AND (c.user_id = e.user_id OR c.user_id IS NULL)
     WHERE e.is_active = 1 AND e.due_day = ? AND u.is_active = 1',
    [$todayDay]
);

foreach ($emis as $emi) {
    // Skip if EMI is fully paid
    if ((int)$emi['remaining_months'] <= 0) continue;

    // Skip if EMI hasn't started yet
    if (new DateTime($emi['start_date']) > $today) continue;

    // Check if we already auto-created this transaction today
    $exists = Database::fetchOne(
        'SELECT id FROM transactions
         WHERE user_id = ? AND emi_id = ? AND txn_date = ? AND note LIKE "%Auto-EMI%"',
        [$emi['user_id'], $emi['id'], $todayStr]
    );
    if ($exists) continue;

    // Find or use default category
    $catId = $emi['emi_cat_id'];
    if (!$catId) {
        $cat = Database::fetchOne(
            'SELECT id FROM categories WHERE (user_id = ? OR user_id IS NULL) AND name IN ("Loan & EMI","Investments") AND type = "expense" LIMIT 1',
            [$emi['user_id']]
        );
        $catId = $cat ? $cat['id'] : 14; // fallback to Investments category
    }

    // Create the transaction
    Database::insert(
        'INSERT INTO transactions (user_id, category_id, emi_id, amount, type, payment_method, note, txn_date, is_recurring)
         VALUES (?,?,?,?,?,?,?,?,1)',
        [
            $emi['user_id'],
            $catId,
            $emi['id'],
            $emi['emi_amount'],
            'expense',
            'netbanking',
            "Auto-EMI: {$emi['loan_name']} — Month auto-recorded",
            $todayStr
        ]
    );

    $log[] = "✅ EMI auto-txn: {$emi['user_name']} — {$emi['loan_name']} ₹{$emi['emi_amount']}";
}

// ── CREDIT CARD BILL AUTO-TRANSACTION ─────────────────────
// On card due date, auto-create a transaction for the full outstanding balance
$cards = Database::fetchAll(
    'SELECT c.*, u.name AS user_name, u.email AS user_email,
            us.notif_card
     FROM cards c
     JOIN users u ON u.id = c.user_id
     LEFT JOIN user_settings us ON us.user_id = c.user_id
     WHERE c.is_active = 1 AND c.card_type = "credit"
       AND c.due_date = ? AND u.is_active = 1
       AND c.current_balance > 0',
    [$todayDay]
);

foreach ($cards as $card) {
    // Check if already processed today
    $exists = Database::fetchOne(
        'SELECT id FROM transactions
         WHERE user_id = ? AND card_id = ? AND txn_date = ? AND note LIKE "%Auto-card payment%"',
        [$card['user_id'], $card['id'], $todayStr]
    );
    if ($exists) continue;

    $balance = (float)$card['current_balance'];

    // Find a payment category
    $cat = Database::fetchOne(
        'SELECT id FROM categories WHERE (user_id = ? OR user_id IS NULL) AND name IN ("Credit Card Payment","Shopping") AND is_active = 1 LIMIT 1',
        [$card['user_id']]
    );
    $catId = $cat ? $cat['id'] : 5;

    // Create expense transaction for card payment
    Database::insert(
        'INSERT INTO transactions (user_id, category_id, card_id, amount, type, payment_method, note, txn_date, is_recurring)
         VALUES (?,?,?,?,?,?,?,?,0)',
        [
            $card['user_id'],
            $catId,
            $card['id'],
            $balance,
            'expense',
            'netbanking',
            "Auto-card payment: {$card['card_name']} — Bill cleared",
            $todayStr
        ]
    );

    // Reset card balance to 0 after payment
    Database::execute(
        'UPDATE cards SET current_balance = 0 WHERE id = ?',
        [$card['id']]
    );

    $log[] = "✅ Card auto-txn: {$card['user_name']} — {$card['card_name']} ₹" . number_format($balance);
}

// ── CARD UTILIZATION WARNING ───────────────────────────────
// Send alert if card is over 30% utilization (credit score impact)
$highUtil = Database::fetchAll(
    'SELECT c.*, u.email AS user_email, u.name AS user_name,
            ROUND((c.current_balance / NULLIF(c.credit_limit,0)) * 100, 1) AS util_pct
     FROM cards c
     JOIN users u ON u.id = c.user_id
     WHERE c.is_active = 1 AND c.card_type = "credit"
       AND c.credit_limit > 0 AND u.is_active = 1
       AND (c.current_balance / c.credit_limit) >= 0.30',
    []
);

foreach ($highUtil as $card) {
    $alreadySent = Database::fetchOne(
        'SELECT id FROM email_notifications WHERE user_id = ? AND type = "card_reminder" AND ref_id = ? AND DATE(sent_at) = CURDATE()',
        [$card['user_id'], $card['id']]
    );
    if ($alreadySent) continue;

    $sent = Mailer::send(
        $card['user_email'],
        "⚠️ Credit utilization high — {$card['card_name']} at {$card['util_pct']}%",
        Mailer::cardUtilizationEmail($card['user_name'], $card['card_name'], $card['util_pct'], $card['current_balance'], $card['credit_limit'])
    );

    Database::insert(
        'INSERT INTO email_notifications (user_id, type, ref_id, subject, status) VALUES (?,?,?,?,?)',
        [$card['user_id'], 'card_reminder', $card['id'], "Card utilization: {$card['card_name']}", $sent?'sent':'failed']
    );

    $log[] = ($sent?'✅':'❌') . " Util alert: {$card['user_email']} ({$card['card_name']}, {$card['util_pct']}%)";
}

$summary = date('Y-m-d H:i:s') . " — Auto-transactions: " . count($log) . " processed";
error_log("Finance Track auto-cron: " . $summary);
echo $summary . "\n" . implode("\n", $log) . "\n";
