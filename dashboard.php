<?php
require_once 'config.php';
require_once 'includes/Database.php';
require_once 'includes/Auth.php';
Auth::startSession();
$userId   = Auth::requireLogin();
$user     = Database::fetchOne('SELECT name FROM users WHERE id = ?', [$userId]);
$userName = htmlspecialchars($user['name'] ?? 'User');
$initial  = strtoupper(mb_substr($userName, 0, 1));
$greeting = date('H') < 12 ? 'Good morning' : (date('H') < 17 ? 'Good afternoon' : 'Good evening');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Dashboard — Finance Track</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<link rel="stylesheet" href="assets/css/theme.css">
<style>
.month-btn{width:30px;height:30px;background:var(--surface2);border:.5px solid var(--border2);border-radius:var(--r-xs);color:var(--text);font-size:15px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s}
.month-btn:hover{background:var(--surface3)}
.month-label{font-family:var(--font-display);font-size:14px;font-weight:600;min-width:110px;text-align:center}
.stat-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px}
.stat-card{background:var(--surface);border:.5px solid var(--border);border-radius:var(--r);padding:14px;position:relative;overflow:hidden;box-shadow:var(--shadow);transition:transform .15s}
.stat-card:hover{transform:translateY(-2px)}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px}
.stat-card.income::before{background:#7C3AED}
.stat-card.expense::before{background:#F59E0B}
.stat-card.savings::before{background:#06B6D4}
.stat-card.health::before{background:#10B981}
.stat-label{font-size:10px;color:var(--muted);font-weight:500;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px}
.stat-value{font-family:var(--font-display);font-size:20px;font-weight:700}
.stat-value.inc{color:var(--success)}
.stat-value.exp{color:var(--danger)}
.stat-value.sav{color:var(--info)}
.stat-sub{font-size:10px;color:var(--muted);margin-top:5px}
.health-card{background:var(--surface);border:.5px solid var(--border);border-radius:var(--r);padding:16px;margin-bottom:14px;position:relative;overflow:hidden;box-shadow:var(--shadow)}
.health-card::after{content:'';position:absolute;top:-50px;right:-50px;width:160px;height:160px;border-radius:50%;background:var(--accent-glow);pointer-events:none}
.score-num{font-family:var(--font-display);font-size:38px;font-weight:700;color:var(--accent);line-height:1}
.health-bar-bg{height:6px;background:var(--surface2);border-radius:3px;overflow:hidden;margin:10px 0 8px}
.health-bar-fill{height:100%;border-radius:3px;background:linear-gradient(90deg,var(--accent),var(--accent-dim));transition:width .7s ease}
.health-insight{font-size:13px;color:var(--muted);line-height:1.55}
.chart-card{background:var(--surface);border:.5px solid var(--border);border-radius:var(--r);padding:16px;margin-bottom:14px;box-shadow:var(--shadow)}
.budget-item{background:var(--surface);border:.5px solid var(--border);border-radius:var(--r-sm);padding:11px 13px;margin-bottom:8px}
.budget-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:7px}
.budget-amt{font-size:11px;color:var(--muted)}
.due-item{background:var(--surface);border:.5px solid var(--border);border-radius:var(--r-sm);padding:11px 13px;display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
.due-name{font-size:13px;font-weight:500}
.due-when{font-size:11px;color:var(--muted);margin-top:1px}
.due-amt{font-family:var(--font-display);font-size:14px;font-weight:600;color:var(--danger);text-align:right}
.right-card{background:var(--surface);border:.5px solid var(--border);border-radius:var(--r);padding:16px;margin-bottom:16px;box-shadow:var(--shadow)}
.avatar-btn{width:36px;height:36px;border-radius:50%;background:var(--accent-glow);border:.5px solid var(--accent);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:600;color:var(--accent);cursor:pointer;font-family:var(--font-display)}
@media(min-width:900px){
  .stat-grid{grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}
  .stat-value{font-size:24px}
  .main-inner{display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start}
  .fab-mobile{display:none}
  .month-nav-mobile{display:none}
}
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

<div class="app">
  <aside class="sidebar">
    <div class="sidebar-logo"><div class="sidebar-logo-icon">₹</div><span class="logo-full">Finance<em>Track</em></span></div>
    <div class="sidebar-section-label">Menu</div>
    <nav class="sidebar-nav">
      <a class="sidebar-item active" href="dashboard.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg><span>Dashboard</span></a>
      <a class="sidebar-item" href="transactions.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg><span>Transactions</span></a>
      <a class="sidebar-item" href="budget.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg><span>Budgets</span></a>
      <a class="sidebar-item" href="emis.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg><span>EMI / Cards</span></a>
      <a class="sidebar-item" href="reports.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg><span>Reports</span></a>
      <a class="sidebar-item" href="profile.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg><span>Profile</span></a>
    </nav>
    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="sidebar-avatar"><?= $initial ?></div>
        <div><div class="sidebar-name"><?= $userName ?></div><div class="sidebar-role">Member</div></div>
      </div>
    </div>
  </aside>

  <div class="desktop-content">
    <div class="topbar">
      <div class="topbar-brand"><span class="tb-logo-icon">₹</span> Finance<em>Track</em></div>
      <div style="display:flex;gap:8px;align-items:center">
        <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme" aria-label="Toggle theme"><span class="t-opt">🌓</span><span class="t-opt">☀️</span><span class="t-opt">🌙</span></button>
        <div class="avatar-btn"><?= $initial ?></div>
      </div>
    </div>

    <div class="desktop-topbar">
      <div><div style="font-size:12px;color:var(--muted);margin-bottom:2px"><?= $greeting ?>, <?= $userName ?> 👋</div><div class="desktop-page-title">Dashboard</div></div>
      <div style="display:flex;align-items:center;gap:10px">
        <button class="month-btn" onclick="prevMonth()">&#8592;</button>
        <span class="month-label" id="month-label-d">Loading…</span>
        <button class="month-btn" onclick="nextMonth()">&#8594;</button>
        <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme" aria-label="Toggle theme"><span class="t-opt">🌓</span><span class="t-opt">☀️</span><span class="t-opt">🌙</span></button>
      </div>
    </div>

    <div class="main">
      <div class="main-inner">
        <div class="main-left">
          <div class="month-nav-mobile" style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
            <button class="month-btn" onclick="prevMonth()">&#8592;</button>
            <span class="month-label" id="month-label-m">Loading…</span>
            <button class="month-btn" onclick="nextMonth()">&#8594;</button>
          </div>

          <div class="stat-grid">
            <div class="stat-card income"><div class="stat-label">Income</div><div class="stat-value inc" id="s-inc">₹0</div><div class="stat-sub" id="s-inc-sub">this month</div></div>
            <div class="stat-card expense"><div class="stat-label">Spent</div><div class="stat-value exp" id="s-exp">₹0</div><div class="stat-sub" id="s-exp-sub">0% of income</div></div>
            <div class="stat-card savings"><div class="stat-label">Saved</div><div class="stat-value sav" id="s-sav">₹0</div><div class="stat-sub" id="s-sav-sub">0% rate</div></div>
            <div class="stat-card health"><div class="stat-label">Health Score</div><div class="stat-value" id="s-health" style="color:var(--accent);display:block">—</div><div class="stat-sub">out of 100</div></div>
          </div>

          <div class="health-card">
            <div style="display:flex;justify-content:space-between;align-items:flex-start">
              <div><div class="stat-label" style="margin-bottom:6px">Financial Health</div><div><span class="score-num" id="health-score">—</span><span style="font-size:13px;color:var(--muted)"> / 100</span></div></div>
              <div style="text-align:right"><div style="font-size:11px;color:var(--muted)"><?= $greeting ?></div><div style="font-size:14px;font-weight:600;margin-top:2px"><?= strtoupper($userName) ?></div></div>
            </div>
            <div class="health-bar-bg"><div class="health-bar-fill" id="health-bar" style="width:0%"></div></div>
            <div class="health-insight" id="health-insight">Calculating your score…</div>
          </div>

          <div class="chart-card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
              <span class="section-title">6-month overview</span>
              <div style="display:flex;gap:12px">
                <div style="display:flex;align-items:center;gap:5px"><span style="width:8px;height:8px;border-radius:2px;background:var(--success);display:inline-block"></span><span style="font-size:11px;color:var(--muted)">Income</span></div>
                <div style="display:flex;align-items:center;gap:5px"><span style="width:8px;height:8px;border-radius:2px;background:var(--danger);display:inline-block"></span><span style="font-size:11px;color:var(--muted)">Expense</span></div>
              </div>
            </div>
            <div style="position:relative;height:170px"><canvas id="bar-chart"></canvas></div>
          </div>

          <div class="section-hdr"><span class="section-title">Recent transactions</span><a href="transactions.php" class="see-all">See all →</a></div>
          <div id="txn-list"><div class="skeleton" style="height:52px;border-radius:10px;margin-bottom:6px"></div><div class="skeleton" style="height:52px;border-radius:10px;margin-bottom:6px;opacity:.8"></div><div class="skeleton" style="height:52px;border-radius:10px;opacity:.6"></div></div>

          <div class="fab-mobile" style="display:flex;justify-content:center;margin:16px 0 4px">
            <button class="fab" onclick="openModal()">+ Add transaction</button>
          </div>
        </div>

        <div class="main-right">
          <div class="right-card">
            <div class="section-hdr" style="margin-bottom:12px"><span class="section-title">Budget status</span><a href="budget.php" class="see-all">Manage →</a></div>
            <div id="budget-list"><div class="skeleton" style="height:50px;border-radius:8px;margin-bottom:8px"></div><div class="skeleton" style="height:50px;border-radius:8px;opacity:.7"></div></div>
          </div>

          <div class="right-card">
            <div class="section-hdr" style="margin-bottom:12px"><span class="section-title">Upcoming dues</span><a href="emis.php" class="see-all">See all →</a></div>
            <div id="due-list"><div class="skeleton" style="height:50px;border-radius:8px"></div></div>
          </div>

          <div class="right-card" style="text-align:center">
            <div style="font-size:13px;color:var(--muted);margin-bottom:12px">Quick add</div>
            <button style="width:100%;padding:12px;background:var(--accent);color:#fff;border:none;border-radius:var(--r-sm);font-family:var(--font-display);font-size:14px;font-weight:600;cursor:pointer;transition:background .2s" onclick="openModal()" onmouseover="this.style.background='var(--accent-dim)'" onmouseout="this.style.background='var(--accent)'">+ Add Transaction</button>
          </div>

          <div class="right-card">
            <div class="section-title" style="margin-bottom:12px">Category breakdown</div>
            <div style="position:relative;height:170px"><canvas id="donut-chart"></canvas></div>
            <div id="cat-legend" style="margin-top:10px;display:flex;flex-direction:column;gap:5px"></div>
          </div>
        </div>
      </div>
    </div>

    <nav class="bottom-nav">
      <a class="nav-item active" href="dashboard.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg><span>Dashboard</span></a>
      <a class="nav-item" href="transactions.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg><span>Transactions</span></a>
      <a class="nav-item" href="budget.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg><span>Budgets</span></a>
      <a class="nav-item" href="reports.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg><span>Reports</span></a>
      <a class="nav-item" href="profile.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg><span>Profile</span></a>
    </nav>
  </div>
</div>

<!-- MODAL -->
<div class="modal-bg" id="modal" onclick="if(event.target===this)closeModal()">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-title">Add transaction</div>
    <!-- Quick type selector -->
    <div class="type-toggle">
      <button class="type-btn active exp" onclick="setType('expense',this)">💸 Expense</button>
      <button class="type-btn inc" onclick="setType('income',this)">💰 Income</button>
    </div>

    <!-- Opening balance quick add -->
    <div id="ob-banner" style="background:var(--accent-glow);border:.5px solid var(--accent);border-radius:var(--r-sm);padding:10px 13px;margin-bottom:12px;display:flex;align-items:center;justify-content:space-between;font-size:13px">
      <div><div style="font-weight:500;color:var(--text)">🏦 Set account balance</div><div style="font-size:11px;color:var(--muted);margin-top:1px">Enter your current total bank balance</div></div>
      <button onclick="setOpeningBalance()" style="background:var(--accent);color:#fff;border:none;border-radius:5px;padding:5px 11px;font-size:12px;cursor:pointer;font-family:var(--font-body)">Set →</button>
    </div>

    <div class="form-group amount-pfx"><label class="form-label">Amount</label><input type="number" class="form-input" id="t-amount" placeholder="0" min="1" inputmode="decimal"></div>
    <div class="grid2">
      <div class="form-group"><label class="form-label">Category</label><select class="form-select" id="t-cat"></select></div>
      <div class="form-group"><label class="form-label">Date</label><input type="date" class="form-input" id="t-date"></div>
    </div>
    <div class="form-group"><label class="form-label">Payment</label><select class="form-select" id="t-method" onchange="toggleCardSelect(this.value)"><option value="upi">UPI</option><option value="cash">Cash</option><option value="card">Card</option><option value="netbanking">Netbanking</option><option value="other">Other</option></select></div>
    <div class="form-group" id="card-select-wrap" style="display:none">
      <label class="form-label">Which credit card? <span style="color:var(--muted);font-size:10px;text-transform:none">(links expense to card balance)</span></label>
      <select class="form-select" id="t-card-id"><option value="">None / Debit card</option></select>
    </div>
    <div class="form-group"><label class="form-label">Note (optional)</label><input type="text" class="form-input" id="t-note" placeholder="e.g. Office lunch, petrol fill" maxlength="255"></div>
    <button class="submit-btn" onclick="saveTxn()">Save transaction</button>
  </div>
</div>
<div class="toast hidden" id="toast"></div>

<script>

const MN=['January','February','March','April','May','June','July','August','September','October','November','December'];
let cm=new Date().getMonth()+1,cy=new Date().getFullYear(),txnType='expense',cats=[],barChart=null,donutChart=null;

document.addEventListener('DOMContentLoaded',async()=>{
  document.getElementById('t-date').value=new Date().toISOString().split('T')[0];
  updLabel();
  await loadCats();
  loadCardOptions();
  load();
});

function prevMonth(){cm--;if(cm<1){cm=12;cy--;}updLabel();load();}
function nextMonth(){const n=new Date();if(cy===n.getFullYear()&&cm>=n.getMonth()+1)return;cm++;if(cm>12){cm=1;cy++;}updLabel();load();}
function updLabel(){const l=MN[cm-1]+' '+cy;['month-label-d','month-label-m'].forEach(id=>{const e=document.getElementById(id);if(e)e.textContent=l;});}

async function load(){await Promise.all([loadSummary(),loadBudgets(),loadTxns(),loadDues()]);loadBarChart();loadHealth();loadDonut();}

async function loadSummary(){
  try{const r=await fetch(`api/transactions.php?action=summary&month=${cm}&year=${cy}`);const j=await r.json();if(!j.success)return;const d=j.data;
  document.getElementById('s-inc').textContent=fmt(d.income);
  document.getElementById('s-exp').textContent=fmt(d.expense);
  document.getElementById('s-sav').textContent=fmt(d.savings);
  const pct=d.income>0?Math.round((d.expense/d.income)*100):0;
  document.getElementById('s-exp-sub').textContent=pct+'% of income';
  document.getElementById('s-sav-sub').textContent=d.savings_rate+'% savings rate';
  }catch(e){}
}

async function loadHealth(){
  try{const r=await fetch(`api/health.php?month=${cm}&year=${cy}`);const j=await r.json();if(!j.success)throw 0;const d=j.data;
  document.getElementById('health-score').textContent=d.score;
  document.getElementById('s-health').textContent=d.score;
  document.getElementById('health-bar').style.width=d.score+'%';
  document.getElementById('health-insight').innerHTML=d.insight;
  }catch(e){document.getElementById('health-score').textContent='0';document.getElementById('s-health').textContent='0';document.getElementById('health-insight').textContent='Add your first transaction to calculate your score.';}
}

async function loadBudgets(){
  try{const r=await fetch(`api/budgets.php?action=list&month=${cm}&year=${cy}`);const j=await r.json();
  if(!j.success||!j.data.length)throw 0;
  document.getElementById('budget-list').innerHTML=j.data.slice(0,3).map(b=>{
    const pct=b.budget>0?Math.min(100,Math.round((b.spent/b.budget)*100)):0;
    const cls=pct>=100?'bar-over':pct>=80?'bar-warn':'bar-ok';
    const col=pct>=100?'var(--danger)':pct>=80?'var(--warning)':'var(--muted)';
    return `<div class="budget-item"><div class="budget-row"><span style="font-size:13px">${b.icon} ${esc(b.name)}</span><span class="budget-amt">${fmt(b.spent)}/${fmt(b.budget)}</span></div><div class="bar-bg"><div class="bar-fill ${cls}" style="width:${pct}%"></div></div><div style="font-size:10px;color:${col};text-align:right;margin-top:3px">${pct}% used</div></div>`;
  }).join('');
  }catch(e){document.getElementById('budget-list').innerHTML='<div style="color:var(--muted);font-size:13px;padding:4px 0">No budgets set. <a href="budget.php" style="color:var(--accent)">Set one →</a></div>';}
}

async function loadTxns(){
  try{const r=await fetch(`api/transactions.php?action=list&month=${cm}&year=${cy}&limit=5`);const j=await r.json();
  if(!j.success||!j.data.length)throw 0;
  document.getElementById('txn-list').innerHTML='<div style="display:flex;flex-direction:column;gap:6px">'+j.data.map(t=>{
    const isI=t.type==='income';const bg=(t.color_hex||'#888780')+'28';
    const d=new Date(t.txn_date+'T00:00:00').toLocaleDateString('en-IN',{day:'numeric',month:'short'});
    return `<div class="txn-item"><div class="txn-dot" style="background:${bg}">${t.category_icon}</div><div class="txn-info"><div class="txn-name">${esc(t.note||t.category_name)}</div><div class="txn-meta"><span>${d}</span><span class="method-tag">${t.payment_method.toUpperCase()}</span></div></div><div class="txn-amt ${isI?'inc':'out'}">${isI?'+':'-'}${fmt(t.amount)}</div></div>`;
  }).join('')+'</div>';
  }catch(e){document.getElementById('txn-list').innerHTML='<div style="color:var(--muted);font-size:13px;padding:8px 0">No transactions this month. Tap + to add one.</div>';}
}

async function loadDues(){
  try{const r=await fetch('api/dues.php?action=upcoming');const j=await r.json();
  if(!j.success||!j.data.length)throw 0;
  document.getElementById('due-list').innerHTML=j.data.slice(0,3).map(d=>{
    const bc=d.days_left<=2?'urgent':d.days_left<=5?'soon':'ok';
    return `<div class="due-item"><div><div class="due-name">${esc(d.name)}</div><div class="due-when">Due in ${d.days_left} day${d.days_left!==1?'s':''}</div></div><div><div class="due-amt">${fmt(d.amount)}</div><span class="badge ${bc}" style="margin-top:3px;display:block;text-align:center">${d.days_left<=2?'Urgent':d.days_left+' days'}</span></div></div>`;
  }).join('');
  }catch(e){document.getElementById('due-list').innerHTML='<div style="color:var(--muted);font-size:13px;padding:4px 0">No upcoming dues.</div>';}
}

async function loadBarChart(){
  try{const r=await fetch('api/transactions.php?action=overview');const j=await r.json();if(!j.success)return;
  const bk={};j.data.forEach(x=>{const k=x.year+'-'+String(x.month).padStart(2,'0');if(!bk[k])bk[k]={income:0,expense:0};bk[k][x.type]=parseFloat(x.total);});
  const keys=Object.keys(bk).sort().slice(-6);
  const labels=keys.map(k=>MN[+k.split('-')[1]-1].slice(0,3));
  if(barChart)barChart.destroy();
  barChart=new Chart(document.getElementById('bar-chart'),{type:'bar',data:{labels,datasets:[
    {label:'Income',data:keys.map(k=>bk[k].income||0),backgroundColor:'rgba(99,102,241,.25)',borderColor:'#6366F1',borderWidth:1.5,borderRadius:5},
    {label:'Expense',data:keys.map(k=>bk[k].expense||0),backgroundColor:'rgba(226,75,74,.3)',borderColor:'#E24B4A',borderWidth:1.5,borderRadius:5},
  ]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>' ₹'+c.raw.toLocaleString('en-IN')}}},scales:{x:{grid:{color:'rgba(128,128,128,.1)'},ticks:{color:'var(--muted)',font:{size:10}}},y:{grid:{color:'rgba(128,128,128,.1)'},ticks:{color:'var(--muted)',font:{size:10},callback:v=>v>=100000?(v/100000).toFixed(0)+'L':v>=1000?(v/1000).toFixed(0)+'K':v}}}}}); 
  }catch(e){}
}

const PC=['#7C3AED','#F59E0B','#06B6D4','#10B981','#E24B4A','#378ADD'];
async function loadDonut(){
  try{const r=await fetch(`api/transactions.php?action=breakdown&month=${cm}&year=${cy}`);const j=await r.json();
  if(!j.success||!j.data.length){document.getElementById('cat-legend').innerHTML='<div style="color:var(--muted);font-size:12px;text-align:center;padding:8px">No expenses this month</div>';return;}
  const data=j.data.slice(0,6);const total=data.reduce((s,d)=>s+parseFloat(d.total),0);
  if(donutChart)donutChart.destroy();
  donutChart=new Chart(document.getElementById('donut-chart'),{type:'doughnut',data:{labels:data.map(d=>d.name),datasets:[{data:data.map(d=>d.total),backgroundColor:PC,borderColor:'var(--surface)',borderWidth:3,hoverOffset:6}]},options:{responsive:true,maintainAspectRatio:false,cutout:'68%',plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>' ₹'+parseFloat(c.raw).toLocaleString('en-IN')}}}}});
  document.getElementById('cat-legend').innerHTML=data.map((d,i)=>{const p=total>0?(parseFloat(d.total)/total*100):0;return `<div style="display:flex;align-items:center;justify-content:space-between;font-size:12px"><div style="display:flex;align-items:center;gap:6px"><span style="width:8px;height:8px;border-radius:2px;background:${PC[i]};display:inline-block"></span><span style="color:var(--muted)">${esc(d.icon)} ${esc(d.name)}</span></div><span style="font-weight:600">${p.toFixed(0)}%</span></div>`;}).join('');
  }catch(e){}
}

