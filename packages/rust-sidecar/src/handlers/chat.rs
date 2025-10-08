use crate::client::OpenAIClient;
use crate::communication::{Request, Response};
use actix_web::web::Json;

use crate::handlers::{create_system_message, create_user_message, send_completion_request};
use crate::handlers::summarize;
use tracing::warn;

pub async fn handle(client: &OpenAIClient, request: Json<Request>) -> Response {
    let mut messages = vec![
        create_system_message("You are a helpful educational assistant integrated with Moodle.")
    ];

    // Handle context - summarize if too long
    if let Some(context) = &request.context {
        if context.len() > client.summarize_threshold {
            match summarize_context(client, &context).await {
                Ok(summary) => {
                    messages.push(create_system_message(&format!("Context summary: {}", summary)));
                }
                Err(e) => {
                    warn!("Failed to summarize context: {}", e);
                    let truncated = context.chars().take(client.summarize_threshold).collect::<String>();
                    messages.push(create_system_message(&format!("Context (truncated): {}", truncated)));
                }
            }
        } else {
            messages.push(create_system_message(&format!("Context: {}", context)));
        }
    }

    messages.push(create_user_message(&request.content));

    match send_completion_request(client, messages, None).await {
        Ok(response) => Response::success(response),
        Err(e) => Response::error(format!("Chat error: {}", e)),
    }
}

async fn summarize_context(client: &OpenAIClient, context: &str) -> Result<String, Box<dyn std::error::Error + Send + Sync>> {
    let summarize_request = Json(Request::new("summarize".to_string(), context.to_string()));
    let response = summarize::handle(client, summarize_request).await;
    
    if response.success {
        Ok(response.data.unwrap_or_default())
    } else {
        Err(response.error.unwrap_or("Summarization failed".to_string()).into())
    }
}