//! JSON Protocol Definitions
//!
//! Defines the request/response protocol for PHP-Rust IPC communication.
//! Follows JSON-RPC 2.0 inspired structure with proper validation.
//! 
//! # Command Schema
//! 
//! ## Supported Commands:
//! - `init`: Initialize AI service with model configuration
//! - `inference`: Perform text generation/Q&A with optional streaming
//! - `load_model`: Load a specific AI model
//! - `cache_clear`: Clear inference cache
//! - `get_stats`: Get service statistics
//! - `ping`: Health check
//! - `shutdown`: Graceful shutdown

use serde::{Deserialize, Serialize};
use std::collections::HashMap;
use crate::errors::{IpcError, Result};

/// Request message from PHP to Rust
#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct Request {
    /// Unique request identifier for correlation
    pub id: Option<String>,
    /// Method/operation to execute
    pub method: String,
    /// Parameters for the operation
    pub params: Option<serde_json::Value>,
    /// Request timestamp (ISO 8601)
    pub timestamp: Option<String>,
    /// Optional metadata
    pub metadata: Option<HashMap<String, String>>,
}

/// Response message from Rust to PHP
#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct Response {
    /// Correlation ID matching the request
    pub id: Option<String>,
    /// Response status
    pub status: ResponseStatus,
    /// Success result data
    pub result: Option<serde_json::Value>,
    /// Error information if status is error
    pub error: Option<ErrorInfo>,
    /// Response timestamp (ISO 8601)
    pub timestamp: Option<String>,
    /// Optional metadata
    pub metadata: Option<HashMap<String, String>>,
}

/// Response status enumeration
#[derive(Debug, Clone, Serialize, Deserialize, PartialEq)]
#[serde(rename_all = "lowercase")]
pub enum ResponseStatus {
    Success,
    Error,
    Ready,
    Processing,
}

/// Detailed error information
#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct ErrorInfo {
    /// Error code for programmatic handling
    pub code: i32,
    /// Human-readable error message
    pub message: String,
    /// Optional additional error data
    pub data: Option<serde_json::Value>,
}

impl Request {
    /// Create a new request
    pub fn new(id: Option<String>, method: String, params: Option<serde_json::Value>) -> Self {
        Self {
            id,
            method,
            params,
            timestamp: Some(chrono::Utc::now().to_rfc3339()),
            metadata: None,
        }
    }

    /// Validate the request structure
    pub fn validate(&self) -> Result<()> {
        if self.method.is_empty() {
            return Err(IpcError::invalid_request("method cannot be empty"));
        }

        // Validate method name format (alphanumeric + underscore)
        if !self.method.chars().all(|c| c.is_alphanumeric() || c == '_') {
            return Err(IpcError::invalid_request("invalid method name format"));
        }

        Ok(())
    }

    /// Get parameter as specific type
    pub fn get_param<T>(&self, key: &str) -> Result<T>
    where
        T: serde::de::DeserializeOwned,
    {
        let params = self.params.as_ref()
            .ok_or_else(|| IpcError::invalid_request("no parameters provided"))?;
        
        let param_value = params.get(key)
            .ok_or_else(|| IpcError::invalid_request(format!("parameter '{}' not found", key)))?;
        
        serde_json::from_value(param_value.clone())
            .map_err(|e| IpcError::invalid_request(format!("invalid parameter '{}': {}", key, e)))
    }
}

impl Response {
    /// Create a success response
    pub fn success(id: Option<String>, result: Option<serde_json::Value>) -> Self {
        Self {
            id,
            status: ResponseStatus::Success,
            result,
            error: None,
            timestamp: Some(chrono::Utc::now().to_rfc3339()),
            metadata: None,
        }
    }

    /// Create an error response
    pub fn error(id: Option<String>, code: i32, message: String) -> Self {
        Self {
            id,
            status: ResponseStatus::Error,
            result: None,
            error: Some(ErrorInfo {
                code,
                message,
                data: None,
            }),
            timestamp: Some(chrono::Utc::now().to_rfc3339()),
            metadata: None,
        }
    }

    /// Create a ready signal response
    pub fn ready() -> Self {
        Self {
            id: None,
            status: ResponseStatus::Ready,
            result: None,
            error: None,
            timestamp: Some(chrono::Utc::now().to_rfc3339()),
            metadata: None,
        }
    }

    /// Create a processing response
    pub fn processing(id: Option<String>) -> Self {
        Self {
            id,
            status: ResponseStatus::Processing,
            result: None,
            error: None,
            timestamp: Some(chrono::Utc::now().to_rfc3339()),
            metadata: None,
        }
    }

