//! utils/tests/errors_test.rs
//!
//! Unit tests for errors.rs to ensure conversions, display formatting, and context work as expected.

use ai_utils::errors::{Error, Result};
use anyhow::Context;

#[test]
fn test_config_error() {
    let err = Error::config("missing key");
    assert!(matches!(err, Error::Config(_)));
    assert!(format!("{}", err).contains("missing key"));
}

#[test]
fn test_logging_error() {
    let err = Error::logging("init failed");
    assert!(matches!(err, Error::Logging(_)));
    assert!(format!("{}", err).contains("init failed"));
}

#[test]
fn test_metrics_error() {
    let err = Error::metrics("export failed");
    assert!(matches!(err, Error::Metrics(_)));
    assert!(format!("{}", err).contains("export failed"));
}

#[test]
fn test_internal_error() {
    let err = Error::internal("something went wrong");
    assert!(matches!(err, Error::Internal(_)));
    assert!(format!("{}", err).contains("something went wrong"));
}

#[test]
fn test_io_error_conversion() {
    use std::fs::File;
    let result: Result<_> = File::open("nonexistent_file.txt").map_err(Error::from).context("while opening file");
    let err = result.unwrap_err();
    assert!(err.to_string().contains("while opening file"));
    assert!(err.downcast_ref::<Error>().is_some_and(|e| matches!(e, Error::Io(_))));
}

#[test]
fn test_env_var_error_conversion() {
    let result: Result<_> = std::env::var("VAR_THAT_DOES_NOT_EXIST").map_err(Error::from).context("while reading env");
    let err = result.unwrap_err();
    assert!(err.to_string().contains("while reading env"));
    assert!(err.downcast_ref::<Error>().is_some_and(|e| matches!(e, Error::EnvVar(_))));
}

#[test]
fn test_parse_error_conversion() {
    let result: Result<i32> = "not_a_number".parse::<i32>().map_err(Error::from).context("while parsing number");
    let err = result.unwrap_err();
    assert!(err.to_string().contains("while parsing number"));
    assert!(err.downcast_ref::<Error>().is_some_and(|e| matches!(e, Error::Parse(_))));
}

#[test]
fn test_json_error_conversion() {
    let result: Result<serde_json::Value> = serde_json::from_str("invalid json").map_err(Error::from).context("while parsing JSON");
    let err = result.unwrap_err();
    assert!(err.to_string().contains("while parsing JSON"));
    assert!(err.downcast_ref::<Error>().is_some_and(|e| matches!(e, Error::Json(_))));
}

#[test]
fn test_yaml_error_conversion() {
    let result: Result<serde_yaml::Value> = serde_yaml::from_str("invalid: yaml:").map_err(Error::from).context("while parsing YAML");
    let err = result.unwrap_err();
    assert!(err.to_string().contains("while parsing YAML"));
    assert!(err.downcast_ref::<Error>().is_some_and(|e| matches!(e, Error::Yaml(_))));
}

#[test]
fn test_anyhow_from_error() {
    let base_err = Error::internal("base");
    let anyhow_err: anyhow::Error = base_err.into();
    assert!(anyhow_err.to_string().contains("base"));
}