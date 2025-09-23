//! Output Writer Module
//!
//! Handles writing responses to stdout with proper error handling and flushing.
//! Follows Single Responsibility Principle and Open/Closed Principle.

use std::io::{self, Write, Stdout, BufWriter};
use tracing::{debug, warn};
use crate::errors::{IpcError, Result};

/// Trait for writing output (enables testing and different output destinations)
pub trait OutputWriter {
    fn write_line(&mut self, line: &str) -> Result<()>;
    fn flush(&mut self) -> Result<()>;
}

/// Standard output writer implementation
pub struct Writer {
    writer: BufWriter<Stdout>,
    lines_written: usize,
}

impl Writer {
    /// Create a new writer
    pub fn new() -> Self {
        Self {
            writer: BufWriter::new(io::stdout()),
            lines_written: 0,
        }
    }

    /// Get the number of lines written so far
    pub fn lines_written(&self) -> usize {
        self.lines_written
    }
}

impl Default for Writer {
    fn default() -> Self {
        Self::new()
    }
}

impl OutputWriter for Writer {
    /// Write a line to stdout with automatic flushing
    fn write_line(&mut self, line: &str) -> Result<()> {
        if line.trim().is_empty() {
            warn!("Attempting to write empty line");
            return Err(IpcError::protocol("cannot write empty line"));
        }

        // Write the line with newline
        writeln!(self.writer, "{}", line)
            .map_err(|e| {
                warn!("IO error writing line {}: {}", self.lines_written + 1, e);
                IpcError::Io(e)
            })?;

        // Flush to ensure immediate delivery
        self.flush()?;
        
        self.lines_written += 1;
        debug!("Wrote line {}: {} bytes", self.lines_written, line.len());
        
        Ok(())
    }

    /// Flush the output buffer
    fn flush(&mut self) -> Result<()> {
        self.writer.flush()
            .map_err(|e| {
                warn!("IO error flushing output: {}", e);
                IpcError::Io(e)
            })
    }
}

/// Mock writer for testing
#[cfg(any(test, feature = "test-utils"))]
pub struct MockWriter {
    lines: Vec<String>,
}

#[cfg(any(test, feature = "test-utils"))]
impl MockWriter {
    pub fn new() -> Self {
        Self {
            lines: Vec::new(),
        }
    }

    pub fn lines(&self) -> &[String] {
        &self.lines
    }

    pub fn clear(&mut self) {
        self.lines.clear();
    }
}

#[cfg(test)]
impl Default for MockWriter {
    fn default() -> Self {
        Self::new()
    }
}

#[cfg(any(test, feature = "test-utils"))]
impl OutputWriter for MockWriter {
    fn write_line(&mut self, line: &str) -> Result<()> {
        if line.trim().is_empty() {
            return Err(IpcError::protocol("cannot write empty line"));
        }
        
        self.lines.push(line.to_string());
        Ok(())
    }

    fn flush(&mut self) -> Result<()> {
        // No-op for mock
        Ok(())
    }
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn test_mock_writer() {
        let mut writer = MockWriter::new();
        
        writer.write_line("test line 1").unwrap();
        writer.write_line("test line 2").unwrap();
        
        assert_eq!(writer.lines().len(), 2);
        assert_eq!(writer.lines()[0], "test line 1");
        assert_eq!(writer.lines()[1], "test line 2");
    }

    #[test]
    fn test_empty_line_handling() {
        let mut writer = MockWriter::new();
        assert!(writer.write_line("").is_err());
        assert!(writer.write_line("   ").is_err());
    }

    #[test]
    fn test_clear_functionality() {
        let mut writer = MockWriter::new();
        writer.write_line("test").unwrap();
        assert_eq!(writer.lines().len(), 1);
        
        writer.clear();
        assert_eq!(writer.lines().len(), 0);
    }
}