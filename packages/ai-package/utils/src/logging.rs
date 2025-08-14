//! utils/src/logging.rs
//!
//! Global logging initialization for the AI stack.
//! - Console logging is enabled by default (ANSI only when stderr is a TTY).
//! - File logging is optional and non-blocking via `tracing-appender`.
//! - Initialization is one-time per process via `Once`; subsequent calls are no-ops.
//! - The global file destination (if any) is defined by the first successful init.
//!
//! Notes:
//! - Filtering uses `EnvFilter`, honoring `RUST_LOG` if present; otherwise a provided
//!   `level` (like "info", "debug"). Invalid levels fall back to `info`.
//! - There is no runtime reload/change of log level in this build (no `reload` feature).
//!
//! Usage:
//!   init_logger("info", None)?; // console only
//!   init_logger("debug", Some("/var/log/ai/engine.log"))?; // adds file output
//!   init_from_config(&cfg)?; // reads `/log/level` and optional `/log/file`

use std::error::Error;
use std::fs;
use std::path::{Path, PathBuf};
use std::sync::{Once, OnceLock};

use tracing_subscriber::{fmt, Registry};
// Explicitly import EnvFilter and bring extension traits into scope for `.with` and `.with_filter`.
use tracing_subscriber::EnvFilter;
use tracing_subscriber::prelude::*;
// reload feature removed to match current crate feature set

// Import WorkerGuard; call the crate-level `tracing_appender::non_blocking` function directly
use tracing_appender::non_blocking::WorkerGuard;
use tracing_appender::rolling;

use crate::config::Config;

static INIT_LOGGER: Once = Once::new();
/// Holds the file appender background worker guard if file logging is enabled.
/// Keeping this alive prevents dropped logs.
static FILE_GUARD: OnceLock<WorkerGuard> = OnceLock::new();
// No reload handle without the reload feature

fn create_filter(level: &str) -> EnvFilter {
    EnvFilter::try_from_default_env().unwrap_or_else(|_| {
        EnvFilter::try_new(level.to_string()).unwrap_or(EnvFilter::new("info"))
    })
}

/// Initialize the global logger (idempotent).
/// - `level`: e.g., "info", "debug", honors `RUST_LOG` if set; invalid values fall back to `info`.
/// - `file_path`: optional path for non-blocking file logging. If provided on the first call,
///                the process will log to this file for its lifetime. Parent directories are
///                created if missing; on failure, the logger falls back to console-only.
///
/// Safe to call multiple times; only the first call sets the global subscriber.
/// Subsequent calls return `Ok(())` without changing the existing configuration.
pub fn init_logger(level: &str, file_path: Option<&str>) -> Result<(), Box<dyn Error>> {
    // We build everything inside `call_once`. If it has already run, this function is a no-op.
    INIT_LOGGER.call_once(|| {
        // 1) Build EnvFilter (fixed for process lifetime)
        let fixed_filter = create_filter(level);

        // 2) Console layer (ANSI on TTY; includes target names, timestamps)
        let console_layer = fmt::layer()
            .with_target(true)
            .with_thread_ids(true)
            .with_thread_names(true)
            .with_ansi(is_terminal::is_terminal(std::io::stderr()))
            .with_filter(fixed_filter);

        // 3) Base subscriber with console layer
        let base = Registry::default().with(console_layer);

        // 4) Optional file layer → install directly in each branch to avoid type unification
        if let Some(path_str) = file_path {
            if let Some((dir, fname)) = split_dir_filename(path_str) {
                if let Err(e) = fs::create_dir_all(&dir) {
                    eprintln!(
                        "⚠️ logging: failed to create log directory '{}': {}. Falling back to console only.",
                        dir.display(),
                        e
                    );
                    // Console only
                    if let Err(e) = tracing::subscriber::set_global_default(base) {
                        eprintln!("⚠️ logging: global subscriber already set ({}). This init was ignored.", e);
                    }
                } else {
                    // A fixed file (no rotation). Use rolling::never(dir, fname).
                    let file_appender = rolling::never(dir, fname);
                    let (non_blocking_writer, guard) = tracing_appender::non_blocking(file_appender);

                    // Keep the guard alive for the process lifetime
                    let _ = FILE_GUARD.set(guard);

                    // Add file layer (no ANSI for files)
                    let file_layer = fmt::layer()
                        .with_ansi(false)
                        .with_target(true)
                        .with_thread_ids(true)
                        .with_thread_names(true)
                        .with_writer(non_blocking_writer);

                    let subscriber = base.with(file_layer);
                    if let Err(e) = tracing::subscriber::set_global_default(subscriber) {
                        eprintln!("⚠️ logging: global subscriber already set ({}). This init was ignored.", e);
                    }
                }
            } else {
                eprintln!(
                    "⚠️ logging: invalid file path '{}'. Falling back to console only.",
                    path_str
                );
                if let Err(e) = tracing::subscriber::set_global_default(base) {
                    eprintln!("⚠️ logging: global subscriber already set ({}). This init was ignored.", e);
                }
            }
        } else {
            // Console only
            if let Err(e) = tracing::subscriber::set_global_default(base) {
                eprintln!("⚠️ logging: global subscriber already set ({}). This init was ignored.", e);
            }
        }
    });

    Ok(())
}

/// Initialize logger using configuration:
/// - Reads `/log/level` (default "info")
/// - Reads optional `/log/file` (enables non-blocking file logging if present & non-empty)
///
/// Note: If logging has already been initialized, this call is a no-op.
pub fn init_from_config(config: &Config) -> Result<(), Box<dyn Error>> {
    let level = config.get_or_default("/log/level", "info");

    let file_opt = config
        .get_str("/log/file")
        .map(|s| s.trim())
        .filter(|s| !s.is_empty());

    init_logger(level, file_opt)
}

/// Helper: split a path into (parent_dir, file_name).
/// Returns None if the input does not contain a valid file name.
fn split_dir_filename(path: &str) -> Option<(PathBuf, &str)> {
    let p = Path::new(path);
    let file_name = p.file_name()?.to_str()?;
    let dir = p.parent().map(|d| d.to_path_buf()).unwrap_or_else(|| PathBuf::from("."));
    Some((dir, file_name))
}
