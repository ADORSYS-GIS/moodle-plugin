use std::env;
use std::fs;
use std::path::PathBuf;

use ai_utils::config::{load_config, Config};
use serde_json::json;
use tempfile::tempdir;

fn write_temp_file(path: &PathBuf, contents: &str) {
    fs::write(path, contents).expect("failed to write temp file");
}

#[test]
fn test_load_yaml_and_json() {
    let dir = tempdir().unwrap();
    let yaml_path = dir.path().join("test_config.yaml");
    let json_path = dir.path().join("test_config.json");

    write_temp_file(
        &yaml_path,
        r#"
database:
  host: "localhost"
  port: 5432
"#,
    );

    write_temp_file(
        &json_path,
        r#"{
  "service": {
    "name": "TestService",
    "enabled": true
  }
}"#,
    );

    let yaml_str = yaml_path.to_string_lossy();
    let json_str = json_path.to_string_lossy();
    let cfg = load_config("", "", &[&yaml_str, &json_str]).unwrap();

    assert_eq!(cfg.get_str("/database/host"), Some("localhost"));
    assert_eq!(cfg.get_as::<u32>("/database/port"), Some(5432));
    assert_eq!(cfg.get_str("/service/name"), Some("TestService"));
    assert_eq!(cfg.get("/service/enabled").unwrap().as_bool(), Some(true));

    // tempdir cleans up
}

#[test]
fn test_env_override() {
    let dir = tempdir().unwrap();
    let yaml_path = dir.path().join("test_config.yaml");
    env::set_var("APP_HOST", "envhost");
    env::set_var("APP_PORT", "9999");

    write_temp_file(&yaml_path, "host: \"filehost\"\nport: 1234\n");

    let yaml_str = yaml_path.to_string_lossy();
    let cfg = load_config("APP_", "", &[&yaml_str]).unwrap();

    assert_eq!(cfg.get_str("/host"), Some("envhost"));
    assert_eq!(cfg.get_as::<u32>("/port"), Some(9999));

    env::remove_var("APP_HOST");
    env::remove_var("APP_PORT");
    // tempdir cleans up
}

#[test]
fn test_dotenv_loading() {
    let dir = tempdir().unwrap();
    let dotenv_path = dir.path().join(".env.test");
    write_temp_file(&dotenv_path, "DOTENV_KEY=value_from_dotenv\n");

    let dotenv_str = dotenv_path.to_string_lossy();
    let cfg = load_config("", &dotenv_str, &[]).unwrap();
    assert_eq!(cfg.get_str("/DOTENV_KEY"), Some("value_from_dotenv"));
    // tempdir cleans up
}

#[test]
fn test_merge_priority() {
    let dir = tempdir().unwrap();
    let file_path = dir.path().join("file.yaml");
    let dotenv_path = dir.path().join(".env.test");

    write_temp_file(&file_path, "port: 1234\n");
    write_temp_file(&dotenv_path, "port=5678\n");

    // Env var says port=9999
    env::set_var("APP_port", "9999");

    let file_str = file_path.to_string_lossy();
    let dotenv_str = dotenv_path.to_string_lossy();
    let cfg = load_config("APP_", &dotenv_str, &[&file_str]).unwrap();
    assert_eq!(cfg.get_as::<u32>("/port"), Some(9999));

    env::remove_var("APP_port");
    // tempdir cleans up
}

#[test]
fn test_get_or_default() {
    let mut cfg = Config::new();
    cfg.merge(Config {
        data: json!({ "exists": "value_here" }),
    });

    assert_eq!(cfg.get_or_default("/exists", "default"), "value_here");
    assert_eq!(cfg.get_or_default("/missing", "default"), "default");
}

#[test]
fn test_nested_merge() {
    let mut cfg1 = Config::new();
    cfg1.merge(Config {
        data: json!({
            "db": { "host": "localhost", "port": 5432 }
        }),
    });

    let mut cfg2 = Config::new();
    cfg2.merge(Config {
        data: json!({
            "db": { "port": 6000 }
        }),
    });

    cfg1.merge(cfg2);

    assert_eq!(cfg1.get_str("/db/host"), Some("localhost"));
    assert_eq!(cfg1.get_as::<u32>("/db/port"), Some(6000));
}
