use k8s_openapi::api::core::v1::{PersistentVolumeClaim, PersistentVolumeClaimSpec, VolumeResourceRequirements};
use k8s_openapi::apimachinery::pkg::api::resource::Quantity;
use kube::api::PostParams;
use kube::Resource;
use kube::{Client, Api, ResourceExt};

use anyhow::{Result};

use crate::crds::crd::Moodle;
use crate::error::Error;


pub async fn create_pvcs_for_replicas(moodle: &Moodle, client: &Client) -> Result<(), Error> {
    let namespace = moodle.namespace().unwrap();
    let pvc_api: Api<PersistentVolumeClaim> = Api::namespaced(client.clone(), &namespace);
    
    for i in 0..moodle.spec.replicas {
        let pvc_name = format!("pvc-{}-{}", moodle.name_any(), i);
        
        if pvc_api.get_opt(&pvc_name).await?.is_some() {
            continue; 
        }
        
        let pvc = PersistentVolumeClaim {
            metadata: kube::core::ObjectMeta {
                name: Some(pvc_name.clone()),
                owner_references: Some(vec![moodle.controller_owner_ref(&()).unwrap()]),
                ..Default::default()
            },
            spec: Some(PersistentVolumeClaimSpec {
                access_modes: Some(vec!["ReadWriteOnce".to_string()]),
                resources: Some(VolumeResourceRequirements {
                      requests: Some([("storage".to_string(), Quantity("1Gi".to_string()))].into()),
                    ..Default::default()
                }),
                ..Default::default()
            }),
            ..Default::default()
        };
        
        pvc_api.create(&PostParams::default(), &pvc).await?;
    }
    
    Ok(())
}