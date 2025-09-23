//! # Integration Module
//!
//! Demonstrates integration between IPC handler, AI core, and utilities.
//! This module shows how to orchestrate AI operations through IPC communication.

use crate::{IpcHandler, Request, Result as IpcResult};
use ai_core::{AiCore, AiCoreError};
use ai_utils::config::Config;
use tracing::{error, info};

/// AI service that integrates IPC communication with AI core functionality
pub struct AiService {
    ipc_handler: IpcHandler,
    ai_core: AiCore,
    config: Config,
}

impl AiService {
    /// Creates a new AI service with configuration
    pub fn new(config: Config) -> Result<Self, Box<dyn std::error::Error>> {
        let ipc_handler = IpcHandler::new();
        let ai_core = AiCore::new(&config)?;
        
        Ok(Self {
            ipc_handler,
            ai_core,
            config,
        })
    }

    /// Main service loop - processes requests from PHP
    pub fn run(&mut self) -> IpcResult<()> {
        info!("Starting AI service");
        
        // Send ready signal to indicate service is initialized
        self.ipc_handler.send_ready()?;
        
        loop {
            match self.process_request() {
                Ok(should_continue) => {
                    if !should_continue {
                        info!("Received shutdown request, exiting");
                        break;
                    }
                }
                Err(e) => {
                    error!("Error processing request: {}", e);
                    // Continue processing other requests even after errors
                }
            }
        }
        
        info!("AI service stopped");
        Ok(())
    }

    /// Processes a single request and returns whether to continue
    fn process_request(&mut self) -> IpcResult<bool> {
        let request = self.ipc_handler.read_request()?;
        
        match request.method.as_str() {
            "shutdown" => {
                self.ipc_handler.send_success(request.id, None)?;
                Ok(false) // Signal to stop the loop
            }
            "ping" => {
                self.handle_ping(&request)
            }
            "load_model" => {
                self.handle_load_model(&request)
            }
            "inference" => {
                self.handle_inference(&request)
            }
            "get_stats" => {
                self.handle_get_stats(&request)
            }
            _ => {
                let error_msg = format!("Unknown method: {}", request.method);
                self.ipc_handler.send_error(request.id, -32601, error_msg)?;
                Ok(true)
            }
        }
    }

    /// Handles ping requests for health checks
    fn handle_ping(&mut self, request: &Request) -> IpcResult<bool> {
        let response_data = serde_json::json!({
            "message": "pong",
            "timestamp": chrono::Utc::now().to_rfc3339(),
            "stats": self.ipc_handler.stats()
        });
        
        self.ipc_handler.send_success(request.id.clone(), Some(response_data))?;
        Ok(true)
    }

    /// Handles model loading requests
    fn handle_load_model(&mut self, request: &Request) -> IpcResult<bool> {
        let params = request.params.as_ref()
            .ok_or_else(|| crate::IpcError::invalid_request("Missing parameters for load_model"))?;
        
        let model_path = params.get("model_path")
            .and_then(|v| v.as_str())
            .ok_or_else(|| crate::IpcError::invalid_request("Missing or invalid model_path parameter"))?;

        // Send processing signal for long-running operation
        self.ipc_handler.send_processing(request.id.clone())?;

        match self.ai_core.load_model(Some(model_path)) {
            Ok(model_info) => {
                let response_data = serde_json::json!({
                    "model_loaded": true,
                    "model_path": model_path,
                    "model_info": {
                        "format": model_info.format,
                        "size_bytes": model_info.size_bytes,
                        "loaded_at": model_info.loaded_at.to_rfc3339()
                    }
                });
                
                self.ipc_handler.send_success(request.id.clone(), Some(response_data))?;
            }
            Err(AiCoreError::ModelNotFound { path }) => {
                let error_msg = format!("Model not found: {}", path);
                self.ipc_handler.send_error(request.id.clone(), -32000, error_msg)?;
            }
            Err(AiCoreError::UnsupportedFormat { format, .. }) => {
                let error_msg = format!("Unsupported model format: {}", format);
                self.ipc_handler.send_error(request.id.clone(), -32001, error_msg)?;
            }
            Err(e) => {
                let error_msg = format!("Failed to load model: {}", e);
                self.ipc_handler.send_error(request.id.clone(), -32002, error_msg)?;
            }
        }

        Ok(true)
    }

