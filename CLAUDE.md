# Promediatum — Assistant Context & Repository Guidelines

Promediatum is a local-first academic desktop workspace designed for independent educators. It manages attendance, weighted grading, qualitative observations, and PDF/Excel exports with an SQLite database and a calm, low-cognitive-load interface.

## Repository Topology

- **Topology**: Non-Monolithic (Polyglot Workspace)
- **Architecture**: A coordinated polyglot desktop application comprising three tightly coupled technology layers:
  1. **Backend Service Engine (PHP / Laravel 12)**: Domain services, Eloquent ORM, SQLite WAL persistence, and data transformation.
  2. **Frontend UI Layer (TypeScript / Vue 3.5 / Inertia.js v2)**: Reactive components, locale management, and desktop composables executed on the Bun runtime with Vite.
  3. **Desktop Native Shell (Rust / Tauri v2)**: Operating system window management, IPC command execution, native dialogs, system tray, menus, and server lifecycle tracking. See [`src-tauri/CLAUDE.md`](file:///Users/houjousxnnyside/Documents/repos/sxnnyside-scholarships/promediatum/src-tauri/CLAUDE.md) for detailed Rust crate guidelines.

## Stack Profiles & Tooling

| Layer | Language / Runtime | Framework | Correctness & Type Safety | Formatter | Linter | Testing |
|---|---|---|---|---|---|---|
| **Backend** | PHP 8.4 | Laravel 12 | PHPStan / Larastan (Level 6+) | Laravel Pint | PHPStan + Pint | PHPUnit / Pest (`artisan test`) |
| **Frontend** | TypeScript 5.9 / Bun 1.4+ | Vue 3.5 + Inertia v2 | TypeScript (`strict: true`, `vue-tsc`) | Biome | Biome | Bun Test (`bun test`) |
| **Desktop** | Rust (stable pinned) | Tauri v2 | `rustc` strict + clippy (`-D warnings`) | `rustfmt` | Clippy | `cargo test` |

## Task Runner Abstraction Layer (`Justfile`)

All routine operations and quality gates are unified through `just`. Individual stack toolchains (`composer`, `bun`, `cargo`, `pint`, `biome`, `clippy`, `phpstan`) are coordinated by the root `Justfile`:

```bash
just install     # Bootstrap all 3 stacks: composer, bun, cargo, .env, DB, and git hooks
just dev         # Start local web development servers (Vite HMR + Laravel)
just tauri-dev   # Launch full native desktop app with live reload and background server
just build       # Produce production build assets for frontend and desktop bundle
just test        # Run all test suites: bun test, php artisan test, cargo test
just typecheck   # Run static correctness checks: vue-tsc, phpstan, cargo check
just lint        # Run linters across all code: Biome, Pint, Cargo Clippy (-D warnings)
just format      # Format all files: Biome (TS/Vue), Pint (PHP), Rustfmt (Rust)
just check       # Complete quality gate: format, lint, typecheck, test (used by CI)
just clean       # Clear Vite artifacts, PHP cache files, and Cargo build targets
```

### Granular Stack Commands

Individual layers can also be targeted directly:

- **Frontend**: `just test-frontend`, `just typecheck-frontend`, `just lint-frontend`, `just format-frontend`
- **Backend**: `just test-backend`, `just typecheck-backend`, `just lint-backend`, `just format-backend`
- **Rust Desktop**: `just test-rust`, `just typecheck-rust`, `just lint-rust`, `just format-rust`

## Coding Conventions & Quality Expectations

### TypeScript & Vue
- Always use TypeScript (`.ts`) and `<script setup lang="ts">` in all Vue components.
- Domain models and shared interfaces live in `resources/js/types/index.d.ts`. Avoid `any`.
- Keep Inertia pages in `resources/js/Pages/` following clean single-word naming (`Index.vue`, `Create.vue`, `Show.vue`, `Edit.vue`).
- Reusable UI components live in `resources/js/Components/` with prefix `Cp` (e.g., `CpButton.vue`, `CpInput.vue`, `CpCard.vue`).
- Desktop integrations must go through `useTauri.ts` composable with graceful browser fallbacks.

### PHP / Laravel
- Controllers must remain thin, delegating business logic to services in `app/Services/`.
- All Eloquent relationships and methods must declare explicit return types.
- Database queries must avoid N+1 bottlenecks; eager-load relationships where appropriate.
- Database migrations and configurations must support SQLite with WAL mode (`journal_mode=WAL`).
- All tests in `tests/` must pass with in-memory SQLite (`:memory:`).

### Rust / Tauri
- IPC command handlers live in `src-tauri/src/commands.rs` returning `Result<T, String>`.
- Desktop menu definitions live in `src-tauri/src/menu.rs`, tray logic in `src-tauri/src/tray.rs`, and lifecycle management in `src-tauri/src/lifecycle.rs`.
- No unchecked `.unwrap()` calls in production paths; use idiomatic error propagation.
- For in-depth guidelines, consult [`src-tauri/CLAUDE.md`](file:///Users/houjousxnnyside/Documents/repos/sxnnyside-scholarships/promediatum/src-tauri/CLAUDE.md).

## Git & Commit Workflow

- **Conventional Commits**: Commits must adhere to `feat:`, `fix:`, `refactor:`, `chore:`, `style:`, `docs:`, `test:`.
- **Git Hooks**: Pre-commit hook in `.githooks/pre-commit` enforces `just lint` across TypeScript, PHP, and Rust prior to every commit.
- **CI / Quality Gate**: Every PR and push to `main` runs the full `just check` pipeline across all 3 stacks in `.github/workflows/ci.yml`.
