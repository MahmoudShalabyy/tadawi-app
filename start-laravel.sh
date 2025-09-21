#!/bin/bash
echo "Waiting for MySQL to be ready..."
sleep 10

echo "Running migrations..."
php artisan migrate --force || { echo "Migration failed"; exit 1; }

echo "Linking storage..."
php artisan storage:link || { echo "Storage link failed"; exit 1; }

echo "Starting Laravel server..."
php artisan serve --host=0.0.0.0 --port=$PORT
