# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
