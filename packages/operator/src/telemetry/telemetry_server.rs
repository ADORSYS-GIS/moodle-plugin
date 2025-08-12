use bytes::Bytes;
use http_body_util::{ BodyExt, Full};
use hyper::{
    body::Incoming as IncomingBody, header, server::conn::http1, service::service_fn, Method,
    Request, Response, StatusCode,
};

use prometheus::{Encoder, IntCounter, Gauge, Registry, TextEncoder};
use std::{ net::SocketAddr};
use std::sync::Arc;
use tokio::{net::TcpListener, sync::Mutex};
use tracing::info;
use sysinfo::{ProcessesToUpdate, System,  get_current_pid};

static NOTFOUND: &[u8] = b"Not Found";

type Result<T> = std::result::Result<T, Box<dyn std::error::Error + Send + Sync>>;
type BoxBody = http_body_util::combinators::BoxBody<Bytes, hyper::Error>;


#[derive(Debug)]
pub struct AppMetrics {
    registry: Registry,
    request_counter: IntCounter,
    memory_gauge: Gauge,
    cpu_gauge: Gauge,
}

impl AppMetrics {
    pub fn new() -> Self {
        let registry = Registry::new();
        
        let request_counter = IntCounter::new("http_requests_total", "Number of HTTP requests").unwrap();
        let memory_gauge = Gauge::new("app_memory_bytes", "Memory used by the app in bytes").unwrap();
        let cpu_gauge = Gauge::new("app_cpu_percent", "CPU usage percent of the app").unwrap();
        
        registry.register(Box::new(request_counter.clone())).unwrap();
        registry.register(Box::new(memory_gauge.clone())).unwrap();
        registry.register(Box::new(cpu_gauge.clone())).unwrap();
        
        Self {
            registry,
            request_counter,
            memory_gauge,
            cpu_gauge,
        }
    }
}


fn full<T: Into<Bytes>>(chunk: T) -> BoxBody {
    Full::new(chunk.into())
    .map_err(|never| match never {})
    .boxed()
}

async fn handle_request(
    req: Request<IncomingBody>,
    metrics: Arc<Mutex<AppMetrics>>,
) -> Result<Response<BoxBody>> {
    match (req.method(), req.uri().path()) {
        (&Method::GET, "/") => {
            {
                let metrics = metrics.lock().await;
                metrics.request_counter.inc();
            }
            
            Ok(Response::new(full("Hello! This request was counted.")))
        }
        
        (&Method::GET, "/metrics") => {
            let encoder = TextEncoder::new();
            let metrics = metrics.lock().await;
            let metric_families = metrics.registry.gather();
            
            let mut buffer = Vec::new();
            encoder.encode(&metric_families, &mut buffer).unwrap();
            
            let response = Response::builder()
            .status(StatusCode::OK)
            .header(header::CONTENT_TYPE, "text/plain; version=0.0.4")
            .body(full(buffer))
            .unwrap();
            
            Ok(response)
        }
        
        _ => Ok(Response::builder()
        .status(StatusCode::NOT_FOUND)
        .body(full(NOTFOUND))
        .unwrap()),
    }
}



async fn update_system_metrics(metrics: Arc<Mutex<AppMetrics>>) {
    let mut sys = System::new_all();
    let pid = get_current_pid().unwrap().as_u32();
    let get_pid= get_current_pid().unwrap();
    let pid_array= [get_pid];
    
    loop {
        sys.refresh_processes(ProcessesToUpdate::Some(&pid_array), true);
        sys.refresh_cpu_all();
        sys.refresh_memory();
        
        if let Some(proc) = sys.process(sysinfo::Pid::from_u32(pid)) {
            let  metrics = metrics.lock().await;
            metrics.memory_gauge.set(proc.memory() as f64 / 1048576.0); // Bytes → Mb
            metrics.cpu_gauge.set(proc.cpu_usage() as f64);
        }
        
        tokio::time::sleep(std::time::Duration::from_secs(5)).await;
    }
}

pub async fn start_otel_server() -> Result<()> {    
    
    let app_metrics = Arc::new(Mutex::new(AppMetrics::new()));
    let metrics_clone = app_metrics.clone();
    tokio::spawn(update_system_metrics(metrics_clone));
    let addr: SocketAddr = "0.0.0.0:8888".parse()?;
    let listener = TcpListener::bind(addr).await?;
    
    
    info!("Metrics Server running at http://{addr}");
    
    loop {
        let (stream, _) = listener.accept().await?;
        let metrics = Arc::clone(&app_metrics);
        tokio::spawn(async move {
            let io = hyper_util::rt::TokioIo::new(stream);
            let service = service_fn(move |req| handle_request(req, Arc::clone(&metrics)));
            
            if let Err(err) = http1::Builder::new().serve_connection(io, service).await {
                eprintln!("Error serving connection: {:?}", err);
            }
        });
    }
    
   
}