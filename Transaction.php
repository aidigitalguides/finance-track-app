<?php
// ============================================================
// includes/Transaction.php — transaction model
// ============================================================

class Transaction {

    // Add a new transaction
    public static function add(int $userId, array $data): array {
        $amount = (float) ($data['amount'] ?? 0);
        if ($amount <= 0) return ['success' => false, 'message' => 'Amount must be greater than zero'];

        $type = $data['type'] ?? 'expense';
        if (!in_array($type, ['expense', 'income', 'transfer'])) {
            return ['success' => false, 'message' => 'Invalid transaction type'];
        }

        $categoryId = (int) ($data['category_id'] ?? 0);
        $txnDate    = $data['txn_date'] ?? date('Y-m-d');
        $note       = trim(substr(strip_tags($data['note'] ?? ''), 0, 255));
        $method     = $data['payment_method'] ?? 'upi';
        $cardId     = !empty($data['card_id']) ? (int) $data['card_id'] : null;
        $emiId      = !empty($data['emi_id']) ? (int) $data['emi_id'] : null;

        // Validate category belongs to user (or is system)
        $cat = Database::fetchOne(
            'SELECT id FROM categories WHERE id = ? AND (user_id = ? OR user_id IS NULL) AND is_active = 1',
            [$categoryId, $userId]
        );
        if (!$cat) return ['success' => false, 'message' => 'Invalid category'];

        $id = Database::insert(
            'INSERT INTO transactions 
             (user_id, category_id, card_id, emi_id, amount, type, payment_method, note, txn_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$userId, $categoryId, $cardId, $emiId, $amount, $type, $method, $note, $txnDate]
        );

        // Update card balance if linked
        if ($cardId && $type === 'expense') {
            Database::execute(
                'UPDATE cards SET current_balance = current_balance + ? WHERE id = ? AND user_id = ?',
                [$amount, $cardId, $userId]
            );
        }

        // Update EMI paid months if it's an EMI payment
        if ($emiId) {
            Database::execute(
                'UPDATE emis SET paid_months = paid_months + 1 WHERE id = ? AND user_id = ?',
                [$emiId, $userId]
            );
        }

        return ['success' => true, 'id' => $id];
    }

    // Get transactions for a user with filters
    public static function getList(int $userId, array $filters = []): array {
        $where  = ['t.user_id = ?'];
        $params = [$userId];

        if (!empty($filters['month']) && !empty($filters['year'])) {
            $where[]  = 'YEAR(t.txn_date) = ? AND MONTH(t.txn_date) = ?';
            $params[] = (int) $filters['year'];
            $params[] = (int) $filters['month'];
        }
        if (!empty($filters['type'])) {
            $where[]  = 't.type = ?';
            $params[] = $filters['type'];
        }
        if (!empty($filters['category_id'])) {
            $where[]  = 't.category_id = ?';
            $params[] = (int) $filters['category_id'];
        }
        if (!empty($filters['search'])) {
            $where[]  = 't.note LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
        }

        $limit  = min((int) ($filters['limit'] ?? 50), 200);
        $offset = (int) ($filters['offset'] ?? 0);
        $sql    = 'SELECT t.*, c.name AS category_name, c.icon AS category_icon, c.color_hex
                   FROM transactions t
                   JOIN categories c ON c.id = t.category_id
                   WHERE ' . implode(' AND ', $where) . '
                   ORDER BY t.txn_date DESC, t.id DESC
                   LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        return Database::fetchAll($sql, $params);
    }

    // Monthly summary: income, expense, savings
    public static function getMonthlySummary(int $userId, int $month, int $year): array {
        $rows = Database::fetchAll(
            'SELECT type, SUM(amount) AS total
             FROM transactions
             WHERE user_id = ? AND MONTH(txn_date) = ? AND YEAR(txn_date) = ?
             GROUP BY type',
            [$userId, $month, $year]
        );

        $summary = ['income' => 0, 'expense' => 0, 'savings' => 0];
        foreach ($rows as $row) {
            if ($row['type'] === 'income')  $summary['income']  = (float) $row['total'];
            if ($row['type'] === 'expense') $summary['expense'] = (float) $row['total'];
        }
        $summary['savings']      = $summary['income'] - $summary['expense'];
        $summary['savings_rate'] = $summary['income'] > 0
            ? round(($summary['savings'] / $summary['income']) * 100, 1)
            : 0;

        return $summary;
    }

    // Spending by category for a month
    public static function getCategoryBreakdown(int $userId, int $month, int $year): array {
        return Database::fetchAll(
            'SELECT c.name, c.icon, c.color_hex, SUM(t.amount) AS total
             FROM transactions t
             JOIN categories c ON c.id = t.category_id
             WHERE t.user_id = ? AND t.type = "expense"
               AND MONTH(t.txn_date) = ? AND YEAR(t.txn_date) = ?
             GROUP BY t.category_id
             ORDER BY total DESC',
            [$userId, $month, $year]
        );
    }

    // Last 6 months overview for chart
    public static function getSixMonthOverview(int $userId): array {
        return Database::fetchAll(
            'SELECT MONTH(txn_date) AS month, YEAR(txn_date) AS year,
                    type, SUM(amount) AS total
             FROM transactions
             WHERE user_id = ?
               AND txn_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
             GROUP BY YEAR(txn_date), MONTH(txn_date), type
             ORDER BY year ASC, month ASC',
            [$userId]
        );
    }

    // Delete a transaction (only owner)
    public static function delete(int $userId, int $txnId): bool {
        $rows = Database::execute(
            'DELETE FROM transactions WHERE id = ? AND user_id = ?',
            [$txnId, $userId]
        );
        return $rows > 0;
    }
}
