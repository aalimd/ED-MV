# PHP 8.4.x Upgrade Plan
Status: Planning document only
Audience: Software engineers, maintainers, automation agents, and coding assistants
Project: `ED-MV`
Objective: Upgrade this application to the latest stable `PHP 8.4.x` release with minimal risk, preserved behavior, and a repeatable rollback path.

---

## 1. Executive Summary

This codebase is a custom server-rendered PHP application backed by MySQL. It already uses PHP 8-era features such as `match` and `str_starts_with`, so it is not a legacy PHP 5/7 codebase. A move to `PHP 8.4.x` is the recommended target.

### Recommendation
Use:
- `PHP 8.4.x` latest patch release
- the existing production MySQL/MariaDB engine/version unless a separate database upgrade has been planned and validated
- Apache or Nginx configured to serve the existing app exactly as today
- the required PHP extensions listed in this plan

### Important limitation
No engineer, plan, or tool can honestly guarantee "zero bugs" before execution. The correct standard is:
- identify known risks
- remove avoidable conflicts
- validate all critical flows
- cut over with rollback ready
- monitor after release

This plan is designed to make the upgrade safe, comprehensive, and low-risk.

### Evidence checked on 2026-04-29
The following facts were verified against this repository and the local runtime:

- No `composer.json` or `composer.lock` was found in the repository file list.
- Local CLI runtime is `PHP 8.4.19`.
- All 23 repository PHP files passed `php -l` with the local `PHP 8.4.19` binary.
- Local `php -m` includes the runtime modules this app needs, including `PDO`, `pdo_mysql`, `openssl`, `mbstring`, `json`, `session`, `filter`, `ctype`, `date`, `hash`, `tokenizer`, `standard`, `pcre`, `random`, `fileinfo`, and `Zend OPcache`.
- The local repository does not contain a `logs/` directory, while `config.php` and `config.example.php` configure `APP_ROOT . '/logs/error.log'`.
- Production PHP version, production enabled extensions, production web server config, production database version, production writable paths, and production `config.php` values have not been verified by this document.
- **XAMPP dual-runtime warning**: The local CLI `php` binary (`PHP 8.4.19` via Homebrew) may differ from the PHP module Apache actually serves. The two must be verified independently. A `phpinfo()` page served by Apache is the authoritative check for the web-facing runtime.

### Target branch clarification
As of 2026-04-29, the official PHP supported-versions page lists both `PHP 8.4` and `PHP 8.5` as actively supported branches. This plan intentionally targets the `8.4.x` branch. If the real business requirement is "latest supported PHP branch overall", pause and create a separate `PHP 8.5` assessment instead of silently expanding this PHP 8.4 plan.

Primary external references used:
- PHP supported versions: `https://www.php.net/supported-versions.php`
- PHP 8.4 migration guide: `https://www.php.net/migration84`
- PHP 8.4 backward-incompatible changes: `https://www.php.net/manual/en/migration84.incompatible.php`
- PHP 8.4 deprecated features: `https://www.php.net/manual/en/migration84.deprecated.php`
- PHP 8.4 removed extensions: `https://www.php.net/manual/en/migration84.removed-extensions.php`

---

## 2. Target State

### Runtime target
- PHP: latest available `8.4.x`
- Charset: `utf8mb4`
- PDO with native prepared statements
- production mode with `APP_DEBUG=false`
- writable error log path
- HTTPS enabled in production

### Functional target
The following behaviors must remain intact after upgrade:
- login
- logout
- registration
- password reset
- session persistence
- CSRF protection
- admin dashboard
- user management
- subscription checks
- maintenance mode
- activity logging
- database connectivity
- protected app routing

---

## 3. Current Codebase Findings

### 3.1 Confirmed stack
- Server language: `PHP`
- Database: `MySQL` via `PDO`
- Frontend: plain HTML/CSS with small inline JavaScript
- Architecture: custom PHP app, no framework, no Composer manifest detected

