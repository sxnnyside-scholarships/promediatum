<?php

/**
 * Promediatum — Internal Version Configuration
 *
 * This file provides the single source of truth for the application's
 * version identity. It is used by:
 *   - The About dialog/screen
 *   - Diagnostic reporting
 *   - The safe migration flow (to detect post-update state)
 *
 * Increment `version` with every GitHub Release.
 * Increment `build_number` with every build.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Application Version (Semantic Versioning)
    |--------------------------------------------------------------------------
    | Format: MAJOR.MINOR.PATCH
    | - MAJOR: Breaking changes or significant rewrites
    | - MINOR: New features, backward-compatible
    | - PATCH: Bug fixes, refinements
    */
    'version' => env('APP_VERSION', '1.0.0'),

    /*
    |--------------------------------------------------------------------------
    | Build Number
    |--------------------------------------------------------------------------
    | Auto-incremented integer for each build. Used internally to track
    | exact builds in diagnostics and update comparison.
    */
    'build_number' => env('APP_BUILD_NUMBER', 1),

    /*
    |--------------------------------------------------------------------------
    | Release Channel
    |--------------------------------------------------------------------------
    | Determines which GitHub Release channel to check for updates.
    | Supported: "stable", "beta"
    |
    | "stable" — Only GA releases (default).
    | "beta"   — Pre-release tags for early testing.
    */
    'release_channel' => env('APP_RELEASE_CHANNEL', 'stable'),

    /*
    |--------------------------------------------------------------------------
    | Release Date
    |--------------------------------------------------------------------------
    | ISO 8601 date of this release. Useful for About screen display.
    */
    'release_date' => '2026-09-19',

    /*
    |--------------------------------------------------------------------------
    | Project Identity
    |--------------------------------------------------------------------------
    */
    'app_id' => 'com.sxnnyside.promediatum.desktop',
    'organization' => 'Sxnnyside Scholarships',
    'website' => 'https://www.sxnnysideproject.com',
    'repository' => 'https://github.com/sxnnyside-scholarships/promediatum',
    'support_email' => 'support.sxnnyside@sxnnysideproject.com',
    'security_email' => 'security.sxnnyside@sxnnysideproject.com',

];
