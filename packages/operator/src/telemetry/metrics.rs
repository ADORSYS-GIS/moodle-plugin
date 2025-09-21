use opentelemetry::{global, KeyValue};
use opentelemetry_otlp::{MetricExporter, Protocol, WithExportConfig};
use opentelemetry_sdk::metrics::{PeriodicReader, SdkMeterProvider};
use std::time::Duration;
use sysinfo::{get_current_pid, ProcessesToUpdate, System};

use crate::telemetry::resource::get_resource;

fn init_metrics(endpoint: &str) -> SdkMeterProvider {
    let exporter = MetricExporter::builder()
        .with_http()
        .with_endpoint(endpoint)
        .with_protocol(Protocol::HttpBinary)
        .build()
        .expect("Failed to create metric exporter");

    let reader = PeriodicReader::builder(exporter)
        .with_interval(Duration::from_secs(30))
        .build();
    SdkMeterProvider::builder()
        .with_reader(reader)
        .with_resource(get_resource())
        .build()
}

/// Initialize system-level metrics (CPU and memory usage) and register them with OpenTelemetry.
///
/// Sets up the global meter provider with a custom OTLP exporter
/// Once registered, these gauges will be periodically observed and exported
/// by the OpenTelemetry SDK as long as the program is running.
pub fn init_system_metrics(endpoint: &str) {
    // Setup metrics
    let meter_provider = init_metrics(endpoint);
    global::set_meter_provider(meter_provider.clone());

    // Get meter
    let meter = global::meter("system-metrics");

    // Observable gauge for CPU
    let _cpu_gauge = meter
        .f64_observable_gauge("operator_cpu_usage")
        .with_description("CPU usage in % ")
        .with_unit("%")
        .with_callback(|observer| {
            let mut sys = System::new_all();
            let pid = get_current_pid().unwrap().as_u32();
            sys.refresh_processes(
                ProcessesToUpdate::Some(&[sysinfo::Pid::from_u32(pid)]),
                true,
            );

            if let Some(proc) = sys.process(sysinfo::Pid::from_u32(pid)) {
                let cpu = proc.cpu_usage() as f64;
                observer.observe(cpu, &[KeyValue::new("process", "self")]);
            }
        })
        .build();

    // Observable gauge for Memory
    let _mem_gauge = meter
        .f64_observable_gauge("operator_memory")
        .with_description("Memory usage in MB ")
        .with_unit("MB")
        .with_callback(|observer| {
            let mut sys = System::new_all();
            let pid = get_current_pid().unwrap().as_u32();
            sys.refresh_processes(
                ProcessesToUpdate::Some(&[sysinfo::Pid::from_u32(pid)]),
                true,
            );

            if let Some(proc) = sys.process(sysinfo::Pid::from_u32(pid)) {
                let mem_mb = proc.memory() as f64 / 1048576.0;
                observer.observe(mem_mb, &[KeyValue::new("process", "self")]);
            }
        })
        .build();
}
