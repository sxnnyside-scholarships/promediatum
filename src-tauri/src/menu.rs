use tauri::{
    menu::{Menu, MenuBuilder, MenuItem, SubmenuBuilder},
    AppHandle, Emitter, Manager, Wry,
};

pub fn build_menu(app: &AppHandle) -> tauri::Result<Menu<Wry>> {
    #[allow(unused_mut)]
    let mut builder = MenuBuilder::new(app);

    // App menu (macOS standard)
    #[cfg(target_os = "macos")]
    {
        let app_menu = SubmenuBuilder::new(app, "Promediatum")
            .about(Some(tauri::menu::AboutMetadata {
                name: Some("Promediatum".into()),
                version: Some(env!("CARGO_PKG_VERSION").into()),
                authors: Some(vec!["Sxnnyside Scholarships".into()]),
                comments: Some("Personal academic workspace for independent educators".into()),
                ..Default::default()
            }))
            .separator()
            .services()
            .separator()
            .hide()
            .hide_others()
            .show_all()
            .separator()
            .quit()
            .build()?;
        builder = builder.item(&app_menu);
    }

    // File Menu
    let file_menu = SubmenuBuilder::new(app, "Archivo")
        .item(&MenuItem::with_id(
            app,
            "menu_new_student",
            "Nuevo Alumno",
            true,
            Some("CmdOrCtrl+N"),
        )?)
        .item(&MenuItem::with_id(
            app,
            "menu_new_group",
            "Nuevo Grupo",
            true,
            Some("CmdOrCtrl+Shift+N"),
        )?)
        .separator()
        .item(&MenuItem::with_id(
            app,
            "menu_backup",
            "Crear Respaldo (.pdbk)",
            true,
            Some("CmdOrCtrl+Shift+B"),
        )?)
        .separator()
        .close_window()
        .build()?;

    // Edit Menu
    let edit_menu = SubmenuBuilder::new(app, "Edición")
        .undo()
        .redo()
        .separator()
        .cut()
        .copy()
        .paste()
        .select_all()
        .build()?;

    // View Menu
    let view_menu = SubmenuBuilder::new(app, "Ver")
        .item(&MenuItem::with_id(
            app,
            "menu_reload",
            "Recargar",
            true,
            Some("CmdOrCtrl+R"),
        )?)
        .separator()
        .fullscreen()
        .build()?;

    // Window Menu
    let window_menu = SubmenuBuilder::new(app, "Ventana").minimize().build()?;

    // Help Menu
    let help_menu = SubmenuBuilder::new(app, "Ayuda")
        .item(&MenuItem::with_id(
            app,
            "menu_docs",
            "Documentación Pedagógica",
            true,
            None::<&str>,
        )?)
        .item(&MenuItem::with_id(
            app,
            "menu_website",
            "Sitio Web del Proyecto",
            true,
            None::<&str>,
        )?)
        .build()?;

    builder
        .item(&file_menu)
        .item(&edit_menu)
        .item(&view_menu)
        .item(&window_menu)
        .item(&help_menu)
        .build()
}

pub fn handle_menu_event(app: &AppHandle, event: tauri::menu::MenuEvent) {
    let id_str = event.id().as_ref();
    match id_str {
        "menu_reload" => {
            if let Some(window) = app.get_webview_window("main") {
                let _ = window.eval("window.location.reload()");
            }
        }
        "menu_new_student" => {
            if let Some(window) = app.get_webview_window("main") {
                let _ =
                    window.eval("if (window.location) window.location.href = '/students/create'");
            }
        }
        "menu_new_group" => {
            if let Some(window) = app.get_webview_window("main") {
                let _ = window.eval("if (window.location) window.location.href = '/groups/create'");
            }
        }
        "menu_backup" => {
            if let Some(window) = app.get_webview_window("main") {
                let _ =
                    window.eval("if (window.location) window.location.href = '/settings#backups'");
            }
        }
        "menu_docs" => {
            let _ = app.emit(
                "open-url",
                "https://github.com/sxnnyside-scholarships/promediatum#readme",
            );
            #[cfg(target_os = "macos")]
            let _ = std::process::Command::new("open")
                .arg("https://github.com/sxnnyside-scholarships/promediatum#readme")
                .spawn();
        }
        "menu_website" => {
            let _ = app.emit("open-url", "https://www.sxnnysideproject.com");
            #[cfg(target_os = "macos")]
            let _ = std::process::Command::new("open")
                .arg("https://www.sxnnysideproject.com")
                .spawn();
        }
        _ => {}
    }
}
