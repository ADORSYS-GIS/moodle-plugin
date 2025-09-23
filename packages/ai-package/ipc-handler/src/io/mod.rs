//! # IO Module
//!
//! Handles low-level input/output operations for IPC communication.
//! Provides buffered reading and writing with real-time streaming support.

pub mod reader;
pub mod writer;

// Re-export key types for easy access
pub use reader::{InputReader, Reader as StdinReader};
pub use writer::{OutputWriter, Writer as StdoutWriter};

#[cfg(any(test, feature = "test-utils"))]
pub use reader::MockReader;
#[cfg(any(test, feature = "test-utils"))]
pub use writer::MockWriter;
