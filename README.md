# Promediatum

Personal academic workspace for independent educators.

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
- **Workspace** — Contextual landing page replacing the traditional dashboard
- **Intelligent FAB** — Context-aware floating action button adapting to current view
- **Customizable UI** — Text weight, theme (light/dark/system), sidebar position, locale (ES/EN)
- **Export-ready architecture** — Prepared for PDF, Excel, CSV, and JSON output

## Tech Stack

| Layer       | Technology                        |
|-------------|-----------------------------------|
| Backend     | Laravel 11                        |
| Frontend    | Vue 3 + Inertia.js v2             |
| Database    | SQLite (local-first)              |
| Styling     | Tailwind CSS 3 (Café Pedagógico)  |
| Build       | Vite 6                            |
| Auth        | Laravel Breeze (modified)         |
| Architecture| Service-layer pattern             |

## Installation

```bash
# Clone the repository
git clone <repo-url> promediatum
cd promediatum

# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database
touch database/database.sqlite
php artisan migrate --seed

# Development servers
npm run dev
php artisan serve
```

Default seeded credentials:
- **Email:** `maria@promediatum.test`
- **Password:** `password1`

## Development Notes

- **Workspace replaces the traditional dashboard.** The `/workspace` route is the authenticated landing page, providing a bento-grid overview of active period, groups, pending observations, and recent activity.
- **Academic calculations are contextual.** Grades, attendance, and observations are always scoped to a specific student + group + period combination.
- **Multi-group membership is supported.** A student can belong to multiple groups within the same period without data merging across groups.
- **Recovery codes replace email-based password reset.** The system is local-only, so password recovery uses pre-generated codes stored by the user.
- **Session lock** allows the teacher to step away without logging out.

## Philosophy

Promediatum follows the **Café Pedagógico** design system — a warm, earthy visual language designed to reduce cognitive load in academic tools. It favors muted tones, generous spacing, and predictable interaction patterns over flashy interfaces. Every element serves the teacher's workflow, not the other way around.

## License

[MIT](LICENSE)
