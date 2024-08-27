# Use PHP 8.2 Apache as base image
FROM php:8.2-apache

# Install necessary packages and PHP extensions
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    && docker-php-ext-install curl

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy the application code
COPY . /var/www/html

# Set working directory
WORKDIR /var/www/html

# Ensure Apache can write to the api directory if needed
RUN chown -R www-data:www-data /var/www/html/api

# Expose port 80
EXPOSE 80

# Copy entrypoint script and make it executable
COPY entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

# Set the entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]