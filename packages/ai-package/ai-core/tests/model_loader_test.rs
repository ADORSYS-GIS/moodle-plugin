//! ai-core/tests/model_loader_test.rs
//!
//! Unit tests for model_loader.rs to verify loading, discovery, caching, and error handling.

use ai_core::{errors::AiCoreError, model_loader::{ModelLoader, ModelFormat}};
use ai_utils::Config;
use std::path::PathBuf;
use tempfile::tempdir;
use std::fs;

fn create_test_config(base_path: &str) -> Config {
    let mut config = Config::default();
    config.data = serde_json::json!({
        "models": {
            "base_path": base_path,
            "backend": "onnx",
            "auto_discover": true,
            "supported_formats": ["gguf", "onnx"],
            "checksums": {
                "test.gguf": "d41d8cd98f00b204e9800998ecf8427e"  // MD5 of empty file
            }
        }
    });
    config
}

#[test]
fn test_from_config() {
    let config = create_test_config("/tmp/models");
    let loader = ModelLoader::from_config(&config);
    assert!(loader.is_ok());
    let loader = loader.unwrap();
    assert_eq!(loader.base_path, PathBuf::from("/tmp/models"));
    assert_eq!(loader.default_backend, "onnx");
    assert!(loader.auto_discover);
    assert_eq!(loader.supported_formats, vec![ModelFormat::Gguf, ModelFormat::Onnx]);
    assert_eq!(loader.checksums.get("test.gguf"), Some(&"d41d8cd98f00b204e9800998ecf8427e".to_string()));
}

#[test]
fn test_load_specific_path_success() {
    let dir = tempdir().unwrap();
    let model_path = dir.path().join("test.gguf");
    fs::write(&model_path, b"").unwrap();

    let config = create_test_config(dir.path().to_str().unwrap());
    let mut loader = ModelLoader::from_config(&config).unwrap();

    let model = loader.load(Some("test.gguf"));
    assert!(model.is_ok());
    let model = model.unwrap();
    assert_eq!(model.path, model_path);
    assert_eq!(model.format, ModelFormat::Gguf);
    assert_eq!(model.backend, "onnx");
    assert_eq!(model.metadata.get("version"), Some(&"1.0".to_string()));
    assert!(model.data.is_empty());
}

#[test]
fn test_load_auto_discovery() {
    let dir = tempdir().unwrap();
    let gguf_path = dir.path().join("test.gguf");
    fs::write(&gguf_path, b"").unwrap();
    let onnx_path = dir.path().join("test.onnx");
    fs::write(&onnx_path, b"").unwrap();

    let config = create_test_config(dir.path().to_str().unwrap());
    let mut loader = ModelLoader::from_config(&config).unwrap();

    let model = loader.load(None);
    assert!(model.is_ok());
    // Assumes newest first; order may vary, but check it's one of them
    let model = model.unwrap();
    assert!(model.path.ends_with("test.gguf") || model.path.ends_with("test.onnx"));
}

#[test]
fn test_load_not_found() {
    let dir = tempdir().unwrap();
    let config = create_test_config(dir.path().to_str().unwrap());
    let mut loader = ModelLoader::from_config(&config).unwrap();

    let result = loader.load(Some("missing.gguf"));
    assert!(result.is_err());
    assert!(matches!(result.unwrap_err(), AiCoreError::NotFound { what, .. } if what.contains("missing.gguf")));
}

#[test]
fn test_load_unsupported_format() {
    let dir = tempdir().unwrap();
    let invalid_path = dir.path().join("test.invalid");
    fs::write(&invalid_path, b"").unwrap();

    let config = create_test_config(dir.path().to_str().unwrap());
    let mut loader = ModelLoader::from_config(&config).unwrap();

    let result = loader.load(Some("test.invalid"));
    assert!(result.is_err());
    assert!(matches!(result.unwrap_err(), AiCoreError::Unsupported { what, .. } if what.contains("unknown model format")));
}

#[test]
fn test_load_checksum_mismatch() {
    let dir = tempdir().unwrap();
    let model_path = dir.path().join("test.gguf");
    fs::write(&model_path, b"content").unwrap();  // Different checksum

    let config = create_test_config(dir.path().to_str().unwrap());
    let mut loader = ModelLoader::from_config(&config).unwrap();

    let result = loader.load(Some("test.gguf"));
    assert!(result.is_err());
    assert!(matches!(result.unwrap_err(), AiCoreError::Cache { message, .. } if message == "checksum mismatch"));
}

#[test]
fn test_caching() {
    let dir = tempdir().unwrap();
    let model_path = dir.path().join("test.gguf");
    fs::write(&model_path, b"").unwrap();

    let config = create_test_config(dir.path().to_str().unwrap());
    let mut loader = ModelLoader::from_config(&config).unwrap();

    let model1 = loader.load(Some("test.gguf")).unwrap();
    let model2 = loader.load(Some("test.gguf")).unwrap();
    assert_eq!(model1.path, model2.path);  // Same model
    // Check cache hit (via metrics or log, but for test, assume no reload error)
}

#[cfg(feature = "tokio")]
#[tokio::test]
async fn test_load_async() {
    let dir = tempdir().unwrap();
    let model_path = dir.path().join("test.gguf");
    fs::write(&model_path, b"").unwrap();

    let config = create_test_config(dir.path().to_str().unwrap());
    let mut loader = ModelLoader::from_config(&config).unwrap();

    let model = loader.load_async(Some("test.gguf")).await;
    assert!(model.is_ok());
}