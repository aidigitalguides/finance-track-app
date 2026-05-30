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
<title>Transactions — Finance Track</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/theme.css">
<style>
.month-btn{width:30px;height:30px;background:var(--surface2);border:.5px solid var(--border2);border-radius:var(--r-xs);color:var(--text);font-size:15px;cursor:pointer;display:flex;align-items:center;justify-content:center}
.month-label{font-family:var(--font-display);font-size:14px;font-weight:600;min-width:110px;text-align:center}
.search-wrap{position:relative;margin-bottom:12px}
.search-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted)}
.search-input{width:100%;padding:10px 14px 10px 38px;background:var(--surface);border:.5px solid var(--border2);border-radius:var(--r-sm);color:var(--text);font-family:var(--font-body);font-size:14px;outline:none;transition:border-color .2s}
.search-input:focus{border-color:var(--accent)}
.search-input::placeholder{color:var(--hint)}
.filter-row{display:flex;gap:7px;margin-bottom:14px;overflow-x:auto;padding-bottom:2px;scrollbar-width:none}
.filter-row::-webkit-scrollbar{display:none}
.chip{white-space:nowrap;padding:6px 13px;background:var(--surface);border:.5px solid var(--border2);border-radius:20px;font-size:12px;color:var(--muted);cursor:pointer;transition:all .2s;flex-shrink:0}
.chip.active{background:var(--accent-glow);border-color:var(--accent);color:var(--accent)}
.summary-bar{display:flex;justify-content:space-between;background:var(--surface);border:.5px solid var(--border);border-radius:var(--r-sm);padding:12px 14px;margin-bottom:14px;box-shadow:var(--shadow)}
.sum-item .lbl{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px}
.sum-item .val{font-family:var(--font-display);font-size:15px;font-weight:700}
.date-group{margin-bottom:14px}
.date-hdr{display:flex;justify-content:space-between;align-items:center;font-size:11px;color:var(--muted);font-weight:500;letter-spacing:.05em;text-transform:uppercase;margin-bottom:7px;padding:0 2px}
.txn-list{display:flex;flex-direction:column;gap:6px}
.txn-item{background:var(--surface);border:.5px solid var(--border);border-radius:var(--r-sm);padding:11px 13px;display:flex;align-items:center;gap:10px;cursor:pointer;transition:background .15s;box-shadow:var(--shadow)}
.txn-item:hover{background:var(--surface2)}
.txn-dot{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0}
.txn-info{flex:1;min-width:0}
.txn-name{font-size:13px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.txn-meta{font-size:11px;color:var(--muted);margin-top:2px;display:flex;gap:6px;align-items:center}
.method-tag{background:var(--surface2);border-radius:4px;padding:1px 6px;font-size:10px;color:var(--hint)}
.txn-amt{font-family:var(--font-display);font-size:14px;font-weight:600;flex-shrink:0}
.txn-amt.out{color:var(--danger)}
.txn-amt.inc{color:var(--success)}
.del-btn{background:var(--danger-bg);color:var(--danger);border:none;border-radius:5px;padding:3px 8px;font-size:11px;cursor:pointer;flex-shrink:0}
.load-more{width:100%;padding:11px;background:var(--surface);border:.5px solid var(--border2);border-radius:var(--r-sm);color:var(--muted);font-size:13px;cursor:pointer;margin-top:10px;transition:background .15s;font-family:var(--font-body)}
.load-more:hover{background:var(--surface2)}
.fab-fixed{position:fixed;bottom:76px;right:20px;width:52px;height:52px;border-radius:50%;background:var(--accent);border:none;color:#fff;font-size:24px;cursor:pointer;box-shadow:0 4px 20px rgba(99,102,241,.4);display:flex;align-items:center;justify-content:center;z-index:20;transition:transform .2s}
.fab-fixed:active{transform:scale(.93)}
@media(min-width:900px){
  .fab-fixed{display:none}
  .desktop-add-btn{display:inline-flex}
}
.desktop-add-btn{display:none;padding:8px 16px;background:var(--accent);color:#fff;border:none;border-radius:var(--r-sm);font-family:var(--font-display);font-size:13px;font-weight:600;cursor:pointer;align-items:center;gap:6px}
.desktop-add-btn:hover{background:var(--accent-dim)}
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
      <a class="sidebar-item active" href="transactions.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg><span>Transactions</span></a>
      <a class="sidebar-item" href="budget.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg><span>Budgets</span></a>
      <a class="sidebar-item" href="emis.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg><span>EMI / Cards</span></a>
      <a class="sidebar-item" href="reports.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg><span>Reports</span></a>
      <a class="sidebar-item" href="profile.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg><span>Profile</span></a>
    </nav>
    <div class="sidebar-footer"><div class="sidebar-user"><div class="sidebar-avatar"><?= $initial ?></div><div><div class="sidebar-name"><?= htmlspecialchars($user['name']??'') ?></div><div class="sidebar-role">Member</div></div></div></div>
  </aside>

  <div class="desktop-content">
    <div class="topbar">
      <div class="topbar-brand"><span class="tb-logo-icon">₹</span> Finance<em>Track</em></div>
      <div style="display:flex;gap:8px"><button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme" aria-label="Toggle theme"><span class="t-opt">🌓</span><span class="t-opt">☀️</span><span class="t-opt">🌙</span></button></div>
    </div>
    <div class="desktop-topbar">
      <div><div style="font-size:12px;color:var(--muted);margin-bottom:2px">Your money movement</div><div class="desktop-page-title">Transactions</div></div>
      <div style="display:flex;align-items:center;gap:10px">
        <button class="month-btn" onclick="prevMonth()">&#8592;</button>
        <span class="month-label" id="mlabel-d"></span>
        <button class="month-btn" onclick="nextMonth()">&#8594;</button>
        <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme" aria-label="Toggle theme"><span class="t-opt">🌓</span><span class="t-opt">☀️</span><span class="t-opt">🌙</span></button>
        <button class="desktop-add-btn" onclick="openModal()"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Add Transaction</button>
      </div>
    </div>

    <div class="main">
      <div class="search-wrap">
        <svg class="search-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input class="search-input" id="search" placeholder="Search transactions…" oninput="onSearch()">
      </div>

      <div class="filter-row">
        <div class="chip active" onclick="setFilter('all',this)">All</div>
        <div class="chip" onclick="setFilter('expense',this)">Expenses</div>
        <div class="chip" onclick="setFilter('income',this)">Income</div>
        <div class="chip" onclick="setFilter('upi',this)">UPI</div>
        <div class="chip" onclick="setFilter('cash',this)">Cash</div>
        <div class="chip" onclick="setFilter('card',this)">Card</div>
      </div>

      <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px" class="mobile-month-row">
        <button class="month-btn" onclick="prevMonth()">&#8592;</button>
        <span class="month-label" id="mlabel-m"></span>
        <button class="month-btn" onclick="nextMonth()">&#8594;</button>
      </div>

      <div class="summary-bar">
        <div class="sum-item"><div class="lbl">Income</div><div class="val" id="s-inc" style="color:var(--success)">—</div></div>
        <div class="sum-item"><div class="lbl">Spent</div><div class="val" id="s-exp" style="color:var(--danger)">—</div></div>
        <div class="sum-item"><div class="lbl">Saved</div><div class="val" id="s-sav" style="color:var(--info)">—</div></div>
        <div class="sum-item"><div class="lbl">Count</div><div class="val" id="s-cnt" style="color:var(--muted)">—</div></div>
      </div>

      <div id="txn-container"><div class="skeleton" style="height:52px;border-radius:10px;margin-bottom:6px"></div><div class="skeleton" style="height:52px;border-radius:10px;margin-bottom:6px;opacity:.8"></div><div class="skeleton" style="height:52px;border-radius:10px;opacity:.6"></div></div>
      <button class="load-more" id="load-more-btn" onclick="loadMore()" style="display:none">Load more transactions</button>
    </div>

    <nav class="bottom-nav">
      <a class="nav-item" href="dashboard.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg><span>Dashboard</span></a>
      <a class="nav-item active" href="transactions.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg><span>Transactions</span></a>
      <a class="nav-item" href="budget.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg><span>Budgets</span></a>
      <a class="nav-item" href="reports.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg><span>Reports</span></a>
      <a class="nav-item" href="profile.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg><span>Profile</span></a>
    </nav>
  </div>
</div>

<button class="fab-fixed" onclick="openModal()">+</button>

<!-- MODAL -->
<div class="modal-bg" id="modal" onclick="if(event.target===this)closeModal()">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-title">Add transaction</div>
    <div class="type-toggle">
      <button class="type-btn active exp" onclick="setType('expense',this)">💸 Expense</button>
      <button class="type-btn inc" onclick="setType('income',this)">💰 Income</button>
    </div>
    <div id="ob-banner-t" style="background:var(--accent-glow);border:.5px solid var(--accent);border-radius:var(--r-sm);padding:10px 13px;margin-bottom:12px;display:none;align-items:center;justify-content:space-between;font-size:13px">
      <div><div style="font-weight:500;color:var(--text)">🏦 Set account balance</div><div style="font-size:11px;color:var(--muted);margin-top:1px">Record your current bank balance</div></div>
      <button onclick="setOB()" style="background:var(--accent);color:#fff;border:none;border-radius:5px;padding:5px 11px;font-size:12px;cursor:pointer;font-family:var(--font-body)">Set →</button>
    </div>
    <div class="form-group amount-pfx"><label class="form-label">Amount</label><input type="number" class="form-input" id="t-amount" placeholder="0" min="1" inputmode="decimal"></div>
    <div class="grid2">
      <div class="form-group"><label class="form-label">Category</label><select class="form-select" id="t-cat"></select></div>
      <div class="form-group"><label class="form-label">Date</label><input type="date" class="form-input" id="t-date"></div>
    </div>
    <div class="form-group"><label class="form-label">Payment method</label><select class="form-select" id="t-method" onchange="toggleCardSelect(this.value)"><option value="upi">UPI</option><option value="cash">Cash</option><option value="card">Card</option><option value="netbanking">Netbanking</option><option value="other">Other</option></select></div>
    <div class="form-group" id="card-select-wrap" style="display:none">
      <label class="form-label">Which credit card? <span style="color:var(--muted);font-size:10px;text-transform:none">(optional — links to card balance)</span></label>
      <select class="form-select" id="t-card-id"><option value="">None / Debit card</option></select>
    </div>
    <div class="form-group"><label class="form-label">Note (optional)</label><input type="text" class="form-input" id="t-note" placeholder="e.g. Office lunch, Swiggy order" maxlength="255"></div>
    <button class="submit-btn" onclick="saveTxn()">Save transaction</button>
  </div>
</div>
<div class="toast hidden" id="toast"></div>

<script>


const MN=['January','February','March','April','May','June','July','August','September','October','November','December'];
let cm=new Date().getMonth()+1,cy=new Date().getFullYear(),filter='all',txnType='expense',cats=[],allTxns=[],offset=0;
const LIMIT=30;
let searchTimer=null;

document.addEventListener('DOMContentLoaded',async()=>{
  document.getElementById('t-date').value=new Date().toISOString().split('T')[0];
  updLabel();
  await loadCats();
  loadCardOptions();
  loadAll();
});

function prevMonth(){cm--;if(cm<1){cm=12;cy--;}updLabel();offset=0;loadAll();}
function nextMonth(){const n=new Date();if(cy===n.getFullYear()&&cm>=n.getMonth()+1)return;cm++;if(cm>12){cm=1;cy++;}updLabel();offset=0;loadAll();}
function updLabel(){const l=MN[cm-1]+' '+cy;['mlabel-d','mlabel-m'].forEach(id=>{const e=document.getElementById(id);if(e)e.textContent=l;});}
function setFilter(f,el){filter=f;document.querySelectorAll('.chip').forEach(c=>c.classList.remove('active'));el.classList.add('active');offset=0;loadAll();}
function onSearch(){clearTimeout(searchTimer);searchTimer=setTimeout(()=>{offset=0;loadAll();},350);}

async function loadAll(){await Promise.all([loadSummary(),loadTxns(false)]);}

async function loadSummary(){
  try{const r=await fetch(`api/transactions.php?action=summary&month=${cm}&year=${cy}`);const j=await r.json();
  if(!j.success)return;const d=j.data;
  document.getElementById('s-inc').textContent=fmt(d.income);
  document.getElementById('s-exp').textContent=fmt(d.expense);
  document.getElementById('s-sav').textContent=fmt(d.savings);
  }catch(e){}
}

async function loadTxns(append=false){
  try{
    const search=document.getElementById('search').value.trim();
    const typeF=['expense','income'].includes(filter)?'&type='+filter:'';
    const searchF=search?'&search='+encodeURIComponent(search):'';
    const r=await fetch(`api/transactions.php?action=list&month=${cm}&year=${cy}&limit=${LIMIT}&offset=${offset}${typeF}${searchF}`);
    const j=await r.json();if(!j.success)return;
    let data=j.data;
    // client-side method filter
    if(['upi','cash','card','netbanking'].includes(filter))data=data.filter(t=>t.payment_method===filter);
    if(!append)allTxns=data;else allTxns=[...allTxns,...data];
    document.getElementById('s-cnt').textContent=allTxns.length+(j.data.length===LIMIT?'+':'');
    renderTxns();
    document.getElementById('load-more-btn').style.display=j.data.length===LIMIT?'':'none';
  }catch(e){document.getElementById('txn-container').innerHTML='<div style="color:var(--muted);font-size:13px;padding:8px 0">Error loading transactions.</div>';}
}
function loadMore(){offset+=LIMIT;loadTxns(true);}

function renderTxns(){
  if(!allTxns.length){document.getElementById('txn-container').innerHTML=`<div style="text-align:center;padding:48px 20px;color:var(--muted)"><div style="font-size:36px;margin-bottom:10px">🧾</div><div style="font-size:14px">No transactions found.</div><div style="font-size:12px;margin-top:6px">Tap + to add your first one.</div></div>`;return;}
  const today=new Date().toISOString().split('T')[0];
  const yesterday=new Date(Date.now()-86400000).toISOString().split('T')[0];
  const groups={};
  allTxns.forEach(t=>{if(!groups[t.txn_date])groups[t.txn_date]=[];groups[t.txn_date].push(t);});
  document.getElementById('txn-container').innerHTML=Object.entries(groups).map(([date,txns])=>{
    let lbl=new Date(date+'T00:00:00').toLocaleDateString('en-IN',{day:'numeric',month:'long',weekday:'long'});
    if(date===today)lbl='Today · '+lbl;
    else if(date===yesterday)lbl='Yesterday · '+lbl;
    const dayTotal=txns.reduce((s,t)=>s+(t.type==='income'?+t.amount:-t.amount),0);
    const dc=dayTotal>=0?'var(--success)':'var(--danger)';
    return `<div class="date-group"><div class="date-hdr"><span>${esc(lbl)}</span><span style="color:${dc}">${dayTotal>=0?'+':''}${fmt(dayTotal)}</span></div><div class="txn-list">${txns.map(txnHTML).join('')}</div></div>`;
  }).join('');
}

function txnHTML(t){
  const isI=t.type==='income';const bg=(t.color_hex||'#888780')+'28';
  const amt=(isI?'+':'-')+fmt(t.amount);
  return `<div class="txn-item"><div class="txn-dot" style="background:${bg}">${t.category_icon}</div><div class="txn-info"><div class="txn-name">${esc(t.note||t.category_name)}</div><div class="txn-meta"><span>${esc(t.category_name)}</span><span class="method-tag">${t.payment_method.toUpperCase()}</span></div></div><div class="txn-amt ${isI?'inc':'out'}">${amt}</div><button class="del-btn" onclick="delTxn(${t.id})">✕</button></div>`;
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
function openModal(){
  document.getElementById('modal').classList.add('open');
  document.getElementById('t-amount').focus();
  // Show OB banner only if no transactions yet
  if(allTxns.length===0){document.getElementById('ob-banner-t').style.display='flex';}
}
async function setOB(){
  document.querySelectorAll('.type-btn').forEach(b=>b.classList.remove('active'));
  document.querySelector('.type-btn.inc').classList.add('active');
  txnType='income';renderCats('income');
  const sel=document.getElementById('t-cat');
  for(let i=0;i<sel.options.length;i++){if(sel.options[i].text.includes('Opening Balance')){sel.selectedIndex=i;break;}}
  document.getElementById('ob-banner-t').style.display='none';
  document.getElementById('t-amount').focus();
  document.getElementById('t-note').value='Opening balance — my current account balance';
}
function closeModal(){document.getElementById('modal').classList.remove('open');}

async function saveTxn(){
  const amount=parseFloat(document.getElementById('t-amount').value);
  if(!amount||amount<=0){toast('Enter a valid amount');return;}
  try{const r=await fetch('api/transactions.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'add',amount,category_id:document.getElementById('t-cat').value,type:txnType,txn_date:document.getElementById('t-date').value,payment_method:document.getElementById('t-method').value,note:document.getElementById('t-note').value,card_id:(document.getElementById('t-card-id')&&document.getElementById('t-method').value==='card')?document.getElementById('t-card-id').value:''})});
  const j=await r.json();
  if(j.success){closeModal();document.getElementById('t-amount').value='';document.getElementById('t-note').value='';toast('Saved! ✓');offset=0;loadAll();}
  else toast(j.message||'Error');
  }catch(e){toast('Network error');}
}

async function delTxn(id){
  if(!confirm('Delete this transaction?'))return;
  try{const r=await fetch('api/transactions.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'delete',id})});
  const j=await r.json();
  if(j.success){toast('Deleted');allTxns=allTxns.filter(t=>t.id!==id);renderTxns();loadSummary();}
  }catch(e){}
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
