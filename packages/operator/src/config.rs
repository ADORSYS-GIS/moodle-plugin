use anyhow::{Context, Result};
use std::env;
use std::net::SocketAddr;

#[derive(Debug, Clone)]
pub struct OtelConfig {
    pub bind_address: SocketAddr,
    pub log_exporter_endpoint: String,
}

impl OtelConfig {
    pub fn from_env() -> Result<Self> {
        // Get SERVER_HOST and SERVER_PORT
        let host = env::var("SERVER_HOST").unwrap_or_else(|_| "0.0.0.0".to_string());
        let port = env::var("SERVER_PORT").unwrap_or_else(|_| "8888".to_string());

        let addr_str = format!("{host}:{port}");
        let bind_address: SocketAddr = addr_str
            .parse()
            .with_context(|| format!("Failed to parse OTEL bind address from '{addr_str}'"))?;

        // Get log exporter endpoint
        let log_exporter_endpoint = env::var("OTEL_LOGS_EXPORTER")
            .unwrap_or_else(|_| "http://localhost:4318/v1/logs".into());

        Ok(OtelConfig {
            bind_address,
            log_exporter_endpoint,
        })
    }
}
