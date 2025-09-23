use crate::client::OpenAIClient;
use crate::communication::{Request, Response};
use crate::handlers::{create_system_message, create_user_message, send_completion_request};

pub async fn handle(client: &OpenAIClient, request: Request) -> Response {
    let prompt = format!(
        "Analyze the following educational content and provide insights about learning objectives, difficulty level, and suggestions for improvement:\n\n{}",
        request.content
    );

    let messages = vec![
        create_system_message("You are an educational content analyst. Provide structured analysis of learning materials."),
        create_user_message(&prompt),
    ];

    match send_completion_request(client, messages, None).await {
        Ok(response) => Response::success(response),
        Err(e) => Response::error(format!("Analysis error: {}", e)),
    }
}