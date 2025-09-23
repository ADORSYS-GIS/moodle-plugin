//! # GISA AI IPC Handler Binary
//!
//! Main binary for the GIS Assistant AI IPC handler.
//! Processes commands from PHP and coordinates with ai-core for AI operations.

use ai_core::{AiCore, init as init_ai_core};
use ai_utils::Config;
use ipc_handler::{IpcHandler};
use ipc_handler::protocol::{Request, Response, Command, CommandResult};
use std::env;
use std::process;
use tracing::{error, info, warn, debug};

/// Main entry point for the IPC handler
fn main() {
    // Initialize configuration
    let config = match initialize_config() {
        Ok(config) => config,
        Err(e) => {
            eprintln!("Failed to initialize configuration: {}", e);
            process::exit(1);
        }
    };

    // Initialize AI core (logging, metrics)
    if let Err(e) = init_ai_core(&config) {
        eprintln!("Failed to initialize AI core: {}", e);
        process::exit(1);
    }

    info!("Starting GISA AI IPC Handler");

    // Create and run the service
    match run_service(config) {
        Ok(_) => {
            info!("GISA AI IPC Handler stopped gracefully");
        }
        Err(e) => {
            error!("Service error: {}", e);
            process::exit(1);
        }
    }
}

/// Initialize configuration from environment and command line arguments
fn initialize_config() -> Result<Config, Box<dyn std::error::Error>> {
    let mut config = Config::new();

    // Set default values
    config.set("ai.model_path", "/opt/gisa/models")?;
    config.set("ai.backend", "onnx")?;
    config.set("logging.level", "info")?;
    config.set("ipc.buffer_size", "8192")?;
    config.set("ipc.timeout_ms", "30000")?;

    // Override with environment variables
    if let Ok(model_path) = env::var("GISA_MODEL_PATH") {
        config.set("ai.model_path", &model_path)?
    }
    if let Ok(log_level) = env::var("GISA_LOG_LEVEL") {
        config.set("logging.level", &log_level)?
    }
    if let Ok(backend) = env::var("GISA_AI_BACKEND") {
        config.set("ai.backend", &backend)?
    }

    // Parse command line arguments
    let args: Vec<String> = env::args().collect();
    for i in 1..args.len() {
        match args[i].as_str() {
            "--model-path" => {
                if i + 1 < args.len() {
                    config.set("ai.model_path", &args[i + 1])?;
                }
            }
            "--log-level" => {
                if i + 1 < args.len() {
                    config.set("logging.level", &args[i + 1])?;
                }
            }
            "--help" => {
                print_help();
                process::exit(0);
            }
            _ => {}
        }
    }

    Ok(config)
}

/// Print help information
fn print_help() {
    println!("GISA AI IPC Handler");
    println!("Usage: gisa_ai_handler [OPTIONS]");
    println!();
    println!("Options:");
    println!("  --model-path PATH    Path to AI models directory");
    println!("  --log-level LEVEL    Logging level (trace, debug, info, warn, error)");
    println!("  --help              Show this help message");
    println!();
    println!("Environment Variables:");
    println!("  GISA_MODEL_PATH     Path to AI models directory");
    println!("  GISA_LOG_LEVEL      Logging level");
    println!("  GISA_AI_BACKEND     AI backend (onnx, ggml)");
}

/// Run the main service loop
fn run_service(config: Config) -> Result<(), Box<dyn std::error::Error>> {
    // Initialize components
    let mut ipc_handler = IpcHandler::new();
    let mut ai_core = AiCore::new(&config)?;

    info!("Service components initialized successfully");

    // Send ready signal
    ipc_handler.send_ready()?;
    info!("Service ready, waiting for commands");

    // Main processing loop
    loop {
        match process_single_request(&mut ipc_handler, &mut ai_core) {
            Ok(should_continue) => {
                if !should_continue {
                    info!("Received shutdown command, exiting gracefully");
                    break;
                }
            }
            Err(e) => {
                error!("Error processing request: {}", e);
                // Continue processing other requests even after errors
            }
        }
    }

    Ok(())
}

