use crate::errors::{Error, Result};
use serde_json::Value as JsonValue;
use std::env;

/// Configuration system for AI utilities
#[derive(Debug, Clone)]
pub struct Config {
    pub(crate) data: JsonValue,
}

impl Config {
    /// Create a new empty config
    pub fn new() -> Self {
        Self {
            data: JsonValue::Object(serde_json::Map::new()),
        }
    }

    /// Load configuration from environment variables with OpenAI prefix
    pub fn from_env() -> Result<Self> {
        let mut config = Self::new();
        
        // Load OpenAI specific environment variables
        if let Ok(api_key) = env::var("OPENAI_API_KEY") {
            config.set("openai.api_key", &api_key)?;
        }
        
        if let Ok(base_url) = env::var("OPENAI_BASE_URL") {
            config.set("openai.base_url", &base_url)?;
        } else {
            config.set("openai.base_url", "https://api.openai.com/v1")?;
        }
        
        if let Ok(model) = env::var("OPENAI_MODEL") {
            config.set("openai.model", &model)?;
        } else {
            config.set("openai.model", "gpt-3.5-turbo")?;
        }
        
        if let Ok(timeout) = env::var("OPENAI_TIMEOUT") {
            config.set("openai.timeout", &timeout)?;
        } else {
            config.set("openai.timeout", "30")?;
        }
        
        Ok(config)
    }

    /// Get a value by JSON pointer path
    pub fn get(&self, pointer: &str) -> Option<&JsonValue> {
        let normalized = self.normalize_pointer(pointer);
        self.data.pointer(&normalized)
    }

    /// Get string value at path
    pub fn get_str(&self, path: &str) -> Option<&str> {
        self.get(path).and_then(|v| v.as_str())
    }

    /// Get string value at path, returning owned String
    pub fn get_string(&self, path: &str) -> Option<String> {
        self.get_str(path).map(|s| s.to_string())
    }

    /// Get typed value at path
    pub fn get_as<T>(&self, path: &str) -> Option<T> 
    where 
        T: serde::de::DeserializeOwned,
    {
        self.get(path).and_then(|v| serde_json::from_value(v.clone()).ok())
    }

    /// Get a value with default fallback
    pub fn get_or_default(&self, pointer: &str, default: &str) -> String {
        self.get_string(pointer).unwrap_or_else(|| default.to_string())
    }

    /// Set a value by JSON pointer path
    pub fn set(&mut self, pointer: &str, value: &str) -> Result<()> {
        let normalized = self.normalize_pointer(pointer);
        let json_value: JsonValue = serde_json::from_str(&format!("\"{}\"", value))
            .map_err(|e| Error::Json(e))?;
        
        self.set_at_pointer(&normalized, json_value)
    }

    /// Normalize pointer to JSON pointer format
    fn normalize_pointer(&self, pointer: &str) -> String {
        if pointer.starts_with('/') {
            pointer.to_string()
        } else {
            format!("/{}", pointer.replace('.', "/"))
        }
    }

    /// Set value at JSON pointer
    fn set_at_pointer(&mut self, pointer: &str, value: JsonValue) -> Result<()> {
        let parts: Vec<&str> = pointer.trim_start_matches('/').split('/').collect();
        if parts.is_empty() || (parts.len() == 1 && parts[0].is_empty()) {
            return Err(Error::config("Invalid pointer path".to_string()).into());
        }

        let mut current = &mut self.data;
        
        // Navigate to parent and create objects as needed
        for part in &parts[..parts.len() - 1] {
            if !current.is_object() {
                *current = JsonValue::Object(serde_json::Map::new());
            }
            
            let obj = current.as_object_mut().unwrap();
            if !obj.contains_key(*part) {
                obj.insert(part.to_string(), JsonValue::Object(serde_json::Map::new()));
            }
            current = obj.get_mut(*part).unwrap();
        }
        
        // Set the final value
        if !current.is_object() {
            *current = JsonValue::Object(serde_json::Map::new());
        }
        
        let obj = current.as_object_mut().unwrap();
        obj.insert(parts.last().unwrap().to_string(), value);
        
        Ok(())
    }

    /// Get the underlying JSON data
    pub fn as_json(&self) -> &JsonValue {
        &self.data
    }

    /// Merge another JSON value into this config
    pub fn merge(&mut self, other: &JsonValue) {
        if let (Some(self_obj), Some(other_obj)) = (self.data.as_object_mut(), other.as_object()) {
            for (key, value) in other_obj {
                self_obj.insert(key.clone(), value.clone());
            }
        } else if let Some(other_array) = other.as_array() {
            self.data = JsonValue::Array(other_array.clone());
        } else if let Some(other_string) = other.as_str() {
            self.data = JsonValue::String(other_string.to_string());
        } else if let Some(other_number) = other.as_f64() {
            if let Some(num) = serde_json::Number::from_f64(other_number) {
                self.data = JsonValue::Number(num);
            }
        } else if let Some(other_bool) = other.as_bool() {
            self.data = JsonValue::Bool(other_bool);
        } else if other.is_null() {
            self.data = JsonValue::Null;
        }
    }

    /// Merge another Config into this config
    pub fn merge_config(&mut self, other: &Config) -> Result<()> {
        self.merge(&other.data);
        Ok(())
    }
}

impl Default for Config {
    fn default() -> Self {
        Self::new()
    }
}

