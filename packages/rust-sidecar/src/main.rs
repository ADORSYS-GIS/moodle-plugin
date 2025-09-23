use openai_moodle_sidecar::{Args, OpenAIClient, Request};
use clap::Parser;
use serde_json;
use std::io::{self, BufRead, BufReader, Write};
use tracing::{error, info};
use tracing_subscriber::fmt::init;  // This line imports the correct function

#[tokio::main]
async fn main() -> Result<(), Box<dyn std::error::Error>> {
    init();  // Initialize tracing subscriber for logging
    
    let args = Args::parse();
    
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
