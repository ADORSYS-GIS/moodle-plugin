//! # Inference Module
//!
//! Handles AI model inference operations including text generation, embeddings, and streaming.

pub mod model_loader;
pub mod text_generation;
pub mod streaming;

// Re-export key types for easy access
pub use model_loader::{Model, ModelLoader, ModelFormat};
pub use text_generation::{InferenceEngine, InferenceResult};
pub use streaming::{StreamingEngine, TokenStream};
