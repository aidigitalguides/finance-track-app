<?php
// api/emis.php
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
    // ── EMI ──────────────────────────────────────────────
    case 'emi_list':
        $rows = Database::fetchAll(
            'SELECT e.*,
                    -- Calculate paid months from start_date to today (not from stored value)
                    GREATEST(0,
                      LEAST(e.tenure_months,
                        PERIOD_DIFF(DATE_FORMAT(CURDATE(),"%Y%m"), DATE_FORMAT(e.start_date,"%Y%m"))
                      )
                    ) AS paid_months_calc,
                    -- Remaining = tenure minus months elapsed
                    GREATEST(0,
                      e.tenure_months - 
                      GREATEST(0, PERIOD_DIFF(DATE_FORMAT(CURDATE(),"%Y%m"), DATE_FORMAT(e.start_date,"%Y%m")))
                    ) AS remaining_months,
                    -- Outstanding amount
                    (e.emi_amount * GREATEST(0,
                      e.tenure_months - 
                      GREATEST(0, PERIOD_DIFF(DATE_FORMAT(CURDATE(),"%Y%m"), DATE_FORMAT(e.start_date,"%Y%m")))
                    )) AS remaining_amount,
                    -- Progress percent
                    ROUND(
                      GREATEST(0, LEAST(100,
                        (PERIOD_DIFF(DATE_FORMAT(CURDATE(),"%Y%m"), DATE_FORMAT(e.start_date,"%Y%m")) / e.tenure_months) * 100
                      )), 1
                    ) AS progress_pct
             FROM emis e
             WHERE e.user_id = ? AND e.is_active = 1
             ORDER BY e.due_day ASC',
            [$userId]
        );
        // Map paid_months_calc → paid_months for the frontend
        foreach ($rows as &$row) {
            $row['paid_months'] = $row['paid_months_calc'];
        }
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    case 'emi_add':
        $loanName  = trim(strip_tags($body['loan_name'] ?? ''));
        $lender    = trim(strip_tags($body['lender'] ?? ''));
        $total     = (float)($body['total_amount'] ?? 0);
        $emi       = (float)($body['emi_amount'] ?? 0);
        $rate      = (float)($body['interest_rate'] ?? 0);
        $tenure    = (int)($body['tenure_months'] ?? 0);
        $dueDay    = (int)($body['due_day'] ?? 5);
        $startDate = $body['start_date'] ?? date('Y-m-d');

        if (!$loanName || $emi <= 0 || $tenure <= 0) {
            echo json_encode(['success'=>false,'message'=>'Fill all required fields']); break;
        }
        $id = Database::insert(
            'INSERT INTO emis (user_id, loan_name, lender, total_amount, emi_amount, interest_rate, tenure_months, due_day, start_date)
             VALUES (?,?,?,?,?,?,?,?,?)',
            [$userId, $loanName, $lender, $total, $emi, $rate ?: null, $tenure, $dueDay, $startDate]
        );
        echo json_encode(['success' => true, 'id' => $id]);
        break;

    case 'emi_delete':
        $id = (int)($body['id'] ?? 0);
        $ok = Database::execute('UPDATE emis SET is_active = 0 WHERE id = ? AND user_id = ?', [$id, $userId]);
        echo json_encode(['success' => $ok > 0]);
        break;

    // ── CARDS ─────────────────────────────────────────────
    case 'card_list':
        $rows = Database::fetchAll(
            'SELECT *, 
                    ROUND((current_balance / NULLIF(credit_limit,0)) * 100, 1) AS utilization_pct
             FROM cards WHERE user_id = ? AND is_active = 1 ORDER BY created_at DESC',
            [$userId]
        );
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    case 'card_add':
        $name     = trim(strip_tags($body['card_name'] ?? ''));
        $bank     = trim(strip_tags($body['bank_name'] ?? ''));
        $type     = in_array($body['card_type']??'', ['credit','debit']) ? $body['card_type'] : 'credit';
        $limit    = (float)($body['credit_limit'] ?? 0);
        $billing  = (int)($body['billing_date'] ?? 1);
        $due      = (int)($body['due_date'] ?? 15);
        $last4    = preg_replace('/\D/', '', $body['last4'] ?? '');
        $last4    = strlen($last4) === 4 ? $last4 : null;
        $color    = preg_match('/^#[0-9a-fA-F]{6}$/', $body['color_hex'] ?? '') ? $body['color_hex'] : '#185FA5';

        if (!$name || !$bank) { echo json_encode(['success'=>false,'message'=>'Card name and bank required']); break; }
        $id = Database::insert(
            'INSERT INTO cards (user_id, card_name, bank_name, card_type, last4, credit_limit, billing_date, due_date, color_hex)
             VALUES (?,?,?,?,?,?,?,?,?)',
            [$userId, $name, $bank, $type, $last4, $limit ?: null, $billing, $due, $color]
        );
        echo json_encode(['success' => true, 'id' => $id]);
        break;

    case 'card_txns':
        // Get transactions for this card in the current billing cycle
        $cardId    = (int)($_GET['card_id'] ?? 0);
        $card      = Database::fetchOne('SELECT * FROM cards WHERE id = ? AND user_id = ?', [$cardId, $userId]);
        if (!$card) { echo json_encode(['success'=>false,'message'=>'Card not found']); break; }

        // Calculate billing cycle: from last billing date to next billing date
        $billingDay = (int)$card['billing_date'];
        $today      = new DateTime('today');
        $todayDay   = (int)$today->format('j');
        $month      = (int)$today->format('n');
        $year       = (int)$today->format('Y');

        // If today is before billing day, cycle started last month
        if ($todayDay < $billingDay) {
            $month--;
            if ($month < 1) { $month = 12; $year--; }
        }
        $cycleStart = sprintf('%04d-%02d-%02d', $year, $month, min($billingDay, cal_days_in_month(CAL_GREGORIAN, $month, $year)));

        $rows = Database::fetchAll(
            'SELECT t.*, c.name AS category_name, c.icon AS category_icon
             FROM transactions t
             JOIN categories c ON c.id = t.category_id
             WHERE t.user_id = ? AND t.card_id = ? AND t.type = "expense"
               AND t.txn_date >= ?
             ORDER BY t.txn_date DESC
             LIMIT 50',
            [$userId, $cardId, $cycleStart]
        );
        echo json_encode(['success' => true, 'data' => $rows, 'cycle_start' => $cycleStart]);
        break;

    case 'card_delete':

        $id = (int)($body['id'] ?? 0);
        $ok = Database::execute('UPDATE cards SET is_active = 0 WHERE id = ? AND user_id = ?', [$id, $userId]);
        echo json_encode(['success' => $ok > 0]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