    /// Validate the response structure
    pub fn validate(&self) -> Result<()> {
        match self.status {
            ResponseStatus::Success => {
                if self.error.is_some() {
                    return Err(IpcError::invalid_response("success response cannot have error"));
                }
            }
            ResponseStatus::Error => {
                if self.error.is_none() {
                    return Err(IpcError::invalid_response("error response must have error info"));
                }
                if self.result.is_some() {
                    return Err(IpcError::invalid_response("error response cannot have result"));
                }
            }
            ResponseStatus::Ready | ResponseStatus::Processing => {
                // These are valid as-is
            }
        }
        Ok(())
    }
}

/// Command enumeration for type-safe command handling
#[derive(Debug, Clone, Serialize, Deserialize, PartialEq)]
#[serde(rename_all = "snake_case")]
pub enum Command {
    /// Initialize AI service with configuration
    Init {
        model: Option<String>,
        params: Option<HashMap<String, serde_json::Value>>,
    },
    /// Perform inference with optional streaming
    Inference {
        prompt: String,
        max_tokens: Option<usize>,
        temperature: Option<f32>,
        stream: Option<bool>,
    },
    /// Load a specific model
    LoadModel {
        model_path: String,
    },
    /// Clear inference cache
    CacheClear,
    /// Get service statistics
    GetStats,
    /// Health check
    Ping,
    /// Graceful shutdown
    Shutdown,
}

/// Command execution result
#[derive(Debug, Clone, Serialize, Deserialize)]
pub enum CommandResult {
    /// Service initialized successfully
    Initialized {
        model: String,
        status: String,
    },
    /// Inference completed
    InferenceComplete {
        output: String,
        tokens_generated: usize,
        inference_time_ms: u64,
        model_used: String,
    },
    /// Streaming token
    StreamToken {
        token: String,
        confidence: Option<f32>,
    },
    /// Model loaded
    ModelLoaded {
        model_path: String,
        model_info: HashMap<String, serde_json::Value>,
    },
    /// Cache cleared
    CacheCleared {
        entries_removed: usize,
    },
    /// Service statistics
    Stats {
        ipc: HashMap<String, serde_json::Value>,
        ai_core: HashMap<String, serde_json::Value>,
        uptime_seconds: u64,
    },
    /// Ping response
    Pong {
        message: String,
        timestamp: String,
    },
    /// Shutdown confirmation
    ShutdownComplete,
}

/// Protocol error for command parsing
#[derive(Debug, thiserror::Error)]
pub enum ProtocolError {
    #[error("Invalid command format: {0}")]
    InvalidCommand(String),
    #[error("Missing required parameter: {0}")]
    MissingParameter(String),
    #[error("Invalid parameter type: {0}")]
    InvalidParameterType(String),
}

impl ErrorInfo {
    /// Standard error codes
    pub const PARSE_ERROR: i32 = -32700;
    pub const INVALID_REQUEST: i32 = -32600;
    pub const METHOD_NOT_FOUND: i32 = -32601;
    pub const INVALID_PARAMS: i32 = -32602;
    pub const INTERNAL_ERROR: i32 = -32603;
    pub const TIMEOUT_ERROR: i32 = -32001;
    pub const PROCESS_ERROR: i32 = -32002;

    /// Create a new error info
    pub fn new(code: i32, message: String) -> Self {
        Self {
            code,
            message,
            data: None,
        }
    }

    /// Create error info with additional data
    pub fn with_data(code: i32, message: String, data: serde_json::Value) -> Self {
        Self {
            code,
            message,
            data: Some(data),
        }
    }
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn test_request_validation() {
        let valid_request = Request::new(
            Some("test-id".to_string()),
            "test_method".to_string(),
            None,
        );
        assert!(valid_request.validate().is_ok());

        let invalid_request = Request::new(
            Some("test-id".to_string()),
            "".to_string(),
            None,
        );
        assert!(invalid_request.validate().is_err());
    }

    #[test]
    fn test_response_validation() {
        let success_response = Response::success(
            Some("test-id".to_string()),
            Some(serde_json::json!({"result": "test"})),
        );
        assert!(success_response.validate().is_ok());

        let error_response = Response::error(
            Some("test-id".to_string()),
            ErrorInfo::INTERNAL_ERROR,
            "Test error".to_string(),
        );
        assert!(error_response.validate().is_ok());
    }
}