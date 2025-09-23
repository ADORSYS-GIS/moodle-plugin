//! Configuration management for the AI package
//!
//! This module provides a simplified configuration system that supports:
//! - Environment variable loading
//! - JSON pointer access for hierarchical data
//! - Type-safe value retrieval

pub mod config;

// Re-export the config as the main Config type
pub use config::Config;