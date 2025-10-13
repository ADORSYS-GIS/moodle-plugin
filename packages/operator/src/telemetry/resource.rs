use opentelemetry_sdk::Resource;
use std::sync::OnceLock;

static RESOURCE: OnceLock<Resource> = OnceLock::new();

pub fn get_resource() -> Resource {
    RESOURCE
        .get_or_init(|| {
            Resource::builder()
                .with_service_name("moodle-operator")
                .build()
        })
        .clone()
}
