<?php
require_once 'config.php';
require_once 'includes/Database.php';
require_once 'includes/Auth.php';
Auth::startSession();
$userId = Auth::requireLogin();
$user   = Database::fetchOne('SELECT name FROM users WHERE id = ?', [$userId]);
$initial = strtoupper(mb_substr($user['name']??'U', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Budgets — Finance Track</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/theme.css">
<style>
.month-btn{width:30px;height:30px;background:var(--surface2);border:.5px solid var(--border2);border-radius:var(--r-xs);color:var(--text);font-size:15px;cursor:pointer;display:flex;align-items:center;justify-content:center}
.month-label{font-family:var(--font-display);font-size:14px;font-weight:600;min-width:110px;text-align:center}
.budget-card{background:var(--surface);border:.5px solid var(--border);border-radius:var(--r);padding:16px;margin-bottom:10px;box-shadow:var(--shadow);transition:transform .15s}
.budget-card:hover{transform:translateY(-1px)}
.bc-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
.bc-left{display:flex;align-items:center;gap:10px}
.bc-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0}
.bc-name{font-size:14px;font-weight:500}
.bc-limit{font-size:11px;color:var(--muted);margin-top:1px}
.bc-right{text-align:right}
.bc-spent{font-family:var(--font-display);font-size:16px;font-weight:700}
.bc-pct{font-size:11px;color:var(--muted);margin-top:1px}
.bar-wrap{margin-bottom:4px}
.bar-bg{height:6px;background:var(--surface2);border-radius:3px;overflow:hidden}
.bar-fill{height:100%;border-radius:3px;transition:width .5s ease}
.bar-ok{background:var(--success)}
.bar-warn{background:var(--warning)}
.bar-over{background:var(--danger)}
.bc-footer{display:flex;justify-content:space-between;align-items:center;margin-top:6px}
.bc-remaining{font-size:11px;color:var(--muted)}
.bc-actions{display:flex;gap:6px}
.btn-sm{font-size:11px;padding:3px 9px;border-radius:5px;cursor:pointer;border:none;font-family:var(--font-body)}
.btn-edit{background:var(--info-bg);color:var(--info)}
.btn-del{background:var(--danger-bg);color:var(--danger)}
.over-badge{background:var(--danger-bg);color:var(--danger);font-size:10px;padding:2px 7px;border-radius:8px}
.ok-badge{background:var(--success-bg);color:var(--success);font-size:10px;padding:2px 7px;border-radius:8px}
.warn-badge{background:var(--warning-bg);color:var(--warning);font-size:10px;padding:2px 7px;border-radius:8px}
.summary-strip{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:18px}
.sum-card{background:var(--surface);border:.5px solid var(--border);border-radius:var(--r-sm);padding:13px;text-align:center;box-shadow:var(--shadow)}
.sum-val{font-family:var(--font-display);font-size:20px;font-weight:700;margin-bottom:3px}
.sum-lbl{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.05em}
.add-btn{width:100%;padding:13px;background:var(--surface);border:.5px dashed var(--border2);border-radius:var(--r-sm);color:var(--muted);font-size:14px;cursor:pointer;transition:all .2s;font-family:var(--font-body);margin-top:4px}
.add-btn:hover{background:var(--surface2);color:var(--text);border-color:var(--accent)}
.alert-row{display:flex;align-items:center;justify-content:space-between;margin-top:8px}
.alert-label{font-size:12px;color:var(--muted)}
.alert-select{background:var(--surface2);border:.5px solid var(--border2);border-radius:5px;color:var(--text);padding:4px 8px;font-size:12px;outline:none}
@media(min-width:900px){
  .budget-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
  .summary-strip{grid-template-columns:repeat(3,1fr)}
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
      <a class="sidebar-item" href="dashboard.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg><span>Dashboard</span></a>
      <a class="sidebar-item" href="transactions.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg><span>Transactions</span></a>
      <a class="sidebar-item active" href="budget.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg><span>Budgets</span></a>
      <a class="sidebar-item" href="emis.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg><span>EMI / Cards</span></a>
      <a class="sidebar-item" href="reports.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg><span>Reports</span></a>
      <a class="sidebar-item" href="profile.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg><span>Profile</span></a>
    </nav>
    <div class="sidebar-footer"><div class="sidebar-user"><div class="sidebar-avatar"><?= $initial ?></div><div><div class="sidebar-name"><?= htmlspecialchars($user['name']??'') ?></div><div class="sidebar-role">Member</div></div></div></div>
  </aside>

  <div class="desktop-content">
    <div class="topbar">
      <div class="topbar-brand"><span class="tb-logo-icon">₹</span> Finance<em>Track</em></div>
      <div style="display:flex;gap:8px;align-items:center">
        <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme" aria-label="Toggle theme"><span class="t-opt">🌓</span><span class="t-opt">☀️</span><span class="t-opt">🌙</span></button>
      </div>
    </div>
    <div class="desktop-topbar">
      <div><div style="font-size:12px;color:var(--muted);margin-bottom:2px">Manage spending limits</div><div class="desktop-page-title">Budgets</div></div>
      <div style="display:flex;align-items:center;gap:10px">
        <button class="month-btn" onclick="prevMonth()">&#8592;</button>
        <span class="month-label" id="mlabel-d"></span>
        <button class="month-btn" onclick="nextMonth()">&#8594;</button>
        <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme" aria-label="Toggle theme"><span class="t-opt">🌓</span><span class="t-opt">☀️</span><span class="t-opt">🌙</span></button>
        <button style="padding:8px 16px;background:var(--accent);color:#fff;border:none;border-radius:var(--r-sm);font-family:var(--font-display);font-size:13px;font-weight:600;cursor:pointer" onclick="openAdd()">+ New Budget</button>
      </div>
    </div>

    <div class="main">
      <!-- Month nav mobile -->
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px" class="mobile-month-nav">
        <button class="month-btn" onclick="prevMonth()">&#8592;</button>
        <span class="month-label" id="mlabel-m"></span>
        <button class="month-btn" onclick="nextMonth()">&#8594;</button>
        <button style="margin-left:auto;padding:8px 14px;background:var(--accent);color:#fff;border:none;border-radius:var(--r-sm);font-family:var(--font-display);font-size:13px;font-weight:600;cursor:pointer" onclick="openAdd()">+ Add</button>
      </div>

      <!-- Summary -->
      <div class="summary-strip">
        <div class="sum-card"><div class="sum-val" id="total-budget" style="color:var(--info)">₹0</div><div class="sum-lbl">Total budget</div></div>
        <div class="sum-card"><div class="sum-val" id="total-spent" style="color:var(--danger)">₹0</div><div class="sum-lbl">Total spent</div></div>
        <div class="sum-card"><div class="sum-val" id="total-left" style="color:var(--success)">₹0</div><div class="sum-lbl">Remaining</div></div>
      </div>

      <!-- Budget list -->
      <div class="budget-grid" id="budget-grid">
        <div class="skeleton" style="height:100px;border-radius:12px"></div>
        <div class="skeleton" style="height:100px;border-radius:12px;opacity:.7"></div>
        <div class="skeleton" style="height:100px;border-radius:12px;opacity:.5"></div>
      </div>

      <button class="add-btn" onclick="openAdd()">+ Set budget for a new category</button>
    </div>

    <nav class="bottom-nav">
      <a class="nav-item" href="dashboard.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg><span>Dashboard</span></a>
      <a class="nav-item" href="transactions.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg><span>Transactions</span></a>
      <a class="nav-item active" href="budget.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg><span>Budgets</span></a>
      <a class="nav-item" href="reports.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg><span>Reports</span></a>
      <a class="nav-item" href="profile.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg><span>Profile</span></a>
    </nav>
  </div>
</div>

<!-- ADD/EDIT MODAL -->
<div class="modal-bg" id="modal" onclick="if(event.target===this)closeModal()">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-title" id="modal-title">Set Budget</div>
    <input type="hidden" id="edit-id">
    <div class="form-group">
      <label class="form-label">Category</label>
      <select class="form-select" id="b-cat"></select>
    </div>
    <div class="form-group amount-pfx">
      <label class="form-label">Monthly limit (₹)</label>
      <input type="number" class="form-input" id="b-amount" placeholder="e.g. 10000" min="1" inputmode="decimal">
    </div>
    <div class="alert-row">
      <span class="alert-label">Alert me when spending reaches</span>
      <select class="alert-select" id="b-alert">
        <option value="50">50%</option>
        <option value="70">70%</option>
        <option value="80" selected>80%</option>
        <option value="90">90%</option>
        <option value="100">100%</option>
      </select>
    </div>
    <div style="margin-top:16px">
      <button class="submit-btn" onclick="saveBudget()">Save budget</button>
    </div>
  </div>
</div>
<div class="toast hidden" id="toast"></div>

<script>


const MN=['January','February','March','April','May','June','July','August','September','October','November','December'];
let cm=new Date().getMonth()+1,cy=new Date().getFullYear(),cats=[],editingId=null;

document.addEventListener('DOMContentLoaded',()=>{updLabel();loadCats().then(()=>loadBudgets());});

function prevMonth(){cm--;if(cm<1){cm=12;cy--;}updLabel();loadBudgets();}
function nextMonth(){const n=new Date();if(cy===n.getFullYear()&&cm>=n.getMonth()+1)return;cm++;if(cm>12){cm=1;cy++;}updLabel();loadBudgets();}
function updLabel(){const l=MN[cm-1]+' '+cy;['mlabel-d','mlabel-m'].forEach(id=>{const e=document.getElementById(id);if(e)e.textContent=l;});}

async function loadCats(){
  try{const r=await fetch('api/categories.php?action=list&type=expense');const j=await r.json();
  if(j.success){cats=j.data;renderCatSelect();}
  }catch(e){}
}
function renderCatSelect(){
  document.getElementById('b-cat').innerHTML=cats.map(c=>`<option value="${c.id}">${c.icon} ${c.name}</option>`).join('');
}

async function loadBudgets(){
  try{const r=await fetch(`api/budgets.php?action=list&month=${cm}&year=${cy}`);const j=await r.json();
  if(!j.success){renderEmpty();return;}
  const data=j.data;
  let totBudget=0,totSpent=0;
  data.forEach(b=>{totBudget+=parseFloat(b.budget);totSpent+=parseFloat(b.spent);});
  document.getElementById('total-budget').textContent=fmt(totBudget);
  document.getElementById('total-spent').textContent=fmt(totSpent);
  document.getElementById('total-left').textContent=fmt(Math.max(0,totBudget-totSpent));
  if(!data.length){renderEmpty();return;}
  document.getElementById('budget-grid').innerHTML=data.map(b=>{
    const pct=b.budget>0?Math.min(100,Math.round((b.spent/b.budget)*100)):0;
    const cls=pct>=100?'bar-over':pct>=80?'bar-warn':'bar-ok';
    const badge=pct>=100?`<span class="over-badge">Over budget!</span>`:pct>=b.alert_at_pct?`<span class="warn-badge">⚠ Near limit</span>`:`<span class="ok-badge">On track</span>`;
    const bg=(b.color_hex||'#888780')+'22';
    const remaining=Math.max(0,parseFloat(b.budget)-parseFloat(b.spent));
    return `<div class="budget-card">
      <div class="bc-top">
        <div class="bc-left">
          <div class="bc-icon" style="background:${bg}">${b.icon}</div>
          <div><div class="bc-name">${esc(b.name)}</div><div class="bc-limit">Limit: ${fmt(b.budget)}</div></div>
        </div>
        <div class="bc-right"><div class="bc-spent" style="color:${pct>=100?'var(--danger)':pct>=80?'var(--warning)':'var(--text)'}">${fmt(b.spent)}</div><div class="bc-pct">${pct}% used</div></div>
      </div>
      <div class="bar-wrap"><div class="bar-bg"><div class="bar-fill ${cls}" style="width:${pct}%"></div></div></div>
      <div class="bc-footer">
        <div style="display:flex;align-items:center;gap:8px">${badge}<span class="bc-remaining">${fmt(remaining)} left</span></div>
        <div class="bc-actions">
          <button class="btn-sm btn-edit" onclick="editBudget(${b.id},'${esc(b.name)}',${b.budget},${b.category_id||0},${b.alert_at_pct})">Edit</button>
          <button class="btn-sm btn-del" onclick="deleteBudget(${b.id})">Delete</button>
        </div>
      </div>
    </div>`;
  }).join('');
  }catch(e){renderEmpty();}
}
function renderEmpty(){
  document.getElementById('budget-grid').innerHTML=`<div style="grid-column:1/-1;text-align:center;padding:40px 20px;color:var(--muted)"><div style="font-size:36px;margin-bottom:12px">📊</div><div style="font-size:15px;font-weight:500;margin-bottom:6px">No budgets for ${MN[cm-1]}</div><div style="font-size:13px">Set spending limits to track where your money goes.</div></div>`;
}

function openAdd(){
  editingId=null;
  document.getElementById('modal-title').textContent='Set Budget';
  document.getElementById('edit-id').value='';
  document.getElementById('b-amount').value='';
  document.getElementById('b-alert').value='80';
  renderCatSelect();
  document.getElementById('modal').classList.add('open');
}
function editBudget(id,name,amount,catId,alertPct){
  editingId=id;
  document.getElementById('modal-title').textContent='Edit Budget';
  document.getElementById('edit-id').value=id;
  document.getElementById('b-amount').value=amount;
  document.getElementById('b-alert').value=alertPct||80;
  // Select the matching category
  const sel=document.getElementById('b-cat');
  for(let i=0;i<sel.options.length;i++){if(parseInt(sel.options[i].value)===catId){sel.selectedIndex=i;break;}}
  document.getElementById('modal').classList.add('open');
}
function closeModal(){document.getElementById('modal').classList.remove('open');}

async function saveBudget(){
  const catId=document.getElementById('b-cat').value;
  const amount=parseFloat(document.getElementById('b-amount').value);
  const alertAt=document.getElementById('b-alert').value;
  if(!catId||!amount||amount<=0){toast('Fill in all fields');return;}
  try{const r=await fetch('api/budgets.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'add',category_id:catId,amount,month:cm,year:cy,alert_at_pct:alertAt})});
  const j=await r.json();
  if(j.success){closeModal();toast('Budget saved! ✓');loadBudgets();}
  else toast(j.message||'Error');
  }catch(e){toast('Network error');}
}
async function deleteBudget(id){
  if(!confirm('Delete this budget?'))return;
  try{const r=await fetch('api/budgets.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'delete',id})});
  const j=await r.json();
  if(j.success){toast('Deleted');loadBudgets();}
  }catch(e){}
}

function fmt(v){return '₹'+Math.abs(parseFloat(v)||0).toLocaleString('en-IN',{maximumFractionDigits:0});}
function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/'/g,'&#39;');}
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
