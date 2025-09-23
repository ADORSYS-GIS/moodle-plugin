//! ai-core/src/inference.rs
//!
//! Inference logic for the AI core.
//!
//! This module provides a unified interface for running model inference on loaded models.
//! It supports single/batch inputs, optional caching for repeated queries, and integrates with
//! `ai_utils` for configuration and metrics. Outputs are generic (e.g., String for text, Vec<f32> for embeddings).
//!
//! # Usage
//! ```rust,no_run
//! use ai_core::{inference::InferenceEngine, model_loader::ModelLoader, Result};
//! use ai_utils::Config;
//!
//! fn main() -> Result<()> {
//!     let config = Config::default();
//!     let mut loader = ModelLoader::from_config(&config)?;
//!     let model = loader.load(Some("model.gguf"))?;
//!     let engine = InferenceEngine::from_config(&config)?;
//!     let _output = engine.infer(&model, "Hello, world!")?;  // Single input
//!     Ok(())
//! }
//! ```

use std::collections::HashMap;
use std::time::Instant;

use ai_utils::{Config, metrics};
use crate::Result;
use crate::Cache;
use crate::Model;
use tracing;
use serde_json;
use serde::{Deserialize, Serialize};

/// Result of an inference operation
#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct InferenceResult {
    pub text: String,
    pub tokens_generated: usize,
    pub inference_time_ms: u64,
    pub model_used: String,
}

/// Generic inference engine for running predictions on loaded models.
#[derive(Debug)]
pub struct InferenceEngine {
    /// Optional cache for inference results (keyed by input hash).
    pub cache: Option<Cache<String, String>>,
    /// Labels for metrics (e.g., model type).
    metrics_labels: HashMap<String, String>,
}

impl InferenceEngine {
    /// Create a new `InferenceEngine` with default settings.
    pub fn new() -> Self {
        Self {
            cache: None,
            metrics_labels: HashMap::new(),
        }
    }

    /// Create a new `InferenceEngine` from configuration.
    ///
    /// Expects the config to contain:
    /// - `/inference/cache_enabled`: Enable caching (default: false).
    /// - `/inference/cache_max_size`: Cache size if enabled (default: 100).
    /// - `/inference/labels`: Optional labels for metrics.
    pub fn from_config(config: &Config) -> Result<Self> {
        let cache_enabled = config.get_as::<bool>("/inference/cache_enabled").unwrap_or(false);
        let cache = if cache_enabled {
            let cache_config = Config::default();  // Sub-config or reuse; adjust as needed
            Some(Cache::from_config(&cache_config)?)
        } else {
            None
        };

        let metrics_labels = match config.get("/inference/labels") {
            Some(value) => {
                // Try to deserialize the JSON value into a HashMap<String, String>
                serde_json::from_value(value.clone())
                    .map_err(|e| crate::AiCoreError::inference(format!("failed to parse inference labels: {}", e)))?
            }
            None => HashMap::new(),
        };

        tracing::debug!("Initialized inference engine; cache_enabled={}", cache_enabled);

        Ok(Self {
            cache,
            metrics_labels,
        })
    }

    /// Run inference on a single input using the loaded model.
    /// Returns the output as String (placeholder; adjust for actual type, e.g., Vec<f32>).
    pub fn infer(&self, model: &Model, input: &str) -> Result<String> {
        self.run(model, input, 512)
    }

    /// Run inference with specified max tokens (alias for compatibility).
    pub fn run(&self, model: &Model, input: &str, max_tokens: usize) -> Result<String> {
        let start = Instant::now();

        // Check cache if enabled
        if let Some(cache) = &self.cache {
            if let Some(cached) = cache.get(&input.to_string()) {
                let duration = start.elapsed().as_secs_f64();
                self.record_metrics(model, duration, "hit");
                return Ok(cached);
            }
        }

        // Simulate inference (replace with actual backend call using model.data)
        let output = format!("Response to '{}' (max_tokens: {})", input, max_tokens);  // Placeholder

        let duration = start.elapsed().as_secs_f64();
        self.record_metrics(model, duration, "miss");

        // Cache if enabled
        if let Some(cache) = &self.cache {
            cache.insert(input.to_string(), output.clone());
        }

        Ok(output)
    }

    /// Run batch inference on multiple inputs.
    pub fn infer_batch(&self, model: &Model, inputs: &[String]) -> Result<Vec<String>> {
        let mut outputs = Vec::with_capacity(inputs.len());
        for input in inputs {
            outputs.push(self.infer(model, input)?);
        }
        Ok(outputs)
    }

    /// Record inference metrics.
    fn record_metrics(&self, model: &Model, duration: f64, status: &str) {
        let mut labels = self.metrics_labels.clone();
        labels.insert("backend".to_string(), model.backend.clone());
        labels.insert("format".to_string(), format!("{:?}", model.format));
        labels.insert("status".to_string(), status.to_string());

        // Convert to HashMap<&str, &str> for metrics functions
        let labels_for_metrics: std::collections::HashMap<&str, &str> = labels
            .iter()
            .map(|(k, v)| (k.as_str(), v.as_str()))
            .collect();

        metrics::record_histogram("inference_duration_seconds", duration, labels_for_metrics.clone());
        metrics::inc_counter("inference_total", labels_for_metrics);

        tracing::info!(
            "Inference completed (model={}, duration={:.2}s, status={})",
            model.path.display(),
            duration,
            status
        );
    }
}