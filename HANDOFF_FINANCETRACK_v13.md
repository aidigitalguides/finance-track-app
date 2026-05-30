# Finance Track — Complete Project Handoff Document
## Version 1.0 (v13) | May 2026 | Domain: finance-track.app

---

## 1. PROJECT OVERVIEW

**App Name:** Finance Track  
**Website:** https://finance-track.app (marketing site — WordPress)  
**App URL:** https://app.finance-track.app (the PHP app)  
**Previous name:** PaisaTrack (renamed for clarity + no conflicts)  
**Your name:** Arshad (MD Arshad) | Delhi NCR | Digital Marketing Executive

### What it is
A personal finance web app for Indians — expense tracking, EMI management, credit cards, budgets, savings goals, and a financial health score. Works for students tracking ₹50 pocket money all the way to families managing home loans and tax planning.

**Not a mobile app** — it's a mobile-responsive web app. Runs in any browser.

---

## 2. TECH STACK

| Layer | Technology |
|---|---|
| Hosting | Hostinger Business Plan |
| Server | PHP 8.2 + MySQL 8.0 |
| Frontend | Vanilla HTML + CSS + JS (no framework) |
| Charts | Chart.js 4.4.1 |
| Fonts | Syne (display) + DM Sans (body) + Inter (numbers) |
| Email | PHP mail() via Hostinger SMTP |
| Cron | Hostinger hPanel cron jobs |
| Auth | Session-based + Google OAuth 2.0 |
| CDN | None (all self-hosted) |

---

## 3. DOMAIN & HOSTING STRUCTURE

```
finance-track.app          →  WordPress (marketing site + blog)
app.finance-track.app      →  PHP app (this codebase)
```

### Hostinger setup
- You already have WordPress running on finance-track.app (per your records)
- Add the PHP app as a subdomain: hPanel → Domains → Add subdomain → `app`
- Upload the `finance-track/` folder contents to the subdomain's public_html

---

## 4. FILE STRUCTURE (38 + 2 new files)

```
/                          ← root of app.finance-track.app
├── config.php             ← ALL settings (DB, Google OAuth, email)
├── index.php              ← Redirects to login or dashboard
├── login.php              ← Sign in / Register + Google OAuth
├── onboarding.php         ← 3-step first-time setup
├── dashboard.php          ← Main overview
├── transactions.php       ← Transaction list + filters
├── budget.php             ← Monthly budget limits
├── emis.php               ← EMI + credit card tracker
├── reports.php            ← Monthly/yearly/category reports
├── profile.php            ← Goals, settings, PIN lock, export
├── forgot-password.php    ← Password reset request
├── reset-password.php     ← Password reset with token
│
├── api/
│   ├── auth.php           ← Email login/register/logout
│   ├── auth-google.php    ← [NEW] Google OAuth callback handler
│   ├── transactions.php
│   ├── budgets.php
│   ├── categories.php
│   ├── emis.php
│   ├── goals.php
│   ├── health.php
│   ├── income.php
│   ├── password-reset.php
│   ├── reports.php
│   ├── settings.php
│   └── dues.php
│
├── includes/
│   ├── Auth.php           ← Session, login, register, bcrypt, PIN
│   ├── Database.php       ← PDO wrapper
│   ├── Transaction.php    ← Transaction operations
│   ├── HealthScore.php    ← 0-100 score calculator
│   └── Mailer.php         ← All email templates
│
├── assets/
│   └── css/
│       └── theme.css      ← [ENHANCED v13] Complete design system
│
├── cron/
│   ├── send-reminders.php ← Daily 9AM reminders
│   └── auto-transactions.php ← Daily midnight auto-transactions
│
├── schema.sql             ← Full database schema
├── schema_patch_v2.sql    ← Existing patches
├── schema_patch_google.sql ← [NEW] Google OAuth columns
└── SETUP_GUIDE.md         ← Step-by-step deployment guide
```

---

## 5. WHAT'S NEW IN v13

### 5.1 Rebranding: PaisaTrack → Finance Track
- All PHP files updated: app name, session names, email references
- config.php now has `APP_NAME = 'Finance Track'`
- Domain references updated to `app.finance-track.app`

### 5.2 Google OAuth Login/Register
- New file: `api/auth-google.php` — handles the full OAuth flow
- New columns in `users` table: `google_id` (VARCHAR 128) + `avatar_url` (VARCHAR 500)
- `password_hash` is now nullable (Google users have no password)
- Google avatar stored and displayed in sidebar
- Smart linking: if a user registers via email first, then tries Google, accounts are auto-linked
- Error messages redirected back to login page via URL param

