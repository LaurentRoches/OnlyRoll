#!/bin/bash

# Script to manually obtain or renew SSL certificates for onlyroll.cloud
# Usage: ./scripts/certbot-obtain.sh [--force-renewal]

set -e

COMPOSE_FILE="docker-compose.prod.yml"
DOMAIN="onlyroll.cloud"
WWW_DOMAIN="www.onlyroll.cloud"
EMAIL="support@onlyroll.cloud"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}============================================${NC}"
echo -e "${BLUE}  SSL Certificate Management for OnlyRoll${NC}"
echo -e "${BLUE}============================================${NC}"
echo ""

# Check if docker-compose.prod.yml exists
if [ ! -f "$COMPOSE_FILE" ]; then
    echo -e "${RED}Error: $COMPOSE_FILE not found!${NC}"
    echo "Please run this script from the project root directory."
    exit 1
fi

# Check if nginx and certbot services are running
echo -e "${YELLOW}[1/5]${NC} Checking services status..."
if ! docker compose -f $COMPOSE_FILE ps | grep -q onlyroll-nginx-prod; then
    echo -e "${RED}Error: Nginx service is not running!${NC}"
    echo "Please start the services first with: docker compose -f $COMPOSE_FILE up -d"
    exit 1
fi
echo -e "${GREEN}✓${NC} Services are running"

# Check if domain is accessible
echo -e "\n${YELLOW}[2/5]${NC} Checking domain accessibility..."
if curl -s -o /dev/null -w "%{http_code}" http://$DOMAIN/.well-known/acme-challenge/ | grep -q "404"; then
    echo -e "${GREEN}✓${NC} Domain is accessible (HTTP 404 is expected for empty challenge)"
else
    echo -e "${YELLOW}⚠${NC}  Domain may not be accessible. Continuing anyway..."
fi

# Check current certificate status
echo -e "\n${YELLOW}[3/5]${NC} Checking current certificate..."
CERT_EXISTS=$(docker run --rm -v production_certbot-certs:/etc/letsencrypt alpine sh -c \
    "test -f /etc/letsencrypt/live/$DOMAIN/fullchain.pem && echo 'yes' || echo 'no'")

if [ "$CERT_EXISTS" = "yes" ]; then
    echo -e "${GREEN}✓${NC} Certificate exists"

    # Check certificate expiry
    DAYS_VALID=$(docker run --rm -v production_certbot-certs:/etc/letsencrypt alpine sh -c \
        "openssl x509 -in /etc/letsencrypt/live/$DOMAIN/fullchain.pem -noout -enddate 2>/dev/null | cut -d= -f2")

    echo "   Valid until: $DAYS_VALID"

    # Check if it's a dummy certificate (valid for only 1 day)
    IS_DUMMY=$(docker run --rm -v production_certbot-certs:/etc/letsencrypt alpine sh -c \
        "openssl x509 -in /etc/letsencrypt/live/$DOMAIN/fullchain.pem -noout -checkend 86400 2>/dev/null && echo 'no' || echo 'yes'")

    if [ "$IS_DUMMY" = "yes" ]; then
        echo -e "${YELLOW}   ⚠ This appears to be a dummy/self-signed certificate${NC}"
    fi
else
    echo -e "${YELLOW}✓${NC} No certificate found - will obtain new one"
fi

# Determine if we should use --force-renewal
FORCE_RENEWAL=""
if [ "$1" = "--force-renewal" ]; then
    FORCE_RENEWAL="--force-renewal"
    echo -e "\n${YELLOW}Force renewal mode enabled${NC}"
fi

# Obtain or renew certificate
echo -e "\n${YELLOW}[4/5]${NC} Obtaining SSL certificate from Let's Encrypt..."
echo "   This may take a minute..."
echo ""

docker compose -f $COMPOSE_FILE run --rm certbot certonly \
    --webroot \
    --webroot-path=/var/www/certbot \
    --email $EMAIL \
    --agree-tos \
    --no-eff-email \
    $FORCE_RENEWAL \
    -d $DOMAIN \
    -d $WWW_DOMAIN && {
        echo ""
        echo -e "${GREEN}✓${NC} Certificate obtained successfully!"
    } || {
        echo ""
        echo -e "${RED}✗${NC} Failed to obtain certificate"
        echo ""
        echo -e "${YELLOW}Troubleshooting tips:${NC}"
        echo "1. Verify DNS is correctly pointing to your server:"
        echo "   nslookup $DOMAIN"
        echo ""
        echo "2. Check if port 80 is accessible from the internet"
        echo ""
        echo "3. View Nginx logs:"
        echo "   docker compose -f $COMPOSE_FILE logs nginx --tail=50"
        echo ""
        echo "4. Check Certbot logs:"
        echo "   docker compose -f $COMPOSE_FILE logs certbot --tail=50"
        echo ""
        exit 1
    }

# Reload Nginx
echo -e "\n${YELLOW}[5/5]${NC} Reloading Nginx to apply new certificate..."
docker compose -f $COMPOSE_FILE exec -T nginx nginx -s reload 2>/dev/null || \
    docker compose -f $COMPOSE_FILE restart nginx
echo -e "${GREEN}✓${NC} Nginx reloaded"

# Verify certificate
echo -e "\n${BLUE}============================================${NC}"
echo -e "${GREEN}Certificate successfully installed!${NC}"
echo -e "${BLUE}============================================${NC}"
echo ""
echo "Certificate details:"
docker run --rm -v production_certbot-certs:/etc/letsencrypt alpine sh -c \
    "openssl x509 -in /etc/letsencrypt/live/$DOMAIN/fullchain.pem -noout -subject -issuer -dates"

echo ""
echo -e "${GREEN}Your site is now secured with HTTPS!${NC}"
echo "Visit: https://$DOMAIN"
echo ""
