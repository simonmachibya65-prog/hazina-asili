# HAZINA ASILI — Mfumo wa Hifadhidata ya Misombo ya Asili ya Kikaboni

A full-stack web application for managing natural organic compound data, built with PHP 8.1+, MySQL, Bootstrap 5.3, and a three-tier MVC architecture.

**Version 3.0** — Now with dark mode, REST API, rate limiting, Docker support, and more.

---

## 🚀 Quick Setup

### Option A: XAMPP (Local)

1. **Place the project** in your XAMPP `htdocs` directory:
   ```
   C:\xampp\htdocs\DB\project\
   ```

2. **Configure environment:**
   ```bash
   copy .env.example .env
   ```
   Edit `.env` with your database credentials and `APP_URL`.

3. **Import the database:**
   - Open phpMyAdmin → `http://localhost/phpmyadmin`
   - Import `database/natural_compounds_db.sql`
   - Import `database/migration_v3_security.sql`

4. **Install dependencies (optional):**
   ```bash
   composer install
   ```

5. **Seed demo users:**
   Visit `http://localhost/DB/project/database/seed_users.php`
   (Delete `seed_users.php` after running.)

6. **Open the app:**
   ```
   http://localhost/DB/project/
   ```

### Option B: Docker

```bash
docker-compose up -d
```

Access:
- App: `http://localhost:8080`
- phpMyAdmin: `http://localhost:8081`

---

## 🔑 Demo Credentials

| Role       | Email                    | Password    |
|------------|--------------------------|-------------|
| Admin      | admin@HAZINA ASILI.com      | Admin@1234  |
| Researcher | researcher@HAZINA ASILI.com | Admin@1234  |

---

## ✅ Features

### v3.0 New Features
- **Dark Mode** — Toggle via navbar button or `Alt+T`. Respects OS preference, persists in localStorage.
- **REST API** — JSON endpoints for compounds, organisms, references, stats. Auth via session or API key.
- **Rate Limiting** — Login brute force protection with lockout after N failed attempts.
- **Bulk Import with Preview** — Upload CSV, validate all rows, review errors, then confirm import.
- **Keyboard Shortcuts** — `Ctrl+K` search, `Alt+D` dashboard, `Alt+N` create, `?` for help.
- **Back to Top** — Floating button on scroll.
- **Session Timeout** — Auto-logout on inactivity.
- **Docker Support** — One-command development environment.
- **PHPUnit Setup** — Test framework configured and ready.
- **Environment Config** — `.env` file for all configuration (no more hardcoded credentials).
- **Content Security Policy** — CSP headers, permissions policy, HSTS.
- **Email Support** — PHPMailer integration for password resets and notifications (optional).
- **API Keys** — Users can generate/revoke API keys from their profile.
- **Accessibility** — Skip navigation, ARIA labels, keyboard-navigable dropdowns.

### Core Features
- **Authentication** — Register, login, logout, password reset (token-based with email)
- **Compound Management** — Full CRUD, version control with rollback, formula validation, MW estimation, duplicate detection, structure images
- **Organism Taxonomy** — Full hierarchy (kingdom → species), linked to compounds
- **Researcher Submissions** — Insights and recommendations with approval workflow and notifications
- **Activity & Error Logging** — Full audit trail, error tracking with stack traces
- **CSV Export/Import** — All data types, with validation preview
- **Reports** — Per-compound, per-organism, and database summary reports (print-to-PDF)
- **Search** — Multi-field search with AJAX autocomplete, advanced search with filters

### Security
- Prepared statements (PDO) — SQL injection prevention
- CSRF tokens on all forms
- bcrypt password hashing (cost 12)
- Rate limiting with account lockout
- Session regeneration on login + timeout
- HttpOnly, SameSite, Secure cookies
- Content Security Policy headers
- Input sanitization and validation
- Role-based access control
- `.htaccess` blocking PHP execution in uploads
- Referrer-Policy, X-Frame-Options, X-Content-Type-Options

---

## 🔌 REST API

Base URL: `{APP_URL}controllers/ApiController.php`

### Authentication
- **Session:** Login via the web app
- **API Key:** Send `X-API-Key: {your_key}` header (generate from Profile page)

### Endpoints

| Resource    | Action   | Parameters                                    |
|-------------|----------|-----------------------------------------------|
| compounds   | list     | page, limit, search, field, sort, dir         |
| compounds   | get      | id                                            |
| compounds   | search   | q                                             |
| compounds   | stats    | —                                             |
| organisms   | list     | page, limit, search                           |
| organisms   | get      | id                                            |
| organisms   | kingdoms | —                                             |
| references  | list     | page, limit, search                           |
| references  | get      | id                                            |
| stats       | —        | —                                             |

