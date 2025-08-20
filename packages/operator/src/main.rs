use anyhow::Result;
use kube::Client;
use mimalloc::MiMalloc;
use tracing::info;
mod crds;
mod error;
mod reconciller;
use crate::reconciller::controller::controller_moodle_cluster;

#[derive(Clone)]
struct Data {
    client: Client,
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
