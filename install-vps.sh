#!/bin/bash

# VPS Installation Script for XSKT Phalcon Application
# This script installs all required software and dependencies

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   print_error "This script must be run as root (use sudo)"
   exit 1
fi

print_info "🚀 Starting VPS setup for XSKT Phalcon application..."

# Update system packages
print_status "Updating system packages..."
apt update && apt upgrade -y

# Install essential packages
print_status "Installing essential packages..."
apt install -y curl wget git unzip software-properties-common apt-transport-https ca-certificates gnupg lsb-release

# Add PHP repository (Ubuntu 24.10 compatible)
print_status "Adding PHP repository..."
# Check Ubuntu version
UBUNTU_VERSION=$(lsb_release -rs)
if [[ "$UBUNTU_VERSION" == "24.10" ]]; then
    print_warning "Ubuntu 24.10 detected, using alternative PHP repository..."
    # Use Sury repository directly for Ubuntu 24.10
    curl -fsSL https://packages.sury.org/php/apt.gpg | gpg --dearmor -o /usr/share/keyrings/php-archive-keyring.gpg
    echo "deb [signed-by=/usr/share/keyrings/php-archive-keyring.gpg] https://packages.sury.org/php/ ubuntu plucky main" > /etc/apt/sources.list.d/php.list
else
    add-apt-repository ppa:ondrej/php -y
fi
apt update

# Install PHP 8.1 and extensions
print_status "Installing PHP 8.1 and required extensions..."
apt install -y php8.1-fpm php8.1-cli php8.1-common php8.1-mysql php8.1-zip php8.1-gd php8.1-mbstring php8.1-curl php8.1-xml php8.1-bcmath php8.1-json php8.1-tokenizer php8.1-opcache php8.1-redis php8.1-memcached

# Install Nginx
print_status "Installing Nginx..."
apt install -y nginx

# Install MySQL client (if needed)
print_status "Installing MySQL client..."
apt install -y mysql-client

# Install Composer
print_status "Installing Composer..."
cd /tmp
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Install Node.js (for build tools)
print_status "Installing Node.js..."
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt install -y nodejs

# Install Redis (optional, for caching)
print_status "Installing Redis..."
apt install -y redis-server

# Configure PHP-FPM
print_status "Configuring PHP-FPM..."
cat > /etc/php/8.1/fpm/conf.d/99-custom.ini << 'EOF'
; Custom PHP configuration for XSKT Phalcon (optimized for 2GB RAM)
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
post_max_size = 32M
upload_max_filesize = 32M
max_file_uploads = 20

; OPcache settings (optimized for 2GB RAM)
opcache.enable = 1
opcache.enable_cli = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 4000
opcache.revalidate_freq = 0
opcache.fast_shutdown = 1
opcache.jit = 1
opcache.jit_buffer_size = 64

; Security settings
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off

; Error reporting for production
log_errors = On
display_errors = Off
display_startup_errors = Off
error_log = /var/log/php/error.log

; Session settings
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
EOF

# Create log directory for PHP
mkdir -p /var/log/php
chown www-data:www-data /var/log/php

# Configure PHP-FPM pool for 2GB RAM VPS
print_status "Configuring PHP-FPM pool for 2GB RAM..."
cat > /etc/php/8.1/fpm/pool.d/www.conf << 'EOF'
[www]
user = www-data
group = www-data
listen = /var/run/php/php8.1-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

; Process management (optimized for 2GB RAM)
pm = dynamic
pm.max_children = 20
pm.start_servers = 3
pm.min_spare_servers = 2
pm.max_spare_servers = 5
pm.max_requests = 1000

; Performance settings
pm.process_idle_timeout = 10s
request_terminate_timeout = 300s
request_slowlog_timeout = 5s

; Logging
access.log = /var/log/php/access.log
slowlog = /var/log/php/slow.log
catch_workers_output = yes

; Security
security.limit_extensions = .php
php_admin_value[error_log] = /var/log/php/error.log
php_admin_flag[log_errors] = on
php_value[session.save_handler] = files
php_value[session.save_path] = /var/lib/php/sessions
php_value[soap.wsdl_cache_dir] = /var/lib/php/wsdlcache
EOF

# Configure Nginx (optimized for 2GB RAM)
print_status "Configuring Nginx for 2GB RAM..."
cat > /etc/nginx/nginx.conf << 'EOF'
user www-data;
worker_processes auto;
pid /run/nginx.pid;

# Optimized for 2GB RAM
worker_rlimit_nofile 1024;
worker_connections 512;

events {
    use epoll;
    multi_accept on;
    worker_connections 512;
}

