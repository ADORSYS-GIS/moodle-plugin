//! utils/tests/metrics_test.rs
//!
//! These tests verify that metric definitions and recording calls do not panic,
//! and that the API accepts labels and names as defined in `metrics.rs`.
//!
//! Notes:
//! - The metrics macros require `'static` names; helper fns that take `name`
//!   use `&'static str`.
//! - Labels passed as `HashMap<&str, &str>` are cloned into owned `(String, String)`
//!   pairs inside `metrics.rs` to satisfy macro lifetime requirements.

use std::collections::HashMap;
use std::thread;

use ai_utils::config::Config;
use ai_utils::metrics::{inc_counter, init_metrics, record_histogram, record_inference, set_gauge};

#[test]
fn test_init_and_record_inference() {
    init_metrics();

    let mut config = Config::default();
    config.data = serde_json::json!({
        "metrics": {
            "log_updates": true  // Enable for test (assumes tracing initialized at debug)
        }
    });

    let labels: HashMap<&str, &str> = [("model", "gguf")].iter().cloned().collect();
    record_inference(&config, 123.45, labels);

    // Since metrics-rs doesn't expose direct gets, we rely on no-panic and assume correct (or use exporter in integration tests)
    // For verification, could add a custom recorder in tests, but for simplicity: no panic == success
}

#[test]
fn test_inc_counter() {
    let config = Config::default();

    let labels: HashMap<&str, &str> = [("type", "error")].iter().cloned().collect();
    // Name must be &'static str
    inc_counter(&config, "custom_counter", labels);

    // No panic == success
}

#[test]
fn test_set_gauge() {
    let config = Config::default();

    let labels: HashMap<&str, &str> = [("component", "cpu")].iter().cloned().collect();
    // Name must be &'static str
    set_gauge(&config, "usage", 75.5, labels);

    // No panic == success
}

#[test]
fn test_record_histogram() {
    let config = Config::default();

    let labels: HashMap<&str, &str> = [("path", "/api")].iter().cloned().collect();
    // Name must be &'static str
    record_histogram(&config, "request_latency", 200.0, labels);

    // No panic == success
}

#[test]
fn test_concurrent_updates() {
    let config = Config::default();

    let handles: Vec<_> = (0..10).map(|_| {
        let config_clone = config.clone();
        thread::spawn(move || {
            for _ in 0..100 {
                let labels: HashMap<&str, &str> = [("thread", "worker")].iter().cloned().collect();
                // Name must be &'static str
                inc_counter(&config_clone, "concurrent_counter", labels);
            }
        })
    }).collect();

    for handle in handles {
        handle.join().unwrap();
    }

    // No panic == success (metrics-rs handles concurrency internally)
}

#[cfg(feature = "prometheus")]
#[test]
fn test_export_prometheus() {
    // Ensure recorder is installed before recording so descriptions are registered properly.
    init_metrics();
    let _ = ai_utils::metrics::export_prometheus().unwrap();

    let config = Config::default();
    record_inference(&config, 100.0, HashMap::new());

    let output = ai_utils::metrics::export_prometheus().unwrap();
    assert!(output.contains("inference_count"));
    assert!(output.contains("inference_time_ms"));
}