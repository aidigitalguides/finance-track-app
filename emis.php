<?php
require_once 'config.php';
require_once 'includes/Database.php';
require_once 'includes/Auth.php';
Auth::startSession();
$userId  = Auth::requireLogin();
$user    = Database::fetchOne('SELECT name FROM users WHERE id = ?', [$userId]);
$initial = strtoupper(mb_substr($user['name']??'U', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>EMI & Cards — Finance Track</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/theme.css">
<style>
.seg{display:flex;background:var(--surface2);border-radius:var(--r-sm);padding:4px;gap:4px;margin-bottom:16px}
.seg-btn{flex:1;padding:9px;border:none;border-radius:6px;font-family:var(--font-body);font-size:13px;font-weight:500;cursor:pointer;background:transparent;color:var(--muted);transition:all .2s}
.seg-btn.active{background:var(--surface);color:var(--text);border:.5px solid var(--border2);box-shadow:var(--shadow)}
.sum-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px}
.sum-card{background:var(--surface);border:.5px solid var(--border);border-radius:var(--r-sm);padding:13px;box-shadow:var(--shadow)}
.sum-lbl{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px}
.sum-val{font-family:var(--font-display);font-size:20px;font-weight:700}
.emi-card{background:var(--surface);border:.5px solid var(--border);border-radius:var(--r);padding:15px;margin-bottom:10px;box-shadow:var(--shadow)}
.emi-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px}
.emi-name{font-size:14px;font-weight:600;font-family:var(--font-display)}
.emi-lender{font-size:11px;color:var(--muted);margin-top:2px}
.emi-due{font-size:11px;padding:3px 8px;border-radius:8px;background:var(--warning-bg);color:var(--warning)}
.emi-stats{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:10px}
.emi-stat-lbl{font-size:10px;color:var(--muted);margin-bottom:3px}
.emi-stat-val{font-size:13px;font-weight:500}
.bar-bg{height:5px;background:var(--surface2);border-radius:3px;overflow:hidden;margin-bottom:6px}
.bar-fill{height:100%;border-radius:3px;background:var(--accent);transition:width .4s}
.emi-footer{display:flex;justify-content:space-between;align-items:center}
.emi-prog{font-size:11px;color:var(--muted)}
.del-btn{font-size:11px;background:var(--danger-bg);color:var(--danger);border:none;border-radius:5px;padding:4px 10px;cursor:pointer;font-family:var(--font-body)}
.cc-card{border-radius:var(--r);padding:16px 18px;margin-bottom:10px;position:relative;overflow:hidden;min-height:120px;color:#fff}
.cc-bank{font-family:var(--font-display);font-size:12px;font-weight:600;opacity:.8;margin-bottom:3px}
.cc-name{font-family:var(--font-display);font-size:16px;font-weight:700;margin-bottom:14px}
.cc-last4{font-size:12px;letter-spacing:.1em;opacity:.7;margin-bottom:12px}
.cc-row{display:flex;justify-content:space-between;align-items:flex-end}
.cc-bal-lbl{font-size:10px;opacity:.6;text-transform:uppercase;letter-spacing:.05em}
.cc-bal{font-family:var(--font-display);font-size:20px;font-weight:700}
.cc-due-txt{font-size:11px;opacity:.7;margin-top:2px}
.cc-util{font-size:11px;opacity:.65}
.cc-overlay{position:absolute;inset:0;background:linear-gradient(135deg,rgba(255,255,255,.1) 0%,transparent 60%);pointer-events:none}
.cc-del-btn{position:absolute;top:10px;right:10px;background:rgba(0,0,0,.3);border:none;border-radius:5px;color:#fff;font-size:11px;padding:3px 8px;cursor:pointer;opacity:.7}
.cc-del-btn:hover{opacity:1}
.util-bar{height:3px;background:rgba(255,255,255,.25);border-radius:2px;overflow:hidden;margin-top:5px;width:90px}
.util-fill{height:100%;border-radius:2px;background:#fff}
.add-btn{width:100%;padding:12px;background:var(--surface);border:.5px dashed var(--border2);border-radius:var(--r-sm);color:var(--muted);font-size:14px;cursor:pointer;transition:all .2s;font-family:var(--font-body);margin-top:4px}
.add-btn:hover{background:var(--surface2);color:var(--text);border-color:var(--accent)}
.empty-state{text-align:center;padding:40px 20px;color:var(--muted);font-size:13px}
.empty-icon{font-size:36px;margin-bottom:10px}
@media(min-width:900px){
  .emi-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
  .card-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
}
.color-opts{display:flex;gap:8px;flex-wrap:wrap}
.c-opt{width:28px;height:28px;border-radius:50%;cursor:pointer;border:2px solid transparent;transition:transform .15s}
.c-opt.sel{border-color:var(--text);transform:scale(1.15)}
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
      <a class="sidebar-item" href="dashboard.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg><span>Dashboard</span></a>
      <a class="sidebar-item" href="transactions.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg><span>Transactions</span></a>
      <a class="sidebar-item" href="budget.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg><span>Budgets</span></a>
      <a class="sidebar-item active" href="emis.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg><span>EMI / Cards</span></a>
      <a class="sidebar-item" href="reports.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg><span>Reports</span></a>
      <a class="sidebar-item" href="profile.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg><span>Profile</span></a>
    </nav>
    <div class="sidebar-footer"><div class="sidebar-user"><div class="sidebar-avatar"><?= $initial ?></div><div><div class="sidebar-name"><?= htmlspecialchars($user['name']??'') ?></div><div class="sidebar-role">Member</div></div></div></div>
  </aside>

  <div class="desktop-content">
    <div class="topbar">
      <div class="topbar-brand"><span class="tb-logo-icon">₹</span> Finance<em>Track</em></div>
      <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme" aria-label="Toggle theme"><span class="t-opt">🌓</span><span class="t-opt">☀️</span><span class="t-opt">🌙</span></button>
    </div>
    <div class="desktop-topbar">
      <div><div style="font-size:12px;color:var(--muted);margin-bottom:2px">Loans & credit cards</div><div class="desktop-page-title">EMI & Cards</div></div>
      <div style="display:flex;align-items:center;gap:10px">
        <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme" aria-label="Toggle theme"><span class="t-opt">🌓</span><span class="t-opt">☀️</span><span class="t-opt">🌙</span></button>
        <button style="padding:8px 16px;background:var(--accent);color:#fff;border:none;border-radius:var(--r-sm);font-family:var(--font-display);font-size:13px;font-weight:600;cursor:pointer" id="desktop-add-btn">+ Add</button>
      </div>
    </div>

    <div class="main">
      <div class="seg">
        <button class="seg-btn active" onclick="switchTab('emi',this)">EMI / Loans</button>
        <button class="seg-btn" onclick="switchTab('cards',this)">Credit Cards</button>
      </div>

      <!-- EMI TAB -->
      <div id="tab-emi">
        <div class="sum-grid">
          <div class="sum-card"><div class="sum-lbl">Monthly EMI</div><div class="sum-val" id="emi-monthly" style="color:var(--danger)">₹0</div></div>
          <div class="sum-card"><div class="sum-lbl">Total outstanding</div><div class="sum-val" id="emi-outstanding" style="color:var(--info)">₹0</div></div>
        </div>
        <div class="emi-grid" id="emi-list"><div class="skeleton" style="height:120px;border-radius:12px"></div></div>
        <button class="add-btn" onclick="openModal('emi-modal')">+ Add EMI / Loan</button>
      </div>

      <!-- CARDS TAB -->
      <div id="tab-cards" style="display:none">
        <div class="sum-grid">
          <div class="sum-card"><div class="sum-lbl">Outstanding bills</div><div class="sum-val" id="card-total" style="color:var(--danger)">₹0</div></div>
          <div class="sum-card"><div class="sum-lbl">Total credit limit</div><div class="sum-val" id="card-limit" style="color:var(--info)">₹0</div></div>
        </div>
        <div class="card-grid" id="card-list"><div class="skeleton" style="height:120px;border-radius:12px"></div></div>
        <button class="add-btn" onclick="openModal('card-modal')">+ Add credit / debit card</button>
      </div>
    </div>

    <nav class="bottom-nav">
      <a class="nav-item" href="dashboard.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg><span>Dashboard</span></a>
      <a class="nav-item" href="transactions.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg><span>Transactions</span></a>
      <a class="nav-item" href="budget.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg><span>Budgets</span></a>
      <a class="nav-item active" href="emis.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg><span>EMI/Cards</span></a>
      <a class="nav-item" href="profile.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg><span>Profile</span></a>
    </nav>
  </div>
</div>

<!-- EMI MODAL -->
<div class="modal-bg" id="emi-modal" onclick="if(event.target===this)closeModal('emi-modal')">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-title">Add EMI / Loan</div>
    <div class="form-group"><label class="form-label">Loan name</label><input class="form-input" id="e-name" placeholder="e.g. Home Loan, Car Loan"></div>
    <div class="grid2">
      <div class="form-group"><label class="form-label">Lender / Bank</label><input class="form-input" id="e-lender" placeholder="HDFC, SBI…"></div>
      <div class="form-group"><label class="form-label">Interest rate %</label><input class="form-input" id="e-rate" type="number" placeholder="8.5" step="0.1" min="0"></div>
    </div>
    <div class="grid2">
      <div class="form-group amount-pfx"><label class="form-label">EMI / month (₹)</label><input class="form-input" id="e-emi" type="number" placeholder="12000" min="1"></div>
      <div class="form-group"><label class="form-label">Tenure (months)</label><input class="form-input" id="e-tenure" type="number" placeholder="60" min="1"></div>
    </div>
    <div class="grid2">
      <div class="form-group"><label class="form-label">Due day of month</label><input class="form-input" id="e-due" type="number" placeholder="5" min="1" max="28"></div>
      <div class="form-group"><label class="form-label">Start date</label><input class="form-input" id="e-start" type="date"></div>
    </div>
    <button class="submit-btn" onclick="saveEmi()">Save EMI</button>
  </div>
</div>

<!-- CARD MODAL -->
<div class="modal-bg" id="card-modal" onclick="if(event.target===this)closeModal('card-modal')">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-title">Add Card</div>
    <div class="form-group"><label class="form-label">Card name</label><input class="form-input" id="c-name" placeholder="e.g. HDFC Regalia, Axis Ace"></div>
    <div class="grid2">
      <div class="form-group"><label class="form-label">Bank</label><input class="form-input" id="c-bank" placeholder="HDFC, ICICI…"></div>
      <div class="form-group"><label class="form-label">Last 4 digits</label><input class="form-input" id="c-last4" type="number" placeholder="1234" maxlength="4"></div>
    </div>
    <div class="grid2">
      <div class="form-group amount-pfx"><label class="form-label">Credit limit (₹)</label><input class="form-input" id="c-limit" type="number" placeholder="100000"></div>
      <div class="form-group"><label class="form-label">Card type</label><select class="form-select" id="c-type"><option value="credit">Credit</option><option value="debit">Debit</option></select></div>
    </div>
    <div class="grid2">
      <div class="form-group"><label class="form-label">Billing date</label><input class="form-input" id="c-billing" type="number" placeholder="1" min="1" max="28"></div>
      <div class="form-group"><label class="form-label">Due date</label><input class="form-input" id="c-due" type="number" placeholder="20" min="1" max="28"></div>
    </div>
    <div class="form-group">
      <label class="form-label">Card colour</label>
      <div class="color-opts" id="color-opts">
        <div class="c-opt sel" data-color="#185FA5" style="background:#185FA5"></div>
        <div class="c-opt" data-color="#0F6E56" style="background:#0F6E56"></div>
        <div class="c-opt" data-color="#7B1FA2" style="background:#7B1FA2"></div>
        <div class="c-opt" data-color="#C62828" style="background:#C62828"></div>
        <div class="c-opt" data-color="#37474F" style="background:#37474F"></div>
        <div class="c-opt" data-color="#E65100" style="background:#E65100"></div>
        <div class="c-opt" data-color="#1B5E20" style="background:#1B5E20"></div>
        <div class="c-opt" data-color="#880E4F" style="background:#880E4F"></div>
      </div>
    </div>
    <button class="submit-btn" onclick="saveCard()">Save card</button>
  </div>
</div>

<div class="toast hidden" id="toast"></div>

<script>


let cardColor='#185FA5',activeTab='emi';
document.addEventListener('DOMContentLoaded',()=>{
  document.getElementById('e-start').value=new Date().toISOString().split('T')[0];
  document.querySelectorAll('.c-opt').forEach(el=>el.addEventListener('click',()=>{document.querySelectorAll('.c-opt').forEach(e=>e.classList.remove('sel'));el.classList.add('sel');cardColor=el.dataset.color;}));
  document.getElementById('desktop-add-btn').onclick=()=>activeTab==='emi'?openModal('emi-modal'):openModal('card-modal');
  loadEmis();
});

function switchTab(tab,btn){activeTab=tab;document.querySelectorAll('.seg-btn').forEach(b=>b.classList.remove('active'));btn.classList.add('active');document.getElementById('tab-emi').style.display=tab==='emi'?'':'none';document.getElementById('tab-cards').style.display=tab==='cards'?'':'none';if(tab==='cards')loadCards();}

async function loadEmis(){
  try{const r=await fetch('api/emis.php?action=emi_list');const j=await r.json();
  if(!j.success||!j.data.length){document.getElementById('emi-list').innerHTML=`<div class="empty-state"><div class="empty-icon">🏦</div>No EMIs yet.<br>Add your loans to track them here.</div>`;document.getElementById('emi-monthly').textContent='₹0';document.getElementById('emi-outstanding').textContent='₹0';return;}
  let totM=0,totO=0;
  document.getElementById('emi-list').innerHTML=j.data.map(e=>{
    totM+=parseFloat(e.emi_amount);totO+=parseFloat(e.remaining_amount);
    const pct=Math.min(100,parseFloat(e.progress_pct));
    const rem=parseInt(e.remaining_months)||0;
    // Calculate clear date
    const clearDate=new Date();
    clearDate.setMonth(clearDate.getMonth()+rem);
    const clearStr=clearDate.toLocaleDateString('en-IN',{month:'short',year:'numeric'});
    const remLabel=rem>0?`${rem} EMI${rem===1?'':'s'} left · Clears ${clearStr}`:'Fully paid 🎉';
    const remColor=rem<=3?'var(--danger)':rem<=6?'var(--warning)':'var(--muted)';
    return `<div class="emi-card">
      <div class="emi-top">
        <div><div class="emi-name">${esc(e.loan_name)}</div><div class="emi-lender">${esc(e.lender||'')}</div></div>
        <div class="emi-due">Due: ${e.due_day}th</div>
      </div>
      <div class="emi-stats">
        <div><div class="emi-stat-lbl">EMI/month</div><div class="emi-stat-val">${fmt(e.emi_amount)}</div></div>
        <div><div class="emi-stat-lbl">EMIs left</div><div class="emi-stat-val" style="color:${remColor};font-weight:600">${rem}</div></div>
        <div><div class="emi-stat-lbl">Outstanding</div><div class="emi-stat-val">${fmt(e.remaining_amount)}</div></div>
      </div>
      <div class="bar-bg"><div class="bar-fill" style="width:${pct}%;background:${rem<=3?'var(--danger)':rem<=6?'var(--warning)':'var(--accent)'}"></div></div>
      <div class="emi-footer">
        <div>
          <span class="emi-prog">${pct.toFixed(0)}% paid (${e.paid_months}/${e.tenure_months})</span>
          <div style="font-size:11px;color:${remColor};margin-top:2px;font-weight:500">${remLabel}</div>
        </div>
        <button class="del-btn" onclick="delEmi(${e.id})">Remove</button>
      </div>
    </div>`;
  }).join('');
  document.getElementById('emi-monthly').textContent=fmt(totM);document.getElementById('emi-outstanding').textContent=fmt(totO);
  }catch(e){document.getElementById('emi-list').innerHTML='<div style="color:var(--muted);font-size:13px">Error loading EMIs.</div>';}
}

async function loadCards(){
  try{const r=await fetch('api/emis.php?action=card_list');const j=await r.json();
  if(!j.success||!j.data.length){document.getElementById('card-list').innerHTML=`<div class="empty-state"><div class="empty-icon">💳</div>No cards yet.<br>Add your credit cards to track bills.</div>`;document.getElementById('card-total').textContent='₹0';document.getElementById('card-limit').textContent='₹0';return;}
  let totB=0,totL=0;
  document.getElementById('card-list').innerHTML=j.data.map(c=>{
    totB+=parseFloat(c.current_balance||0);totL+=parseFloat(c.credit_limit||0);
    const up=Math.min(100,parseFloat(c.utilization_pct||0));
    const upColor=up>=70?'#EF4444':up>=30?'#F59E0B':'rgba(255,255,255,.7)';
    const billingInfo=c.billing_date?`Bill generates on ${c.billing_date}th · `:'';
    return `<div style="margin-bottom:16px">
      <div class="cc-card" style="background:linear-gradient(135deg,${c.color_hex},${c.color_hex}bb)">
        <div class="cc-overlay"></div>
        <button class="cc-del-btn" onclick="delCard(${c.id})">✕</button>
        <div class="cc-bank">${esc(c.bank_name)} · ${c.card_type.toUpperCase()}</div>
        <div class="cc-name">${esc(c.card_name)}</div>
        <div class="cc-last4">${c.last4?'•••• •••• •••• '+c.last4:'No number saved'}</div>
        <div class="cc-row">
          <div>
            <div class="cc-bal-lbl">Outstanding bill</div>
            <div class="cc-bal">${fmt(c.current_balance)}</div>
            <div class="cc-due-txt">${billingInfo}Due on ${c.due_date}th</div>
          </div>
          <div style="text-align:right">
            <div class="cc-util" style="color:${upColor}">${up.toFixed(0)}% used</div>
            <div class="util-bar"><div class="util-fill" style="width:${up}%;background:${upColor}"></div></div>
            <div class="cc-util" style="margin-top:4px">Limit ${fmt(c.credit_limit)}</div>
          </div>
        </div>
      </div>
      <!-- Card transactions this cycle -->
      <div id="card-txns-${c.id}" style="background:var(--surface);border:.5px solid var(--border);border-radius:0 0 var(--r) var(--r);padding:12px;margin-top:-8px">
        <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;font-weight:600">
          Transactions this billing cycle
        </div>
        <div id="card-txn-list-${c.id}" style="font-size:12px;color:var(--muted)">Loading…</div>
      </div>
    </div>`;
  }).join('');
  document.getElementById('card-total').textContent=fmt(totB);document.getElementById('card-limit').textContent=fmt(totL);
  // Load transactions for each card
  j.data.forEach(c=>loadCardTxns(c.id));
  }catch(e){}
}

async function loadCardTxns(cardId){
  try{
    const r=await fetch(`api/emis.php?action=card_txns&card_id=${cardId}`);
    const j=await r.json();
    const el=document.getElementById(`card-txn-list-${cardId}`);
    if(!el)return;
    if(!j.success||!j.data.length){el.textContent='No transactions yet this cycle.';return;}
    el.innerHTML=j.data.map(t=>`
      <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:.5px solid var(--border)">
        <div>
          <div style="font-size:13px;font-weight:500;color:var(--text)">${esc(t.note||t.category_name)}</div>
          <div style="font-size:10px;color:var(--muted);margin-top:1px">${t.txn_date} · ${t.category_name}</div>
        </div>
        <div style="font-size:13px;font-weight:600;color:var(--danger)">-${fmt(t.amount)}</div>
      </div>`).join('') +
      `<div style="display:flex;justify-content:space-between;padding-top:8px;font-size:12px;font-weight:700">
        <span style="color:var(--muted)">Total this cycle</span>
        <span style="color:var(--danger)">${fmt(j.data.reduce((s,t)=>s+parseFloat(t.amount),0))}</span>
      </div>`;
  }catch(e){}
}

async function saveEmi(){
  const body={action:'emi_add',loan_name:document.getElementById('e-name').value,lender:document.getElementById('e-lender').value,interest_rate:document.getElementById('e-rate').value,emi_amount:document.getElementById('e-emi').value,tenure_months:document.getElementById('e-tenure').value,due_day:document.getElementById('e-due').value,start_date:document.getElementById('e-start').value,total_amount:0};
  try{const r=await fetch('api/emis.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});const j=await r.json();if(j.success){closeModal('emi-modal');toast('EMI added! ✓');loadEmis();}else toast(j.message||'Error');}catch(e){toast('Error');}
}
async function saveCard(){
  const body={action:'card_add',card_name:document.getElementById('c-name').value,bank_name:document.getElementById('c-bank').value,last4:document.getElementById('c-last4').value,credit_limit:document.getElementById('c-limit').value,card_type:document.getElementById('c-type').value,billing_date:document.getElementById('c-billing').value,due_date:document.getElementById('c-due').value,color_hex:cardColor};
  try{const r=await fetch('api/emis.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});const j=await r.json();if(j.success){closeModal('card-modal');toast('Card added! ✓');loadCards();}else toast(j.message||'Error');}catch(e){toast('Error');}
}
async function delEmi(id){if(!confirm('Remove this EMI?'))return;try{const r=await fetch('api/emis.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'emi_delete',id})});const j=await r.json();if(j.success){toast('Removed');loadEmis();}}catch(e){}}
async function delCard(id){if(!confirm('Remove this card?'))return;try{const r=await fetch('api/emis.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'card_delete',id})});const j=await r.json();if(j.success){toast('Removed');loadCards();}}catch(e){}}

function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
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
