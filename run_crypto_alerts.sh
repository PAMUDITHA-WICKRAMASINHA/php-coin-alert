#!/bin/bash

# Define the path to your Laravel project
LARAVEL_PROJECT_PATH="/home/assetmov/public_html"

# Define the path to the PHP binary
PHP_BINARY="/usr/local/bin/php"

# Run the Laravel command
$PHP_BINARY $LARAVEL_PROJECT_PATH/artisan crypto:get-alerts
