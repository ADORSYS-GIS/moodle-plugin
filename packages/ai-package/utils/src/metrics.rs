//! utils/src/metrics.rs
//!
//! Metrics collection using the metrics-rs crate for performance and standards compliance.
//! Supports counters, gauges, and histograms with labels.
//! Integrates optionally with tracing for logging updates in dev mode (configurable via /metrics/log_updates).
//! Call init_metrics() early (e.g., in main) to describe metrics.
//! Use record_* functions for updates, which handle logging if enabled.

use metrics::{counter, describe_counter, describe_gauge, describe_histogram, gauge, histogram, Unit};
use std::collections::HashMap;
use std::sync::OnceLock;
use tracing::{debug, enabled, Level};

use crate::config::Config;

// Cache the log_updates flag to avoid repeated config lookups
static LOG_UPDATES_FLAG: OnceLock<bool> = OnceLock::new();

// Feature flag for Prometheus export (enable in Cargo.toml with features = ["prometheus"]) 
// We cache the handle so repeated calls don't try to reinstall the recorder.
#[cfg(feature = "prometheus")]
use metrics_exporter_prometheus::PrometheusBuilder;
#[cfg(feature = "prometheus")]
use once_cell::sync::OnceCell;
#[cfg(feature = "prometheus")]
use tokio::net::TcpListener;
#[cfg(feature = "prometheus")]
use tokio::io::{AsyncReadExt, AsyncWriteExt};

