#!/bin/bash
echo "Waiting for MySQL to be ready..."
sleep 15  # يعطي وقت للـ DB يشتغل
echo "Running migrations..."
php artisan migrate --force
echo "Linking storage..."
php artisan storage:link
echo "Starting Laravel server..."
vendor/bin/heroku-php-apache2 public/
