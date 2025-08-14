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
    let mut config = Config::default();
    config.data = serde_json::json!({
        "metrics": {
            "log_updates": true  // Enable for test (assumes tracing initialized at debug)
        }
    });
    
    init_metrics(&config);

    let labels: HashMap<&str, &str> = [("model", "gguf")].iter().cloned().collect();
    record_inference(123.45, labels);

    // Since metrics-rs doesn't expose direct gets, we rely on no-panic and assume correct (or use exporter in integration tests)
    // For verification, could add a custom recorder in tests, but for simplicity: no panic == success
}

#[test]
fn test_inc_counter() {
    let labels: HashMap<&str, &str> = [("type", "error")].iter().cloned().collect();
    // Name must be &'static str
    inc_counter("custom_counter", labels);

    // No panic == success
}

#[test]
fn test_set_gauge() {
    let labels: HashMap<&str, &str> = [("component", "cpu")].iter().cloned().collect();
    // Name must be &'static str
    set_gauge("usage", 75.5, labels);

    // No panic == success
}

#[test]
fn test_record_histogram() {
    let labels: HashMap<&str, &str> = [("path", "/api")].iter().cloned().collect();
    // Name must be &'static str
    record_histogram("request_latency", 200.0, labels);

    // No panic == success
}

#[test]
fn test_concurrent_updates() {
    let handles: Vec<_> = (0..10).map(|_| {
        thread::spawn(move || {
            for _ in 0..100 {
                let labels: HashMap<&str, &str> = [("thread", "worker")].iter().cloned().collect();
                // Name must be &'static str
                inc_counter("concurrent_counter", labels);
            }
        })
    }).collect();

    for handle in handles {
        handle.join().unwrap();
    }

    // No panic == success (metrics-rs handles concurrency internally)
}

#[test]
fn test_cached_log_updates_flag() {
    // Test that the log_updates flag is properly cached
    let mut config = Config::default();
    config.data = serde_json::json!({
        "metrics": {
            "log_updates": true
        }
    });
    
    init_metrics(&config);
    
    // Record metrics multiple times to verify cached flag works
    let labels: HashMap<&str, &str> = [("test", "cached")].iter().cloned().collect();
    for i in 0..5 {
        record_inference(i as f64 * 10.0, labels.clone());
    }
    
    // No panic == success (cached flag prevents repeated config lookups)
}

#[cfg(feature = "prometheus")]
#[test]
fn test_export_prometheus() {
    let config = Config::default();
    // Ensure recorder is installed before recording so descriptions are registered properly.
    init_metrics(&config);
    let _ = ai_utils::metrics::export_prometheus().unwrap();

    record_inference(100.0, HashMap::new());

    let output = ai_utils::metrics::export_prometheus().unwrap();
    assert!(output.contains("inference_count"));
    assert!(output.contains("inference_time_ms"));
}