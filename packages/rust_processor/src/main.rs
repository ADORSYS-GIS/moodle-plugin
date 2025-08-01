// // --- Crate Imports ---
// // Serde is used for serializing Rust structs into JSON and deserializing JSON into Rust structs.
// use serde::{Deserialize, Serialize};
// // For timing operations.
// use std::time::{Instant, SystemTime, UNIX_EPOCH};
// // `anyhow` provides a simple and ergonomic way to handle errors.
// use anyhow::Result;
// // The primary library for interacting with the OpenAI API.
// use async_openai::{Client, config::OpenAIConfig};
// // Specific builder structs from async-openai to construct our API request.
// use async_openai::types::{
//     CreateChatCompletionRequestArgs,
//     ChatCompletionRequestSystemMessageArgs,
//     ChatCompletionRequestUserMessageArgs,
//     ChatCompletionRequestMessage,
// };
// // The asynchronous runtime that will power our application.
// use tokio::io::{self as tokio_io, AsyncBufReadExt, AsyncWriteExt, BufReader};
// // `log` provides a standard logging API, and `env_logger` is an implementation.
// use log::{info, error, warn};

// // --- Data Structures (The "Contract" between PHP and Rust) ---

// // This struct defines the shape of a request coming from PHP.
// // `#[derive(Deserialize, Debug)]` automatically implements the code needed
// // to parse this struct from a JSON string and to print it for debugging.
// #[derive(Deserialize, Debug)]
// struct AIRequest {
//     id: String, // A unique ID to match requests with responses.
//     user_id: u64, // The Moodle user's ID.
//     message: String, // The user's direct message.
//     context: serde_json::Value, // A flexible field for any other JSON data (course_id, etc.).
//     conversation_history: Vec<String>, // A list of previous messages for context.
//     large_input_text: Option<String>, // An optional field for large texts, e.g., for summarization.
// }

// // This struct defines the shape of a response going back to PHP.
// // `#[derive(Serialize, Debug, Default)]` implements code to convert this struct
// // into a JSON string, print it for debugging, and create a default empty version.
// #[derive(Serialize, Debug, Default)]
// struct AIResponse {
//     id: String,
//     ai_response_text: String,
//     confidence: f32,
//     processing_time_ms: u128,
//     model_version: String,
//     timestamp: f64,
//     error: Option<String>, // If an error occurred, it will be described here.
// }

// // A special message used once at startup to tell PHP the process is ready.
// #[derive(Serialize)]
// struct StatusMessage {
//     status: String,
//     message: String,
// }

// // --- AI Processor Core ---

// // This struct holds the state of our AI application, primarily the API client.
// struct AIProcessor {
//     client: Client<OpenAIConfig>,
//     model_version: String,
// }

// impl AIProcessor {
//     // The constructor for our processor.
//     fn new() -> Result<Self> {
//         // Reads the OPENAI_API_KEY from the environment variables passed by PHP's proc_open.
//         // Fails with a descriptive error if the key is not set.
//         let api_key = std::env::var("OPENAI_API_KEY")
//             .map_err(|_| anyhow::anyhow!("FATAL: OPENAI_API_KEY environment variable not set"))?;
        
//         // Creates the configuration for the OpenAI client with the key.
//         let config = OpenAIConfig::new().with_api_key(api_key);
//         // Creates the client instance.
//         let client = Client::with_config(config);
        
//         Ok(Self {
//             client,
//             model_version: "gpt-4o-via-rust-v2.1-robust".to_string(),
//         })
//     }

//     // Sends the one-time "ready" signal to stdout for PHP to read.
//     async fn signal_ready(&self) -> Result<()> {
//         let status = StatusMessage {
//             status: "ready".to_string(),
//             message: format!("AI processor initialized. Model family: {}", self.model_version),
//         };
//         // Get a handle to the standard output pipe.
//         let mut stdout = tokio_io::stdout();
//         // Convert the status message to a JSON string and write it to the pipe.
//         stdout.write_all(serde_json::to_string(&status)?.as_bytes()).await?;
//         // Write a newline character, which PHP will use as a delimiter.
//         stdout.write_all(b"\n").await?;
//         // `flush()` ensures the data is sent immediately and not held in a buffer.
//         stdout.flush().await?;
//         // Log to stderr for debugging purposes (doesn't interfere with stdout pipe).
//         info!("Sent 'ready' signal to PHP.");
//         Ok(())
//     }

