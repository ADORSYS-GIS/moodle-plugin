use kube::CustomResource;
use schemars::JsonSchema;
use serde::{Deserialize, Serialize};

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
    pub db_type: String,
    pub name: String,
}

#[derive(Debug, Deserialize, Serialize, Clone, Default, PartialEq, JsonSchema)]
pub struct MoodleStatus {
    ready_replicas: Option<i32>,
    phase: Option<String>,
}

impl MoodleSpec {
    pub fn validate(&self) -> Result<(), String> {
        if self.image.trim().is_empty() {
            return Err("Image field must not be empty.".to_string());
        }
        if self.replicas < 0 {
            return Err("Replicas must be 0 or greater.".to_string());
        }
        if self.service_type != "ClusterIP"
            && self.service_type != "NodePort"
            && self.service_type != "LoadBalancer"
        {
            return Err(format!("Invalid serviceType: {}", self.service_type));
        }
        if self.database.db_type.is_empty()
            || self.database.name.is_empty()
            || self.database.user.is_empty()
            || self.database.password.is_empty()
            || self.database.host.is_empty()
        {
            return Err("Database config fields must not be empty.".to_string());
        }
        Ok(())
    }
}
