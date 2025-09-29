pub mod chat;
pub mod summarize;
pub mod analyze;

use crate::client::OpenAIClient;
use serde_json::{json, Value};

/// Create a system message as a JSON object compatible with the reqwest-based client
pub fn create_system_message(content: &str) -> Value {
    json!({
        "role": "system",
        "content": content,
    })
}

/// Create a user message as a JSON object compatible with the reqwest-based client
pub fn create_user_message(content: &str) -> Value {
    json!({
        "role": "user",
        "content": content,
    })
}

/// Send completion request via the reqwest-based OpenAIClient
pub async fn send_completion_request(
    client: &OpenAIClient,
    messages: Vec<Value>,
    max_tokens: Option<u32>,
) -> Result<String, Box<dyn std::error::Error + Send + Sync>> {
    // Delegate to the client's reqwest-based sender
    client.send_chat_request(messages, max_tokens).await
}