//     // The main logic for handling a single request.
//     async fn process_request(&self, request: AIRequest) -> AIResponse {
//         let start_time = Instant::now();
//         info!("Processing request ID: {}", request.id);

//         // Call the separate function that actually interacts with the OpenAI API.
//         let response = self.generate_ai_response(&request).await;
//         // Calculate the total time taken.
//         let processing_time = start_time.elapsed().as_millis();

//         // Get the current Unix timestamp.
//         let timestamp = SystemTime::now()
//             .duration_since(UNIX_EPOCH)
//             .unwrap_or_default()
//             .as_secs_f64();

//         // Construct the AIResponse based on whether `generate_ai_response` succeeded or failed.
//         match response {
//             Ok(ai_response_text) => AIResponse {
//                 id: request.id,
//                 ai_response_text,
//                 confidence: 0.98,
//                 processing_time_ms: processing_time,
//                 model_version: self.model_version.clone(),
//                 timestamp,
//                 error: None, // No error occurred.
//             },
//             Err(e) => {
//                 let error_message = format!("Error processing request: {}", e);
//                 error!("{}", error_message);
//                 AIResponse {
//                     id: request.id,
//                     error: Some(error_message),
//                     ..Default::default() // Use default values for other fields.
//                 }
//             }
//         }
//     }

//     // This async function builds the prompt and calls the OpenAI API.
//     async fn generate_ai_response(&self, request: &AIRequest) -> Result<String> {
//         let mut messages: Vec<ChatCompletionRequestMessage> = vec![];

//         // 1. Add the System Message to set the AI's persona.
//         messages.push(
//             ChatCompletionRequestSystemMessageArgs::default()
//                 .content("You are a helpful and creative assistant integrated into the Moodle Learning Management System.")
//                 .build()?
//                 .into(),
//         );

//         // 2. Add the conversation history, alternating between user and assistant roles.
//         for (i, msg) in request.conversation_history.iter().enumerate() {
//             let role_args = if i % 2 == 0 { // Even index = user message
//                 ChatCompletionRequestUserMessageArgs::default().content(msg.as_str()).build()?.into() // Convert to user message(as a string slice)
//             } else { // Odd index = previous AI response
//                 ChatCompletionRequestSystemMessageArgs::default().content(msg.as_str()).build()?.into() // Convert to system message
//             };
//             messages.push(role_args);
//         }

//         // 3. Add the current user message.
//         messages.push(
//             ChatCompletionRequestUserMessageArgs::default()
//                 .content(request.message.as_str()) // convert the message from a String to a &str 
//                 .build()?
//                 .into(),
//         );

//         // 4. Build the final request object.
//         let req = CreateChatCompletionRequestArgs::default()
//             .model("gpt-4o")
//             .messages(messages)
//             .build()?;

//         // 5. Make the asynchronous network call.
//         let response = self.client.chat().create(req).await?;

//         // 6. Safely extract the response content.
//         if let Some(choice) = response.choices.first() {
//             if let Some(content) = &choice.message.content {
//                 return Ok(content.clone());
//             }
//         }
//         // If the response format was unexpected, return a descriptive error.
//         Err(anyhow::anyhow!("No content found in OpenAI API response choice"))
//     }
// }

// // --- Main Application Loop ---

// // `#[tokio::main]` is a macro that sets up the asynchronous runtime.
// #[tokio::main]
// async fn main() -> Result<()> {
//     // Initialize the logger. It will read the `RUST_LOG` env var.
//     env_logger::init();
//     info!("Moodle AI Processor starting up...");

