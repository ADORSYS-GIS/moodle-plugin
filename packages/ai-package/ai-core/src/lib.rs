//! ai-core/src/lib.rs
//!
//! Core logic for the Moodle AI plugin: model loading, caching, and unified errors.
//! Integrates with `ai_utils` for configuration, logging, and metrics.
//!
//! # Usage
//! 1. Build or load a configuration (`ai_utils::Config`).
//! 2. Call `init(&config)` to initialize logging and metrics once per process.
//! 3. Construct a `ModelLoader` from the config, then load models (sync or async).
//! 4. Use `Cache` for memoization where appropriate.
//! 5. Run inference with `InferenceEngine`.
//!
//! # Example
//! ```rust,no_run
//! use ai_core::{init, ModelLoader, Result};
//! use ai_utils::Config;
//!
//! fn main() -> Result<()> {
//!     // Minimal in-memory config for demonstration
//!     let mut config = Config::new();
//!     config.data["models"]["base_path"] = serde_json::json!("/path/to/models");
//!     config.data["models"]["backend"] = serde_json::json!("onnx");
//!
//!     // Initialize logging and metrics once
//!     init(&config)?;
//!
//!     // Load a model
//!     let mut loader = ModelLoader::from_config(&config)?;
//!     let _model = loader.load(Some("model.gguf"))?;
//!     Ok(())
//! }
//! ```

pub mod errors;
pub mod model_loader;
pub mod cache;
pub mod inference;
// Re-export key types for easy access
pub use errors::{AiCoreError, Result};
pub use model_loader::{Model, ModelLoader, ModelFormat};
pub use cache::Cache;
pub use inference::InferenceEngine;

use ai_utils::{logging::init_from_config, metrics as utils_metrics, Config};

/// Initialize ai-core with the given config (sets up logging and metrics).
pub fn init(config: &Config) -> Result<()> {
    // Initialize logging once (global tracing subscriber)
    init_from_config(config)?;
    // Initialize metric descriptions and cache config flags
    utils_metrics::init_metrics(config);
    Ok(())
}