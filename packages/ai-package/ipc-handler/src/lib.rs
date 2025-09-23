//! # IPC Handler
//!
//! Core IPC communication module for PHP-Rust interprocess communication.
//! Provides low-level primitives for reading/writing JSON messages via stdin/stdout.

pub mod errors;
pub mod protocol;
pub mod io;
pub mod integration;

// Re-export key types for convenience
pub use crate::{
    errors::{IpcError, Result},
    protocol::{Request, Response, ResponseStatus},
    io::{InputReader, StdinReader, OutputWriter, StdoutWriter},
};

use tracing::{debug, info, warn};

/// Core IPC handler for PHP-Rust communication
/// 
/// This is a lightweight handler focused on the core IPC functionality.
/// Higher-level orchestration should be handled by the ai-core package.
pub struct IpcHandler<R = StdinReader, W = StdoutWriter> 
where
    R: InputReader,
    W: OutputWriter,
{
    reader: R,
    writer: W,
    request_count: usize,
    response_count: usize,
}

impl IpcHandler<StdinReader, StdoutWriter> {
    /// Creates a new IPC handler with default stdin/stdout
    pub fn new() -> Self {
        info!("Initializing IPC handler");
        Self {
            reader: StdinReader::new(),
            writer: StdoutWriter::new(),
            request_count: 0,
            response_count: 0,
        }
    }
}

impl<R, W> IpcHandler<R, W>
where
    R: InputReader,
    W: OutputWriter,
{
    /// Creates a new IPC handler with custom reader and writer (for testing)
    pub fn with_io(reader: R, writer: W) -> Self {
        Self {
            reader,
            writer,
            request_count: 0,
            response_count: 0,
        }
    }

    /// Reads and parses a request from input
    pub fn read_request(&mut self) -> Result<Request> {
        debug!("Reading request #{}", self.request_count + 1);
        
        let line = self.reader.read_line()
            .map_err(|e| {
                warn!("Failed to read line: {}", e);
                e
            })?;

        let request: Request = serde_json::from_str(&line)
            .map_err(|e| {
                warn!("Failed to parse JSON: {}", e);
                IpcError::Json(e)
            })?;

        // Validate the request
        request.validate()
            .map_err(|e| {
                warn!("Invalid request: {}", e);
                e
            })?;

        self.request_count += 1;
        debug!("Successfully parsed request: method={}, id={:?}", 
               request.method, request.id);
        
        Ok(request)
    }

    /// Serializes and writes a response to output
    pub fn write_response(&mut self, response: &Response) -> Result<()> {
        debug!("Writing response #{}", self.response_count + 1);
        
        // Validate the response before sending
        response.validate()
            .map_err(|e| {
                warn!("Invalid response: {}", e);
                e
            })?;

        let json = serde_json::to_string(response)
            .map_err(|e| {
                warn!("Failed to serialize response: {}", e);
                IpcError::Json(e)
            })?;

        self.writer.write_line(&json)
            .map_err(|e| {
                warn!("Failed to write response: {}", e);
                e
            })?;

        self.response_count += 1;
        debug!("Successfully wrote response: status={:?}, id={:?}", 
               response.status, response.id);
        
        Ok(())
    }

    /// Sends a ready signal to indicate the process is initialized
    pub fn send_ready(&mut self) -> Result<()> {
        info!("Sending ready signal");
        let ready_response = Response::ready();
        self.write_response(&ready_response)
    }

    /// Sends a processing signal for long-running operations
    pub fn send_processing(&mut self, request_id: Option<String>) -> Result<()> {
        debug!("Sending processing signal for request: {:?}", request_id);
        let processing_response = Response::processing(request_id);
        self.write_response(&processing_response)
    }

    /// Sends a success response
    pub fn send_success(&mut self, request_id: Option<String>, result: Option<serde_json::Value>) -> Result<()> {
        debug!("Sending success response for request: {:?}", request_id);
        let success_response = Response::success(request_id, result);
        self.write_response(&success_response)
    }

    /// Sends an error response
    pub fn send_error(&mut self, request_id: Option<String>, code: i32, message: String) -> Result<()> {
        warn!("Sending error response for request: {:?}, code: {}, message: {}", 
              request_id, code, message);
        let error_response = Response::error(request_id, code, message);
        self.write_response(&error_response)
    }

    /// Sends an error response from an IpcError
    pub fn send_error_from(&mut self, request_id: Option<String>, error: &IpcError) -> Result<()> {
        use crate::protocol::json_protocol::ErrorInfo;
        let (code, message) = match error {
            IpcError::Json(_) => (ErrorInfo::PARSE_ERROR, error.to_string()),
            IpcError::InvalidRequest { .. } => (ErrorInfo::INVALID_REQUEST, error.to_string()),
            IpcError::InvalidResponse { .. } => (ErrorInfo::INVALID_PARAMS, error.to_string()),
            IpcError::Timeout { .. } => (ErrorInfo::TIMEOUT_ERROR, error.to_string()),
            IpcError::ProcessCommunication { .. } => (ErrorInfo::PROCESS_ERROR, error.to_string()),
            _ => (ErrorInfo::INTERNAL_ERROR, error.to_string()),
        };
        
        self.send_error(request_id, code, message)
    }

    /// Sends a response directly (for advanced use cases)
    pub fn send_response(&mut self, response: &Response) -> Result<()> {
        self.write_response(response)
    }

    /// Get statistics about processed requests/responses
    pub fn stats(&self) -> IpcStats {
        IpcStats {
            requests_processed: self.request_count,
            responses_sent: self.response_count,
        }
    }

    /// Flush the output writer
    pub fn flush(&mut self) -> Result<()> {
        self.writer.flush()
    }
}

impl<R, W> Default for IpcHandler<R, W>
where
    R: InputReader + Default,
    W: OutputWriter + Default,
{
    fn default() -> Self {
        Self::with_io(R::default(), W::default())
    }
}

/// Statistics about IPC handler usage
#[derive(Debug, Clone, PartialEq, serde::Serialize, serde::Deserialize)]
pub struct IpcStats {
    pub requests_processed: usize,
    pub responses_sent: usize,
}