//     // Create our processor instance. This will fail if OPENAI_API_KEY is not set.
//     let processor = AIProcessor::new()?;
//     // Send the "I'm ready" signal back to PHP.
//     processor.signal_ready().await?;

//     // Get handles to the standard I/O pipes.
//     let stdin = tokio_io::stdin();
//     let mut reader = BufReader::new(stdin);
//     let mut stdout = tokio_io::stdout();
//     let mut line_buffer = String::new();

//     // The main event loop.
//     loop {
//         // `tokio::select!` allows us to wait on multiple asynchronous events at once.
//         tokio::select! {
//             // Event 1: A new line of data arrives on stdin from PHP.
//             result = reader.read_line(&mut line_buffer) => {
//                 match result {
//                     Ok(0) => { // 0 bytes read means the pipe was closed (EOF).
//                         info!("Stdin closed. Shutting down.");
//                         break; // Exit the loop.
//                     },
//                     Ok(_) => { // Successfully read some data.
//                         let trimmed_line = line_buffer.trim();
//                         if !trimmed_line.is_empty() {
//                             // Try to parse the line as an AIRequest.
//                             let response = match serde_json::from_str::<AIRequest>(trimmed_line) {
//                                 Ok(request) => processor.process_request(request).await,
//                                 Err(e) => { // If parsing fails, create an error response.
//                                     warn!("Failed to parse request: {} - Line: '{}'", e, trimmed_line);
//                                     AIResponse {
//                                         id: "parse_error".to_string(),
//                                         error: Some(format!("Invalid JSON request: {}", e)),
//                                         ..Default::default()
//                                     }
//                                 }
//                             };

//                             // Send the JSON response back to PHP via stdout.
//                             stdout.write_all(serde_json::to_string(&response)?.as_bytes()).await?;
//                             stdout.write_all(b"\n").await?;
//                             stdout.flush().await?;
//                         }
//                         line_buffer.clear(); // Clear the buffer for the next line.
//                     },
//                     Err(e) => { // An error occurred while reading the pipe.
//                         error!("Error reading from stdin: {}. Shutting down.", e);
//                         break; // Exit the loop.
//                     }
//                 }
//             },
//             // Event 2: The user presses Ctrl+C.
//             _ = tokio::signal::ctrl_c() => {
//                 info!("Received shutdown signal. Exiting gracefully.");
//                 break; // Exit the loop.
//             }
//         }
//     }

//     info!("Moodle AI Processor has shut down.");
//     Ok(())
// }

// // --- Unit and Integration Tests ---

// // The `#[cfg(test)]` attribute tells Rust to only compile this module
// // when running `cargo test`, not when building for release.
// #[cfg(test)]
// mod tests {
//     use super::*; // Import everything from the parent module.

//     // A helper function to create a mock AIRequest for tests.
//     fn create_mock_request(id: &str, message: &str) -> AIRequest {
//         AIRequest {
//             id: id.to_string(),
//             user_id: 2,
//             message: message.to_string(),
//             context: serde_json::json!({ "course_id": 101 }),
//             conversation_history: vec![],
//             large_input_text: None,
//         }
//     }

//     // Test 1: Test JSON deserialization.
//     // This checks if we can correctly parse the JSON that PHP will send.
//     #[test]
//     fn test_deserialize_airequest() {
//         let json_from_php = r#"
//         {
//             "id": "test_req_123",
//             "user_id": 5,
//             "message": "Hello, world!",
//             "context": {
//                 "course_id": 12,
//                 "activity": "forum"
//             },
//             "conversation_history": ["Hi", "How can I help?"],
//             "large_input_text": "This is a long document..."
//         }
//         "#;
//         let request: Result<AIRequest, _> = serde_json::from_str(json_from_php);
//         assert!(request.is_ok()); // Assert that parsing succeeded.
//         let req = request.unwrap();
//         assert_eq!(req.id, "test_req_123");
//         assert_eq!(req.conversation_history.len(), 2);
//     }

