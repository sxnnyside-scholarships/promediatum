# Promediatum

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/HoujouSxnnyside/promediatum/releases)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Personal academic workspace for independent educators.

**Website:** [sxnnysideproject.com](https://www.sxnnysideproject.com)
**Support:** support.sxnnyside@sxnnysideproject.com
**Security:** security.sxnnyside@sxnnysideproject.com
**Repository:** [github.com/HoujouSxnnyside/promediatum](https://github.com/HoujouSxnnyside/promediatum)

## Overview

Promediatum is a local-first, single-user academic workspace designed for teachers who manage their own grading, attendance, and student observation workflows. It provides a structured, calm interface built around the Café Pedagógico design system.

This is not a SaaS. It runs entirely on your machine with a local SQLite database — no cloud dependencies, no external accounts, no data leaves your device.

## Core Features

- **Period management** — Academic term tracking with activation control
- **Groups** — Course/class organization linked to periods
- **Students** — Student registry with multi-group membership per period
- **Attendance** — Daily attendance tracking with consecutive absence detection
- **Weighted grades** — Category-based grading with configurable weights
- **Observations** — Typed notes (performance, behavior, achievement, follow-up) with resolution tracking
- **Export engine** — PDF, Excel, CSV, and JSON exports with customizable templates
- **SMTP integration** — Email report delivery with per-user encrypted configuration
- **Workspace** — Contextual landing page with insights and suggested actions
- **Intelligent FAB** — Context-aware floating action button adapting to current view
- **Insights engine** — Risk detection, trend analysis, and attendance pattern recognition
- **Automation engine** — Rule-based suggested actions with notification integration
- **Desktop packaging** — Standalone macOS and Windows desktop app via NativePHP
- **Encrypted backups** — AES-256-CBC encrypted .pdbk backup files
- **Auto-update** — GitHub Releases-based update system with safe migration
- **Customizable UI** — Text weight, theme (light/dark/system), sidebar position, locale (ES/EN)

## Local-Only Philosophy

Promediatum stores all data in a local SQLite database. There are no cloud services, no telemetry, no external API calls. Your academic data stays on your machine. Password recovery uses locally-stored recovery codes instead of email-based flows.

## Tech Stack

| Layer       | Technology                        |
|-------------|-----------------------------------|
| Backend     | Laravel 12                        |
| Frontend    | Vue 3.5 + Inertia.js v2 + TypeScript |
| Package Mgr | Bun                               |
| Database    | SQLite (local-first)              |
| Styling     | Tailwind CSS 3 (Café Pedagógico)  |
| Build       | Vite 6 (TypeScript)               |
| Linter/Fmt  | Biome                             |
| Auth        | Laravel Breeze (modified)         |
| Architecture| Service-layer pattern             |

## Installation

### Local Development

```bash
# Clone the repository
git clone https://github.com/HoujouSxnnyside/promediatum.git
cd promediatum

# Install dependencies
composer install
bun install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database
touch database/database.sqlite
php artisan migrate --seed

# Fast bootstrap & dev server
just install
just dev
```

Default seeded credentials:
- **Email:** `maria@promediatum.test`
- **Password:** `password1`

## Command Surface (`Justfile`)

```bash
just install             # Bootstrap dependencies, .env, database, and git hooks
just dev                 # Start PHP server + Vite HMR
just build               # Production Vite build
just test                # Run full PHPUnit test suite
just typecheck           # TypeScript (vue-tsc) & PHPStan checks
just lint                # Biome & Pint style audits
just format              # Automatically format JS, TS, Vue, and PHP files
just check               # Run full quality gate (format, lint, typecheck, test)
just clean               # Clear build artifacts and caches
```

## Philosophy

Promediatum follows the **Café Pedagógico** design system — a warm, earthy visual language designed to reduce cognitive load in academic tools. It favors muted tones, generous spacing, and predictable interaction patterns over flashy interfaces. Every element serves the teacher's workflow, not the other way around.

## Security

For security vulnerabilities, please see [SECURITY.md](SECURITY.md).

## License

[MIT](LICENSE) — Copyright © 2026 Sxnnyside Scholarships
