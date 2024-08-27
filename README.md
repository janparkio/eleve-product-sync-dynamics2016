# Product Sync Application

This application contains scripts to synchronize product data between different systems.

## Requirements

- Docker

## Setup and Usage

1. Build the Docker image:
   ```
   docker build -t product-sync-app .
   ```

2. Run the Docker container:
   ```
   docker run product-sync-app
   ```

This will execute both `product-sync-md365.php` and `product-sync-md2016.php` scripts.

## Project Structure

- `api/`: Contains the main synchronization scripts and JSON data files.
- `backup/`: Stores backups of the products JSON file.
- `Dockerfile`: Defines the Docker image for the application.
- `products-admin.php`: Admin interface for products (not used in Docker setup).

## Note

This Docker setup is designed to run the sync scripts directly. If you need to run the `products-admin.php` file, you'll need to set up a web server environment separately.