#!/bin/bash

set -e

echo "Installing CloudNativePG operator..."

kubectl apply -f https://raw.githubusercontent.com/cloudnative-pg/cloudnative-pg/release-1.24/releases/cnpg-1.24.2.yaml

echo "CloudNativePG operator installed."
