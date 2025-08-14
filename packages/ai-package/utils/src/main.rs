use ai_utils::config::Config;
use ai_utils::metrics::{init_metrics, record_inference};
use std::collections::HashMap;

#[tokio::main]
async fn main() -> Result<(), Box<dyn std::error::Error>> {
    // Initialize logging
    ai_utils::logging::init_logger("debug", None)?;
    
    // Initialize metrics with config
    let mut config = Config::default();
    config.data = serde_json::json!({
        "metrics": {
            "log_updates": true
        }
    });
    init_metrics(&config);
    
    // Start Prometheus server in background
    let server_handle = tokio::spawn(async {
        if let Err(e) = ai_utils::metrics::start_prometheus_server("127.0.0.1:9090").await {
            eprintln!("Prometheus server error: {}", e);
        }
    });
    
    // Record some test metrics
    let labels: HashMap<&str, &str> = [("model", "test-model")].iter().cloned().collect();
    record_inference(150.0, labels);
    
    println!("Prometheus server started on http://127.0.0.1:9090/metrics");
    println!("Test with: curl http://127.0.0.1:9090/metrics");
    println!("Press Ctrl+C to stop");
    
    // Keep the server running
    server_handle.await?;
    Ok(())
}
