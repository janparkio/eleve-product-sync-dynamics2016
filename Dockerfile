# Use PHP 8.2 CLI Alpine as base image
FROM php:8.2-cli-alpine

# Install necessary packages and PHP extensions
RUN apk add --no-cache \
    curl \
    libcurl \
    && docker-php-ext-install curl

# Copy the application code
COPY . /app

# Set working directory
WORKDIR /app

# Create a simple entry point script
RUN echo '#!/bin/sh' > /app/entrypoint.sh && \
    echo 'php /app/api/product-sync-md365.php' >> /app/entrypoint.sh && \
    echo 'php /app/api/product-sync-md2016.php' >> /app/entrypoint.sh && \
    chmod +x /app/entrypoint.sh

# Set the entry point
ENTRYPOINT ["/app/entrypoint.sh"]