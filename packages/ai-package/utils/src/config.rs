use std::{
    error::Error,
    fs,
    path::Path,
    str::FromStr,
};

use serde_json::Value as JsonValue;
use serde_yaml::Value as YamlValue;

#[derive(Debug, Clone, Default)]
pub struct Config {
    /// We now store a hierarchical map using serde_json::Value
    pub data: JsonValue,
}

impl Config {
    pub fn new() -> Self {
        Self {
            data: JsonValue::Object(serde_json::Map::new()),
        }
    }

    /// Merge another config into self (deep merge for nested keys).
    pub fn merge(&mut self, other: Config) {
        deep_merge(&mut self.data, other.data);
    }

    /// Load config from environment variables with a given prefix (flattened into top-level keys).
    pub fn from_env(prefix: &str) -> Result<Self, Box<dyn Error>> {
        let mut cfg = Config::new();

        if let Some(map) = cfg.data.as_object_mut() {
            for (key, value) in std::env::vars() {
                if let Some(stripped_key) = key.strip_prefix(prefix) {
                    // Normalise to lowercase so env overrides match file keys (e.g., host/port)
                    map.insert(stripped_key.to_lowercase(), JsonValue::String(value));
                }
            }
        }

        Ok(cfg)
    }

    /// Load config from `.env` file.
    pub fn from_dotenv(path: &str) -> Result<Self, Box<dyn Error>> {
        let mut cfg = Config::new();
        let content = fs::read_to_string(path)?;

        if let Some(map) = cfg.data.as_object_mut() {
            for line in content.lines() {
                let trimmed = line.trim();
                if trimmed.is_empty() || trimmed.starts_with('#') {
                    continue;
                }
                if let Some((key, value)) = trimmed.split_once('=') {
                    map.insert(key.trim().to_string(), JsonValue::String(value.trim().to_string()));
                }
            }
        }

        Ok(cfg)
    }

    /// Load from JSON file (supports nested structures).
    pub fn from_json(path: &str) -> Result<Self, Box<dyn Error>> {
        let data = fs::read_to_string(path)?;
        let parsed: JsonValue = serde_json::from_str(&data)?;
        Ok(Self { data: parsed })
    }

    /// Load from YAML file (supports nested structures).
    pub fn from_yaml(path: &str) -> Result<Self, Box<dyn Error>> {
        let data = fs::read_to_string(path)?;
        let parsed_yaml: YamlValue = serde_yaml::from_str(&data)?;
        // Convert YAML to JSON for consistency
        let parsed_json: JsonValue = serde_json::from_str(&serde_json::to_string(&parsed_yaml)?)?;
        Ok(Self { data: parsed_json })
    }

    /// Get a value by JSON pointer (e.g. "/database/host").
    pub fn get(&self, pointer: &str) -> Option<&JsonValue> {
        self.data.pointer(pointer)
    }

    /// Get as string if possible.
    pub fn get_str(&self, pointer: &str) -> Option<&str> {
        self.get(pointer)?.as_str()
    }

    /// Get as type `T` if parsable.
    pub fn get_as<T: FromStr>(&self, pointer: &str) -> Option<T> {
        let v = self.get(pointer)?;
        // If it's already a string, parse directly.
        if let Some(s) = v.as_str() {
            return s.parse().ok();
        }
        // If it's a number, convert to string then parse.
        if let Some(n) = v.as_u64() {
            return n.to_string().parse().ok();
        }
        if let Some(n) = v.as_i64() {
            return n.to_string().parse().ok();
        }
        if let Some(n) = v.as_f64() {
            return n.to_string().parse().ok();
        }
        // If it's a boolean, stringify then parse (works for types implementing FromStr for bool or strings)
        if let Some(b) = v.as_bool() {
            return b.to_string().parse().ok();
        }
        None
    }

    /// Get with default fallback.
    pub fn get_or_default<'a>(&'a self, pointer: &str, default: &'a str) -> &'a str {
        self.get_str(pointer).unwrap_or(default)
    }
}

/// Recursively merge two serde_json::Values.
fn deep_merge(a: &mut JsonValue, b: JsonValue) {
    match (a, b) {
        (JsonValue::Object(a_map), JsonValue::Object(b_map)) => {
            for (k, v) in b_map {
                deep_merge(a_map.entry(k).or_insert(JsonValue::Null), v);
            }
        }
        (a_slot, b_value) => {
            *a_slot = b_value;
        }
    }
}

/// Unified loader function that detects format and merges in correct priority.
pub fn load_config(
    env_prefix: &str,
    dotenv_path: &str,
    config_files: &[&str],
) -> Result<Config, Box<dyn Error>> {
    let mut config = Config::new();

    // 1. Load from files (lowest priority)
    for file in config_files {
        if Path::new(file).exists() {
            let ext = Path::new(file)
                .extension()
                .and_then(|s| s.to_str())
                .unwrap_or("")
                .to_lowercase();

            let file_cfg = match ext.as_str() {
                "yaml" | "yml" => Config::from_yaml(file)?,
                "json" => Config::from_json(file)?,
                _ => continue,
            };
            config.merge(file_cfg);
        }
    }

    // 2. Load from .env
    if Path::new(dotenv_path).exists() {
        config.merge(Config::from_dotenv(dotenv_path)?);
    }

    // 3. Load from environment (highest priority)
    config.merge(Config::from_env(env_prefix)?);

    Ok(config)
}
