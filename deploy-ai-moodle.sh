#!/bin/bash

# Moodle AI Plugin Deployment Script
# This script sets up and deploys Moodle with the AI Assistant plugin

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if Docker and Docker Compose are installed
check_dependencies() {
    info "Checking dependencies..."
    
    if ! command -v docker &> /dev/null; then
        error "Docker is not installed. Please install Docker first."
        exit 1
    fi
    
    if ! docker compose version &> /dev/null; then
        error "Docker Compose is not installed. Please install Docker Compose first."
        exit 1
    fi
    
    success "Dependencies check passed"
}

# Check environment configuration
check_environment() {
    info "Checking environment configuration..."
    
    if [ ! -f ".env" ]; then
        if [ -f ".env.example" ]; then
            warning ".env file not found. Creating from .env.example"
            cp .env.example .env
            warning "Please edit .env file with your OpenAI API key and other settings"
            warning "Especially set OPENAI_API_KEY to your actual API key"
        else
            error ".env.example file not found. Cannot create environment configuration."
            exit 1
        fi
    fi
    
    # Source the .env file
    if [ -f ".env" ]; then
        source .env
    fi
    
    # Check critical environment variables
    if [ -z "$OPENAI_API_KEY" ] || [ "$OPENAI_API_KEY" = "sk-your-openai-api-key-here" ]; then
        error "OPENAI_API_KEY is not set or using default value"
        error "Please set a valid OpenAI API key in your .env file"
        exit 1
    fi
    
    success "Environment configuration check passed"
}

# Validate AI plugin structure
validate_plugin() {
    info "Validating AI plugin structure..."
    
    if [ ! -d "plugins/local_gis_ai_assistant" ]; then
        error "GIS AI Assistant plugin not found at plugins/local_gis_ai_assistant"
        error "Please ensure the AI plugin is properly placed"
        exit 1
    fi
    
    # Check essential plugin files
    essential_files=(
        "plugins/local_gis_ai_assistant/version.php"
        "plugins/local_gis_ai_assistant/lib.php"
        "plugins/local_gis_ai_assistant/settings.php"
        "plugins/local_gis_ai_assistant/db/install.xml"
        "plugins/local_gis_ai_assistant/classes/api/inference_service.php"
    )
    
    for file in "${essential_files[@]}"; do
        if [ ! -f "$file" ]; then
            error "Essential plugin file missing: $file"
            exit 1
        fi
    done
    
    success "AI plugin structure validation passed"
}

# Set up Docker permissions
setup_permissions() {
    info "Setting up file permissions..."
    
    # Make scripts executable
    chmod +x docker/post-init-ai.sh
    
    # Set proper permissions for plugin files
    find plugins/local_gis_ai_assistant -type f -name "*.php" -exec chmod 644 {} \;
    find plugins/local_gis_ai_assistant -type f -name "*.js" -exec chmod 644 {} \;
    find plugins/local_gis_ai_assistant -type f -name "*.css" -exec chmod 644 {} \;
    find plugins/local_gis_ai_assistant -type d -exec chmod 755 {} \;
    
    success "File permissions set up"
}

# Deploy the application
deploy() {
    info "Starting Moodle deployment with AI plugin..."
    
    # Pull latest images (skip if network issues)
    info "Pulling Docker images..."
    if ! docker compose pull --ignore-pull-failures; then
        warning "Image pull failed, using existing local images"
        info "This is normal if you have network connectivity issues"
    fi
    
    # Build and start services
    info "Starting services..."
    docker compose up -d --build
    
    # Wait for services to be ready
    info "Waiting for services to start..."
    sleep 30
    
    # Check service health
    check_services_health
    
    success "Deployment completed!"
}

# Check services health
check_services_health() {
    info "Checking services health..."
    
    services=("mariadb" "redis" "minio" "moodle")
    
    for service in "${services[@]}"; do
        if docker compose ps "$service" | grep -q "Up"; then
            success "$service is running"
        else
            warning "$service may not be running properly"
        fi
    done
    
    # Test Moodle accessibility
    info "Testing Moodle accessibility..."
    sleep 10
    
    if curl -f -s http://localhost:8080/login/index.php > /dev/null; then
        success "Moodle is accessible at http://localhost:8080"
    else
        warning "Moodle may not be fully ready yet. Please wait a few more minutes."
    fi
}

# Show deployment information
show_info() {
    echo
    echo "======================================"
    echo "  Moodle AI Plugin Deployment Info"
    echo "======================================"
    echo
    echo "🌐 Moodle URL: http://localhost:8080"
    echo "👤 Admin Username: adorsys-gis"
    echo "🔑 Admin Password: adorsys-gis-password"
    echo
    echo "🤖 AI Assistant: http://localhost:8080/local/ai/"
    echo "📊 AI Analytics: http://localhost:8080/local/ai/analytics.php"
    echo
    echo "🗄️  Database (Adminer): http://localhost:18080"
    echo "📧 Mail (MailDev): http://localhost:1080"
    echo "📁 File Storage (MinIO): http://localhost:19000"
    echo
    echo "======================================"
    echo "  Useful Commands"
    echo "======================================"
    echo
    echo "View logs:"
    echo "  docker compose logs -f moodle"
    echo
    echo "Access Moodle container:"
    echo "  docker compose exec moodle bash"
    echo
    echo "Run AI plugin health check:"
    echo "  docker compose exec moodle php /bitnami/moodledata/ai_health_check.php"
    echo
    echo "Stop services:"
    echo "  docker compose down"
    echo
    echo "Update and restart:"
    echo "  docker compose down && docker compose up -d"
    echo
}

# Cleanup function
cleanup() {
    info "Cleaning up..."
    docker compose down
    docker system prune -f
    success "Cleanup completed"
}

# Main execution
main() {
    echo "======================================"
    echo "  Moodle AI Plugin Deployment"
    echo "======================================"
    echo
    
    case "${1:-deploy}" in
        "deploy")
            check_dependencies
            check_environment
            validate_plugin
            setup_permissions
            deploy
            show_info
            ;;
        "cleanup")
            cleanup
            ;;
        "info")
            show_info
            ;;
        "health")
            check_services_health
            ;;
        *)
            echo "Usage: $0 [deploy|cleanup|info|health]"
            echo
            echo "Commands:"
            echo "  deploy  - Deploy Moodle with AI plugin (default)"
            echo "  cleanup - Stop and clean up all services"
            echo "  info    - Show deployment information"
            echo "  health  - Check services health"
            exit 1
            ;;
    esac
}

# Run main function with all arguments
main "$@"
