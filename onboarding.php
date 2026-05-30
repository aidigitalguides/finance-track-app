<?php
require_once 'config.php';
require_once 'includes/Database.php';
require_once 'includes/Auth.php';
Auth::startSession();
$userId = Auth::requireLogin();
$user   = Database::fetchOne('SELECT name FROM users WHERE id = ?', [$userId]);
$name   = htmlspecialchars(explode(' ', $user['name'] ?? 'there')[0]);
// Check if user already has transactions — skip onboarding if so
$txnCount = Database::fetchOne('SELECT COUNT(*) as cnt FROM transactions WHERE user_id = ?', [$userId]);
if (($txnCount['cnt'] ?? 0) > 0) {
    header('Location: dashboard.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Welcome — Finance Track</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/theme.css">
<style>
body{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;background:var(--bg)}
body::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 800px 600px at 30% 30%,rgba(99,102,241,.07) 0%,transparent 70%),radial-gradient(ellipse 600px 800px at 70% 70%,rgba(55,138,221,.05) 0%,transparent 70%);pointer-events:none}
.wrap{width:100%;max-width:480px;position:relative;z-index:1}
.step{display:none}.step.active{display:block}
.step-header{text-align:center;margin-bottom:28px}
.step-icon{font-size:48px;margin-bottom:14px}
.step-title{font-family:var(--font-display);font-size:24px;font-weight:700;margin-bottom:8px}
.step-sub{font-size:14px;color:var(--muted);line-height:1.6}
.card{background:var(--surface);border:.5px solid var(--border2);border-radius:var(--r);padding:24px}
.progress-bar{display:flex;gap:6px;margin-bottom:28px;justify-content:center}
.prog-dot{width:8px;height:8px;border-radius:50%;background:var(--border2);transition:all .3s}
.prog-dot.done{background:var(--accent);width:24px;border-radius:4px}
.prog-dot.active{background:var(--accent);opacity:.6}
.balance-display{text-align:center;padding:20px 0;margin:16px 0}
.balance-amount{font-family:var(--font-display);font-size:42px;font-weight:700;color:var(--text);display:flex;align-items:center;justify-content:center;gap:4px}
.balance-rs{font-size:28px;color:var(--muted);font-weight:400}
.balance-input{font-family:var(--font-display);font-size:42px;font-weight:700;background:transparent;border:none;outline:none;color:var(--text);width:200px;text-align:center;-moz-appearance:textfield}
.balance-input::-webkit-inner-spin-button,.balance-input::-webkit-outer-spin-button{-webkit-appearance:none}
.balance-label{font-size:12px;color:var(--muted);margin-top:6px}
.account-types{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:16px 0}
.acc-type{padding:12px;background:var(--surface2);border:.5px solid var(--border2);border-radius:var(--r-sm);text-align:center;cursor:pointer;transition:all .2s}
.acc-type:hover,.acc-type.sel{border-color:var(--accent);background:var(--accent-glow)}
.acc-type .acc-icon{font-size:24px;margin-bottom:5px}
.acc-type .acc-name{font-size:13px;font-weight:500}
.acc-type .acc-desc{font-size:10px;color:var(--muted);margin-top:2px}
.income-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:16px 0}
.inc-opt{padding:10px;background:var(--surface2);border:.5px solid var(--border2);border-radius:var(--r-sm);text-align:center;cursor:pointer;transition:all .2s;font-size:13px}
.inc-opt.sel{border-color:var(--accent);background:var(--accent-glow);color:var(--accent)}
.btn-next{width:100%;padding:14px;background:var(--accent);color:#fff;border:none;border-radius:var(--r-sm);font-family:var(--font-display);font-size:15px;font-weight:600;cursor:pointer;transition:all .2s;margin-top:8px}
.btn-next:hover{background:var(--accent-dim);transform:translateY(-1px)}
.btn-skip{width:100%;padding:10px;background:transparent;color:var(--muted);border:none;font-size:13px;cursor:pointer;margin-top:6px}
.btn-skip:hover{color:var(--text)}
.accounts-list{display:flex;flex-direction:column;gap:8px;margin:16px 0}
.acc-row{display:flex;align-items:center;gap:10px;padding:12px;background:var(--surface2);border:.5px solid var(--border2);border-radius:var(--r-sm)}
.acc-row-icon{font-size:20px;flex-shrink:0}
.acc-row-info{flex:1}
.acc-row-name{font-size:13px;font-weight:500}
.acc-row-bal{font-family:var(--font-display);font-size:15px;font-weight:700;color:var(--success)}
.add-acc-btn{width:100%;padding:11px;background:var(--surface);border:.5px dashed var(--border2);border-radius:var(--r-sm);color:var(--muted);font-size:13px;cursor:pointer;transition:all .2s;font-family:var(--font-body)}
.add-acc-btn:hover{border-color:var(--accent);color:var(--accent)}
.acc-input-row{display:flex;gap:8px;margin-bottom:10px}
.acc-input-row .form-input{flex:1}
.acc-bal-input{width:130px!important;flex-shrink:0}
</style>
</head>
<body>
<script>
(function(){
  var t=localStorage.getItem('ft-theme')||'auto';
  if(t==='dark')document.documentElement.setAttribute('data-theme','dark');
  else if(t==='light')document.documentElement.setAttribute('data-theme','light');
  else document.documentElement.removeAttribute('data-theme');
})();
</script>


<div class="wrap">
  <div class="progress-bar" id="progress">
    <div class="prog-dot active"></div>
    <div class="prog-dot"></div>
    <div class="prog-dot"></div>
  </div>

  <!-- STEP 1: Welcome + Account Balance -->
  <div class="step active" id="step-1">
    <div class="card">
      <div class="step-header">
        <div class="step-icon">👋</div>
        <div class="step-title">Welcome, <?= $name ?>!</div>
        <div class="step-sub">Let's set up your account in 3 quick steps so your dashboard is accurate from day one.</div>
      </div>

      <div class="form-group">
        <label class="form-label">What is your current total account balance?</label>
        <div class="balance-display">
          <div class="balance-amount">
            <span class="balance-rs">₹</span>
            <input type="number" class="balance-input" id="balance-input" placeholder="0" min="0" inputmode="decimal" oninput="updateBalanceDisplay()">
          </div>
          <div class="balance-label">All savings + bank accounts combined</div>
        </div>
        <input type="date" class="form-input" id="balance-date" style="margin-top:8px">
        <div style="font-size:11px;color:var(--muted);margin-top:6px">This will be recorded as your starting balance. You can always update it later.</div>
      </div>

      <button class="btn-next" onclick="nextStep(1)">Continue →</button>
      <button class="btn-skip" onclick="skipStep(1)">Skip — I'll add this later</button>
    </div>
  </div>

  <!-- STEP 2: Monthly Income -->
  <div class="step" id="step-2">
    <div class="card">
      <div class="step-header">
        <div class="step-icon">💰</div>
        <div class="step-title">What's your income?</div>
        <div class="step-sub">This helps calculate your savings rate and financial health score accurately.</div>
      </div>

      <div class="form-group">
        <label class="form-label">Primary income type</label>
        <div class="income-grid">
          <div class="inc-opt sel" onclick="selIncome(this,'salary')">💼 Salary</div>
          <div class="inc-opt" onclick="selIncome(this,'business')">🏢 Business</div>
          <div class="inc-opt" onclick="selIncome(this,'freelance')">💻 Freelance</div>
          <div class="inc-opt" onclick="selIncome(this,'pocket_money')">👛 Pocket money</div>
        </div>
      </div>

      <div class="form-group amount-pfx">
        <label class="form-label">Monthly income amount (₹)</label>
        <input type="number" class="form-input" id="income-amount" placeholder="e.g. 50000" min="0" inputmode="decimal">
      </div>

      <div class="form-group">
        <label class="form-label">Which day of the month do you receive it?</label>
        <select class="form-select" id="income-day">
          <?php for($d=1;$d<=28;$d++) echo "<option value='$d'" . ($d==1?' selected':'') . ">$d" . ($d==1?"st":($d==2?"nd":($d==3?"rd":"th"))) . "</option>"; ?>
        </select>
      </div>

      <button class="btn-next" onclick="nextStep(2)">Continue →</button>
      <button class="btn-skip" onclick="skipStep(2)">Skip — I'll add income manually</button>
    </div>
  </div>

  <!-- STEP 3: Done -->
  <div class="step" id="step-3">
    <div class="card" style="text-align:center;padding:32px 24px">
      <div class="step-icon">🎉</div>
      <div class="step-title" style="margin-bottom:10px">You're all set!</div>
      <div style="font-size:14px;color:var(--muted);margin-bottom:24px;line-height:1.7">Your account is ready. Start by adding your daily expenses — even ₹50 here and ₹100 there adds up fast.<br><br>The more you track, the smarter your dashboard gets.</div>

      <div style="display:flex;flex-direction:column;gap:10px">
        <div style="background:var(--surface2);border-radius:var(--r-sm);padding:13px;display:flex;align-items:center;gap:12px;text-align:left">
          <span style="font-size:22px">📊</span>
          <div><div style="font-size:13px;font-weight:500">Dashboard</div><div style="font-size:11px;color:var(--muted)">See your financial overview</div></div>
          <a href="dashboard.php" style="margin-left:auto;font-size:12px;color:var(--accent)">Open →</a>
        </div>
        <div style="background:var(--surface2);border-radius:var(--r-sm);padding:13px;display:flex;align-items:center;gap:12px;text-align:left">
          <span style="font-size:22px">💸</span>
          <div><div style="font-size:13px;font-weight:500">Add transaction</div><div style="font-size:11px;color:var(--muted)">Record your first expense or income</div></div>
          <a href="transactions.php" style="margin-left:auto;font-size:12px;color:var(--accent)">Open →</a>
        </div>
        <div style="background:var(--surface2);border-radius:var(--r-sm);padding:13px;display:flex;align-items:center;gap:12px;text-align:left">
          <span style="font-size:22px">🎯</span>
          <div><div style="font-size:13px;font-weight:500">Set budgets</div><div style="font-size:11px;color:var(--muted)">Control your monthly spending</div></div>
          <a href="budget.php" style="margin-left:auto;font-size:12px;color:var(--accent)">Open →</a>
        </div>
      </div>

      <button class="btn-next" onclick="location.href='dashboard.php'" style="margin-top:20px">Go to Dashboard →</button>
    </div>
  </div>
</div>

<script>
let curStep = 1;
let incomeType = 'salary';

document.getElementById('balance-date').value = new Date().toISOString().split('T')[0];

function updateProgress() {
  const dots = document.querySelectorAll('.prog-dot');
  dots.forEach((d, i) => {
    d.className = 'prog-dot';
    if (i < curStep - 1) d.classList.add('done');
    else if (i === curStep - 1) d.classList.add('active');
  });
}

function selIncome(el, type) {
  document.querySelectorAll('.inc-opt').forEach(o => o.classList.remove('sel'));
  el.classList.add('sel');
  incomeType = type;
}

async function nextStep(step) {
  if (step === 1) {
    const balance = parseFloat(document.getElementById('balance-input').value) || 0;
    const date    = document.getElementById('balance-date').value;
    if (balance > 0) {
      await saveOpeningBalance(balance, date);
    }
  }
  if (step === 2) {
    const amount = parseFloat(document.getElementById('income-amount').value) || 0;
    const day    = document.getElementById('income-day').value;
    if (amount > 0) {
      await saveIncome(amount, day, incomeType);
    }
  }
  goToStep(step + 1);
}

function skipStep(step) {
  goToStep(step + 1);
}

function goToStep(step) {
  document.getElementById('step-' + curStep).classList.remove('active');
  curStep = step;
  if (curStep > 3) { location.href = 'dashboard.php'; return; }
  document.getElementById('step-' + curStep).classList.add('active');
  updateProgress();
  window.scrollTo(0, 0);
}

async function saveOpeningBalance(amount, date) {
  try {
    // Get the Opening Balance category ID
    const catRes = await fetch('api/categories.php?action=list');
    const catJson = await catRes.json();
    const obCat = catJson.data?.find(c => c.name === 'Opening Balance');
    if (!obCat) return;

    await fetch('api/transactions.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        action: 'add',
        amount: amount,
        category_id: obCat.id,
        type: 'income',
        txn_date: date,
        payment_method: 'other',
        note: 'Opening balance — account balance when I joined Finance Track'
      })
    });
  } catch(e) { console.error('Balance save error:', e); }
}

