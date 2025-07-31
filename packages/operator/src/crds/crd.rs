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
    pub service_type: String, // Could also use enum for ClusterIP, etc.
    pub database: DatabaseConfig,
}

#[derive(Debug, Deserialize, Serialize, Clone, PartialEq, JsonSchema)]
pub struct DatabaseConfig {
    pub host: String,
    pub port: u16,
    pub user: String,
    pub password: String,
}

#[derive(Debug, Deserialize, Serialize, Clone, Default, PartialEq, JsonSchema)]
pub struct MoodleStatus {
    ready_replicas: Option<i32>,
    phase: Option<String>,
}