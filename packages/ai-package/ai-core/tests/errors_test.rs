//! ai-core/tests/errors_test.rs
//!
//! Unit tests for errors.rs to verify variants, conversions, constructors, and helpers.

use ai_core::errors::{AiCoreError, Result, Context};
use std::io;

#[test]
fn test_model_load_error() {
    let err = AiCoreError::model_load("invalid format");
    assert_eq!(err.to_string(), "model load error: invalid format");
    assert!(!err.is_retryable());
}

#[test]
fn test_inference_error() {
    let err = AiCoreError::inference("shape mismatch");
    assert_eq!(err.to_string(), "inference error: shape mismatch");
    assert!(!err.is_retryable());
}

#[test]
fn test_streaming_error() {
    let err = AiCoreError::streaming("token overflow");
    assert_eq!(err.to_string(), "streaming error: token overflow");
    assert!(!err.is_retryable());
}

#[test]
fn test_cache_error() {
    let err = AiCoreError::cache("eviction failed");
    assert_eq!(err.to_string(), "cache error: eviction failed");
    assert!(!err.is_retryable());
}

#[test]
fn test_invalid_arg_error() {
    let err = AiCoreError::invalid_arg("negative value");
    assert_eq!(err.to_string(), "invalid argument: negative value");
    assert!(!err.is_retryable());
}

#[test]
fn test_not_found_error() {
    let err = AiCoreError::not_found("tensor key");
    assert_eq!(err.to_string(), "not found: tensor key");
    assert!(!err.is_retryable());
}

#[test]
fn test_unsupported_error() {
    let err = AiCoreError::unsupported("GPU backend");
    assert_eq!(err.to_string(), "unsupported operation: GPU backend");
    assert!(!err.is_retryable());
}

#[test]
fn test_timeout_error() {
    let err = AiCoreError::timeout();
    assert_eq!(err.to_string(), "operation timed out");
    assert!(err.is_retryable());
}

#[test]
fn test_canceled_error() {
    let err = AiCoreError::canceled();
    assert_eq!(err.to_string(), "operation cancelled");
    assert!(err.is_retryable());
}

#[test]
fn test_io_error_conversion() {
    let io_err = io::Error::new(io::ErrorKind::NotFound, "file missing");
    let err: AiCoreError = io_err.into();
    assert_eq!(err.to_string(), "io error: file missing");
}

#[test]
fn test_json_error_conversion() {
    let json_err = serde_json::from_str::<serde_json::Value>("invalid json").unwrap_err();
    let err: AiCoreError = json_err.into();
    assert!(err.to_string().contains("json error"));
}

#[test]
fn test_yaml_error_conversion() {
    // Create a serde_yaml::Error by parsing invalid YAML
    // This is the fix for the original issue: serde_yaml::Error::io doesn't exist in serde_yaml 0.9.34
    let yaml_err = serde_yaml::from_str::<serde_yaml::Value>("invalid: yaml:").unwrap_err();
    let err: AiCoreError = yaml_err.into();
    assert!(err.to_string().contains("yaml error"));
}

#[test]
fn test_utf8_error_conversion() {
    let utf8_err = String::from_utf8(vec![0xff]).unwrap_err();
    let err: AiCoreError = utf8_err.into();
    assert!(err.to_string().contains("utf8 error"));
}

#[test]
fn test_channel_send_error_conversion() {
    use std::sync::mpsc;
    let (tx, _rx) = mpsc::channel::<i32>();
    drop(_rx); // Drop the receiver to cause send errors
    let send_err = tx.send(42).unwrap_err();
    let err: AiCoreError = send_err.into();
    assert!(err.to_string().contains("channel send error"));
    assert!(err.is_retryable());
}

#[test]
fn test_channel_recv_error_conversion() {
    use std::sync::mpsc;
    let (tx, rx) = mpsc::channel::<i32>();
    drop(tx);
    let recv_err = rx.recv().unwrap_err();
    let err: AiCoreError = recv_err.into();
    assert!(err.to_string().contains("channel recv error"));
    assert!(err.is_retryable());
}

#[test]
fn test_with_context() {
    let io_err = io::Error::new(io::ErrorKind::NotFound, "file missing");
    let result: Result<i32> = Err(io_err).with_context("during load");
    let err = result.unwrap_err();
    assert!(err.to_string().contains("during load"));
    if let AiCoreError::Other { message, source, .. } = err {
        assert_eq!(message, "during load");
        assert!(source.is_some());
    } else {
        panic!("expected Other variant");
    }
}

#[test]
fn test_display() {
    let err = AiCoreError::with_context("test error", "source");
    assert!(err.to_string().contains("test error"));
}

#[test]
#[cfg(feature = "backtrace")]
fn test_backtrace_capture() {
    let err = AiCoreError::model_load("test");
    if let AiCoreError::ModelLoad { backtrace, .. } = err {
        assert!(!backtrace.to_string().is_empty());
    } else {
        panic!("expected ModelLoad variant");
    }
}