# Moodle AI Provider: OpenAI Compatible V1

This Moodle plugin implements the `core_ai` provider interface to connect Moodle to any OpenAI-compatible AI service.

It uses a high-performance architecture where the PHP plugin communicates with a dedicated Rust binary over `stdin`/`stdout`. This avoids network latency for local processing and keeps the AI model client (managed by Rust) alive for the lifetime of a PHP-FPM worker, reducing overhead.

## Architecture

1.  **Moodle `core_ai` Provider (PHP)**: The plugin registers as a standard Moodle AI provider.
2.  **Process Manager (PHP)**: A PHP class (`process_manager`) is responsible for spawning and managing the Rust process. It uses `proc_open`.
3.  **IPC (stdin/stdout)**: PHP sends JSON requests to the Rust process's `stdin` and reads JSON responses from its `stdout`.
4.  **AI Processor (Rust)**: A long-lived Rust application that reads from `stdin`, uses the `async-openai` crate to communicate with the AI service, and writes the response to `stdout`.
5.  **Environment Variables**: The PHP process passes the API key and base URL from Moodle's settings to the Rust process via environment variables.

## Prerequisites

-   A running Moodle instance (4.3+).
-   Docker and Docker Compose.
-   The Rust toolchain (to compile the processor). Install via [rustup.rs](https://rustup.rs/).
-   An API key for an OpenAI-compatible service.

## Installation and Setup

### 1. Build the Rust Processor

First, you need to compile the Rust binary. This only needs to be done once, or whenever you change the Rust code.

```bash
# Navigate to the rust_processor directory
cd /path/to/plugins/local/ai_openai_v1/rust_processor

# Build the project in release mode for performance
cargo build --release