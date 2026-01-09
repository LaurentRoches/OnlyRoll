#!/bin/bash

# Script to check SSL certificate status for onlyroll.cloud
# Usage: ./scripts/certbot-status.sh

set -e

DOMAIN="onlyroll.cloud"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}============================================${NC}"
echo -e "${BLUE}  SSL Certificate Status for OnlyRoll${NC}"
echo -e "${BLUE}============================================${NC}"
echo ""

# Check if certificate exists
echo -e "${YELLOW}Checking certificate existence...${NC}"
CERT_EXISTS=$(docker run --rm -v production_certbot-certs:/etc/letsencrypt alpine sh -c \
    "test -f /etc/letsencrypt/live/$DOMAIN/fullchain.pem && echo 'yes' || echo 'no'" 2>/dev/null)

if [ "$CERT_EXISTS" = "no" ]; then
    echo -e "${RED}✗ No certificate found!${NC}"
    echo ""
    echo "To obtain a certificate, run:"
    echo "   ./scripts/certbot-obtain.sh"
    echo ""
    exit 1
fi

echo -e "${GREEN}✓ Certificate exists${NC}"
echo ""

# Display certificate information
echo -e "${YELLOW}Certificate Information:${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# Subject
SUBJECT=$(docker run --rm -v production_certbot-certs:/etc/letsencrypt alpine sh -c \
    "openssl x509 -in /etc/letsencrypt/live/$DOMAIN/fullchain.pem -noout -subject 2>/dev/null" | sed 's/^subject=//')
echo -e "Subject:        ${GREEN}$SUBJECT${NC}"

# Issuer
ISSUER=$(docker run --rm -v production_certbot-certs:/etc/letsencrypt alpine sh -c \
    "openssl x509 -in /etc/letsencrypt/live/$DOMAIN/fullchain.pem -noout -issuer 2>/dev/null" | sed 's/^issuer=//')
echo -e "Issuer:         $ISSUER"

# Valid dates
NOT_BEFORE=$(docker run --rm -v production_certbot-certs:/etc/letsencrypt alpine sh -c \
    "openssl x509 -in /etc/letsencrypt/live/$DOMAIN/fullchain.pem -noout -startdate 2>/dev/null" | sed 's/^notBefore=//')
echo -e "Valid from:     $NOT_BEFORE"

NOT_AFTER=$(docker run --rm -v production_certbot-certs:/etc/letsencrypt alpine sh -c \
    "openssl x509 -in /etc/letsencrypt/live/$DOMAIN/fullchain.pem -noout -enddate 2>/dev/null" | sed 's/^notAfter=//')
echo -e "Valid until:    ${GREEN}$NOT_AFTER${NC}"

# Check how many days until expiry
EXPIRY_TIMESTAMP=$(date -d "$NOT_AFTER" +%s 2>/dev/null || echo "0")
CURRENT_TIMESTAMP=$(date +%s)
DAYS_UNTIL_EXPIRY=$(( ($EXPIRY_TIMESTAMP - $CURRENT_TIMESTAMP) / 86400 ))

echo ""
if [ $DAYS_UNTIL_EXPIRY -gt 30 ]; then
    echo -e "${GREEN}✓ Certificate is valid for $DAYS_UNTIL_EXPIRY more days${NC}"
elif [ $DAYS_UNTIL_EXPIRY -gt 0 ]; then
    echo -e "${YELLOW}⚠ Certificate expires in $DAYS_UNTIL_EXPIRY days - consider renewing${NC}"
else
    echo -e "${RED}✗ Certificate has expired!${NC}"
fi

# Check if it's a dummy/self-signed certificate
IS_SELF_SIGNED=$(docker run --rm -v production_certbot-certs:/etc/letsencrypt alpine sh -c \
    "openssl x509 -in /etc/letsencrypt/live/$DOMAIN/fullchain.pem -noout -text 2>/dev/null" | grep -q "Issuer:.*CN = $DOMAIN" && echo "yes" || echo "no")

if [ "$IS_SELF_SIGNED" = "yes" ]; then
    echo -e "${YELLOW}⚠ This is a self-signed/dummy certificate${NC}"
    echo ""
    echo "To obtain a real Let's Encrypt certificate, run:"
    echo "   ./scripts/certbot-obtain.sh --force-renewal"
fi

# Check if certificate will expire soon (less than 30 days)
if [ $DAYS_UNTIL_EXPIRY -le 30 ] && [ $DAYS_UNTIL_EXPIRY -gt 0 ]; then
    echo ""
    echo "To renew the certificate, run:"
    echo "   ./scripts/certbot-obtain.sh"
fi

echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# List all certificates
echo ""
echo -e "${YELLOW}All certificates in Certbot:${NC}"
docker run --rm -v production_certbot-certs:/etc/letsencrypt alpine sh -c \
    "ls -la /etc/letsencrypt/live/" 2>/dev/null || echo "Unable to list certificates"

echo ""

# Check Nginx is using the certificate
echo -e "${YELLOW}Checking Nginx configuration...${NC}"
if docker compose -f docker-compose.prod.yml ps | grep -q onlyroll-nginx-prod; then
    echo -e "${GREEN}✓ Nginx is running${NC}"

    # Test HTTPS connection
    echo ""
    echo -e "${YELLOW}Testing HTTPS connection...${NC}"
    if curl -s -o /dev/null -w "%{http_code}" --connect-timeout 5 https://$DOMAIN/health 2>/dev/null | grep -q "200"; then
        echo -e "${GREEN}✓ HTTPS is working! (HTTP 200)${NC}"
    else
        HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" --connect-timeout 5 https://$DOMAIN/ 2>/dev/null || echo "ERROR")
        if [ "$HTTP_CODE" != "ERROR" ]; then
            echo -e "${YELLOW}⚠ HTTPS connection returned: HTTP $HTTP_CODE${NC}"
        else
            echo -e "${RED}✗ Cannot connect via HTTPS${NC}"
        fi
    fi
else
    echo -e "${RED}✗ Nginx is not running${NC}"
    echo "Start services with: docker compose -f docker-compose.prod.yml up -d"
fi

echo ""