### 3.2 Minimum observed PHP level
The app already requires at least PHP 8.0 because it uses:
- `match`
- `str_starts_with`

### 3.3 Positive findings
The app already uses several modern/safe primitives:
- `PDO`
- prepared statements
- `password_hash()`
- `password_verify()`
- `random_bytes()`
- `hash_equals()`
- cookie-based sessions with secure flags
- CSRF token generation and validation

### 3.4 Project-specific risks to address during the upgrade
These are not all PHP 8.4 blockers, but they should be addressed during the upgrade window.

#### A. Error log path assumes a logs directory exists
`config.example.php` sets:
- `ini_set('error_log', APP_ROOT . '/logs/error.log');`

Risk:
- the repository currently does not show a `logs/` directory
- PHP 8.4 error logging may fail or log elsewhere if the path is missing or not writable

Action:
- create a writable `logs/` directory outside public access if possible
- otherwise update logging to a known writable location on the server

#### B. Login redirect builds absolute URLs manually
`auth/login.php` manually constructs a redirect using `http://` and `$_SERVER['HTTP_HOST']`.

Risk:
- wrong scheme behind HTTPS or reverse proxy
- inconsistent redirects between environments
- fragile behavior during mobile/API evolution

Action:
- replace manual absolute URL building with an internal-only redirect helper

#### C. Security settings exist in two sources
There is a mismatch between:
- constants in config, such as `SESSION_LIFETIME`, `MAX_LOGIN_ATTEMPTS`, `LOCKOUT_MINUTES`, `REQUIRE_ADMIN_APPROVAL`
- database settings shown in admin UI, such as `session_timeout_minutes`, `max_login_attempts`, `lockout_minutes`, `require_approval`

Risk:
- admins may think a setting is active when the runtime is still using the config constant
- upgrade testing can give misleading results

Action:
- pick one source of truth for each setting
- preferred: config for bootstrap/security-critical settings, DB for runtime business settings
- document each decision

#### D. Password reset testing link is exposed in-app
`auth/forgot.php` stores and displays a reset link for testing.

Risk:
- acceptable for local development
- not acceptable for production

Action:
- disable this behavior in production before or during the upgrade
- replace with real email delivery or a development-only guard

#### E. setup.php is a standing security risk if left deployed
`setup.php` is intended for first-run admin creation and should not remain on a live server.

Action:
- confirm it is removed or blocked in production

#### F. Rate-limit table design works but is inefficient
`login_attempts.attempts` is stored, but the code inserts a new row per failed attempt and counts rows instead of incrementing a single logical counter.

Risk:
- not a PHP 8.4 compatibility blocker
- operationally less clean under heavy use

Action:
- leave unchanged for a pure runtime upgrade
- optionally refactor after PHP 8.4 cutover

#### G. Missing `.htaccess` protection for new directories
The `includes/` directory has a `.htaccess` with `Deny from all`. However, `admin/`, `app/`, and the planned `logs/` directory do not.

Risk:
- `logs/` without `.htaccess` would expose error logs to the public if directory listing is enabled
- while `admin/` and `app/` are protected by PHP-level auth middleware, defense-in-depth is the expert standard

Action:
- create `logs/.htaccess` with `Deny from all` when creating the `logs/` directory
- optionally add `.htaccess` to `admin/` and `app/` as a secondary defense layer

#### H. No HTTP security headers
The application sets secure session cookie flags but does not emit HTTP security headers.

Risk:
- missing `X-Content-Type-Options: nosniff` allows MIME-sniffing attacks
- missing `X-Frame-Options: DENY` allows clickjacking
- missing `Referrer-Policy` may leak URLs in cross-origin requests

Action:
- add security headers in a shared PHP include or global `.htaccess`
- recommended headers: `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: strict-origin-when-cross-origin`

#### I. Password hashing uses bcrypt, not Argon2id
The code uses `PASSWORD_BCRYPT`. Argon2id is the modern recommendation per OWASP 2025 guidance, but requires `libargon2` in the PHP build.

