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
<title>Reports — Finance Track</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<link rel="stylesheet" href="assets/css/theme.css">
<style>
.seg{display:flex;background:var(--surface2);border-radius:var(--r-sm);padding:4px;gap:4px;margin-bottom:16px}
.seg-btn{flex:1;padding:9px;border:none;border-radius:6px;font-family:var(--font-body);font-size:13px;font-weight:500;cursor:pointer;background:transparent;color:var(--muted);transition:all .2s}
.seg-btn.active{background:var(--surface);color:var(--text);border:.5px solid var(--border2);box-shadow:var(--shadow)}
.month-btn{width:30px;height:30px;background:var(--surface2);border:.5px solid var(--border2);border-radius:var(--r-xs);color:var(--text);font-size:15px;cursor:pointer;display:flex;align-items:center;justify-content:center}
.month-label{font-family:var(--font-display);font-size:14px;font-weight:600;min-width:110px;text-align:center}
.big-stat{background:var(--surface);border:.5px solid var(--border);border-radius:var(--r);padding:16px;box-shadow:var(--shadow)}
.big-stat .lbl{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px}
.big-stat .val{font-family:var(--font-display);font-size:22px;font-weight:700}
.chart-card{background:var(--surface);border:.5px solid var(--border);border-radius:var(--r);padding:16px;box-shadow:var(--shadow)}
.chart-wrap{position:relative;height:200px}
.cat-row{display:flex;align-items:center;gap:10px;margin-bottom:8px}
.cat-dot{width:10px;height:10px;border-radius:3px;flex-shrink:0}
.cat-name{flex:1;font-size:13px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.cat-bar-bg{height:4px;background:var(--surface2);border-radius:2px;overflow:hidden;flex:1}
.cat-bar-fill{height:100%;border-radius:2px}
.cat-amt{font-family:var(--font-display);font-size:13px;font-weight:600;min-width:60px;text-align:right}
.cat-pct{font-size:11px;color:var(--muted);min-width:32px;text-align:right}
.yt-wrap{background:var(--surface);border:.5px solid var(--border);border-radius:var(--r);overflow:hidden;box-shadow:var(--shadow)}
.yt-hdr{display:grid;grid-template-columns:80px 1fr 1fr 1fr;padding:10px 14px;background:var(--surface2);font-size:11px;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em}
.yt-row{display:grid;grid-template-columns:80px 1fr 1fr 1fr;padding:10px 14px;border-top:.5px solid var(--border);font-size:13px;align-items:center}
.yt-row:hover{background:var(--surface2)}
.yt-row.current{background:var(--accent-glow)}
.yt-totals{display:grid;grid-template-columns:80px 1fr 1fr 1fr;padding:12px 14px;background:var(--surface2);border-top:.5px solid var(--border2);font-size:14px;font-weight:700;font-family:var(--font-display)}
.export-btn{width:100%;padding:11px;background:var(--surface);border:.5px solid var(--border2);border-radius:var(--r-sm);color:var(--muted);font-size:13px;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:8px;font-family:var(--font-body);margin-top:14px}
.export-btn:hover{background:var(--surface2);color:var(--text)}
.insight-card{background:linear-gradient(135deg,var(--accent-glow),transparent);border:.5px solid var(--accent);border-radius:var(--r);padding:16px;margin-bottom:14px}
.insight-item{display:flex;align-items:flex-start;gap:10px;margin-bottom:10px}
.insight-item:last-child{margin-bottom:0}
.insight-icon{font-size:20px;flex-shrink:0}
.insight-text{font-size:13px;color:var(--muted);line-height:1.5}
.insight-text strong{color:var(--text)}
@media(min-width:900px){
  .stats-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:16px}
  .report-cols{display:grid;grid-template-columns:1fr 320px;gap:16px;align-items:start}
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
      <a class="sidebar-item active" href="reports.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg><span>Reports</span></a>
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
      <div><div style="font-size:12px;color:var(--muted);margin-bottom:2px">Your financial summary</div><div class="desktop-page-title">Reports</div></div>
      <div style="display:flex;align-items:center;gap:10px">
        <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme" aria-label="Toggle theme"><span class="t-opt">🌓</span><span class="t-opt">☀️</span><span class="t-opt">🌙</span></button>
      </div>
    </div>

    <div class="main">
      <div class="seg">
        <button class="seg-btn active" onclick="switchTab('monthly',this)">Monthly</button>
        <button class="seg-btn" onclick="switchTab('yearly',this)">Yearly</button>
        <button class="seg-btn" onclick="switchTab('breakdown',this)">Categories</button>
        <button class="seg-btn" onclick="switchTab('insights',this)">Insights</button>
      </div>

      <!-- MONTHLY -->
      <div id="tab-monthly">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
          <button class="month-btn" onclick="prevM()">&#8592;</button>
          <span class="month-label" id="ml-monthly"></span>
          <button class="month-btn" onclick="nextM()">&#8594;</button>
        </div>
        <div class="report-cols">
          <div>
            <div class="stats-grid" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:14px">
              <div class="big-stat"><div class="lbl">Income</div><div class="val" id="m-inc" style="color:var(--success)">—</div></div>
              <div class="big-stat"><div class="lbl">Spent</div><div class="val" id="m-exp" style="color:var(--danger)">—</div></div>
              <div class="big-stat"><div class="lbl">Saved</div><div class="val" id="m-sav" style="color:var(--info)">—</div><div style="font-size:11px;color:var(--muted);margin-top:4px" id="m-rate"></div></div>
            </div>
            <div class="chart-card" style="margin-bottom:14px">
              <div style="font-size:13px;font-weight:600;font-family:var(--font-display);margin-bottom:12px">Spending trend (6 months)</div>
              <div class="chart-wrap"><canvas id="trend-chart"></canvas></div>
            </div>
            <button class="export-btn" onclick="exportCSV()">
              <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
              Download CSV (this month)
            </button>
          </div>
          <div>
            <div class="chart-card">
              <div style="font-size:13px;font-weight:600;font-family:var(--font-display);margin-bottom:12px">Spending breakdown</div>
              <div style="position:relative;height:200px"><canvas id="donut-m"></canvas></div>
              <div id="donut-m-legend" style="margin-top:12px"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- YEARLY -->
      <div id="tab-yearly" style="display:none">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
          <button class="month-btn" onclick="prevY()">&#8592;</button>
          <span class="month-label" id="ml-yearly" style="min-width:60px"></span>
          <button class="month-btn" onclick="nextY()">&#8594;</button>
        </div>
        <div class="chart-card" style="margin-bottom:14px">
          <div style="font-size:13px;font-weight:600;font-family:var(--font-display);margin-bottom:12px">Monthly income vs expense</div>
          <div class="chart-wrap"><canvas id="yearly-chart"></canvas></div>
        </div>
        <div class="yt-wrap">
          <div class="yt-hdr"><span>Month</span><span>Income</span><span>Spent</span><span>Saved</span></div>
          <div id="year-rows"></div>
          <div class="yt-totals" id="year-totals"></div>
        </div>
        <button class="export-btn" onclick="window.location.href=`api/reports.php?action=export_csv&month=0&year=${curYear}`">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
          Download full year CSV
        </button>
      </div>

      <!-- BREAKDOWN -->
      <div id="tab-breakdown" style="display:none">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
          <button class="month-btn" onclick="prevMB()">&#8592;</button>
          <span class="month-label" id="ml-breakdown"></span>
          <button class="month-btn" onclick="nextMB()">&#8594;</button>
        </div>
        <div class="chart-card" style="margin-bottom:14px">
          <div style="font-size:13px;font-weight:600;font-family:var(--font-display);margin-bottom:12px">Spending by category</div>
          <div style="position:relative;height:240px"><canvas id="donut-b"></canvas></div>
        </div>
        <div id="cat-breakdown"></div>
      </div>

      <!-- INSIGHTS -->
      <div id="tab-insights" style="display:none">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
          <button class="month-btn" onclick="prevMI()">&#8592;</button>
          <span class="month-label" id="ml-insights"></span>
          <button class="month-btn" onclick="nextMI()">&#8594;</button>
        </div>
        <div class="insight-card" id="insights-content">
          <div style="text-align:center;padding:20px;color:var(--muted)">Loading insights…</div>
        </div>
      </div>
    </div>

    <nav class="bottom-nav">
      <a class="nav-item" href="dashboard.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg><span>Dashboard</span></a>
      <a class="nav-item" href="transactions.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg><span>Transactions</span></a>
      <a class="nav-item" href="budget.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg><span>Budgets</span></a>
      <a class="nav-item active" href="reports.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg><span>Reports</span></a>
      <a class="nav-item" href="profile.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg><span>Profile</span></a>
    </nav>
  </div>
</div>

<script>


const MNF=['January','February','March','April','May','June','July','August','September','October','November','December'];
const MNS=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const PC=['#7C3AED','#F59E0B','#06B6D4','#10B981','#E24B4A','#378ADD','#F472B6','#34D399'];
let cm=new Date().getMonth()+1,cy=new Date().getFullYear();
let cmB=cm,cyB=cy,cmI=cm,cyI=cy,curYear=new Date().getFullYear();
let trendC=null,yearlyC=null,donutMC=null,donutBC=null;
let activeTab='monthly';

document.addEventListener('DOMContentLoaded',()=>{
  updAllLabels();
  loadMonthly();
});

function switchTab(tab,btn){
  activeTab=tab;
  document.querySelectorAll('.seg-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  ['monthly','yearly','breakdown','insights'].forEach(t=>document.getElementById('tab-'+t).style.display=t===tab?'':'none');
  if(tab==='yearly')loadYearly();
  else if(tab==='breakdown')loadBreakdown();
  else if(tab==='insights')loadInsights();
}

function prevM(){cm--;if(cm<1){cm=12;cy--;}updAllLabels();loadMonthly();}
function nextM(){const n=new Date();if(cy===n.getFullYear()&&cm>=n.getMonth()+1)return;cm++;if(cm>12){cm=1;cy++;}updAllLabels();loadMonthly();}
function prevY(){curYear--;document.getElementById('ml-yearly').textContent=curYear;loadYearly();}
function nextY(){if(curYear>=new Date().getFullYear())return;curYear++;document.getElementById('ml-yearly').textContent=curYear;loadYearly();}
function prevMB(){cmB--;if(cmB<1){cmB=12;cyB--;}document.getElementById('ml-breakdown').textContent=MNF[cmB-1]+' '+cyB;loadBreakdown();}
function nextMB(){const n=new Date();if(cyB===n.getFullYear()&&cmB>=n.getMonth()+1)return;cmB++;if(cmB>12){cmB=1;cyB++;}document.getElementById('ml-breakdown').textContent=MNF[cmB-1]+' '+cyB;loadBreakdown();}
function prevMI(){cmI--;if(cmI<1){cmI=12;cyI--;}document.getElementById('ml-insights').textContent=MNF[cmI-1]+' '+cyI;loadInsights();}
function nextMI(){const n=new Date();if(cyI===n.getFullYear()&&cmI>=n.getMonth()+1)return;cmI++;if(cmI>12){cmI=1;cyI++;}document.getElementById('ml-insights').textContent=MNF[cmI-1]+' '+cyI;loadInsights();}
function updAllLabels(){['ml-monthly','ml-breakdown','ml-insights'].forEach(id=>{const e=document.getElementById(id);if(e)e.textContent=MNF[cm-1]+' '+cy;});document.getElementById('ml-yearly').textContent=curYear;}

async function loadMonthly(){
  try{
    const[sr,tr]=await Promise.all([fetch(`api/reports.php?action=monthly&month=${cm}&year=${cy}`),fetch('api/transactions.php?action=overview')]);
    const sj=await sr.json();const tj=await tr.json();
    if(sj.success){const d=sj.data.summary;document.getElementById('m-inc').textContent=fmt(d.income);document.getElementById('m-exp').textContent=fmt(d.expense);document.getElementById('m-sav').textContent=fmt(d.savings);document.getElementById('m-rate').textContent=d.savings_rate+'% savings rate';}
    if(tj.success){
      const bk={};tj.data.forEach(r=>{const k=r.year+'-'+String(r.month).padStart(2,'0');if(!bk[k])bk[k]={income:0,expense:0};bk[k][r.type]=parseFloat(r.total);});
      const keys=Object.keys(bk).sort().slice(-6);
      if(trendC)trendC.destroy();
      trendC=new Chart(document.getElementById('trend-chart'),{type:'line',data:{labels:keys.map(k=>MNS[+k.split('-')[1]-1]),datasets:[{label:'Income',data:keys.map(k=>bk[k].income||0),borderColor:'#10B981',backgroundColor:'rgba(16,185,129,.1)',tension:.3,fill:true,pointBackgroundColor:'#10B981',pointRadius:4},{label:'Expense',data:keys.map(k=>bk[k].expense||0),borderColor:'#E24B4A',backgroundColor:'rgba(226,75,74,.1)',tension:.3,fill:true,pointBackgroundColor:'#E24B4A',pointRadius:4}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>' ₹'+c.raw.toLocaleString('en-IN')}}},scales:{x:{grid:{color:'rgba(128,128,128,.1)'},ticks:{color:'#888',font:{size:10}}},y:{grid:{color:'rgba(128,128,128,.1)'},ticks:{color:'#888',font:{size:10},callback:v=>v>=100000?(v/100000).toFixed(0)+'L':v>=1000?(v/1000).toFixed(0)+'K':v}}}}});
    }
    if(sj.success&&sj.data.breakdown.length){
      const data=sj.data.breakdown.slice(0,6);const total=data.reduce((s,d)=>s+parseFloat(d.total),0);
      if(donutMC)donutMC.destroy();
      donutMC=new Chart(document.getElementById('donut-m'),{type:'doughnut',data:{labels:data.map(d=>d.name),datasets:[{data:data.map(d=>d.total),backgroundColor:PC,borderColor:'var(--surface)',borderWidth:3,hoverOffset:6}]},options:{responsive:true,maintainAspectRatio:false,cutout:'65%',plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>' ₹'+parseFloat(c.raw).toLocaleString('en-IN')}}}}});
      document.getElementById('donut-m-legend').innerHTML=data.map((d,i)=>{const p=total>0?(parseFloat(d.total)/total*100):0;return `<div style="display:flex;align-items:center;justify-content:space-between;font-size:12px;margin-bottom:6px"><div style="display:flex;align-items:center;gap:6px"><span style="width:8px;height:8px;border-radius:2px;background:${PC[i]};display:inline-block"></span><span style="color:var(--muted)">${esc(d.icon)} ${esc(d.name)}</span></div><span style="font-weight:600">${fmt(d.total)} (${p.toFixed(0)}%)</span></div>`;}).join('');
    }
  }catch(e){}
}
function exportCSV(){window.location.href=`api/reports.php?action=export_csv&month=${cm}&year=${cy}`;}