async function loadCats(){try{const r=await fetch('api/categories.php?action=list');const j=await r.json();if(j.success)cats=j.data;renderCats('expense');}catch(e){}}

// Load user's credit cards into the card selector
async function loadCardOptions(){
  try{
    const r=await fetch('api/emis.php?action=card_list');
    const j=await r.json();
    const sel=document.getElementById('t-card-id');
    if(!sel)return;
    if(j.success&&j.data.length){
      sel.innerHTML='<option value="">None / Debit card</option>'+
        j.data.filter(c=>c.card_type==='credit').map(c=>
          `<option value="${c.id}">${esc(c.card_name)} (${c.bank_name})</option>`
        ).join('');
    }
  }catch(e){}
}

function toggleCardSelect(method){
  const wrap=document.getElementById('card-select-wrap');
  if(wrap) wrap.style.display=(method==='card')?'':'none';
}
function renderCats(type){document.getElementById('t-cat').innerHTML=cats.filter(c=>c.type===type||c.type==='both').map(c=>`<option value="${c.id}">${c.icon} ${c.name}</option>`).join('');}
function setType(type,btn){txnType=type;document.querySelectorAll('.type-btn').forEach(b=>b.classList.remove('active'));btn.classList.add('active');renderCats(type);}
function openModal(){document.getElementById('modal').classList.add('open');document.getElementById('t-amount').focus();checkObBanner();}

