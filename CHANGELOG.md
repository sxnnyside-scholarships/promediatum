# Changelog

All notable changes to **Promediatum** are documented here.

This project follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

---

## [1.0.0] — 2026-09-19

### Added

- Standalone zero-dependency runtime bundling via FrankenPHP sidecar binary, enabling Promediatum to run completely offline without pre-installed PHP, Rust, or development tooling.
- Single-command local desktop development environment (`just tauri-dev`) running both Laravel backend and Tauri native window concurrently.
- User-friendly desktop environment card in Settings matching the Café Pedagógico design system.
- Native desktop shell powered by Tauri v2 and Rust, replacing legacy wrapper layers.
- Native IPC commands in Rust (`get_system_info`, `get_app_paths`, `open_path_in_file_manager`, `ping_backend`).
- Official Tauri v2 desktop plugins (`dialog`, `notification`, `window-state`, `fs`, `shell`, `process`).
- Operating system application menus with keyboard shortcuts and desktop system tray icon.
- Desktop reactive bridge composable `useTauri.ts` with browser fallback support.
- Native notification dispatching for background operations via `useToast.ts`.
- SQLite Write-Ahead Logging (`journal_mode=WAL`) and busy timeout configuration.
- Two-Factor Authentication with QR codes and recovery code verification.
- Fast frontend unit test suite utilizing Bun Test (`bun test`) with locale parity validation.
- Unified polyglot task runner in `Justfile` coordinating TypeScript, PHP, and Rust toolchains.
- Export Engine — PDF, Excel, CSV, and JSON export generation with customizable templates, export history tracking, and download management.
- Template System — User-defined export templates with column selection, sorting, grouping, and format configuration per template.
- SMTP Integration — Per-user encrypted SMTP configuration with connection testing, verification flow, and email-based report delivery.
- Backup System — AES-256-CBC encrypted `.pdbk` backup files with create, restore, validate, list, prune, and download operations via dedicated UI.
- Insights Engine — Composite risk scoring, trend analysis, attendance pattern recognition, and observation volume analysis.
- Automation Engine — Rule-based suggested actions system with severity escalation and deduplication.
- Notification Service — User-scoped notification storage and automatic processing of high/critical automation actions.
- Performance Optimization — Batch student summary queries (N+1 elimination) and composite database indexes.
- Workspace Enhancements — Insights panel with risk/attendance/trend cards, Suggested Actions bento section.
- Desktop Path Resolver — OS-aware storage paths (macOS/Windows/Linux) for database, exports, backups, and logs.

### Changed

- Migrated all Vue views and components to strict TypeScript (`<script setup lang="ts">`).
- Upgraded Biome configuration with strict linter rules and clean formatting.
- Replaced monolithic translations with modular locale dictionaries (`en.ts`, `es.ts`).
- Updated CI/CD workflows to test and build PHP, Bun, and Rust stacks concurrently.
- Replaced legacy application shell with native Tauri v2 architecture.

---

## [0.1.0] — 2026-02-13

### Added

- Authentication system with recovery token-based password reset.
- Session lock / unlock flow for stepping away without logout.
- Profile page with read-first editing pattern.
- Settings with personalization options (theme, locale, sidebar position, text weight, FAB toggle).
- Period management with activation control and days-remaining tracking.
- Groups module with archival, student assignment, and grade categories.
- Students module with multi-group membership and risk indicators.
- Attendance tracking with consecutive absence detection (3+ alert).
- Grade system with weighted categories and normalized percentage scoring.
- Observations system with typed notes and resolution workflow.
- Workspace landing page replacing traditional dashboard (bento grid layout).
- Intelligent FAB — context-aware floating action button per route.
- CpIcon centralized icon component (25+ named icons).
- Café Pedagógico design system (warm tonal palette, custom typography, spacing tokens).
- Bilingual interface (Spanish / English) with runtime locale switching.
- Development seeders with realistic academic data for risk detection testing.
- AcademicService service-layer for domain calculations.

### Changed

- Dashboard renamed to Workspace (`/workspace` route).
- Full-width layout enforcement on all authenticated pages.
- Create/edit views use 70/30 grid layout with contextual tip panels.
- Icon system standardized — all inline SVGs replaced with CpIcon component.
- Settings text weight descriptions use human-readable labels instead of CSS terminology.
- Password visibility toggle hover changed to accent color.

### Fixed

- Period days-remaining calculation for ended periods.
- Redirect loop in authentication flow when session is locked.
- Narrow container layout issues on form pages.

---

[Unreleased]: https://github.com/sxnnyside-scholarships/promediatum/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/sxnnyside-scholarships/promediatum/releases/tag/v1.0.0
[0.1.0]: https://github.com/sxnnyside-scholarships/promediatum/releases/tag/v0.1.0
