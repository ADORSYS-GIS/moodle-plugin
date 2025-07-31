use std::sync::Arc;

use futures::StreamExt;
use kube::{Api, Client};
use kube_runtime::Controller;
use tracing::info;

use crate::{crds::crd::Moodle, error, error_policy, reconciller::reconcille_moodle::reconcile, Data};


pub async fn controller_moodle_cluster(client: &Client) {
    
    let moodles: Api<Moodle> = Api::namespaced(client.clone(), "default"); // or dynamic namespace
    
    Controller::new(moodles, Default::default())
    .run(reconcile, error_policy, Arc::new(Data { client: client.clone() }))
    .for_each(|res| async move {
        match res {
            Ok((obj_ref, _action)) => info!("Reconciled {:?}", obj_ref.name),
            Err(e) => error!("Reconcile failed: {:?}", e),
        }
    })
    .await;
}