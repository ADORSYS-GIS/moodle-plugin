use actix_web::{web, App, HttpServer, HttpResponse, Result as ActixResult};
use openai_moodle_sidecar::{
    handlers, Args, OpenAIClient, Request, Response,
};
use serde_json;
use std::io::{self, BufRead, BufReader, Write};
use std::sync::Arc;
use tracing::{error, info, warn};
use tracing_subscriber::fmt;

#[tokio::main]
async fn main() -> Result<(), Box<dyn std::error::Error>> {
    // Initialize tracing subscriber to write logs to stderr so stdout remains clean
    fmt().with_writer(|| std::io::stderr()).init();
    
    let args = Args::from_env();
    
    // Create shared client instance wrapped in Arc for efficiency
    let client = Arc::new(OpenAIClient::new(
        args.api_key.clone(),
        args.base_url.clone(),
        args.model.clone(),
        args.max_tokens,
        args.summarize_threshold,
    ));

    // Determine mode based on arguments or stdin availability
    let mode = determine_mode(&args);
    
    match mode {
        Mode::Http => run_http_server(client, args).await,
        Mode::Stdio => run_stdio_mode(client).await,
    }
}

#[derive(Debug)]
enum Mode {
    Http,
    Stdio,
}

fn determine_mode(args: &Args) -> Mode {
    // Check if explicitly set to HTTP mode
    if args.http_mode {
        return Mode::Http;
    }
    
    // Check if stdin is available (not a TTY)
    if atty::is(atty::Stream::Stdin) {
        // Running in terminal, prefer HTTP mode
        Mode::Http
    } else {
        // Stdin has data piped to it, use stdio mode
        Mode::Stdio
    }
}

async fn run_http_server(
    client: Arc<OpenAIClient>, 
    args: Args
) -> Result<(), Box<dyn std::error::Error>> {
    info!("Starting OpenAI Moodle Sidecar in HTTP mode on 127.0.0.1:{}", args.port);
    
    HttpServer::new(move || {
        App::new()
            .app_data(web::Data::new(client.clone()))
            .route("/ai", web::post().to(process_request_http))
            .route("/health", web::get().to(health_check))
    })
    .bind(("127.0.0.1", args.port))?
    .run()
    .await?;
    
    Ok(())
}

async fn run_stdio_mode(client: Arc<OpenAIClient>) -> Result<(), Box<dyn std::error::Error>> {
    info!("Starting OpenAI Moodle Sidecar in STDIO mode");
    
    let stdin = io::stdin();
    let reader = BufReader::new(stdin.lock());

    for line in reader.lines() {
        match line {
            Ok(input) => {
                if input.trim().is_empty() {
                    continue;
                }

                match serde_json::from_str::<Request>(&input) {
                    Ok(request) => {
                        let response = match request.action.as_str() {
                            "chat" => handlers::chat::handle(&client, web::Json(request)).await,
                            "summarize" => handlers::summarize::handle(&client, web::Json(request)).await,
                            "analyze" => handlers::analyze::handle(&client, web::Json(request)).await,
                            _ => {
                                warn!("Unknown action: {}", request.action);
                                Response::error("Unknown action")
                            },
                        };
                        
                        match serde_json::to_string(&response) {
                            Ok(json) => {
                                println!("{}", json);
                                io::stdout().flush()?;
                            }
                            Err(e) => {
                                error!("Failed to serialize response: {}", e);
                            }
                        }
                    }
                    Err(e) => {
                        error!("Failed to parse request: {}", e);
                        let error_response = Response::error("Invalid JSON request");
                        if let Ok(json) = serde_json::to_string(&error_response) {
                            println!("{}", json);
                            io::stdout().flush()?;
                        }
                    }
                }
            }
            Err(e) => {
                error!("Error reading from stdin: {}", e);
                break;
            }
        }
    }
    Ok(())
}

// HTTP mode request handler
async fn process_request_http(
    client: web::Data<Arc<OpenAIClient>>,
    req: web::Json<Request>
) -> ActixResult<HttpResponse> {
    info!("Processing HTTP request: {}", req.action);
    
    let response = match req.action.as_str() {
        "chat" => handlers::chat::handle(&client, req).await,
        "summarize" => handlers::summarize::handle(&client, req).await,
        "analyze" => handlers::analyze::handle(&client, req).await,
        _ => Response::error("Unknown action"),
    };

    Ok(HttpResponse::Ok().json(response))
}

// Health check endpoint for HTTP mode
async fn health_check() -> ActixResult<HttpResponse> {
    Ok(HttpResponse::Ok().json(serde_json::json!({
        "status": "healthy",
        "service": "openai-moodle-sidecar"
    })))
}
