#!/usr/bin/env bash
# Run Prometheus locally on host port 9091 to avoid port 9090 conflict
docker run --rm \
  -p 9091:9090 \
  -v "$(pwd)/prometheus-local.yml":/etc/prometheus/prometheus.yml \
  prom/prometheus
echo "Prometheus is available at http://localhost:9091"