mod commands;
mod lifecycle;
mod menu;
mod tray;

use lifecycle::ServerManager;

#[cfg_attr(mobile, tauri::mobile_entry_point)]
pub fn run() {
    let server_manager = ServerManager::new();
    let server_manager_clone = server_manager.clone();

    tauri::Builder::default()
        .plugin(
            tauri_plugin_log::Builder::default()
                .level(log::LevelFilter::Info)
                .build(),
        )
        .plugin(tauri_plugin_dialog::init())
        .plugin(tauri_plugin_notification::init())
        .plugin(tauri_plugin_window_state::Builder::default().build())
        .plugin(tauri_plugin_fs::init())
        .plugin(tauri_plugin_shell::init())
        .plugin(tauri_plugin_process::init())
        .invoke_handler(tauri::generate_handler![
            commands::get_system_info,
            commands::get_app_paths,
            commands::open_path_in_file_manager,
            commands::ping_backend,
        ])
        .setup(move |app| {
            let app_handle = app.handle();

            // Build native OS menu
            if let Ok(m) = menu::build_menu(app_handle) {
                let _ = app.set_menu(m);
            }

            // Build system tray
            let _ = tray::build_tray(app_handle);

            // Supervise backend readiness
            server_manager_clone.ensure_backend_ready("127.0.0.1", 8000);

            Ok(())
        })
        .on_menu_event(menu::handle_menu_event)
        .build(tauri::generate_context!())
        .expect("error while building tauri application")
        .run(move |_app_handle, event| {
            if let tauri::RunEvent::ExitRequested { .. } = event {
                server_manager.shutdown();
            }
        });
}
