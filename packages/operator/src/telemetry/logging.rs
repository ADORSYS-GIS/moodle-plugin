use opentelemetry_appender_tracing::layer::OpenTelemetryTracingBridge;
use opentelemetry_otlp::{LogExporter, Protocol, WithExportConfig};
use opentelemetry_sdk::logs::SdkLoggerProvider;
use tracing_subscriber::{prelude::*, EnvFilter};

use crate::telemetry::resource::get_resource;

/// Initialize logging and tracing with OpenTelemetry and tracing subscriber.
/// - Sets up a log exporter to send logs to the specified OTLP endpoint.
pub fn init_logs_and_tracing(log_exporter_endpoint: &str) -> SdkLoggerProvider {
    let exporter = LogExporter::builder()
        .with_http()
        .with_endpoint(log_exporter_endpoint)
        .with_protocol(Protocol::HttpBinary)
        .build()
        .expect("Failed to create log exporter");

    let logger_provider = SdkLoggerProvider::builder()
        .with_batch_exporter(exporter)
        .with_resource(get_resource())
        .build();

    let otel_layer = OpenTelemetryTracingBridge::new(&logger_provider);

    let env_filter = EnvFilter::try_from_default_env()
        .unwrap_or_else(|_| EnvFilter::new("info"))
        .add_directive("hyper=off".parse().unwrap())
        .add_directive("tonic=off".parse().unwrap())
        .add_directive("h2=off".parse().unwrap())
        .add_directive("reqwest=off".parse().unwrap());

    let otel_layer = otel_layer.with_filter(env_filter);

    let fmt_layer = tracing_subscriber::fmt::layer()
        .with_thread_names(false)
        .with_target(false)
        .with_filter(EnvFilter::try_from_default_env().unwrap_or_else(|_| EnvFilter::new("info")));

    tracing_subscriber::registry()
        .with(otel_layer)
        .with(fmt_layer)
        .init();

    logger_provider
}
