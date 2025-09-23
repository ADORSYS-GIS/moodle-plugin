//! # Cache Module
//!
//! Provides in-memory caching for AI operations including model caching,
//! inference result caching, and context caching for improved performance.

pub mod memory_cache;

// Re-export key types for easy access
pub use memory_cache::{Cache, CacheStats};