/// Initialize metric descriptions and cache configuration (call once at startup).
/// Note: Units are hints; histogram buckets are exporter-defined (not set here).
pub fn init_metrics(config: &Config) {
    // Cache the log_updates flag to avoid repeated config lookups
    let log_updates = config.get_as::<bool>("/metrics/log_updates").unwrap_or(false);
    let _ = LOG_UPDATES_FLAG.set(log_updates);

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
pub fn record_inference(time_ms: f64, labels: HashMap<&str, &str>) {
    let log_updates = LOG_UPDATES_FLAG.get().copied().unwrap_or(false);

    let label_pairs = to_owned_label_pairs(&labels);

    counter!("inference_count", &label_pairs).increment(1);
    gauge!("inference_time_ms", &label_pairs).set(time_ms);
    histogram!("latency_distribution_ms", &label_pairs).record(time_ms);

    if log_updates && enabled!(Level::DEBUG) {
        debug!("Metric update: inference_count +1, inference_time_ms={}, labels={:?}", time_ms, labels);
    }
}

/// Increment a generic counter with optional labels and logging.
/// `name` must be a literal or have a 'static lifetime when using macros.
pub fn inc_counter(name: &'static str, labels: HashMap<&str, &str>) {
    let log_updates = LOG_UPDATES_FLAG.get().copied().unwrap_or(false);

    let label_pairs = to_owned_label_pairs(&labels);
    counter!(name, &label_pairs).increment(1);

    if log_updates && enabled!(Level::DEBUG) {
        debug!("Metric update: {} +1, labels={:?}", name, labels);
    }
}

/// Set a generic gauge with optional labels and logging.
/// `name` must be a literal or have a 'static lifetime when using macros.
pub fn set_gauge(name: &'static str, value: f64, labels: HashMap<&str, &str>) {
    let log_updates = LOG_UPDATES_FLAG.get().copied().unwrap_or(false);

    let label_pairs = to_owned_label_pairs(&labels);
    gauge!(name, &label_pairs).set(value);

    if log_updates && enabled!(Level::DEBUG) {
        debug!("Metric update: {} = {}, labels={:?}", name, value, labels);
    }
}

/// Record a histogram value with optional labels and logging.
/// `name` must be a literal or have a 'static lifetime when using macros.
pub fn record_histogram(name: &'static str, value: f64, labels: HashMap<&str, &str>) {
    let log_updates = LOG_UPDATES_FLAG.get().copied().unwrap_or(false);

    let label_pairs = to_owned_label_pairs(&labels);
    histogram!(name, &label_pairs).record(value);

    if log_updates && enabled!(Level::DEBUG) {
        debug!("Metric update: {} recorded {}, labels={:?}", name, value, labels);
    }
}

/// Export all metrics in Prometheus format (requires "prometheus" feature).
/// Installs a global Prometheus recorder on first call and reuses the handle thereafter.
#[cfg(feature = "prometheus")]
pub fn export_prometheus() -> Result<String, Box<dyn std::error::Error + Send + Sync>> {
    static PROM_HANDLE: OnceCell<metrics_exporter_prometheus::PrometheusHandle> = OnceCell::new();
    if let Some(h) = PROM_HANDLE.get() {
        return Ok(h.render());
    }
    let handle = PrometheusBuilder::new().install_recorder()?;
    let _ = PROM_HANDLE.set(handle);
    Ok(PROM_HANDLE.get().unwrap().render())
}

/// Start a simple HTTP server that serves Prometheus metrics (requires "prometheus" feature).
/// This is the production-ready way to expose metrics for Prometheus scraping.
/// 
/// # Arguments
/// * `bind_addr` - The address to bind to (e.g., "127.0.0.1:9090")
/// 
/// # Returns
/// * `Ok(())` if the server started successfully
/// * `Err` if there was an error starting the server
/// 
/// # Example
/// ```rust
/// #[cfg(feature = "prometheus")]
/// {
///     // Start the metrics server in a separate task
///     tokio::spawn(async {
///         if let Err(e) = ai_utils::metrics::start_prometheus_server("127.0.0.1:9090").await {
///             eprintln!("Failed to start Prometheus server: {}", e);
///         }
///     });
/// }
/// ```
#[cfg(feature = "prometheus")]
pub async fn start_prometheus_server(bind_addr: &str) -> Result<(), Box<dyn std::error::Error + Send + Sync>> {
    // Ensure Prometheus recorder is installed
    let _ = export_prometheus()?;
    
    let listener = TcpListener::bind(bind_addr).await?;
    tracing::info!("Prometheus metrics server listening on {}", bind_addr);
    
    loop {
        let (mut socket, addr) = listener.accept().await?;
        
        tokio::spawn(async move {
            tracing::debug!("Prometheus metrics request from {}", addr);
            let mut buffer = [0; 1024];
            
            // Simple HTTP request parsing
            let n = match socket.read(&mut buffer).await {
                Ok(n) if n == 0 => return, // Connection closed
                Ok(n) => n,
                Err(_) => return, // Connection error
            };
            
            let request = String::from_utf8_lossy(&buffer[..n]);
            
            // Only respond to GET requests to /metrics
            if request.starts_with("GET /metrics") {
                // Optional: Basic access control (only allow localhost and private networks)
                let client_ip = addr.ip();
                let is_private = match client_ip {
                    std::net::IpAddr::V4(ipv4) => ipv4.is_private(),
                    std::net::IpAddr::V6(ipv6) => ipv6.is_loopback() || ipv6.is_unspecified(),
                };
                
                if !client_ip.is_loopback() && !is_private {
                    tracing::warn!("Prometheus scrape attempt from non-private IP: {}", addr);
                    let _ = socket.write_all(b"HTTP/1.1 403 Forbidden\r\n\r\n").await;
                    return;
                }
                
                // Record a metric about who's scraping (optional)
                let client_ip_str = client_ip.to_string();
                let client_labels: HashMap<&str, &str> = [("client_ip", client_ip_str.as_str())].iter().cloned().collect();
                inc_counter("prometheus_scrapes", client_labels);
                
                let metrics = match export_prometheus() {
                    Ok(m) => m,
                    Err(_) => {
                        let _ = socket.write_all(b"HTTP/1.1 500 Internal Server Error\r\n\r\n").await;
                        return;
                    }
                };
                
                let response = format!(
                    "HTTP/1.1 200 OK\r\n\
                     Content-Type: text/plain; version=0.0.4; charset=utf-8\r\n\
                     Content-Length: {}\r\n\
                     \r\n\
                     {}",
                    metrics.len(),
                    metrics
                );
                
                if let Err(_) = socket.write_all(response.as_bytes()).await {
                    // Connection error, ignore
                }
            } else {
                // Return 404 for non-metrics requests
                let _ = socket.write_all(b"HTTP/1.1 404 Not Found\r\n\r\n").await;
            }
        });
    }
}