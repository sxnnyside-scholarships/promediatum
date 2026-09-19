use std::net::{SocketAddr, TcpStream};
use std::process::Child;
use std::sync::{Arc, Mutex};
use std::time::{Duration, Instant};

#[derive(Clone, Default)]
pub struct ServerManager {
    child: Arc<Mutex<Option<Child>>>,
}

impl ServerManager {
    pub fn new() -> Self {
        Self {
            child: Arc::new(Mutex::new(None)),
        }
    }

    /// Check if local server is listening on port 8000
    pub fn is_server_listening(&self, host: &str, port: u16) -> bool {
        let addr_str = format!("{}:{}", host, port);
        if let Ok(sock) = addr_str.parse::<SocketAddr>() {
            TcpStream::connect_timeout(&sock, Duration::from_millis(300)).is_ok()
        } else {
            false
        }
    }

    /// Ensure backend is ready. If not already running, attempts to launch local PHP server
    pub fn ensure_backend_ready(&self, host: &str, port: u16) -> bool {
        if self.is_server_listening(host, port) {
            log::info!("Promediatum server is already running on {}:{}", host, port);
            return true;
        }

        log::info!(
            "Server not detected on {}:{}. Checking for PHP runtime...",
            host,
            port
        );

        // Attempt to launch php artisan serve if php is available
        let spawn_result = std::process::Command::new("php")
            .arg("artisan")
            .arg("serve")
            .arg(format!("--host={}", host))
            .arg(format!("--port={}", port))
            .stdout(std::process::Stdio::null())
            .stderr(std::process::Stdio::null())
            .spawn();

        match spawn_result {
            Ok(child_proc) => {
                log::info!(
                    "Spawned PHP backend supervisor process (PID: {:?})",
                    child_proc.id()
                );
                if let Ok(mut lock) = self.child.lock() {
                    *lock = Some(child_proc);
                }

                // Poll readiness for up to 5 seconds
                let start = Instant::now();
                while start.elapsed() < Duration::from_secs(5) {
                    if self.is_server_listening(host, port) {
                        log::info!("Promediatum backend became ready in {:?}", start.elapsed());
                        return true;
                    }
                    std::thread::sleep(Duration::from_millis(150));
                }

                log::warn!("Backend spawned but did not respond within timeout.");
                false
            }
            Err(e) => {
                log::info!(
                    "PHP binary not found in standard PATH or not standalone: {}",
                    e
                );
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
