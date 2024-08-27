FROM php:8.2-fpm-alpine

# Install JavaScript tools (if needed)
# RUN apk add --no-cache nodejs npm

# Copy your project files
COPY . /app

# Set the working directory
WORKDIR /app

# Expose the web server port
EXPOSE 80

# Define the command to run the application
CMD ["php-fpm"]
