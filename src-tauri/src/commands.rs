use serde::{Deserialize, Serialize};
use std::net::{SocketAddr, TcpStream};
use std::path::{Path, PathBuf};
use std::time::{Duration, Instant};

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct SystemInfo {
    pub os: String,
    pub arch: String,
    pub app_version: String,
    pub platform: String,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct AppPaths {
    pub base_dir: String,
    pub database_path: String,
    pub backups_dir: String,
    pub exports_dir: String,
    pub logs_dir: String,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct BackendHealth {
    pub ok: bool,
    pub latency_ms: u64,
    pub port: u16,
    pub host: String,
}

/// Resolves standard desktop storage directories matching DesktopPathResolver.php
fn resolve_base_dir() -> PathBuf {
    #[cfg(target_os = "macos")]
    {
        if let Some(home) = dirs::home_dir() {
            return home
                .join("Library")
                .join("Application Support")
                .join("Promediatum");
        }
    }

    #[cfg(target_os = "windows")]
    {
        if let Some(data) = dirs::data_dir() {
            return data.join("Promediatum");
        }
    }

    #[cfg(target_os = "linux")]
    {
        if let Some(data) = dirs::data_dir() {
            return data.join("Promediatum");
        }
    }

    PathBuf::from("/tmp/Promediatum")
}

/// IPC: Get system architecture, OS, and application metadata
#[tauri::command]
pub fn get_system_info() -> SystemInfo {
    SystemInfo {
        os: std::env::consts::OS.to_string(),
        arch: std::env::consts::ARCH.to_string(),
        app_version: env!("CARGO_PKG_VERSION").to_string(),
        platform: match std::env::consts::OS {
            "macos" => "macOS".to_string(),
            "windows" => "Windows".to_string(),
            "linux" => "Linux".to_string(),
            other => other.to_string(),
        },
    }
}

/// IPC: Get canonical OS-aware filesystem paths used by Promediatum
#[tauri::command]
pub fn get_app_paths() -> AppPaths {
    let base = resolve_base_dir();
    let database = base.join("database").join("database.sqlite");
    let backups = base.join("backups");
    let exports = base.join("exports");
    let logs = base.join("logs");

    AppPaths {
        base_dir: base.to_string_lossy().to_string(),
        database_path: database.to_string_lossy().to_string(),
        backups_dir: backups.to_string_lossy().to_string(),
        exports_dir: exports.to_string_lossy().to_string(),
        logs_dir: logs.to_string_lossy().to_string(),
    }
}

/// IPC: Open or reveal a given file or directory in Finder (macOS), Explorer (Windows), or File Manager (Linux)
#[tauri::command]
pub fn open_path_in_file_manager(path: String) -> Result<bool, String> {
    let target = Path::new(&path);

    if !target.exists() {
        // If file doesn't exist, try parent directory
        if let Some(parent) = target.parent() {
            if parent.exists() {
                return open_path_in_file_manager(parent.to_string_lossy().to_string());
            }
        }
        return Err(format!("Path does not exist: {}", path));
    }

    #[cfg(target_os = "macos")]
    {
        let mut cmd = std::process::Command::new("open");
        if target.is_file() {
            cmd.arg("-R"); // Reveal file in Finder
        }
        cmd.arg(&path);
        cmd.spawn().map_err(|e| e.to_string())?;
        return Ok(true);
    }

    #[cfg(target_os = "windows")]
    {
        let mut cmd = std::process::Command::new("explorer");
        if target.is_file() {
            cmd.arg(format!("/select,\"{}\"", path));
        } else {
            cmd.arg(&path);
        }
        cmd.spawn().map_err(|e| e.to_string())?;
        return Ok(true);
    }

    #[cfg(target_os = "linux")]
    {
        let to_open = if target.is_file() {
            target
                .parent()
                .unwrap_or(target)
                .to_string_lossy()
                .to_string()
        } else {
            path
        };
        std::process::Command::new("xdg-open")
            .arg(to_open)
            .spawn()
            .map_err(|e| e.to_string())?;
        return Ok(true);
    }

    #[allow(unreachable_code)]
    Ok(false)
}

/// IPC: Ping the local Laravel backend process (defaults to 127.0.0.1:8000)
#[tauri::command]
pub fn ping_backend(host: Option<String>, port: Option<u16>) -> BackendHealth {
    let resolved_host = host.unwrap_or_else(|| "127.0.0.1".to_string());
    let resolved_port = port.unwrap_or(8000);
    let addr_str = format!("{}:{}", resolved_host, resolved_port);

    let start = Instant::now();
    let addr: Result<SocketAddr, _> = addr_str.parse();

    match addr {
        Ok(sock) => {
            let timeout = Duration::from_millis(600);
            match TcpStream::connect_timeout(&sock, timeout) {
                Ok(_) => {
                    let elapsed = start.elapsed().as_millis() as u64;
                    BackendHealth {
                        ok: true,
                        latency_ms: elapsed.max(1),
                        port: resolved_port,
                        host: resolved_host,
                    }
                }
                Err(_) => BackendHealth {
                    ok: false,
                    latency_ms: 0,
                    port: resolved_port,
                    host: resolved_host,
                },
            }
        }
        Err(_) => BackendHealth {
            ok: false,
            latency_ms: 0,
            port: resolved_port,
            host: resolved_host,
        },
    }
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn test_get_system_info() {
        let info = get_system_info();
        assert!(!info.os.is_empty());
        assert!(!info.arch.is_empty());
        assert!(!info.app_version.is_empty());
    }

    #[test]
    fn test_get_app_paths() {
        let paths = get_app_paths();
        assert!(paths.base_dir.contains("Promediatum"));
        assert!(paths.database_path.ends_with("database.sqlite"));
        assert!(paths.backups_dir.ends_with("backups"));
        assert!(paths.exports_dir.ends_with("exports"));
    }
}
