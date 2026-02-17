# Security Policy

## Reporting a Vulnerability

If you discover a security vulnerability in Promediatum, please report it responsibly.

**Do not open a public GitHub issue for security vulnerabilities.**

Instead, send an email to:

**security.sxnnyside@sxnnysideproject.com**

Include:
- Description of the vulnerability
- Steps to reproduce (if applicable)
- Potential impact assessment
- Suggested fix (if any)

## Response Timeline

- **Acknowledgment:** Within 48 hours of receipt
- **Initial assessment:** Within 7 days
- **Fix or mitigation:** As soon as reasonably possible, depending on severity

## Responsible Disclosure

We ask that you:

1. **Do not** disclose the vulnerability publicly until we have issued a fix or explicitly agreed to disclosure.
2. **Do not** exploit the vulnerability beyond what is necessary to demonstrate it.
3. **Do not** access, modify, or delete data belonging to other users (Promediatum is single-user and local-only, but this applies to any shared testing environments).

We will credit reporters in the release notes unless you prefer to remain anonymous.

## Scope

This policy applies to the Promediatum desktop application and its source code hosted at:

https://github.com/HoujouSxnnyside/promediatum

## Security Design

Promediatum is a **local-first, single-user** desktop application. Key security properties:

- **No cloud services** — All data is stored locally in SQLite. No telemetry, no external API calls.
- **Encrypted backups** — Backup files use AES-256-CBC encryption.
- **Encrypted SMTP credentials** — SMTP passwords are stored using Laravel's `encrypted` cast (AES-256-CBC via APP_KEY).
- **No plaintext secrets** — Sensitive environment variables are excluded from desktop builds via `cleanup_env_keys`.
- **Recovery codes** — Password reset uses locally-stored recovery codes instead of email-based flows.
- **Session lock** — Users can lock their session without logging out.
- **Production hardening** — Dev tools disabled, debug mode off, no test/debug routes exposed.

## Contact

- **Security issues:** security.sxnnyside@sxnnysideproject.com
- **General support:** support.sxnnyside@sxnnysideproject.com
- **Website:** https://www.sxnnysideproject.com
