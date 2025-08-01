use std::sync::Arc;

use kube::{Resource, ResourceExt};
use kube_runtime::controller::{self, Action};
use tracing::info;

use crate::{crds::crd::Moodle, error::Error, reconciller::{create_or_update_rs::create_or_update_replicaset}, Data};


pub async fn reconcile(moodle: Arc<Moodle>, ctx: Arc<Data>) -> Result<Action, Error> {
    let client = &ctx.client;
    
    if moodle.meta().deletion_timestamp.is_some() {
        info!("Moodle {} is marked for deletion. Skipping reconciliation.", moodle.name_any());
        return Ok(Action::await_change());
    }
    
    match create_or_update_replicaset(&moodle, client).await {
        Ok(_) => {
            tracing::info!("Successfully created or updated ReplicaSet.");
        }
        Err(e) => {
            tracing::error!("Failed to create or update ReplicaSet: {}", e);
            return Err(e.into());
        }
    } 
    
    //  requeue after 30s
    Ok(controller::Action::requeue(std::time::Duration::from_secs(30)))
}