use openai_moodle_sidecar::{Args, OpenAIClient, Request};
use serde_json;
use std::io::{self, BufRead, BufReader, Write};
use tracing::{error, info};
use tracing_subscriber::fmt;

#[tokio::main]
async fn main() -> Result<(), Box<dyn std::error::Error>> {
    // Initialize tracing subscriber to write logs to stderr so stdout remains clean
    fmt().with_writer(|| std::io::stderr()).init();
    
    // Prefer environment variables over CLI parsing so secrets can be provided
    // via container environment (OPENAI_API_KEY, OPENAI_BASE_URL, OPENAI_MODEL).
    let args = Args::from_env();
    
    let client = OpenAIClient::new(
        args.api_key,
        args.base_url,
        args.model,
        args.max_tokens,
        args.summarize_threshold,
    );
    
    info!("OpenAI Moodle Sidecar started");
    
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
                        let response = client.process_request(request).await;
                        
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