### 5.3 Enhanced Design System (theme.css)
- New gradient system: `--grad-primary`, `--grad-success`, `--grad-danger` etc.
- Glassmorphism: `--glass`, `--glass-border` variables for blur effects
- Animated login page: floating orbs background
- Password strength meter on registration
- Google OAuth button with proper SVG logo
- Improved hover states with subtle `translateY` animations
- Better shadow system: `--shadow-accent` for accent-colored glows
- Updated color palette: deeper dark mode (`#07080f` bg), more vibrant accents
- Trust badges on login page
- Scrollbar styling
- `::selection` color for text selection

---

## 6. DESIGN SYSTEM

### Dark Theme (default)
```css
--bg:          #07080f    (deeper dark)
--surface:     #0e0f1a
--surface2:    #161728
--accent:      #7c6fff    (violet-indigo)
--success:     #10b981    (emerald)
--danger:      #ef4444    (red)
--font-display: Syne 800
--font-body:    DM Sans 400/500/600
--font-numbers: Inter 400-700
```

### Light Theme
```css
--bg:          #f0f2f9
--surface:     #ffffff
--accent:      #5b4ee8
--sidebar-bg:  #1a1c2e  (dark sidebar always)
```

### Gradient Variables (new in v13)
```css
--grad-primary: linear-gradient(135deg, #7c6fff 0%, #a78bfa 100%)
--grad-success: linear-gradient(135deg, #10b981 0%, #34d399 100%)
--grad-danger:  linear-gradient(135deg, #ef4444 0%, #f87171 100%)
--grad-brand:   linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a78bfa 100%)
```

---

## 7. DATABASE — ALL TABLES (15 total after patches)

| Table | Purpose |
|---|---|
| users | Core user data + google_id + avatar_url (new v13) |
| sessions | Token-based auth |
| categories | 19 system + custom user categories |
| transactions | Core transaction table |
| income_sources | Recurring income setup |
| recurring_transactions | Auto-repeat logic |
| cards | Credit/debit cards |
| emis | Loan EMI tracker |
| budgets | Monthly limits per category |
| savings_goals | Target + saved + target date |
| reminders | EMI/card/bill reminders |
| user_settings | Preferences, notifications |
| financial_health_log | Monthly score snapshots |
| password_resets | Secure token reset |
| email_notifications | Email send log |
| oauth_log | [NEW v13] OAuth event log |

---

## 8. STEP-BY-STEP DEPLOYMENT GUIDE

### Phase 1: Hostinger Setup

**A. Create the subdomain**
1. hPanel → Domains → Subdomains
2. Add subdomain: `app` on `finance-track.app`
3. This creates a new folder (usually `public_html/app` or a separate path)

**B. Create the database**
1. hPanel → Databases → MySQL Databases
2. Create database: `financetrack_db`
3. Create user: `financetrack_user` with a strong password
4. Assign user to database with ALL privileges
5. Note: database name and user name on Hostinger usually have your account prefix (e.g. `u429732427_financetrack_db`)

**C. Upload files**
1. Extract the zip locally
2. Upload ALL files inside `finance-track/` to your subdomain's document root
3. NOT the `finance-track/` folder itself — its CONTENTS

---

### Phase 2: Configuration

**Edit `config.php`** (this is the only file you need to touch):

```php
define('DB_NAME',    'u429732427_financetrack_db');  // exact name from hPanel
define('DB_USER',    'u429732427_financetrack_user'); // exact user from hPanel
define('DB_PASS',    'YourActualPassword123!');       // password you set

define('APP_URL',    'https://app.finance-track.app');

define('GOOGLE_CLIENT_ID',     'xxxxx.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'GOCSPX-xxxxxxxxxxxx');

define('MAIL_FROM',   'noreply@finance-track.app');
define('CRON_SECRET', 'make-this-a-long-random-string-abc123xyz');
```

---

### Phase 3: Run SQL in phpMyAdmin

1. hPanel → Databases → phpMyAdmin → select your DB
2. Click SQL tab
3. Paste and run these files IN ORDER:

**Step 1:** Paste entire contents of `schema.sql` → Execute  
**Step 2:** Paste entire contents of `schema_patch.sql` → Execute  
**Step 3:** Paste entire contents of `schema_patch_v2.sql` → Execute  
**Step 4:** Paste entire contents of `schema_patch_google.sql` → Execute  

---

### Phase 4: Email Setup

1. hPanel → Email → Email Accounts
2. Create: `noreply@finance-track.app`
3. Note: Hostinger may require SMTP relay for reliable delivery
4. Optional but recommended: set up SMTP in Mailer.php using your Hostinger SMTP credentials

