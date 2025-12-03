#!/bin/bash
set -e

DOMAIN="onlyroll.cloud"
EMAIL="support@onlyroll.cloud"  # Changez cette adresse email

echo "🔐 Initializing SSL certificate for $DOMAIN"

# Check if certificate already exists
if [ -d "/etc/letsencrypt/live/$DOMAIN" ]; then
    echo "✅ SSL certificate already exists for $DOMAIN"
    exit 0
fi

echo "📝 Generating SSL certificate with Certbot..."

# Run certbot to obtain certificate
docker compose -f docker-compose.prod.yml run --rm certbot certonly \
    --webroot \
    --webroot-path=/var/www/certbot \
    --email "$EMAIL" \
    --agree-tos \
    --no-eff-email \
    -d "$DOMAIN" \
    -d "www.$DOMAIN"

if [ $? -eq 0 ]; then
    echo "✅ SSL certificate successfully generated for $DOMAIN"
    echo "🔄 Reloading Nginx..."
    docker compose -f docker-compose.prod.yml exec nginx nginx -s reload || true
else
    echo "❌ Failed to generate SSL certificate"
    exit 1
fi
