#!/bin/bash
echo "Starting queue worker for ESP sync..."
php artisan queue:work --queue=esp-sync,default --sleep=3 --tries=3 --max-time=3600
