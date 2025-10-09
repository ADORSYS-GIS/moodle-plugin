# OpenAI Moodle Sidecar - Production Deployment Guide

## 🚀 Overview

The OpenAI Moodle Sidecar runs as a background process in the Moodle container, providing dual-mode communication (HTTP primary, stdio fallback) for AI assistant functionality.

## 📋 Architecture

```
┌─────────────────────────────────────────┐
│             Moodle Container            │
├─────────────────────────────────────────┤
│  ┌─────────────┐    ┌─────────────────┐ │
│  │   Moodle    │───▶│  Rust Sidecar   │ │
│  │ PHP Plugin  │    │  (Background)   │ │
│  └─────────────┘    │  Port: 8081     │ │
│                     └─────────────────┘ │
└─────────────────────────────────────────┘
         │
         ▼
┌─────────────────┐
│   OpenAI API    │
│ (ai.kivoyo.com) │
└─────────────────┘
```

## 🛠️ Deployment Steps

### 1. Build the Sidecar Binary

```bash
# Build the release binary
cd packages/rust-sidecar
cargo build --release

# Copy to build directory
cp ../../target/release/openai-moodle-sidecar ../../build/
```

### 2. Configure Environment Variables

Create/update your `.env` file:

```bash
# OpenAI Configuration
OPENAI_API_KEY=sk-your-actual-api-key-here
OPENAI_BASE_URL=https://ai.kivoyo.com/api
OPENAI_MODEL=adorsys
MAX_TOKENS=1000
SUMMARIZE_THRESHOLD=2000
```

### 3. Deploy with Docker Compose

```bash
# Start the services
docker compose up -d

# Check container status
docker compose ps
```

## 🔧 Management Commands

### Inside Moodle Container

```bash
# View logs
tail -f /var/log/sidecar.log

# Check process
ps aux | grep openai-moodle-sidecar
```

## 🔒 Security Considerations

### API Key Protection
- ✅ Environment variables only
- ✅ Masked in logs
- ✅ Not hardcoded anywhere

### Network Security
- ✅ Binds to localhost only
- ✅ No external exposure except through Moodle
- ✅ HTTPS to OpenAI API




### Updates
```bash
# To update the sidecar:
1. Build new binary: cargo build --release
2. Copy to build/: cp target/release/openai-moodle-sidecar build/
3. Restart container: docker-compose restart moodle
```

## ✅ Production Checklist

- [ ] Binary built and copied to `build/` directory
- [ ] Environment variables configured
- [ ] Container starts successfully
- [ ] Sidecar process starts automatically
- [ ] Chat/Summarize/Analyze endpoints work
- [ ] PHP plugin communicates successfully
- [ ] Logs are accessible and readable
- [ ] Error handling works (fallback to stdio)
- [ ] Performance meets requirements




