#!/bin/bash

# Vérifie le certificat SSL réellement servi par chaque domaine.
# Échoue si un certificat est invalide (expiré, auto-signé, mauvais domaine)
# ou s'il expire dans moins de <min_days> jours.
# Usage: ./scripts/ssl-expiry-check.sh <min_days> <domain> [domain...]

if [ $# -lt 2 ]; then
    echo "Usage: $0 <min_days> <domain> [domain...]"
    exit 2
fi

MIN_DAYS="$1"
shift

STATUS=0

for DOMAIN in "$@"; do
    OUTPUT=$(echo | timeout 20 openssl s_client -connect "$DOMAIN:443" -servername "$DOMAIN" \
        -verify_hostname "$DOMAIN" 2>&1)
    CERT=$(echo "$OUTPUT" | openssl x509 2>/dev/null)

    if [ -z "$CERT" ]; then
        echo "✗ $DOMAIN: no certificate received (connection failed)"
        STATUS=1
        continue
    fi

    EXPIRY=$(echo "$CERT" | openssl x509 -noout -enddate 2>/dev/null | cut -d= -f2)
    VERIFY=$(echo "$OUTPUT" | grep -m1 'Verify return code' | sed 's/^ *Verify return code: //')

    if [ "$VERIFY" != "0 (ok)" ]; then
        echo "✗ $DOMAIN: invalid certificate - $VERIFY (expires $EXPIRY)"
        STATUS=1
    elif ! echo "$CERT" | openssl x509 -noout -checkend $((MIN_DAYS * 86400)) > /dev/null 2>&1; then
        echo "✗ $DOMAIN: expires in less than $MIN_DAYS days ($EXPIRY) - renewal is not working"
        STATUS=1
    else
        echo "✓ $DOMAIN: valid until $EXPIRY"
    fi
done

exit $STATUS
