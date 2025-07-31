use kube::{Client, ResourceExt};
use kube_runtime::controller;
use std::sync::Arc;
use tracing::{info, error};
use anyhow::{Result};
mod crds;
mod error;
mod reconciller;
use crate::crds::crd::{Moodle, MoodleSpec};
use crate::error::Error;
use crate::reconciller::controller::controller_moodle_cluster;

#[derive(Clone)]
struct Data {
    client: Client,
}

#[tokio::main]
async fn main() -> Result<()> {
    tracing_subscriber::fmt::init();
    
    let client = Client::try_default().await?;

    info!("Started controller");
    controller_moodle_cluster(&client).await;
    
    Ok(())
}


fn error_policy(moodle: Arc<Moodle>, err: &Error, _ctx: Arc<Data>) -> controller::Action {
    error!(
        "Error reconciling Moodle '{}': {}",
        moodle.name_any(),
        err
    );
    controller::Action::requeue(std::time::Duration::from_secs(10))
}


