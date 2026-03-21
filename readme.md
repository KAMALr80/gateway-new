# Development
php artisan queue:work --tries=3 --timeout=60

# Production (use supervisor to keep this running)
php artisan queue:work redis --tries=3 --timeout=60 --sleep=3