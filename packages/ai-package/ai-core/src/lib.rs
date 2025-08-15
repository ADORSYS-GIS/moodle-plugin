//! ai-core/src/lib.rs
//!
//! Core error handling for the Moodle AI plugin.
//!
//! This crate provides unified error handling for the AI components.
//! It exposes the error types and result type for consistent error management.

pub mod errors;

/// Re-export the core error type and result type for easy access
pub use errors::{AiCoreError, Result};