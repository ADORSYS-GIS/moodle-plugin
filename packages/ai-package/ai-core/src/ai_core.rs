//! # AiCore
//!
//! Unified interface for AI operations that integrates ModelLoader, InferenceEngine, and Cache.
//! This provides a high-level API for the IPC handler to interact with AI functionality.

use crate::{Cache, InferenceEngine, Model, ModelLoader, Result};
use ai_utils::Config;
use serde::{Deserialize, Serialize};
use std::time::{Duration, Instant};
use tracing::{debug, info};

/// Information about a loaded model
#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct ModelInfo {
    pub format: String,
    pub size_bytes: u64,
    pub loaded_at: chrono::DateTime<chrono::Utc>,
}

/// Result of an inference operation
#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct InferenceResult {
    pub text: String,
    pub tokens_generated: usize,
    pub inference_time_ms: u64,
    pub model_used: String,
}

/// Statistics about AI operations
#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct AiStats {
    pub models_loaded: u64,
    pub inferences_performed: u64,
    pub cache_hits: u64,
    pub cache_misses: u64,
    pub uptime: Duration,
}

/// Unified AI core that manages models, inference, and caching
pub struct AiCore {
    model_loader: ModelLoader,
    inference_engine: InferenceEngine,
    cache: Cache<String, InferenceResult>,
    current_model: Option<Model>,
    current_model_path: Option<String>,
    stats: AiStats,
    start_time: Instant,
}

impl AiCore {
    /// Creates a new AiCore instance with the given configuration
    pub fn new(config: &Config) -> Result<Self> {
        debug!("Initializing AiCore with configuration");
        
        let model_loader = ModelLoader::from_config(config)?;
        let inference_engine = InferenceEngine::new();
        let cache = Cache::new(1000); // Default cache size of 1000 entries
        
        let stats = AiStats {
            models_loaded: 0,
            inferences_performed: 0,
            cache_hits: 0,
            cache_misses: 0,
            uptime: Duration::from_secs(0),
        };
        
        info!("AiCore initialized successfully");
        
        Ok(Self {
            model_loader,
            inference_engine,
            cache,
            current_model: None,
            current_model_path: None,
            stats,
            start_time: Instant::now(),
        })
    }
    
    /// Loads a model from the specified path
    pub fn load_model(&mut self, model_path: Option<&str>) -> Result<ModelInfo> {
        info!("Loading model: {:?}", model_path);
        
        let model = std::sync::Arc::try_unwrap(self.model_loader.load(model_path)?).unwrap_or_else(|arc| (*arc).clone());
        let model_path_str = model_path.unwrap_or("default").to_string();
        
        // Get model information
        let model_info = ModelInfo {
            format: format!("{:?}", model.format),
            size_bytes: model.data.len() as u64, // Use actual data size
            loaded_at: chrono::Utc::now(),
        };
        
        // Store the loaded model
        self.current_model = Some(model);
        self.current_model_path = Some(model_path_str);
        self.stats.models_loaded += 1;
        
        info!("Model loaded successfully: {:?}", model_info);
        Ok(model_info)
    }
    
    /// Performs inference with the currently loaded model
    pub fn inference(&mut self, input_text: &str, max_tokens: usize) -> Result<InferenceResult> {
        debug!("Starting inference for input: {} chars", input_text.len());
        
        // Check if a model is loaded
        let model = self.current_model.as_ref()
            .ok_or_else(|| crate::AiCoreError::ModelNotLoaded)?;
        
        let model_name = self.current_model_path.as_ref()
            .unwrap_or(&"unknown".to_string())
            .clone();
        
        // Create cache key
        let cache_key = format!("{}:{}:{}", input_text, max_tokens, model_name);
        
        // Check cache first
        if let Some(cached_result) = self.cache.get(&cache_key) {
            debug!("Cache hit for inference request");
            self.stats.cache_hits += 1;
            return Ok(cached_result.clone());
        }
        
        self.stats.cache_misses += 1;
        
        // Perform inference
        let start_time = Instant::now();
        let output = self.inference_engine.run(model, input_text, max_tokens)?;
        let inference_time = start_time.elapsed();
        
        let result = InferenceResult {
            text: output,
            tokens_generated: max_tokens.min(input_text.len() / 4), // Rough estimate
            inference_time_ms: inference_time.as_millis() as u64,
            model_used: model_name,
        };
        
        // Cache the result
        self.cache.put(cache_key, result.clone());
        self.stats.inferences_performed += 1;
        
        info!("Inference completed in {}ms", result.inference_time_ms);
        Ok(result)
    }
    
    /// Gets current statistics about AI operations
    pub fn get_stats(&mut self) -> AiStats {
        self.stats.uptime = self.start_time.elapsed();
        self.stats.clone()
    }
    
    /// Checks if a model is currently loaded
    pub fn is_model_loaded(&self) -> bool {
        self.current_model.is_some()
    }
    
    /// Gets information about the currently loaded model
    pub fn current_model_info(&self) -> Option<String> {
        self.current_model_path.clone()
    }
    
    /// Clears the inference cache
    pub fn clear_cache(&mut self) {
        self.cache.clear();
        debug!("Inference cache cleared");
    }
    
    /// Gets cache statistics
    pub fn cache_stats(&self) -> (usize, usize) {
        (self.cache.len(), self.cache.capacity())
    }
}

#[cfg(test)]
mod tests {
    use super::*;
    use ai_utils::Config;
    
    #[test]
    fn test_ai_core_creation() {
        let config = Config::new();
        let ai_core = AiCore::new(&config);
        assert!(ai_core.is_ok());
    }
    
    #[test]
    fn test_model_not_loaded_error() {
        let config = Config::new();
        let mut ai_core = AiCore::new(&config).unwrap();
        
        let result = ai_core.inference("test input", 10);
        assert!(result.is_err());
        assert!(matches!(result.unwrap_err(), crate::AiCoreError::ModelNotLoaded));
    }
    
    #[test]
    fn test_stats_tracking() {
        let config = Config::new();
        let mut ai_core = AiCore::new(&config).unwrap();
        
        let stats = ai_core.get_stats();
        assert_eq!(stats.models_loaded, 0);
        assert_eq!(stats.inferences_performed, 0);
        assert!(stats.uptime.as_secs() >= 0);
    }
    
    #[test]
    fn test_cache_operations() {
        let config = Config::new();
        let mut ai_core = AiCore::new(&config).unwrap();
        
        assert!(!ai_core.is_model_loaded());
        ai_core.clear_cache();
        
        let (size, capacity) = ai_core.cache_stats();
        assert_eq!(size, 0);
        assert_eq!(capacity, 1000);
    }
}
