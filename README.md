# Promediatum

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![License](https://img.shields.io/badge/License-MIT-green)
[![CI](https://github.com/sxnnyside-scholarships/promediatum/workflows/CI/badge.svg)](https://github.com/sxnnyside-scholarships/promediatum/actions)

<p align="center">
  <strong>Local-First ✦ Café Pedagógico ✦ Zero Cloud Dependencies</strong><br>
  <em>Personal academic workspace for independent educators.</em>
</p>

<p align="center">
  <a href="#about">About</a> ✦
  <a href="#features">Features</a> ✦
  <a href="#installation">Installation</a> ✦
  <a href="#usage">Usage</a> ✦
  <a href="#architecture">Architecture</a> ✦
  <a href="#contributing">Contributing</a>
</p>

---

## About

**Promediatum** is a local-first academic desktop workspace designed for independent educators who manage attendance, weighted grading, observations, and exports with zero cognitive friction.

It exists to free teachers from convoluted cloud spreadsheets and monolithic school management systems. All pedagogical data, student profiles, and historical records remain strictly on the local machine with an SQLite database and encrypted backups.

Promediatum couples a high-performance native desktop shell in Rust (Tauri v2) with an encapsulated Laravel 12 backend engine and a calm Vue 3.5 interface.

### Philosophy

> *"Pedagogical clarity over administrative clutter. Local-first, private by default, and crafted to reduce cognitive fatigue."*

This is a Sxnnyside Scholarships release, part of the Sxnnyside Project ecosystem.

## Features

- **Academic Workspace**: Contextual command center with attendance KPIs, performance trends, and action suggestions.
- **Period & Group Tracking**: Comprehensive academic term scheduling with course assignment and group archiving.
- **Student Registry & Attendance**: Multi-group enrollment with single-click attendance capturing and consecutive absence alerts.
- **Weighted Grading Engine**: Configurable category weighting, automatic grade normalization, and student summary calculations.
- **Qualitative Observations**: Structured student progress tracking with severity categorization and resolution status.
- **Multi-Format Exports**: PDF, Excel, CSV, and JSON report generation with user-defined templates and email delivery.
- **Encrypted Local Backups**: AES-256-CBC encrypted `.pdbk` backup files with native dialog picking and restoration verification.
- **Native Desktop Shell**: Tauri v2 integration providing OS menus, system tray, notifications, and window state persistence.

## Installation

### Prerequisites

- PHP (>= 8.4)
- Composer (>= 2.0)
- Bun (>= 1.4)
- Rust & Cargo (>= 1.80, stable)
- Just (>= 1.0)

### From Source

```bash
git clone https://github.com/sxnnyside-scholarships/promediatum.git
cd promediatum

# Single-command bootstrap (installs PHP, Bun, Cargo dependencies, .env, DB, and git hooks)
just install

# Launch desktop development application
just tauri-dev
```

## Usage

```bash
# Launch desktop development application (Tauri + Vite HMR + Laravel backend)
just tauri-dev

# Or run in standard web mode
just dev

# Run the unified quality check across all 3 stacks (format, lint, typecheck, test)
just check
```

## Architecture

```
promediatum/
├── app/          # Backend Laravel 12 domain services, models, and controllers
├── resources/    # Frontend Vue 3.5, Inertia.js v2 pages, and TypeScript composables
├── src-tauri/    # Native desktop shell, Rust IPC commands, system tray, and menus
├── database/     # SQLite database, migrations, factories, and development seeders
├── config/       # Application, database WAL, and version configurations
└── tests/        # Backend PHPUnit tests and frontend Bun test suites
```

## Contributing

Contributions are accepted. See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

Before contributing, read the [Code of Conduct](CODE_OF_CONDUCT.md).

## License

This project is licensed under the MIT License — see the [LICENSE](LICENSE) file for details.

---

<p align="center">
  <strong>Promediatum</strong> — A Sxnnyside Scholarships Release<br>
  <em>&copy; 2026 Sxnnyside Project</em>
</p>
