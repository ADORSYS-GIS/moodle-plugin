//! ai-core/src/cache.rs
//!
//! In-memory caching for AI model outputs and contexts.
//!
//! This module provides a thread-safe cache for storing inference results, model contexts,
//! or tokenized inputs to reduce recomputation. It integrates with `ai_utils` for configuration
//! (e.g., cache size limits) and metrics (e.g., hit/miss rates). Cache eviction is LRU-based.
//!
//! # Usage
//! ```rust,no_run
//! use ai_core::{cache::Cache, Result};
//! use ai_utils::Config;
//!
//! fn main() -> Result<()> {
//!     let config = Config::default();
//!     let cache = Cache::from_config(&config)?;
//!     cache.insert("prompt1", "response1".to_string());
//!     if let Some(response) = cache.get(&"prompt1") {
//!         println!("Cache hit: {}", response);
//!     }
//!     Ok(())
//! }
//! ```
//!
//! Notes:
//! - `/cache/max_size` defaults to 100 when not set. An explicit value of 0 is rejected with
//!   `InvalidArgument`.
//! - `insert` and `clear` take `&self` (interior mutability). Share `Cache` across threads with
//!   `Arc<Cache<_, _>>` when needed.

use lru::LruCache;
use std::collections::HashMap;
use std::hash::Hash;
use std::sync::Mutex;
use ai_utils::{config::Config, metrics};
use crate::errors::{AiCoreError, Result};
use tracing::debug;

/// Generic cache for key-value pairs, with LRU eviction.
#[derive(Debug)]
pub struct Cache<K, V>
where
    K: Eq + Hash + Clone,
    V: Clone,
{
    /// Internal LRU cache.
    inner: Mutex<LruCache<K, V>>,
    // Note: max size is tracked inside the LruCache; we don't need a separate field here.
    /// Labels for metrics (e.g., cache type).
    metrics_labels: HashMap<String, String>,
}

impl<K, V> Cache<K, V>
where
    K: Eq + Hash + Clone,
    V: Clone,
{
    /// Create a new cache with the specified capacity.
    pub fn new(capacity: usize) -> Self {
        let max_size = std::num::NonZero::new(capacity.max(1)).expect("capacity > 0");
        let inner = Mutex::new(LruCache::new(max_size));
        let metrics_labels = HashMap::new();
        
        debug!("Initialized cache with capacity={}", capacity);
        
        Self { inner, metrics_labels }
    }

    /// Create a new cache from configuration.
    ///
    /// Expects the config to contain:
    /// - `/cache/max_size`: Maximum number of entries (default: 100). If explicitly set to 0, returns an error.
    /// - `/cache/labels`: Optional labels for metrics (e.g., {"type": "context"}).
    pub fn from_config(config: &Config) -> Result<Self> {
        let size = config.get_as::<usize>("/cache/max_size").unwrap_or(100);
        if size == 0 {
            return Err(AiCoreError::invalid_arg("cache max_size cannot be 0"));
        }
        // Convert to NonZero<usize> for LruCache (safe: size > 0 guaranteed above)
        let max_size = std::num::NonZero::new(size).expect("size > 0 ensured by check");
        let metrics_labels = config
            .get("/cache/labels")
            .and_then(|v| v.as_object())
            .map(|obj| {
                obj.iter()
                    .map(|(k, v)| (k.clone(), v.as_str().unwrap_or("").to_string()))
                    .collect::<HashMap<String, String>>()
            })
            .unwrap_or_default();

        let inner = Mutex::new(LruCache::new(max_size));
        
        debug!("Initialized cache with max_size={}", max_size);
        
        Ok(Self { inner, metrics_labels })
    }

    /// Insert a key-value pair into the cache.
    pub fn insert(&self, key: K, value: V) {
        let mut inner = self.inner.lock().unwrap();
        let before_len = inner.len();
        let existed = { inner.get(&key).is_some() };
        // Insert the new value
        inner.put(key, value);
        let after_len = inner.len();

        metrics::inc_counter("cache_inserts", self.labels_for_metrics());

        // Eviction occurs when inserting a new key while already at capacity
        if !existed && after_len == before_len {
            metrics::inc_counter("cache_evictions", self.labels_for_metrics());
            debug!("Cache at capacity; oldest entry evicted");
        }
    }

    /// Get a value from the cache if present.
    pub fn get(&self, key: &K) -> Option<V> {
        let mut inner = self.inner.lock().unwrap();
        let result = inner.get(key).cloned();

        if result.is_some() {
            metrics::inc_counter("cache_hits", self.labels_for_metrics());
        } else {
            metrics::inc_counter("cache_misses", self.labels_for_metrics());
        }

        result
    }

    /// Clear the cache.
    pub fn clear(&self) {
        let mut inner = self.inner.lock().unwrap();
        inner.clear();

        metrics::inc_counter("cache_clears", self.labels_for_metrics());

        debug!("Cache cleared");
    }

    /// Get the current cache size.
    pub fn size(&self) -> usize {
        self.inner.lock().unwrap().len()
    }

    /// Alias for insert method (for compatibility)
    pub fn put(&self, key: K, value: V) {
        self.insert(key, value);
    }

    /// Get the current number of entries in the cache.
    pub fn len(&self) -> usize {
        self.inner.lock().unwrap().len()
    }

    /// Get the maximum capacity of the cache.
    pub fn capacity(&self) -> usize {
        self.inner.lock().unwrap().cap().get()
    }

    /// Check if the cache is empty.
    pub fn is_empty(&self) -> bool {
        self.inner.lock().unwrap().is_empty()
    }
}

/// Cache statistics for monitoring and debugging
#[derive(Debug, Clone)]
pub struct CacheStats {
    pub size: usize,
    pub capacity: usize,
    pub hit_rate: f64,
    pub miss_rate: f64,
}

impl<K, V> Cache<K, V>
where
    K: Eq + Hash + Clone,
    V: Clone,
{
    /// Get cache statistics
    pub fn stats(&self) -> CacheStats {
        let inner = self.inner.lock().unwrap();
        CacheStats {
            size: inner.len(),
            capacity: inner.cap().get(),
            hit_rate: 0.0, // TODO: Track hit/miss rates
            miss_rate: 0.0,
        }
    }

    /// Build metrics labels in the borrowing form expected by the metrics facade.
    fn labels_for_metrics(&self) -> HashMap<&str, &str> {
        self.metrics_labels
            .iter()
            .map(|(k, v)| (k.as_str(), v.as_str()))
            .collect()
    }
}