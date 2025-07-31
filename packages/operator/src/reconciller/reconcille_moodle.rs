use std::sync::Arc;

use kube::{Resource, ResourceExt};
use kube_runtime::controller::{self, Action};
use tracing::info;

use crate::{crds::crd::Moodle, error::Error, reconciller::{create_or_update_rs::create_or_update_replicaset, create_pv_for_rs::create_pvcs_for_replicas}, Data};


pub async fn reconcile(moodle: Arc<Moodle>, ctx: Arc<Data>) -> Result<Action, Error> {
    let client = &ctx.client;
    let ns = moodle.namespace().unwrap();
    let name = moodle.name_any();
    
    if moodle.meta().deletion_timestamp.is_some() {
        info!("Moodle {} is marked for deletion. Skipping reconciliation.", moodle.name_any());
        return Ok(Action::await_change());
    }
    
    match create_pvcs_for_replicas(&moodle, client).await {
        Ok(_) => {
            tracing::info!("Successfully created PVCs for replicas.");
        }
        Err(e) => {
            tracing::error!("Failed to create PVCs for replicas: {}", e);
            return Err(e.into());
        }
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
    
    // match update_status(&moodle, client).await {
    //     Ok(_) => {
    //         tracing::info!("Successfully updated Moodle status.");
    //     }
    //     Err(e) => {
    //         tracing::error!("Failed to update Moodle status: {}", e);
    //         return Err(e.into());
    //     }
    // }
    
    // For now, just requeue after 30s
    Ok(controller::Action::requeue(std::time::Duration::from_secs(30)))
}