/// Process a single request and return whether to continue
fn process_single_request(
    ipc_handler: &mut IpcHandler,
    ai_core: &mut AiCore,
) -> Result<bool, Box<dyn std::error::Error>> {
    let request = ipc_handler.read_request()?;
    debug!("Processing request: method={}", request.method);

    // Parse command from request
    let command = parse_command(&request)?;
    
    // Execute command
    match execute_command(command, &request, ipc_handler, ai_core)? {
        CommandResult::ShutdownComplete => {
            ipc_handler.send_success(request.id, Some(serde_json::json!({
                "status": "shutdown_complete"
            })))?;
            Ok(false) // Signal to stop the loop
        }
        result => {
            // Send result back to PHP
            let result_json = serde_json::to_value(result)?;
            ipc_handler.send_success(request.id, Some(result_json))?;
            Ok(true) // Continue processing
        }
    }
}

/// Parse command from request
fn parse_command(request: &Request) -> Result<Command, Box<dyn std::error::Error>> {
    match request.method.as_str() {
        "init" => {
            let model = request.params.as_ref()
                .and_then(|p| p.get("model"))
                .and_then(|v| v.as_str())
                .map(|s| s.to_string());
            
            let params = request.params.as_ref()
                .and_then(|p| p.get("params"))
                .and_then(|v| v.as_object())
                .map(|obj| obj.iter().map(|(k, v)| (k.clone(), v.clone())).collect());

            Ok(Command::Init { model, params })
        }
        "inference" => {
            let prompt = request.get_param::<String>("prompt")?;
            let max_tokens = request.params.as_ref()
                .and_then(|p| p.get("max_tokens"))
                .and_then(|v| v.as_u64())
                .map(|n| n as usize);
            let temperature = request.params.as_ref()
                .and_then(|p| p.get("temperature"))
                .and_then(|v| v.as_f64())
                .map(|f| f as f32);
            let stream = request.params.as_ref()
                .and_then(|p| p.get("stream"))
                .and_then(|v| v.as_bool());

            Ok(Command::Inference { prompt, max_tokens, temperature, stream })
        }
        "load_model" => {
            let model_path = request.get_param::<String>("model_path")?;
            Ok(Command::LoadModel { model_path })
        }
        "cache_clear" => Ok(Command::CacheClear),
        "get_stats" => Ok(Command::GetStats),
        "ping" => Ok(Command::Ping),
        "shutdown" => Ok(Command::Shutdown),
        _ => Err(format!("Unknown method: {}", request.method).into()),
    }
}

