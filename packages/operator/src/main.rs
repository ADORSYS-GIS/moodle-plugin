use kube::Client;
use tracing::info;
use anyhow::{Result};
use mimalloc::MiMalloc;
mod crds;
mod error;
mod reconciller;
mod telemetry;
use crate::{reconciller::controller::controller_moodle_cluster, telemetry::{logging::init_logs_and_tracing,telemetry_server::{ start_otel_server}}};


#[derive(Clone)]
struct Data {
    client: Client,
}

#[global_allocator]
static GLOBAL: MiMalloc = MiMalloc;

#[tokio::main]
async fn main() -> Result<()> {
    // tracing_subscriber::fmt::init();
    init_logs_and_tracing();
    
    let client = Client::try_default().await?;
    
    let client_clone = client.clone();
    info!("Started controller and server...");
    
    tokio::spawn(
        async move {
            controller_moodle_cluster(&client_clone).await;
        }
    );
    let _ = start_otel_server().await;
    
    Ok(())

}

#[global_allocator]
static GLOBAL: MiMalloc = MiMalloc;


#[tokio::main]
async fn main() -> Result<()> {
    tracing_subscriber::fmt::init();
    
    let client = Client::try_default().await?;
    
    info!("Started controller");
    controller_moodle_cluster(&client).await;
    
    Ok(())
}