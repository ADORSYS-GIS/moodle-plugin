use kube::CustomResource;
use serde::{Deserialize, Serialize};
use schemars::JsonSchema;


#[derive(CustomResource, Debug, Deserialize, Serialize, Clone, JsonSchema)]
#[kube(
    kind = "Moodle",
    group = "moodle.adorsys.com",
    version = "v1",
    namespaced,
    shortname = "mdl",
    status = "MoodleStatus",
    derive = "PartialEq",
    printcolumn = r#"{"name":"Phase", "type":"string", "description":"Status", "jsonPath":".status.phase"}"#
)]
#[derive(PartialEq)]
pub struct MoodleSpec {
    pub image: String,
    pub replicas: i32,
    #[serde(rename = "serviceType")]
    pub service_type: String,
    #[serde(rename = "pvcName")]
    pub pvc_name: String,
    pub database: DatabaseConfig,
}

#[derive(Debug, Deserialize, Serialize, Clone, PartialEq, JsonSchema)]
pub struct DatabaseConfig {
    pub host: String,
    pub port: u16,
    pub user: String,
    pub password: String,
    #[serde(rename = "type")]
    pub db_type: String, // e.g. "pgsql", "mariadb", etc.
    pub name: String,  
}

#[derive(Debug, Deserialize, Serialize, Clone, Default, PartialEq, JsonSchema)]
pub struct MoodleStatus {
    ready_replicas: Option<i32>,
    phase: Option<String>,
}