/// Execute a parsed command
fn execute_command(
    command: Command,
    request: &Request,
    ipc_handler: &mut IpcHandler,
    ai_core: &mut AiCore,
) -> Result<CommandResult, Box<dyn std::error::Error>> {
    match command {
        Command::Init { model, params: _ } => {
            info!("Initializing AI service with model: {:?}", model);
            
            // Load default model if specified
            if let Some(model_path) = model.as_ref() {
                ai_core.load_model(Some(model_path))?;
            }
            
            Ok(CommandResult::Initialized {
                model: model.unwrap_or_else(|| "default".to_string()),
                status: "ready".to_string(),
            })
        }
        Command::Inference { prompt, max_tokens, temperature: _, stream } => {
            info!("Processing inference request: {} chars, stream={:?}", prompt.len(), stream);
            
            // Send processing signal for long operations
            if let Some(id) = &request.id {
                ipc_handler.send_processing(Some(id.clone()))?;
            }
            
            if stream.unwrap_or(false) {
                // Handle streaming inference
                handle_streaming_inference(&prompt, max_tokens, ipc_handler, ai_core, &request.id)
            } else {
                // Handle regular inference
                let result = ai_core.inference(&prompt, max_tokens.unwrap_or(512))?;
                Ok(CommandResult::InferenceComplete {
                    output: result.text,
                    tokens_generated: result.tokens_generated,
                    inference_time_ms: result.inference_time_ms,
                    model_used: result.model_used,
                })
            }
        }
        Command::LoadModel { model_path } => {
            info!("Loading model: {}", model_path);
            
            if let Some(id) = &request.id {
                ipc_handler.send_processing(Some(id.clone()))?;
            }
            
            let model_info = ai_core.load_model(Some(&model_path))?;
            let mut info_map = std::collections::HashMap::new();
            info_map.insert("format".to_string(), serde_json::json!(model_info.format));
            info_map.insert("size_bytes".to_string(), serde_json::json!(model_info.size_bytes));
            info_map.insert("loaded_at".to_string(), serde_json::json!(model_info.loaded_at.to_rfc3339()));
            
            Ok(CommandResult::ModelLoaded {
                model_path,
                model_info: info_map,
            })
        }
        Command::CacheClear => {
            info!("Clearing inference cache");
            ai_core.clear_cache();
            Ok(CommandResult::CacheCleared {
                entries_removed: 0, // TODO: Return actual count
            })
        }
        Command::GetStats => {
            debug!("Getting service statistics");
            let ai_stats = ai_core.get_stats();
            let ipc_stats = ipc_handler.stats();
            
            let mut ipc_map = std::collections::HashMap::new();
            ipc_map.insert("requests_processed".to_string(), serde_json::json!(ipc_stats.requests_processed));
            ipc_map.insert("responses_sent".to_string(), serde_json::json!(ipc_stats.responses_sent));
            
            let mut ai_map = std::collections::HashMap::new();
            ai_map.insert("models_loaded".to_string(), serde_json::json!(ai_stats.models_loaded));
            ai_map.insert("inferences_performed".to_string(), serde_json::json!(ai_stats.inferences_performed));
            ai_map.insert("cache_hits".to_string(), serde_json::json!(ai_stats.cache_hits));
            ai_map.insert("cache_misses".to_string(), serde_json::json!(ai_stats.cache_misses));
            
            Ok(CommandResult::Stats {
                ipc: ipc_map,
                ai_core: ai_map,
                uptime_seconds: ai_stats.uptime.as_secs(),
            })
        }
        Command::Ping => {
            debug!("Processing ping request");
            Ok(CommandResult::Pong {
                message: "pong".to_string(),
                timestamp: chrono::Utc::now().to_rfc3339(),
            })
        }
        Command::Shutdown => {
            info!("Processing shutdown request");
            Ok(CommandResult::ShutdownComplete)
        }
    }
}

/// Handle streaming inference with real-time token output
fn handle_streaming_inference(
    prompt: &str,
    max_tokens: Option<usize>,
    ipc_handler: &mut IpcHandler,
    _ai_core: &mut AiCore,
    request_id: &Option<String>,
) -> Result<CommandResult, Box<dyn std::error::Error>> {
    use ai_core::{StreamingEngine, StreamEvent};
    
    let streaming_engine = StreamingEngine::new();
    let token_stream = streaming_engine.stream_inference(prompt)?;
    
    let mut total_tokens = 0;
    let mut complete_text = String::new();
    let start_time = std::time::Instant::now();
    
    // Process streaming tokens
    for event in token_stream {
        match event {
            StreamEvent::Token(token) => {
                complete_text.push_str(&token.text);
                total_tokens += 1;
                
                // Send streaming token response
                let stream_response = Response::success(
                    request_id.clone(),
                    Some(serde_json::json!({
                        "event": "token",
                        "data": {
                            "token": token.text,
                            "confidence": token.confidence
                        }
                    }))
                );
                
                if let Err(e) = ipc_handler.send_response(&stream_response) {
                    warn!("Failed to send streaming token: {}", e);
                    break;
                }
                
                // Check max tokens limit
                if let Some(max) = max_tokens {
                    if total_tokens >= max {
                        break;
                    }
                }
            }
            StreamEvent::Complete { total_tokens: stream_total, inference_time_ms } => {
                info!("Streaming inference completed: {} tokens in {}ms", stream_total, inference_time_ms);
                break;
            }
            StreamEvent::Error(error_msg) => {
                error!("Streaming error: {}", error_msg);
                return Err(error_msg.into());
            }
            StreamEvent::Started => {
                debug!("Streaming inference started");
            }
        }
    }
    
    let total_time = start_time.elapsed();
    
    Ok(CommandResult::InferenceComplete {
        output: complete_text,
        tokens_generated: total_tokens,
        inference_time_ms: total_time.as_millis() as u64,
        model_used: "streaming_model".to_string(),
    })
}
