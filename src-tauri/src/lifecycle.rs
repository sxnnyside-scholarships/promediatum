use std::net::{SocketAddr, TcpStream};
use std::path::PathBuf;
use std::process::Child;
use std::sync::{Arc, Mutex};
use std::time::{Duration, Instant};
use tauri::{AppHandle, Manager};

#[derive(Clone, Default)]
pub struct ServerManager {
    child: Arc<Mutex<Option<Child>>>,
}

enum PhpRuntime {
    FrankenPhp(PathBuf),
    SystemPhp(PathBuf),
}

impl ServerManager {
    pub fn new() -> Self {
        Self {
            child: Arc::new(Mutex::new(None)),
        }
    }

    /// Check if local server is listening on specified port
    pub fn is_server_listening(&self, host: &str, port: u16) -> bool {
        let addr_str = format!("{}:{}", host, port);
        if let Ok(sock) = addr_str.parse::<SocketAddr>() {
            TcpStream::connect_timeout(&sock, Duration::from_millis(300)).is_ok()
        } else {
            false
        }
    }

    /// Locate the appropriate PHP runtime (bundled FrankenPHP sidecar or system PHP)
    fn resolve_runtime(app: Option<&AppHandle>) -> Option<PhpRuntime> {
        // 1. Check next to current executable (packaged app / Contents/MacOS)
        if let Ok(exe_path) = std::env::current_exe() {
            if let Some(parent) = exe_path.parent() {
                let direct_bin = parent.join(if cfg!(windows) {
                    "frankenphp.exe"
                } else {
                    "frankenphp"
                });
                if direct_bin.is_file() {
                    log::info!(
                        "Found bundled FrankenPHP beside executable: {:?}",
                        direct_bin
                    );
                    return Some(PhpRuntime::FrankenPhp(direct_bin));
                }
            }
        }

        // 2. Check Tauri resource directory
        if let Some(app_handle) = app {
            if let Ok(resource_dir) = app_handle.path().resource_dir() {
                let candidates = [
                    resource_dir.join("binaries").join(if cfg!(windows) {
                        "frankenphp.exe"
                    } else {
                        "frankenphp"
                    }),
                    resource_dir.join(if cfg!(windows) {
                        "frankenphp.exe"
                    } else {
                        "frankenphp"
                    }),
                ];
                for cand in candidates {
                    if cand.is_file() {
                        log::info!("Found bundled FrankenPHP in resources: {:?}", cand);
                        return Some(PhpRuntime::FrankenPhp(cand));
                    }
                }
            }
        }

        // 3. Check local development binaries folder (src-tauri/binaries/)
        let target_name = if cfg!(all(target_os = "macos", target_arch = "aarch64")) {
            "frankenphp-aarch64-apple-darwin"
        } else if cfg!(all(target_os = "macos", target_arch = "x86_64")) {
            "frankenphp-x86_64-apple-darwin"
        } else if cfg!(all(target_os = "linux", target_arch = "x86_64")) {
            "frankenphp-x86_64-unknown-linux-gnu"
        } else if cfg!(all(target_os = "windows", target_arch = "x86_64")) {
            "frankenphp-x86_64-pc-windows-msvc.exe"
        } else {
            "frankenphp"
        };

        let dev_candidates = [
            PathBuf::from("src-tauri")
                .join("binaries")
                .join(target_name),
            PathBuf::from("binaries").join(target_name),
        ];

        for cand in dev_candidates {
            if cand.is_file() {
                log::info!("Found development FrankenPHP sidecar: {:?}", cand);
                return Some(PhpRuntime::FrankenPhp(cand));
            }
        }

        // 4. Fallback to system PHP in PATH (for maintainer dev mode)
        let system_php_name = if cfg!(windows) { "php.exe" } else { "php" };
        if let Ok(output) = std::process::Command::new(system_php_name)
            .arg("-v")
            .output()
        {
            if output.status.success() {
                log::info!("Using system PHP from PATH: {}", system_php_name);
                return Some(PhpRuntime::SystemPhp(PathBuf::from(system_php_name)));
            }
        }

        None
    }

    /// Resolve the root application directory containing artisan
    fn resolve_app_root(app: Option<&AppHandle>) -> PathBuf {
        // 1. Current working directory (dev mode)
        if let Ok(cwd) = std::env::current_dir() {
            if cwd.join("artisan").is_file() {
                return cwd;
            }
        }

        // 2. Resource directory inside packaged app bundle
        if let Some(app_handle) = app {
            if let Ok(res) = app_handle.path().resource_dir() {
                if res.join("artisan").is_file() {
                    return res;
                }
            }
        }

        PathBuf::from(".")
    }

