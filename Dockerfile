# =====================================================
# COLLEGE MANAGEMENT SYSTEM - DOCKER IMAGE
# =====================================================
# Build with: docker build -t college-management .

FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    nodejs \
    npm \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY traditional-php/ /var/www/html/

# Copy configuration template and create config
COPY config_template.php /var/www/html/config.php

# Update configuration for Docker environment
RUN sed -i 's/localhost/db/g' /var/www/html/config.php && \
    sed -i 's/your_mysql_username/cms_user/g' /var/www/html/config.php && \
    sed -i 's/your_mysql_password/cms_password/g' /var/www/html/config.php && \
    sed -i 's|http://localhost.*|http://localhost:8080|g' /var/www/html/config.php

# Create necessary directories
RUN mkdir -p /var/www/html/uploads && \
    mkdir -p /var/www/html/logs && \
    chown -R www-data:www-data /var/www/html/uploads && \
    chown -R www-data:www-data /var/www/html/logs

# Copy Apache configuration
COPY .htaccess /var/www/html/.htaccess

# Enable Apache modules
RUN a2enmod rewrite headers

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]