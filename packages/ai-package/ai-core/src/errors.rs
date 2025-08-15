//! ai-core/src/errors.rs
//!
//! Unified error type for the AI core. Keep this crate-level so all submodules
//! (model loading, inference, streaming, caching) can share one Result<> and
//! compose errors ergonomically.
//!
//! Design goals:
//! - Provide focused variants for common failure classes (I/O, model load,
//!   inference runtime, streaming, cache, config).
//! - Offer `From` conversions for frequently used error sources (io, serde,
//!   channels, utf8, spawn/join) to keep call sites clean.
//! - Optional backtrace capture (enable with the `backtrace` feature).
//! - Convenience helpers for retry logic and context enrichment.

use std::fmt;
use std::result;
use std::{error::Error as StdError};

use thiserror::Error;

#[cfg(feature = "backtrace")]
use std::backtrace::Backtrace;

/// Crate-wide result type.
pub type Result<T> = result::Result<T, AiCoreError>;

/// Core error type for `ai-core`.
#[derive(Error, Debug)]
pub enum AiCoreError {
    // ---------- Domain errors ----------
    /// Errors while loading or initializing a model (e.g., invalid path, bad format).
    #[error("model load error: {message}")]
    ModelLoad {
        message: String,
        #[source]
        source: Option<Box<dyn StdError + Send + Sync>>,
        #[cfg(feature = "backtrace")]
        backtrace: Backtrace,
    },

    /// Errors during inference runtime (shape mismatch, backend failure, OOM, etc.).
    #[error("inference error: {message}")]
    Inference {
        message: String,
        #[source]
        source: Option<Box<dyn StdError + Send + Sync>>,
        #[cfg(feature = "backtrace")]
        backtrace: Backtrace,
    },

    /// Errors related to token streaming / IPC framing.
    #[error("streaming error: {message}")]
    Streaming {
        message: String,
        #[source]
        source: Option<Box<dyn StdError + Send + Sync>>,
        #[cfg(feature = "backtrace")]
        backtrace: Backtrace,
    },

    /// Cache layer failures (serialization, eviction, corruption).
    #[error("cache error: {message}")]
    Cache {
        message: String,
        #[source]
        source: Option<Box<dyn StdError + Send + Sync>>,
        #[cfg(feature = "backtrace")]
        backtrace: Backtrace,
    },

    /// Configuration or argument validation errors.
    #[error("invalid argument: {message}")]
    InvalidArgument {
        message: String,
        #[source]
        source: Option<Box<dyn StdError + Send + Sync>>,
        #[cfg(feature = "backtrace")]
        backtrace: Backtrace,
    },

    /// Not found (model, tensor, file, key).
    #[error("not found: {what}")]
    NotFound {
        what: String,
        #[cfg(feature = "backtrace")]
        backtrace: Backtrace,
    },

    /// Operation not supported by current backend/build.
    #[error("unsupported operation: {what}")]
    Unsupported {
        what: String,
        #[cfg(feature = "backtrace")]
        backtrace: Backtrace,
    },

    /// Timeouts/cancellation (cooperative cancel, deadline exceeded).
    #[error("operation timed out")]
    Timeout {
        #[cfg(feature = "backtrace")]
        backtrace: Backtrace,
    },
    #[error("operation cancelled")]
    Canceled {
        #[cfg(feature = "backtrace")]
        backtrace: Backtrace,
    },

