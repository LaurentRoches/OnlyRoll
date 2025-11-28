#!/bin/bash
set -e

echo "Starting PHP-FPM container..."

# ===========================================
# 1. Wait for MySQL to be ready
# ===========================================
echo "Waiting for MySQL..."

DB_HOST=$(echo $DATABASE_URL | sed -n 's/.*@\([^:]*\):.*/\1/p' || echo "mysql")
DB_PORT=$(echo $DATABASE_URL | sed -n 's/.*:\([0-9]*\)\/.*/\1/p' || echo "3306")

for i in {1..30}; do
    if mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" --silent 2>/dev/null; then
        echo "MySQL is ready"
        break
    fi
    
    if [ $i -eq 30 ]; then
        echo "MySQL is not accessible after 30 attempts"
        exit 1
    fi
    
    echo "Attempt $i/30 - MySQL not ready, waiting..."
    sleep 2
done

# ===========================================
# 2. Wait for Redis to be ready
# ===========================================
echo "Waiting for Redis..."

REDIS_HOST=$(echo $REDIS_URL | sed -n 's/redis:\/\/\([^:]*\).*/\1/p' || echo "redis")

for i in {1..15}; do
    if redis-cli -h "$REDIS_HOST" ping > /dev/null 2>&1; then
        echo "Redis is ready"
        break
    fi
    
    if [ $i -eq 15 ]; then
        echo "Redis is not accessible after 15 attempts"
        exit 1
    fi
    
    echo "Attempt $i/15 - Redis not ready, waiting..."
    sleep 2
done

# ===========================================
# 3. Check if vendor directory exists
# ===========================================
if [ ! -d "/var/www/html/vendor" ]; then
    echo "Vendor directory not found! This should not happen in production."
    echo "Running composer install as fallback..."
    composer install --no-dev --no-interaction --no-progress --optimize-autoloader
fi

# Check for API Platform specifically
if [ ! -d "/var/www/html/vendor/api-platform" ]; then
    echo "API Platform not found in vendor!"
    exit 1
fi

echo "Dependencies verified"

# ===========================================
# 4. Regenerate autoloader
# ===========================================
echo "Regenerating autoloader..."
composer dump-autoload --no-interaction --optimize --classmap-authoritative

# ===========================================
# 5. Create necessary directories
# ===========================================
echo "Creating necessary directories..."
mkdir -p var/cache var/log var/sessions public/uploads

# ===========================================
# 6. Clear and warmup Symfony cache
# ===========================================
if [ "$APP_ENV" = "prod" ]; then
    echo "Warming up production cache..."
    
    # Clear cache first
    rm -rf var/cache/prod/* 2>/dev/null || true
    
    # Regenerate cache
    php bin/console cache:clear --env=prod --no-debug || {
        echo "Cache clear failed, but continuing..."
    }
    
    php bin/console cache:warmup --env=prod --no-debug || {
        echo "Cache warmup failed, but continuing..."
    }
else
    echo "Clearing dev cache..."
    php bin/console cache:clear --no-interaction || true
fi

# ===========================================
# 7. Run database migrations
# ===========================================
echo "🗄️  Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || {
    echo "Migrations failed, continuing..."
}

# ===========================================
# 8. Display startup information
# ===========================================
echo "========================================"
echo "Environment: $APP_ENV"
echo "PHP Version: $(php -v | head -n 1)"
echo "Symfony: $(php bin/console --version | head -n 1)"
echo "========================================"
echo "Container ready!"

# Execute the main command (php-fpm)
exec "$@"