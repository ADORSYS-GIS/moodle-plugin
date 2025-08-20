//! ai-core/src/lib.rs
//!
//! Core logic for the Moodle AI plugin, providing unified error handling and model loading.
//! Integrates with the `ai_utils` crate for configuration, logging, metrics, and error handling.
//!
//! # Usage
//! 1. Initialize with `init` using an `ai_utils::Config` (sets up logging from ai_utils).
//! 2. Load models with `ModelLoader::load` using an `ai_utils::Config`.
//! 3. Handle errors with `?` and `AiCoreError`.
//!
//! # Example
//! ```rust
//! use ai_core::{init, ModelLoader, Result};
//! use ai_utils::Config;
//!
//! fn main() -> Result<()> {
//!     let mut config = Config::default();
//!     config.merge_from_yaml("config.yaml")?;
//!     init(&config)?;  // Initializes logging from ai_utils
//!     let model = ModelLoader::load(&config)?;
//!     Ok(())
//! }
//! ```

pub mod errors;
pub mod model_loader;

// Re-export key types for easy access
pub use errors::{AiCoreError, Result};
pub use model_loader::{Model, ModelLoader, ModelFormat};

use ai_utils::{logging::init_from_config, Config};

/// Initialize ai-core with the given config (sets up logging from ai_utils).
pub fn init(config: &Config) -> Result<()> {
    // Initialize logging from ai_utils
    init_from_config(config)?;

    // Future: Add metrics or cache init here as needed

    Ok(())
}