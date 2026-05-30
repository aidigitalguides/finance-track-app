<?php
// Handle Google OAuth error redirect
$oauthError = '';
if (!empty($_GET['error'])) {
    $oauthError = htmlspecialchars($_GET['error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Finance Track — Sign in</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/theme.css">
<style>
body{
  min-height:100vh;display:flex;align-items:center;justify-content:center;
  padding:20px;position:relative;overflow:hidden;
}

/* Animated background */
.bg-orbs{position:absolute;inset:0;pointer-events:none;overflow:hidden}
.bg-orb{
  position:absolute;border-radius:50%;filter:blur(80px);
  animation:floatOrb 8s ease-in-out infinite;
}
.bg-orb-1{width:400px;height:400px;background:rgba(124,111,255,.08);top:-100px;left:-100px;animation-delay:0s}
.bg-orb-2{width:300px;height:300px;background:rgba(167,139,250,.06);bottom:-50px;right:-50px;animation-delay:-3s}
.bg-orb-3{width:200px;height:200px;background:rgba(96,165,250,.05);top:50%;left:50%;transform:translate(-50%,-50%);animation-delay:-6s}
@keyframes floatOrb{0%,100%{transform:translateY(0) scale(1)}50%{transform:translateY(-20px) scale(1.05)}}

.grid-bg{
  position:absolute;inset:0;
  background-image:linear-gradient(var(--border) 1px,transparent 1px),linear-gradient(90deg,var(--border) 1px,transparent 1px);
  background-size:56px 56px;
  mask-image:radial-gradient(ellipse 80% 80% at center,black 0%,transparent 100%);
  pointer-events:none;
}

.wrap{width:100%;max-width:430px;position:relative;z-index:1}

/* Brand */
.brand{text-align:center;margin-bottom:28px}
.brand-icon{
  width:56px;height:56px;background:var(--grad-primary);
  border-radius:16px;display:inline-flex;align-items:center;justify-content:center;
  font-size:26px;color:#fff;font-family:var(--font-display);font-weight:800;
  box-shadow:var(--shadow-accent);margin-bottom:14px;
  position:relative;overflow:hidden;
}
.brand-icon::after{
  content:'';position:absolute;inset:0;
  background:linear-gradient(135deg,rgba(255,255,255,.2) 0%,transparent 50%);
}
.brand-name{font-family:var(--font-display);font-size:28px;font-weight:800;color:var(--text);line-height:1}
.brand-name span{
  background:var(--grad-primary);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}
.brand-tag{font-size:13px;color:var(--muted);margin-top:6px}

/* Card */
.auth-card{
  background:var(--surface);
  border:.5px solid var(--border2);
  border-radius:var(--r-lg);
  box-shadow:var(--shadow-lg),0 0 0 .5px var(--border);
  padding:28px;
  position:relative;overflow:hidden;
}
.auth-card::before{
  content:'';position:absolute;top:0;left:0;right:0;height:1px;
  background:linear-gradient(90deg,transparent,var(--accent-glow2),transparent);
}

/* Tabs */
.tabs{
  display:flex;background:var(--surface2);
  border-radius:var(--r-sm);padding:4px;margin-bottom:24px;gap:4px;
  border:.5px solid var(--border);
}
.tab-btn{
  flex:1;padding:9px;border:none;border-radius:8px;
  font-family:var(--font-body);font-size:13.5px;font-weight:600;
  cursor:pointer;transition:all .2s;background:transparent;color:var(--muted);
}
.tab-btn.active{
  background:var(--grad-primary);color:#fff;
  box-shadow:0 2px 8px rgba(124,111,255,.4);
}

/* Panels */
.panel{display:none}.panel.active{display:block}

/* Age group */
.age-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
.age-opt{
  padding:11px 6px;
  background:var(--surface2);border:.5px solid var(--border2);
  border-radius:var(--r-sm);text-align:center;cursor:pointer;
  transition:all .2s;position:relative;overflow:hidden;
}
.age-opt::before{content:'';position:absolute;inset:0;background:var(--grad-primary);opacity:0;transition:.2s}
.age-opt:hover,.age-opt.sel{border-color:var(--accent);background:var(--accent-glow2)}
.age-opt.sel{box-shadow:0 0 0 2px var(--accent-glow)}
.age-opt input{display:none}
.age-val{font-size:13px;font-weight:700;display:block;color:var(--text);font-family:var(--font-display)}
.age-lbl{font-size:10px;color:var(--muted);display:block;margin-top:3px}

/* Bottom stats */
.stats-strip{
  display:flex;justify-content:center;gap:28px;margin-top:20px;
  padding-top:16px;border-top:.5px solid var(--border);
}
.stat-item{text-align:center}
.stat-n{font-family:var(--font-display);font-size:16px;font-weight:800;color:var(--text)}
.stat-l{font-size:10px;color:var(--muted);margin-top:2px;font-weight:500}

.theme-wrap{position:absolute;top:20px;right:20px}

/* Trust badges */
.trust-badges{display:flex;align-items:center;justify-content:center;gap:16px;margin-top:16px;flex-wrap:wrap}
.trust-badge{display:flex;align-items:center;gap:5px;font-size:11px;color:var(--muted);font-weight:500}
.trust-badge span{font-size:14px}
</style>
</head>
<body>
<!-- Theme flash prevention -->
<script>
(function(){
  var t=localStorage.getItem('ft-theme')||'auto';
  if(t==='dark')document.documentElement.setAttribute('data-theme','dark');
  else if(t==='light')document.documentElement.setAttribute('data-theme','light');
  else document.documentElement.removeAttribute('data-theme');
})();
</script>

<div class="bg-orbs">
  <div class="bg-orb bg-orb-1"></div>
  <div class="bg-orb bg-orb-2"></div>
  <div class="bg-orb bg-orb-3"></div>
</div>
<div class="grid-bg"></div>
<div class="theme-wrap"><button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme"><span class="t-opt">🌓</span><span class="t-opt">☀️</span><span class="t-opt">🌙</span></button></div>

<div class="wrap">
  <div class="brand">
    <div class="brand-icon">₹</div>
    <div class="brand-name">Finance<span>Track</span></div>
    <p class="brand-tag">Smart money management for every Indian</p>
  </div>

  <div class="auth-card">
    <div class="tabs">
      <button class="tab-btn active" onclick="switchTab('login',this)">Sign in</button>
      <button class="tab-btn" onclick="switchTab('register',this)">Create account</button>
    </div>

    <div id="alert-box" class="alert"></div>

    <!-- LOGIN PANEL -->
    <div class="panel active" id="panel-login">
      <!-- Google OAuth button -->
      <a href="api/auth-google.php?action=login" class="btn-google" id="google-login-btn">
        <svg viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/><path fill="none" d="M0 0h48v48H0z"/></svg>
        Continue with Google
      </a>

      <div class="divider">or sign in with email</div>

      <div class="form-group">
        <label class="form-label">Email address</label>
        <input type="email" class="form-input" id="l-email" placeholder="you@example.com" autocomplete="email">
      </div>
      <div class="form-group">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:7px">
          <label class="form-label" style="margin-bottom:0">Password</label>
          <a href="forgot-password.php" style="font-size:12px;color:var(--accent-bright);text-decoration:none;font-weight:500">Forgot?</a>
        </div>
        <input type="password" class="form-input" id="l-pass" placeholder="Your password" autocomplete="current-password">
      </div>
      <button class="submit-btn" onclick="doLogin()" style="margin-top:4px">Sign in to Finance Track</button>
      <div style="text-align:center;font-size:13px;color:var(--muted);margin-top:16px">
        No account? <span style="color:var(--accent-bright);cursor:pointer;font-weight:600" onclick="switchTabByName('register')">Create one free →</span>
      </div>
    </div>

    <!-- REGISTER PANEL -->
    <div class="panel" id="panel-register">
      <a href="api/auth-google.php?action=register" class="btn-google">
        <svg viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/><path fill="none" d="M0 0h48v48H0z"/></svg>
        Sign up with Google
      </a>

      <div class="divider">or create account with email</div>

      <div class="form-group">
        <label class="form-label">Full name</label>
        <input type="text" class="form-input" id="r-name" placeholder="Your name" autocomplete="name">
      </div>
      <div class="form-group">
        <label class="form-label">Email address</label>
        <input type="email" class="form-input" id="r-email" placeholder="you@example.com" autocomplete="email">
      </div>
      <div class="form-group">
        <label class="form-label">Password <span style="font-size:10px;text-transform:none;color:var(--muted);font-weight:400">(min 8 chars)</span></label>
        <input type="password" class="form-input" id="r-pass" placeholder="Create a strong password" autocomplete="new-password" oninput="checkPwdStrength(this.value)">
        <div class="pwd-strength" id="pwd-strength" style="display:none;margin-top:8px">
          <div style="height:4px;background:var(--surface3);border-radius:999px;overflow:hidden">
            <div id="pwd-bar" style="height:100%;border-radius:999px;transition:width .3s,background .3s;width:0"></div>
          </div>
          <div id="pwd-label" style="font-size:11px;color:var(--muted);margin-top:4px"></div>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Your age group</label>
        <div class="age-grid">
          <label class="age-opt sel" onclick="selAge(this,1)">
            <input type="radio" name="age" value="1" checked>
            <span class="age-val">16–25</span><span class="age-lbl">Student</span>
          </label>
          <label class="age-opt" onclick="selAge(this,2)">
            <input type="radio" name="age" value="2">
            <span class="age-val">25–45</span><span class="age-lbl">Working</span>
          </label>
          <label class="age-opt" onclick="selAge(this,3)">
            <input type="radio" name="age" value="3">
            <span class="age-val">45–60+</span><span class="age-lbl">Family</span>
          </label>
        </div>
      </div>
      <button class="submit-btn" onclick="doRegister()" style="margin-top:4px">Create my account — Free</button>
      <div style="text-align:center;font-size:13px;color:var(--muted);margin-top:16px">
        Already have an account? <span style="color:var(--accent-bright);cursor:pointer;font-weight:600" onclick="switchTabByName('login')">Sign in →</span>
      </div>
    </div>
  </div><!-- /auth-card -->

  <div class="trust-badges">
    <div class="trust-badge"><span>🔒</span> Bank-level secure</div>
    <div class="trust-badge"><span>🇮🇳</span> Built for India</div>
    <div class="trust-badge"><span>✅</span> 100% Free</div>
  </div>

  <div class="stats-strip">
    <div class="stat-item"><div class="stat-n">₹ 0</div><div class="stat-l">Bank access needed</div></div>
    <div class="stat-item"><div class="stat-n">100%</div><div class="stat-l">Your data</div></div>
    <div class="stat-item"><div class="stat-n">Safe</div><div class="stat-l">Always private</div></div>
  </div>
</div><!-- /wrap -->

<script>
// ── THEME ─────────────────────────────────────────────────────
const _themes=['auto','light','dark'];
let _idx=_themes.indexOf(localStorage.getItem('ft-theme')||'auto');
if(_idx<0)_idx=0;
function applyTheme(){
  const t=_themes[_idx];
  if(t==='dark')document.documentElement.setAttribute('data-theme','dark');
  else if(t==='light')document.documentElement.setAttribute('data-theme','light');
  else document.documentElement.removeAttribute('data-theme');
  localStorage.setItem('ft-theme',t);
  document.querySelectorAll('.theme-toggle .t-opt').forEach((o,i)=>{
    o.style.background=i===_idx?'var(--accent)':'';
    o.style.color=i===_idx?'#fff':'';
    o.style.borderRadius=i===_idx?'999px':'';
    o.style.padding=i===_idx?'5px 10px':'';
  });
}
function toggleTheme(){_idx=(_idx+1)%_themes.length;applyTheme();}
applyTheme();

// ── TABS ──────────────────────────────────────────────────────
let selAgeVal=1;
function selAge(el,v){document.querySelectorAll('.age-opt').forEach(o=>o.classList.remove('sel'));el.classList.add('sel');selAgeVal=v;}
function switchTab(name,btn){
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  document.querySelectorAll('.panel').forEach(p=>p.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('panel-'+name).classList.add('active');
  hideAlert();
}
function switchTabByName(name){switchTab(name,document.querySelectorAll('.tab-btn')[name==='login'?0:1]);}

// ── ALERT ─────────────────────────────────────────────────────
function showAlert(msg,type='error'){
  const b=document.getElementById('alert-box');
  b.textContent=msg;b.className='alert '+type+' show';b.style.display='flex';
}
function hideAlert(){const b=document.getElementById('alert-box');b.style.display='none';b.className='alert';}

// ── PASSWORD STRENGTH ─────────────────────────────────────────
function checkPwdStrength(v){
  const wrap=document.getElementById('pwd-strength');
  const bar=document.getElementById('pwd-bar');
  const lbl=document.getElementById('pwd-label');
  if(!v){wrap.style.display='none';return;}
  wrap.style.display='block';
  let s=0;
  if(v.length>=8)s++;if(v.length>=12)s++;
  if(/[A-Z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;
  const levels=[
    {label:'Too short',color:'var(--danger)',w:'15%'},
    {label:'Weak',color:'var(--danger)',w:'30%'},
    {label:'Fair',color:'var(--warning)',w:'55%'},
    {label:'Good',color:'var(--info)',w:'75%'},
    {label:'Strong',color:'var(--success)',w:'100%'},
    {label:'Very strong 💪',color:'var(--success)',w:'100%'},
  ];
  const l=levels[Math.min(s,5)];
  bar.style.width=l.w;bar.style.background=l.color;
  lbl.textContent=l.label;lbl.style.color=l.color;
}

// ── AUTH ──────────────────────────────────────────────────────
async function doLogin(){
  hideAlert();
  const email=document.getElementById('l-email').value.trim();
  const pass=document.getElementById('l-pass').value;
  if(!email||!pass){showAlert('Please fill in all fields');return;}
  const btn=document.querySelector('#panel-login .submit-btn');
  btn.disabled=true;btn.textContent='Signing in…';
  try{
    const r=await fetch('api/auth.php',{method:'POST',headers:{'Content-Type':'application/json'},
      body:JSON.stringify({action:'login',email,password:pass})});
    const j=await r.json();
    if(j.success){showAlert('Welcome back! Redirecting…','success');setTimeout(()=>location.href='dashboard.php',700);}
    else{showAlert(j.message||'Invalid email or password');btn.disabled=false;btn.textContent='Sign in to Finance Track';}
  }catch(e){showAlert('Network error. Please try again.');btn.disabled=false;btn.textContent='Sign in to Finance Track';}
}

async function doRegister(){
  hideAlert();
  const name=document.getElementById('r-name').value.trim();
  const email=document.getElementById('r-email').value.trim();
  const pass=document.getElementById('r-pass').value;
  if(!name||!email||!pass){showAlert('Please fill in all fields');return;}
  if(pass.length<8){showAlert('Password must be at least 8 characters');return;}
  const btn=document.querySelector('#panel-register .submit-btn');
  btn.disabled=true;btn.textContent='Creating account…';
  try{
    const r=await fetch('api/auth.php',{method:'POST',headers:{'Content-Type':'application/json'},
      body:JSON.stringify({action:'register',name,email,password:pass,age_group:selAgeVal})});
    const j=await r.json();
    if(j.success){showAlert('Account created! Setting up…','success');setTimeout(()=>location.href=(j.redirect||'onboarding.php'),800);}
    else{showAlert(j.message||'Registration failed');btn.disabled=false;btn.textContent='Create my account — Free';}
  }catch(e){showAlert('Network error. Please try again.');btn.disabled=false;btn.textContent='Create my account — Free';}
}

document.addEventListener('keydown',e=>{
  if(e.key!=='Enter')return;
  const loginActive=document.getElementById('panel-login').classList.contains('active');
  if(loginActive)doLogin();else doRegister();
});
</script>
</body>
</html>