async function loadYearly(){
  try{const r=await fetch(`api/reports.php?action=yearly&year=${curYear}`);const j=await r.json();if(!j.success)return;
  const{months,totals}=j.data;const nowM=new Date().getMonth()+1,nowY=new Date().getFullYear();
  if(yearlyC)yearlyC.destroy();
  yearlyC=new Chart(document.getElementById('yearly-chart'),{type:'bar',data:{labels:months.map(m=>MNS[m.month-1]),datasets:[{label:'Income',data:months.map(m=>m.income),backgroundColor:'rgba(16,185,129,.25)',borderColor:'#10B981',borderWidth:1.5,borderRadius:5},{label:'Expense',data:months.map(m=>m.expense),backgroundColor:'rgba(226,75,74,.3)',borderColor:'#E24B4A',borderWidth:1.5,borderRadius:5}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>' ₹'+c.raw.toLocaleString('en-IN')}}},scales:{x:{grid:{color:'rgba(128,128,128,.1)'},ticks:{color:'#888',font:{size:10}}},y:{grid:{color:'rgba(128,128,128,.1)'},ticks:{color:'#888',font:{size:10},callback:v=>v>=100000?(v/100000).toFixed(0)+'L':v>=1000?(v/1000).toFixed(0)+'K':v}}}}});
  document.getElementById('year-rows').innerHTML=months.map(m=>{const cur=m.month===nowM&&m.year===nowY;const sc=m.savings>=0?'var(--success)':'var(--danger)';return `<div class="yt-row${cur?' current':''}"><span style="color:var(--muted)">${MNS[m.month-1]}</span><span style="color:var(--success)">${m.income>0?fmt(m.income):'—'}</span><span style="color:var(--danger)">${m.expense>0?fmt(m.expense):'—'}</span><span style="color:${sc}">${m.income>0||m.expense>0?fmt(m.savings):'—'}</span></div>`;}).join('');
  const ts=totals.savings>=0?'var(--success)':'var(--danger)';
  document.getElementById('year-totals').innerHTML=`<span>Total</span><span style="color:var(--success)">${fmt(totals.income)}</span><span style="color:var(--danger)">${fmt(totals.expense)}</span><span style="color:${ts}">${fmt(totals.savings)}</span>`;
  }catch(e){}
}

