//! utils/src/errors.rs
//!
//! Centralized error handling for the utils crate.
//! Uses `thiserror` for ergonomic error definitions and `anyhow` for flexible error wrapping with context/backtraces.
//! Designed to be extended as the project grows.
//!
//! # Conventions
//! - All modules should use `crate::errors::Result<T>` for fallible operations.
//! - Wrap external errors with `?` to automatically convert into `Error` variants.
//! - For unexpected conditions, use `Error::Internal` with a descriptive message.
//! - Use `.context("details")` from anyhow for chaining.

use std::io;
use thiserror::Error;
use anyhow;

/// A specialized `Result` type for the utils crate, backed by anyhow for backtraces.
pub type Result<T> = anyhow::Result<T>;

/// Unified error type for utils crate.
#[derive(Debug, Error)]
pub enum Error {
    /// Configuration-related errors.
    #[error("configuration error: {0}")]
    Config(String),

    /// Logging initialization errors.
    #[error("logging error: {0}")]
    Logging(String),

    /// Metrics-related errors.
    #[error("metrics error: {0}")]
    Metrics(String),

    /// Input/output errors.
    #[error("I/O error: {0}")]
    Io(#[from] io::Error),

    /// Environment variable errors.
    #[error("environment variable error: {0}")]
    EnvVar(#[from] std::env::VarError),

    /// Errors from parsing integers, floats, etc.
    #[error("parse error: {0}")]
    Parse(#[from] std::num::ParseIntError),

    /// Errors from JSON serialization/deserialization.
    #[error("JSON serialization error: {0}")]
    Json(#[from] serde_json::Error),

    /// Errors from YAML serialization/deserialization.
    #[error("YAML serialization error: {0}")]
    Yaml(#[from] serde_yaml::Error),

    /// Catch-all for other internal errors.
    #[error("internal error: {0}")]
    Internal(String),
}

impl Error {
    /// Create a configuration error from a message.
    pub fn config<M: Into<String>>(msg: M) -> Self {
        Error::Config(msg.into())
    }

    /// Create a logging error from a message.
    pub fn logging<M: Into<String>>(msg: M) -> Self {
        Error::Logging(msg.into())
    }

    /// Create a metrics error from a message.
    pub fn metrics<M: Into<String>>(msg: M) -> Self {
        Error::Metrics(msg.into())
    }

    /// Create an internal error from a message.
    pub fn internal<M: Into<String>>(msg: M) -> Self {
        Error::Internal(msg.into())
    }
}