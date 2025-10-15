use bytes::Bytes;
use http_body_util::{BodyExt, Full};
use hyper::{
    body::Incoming as IncomingBody, header, server::conn::http1, service::service_fn, Method,
    Request, Response, StatusCode,
};
use kube::Client;
use std::net::SocketAddr;
use tokio::net::TcpListener;
use tracing::info;

static NOTFOUND: &[u8] = b"Not Found";

type Result<T> = std::result::Result<T, Box<dyn std::error::Error + Send + Sync>>;
type BoxBody = http_body_util::combinators::BoxBody<Bytes, hyper::Error>;

fn full<T: Into<Bytes>>(chunk: T) -> BoxBody {
    Full::new(chunk.into())
        .map_err(|never| match never {})
        .boxed()
}

async fn handle_request(req: Request<IncomingBody>) -> Result<Response<BoxBody>> {
    match (req.method(), req.uri().path()) {
        (&Method::GET, "/readyz") => match check_kube_readyz().await {
            Ok(_) => {
                let body = r#"{"status": "ok"}"#;
                Ok(Response::builder()
                    .status(StatusCode::OK)
                    .header(header::CONTENT_TYPE, "application/json")
                    .body(full(body))
                    .unwrap())
            }
            Err(err) => {
                let body = format!(r#"{{"status": "error", "message": "{err}"}}"#);
                Ok(Response::builder()
                    .status(StatusCode::SERVICE_UNAVAILABLE)
                    .header(header::CONTENT_TYPE, "application/json")
                    .body(full(body))
                    .unwrap())
            }
        },

        _ => Ok(Response::builder()
            .status(StatusCode::NOT_FOUND)
            .body(full(NOTFOUND))
            .unwrap()),
    }
}

pub async fn start_server(bind_address: SocketAddr) -> Result<()> {
    let listener = TcpListener::bind(bind_address).await?;

    info!("Metrics Server running at http://{bind_address}");

    loop {
        let (stream, _) = listener.accept().await?;

        // Spawn a new task to handle the incoming HTTP connection
        tokio::spawn(async move {
            let io = hyper_util::rt::TokioIo::new(stream);
            let service = service_fn(handle_request);

            if let Err(err) = http1::Builder::new().serve_connection(io, service).await {
                eprintln!("Error serving connection: {err:?}");
            }
        });
    }
}

async fn check_kube_readyz() -> Result<String> {
    let client = Client::try_default().await?;

    let req = Request::builder().uri("/readyz?verbose").body(Vec::new())?; // Empty body

    let text = client.request_text(req).await?;

    let all_ok = text
        .lines()
        .filter(|line| line.contains("[+]"))
        .all(|line| line.to_lowercase().contains("ok"));

    if all_ok {
        Ok("Kubernetes API server is ready".into())
    } else {
        Err(format!("Kubernetes API server not ready:\n{text}").into())
    }
}
