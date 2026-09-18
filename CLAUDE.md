# Promediatum — Assistant Context & Repository Guidelines

Promediatum is a local-first academic workspace designed for independent educators. It manages attendance, weighted grading, observations, and exports with an SQLite database and a calm, low-cognitive-load interface.

## Repository Topology

- **Topology**: Monolithic
- **Architecture**: Single unified application consisting of a Laravel 12 backend (service-layer pattern) paired with an Inertia.js v2 + Vue 3.5 TypeScript frontend.

## Tech Stack & Tooling

| Component | Toolchain | Role |
|---|---|---|
| **Backend Framework** | Laravel 12 / PHP 8.4 | API, routing, SQLite persistence, business logic |
| **Package Manager (PHP)** | Composer 2 | Backend dependency resolution |
| **Frontend Framework** | Vue 3.5 + Inertia.js v2 | Reactive UI components and state |
| **Frontend Language** | TypeScript 5.9 (Strict) | Type safety, domain model contracts |
| **Package Manager (JS/TS)** | Bun 1.4+ | Fast JS dependency management and execution |
| **Bundler** | Vite 6 | Asset compilation and HMR |
| **Linter & Formatter (JS/TS/CSS)** | Biome | Sub-millisecond formatting and linting |
| **PHP Formatter & Linter** | Laravel Pint + Larastan | PSR-12 code style and static analysis |
| **Task Runner** | Just (`Justfile`) | Uniform command surface |

## Task Runner Commands

All routine development and quality gate commands are run via `just`:

```bash
just install     # Bootstrap all dependencies, .env, DB, and git hooks
just dev         # Start local dev servers (Vite HMR + Laravel)
just build       # Produce production build assets via Vite
just test        # Run PHPUnit test suite
just typecheck   # Run TypeScript (vue-tsc) & PHPStan static analysis
just lint        # Run Biome & Pint style audits
just format      # Automatically format all JS, TS, Vue, and PHP files
just check       # Run the full quality gate (format, lint, typecheck, test)
just clean       # Clear caches and build artifacts
```

## Coding Conventions

### TypeScript & Vue
- Always use TypeScript for new frontend files (`.ts`) and specify `<script setup lang="ts">` in Vue components.
- Interfaces for models and shared props live in `resources/js/types/index.d.ts`. Avoid `any`.
- Inertia pages live under `resources/js/Pages/` and follow single-word naming (`Index.vue`, `Create.vue`, `Show.vue`, `Edit.vue`).
- Reusable UI components live in `resources/js/Components/` with prefix `Cp` (e.g. `CpButton.vue`, `CpInput.vue`).
- Do not add explicit `.js` extensions to TypeScript module imports.

### PHP / Laravel
- Controllers should remain thin and delegate domain logic to services in `app/Services/`.
- All models use strict Eloquent relationships with return types.
- Ensure all tests in `tests/` pass with SQLite `:memory:` connection.
- Code style must pass `vendor/bin/pint --test` without exceptions.

### Git & Commits
- Commit messages must adhere to Conventional Commits: `feat:`, `fix:`, `refactor:`, `chore:`, `style:`, `docs:`, `test:`.
- Pre-commit git hooks automatically verify formatting and linting before commits are finalized.
