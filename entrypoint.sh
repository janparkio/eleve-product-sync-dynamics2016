#!/bin/bash

# Run your PHP scripts in the background
php /var/www/html/api/product-sync-md365.php &
php /var/www/html/api/product-sync-md2016.php &

# Start Apache in the foreground
apache2-foreground