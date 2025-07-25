# Moodle Plugin Monorepo
[![Build Status](https://img.shields.io/badge/Build-Passing-brightgreen)](https://example.com/build-status)
[![Release Version](https://img.shields.io/badge/Release-v1.0.0-blue)](https://example.com/release)
[![Documentation](https://img.shields.io/badge/Docs-Available-informational)](https://example.com/docs)
[![License](https://img.shields.io/badge/License-MIT-yellow)](LICENSE)

## Overview

The Moodle Plugin Monorepo is a centralized repository housing Helm charts, Kubernetes operators, and Moodle plugins. It aims to simplify the deployment and management of Moodle instances in Kubernetes environments. This monorepo is intended for DevOps teams, plugin authors, and Helm users to streamline their workflow.

## Repository Structure

The repository is organized into the following top-level directories:

- `charts/`: Contains Helm charts for Moodle and its dependencies, making it easier to deploy and manage Moodle instances in Kubernetes.
- `operators/`: Includes Kubernetes operators that manage the lifecycle of Moodle instances, handling tasks such as scaling, backups, and upgrades.
- `plugins/`: Houses custom Moodle plugins and examples to extend the functionality of Moodle.
- `infra/`: Provides infrastructure-as-code scripts and automation helpers for setting up and managing the underlying infrastructure.
- `docs/`: Offers in-depth documentation, including guides, concepts, and features related to the Moodle Plugin Monorepo.

## Features

- Pre-packaged Helm charts for both production and development environments.
- Kubernetes operator for managing Moodle instances, including scaling, backups, and upgrades.
- Reference Moodle plugins and extension templates for custom development.
- Infrastructure automation scripts and CI/CD integrations for streamlined deployment.

## Getting Started

### Prerequisites

To get started with the Moodle Plugin Monorepo, ensure you have the following prerequisites installed:

- Docker
- Kubernetes (minikube or a Kubernetes cluster)
- Helm CLI
- Rust toolchain (for compiling the Kubernetes operator)

### Installation Steps

1. Add the Helm repository and install the charts:
   ```bash
   helm repo add moodle https://example.com/moodle-repo
   helm install moodle/moodle --generate-name
   ```

2. Build and deploy the Kubernetes operator:
   ```bash
   cd operators/operator
   cargo build --release
   kubectl apply -f deploy/operator.yaml
   ```

3. Install or update Moodle plugins:
   ```bash
   cd plugins
   # Follow the instructions in the plugin directory
   ```

### Quickstart Examples

#### Local Docker Compose Setup

To run Moodle locally using Docker Compose:

```bash
docker-compose up -d
```

#### Kubernetes Deployment Example

#### Frontend WASM Example

To build and run the frontend WASM example:
1. Build the WASM module and copy necessary files:
   ```bash
   wasm-pack build --target web --out-dir ./frontend-example/pkg ./packages/moodle-wasm-example
   ```
2. Open `frontend-example/index.html` in a web browser to view the example.


To deploy Moodle on a Kubernetes cluster:

```bash
kubectl apply -f examples/moodle-deployment.yaml
```

## Documentation

For detailed guides and documentation, please refer to the [MkDocs site](docs/index.md). Key guides include:

- [Architecture Overview](docs/concepts/01-architecture.md)
- [Moodle Operation Guide](docs/guides/01-moodle-operation.md)
- [Optimizing Moodle with Rust](docs/guides/05-moodle-optimisations-with-rust.md)

## Contributing

We welcome contributions to the Moodle Plugin Monorepo. Please follow the issue filing and PR workflow guidelines. Ensure your code adheres to our coding standards and linting rules as outlined in [`.vale.ini`](.vale.ini) and Rust formatting guidelines.

## License

This project is licensed under the [MIT License](LICENSE).

## Contact / Support

For support and discussions, please use [GitHub Discussions](https://github.com/example/moodle-plugin-monorepo/discussions) or file an issue on the [issue tracker](https://github.com/example/moodle-plugin-monorepo/issues). You can also join our [Slack channel](https://example.slack.com/moodle-plugin-monorepo) for community support.