Risk:
- bcrypt is still secure; this is not a blocker
- Argon2id provides better resistance to GPU/ASIC attacks

Action:
- check if `PASSWORD_ARGON2ID` is defined in the target PHP 8.4 build
- if available, migrate to it after cutover as an optional hardening step
- if not available, leave bcrypt in place - it remains a safe choice

---

## 4. Required PHP Extensions

The target PHP 8.4 environment must have at least these extensions enabled:

- `pdo`
- `pdo_mysql`
- `openssl`
- `mbstring`
- `json`
- `session`
- `filter`
- `pcre`
- `random`
- `ctype`
- `date`
- `hash`
- `tokenizer`
- `standard`

Recommended:
- `opcache`
- `fileinfo`

Do not proceed to cutover unless the extension set is verified in both staging and production.

---

## 5. Upgrade Strategy

Use a staged, reversible upgrade. Do not upgrade production first.

### Phase 0. Freeze and prepare
1. Freeze feature changes on the application.
2. Export the current database.
3. Archive the current application files.
4. Record the exact current runtime:
   - PHP version
   - web server version
   - MySQL version
   - enabled PHP extensions
   - `php.ini` path
5. Create a staging environment that mirrors production as closely as possible.

### Phase 1. Baseline inventory
Collect:
- current `php -v`
- current `php -m`
- current web server vhost config
- current document root
- current `APP_URL`
- current `.htaccess` behavior
- current database collation and character set
- current session storage location
- current writable directories
- current `php.ini` settings diff: compare the current `php.ini` against PHP 8.4's `php.ini-production` template, paying special attention to `session.*`, `opcache.*`, `error_reporting`, and `implicit_flush` defaults
- Apache's actual PHP version via a `phpinfo()` page (do not rely solely on CLI `php -v`)

### Phase 2. Static compatibility review
Run all of the following before switching runtime:
1. Lint every PHP file with the target PHP 8.4 binary.
2. Search for removed/deprecated features:
   - dynamic property usage
   - legacy string/regex APIs
   - old MySQL APIs
   - reliance on undefined array indexes
   - reliance on implicit null-to-string behavior
   - implicit nullable parameter declarations, for example `function x(Type $value = null)`
   - `E_STRICT` and `trigger_error(..., E_USER_ERROR)` usage
   - `exit()` or `die()` calls with non-string/non-int values
   - removed bundled extensions: `imap`, `oci8`, `pdo_oci`, `pspell`
   - explicit default CSV escape behavior if `fputcsv()`, `fgetcsv()`, or `str_getcsv()` are introduced later
   - invalid `round()` modes if numeric utilities are added later
3. **Completed deprecation scan results** (verified 2026-04-29 against this codebase):
    - Implicit nullable params (`function x(Type $val = null)`): **not used** OK
    - Dynamic properties on non-`stdClass`: **not used** (no classes) OK
    - `E_STRICT` usage: **not used** OK
    - `trigger_error(E_USER_ERROR)`: **not used** OK
    - `exit()`/`die()` with non-string/int: **not used** OK
    - `imap`/`oci8`/`pspell` removed extensions: **not used** OK
    - `fputcsv`/`fgetcsv` escape param: **not used** OK
    - `round()` invalid modes: **not used** OK
    - `PDO_MYSQL` boolean attribute values: uses `PDO::ATTR_EMULATE_PREPARES => false` - **already boolean, safe** OK
    - `password_hash` algorithm: uses `PASSWORD_BCRYPT` - **works on 8.4**, Argon2id preferred if available (note)
    - **Conclusion**: zero PHP 8.4 compatibility blockers found in the codebase
4. Validate all entry points:
   - `/index.php`
   - `/auth/login.php`
   - `/auth/register.php`
   - `/auth/forgot.php`
   - `/auth/reset.php`
   - `/subscribe.php`
   - `/setup.php` only in local/dev, never in live production
   - `/admin/*`
   - `/app/ventguide.php`

