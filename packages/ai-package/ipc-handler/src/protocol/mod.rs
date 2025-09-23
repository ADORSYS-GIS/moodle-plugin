//! # Protocol Module
//!
//! Handles communication protocols between PHP and Rust processes.
//! Supports JSON-based messaging with structured commands and responses.

pub mod json_protocol;

// Re-export key types for easy access
pub use json_protocol::{Request, Response, ResponseStatus, ErrorInfo, Command, CommandResult, ProtocolError};
