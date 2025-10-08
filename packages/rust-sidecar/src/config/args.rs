use clap::Parser;
use std::env;

#[derive(Parser, Clone)]
#[command(name = "openai-moodle-sidecar")]
#[command(about = "OpenAI sidecar for Moodle plugin")]
pub struct Args {
    #[arg(long)]
    pub api_key: String,

    #[arg(long)]
    pub base_url: Option<String>,

    #[arg(long, default_value = "adorsys")]
    pub model: String,

    #[arg(long, default_value = "1000")]
    pub max_tokens: u32,

    #[arg(long, default_value = "2000")]
    pub summarize_threshold: usize,

    #[arg(long, default_value = "false", help = "Force HTTP server mode")]
    pub http_mode: bool,

    #[arg(long, default_value = "8080", help = "HTTP server port")]
    pub port: u16,
}

impl Args {
    pub fn from_env() -> Self {
        let mut args = Args::parse();

        // Set environment variables manually for each field
        if let Ok(api_key) = env::var("OPENAI_API_KEY") {
            args.api_key = api_key;
        }

        if let Ok(base_url) = env::var("OPENAI_BASE_URL") {
            args.base_url = Some(base_url);
        }

        if let Ok(model) = env::var("OPENAI_MODEL") {
            args.model = model;
        }

        if let Ok(max_tokens) = env::var("MAX_TOKENS").and_then(|v| v.parse().map_err(|_| std::env::VarError::NotPresent)) {
            args.max_tokens = max_tokens;
        }

        if let Ok(summarize_threshold) = env::var("SUMMARIZE_THRESHOLD").and_then(|v| v.parse().map_err(|_| std::env::VarError::NotPresent)) {
            args.summarize_threshold = summarize_threshold;
        }

        if let Ok(http_mode) = env::var("HTTP_MODE").and_then(|v| v.parse().map_err(|_| std::env::VarError::NotPresent)) {
            args.http_mode = http_mode;
        }

        if let Ok(port) = env::var("PORT").and_then(|v| v.parse().map_err(|_| std::env::VarError::NotPresent)) {
            args.port = port;
        }

        args
    }
}
