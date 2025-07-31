//   use tracing::{ error};
//   use k8s_openapi::api::apps::v1::{ReplicaSet};
//   use kube::api::{Patch, PatchParams};
//   use kube::{Client, Api, ResourceExt};
//   use serde_json::json;
//   use anyhow::{Result};
  
  
//   use crate::crds::crd::Moodle;
//   use crate::error::Error;
  
  
//   pub async fn update_status(moodle: &Moodle, client: &Client) -> Result<(), Error> {
//     let namespace = match moodle.namespace() {
//         Some(ns) => ns,
//         None => {
//             error!("Moodle resource has no namespace");
//             return Err(Error::MissingNamespace);
//         }
//     };
    
//     let rs_name = format!("moodle-rs-{}", moodle.name_any());
//     let rs_api: Api<ReplicaSet> = Api::namespaced(client.clone(), &namespace);
//     let cr_api: Api<Moodle> = Api::namespaced(client.clone(), &namespace);
    
//     match cr_api.get_opt(&moodle.name_any()).await {
//         Ok(Some(_)) => (),
//         Ok(None) => {
//             error!("Moodle CR {} not found while updating status", moodle.name_any());
//             return Ok(()); // not an error — resource was deleted
//         }
//         Err(e) => {
//             error!("Error retrieving Moodle CR {}: {}", moodle.name_any(), e);
//             return Err(Error::MoodleCRGetFailed(e));
//         }
//     }
    
//     let rs = match rs_api.get(&rs_name).await {
//         Ok(rs) => rs,
//         Err(e) => {
//             error!("Error fetching ReplicaSet {}: {}", rs_name, e);
//             return Err(Error::ReplicaSetGetFailed(e));
//         }
//     };
    
//     let ready = rs.status.and_then(|s| s.ready_replicas).unwrap_or(0);
    
//     let phase = match ready {
//         r if r >= moodle.spec.replicas => "Running",
//         r if r > 0 => "Progressing",
//         _ => "Pending",
//     };
    
//     let status = json!({
//         "status": {
//             "readyReplicas": ready,
//             "phase": phase
//         }
//     });
    
//     match cr_api
//     .patch_status(
//         &moodle.name_any(),
//         &PatchParams::apply("moodle-operator"),
//         &Patch::Merge(&status),
//     )
//     .await
//     {
//         Ok(_) => (),
//         Err(e) => {
//             error!("Failed to patch status for {}: {}", moodle.name_any(), e);
//             return Err(Error::StatusPatchFailed(e));
//         }
//     }
    
//     Ok(())
// }