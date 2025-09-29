use crate::communication::{Request, Response};
use crate::handlers;
use serde_json::json;
use tracing::info;

pub struct OpenAIClient {
    pub client: reqwest::Client,
    pub api_key: String,
    pub base_url: String,
    pub model: String,
    pub max_tokens: u32,
    pub summarize_threshold: usize,
}

impl OpenAIClient {
    pub fn new(api_key: String, base_url: Option<String>, model: String, max_tokens: u32, summarize_threshold: usize) -> Self {
        let client = reqwest::Client::new();
        let base_url = base_url.unwrap_or_else(|| "https://ai.kivoyo.com/api".to_string());
        
        Self {
            client,
            api_key,
            base_url,
            model,
            max_tokens,
            summarize_threshold,
        }
    }

    pub async fn process_request(&self, request: Request) -> Response {
        info!("Processing request: {}", request.action);
        
        match request.action.as_str() {
            "chat" => handlers::chat::handle(self, request).await,
            "summarize" => handlers::summarize::handle(self, request).await,
            "analyze" => handlers::analyze::handle(self, request).await,
            _ => Response::error("Unknown action"),
        }
    }

    pub async fn send_chat_request(&self, messages: Vec<serde_json::Value>, max_tokens: Option<u32>) -> Result<String, Box<dyn std::error::Error + Send + Sync>> {
        // Accept either a full completions endpoint or a base URL and normalize it.
        let base = self.base_url.trim_end_matches('/');
        let url = if base.ends_with("/chat/completions") || base.ends_with("chat/completions") {
            base.to_string()
        } else {
            format!("{}/chat/completions", base)
        };
        let tokens = max_tokens.unwrap_or(self.max_tokens);
        
        let payload = json!({
            "model": self.model,
            "messages": messages,
            "max_tokens": tokens
        });

        let response = self.client
            .post(&url)
            .header("Authorization", format!("Bearer {}", self.api_key))
            .header("Content-Type", "application/json")
            .json(&payload)
            .send()
            .await?;

        if !response.status().is_success() {
            let error_text = response.text().await?;
            return Err(format!("API error: {}", error_text).into());
        }

        let response_data: serde_json::Value = response.json().await?;
        
        if let Some(choices) = response_data["choices"].as_array() {
            if let Some(first_choice) = choices.first() {
                if let Some(content) = first_choice["message"]["content"].as_str() {
                    return Ok(content.to_string());
                }
            }
        }

        Err("No response content found".into())
    }
}