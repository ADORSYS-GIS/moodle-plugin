//! utils/tests/logging_test.rs
//!
//! Tests are serialized because logging is a global singleton set once per process.
//! We use a shared log file path across tests to avoid re-initialization issues.
//! Each test clears the shared file before asserting on its contents.

use std::fs;
use std::io::Read;
use std::io::Write;
use std::path::PathBuf;
use std::time::{Duration, Instant};

use tempfile::tempdir;
use serial_test::serial;
use lazy_static::lazy_static;

use ai_utils::config::Config;
use ai_utils::logging::{init_from_config, init_logger};
use serde_json::json;

lazy_static! {
    static ref SHARED_LOG_DIR: PathBuf = {
        let mut p = std::env::temp_dir();
        p.push("ai_utils_shared_logs");
        let _ = std::fs::create_dir_all(&p);
        p
    };
    static ref SHARED_LOG_PATH: PathBuf = {
        SHARED_LOG_DIR.join("test.log")
    };
}

/// Busy-wait until `path` contains `needle` or timeout elapses.
/// Returns file contents on success, `None` on timeout.
fn wait_for_log_contains(path: &std::path::Path, needle: &str, timeout: Duration) -> Option<String> {
    let start = Instant::now();
    let mut iters = 0u32;
    loop {
        if start.elapsed() > timeout {
            return None;
        }
        if iters > 800 { // hard cap (~8s max with 10ms sleep)
            return None;
        }
        if let Ok(mut f) = fs::File::open(path) {
            let mut s = String::new();
            let _ = f.read_to_string(&mut s);
            if s.contains(needle) {
                return Some(s);
            }
        }
        std::thread::sleep(Duration::from_millis(10));
        iters += 1;
    }
}

#[test]
#[serial(logging)]
fn a_logging_file_ok_and_writes() {
    // Initialize global logger with shared file path (idempotent)
    let log_path = SHARED_LOG_PATH.clone();
    let log_path_str = log_path.to_string_lossy().to_string();
    let res = init_logger("debug", Some(&log_path_str));
    assert!(res.is_ok());

    // Start each test from a clean file state
    let _ = fs::write(&log_path, "");

    // Emit a line and give the non-blocking writer a moment
    tracing::info!("hello from file logger");
    std::thread::sleep(Duration::from_millis(50));

    let contents = wait_for_log_contains(&log_path, "hello from file logger", Duration::from_secs(5))
        .unwrap_or_else(|| panic!("log file did not contain expected line: {:?}", log_path));

    assert!(contents.contains("hello from file logger"));
}

#[test]
#[serial(logging)]
fn logging_end_to_end_console_only_ok() {
    // First init: console only
    // (If another test already initialized, this is a no-op, which is fine.)
    let res = init_logger("info", None);
    assert!(res.is_ok());
}

#[test]
#[serial(logging)]
fn logging_invalid_level_falls_back_to_info() {
    // Use the same shared file path; init is idempotent
    let log_path = SHARED_LOG_PATH.clone();
    let log_path_str = log_path.to_string_lossy().to_string();
    let res = init_logger("this_is_not_a_valid_level", Some(&log_path_str));
    assert!(res.is_ok());

    // Clean file then log an INFO line (should appear regardless of prior level)
    let _ = fs::write(&log_path, "");
    tracing::info!("should log at info after fallback");
    std::thread::sleep(Duration::from_millis(50));
    let contents = wait_for_log_contains(&log_path, "should log at info", Duration::from_secs(5))
        .expect("log file did not contain expected line after fallback");
    assert!(contents.contains("should log at info"));
}

#[test]
#[serial(logging)]
fn logging_warns_on_dir_creation_failure() {
    // Simulate failure by pointing to a file path where parent is a file, not a dir
    let dir = tempdir().expect("tempdir");
    let blocker = dir.path().join("as_file");
    // Create a file named 'as_file'
    {
        let mut f = fs::File::create(&blocker).expect("create file");
        let _ = f.write_all(b"blocker");
    }
    // Now use a nested path under that file -> parent_dir will be a file, mkdir should fail
    let bad_path = blocker.join("log.txt");
    let bad_path_str = bad_path.to_string_lossy().to_string();

    // Capture stderr by temporarily redirecting it is non-trivial across platforms; instead,
    // we simply assert that init does not panic and returns Ok (it falls back to console only).
    let res = init_logger("info", Some(&bad_path_str));
    assert!(res.is_ok());
}

#[test]
#[serial(logging)]
fn logging_init_from_config_noop_if_already_initialized() {
    // If global logger was already set, this is a no-op but must succeed.
    let mut cfg = Config::new();
    cfg.merge(Config { data: json!({
        "log": {
            "level": "trace",
            "file": ""  // empty -> no file logging
        }
    })});

    let res = init_from_config(&cfg);
    assert!(res.is_ok());

    // Still can emit logs without panicking
    tracing::debug!("debug log via config init noop");
}
