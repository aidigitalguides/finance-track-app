<?php
require_once 'config.php';
require_once 'includes/Database.php';
require_once 'includes/Auth.php';
Auth::startSession();
if (!empty($_SESSION['logged_in'])) { header('Location: dashboard.php'); exit; }
$token = trim($_GET['token'] ?? '');
$valid = false; $expired = false; $tokenEmail = '';
if ($token) {
    $row = Database::fetchOne('SELECT * FROM password_resets WHERE token = ? AND used = 0', [$token]);
    if ($row) {
        if (new DateTime() < new DateTime($row['expires_at'])) { $valid = true; $tokenEmail = $row['email']; }
        else $expired = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password — Finance Track</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/theme.css">
<style>
body{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
body::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 700px 500px at 30% 30%,rgba(99,102,241,.07) 0%,transparent 70%);pointer-events:none}
.wrap{width:100%;max-width:400px;position:relative;z-index:1}
.brand{text-align:center;margin-bottom:24px}
.logo-icon{width:42px;height:42px;background:#1D9E75;border-radius:11px;display:inline-flex;align-items:center;justify-content:center;font-size:20px;color:#fff;font-family:var(--font-display);font-weight:700;margin-right:10px;vertical-align:middle}
.brand-name{font-family:var(--font-display);font-size:24px;font-weight:700;color:var(--text);vertical-align:middle}
.brand-name em{font-style:normal;background:var(--grad-primary);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.card{background:var(--surface);border:.5px solid var(--border2);border-radius:var(--r);padding:28px}
.card-icon{text-align:center;font-size:40px;margin-bottom:12px}
.card-title{font-family:var(--font-display);font-size:20px;font-weight:700;text-align:center;margin-bottom:6px}
.card-sub{font-size:13px;color:var(--muted);text-align:center;margin-bottom:22px;line-height:1.6}
.alert{padding:10px 14px;border-radius:var(--r-sm);font-size:13px;margin-bottom:14px}
.alert.error{background:var(--danger-bg);border:.5px solid var(--danger);color:var(--danger)}
.alert.success{background:var(--success-bg);border:.5px solid var(--success);color:var(--success)}
.back-link{display:block;text-align:center;margin-top:14px;font-size:13px;color:var(--muted);text-decoration:none}
.back-link:hover{color:var(--accent)}
.pw-strength{height:4px;border-radius:2px;margin-top:6px;background:var(--surface2);overflow:hidden}
.pw-strength-fill{height:100%;border-radius:2px;transition:width .3s,background .3s}
</style>
</head>
<body>
<script>(function(){var t=localStorage.getItem('ft-theme')||'auto';if(t==='dark')document.documentElement.setAttribute('data-theme','dark');else if(t==='light')document.documentElement.setAttribute('data-theme','light');})();</script>

<div class="wrap">
  <div class="brand" style="text-align:center;margin-bottom:24px">
    <div class="logo-icon">₹</div>
    <span class="brand-name">Finance<em>Track</em></span>
  </div>

  <?php if (!$token || (!$valid && !$expired)): ?>
  <div class="card" style="text-align:center">
    <div class="card-icon">❌</div>
    <div class="card-title">Invalid link</div>
    <div class="card-sub">This password reset link is invalid. Please request a new one.</div>
    <a href="forgot-password.php" style="display:inline-block;margin-top:16px;padding:11px 24px;background:var(--accent);color:#fff;text-decoration:none;border-radius:var(--r-sm);font-family:var(--font-display);font-weight:600;font-size:14px">Request new link</a>
  </div>

  <?php elseif ($expired): ?>
  <div class="card" style="text-align:center">
    <div class="card-icon">⏰</div>
    <div class="card-title">Link expired</div>
    <div class="card-sub">This reset link has expired (links are valid for 1 hour). Please request a new one.</div>
    <a href="forgot-password.php" style="display:inline-block;margin-top:16px;padding:11px 24px;background:var(--accent);color:#fff;text-decoration:none;border-radius:var(--r-sm);font-family:var(--font-display);font-weight:600;font-size:14px">Request new link</a>
  </div>

  <?php else: ?>
  <div class="card" id="reset-form">
    <div class="card-icon">🔑</div>
    <div class="card-title">Set new password</div>
    <div class="card-sub">Create a strong password for your Finance Track account.</div>
    <div id="alert-box" style="display:none" class="alert"></div>
    <input type="hidden" id="token" value="<?= htmlspecialchars($token) ?>">
    <div class="form-group">
      <label class="form-label">New password</label>
      <input type="password" class="form-input" id="pw1" placeholder="At least 8 characters" oninput="checkStrength()">
      <div class="pw-strength"><div class="pw-strength-fill" id="pw-bar" style="width:0%"></div></div>
    </div>
    <div class="form-group">
      <label class="form-label">Confirm password</label>
      <input type="password" class="form-input" id="pw2" placeholder="Repeat your password">
    </div>
    <button class="submit-btn" onclick="doReset()">Set new password</button>
    <a href="login.php" class="back-link">← Back to sign in</a>
  </div>
  <div class="card" id="success-card" style="display:none;text-align:center">
    <div style="font-size:48px;margin-bottom:14px">✅</div>
    <div class="card-title">Password updated!</div>
    <div class="card-sub">Your password has been changed. You can now sign in with your new password.</div>
    <a href="login.php" style="display:inline-block;margin-top:16px;padding:11px 24px;background:var(--accent);color:#fff;text-decoration:none;border-radius:var(--r-sm);font-family:var(--font-display);font-weight:600;font-size:14px">Sign in →</a>
  </div>
  <?php endif; ?>
</div>

<script>
function checkStrength() {
  const pw = document.getElementById('pw1').value;
  const bar = document.getElementById('pw-bar');
  let score = 0;
  if (pw.length >= 8) score++;
  if (/[A-Z]/.test(pw)) score++;
  if (/[0-9]/.test(pw)) score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;
  const w = [0, 25, 50, 75, 100][score];
  const c = ['', '#EF4444', '#F59E0B', '#10B981', '#6366F1'][score];
  bar.style.width = w + '%';
  bar.style.background = c;
}

async function doReset() {
  const pw1 = document.getElementById('pw1').value;
  const pw2 = document.getElementById('pw2').value;
  const tok = document.getElementById('token').value;
  if (pw1.length < 8) { showAlert('Password must be at least 8 characters'); return; }
  if (pw1 !== pw2)    { showAlert('Passwords do not match'); return; }
  const btn = document.querySelector('.submit-btn');
  btn.disabled = true; btn.textContent = 'Updating…';
  try {
    const r = await fetch('api/password-reset.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({action: 'reset', token: tok, password: pw1})
    });
    const j = await r.json();
    if (j.success) {
      document.getElementById('reset-form').style.display = 'none';
      document.getElementById('success-card').style.display = '';
    } else {
      showAlert(j.message || 'Error. Please try again.');
      btn.disabled = false; btn.textContent = 'Set new password';
    }
  } catch(e) { showAlert('Network error.'); btn.disabled = false; btn.textContent = 'Set new password'; }
}
function showAlert(msg) { const b=document.getElementById('alert-box');b.textContent=msg;b.className='alert error';b.style.display='block'; }
</script>
</body>
</html>
