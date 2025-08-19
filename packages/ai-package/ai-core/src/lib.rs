//! ai-core/src/lib.rs
//!
//! Core error handling and model loading for the Moodle AI plugin.
//!
//! This crate provides unified error handling for the AI components and a
//! high-level interface for working with machine learning models. It supports
//! different model formats, integrates with a robust configuration system,
//! and includes features like in-memory caching and checksum validation to
//! ensure reliability and performance.
//!
//! # Features
//!
//! - `tokio`: Enables asynchronous model loading, allowing non-blocking I/O operations.
//!   This is essential for high-throughput services.
//!
//! # Modules
//!
//! - `errors`: Defines a unified error type (`AiCoreError`) for consistent error handling across the crate.
//! - `model_loader`: Manages the entire lifecycle of AI models, from discovery and loading to caching and validation.
//!
//! # Getting Started
//!
//! A typical workflow involves initializing the `ModelLoader` from a configuration and then loading a model.
//!
//! ```rust
//! use ai_core::{ModelLoader, Result};
//! use ai_utils::config::Config;
//!
//! #[tokio::main]
//! async fn main() -> Result<()> {
//!     // 1. Load application configuration
//!     let mut config = Config::default();
//!     // Assume 'config.yaml' contains the model paths and settings
//!     config.merge_from_yaml("config.yaml")?;
//!
//!     // 2. Create the ModelLoader from configuration
//!     let loader = ModelLoader::from_config(&config)?;
//!
//!     // 3. Load a specific model (or use auto-discovery with None)
//!     let model = loader.load_async(Some("my_model.gguf")).await?;
//!
//!     // The model is now loaded and ready for use
//!     println!("Successfully loaded model from: {}", model.path.display());
//!
//!     Ok(())
//! }

pub mod errors;
pub mod model_loader;

/// Re-export the core error type and result type for easy access
pub use errors::{AiCoreError, Result};
pub use model_loader::ModelLoader;