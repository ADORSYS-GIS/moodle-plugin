pub mod chat;
pub mod summarize;
pub mod analyze;

use crate::client::OpenAIClient;
use openai::chat::{ChatCompletion, ChatCompletionMessage, ChatCompletionMessageRole};

// Common function to create system messages
pub fn create_system_message(content: &str) -> ChatCompletionMessage {
    ChatCompletionMessage {
        role: ChatCompletionMessageRole::System,
        content: Some(content.to_string()),
        name: None,
        function_call: None,
        tool_call_id: None,
        tool_calls: None,
    }
}

// Common function to create user messages
pub fn create_user_message(content: &str) -> ChatCompletionMessage {
    ChatCompletionMessage {
        role: ChatCompletionMessageRole::User,
        content: Some(content.to_string()),
        name: None,
        function_call: None,
        tool_call_id: None,
        tool_calls: None,

    }
}

// Common function to send chat completion request
pub async fn send_completion_request(
    client: &OpenAIClient,
    messages: Vec<ChatCompletionMessage>,
    max_tokens: Option<u32>,
) -> Result<String, Box<dyn std::error::Error + Send + Sync>> {
    let tokens = max_tokens.unwrap_or(client.max_tokens);
    
    let completion = ChatCompletion::builder(&client.model, messages)
        .max_tokens(tokens)
        .create()
        .await?;

    if let Some(choice) = completion.choices.first() {
        if let Some(content) = &choice.message.content {
            Ok(content.clone())
        } else {
            Err("No content in response".into())
        }
    } else {
        Err("No choices in response".into())
    }
}
