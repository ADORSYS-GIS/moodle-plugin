use std::sync::Arc;
use tracing::{info, error};
use futures::StreamExt;
use kube::{Api, Client, ResourceExt};
use kube_runtime::{controller, Controller};

use crate::{crds::crd::Moodle, error:: Error, reconciller::reconcille_moodle::reconcile, Data};


pub async fn controller_moodle_cluster(client: &Client) {
    
    let moodles: Api<Moodle> = Api::namespaced(client.clone(), "default");
    
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

fn error_policy(moodle: Arc<Moodle>, err: &Error, _ctx: Arc<Data>) -> controller::Action {
    error!(
        "Error reconciling Moodle '{}': {}",
        moodle.name_any(),
        err
    );
    controller::Action::requeue(std::time::Duration::from_secs(10))
}