http {
    # Basic settings
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;
    server_tokens off;

    # Buffer sizes (optimized for 2GB RAM)
    client_body_buffer_size 16k;
    client_header_buffer_size 1k;
    large_client_header_buffers 2 1k;
    client_max_body_size 32m;

    # Timeouts
    client_body_timeout 12;
    client_header_timeout 12;
    send_timeout 10;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_comp_level 6;
    gzip_types
        text/plain
        text/css
        text/xml
        text/javascript
        application/javascript
        application/xml+rss
        application/json;

    # Logging
    access_log /var/log/nginx/access.log;
    error_log /var/log/nginx/error.log;

    # Include sites
    include /etc/nginx/conf.d/*.conf;
    include /etc/nginx/sites-enabled/*;
}
EOF

# Remove default site
rm -f /etc/nginx/sites-enabled/default

# Create project directory
print_status "Creating project directory..."
mkdir -p /var/www/xskt_phalcon
chown -R www-data:www-data /var/www/xskt_phalcon

# Create backup directory
mkdir -p /var/www/backups/xskt_phalcon
chown -R www-data:www-data /var/www/backups/xskt_phalcon

# Configure firewall (DigitalOcean optimized)
print_status "Configuring firewall..."
ufw --force enable
ufw allow ssh
ufw allow 'Nginx Full'
ufw allow 80
ufw allow 443

# DigitalOcean specific: Allow monitoring
ufw allow 8080  # For DigitalOcean monitoring

# Install SSL certificate tool (Let's Encrypt)
print_status "Installing Certbot for SSL certificates..."
apt install -y certbot python3-certbot-nginx

# Start and enable services
print_status "Starting and enabling services..."
systemctl start php8.1-fpm
systemctl enable php8.1-fpm
systemctl start nginx
systemctl enable nginx
systemctl start redis-server
systemctl enable redis-server

# Configure Redis (Anti-fragmentation optimized)
print_status "Configuring Redis with anti-fragmentation..."
cat > /etc/redis/redis.conf << 'EOF'
# Redis configuration optimized for XSKT Phalcon
# Network
bind 127.0.0.1
port 6379
timeout 300
tcp-keepalive 300

# Memory management (optimized for 2GB RAM VPS)
maxmemory 512mb
maxmemory-policy allkeys-lru
maxmemory-samples 5

# Anti-fragmentation settings (optimized for 2GB RAM)
activedefrag yes
active-defrag-ignore-bytes 50mb
active-defrag-threshold-lower 10
active-defrag-threshold-upper 100
active-defrag-cycle-min 1
active-defrag-cycle-max 25

# Persistence
save 900 1
save 300 10
save 60 10000
stop-writes-on-bgsave-error yes
rdbcompression yes
rdbchecksum yes
dbfilename dump.rdb
dir /var/lib/redis

# Logging
loglevel notice
logfile /var/log/redis/redis-server.log

# Performance
tcp-backlog 511
databases 16
always-show-logo yes

# Security
protected-mode yes
EOF

# Create Redis log directory
mkdir -p /var/log/redis
chown redis:redis /var/log/redis

# Restart Redis with new configuration
systemctl restart redis-server

# Install monitoring tools (optional)
print_status "Installing monitoring tools..."
apt install -y htop iotop nethogs

# Create systemd service for the application (if needed)
print_status "Creating systemd service..."
cat > /etc/systemd/system/xskt_phalcon.service << 'EOF'
[Unit]
Description=XSKT Phalcon Application
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/xskt_phalcon
ExecStart=/usr/bin/php -S 0.0.0.0:8000 -t public
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

# Reload systemd
systemctl daemon-reload

# Set up log rotation
print_status "Setting up log rotation..."
cat > /etc/logrotate.d/xskt_phalcon << 'EOF'
/var/log/nginx/xskt_phalcon_*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload nginx
    endscript
}

/var/log/php/error.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload php8.1-fpm
    endscript
}
EOF

# Create deployment user (non-root)
print_status "Creating deployment user..."
useradd -m -s /bin/bash deploy || print_warning "User 'deploy' already exists"
usermod -aG www-data deploy
usermod -aG sudo deploy

# Set up SSH key for deploy user (if not exists)
if [ ! -d /home/deploy/.ssh ]; then
    mkdir -p /home/deploy/.ssh
    chmod 700 /home/deploy/.ssh
    chown deploy:deploy /home/deploy/.ssh
fi

# Configure sudo for deploy user
echo "deploy ALL=(ALL) NOPASSWD: /bin/systemctl reload nginx, /bin/systemctl restart nginx, /bin/systemctl reload php8.1-fpm, /bin/systemctl restart php8.1-fpm" >> /etc/sudoers.d/deploy

# Final system optimization (DigitalOcean optimized)
print_status "Optimizing system settings..."
# Increase file limits
echo "* soft nofile 65536" >> /etc/security/limits.conf
echo "* hard nofile 65536" >> /etc/security/limits.conf

# DigitalOcean specific optimizations
cat >> /etc/sysctl.conf << 'EOF'
# Network optimizations
net.core.somaxconn = 65535
net.core.netdev_max_backlog = 5000
net.ipv4.tcp_max_syn_backlog = 65535
net.ipv4.tcp_fin_timeout = 10
net.ipv4.tcp_tw_reuse = 1
net.ipv4.tcp_timestamps = 1
net.ipv4.tcp_window_scaling = 1

# Memory optimizations
vm.swappiness = 10
vm.dirty_ratio = 15
vm.dirty_background_ratio = 5

# DigitalOcean specific
net.ipv4.ip_forward = 1
net.ipv4.conf.all.accept_redirects = 0
net.ipv4.conf.all.send_redirects = 0
EOF

# Apply sysctl changes
sysctl -p

print_status "🎉 VPS setup completed successfully!"
print_info "📋 Next steps:"
print_info "1. Clone your repository: git clone https://github.com/your-username/xskt_phalcon.git /var/www/xskt_phalcon"
print_info "2. Run the deployment script: ./deploy.sh production main"
print_info "3. Configure your domain in /etc/nginx/sites-available/xskt_phalcon"
print_info "4. Get SSL certificate: certbot --nginx -d your-domain.com"
print_info "5. Update .env file with production settings"

print_info "🔧 Useful commands:"
print_info "• Check Nginx status: systemctl status nginx"
print_info "• Check PHP-FPM status: systemctl status php8.1-fpm"
print_info "• Check logs: tail -f /var/log/nginx/xskt_phalcon_error.log"
print_info "• Restart services: systemctl restart nginx php8.1-fpm"

print_warning "⚠️  Remember to:"
print_warning "• Update your domain name in Nginx configuration"
print_warning "• Configure your database connection"
print_warning "• Set up proper SSL certificates"
print_warning "• Configure firewall rules for your specific needs"