async function saveIncome(amount, day, type) {
  try {
    await fetch('api/income.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        action: 'add',
        name: type.charAt(0).toUpperCase() + type.slice(1).replace('_', ' '),
        type: type,
        amount: amount,
        frequency: 'monthly',
        day_of_month: parseInt(day)
      })
    });
    // Also add as a transaction for this month
    const today = new Date();
    const txnDate = new Date(today.getFullYear(), today.getMonth(), parseInt(day));
    if (txnDate > today) txnDate.setMonth(txnDate.getMonth() - 1);

    const catRes = await fetch('api/categories.php?action=list');
    const catJson = await catRes.json();
    const typeMap = {'salary':'Salary','business':'Business Income','freelance':'Freelance','pocket_money':'Pocket Money'};
    const catName = typeMap[type] || 'Salary';
    const cat = catJson.data?.find(c => c.name === catName);
    if (!cat) return;

    await fetch('api/transactions.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        action: 'add',
        amount: amount,
        category_id: cat.id,
        type: 'income',
        txn_date: txnDate.toISOString().split('T')[0],
        payment_method: 'other',
        note: catName + ' — added during setup'
      })
    });
  } catch(e) { console.error('Income save error:', e); }
}

updateProgress();

// ── THEME SYSTEM (single source of truth) ─────────────────
const _themes = ['auto','light','dark'];
let _themeIdx = _themes.indexOf(localStorage.getItem('ft-theme') || 'auto');
if (_themeIdx < 0) _themeIdx = 0;

function applyTheme() {
  const t = _themes[_themeIdx];
  if (t === 'dark')       document.documentElement.setAttribute('data-theme','dark');
  else if (t === 'light') document.documentElement.setAttribute('data-theme','light');
  else                    document.documentElement.removeAttribute('data-theme');
  localStorage.setItem('ft-theme', t);
  document.querySelectorAll('.theme-toggle .t-opt').forEach((opt, i) => {
    opt.style.background = (i === _themeIdx) ? 'var(--accent)' : '';
    opt.style.borderRadius = (i === _themeIdx) ? '50px' : '';
    opt.style.boxShadow = (i === _themeIdx) ? '0 1px 4px rgba(0,0,0,.2)' : '';
  });
}

function toggleTheme() {
  _themeIdx = (_themeIdx + 1) % _themes.length;
  applyTheme();
}

// Run immediately
applyTheme();

</script>
</body>
</html>
