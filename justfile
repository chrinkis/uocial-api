dev:
    php artisan serve

lint:
    ./vendor/bin/phpstan analyse --memory-limit=2G

format:
    ./vendor/bin/pint
