#!/bin/zsh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")" && pwd)"

if [[ ! -f "$ROOT/api/.env" ]]; then
  cp "$ROOT/api/.env.example" "$ROOT/api/.env"
  (cd "$ROOT/api" && php artisan key:generate --force)
fi

if [[ ! -d "$ROOT/web/node_modules" ]]; then
  (cd "$ROOT/web" && npm install)
fi

(cd "$ROOT/api" && php artisan monitor:prepare)

php "$ROOT/api/artisan" serve --host=127.0.0.1 --port=8080 &
PHP_PID=$!
php "$ROOT/api/artisan" schedule:work &
SCHED_PID=$!
trap "kill $PHP_PID $SCHED_PID" EXIT

cd "$ROOT/web"
npm run dev