//     // Test 2: Test JSON serialization.
//     // This checks if the JSON we send back to PHP has the correct format.
//     #[test]
//     fn test_serialize_airesponse() {
//         let response = AIResponse {
//             id: "test_resp_456".to_string(),
//             ai_response_text: "This is a test response.".to_string(),
//             confidence: 0.99,
//             processing_time_ms: 123,
//             model_version: "test-model-v1".to_string(),
//             timestamp: 1234567890.0,
//             error: Some("This is a test error.".to_string()),
//         };
//         let json_to_php = serde_json::to_string(&response).unwrap();
//         // Check if the resulting string contains the expected keys and values.
//         assert!(json_to_php.contains("\"id\":\"test_resp_456\""));
//         assert!(json_to_php.contains("\"error\":\"This is a test error.\""));
//     }

//     // Test 3: An integration test for the AI processor logic.
//     // This is an async test that requires the Tokio runtime.
//     // It mocks the AI call to avoid making a real network request during tests.
//     // IMPORTANT: This test requires a real (but can be fake) OPENAI_API_KEY to be set
//     // in the environment, because `AIProcessor::new()` will read it.
//     // Run tests with: `OPENAI_API_KEY="test" cargo test`
//     #[tokio::test]
//     async fn test_process_request_logic() {
//         // This test will only run if the OPENAI_API_KEY is set.
//         if std::env::var("OPENAI_API_KEY").is_err() {
//             println!("Skipping test_process_request_logic: OPENAI_API_KEY not set.");
//             return;
//         }

//         // We can create a mock processor for more advanced tests, but for now
//         // we'll just check the structure of the response.
//         let processor = AIProcessor::new().unwrap();
//         let request = create_mock_request("logic_test_1", "What is Rust?");

//         // We can't easily mock the OpenAI call here without more complex libraries,
//         // so we'll just call the real `process_request` and check the shape of the output.
//         // In a real production codebase, you'd use a mocking library like `mockall`.
//         let response = processor.process_request(request).await;

//         assert_eq!(response.id, "logic_test_1");
//         assert_eq!(response.model_version, processor.model_version);
//         // We can't know the exact response text, but we can check if it's empty or not,
//         // and if an error was unexpectedly returned.
//         assert!(response.error.is_none());
//         assert!(!response.ai_response_text.is_empty());
//     }
// }

// --- Crate Imports ---
use serde::{Deserialize, Serialize};
use std::time::{Instant, SystemTime, UNIX_EPOCH};
use anyhow::Result;
use async_openai::{Client, config::OpenAIConfig};
use async_openai::types::{
    CreateChatCompletionRequestArgs,
    ChatCompletionRequestSystemMessageArgs,
    ChatCompletionRequestUserMessageArgs,
    ChatCompletionRequestMessage,
};
// Use Tokio's async I/O exclusively
use tokio::io::{self as tokio_io, AsyncBufReadExt, AsyncWriteExt, BufReader};
use log::{info, error, warn};

// --- Data Structures (No changes here) ---
#[derive(Deserialize, Debug)]
struct AIRequest {
    id: String,
    user_id: u64,
    message: String,
    context: serde_json::Value,
    conversation_history: Vec<String>,
    large_input_text: Option<String>,
}

#[derive(Serialize, Debug, Default)]
struct AIResponse {
    id: String,
    ai_response_text: String,
    confidence: f32,
    processing_time_ms: u128,
    model_version: String,
    timestamp: f64,
    error: Option<String>,
}

#[derive(Serialize)]
struct StatusMessage {
    status: String,
    message: String,
}

// --- AI Processor Core ---
struct AIProcessor {
    client: Client<OpenAIConfig>,
    model_version: String,
}

impl AIProcessor {
    // UPDATED: `new` now accepts the API key as an argument.
    fn new(api_key: String) -> Result<Self> {
        if api_key.is_empty() {
            return Err(anyhow::anyhow!("FATAL: API key provided was empty"));
        }
        
        let config = OpenAIConfig::new().with_api_key(api_key);
        let client = Client::with_config(config);
        
        Ok(Self {
            client,
            model_version: "gpt-4o-via-rust-v2.2-final".to_string(),
        })
    }

