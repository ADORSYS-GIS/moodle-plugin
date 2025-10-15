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
mod server;
mod telemetry;
use crate::{
    config::Config,
    reconciller::controller::controller_moodle_cluster,
    server::start_server,
    telemetry::{logging::LoggerHandle, metrics::MetricsHandle},
};

#[derive(Clone)]
struct Data {
    client: Client,
}

#[global_allocator]
static GLOBAL: MiMalloc = MiMalloc;

#[tokio::main]
async fn main() -> Result<()> {
    // Load env configuration
    let config = Config::from_env()?;

    //Initialize logs
    let logger_handle = LoggerHandle::init(&config.log_exporter_endpoint);
    // Initialize metrics
    let metrics_handle = MetricsHandle::init(&config.metrics_exporter_endpoint);
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

    // Spawn Server Task
    let server_bind_addr = config.bind_address;
    let server_error_tx = tx.clone();
    tokio::spawn(async move {
        if let Err(e) = start_server(server_bind_addr).await {
            let _ = server_error_tx.send(format!("server failed: {e}")).await;
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

    // Gracefully shutdown metrics and logging providers before exiting
    metrics_handle.shutdown();
    logger_handle.shutdown();

    Ok(())
}
