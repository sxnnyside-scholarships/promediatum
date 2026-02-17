# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-02-17 — Initial Desktop Release

### Added

- **Export Engine** — PDF (DomPDF), Excel (Maatwebsite), CSV, and JSON export generation with customizable templates, export history tracking, and download management
- **Template System** — User-defined export templates with column selection, sorting, grouping, and format configuration per template
- **SMTP Integration** — Per-user encrypted SMTP configuration with connection testing, verification flow, and email-based report delivery
- **Desktop Packaging** — NativePHP + Electron standalone desktop application for macOS and Windows with proper app identity, bundle ID, and OS-native menus
- **Backup System** — AES-256-CBC encrypted `.pdbk` backup files with create, restore, validate, list, prune, and download operations via dedicated UI
- **Auto-Update System** — GitHub Releases-based update checking with safe migration (pre-update backup, automatic rollback on failure), version comparison, and release channel support (stable/beta)
- **Insights Engine** — Composite risk scoring (grade avg × 0.40 + attendance × 0.25 + absence streak × 0.15 + observations × 0.20), trend analysis, attendance pattern recognition, and observation volume analysis with severity-ranked results
- **Automation Engine** — Rule-based suggested actions system (HighRiskObservation, ConsecutiveAbsencesFollowUp, PeriodEndingExport, UnresolvedObservationsReview) with severity escalation, deduplication, and 15-minute cache
- **Notification Service** — Cache-based in-app notifications with 24-hour cooldown per rule+context, user-scoped storage, and automatic processing of high/critical automation actions
- **Performance Optimization** — Batch student summary queries (N+1 → 3 queries), 8 composite database indexes, InsightEngine + AutomationEngine cache with model observer invalidation
- **Workspace Enhancements** — Insights panel with risk/attendance/trend cards, Suggested Actions bento section, automation-driven FAB prioritization
- **Desktop Path Resolver** — OS-aware storage paths (macOS/Windows/Linux) for database, exports, backups, and logs
- **Desktop Notification Service** — Native OS notifications for export completion, backup completion, and error reporting
- **Security hardening** — Dev tools disabled in production, no debug routes, encrypted SMTP passwords, AES-256 backup encryption, safe migration with rollback
- **Production build configuration** — Proper app ID (`com.sxnnyside.promediatum.desktop`), auto-update feed, stable release channel, .env.example for production

### Changed

- NativePHP app ID updated to `com.sxnnyside.promediatum.desktop`
- Default update channel changed from `latest` to `stable`
- Default release type changed from `draft` to `release`
- Main window routes to `/workspace` instead of `/dashboard`
- Dev tools menu item hidden in production builds
- README updated with desktop build instructions, local-only philosophy, and full feature listing

## [0.1.0] - 2026-02-13

### Added

- Authentication system with recovery token-based password reset
- Session lock / unlock flow for stepping away without logout
- Profile page with read-first editing pattern
- Settings with personalization options (theme, locale, sidebar position, text weight, FAB toggle)
- Period management with activation control and days-remaining tracking
- Groups module with archival, student assignment, and grade categories
- Students module with multi-group membership and risk indicators
- Attendance tracking with consecutive absence detection (3+ alert)
- Grade system with weighted categories and normalized percentage scoring
- Observations system with typed notes and resolution workflow
- Workspace landing page replacing traditional dashboard (bento grid layout)
- Intelligent FAB — context-aware floating action button per route
- CpIcon centralized icon component (25+ named icons)
- Café Pedagógico design system (warm tonal palette, custom typography, spacing tokens)
- Bilingual interface (Spanish / English) with runtime locale switching
- Development seeders with realistic academic data for risk detection testing
- AcademicService service-layer for domain calculations

### Changed

- Dashboard renamed to Workspace (`/workspace` route)
- Full-width layout enforcement on all authenticated pages
- Create/edit views use 70/30 grid layout with contextual tip panels
- Icon system standardized — all inline SVGs replaced with CpIcon component
- Settings text weight descriptions use human-readable labels instead of CSS terminology
- Password visibility toggle hover changed to accent color

### Fixed

- Period days-remaining calculation for ended periods
- Redirect loop in authentication flow when session is locked
- Narrow container layout issues on form pages (removed `max-w-lg` constraints)