    async fn signal_ready(&self) -> Result<()> {
        let status = StatusMessage {
            status: "ready".to_string(),
            message: format!("AI processor initialized. Model family: {}", self.model_version),
        };
        let mut stdout = tokio_io::stdout();
        stdout.write_all(serde_json::to_string(&status)?.as_bytes()).await?;
        stdout.write_all(b"\n").await?;
        stdout.flush().await?;
        info!("Sent 'ready' signal to PHP.");
        Ok(())
    }

    async fn process_request(&self, request: AIRequest) -> AIResponse {
        let start_time = Instant::now();
        info!("Processing request ID: {} for user_id {}", request.id, request.user_id);
        let response = self.generate_ai_response(&request).await;
        let processing_time = start_time.elapsed().as_millis();
        let timestamp = SystemTime::now().duration_since(UNIX_EPOCH).unwrap_or_default().as_secs_f64();

        match response {
            Ok(ai_response_text) => AIResponse {
                id: request.id,
                ai_response_text,
                confidence: 0.98,
                processing_time_ms: processing_time,
                model_version: self.model_version.clone(),
                timestamp,
                error: None,
            },
            Err(e) => {
                let error_message = format!("Error processing request: {}", e);
                error!("{}", error_message);
                AIResponse {
                    id: request.id,
                    error: Some(error_message),
                    ..Default::default()
                }
            }
        }
    }

    async fn generate_ai_response(&self, request: &AIRequest) -> Result<String> {
        let mut messages: Vec<ChatCompletionRequestMessage> = vec![];

        messages.push(
            ChatCompletionRequestSystemMessageArgs::default()
                .content("You are a helpful and creative assistant integrated into the Moodle Learning Management System.")
                .build()?
                .into(),
        );

        for (i, msg) in request.conversation_history.iter().enumerate() {
            // CORRECTED: Dereference `msg` to convert `&String` to `&str`.
            let role_args = if i % 2 == 0 {
                ChatCompletionRequestUserMessageArgs::default().content(msg.as_str()).build()?.into()
            } else {
                ChatCompletionRequestSystemMessageArgs::default().content(msg.as_str()).build()?.into()
            };
            messages.push(role_args);
        }

        // CORRECTED: Dereference `request.message` to convert `&String` to `&str`.
        messages.push(
            ChatCompletionRequestUserMessageArgs::default()
                .content(request.message.as_str())
                .build()?
                .into(),
        );

        let req = CreateChatCompletionRequestArgs::default()
            .model("gpt-4o")
            .messages(messages)
            .build()?;

        let response = self.client.chat().create(req).await?;

        if let Some(choice) = response.choices.first() {
            if let Some(content) = &choice.message.content {
                return Ok(content.clone());
            }
        }
        Err(anyhow::anyhow!("No content found in OpenAI API response choice"))
    }
}

