# Promediatum Desktop Shell (`src-tauri`) — Assistant Context & Guidelines

This directory contains the native desktop shell for Promediatum, implemented in Rust using Tauri v2. It provides native OS capabilities, IPC command execution, window state persistence, system tray, application menus, and background server lifecycle management.

## Stack Profile: Rust (Tauri v2)

| Category | Tool / Configuration |
|---|---|
| **Runtime & Build** | Cargo, Rust 1.80+ (pinned in `rust-toolchain.toml`) |
| **Framework** | Tauri v2 (`tauri`, `tauri-build`) |
| **Formatter** | `rustfmt` |
| **Linter** | `clippy` (`cargo clippy -- -D warnings`) |
| **Testing** | `cargo test` |
| **Native Plugins** | `dialog`, `notification`, `window-state`, `fs`, `shell`, `process` |

## Architecture & Module Structure

```
src-tauri/
├── capabilities/
│   └── default.json          # Tauri v2 security permissions and capabilities
├── src/
│   ├── commands.rs           # Native IPC #[tauri::command] handlers & unit tests
│   ├── lifecycle.rs          # ServerManager, port probing, child process tracking
│   ├── menu.rs               # Native OS application menu & keyboard accelerators
│   ├── tray.rs               # System tray icon, status menu & window toggle
│   ├── lib.rs                # Application builder, plugin registration & entrypoint
│   └── main.rs               # Binary entrypoint invoking lib::run()
├── Cargo.toml                # Crate manifest with dependencies and features
├── rust-toolchain.toml       # Toolchain pin: stable channel with clippy & rustfmt
└── tauri.conf.json           # Tauri bundle configuration, identifier, windows
```

## Available IPC Commands (`commands.rs`)

| Command | Signature | Description |
|---|---|---|
| `get_system_info` | `() -> Result<SystemInfo, String>` | OS name, architecture, kernel, and Tauri version |
| `get_app_paths` | `(app: AppHandle) -> Result<AppPaths, String>` | Resolves app config, data, cache, and home directories |
| `open_path_in_file_manager` | `(path: String) -> Result<(), String>` | Reveals a directory or file in Finder / Explorer |
| `ping_backend` | `(url: Option<String>) -> Result<BackendStatus, String>` | Probes Laravel backend port/URL availability and latency |

## Security & Capabilities (`capabilities/default.json`)

Tauri v2 enforces fine-grained permissions. When adding new native features:
1. Ensure the relevant plugin permission is declared in `capabilities/default.json`.
2. Do not use wildcards (`*`) for file system access; restrict access to app data directories or user-selected paths via dialogs.
3. Validate user inputs before passing them to native system APIs or shell commands.

## Task Runner Commands

While direct `cargo` commands work, developers and CI use the root `Justfile`:

```bash
just test-rust       # cargo test --manifest-path src-tauri/Cargo.toml
just typecheck-rust  # cargo check --manifest-path src-tauri/Cargo.toml
just lint-rust       # cargo clippy --manifest-path src-tauri/Cargo.toml -- -D warnings
just format-rust     # cargo fmt --manifest-path src-tauri/Cargo.toml
```

## Coding Conventions

- **Error Handling**: Use `Result<T, String>` for all IPC commands so errors serialize cleanly across the Tauri boundary to JavaScript promises.
- **Safety**: Avoid `unsafe` blocks. Do not use `.unwrap()` in production paths; use `?`, `.map_err()`, or default fallbacks with descriptive errors.
- **Async Runtime**: Long-running operations or network probes should use async tasks or Tokio threads spawned via `tauri::async_runtime::spawn`.
- **Formatting & Lints**: All code must pass `cargo fmt --check` and `cargo clippy -- -D warnings` with zero warnings before committing.
