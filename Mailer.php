<?php
// ============================================================
// includes/Mailer.php — Email sending via Hostinger SMTP
// Uses PHP's built-in mail() — works on all Hostinger plans
// For better deliverability, configure SMTP in hPanel first
// ============================================================

class Mailer {

    // Send email using PHP mail() — works on Hostinger out of the box
    public static function send(string $to, string $subject, string $htmlBody): bool {
        $fromName  = APP_NAME;
        $fromEmail = defined('MAIL_FROM') ? MAIL_FROM : 'noreply@' . parse_url(APP_URL, PHP_URL_HOST);

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
        $headers .= "Reply-To: {$fromEmail}\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        $result = mail($to, $subject, $htmlBody, $headers);

        // Log the attempt
        error_log("Email " . ($result ? "sent" : "FAILED") . " to {$to}: {$subject}");

        return $result;
    }

    // ── EMAIL TEMPLATES ────────────────────────────────────────

    public static function passwordResetEmail(string $name, string $resetUrl): string {
        return self::template("Reset your Finance Track password", "
            <h2 style='color:#111827;margin:0 0 8px'>Reset your password</h2>
            <p style='color:#6B7280;margin:0 0 20px'>Hi {$name}, we received a request to reset your Finance Track password.</p>
            <a href='{$resetUrl}' style='display:inline-block;background:#6366F1;color:#fff;text-decoration:none;padding:13px 28px;border-radius:8px;font-weight:600;font-size:15px;margin-bottom:20px'>Reset password →</a>
            <p style='color:#9CA3AF;font-size:13px;margin:0'>This link expires in 1 hour. If you didn't request this, ignore this email — your password won't change.</p>
        ");
    }

    public static function emiReminderEmail(string $name, string $loanName, string $amount, string $dueDate, int $daysLeft): string {
        $urgency = $daysLeft <= 2 ? "⚠️ URGENT: " : ($daysLeft <= 5 ? "🔔 Reminder: " : "📅 Upcoming: ");
        $color   = $daysLeft <= 2 ? "#EF4444" : ($daysLeft <= 5 ? "#F59E0B" : "#6366F1");
        return self::template("{$urgency}EMI due in {$daysLeft} days — {$loanName}", "
            <h2 style='color:#111827;margin:0 0 8px'>EMI Payment Due</h2>
            <p style='color:#6B7280;margin:0 0 16px'>Hi {$name}, your EMI payment is due soon.</p>
            <div style='background:#F4F6FA;border-radius:10px;padding:16px;margin-bottom:20px'>
                <div style='font-size:13px;color:#6B7280;margin-bottom:4px'>LOAN</div>
                <div style='font-size:18px;font-weight:700;color:#111827;margin-bottom:12px'>{$loanName}</div>
                <div style='display:flex;gap:20px'>
                    <div><div style='font-size:12px;color:#9CA3AF'>Amount</div><div style='font-size:20px;font-weight:700;color:{$color}'>₹{$amount}</div></div>
                    <div><div style='font-size:12px;color:#9CA3AF'>Due date</div><div style='font-size:16px;font-weight:600;color:#111827'>{$dueDate}</div></div>
                    <div><div style='font-size:12px;color:#9CA3AF'>Days left</div><div style='font-size:16px;font-weight:700;color:{$color}'>{$daysLeft} days</div></div>
                </div>
            </div>
            <a href='" . APP_URL . "' style='display:inline-block;background:#6366F1;color:#fff;text-decoration:none;padding:11px 24px;border-radius:8px;font-weight:600;font-size:14px'>Open Finance Track →</a>
        ");
    }

    public static function budgetAlertEmail(string $name, string $category, float $spent, float $budget, float $pct): string {
        $color = $pct >= 100 ? "#EF4444" : "#F59E0B";
        $msg   = $pct >= 100 ? "You've exceeded your budget!" : "You've used " . round($pct) . "% of your budget.";
        return self::template("Budget alert: {$category} — " . round($pct) . "% used", "
            <h2 style='color:#111827;margin:0 0 8px'>Budget Alert</h2>
            <p style='color:#6B7280;margin:0 0 16px'>Hi {$name}, {$msg}</p>
            <div style='background:#F4F6FA;border-radius:10px;padding:16px;margin-bottom:20px'>
                <div style='font-weight:700;font-size:16px;color:#111827;margin-bottom:12px'>{$category}</div>
                <div style='background:#E5E7EB;border-radius:4px;height:8px;overflow:hidden;margin-bottom:8px'>
                    <div style='background:{$color};height:100%;width:" . min(100, round($pct)) . "%'></div>
                </div>
                <div style='display:flex;justify-content:space-between;font-size:13px;color:#6B7280'>
                    <span>Spent: <strong style='color:{$color}'>₹" . number_format($spent) . "</strong></span>
                    <span>Budget: <strong>₹" . number_format($budget) . "</strong></span>
                </div>
            </div>
            <a href='" . APP_URL . "/budget.php' style='display:inline-block;background:#6366F1;color:#fff;text-decoration:none;padding:11px 24px;border-radius:8px;font-weight:600;font-size:14px'>Review budget →</a>
        ");
    }

    public static function welcomeEmail(string $name): string {
        return self::template("Welcome to Finance Track! 🎉", "
            <h2 style='color:#111827;margin:0 0 8px'>Welcome to Finance Track! 🎉</h2>
            <p style='color:#6B7280;margin:0 0 16px'>Hi {$name}, your account is ready. Start tracking your money in 3 easy steps:</p>
            <div style='margin-bottom:16px'>
                <div style='display:flex;align-items:flex-start;gap:12px;margin-bottom:12px'>
                    <span style='background:#6366F1;color:#fff;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;font-size:13px'>1</span>
                    <div><strong>Add your transactions</strong><br><span style='color:#6B7280;font-size:13px'>Record daily expenses — food, travel, bills</span></div>
                </div>
                <div style='display:flex;align-items:flex-start;gap:12px;margin-bottom:12px'>
                    <span style='background:#6366F1;color:#fff;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;font-size:13px'>2</span>
                    <div><strong>Set monthly budgets</strong><br><span style='color:#6B7280;font-size:13px'>Control spending by category</span></div>
                </div>
                <div style='display:flex;align-items:flex-start;gap:12px'>
                    <span style='background:#6366F1;color:#fff;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;font-size:13px'>3</span>
                    <div><strong>Track your EMIs</strong><br><span style='color:#6B7280;font-size:13px'>Never miss a payment deadline</span></div>
                </div>
            </div>
            <a href='" . APP_URL . "' style='display:inline-block;background:#6366F1;color:#fff;text-decoration:none;padding:13px 28px;border-radius:8px;font-weight:600;font-size:15px'>Go to dashboard →</a>
        ");
    }

    public static function cardUtilizationEmail(string $name, string $cardName, float $utilPct, float $balance, float $limit): string {
        $color = $utilPct >= 70 ? '#EF4444' : '#F59E0B';
        $tip   = $utilPct >= 70
            ? "High utilization (above 30%) can hurt your credit score. Try to pay down your balance soon."
            : "Keeping utilization below 30% helps maintain a good credit score.";
        return self::template("⚠️ Credit card utilization alert — {$cardName}", "
            <h2 style='color:#111827;margin:0 0 8px'>Credit Card Alert</h2>
            <p style='color:#6B7280;margin:0 0 16px'>Hi {$name}, your credit card utilization is getting high.</p>
            <div style='background:#F4F6FA;border-radius:10px;padding:16px;margin-bottom:16px'>
              <div style='font-weight:700;font-size:16px;margin-bottom:10px'>💳 {$cardName}</div>
              <div style='background:#E5E7EB;border-radius:4px;height:10px;overflow:hidden;margin-bottom:8px'>
                <div style='background:{$color};height:100%;width:" . min(100, round($utilPct)) . "%'></div>
              </div>
              <div style='display:flex;justify-content:space-between;font-size:13px'>
                <span style='color:{$color};font-weight:700'>" . round($utilPct) . "% used</span>
                <span style='color:#6B7280'>₹" . number_format($balance) . " / ₹" . number_format($limit) . "</span>
              </div>
            </div>
            <p style='color:#6B7280;font-size:13px;margin:0 0 16px'>{$tip}</p>
            <a href='" . APP_URL . "/emis.php' style='display:inline-block;background:#6366F1;color:#fff;text-decoration:none;padding:11px 24px;border-radius:8px;font-weight:600;font-size:14px'>View card details →</a>
        ");
    }

    public static function lowBalanceWarningEmail(string $name, string $loanName, float $emiAmount, float $balance): string {
        return self::template("⚠️ Low balance — EMI due soon for {$loanName}", "
            <h2 style='color:#111827;margin:0 0 8px'>Low Balance Warning</h2>
            <p style='color:#6B7280;margin:0 0 16px'>Hi {$name}, your recorded account balance may be insufficient for an upcoming EMI payment.</p>
            <div style='background:#FEF2F2;border:.5px solid #FCA5A5;border-radius:10px;padding:16px;margin-bottom:16px'>
              <div style='color:#EF4444;font-weight:700;margin-bottom:6px'>⚠️ {$loanName}</div>
              <div style='font-size:13px;color:#6B7280'>EMI amount: <strong style='color:#111'>₹" . number_format($emiAmount) . "</strong></div>
              <div style='font-size:13px;color:#6B7280'>Account balance: <strong style='color:#EF4444'>₹" . number_format($balance) . "</strong></div>
            </div>
            <p style='color:#6B7280;font-size:13px;margin:0 0 16px'>Make sure you have enough funds in your account before the due date to avoid a bounce charge.</p>
            <a href='" . APP_URL . "' style='display:inline-block;background:#6366F1;color:#fff;text-decoration:none;padding:11px 24px;border-radius:8px;font-weight:600;font-size:14px'>Open Finance Track →</a>
        ");
    }

    // Base HTML template
    private static function template(string $subject, string $body): string {
        return "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>{$subject}</title></head>
        <body style='margin:0;padding:0;background:#F4F6FA;font-family:\"DM Sans\",Arial,sans-serif'>
        <table width='100%' cellpadding='0' cellspacing='0' style='background:#F4F6FA;padding:40px 20px'>
        <tr><td align='center'>
        <table width='520' cellpadding='0' cellspacing='0' style='max-width:520px;width:100%'>
          <!-- Header -->
          <tr><td style='background:#1E293B;border-radius:12px 12px 0 0;padding:20px 28px;text-align:center'>
            <span style='font-family:\"Syne\",Arial,sans-serif;font-size:22px;font-weight:700;color:#fff'>
              <span style='background:#1D9E75;border-radius:7px;padding:3px 9px;margin-right:6px;font-size:16px'>₹</span>
              Finance<span style='color:#60A5FA'>Track</span>
            </span>
          </td></tr>
          <!-- Body -->
          <tr><td style='background:#fff;padding:28px;border-radius:0 0 12px 12px'>
            {$body}
          </td></tr>
          <!-- Footer -->
          <tr><td style='padding:20px 0;text-align:center;font-size:12px;color:#9CA3AF'>
            You're receiving this from Finance Track &nbsp;·&nbsp;
            <a href='" . APP_URL . "' style='color:#6366F1;text-decoration:none'>Open app</a>
          </td></tr>
        </table>
        </td></tr></table>
        </body></html>";
    }
}
