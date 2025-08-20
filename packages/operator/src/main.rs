use anyhow::Result;
use kube::Client;
use mimalloc::MiMalloc;
use std::process;
use tokio::sync::mpsc;
use tracing::{error, info};
mod config;
mod crds;
mod error;
mod reconciller;
mod telemetry;
use crate::{
    config::OtelConfig,
    reconciller::controller::controller_moodle_cluster,
    telemetry::{logging::init_logs_and_tracing, telemetry_server::start_otel_server},
};

#[derive(Clone)]
struct Data {
    client: Client,
}

#[global_allocator]
static GLOBAL: MiMalloc = MiMalloc;

#[tokio::main]
async fn main() -> Result<()> {
    // Load OTEL-related configuration
    let config = OtelConfig::from_env()?;

    init_logs_and_tracing(&config.log_exporter_endpoint);

    let client = Client::try_default().await?;

    // Create an mpsc channel for receiving errors from background tasks
    let (tx, mut rx) = mpsc::channel::<String>(2);

    // Spawn Error Listener Task
    let error_listener = tokio::spawn(async move {
        if let Some(e) = rx.recv().await {
            error!("Critical error received: {e}");
            process::exit(1);
        }
    });

    // Spawn OTEL Server Task
    let otel_bind_addr = config.bind_address;
    let otel_error_tx = tx.clone();
    tokio::spawn(async move {
        if let Err(e) = start_otel_server(otel_bind_addr).await {
            let _ = otel_error_tx.send(format!("OTEL server failed: {e}")).await;
        }
    });

    // Spawn Controller Task
    let controller_error_tx = tx.clone();
    tokio::spawn(async move {
        info!("Starting Moodle controller");
        if let Err(e) = controller_moodle_cluster(&client).await {
            let _ = controller_error_tx
                .send(format!("Controller error: {e}"))
                .await;
        }
    });

    let _ = error_listener.await;

    Ok(())
}
