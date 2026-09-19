# Default goal
default:
    @just --list

# ── Primary Polyglot Command Surface (PHP + TypeScript + Rust) ──

# Bootstrap all project dependencies and environments
install:
    composer install --no-interaction
    bun install
    @cargo fetch --manifest-path src-tauri/Cargo.toml 2>/dev/null || true
    @git config core.hooksPath .githooks 2>/dev/null || true
    @if [ ! -f .env ]; then cp .env.example .env && php artisan key:generate; fi
    @if [ ! -f database/database.sqlite ]; then touch database/database.sqlite; fi
    php artisan migrate --force
    bun run build

# Start local development workflow (Vite HMR + Laravel serve)
dev:
    bunx concurrently -k -n "vite,php" -c "cyan,green" "bun run dev" "php artisan serve"

# Produce production build artifacts
build: build-frontend build-rust

build-frontend:
    bun run build

build-rust:
    cargo build --manifest-path src-tauri/Cargo.toml --release

# Run all automated test suites (Frontend + Backend + Rust)
test: test-frontend test-backend test-rust

# Run frontend tests via Bun
test-frontend:
    bun test

# Run backend tests via PHPUnit
test-backend:
    php artisan test

# Run Rust unit and integration tests
test-rust:
    cargo test --manifest-path src-tauri/Cargo.toml

# Run static correctness and type checks across all stacks
typecheck: typecheck-frontend typecheck-backend typecheck-rust

typecheck-frontend:
    bun run typecheck

typecheck-backend:
    ./vendor/bin/phpstan analyse --memory-limit=512M

typecheck-rust:
    cargo check --manifest-path src-tauri/Cargo.toml

# Run static analysis and style audits across all stacks
lint: lint-frontend lint-backend lint-rust

lint-frontend:
    bun run lint

lint-backend:
    ./vendor/bin/pint --test

lint-rust:
    cargo clippy --manifest-path src-tauri/Cargo.toml -- -D warnings

# Automatically apply formatters across all stacks
format: format-frontend format-backend format-rust

format-frontend:
    bun run format

format-backend:
    ./vendor/bin/pint

format-rust:
    cargo fmt --manifest-path src-tauri/Cargo.toml

# Run full quality gate across all stacks
check: format lint typecheck test

# Remove build artifacts, caches, and temporary files across all stacks
clean:
    rm -rf public/build public/hot node_modules/.vite
    cargo clean --manifest-path src-tauri/Cargo.toml
    @if [ -f vendor/autoload.php ]; then php artisan optimize:clear; fi

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

# ── Desktop Application ──

# Download FrankenPHP standalone runtime sidecar for local desktop bundling (macOS arm64 or Linux x64)
setup-sidecar:
    @mkdir -p src-tauri/binaries
    @case "$$(uname -s)-$$(uname -m)" in \
        Darwin-arm64) \
            if [ ! -f src-tauri/binaries/frankenphp-aarch64-apple-darwin ]; then \
                echo "Downloading FrankenPHP for macOS Apple Silicon..."; \
                curl -fSL -o src-tauri/binaries/frankenphp-aarch64-apple-darwin https://github.com/php/frankenphp/releases/download/v1.12.7/frankenphp-mac-arm64; \
                chmod +x src-tauri/binaries/frankenphp-aarch64-apple-darwin; \
            fi ;; \
        Linux-x86_64) \
            if [ ! -f src-tauri/binaries/frankenphp-x86_64-unknown-linux-gnu ]; then \
                echo "Downloading FrankenPHP for Linux x86_64..."; \
                curl -fSL -o src-tauri/binaries/frankenphp-x86_64-unknown-linux-gnu https://github.com/php/frankenphp/releases/download/v1.12.7/frankenphp-linux-x86_64; \
                chmod +x src-tauri/binaries/frankenphp-x86_64-unknown-linux-gnu; \
            fi ;; \
    esac

# Run desktop app with local Laravel backend in a single terminal
tauri-dev:
    bunx concurrently -k -n "php,tauri" -c "green,cyan" "php artisan serve" "bunx @tauri-apps/cli dev"

# Build standalone desktop bundle via Tauri
tauri-build: setup-sidecar
    bun run build
    bunx @tauri-apps/cli build
