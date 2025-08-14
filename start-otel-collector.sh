#!/usr/bin/env bash
# Start a local OpenTelemetry Collector exposing OTLP (gRPC/HTTP) and Prometheus endpoints.
set -e

CONFIG_FILE="$(pwd)/otel-collector-config.yaml"
if [[ ! -f "$CONFIG_FILE" ]]; then
  echo "Configuration file not found: $CONFIG_FILE"
  exit 1
fi

docker run --rm \
  -p 4317:4317 \
  -p 4318:4318 \
  -p 8889:8889 \
  -v "$CONFIG_FILE":/etc/otel-collector-config.yaml \
  otel/opentelemetry-collector-contrib:latest \
  --config /etc/otel-collector-config.yaml