    /// Resolve SQLite database path in OS user data directory
    fn resolve_database_path() -> PathBuf {
        let base = if cfg!(target_os = "macos") {
            dirs::home_dir()
                .map(|h| {
                    h.join("Library")
                        .join("Application Support")
                        .join("Promediatum")
                })
                .unwrap_or_else(|| PathBuf::from("/tmp/Promediatum"))
        } else {
            dirs::data_dir()
                .map(|d| d.join("Promediatum"))
                .unwrap_or_else(|| PathBuf::from("/tmp/Promediatum"))
        };

        base.join("database").join("database.sqlite")
    }

    /// Ensure backend is ready. If not running, launches local FrankenPHP/PHP supervisor
    pub fn ensure_backend_ready(&self, app: Option<&AppHandle>, host: &str, port: u16) -> bool {
        if self.is_server_listening(host, port) {
            log::info!("Promediatum server is already running on {}:{}", host, port);
            return true;
        }

        log::info!(
            "Server not detected on {}:{}. Resolving standalone runtime...",
            host,
            port
        );

        let runtime = match Self::resolve_runtime(app) {
            Some(r) => r,
            None => {
                log::error!("No PHP runtime found (neither FrankenPHP sidecar nor system PHP).");
                return false;
            }
        };

        let app_root = Self::resolve_app_root(app);
        let db_path = Self::resolve_database_path();

        // Ensure database directory exists
        if let Some(db_dir) = db_path.parent() {
            let _ = std::fs::create_dir_all(db_dir);
        }

        let is_first_run = !db_path.exists();
        if is_first_run {
            let _ = std::fs::File::create(&db_path);
            log::info!("Created initial SQLite database at {:?}", db_path);
        }

        // Run migrations on first setup or app startup
        let mut migrate_cmd = match &runtime {
            PhpRuntime::FrankenPhp(bin) => {
                let mut cmd = std::process::Command::new(bin);
                cmd.arg("php-cli")
                    .arg("artisan")
                    .arg("migrate")
                    .arg("--force");
                cmd
            }
            PhpRuntime::SystemPhp(bin) => {
                let mut cmd = std::process::Command::new(bin);
                cmd.arg("artisan").arg("migrate").arg("--force");
                cmd
            }
        };

        migrate_cmd
            .current_dir(&app_root)
            .env("DB_CONNECTION", "sqlite")
            .env("DB_DATABASE", db_path.to_string_lossy().as_ref());

        let _ = migrate_cmd.output();

        // Spawn server process
        let mut server_cmd = match &runtime {
            PhpRuntime::FrankenPhp(bin) => {
                let mut cmd = std::process::Command::new(bin);
                cmd.arg("php-cli")
                    .arg("artisan")
                    .arg("serve")
                    .arg(format!("--host={}", host))
                    .arg(format!("--port={}", port));
                cmd
            }
            PhpRuntime::SystemPhp(bin) => {
                let mut cmd = std::process::Command::new(bin);
                cmd.arg("artisan")
                    .arg("serve")
                    .arg(format!("--host={}", host))
                    .arg(format!("--port={}", port));
                cmd
            }
        };

        server_cmd
            .current_dir(&app_root)
            .env("DB_CONNECTION", "sqlite")
            .env("DB_DATABASE", db_path.to_string_lossy().as_ref())
            .stdout(std::process::Stdio::null())
            .stderr(std::process::Stdio::null());

        match server_cmd.spawn() {
            Ok(child_proc) => {
                log::info!(
                    "Spawned backend process (PID: {:?}) using {:?}",
                    child_proc.id(),
                    match &runtime {
                        PhpRuntime::FrankenPhp(p) => p,
                        PhpRuntime::SystemPhp(p) => p,
                    }
                );

                if let Ok(mut lock) = self.child.lock() {
                    *lock = Some(child_proc);
                }

                // Poll readiness for up to 8 seconds
                let start = Instant::now();
                while start.elapsed() < Duration::from_secs(8) {
                    if self.is_server_listening(host, port) {
                        log::info!("Promediatum backend became ready in {:?}", start.elapsed());
                        return true;
                    }
                    std::thread::sleep(Duration::from_millis(150));
                }

                log::warn!(
                    "Backend process spawned but port {}:{} did not respond in time.",
                    host,
                    port
                );
                false
            }
            Err(e) => {
                log::error!("Failed to spawn Promediatum backend: {}", e);
                false
            }
        }
    }

    /// Cleanly shutdown any spawned backend child process
    pub fn shutdown(&self) {
        if let Ok(mut lock) = self.child.lock() {
            if let Some(mut child) = lock.take() {
                log::info!("Terminating spawned Promediatum backend process...");
                let _ = child.kill();
                let _ = child.wait();
            }
        }
    }
}
