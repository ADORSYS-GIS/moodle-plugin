#!/bin/bash

# OpenAI Moodle Sidecar Startup Script
# This script starts the sidecar in HTTP server mode for better performance

set -e

echo "Starting OpenAI Moodle Sidecar..."

# Set default environment variables if not provided
export OPENAI_API_KEY="${OPENAI_API_KEY:-sk-5446a6fc82104605a4125e6abc0048c3}"
export OPENAI_BASE_URL="${OPENAI_BASE_URL:-https://ai.kivoyo.com/api}"
export OPENAI_MODEL="${OPENAI_MODEL:-adorsys}"
export MAX_TOKENS="${MAX_TOKENS:-1000}"
export SUMMARIZE_THRESHOLD="${SUMMARIZE_THRESHOLD:-2000}"
export HTTP_MODE="${HTTP_MODE:-true}"
export PORT="${PORT:-8081}"

# Build the project if needed
if [ ! -f "target/release/openai-moodle-sidecar" ]; then
    echo "Building Rust sidecar..."
    cargo build --release
fi

# Start the server
echo "Starting HTTP server on port ${PORT}..."
echo "Health check available at: http://127.0.0.1:${PORT}/health"
echo "API endpoint available at: http://127.0.0.1:${PORT}/ai"

exec ./target/release/openai-moodle-sidecar --http-mode --port="${PORT}"