**Example:**
```bash
curl "http://localhost/DB/project/controllers/ApiController.php?resource=compounds&action=list&limit=5" \
  -H "X-API-Key: your_api_key_here"
```

---

## ⌨️ Keyboard Shortcuts

| Shortcut   | Action              |
|------------|---------------------|
| `Ctrl+K`   | Focus search bar    |
| `Alt+D`    | Go to Dashboard     |
| `Alt+N`    | Create new item     |
| `Alt+T`    | Toggle dark mode    |
| `Esc`      | Clear search        |
| `?`        | Show shortcut help  |

---

## 📁 Project Structure

```
project/
├── config/
│   ├── config.php            # App settings, .env loading, error handlers
│   └── database.php          # PDO singleton (reads from .env)
├── models/                   # Data access layer (PDO prepared statements)
├── controllers/
│   ├── process.php           # Central POST router (all form actions)
│   ├── ApiController.php     # REST API endpoint
│   ├── ImportController.php  # Bulk CSV import with preview
│   └── ...Controller.php     # Feature controllers
├── views/
│   ├── layouts/              # Header, footer, navbars (with dark mode toggle)
│   ├── auth/                 # Login, register, password reset
│   ├── admin/                # Admin CRUD + system tools
│   └── researcher/           # Researcher browse + submissions
├── helpers/
│   ├── functions.php         # Global utilities
│   ├── RateLimiter.php       # Brute force protection
│   ├── Mailer.php            # Email sending (PHPMailer wrapper)
│   └── report_functions.php  # PDF/print report generation
├── assets/
│   ├── css/style.css         # Custom CSS with dark mode variables
│   └── js/app.js             # Client JS (theme, shortcuts, AJAX, a11y)
├── database/                 # SQL schema + migrations
├── tests/                    # PHPUnit tests
├── .env.example              # Environment template
├── .htaccess                 # Security headers, CSP, compression
├── composer.json             # Dependencies (PHPMailer, phpdotenv, PHPUnit)
├── Dockerfile                # PHP 8.2 + Apache container
├── docker-compose.yml        # Full stack (web + MySQL + phpMyAdmin)
└── phpunit.xml               # Test configuration
```

---

## ⚙️ Configuration

All configuration is in `.env` (copy from `.env.example`):

| Variable              | Description                     | Default          |
|-----------------------|---------------------------------|------------------|
| `APP_URL`             | Base URL of the app             | localhost/...    |
| `APP_ENV`             | Environment (development/prod)  | development      |
| `DB_HOST`             | MySQL host                      | localhost        |
| `DB_NAME`             | Database name                   | natural_compounds_db |
| `LOGIN_MAX_ATTEMPTS`  | Failed logins before lockout    | 5                |
| `LOGIN_LOCKOUT_MINUTES` | Lockout duration              | 15               |
| `MAIL_ENABLED`        | Enable real email sending       | false            |
| `FORCE_HTTPS`         | Redirect all traffic to HTTPS   | false            |

---

## 🧪 Testing

```bash
# Install dev dependencies
composer install

# Run tests
composer test
# or
./vendor/bin/phpunit
```

---

## 🗄️ Database Schema

| Table                      | Description                          |
|----------------------------|--------------------------------------|
| `users`                    | Admin and researcher accounts        |
| `compounds`                | Natural organic compound records     |
| `organisms`                | Taxonomic classification data        |
| `references`               | Scientific publication references    |
| `compound_reference`       | Many-to-many: compounds ↔ references |
| `compound_versions`        | Version history snapshots            |
| `researcher_insights`      | Researcher-submitted insights        |
| `researcher_recommendations` | Researcher-suggested data changes |
| `activity_log`             | Audit trail of all user actions      |
| `error_log`                | Application error tracking           |
| `notifications`            | In-app notification system           |
| `login_attempts`           | Rate limiting tracking               |
| `user_sessions`            | Session management                   |

---

## 📋 Changelog

### v3.0.0
- Dark mode with system preference detection
- REST API with API key authentication
- Rate limiting and account lockout
- Bulk CSV import with validation preview
- Keyboard shortcuts
- Docker development environment
- PHPUnit test framework
- .env configuration (phpdotenv)
- Content Security Policy headers
- PHPMailer integration
- Session timeout auto-logout
- Back-to-top button
- Accessibility improvements (skip nav, ARIA)
- Print styles

### v2.0.0
- Version control for compounds with rollback
- Notifications system
- Error logging
- Advanced search with filters
- Organism taxonomy management
- CSV export
- Activity logging

### v1.0.0
- Initial release with basic CRUD
- Authentication and role-based access