    // ---------- Plumbing / integration ----------
    /// I/O error wrapper.
    #[error("io error: {0}")]
    Io(#[from] std::io::Error),

    /// (De)serialization failures.
    #[error("json error: {0}")]
    Json(#[from] serde_json::Error),
    #[error("yaml error: {0}")]
    Yaml(#[from] serde_yaml::Error),

    /// UTF-8 conversion.
    #[error("utf8 error: {0}")]
    Utf8(#[from] std::string::FromUtf8Error),

    /// Channel send/recv (crossbeam, std sync mpsc compatible via Display messages).
    #[error("channel send error: {0}")]
    ChannelSend(String),
    #[error("channel recv error: {0}")]
    ChannelRecv(String),

    /// Process/async integration.
    #[error("spawn error: {0}")]
    Spawn(String),
    #[error("join error: {0}")]
    Join(String),

    /// Catch-all with message + optional source.
    #[error("{message}")]
    Other {
        message: String,
        #[source]
        source: Option<Box<dyn StdError + Send + Sync>>,
        #[cfg(feature = "backtrace")]
        backtrace: Backtrace,
    },
}

impl AiCoreError {
    /// Add high-level context while preserving the underlying error.
    pub fn with_context<E>(message: impl Into<String>, source: E) -> Self
    where
        E: Into<Box<dyn StdError + Send + Sync>>,
    {
        AiCoreError::Other {
            message: message.into(),
            source: Some(source.into()),
            #[cfg(feature = "backtrace")]
            backtrace: Backtrace::capture(),
        }
    }

    /// Whether the error is likely retryable (e.g., transient).
    pub fn is_retryable(&self) -> bool {
        matches!(
            self,
            AiCoreError::Timeout { .. }
                | AiCoreError::Canceled { .. }
                | AiCoreError::ChannelSend(_)
                | AiCoreError::ChannelRecv(_)
        )
    }

    /// Convenience constructor helpers.
    pub fn model_load(msg: impl Into<String>) -> Self {
        AiCoreError::ModelLoad {
            message: msg.into(),
            source: None,
            #[cfg(feature = "backtrace")]
            backtrace: Backtrace::capture(),
        }
    }
    pub fn inference(msg: impl Into<String>) -> Self {
        AiCoreError::Inference {
            message: msg.into(),
            source: None,
            #[cfg(feature = "backtrace")]
            backtrace: Backtrace::capture(),
        }
    }
    pub fn streaming(msg: impl Into<String>) -> Self {
        AiCoreError::Streaming {
            message: msg.into(),
            source: None,
            #[cfg(feature = "backtrace")]
            backtrace: Backtrace::capture(),
        }
    }
    pub fn cache(msg: impl Into<String>) -> Self {
        AiCoreError::Cache {
            message: msg.into(),
            source: None,
            #[cfg(feature = "backtrace")]
            backtrace: Backtrace::capture(),
        }
    }
    pub fn invalid_arg(msg: impl Into<String>) -> Self {
        AiCoreError::InvalidArgument {
            message: msg.into(),
            source: None,
            #[cfg(feature = "backtrace")]
            backtrace: Backtrace::capture(),
        }
    }
    pub fn not_found(what: impl Into<String>) -> Self {
        AiCoreError::NotFound {
            what: what.into(),
            #[cfg(feature = "backtrace")]
            backtrace: Backtrace::capture(),
        }
    }
    pub fn unsupported(what: impl Into<String>) -> Self {
        AiCoreError::Unsupported {
            what: what.into(),
            #[cfg(feature = "backtrace")]
            backtrace: Backtrace::capture(),
        }
    }
    pub fn timeout() -> Self {
        AiCoreError::Timeout {
            #[cfg(feature = "backtrace")]
            backtrace: Backtrace::capture(),
        }
    }
    pub fn canceled() -> Self {
        AiCoreError::Canceled {
            #[cfg(feature = "backtrace")]
            backtrace: Backtrace::capture(),
        }
    }
}

impl<T> From<std::sync::mpsc::SendError<T>> for AiCoreError {
    fn from(e: std::sync::mpsc::SendError<T>) -> Self {
        AiCoreError::ChannelSend(e.to_string())
    }
}
impl From<std::sync::mpsc::RecvError> for AiCoreError {
    fn from(e: std::sync::mpsc::RecvError) -> Self {
        AiCoreError::ChannelRecv(e.to_string())
    }
}

#[cfg(feature = "crossbeam")]
impl<T> From<crossbeam_channel::SendError<T>> for AiCoreError {
    fn from(e: crossbeam_channel::SendError<T>) -> Self {
        AiCoreError::ChannelSend(e.to_string())
    }
}
#[cfg(feature = "crossbeam")]
impl From<crossbeam_channel::RecvError> for AiCoreError {
    fn from(e: crossbeam_channel::RecvError) -> Self {
        AiCoreError::ChannelRecv(e.to_string())
    }
}

#[cfg(feature = "tokio")]
impl From<tokio::task::JoinError> for AiCoreError {
    fn from(e: tokio::task::JoinError) -> Self {
        AiCoreError::Join(e.to_string())
    }
}

pub trait Context<T> {
    fn with_context(self, msg: impl Into<String>) -> Result<T>;
}

impl<T, E> Context<T> for result::Result<T, E>
where
    E: Into<Box<dyn StdError + Send + Sync>>,
{
    fn with_context(self, msg: impl Into<String>) -> Result<T> {
        self.map_err(|e| AiCoreError::with_context(msg, e))
    }
}
