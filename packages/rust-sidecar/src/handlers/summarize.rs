use crate::client::OpenAIClient;
use crate::communication::{Request, Response};
use actix_web::web::Json;
use crate::handlers::{create_system_message, create_user_message, send_completion_request};

pub async fn handle(client: &OpenAIClient, request: Json<Request>) -> Response {
    let messages = vec![
        create_system_message("Summarize the following text concisely while preserving key information."),
        create_user_message(&request.content),
    ];

    match send_completion_request(client, messages, Some(client.max_tokens / 2)).await {
        Ok(response) => Response::success(response),
        Err(e) => Response::error(format!("Summarization error: {}", e)),
    }
}
