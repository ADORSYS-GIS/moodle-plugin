use crate::communication::{Request, Response};
use crate::handlers;
use reqwest::{Client as ReqwestClient, Error};
use serde::Serialize;
use tracing::info;

pub struct OpenAIClient {
    pub client: ReqwestClient,
    pub model: String,
    pub max_tokens: u32,
    pub summarize_threshold: usize,
}

#[derive(Serialize)]
struct OpenAIRequest<'a> {
    model: &'a str,
    prompt: &'a str,
    max_tokens: u32,
}

impl OpenAIClient {
    pub fn new(api_key: String, base_url: Option<String>, model: String, max_tokens: u32, summarize_threshold: usize) -> Self {
        // Create reqwest client
        let client = ReqwestClient::builder()
            .danger_accept_invalid_certs(true)  // Handle invalid certificates (only for development)
            .build()
            .expect("Failed to create reqwest client");

        Self {
            client,
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

    pub async fn generate_text(&self, prompt: &str) -> Result<String, Error> {
        let openai_url = "https://ai.kivoyo.com/api"; // Default OpenAI endpoint
        
        let request_data = OpenAIRequest {
            model: &self.model,
            prompt,
            max_tokens: self.max_tokens,
        };

        let response = self.client.post(openai_url)
            .header("Authorization", format!("Bearer {}", "YOUR_OPENAI_API_KEY"))
            .json(&request_data)
            .send()
            .await?;

        let response_text = response.text().await?;

        Ok(response_text)
    }
}