### Phase 3. Fix environment prerequisites before code changes
Complete these items first:
1. Ensure the target PHP 8.4 build includes all required extensions.
2. Ensure the configured error log path exists and is writable.
3. Ensure `session.save_path` is writable.
4. Ensure file ownership and permissions are correct for Apache/PHP-FPM user.
5. Ensure HTTPS is terminated correctly if the app relies on `$_SERVER['HTTPS']`.
6. Verify `.htaccess` rules still behave correctly under the target web stack.
7. Create `logs/.htaccess` with `Deny from all` alongside the `logs/` directory.
8. In staging, enable `E_ALL` logging via `error_reporting(E_ALL)` with `display_errors=0` so PHP 8.4 deprecation notices are captured in the log without exposing them to users.
9. Verify `APP_URL` exactly matches the deployed public URL.

### Phase 4. Apply project-specific code hardening
These are the recommended code-level changes to make before production cutover to PHP 8.4.

#### Mandatory hardening
1. Replace manual redirect construction in `auth/login.php` with internal redirect logic.
2. Make production password reset flow stop exposing test links.
3. Resolve config-vs-database security setting drift.
4. Guarantee a valid writable error log destination.
5. Verify `setup.php` is removed or denied in production.

#### Optional but recommended hardening
1. Add explicit return types where safe.
2. Add explicit parameter types where safe.
3. Normalize redirect behavior to avoid absolute URL guesswork.
4. Centralize security header logic if future API/mobile work is planned.
5. Refactor rate limiting after the runtime upgrade is stable.
6. Add HTTP security headers (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`) via shared include or `.htaccess`.
7. Check `PASSWORD_ARGON2ID` availability and migrate from bcrypt if supported.
8. Update `config.example.php` to match the final `config.php` structure after all changes.

### Phase 5. Staging deployment on PHP 8.4.x
Deploy to staging with:
- same database schema
- same application config structure
- same `.htaccess`
- production-like HTTPS if possible
- production-like `APP_URL`, cookie security, and reverse-proxy headers if the live stack uses a proxy or TLS terminator

Do not change multiple major variables at once. Avoid upgrading:
- PHP
- DB engine
- server
- application behavior

all in the same deployment unless unavoidable.

### Phase 6. Staging validation
Run the full test matrix in Section 8.

No production cutover unless:
- no fatal errors
- no warnings affecting user flow
- no redirect loops
- no session breakage
- no password/auth regressions
- no admin flow failures

### Phase 7. Production cutover
1. Create a git tag: `git tag v1.0-pre-php84` to enable instant code rollback.
2. Put the app into maintenance mode.
3. Take fresh DB and file backups immediately before cutover.
4. Switch runtime to PHP 8.4.x.
5. Clear opcode cache if used.
6. Set `opcache.validate_timestamps=1` and `opcache.revalidate_freq=0` in `php.ini` to ensure every request uses freshly compiled bytecode during the validation window.
7. Confirm web server uses the intended PHP 8.4 binary (verify via `phpinfo()` page, not just CLI).
8. Smoke-test all critical flows.
9. Remove maintenance mode only after validation passes.
10. After 48 hours of stable operation, revert OPcache settings to production values and tag: `git tag v1.0-php84`.

### Phase 8. Post-cutover monitoring
Monitor:
- PHP error log
- web server error log
- login success/failure rates
- password reset activity
- admin actions
- session-related complaints
- redirect anomalies
- 500 errors
- database connection failures

Monitoring window:
- first hour: continuous
- first 24 hours: frequent checks
- first 7 days: daily review
- first 30 days: weekly log review for deprecation notices that may become errors in PHP 9.0

---

## 6. Compatibility Expectations for This Codebase

### 6.1 What should already be compatible with PHP 8.4
The observed code style suggests the app should be broadly compatible with PHP 8.4 because it already uses modern features and avoids obvious removed APIs.

### 6.2 What still needs verification
Compatibility is not only syntax. Verify:
- Apache/XAMPP integration
- extension availability
- path permissions
- header behavior
- session behavior
- redirect behavior
- error logging
- production-only config values

### 6.3 Special note on XAMPP
If local or production relies on XAMPP, verify that the chosen XAMPP distribution actually bundles the desired PHP 8.4.x release. If not, use one of:
- a supported XAMPP build that includes PHP 8.4
- a standard Apache + PHP installation
- Docker
- another managed hosting/runtime that supports PHP 8.4.x cleanly

Do not force an unsupported XAMPP/PHP combination.

---

## 7. Required Code Review Checklist

An engineer or coding agent must review these files specifically:

- `config.php` or `config.example.php`
- `includes/db.php`
- `includes/session.php`
- `includes/helpers.php`
- `includes/auth.php`
- `includes/rate_limit.php`
- `auth/login.php`
- `auth/register.php`
- `auth/forgot.php`
- `auth/reset.php`
- `admin/users.php`
- `admin/settings.php`
- `index.php`
- `setup.php`
- `app/ventguide.php`
- `app/ventguide_raw.html`

For each file, review:
- syntax on PHP 8.4
- deprecated behavior
- session/header ordering
- redirect correctness
- null-handling
- HTML escaping
- database error handling
- environment assumptions
- writable path dependencies

---

## 8. Full Test Matrix

All tests must pass in staging before production.

### 8.1 Authentication
1. Login with valid active user.
2. Login with wrong password.
3. Login with pending user.
4. Login with suspended user.
5. Login while rate-limited.
6. Logout.
7. Session timeout behavior.
8. CSRF failure handling on login.

### 8.2 Registration
1. Register with valid data.
2. Register with duplicate email.
3. Register with invalid email.
4. Register with weak password.
5. Register when registration is closed.
6. Confirm pending/active status logic works as intended.

### 8.3 Password reset
1. Request reset for valid active user.
2. Request reset for unknown email.
3. Use valid token.
4. Use expired token.
5. Use already-used token.
6. Confirm reset updates password.
7. Confirm old password no longer works.
8. Confirm reset link is not exposed in production.

### 8.4 Admin flows
1. Access admin dashboard as admin.
2. Block admin access for non-admin.
3. Activate pending user.
4. Suspend active user.
5. Soft-delete user.
6. Promote user to admin.
7. Reset password from admin area.
8. Save settings.
9. View logs.
10. Manage subscriptions.

### 8.5 Subscription and app routing
1. Logged-out user hits `/index.php`.
2. Active subscriber hits `/index.php`.
3. Non-subscriber hits `/index.php`.
4. Admin hits `/index.php`.
5. User reaches `/app/ventguide.php` only when authorized.

### 8.6 Security behavior
1. CSRF token is required on POST forms.
2. Session cookie flags are present.
3. Secure cookies are set when HTTPS is active.
4. Headers are sent without warning.
5. Direct access to protected pages redirects correctly.
6. Maintenance mode blocks non-admin users.

### 8.7 Operational checks
1. PHP error log receives entries when expected.
2. No fatal errors in web server logs.
3. No permission denied issues.
4. No broken includes.
5. Database connection succeeds on every entry point.
6. Character encoding remains correct.

---

## 9. Acceptance Criteria

The upgrade is complete only when all are true:

- application runs on latest intended `PHP 8.4.x`
- required extensions are enabled
- no fatal runtime errors
- no broken authentication flows
- no broken admin flows
- no broken subscriptions flow
- no redirect regression
- no writable-path failure
- no test reset-link exposure in production
- no unresolved source-of-truth conflict for security settings
- rollback package exists and has been tested

---

## 10. Rollback Plan

Rollback must be prepared before any production switch.

### Rollback assets
- full code backup
- full database backup
- previous PHP runtime reference
- previous web server configuration
- previous `php.ini`
- rollback instructions written and tested

### Rollback trigger conditions
Rollback immediately if any of these occur:
- login fails broadly
- sessions break broadly
- admin access is broken
- app returns 500 errors
- database connectivity becomes unstable
- password reset flow breaks
- routing or redirects loop or misdirect

### Rollback steps
1. Put app in maintenance mode.
2. Restore previous PHP runtime or switch the web server back to previous handler.
3. Restore previous app files if code changed.
4. Restore DB only if schema/data was modified and restoration is necessary.
5. Clear caches/opcache.
6. Smoke-test critical flows.
7. Reopen only when stable.

---

## 11. Suggested Execution Order for Engineers or Coding Agents

Follow this exact order:

1. inventory current runtime
2. provision PHP 8.4.x staging
3. verify extensions and writable paths
4. lint the entire codebase with PHP 8.4
5. fix the mandatory hardening items
6. run full staging test matrix
7. repeat until clean
8. prepare rollback artifacts
9. schedule maintenance window
10. cut over production
11. smoke-test immediately
12. monitor closely

---

## 12. Commands Engineers May Use

These are examples only. Adjust paths to the real server.

```bash
php -v
php -m
php --ini
find . -name "*.php" -print0 | xargs -0 -n1 php -l
```

If using a dedicated PHP 8.4 binary:

```bash
/absolute/path/to/php84/bin/php -v
find . -name "*.php" -print0 | xargs -0 -n1 /absolute/path/to/php84/bin/php -l
```

To verify the error log destination exists:

```bash
mkdir -p /path/to/app/logs
touch /path/to/app/logs/error.log
chmod 775 /path/to/app/logs
chmod 664 /path/to/app/logs/error.log
```

---

## 13. Mandatory Work Items Before Production Approval

These must be tracked explicitly as tickets:

- [ ] Confirm exact current production PHP version
- [ ] Confirm Apache is serving the same PHP version as CLI (`phpinfo()` page check)
- [ ] Confirm exact target PHP 8.4.x patch version
- [ ] Confirm PHP 8.4 is the intended target branch, not PHP 8.5 or another currently supported branch
- [ ] Confirm all required PHP extensions
- [ ] Diff old `php.ini` against PHP 8.4 `php.ini-production` (session, opcache, error settings)
- [ ] Confirm writable log path
- [ ] Create `logs/.htaccess` with `Deny from all`
- [ ] Confirm writable session path
- [ ] Remove or block `setup.php` in production
- [ ] Remove password-reset test link behavior from production flow
- [ ] Replace fragile redirect logic in login flow
- [ ] Resolve config-vs-database security setting conflicts
- [ ] Update `config.example.php` to match final `config.php` structure
- [ ] Add HTTP security headers (X-Content-Type-Options, X-Frame-Options, Referrer-Policy)
- [ ] Check `PASSWORD_ARGON2ID` availability (optional upgrade from bcrypt)
- [ ] Set `opcache.validate_timestamps=1` during validation window
- [ ] Create git tag `v1.0-pre-php84` before cutover
- [ ] Complete full staging test matrix
- [ ] Prepare and test rollback
- [ ] Perform production cutover and smoke tests
- [ ] Monitor for 7 days post-cutover; review logs for deprecation notices for 30 days

---

## 14. Non-Goals for This Upgrade

Do not combine these with the PHP 8.4 runtime upgrade unless they are separately planned and tested:

- full Laravel migration
- API redesign
- iOS/mobile API rollout
- database engine migration
- UI redesign
- large refactors unrelated to compatibility or security
- business logic changes

Keep the runtime upgrade focused.

---

## 15. Final Recommendation

Best production target if the requirement is specifically PHP 8.4:
- latest maintained `PHP 8.4.x` patch release available from the chosen runtime provider

Best execution model:
- staging first
- fix environment assumptions
- address the identified hardening issues
- cut over with rollback ready

This codebase appears to be a strong candidate for PHP 8.4.x, provided the project-specific issues in this plan are handled before production deployment. This document does not claim PHP 8.4 is the newest PHP branch overall; as of 2026-04-29, PHP 8.5 also exists and must be assessed separately if "newest supported branch" is the actual goal.