// Check if user already has transactions — hide opening balance banner if so
async function checkObBanner(){
  try{
    const r=await fetch(`api/transactions.php?action=list&month=${cm}&year=${cy}&limit=1`);
    const j=await r.json();
    const banner=document.getElementById('ob-banner');
    if(banner) banner.style.display=(j.success&&j.data.length>0)?'none':'';
  }catch(e){}
}

// Open balance quick-set: pre-fills income form with Opening Balance category
async function setOpeningBalance(){
  // Switch to income type
  document.querySelectorAll('.type-btn').forEach(b=>b.classList.remove('active'));
  document.querySelector('.type-btn.inc').classList.add('active');
  txnType='income';
  renderCats('income');
  // Pre-select Opening Balance category
  const sel=document.getElementById('t-cat');
  for(let i=0;i<sel.options.length;i++){
    if(sel.options[i].text.includes('Opening Balance')){sel.selectedIndex=i;break;}
  }
  document.getElementById('ob-banner').style.display='none';
  document.getElementById('t-amount').focus();
  document.getElementById('t-amount').placeholder='Your current account balance';
  document.getElementById('t-note').value='Opening balance — my account balance today';
}
function closeModal(){document.getElementById('modal').classList.remove('open');}
async function saveTxn(){
  const amount=parseFloat(document.getElementById('t-amount').value);
  if(!amount||amount<=0){toast('Enter a valid amount');return;}
  try{const r=await fetch('api/transactions.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'add',amount,category_id:document.getElementById('t-cat').value,type:txnType,txn_date:document.getElementById('t-date').value,payment_method:document.getElementById('t-method').value,note:document.getElementById('t-note').value,card_id:(document.getElementById('t-card-id')&&document.getElementById('t-method').value==='card')?document.getElementById('t-card-id').value:''})});
  const j=await r.json();
  if(j.success){closeModal();document.getElementById('t-amount').value='';document.getElementById('t-note').value='';toast('Saved! ✓');load();}
  else toast(j.message||'Error');
  }catch(e){toast('Network error');}
}
function fmt(v){return '₹'+Math.abs(parseFloat(v)||0).toLocaleString('en-IN',{maximumFractionDigits:0});}
function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function toast(m){const t=document.getElementById('toast');t.textContent=m;t.classList.remove('hidden');clearTimeout(t._t);t._t=setTimeout(()=>t.classList.add('hidden'),2500);}



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