---

### Phase 5: Cron Jobs

1. hPanel → Advanced → Cron Jobs
2. Add these two jobs:

**EMI Reminders (9 AM daily):**
```
0 9 * * * php /home/u429732427/domains/app.finance-track.app/public_html/cron/send-reminders.php secret=YOUR_CRON_SECRET
```

**Auto Transactions (midnight daily):**
```
0 0 * * * php /home/u429732427/domains/app.finance-track.app/public_html/cron/auto-transactions.php secret=YOUR_CRON_SECRET
```

> Replace `/home/u429732427/domains/app.finance-track.app/public_html/` with your actual server path (visible in hPanel → Files → File Manager)

---

### Phase 6: Google OAuth Setup

1. Go to https://console.cloud.google.com/
2. Create a new project: "Finance Track"
3. APIs & Services → OAuth consent screen
   - User type: External
   - App name: Finance Track
   - Support email: your email
   - Add scope: `email`, `profile`, `openid`
4. APIs & Services → Credentials → Create Credentials → OAuth client ID
   - Application type: Web application
   - Name: Finance Track Web
   - Authorised JavaScript origins: `https://app.finance-track.app`
   - Authorised redirect URIs: `https://app.finance-track.app/api/auth-google.php`
5. Copy the Client ID and Client Secret
6. Paste into `config.php`:
   ```php
   define('GOOGLE_CLIENT_ID',     'xxxx.apps.googleusercontent.com');
   define('GOOGLE_CLIENT_SECRET', 'GOCSPX-xxxx');
   ```

> **Testing:** Google OAuth requires your app to be on HTTPS. On Hostinger, SSL is auto-provisioned. If testing locally, use `http://localhost/api/auth-google.php` as redirect URI and add it in Google console.

---

### Phase 7: SSL

1. hPanel → SSL → your subdomain
2. Enable Let's Encrypt (free)
3. Wait 5-10 minutes
4. In `config.php`, change: `define('SESSION_SECURE', true);`

---

### Phase 8: Test Checklist

- [ ] Visit `https://app.finance-track.app` → redirects to login
- [ ] Register new account with email → welcome email arrives
- [ ] Login with email → goes to dashboard
- [ ] Click "Continue with Google" → Google screen appears → login works
- [ ] Forgot password → reset email arrives
- [ ] Dashboard loads with charts
- [ ] Add a transaction → appears in list
- [ ] EMI page loads
- [ ] Reports page loads
- [ ] Profile page loads

---

## 9. GOOGLE OAUTH — HOW IT WORKS (Technical)

```
User clicks "Continue with Google"
    ↓
api/auth-google.php (no ?code param) → redirects to Google
    ↓
Google shows consent screen
    ↓
User approves → Google redirects back to:
api/auth-google.php?code=XXXX
    ↓
Exchange code for access token (server-to-server POST to Google)
    ↓
Use token to GET user info (email, name, picture, google_id)
    ↓
Check if user exists in DB:
  - YES + same google_id → update last_login, start session → dashboard
  - YES + email match (no google_id) → link accounts → dashboard
  - NO → create new user with NULL password_hash → onboarding
```

**Security notes:**
- Password for Google users is a random 48-char hash (never used, never disclosed)
- Google users without a password CAN set a password later via profile settings (future feature)
- `google_id` column has UNIQUE constraint — one Google account per Finance Track account

---

## 10. FEATURES COMPLETE (v13)

- ✅ Email registration + login
- ✅ Google OAuth login + register
- ✅ Light / Dark / Auto theme (no flash)
- ✅ Onboarding (opening balance → income → done)
- ✅ Dashboard: 4 stat cards, health score, chart, budgets, due items
- ✅ Transactions: list, search, filters (All/Expense/Income/UPI/Cash/Card), delete
- ✅ Budget tracker with alert thresholds + color-coded progress
- ✅ EMI tracker: auto paid months, clear date calculation
- ✅ Credit card billing cycle tracking
- ✅ Reports: monthly, yearly, categories, AI insights
- ✅ Profile: savings goals, PIN lock, CSV export
- ✅ Password reset via email
- ✅ Welcome email on registration
- ✅ Daily EMI reminders (7/3/1 days)
- ✅ Budget alerts via email
- ✅ Credit card utilization alerts (>30%)
- ✅ Cron: auto EMI + card payment transactions
- ✅ Financial health score (0–100)
- ✅ 6-month overview chart (Chart.js)
- ✅ Category breakdown donut chart
- ✅ Responsive: sidebar desktop, bottom nav mobile
- ✅ Password strength meter (v13)
- ✅ Animated login page (v13)

---

