#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

echo "== ED VentGuide Pro deploy =="

if [ ! -f config.php ]; then
  echo "ERROR: config.php is missing on this server." >&2
  exit 1
fi

if ! command -v git >/dev/null 2>&1; then
  echo "ERROR: git is not available on this server." >&2
  exit 1
fi

if ! command -v php >/dev/null 2>&1; then
  echo "ERROR: php CLI is not available on this server." >&2
  exit 1
fi

branch="$(git rev-parse --abbrev-ref HEAD)"
if [ "$branch" != "main" ]; then
  echo "ERROR: expected branch main, found $branch." >&2
  exit 1
fi

dirty="$(git status --porcelain --untracked-files=no)"
if [ -n "$dirty" ]; then
  echo "ERROR: tracked files have local changes. Commit, discard, or inspect them before deployment:" >&2
  echo "$dirty" >&2
  exit 1
fi

echo "== Pulling latest code =="
git fetch origin main
git pull --ff-only origin main

echo "== Linting PHP files =="
find . \
  -path './.git' -prune -o \
  -path './logs' -prune -o \
  -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null

echo "== Applying database migrations =="
php tools/migrate.php --apply

echo "== Verifying deployment =="
php tools/deployment_check.php

echo "== Deployment complete =="
git rev-parse --short HEAD