async function loadBreakdown(){
  try{const r=await fetch(`api/transactions.php?action=breakdown&month=${cmB}&year=${cyB}`);const j=await r.json();
  if(!j.success||!j.data.length){document.getElementById('cat-breakdown').innerHTML='<div style="color:var(--muted);font-size:13px;padding:8px 0">No expense data this month.</div>';return;}
  const data=j.data;const total=data.reduce((s,d)=>s+parseFloat(d.total),0);
  if(donutBC)donutBC.destroy();
  donutBC=new Chart(document.getElementById('donut-b'),{type:'doughnut',data:{labels:data.map(d=>d.name),datasets:[{data:data.map(d=>d.total),backgroundColor:PC,borderColor:'var(--surface)',borderWidth:3,hoverOffset:6}]},options:{responsive:true,maintainAspectRatio:false,cutout:'65%',plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>' ₹'+parseFloat(c.raw).toLocaleString('en-IN')}}}}});
  document.getElementById('cat-breakdown').innerHTML=data.map((d,i)=>{const p=total>0?(parseFloat(d.total)/total*100):0;return `<div class="cat-row"><div class="cat-dot" style="background:${PC[i%PC.length]}"></div><div class="cat-name">${esc(d.icon)} ${esc(d.name)}</div><div class="cat-bar-bg"><div class="cat-bar-fill" style="width:${p}%;background:${PC[i%PC.length]}"></div></div><div class="cat-pct">${p.toFixed(0)}%</div><div class="cat-amt">${fmt(d.total)}</div></div>`;}).join('');
  }catch(e){}
}

