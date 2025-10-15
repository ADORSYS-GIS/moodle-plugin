use opentelemetry::{
    metrics::{MeterProvider, ObservableGauge},
    KeyValue,
};
use opentelemetry_otlp::{MetricExporter, Protocol, WithExportConfig};
use opentelemetry_sdk::metrics::{PeriodicReader, SdkMeterProvider};
use std::{sync::Arc, time::Duration};
use sysinfo::{get_current_pid, ProcessesToUpdate, System};

use crate::telemetry::resource::get_resource;

/// Struct to hold provider and gauges so their lifetime is explicit
pub struct MetricsHandle {
    pub provider: SdkMeterProvider,
    _cpu_gauge: Arc<ObservableGauge<f64>>,
    _mem_gauge: Arc<ObservableGauge<f64>>,
}

impl MetricsHandle {
    /// Initialize metrics, register gauges, and keep handles alive
    pub fn init(endpoint: &str) -> Self {
        let exporter = MetricExporter::builder()
            .with_http()
            .with_endpoint(endpoint)
            .with_protocol(Protocol::HttpBinary)
            .build()
            .expect("Failed to create metric exporter");

        let reader = PeriodicReader::builder(exporter)
            .with_interval(Duration::from_secs(30))
            .build();

        let provider = SdkMeterProvider::builder()
            .with_reader(reader)
            .with_resource(get_resource())
            .build();

        let meter = provider.meter("system-metrics");

        // CPU gauge
        let cpu_gauge = meter
            .f64_observable_gauge("operator_cpu_usage")
            .with_description("CPU usage of this process in %")
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

        // Memory gauge
        let mem_gauge = meter
            .f64_observable_gauge("operator_memory")
            .with_description("Memory usage of this process in MB")
            .with_unit("mb")
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

        Self {
            provider,
            _cpu_gauge: Arc::new(cpu_gauge),
            _mem_gauge: Arc::new(mem_gauge),
        }
    }

    /// Shutdown provider and flush metrics
    pub fn shutdown(&self) {
        if let Err(e) = self.provider.shutdown() {
            eprintln!("Failed to shutdown logger provider: {e}");
        }
    }
}
