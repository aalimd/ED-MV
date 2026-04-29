# Deployment

This project uses Git for application files and SQL migrations for database changes.

## Production Rules

- Keep `config.php` on the server only. Do not commit it.
- Keep `APP_DEBUG` set to `false` on hosting.
- Use HTTPS in `APP_URL`.
- Do not re-import `db_setup.sql` over a live database after launch.
- Add every future SQL change as a new file in `migrations/`.
- Never edit an old migration after it has been applied online.

## Best Deployment Flow

Run this on the hosting server from the project folder:

```bash
cd /path/to/your/site
bash tools/deploy.sh
```

The deploy script does all of this safely:

1. Confirms `config.php` exists.
2. Confirms Git and PHP CLI are available.
3. Refuses to deploy if tracked files were edited directly on hosting.
4. Pulls `origin/main` using `--ff-only`.
5. Lints PHP files.
6. Applies pending SQL migrations.
7. Runs the final deployment check.

## Manual Commands

If you do not want to use the deploy script, run these on hosting:

```bash
cd /path/to/your/site
git status --short --branch
git pull --ff-only origin main
php tools/migrate.php --status
php tools/migrate.php --apply
php tools/deployment_check.php
```

Local and hosting should show the same Git commit:

```bash
git rev-parse --short HEAD
```

## First Online Database Setup

Preferred:

```bash
php tools/migrate.php --apply
```

This creates all required tables and inserts default plans/settings only if they are missing.

If the host does not provide SSH/PHP CLI, use phpMyAdmin and import the SQL files in `migrations/` in filename order. After import, create `schema_migrations` or switch to SSH for future changes. The CLI migration tool is safer because it records what already ran.

## Future SQL Changes

Create a new migration file:

```text
migrations/YYYYMMDDNNNN_short_description.sql
```

Examples:

```sql
ALTER TABLE users ADD COLUMN phone VARCHAR(30) NULL;
CREATE INDEX idx_users_created_at ON users (created_at);
```

Then commit it with the PHP changes that require it. Deployment will run it once online.

## Verification

After deployment, this command must finish with `0 failure(s)`:

```bash
php tools/deployment_check.php
```

Also test the live app:

- `/auth/login.php`
- `/auth/register.php`
- `/subscribe.php`
- `/admin/`

## Rollback

For file rollback:

```bash
git log --oneline -5
git checkout <known-good-commit>
```

For database rollback, restore a hosting database backup. Most SQL migrations are not safely reversible after real users have created data, so take a database backup before deploying migrations on production.