async function loadInsights(){
  try{
    const[hr,sr,br]=await Promise.all([fetch(`api/health.php?month=${cmI}&year=${cyI}`),fetch(`api/transactions.php?action=summary&month=${cmI}&year=${cyI}`),fetch(`api/transactions.php?action=breakdown&month=${cmI}&year=${cyI}`)]);
    const hj=await hr.json();const sj=await sr.json();const bj=await br.json();
    const items=[];
    if(sj.success){const d=sj.data;
      if(d.savings_rate>=30)items.push({icon:'🎉',text:`<strong>Excellent savings rate!</strong> You saved ${d.savings_rate}% of your income this month — that's above the recommended 20%.`});
      else if(d.savings_rate>=15)items.push({icon:'✅',text:`<strong>Good savings rate.</strong> You saved ${d.savings_rate}% this month. Try to push it above 20% next month.`});
      else if(d.savings_rate>0)items.push({icon:'⚠️',text:`<strong>Low savings rate.</strong> You only saved ${d.savings_rate}% this month. Aim to cut back on discretionary spending.`});
      else items.push({icon:'🚨',text:`<strong>No savings this month.</strong> Your expenses exceeded your income. Review your spending immediately.`});
      if(d.income===0)items.push({icon:'💡',text:`<strong>Add your income.</strong> We can't calculate your savings rate without knowing your income. Add a salary or income transaction.`});
    }
    if(bj.success&&bj.data.length){
      const top=bj.data[0];items.push({icon:'📊',text:`<strong>Top spending category:</strong> ${top.icon} ${top.name} at ${fmt(top.total)}. This is your biggest expense this month.`});
    }
    if(hj.success){const score=hj.data.score;
      if(score>=80)items.push({icon:'💪',text:`<strong>Financial health score: ${score}/100.</strong> Your finances are in great shape. Keep up the discipline!`});
      else if(score>=60)items.push({icon:'📈',text:`<strong>Financial health score: ${score}/100.</strong> You're doing well but there's room to improve. Focus on savings and reducing debt.`});
      else items.push({icon:'📉',text:`<strong>Financial health score: ${score}/100.</strong> Your score needs attention. Set a budget and try to reduce unnecessary expenses.`});
    }
    items.push({icon:'💡',text:`<strong>Tip:</strong> Track every expense, even small ones. They add up quickly — ₹50 here and ₹100 there can easily become ₹3,000 a month.`});
    document.getElementById('insights-content').innerHTML=items.map(i=>`<div class="insight-item"><span class="insight-icon">${i.icon}</span><div class="insight-text">${i.text}</div></div>`).join('');
  }catch(e){document.getElementById('insights-content').innerHTML='<div style="color:var(--muted);font-size:13px;padding:8px">Could not load insights.</div>';}
}

function fmt(v){return '₹'+Math.abs(parseFloat(v)||0).toLocaleString('en-IN',{maximumFractionDigits:0});}
function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}



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
