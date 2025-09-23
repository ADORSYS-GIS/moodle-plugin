//! Input Reader Module
//!
//! Handles reading and parsing input from stdin with proper error handling
//! and buffering. Follows Single Responsibility Principle.

use std::io::{self, BufRead, BufReader, Stdin};
use tracing::{debug, warn};
use crate::errors::{IpcError, Result};

/// Trait for reading input (enables testing and different input sources)
pub trait InputReader {
    fn read_line(&mut self) -> Result<String>;
}

/// Standard input reader implementation
pub struct Reader {
    reader: BufReader<Stdin>,
    line_count: usize,
}

impl Reader {
    /// Create a new reader
    pub fn new() -> Self {
        Self {
            reader: BufReader::new(io::stdin()),
            line_count: 0,
        }
    }

    /// Get the number of lines read so far
    pub fn lines_read(&self) -> usize {
        self.line_count
    }
}

impl Default for Reader {
    fn default() -> Self {
        Self::new()
    }
}

impl InputReader for Reader {
    /// Read a line from stdin with proper error handling
    fn read_line(&mut self) -> Result<String> {
        let mut line = String::new();
        
        match self.reader.read_line(&mut line) {
            Ok(0) => {
                debug!("EOF reached after {} lines", self.line_count);
                Err(IpcError::process_communication("EOF reached"))
            }
            Ok(_) => {
                self.line_count += 1;
                
                // Remove trailing newline
                if line.ends_with('\n') {
                    line.pop();
                    if line.ends_with('\r') {
                        line.pop();
                    }
                }
                
                if line.trim().is_empty() {
                    warn!("Received empty line at line {}", self.line_count);
                    return Err(IpcError::protocol("empty line received"));
                }
                
                debug!("Read line {}: {} bytes", self.line_count, line.len());
                Ok(line)
            }
            Err(e) => {
                warn!("IO error reading line {}: {}", self.line_count + 1, e);
                Err(IpcError::Io(e))
            }
        }
    }
}

/// Mock reader for testing
#[cfg(any(test, feature = "test-utils"))]
pub struct MockReader {
    lines: Vec<String>,
    position: usize,
}

#[cfg(any(test, feature = "test-utils"))]
impl MockReader {
    pub fn new(lines: Vec<String>) -> Self {
        Self {
            lines,
            position: 0,
        }
    }
}

#[cfg(any(test, feature = "test-utils"))]
impl InputReader for MockReader {
    fn read_line(&mut self) -> Result<String> {
        if self.position >= self.lines.len() {
            return Err(IpcError::process_communication("EOF reached"));
        }
        
        let line = self.lines[self.position].clone();
        self.position += 1;
        Ok(line)
    }
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn test_mock_reader() {
        let mut reader = MockReader::new(vec![
            "line1".to_string(),
            "line2".to_string(),
        ]);
        
        assert_eq!(reader.read_line().unwrap(), "line1");
        assert_eq!(reader.read_line().unwrap(), "line2");
        assert!(reader.read_line().is_err());
    }

    #[test]
    fn test_empty_line_handling() {
        let mut reader = MockReader::new(vec!["".to_string()]);
        let result = reader.read_line();
        // MockReader doesn't apply the same empty line filtering as the real Reader
        // This test should verify the protocol-level validation instead
        assert!(result.is_ok()); // MockReader returns the empty string as-is
        
        // Test with whitespace-only line
        let mut reader2 = MockReader::new(vec!["   ".to_string()]);
        let result2 = reader2.read_line();
        assert!(result2.is_ok()); // MockReader doesn't filter whitespace
    }
}