#!/bin/bash

# MONC Deployment Script for aaPanel
# This script automates the deployment process

set -e

echo "=========================================="
echo "MONC Deployment Script"
echo "=========================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
PROJECT_PATH="/www/wwwroot/monc.yourdomain.com"
PHP_BIN="/www/server/php/82/bin/php"
COMPOSER_BIN="/usr/bin/composer"

# Functions
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}→ $1${NC}"
}

check_requirements() {
    print_info "Checking requirements..."
    
    if [ ! -d "$PROJECT_PATH" ]; then
        print_error "Project directory not found: $PROJECT_PATH"
        exit 1
    fi
    
    if [ ! -f "$PHP_BIN" ]; then
        print_error "PHP binary not found: $PHP_BIN"
        exit 1
    fi
    
    if [ ! -f "$COMPOSER_BIN" ]; then
        print_error "Composer not found: $COMPOSER_BIN"
        exit 1
    fi
    
    print_success "All requirements met"
}

enable_maintenance_mode() {
    print_info "Enabling maintenance mode..."
    cd "$PROJECT_PATH"
    $PHP_BIN artisan down --retry=60
    print_success "Maintenance mode enabled"
}

disable_maintenance_mode() {
    print_info "Disabling maintenance mode..."
    cd "$PROJECT_PATH"
    $PHP_BIN artisan up
    print_success "Maintenance mode disabled"
}

pull_latest_code() {
    print_info "Pulling latest code from repository..."
    cd "$PROJECT_PATH"
    git pull origin main
    print_success "Code updated"
}

install_dependencies() {
    print_info "Installing Composer dependencies..."
    cd "$PROJECT_PATH"
    $COMPOSER_BIN install --optimize-autoloader --no-dev
    print_success "Dependencies installed"
}

run_migrations() {
    print_info "Running database migrations..."
    cd "$PROJECT_PATH"
    $PHP_BIN artisan migrate --force
    print_success "Migrations completed"
}

clear_cache() {
    print_info "Clearing application cache..."
    cd "$PROJECT_PATH"
    $PHP_BIN artisan cache:clear
    $PHP_BIN artisan config:clear
    $PHP_BIN artisan route:clear
    $PHP_BIN artisan view:clear
    print_success "Cache cleared"
}

optimize_application() {
    print_info "Optimizing application..."
    cd "$PROJECT_PATH"
    $PHP_BIN artisan config:cache
    $PHP_BIN artisan route:cache
    $PHP_BIN artisan view:cache
    $PHP_BIN artisan event:cache
    print_success "Application optimized"
}

restart_services() {
    print_info "Restarting services..."
    
    # Restart queue worker
    if command -v supervisorctl &> /dev/null; then
        supervisorctl restart monc-queue-worker
        print_success "Queue worker restarted"
    fi
    
    # Restart go2rtc
    if command -v pm2 &> /dev/null; then
        pm2 restart monc-go2rtc
        print_success "go2rtc restarted"
    fi
    
    # Restart PHP-FPM
    systemctl restart php-fpm-82
    print_success "PHP-FPM restarted"
}

set_permissions() {
    print_info "Setting correct permissions..."
    cd "$PROJECT_PATH"
    chown -R www:www .
    chmod -R 755 .
    chmod -R 775 storage bootstrap/cache
    print_success "Permissions set"
}

# Main deployment flow
main() {
    echo ""
    print_info "Starting deployment process..."
    echo ""
    
    check_requirements
    enable_maintenance_mode
    
    # Deployment steps
    pull_latest_code
    install_dependencies
    run_migrations
    clear_cache
    optimize_application
    set_permissions
    restart_services
    
    disable_maintenance_mode
    
    echo ""
    print_success "Deployment completed successfully!"
    echo ""
    print_info "Please verify:"
    echo "  - Website is accessible"
    echo "  - Live monitoring works"
    echo "  - Queue worker is running"
    echo "  - go2rtc is running"
    echo ""
}

# Run main function
main
