//! ai-core/tests/inference_test.rs
//!
//! Unit tests for inference.rs to verify single/batch inference, caching, and metrics.

use ai_core::{inference::InferenceEngine, model_loader::{Model, ModelFormat}};
use ai_utils::Config;
use std::collections::HashMap;
use std::path::PathBuf;
use std::sync::Arc;

#[test]
fn test_inference_from_config() {
    let mut config = Config::default();
    config.data = serde_json::json!({
        "inference": {
            "cache_enabled": true,
            "cache_max_size": 10,
            "labels": {"type": "test"}
        }
    });

    let engine = InferenceEngine::from_config(&config);
    assert!(engine.is_ok());
    let engine = engine.unwrap();
    assert!(engine.cache.is_some());
}

#[test]
fn test_infer_single() {
    let config = Config::default();
    let engine = InferenceEngine::from_config(&config).unwrap();

    let model = Model {
        path: PathBuf::from("test.gguf"),
        format: ModelFormat::Gguf,
        backend: "onnx".to_string(),
        metadata: HashMap::new(),
        data: Arc::new(vec![]),
    };

    let output = engine.infer(&model, "test input");
    assert!(output.is_ok());
    assert!(output.unwrap().contains("Response to 'test input'"));
}

#[test]
fn test_infer_batch() {
    let config = Config::default();
    let engine = InferenceEngine::from_config(&config).unwrap();

    let model = Model {
        path: PathBuf::from("test.gguf"),
        format: ModelFormat::Gguf,
        backend: "onnx".to_string(),
        metadata: HashMap::new(),
        data: Arc::new(vec![]),
    };

    let inputs = vec!["input1".to_string(), "input2".to_string()];
    let outputs = engine.infer_batch(&model, &inputs);
    assert!(outputs.is_ok());
    let outputs = outputs.unwrap();
    assert_eq!(outputs.len(), 2);
    assert!(outputs[0].contains("input1"));
    assert!(outputs[1].contains("input2"));
}

#[test]
fn test_inference_cache() {
    let mut config = Config::default();
    config.data = serde_json::json!({
        "inference": {
            "cache_enabled": true,
            "cache_max_size": 5
        }
    });
    let engine = InferenceEngine::from_config(&config).unwrap();

    let model = Model {
        path: PathBuf::from("test.gguf"),
        format: ModelFormat::Gguf,
        backend: "onnx".to_string(),
        metadata: HashMap::new(),
        data: Arc::new(vec![]),
    };

    let input = "cached input";
    let output1 = engine.infer(&model, input).unwrap();
    let output2 = engine.infer(&model, input).unwrap();
    assert_eq!(output1, output2);  // Cache hit
}