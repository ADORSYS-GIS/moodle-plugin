use anyhow::{Context, Result};
use std::env;
use std::net::SocketAddr;

#[derive(Debug, Clone)]
pub struct Config {
    pub bind_address: SocketAddr,
    pub log_exporter_endpoint: String,
    pub metrics_exporter_endpoint: String,
}

impl Config {
    pub fn from_env() -> Result<Self> {
        // Get SERVER_HOST and SERVER_PORT
        let host = env::var("SERVER_HOST").unwrap_or_else(|_| "0.0.0.0".to_string());
        let port = env::var("SERVER_PORT").unwrap_or_else(|_| "8888".to_string());

        let addr_str = format!("{host}:{port}");
        let bind_address: SocketAddr = addr_str
            .parse()
            .with_context(|| format!("Failed to parse server bind address from '{addr_str}'"))?;

        // Get log exporter endpoint
        let log_exporter_endpoint = env::var("OTEL_LOGS_EXPORTER")
            .unwrap_or_else(|_| "http://localhost:4318/v1/logs".into());

        // Get metrics exporter endpoint
        let metrics_exporter_endpoint = env::var("OTEL_METRICS_EXPORTER")
            .unwrap_or_else(|_| "http://localhost:9090/api/v1/otlp/v1/metrics".into());

        Ok(Config {
            bind_address,
            log_exporter_endpoint,
            metrics_exporter_endpoint,
        })
    }
}
