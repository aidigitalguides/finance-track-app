<?php
require_once 'config.php';
require_once 'includes/Database.php';
require_once 'includes/Auth.php';
Auth::startSession();
$userId   = Auth::requireLogin();
$user     = Database::fetchOne('SELECT name, email, created_at FROM users WHERE id = ?', [$userId]);
$settings = Database::fetchOne('SELECT * FROM user_settings WHERE user_id = ?', [$userId]);
$initial  = strtoupper(mb_substr($user['name']??'U', 0, 1));
$joinYear = date('Y', strtotime($user['created_at']??'now'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Profile — Finance Track</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/theme.css">
<style>
.profile-hero{background:var(--surface);border:.5px solid var(--border);border-radius:var(--r);padding:20px;margin-bottom:14px;display:flex;align-items:center;gap:16px;box-shadow:var(--shadow)}
.hero-avatar{width:64px;height:64px;border-radius:50%;background:var(--accent-glow);border:.5px solid var(--accent);display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-size:26px;font-weight:700;color:var(--accent);flex-shrink:0}
.hero-name{font-family:var(--font-display);font-size:20px;font-weight:700;margin-bottom:3px}
.hero-email{font-size:12px;color:var(--muted)}
.hero-badge{display:inline-flex;align-items:center;gap:5px;margin-top:8px;background:var(--accent-glow);border:.5px solid var(--accent);border-radius:12px;padding:3px 10px;font-size:11px;color:var(--accent)}
.section-label{font-size:11px;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin:18px 0 8px;padding:0 2px}
.settings-group{background:var(--surface);border:.5px solid var(--border);border-radius:var(--r);overflow:hidden;margin-bottom:8px;box-shadow:var(--shadow)}
.setting-row{display:flex;align-items:center;justify-content:space-between;padding:13px 16px;border-bottom:.5px solid var(--border);cursor:pointer;transition:background .15s}
.setting-row:last-child{border-bottom:none}
.setting-row:hover{background:var(--surface2)}
.setting-left{display:flex;align-items:center;gap:12px}
.setting-icon{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.setting-name{font-size:14px;font-weight:500}
.setting-desc{font-size:11px;color:var(--muted);margin-top:1px}
.chevron{color:var(--hint);font-size:16px}
.toggle{position:relative;width:44px;height:24px;flex-shrink:0}
.toggle input{opacity:0;width:0;height:0}
.toggle-slider{position:absolute;inset:0;background:var(--surface2);border:.5px solid var(--border2);border-radius:12px;transition:.2s;cursor:pointer}
.toggle-slider::before{content:'';position:absolute;width:18px;height:18px;left:2px;top:2px;background:var(--muted);border-radius:50%;transition:.2s}
input:checked+.toggle-slider{background:var(--accent);border-color:var(--accent)}
input:checked+.toggle-slider::before{transform:translateX(20px);background:#fff}
.goal-card{background:var(--surface);border:.5px solid var(--border);border-radius:var(--r-sm);padding:14px;margin-bottom:8px;box-shadow:var(--shadow)}
.goal-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
.goal-name{font-size:13px;font-weight:500}
.goal-amounts{font-size:11px;color:var(--muted);margin-top:2px}
.goal-bar-bg{height:5px;background:var(--surface2);border-radius:3px;overflow:hidden;margin-bottom:4px}
.goal-bar-fill{height:100%;border-radius:3px;transition:width .4s}
.goal-pct{font-size:10px;color:var(--muted);text-align:right}
.goal-actions{display:flex;gap:6px;margin-top:8px}
.goal-add-btn{flex:1;padding:7px;background:var(--accent-glow);border:.5px solid var(--accent);border-radius:5px;color:var(--accent);font-size:12px;cursor:pointer;font-family:var(--font-body)}
.goal-del-btn{padding:7px 10px;background:var(--danger-bg);border:.5px solid var(--danger);border-radius:5px;color:var(--danger);font-size:12px;cursor:pointer;font-family:var(--font-body)}
.achieved-badge{background:var(--success-bg);color:var(--success);font-size:10px;padding:2px 8px;border-radius:8px}
.add-goal-btn{width:100%;padding:11px;background:var(--surface);border:.5px dashed var(--border2);border-radius:var(--r-sm);color:var(--muted);font-size:14px;cursor:pointer;transition:all .2s;font-family:var(--font-body);margin-bottom:8px}
.add-goal-btn:hover{background:var(--surface2);color:var(--text)}
.danger-btn{width:100%;padding:12px;background:var(--danger-bg);border:.5px solid var(--danger);border-radius:var(--r-sm);color:var(--danger);font-size:14px;cursor:pointer;font-family:var(--font-body);font-weight:500;margin-bottom:8px;transition:background .2s}
.danger-btn:hover{background:#E24B4A22}
.profile-cols{display:flex;flex-direction:column;gap:0}
@media(min-width:900px){
  .profile-cols{display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start}
  .profile-col-right{}
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
      <a class="sidebar-item" href="budget.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg><span>Budgets</span></a>
      <a class="sidebar-item" href="emis.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg><span>EMI / Cards</span></a>
      <a class="sidebar-item" href="reports.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg><span>Reports</span></a>
      <a class="sidebar-item active" href="profile.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg><span>Profile</span></a>
    </nav>
    <div class="sidebar-footer"><div class="sidebar-user"><div class="sidebar-avatar"><?= $initial ?></div><div><div class="sidebar-name"><?= htmlspecialchars($user['name']??'') ?></div><div class="sidebar-role">Member</div></div></div></div>
  </aside>

  <div class="desktop-content">
    <div class="topbar">
      <div class="topbar-brand"><span class="tb-logo-icon">₹</span> Finance<em>Track</em></div>
      <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme" aria-label="Toggle theme"><span class="t-opt">🌓</span><span class="t-opt">☀️</span><span class="t-opt">🌙</span></button>
    </div>
    <div class="desktop-topbar">
      <div><div style="font-size:12px;color:var(--muted);margin-bottom:2px">Account & settings</div><div class="desktop-page-title">Profile</div></div>
      <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme" aria-label="Toggle theme"><span class="t-opt">🌓</span><span class="t-opt">☀️</span><span class="t-opt">🌙</span></button>
    </div>

    <div class="main">
      <div class="profile-hero">
        <div class="hero-avatar"><?= $initial ?></div>
        <div>
          <div class="hero-name"><?= htmlspecialchars($user['name']??'') ?></div>
          <div class="hero-email"><?= htmlspecialchars($user['email']??'') ?></div>
          <div class="hero-badge">🇮🇳 Finance Track since <?= $joinYear ?></div>
        </div>
      </div>

      <div class="profile-cols">
        <div class="profile-col-left">
          <div class="section-label">Savings goals</div>
          <div id="goal-list"><div class="skeleton" style="height:80px;border-radius:10px;margin-bottom:8px"></div></div>
          <button class="add-goal-btn" onclick="openModal('goal-modal')">+ Add savings goal</button>

          <div class="section-label">Preferences</div>
          <div class="settings-group">
            <div class="setting-row">
              <div class="setting-left"><div class="setting-icon" style="background:var(--surface2)">🔤</div><div><div class="setting-name">Large text</div><div class="setting-desc">Bigger font for easier reading</div></div></div>
              <label class="toggle" onclick="event.stopPropagation()"><input type="checkbox" id="tog-large" <?= $settings['large_text']??0 ? 'checked' : '' ?> onchange="saveSetting('large_text',this.checked?1:0)"><span class="toggle-slider"></span></label>
            </div>
            <div class="setting-row">
              <div class="setting-left"><div class="setting-icon" style="background:var(--surface2)">🇮🇳</div><div><div class="setting-name">Tax regime</div><div class="setting-desc">For tax estimation</div></div></div>
              <select onchange="saveSetting('tax_regime',this.value)" style="background:var(--surface2);border:.5px solid var(--border2);color:var(--text);border-radius:6px;padding:5px 8px;font-size:12px;outline:none">
                <option value="new" <?= ($settings['tax_regime']??'new')==='new'?'selected':'' ?>>New regime</option>
                <option value="old" <?= ($settings['tax_regime']??'new')==='old'?'selected':'' ?>>Old regime</option>
              </select>
            </div>
            <div class="setting-row">
              <div class="setting-left"><div class="setting-icon" style="background:var(--surface2)">📊</div><div><div class="setting-name">Budget alerts</div><div class="setting-desc">Alert when nearing limit</div></div></div>
              <label class="toggle" onclick="event.stopPropagation()"><input type="checkbox" id="tog-budget" <?= $settings['notif_budget']??1 ? 'checked' : '' ?> onchange="saveSetting('notif_budget',this.checked?1:0)"><span class="toggle-slider"></span></label>
            </div>
            <div class="setting-row">
              <div class="setting-left"><div class="setting-icon" style="background:var(--surface2)">🏦</div><div><div class="setting-name">EMI reminders</div><div class="setting-desc">Remind before EMI due date</div></div></div>
              <label class="toggle" onclick="event.stopPropagation()"><input type="checkbox" id="tog-emi" <?= $settings['notif_emi']??1 ? 'checked' : '' ?> onchange="saveSetting('notif_emi',this.checked?1:0)"><span class="toggle-slider"></span></label>
            </div>
          </div>
        </div>

        <div class="profile-col-right">
          <div class="section-label">Account</div>
          <div class="settings-group">
            <div class="setting-row" onclick="openModal('pin-modal')">
              <div class="setting-left"><div class="setting-icon" style="background:var(--info-bg)">🔒</div><div><div class="setting-name">App PIN</div><div class="setting-desc">Set a 4-digit lock</div></div></div>
              <span class="chevron">›</span>
            </div>
            <div class="setting-row" onclick="openModal('export-modal')">
              <div class="setting-left"><div class="setting-icon" style="background:var(--success-bg)">📤</div><div><div class="setting-name">Export data</div><div class="setting-desc">Download your transactions as CSV</div></div></div>
              <span class="chevron">›</span>
            </div>
            <div class="setting-row" href="emis.php" onclick="location.href='emis.php'">
              <div class="setting-left"><div class="setting-icon" style="background:var(--warning-bg)">💳</div><div><div class="setting-name">EMI & Cards</div><div class="setting-desc">Manage loans and credit cards</div></div></div>
              <span class="chevron">›</span>
            </div>
          </div>

          <div class="section-label">Danger zone</div>
          <button class="danger-btn" onclick="doLogout()">Sign out of Finance Track</button>

          <div style="text-align:center;font-size:11px;color:var(--hint);margin-top:16px">Finance Track v1.0 · Your data stays on your server</div>
        </div>
      </div>
    </div>

    <nav class="bottom-nav">
      <a class="nav-item" href="dashboard.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg><span>Dashboard</span></a>
      <a class="nav-item" href="transactions.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg><span>Transactions</span></a>
      <a class="nav-item" href="budget.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg><span>Budgets</span></a>
      <a class="nav-item" href="reports.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg><span>Reports</span></a>
      <a class="nav-item active" href="profile.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg><span>Profile</span></a>
    </nav>
  </div>
</div>

<!-- GOAL MODAL -->
<div class="modal-bg" id="goal-modal" onclick="if(event.target===this)closeModal('goal-modal')">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-title">Add savings goal</div>
    <div class="form-group"><label class="form-label">Goal name</label><input class="form-input" id="g-name" placeholder="e.g. Emergency fund, Europe trip"></div>
    <div class="grid2">
      <div class="form-group amount-pfx"><label class="form-label">Target (₹)</label><input class="form-input" id="g-target" type="number" placeholder="100000" min="1"></div>
      <div class="form-group amount-pfx"><label class="form-label">Already saved (₹)</label><input class="form-input" id="g-saved" type="number" placeholder="0" min="0"></div>
    </div>
    <div class="grid2">
      <div class="form-group"><label class="form-label">Target date</label><input class="form-input" id="g-date" type="date"></div>
      <div class="form-group"><label class="form-label">Icon</label><input class="form-input" id="g-icon" placeholder="🎯" maxlength="4" style="font-size:20px;text-align:center"></div>
    </div>
    <button class="submit-btn" onclick="saveGoal()">Create goal</button>
  </div>
</div>

<!-- ADD FUNDS MODAL -->
<div class="modal-bg" id="funds-modal" onclick="if(event.target===this)closeModal('funds-modal')">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-title">Add funds to goal</div>
    <input type="hidden" id="funds-goal-id">
    <div class="form-group amount-pfx"><label class="form-label">Amount (₹)</label><input class="form-input" id="funds-amt" type="number" placeholder="5000" min="1"></div>
    <button class="submit-btn" onclick="addFunds()">Add funds</button>
  </div>
</div>

<!-- PIN MODAL -->
<div class="modal-bg" id="pin-modal" onclick="if(event.target===this)closeModal('pin-modal')">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-title">Set app PIN</div>
    <div class="form-group"><label class="form-label">New 4-digit PIN</label><input class="form-input" id="pin-new" type="number" placeholder="1234" style="letter-spacing:.3em;font-size:20px;text-align:center"></div>
    <div class="form-group"><label class="form-label">Confirm PIN</label><input class="form-input" id="pin-confirm" type="number" placeholder="1234" style="letter-spacing:.3em;font-size:20px;text-align:center"></div>
    <button class="submit-btn" onclick="savePin()">Save PIN</button>
  </div>
</div>

<!-- EXPORT MODAL -->
<div class="modal-bg" id="export-modal" onclick="if(event.target===this)closeModal('export-modal')">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-title">Export data</div>
    <p style="font-size:13px;color:var(--muted);margin-bottom:18px">Download your transactions as CSV. Opens in Excel, Google Sheets, or any spreadsheet app.</p>
    <div class="grid2" style="margin-bottom:14px">
      <div class="form-group"><label class="form-label">Month</label><select class="form-select" id="exp-month"><?php for($m=1;$m<=12;$m++)echo "<option value='$m'".($m==date('n')?' selected':'').">".date('F',mktime(0,0,0,$m,1))."</option>"; ?></select></div>
      <div class="form-group"><label class="form-label">Year</label><select class="form-select" id="exp-year"><?php for($y=date('Y');$y>=date('Y')-3;$y--)echo "<option>$y</option>"; ?></select></div>
    </div>
    <button class="submit-btn" onclick="doExport()">Download CSV</button>
  </div>
</div>

<div class="toast hidden" id="toast"></div>

<script>


document.addEventListener('DOMContentLoaded',loadGoals);

async function loadGoals(){
  try{const r=await fetch('api/goals.php?action=list');const j=await r.json();
  if(!j.success||!j.data.length){document.getElementById('goal-list').innerHTML='<div style="color:var(--muted);font-size:13px;padding:4px 0">No goals yet. Create one to start saving!</div>';return;}
  document.getElementById('goal-list').innerHTML=j.data.map(g=>{
    const pct=Math.min(100,parseFloat(g.progress_pct||0));
    const bc=g.is_achieved?'var(--success)':'var(--accent)';
    const dl=g.target_date?Math.max(0,Math.round((new Date(g.target_date)-new Date())/86400000)):null;
    return `<div class="goal-card">
      <div class="goal-top">
        <div><div style="display:flex;align-items:center;gap:8px"><span style="font-size:18px">${esc(g.icon)}</span><div><div class="goal-name">${esc(g.name)}</div><div class="goal-amounts">₹${fmtN(g.saved_amount)} of ₹${fmtN(g.target_amount)}${dl!==null?' · '+dl+' days left':''}</div></div></div></div>
        ${g.is_achieved?'<span class="achieved-badge">✓ Done!</span>':''}
      </div>
      <div class="goal-bar-bg"><div style="height:100%;border-radius:3px;background:${bc};width:${pct}%;transition:width .4s"></div></div>
      <div class="goal-pct">${pct.toFixed(0)}% saved</div>
      <div class="goal-actions">
        ${!g.is_achieved?`<button class="goal-add-btn" onclick="openFunds(${g.id})">+ Add funds</button>`:''}
        <button class="goal-del-btn" onclick="delGoal(${g.id})">Delete</button>
      </div>
    </div>`;
  }).join('');
  }catch(e){document.getElementById('goal-list').innerHTML='<div style="color:var(--muted);font-size:13px">Error loading goals.</div>';}
}

async function saveGoal(){
  const body={action:'add',name:document.getElementById('g-name').value,target_amount:document.getElementById('g-target').value,saved_amount:document.getElementById('g-saved').value||0,target_date:document.getElementById('g-date').value,icon:document.getElementById('g-icon').value||'🎯'};
  try{const r=await fetch('api/goals.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});const j=await r.json();
  if(j.success){closeModal('goal-modal');toast('Goal created! ✓');loadGoals();}else toast(j.message||'Error');}catch(e){toast('Error');}
}

function openFunds(id){document.getElementById('funds-goal-id').value=id;document.getElementById('funds-amt').value='';openModal('funds-modal');}
async function addFunds(){
  const id=document.getElementById('funds-goal-id').value;const amount=document.getElementById('funds-amt').value;
  try{const r=await fetch('api/goals.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'add_funds',id,amount})});const j=await r.json();
  if(j.success){closeModal('funds-modal');toast('Funds added! ✓');loadGoals();}else toast('Error');}catch(e){}
}
async function delGoal(id){if(!confirm('Delete this goal?'))return;try{const r=await fetch('api/goals.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'delete',id})});const j=await r.json();if(j.success){toast('Deleted');loadGoals();}}catch(e){}}

async function saveSetting(key,value){try{await fetch('api/settings.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({key,value})});toast('Saved ✓');}catch(e){}}

async function savePin(){
  const p=document.getElementById('pin-new').value;const c=document.getElementById('pin-confirm').value;
  if(p.length<4){toast('PIN must be 4 digits');return;}if(p!==c){toast('PINs do not match');return;}
  try{const r=await fetch('api/settings.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'set_pin',pin:p})});const j=await r.json();
  if(j.success){closeModal('pin-modal');toast('PIN set! ✓');}else toast(j.message||'Error');}catch(e){}
}

function doExport(){window.location.href=`api/reports.php?action=export_csv&month=${document.getElementById('exp-month').value}&year=${document.getElementById('exp-year').value}`;closeModal('export-modal');}
async function doLogout(){try{await fetch('api/auth.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'logout'})});}catch(e){}window.location.href='login.php';}

function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
function fmtN(v){return parseFloat(v||0).toLocaleString('en-IN',{maximumFractionDigits:0});}
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
