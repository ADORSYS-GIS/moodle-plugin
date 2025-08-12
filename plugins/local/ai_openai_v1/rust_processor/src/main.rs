use async_openai::{
    config::OpenAIConfig, types::{ChatCompletionRequestUserMessageArgs, CreateChatCompletionRequestArgs}, Client
};
use serde::{Deserialize, Serialize};
use std::io::{self, BufRead, Write};
use std::time::{Instant};

// --- Data Structures (from PHP) ---
#[derive(Deserialize, Debug)]
struct AIRequest {
    id: String,
    prompt: String,
    // We can add more fields like context, user_id etc. as needed.
}

// --- Data Structures (to PHP) ---
#[derive(Serialize, Debug)]
struct AIResponse {
    id: String,
    ai_response_text: String,
    processing_time_ms: u64,
    model_version: String,
    error: Option<String>,
}

#[derive(Serialize)]
struct StatusMessage {
    status: String,
    message: String,
}

// --- AI Processor Core ---
struct AIProcessor {
    client: Client<OpenAIConfig>,
    model_name: String,
}

impl AIProcessor {
    fn new() -> Self {
        // The async-openai crate automatically uses OPENAI_API_KEY and OPENAI_BASE_URL
        // environment variables if they are set, which our PHP script will provide.
        let client = Client::new();
        let model_name = "gpt-3.5-turbo".to_string(); // Default model
        Self { client, model_name }
    }

    // Signal PHP that the processor is ready via stdout
    fn signal_ready(&self) -> Result<(), Box<dyn std::error::Error>> {
        eprintln!("[Rust AI] Processor is ready.");
        let status = StatusMessage {
            status: "ready".to_string(),
            message: "AI processor initialized and ready.".to_string(),
        };
        // Use println! for stdout, which PHP reads
        println!("{}", serde_json::to_string(&status)?);
        io::stdout().flush()?; // IMPORTANT: Flush immediately
        Ok(())
    }

    // Main request processing logic
    async fn process_request(&self, request: AIRequest) -> AIResponse {
        let start_time = Instant::now();
        eprintln!("[Rust AI] Received request ID: {}", request.id);

        let mut response_text = String::new();
        let mut error_message = None;

        let request_builder = CreateChatCompletionRequestArgs::default()
            .model(&self.model_name)
            .messages([ChatCompletionRequestUserMessageArgs::default()
                .content(request.prompt.clone())
                .build()
                .unwrap()
                .into()])
            .build();

        match request_builder {
            Ok(req) => match self.client.chat().create(req).await {
                Ok(response) => {
                    if let Some(choice) = response.choices.into_iter().next() {
                        response_text = choice.message.content.unwrap_or_default();
                    } else {
                        error_message = Some("API returned no choices.".to_string());
                    }
                }
                Err(e) => {
                    let err_str = format!("API Error: {}", e);
                    eprintln!("[Rust AI] {}", err_str);
                    error_message = Some(err_str);
                }
            },
            Err(e) => {
                let err_str = format!("Failed to build request: {}", e);
                eprintln!("[Rust AI] {}", err_str);
                error_message = Some(err_str);
            }
        }

        AIResponse {
            id: request.id,
            ai_response_text: response_text,
            processing_time_ms: start_time.elapsed().as_millis() as u64,
            model_version: self.model_name.clone(),
            error: error_message,
        }
    }
}

// --- Main Loop ---
#[tokio::main]
async fn main() -> Result<(), Box<dyn std::error::Error>> {
    eprintln!("[Rust AI] Starting up...");
    // This will load .env file if it exists, useful for local testing
    dotenvy::dotenv().ok();

    let processor = AIProcessor::new();
    processor.signal_ready()?;

    let stdin = io::stdin();
    let mut reader = io::BufReader::new(stdin.lock());

    loop {
        let mut line = String::new();
        match reader.read_line(&mut line) {
            Ok(0) => { // EOF
                eprintln!("[Rust AI] Stdin closed. Shutting down.");
                break;
            }
            Ok(_) => {
                let trimmed_line = line.trim();
                if trimmed_line.is_empty() {
                    continue;
                }

                match serde_json::from_str::<AIRequest>(trimmed_line) {
                    Ok(request) => {
                        let response = processor.process_request(request).await;
                        println!("{}", serde_json::to_string(&response)?);
                        io::stdout().flush()?;
                    }
                    Err(e) => {
                        eprintln!("[Rust AI] Failed to parse request: {} - Line: '{}'", e, trimmed_line);
                        // Send a parse error back to PHP
                        let error_response = AIResponse {
                            id: "unknown".to_string(),
                            ai_response_text: String::new(),
                            processing_time_ms: 0,
                            model_version: processor.model_name.clone(),
                            error: Some(format!("Invalid JSON request: {}", e)),
                        };
                        println!("{}", serde_json::to_string(&error_response)?);
                        io::stdout().flush()?;
                    }
                }
            }
            Err(e) => {
                eprintln!("[Rust AI] Error reading from stdin: {}", e);
                break;
            }
        }
    }

    Ok(())
}