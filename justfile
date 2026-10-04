dev:
    npx concurrently -c "#93c5fd,#c4b5fd" "php artisan serve" "php artisan queue:listen --tries=1" --names=server,queue --kill-others

lint:
    ./vendor/bin/phpstan analyse --memory-limit=2G

format:
    ./vendor/bin/pint