## 11. PHASE 4 ROADMAP (after launch)

| Feature | Effort | Priority |
|---|---|---|
| PWA (installable app) | Low — manifest.json + service worker | High |
| SMS reminders (MSG91) | Medium | Medium |
| Tax estimator (Old vs New regime) | Medium | High |
| Net worth tracker | Medium | High |
| Recurring transaction auto-detection | Medium | Medium |
| Multi-currency support | Low | Low |
| Family/shared account mode | High | Medium |
| Android WebView wrapper | Low | High |

---

## 12. MONETIZATION PLAN

### Freemium Model

| Feature | Free | Pro (₹99/mo or ₹799/yr) |
|---|---|---|
| Transactions | 50/month | Unlimited |
| EMIs | 2 | Unlimited |
| Cards | 1 | Unlimited |
| Goals | 2 | Unlimited |
| Budgets | 3 categories | Unlimited |
| Reports | Monthly only | Full yearly + CSV |
| Data export | ❌ | ✅ |
| AI insights | Basic | Advanced |
| Email reminders | ❌ | ✅ |
| Google Sign-In | ✅ | ✅ |

### Revenue Channels
1. Pro subscriptions (primary) — integrate Razorpay
2. Google AdSense on free-tier pages (you're already approved on aidigitalguides.com)
3. Affiliate: credit cards, insurance, mutual funds (Admitad / PayZippy)
4. White-label for CAs and small businesses (future)

---

## 13. WORDPRESS MARKETING SITE (finance-track.app)

### Plugins to install
- **RankMath** — SEO (best for AI agent publishing via REST API)
- **WP Fastest Cache** — speed
- **Smush** — image compression
- **WPForms** — newsletter + contact
- **MonsterInsights** — GA4 integration

### AI Agent Publishing
WordPress REST API: `https://finance-track.app/wp-json/wp/v2/posts`
RankMath REST API: `https://finance-track.app/wp-json/rankmath/v1/updateMeta`

Create a WordPress Application Password (Users → Profile → Application Passwords) and use it as Basic Auth for API calls.

### SEO Keywords to Target
**Tool keywords:** "expense tracker india free", "budget app india", "emi calculator india"  
**How-to:** "how to track expenses india", "how to save money on salary india"  
**Comparison:** "best personal finance app india 2026", "money manager app india"  
**Problem-aware:** "how to manage credit card debt india", "reduce emi burden"  
**Life stage:** "budgeting tips for freshers india", "save for home loan india"  

### Schema markup to implement
- `SoftwareApplication` schema on app page
- `FAQPage` schema
- `HowTo` schema for tutorials
- `Article` schema on all blog posts

---

## 14. SECURITY CHECKLIST

- [x] `.htaccess` blocks direct access to `api/` folder
- [x] All DB queries use prepared statements (PDO)
- [x] Passwords hashed with bcrypt (cost 12)
- [x] Session regenerated on login
- [x] CSRF tokens on all state-changing requests
- [x] Google OAuth uses server-to-server token exchange (no secrets in frontend)
- [x] `config.php` should be OUTSIDE `public_html` (or protected by .htaccess)
- [x] `display_errors = 0` in production (set in config.php)
- [ ] Set `SESSION_SECURE = true` after SSL confirmed
- [ ] Move `config.php` outside `public_html` on Hostinger (path: `/home/u429732427/config.php` then update require_once paths)

---

## 15. QUICK REFERENCE — KEY CONFIGS

| Setting | Location | Value to change |
|---|---|---|
| Database password | config.php | `DB_PASS` |
| App URL | config.php | `APP_URL` |
| Google OAuth keys | config.php | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` |
| Email sender | config.php | `MAIL_FROM` |
| Cron secret | config.php | `CRON_SECRET` |
| Session name | config.php | `SESSION_NAME` (default: `ft_sess`) |

---

## 16. TROUBLESHOOTING

**Login doesn't work / black screen**
→ In config.php, temporarily set `ini_set('display_errors', 1)` to see PHP errors

**Google OAuth redirect fails**
→ Check that redirect URI in Google Console exactly matches `APP_URL . '/api/auth-google.php'`
→ Must use HTTPS in production

**Emails not sending**
→ Verify `MAIL_FROM` matches an email account that exists in hPanel
→ Check PHP mail logs: hPanel → Advanced → PHP Error Logs

**Charts not showing**
→ Chart.js is loaded from CDN — check internet connection / ad blocker

**Session drops after page reload**
→ Ensure `SESSION_SECURE = false` until SSL is confirmed working

---

*Document version: v13 | Created: May 2026 | App: Finance Track v1.0 | Files: 40*
