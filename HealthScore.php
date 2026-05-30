<?php
// ============================================================
// includes/HealthScore.php — financial health score engine
// ============================================================

class HealthScore {

    // Calculate score 0–100 for a given month
    // Factors: savings rate (40pts), budget adherence (30pts),
    //          debt ratio (20pts), streak (10pts)
    public static function calculate(int $userId, int $month, int $year): array {
        $db = Database::get();

        // 1. Income & expense
        $summary = Transaction::getMonthlySummary($userId, $month, $year);
        $income  = $summary['income'];
        $expense = $summary['expense'];
        $savings = $summary['savings'];
        $savingsRate = $summary['savings_rate'];

        // 2. Savings rate score (0–40 pts)
        $savingsScore = 0;
        if ($savingsRate >= 30)     $savingsScore = 40;
        elseif ($savingsRate >= 20) $savingsScore = 30;
        elseif ($savingsRate >= 10) $savingsScore = 20;
        elseif ($savingsRate > 0)   $savingsScore = 10;

        // 3. Budget adherence (0–30 pts)
        $budgets = Database::fetchAll(
            'SELECT b.amount AS budget_amt,
                    COALESCE(SUM(t.amount),0) AS spent
             FROM budgets b
             LEFT JOIN transactions t ON t.category_id = b.category_id
               AND t.user_id = b.user_id
               AND MONTH(t.txn_date) = b.month AND YEAR(t.txn_date) = b.year
               AND t.type = "expense"
             WHERE b.user_id = ? AND b.month = ? AND b.year = ? AND b.is_active = 1
             GROUP BY b.id',
            [$userId, $month, $year]
        );

        $budgetScore = 30; // Start full, deduct for overruns
        foreach ($budgets as $b) {
            if ($b['budget_amt'] > 0 && $b['spent'] > $b['budget_amt']) {
                $overPct = (($b['spent'] - $b['budget_amt']) / $b['budget_amt']) * 100;
                $budgetScore -= min(10, (int)($overPct / 10)); // -1 pt per 10% overrun, max -10
            }
        }
        $budgetScore = max(0, $budgetScore);

        // 4. Debt ratio (0–20 pts)
        $totalEmi = Database::fetchOne(
            'SELECT COALESCE(SUM(emi_amount),0) AS total_emi
             FROM emis WHERE user_id = ? AND is_active = 1',
            [$userId]
        );
        $emiTotal    = (float) ($totalEmi['total_emi'] ?? 0);
        $debtRatio   = $income > 0 ? ($emiTotal / $income) * 100 : 0;
        $debtScore   = 0;
        if ($debtRatio <= 20)      $debtScore = 20;
        elseif ($debtRatio <= 35)  $debtScore = 12;
        elseif ($debtRatio <= 50)  $debtScore = 6;

        // 5. Streak bonus (0–10 pts) — consecutive months of positive savings
        $streak = self::getSavingsStreak($userId, $month, $year);
        $streakScore = min(10, $streak * 2);

        $totalScore = $savingsScore + $budgetScore + $debtScore + $streakScore;

        $result = [
            'score'         => $totalScore,
            'savings_score' => $savingsScore,
            'budget_score'  => $budgetScore,
            'debt_score'    => $debtScore,
            'streak_score'  => $streakScore,
            'savings_rate'  => $savingsRate,
            'income'        => $income,
            'expense'       => $expense,
            'savings'       => $savings,
            'insight'       => self::getInsight($totalScore, $savingsRate, $debtRatio, $budgets),
        ];

        // Persist to log
        Database::execute(
            'INSERT INTO financial_health_log
             (user_id, month, year, score, total_income, total_expense, total_savings, savings_rate)
             VALUES (?,?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE
             score=VALUES(score), total_income=VALUES(total_income),
             total_expense=VALUES(total_expense), total_savings=VALUES(total_savings),
             savings_rate=VALUES(savings_rate), calculated_at=NOW()',
            [$userId, $month, $year, $totalScore, $income, $expense, $savings, $savingsRate]
        );

        return $result;
    }

    private static function getSavingsStreak(int $userId, int $month, int $year): int {
        $streak = 0;
        $m = $month; $y = $year;
        for ($i = 0; $i < 12; $i++) {
            $m--; if ($m < 1) { $m = 12; $y--; }
            $s = Transaction::getMonthlySummary($userId, $m, $y);
            if ($s['savings'] > 0) $streak++;
            else break;
        }
        return $streak;
    }

    private static function getInsight(float $score, float $savingsRate, float $debtRatio, array $budgets): string {
        if ($score >= 80) return "Excellent! You are managing money very well. Keep it up!";
        if ($savingsRate < 5) return "Your savings are very low this month. Try to cut back on dining out or shopping.";
        if ($debtRatio > 50) return "Your EMI payments are over 50% of income. Consider prepaying a loan if possible.";
        if ($savingsRate < 15) return "You saved " . number_format($savingsRate, 1) . "% this month. Aim for at least 20% next month.";

        // Check worst budget overrun
        foreach ($budgets as $b) {
            if ($b['budget_amt'] > 0 && $b['spent'] > $b['budget_amt']) {
                return "You went over budget in some categories. Review your spending and adjust limits.";
            }
        }
        return "Good job this month! You saved " . number_format($savingsRate, 1) . "% of your income.";
    }
}
