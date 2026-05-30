<?php
require_once 'config.php';
require_once 'includes/Database.php';
require_once 'includes/Auth.php';
require_once 'includes/Mailer.php';
Auth::startSession();
// If already logged in, redirect
if (!empty($_SESSION['logged_in'])) { header('Location: dashboard.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password — Finance Track</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/theme.css">
<style>
body{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
body::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 700px 500px at 30% 30%,rgba(99,102,241,.07) 0%,transparent 70%);pointer-events:none}
.wrap{width:100%;max-width:400px;position:relative;z-index:1}
.brand{text-align:center;margin-bottom:24px}
.brand-row{display:inline-flex;align-items:center;gap:10px;margin-bottom:8px}
.logo-icon{width:42px;height:42px;background:#1D9E75;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff;font-family:var(--font-display);font-weight:700}
.brand-name{font-family:var(--font-display);font-size:24px;font-weight:700;color:var(--text)}
.brand-name em{font-style:normal;background:var(--grad-primary);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.card{background:var(--surface);border:.5px solid var(--border2);border-radius:var(--r);padding:28px}
.card-icon{text-align:center;font-size:40px;margin-bottom:12px}
.card-title{font-family:var(--font-display);font-size:20px;font-weight:700;text-align:center;margin-bottom:6px}
.card-sub{font-size:13px;color:var(--muted);text-align:center;margin-bottom:22px;line-height:1.6}
.alert{padding:10px 14px;border-radius:var(--r-sm);font-size:13px;margin-bottom:14px;display:none}
.alert.error{background:var(--danger-bg);border:.5px solid var(--danger);color:var(--danger)}
.alert.success{background:var(--success-bg);border:.5px solid var(--success);color:var(--success)}
.back-link{display:block;text-align:center;margin-top:14px;font-size:13px;color:var(--muted);text-decoration:none}
.back-link:hover{color:var(--accent)}
</style>
</head>
<body>
<script>(function(){var t=localStorage.getItem('ft-theme')||'auto';if(t==='dark')document.documentElement.setAttribute('data-theme','dark');else if(t==='light')document.documentElement.setAttribute('data-theme','light');})();</script>

<div class="wrap">
  <div class="brand">
    <div class="brand-row">
      <div class="logo-icon">₹</div>
      <span class="brand-name">Finance<em>Track</em></span>
    </div>
  </div>

  <div class="card" id="request-form">
    <div class="card-icon">🔐</div>
    <div class="card-title">Forgot your password?</div>
    <div class="card-sub">Enter your email address and we'll send you a link to reset your password.</div>
    <div id="alert-box" class="alert"></div>
    <div class="form-group">
      <label class="form-label">Email address</label>
      <input type="email" class="form-input" id="email" placeholder="you@example.com" autocomplete="email">
    </div>
    <button class="submit-btn" onclick="sendReset()">Send reset link</button>
    <a href="login.php" class="back-link">← Back to sign in</a>
  </div>

  <div class="card" id="success-card" style="display:none;text-align:center">
    <div style="font-size:48px;margin-bottom:14px">📧</div>
    <div class="card-title">Check your email</div>
    <div class="card-sub" id="success-msg"></div>
    <a href="login.php" style="display:inline-block;margin-top:16px;padding:11px 24px;background:var(--accent);color:#fff;text-decoration:none;border-radius:var(--r-sm);font-family:var(--font-display);font-weight:600;font-size:14px">Back to sign in</a>
  </div>
</div>

<script>
async function sendReset() {
  const email = document.getElementById('email').value.trim();
  if (!email) { showAlert('Enter your email address'); return; }
  const btn = document.querySelector('.submit-btn');
  btn.disabled = true; btn.textContent = 'Sending…';
  try {
    const r = await fetch('api/password-reset.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({action: 'request', email})
    });
    const j = await r.json();
    document.getElementById('request-form').style.display = 'none';
    document.getElementById('success-card').style.display = '';
    document.getElementById('success-msg').textContent = j.message || 'If this email is registered, you\'ll receive a reset link shortly.';
  } catch(e) {
    showAlert('Network error. Please try again.');
    btn.disabled = false; btn.textContent = 'Send reset link';
  }
}
function showAlert(msg) { const b=document.getElementById('alert-box');b.textContent=msg;b.className='alert error';b.style.display='block'; }
document.getElementById('email').addEventListener('keydown', e => { if(e.key==='Enter') sendReset(); });
</script>
</body>
</html>
