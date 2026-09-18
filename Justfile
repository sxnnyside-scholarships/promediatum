# Default goal
default:
    @just --list

# Bootstrap all project dependencies and environment
install:
    composer install --no-interaction
    bun install
    @git config core.hooksPath .githooks 2>/dev/null || true
    @if [ ! -f .env ]; then cp .env.example .env && php artisan key:generate; fi
    @if [ ! -f database/database.sqlite ]; then touch database/database.sqlite; fi
    php artisan migrate --force
    bun run build

# Start local development workflow (Vite HMR + Laravel serve)
dev:
    bunx concurrently -k -n "vite,php" -c "cyan,green" "bun run dev" "php artisan serve"

# Produce production build artifacts
build:
    bun run build

# Run automated test suites
test:
    php artisan test

# Run static correctness and type checks
typecheck:
    bun run typecheck
    ./vendor/bin/phpstan analyse --memory-limit=512M

# Run static analysis and style audits
lint:
    bun run lint
    ./vendor/bin/pint --test

# Automatically apply formatters
format:
    bun run format
    ./vendor/bin/pint

# Run full quality gate
check: format lint typecheck test

# Remove build artifacts, caches, and temporary files
clean:
    rm -rf public/build public/hot node_modules/.vite
    php artisan optimize:clear

# ── Database & Artisan Helpers ──

# Run pending database migrations
migrate:
    php artisan migrate

# Drop all tables, re-migrate, and run seeders
fresh:
    php artisan migrate:fresh --seed

# Run database seeders
seed:
    php artisan db:seed

# Start PHP dev server on :8000
serve:
    php artisan serve

# ── Tauri Desktop App ──

# Run desktop app via Tauri
tauri-dev:
    bunx tauri dev

# Build standalone desktop binary via Tauri
tauri-build:
    bun run build
    bunx tauri build
