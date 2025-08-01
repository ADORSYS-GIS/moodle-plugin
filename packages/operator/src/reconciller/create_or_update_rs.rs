use std::collections::BTreeMap;
use k8s_openapi::api::apps::v1::{ReplicaSet, ReplicaSetSpec};
use k8s_openapi::api::core::v1::{Container, EnvVar, PersistentVolumeClaimVolumeSource, PodSpec, PodTemplateSpec, Volume, VolumeMount};
use kube::api::{ Patch, PatchParams, PostParams};
use kube::Resource;
use kube::{Client, Api, ResourceExt};
use k8s_openapi::apimachinery::pkg::apis::meta::v1::LabelSelector;
use anyhow::{Result};


use crate::crds::crd::Moodle;
use crate::error::Error;


pub async fn create_or_update_replicaset(moodle: &Moodle, client: &Client) -> Result<(), Error> {
    let namespace = moodle.namespace().unwrap();
    let app_label_value = moodle.name_any();
    let labels = BTreeMap::from([("app".to_string(), app_label_value)]);
    
    
    let rs_name =  moodle.name_any();
    let rs_api: Api<ReplicaSet> = Api::namespaced(client.clone(), &namespace);
    
    
    let pvc_mount = VolumeMount {
        name: "moodle-data".to_string(),
        mount_path: "/bitnami/moodle".to_string(),
        ..Default::default()
    };
    
    let container = Container {
        name: "moodle".to_string(),
        image: Some(moodle.spec.image.clone()),
        volume_mounts: Some(vec![pvc_mount]),
        env: Some(vec![
            EnvVar {
                name: "MOODLE_DATABASE_HOST".to_string(),
                value: Some(moodle.spec.database.host.clone()),
                ..Default::default()
            },
            EnvVar {
                name: "MOODLE_DATABASE_TYPE".to_string(),
                value: Some(moodle.spec.database.db_type.clone()),
                ..Default::default()
            },
            EnvVar {
                name: "MOODLE_DATABASE_PORT_NUMBER".to_string(),
                value: Some(moodle.spec.database.port.to_string()),
                ..Default::default()
            },
            EnvVar {
                name: "MOODLE_DATABASE_USER".to_string(),
                value: Some(moodle.spec.database.user.clone()),
                ..Default::default()
            },
            EnvVar {
                name: "MOODLE_DATABASE_PASSWORD".to_string(),
                value: Some(moodle.spec.database.password.clone()),
                ..Default::default()
            },
            EnvVar {
                name: "MOODLE_DATABASE_NAME".to_string(),
                value: Some(moodle.spec.database.name.clone()), 
                ..Default::default()
            },
            
            ]),
            ..Default::default()
        };
        
        
        let volume = Volume {
            name: "moodle-data".to_string(),
            persistent_volume_claim: Some(PersistentVolumeClaimVolumeSource {
                claim_name: moodle.spec.pvc_name.clone(),               
                ..Default::default()
            }),
            ..Default::default()
        };
        
        let pod_template = PodTemplateSpec {
            metadata: Some(kube::core::ObjectMeta {
                labels: Some(labels.clone()),
                ..Default::default()
            }),
            spec: Some(PodSpec {
                containers: vec![container],
                volumes: Some(vec![volume]),
                ..Default::default()
            }),
        };
        
        let rs_spec = ReplicaSetSpec {
            replicas: Some(moodle.spec.replicas),
            selector: LabelSelector {
                match_labels: Some(labels.clone()),
                ..Default::default()
            },
            template: Some(pod_template),
            ..Default::default()
        };
        
        let replicaset = ReplicaSet {
            metadata: kube::core::ObjectMeta {
                name: Some(rs_name.clone()),
                owner_references: Some(vec![moodle.controller_owner_ref(&()).unwrap()]),
                labels: Some(labels),
                ..Default::default()
            },
            spec: Some(rs_spec),
            ..Default::default()
        };
        
        let pp = PostParams::default();
        match rs_api.get(&rs_name).await {
            Ok(_) => {
                let patch = Patch::Apply(&replicaset);
                match rs_api
                .patch(&rs_name, &PatchParams::apply("moodle-operator").force(), &patch)
                .await
                {
                    Ok(_) => return Ok(()),
                    Err(err) => return Err(Error::ReplicaSetCreationFailed(err)),
                };
            }
            Err(err) => {
                let err = Error::ReplicaSetGetFailed(err);
                match err.is_not_found() {
                    true => match rs_api.create(&pp, &replicaset).await {
                        Ok(_) => return Ok(()),
                        Err(err) => return Err(Error::ReplicaSetCreationFailed(err)),
                    },
                    false => return Err(err),
                };
            }
        }        
        
    }