    /// Handles inference requests
    fn handle_inference(&mut self, request: &Request) -> IpcResult<bool> {
        let params = request.params.as_ref()
            .ok_or_else(|| crate::IpcError::invalid_request("Missing parameters for inference"))?;
        
        let input_text = params.get("input")
            .and_then(|v| v.as_str())
            .ok_or_else(|| crate::IpcError::invalid_request("Missing or invalid input parameter"))?;

        let max_tokens = params.get("max_tokens")
            .and_then(|v| v.as_u64())
            .unwrap_or(100) as usize;

        // Send processing signal for inference operation
        self.ipc_handler.send_processing(request.id.clone())?;

        match self.ai_core.inference(input_text, max_tokens) {
            Ok(result) => {
                let response_data = serde_json::json!({
                    "output": result.text,
                    "tokens_generated": result.tokens_generated,
                    "inference_time_ms": result.inference_time_ms,
                    "model_used": result.model_used
                });
                
                self.ipc_handler.send_success(request.id.clone(), Some(response_data))?;
            }
            Err(AiCoreError::ModelNotLoaded) => {
                let error_msg = "No model loaded. Please load a model first.";
                self.ipc_handler.send_error(request.id.clone(), -32003, error_msg.to_string())?;
            }
            Err(e) => {
                let error_msg = format!("Inference failed: {}", e);
                self.ipc_handler.send_error(request.id.clone(), -32004, error_msg)?;
            }
        }

        Ok(true)
    }

    /// Handles statistics requests
    fn handle_get_stats(&mut self, request: &Request) -> IpcResult<bool> {
        let ipc_stats = self.ipc_handler.stats();
        let ai_stats = self.ai_core.get_stats();
        
        let response_data = serde_json::json!({
            "ipc": {
                "requests_processed": ipc_stats.requests_processed,
                "responses_sent": ipc_stats.responses_sent
            },
            "ai_core": {
                "models_loaded": ai_stats.models_loaded,
                "inferences_performed": ai_stats.inferences_performed,
                "cache_hits": ai_stats.cache_hits,
                "cache_misses": ai_stats.cache_misses
            },
            "uptime_seconds": ai_stats.uptime.as_secs()
        });
        
        self.ipc_handler.send_success(request.id.clone(), Some(response_data))?;
        Ok(true)
    }
}

/// Example usage and integration patterns
#[cfg(feature = "examples")]
pub mod examples {
    use super::*;
    use ai_utils::config::Config;

    /// Example: Basic AI service setup
    pub fn basic_service_example() -> Result<(), Box<dyn std::error::Error>> {
        // Initialize configuration
        let mut config = Config::new();
        config.set("ai.model_path", "/path/to/models")?;
        config.set("ai.backend", "onnx")?;
        config.set("logging.level", "info")?;

        // Create and run the service
        let mut service = AiService::new(config)?;
        service.run()?;

        Ok(())
    }

    /// Example: Custom configuration with environment variables
    pub fn env_config_example() -> Result<(), Box<dyn std::error::Error>> {
        let config = Config::from_env_with_prefix("MOODLE_AI")?;
        let mut service = AiService::new(config)?;
        service.run()?;

        Ok(())
    }

    /// Example: Configuration from file
    pub fn file_config_example() -> Result<(), Box<dyn std::error::Error>> {
        let config = Config::from_file("config/ai_service.yaml")?;
        let mut service = AiService::new(config)?;
        service.run()?;

        Ok(())
    }
}

#[cfg(test)]
mod tests {
    use super::*;
    use crate::{MockReader, MockWriter};
    use ai_utils::config::Config;

    #[test]
    fn test_ping_handling() {
        let mut config = Config::new();
        config.set("ai.model_path", "/tmp/test").unwrap();
        
        let mock_reader = MockReader::new(vec![
            r#"{"jsonrpc":"2.0","method":"ping","id":"test-1"}"#.to_string()
        ]);
        let mock_writer = MockWriter::new();
        
        let ipc_handler = IpcHandler::with_io(mock_reader, mock_writer);
        let ai_core = AiCore::new(&config).unwrap();
        
        let mut service = AiService {
            ipc_handler,
            ai_core,
            config,
        };
        
        // Process the ping request
        let result = service.process_request();
        assert!(result.is_ok());
        
        // Check that a response was written
        let stats = service.ipc_handler.stats();
        assert_eq!(stats.responses_sent, 1);
    }

    #[test]
    fn test_unknown_method_handling() {
        let mut config = Config::new();
        config.set("ai.model_path", "/tmp/test").unwrap();
        
        let mock_reader = MockReader::new(vec![
            r#"{"jsonrpc":"2.0","method":"unknown_method","id":"test-1"}"#.to_string()
        ]);
        let mock_writer = MockWriter::new();
        
        let ipc_handler = IpcHandler::with_io(mock_reader, mock_writer);
        let ai_core = AiCore::new(&config).unwrap();
        
        let mut service = AiService {
            ipc_handler,
            ai_core,
            config,
        };
        
        // Process the unknown method request
        let result = service.process_request();
        assert!(result.is_ok());
        
        // Should continue processing (return true) and send an error response
        let stats = service.ipc_handler.stats();
        assert_eq!(stats.responses_sent, 1);
    }
}
