//! # Utils Crate
//!
//! **`utils`** is a shared library of production-grade utilities designed for
//! high-performance AI and data-intensive applications.  
//! It centralizes common concerns such as configuration, logging, metrics,
//! and error handling into one reusable package.
//!
//! ## Overview
//! This crate is intended to be imported by multiple services and binaries,
//! ensuring consistent behavior and reducing code duplication.
//!
//! ### Provided Modules
//! - [`config`] — Flexible, layered configuration management (env, files, CLI).
//! - [`logging`] — Centralized, thread-safe logging using [`tracing`].
//! - [`metrics`] — High-performance metrics collection and export.
//! - [`errors`] — Unified error type with backtrace and rich context support.
//!
//! ### Example
//! ```no_run
//! use utils::{config, logging, metrics};
//!
//! fn main() {
//!     // Initialize logging
//!     logging::init_logger(None).expect("Failed to initialize logger");
//!
//!     // Load configuration
//!     let cfg = config::load().expect("Failed to load config");
//!
//!     // Record a sample metric
//!     metrics::counter!("app.startups", 1);
//!
//!     println!("Application started with config: {:?}", cfg);
//! }
//! ```
//!
//! ### Features
//! - **Thread-safe**: Built for concurrent workloads.
//! - **Extensible**: Easy to add custom metrics, log layers, or config sources.
//! - **Minimal overhead**: Uses efficient, async-friendly primitives.
//!
//! [`tracing`]: https://docs.rs/tracing

pub mod config;
pub mod errors;
pub mod logging;
pub mod metrics;

pub use config::Config;