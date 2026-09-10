#!/bin/bash
set -e

# Render injects $PORT (usually 10000)
PORT="${PORT:-80}"
echo "Configuring Apache to listen on port ${PORT} for Render deployment..."

sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/:80/:${PORT}/g" /etc/apache2/sites-available/000-default.conf

# Execute Apache in foreground
exec apache2-foreground
