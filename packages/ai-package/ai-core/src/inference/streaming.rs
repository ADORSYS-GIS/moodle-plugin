//! # Streaming Module
//!
//! Provides real-time token streaming for AI inference operations.
//! Enables progressive text generation with immediate output flushing.

use crate::errors::{AiCoreError, Result};
use serde::{Deserialize, Serialize};
use std::sync::mpsc::{self, Receiver, Sender};
use std::thread;
use std::time::{Duration, Instant};
use tracing::{debug, info, warn};

/// Represents a single token in the stream
#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct Token {
    pub text: String,
    pub confidence: Option<f32>,
    pub timestamp: chrono::DateTime<chrono::Utc>,
}

/// Stream event types for real-time communication
#[derive(Debug, Clone, Serialize, Deserialize)]
#[serde(tag = "event", content = "data")]
pub enum StreamEvent {
    /// New token generated
    Token(Token),
    /// Inference completed
    Complete {
        total_tokens: usize,
        inference_time_ms: u64,
    },
    /// Error occurred during streaming
    Error(String),
    /// Processing started
    Started,
}

/// Configuration for streaming behavior
#[derive(Debug, Clone)]
pub struct StreamConfig {
    pub buffer_size: usize,
    pub flush_interval_ms: u64,
    pub max_tokens: Option<usize>,
    pub temperature: f32,
}

impl Default for StreamConfig {
    fn default() -> Self {
        Self {
            buffer_size: 1024,
            flush_interval_ms: 50,
            max_tokens: Some(512),
            temperature: 0.7,
        }
    }
}

/// Token stream for progressive text generation
pub struct TokenStream {
    receiver: Receiver<StreamEvent>,
    _handle: thread::JoinHandle<()>,
}

impl TokenStream {
    /// Creates a new token stream for the given input
    pub fn new(input: String, config: StreamConfig) -> Result<Self> {
        let (sender, receiver) = mpsc::channel();
        
        let handle = thread::spawn(move || {
            if let Err(e) = Self::generate_stream(input, config, sender.clone()) {
                let _ = sender.send(StreamEvent::Error(e.to_string()));
            }
        });
        
        Ok(Self {
            receiver,
            _handle: handle,
        })
    }
    
    /// Generates the token stream in a background thread
    fn generate_stream(
        input: String,
        config: StreamConfig,
        sender: Sender<StreamEvent>,
    ) -> Result<()> {
        debug!("Starting token stream generation for input: {} chars", input.len());
        
        let start_time = Instant::now();
        let _ = sender.send(StreamEvent::Started);
        
        // Simulate token generation (replace with actual AI inference)
        let _words: Vec<&str> = input.split_whitespace().collect();
        let response_words = vec![
            "Based", "on", "your", "question", "about", "GIS", "and", "spatial", "analysis,",
            "I", "can", "help", "you", "understand", "the", "key", "concepts", "and", "provide",
            "detailed", "explanations", "with", "practical", "examples."
        ];
        
        let mut token_count = 0;
        let max_tokens = config.max_tokens.unwrap_or(response_words.len());
        
        for (i, word) in response_words.iter().enumerate().take(max_tokens) {
            if token_count >= max_tokens {
                break;
            }
            
            let token = Token {
                text: if i == 0 { word.to_string() } else { format!(" {}", word) },
                confidence: Some(0.95 - (i as f32 * 0.01)), // Decreasing confidence
                timestamp: chrono::Utc::now(),
            };
            
            if sender.send(StreamEvent::Token(token)).is_err() {
                warn!("Receiver dropped, stopping stream generation");
                break;
            }
            
            token_count += 1;
            
            // Simulate processing delay
            thread::sleep(Duration::from_millis(config.flush_interval_ms));
        }
        
        let total_time = start_time.elapsed();
        let _ = sender.send(StreamEvent::Complete {
            total_tokens: token_count,
            inference_time_ms: total_time.as_millis() as u64,
        });
        
        info!("Stream generation completed: {} tokens in {}ms", token_count, total_time.as_millis());
        Ok(())
    }
}

impl Iterator for TokenStream {
    type Item = StreamEvent;
    
    fn next(&mut self) -> Option<Self::Item> {
        self.receiver.recv().ok()
    }
}

/// High-level streaming engine for AI inference
pub struct StreamingEngine {
    config: StreamConfig,
}

impl StreamingEngine {
    /// Creates a new streaming engine with default configuration
    pub fn new() -> Self {
        Self {
            config: StreamConfig::default(),
        }
    }
    
    /// Creates a streaming engine with custom configuration
    pub fn with_config(config: StreamConfig) -> Self {
        Self { config }
    }
    
    /// Starts a streaming inference session
    pub fn stream_inference(&self, input: &str) -> Result<TokenStream> {
        if input.trim().is_empty() {
            return Err(AiCoreError::InvalidInput {
                message: "Input cannot be empty".to_string(),
            });
        }
        
        info!("Starting streaming inference for {} character input", input.len());
        TokenStream::new(input.to_string(), self.config.clone())
    }
    
    /// Updates the streaming configuration
    pub fn update_config(&mut self, config: StreamConfig) {
        self.config = config;
        debug!("Updated streaming configuration");
    }
    
    /// Gets the current configuration
    pub fn config(&self) -> &StreamConfig {
        &self.config
    }
}

impl Default for StreamingEngine {
    fn default() -> Self {
        Self::new()
    }
}

#[cfg(test)]
mod tests {
    use super::*;
    use std::time::Duration;
    
    #[test]
    fn test_streaming_engine_creation() {
        let engine = StreamingEngine::new();
        assert_eq!(engine.config.buffer_size, 1024);
        assert_eq!(engine.config.flush_interval_ms, 50);
    }
    
    #[test]
    fn test_custom_config() {
        let config = StreamConfig {
            buffer_size: 2048,
            flush_interval_ms: 100,
            max_tokens: Some(256),
            temperature: 0.8,
        };
        
        let engine = StreamingEngine::with_config(config.clone());
        assert_eq!(engine.config.buffer_size, 2048);
        assert_eq!(engine.config.flush_interval_ms, 100);
    }
    
    #[test]
    fn test_empty_input_error() {
        let engine = StreamingEngine::new();
        let result = engine.stream_inference("");
        assert!(result.is_err());
    }
    
    #[test]
    fn test_token_stream_generation() {
        let engine = StreamingEngine::new();
        let mut stream = engine.stream_inference("Test input for GIS analysis").unwrap();
        
        let mut events = Vec::new();
        let mut token_count = 0;
        
        // Collect events with timeout
        let start = Instant::now();
        while start.elapsed() < Duration::from_secs(5) {
            if let Some(event) = stream.next() {
                match &event {
                    StreamEvent::Token(_) => token_count += 1,
                    StreamEvent::Complete { .. } => {
                        events.push(event);
                        break;
                    }
                    _ => {}
                }
                events.push(event);
            }
        }
        
        assert!(token_count > 0, "Should generate at least one token");
        assert!(events.iter().any(|e| matches!(e, StreamEvent::Started)), "Should have started event");
        assert!(events.iter().any(|e| matches!(e, StreamEvent::Complete { .. })), "Should have completion event");
    }
    
    #[test]
    fn test_token_structure() {
        let token = Token {
            text: "test".to_string(),
            confidence: Some(0.95),
            timestamp: chrono::Utc::now(),
        };
        
        assert_eq!(token.text, "test");
        assert_eq!(token.confidence, Some(0.95));
    }
}