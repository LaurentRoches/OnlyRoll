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

# Check for API Platform specifically (only in production)
if [ "$APP_ENV" = "prod" ]; then
    if [ ! -d "/var/www/html/vendor/api-platform" ]; then
        echo "API Platform not found in vendor!"
        exit 1
    fi
    echo "Dependencies verified"
else
    echo "Development mode - skipping strict dependency check"
fi

# ===========================================
# 4. Regenerate autoloader
# ===========================================
# Autoloader already generated during build, no need to regenerate
# echo "Regenerating autoloader..."
# composer dump-autoload --no-interaction --optimize --classmap-authoritative

# ===========================================
# 5. Create necessary directories
# ===========================================
echo "Creating necessary directories..."
mkdir -p var/cache var/log var/sessions public/uploads

# Fix permissions for uploads directory (volume mount)
echo "Fixing permissions for uploads directory..."
chown -R www-data:www-data public/uploads
chmod -R 775 public/uploads

# ===========================================
# 6. Generate JWT keys if they don't exist
# ===========================================
if [ ! -f "config/jwt/private.pem" ] || [ ! -f "config/jwt/public.pem" ]; then
    echo "🔐 Generating JWT keys..."
    mkdir -p config/jwt

    # Generate private key
    openssl genpkey -algorithm RSA -out config/jwt/private.pem -pkeyopt rsa_keygen_bits:4096 -pass pass:"${JWT_PASSPHRASE}"

    # Generate public key from private key
    openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem -passin pass:"${JWT_PASSPHRASE}"

    # Set proper permissions
    chmod 644 config/jwt/private.pem config/jwt/public.pem

    echo "✅ JWT keys generated successfully"
else
    echo "✅ JWT keys already exist"
fi

# ===========================================
# 7. Clear and warmup Symfony cache
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
# 8. Run database migrations
# ===========================================
echo "🗄️  Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || {
    echo "Migrations failed, continuing..."
}

# ===========================================
# 9. Display startup information
# ===========================================
echo "========================================"
echo "Environment: $APP_ENV"
echo "PHP Version: $(php -v | head -n 1)"
echo "Symfony: $(php bin/console --version | head -n 1)"
echo "========================================"
echo "Container ready!"

# Execute the main command (php-fpm)
exec "$@"