// --- Main Application Loop ---
#[tokio::main]
async fn main() -> Result<()> {
    env_logger::init();
    info!("Moodle AI Processor starting up...");

    // UPDATED: Read API key from the first command-line argument.
    // This is how PHP will pass the key to us from its secure storage.
    let api_key = std::env::args().nth(1)
        .ok_or_else(|| anyhow::anyhow!("FATAL: No API key provided as a command-line argument."))?;

    let processor = AIProcessor::new(api_key)?;
    processor.signal_ready().await?;

    let stdin = tokio_io::stdin();
    let mut reader = BufReader::new(stdin);
    let mut stdout = tokio_io::stdout();
    let mut line_buffer = String::new();

    loop {
        tokio::select! {
            result = reader.read_line(&mut line_buffer) => {
                match result {
                    Ok(0) => {
                        info!("Stdin closed. Shutting down.");
                        break;
                    },
                    Ok(_) => {
                        let trimmed_line = line_buffer.trim();
                        if !trimmed_line.is_empty() {
                            let response = match serde_json::from_str::<AIRequest>(trimmed_line) {
                                Ok(request) => processor.process_request(request).await,
                                Err(e) => {
                                    warn!("Failed to parse request: {} - Line: '{}'", e, trimmed_line);
                                    AIResponse {
                                        id: "parse_error".to_string(),
                                        error: Some(format!("Invalid JSON request: {}", e)),
                                        ..Default::default()
                                    }
                                }
                            };
                            stdout.write_all(serde_json::to_string(&response)?.as_bytes()).await?;
                            stdout.write_all(b"\n").await?;
                            stdout.flush().await?;
                        }
                        line_buffer.clear();
                    },
                    Err(e) => {
                        error!("Error reading from stdin: {}. Shutting down.", e);
                        break;
                    }
                }
            },
            _ = tokio::signal::ctrl_c() => {
                info!("Received shutdown signal. Exiting gracefully.");
                break;
            }
        }
    }

    info!("Moodle AI Processor has shut down.");
    Ok(())
}

// --- Unit and Integration Tests ---
#[cfg(test)]
mod tests {
    use super::*; // Import everything from the parent module.

    // A helper function to create a mock AIRequest for tests.
    fn create_mock_request(id: &str, message: &str) -> AIRequest {
        AIRequest {
            id: id.to_string(),
            user_id: 2,
            message: message.to_string(),
            context: serde_json::json!({ "course_id": 101 }),
            conversation_history: vec![],
            large_input_text: None,
        }
    }

    // Test 1: Test JSON deserialization.
    #[test]
    fn test_deserialize_airequest() {
        let json_from_php = r#"
        {
            "id": "test_req_123",
            "user_id": 5,
            "message": "Hello, world!",
            "context": {
                "course_id": 12,
                "activity": "forum"
            },
            "conversation_history": ["Hi", "How can I help?"],
            "large_input_text": "This is a long document..."
        }
        "#;
        let request: Result<AIRequest, _> = serde_json::from_str(json_from_php);
        assert!(request.is_ok());
        let req = request.unwrap();
        assert_eq!(req.id, "test_req_123");
        assert_eq!(req.large_input_text.is_some(), true);
    }

    // Test 2: Test JSON serialization.
    #[test]
    fn test_serialize_airesponse() {
        let response = AIResponse {
            id: "test_resp_456".to_string(),
            ai_response_text: "This is a test response.".to_string(),
            confidence: 0.99,
            processing_time_ms: 123,
            model_version: "test-model-v1".to_string(),
            timestamp: 1234567890.0,
            error: Some("This is a test error.".to_string()),
        };
        let json_to_php = serde_json::to_string(&response).unwrap();
        assert!(json_to_php.contains("\"id\":\"test_resp_456\""));
        assert!(json_to_php.contains("\"error\":\"This is a test error.\""));
    }
    
    // Test 3: Test the processor's error handling with a fake API key.
    #[tokio::test]
    async fn test_process_request_error_handling() {
        // We create the processor with a clearly invalid API key.
        let processor = AIProcessor::new("FAKE_KEY_THIS_WILL_FAIL".to_string()).unwrap();
        let request = create_mock_request("error_test_1", "This request will fail");

        let response = processor.process_request(request).await;

        // Assert that the response correctly indicates an error occurred.
        assert_eq!(response.id, "error_test_1");
        assert!(response.error.is_some(), "Expected an error message, but got none.");
        assert!(response.ai_response_text.is_empty(), "Expected empty response text on error.");
        
        // A more robust check for the error content.
        let error_msg = response.error.unwrap().to_lowercase(); // Convert to lowercase for case-insensitive search
        
        // Check for common substrings that indicate an authentication/API error.
        let is_api_error = error_msg.contains("api key") || 
                           error_msg.contains("authentication") ||
                           error_msg.contains("openai api error");

        assert!(
            is_api_error, 
            "The error message did not seem to be an API key/authentication error. Actual error: '{}'", 
            error_msg
        );
    }
}