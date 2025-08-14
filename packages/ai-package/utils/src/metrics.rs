//! utils/src/metrics.rs
//!
//! Metrics collection using the metrics-rs crate for performance and standards compliance.
//! Supports counters, gauges, and histograms with labels.
//! Integrates optionally with tracing for logging updates in dev mode (configurable via /metrics/log_updates).
//! Call init_metrics() early (e.g., in main) to describe metrics.
//! Use record_* functions for updates, which handle logging if enabled.

use metrics::{counter, describe_counter, describe_gauge, describe_histogram, gauge, histogram, Unit};
use std::collections::HashMap;
use tracing::{debug, enabled, Level};

use crate::config::Config;

// Feature flag for Prometheus export (enable in Cargo.toml with features = ["prometheus"]) 
// We cache the handle so repeated calls don't try to reinstall the recorder.
#[cfg(feature = "prometheus")]
use metrics_exporter_prometheus::PrometheusBuilder;
#[cfg(feature = "prometheus")]
use once_cell::sync::OnceCell;

/// Initialize metric descriptions (call once at startup).
/// Note: Units are hints; histogram buckets are exporter-defined (not set here).
pub fn init_metrics() {
    describe_counter!(
        "inference_count",
        Unit::Count,
        "Number of AI inferences performed"
    );
    describe_gauge!(
        "inference_time_ms",
        Unit::Milliseconds,
        "Time taken per inference in milliseconds"
    );
    describe_histogram!(
        "latency_distribution_ms",
        Unit::Milliseconds,
        "Inference latency distribution"
    );
}

/// Convert borrowed label map to owned (String, String) pairs so macros can accept 'static-ish labels.
fn to_owned_label_pairs(labels: &HashMap<&str, &str>) -> Vec<(String, String)> {
    labels
        .iter()
        .map(|(k, v)| (k.to_string(), v.to_string()))
        .collect()
}

/// Record an inference event with time and optional labels.
/// If logging is enabled in config, logs the update at debug level.
pub fn record_inference(config: &Config, time_ms: f64, labels: HashMap<&str, &str>) {
    let log_updates = config.get_as::<bool>("/metrics/log_updates").unwrap_or(false);

    let label_pairs = to_owned_label_pairs(&labels);

    counter!("inference_count", &label_pairs).increment(1);
    gauge!("inference_time_ms", &label_pairs).set(time_ms);
    histogram!("latency_distribution_ms", &label_pairs).record(time_ms);

    if log_updates && enabled!(Level::DEBUG) {
        debug!("Metric update: inference_count +1, inference_time_ms={}, labels={:?}", time_ms, labels);
    }
}

/// Increment a generic counter with optional labels and logging.
/// Increment a generic counter with optional labels and logging.
/// `name` must be a literal or have a 'static lifetime when using macros.
pub fn inc_counter(config: &Config, name: &'static str, labels: HashMap<&str, &str>) {
    let log_updates = config.get_as::<bool>("/metrics/log_updates").unwrap_or(false);

    let label_pairs = to_owned_label_pairs(&labels);
    counter!(name, &label_pairs).increment(1);

    if log_updates && enabled!(Level::DEBUG) {
        debug!("Metric update: {} +1, labels={:?}", name, labels);
    }
}

/// Set a generic gauge with optional labels and logging.
/// Set a generic gauge with optional labels and logging.
/// `name` must be a literal or have a 'static lifetime when using macros.
pub fn set_gauge(config: &Config, name: &'static str, value: f64, labels: HashMap<&str, &str>) {
    let log_updates = config.get_as::<bool>("/metrics/log_updates").unwrap_or(false);

    let label_pairs = to_owned_label_pairs(&labels);
    gauge!(name, &label_pairs).set(value);

    if log_updates && enabled!(Level::DEBUG) {
        debug!("Metric update: {} = {}, labels={:?}", name, value, labels);
    }
}

/// Record a histogram value with optional labels and logging.
/// Record a histogram value with optional labels and logging.
/// `name` must be a literal or have a 'static lifetime when using macros.
pub fn record_histogram(config: &Config, name: &'static str, value: f64, labels: HashMap<&str, &str>) {
    let log_updates = config.get_as::<bool>("/metrics/log_updates").unwrap_or(false);

    let label_pairs = to_owned_label_pairs(&labels);
    histogram!(name, &label_pairs).record(value);

    if log_updates && enabled!(Level::DEBUG) {
        debug!("Metric update: {} recorded {}, labels={:?}", name, value, labels);
    }
}

/// Export all metrics in Prometheus format (requires "prometheus" feature).
/// Installs a global Prometheus recorder on first call and reuses the handle thereafter.
#[cfg(feature = "prometheus")]
pub fn export_prometheus() -> Result<String, Box<dyn std::error::Error>> {
    static PROM_HANDLE: OnceCell<metrics_exporter_prometheus::PrometheusHandle> = OnceCell::new();
    if let Some(h) = PROM_HANDLE.get() {
        return Ok(h.render());
    }
    let handle = PrometheusBuilder::new().install_recorder()?;
    let _ = PROM_HANDLE.set(handle);
    Ok(PROM_HANDLE.get().unwrap().render())
}