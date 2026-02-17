# ╔══════════════════════════════════════════════════════════════════╗
# ║  Promediatum — Development Makefile                            ║
# ╚══════════════════════════════════════════════════════════════════╝

.PHONY: help install update dev serve build fresh reset seed \
        cache-clear optimize test test-unit test-feature lint \
        queue status logs migrate rollback \
        native-dev native-build native-build-mac native-build-win native-status

.DEFAULT_GOAL := help

# ── Colors ──────────────────────────────────────────────────────
GREEN  := \033[0;32m
YELLOW := \033[0;33m
CYAN   := \033[0;36m
NC     := \033[0m

# ════════════════════════════════════════════════════════════════
# HELP
# ════════════════════════════════════════════════════════════════

help: ## Show this help
	@echo ""
	@echo "$(CYAN)Promediatum$(NC) — available commands:"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-16s$(NC) %s\n", $$1, $$2}'
	@echo ""

# ════════════════════════════════════════════════════════════════
# SETUP & INSTALL
# ════════════════════════════════════════════════════════════════

install: ## First-time setup: deps, .env, key, DB, build
	composer install --no-interaction
	npm ci
	@if [ ! -f .env ]; then cp .env.example .env && php artisan key:generate; fi
	@if [ ! -f database/database.sqlite ]; then touch database/database.sqlite; fi
	php artisan migrate --force
	npm run build
	@echo "$(GREEN)✔ Install complete$(NC)"

update: ## Pull latest, install deps, migrate, rebuild
	composer install --no-interaction
	npm ci
	php artisan migrate --force
	npm run build
	php artisan optimize:clear
	@echo "$(GREEN)✔ Update complete$(NC)"

# ════════════════════════════════════════════════════════════════
# DEVELOPMENT
# ════════════════════════════════════════════════════════════════

dev: ## Start Vite dev server (HMR)
	npm run dev

serve: ## Start PHP dev server on :8000
	php artisan serve

start: ## Start both PHP server and Vite in parallel
	npx concurrently -k -n "vite,php" -c "cyan,green" "npm run dev" "php artisan serve"

# ════════════════════════════════════════════════════════════════
# BUILD
# ════════════════════════════════════════════════════════════════

build: ## Production Vite build
	npm run build
	@echo "$(GREEN)✔ Build complete$(NC)"

# ════════════════════════════════════════════════════════════════
# DATABASE
# ════════════════════════════════════════════════════════════════

migrate: ## Run pending migrations
	php artisan migrate

rollback: ## Rollback last migration batch
	php artisan migrate:rollback

fresh: ## Drop all tables, re-migrate, and seed
	php artisan migrate:fresh --seed
	@echo "$(GREEN)✔ Fresh database ready$(NC)"

reset: ## Recreate SQLite file and migrate (no seed)
	rm -f database/database.sqlite
	touch database/database.sqlite
	php artisan migrate
	@echo "$(GREEN)✔ Database reset$(NC)"

seed: ## Run database seeders
	php artisan db:seed
	@echo "$(GREEN)✔ Seeding complete$(NC)"

# ════════════════════════════════════════════════════════════════
# CACHE & OPTIMIZATION
# ════════════════════════════════════════════════════════════════

cache-clear: ## Clear all framework caches
	php artisan optimize:clear
	@echo "$(GREEN)✔ All caches cleared$(NC)"

optimize: ## Cache config, routes, views for production
	php artisan optimize
	php artisan view:cache
	@echo "$(GREEN)✔ Optimized for production$(NC)"

# ════════════════════════════════════════════════════════════════
# QUEUE
# ════════════════════════════════════════════════════════════════

queue: ## Start the queue worker
	php artisan queue:work --tries=3 --timeout=60

queue-retry: ## Retry all failed jobs
	php artisan queue:retry all

# ════════════════════════════════════════════════════════════════
# TESTING
# ════════════════════════════════════════════════════════════════

test: ## Run full test suite
	php artisan test

test-unit: ## Run unit tests only
	php artisan test --testsuite=Unit

test-feature: ## Run feature tests only
	php artisan test --testsuite=Feature

# ════════════════════════════════════════════════════════════════
# LINTING & VALIDATION
# ════════════════════════════════════════════════════════════════

lint: ## PHP syntax check on all app files
	@find app -name "*.php" -exec php -l {} \; 2>&1 | grep -v "No syntax errors";\
	 echo "$(GREEN)✔ PHP lint passed$(NC)"

# ════════════════════════════════════════════════════════════════
# STATUS & DIAGNOSTICS
# ════════════════════════════════════════════════════════════════

status: ## Show app environment, routes, migrations
	@echo "$(CYAN)── Environment ──$(NC)"
	@php artisan env
	@echo ""
	@echo "$(CYAN)── Migrations ──$(NC)"
	@php artisan migrate:status
	@echo ""
	@echo "$(CYAN)── Routes ──$(NC)"
	@php artisan route:list --compact
	@echo ""
	@echo "$(CYAN)── Queue ──$(NC)"
	@php artisan queue:failed 2>/dev/null || echo "  No failed jobs table"

logs: ## Tail Laravel log
	@tail -f storage/logs/laravel.log
	make start

# ════════════════════════════════════════════════════════════════
# NATIVEPHP DESKTOP
# ════════════════════════════════════════════════════════════════

native-dev: ## Run NativePHP desktop in development mode
	@echo "$(CYAN)Starting NativePHP dev server...$(NC)"
	php artisan native:serve

native-build: ## Build desktop app for current platform
	@echo "$(CYAN)Building NativePHP desktop app...$(NC)"
	npm run build
	php artisan native:build

native-build-mac: ## Build desktop app for macOS (Apple Silicon)
	@echo "$(CYAN)Building for macOS (arm64)...$(NC)"
	npm run build
	php artisan native:build mac --arch=arm64

native-build-win: ## Build desktop app for Windows
	@echo "$(CYAN)Building for Windows...$(NC)"
	npm run build
	php artisan native:build win

native-status: ## Show NativePHP desktop diagnostics
	@echo "$(CYAN)── NativePHP Configuration ──$(NC)"
	@php artisan tinker --execute="echo json_encode(app(App\Services\Desktop\DesktopPathResolver::class)->diagnostics(), JSON_PRETTY_PRINT);" 2>/dev/null || echo "  DesktopPathResolver not available"
	@echo ""
